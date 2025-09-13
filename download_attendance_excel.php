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
if(!isset($_POST['year']) || !isset($_POST['month'])){
    ?>
  <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Download Attendance Report</title>
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
    body {
        background: #f4f7fa;
        margin: 0;
        padding: 0;
        min-height: 100vh;
    }
    h1{
        margin-left: 300px;
    }
   .header-bar {
    display: flex;
    align-items: center;
    justify-content: space-between; /* spread out items */
    padding: 10px 30px; /* add spacing inside */
}

.header-bar a {
    margin-left: 20px; /* extra gap if needed */
    padding: 6px 14px;
    border-radius: 6px;
   
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.3s;
}
.header-bar a:hover {
    background: #2980b9;
}

    .container {
        background: #ffffff;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        width: 400px;
        text-align: center;
        margin: 40px auto;
    }
    h3 {
        margin-bottom: 20px;
        color: #2c3e50;
        font-size: 20px;
    }
    label {
        display: block;
        margin: 15px 0 8px;
        font-weight: 600;
        color: #34495e;
        text-align: left;
    }
    select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 15px;
    }
    button {
        margin-top: 25px;
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 6px;
        background: #3498db;
        color: #fff;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.3s;
    }
    button:hover {
        background: #2980b9;
    }
    btn{
        right: 20px;
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
        <h3>Download Attendance Excel Sheet</h3>
        <form method="post">
            <label>Select Year:</label>
            <select name="year" required>
                <option value="">--Select--</option>
                <option value="E1">E1</option>
                <option value="E2">E2</option>
                <option value="E3">E3</option>
                <option value="E4">E4</option>
            </select>

            <label>Select Month Test:</label>
            <select name="month" required>
                <option value="">--Select--</option>
                <option value="MT-1">MT-1</option>
                <option value="MT-2">MT-2</option>
                <option value="MT-3">MT-3</option>
            </select>

            <button type="submit">Download</button>
        </form>
    </div>

</body>
</html>

    <?php
    exit;
}
$year = $_POST['year'];
$month = $_POST['month'];

// Fetch semester and academic_year
$res = $conn->query("SELECT DISTINCT semester, academic_year 
                     FROM attendance 
                     WHERE dept='$dept' AND year='$year' AND month='$month' LIMIT 1");
$row = $res->fetch_assoc();
$semester = $row['semester'] ?? 'Sem1';
$academic_year = $row['academic_year'] ?? date("Y");

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
$consolidatedSheet->setCellValue("{$percentColLetter}2","Attendace%");

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
        $q = $conn->query("SELECT COUNT(*) AS conducted, 
                                  SUM(CASE WHEN status='P' THEN 1 ELSE 0 END) AS attended
                            FROM attendance
                            WHERE dept='$dept' AND year='$year' 
                              AND semester='$semester'
                              AND month='$month'
                              AND subject_code='$code' 
                              AND student_id='$studentId'");
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
            $q = $conn->query("SELECT COUNT(*) AS conducted, 
                                      SUM(CASE WHEN status='P' THEN 1 ELSE 0 END) AS attended
                                FROM attendance
                                WHERE dept='$dept' AND year='$year' 
                                  AND semester='$semester'
                                  AND month='$month'
                                  AND subject_code='$code' 
                                  AND student_id='$studentId'");
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
        $sheet->getColumnDimension($col)->setWidth(20); // clean look
    }
}

// File name
$fileName = "{$year}_{$semester}_AY{$academic_year}_Attendance_Sheet.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=$fileName");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
