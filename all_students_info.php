<?php
session_start();
// Ensure only department office users can access this page
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office'){
    die("Access Denied. This action is only allowed for Department Office users.");
}

include 'db_connect.php'; 

// Fetch all students in the department with filters
$dept = $_SESSION['dept'];

$yearFilter = isset($_GET['year']) && $_GET['year'] !== '' ? $_GET['year'] : null;
$sectionFilter = isset($_GET['section']) && $_GET['section'] !== '' ? $_GET['section'] : null;

$sql = "SELECT studentId, studentName, year, section, contact 
        FROM userstudent 
        WHERE dept = ?";

$params = [$dept];
$types = "s";

if($yearFilter){
    $sql .= " AND year = ?";
    $types .= "s";
    $params[] = $yearFilter;
}

if($sectionFilter){
    $sql .= " AND section = ?";
    $types .= "s";
    $params[] = $sectionFilter;
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
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
        table, th, td { border: 1px solid black; border-collapse: collapse; padding: 8px; }
        th { background-color: #f2f2f2; }
        .header-container { text-align: right; padding: 10px; background-color: #f8f9fa; }
    </style>
</head>
<body>
   

   <div class="container mt-4">
     
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-center position-relative mb-4">
        <div class="text-center">
            <h2>Department of <?php echo htmlspecialchars($dept); ?></h2> 
            <h3>Students Data </h3>
        </div>
        <a href="dept_office_dashboard.php" class="btn btn-primary position-absolute end-0">
            Dashboard
        </a>
    </div>
        <!-- Filter Form -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-6">
                <label for="year" class="form-label">Year</label>
                <select name="year" id="year" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="E1" <?php if($yearFilter=="E1") echo "selected"; ?>>E1</option>
                    <option value="E2" <?php if($yearFilter=="E2") echo "selected"; ?>>E2</option>
                    <option value="E3" <?php if($yearFilter=="E3") echo "selected"; ?>>E3</option>
                    <option value="E4" <?php if($yearFilter=="E4") echo "selected"; ?>>E4</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="section" class="form-label">Section</label>
                <select name="section" id="section" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="1" <?php if($sectionFilter=="1") echo "selected"; ?>>1</option>
                    <option value="2" <?php if($sectionFilter=="2") echo "selected"; ?>>2</option>
                    <option value="3" <?php if($sectionFilter=="3") echo "selected"; ?>>3</option>
                    <option value="4" <?php if($sectionFilter=="4") echo "selected"; ?>>4</option>
                    <option value="5" <?php if($sectionFilter=="5") echo "selected"; ?>>5</option>
                    <option value="6" <?php if($sectionFilter=="6") echo "selected"; ?>>6</option>
                </select>
            </div>
        </form>

        <!-- Students Table -->
        <div class="table-responsive">
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
   </div>
</body>
</html>
