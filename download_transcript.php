<?php
session_start();
require 'vendor/autoload.php';
use Dompdf\Dompdf;

include "db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM students WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) die("Student not found.");

$student_id = $student['id'];

/* Fetch grades */
$gq = $conn->prepare("
    SELECT g.academic_year, g.semester, g.ca_score, g.exam_score, g.total_score,
           g.grade, g.grade_point,
           c.course_code, c.course_title, c.credit_units
    FROM grades g
    JOIN courses c ON g.course_id = c.id
    WHERE g.student_id = ?
    ORDER BY g.academic_year, g.semester, c.course_code
");
$gq->bind_param("i", $student_id);
$gq->execute();
$gResult = $gq->get_result();

$transcript = [];
while ($row = $gResult->fetch_assoc()) {
    $transcript[$row['academic_year']][$row['semester']][] = $row;
}

/* Build HTML */
$html = '
<style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    h2, h3, h4 { text-align: center; margin: 5px 0; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    th, td { border: 1px solid #333; padding: 5px 8px; text-align: center; }
    th { background: #1b2a4e; color: #fff; }
    .bio { margin-bottom: 15px; }
    .bio p { margin: 2px 0; }
    .summary { text-align: center; margin-top: 20px; padding: 15px; background: #e8f5e9; border-radius: 8px; }
</style>

<h2>University of Delta, Agbor</h2>
<h4>Faculty of Computing</h4>
<h3>STUDENT ACADEMIC TRANSCRIPT</h3>
<hr>

<div class="bio">
    <p><strong>Name:</strong> ' . htmlspecialchars($student['name']) . '</p>
    <p><strong>Matric No:</strong> ' . htmlspecialchars($student['roll_no']) . '</p>
    <p><strong>Department:</strong> ' . htmlspecialchars($student['branch']) . '</p>
    <p><strong>Level:</strong> ' . (($student['year'] ?? 1) * 100) . '</p>
</div>
';

$cumTQP = 0;
$cumTCU = 0;

foreach ($transcript as $session => $semesters) {
    foreach ($semesters as $sem => $courses) {
        $semLabel = $sem == 1 ? 'First' : 'Second';
        $html .= "<h4>" . htmlspecialchars($session) . " – {$semLabel} Semester</h4>";
        $html .= '
        <table>
        <tr>
            <th>Code</th><th>Course Title</th><th>Units</th>
            <th>CA</th><th>Exam</th><th>Total</th>
            <th>Grade</th><th>GP</th><th>QP</th>
        </tr>';

        $tqp = 0; $tcu = 0;
        foreach ($courses as $c) {
            $qp = round($c['grade_point'] * $c['credit_units'], 1);
            $tqp += $qp;
            $tcu += $c['credit_units'];
            $html .= '<tr>
                <td>' . htmlspecialchars($c['course_code']) . '</td>
                <td style="text-align:left;">' . htmlspecialchars($c['course_title']) . '</td>
                <td>' . $c['credit_units'] . '</td>
                <td>' . $c['ca_score'] . '</td>
                <td>' . $c['exam_score'] . '</td>
                <td>' . $c['total_score'] . '</td>
                <td><strong>' . $c['grade'] . '</strong></td>
                <td>' . $c['grade_point'] . '</td>
                <td>' . $qp . '</td>
            </tr>';
        }

        $cumTQP += $tqp;
        $cumTCU += $tcu;
        $gpa  = $tcu > 0 ? round($tqp / $tcu, 2) : 0;
        $cgpa = $cumTCU > 0 ? round($cumTQP / $cumTCU, 2) : 0;

        $html .= '<tr style="background:#f0f0f0; font-weight:bold;">
            <td colspan="2">Total</td><td>' . $tcu . '</td>
            <td colspan="4"></td><td></td><td>' . $tqp . '</td>
        </tr>';
        $html .= '<tr>
            <td colspan="5"></td>
            <td colspan="2"><strong>GPA:</strong> ' . $gpa . '</td>
            <td colspan="2"><strong>CGPA:</strong> ' . $cgpa . '</td>
        </tr>';
        $html .= '</table>';
    }
}

$finalCGPA = $cumTCU > 0 ? round($cumTQP / $cumTCU, 2) : 0;

/* Classification */
if ($finalCGPA >= 4.50) $class = 'First Class Honours';
elseif ($finalCGPA >= 3.50) $class = 'Second Class Upper';
elseif ($finalCGPA >= 2.40) $class = 'Second Class Lower';
elseif ($finalCGPA >= 1.50) $class = 'Third Class';
elseif ($finalCGPA >= 1.00) $class = 'Pass';
else $class = 'Fail';

$html .= '
<div class="summary">
    <h3>Cumulative GPA: ' . $finalCGPA . '</h3>
    <h4>' . $class . '</h4>
</div>';

/* Generate PDF */
$pdf = new Dompdf();
$pdf->loadHtml($html);
$pdf->setPaper('A4', 'portrait');
$pdf->render();

$filename = 'Transcript_' . preg_replace('/[^a-zA-Z0-9]/', '_', $student['roll_no']) . '.pdf';
$pdf->stream($filename, ["Attachment" => true]);
