<?php
session_start();
include "db.php";

if($_SESSION['role']!="admin"){
    header("Location: login.php");
    exit();
}

$student_id = (int)$_GET['id'];

if(isset($_POST['save'])){

    for($i=1;$i<=6;$i++){

        $marks = (int)$_POST["sem$i"];

        if($marks>0){

            // SGPA calculation
            $sgpa = round(($marks/1000)*10,2);

            $stmt = $conn->prepare("
            INSERT INTO semester_marks
            (student_id,semester,obtained_marks,sgpa)
            VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE
            obtained_marks=VALUES(obtained_marks),
            sgpa=VALUES(sgpa)
            ");

            $stmt->bind_param("iiid",
                $student_id,$i,$marks,$sgpa
            );
            $stmt->execute();
        }
    }

    header("Location: dashboard.php");
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Marks - UNIDEL</title>
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

<div class="card shadow p-4">

<h4 class="text-center mb-4">Add Semester Marks</h4>

<form method="POST">

<div class="row g-3">

<?php for($i=1;$i<=6;$i++): ?>

<div class="col-md-6">

<label class="form-label fw-semibold">
Semester <?= $i ?> (Out of 1000)
</label>

<input
type="number"
name="sem<?= $i ?>"
class="form-control"
max="1000"
placeholder="Enter marks">

</div>

<?php endfor; ?>

</div>

<!-- Buttons -->
<div class="d-flex justify-content-end mt-4 gap-2">

<a href="dashboard.php" class="btn btn-secondary">
Cancel
</a>

<button type="submit" name="save" class="btn btn-primary">
Save Marks
</button>

</div>

</form>

</div>
</div>

</div>
</div>

<?php include "footer.php"; ?>

</body>
</html>
