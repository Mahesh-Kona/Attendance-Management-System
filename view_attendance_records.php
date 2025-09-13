<?php
session_start();

// Check if faculty is logged in
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'faculty'){
    die("Access Denied. This action is only allowed for Faculty users.");
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "attendance_management_system";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get query params
$faculty_id   = $_GET['faculty_id'] ?? '';
$faculty_name = $_GET['faculty_name'] ?? '';
$subject_code = $_GET['subject_code'] ?? '';
$subject_name = $_GET['subject_name'] ?? '';
$year         = $_GET['year'] ?? '';
$section      = $_GET['section'] ?? '';

// Step 1: Get all unique dates when faculty marked attendance
$date_sql = "SELECT DATE(time) as class_date
             FROM attendance
             WHERE subject_code = ? 
               AND faculty_name = ? 
               AND year = ? 
               AND section = ?
             GROUP BY DATE(time)
             ORDER BY class_date ASC";
$stmt = $conn->prepare($date_sql);
$stmt->bind_param("ssss", $subject_code, $faculty_name, $year, $section);
$stmt->execute();
$date_result = $stmt->get_result();
$dates = [];
while ($row = $date_result->fetch_assoc()) {
    $dates[] = $row['class_date'];
}
$stmt->close();

// Step 2: Get all students in this subject/year/section
$students_sql = "SELECT DISTINCT student_id, student_name 
                 FROM attendance
                 WHERE subject_code = ? AND faculty_name = ? AND year = ? AND section = ?
                 ORDER BY student_id ASC";
$stmt = $conn->prepare($students_sql);
$stmt->bind_param("ssss", $subject_code, $faculty_name, $year, $section);
$stmt->execute();
$students_result = $stmt->get_result();
$students = [];
while ($row = $students_result->fetch_assoc()) {
    $students[$row['student_id']] = $row['student_name'];
}
$stmt->close();

// Step 3: Fetch attendance (multiple per day possible)
$attendance_sql = "SELECT student_id, DATE(time) as class_date, status
                   FROM attendance
                   WHERE subject_code = ? AND faculty_name = ? AND year = ? AND section = ?
                   ORDER BY time ASC";
$stmt = $conn->prepare($attendance_sql);
$stmt->bind_param("ssss", $subject_code, $faculty_name, $year, $section);
$stmt->execute();
$attendance_result = $stmt->get_result();

$attendance_data = [];
while ($row = $attendance_result->fetch_assoc()) {
    $attendance_data[$row['student_id']][$row['class_date']][] = $row['status'];
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance Report - <?php echo htmlspecialchars($subject_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
    h1 { text-align: center; margin-bottom: 20px; }
    .info-box { background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .table-container { overflow-x: auto; max-width: 100%; }
    table { background: #fff; font-size: 14px; border-collapse: collapse; white-space: nowrap; }
    th, td { text-align: center; vertical-align: middle; padding: 8px; }


    th.sticky-col, td.sticky-col {
        position: sticky;
        left: 0;
        background: #fff;   
        z-index: 2;
    }
    th.sticky-col-2, td.sticky-col-2 {
        position: sticky;
        left: 120px; 
        background: #fff;   
        z-index: 2;
    }

    thead th {
        background-color: #cfe2ff !important; 
    }
</style>

</head>
<body>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Attendance Management System</h1>
    <h2 class="flex-grow-1 text-center m-0">Attendance Report</h2>
    <a href="faculty_dashboard.php" class="btn btn-primary">Dashboard</a>
</div>

<div class="info-box mt-3">
    <p><b>Faculty:</b> <?php echo htmlspecialchars($faculty_name); ?></p>
    <p><b>Subject:</b> <?php echo htmlspecialchars($subject_code . " - " . $subject_name); ?></p>
    <p><b>Year:</b> <?php echo htmlspecialchars($year ); ?></p>
    <p><b>Section:</b> <?php echo htmlspecialchars($section); ?></p>
</div>

    <div class="table-container">
        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th class="sticky-col">Student ID</th>
                    <th class="sticky-col-2">Student Name</th>
                    <?php foreach ($dates as $d) { ?>
                        <th><?php echo htmlspecialchars($d); ?></th>
                    <?php } ?>
                    <th>No of Classes Conducted</th>
                    <th>No of Classes Attended</th>
                </tr>
            </thead>
            <tbody>
        <?php foreach ($students as $sid => $sname) { 
            $total_classes = 0;
            $total_present = 0;
            ?>
            <tr>
                <td class="sticky-col"><?php echo htmlspecialchars($sid); ?></td>
                <td class="sticky-col-2"><?php echo htmlspecialchars($sname); ?></td>
                <?php foreach ($dates as $d) { 
                    $statuses = $attendance_data[$sid][$d] ?? [];
                    $cell_content = "";
                    if ($statuses) {
                        foreach ($statuses as $st) {
                            if ($st === 'P' || $st === 'Present') {
                                $cell_content .= "<span class='text-success'><b>P</b></span> ";
                                $total_present++;
                            } elseif ($st === 'A' || $st === 'Absent') {
                                $cell_content .= "<span class='text-danger'><b>A</b></span> ";
                            }
                            $total_classes++;
                        }
                    } else {
                        $cell_content = "<span class='text-muted'>-</span>";
                    }
                ?>
                <td><?php echo $cell_content; ?></td>
                <?php } ?>
                <td><b><?php echo $total_classes; ?></b></td>
                <td><b><?php echo $total_present; ?></b></td>
            </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>

   

</body>
</html>
