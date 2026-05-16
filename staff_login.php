<?php
session_start();
include("db.php");

$error = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare(
        "SELECT id, user, pass, role, must_change_password
         FROM users
         WHERE user=? AND role='staff'"
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['pass']) || $password === $row['pass']) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user']    = $row['user'];
            $_SESSION['role']    = $row['role'];

            /* get staff profile id */
            $sp = $conn->prepare("SELECT id, full_name FROM staff WHERE user_id=?");
            $sp->bind_param("i", $row['id']);
            $sp->execute();
            $staffRow = $sp->get_result()->fetch_assoc();
            if ($staffRow) {
                $_SESSION['staff_db_id'] = $staffRow['id'];
                $_SESSION['staff_name']  = $staffRow['full_name'];
            }

            if ($row['must_change_password'] == 1) {
                header("Location: change_password.php");
                exit();
            }
            header("Location: staff_dashboard.php");
            exit();
        } else {
            $error = "Wrong Password";
        }
    } else {
        $error = "Staff account not found";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Staff Login – UNIDEL</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>

<body class="d-flex flex-column min-vh-100">

<?php include "header.php"; ?>

<div class="main-content d-flex align-items-center justify-content-center">
<div class="card shadow p-4" style="width:420px;">

<h4 class="text-center mb-3">
<i class="bi bi-person-badge"></i> Academic Staff Login
</h4>

<?php if ($error != "") { ?>
<div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
<?php } ?>

<form method="POST">

<input name="username"
       class="form-control mb-3"
       placeholder="Staff Username"
       required>

<input type="password"
       name="password"
       class="form-control mb-3"
       placeholder="Password"
       required>

<button name="login" class="btn btn-success w-100">
Login as Staff
</button>

</form>

<div class="text-center mt-3">
    Student login? <a href="login.php">Click Here</a><br>
    Admin login? <a href="admin_login.php">Click Here</a>
</div>

</div>
</div>

<?php include "footer.php"; ?>

</body>
</html>
