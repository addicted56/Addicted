<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

if($_SESSION['role'] === 'admin'){
    header("Location: dashboard.php");
}elseif($_SESSION['role'] === 'staff'){
    header("Location: staff_dashboard.php");
}else{
    header("Location: student_dashboard.php");
}

exit();
?>
