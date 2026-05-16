<?php
session_start();

if(
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
){
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard – UNIDEL</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

</head>

<body>

<?php include "header.php"; ?>

<div class="container mt-5">

<div class="card shadow p-4">

<!-- TOP BAR -->
<div class="d-flex justify-content-between align-items-center mb-3">

<div>
<h4 class="mb-0">Admin Dashboard</h4>

<small class="text-muted">
Welcome <?= $_SESSION['user']; ?>

<?php if($_SESSION['role']=="admin"){ ?>
<span class="badge bg-danger ms-2">ADMIN</span>
<?php } ?>

</small>
</div>

<div class="d-flex gap-2">

<a href="manage_courses.php" class="btn btn-info text-white">
<i class="bi bi-journal-text"></i> Courses
</a>

<a href="manage_staff.php" class="btn btn-warning">
<i class="bi bi-people"></i> Staff
</a>

<a href="download_pdf.php" class="btn btn-success">
<i class="bi bi-download"></i> Download Report
</a>

<a href="logout.php" class="btn btn-danger">
Logout
</a>

</div>

</div>

<!-- SEARCH -->
<div class="input-group mb-4">

<span class="input-group-text">
<i class="bi bi-search"></i>
</span>

<input id="search"
class="form-control"
placeholder="Search student...">

</div>

<!-- ADD STUDENT -->
<form id="studentForm" class="row g-2">

<div class="col-md-3">
<input name="name" class="form-control" placeholder="Name" required>
</div>

<div class="col-md-2">
<input name="roll" class="form-control" placeholder="Roll No" required>
</div>

<div class="col-md-3">
<input name="branch" class="form-control" placeholder="Branch" required>
</div>

<div class="col-md-2">
<input type="number" name="year" class="form-control" placeholder="Year" required>
</div>

<div class="col-md-2">
<button class="btn btn-primary w-100">Add</button>
</div>

</form>

<hr>

<!-- STUDENT LIST -->
<div id="studentTable"></div>

</div>
</div>

<script>

/* ---------- LOAD STUDENTS ---------- */
function loadStudents(){
fetch("fetch_students.php")
.then(r=>r.text())
.then(d=>{
document.getElementById("studentTable").innerHTML=d;
});
}

loadStudents();

/* ---------- ADD STUDENT ---------- */
document.getElementById("studentForm").onsubmit=function(e){

e.preventDefault();

fetch("add_student.php",{
method:"POST",
body:new FormData(this)
})
.then(()=>{
this.reset();
loadStudents();
});
};

/* ---------- SEARCH ---------- */
document.getElementById("search").onkeyup=function(){

fetch("search.php?q="+this.value)
.then(r=>r.text())
.then(d=>{
document.getElementById("studentTable").innerHTML=d;
});

};

</script>

<?php include "footer.php"; ?>

</body>
</html>
