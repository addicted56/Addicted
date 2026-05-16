<?php
session_start();
include "db.php";

/* =========================
   ADMIN CHECK
========================= */

if(!isset($_SESSION['role']) || $_SESSION['role']!="admin"){
    header("Location: login.php");
    exit();
}

/* =========================
   VALIDATE STUDENT ID
========================= */

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    die("Invalid student");
}

$student_id = (int)$_GET['id'];


/* =========================
   UPDATE STUDENT + MARKS
========================= */

if(isset($_POST['update'])){

    $name   = trim($_POST['name']);
    $roll   = trim($_POST['roll_no']);
    $branch = trim($_POST['branch']);
    $year   = (int)$_POST['year'];

    /* UPDATE PROFILE */
    $update = $conn->prepare("
        UPDATE students
        SET name=?, roll_no=?, branch=?, year=?
        WHERE id=?
    ");

    $update->bind_param(
        "sssii",
        $name,
        $roll,
        $branch,
        $year,
        $student_id
    );

    $update->execute();


    /* UPDATE SEMESTER MARKS */
    for($i=1;$i<=6;$i++){

        $marks = isset($_POST["sem$i"])
            ? (int)$_POST["sem$i"]
            : 0;

        /* SGPA CALCULATION */
        $sgpa = round(($marks/1000)*10,2);

        $stmt = $conn->prepare("
            UPDATE semester_marks
            SET obtained_marks=?, sgpa=?
            WHERE student_id=? AND semester=?
        ");

        $stmt->bind_param(
            "idii",
            $marks,
            $sgpa,
            $student_id,
            $i
        );

        $stmt->execute();
    }

    header("Location: dashboard.php");
    exit();
}


/* =========================
   FETCH STUDENT DATA
========================= */

$studentStmt = $conn->prepare(
    "SELECT * FROM students WHERE id=?"
);

$studentStmt->bind_param("i",$student_id);
$studentStmt->execute();

$student = $studentStmt
            ->get_result()
            ->fetch_assoc();

if(!$student){
    die("Student not found");
}


/* =========================
   FETCH EXISTING MARKS
========================= */

$semData = [];

$semQuery = $conn->prepare("
    SELECT semester, obtained_marks
    FROM semester_marks
    WHERE student_id=?
");

$semQuery->bind_param("i",$student_id);
$semQuery->execute();

$result = $semQuery->get_result();

while($row = $result->fetch_assoc()){
    $semData[(int)$row['semester']]
        = $row['obtained_marks'];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Student - UNIDEL</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>

<body class="d-flex flex-column min-vh-100">

<?php include "header.php"; ?>

<div class="container mt-5">
<div class="row justify-content-center">
<div class="col-lg-8">

<div class="card shadow">
<div class="card-body p-4">

<h4 class="mb-4 text-center">
Edit Student: <?= htmlspecialchars($student['name']) ?>
</h4>

<form method="POST">

<div class="row g-3">

<div class="col-md-6">
<label class="form-label">Student Name</label>
<input type="text" name="name"
class="form-control"
value="<?= htmlspecialchars($student['name']) ?>" required>
</div>

<div class="col-md-6">
<label class="form-label">Roll Number</label>
<input type="text" name="roll_no"
class="form-control"
value="<?= htmlspecialchars($student['roll_no']) ?>" required>
</div>

<div class="col-md-6">
<label class="form-label">Branch</label>
<input type="text" name="branch"
class="form-control"
value="<?= htmlspecialchars($student['branch']) ?>">
</div>

<div class="col-md-6">
<label class="form-label">Year</label>
<input type="number" name="year"
class="form-control"
value="<?= htmlspecialchars($student['year']) ?>">
</div>

</div>

<hr class="my-4">

<h5 class="text-center mb-3">Academic Performance</h5>

<div class="row g-3">

<?php for($i=1;$i<=6;$i++): ?>

<div class="col-md-6">
<label class="form-label">
Semester <?= $i ?> (Out of 1000)
</label>

<input type="number"
name="sem<?= $i ?>"
class="form-control"
max="1000"
value="<?= $semData[$i] ?? '' ?>">
</div>

<?php endfor; ?>

</div>

<div class="d-flex justify-content-end gap-2 mt-4">

<a href="dashboard.php" class="btn btn-secondary">
Cancel
</a>

<button type="submit"
name="update"
class="btn btn-primary">
Update Student
</button>

</div>

</form>

</div>
</div>

</div>
</div>
</div>

<?php include "footer.php"; ?>

</body>
</html>
