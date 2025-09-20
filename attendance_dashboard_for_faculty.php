<?php
session_start();

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'faculty') {
    die("Access Denied. Faculty only.");
}

// Set timezone to India (IST)
date_default_timezone_set("Asia/Kolkata");

include 'db_connect.php';

// Get data from query params
$faculty_id   = $_GET['faculty_id'] ?? '';
$faculty_name = $_GET['faculty_name'] ?? '';
$subject_code = $_GET['subject_code'] ?? '';
$subject_name = $_GET['subject_name'] ?? '';
$section      = $_GET['section'] ?? '';
$year         = $_GET['year'] ?? '';   

// Get department from subjects table
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

// Fetch students
$sql = "SELECT studentId, studentName, year, academic_year, section, dept, semester
        FROM userstudent
        WHERE section = ? AND dept = ? AND year = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $section, $subject_dept, $year);
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();

$students_data = [];
while ($row = $students->fetch_assoc()) {
    $students_data[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_code  = $_POST['subject_code'];
    $subject_name  = $_POST['subject_name'];
    $faculty_name  = $_POST['faculty_name'];
    $section       = $_POST['section'];
    $dept          = $_POST['dept'];
    $period        = (int)$_POST['period']; 
    $month         = $_POST['month']; // ✅ now comes from inside form

    foreach ($_POST['student_name'] as $student_id => $student_name) {
        $year_db       = $_POST['year'][$student_id];
        $academic_year = $_POST['academic_year'][$student_id];
        $semester      = (int)$_POST['semester'][$student_id];

        $status = isset($_POST['attendance'][$student_id]) ? 'P' : 'A';

        $insert = "INSERT INTO attendance 
                  (student_id, student_name, year, academic_year, subject_code, subject_name, section, faculty_name, status, `time`, period, dept, semester, month) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";

        $stmt = $conn->prepare($insert);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param(
            "sssssssssisis",
            $student_id,    // s
            $student_name,  // s
            $year_db,       // s
            $academic_year, // s
            $subject_code,  // s
            $subject_name,  // s
            $section,       // s
            $faculty_name,  // s
            $status,        // s
            $period,        // i
            $dept,          // s
            $semester,      // i
            $month          // s ✅ now works
        );

        if (!$stmt->execute()) {
            die("Insert failed: " . $stmt->error);
        }
        $stmt->close();
    }

    echo "<script>alert('Attendance Saved Successfully!'); window.location='faculty_dashboard.php';</script>";
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Take Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; font-family: Arial, sans-serif; padding: 20px; }
        h1, h2, h5 { color: #333; }

        .header {
            position: relative;
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 { margin: 0; }
        .header a {
            position: absolute;
            right: 0;
            top: 0;
        }

        .card {
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background: #fff;
            margin-bottom: 20px;
        }

        table th, table td { vertical-align: middle; }
    </style>
</head>
<body class="container mt-4">

    <!-- Header -->
    <div class="header">
        
        <h1>Attendance Dashboard</h1>
        <a href="faculty_dashboard.php" class="btn btn-primary">Dashboard</a>
    </div>

    <!-- Attendance Form (now includes period + month inputs) -->
    <form method="POST">
        <!-- Faculty/Class Details -->
        <div class="card">
            <!-- <h5><strong><center>Class & Faculty Details</strong></center></h5><br> -->
            <div class="row">
                <!-- Left column (Class details) -->
                <div class="col-md-6">
                    <p>
                       <b>Subject:</b> <?php echo htmlspecialchars($subject_name); ?> (<?php echo htmlspecialchars($subject_code); ?>)<br>
                       <b>Year:</b> <?php echo htmlspecialchars($year); ?><br>
                       <b>Semester:</b> <?php echo htmlspecialchars(!empty($students_data) ? $students_data[0]['semester'] : 'N/A'); ?><br>
                       <b>Department:</b> <?php echo htmlspecialchars($subject_dept); ?><br>
                       <b>Section:</b> <?php echo htmlspecialchars($section); ?><br>
                       <b>Academic Year:</b> <?php echo htmlspecialchars(!empty($students_data) ? $students_data[0]['academic_year'] : 'N/A'); ?><br>
                       <b>Date & Time:</b> <?php echo date("d-m-Y H:i:s"); ?><br>
                    </p>
                </div>

                <!-- Right column (Faculty + Inputs) -->
                <div class="col-md-6">
                    <p>
                       <b>Faculty Name:</b> <?php echo htmlspecialchars($faculty_name); ?><br>
        
                       <!-- Period -->
                       <b>Period:</b><br>
                       <input type="number" name="period" min="1" max="7" placeholder="Enter period" 
                              class="form-control mb-3" style="width: 150px;" required>

                       <!-- Month/Test dropdown -->
                       <b>Month/Test:</b><br>
                       <select name="month" class="form-select" style="width: 160px;" required>
                           <option value="">Select</option>
                           <option value="MT-1">MT-1</option>
                           <option value="MT-2">MT-2</option>
                           <option value="MT-3">MT-3</option>
                       </select>
                    </p>
                </div>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="card">
            <h5><strong>Mark Attendance</strong></h5>
            <table class="table table-bordered table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>S.No</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Status (Present)</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($students_data)) { ?>
                    <?php foreach ($students_data as $i => $row) { ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($row['studentId']); ?></td>
                            <td><?php echo htmlspecialchars($row['studentName']); ?></td>
                            <td>
                                <input type="checkbox" name="attendance[<?php echo $row['studentId']; ?>]" value="P" checked>
                            </td>
                        </tr>
                        <!-- Hidden fields -->
                        <input type="hidden" name="student_name[<?php echo $row['studentId']; ?>]" value="<?php echo htmlspecialchars($row['studentName']); ?>">
                        <input type="hidden" name="year[<?php echo $row['studentId']; ?>]" value="<?php echo htmlspecialchars($row['year']); ?>">
                        <input type="hidden" name="semester[<?php echo $row['studentId']; ?>]" value="<?php echo htmlspecialchars($row['semester']); ?>">
                        <input type="hidden" name="academic_year[<?php echo $row['studentId']; ?>]" value="<?php echo htmlspecialchars($row['academic_year']); ?>">
                        <input type="hidden" name="subject_code" value="<?php echo htmlspecialchars($subject_code); ?>">
                        <input type="hidden" name="subject_name" value="<?php echo htmlspecialchars($subject_name); ?>">
                        <input type="hidden" name="faculty_name" value="<?php echo htmlspecialchars($faculty_name); ?>">
                        <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
                        <input type="hidden" name="dept" value="<?php echo htmlspecialchars($subject_dept); ?>">
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">No students available</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>

            <button type="submit" class="btn btn-success">Save Attendance</button>
        </div>
    </form>

</body>
</html>
