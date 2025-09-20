<?php
session_start();
include 'db_connect.php';

// Only allow Dean access
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dean'){
    die("Access Denied. Allowed only for Dean.");
}

// Map short branch codes to full dept names in your DB
$branchMap = [
    'CSE' => 'Computer Science & Engineering',
    'ECE' => 'Electronics & Communication Engineering',
    'MECH' => 'Mechanical Engineering',
    'EEE' => 'Electrical & Electronics Engineering',
    'CIVIL' => 'Civil Engineering',
    'MME' => 'Metallurgical & Material Science Engineering',
    'CHEMICAL' => 'Chemical Engineering'
];

// --- GET FILTER VALUES ---
$filterYear = $_GET['year'] ?? '';
$filterBranch = $_GET['branch'] ?? '';
$percentFilter = $_GET['percent'] ?? '';
$filterAY = $_GET['ay'] ?? '';
$month = $_GET['month'] ?? '';

$branchAverages = [];
$campusAvg = "NA";

// --- Only calculate if any filter is applied ---
if ($filterYear || $filterBranch || $filterAY || $month || $percentFilter) {
    // --- CAMPUS AVERAGE ---
    $campusSum = 0;
    $campusCount = 0;

    foreach ($branchMap as $branch => $deptFull) {
        $sqlStudents = "SELECT studentID FROM userstudent WHERE TRIM(UPPER(dept)) = TRIM(UPPER(?))";
        $stmt = $conn->prepare($sqlStudents);
        $stmt->bind_param("s", $deptFull);
        $stmt->execute();
        $studentsResult = $stmt->get_result();

        while ($row = $studentsResult->fetch_assoc()) {
            $student_id = $row['studentID'];

            // Attendance query with optional month filter
            $sqlSubj = "SELECT ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) AS percent
                        FROM attendance a
                        WHERE a.student_id = ?";

            if ($month) {
                $sqlSubj .= " AND a.month = ?";
            }

            $sqlSubj .= " GROUP BY a.subject_code";

            if ($month) {
                $stmtSubj = $conn->prepare($sqlSubj);
                $stmtSubj->bind_param("ss", $student_id, $month);
            } else {
                $stmtSubj = $conn->prepare($sqlSubj);
                $stmtSubj->bind_param("s", $student_id);
            }

            $stmtSubj->execute();
            $resSubj = $stmtSubj->get_result();

            $subjPercents = [];
            while ($r = $resSubj->fetch_assoc()) {
                $subjPercents[] = $r['percent'];
            }

            if (count($subjPercents) > 0) {
                $studentPerc = array_sum($subjPercents) / count($subjPercents);

                if ($percentFilter !== '' && $studentPerc >= (int)$percentFilter) {
                    continue;
                }

                $campusSum += $studentPerc;
                $campusCount++;
            }
        }
    }

    $campusAvg = ($campusCount > 0) ? round($campusSum/$campusCount,2) : "NA";

    // --- BRANCH AVERAGES ---
    foreach ($branchMap as $branch => $deptFull) {
        if($filterBranch && $branch !== $filterBranch){
            continue;
        }

        $sqlStudents = "SELECT studentID, year, academic_year 
                    FROM userstudent 
                    WHERE TRIM(UPPER(dept)) = TRIM(UPPER(?))";
        if($filterYear){
            $sqlStudents .= " AND year = ?";
        }
        if($filterAY){
            $sqlStudents .= " AND academic_year= ?";
        }

        if($filterYear && $filterAY){
            $stmt = $conn->prepare($sqlStudents);
            $stmt->bind_param("sss", $deptFull, $filterYear, $filterAY);
        } elseif($filterYear){
            $stmt = $conn->prepare($sqlStudents);
            $stmt->bind_param("ss", $deptFull, $filterYear);
        } elseif($filterAY){
            $stmt = $conn->prepare($sqlStudents);
            $stmt->bind_param("ss", $deptFull, $filterAY);
        } else {
            $stmt = $conn->prepare($sqlStudents);
            $stmt->bind_param("s", $deptFull);
        }
        $stmt->execute();
        $studentsResult = $stmt->get_result();

        $studentPercents = [];
        while ($row = $studentsResult->fetch_assoc()) {
            $student_id = $row['studentID'];

            $sqlSubj = "SELECT ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) AS percent
                        FROM attendance a
                        WHERE a.student_id = ?";

            if ($month) {
                $sqlSubj .= " AND a.month = ?";
            }

            $sqlSubj .= " GROUP BY a.subject_code";

            if ($month) {
                $stmtSubj = $conn->prepare($sqlSubj);
                $stmtSubj->bind_param("ss", $student_id, $month);
            } else {
                $stmtSubj = $conn->prepare($sqlSubj);
                $stmtSubj->bind_param("s", $student_id);
            }

            $stmtSubj->execute();
            $resSubj = $stmtSubj->get_result();

            $subjPercents = [];
            while ($r = $resSubj->fetch_assoc()) {
                $subjPercents[] = $r['percent'];
            }

            if(count($subjPercents) > 0){
                $studentPerc = array_sum($subjPercents)/count($subjPercents);

                if($percentFilter !== '' && $studentPerc >= (int)$percentFilter){
                    continue;
                }

                $studentPercents[] = $studentPerc;
            }
        }

        if(count($studentPercents) > 0){
            $branchAvg = array_sum($studentPercents)/count($studentPercents);
            $branchAverages[$branch] = round($branchAvg,2);
        } else {
            $branchAverages[$branch] = "N/A";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dean Dashboard - Attendance Statistics</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
<div class="d-flex justify-content-center align-items-center mb-4 position-relative text-center">
  <div>
    <h1>Attendance Management System</h1>
    <h2 class="m-0">Dean of Academics</h2>
   
  </div>
  
  <a href="index.php" class="btn btn-primary position-absolute end-0" onclick="hi()">
    Logout
  </a>
</div>


<div class="container mt-3">
  <div class="card shadow-sm mb-3">
  <div class="card-body">
    <form method="GET" id="filterForm" class="row g-2 align-items-center">
<!-- Year -->
<div class="col-md-2">
  <label class="form-label">Year</label>
  <select name="year" class="form-select" onchange="this.form.submit()">
    <option value="" <?= $filterYear==''?'selected':'' ?>>All</option>
    <option value="E1" <?= ($filterYear=="E1")?'selected':'' ?>>E1</option>
    <option value="E2" <?= ($filterYear=="E2")?'selected':'' ?>>E2</option>
    <option value="E3" <?= ($filterYear=="E3")?'selected':'' ?>>E3</option>
    <option value="E4" <?= ($filterYear=="E4")?'selected':'' ?>>E4</option>
  </select>
</div>

<!-- Branch -->
<div class="col-md-2">
  <label class="form-label">Branch</label>
  <select name="branch" class="form-select" onchange="this.form.submit()">
    <option value="" <?= $filterBranch==''?'selected':'' ?>>All</option>
    <?php foreach($branchMap as $code => $name): ?>
      <option value="<?= $code ?>" <?= ($filterBranch==$code)?'selected':'' ?>><?= $code ?></option>
    <?php endforeach; ?>
  </select>
</div>

<!-- Academic Year (mandatory) -->
<div class="col-md-3">
  <label class="form-label">Academic Year</label>
  <select name="ay" class="form-select" onchange="this.form.submit()">
    <option value="" disabled <?= $filterAY==''?'selected':'' ?>>Select</option>
    <option value="2025-26" <?= ($filterAY=="2025-26")?'selected':'' ?>>2025-26</option>
    <option value="2024-25" <?= ($filterAY=="2024-25")?'selected':'' ?>>2024-25</option>
  </select>
</div>

<!-- Month/Test -->
<div class="col-md-2">
  <label class="form-label">Month/Test</label>
  <select name="month" class="form-select" onchange="this.form.submit()">
    <option value="" <?= $month==''?'selected':'' ?>>All</option>
    <option value="MT-1" <?= ($month=="MT-1")?'selected':'' ?>>MT-1</option>
    <option value="MT-2" <?= ($month=="MT-2")?'selected':'' ?>>MT-2</option>
    <option value="MT-3" <?= ($month=="MT-3")?'selected':'' ?>>MT-3</option>
  </select>
</div>

<!-- Percentage -->
<div class="col-md-3">
  <label class="form-label">Below %</label>
  <select name="percent" class="form-select" onchange="this.form.submit()">
    <option value="" <?= $percentFilter==''?'selected':'' ?>>All</option>
    <option value="65" <?= ($percentFilter=="65")?'selected':'' ?>>65%</option>
    <option value="75" <?= ($percentFilter=="75")?'selected':'' ?>>75%</option>
    <option value="85" <?= ($percentFilter=="85")?'selected':'' ?>>85%</option>
  </select>
</div>


    </form>
  </div>
</div>


  <?php if($filterYear || $filterBranch || $filterAY || $month || $percentFilter): ?>
      <p class="text-center fw-bold fs-5 mt-3">
        Overall Campus Average: <?= $campusAvg; ?>%
      </p>

      <?php if(!empty($branchAverages)): ?>
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="text-center">Branch Average Attendance</h5>
            <canvas id="branchChart" style="width:500px; height:400px; margin:auto; display:block;"></canvas>
          </div>
        </div>

<script>
  const ctxBranch = document.getElementById('branchChart').getContext('2d');
  new Chart(ctxBranch, {
    type: 'bar',
    data: {
      labels: [<?php foreach($branchAverages as $branch => $avg) echo "'".$branch."',"; ?>],
      datasets: [{
        label: 'Attendance %',
        data: [<?php foreach($branchAverages as $avg) echo is_numeric($avg)?$avg."," : "0,"; ?>],
        backgroundColor: 'rgba(75, 192, 192, 0.7)'
      }]
    },
    options: {
      scales: {
        x: {
          ticks: {
            maxRotation: 0, // ✅ no rotation
            minRotation: 0  // ✅ keep horizontal
          }
        },
        y: {
          beginAtZero: true,
          max: 100
        }
      }
    }
  });
</script>

      <?php else: ?>
        <div class="alert alert-warning text-center">
          No data available for the selected filters.
        </div>
      <?php endif; ?>
  <?php endif; ?>

</div>
<script>
function hi(){
    return confirm("Logging out! Are you sure?");
}
  </script>
</body>
</html>
