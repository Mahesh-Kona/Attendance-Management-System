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
$academic_year='2025-26';
// Fetch students
$sql = "SELECT studentId, studentName, year, '2025-26', section, dept,'1'
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
    $month         = $_POST['month'];

    // New: Get no_of_periods and period(s)
    $no_of_periods = isset($_POST['no_of_periods']) ? (int)$_POST['no_of_periods'] : 0;
    $period_input = $_POST['period'];
    $periods = array_map('trim', explode(',', $period_input));
    if ($no_of_periods > 0) {
        $periods = array_slice($periods, 0, $no_of_periods);
    }

    //modify it for every sem
    foreach ($_POST['student_name'] as $student_id => $student_name) {
        $year_db       = $_POST['year'][$student_id];
        $academic_year = '2025-26';
        $semester      = (int)$_POST['semester'][$student_id];
        $status = isset($_POST['attendance'][$student_id]) ? 'P' : 'A';

        foreach ($periods as $period) {
            $period = (int)$period;
            $insert = "INSERT INTO attendance 
                      (student_id, student_name, year,academic_year, subject_code, subject_name, section, faculty_name, status, `time`, period, dept, semester, month) 
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
                $month          // s
            );
            if (!$stmt->execute()) {
                die("Insert failed: " . $stmt->error);
            }
            $stmt->close();
        }
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
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
            padding: 20px 0 0 0;
        }
        .main-content {
            flex: 1 0 auto;
        }
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
        
        footer {
            background: #002147;
            color: #fff;
            text-align: center;
            padding: 15px 0;
            font-size: 0.9rem;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            margin-top: auto;
        }
    </style>
</head>
<body style="display:flex; flex-direction:column; min-height:100vh; padding-bottom:0;">

    <!-- Header -->
    <div class="header">
        
        <h1>Attendance Dashboard</h1>
        <a href="faculty_dashboard.php" class="btn btn-primary">Dashboard</a>
    </div>
    <div class="main-content">
       
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
                           <b>Semester:</b> <?php echo '1' ?><br>
                           <b>Department:</b> <?php echo htmlspecialchars($subject_dept); ?><br>
                           <b>Section:</b> <?php echo htmlspecialchars($section); ?><br>
                           <b>Academic Year:</b> <?php echo htmlspecialchars($academic_year);?><br>
                           <b>Date & Time:</b> <?php echo date("d-m-Y H:i:s"); ?><br>
                        </p>
                    </div>

                    <!-- Right column (Faculty + Inputs) -->
                    <div class="col-md-6">
                        <p>
                           <!-- No of Periods (optional) -->
                           <b>No of Periods (optional):</b><br>
                           <input type="number" name="no_of_periods" min="1" max="7" placeholder="Enter no of periods" 
                                  class="form-control mb-3" style="width: 150px;">

                           <!-- Period(s) -->
                           <b>Period(s):</b><br>
                           <input type="text" name="period" placeholder="Comma Seperated" 
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
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Roll No</th>

                            <th>
                                Present/Absent
                               &nbsp&nbsp&nbsp
                               <label for="checkAll">All</label>
                                <input type="checkbox" id="checkAll" onclick="toggleAll(this)" checked>
                                
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($students_data)) { ?>
                        <?php foreach ($students_data as $i => $row) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['studentId']); ?></td>
                                <td><?php echo htmlspecialchars($row['studentName']); ?></td>
                                <td><?php echo $i + 1; ?></td>
                                <td>
                                    <input type="checkbox" class="student-check" name="attendance[<?php echo $row['studentId']; ?>]" value="P" checked>
                                </td>
                            </tr>
                            <!-- Hidden fields -->
                            <input type="hidden" name="student_name[<?php echo $row['studentId']; ?>]" value="<?php echo htmlspecialchars($row['studentName']); ?>">
                            <input type="hidden" name="year[<?php echo $row['studentId']; ?>]" value="<?php echo htmlspecialchars($row['year']); ?>">
                            <input type="hidden" name="semester[<?php echo $row['studentId']; ?>]" value="<?php echo 1; ?>">
                            <input type="hidden" name="academic_year[<?php echo $row['studentId']; ?>]" value="<?php htmlspecialchars($academic_year); ?>">
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
    </div>
    <footer>
    &copy; <?= date('Y') ?> Rajiv Gandhi University of Knowledge Technologies Nuzvid. All rights reserved.
    </footer>

    <script>
    function toggleAll(source) {
        var checkboxes = document.querySelectorAll('.student-check');
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = source.checked;
        }
    }
    </script>
</body>
</html>
