<?php
session_start();
include "db.php";

if($_SESSION['role']!="admin"){
    header("Location: login.php");
    exit();
}

$student_id = (int)$_GET['id'];

if(isset($_POST['save'])){

    $attendance = $_POST['attendance'];

    $stmt=$conn->prepare("
    INSERT INTO attendance(student_id,attendance_percent)
    VALUES(?,?)
    ON DUPLICATE KEY UPDATE
    attendance_percent=VALUES(attendance_percent)
    ");

    $stmt->bind_param("id",$student_id,$attendance);
    $stmt->execute();

    header("Location: dashboard.php");
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Attendance - UNIDEL</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>

<body class="d-flex flex-column min-vh-100">
<?php include "header.php"; ?>

<div class="container mt-5">
<div class="card p-4 shadow">

<h4>Add Attendance</h4>

<form method="POST">

<input type="number"
step="0.01"
name="attendance"
class="form-control mb-3"
placeholder="Attendance %"
required>

<button name="save" class="btn btn-warning">
Save Attendance
</button>

</form>

</div>
</div>

<?php include "footer.php"; ?>

</body>
</html>
