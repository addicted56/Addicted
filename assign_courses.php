<?php
session_start();
include "db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$academic_year = getCurrentSession($conn);
$msg = "";

/* Validate staff_id */
if (!isset($_GET['staff_id']) || !is_numeric($_GET['staff_id'])) {
    die("Invalid staff");
}
$staff_id = (int)$_GET['staff_id'];

/* Get staff info */
$sq = $conn->prepare("SELECT * FROM staff WHERE id=?");
$sq->bind_param("i", $staff_id);
$sq->execute();
$staff = $sq->get_result()->fetch_assoc();
if (!$staff) die("Staff not found");

/* =========================
   ASSIGN COURSE
========================= */
if (isset($_POST['assign'])) {
    $course_id = (int)$_POST['course_id'];
    $semester  = (int)$_POST['semester'];

    $stmt = $conn->prepare("
        INSERT INTO staff_courses (staff_id, course_id, academic_year, semester)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE academic_year=VALUES(academic_year)
    ");
    $stmt->bind_param("iisi", $staff_id, $course_id, $academic_year, $semester);
    $stmt->execute();
    $msg = "Course assigned successfully!";
}

/* =========================
   REMOVE ASSIGNMENT
========================= */
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $rem = (int)$_GET['remove'];
    $dStmt = $conn->prepare("DELETE FROM staff_courses WHERE id=? AND staff_id=?");
    $dStmt->bind_param("ii", $rem, $staff_id);
    $dStmt->execute();
    header("Location: assign_courses.php?staff_id=$staff_id");
    exit();
}

/* =========================
   FETCH CURRENT ASSIGNMENTS
========================= */
$assignments = [];
$aq = $conn->prepare("
    SELECT sc.id AS assign_id, c.course_code, c.course_title, c.credit_units,
           c.semester, c.level, d.dept_name, sc.academic_year
    FROM staff_courses sc
    JOIN courses c ON sc.course_id = c.id
    JOIN departments d ON c.dept_id = d.id
    WHERE sc.staff_id = ?
    ORDER BY sc.academic_year DESC, c.level, c.semester
");
$aq->bind_param("i", $staff_id);
$aq->execute();
$aResult = $aq->get_result();
while ($row = $aResult->fetch_assoc()) {
    $assignments[] = $row;
}

/* =========================
   FETCH ALL COURSES
========================= */
$courses = [];
$cq = $conn->query("
    SELECT c.*, d.dept_name
    FROM courses c
    JOIN departments d ON c.dept_id = d.id
    ORDER BY c.course_code
");
while ($row = $cq->fetch_assoc()) {
    $courses[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Assign Courses – UNIDEL Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="styles.css">
</head>

<body class="d-flex flex-column min-vh-100">

<?php include "header.php"; ?>

<div class="container mt-4 flex-fill">

<div class="mb-3">
    <a href="manage_staff.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Staff List
    </a>
</div>

<?php if ($msg) { ?>
<div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php } ?>

<div class="card shadow mb-4">
<div class="card-body">
    <h5>Assign Courses to: <strong><?= htmlspecialchars($staff['full_name']) ?></strong>
        <small class="text-muted">(<?= htmlspecialchars($staff['staff_id']) ?> – <?= htmlspecialchars($staff['department']) ?>)</small>
    </h5>

    <form method="POST" class="row g-3 mt-2">
        <div class="col-md-5">
            <label class="form-label">Course</label>
            <select name="course_id" class="form-select" required>
                <option value="">-- Select Course --</option>
                <?php foreach ($courses as $c) { ?>
                <option value="<?= $c['id'] ?>">
                    <?= htmlspecialchars($c['course_code']) ?> – <?= htmlspecialchars($c['course_title']) ?>
                    (<?= $c['credit_units'] ?> units, Level <?= $c['level'] ?>)
                </option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Semester</label>
            <select name="semester" class="form-select" required>
                <option value="1">First Semester</option>
                <option value="2">Second Semester</option>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button name="assign" class="btn btn-primary w-100">
                <i class="bi bi-plus"></i> Assign
            </button>
        </div>
    </form>
</div>
</div>

<!-- CURRENT ASSIGNMENTS -->
<div class="card shadow">
<div class="card-body">
    <h5>Current Assignments</h5>

    <?php if (empty($assignments)) { ?>
    <div class="alert alert-info mt-3">No courses assigned yet.</div>
    <?php } else { ?>
    <div class="table-responsive mt-3">
    <table class="table table-bordered table-hover align-middle">
    <thead class="table-dark">
    <tr>
        <th>#</th><th>Code</th><th>Course Title</th><th>Units</th>
        <th>Level</th><th>Semester</th><th>Session</th><th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($assignments as $i => $a) { ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td><strong><?= htmlspecialchars($a['course_code']) ?></strong></td>
        <td><?= htmlspecialchars($a['course_title']) ?></td>
        <td><?= $a['credit_units'] ?></td>
        <td><?= $a['level'] ?></td>
        <td><?= $a['semester'] ?></td>
        <td><?= htmlspecialchars($a['academic_year']) ?></td>
        <td>
            <a href="assign_courses.php?staff_id=<?= $staff_id ?>&remove=<?= $a['assign_id'] ?>"
               class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Remove this assignment?')">
               <i class="bi bi-x-circle"></i> Remove
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
