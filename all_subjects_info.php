<?php
session_start();
// Allow only dept_office users
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office'){
    die("Access Denied. This action is only allowed for Department Office users.");
}

include 'db_connect.php';

$dept = $_SESSION['dept'];

// Filters
$yearFilter = isset($_GET['year']) && $_GET['year'] !== '' ? $_GET['year'] : null;
$semesterFilter = isset($_GET['semester']) && $_GET['semester'] !== '' ? $_GET['semester'] : null;
$academicYearFilter = isset($_GET['academic_year']) && $_GET['academic_year'] !== '' ? $_GET['academic_year'] : '2025-26'; // default 🔹


// Base query - unique subjects 
$sql = "SELECT subject_code, subject_name, year, semester, academic_year, MIN(date_time) as date
        FROM subjects 
        WHERE dept = ?";

$params = [$dept];
$types  = "s";   

// Add filters if chosen
if($yearFilter){
    $sql .= " AND year = ?";
    $types .= "s";
    $params[] = $yearFilter;
}
if($semesterFilter){
    $sql .= " AND semester = ?";
    $types .= "s";
    $params[] = $semesterFilter;
}
if($academicYearFilter){  // 🔹 mandatory filter
    $sql .= " AND academic_year = ?";
    $types .= "s";
    $params[] = $academicYearFilter;
}

// Grouping ensures uniqueness
$sql .= " GROUP BY subject_code, subject_name, year, semester, academic_year
          ORDER BY year, semester, subject_code";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Subjects Info</title>
</head>
<body>
<div class="container py-5">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-center position-relative mb-4">
        <div class="text-center">
            <h2>Department of <?php echo htmlspecialchars($dept); ?></h2> 
            <h3>Subjects Details</h3>
        </div>
        <a href="dept_office_dashboard.php" class="btn btn-primary position-absolute end-0">
            Dashboard
        </a>
    </div>

        <!-- Filter Form (Auto-submit on change, no button) -->
        <form method="GET" class="row g-3 mb-4 align-items-end">
            <div class="col-md-4">
                <label for="year" class="form-label">Year</label>
                <select name="year" id="year" class="form-select" onchange="this.form.submit()">
                    <option value="E1" <?php if($yearFilter=="E1") echo "selected"; ?>>E1</option>
                    <option value="E2" <?php if($yearFilter=="E2") echo "selected"; ?>>E2</option>
                    <option value="E3" <?php if($yearFilter=="E3") echo "selected"; ?>>E3</option>
                    <option value="E4" <?php if($yearFilter=="E4") echo "selected"; ?>>E4</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="semester" class="form-label">Semester</label>
                <select name="semester" id="semester" class="form-select" onchange="this.form.submit()">
                    <option value="1" <?php if($semesterFilter=="1") echo "selected"; ?>>1</option>
                    <option value="2" <?php if($semesterFilter=="2") echo "selected"; ?>>2</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="academic_year" class="form-label">Academic Year</label>
                <select name="academic_year" id="academic_year" class="form-select" onchange="this.form.submit()">
                    <option value="2025-26" <?php if($academicYearFilter=="2025-26") echo "selected"; ?>>2025-26</option>
                    <option value="2024-25" <?php if($academicYearFilter=="2024-25") echo "selected"; ?>>2024-25</option>
                </select>
            </div>
        </form>

        <!-- Subjects Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover shadow-sm">
                <thead class="table-primary text-center">
                    <tr>
                        <th>S.No</th>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Year</th>
                        <th>Semester</th>
                        <th>Academic Year</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                        <tr class="align-middle text-center">
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
                            <td class="text-start"><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['year']); ?></td>
                            <td><?php echo htmlspecialchars($row['semester']); ?></td>
                            <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No subjects found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
   </div>
</body>
</html>
