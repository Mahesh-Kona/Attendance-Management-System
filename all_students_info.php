<?php
session_start();
// Ensure only department office users can access this page
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office'){
    die("Access Denied. This action is only allowed for Department Office users.");
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "attendance_management_system";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch all students in the department
$dept = $_SESSION['dept'];
$sql = "SELECT studentId, studentName, year, section, contact FROM userstudent WHERE dept=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $dept);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Students Info</title>
    <style>
        table, th, td { border: 1px solid black; border-collapse: collapse; padding: 8px;    }
        th { background-color: #f2f2f2; }
        .header-container {text-align: right; padding: 10px; background-color: #f8f9fa; }
    </style>
</head>
<body>
   <div class="header-container">
    <a href="dept_office_dashboard.php" class="btn btn-primary">Dashboard</a>
</div>
    
   
    
    <div class="table-responsive">
         <h2 class="text-center">Department of <?php echo htmlspecialchars($dept); ?></h2>
            <h3 class="text-center">Students details</h3>
    <table class="table table-bordered table-striped table-hover shadow-sm">
        <thead class="table-primary text-center">
            <tr>
                <th>S.No</th>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Year</th>
                <th>Section</th>
                <th>Contact</th>
            </tr>
        </thead>
        <tbody>
            <?php if($result->num_rows > 0): ?>
                <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                <tr class="align-middle">
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['studentId']); ?></td>
                    <td><?php echo htmlspecialchars($row['studentName']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['year']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['section']); ?></td>
                    <td><?php echo htmlspecialchars($row['contact']); ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">No students found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

    </table>
</body>
</html>
