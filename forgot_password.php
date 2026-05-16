<?php
include "db.php";

$msg = "";

if(isset($_POST['reset'])){

    $user    = trim($_POST['user']);
    $newpass = trim($_POST['newpass']);

    /* =========================
       CHECK USER EXISTS
    ========================= */

    $stmt = $conn->prepare(
        "SELECT id FROM users WHERE user=?"
    );

    $stmt->bind_param("s",$user);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows > 0){

        /* OPTIONAL: HASH PASSWORD
           (login supports hashed + plain)
        */
        $hashed = password_hash($newpass, PASSWORD_DEFAULT);

        $update = $conn->prepare(
            "UPDATE users SET pass=? WHERE user=?"
        );

        $update->bind_param("ss",$hashed,$user);
        $update->execute();

        $msg = "Password updated successfully!";
    }
    else{
        $msg = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Forgot Password – UNIDEL</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>

<?php include "header.php"; ?>

<div class="main-content d-flex align-items-center justify-content-center">

<div class="container d-flex justify-content-center mt-5">

<div class="card p-4 shadow" style="width:400px;">

<h4 class="text-center mb-3">Reset Password</h4>

<?php if($msg!=""){ ?>
<div class="alert alert-info">
<?php echo $msg; ?>
</div>
<?php } ?>

<form method="POST">

<input type="text"
name="user"
class="form-control mb-3"
placeholder="Enter Username"
required>

<input type="password"
name="newpass"
class="form-control mb-3"
placeholder="New Password"
required>

<button name="reset" class="btn btn-primary w-100">
Reset Password
</button>

</form>

<a href="login.php" class="btn btn-secondary w-100 mt-3">
Back to Login
</a>

</div>
</div>
</div>

<?php include "footer.php"; ?>

</body>
</html>
