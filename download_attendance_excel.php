<?php
session_start();
if(!isset($_SESSION['userID'])){
    die("Access Denied.");
}

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

// Determine dept
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
    $dept = $_POST['dept'] ?? '';
}

// If form not submitted yet → show frontend
if(!isset($_POST['year'], $_POST['month'], $_POST['academic_year'], $_POST['semester'])){
    include 'attendance_form.php'; // move your HTML form to a separate file for cleanliness
    exit;
}

// Filters
$year = $_POST['year'];
$month = $_POST['month'];
$academic_year = $_POST['academic_year'];
$semester = $_POST['semester'];
$sectionFilter = $_POST['section'] ?? null;
$deptFilter = $_POST['dept'] ?? $dept;

// Dept office sees all sections
if ($_SESSION['role'] === 'dept_office') $sectionFilter = null;

// Fetch subjects once
$subjects = [];
$res = $conn->query("SELECT subject_code, subject_name FROM subjects WHERE dept='". $conn->real_escape_string($deptFilter) ."' AND year='". $conn->real_escape_string($year) ."' AND semester='". $conn->real_escape_string($semester) ."'");
while($s = $res->fetch_assoc()){
    $subjects[$s['subject_code']] = $s['subject_name'];
}

// Fetch all attendance in one query
$attQuery = "SELECT student_id, subject_code, 
                    COUNT(*) AS conducted, 
                    SUM(CASE WHEN status='P' THEN 1 ELSE 0 END) AS attended
             FROM attendance
             WHERE dept='". $conn->real_escape_string($dept) ."'
               AND year='". $conn->real_escape_string($year) ."'
               AND semester='". $conn->real_escape_string($semester) ."'
               AND academic_year='". $conn->real_escape_string($academic_year) ."'";
if($month !== "All") $attQuery .= " AND month='". $conn->real_escape_string($month) ."'";
$attQuery .= " GROUP BY student_id, subject_code";
$attRes = $conn->query($attQuery);

$attendanceData = [];
while($row = $attRes->fetch_assoc()){
    $attendanceData[$row['student_id']][$row['subject_code']] = [
        'conducted' => (int)$row['conducted'],
        'attended' => (int)$row['attended']
    ];
}

// Fetch students
$stuQuery = "SELECT studentId, section FROM userstudent WHERE dept='". $conn->real_escape_string($dept) ."' AND year='". $conn->real_escape_string($year) ."'";
if($sectionFilter) $stuQuery .= " AND section='". $conn->real_escape_string($sectionFilter) ."'";
$stuRes = $conn->query($stuQuery);

$students = [];
$sections = [];
while($stu = $stuRes->fetch_assoc()){
    $students[] = $stu;
    $sections[$stu['section']][] = $stu['studentId'];
}

// Spreadsheet
$spreadsheet = new Spreadsheet();

// Common styles
$headerStyle = [
    'font' => ['bold'=>true,'size'=>11],
    'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
    'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN]],
    'fill'=>['fillType'=>Fill::FILL_SOLID,'color'=>['rgb'=>'D9E1F2']]
];
$titleStyle = [
    'font'=>['bold'=>true,'size'=>14],
    'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER]
];

// ---------- Consolidated Sheet ----------
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Consolidated');

$maxCol = 2 + count($subjects)*2 + 1;
$lastCol = Coordinate::stringFromColumnIndex($maxCol);
$sheet->mergeCells("A1:{$lastCol}1");
$sheet->setCellValue("A1","Dept of $dept, RGUKT Nuzvid");
$sheet->getStyle("A1")->applyFromArray($titleStyle);

// Header
$sheet->setCellValue('A2','S.No');
$sheet->setCellValue('B2','Student Id');
$sheet->mergeCells('A2:A3');
$sheet->mergeCells('B2:B3');

$colIndex = 3;
foreach($subjects as $code=>$name){
    $start = Coordinate::stringFromColumnIndex($colIndex);
    $end = Coordinate::stringFromColumnIndex($colIndex+1);
    $sheet->mergeCells("{$start}2:{$end}2");
    $sheet->setCellValue("{$start}2",$name);
    $sheet->setCellValue("{$start}3",'Conducted');
    $sheet->setCellValue("{$end}3",'Attended');
    $colIndex +=2;
}
$percentCol = Coordinate::stringFromColumnIndex($colIndex);
$sheet->mergeCells("{$percentCol}2:{$percentCol}3");
$sheet->setCellValue("{$percentCol}2","Attendance%");
$sheet->getStyle("A2:{$percentCol}3")->applyFromArray($headerStyle);

// Fill student data
$rowNum=4; $sno=1;
foreach($students as $stu){
    $sheet->setCellValue("A{$rowNum}", $sno++);
    $sheet->setCellValue("B{$rowNum}", $stu['studentId']);
    $totalConducted = $totalAttended = 0;
    $colIndex=3;
    foreach($subjects as $code=>$name){
        $c = $attendanceData[$stu['studentId']][$code]['conducted'] ?? 0;
        $a = $attendanceData[$stu['studentId']][$code]['attended'] ?? 0;
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex).$rowNum,$c);
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex+1).$rowNum,$a);
        $totalConducted += $c;
        $totalAttended += $a;
        $colIndex +=2;
    }
    $percent = ($totalConducted>0)?round(($totalAttended/$totalConducted)*100,2):0;
    $sheet->setCellValue("{$percentCol}{$rowNum}", $percent.'%');
    $rowNum++;
}

// ---------- Section Sheets ----------
$sheetIndex=1;
foreach($sections as $section=>$stuIds){
    $sheet = $spreadsheet->createSheet($sheetIndex++);
    $sheet->setTitle("Section-$section");

    $maxCol = 2 + count($subjects)*2 + 3;
    $lastCol = Coordinate::stringFromColumnIndex($maxCol);
    $sheet->mergeCells("A1:{$lastCol}1");
    $sheet->setCellValue("A1","Dept of $dept, RGUKT Nuzvid");
    $sheet->getStyle("A1")->applyFromArray($titleStyle);

    $sheet->setCellValue('A2','S.No');
    $sheet->setCellValue('B2','Student Id');
    $sheet->mergeCells('A2:A3');
    $sheet->mergeCells('B2:B3');

    $colIndex=3;
    foreach($subjects as $code=>$name){
        $start = Coordinate::stringFromColumnIndex($colIndex);
        $end = Coordinate::stringFromColumnIndex($colIndex+1);
        $sheet->mergeCells("{$start}2:{$end}2");
        $sheet->setCellValue("{$start}2",$name);
        $sheet->setCellValue("{$start}3",'Conducted');
        $sheet->setCellValue("{$end}3",'Attended');
        $colIndex+=2;
    }
    $colTotalC = Coordinate::stringFromColumnIndex($colIndex);
    $colTotalA = Coordinate::stringFromColumnIndex($colIndex+1);
    $colPercent = Coordinate::stringFromColumnIndex($colIndex+2);
    $sheet->mergeCells("{$colTotalC}2:{$colTotalC}3");
    $sheet->mergeCells("{$colTotalA}2:{$colTotalA}3");
    $sheet->mergeCells("{$colPercent}2:{$colPercent}3");
    $sheet->setCellValue("{$colTotalC}2","Total Conducted");
    $sheet->setCellValue("{$colTotalA}2","Total Attended");
    $sheet->setCellValue("{$colPercent}2","Attendance%");
    $sheet->getStyle("A2:{$colPercent}3")->applyFromArray($headerStyle);

    $rowNum=4; $sno=1;
    foreach($stuIds as $studentId){
        $sheet->setCellValue("A{$rowNum}",$sno++);
        $sheet->setCellValue("B{$rowNum}",$studentId);
        $totalC = $totalA = 0;
        $colIndex=3;
        foreach($subjects as $code=>$name){
            $c = $attendanceData[$studentId][$code]['conducted'] ?? 0;
            $a = $attendanceData[$studentId][$code]['attended'] ?? 0;
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex).$rowNum,$c);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex+1).$rowNum,$a);
            $totalC += $c; $totalA += $a;
            $colIndex+=2;
        }
        $percent = ($totalC>0)?round(($totalA/$totalC)*100,2):0;
        $sheet->setCellValue("{$colTotalC}{$rowNum}",$totalC);
        $sheet->setCellValue("{$colTotalA}{$rowNum}",$totalA);
        $sheet->setCellValue("{$colPercent}{$rowNum}",$percent.'%');
        $rowNum++;
    }
}

// Auto width
foreach($spreadsheet->getAllSheets() as $sheet){
    foreach(range('A', $sheet->getHighestColumn()) as $col){
        $sheet->getColumnDimension($col)->setWidth(20);
    }
}

// File name
$deptCode = preg_replace('/[^A-Za-z0-9]/','',$dept);
$examSafe = ($month==='All')?'FullSemester':preg_replace('/[^A-Za-z0-9]/','',$month);
$sectionSafe = $sectionFilter ?? '';
$fileName = empty($sectionSafe) 
    ? "$year$deptCode__sem$semester"."_AY$academic_year"."_$examSafe"."_attendance.xlsx"
    : "$year$deptCode"."_section$sectionSafe"."__sem$semester"."_AY$academic_year"."_$examSafe"."_attendance.xlsx";

// Clear output buffers
while(ob_get_level()) ob_end_clean();

// Send file directly
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$fileName.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
