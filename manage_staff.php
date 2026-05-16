<?php
session_start();
include "db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$msg = "";

/* =========================
   ADD NEW STAFF MEMBER
========================= */
if (isset($_POST['add_staff'])) {
    $full_name   = trim($_POST['full_name']);
    $staff_id    = trim($_POST['staff_id']);
    $department  = trim($_POST['department']);
    $designation = trim($_POST['designation']);
    $email       = trim($_POST['email']);

    /* Generate username from staff_id */
    $username = strtolower(str_replace(' ', '', $staff_id));
    $default_password = "staff1234";
    $hashed = password_hash($default_password, PASSWORD_DEFAULT);

    /* Check username exists */
    $check = $conn->prepare("SELECT id FROM users WHERE user=?");
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $msg = "A user with username '$username' already exists!";
    } else {
        /* Create user account */
        $uStmt = $conn->prepare("INSERT INTO users (user, pass, email, role, must_change_password) VALUES (?, ?, ?, 'staff', 1)");
        $uStmt->bind_param("sss", $username, $hashed, $email);
        $uStmt->execute();
        $user_id = $conn->insert_id;

        /* Create staff profile */
        $sStmt = $conn->prepare("INSERT INTO staff (user_id, full_name, staff_id, department, designation) VALUES (?, ?, ?, ?, ?)");
        $sStmt->bind_param("issss", $user_id, $full_name, $staff_id, $department, $designation);
        $sStmt->execute();

        $msg = "Staff member added! Username: <strong>$username</strong> | Default Password: <strong>$default_password</strong>";
    }
}

/* =========================
   DELETE STAFF
========================= */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $getUser = $conn->prepare("SELECT user_id FROM staff WHERE id=?");
    $getUser->bind_param("i", $del_id);
    $getUser->execute();
    $userData = $getUser->get_result()->fetch_assoc();
    if ($userData) {
        $dStmt = $conn->prepare("DELETE FROM staff WHERE id=?");
        $dStmt->bind_param("i", $del_id);
        $dStmt->execute();

        $duStmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='staff'");
        $duStmt->bind_param("i", $userData['user_id']);
        $duStmt->execute();
    }
    header("Location: manage_staff.php");
    exit();
}

/* =========================
   FETCH ALL STAFF
========================= */
$staffList = [];
$sq = $conn->query("
    SELECT s.*, u.user AS username, u.email
    FROM staff s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.full_name
");
while ($row = $sq->fetch_assoc()) {
    $staffList[] = $row;
}

/* Fetch departments for dropdown */
$departments = [];
$dq = $conn->query("SELECT * FROM departments ORDER BY dept_name");
while ($row = $dq->fetch_assoc()) {
    $departments[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Manage Staff – UNIDEL Admin</title>
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

<!-- ADD STAFF FORM -->
<div class="card shadow mb-4">
<div class="card-body">
    <h5><i class="bi bi-person-plus"></i> Add Academic Staff</h5>

    <form method="POST" class="row g-3 mt-2">
        <div class="col-md-4">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" placeholder="Dr. John Doe" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Staff ID</label>
            <input type="text" name="staff_id" class="form-control" placeholder="UNIDEL/STF/001" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Department</label>
            <select name="department" class="form-select" required>
                <?php foreach ($departments as $d) { ?>
                <option value="<?= htmlspecialchars($d['dept_name']) ?>"><?= htmlspecialchars($d['dept_name']) ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Designation</label>
            <select name="designation" class="form-select">
                <option>Lecturer</option>
                <option>Senior Lecturer</option>
                <option>Assistant Professor</option>
                <option>Professor</option>
                <option>HOD</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="john@unidel.edu.ng">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button name="add_staff" class="btn btn-primary w-100">
                <i class="bi bi-plus-circle"></i> Add
            </button>
        </div>
    </form>
</div>
</div>

<!-- STAFF LIST -->
<div class="card shadow">
<div class="card-body">
    <h5><i class="bi bi-people"></i> Academic Staff List</h5>

    <?php if (empty($staffList)) { ?>
    <div class="alert alert-info mt-3">No staff members registered yet.</div>
    <?php } else { ?>
    <div class="table-responsive mt-3">
    <table class="table table-hover align-middle">
    <thead class="table-dark">
    <tr>
        <th>#</th><th>Name</th><th>Staff ID</th><th>Username</th>
        <th>Department</th><th>Designation</th><th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($staffList as $i => $s) { ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($s['full_name']) ?></td>
        <td><?= htmlspecialchars($s['staff_id']) ?></td>
        <td><code><?= htmlspecialchars($s['username']) ?></code></td>
        <td><?= htmlspecialchars($s['department']) ?></td>
        <td><?= htmlspecialchars($s['designation']) ?></td>
        <td>
            <a href="assign_courses.php?staff_id=<?= $s['id'] ?>" class="btn btn-sm btn-success">
                <i class="bi bi-journal-plus"></i> Assign Courses
            </a>
            <a href="manage_staff.php?delete=<?= $s['id'] ?>" class="btn btn-sm btn-danger"
               onclick="return confirm('Delete this staff member?')">
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
