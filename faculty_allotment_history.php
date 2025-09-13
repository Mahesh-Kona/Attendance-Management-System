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

// --- Get filters from request ---
$yearFilter = $_GET['year'] ?? 'All';
$sectionFilter = $_GET['section'] ?? 'All';

// Fetch all faculty and their subjects (if any) for this department
$sql = "
    SELECT u.facultyId, u.facultyName, s.subject_code, s.year, s.section, s.subject_name, 
           s.credits, s.semester, s.date_time
    FROM userfaculty u
    LEFT JOIN subjects s ON u.facultyId = s.faculty_id
    WHERE u.dept = ?
";

$params = [$dept_code];
$types = "s";

if ($yearFilter !== "All") {
    $sql .= " AND s.year = ? ";
    $params[] = $yearFilter;
    $types .= "s";
}
if ($sectionFilter !== "All") {
    $sql .= " AND s.section = ? ";
    $params[] = $sectionFilter;
    $types .= "s";
}

$sql .= " ORDER BY u.facultyName ASC, s.date_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
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
</head>
<body class="bg-light">

<div class="container py-5">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-center position-relative mb-4">
        <div class="text-center">
            <h2>Department of <?php echo htmlspecialchars($dept_full); ?></h2> 
            <h3>Faculty Allotment History</h3>
        </div>
        <a href="dept_office_dashboard.php" class="btn btn-primary position-absolute end-0">
            Dashboard
        </a>
    </div>

   <!-- Filters -->
<form method="get" class="row g-3 mb-4">
    <div class="col-md-6">
        <label for="year" class="form-label">Year</label>
        <select name="year" id="year" class="form-select" onchange="this.form.submit()">
            <option value="All" <?php if($yearFilter=="All") echo "selected"; ?>>All</option>
            <option value="E1" <?php if($yearFilter=="E1") echo "selected"; ?>>E1</option>
            <option value="E2" <?php if($yearFilter=="E2") echo "selected"; ?>>E2</option>
            <option value="E3" <?php if($yearFilter=="E3") echo "selected"; ?>>E3</option>
            <option value="E4" <?php if($yearFilter=="E4") echo "selected"; ?>>E4</option>
        </select>
    </div>
    <div class="col-md-6">
        <label for="section" class="form-label">Section</label>
        <select name="section" id="section" class="form-select" onchange="this.form.submit()">
            <option value="All" <?php if($sectionFilter=="All") echo "selected"; ?>>All</option>
            <option value="1" <?php if($sectionFilter=="1") echo "selected"; ?>>1</option>
            <option value="2" <?php if($sectionFilter=="2") echo "selected"; ?>>2</option>
            <option value="3" <?php if($sectionFilter=="3") echo "selected"; ?>>3</option>
            <option value="4" <?php if($sectionFilter=="5") echo "selected"; ?>>4</option>
            <option value="5" <?php if($sectionFilter=="5") echo "selected"; ?>>5</option>
            <option value="6" <?php if($sectionFilter=="6") echo "selected"; ?>>6</option>
        </select>
    </div>
</form>


    <!-- Table -->
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
                        <td class="text-center"><?php echo htmlspecialchars($fs['year'] ?? 'N/A'); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($fs['section'] ?? 'N/A'); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($fs['semester'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($fs['facultyId']); ?></td>
                        <td><?php echo htmlspecialchars($fs['facultyName']); ?></td>
                        <td><?php echo htmlspecialchars($fs['date_time'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted">No data available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
