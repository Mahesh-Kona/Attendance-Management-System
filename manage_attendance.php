<?php
session_start();
include 'db_connect.php'; 

// Ensure user is logged in and is dept_office
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office'){
    die("Access Denied. This page is only for Department Office users.");
}   

$dept = $_SESSION['dept'];

// Fetch subjects for this dept
$sql = "SELECT subject_code, subject_name, faculty_name, faculty_id, year, semester, section 
        FROM subjects 
        WHERE dept = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $dept);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="container mt-5">

    <!-- Header with Dept Name and Dashboard -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>No. of Working Days - <?php echo htmlspecialchars($dept); ?></h2>
        <a href="dept_office_dashboard.php" class="btn btn-primary">Dashboard</a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover shadow-sm">
            <thead class="table-primary text-center">
                <tr>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Faculty Name</th>
                    <th>Year</th>
                    <th>Semester</th>
                    <th>Section</th>
                    <th>No of Classes Conducted</th>
                </tr>
            </thead>
            <tbody>
              <?php while ($row = $result->fetch_assoc()) { 
    // Count total number of attendance sessions (including multiple per day)
$count_sql = "SELECT COUNT(DISTINCT DATE(time), HOUR(time), MINUTE(time)) AS total_classes
              FROM attendance 
              WHERE subject_code = ? 
                AND faculty_name = ? 
                AND section = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("sis", $row['subject_code'], $row['faculty_name'], $row['section']);

    $count_stmt->execute();
    $count_res = $count_stmt->get_result();
    $count_row = $count_res->fetch_assoc();
    $total_classes = $count_row['total_classes'] ?? 0;
    $count_stmt->close();
?>
<tr>
    <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
    <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
    <td><?php echo htmlspecialchars($row['faculty_name']); ?></td>
    <td><?php echo htmlspecialchars($row['year']); ?></td>
    <td><?php echo htmlspecialchars($row['semester']); ?></td>
    <td><?php echo htmlspecialchars($row['section']); ?></td>
    <td class="text-center fw-bold"><?php echo $total_classes; ?></td>
</tr>
<?php } ?>

            </tbody>
        </table>
    </div>

</body>
</html>
