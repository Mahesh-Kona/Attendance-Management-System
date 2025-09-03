<?php
session_start();
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office'){
    die("Access Denied. This page is only for Department Office users.");
}

$userID = $_SESSION['userID'];

$host = "localhost";
$user = "root";
$pass = "";
$db   = "attendance_management_system";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error) die("Connection failed: " . $conn->connect_error);

//Get dept code of logged-in user
$stmt = $conn->prepare("SELECT dept FROM admin_roles WHERE username = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$stmt->bind_result($dept_code);
$stmt->fetch();
$stmt->close();

//  Map dept codes to full names
$dept_names = [
    'CSE' => 'Computer Science & Engineering',
    'ECE' => 'Electronics & Communication Engineering',
    'MECH' => 'Mechanical Engineering',
    'EEE' => 'Electrical & Electronics Engineering',
    'CIVIL' => 'Civil Engineering',
    'MME' => 'Metallurgical & Material Science Engineering',
    'CHEMICAL' => 'Chemical Engineering'
];

$dept_full = isset($dept_names[$dept_code]) ? $dept_names[$dept_code] : $dept_code;

// Fetch all faculty and their subjects (if any) for this department
$sql = "
    SELECT u.facultyId, u.facultyName, s.subject_code,s.year,s.section, s.subject_name, s.credits, s.semester, s.date_time
    FROM userfaculty u
    LEFT JOIN subjects s ON u.facultyId = s.faculty_id
    WHERE u.dept = ?
    ORDER BY u.facultyName ASC, s.date_time DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $dept_code);
$stmt->execute();
$result = $stmt->get_result();
$faculty_subjects = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Faculty Allotment History</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
       <h2>Faculty Allotment History - <?php echo htmlspecialchars($dept_full); ?></h2> 
        <a href="dept_office_dashboard.php" class="btn btn-primary">
            Back to Dashboard
        </a>
    </div>
<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover shadow-sm">
        <thead class="table-primary text-center">
            <tr>
                <th>S.No</th>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th>Credits</th>
                <th>Year</th>
                <th>Section</th>
                <th>Semester</th>
                <th>Faculty ID</th>
                <th>Faculty Name</th>
                <th>Date & Time</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($faculty_subjects)): ?>
                <?php $i = 1; foreach($faculty_subjects as $fs): ?>
                <tr class="align-middle">
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($fs['subject_code'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($fs['subject_name'] ?? 'N/A'); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($fs['credits'] ?? 'N/A'); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($fs['semester'] ?? 'N/A'); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($fs['section'] ?? 'N/A'); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($fs['year'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($fs['facultyId']); ?></td>
                    <td><?php echo htmlspecialchars($fs['facultyName']); ?></td>
                    <td><?php echo htmlspecialchars($fs['date_time'] ?? 'N/A'); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center text-muted">No faculty found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
