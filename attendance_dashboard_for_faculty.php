<?php
session_start();

if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'faculty'){
    die("Access Denied. Faculty only.");
}

//Set timezone to India (IST)
date_default_timezone_set("Asia/Kolkata");

$host = "localhost";
$user = "root";
$pass = "";
$db   = "attendance_management_system";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// Get data from query params
$faculty_id   = $_GET['faculty_id'] ?? '';
$faculty_name = $_GET['faculty_name'] ?? '';
$subject_code = $_GET['subject_code'] ?? '';
$subject_name = $_GET['subject_name'] ?? '';
$section      = $_GET['section'] ?? '';
$year         = $_GET['year'] ?? '';   // <-- added year filter

//  Get department from subjects table (based on subject_code)
$subject_dept = '';
if (!empty($subject_code)) {
    $sql = "SELECT dept FROM subjects WHERE subject_code = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $subject_code);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $subject_dept = $row['dept'];
    }
    $stmt->close();
}

// Current system date & time (for DB insert)
$date_today   = date("Y-m-d H:i:s");

//  Fetch students from that section, dept AND year
$sql = "SELECT studentId, studentName, year, academic_year, section, dept
        FROM userstudent
        WHERE section = ? AND dept = ? AND year = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $section, $subject_dept, $year);
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance'])) {
    $subject_code  = $_POST['subject_code'];
    $subject_name  = $_POST['subject_name'];
    $faculty_name  = $_POST['faculty_name'];
    $section       = $_POST['section'];
    $dept          = $_POST['dept'];
    $period        = $_POST['period'];
    $year          = $_POST['year'];   // <-- capture year in POST also

    foreach ($_POST['attendance'] as $student_id => $status) {
        $student_name  = $_POST['student_name'][$student_id];
        $year_db       = $_POST['year'][$student_id];
        $academic_year = $_POST['academic_year'][$student_id];

        $insert = "INSERT INTO attendance 
                  (student_id, student_name, year, academic_year, subject_code, subject_name, section, faculty_name, status, time, period, dept) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)";

        $stmt = $conn->prepare($insert);
        $stmt->bind_param("sssssssssss", 
            $student_id,
            $student_name,
            $year_db,
            $academic_year,
            $subject_code,
            $subject_name,
            $section,
            $faculty_name,
            $status,
            $period,
            $dept
        );
        $stmt->execute();
        $stmt->close();
    }
    echo "<script>alert('Attendance Saved Successfully!'); window.location='faculty_dashboard.php';</script>";
}

$conn->close();
?>


<!DOCTYPE html>
<html>
<head>
    <title>Take Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .header{
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="container mt-4">

    <div class='header'>
        <h2>Take Attendance</h2>
        <a href="faculty_dashboard.php" class="btn btn-primary ms-2">Back to Dashboard</a>
    </div>

    <form method="POST">  
        <p><b>Name of the faculty:</b> <?php echo htmlspecialchars($faculty_name); ?> <br>
           <b>Subject:</b> <?php echo htmlspecialchars($subject_name); ?> (<?php echo htmlspecialchars($subject_code); ?>) <br>
           <b>Section:</b> <?php echo htmlspecialchars($section); ?> <br>
           <b>Department:</b> <?php echo htmlspecialchars($subject_dept); ?> <br>
           <b>Date:</b> <?php echo date("d-m-Y H:i:s"); ?><br>
           <b>Period:</b><input type="number" name="period" min="1" max="7" placeholder="Enter the period"  style="width: 140px;" required> 

        <table class="table table-bordered mt-3">
            <thead class="table-primary">
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Year</th>
                    <th>Academic Year</th>
                    <th>Status (P/A)</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($students->num_rows > 0) { ?>
                <?php while($row = $students->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['studentId']); ?></td>
                        <td><?php echo htmlspecialchars($row['studentName']); ?></td>
                        <td><?php echo htmlspecialchars($row['year']); ?></td>
                        <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                        <td>
                            <input type="radio" name="attendance[<?php echo $row['studentId']; ?>]" value="P" required> P
                            <input type="radio" name="attendance[<?php echo $row['studentId']; ?>]" value="A"> A
                        </td>
                    </tr>
                    <!-- Hidden fields -->
                    <input type="hidden" name="student_name[<?php echo $row['studentId']; ?>]" value="<?php echo htmlspecialchars($row['studentName']); ?>">
                    <input type="hidden" name="year[<?php echo $row['studentId']; ?>]" value="<?php echo htmlspecialchars($row['year']); ?>">
                    <input type="hidden" name="academic_year[<?php echo $row['studentId']; ?>]" value="<?php echo htmlspecialchars($row['academic_year']); ?>">
                    <input type="hidden" name="subject_code" value="<?php echo htmlspecialchars($subject_code); ?>">
                    <input type="hidden" name="subject_name" value="<?php echo htmlspecialchars($subject_name); ?>">
                    <input type="hidden" name="faculty_name" value="<?php echo htmlspecialchars($faculty_name); ?>">
                    <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
                    <input type="hidden" name="dept" value="<?php echo htmlspecialchars($subject_dept); ?>">
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">No data available</td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <button type="submit" class="btn btn-success">Save Attendance</button>
    </form>

</body>
</html>
