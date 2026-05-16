<?php
session_start();

if(
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'student'
){
    header("Location: login.php");
    exit();
}
if(isset($_SESSION['must_change_password']) &&
   $_SESSION['must_change_password']==1){
    header("Location: change_password.php");
    exit();
}

include "db.php";

$user_id = $_SESSION['user_id'];

/* =========================
   FETCH STUDENT PROFILE
========================= */

$stmt = $conn->prepare(
    "SELECT * FROM students WHERE user_id=?"
);
$stmt->bind_param("i",$user_id);
$stmt->execute();

$result = $stmt->get_result();
$student = $result->fetch_assoc();

if(!$student){
    die("Student record not found.");
}

$student_id = $student['id'];

/* =========================
   FETCH SEMESTER MARKS
========================= */

$semData = [];
$totalMarks = 0;

$query = $conn->prepare("
    SELECT semester, obtained_marks, sgpa
    FROM semester_marks
    WHERE student_id=?
    ORDER BY semester
");

$query->bind_param("i",$student_id);
$query->execute();

$res = $query->get_result();

while($row = $res->fetch_assoc()){
    $sem = (int)$row['semester'];
    $semData[$sem] = $row;
    $totalMarks += (int)$row['obtained_marks'];
}

/* =========================
   CGPA CALCULATION
========================= */

$cgpaQuery = $conn->prepare("
    SELECT ROUND(AVG(sgpa),2) AS cgpa
    FROM semester_marks
    WHERE student_id=?
");

$cgpaQuery->bind_param("i",$student_id);
$cgpaQuery->execute();

$cgpaResult = $cgpaQuery->get_result()->fetch_assoc();
$cgpa = $cgpaResult['cgpa'] ?? 0;

/* =========================
   ATTENDANCE
========================= */

$attendance = 0;

$attQuery = $conn->prepare("
    SELECT attendance_percent
    FROM attendance
    WHERE student_id=?
");

$attQuery->bind_param("i",$student_id);
$attQuery->execute();

$attRes = $attQuery->get_result()->fetch_assoc();

if($attRes){
    $attendance = $attRes['attendance_percent'];
}

$attendanceWarning = ($attendance <= 10.99);

if($attendance < 65){
    $barColor = "bg-danger";
}elseif($attendance <= 75){
    $barColor = "bg-warning";
}else{
    $barColor = "bg-success";
}
?>
<!DOCTYPE html>
<html>
<head>

<title>Student Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">

</head>

<body>

<?php include "header.php"; ?>

<div class="container mt-4">

<div class="page-banner mb-3">
<h4 class="mb-1">Welcome Back, <?= htmlspecialchars($student['name']); ?></h4>
<p class="mb-0">Track your progress, manage your courses, and keep your record ready for final-year presentation.</p>
</div>

<div class="d-flex justify-content-end gap-2 flex-wrap">

<a href="course_registration.php" class="btn btn-primary shadow-sm">
<i class="bi bi-journal-plus"></i> Course Registration
</a>

<a href="transcript.php" class="btn btn-info shadow-sm text-white">
<i class="bi bi-file-earmark-text"></i> View Transcript
</a>

<a href="download_pdf.php" class="btn btn-success shadow-sm">
Download Report
</a>

<a href="logout.php" class="btn btn-danger shadow-sm">
Logout
</a>

</div>
</div>

<div class="container mt-4">
<div class="dashboard-card p-4">

<h3 class="mb-4">
Student Profile Overview
</h3>

<!-- BASIC INFO -->
<div class="row g-4">

<div class="col-md-4">
<div class="info-box">
Roll Number
<h4><?php echo htmlspecialchars($student['roll_no']); ?></h4>
</div>
</div>

<div class="col-md-4">
<div class="info-box">
Branch
<h4><?php echo htmlspecialchars($student['branch']); ?></h4>
</div>
</div>

<div class="col-md-4">
<div class="info-box">
Year
<h4><?php echo htmlspecialchars($student['year']); ?></h4>
</div>
</div>

</div>

<!-- ATTENDANCE -->
<div class="row justify-content-center mt-4 g-3">
<div class="col-md-5">
<div class="media-tile h-100">
<img src="https://picsum.photos/id/1005/900/620" alt="Student study desk">
</div>
</div>
<div class="col-md-6">

<div class="info-box text-center">

<span>Attendance</span>
<h4 class="highlight-text"><?php echo $attendance; ?>%</h4>

<div class="progress mx-auto" style="max-width:300px;">
<div class="progress-bar <?php echo $barColor; ?>"
     style="width: <?php echo $attendance; ?>%;">
</div>
</div>

<?php if($attendanceWarning): ?>
<div class="alert alert-danger mt-3 hod-warning">
<strong>Warning!</strong><br>
Attendance critically low. Contact your HOD immediately.
</div>
<?php endif; ?>

</div>
</div>
</div>

<hr class="my-5">

<h4 class="text-center mb-4">Academic Performance</h4>

<div class="row g-4 justify-content-center">

<?php for($i=1;$i<=6;$i++): 
    $marks = $semData[$i]['obtained_marks'] ?? 0;
    $sgpa  = $semData[$i]['sgpa'] ?? 0;
?>

<div class="col-lg-3 col-md-4 col-sm-6">
<div class="info-box">

Semester <?php echo $i; ?>

<h5><?php echo $marks; ?>/1000</h5>

<small>SGPA: <?php echo $sgpa; ?></small>

</div>
</div>

<?php endfor; ?>

</div>

<!-- FINAL RESULT -->
<div class="row justify-content-center mt-5">
<div class="col-md-4">
<div class="info-box bg-success">

Total Marks<br>
<h4><?php echo $totalMarks; ?> / 6000</h4>

Final CGPA
<h3><?php echo $cgpa; ?></h3>

</div>
</div>
</div>

</div>
</div>

<?php include "footer.php"; ?>

</body>
</html>
