<?php
session_start();
include 'db_connect.php'; // your DB connection

// Only allow HOD access
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'hod'){
    die("Access Denied");
}

// Dept from session
$dept = $_SESSION['dept']; 

$result = null;
$summaryPercent = null;
$overallDeptPercent = null;

$year = $_GET['year'] ?? '';
$section = $_GET['section'] ?? '';

if($dept){

    // STEP 1: Student-Subject level attendance %

    $sql = "SELECT a.student_id, a.subject_code,
                   COUNT(CASE WHEN a.status='P' THEN 1 END) AS attended,
                   COUNT(*) AS total_classes,
                   ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) AS percent
            FROM attendance a
            WHERE a.dept=?";
    $params = [$dept];
    $types = "s";

    if($year){
        $sql .= " AND a.year=?";
        $params[] = $year;
        $types .= "s";
    }

    if($section){
        $sql .= " AND a.section=?";
        $params[] = $section;
        $types .= "s";
    }

    $sql .= " GROUP BY a.student_id, a.subject_code";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $studentSubjectRes = $stmt->get_result();

    // Organize student -> [subject%...]
    $studentData = [];
    while($row = $studentSubjectRes->fetch_assoc()){
        $studentData[$row['student_id']][] = $row['percent'];
    }

 
    // STEP 2: Average per student
  
    $studentAvg = [];
    foreach($studentData as $sid => $subjects){
        $studentAvg[$sid] = array_sum($subjects)/count($subjects);
    }

    // STEP 3: Average across all students (Filtered Summary)

    if(count($studentAvg) > 0){
        $summaryPercent = round(array_sum($studentAvg)/count($studentAvg),2);
    } else {
        $summaryPercent = "NA";
    }


    // STEP 4: Overall Dept Attendance %
    
    $sql3 = "SELECT a.student_id, a.subject_code,
                    COUNT(CASE WHEN a.status='P' THEN 1 END) AS attended,
                    COUNT(*) AS total_classes,
                    ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) AS percent
             FROM attendance a
             WHERE a.dept=?
             GROUP BY a.student_id, a.subject_code";

    $stmt3 = $conn->prepare($sql3);
    $stmt3->bind_param("s", $dept);
    $stmt3->execute();
    $deptRes = $stmt3->get_result();

    $deptData = [];
    while($row = $deptRes->fetch_assoc()){
        $deptData[$row['student_id']][] = $row['percent'];
    }

    $deptAvg = [];
    foreach($deptData as $sid => $subjects){
        $deptAvg[$sid] = array_sum($subjects)/count($subjects);
    }

    if(count($deptAvg) > 0){
        $overallDeptPercent = round(array_sum($deptAvg)/count($deptAvg),2);
    } else {
        $overallDeptPercent = "NA";
    }

    // STEP 5: Detailed Subject Results for display

    $sql4 = "SELECT a.subject_code, a.subject_name, a.year, a.section,
                    COUNT(CASE WHEN a.status='P' THEN 1 END) AS attended,
                    COUNT(*) AS total_classes,
                    ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) AS percent
             FROM attendance a
             WHERE a.dept=?";
    $params4 = [$dept];
    $types4 = "s";

    if($year){
        $sql4 .= " AND a.year=?";
        $params4[] = $year;
        $types4 .= "s";
    }
    if($section){
        $sql4 .= " AND a.section=?";
        $params4[] = $section;
        $types4 .= "s";
    }

    $sql4 .= " GROUP BY a.subject_code, a.year, a.section";

    $stmt4 = $conn->prepare($sql4);
    $stmt4->bind_param($types4, ...$params4);
    $stmt4->execute();
    $result = $stmt4->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>HOD Dashboard - Attendance Statistics</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script>
    function autoSubmit(){
      document.getElementById('filterForm').submit();
    }
  </script>
</head>
<body class="bg-light">
<div class="d-flex justify-content-center align-items-center mb-4 position-relative">
    <h2 class="text-center m-0">
        Head of the Department, <?php echo htmlspecialchars($dept); ?><br> Attendance Statistics
    </h2>
    <a href="index.php" class="btn btn-primary position-absolute end-0">
        Logout
    </a>
</div>

<div class="container mt-4">
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="" id="filterForm">
        <div class="row mb-3">
          <!-- Year -->
          <div class="col-md-6">
            <label class="form-label">Year</label>
            <select name="year" class="form-select" onchange="autoSubmit()">
              <option value="">Select Year</option>
              <option value="E1" <?= ($year=="E1")?'selected':'' ?>>E1</option>
              <option value="E2" <?= ($year=="E2")?'selected':'' ?>>E2</option>
              <option value="E3" <?= ($year=="E3")?'selected':'' ?>>E3</option>
              <option value="E4" <?= ($year=="E4")?'selected':'' ?>>E4</option>
            </select>
          </div>

          <!-- Section -->
          <div class="col-md-6">
            <label class="form-label">Section</label>
            <select name="section" class="form-select" onchange="autoSubmit()">
              <option value="">All Sections</option>
              <option value="1" <?= ($section=="1")?'selected':'' ?>>1</option>
              <option value="2" <?= ($section=="2")?'selected':'' ?>>2</option>
              <option value="3" <?= ($section=="3")?'selected':'' ?>>3</option>
              <option value="4" <?= ($section=="4")?'selected':'' ?>>4</option>
              <option value="5" <?= ($section=="5")?'selected':'' ?>>5</option>
              <option value="6" <?= ($section=="6")?'selected':'' ?>>6</option>
            </select>
          </div>
        </div>
      </form>
    </div>
  </div>

  <?php if($result): ?>
  <div class="card">
    <div class="card-body">
      <h5>Attendance Results</h5>
      <?php if($summaryPercent !== null): ?>
        <div class="alert alert-info">
          <strong>Filtered Attendance %:</strong> <?= $summaryPercent ?>
        </div>
      <?php endif; ?>

      <?php if($overallDeptPercent !== null): ?>
        <div class="alert alert-success">
          <strong>Overall Department Attendance %:</strong> <?= $overallDeptPercent ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
