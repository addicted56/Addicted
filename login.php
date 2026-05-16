<?php
session_start();
include "db.php";

$error = "";

if(isset($_POST['login'])){

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    /* =========================
       FETCH USER
    ========================= */

    $stmt = $conn->prepare(
        "SELECT id, user, pass, role, must_change_password
         FROM users
         WHERE user=? AND role='student'"
    );

    $stmt->bind_param("s",$username);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows === 1){

        $data = $result->fetch_assoc();

        /* Accept hashed OR plain password */
        if(password_verify($password,$data['pass']) || $password === $data['pass']){

            /* SESSION FORMAT (USED EVERYWHERE) */
            $_SESSION['user_id'] = $data['id'];
            $_SESSION['user']    = $data['user'];
            $_SESSION['role']    = $data['role'];
            $_SESSION['must_change_password'] = $data['must_change_password'];


            /* ROLE REDIRECT */
            if($data['role'] === "admin"){
    header("Location: dashboard.php");
    exit();
}

/* FORCE PASSWORD CHANGE */
if($data['must_change_password'] == 1){
    header("Location: change_password.php");
    exit();
}

header("Location: student_dashboard.php");
exit();

        }
    }

    $error = "Student not found or wrong password. For admins use admin login.";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Student Login – UNIDEL</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="d-flex flex-column min-vh-100">

<?php include "header.php"; ?>

<div class="main-content d-flex align-items-center justify-content-center">

<div class="card shadow p-4" style="width:400px;">

<h4 class="text-center mb-3 text-dark">Student Login</h4>

<?php if($error!=""){ ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php } ?>

<form method="POST">

<input
type="text"
name="username"
class="form-control mb-3"
placeholder="Username"
required>

<input
type="password"
name="password"
class="form-control mb-3"
placeholder="Password"
required>

<button type="submit" name="login"
class="btn btn-primary w-100">
Login
</button>

</form>

<p class="mt-3 text-center">

Forgot Password?
<a href="forgot_password.php">Click here</a><br>

New user?
<a href="register.php">Register</a><br>

Academic Staff?
<a href="staff_login.php">Staff Login</a><br>

Admin login?
<a href="admin_login.php">Click Here</a>

</p>

</div>
</div>

<?php include "footer.php"; ?>

</body>
</html>
