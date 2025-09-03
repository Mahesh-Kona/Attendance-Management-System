<?php
session_start();
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'hod') {
    die("Access Denied. Only HODs can access this page.");
}

$hodUsername = $_SESSION['userID']; // assuming username stored in session

$host = "localhost";
$user = "root";
$pass = "";
$db   = "attendance_management_system";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch HOD department from admin_roles
$dept_sql = "SELECT dept FROM admin_roles WHERE username = ?";
$stmt = $conn->prepare($dept_sql);
$stmt->bind_param("s", $hodUsername);
$stmt->execute();
$result = $stmt->get_result();
$hod = $result->fetch_assoc();
$dept = $hod['dept'];
$stmt->close();

// 1. Get all subjects in this dept (only required columns)
$subjects_sql = "SELECT subject_code, subject_name, year, semester, credits 
                 FROM subjects 
                 WHERE dept = ?";
$stmt = $conn->prepare($subjects_sql);
$stmt->bind_param("s", $dept);
$stmt->execute();
$subjects_result = $stmt->get_result();
$stmt->close();

// 2. Get faculty
$faculty_sql = "SELECT facultyName,facultyId,contact
                FROM userfaculty
                WHERE dept = ?
                ORDER BY facultyName";
$stmt = $conn->prepare($faculty_sql);
$stmt->bind_param("s", $dept);
$stmt->execute();
$faculty_result = $stmt->get_result();
$stmt->close();

// 3. Get all students in dept
$students_sql = "SELECT studentID, studentName,contact, year, section 
                 FROM userstudent 
                 WHERE dept = ?
                 ORDER BY year, section";
$stmt = $conn->prepare($students_sql);
$stmt->bind_param("s", $dept);
$stmt->execute();
$students_result = $stmt->get_result();
$stmt->close();

// 4. Attendance stats (section-wise)
$attendance_sql = "
    SELECT s.subject_code, s.subject_name, s.section,
           COUNT(a.id) AS total_marked,
           SUM(CASE WHEN a.status='P' THEN 1 ELSE 0 END) AS total_present
    FROM subjects s
    LEFT JOIN attendance a ON s.subject_code = a.subject_code
    WHERE s.dept = ?
    GROUP BY s.subject_code, s.subject_name, s.section
    ORDER BY s.section, s.subject_name
";
$stmt = $conn->prepare($attendance_sql);
$stmt->bind_param("s", $dept);
$stmt->execute();
$attendance_result = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HOD Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background: #f5f5f5; font-family: Arial, sans-serif; padding: 20px; }
    h2 { margin-top: 40px; }
    .card { padding: 20px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
</style>
</head>
<body>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>HOD Dashboard - <?php echo htmlspecialchars($dept); ?> Department</h1>
    <a class="btn btn-danger" href="index.php">Logout</a>
</div>

<div class="container">
   <!-- Subjects -->
<div class="card">
    <h2>Subjects in Department</h2>
    <table class="table table-bordered">
        <thead class="table-primary"><tr>
            <th>Code</th>
            <th>Name</th>
            <th>Year</th>
            <th>Semester</th>
            <th>Credits</th>
        </tr></thead>
        <tbody>
        <?php while($row = $subjects_result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['subject_code']); ?></td>
                <td><?= htmlspecialchars($row['subject_name']); ?></td>
                <td><?= htmlspecialchars($row['year']); ?></td>
                <td><?= htmlspecialchars($row['semester']); ?></td>
                <td><?= htmlspecialchars($row['credits']); ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>


    <!-- Faculty -->
    <div class="card">
        <h2>Faculty</h2>
        <table class="table table-bordered">
            <thead class="table-success"><tr>
                <th>Faculty Name</th><th>Faculty Id</th><th>Contact</th>
            </tr></thead>
            <tbody>
            <?php while($row = $faculty_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['facultyId']); ?></td>
                    <td><?= htmlspecialchars($row['facultyName']); ?></td>
                    <td><?= htmlspecialchars($row['contact']); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>


    <!-- Students -->
    <div class="card">
        <h2>Students in Department</h2>
        <table class="table table-bordered">
            <thead class="table-warning"><tr>
                <th>Student ID</th><th>Name</th><th>contact</th><th>Year</th><th>Section</th>
            </tr></thead>
            <tbody>
            <?php while($row = $students_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['studentID']); ?></td>
                    <td><?= htmlspecialchars($row['studentName']); ?></td>
                    <td><?= htmlspecialchars($row['contact']); ?></td>
                    <td><?= htmlspecialchars($row['year']); ?></td>
                    <td><?= htmlspecialchars($row['section']); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Attendance -->
    <div class="card">
        <h2>Attendance Statistics (Section-wise)</h2>
        <table class="table table-bordered">
            <thead class="table-info"><tr>
                <th>Subject Code</th><th>Subject Name</th><th>Section</th><th>Total Marked</th><th>Total Present</th><th>Overall %</th>
            </tr></thead>
            <tbody>
            <?php while($row = $attendance_result->fetch_assoc()):
                $percent = ($row['total_marked'] > 0) ? ($row['total_present'] / $row['total_marked']) * 100 : 0;
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['subject_code']); ?></td>
                    <td><?= htmlspecialchars($row['subject_name']); ?></td>
                    <td><?= htmlspecialchars($row['section']); ?></td>
                    <td><?= $row['total_marked']; ?></td>
                    <td><?= $row['total_present']; ?></td>
                    <td><?= number_format($percent, 2); ?>%</td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
