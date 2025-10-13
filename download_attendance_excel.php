<?php
session_start();
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office'){
    die("Access Denied.");
}

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

include 'db_connect.php';

// Get dept from session
$userID = $_SESSION['userID'];
$stmt = $conn->prepare("SELECT dept FROM admin_roles WHERE username=?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$stmt->bind_result($dept);
$stmt->fetch();
$stmt->close();

// If form not submitted yet → keep frontend same
if(!isset($_POST['year']) || !isset($_POST['month']) || !isset($_POST['academic_year']) || !isset($_POST['semester'])){
    ?>
  <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Download Attendance Report</title>
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
    /* Responsive form layout */
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }
    body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        background: #f4f7fa;
        -webkit-font-smoothing:antialiased;
    }
    .container {
        background: #ffffff;
        padding: 28px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        width: 100%;
        max-width: 520px;
        text-align: center;
        margin: 24px auto;
        flex: 1 0 auto;
        box-sizing: border-box;
    }
    .header-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 12px;
        flex-wrap: wrap;
    }
    .header-bar h1 { font-size: 1.25rem; margin: 0; }
    h3 {
        margin-bottom: 18px;
        color: #2c3e50;
        font-size: 1.05rem;
        font-weight: 600;
    }
    label {
        display: block;
        margin: 12px 0 6px;
        font-weight: 600;
        color: #34495e;
        text-align: left;
        font-size: 0.95rem;
    }
    select {
        width: 100%;
        padding: 10px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 15px;
        box-sizing: border-box;
    }
    /* Use Bootstrap button but ensure comfortable tap target */
    .container .btn-primary {
        margin-top: 16px;
        width: 100%;
        padding: 10px 12px;
        border-radius: 8px;
        font-weight: 600;
    }
    footer {
        background: #002147;
        color: #fff;
        text-align: center;
        padding: 12px 0;
        font-size: 0.9rem;
        width: 100%;
        box-sizing: border-box;
        position: static;
        margin-top: auto;
    }

    /* Small screens tweaks */
    @media (max-width: 576px) {
        .header-bar { padding: 8px 12px; }
        .header-bar h1 { font-size: 1.05rem; text-align: center; width: 100%; }
        .header-bar a.btn { width: 100%; }
        .container { padding: 18px; margin: 16px; }
        h3 { font-size: 1rem; }
        label { margin-top: 10px; }
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
        <form method="post">
            <label>Year:</label>
            <select name="year" required>
                <option value="">--Select--</option>
                <option value="E1">E1</option>
                <option value="E2">E2</option>
                <option value="E3">E3</option>
                <option value="E4">E4</option>
            </select>

            <label>Semester:</label>
            <select name="semester" required>
                <option value="">--Select--</option>
                <option value="1">1</option>
                <option value="2">2</option>
            </select>

            <label>Academic Year:</label>
            <select name="academic_year" required>
                <option value="">--Select--</option>
                <option value="2025-26">2025-26</option>
                <option value="2026-27">2026-27</option>
            </select>

            <label>Exam:</label>
            <select name="month" required>
                <option value="">--Select--</option>
                <option value="All">Full Semester</option>
                <option value="MT-1">MT-1</option>
                <option value="MT-2">MT-2</option>
                <option value="MT-3">MT-3</option>
</select>
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
$year = $_POST['year'];
$month = $_POST['month'];
$academic_year = $_POST['academic_year'];
$semester = $_POST['semester'];

// Subjects list
$subjects = [];
$subRes = $conn->query("SELECT DISTINCT subject_code, subject_name 
                        FROM subjects 
                        WHERE dept='$dept' AND year='$year' AND semester='$semester'");
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
$sections = $conn->query("SELECT DISTINCT section 
                          FROM userstudent 
                          WHERE dept='$dept' AND year='$year' ORDER BY section ASC");

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
if ($month === "All") {
    $fileName = "{$year}_Sem{$semester}_AY{$academic_year}_Attendance_FullSemester.xlsx";
} else {
    $fileName = "{$year}_{$semester}_AY{$academic_year}_{$month}_Attendance.xlsx";
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=$fileName");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
