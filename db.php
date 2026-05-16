<?php
ini_set('display_errors', 0);

$conn = new mysqli(
    "localhost",      // DB Host
    "root",           // DB Username
    "",               // DB Password
    "unidel_sarms"    // DB Name
);

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

/* Helper: get current academic session */
function getCurrentSession($conn) {
    $r = $conn->query("SELECT session_name FROM academic_sessions WHERE is_current=1 LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) return $row['session_name'];
    return '2025/2026';
}

/* Helper: compute letter grade & grade point (Nigerian system) */
function computeGrade($total) {
    if ($total >= 70) return ['A', 5.0];
    if ($total >= 60) return ['B', 4.0];
    if ($total >= 50) return ['C', 3.0];
    if ($total >= 45) return ['D', 2.0];
    if ($total >= 40) return ['E', 1.0];
    return ['F', 0.0];
}
?>
