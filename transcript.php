<?php
session_start();
include "db.php";

/* Only students */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Get student profile */
$stmt = $conn->prepare("SELECT * FROM students WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) die("Student record not found.");

$student_id = $student['id'];

/* =========================
   FETCH ALL GRADES GROUPED BY SESSION/SEMESTER
========================= */
$gradesQuery = $conn->prepare("
    SELECT g.academic_year, g.semester, g.ca_score, g.exam_score, g.total_score,
           g.grade, g.grade_point,
           c.course_code, c.course_title, c.credit_units
    FROM grades g
    JOIN courses c ON g.course_id = c.id
    WHERE g.student_id = ?
    ORDER BY g.academic_year, g.semester, c.course_code
");
$gradesQuery->bind_param("i", $student_id);
$gradesQuery->execute();
$gResult = $gradesQuery->get_result();

$transcript = []; // grouped: [session][semester] => [courses]
$allGPA = [];

while ($row = $gResult->fetch_assoc()) {
    $key = $row['academic_year'];
    $sem = $row['semester'];
    $transcript[$key][$sem][] = $row;
}

/* Calculate GPA per semester and cumulative */
$cumulativeTQP = 0;
$cumulativeTCU = 0;
$semesterGPAs = [];

foreach ($transcript as $session => $semesters) {
    foreach ($semesters as $sem => $courses) {
        $tqp = 0; $tcu = 0;
        foreach ($courses as $c) {
            $qp = $c['grade_point'] * $c['credit_units'];
            $tqp += $qp;
            $tcu += $c['credit_units'];
        }
        $gpa = $tcu > 0 ? round($tqp / $tcu, 2) : 0;
        $cumulativeTQP += $tqp;
        $cumulativeTCU += $tcu;
        $cgpa = $cumulativeTCU > 0 ? round($cumulativeTQP / $cumulativeTCU, 2) : 0;
        $semesterGPAs[$session][$sem] = [
            'gpa'  => $gpa,
            'cgpa' => $cgpa,
            'tcu'  => $tcu,
            'tqp'  => $tqp
        ];
    }
}

$finalCGPA = $cumulativeTCU > 0 ? round($cumulativeTQP / $cumulativeTCU, 2) : 0;

/* Classification */
function getClassification($cgpa) {
    if ($cgpa >= 4.50) return 'First Class Honours';
    if ($cgpa >= 3.50) return 'Second Class Upper';
    if ($cgpa >= 2.40) return 'Second Class Lower';
    if ($cgpa >= 1.50) return 'Third Class';
    if ($cgpa >= 1.00) return 'Pass';
    return 'Fail';
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Transcript – UNIDEL</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="styles.css">
<style>
@media print {
    .no-print { display: none !important; }
    body { background: #fff !important; }
    .card { box-shadow: none !important; border: none !important; }
}
</style>
</head>

<body class="d-flex flex-column min-vh-100">

<?php include "header.php"; ?>

<div class="container mt-4 flex-fill">

<div class="no-print mb-3 d-flex gap-2">
    <a href="student_dashboard.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
    <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-printer"></i> Print Transcript
    </button>
    <a href="download_transcript.php" class="btn btn-success btn-sm">
        <i class="bi bi-download"></i> Download PDF
    </a>
</div>

<div class="card shadow p-4">

<!-- INSTITUTION HEADER -->
<div class="text-center mb-4">
    <h4 class="fw-bold text-uppercase" style="color:#1b2a4e;">University of Delta, Agbor</h4>
    <h6 class="text-muted">Faculty of Computing</h6>
    <h5 class="mt-2">STUDENT ACADEMIC TRANSCRIPT</h5>
    <hr>
</div>

<!-- STUDENT BIO -->
<div class="row mb-4">
    <div class="col-md-6">
        <p><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
        <p><strong>Matric No:</strong> <?= htmlspecialchars($student['roll_no']) ?></p>
    </div>
    <div class="col-md-6">
        <p><strong>Department:</strong> <?= htmlspecialchars($student['branch']) ?></p>
        <p><strong>Level:</strong> <?= ($student['year'] ?? 1) * 100 ?></p>
    </div>
</div>

<?php if (empty($transcript)) { ?>
<div class="alert alert-info">No grades have been recorded yet.</div>
<?php } else { ?>

<?php foreach ($transcript as $session => $semesters) { ?>
    <?php foreach ($semesters as $sem => $courses) {
        $stats = $semesterGPAs[$session][$sem];
    ?>

    <h6 class="mt-4 fw-bold"><?= htmlspecialchars($session) ?> – <?= $sem == 1 ? 'First' : 'Second' ?> Semester</h6>

    <div class="table-responsive">
    <table class="table table-bordered table-sm align-middle">
    <thead class="table-dark">
    <tr>
        <th>Course Code</th>
        <th>Course Title</th>
        <th>Units</th>
        <th>CA</th>
        <th>Exam</th>
        <th>Total</th>
        <th>Grade</th>
        <th>GP</th>
        <th>QP</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($courses as $c) { ?>
    <tr>
        <td><?= htmlspecialchars($c['course_code']) ?></td>
        <td><?= htmlspecialchars($c['course_title']) ?></td>
        <td><?= $c['credit_units'] ?></td>
        <td><?= $c['ca_score'] ?></td>
        <td><?= $c['exam_score'] ?></td>
        <td><?= $c['total_score'] ?></td>
        <td><strong><?= $c['grade'] ?></strong></td>
        <td><?= $c['grade_point'] ?></td>
        <td><?= round($c['grade_point'] * $c['credit_units'], 1) ?></td>
    </tr>
    <?php } ?>
    </tbody>
    <tfoot>
    <tr class="table-secondary fw-bold">
        <td colspan="2">Total</td>
        <td><?= $stats['tcu'] ?></td>
        <td colspan="4"></td>
        <td></td>
        <td><?= $stats['tqp'] ?></td>
    </tr>
    <tr>
        <td colspan="5"></td>
        <td colspan="2"><strong>GPA:</strong> <?= $stats['gpa'] ?></td>
        <td colspan="2"><strong>CGPA:</strong> <?= $stats['cgpa'] ?></td>
    </tr>
    </tfoot>
    </table>
    </div>

    <?php } ?>
<?php } ?>

<!-- FINAL SUMMARY -->
<div class="row justify-content-center mt-4">
<div class="col-md-6">
<div class="info-box bg-success text-center">
    <span>Cumulative GPA</span>
    <h3><?= $finalCGPA ?></h3>
    <span><?= getClassification($finalCGPA) ?></span>
</div>
</div>
</div>

<?php } ?>

</div>
</div>

<?php include "footer.php"; ?>

</body>
</html>
