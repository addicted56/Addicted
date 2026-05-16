<?php
session_start();
include "db.php";

/* Only staff can access */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: staff_login.php");
    exit();
}

$staff_db_id   = $_SESSION['staff_db_id'] ?? 0;
$academic_year = getCurrentSession($conn);

/* Validate course_id */
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    die("Invalid course");
}
$course_id = (int)$_GET['course_id'];

/* Verify staff is assigned to this course */
$verify = $conn->prepare("
    SELECT sc.id FROM staff_courses sc
    WHERE sc.staff_id=? AND sc.course_id=? AND sc.academic_year=?
");
$verify->bind_param("iis", $staff_db_id, $course_id, $academic_year);
$verify->execute();
if ($verify->get_result()->num_rows === 0) {
    die("You are not assigned to this course.");
}

/* Get course info */
$cq = $conn->prepare("SELECT c.*, d.dept_name FROM courses c JOIN departments d ON c.dept_id=d.id WHERE c.id=?");
$cq->bind_param("i", $course_id);
$cq->execute();
$course = $cq->get_result()->fetch_assoc();
if (!$course) die("Course not found");

$msg = "";

/* =========================
   HANDLE GRADE SUBMISSION
========================= */
if (isset($_POST['save_grades'])) {
    $student_ids = $_POST['student_id'] ?? [];
    $ca_scores   = $_POST['ca_score'] ?? [];
    $exam_scores = $_POST['exam_score'] ?? [];

    $semester = $course['semester'];

    $stmt = $conn->prepare("
        INSERT INTO grades (student_id, course_id, academic_year, semester, ca_score, exam_score, total_score, grade, grade_point, entered_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            ca_score=VALUES(ca_score),
            exam_score=VALUES(exam_score),
            total_score=VALUES(total_score),
            grade=VALUES(grade),
            grade_point=VALUES(grade_point),
            entered_by=VALUES(entered_by)
    ");

    foreach ($student_ids as $idx => $sid) {
        $sid  = (int)$sid;
        $ca   = min(30, max(0, (float)($ca_scores[$idx] ?? 0)));
        $exam = min(70, max(0, (float)($exam_scores[$idx] ?? 0)));
        $total = $ca + $exam;

        list($letterGrade, $gradePoint) = computeGrade($total);

        $stmt->bind_param("iisidddsdi",
            $sid, $course_id, $academic_year, $semester,
            $ca, $exam, $total, $letterGrade, $gradePoint, $staff_db_id
        );
        $stmt->execute();
    }

    $msg = "Grades saved successfully!";
}

/* =========================
   FETCH REGISTERED STUDENTS
========================= */
$students = [];
$sq = $conn->prepare("
    SELECT s.id, s.name, s.roll_no, s.branch,
           g.ca_score, g.exam_score, g.total_score, g.grade, g.grade_point
    FROM course_registrations cr
    JOIN students s ON cr.student_id = s.id
    LEFT JOIN grades g ON g.student_id = s.id AND g.course_id = cr.course_id AND g.academic_year = cr.academic_year
    WHERE cr.course_id = ? AND cr.academic_year = ?
    ORDER BY s.name
");
$sq->bind_param("is", $course_id, $academic_year);
$sq->execute();
$sResult = $sq->get_result();
while ($row = $sResult->fetch_assoc()) {
    $students[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Manage Grades – <?= htmlspecialchars($course['course_code']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="styles.css">
</head>

<body class="d-flex flex-column min-vh-100">

<?php include "header.php"; ?>

<div class="container mt-4 flex-fill">
<div class="card shadow p-4">

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><?= htmlspecialchars($course['course_code']) ?> – <?= htmlspecialchars($course['course_title']) ?></h4>
        <small class="text-muted">
            <?= $course['credit_units'] ?> Credit Units | Level <?= $course['level'] ?> |
            Semester <?= $course['semester'] ?> | <?= htmlspecialchars($course['dept_name']) ?>
        </small>
    </div>
    <a href="staff_dashboard.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if ($msg) { ?>
<div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php } ?>

<?php if (empty($students)) { ?>
<div class="alert alert-info">No students registered for this course yet.</div>
<?php } else { ?>

<form method="POST">
<div class="table-responsive">
<table class="table table-bordered table-hover align-middle">
<thead class="table-dark">
<tr>
    <th>#</th>
    <th>Name</th>
    <th>Roll No</th>
    <th>CA Score (max 30)</th>
    <th>Exam Score (max 70)</th>
    <th>Total</th>
    <th>Grade</th>
    <th>GP</th>
</tr>
</thead>
<tbody>
<?php foreach ($students as $i => $s) { ?>
<tr>
    <td><?= $i + 1 ?></td>
    <td><?= htmlspecialchars($s['name']) ?></td>
    <td><?= htmlspecialchars($s['roll_no']) ?></td>
    <td>
        <input type="hidden" name="student_id[]" value="<?= $s['id'] ?>">
        <input type="number" name="ca_score[]" class="form-control form-control-sm"
               min="0" max="30" step="0.5"
               value="<?= $s['ca_score'] ?? '' ?>" required>
    </td>
    <td>
        <input type="number" name="exam_score[]" class="form-control form-control-sm"
               min="0" max="70" step="0.5"
               value="<?= $s['exam_score'] ?? '' ?>" required>
    </td>
    <td><?= $s['total_score'] ?? '–' ?></td>
    <td><strong><?= $s['grade'] ?? '–' ?></strong></td>
    <td><?= $s['grade_point'] ?? '–' ?></td>
</tr>
<?php } ?>
</tbody>
</table>
</div>

<div class="d-flex justify-content-end gap-2 mt-3">
    <a href="staff_dashboard.php" class="btn btn-secondary">Cancel</a>
    <button type="submit" name="save_grades" class="btn btn-primary">
        <i class="bi bi-save"></i> Save All Grades
    </button>
</div>
</form>

<?php } ?>

</div>
</div>

<?php include "footer.php"; ?>

</body>
</html>
