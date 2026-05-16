<?php
session_start();
include("db.php");

$error = "";

if(isset($_POST['login']))
{
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    /* =========================
       FETCH ADMIN USER
    ========================= */

    $stmt = $conn->prepare(
        "SELECT * FROM users
         WHERE user=? AND role='admin'"
    );

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows === 1)
    {
        $row = $result->fetch_assoc();

        /* accept hashed OR plain password */
        if(password_verify($password,$row['pass']) || $password === $row['pass'])
        {
            /* SAME SESSION STRUCTURE (DO NOT CHANGE) */
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user']    = $row['user'];
            $_SESSION['role']    = $row['role'];

            header("Location: dashboard.php");
            exit();
        }
        else
        {
            $error = "Wrong Password";
        }
    }
    else
    {
        $error = "Admin not found";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Login – UNIDEL</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>

<?php include "header.php"; ?>

<div class="main-content d-flex align-items-center justify-content-center">

<div class="container d-flex justify-content-center mt-5">

<div class="card shadow p-4" style="width:420px;">

<h4 class="text-center mb-3">Admin Login</h4>

<?php if($error!=""){ ?>
<div class="alert alert-danger text-center">
<?php echo $error; ?>
</div>
<?php } ?>

<form method="POST">

<input
name="username"
class="form-control mb-3"
placeholder="Admin Username"
required>

<input
type="password"
name="password"
class="form-control mb-3"
placeholder="Password"
required>

<button name="login" class="btn btn-dark w-100">
Login
</button>
<div class="text-center mt-3">
        Student login?
        <a href="login.php" class="text-decoration-none fw-semibold">Click Here</a>
        <br>
        Academic Staff?
        <a href="staff_login.php" class="text-decoration-none fw-semibold">Staff Login</a>
</div>


</form>

</div>
</div>
</div>

<?php include "footer.php"; ?>

</body>
</html>
