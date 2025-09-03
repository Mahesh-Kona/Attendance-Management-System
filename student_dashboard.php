<?php

session_start();
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student'){
    die("Access Denied. Only students can access this page.");
}

$studentID = $_SESSION['userID'];

$host = "localhost";
$user = "root";
$pass = "";
$db   = "attendance_management_system";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch student details including year, dept, section
$student_sql = "SELECT studentID, studentName, year, dept, section FROM userstudent WHERE studentID=?";
$stmt = $conn->prepare($student_sql);
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

$year = $student['year'];
$dept = $student['dept'];
$section = $student['section'];

// Fetch subjects allotted to this student filtered by year, dept, section
$subjects_sql = "
SELECT 
    s.subject_name, 
    s.subject_code, 
    s.faculty_name, 
    s.no_of_working_days, 
    s.section,
    SUM(CASE WHEN a.status='P' THEN 1 ELSE 0 END) AS days_attended
FROM subjects s
LEFT JOIN attendance a 
    ON s.subject_code = a.subject_code 
   AND a.student_id = ?
WHERE s.year = ? 
  AND s.dept = ? 
  AND s.section = ?
GROUP BY s.subject_code, s.subject_name, s.faculty_name, s.no_of_working_days, s.section
ORDER BY s.subject_name
";

$stmt = $conn->prepare($subjects_sql);
$stmt->bind_param("ssss", $studentID, $year, $dept, $section);
$stmt->execute();
$subjects_result = $stmt->get_result();
$stmt->close();

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #f5f5f5; font-family: Arial, sans-serif; padding: 20px; }
    h1, h2, h5 { color: #333; }
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .card { border-radius: 15px; padding: 50px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); background: #fff; }
    table th, table td { vertical-align: middle; }
    a.button { display: inline-block; padding: 10px 15px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px; }
    a.button:hover { background: #0056b3; }
    .low-attendance { background-color: #f8d7da !important; color: #842029; font-weight: bold; }
</style>
</head>
<body>

<div class="header">
    <h1>Student Dashboard</h1>
    <a class="button" href="index.php">Logout</a>
</div>

<div class="container">
    <div class="card">
        <div class="mb-5">
            <h5><strong>Student Details</strong></h5>
            <strong>ID:</strong> <?php echo htmlspecialchars($student['studentID']); ?><br>
            <strong>Name:</strong> <?php echo htmlspecialchars($student['studentName']); ?><br>
            <strong>Year:</strong> <?php echo htmlspecialchars($year); ?><br>
            <strong>Department:</strong> <?php echo htmlspecialchars($dept); ?><br>
            <strong>Section:</strong> <?php echo htmlspecialchars($student['section'] ?? 'N/A'); ?><br>
        </div>

        <h5><strong>Attendance Details</strong></h5>
        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>Serial No</th>
                    <th>Subject Name</th>
                    <th>Subject Code</th>
                    <th>Faculty Name</th>
                    <th>Days Attended</th>
                    <th>Total Lectures</th>
                    <th>Attendance %</th>
                    <th>EST Eligibility</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $serial = 1;
                while($row = $subjects_result->fetch_assoc()):
                    $days_attended = $row['days_attended'];
                    $total_lectures = $row['no_of_working_days']; // from subjects table
                    $percent = ($total_lectures > 0) ? ($days_attended / $total_lectures) * 100 : 0;
                    $lowAttendance = $percent < 75;
                ?>
                <tr class="<?php echo $lowAttendance ? 'low-attendance' : ''; ?>">
                    <td><?php echo $serial++; ?></td>
                    <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
                    <td><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                    <td><?php echo $days_attended; ?></td>
                    <td><?php echo $total_lectures; ?></td>
                    <td><?php echo number_format($percent, 2); ?>%</td>
                    <td>
                        <?php echo $lowAttendance ? 'Not Allowed to Write EST' : 'Allowed'; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <p><strong>Note:</strong>You have less attendance for the subjects which are highlighted</p>

    </div>
</div>

</body>
</html>
