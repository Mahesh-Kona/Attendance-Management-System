<?php
session_start();

// Check if faculty is logged in
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'faculty'){
    die("Access Denied. This action is only allowed for Faculty users.");
}

include 'db_connect.php';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$facultyID = $_SESSION['userID'];

// Fetch faculty details from userfaculty
$sql = "SELECT facultyID, facultyName, dept FROM userfaculty WHERE facultyID=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $facultyID);
$stmt->execute();
$result = $stmt->get_result();
$faculty = $result->fetch_assoc();
$stmt->close();

$facultyName = $faculty['facultyName'];
$dept = $faculty['dept'];

// Fetch subjects allotted to this faculty from subjects table modify this for every sem
$sql = "SELECT subject_code, subject_name, credits,date_time, year, section
        FROM subjects WHERE faculty_id=? AND semester='1' AND academic_year='2025-26' ";
        $semester=2;
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $facultyID);
$stmt->execute();
$subjects = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Faculty Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden; /* Prevent horizontal scroll bar */
        }
        body {
            min-height: 100vh;
            background: #f5f5f5;
            font-family: Arial, sans-serif;
            padding: 20px;
            position: relative;
        }
        .main-content {
            padding-bottom: 60px; /* space for footer */
        }
        h1 { color: #333; margin: 0;text-align: center; }

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

        .info-box {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            width: 50%;
            margin: 0 auto 20px auto;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: left;
        }

        a.button { display: inline-block; padding: 10px 15px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px; }
        a.button:hover { background: #0056b3; }
        a.link { color: #007bff; text-decoration: none; }
        a.link:hover { text-decoration: underline; }
        footer {
            background: #002147;
            color: #fff;
            text-align: center;
            padding: 15px 0;
            font-size: 0.9rem;
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            box-sizing: border-box;
        }
        .table {
            width: 100%;
            min-width: 900px;
            box-sizing: border-box;
            overflow-x: auto;
        }
    </style>
</head>
<body style="position:relative; min-height:100vh;">

    <!-- Header -->
    <div class="header">
        
        <h1>Faculty Dashboard</h1>
        <a href="index.php" class="btn btn-primary" >Logout</a>
    </div>
    <div class="main-content">
        <!-- Faculty Info Box -->
        <div class="info-box">
            <p><b>Faculty ID:</b> <?php echo htmlspecialchars($facultyID); ?></p>
            <p><b>Faculty Name:</b> <?php echo htmlspecialchars($facultyName); ?></p>
            <p><b>Department:</b> <?php echo htmlspecialchars($dept); ?></p>
        </div>

        <br><br>
        <h3>Your Allotted Subjects:</h3>

        <!-- Your old table kept as-is -->
        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Credits</th>
                    <th>Year</th>
                    <th>Academic Year</th>
                    <th>Semester</th>
                    <th>Reg Date</th>
                    <th>Section</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($subjects->num_rows > 0) { ?>
                    <?php while ($row = $subjects->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['credits']); ?></td>
                            <td><?php echo htmlspecialchars($row['year']); ?></td>
                            <td><?php echo '2025-26'; ?></td>
                            <td><?php echo 1; ?></td>
                            <td><?php echo htmlspecialchars($row['date_time']); ?></td>
                             <td><?php echo htmlspecialchars($row['section']); ?></td>
                            <td>
                                <a class="btn btn-primary btn-sm" 
                                   href="attendance_dashboard_for_faculty.php?subject_code=<?php echo urlencode($row['subject_code']); ?>&subject_name=<?php echo urlencode($row['subject_name']); ?>&faculty_id=<?php echo urlencode($facultyID); ?>&faculty_name=<?php echo urlencode($facultyName); ?>&section=<?php echo urlencode($row['section']); ?>&year=<?php echo urlencode($row['year']); ?>">
                                    Take Attendance
                                </a>
                                <a class="btn btn-success btn-sm" 
                                   href="view_attendance_records.php?subject_code=<?php echo urlencode($row['subject_code']); ?>&subject_name=<?php echo urlencode($row['subject_name']); ?>&faculty_id=<?php echo urlencode($facultyID); ?>&faculty_name=<?php echo urlencode($facultyName); ?>&section=<?php echo urlencode($row['section']); ?>&year=<?php echo urlencode($row['year']); ?>">
                                    View Records
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">No data available</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <footer>
        &copy; <?= date('Y') ?> Rajiv Gandhi University of Knowledge Technologies Nuzvid. All rights reserved.
    </footer>
</body>
</html>


