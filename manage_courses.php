<?php
session_start();
include "db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$msg = "";

/* =========================
   ADD COURSE
========================= */
if (isset($_POST['add_course'])) {
    $code    = strtoupper(trim($_POST['course_code']));
    $title   = trim($_POST['course_title']);
    $units   = (int)$_POST['credit_units'];
    $sem     = (int)$_POST['semester'];
    $level   = (int)$_POST['level'];
    $dept_id = (int)$_POST['dept_id'];

    $stmt = $conn->prepare("
        INSERT INTO courses (course_code, course_title, credit_units, semester, level, dept_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssiiii", $code, $title, $units, $sem, $level, $dept_id);

    if ($stmt->execute()) {
        $msg = "Course <strong>$code</strong> added successfully!";
    } else {
        $msg = "Error: Course code may already exist.";
    }
}

/* =========================
   DELETE COURSE
========================= */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del = (int)$_GET['delete'];
    $dStmt = $conn->prepare("DELETE FROM courses WHERE id=?");
    $dStmt->bind_param("i", $del);
    $dStmt->execute();
    header("Location: manage_courses.php");
    exit();
}

/* =========================
   FETCH ALL COURSES
========================= */
$courses = [];
$cq = $conn->query("
    SELECT c.*, d.dept_name, d.dept_code
    FROM courses c
    JOIN departments d ON c.dept_id = d.id
    ORDER BY c.level, c.semester, c.course_code
");
while ($row = $cq->fetch_assoc()) {
    $courses[] = $row;
}

/* Departments */
$departments = [];
$dq = $conn->query("SELECT * FROM departments ORDER BY dept_name");
while ($row = $dq->fetch_assoc()) {
    $departments[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Manage Courses – UNIDEL Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="styles.css">
</head>

<body class="d-flex flex-column min-vh-100">

<?php include "header.php"; ?>

<div class="container mt-4 flex-fill">

<div class="mb-3">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
</div>

<?php if ($msg) { ?>
<div class="alert alert-info"><?= $msg ?></div>
<?php } ?>

<!-- ADD COURSE -->
<div class="card shadow mb-4">
<div class="card-body">
    <h5><i class="bi bi-journal-plus"></i> Add New Course</h5>

    <form method="POST" class="row g-3 mt-2">
        <div class="col-md-2">
            <label class="form-label">Course Code</label>
            <input type="text" name="course_code" class="form-control" placeholder="CSC 101" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Course Title</label>
            <input type="text" name="course_title" class="form-control" placeholder="Introduction to Computer Science" required>
        </div>
        <div class="col-md-1">
            <label class="form-label">Units</label>
            <input type="number" name="credit_units" class="form-control" value="3" min="1" max="6" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Semester</label>
            <select name="semester" class="form-select" required>
                <option value="1">1st Semester</option>
                <option value="2">2nd Semester</option>
            </select>
        </div>
        <div class="col-md-1">
            <label class="form-label">Level</label>
            <select name="level" class="form-select" required>
                <option value="100">100</option>
                <option value="200">200</option>
                <option value="300">300</option>
                <option value="400">400</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Department</label>
            <select name="dept_id" class="form-select" required>
                <?php foreach ($departments as $d) { ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['dept_code']) ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-12">
            <button name="add_course" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Course
            </button>
        </div>
    </form>
</div>
</div>

<!-- COURSE LIST -->
<div class="card shadow">
<div class="card-body">
    <h5><i class="bi bi-journal-text"></i> All Courses (<?= count($courses) ?>)</h5>

    <?php if (empty($courses)) { ?>
    <div class="alert alert-info mt-3">No courses added yet.</div>
    <?php } else { ?>
    <div class="table-responsive mt-3">
    <table class="table table-bordered table-hover align-middle">
    <thead class="table-dark">
    <tr>
        <th>#</th><th>Code</th><th>Course Title</th><th>Units</th>
        <th>Level</th><th>Semester</th><th>Dept</th><th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($courses as $i => $c) { ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td><strong><?= htmlspecialchars($c['course_code']) ?></strong></td>
        <td><?= htmlspecialchars($c['course_title']) ?></td>
        <td><?= $c['credit_units'] ?></td>
        <td><?= $c['level'] ?></td>
        <td><?= $c['semester'] ?></td>
        <td><?= htmlspecialchars($c['dept_code']) ?></td>
        <td>
            <a href="manage_courses.php?delete=<?= $c['id'] ?>"
               class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Delete this course?')">
               <i class="bi bi-trash"></i>
            </a>
        </td>
    </tr>
    <?php } ?>
    </tbody>
    </table>
    </div>
    <?php } ?>
</div>
</div>

</div>

<?php include "footer.php"; ?>

</body>
</html>
