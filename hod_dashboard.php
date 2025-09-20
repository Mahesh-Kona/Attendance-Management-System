<?php
session_start();
include 'db_connect.php';

// Only allow HOD access
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'hod'){
    die("Access Denied");
}

$dept = $_SESSION['dept'];

// --- GET FILTERS ---
$year = $_GET['year'] ?? '';
$month = $_GET['month'] ?? '';
$percentFilter = $_GET['percent'] ?? '';
$academicYear = $_GET['ay'] ?? '';

$sectionData = [];
$deptAvg = "NA";

if($dept && $year && $academicYear && $month){  // only run query if all filters selected
    $sql = "SELECT a.section,
                   COUNT(CASE WHEN a.status='P' THEN 1 END) AS attended,
                   COUNT(*) AS total_classes,
                   ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) AS percent
            FROM attendance a
            WHERE a.dept=? AND a.year=? AND a.academic_year=? AND a.month=?";
    $params = [$dept, $year, $academicYear, $month];
    $types = "ssss";

    $sql .= " GROUP BY a.section";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $sectionPercents = [];
    while($row = $res->fetch_assoc()){
        if ($percentFilter !== '' && $row['percent'] >= (int)$percentFilter) {
            continue;
        }
        $sectionData["Section-".$row['section']] = $row['percent'];
        $sectionPercents[] = $row['percent'];
    }

    if(count($sectionPercents) > 0){
        $deptAvg = round(array_sum($sectionPercents)/count($sectionPercents), 2);
    }
}

// --- Always show Section-1 to Section-6 ---
$allSections = [];
for($i=1; $i<=6; $i++){
    $secKey = "Section-$i";
    $allSections[$secKey] = $sectionData[$secKey] ?? 0;
}
$sectionData = $allSections;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>HOD Dashboard - Attendance Statistics</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
<br>

<div class="container mt-4">
  <!-- Header -->
    <div class="d-flex align-items-center justify-content-center position-relative mb-4">
        <div class="text-center">
          <h2 class="m-0">Head of the Department, <?= htmlspecialchars($dept); ?></h2>
           
        </div>
        <a href="index.php" class="btn btn-primary position-absolute end-0" onclick="hi()">
            Logout
        </a>
    </div>
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" id="filterForm" class="row g-3">
        <!-- Year -->
        <div class="col-md-3">
          <label class="form-label">Year</label>
          <select name="year" class="form-select" required onchange="this.form.submit()">
            <option value="" disabled <?= $year==''?'selected':'' ?>>Select</option>
            <option value="E1" <?= ($year=="E1")?'selected':'' ?>>E1</option>
            <option value="E2" <?= ($year=="E2")?'selected':'' ?>>E2</option>
            <option value="E3" <?= ($year=="E3")?'selected':'' ?>>E3</option>
            <option value="E4" <?= ($year=="E4")?'selected':'' ?>>E4</option>
          </select>
        </div>

        <!-- Academic Year -->
        <div class="col-md-3">
          <label class="form-label">Academic Year</label>
          <select name="ay" class="form-select" required onchange="this.form.submit()">
            <option value="" disabled <?= $academicYear==''?'selected':'' ?>>Select</option>
            <option value="2025-26" <?= ($academicYear=="2025-26")?'selected':'' ?>>2025-26</option>
            <option value="2024-25" <?= ($academicYear=="2024-25")?'selected':'' ?>>2024-25</option>
          </select>
        </div>

        <!-- Month -->
        <div class="col-md-3">
          <label class="form-label">Month/Test</label>
          <select name="month" class="form-select" required onchange="this.form.submit()">
            <option value="" disabled <?= $month==''?'selected':'' ?>>Select</option>
            <option value="MT-1" <?= ($month=="MT-1")?'selected':'' ?>>MT-1</option>
            <option value="MT-2" <?= ($month=="MT-2")?'selected':'' ?>>MT-2</option>
            <option value="MT-3" <?= ($month=="MT-3")?'selected':'' ?>>MT-3</option>
          </select>
        </div>

        <!-- Percentage -->
        <div class="col-md-3">
          <label class="form-label">Below %</label>
          <select name="percent" class="form-select" onchange="this.form.submit()">
            <option value="" <?= $percentFilter==''?'selected':'' ?>>Select</option>
            <option value="65" <?= ($percentFilter=="65")?'selected':'' ?>>65%</option>
            <option value="75" <?= ($percentFilter=="75")?'selected':'' ?>>75%</option>
            <option value="85" <?= ($percentFilter=="85")?'selected':'' ?>>85%</option>
          </select>
        </div>
      </form>
    </div>
  </div>

  <?php if($dept && $year && $academicYear && $month): ?>
    <p class="text-center fw-bold fs-5 mt-3">
      Overall Department Average: <?= $deptAvg; ?>%
    </p>

    <div class="card mb-4">
      <div class="card-body">
        <h5 class="text-center"><?= htmlspecialchars($year) ?> - Attendance by Section</h5>
        <canvas id="sectionChart" style="width:600px; height:400px; margin:auto; display:block;"></canvas>
      </div>
    </div>

    <script>
      const ctx = document.getElementById('sectionChart').getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: [<?php foreach($sectionData as $sec => $val) echo "'".$sec."',"; ?>],
          datasets: [{
            label: 'Attendance %',
            data: [<?php foreach($sectionData as $sec => $val) echo $val.","; ?>],
            backgroundColor: 'rgba(54, 162, 235, 0.7)'
          }]
        },
        options: {
          scales: {
            y: { beginAtZero: true, max: 100 }
          }
        }
      });
    </script>
  <?php endif; ?>
</div>
<script>
function hi(){
    return confirm("Logging out! Are you sure?");
}
  </script>
</body>
</html>
