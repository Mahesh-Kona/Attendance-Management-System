<?php
session_start();
include 'db_connect.php'; 

// Ensure user is logged in and is dept_office
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office'){
    die("Access Denied. This page is only for Department Office users.");
}   

$dept = $_SESSION['dept'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['working_days'] as $key => $days) {
        $days = intval($days);

        
        list($subject_code, $section) = explode('_', $key);

        $sql = "UPDATE subjects 
                SET no_of_working_days = ? 
                WHERE subject_code = ? AND section = ? AND dept = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("isis", $days, $subject_code, $section, $dept);

        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }

        $stmt->close();
    }

    echo "<script>
            alert('Working days updated successfully!');
            window.location.href='manage_attendance.php';
          </script>";
    exit;
}

// Fetch subjects for this dept
$sql = "SELECT subject_code, subject_name,faculty_name,year,semester,section,no_of_working_days 
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

    <!-- Header with Dept Name and Logout -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage the no.of Working Days-<?php echo htmlspecialchars($dept); ?></h2>
        <a href="dept_office_dashboard.php" class="btn btn-primary">Dashboard</a>
    </div>

    <form method="POST">
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
            <th>No. of Working Days</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
            <td><?php echo htmlspecialchars($row['faculty_name']); ?></td>
            <td><?php echo htmlspecialchars($row['year']); ?></td>
            <td><?php echo htmlspecialchars($row['semester']); ?></td>
            <td><?php echo htmlspecialchars($row['section']); ?></td>
            <td>
    <input type="number" 
           name="working_days[<?php echo $row['subject_code'] . '_' . $row['section']; ?>]" 
           value="<?php echo htmlspecialchars($row['no_of_working_days']); ?>" 
           class="form-control" min="0">
</td>

        </tr>
        <?php } ?>
    </tbody>
</table>
        </div>
        <div class="text-center">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>

</body>
</html>