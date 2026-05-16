<?php
session_start();

require 'vendor/autoload.php';
use Dompdf\Dompdf;

include "db.php";

if(!isset($_SESSION['user_id']) || !isset($_SESSION['role'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

$html = "";

/* =====================================================
   FUNCTION → BUILD ONE STUDENT REPORT BLOCK
===================================================== */

function generateStudentReport($conn, $student){

    $student_id = $student['id'];

    /* Attendance */
    $attStmt = $conn->prepare("
        SELECT attendance_percent
        FROM attendance
        WHERE student_id=?
    ");
    $attStmt->bind_param("i",$student_id);
    $attStmt->execute();
    $attendance =
        $attStmt->get_result()->fetch_assoc()['attendance_percent'] ?? 0;

    /* Semester Data */
    $semStmt = $conn->prepare("
        SELECT semester, obtained_marks, sgpa
        FROM semester_marks
        WHERE student_id=?
        ORDER BY semester
    ");
    $semStmt->bind_param("i",$student_id);
    $semStmt->execute();

    $semResult = $semStmt->get_result();

    $totalMarks = 0;

    $block = "
    <div style='page-break-after:always;'>

    <h2 style='text-align:center;'>Student Academic Report</h2>

    <h3>{$student['name']}</h3>

    <p>
    <b>Roll Number:</b> {$student['roll_no']}<br>
    <b>Branch:</b> {$student['branch']}<br>
    <b>Year:</b> {$student['year']}<br>
    <b>Attendance:</b> {$attendance}%<br>
    </p>

    <h4>Semester Performance</h4>

    <table border='1' cellpadding='8' cellspacing='0' width='100%'>
    <tr>
        <th>Semester</th>
        <th>Marks (Out of 1000)</th>
        <th>SGPA</th>
    </tr>
    ";

    while($sem=$semResult->fetch_assoc()){

        $totalMarks += $sem['obtained_marks'];

        $block .= "
        <tr>
            <td>Semester {$sem['semester']}</td>
            <td>{$sem['obtained_marks']}</td>
            <td>{$sem['sgpa']}</td>
        </tr>";
    }

    /* CGPA */
    $cgpaStmt = $conn->prepare("
        SELECT ROUND(AVG(sgpa),2) AS cgpa
        FROM semester_marks
        WHERE student_id=?
    ");
    $cgpaStmt->bind_param("i",$student_id);
    $cgpaStmt->execute();

    $cgpa =
        $cgpaStmt->get_result()->fetch_assoc()['cgpa'] ?? 0;

    $block .= "
    </table>

    <br>

    <h4>Total Marks: {$totalMarks} / 6000</h4>
    <h3>Final CGPA: {$cgpa}</h3>

    </div>
    ";

    return $block;
}

/* =====================================================
   STUDENT DOWNLOAD → ONLY HIS REPORT
===================================================== */

if($role === "student"){

$stmt = $conn->prepare("
    SELECT * FROM students WHERE user_id=?
");
$stmt->bind_param("i",$user_id);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();

$html .= generateStudentReport($conn,$student);
}

/* =====================================================
   STAFF DOWNLOAD → STUDENTS IN ASSIGNED COURSES
===================================================== */

elseif($role === "staff"){

/* staff can only see their own students */
$sp = $conn->prepare("SELECT id FROM staff WHERE user_id=?");
$sp->bind_param("i",$user_id);
$sp->execute();
$staffRow = $sp->get_result()->fetch_assoc();
if($staffRow){
    $academic_year = getCurrentSession($conn);
    $res = $conn->prepare("
        SELECT DISTINCT s.* FROM students s
        JOIN course_registrations cr ON s.id = cr.student_id
        JOIN staff_courses sc ON cr.course_id = sc.course_id AND cr.academic_year = sc.academic_year
        WHERE sc.staff_id=? AND sc.academic_year=?
        ORDER BY s.name
    ");
    $res->bind_param("is", $staffRow['id'], $academic_year);
    $res->execute();
    $sResult = $res->get_result();
    while($student = $sResult->fetch_assoc()){
        $html .= generateStudentReport($conn,$student);
    }
}

}

/* =====================================================
   ADMIN DOWNLOAD → ALL STUDENTS FULL REPORTS
===================================================== */

else{

$res = $conn->query("
    SELECT * FROM students ORDER BY name
");

while($student = $res->fetch_assoc()){
    $html .= generateStudentReport($conn,$student);
}

}

/* =====================================================
   GENERATE PDF
===================================================== */

$pdf = new Dompdf();
$pdf->loadHtml($html);
$pdf->setPaper('A4','portrait');
$pdf->render();

$pdf->stream("student_reports.pdf", ["Attachment"=>true]);
?>
