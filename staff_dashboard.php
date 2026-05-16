<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'staff'
) {
    header("Location: staff_login.php");
    exit();
}

include "db.php";

$staff_db_id   = $_SESSION['staff_db_id'] ?? 0;
$staff_name    = $_SESSION['staff_name'] ?? $_SESSION['user'];
$academic_year = getCurrentSession($conn);

/* =========================
   FETCH ASSIGNED COURSES
========================= */
$courses = [];
$cq = $conn->prepare("
    SELECT sc.id AS assign_id, c.id AS course_id, c.course_code, c.course_title,
           c.credit_units, c.semester, c.level, d.dept_name
    FROM staff_courses sc
    JOIN courses c ON sc.course_id = c.id
    JOIN departments d ON c.dept_id = d.id
    WHERE sc.staff_id = ? AND sc.academic_year = ?
    ORDER BY c.level, c.semester, c.course_code
");
$cq->bind_param("is", $staff_db_id, $academic_year);
$cq->execute();
$cResult = $cq->get_result();
while ($row = $cResult->fetch_assoc()) {
    $courses[] = $row;
}

/* =========================
   COUNT STUDENTS IN ASSIGNED COURSES
========================= */
$totalStudents = 0;
if (!empty($courses)) {
    $courseIds = array_column($courses, 'course_id');
    $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
    $types = str_repeat('i', count($courseIds));

    $sq = $conn->prepare("
        SELECT COUNT(DISTINCT student_id) AS cnt
        FROM course_registrations
        WHERE course_id IN ($placeholders) AND academic_year = ?
    ");
    $params = $courseIds;
    $params[] = $academic_year;
    $types .= 's';
    $sq->bind_param($types, ...$params);
    $sq->execute();
    $totalStudents = $sq->get_result()->fetch_assoc()['cnt'] ?? 0;
}

/* count pending grades (courses with students but no grades yet) */
$pendingGrades = 0;
foreach ($courses as $c) {
    $pg = $conn->prepare("
        SELECT COUNT(*) AS cnt FROM course_registrations cr
        LEFT JOIN grades g ON g.student_id = cr.student_id
            AND g.course_id = cr.course_id
            AND g.academic_year = cr.academic_year
        WHERE cr.course_id = ? AND cr.academic_year = ? AND g.id IS NULL
    ");
    $pg->bind_param("is", $c['course_id'], $academic_year);
    $pg->execute();
    $pendingGrades += $pg->get_result()->fetch_assoc()['cnt'];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Staff Dashboard – UNIDEL</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="styles.css">
</head>

<body class="d-flex flex-column min-vh-100">

<?php include "header.php"; ?>

<div class="container mt-4 flex-fill">
<div class="card shadow p-4">

<!-- TOP BAR -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0">Academic Staff Dashboard</h4>
        <small class="text-muted">
            Welcome, <?= htmlspecialchars($staff_name) ?>
            <span class="badge bg-success ms-2">STAFF</span>
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
</div>

<!-- STATS ROW -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="info-box text-center">
            <span>My Courses</span>
            <h4><?= count($courses) ?></h4>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box text-center">
            <span>Total Students</span>
            <h4><?= $totalStudents ?></h4>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box text-center">
            <span>Pending Grades</span>
            <h4><?= $pendingGrades ?></h4>
        </div>
    </div>
</div>

<!-- COURSE LIST -->
<h5 class="mb-3"><i class="bi bi-journal-text"></i> My Assigned Courses (<?= htmlspecialchars($academic_year) ?>)</h5>

<?php if (empty($courses)) { ?>
    <div class="alert alert-info">No courses assigned to you for this session. Contact the Admin.</div>
<?php } else { ?>

<div class="table-responsive">
<table class="table table-hover align-middle">
<thead class="table-dark">
<tr>
    <th>Code</th>
    <th>Course Title</th>
    <th>Units</th>
    <th>Level</th>
    <th>Semester</th>
    <th>Department</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($courses as $c) { ?>
<tr>
    <td><strong><?= htmlspecialchars($c['course_code']) ?></strong></td>
    <td><?= htmlspecialchars($c['course_title']) ?></td>
    <td><?= $c['credit_units'] ?></td>
    <td><?= $c['level'] ?></td>
    <td><?= $c['semester'] ?></td>
    <td><?= htmlspecialchars($c['dept_name']) ?></td>
    <td>
        <a href="manage_grades.php?course_id=<?= $c['course_id'] ?>"
           class="btn btn-primary btn-sm">
           <i class="bi bi-pencil-square"></i> Enter Grades
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

<?php include "footer.php"; ?>

</body>
</html>
