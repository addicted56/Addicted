<?php
session_start();
include "db.php";

/* Only logged-in students */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}
if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] == 1) {
    header("Location: change_password.php");
    exit();
}

$user_id       = $_SESSION['user_id'];
$academic_year = getCurrentSession($conn);

/* Get student profile */
$stmt = $conn->prepare("SELECT * FROM students WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) die("Student record not found.");

$student_id = $student['id'];
$msg = "";
$statusClass = "alert-info";
$selectedPairs = [];

/* =========================
   HANDLE REGISTRATION
========================= */
if (isset($_POST['register_courses'])) {
    $selected = $_POST['courses'] ?? [];

    if (empty($selected)) {
        $msg = "Please select at least one course.";
        $statusClass = "alert-warning";
    } else {
        $regStmt = $conn->prepare("
            INSERT INTO course_registrations (student_id, course_id, academic_year, semester)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE registered_at=CURRENT_TIMESTAMP
        ");

        $count = 0;
        $errors = 0;

        foreach ($selected as $pair) {
            $pair = trim((string)$pair);
            if (!preg_match('/^(\d+):(\d+)$/', $pair, $m)) {
                continue;
            }

            $cid = (int)$m[1];
            $semester = (int)$m[2];
            if ($semester < 1 || $semester > 2) {
                continue;
            }

            $selectedPairs[] = $pair;
            $regStmt->bind_param("iisi", $student_id, $cid, $academic_year, $semester);
            if ($regStmt->execute()) {
                $count++;
            } else {
                $errors++;
            }
        }

        if ($count > 0) {
            $msg = "$count course(s) registered successfully for " . htmlspecialchars($academic_year) . ".";
            if ($errors > 0) {
                $msg .= " Some selected rows could not be saved.";
            }
            $statusClass = $errors > 0 ? "alert-warning" : "alert-success";
        } else {
            $msg = "No course was registered. Please reselect courses and try again.";
            $statusClass = "alert-danger";
        }
    }
}

/* =========================
   HANDLE DROP COURSE
========================= */
if (isset($_GET['drop']) && is_numeric($_GET['drop'])) {
    $drop_id = (int)$_GET['drop'];
    $dStmt = $conn->prepare("DELETE FROM course_registrations WHERE id=? AND student_id=?");
    $dStmt->bind_param("ii", $drop_id, $student_id);
    $dStmt->execute();
    header("Location: course_registration.php");
    exit();
}

/* =========================
   FETCH AVAILABLE COURSES
========================= */
$courses = [];

/* Determine student level from year (Year 1=100, Year 2=200, etc.) */
$studentLevel = ($student['year'] ?? 1) * 100;

$cq = $conn->prepare("
    SELECT c.*, d.dept_name
    FROM courses c
    JOIN departments d ON c.dept_id = d.id
    ORDER BY c.level, c.semester, c.course_code
");
$cq->execute();
$cResult = $cq->get_result();
while ($row = $cResult->fetch_assoc()) {
    if ((int)$row['level'] <= $studentLevel) {
        $courses[] = $row;
    }
}

/* =========================
   FETCH MY REGISTRATIONS
========================= */
$myRegs = [];
$rq = $conn->prepare("
    SELECT cr.id AS reg_id, c.course_code, c.course_title, c.credit_units,
           c.level, c.semester, cr.academic_year, d.dept_name
    FROM course_registrations cr
    JOIN courses c ON cr.course_id = c.id
    JOIN departments d ON c.dept_id = d.id
    WHERE cr.student_id = ?
    ORDER BY cr.academic_year DESC, c.level, c.semester
");
$rq->bind_param("i", $student_id);
$rq->execute();
$rResult = $rq->get_result();
while ($row = $rResult->fetch_assoc()) {
    $myRegs[] = $row;
}

/* total registered credit units this session */
$totalUnits = 0;
foreach ($myRegs as $r) {
    if ($r['academic_year'] === $academic_year) {
        $totalUnits += $r['credit_units'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Course Registration – UNIDEL</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="styles.css">
</head>

<body class="d-flex flex-column min-vh-100">

<?php include "header.php"; ?>

<div class="container mt-4 flex-fill">

<!-- BACK NAV -->
<div class="mb-3">
    <a href="student_dashboard.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
</div>

<div class="page-banner mb-4">
    <h4 class="mb-1">Course Registration</h4>
    <p class="mb-0">Enroll by selecting each course row. The semester is captured automatically from the selected course.</p>
    <small class="d-inline-block mt-2">Current Session: <?= htmlspecialchars($academic_year) ?></small>
</div>

<?php if ($msg) { ?>
<div class="alert <?= $statusClass ?> alert-dismissible fade show">
    <?= $msg ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php } ?>

<!-- MY REGISTERED COURSES -->
<div class="card shadow mb-4 page-shell">
<div class="card-body">
    <h5><i class="bi bi-journal-check"></i> My Registered Courses (<?= htmlspecialchars($academic_year) ?>)</h5>
    <p class="text-muted">Total Credit Units: <strong><?= $totalUnits ?></strong></p>

    <?php if (empty($myRegs)) { ?>
    <div class="alert alert-warning">You have not registered for any courses yet.</div>
    <?php } else { ?>
    <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
    <thead class="table-dark">
    <tr>
        <th>#</th><th>Code</th><th>Course Title</th><th>Units</th>
        <th>Level</th><th>Semester</th><th>Session</th><th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($myRegs as $i => $r) { ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td><strong><?= htmlspecialchars($r['course_code']) ?></strong></td>
        <td><?= htmlspecialchars($r['course_title']) ?></td>
        <td><?= $r['credit_units'] ?></td>
        <td><?= $r['level'] ?></td>
        <td><?= $r['semester'] ?></td>
        <td><?= htmlspecialchars($r['academic_year']) ?></td>
        <td>
            <?php if ($r['academic_year'] === $academic_year) { ?>
            <a href="course_registration.php?drop=<?= $r['reg_id'] ?>"
               class="btn btn-outline-danger btn-sm"
               onclick="return confirm('Drop this course?')">
               <i class="bi bi-x-circle"></i> Drop
            </a>
            <?php } else { ?>
            <span class="text-muted">–</span>
            <?php } ?>
        </td>
    </tr>
    <?php } ?>
    </tbody>
    </table>
    </div>
    <?php } ?>
</div>
</div>

<!-- REGISTER NEW COURSES -->
<div class="card shadow page-shell">
<div class="card-body">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <h5 class="mb-1"><i class="bi bi-plus-circle"></i> Register New Courses</h5>
            <small class="text-muted">Only courses up to your current level (<?= $studentLevel ?>) are shown.</small>
        </div>
        <span class="muted-chip">Selected: <span id="selectedCount">0</span></span>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <div class="media-tile">
                <img src="https://picsum.photos/id/1076/1200/420" alt="University classroom">
            </div>
        </div>
        <div class="col-md-4">
            <div class="h-100 p-3 rounded" style="background:#eef4ff;border:1px solid #d3e2ff;">
                <h6 class="mb-2">Before you submit</h6>
                <ul class="mb-0 ps-3 small text-muted">
                    <li>Select all required courses.</li>
                    <li>Verify the semester column for each selected row.</li>
                    <li>Click Register Selected Courses once.</li>
                </ul>
            </div>
        </div>
    </div>

    <form method="POST">
    <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
    <thead class="table-dark">
    <tr>
        <th style="width:70px">
            <input type="checkbox" id="selectAll" class="form-check-input">
        </th>
        <th>Code</th><th>Course Title</th><th>Units</th>
        <th>Level</th><th>Semester</th><th>Department</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($courses as $c) { ?>
    <tr>
        <td class="text-center">
            <?php $pairValue = $c['id'] . ':' . $c['semester']; ?>
            <input type="checkbox" name="courses[]" value="<?= $pairValue ?>"
                   class="form-check-input" <?= in_array($pairValue, $selectedPairs, true) ? 'checked' : '' ?>>
        </td>
        <td><strong><?= htmlspecialchars($c['course_code']) ?></strong></td>
        <td><?= htmlspecialchars($c['course_title']) ?></td>
        <td><?= $c['credit_units'] ?></td>
        <td><?= $c['level'] ?></td>
        <td><?= $c['semester'] ?></td>
        <td><?= htmlspecialchars($c['dept_name']) ?></td>
    </tr>
    <?php } ?>
    </tbody>
    </table>
    </div>

    <div class="d-flex justify-content-end mt-3">
        <button type="submit" name="register_courses" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Register Selected Courses
        </button>
    </div>
    </form>
</div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const checkboxes = document.querySelectorAll('input[name="courses[]"]');
const selectAll = document.getElementById('selectAll');
const selectedCount = document.getElementById('selectedCount');

function updateSelectedCount() {
    let count = 0;
    checkboxes.forEach(function (cb) {
        if (cb.checked) {
            count++;
        }
    });
    selectedCount.textContent = count;
}

if (selectAll) {
    selectAll.addEventListener('change', function () {
        checkboxes.forEach(function (cb) {
            cb.checked = selectAll.checked;
        });
        updateSelectedCount();
    });
}

checkboxes.forEach(function (cb) {
    cb.addEventListener('change', updateSelectedCount);
});

updateSelectedCount();
</script>
<?php include "footer.php"; ?>

</body>
</html>
