<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if(isset($_POST['change'])){

    $newpass = $_POST['new_password'];
    $hash = password_hash($newpass, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        UPDATE users
        SET pass=?, must_change_password=0
        WHERE id=?
    ");

    $stmt->bind_param("si",$hash,$user_id);
    $stmt->execute();

    /* Redirect based on role */
    if ($_SESSION['role'] === 'staff') {
        header("Location: staff_dashboard.php");
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: student_dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Change Password - UNIDEL</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>

<body class="d-flex flex-column min-vh-100">

<?php include "header.php"; ?>

<div class="container mt-5">
<div class="row justify-content-center">
<div class="col-md-5">

<div class="card shadow p-4">

<h4 class="text-center mb-3">Change Password</h4>

<form method="POST">

<label class="form-label">New Password</label>
<input type="password"
name="new_password"
class="form-control mb-3"
required>

<button name="change"
class="btn btn-primary w-100">
Update Password
</button>

</form>

</div>
</div>
</div>
</div>

<?php include "footer.php"; ?>

</body>
</html>
