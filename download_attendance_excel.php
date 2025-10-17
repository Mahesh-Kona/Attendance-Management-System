<?php
session_start();
if(!isset($_SESSION['userID'])){
    die("Access Denied.");
}

// Prevent PHP warnings/notices from being sent into the binary output on production
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

include 'db_connect.php';

// Determine dept: for dept_office use admin_roles lookup, otherwise allow dept from POST for faculty
$dept = '';
if ($_SESSION['role'] === 'dept_office') {
    $userID = $_SESSION['userID'];
    $stmt = $conn->prepare("SELECT dept FROM admin_roles WHERE username=?");
    $stmt->bind_param("s", $userID);
    $stmt->execute();
    $stmt->bind_result($dept);
    $stmt->fetch();
    $stmt->close();
} else {
    // allow dept from POST (e.g., faculty requesting export for their dept)
    $dept = $_POST['dept'] ?? '';
}

// If form not submitted yet → keep frontend same
// If form not submitted yet → keep frontend same
if(!isset($_POST['year']) || !isset($_POST['month']) || !isset($_POST['academic_year']) || !isset($_POST['semester'])){
    ?>
  <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Attendance Report</title>
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
  html, body {
    height: 100%;
    margin: 0;
    padding: 0;
}

body {
    background: #f4f7fa;
    -webkit-font-smoothing: antialiased;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* Header fixed at the very top */
.header-bar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    z-index: 1000;
    flex-wrap: wrap;
}

.header-bar h1 {
    font-size: 1.5rem;
    margin: 0;
    color: #002147;
}

.header-bar a.btn {
    font-size: 0.9rem;
    padding: 6px 12px;
}

/* Center form container vertically below header */
.container {
    background: #ffffff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.06);
    width: 90%;
    max-width: 420px;
    margin: auto; /* centers horizontally */
    margin-top: 80px; /* space below fixed header */
    margin-bottom: 70px; /* space above fixed footer */
    text-align: center;
    box-sizing: border-box;
}

h3 {
    margin: 8px 0 14px;
    color: #2c3e50;
    font-size: 1rem;
    font-weight: 600;
}

label {
    display: block;
    margin: 8px 0 6px;
    font-weight: 600;
    color: #34495e;
    text-align: left;
    font-size: 0.92rem;
}

select {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 15px;
    box-sizing: border-box;
}

.container .btn-primary {
    margin-top: 12px;
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    font-weight: 600;
}

/* Form row layout */
.form-row { 
    display: flex; 
    gap: 10px; 
}
.form-row .col { flex: 1; }

/* Footer fixed at bottom */
footer {
    background: #002147;
    color: #fff;
    text-align: center;
    padding: 10px 0;
    font-size: 0.9rem;
    width: 100%;
    position: fixed;
    bottom: 0;
    left: 0;
    box-sizing: border-box;
}

/* Mobile adjustments */
@media (max-width: 576px) {
    .header-bar {
        flex-direction: column;
        align-items: flex-start;
        padding: 10px 12px;
    }
    .header-bar h1 {
        font-size: 1rem;
        width: 100%;
        text-align: center;
        margin-bottom: 6px;
    }
    .header-bar a.btn {
        width: 100%;
    }
    .container {
        margin-top: 100px;
        margin-bottom: 80px;
        padding: 16px;
    }
}

    </style>
</head>
<body>
 <div class="header-bar">
    <h1>Department of <?php echo htmlspecialchars($dept); ?></h1>
    <a href="dept_office_dashboard.php" class='btn btn-primary'>Dashboard</a>
</div>

    <!-- Form -->
    <div class="container">
        <h3>Download Attendance Sheet</h3>
        <form method="post" class="grow-down">
            <div class="row form-row">
                <div class="col col-12 col-md-6">
                    <label>Year:</label>
                    <select name="year" required>
                        <option value="">--Select--</option>
                        <option value="E1">E1</option>
                        <option value="E2">E2</option>
                        <option value="E3">E3</option>
                        <option value="E4">E4</option>
                    </select>
                </div>
                <div class="col col-12 col-md-6">
                    <label>Semester:</label>
                    <select name="semester" required>
                        <option value="">--Select--</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                    </select>
                </div>
            </div>

            <div class="row form-row">
                <div class="col col-12 col-md-6">
                    <label>Academic Year:</label>
                    <select name="academic_year" required>
                        <option value="">--Select--</option>
                        <option value="2025-26">2025-26</option>
                        <option value="2026-27">2026-27</option>
                    </select>
                </div>
                <div class="col col-12 col-md-6">
                    <label>Exam:</label>
                    <select name="month" required>
                        <option value="">--Select--</option>
                      
                        <option value="MT-1">MT-1</option>
                        <option value="MT-2">MT-2</option>
                        <option value="MT-3">MT-3</option>
                          <option value="All">EST</option>
                    </select>
                </div>
            </div>

            <button type="submit" class='btn btn-primary'>Download</button>
        </form>
    </div>
    <footer>
      &copy; <?= date('Y') ?> Rajiv Gandhi University of Knowledge Technologies Nuzvid. All rights reserved.
    </footer>
</body>
</html>
    <?php
    exit;
}

// Get filters from POST
// Get filters from POST
$year = $_POST['year'];
$month = $_POST['month'];
$academic_year = $_POST['academic_year'];
$semester = $_POST['semester'];
$sectionFilter = $_POST['section'] ?? null;
$deptFilter = $_POST['dept'] ?? $dept;

// If current user is dept_office, enforce downloading all sections (ignore any provided section)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'dept_office') {
    $sectionFilter = null;
}

// Subjects list
$subjects = [];
$subRes = $conn->query("SELECT DISTINCT subject_code, subject_name 
                        FROM subjects 
                        WHERE dept='". $conn->real_escape_string($deptFilter) ."' AND year='". $conn->real_escape_string($year) ."' AND semester='". $conn->real_escape_string($semester) ."'");
while($s = $subRes->fetch_assoc()){
    $subjects[$s['subject_code']] = $s['subject_name'];
}

$spreadsheet = new Spreadsheet();

// Common styling arrays
$headerStyle = [
    'font' => ['bold' => true, 'size' => 11],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9E1F2']]
];
$titleStyle = [
    'font' => ['bold' => true, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
];

/* ---------------- Consolidated Sheet ---------------- */
$consolidatedSheet = $spreadsheet->getActiveSheet();
$consolidatedSheet->setTitle("Consolidated");

$maxCol = 2 + (count($subjects) * 2) + 1;
$lastColLetter = Coordinate::stringFromColumnIndex($maxCol);

// Dept title
$consolidatedSheet->mergeCells("A1:{$lastColLetter}1");
$consolidatedSheet->setCellValue("A1", "Dept of $dept, RGUKT Nuzvid");
$consolidatedSheet->getStyle("A1")->applyFromArray($titleStyle);

// Header
$consolidatedSheet->setCellValue("A2","S.No");
$consolidatedSheet->setCellValue("B2","Student Id");
$consolidatedSheet->mergeCells("A2:A3");
$consolidatedSheet->mergeCells("B2:B3");

$colIndex = 3;
foreach($subjects as $code=>$name){
    $colStartLetter = Coordinate::stringFromColumnIndex($colIndex);
    $colEndLetter   = Coordinate::stringFromColumnIndex($colIndex+1);
    $consolidatedSheet->mergeCells("{$colStartLetter}2:{$colEndLetter}2");
    $consolidatedSheet->setCellValue("{$colStartLetter}2",$name);
    $consolidatedSheet->setCellValue("{$colStartLetter}3",'Conducted');
    $consolidatedSheet->setCellValue("{$colEndLetter}3",'Attended');
    $colIndex += 2;
}

// % column
$percentColLetter = Coordinate::stringFromColumnIndex($colIndex);
$consolidatedSheet->mergeCells("{$percentColLetter}2:{$percentColLetter}3");
$consolidatedSheet->setCellValue("{$percentColLetter}2","Attendance%");

// Apply header style
$consolidatedSheet->getStyle("A2:{$percentColLetter}3")->applyFromArray($headerStyle);

// Students
$students = $conn->query("SELECT studentId FROM userstudent WHERE dept='$dept' AND year='$year'");
$rowNum=4; $sno=1;
while($stu = $students->fetch_assoc()){
    $studentId = $stu['studentId'];
    $consolidatedSheet->setCellValue("A{$rowNum}",$sno++);
    $consolidatedSheet->setCellValue("B{$rowNum}",$studentId);

    $colIndex=3; $totalConducted=0; $totalAttended=0;
    foreach($subjects as $code=>$name){
        if ($month === "All") {
            $q = $conn->query("SELECT COUNT(*) AS conducted, 
                                      SUM(CASE WHEN status='P' THEN 1 ELSE 0 END) AS attended
                                FROM attendance
                                WHERE dept='$dept' AND year='$year' 
                                  AND semester='$semester'
                                  AND academic_year='$academic_year'
                                  AND subject_code='$code' 
                                  AND student_id='$studentId'");
        } else {
            $q = $conn->query("SELECT COUNT(*) AS conducted, 
                                      SUM(CASE WHEN status='P' THEN 1 ELSE 0 END) AS attended
                                FROM attendance
                                WHERE dept='$dept' AND year='$year' 
                                  AND semester='$semester'
                                  AND academic_year='$academic_year'
                                  AND month='$month'
                                  AND subject_code='$code' 
                                  AND student_id='$studentId'");
        }
        $att = $q->fetch_assoc();
        $conducted = $att['conducted'] ?? 0;
        $attended = $att['attended'] ?? 0;
        $totalConducted += $conducted;
        $totalAttended  += $attended;

        $colLetter1 = Coordinate::stringFromColumnIndex($colIndex);
        $colLetter2 = Coordinate::stringFromColumnIndex($colIndex+1);
        $consolidatedSheet->setCellValue("{$colLetter1}{$rowNum}",$conducted);
        $consolidatedSheet->setCellValue("{$colLetter2}{$rowNum}",$attended);
        $colIndex += 2;
    }
    $percent = ($totalConducted>0) ? round(($totalAttended/$totalConducted)*100,2) : 0;
    $consolidatedSheet->setCellValue("{$percentColLetter}{$rowNum}",$percent."%");
    $rowNum++;
}

/* ---------------- Section Sheets ---------------- */
$sectionsQuery = "SELECT DISTINCT section FROM userstudent WHERE dept='". $conn->real_escape_string($deptFilter) ."' AND year='". $conn->real_escape_string($year) ."' ORDER BY section ASC";
if ($sectionFilter) {
    // Only that one section
    $sections = $conn->query("SELECT DISTINCT section FROM userstudent WHERE dept='". $conn->real_escape_string($deptFilter) ."' AND year='". $conn->real_escape_string($year) ."' AND section='". $conn->real_escape_string($sectionFilter) ."'");
} else {
    $sections = $conn->query($sectionsQuery);
}

$sheetIndex = 1;
while($sec = $sections->fetch_assoc()){
    $section = $sec['section'];
    $sheet = $spreadsheet->createSheet($sheetIndex++);
    $sheet->setTitle("Section-$section");

    $maxCol = 2 + (count($subjects) * 2) + 3; 
    $lastColLetter = Coordinate::stringFromColumnIndex($maxCol);

    // Dept title
    $sheet->mergeCells("A1:{$lastColLetter}1");
    $sheet->setCellValue("A1", "Dept of $dept, RGUKT Nuzvid");
    $sheet->getStyle("A1")->applyFromArray($titleStyle);

    // Header
    $sheet->setCellValue("A2","S.No");
    $sheet->setCellValue("B2","Student Id");
    $sheet->mergeCells("A2:A3");
    $sheet->mergeCells("B2:B3");

    $colIndex = 3;
    foreach($subjects as $code=>$name){
        $colStartLetter = Coordinate::stringFromColumnIndex($colIndex);
        $colEndLetter   = Coordinate::stringFromColumnIndex($colIndex+1);
        $sheet->mergeCells("{$colStartLetter}2:{$colEndLetter}2");
        $sheet->setCellValue("{$colStartLetter}2",$name);
        $sheet->setCellValue("{$colStartLetter}3",'Conducted');
        $sheet->setCellValue("{$colEndLetter}3",'Attended');
        $colIndex += 2;
    }

    // Totals
    $colTotalC = Coordinate::stringFromColumnIndex($colIndex);
    $colTotalA = Coordinate::stringFromColumnIndex($colIndex+1);
    $colPercent= Coordinate::stringFromColumnIndex($colIndex+2);
    $sheet->mergeCells("{$colTotalC}2:{$colTotalC}3");
    $sheet->mergeCells("{$colTotalA}2:{$colTotalA}3");
    $sheet->mergeCells("{$colPercent}2:{$colPercent}3");
    $sheet->setCellValue("{$colTotalC}2","Total Conducted");
    $sheet->setCellValue("{$colTotalA}2","Total Attended");
    $sheet->setCellValue("{$colPercent}2","Attendance%");

    // Apply header style
    $sheet->getStyle("A2:{$colPercent}3")->applyFromArray($headerStyle);

    // Students
    $students = $conn->query("SELECT studentId FROM userstudent 
                              WHERE dept='$dept' AND year='$year' AND section='$section'");
    $rowNum=4; $sno=1;
    while($stu = $students->fetch_assoc()){
        $studentId = $stu['studentId'];
        $sheet->setCellValue("A{$rowNum}",$sno++);
        $sheet->setCellValue("B{$rowNum}",$studentId);

        $colIndex=3; $totalConducted=0; $totalAttended=0;
        foreach($subjects as $code=>$name){
            if ($month === "All") {
                $q = $conn->query("SELECT COUNT(*) AS conducted, 
                                          SUM(CASE WHEN status='P' THEN 1 ELSE 0 END) AS attended
                                    FROM attendance
                                    WHERE dept='$dept' AND year='$year' 
                                      AND semester='$semester'
                                      AND academic_year='$academic_year'
                                      AND subject_code='$code' 
                                      AND student_id='$studentId'");
            } else {
                $q = $conn->query("SELECT COUNT(*) AS conducted, 
                                          SUM(CASE WHEN status='P' THEN 1 ELSE 0 END) AS attended
                                    FROM attendance
                                    WHERE dept='$dept' AND year='$year' 
                                      AND semester='$semester'
                                      AND academic_year='$academic_year'
                                      AND month='$month'
                                      AND subject_code='$code' 
                                      AND student_id='$studentId'");
            }
            $att = $q->fetch_assoc();
            $conducted = $att['conducted'] ?? 0;
            $attended = $att['attended'] ?? 0;
            $totalConducted += $conducted;
            $totalAttended  += $attended;

            $colLetter1 = Coordinate::stringFromColumnIndex($colIndex);
            $colLetter2 = Coordinate::stringFromColumnIndex($colIndex+1);
            $sheet->setCellValue("{$colLetter1}{$rowNum}",$conducted);
            $sheet->setCellValue("{$colLetter2}{$rowNum}",$attended);
            $colIndex += 2;
        }
        $percent = ($totalConducted>0) ? round(($totalAttended/$totalConducted)*100,2) : 0;
        $sheet->setCellValue("{$colTotalC}{$rowNum}",$totalConducted);
        $sheet->setCellValue("{$colTotalA}{$rowNum}",$totalAttended);
        $sheet->setCellValue("{$colPercent}{$rowNum}",$percent."%");
        $rowNum++;
    }
}

/* ---------------- Column Widths ---------------- */
foreach ($spreadsheet->getAllSheets() as $sheet) {
    foreach (range('A', $sheet->getHighestColumn()) as $col) {
        $sheet->getColumnDimension($col)->setWidth(20);
    }
}

// File name
// Sanitize parts for filename (allow alphanumerics, dash, underscore)
$safe = function($s){
    return preg_replace('/[^A-Za-z0-9_\-]/', '', str_replace(' ', '_', $s));
};
$yearSafe = $safe($year);
$sectionSafe = $safe($sectionFilter ?? ($section ?? ''));

// For dept_office, ensure section is not included in filename
if (isset($_SESSION['role']) && $_SESSION['role'] === 'dept_office') {
    $sectionSafe = '';
}
$semSafe = $safe($semester);
$aySafe = $safe($academic_year);
$examSafe = ($month === 'All') ? 'FullSemester' : $safe($month);

// Map department codes to friendly names
$deptSafe = $safe($dept);
$dept_names = [
   'Computer Science & Engineering'=> 'CSE',
     'Electronics & Communication Engineering'   =>  'ECE',
     'Mechanical Engineering'   =>  'Mech',
 'Electrical & Electronics Engineering'   =>  'EEE',
   'Civil Engineering'   =>   'Civil' ,
    'Metallurgical & Material Science Engineering'   => 'MME',
     'Chemical Engineering'   => 'Chemical'
];

// Determine dept short code robustly (mapping expects full department name)
$deptCode = $dept_names[$dept] ?? null;
if (!$deptCode) {
    // try fuzzy match against mapping keys (case-insensitive)
    foreach ($dept_names as $fullName => $short) {
        if (stripos($fullName, str_replace(['_','-'], ' ', $deptSafe)) !== false || stripos($fullName, $dept) !== false) {
            $deptCode = $short;
            break;
        }
    }
}
if (!$deptCode) {
    // fallback to sanitized dept string
    $deptCode = $deptSafe ?: 'dept';
}

// Filename format:
// - If section provided: year + deptCode + _section{section}__sem{sem}_AY{academic_year}_{exam}_attendance.xlsx
// - If no section: year + deptCode + __sem{sem}_AY{academic_year}_{exam}_attendance.xlsx
if (!empty($sectionSafe)) {
    $fileName = sprintf("%s%s_section%s__sem%s_AY%s_%s_attendance.xlsx", $yearSafe, $deptCode, $sectionSafe, $semSafe, $aySafe, $examSafe);
} else {
    $fileName = sprintf("%s%s__sem%s_AY%s_%s_attendance.xlsx", $yearSafe, $deptCode, $semSafe, $aySafe, $examSafe);
}

// Clear (end) any output buffers to avoid corrupting the Excel file
if (ob_get_length()) {
    @ob_end_clean();
}

// Send binary-safe headers
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
// Flush system output buffer and write file
$writer->save('php://output');
flush();
exit;
?>
