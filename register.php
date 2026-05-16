<?php
session_start();
include("db.php");

$msg = "";

if(isset($_POST['register']))
{
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    /* =========================
       CHECK USER EXISTS
    ========================= */

    $check = $conn->prepare(
        "SELECT id FROM users WHERE user=?"
    );

    $check->bind_param("s",$username);
    $check->execute();
    $result = $check->get_result();

    if($result->num_rows > 0){
        $msg = "Username already exists!";
    }
    else{

        /* HASH PASSWORD */
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        /* INSERT STUDENT USER */
        $stmt = $conn->prepare(
            "INSERT INTO users(user,pass,role)
             VALUES(?, ?, 'student')"
        );

        $stmt->bind_param("ss",$username,$hashed);

        if($stmt->execute()){
            $msg = "Registration Successful!";
        }else{
            $msg = "Error occurred!";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Register – UNIDEL</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="d-flex flex-column min-vh-100">

<?php include "header.php"; ?>

<div class="main-content d-flex align-items-center justify-content-center">

<div class="card shadow-lg p-4" style="width:420px;">

<h4 class="text-center mb-3 text-dark">
Student Registration
</h4>

<?php if($msg!=""){ ?>
<div class="alert alert-info text-center">
<?= $msg ?>
</div>
<?php } ?>

<form method="POST">

<div class="mb-3">
<label class="form-label">Username</label>
<input
type="text"
name="username"
class="form-control"
placeholder="Enter username"
required>
</div>

<div class="mb-3">
<label class="form-label">Password</label>
<input
type="password"
name="password"
class="form-control"
placeholder="Create password"
required>
</div>

<button name="register" class="btn btn-primary w-100">
Create Account
</button>

</form>

<p class="text-center mt-3">
Already have account?
<a href="login.php">Login</a>
</p>

</div>
</div>

<?php include "footer.php"; ?>

</body>
</html>
