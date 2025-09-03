<?php
session_start();
include 'db_connect.php';

// Only allow DEAN access
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dean'){
    die("Access Denied.Allowed only for Dean.");
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

$branchAverages = [];

// --- CAMPUS AVERAGE (Always full campus, no filters) ---
$campusSum = 0;
$campusCount = 0;
foreach ($branchMap as $branch => $deptFull) {
    $sqlStudents = "SELECT studentID 
                    FROM userstudent 
                    WHERE TRIM(UPPER(dept)) = TRIM(UPPER(?))";
    $stmt = $conn->prepare($sqlStudents);
    $stmt->bind_param("s", $deptFull);
    $stmt->execute();
    $studentsResult = $stmt->get_result();

    while ($row = $studentsResult->fetch_assoc()) {
        $student_id = $row['studentID'];

        $sqlSubj = "SELECT a.subject_code,
                           COUNT(CASE WHEN a.status='P' THEN 1 END) AS attended,
                           COUNT(*) AS total_classes,
                           ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) AS percent
                    FROM attendance a
                    WHERE a.student_id = ?
                    GROUP BY a.subject_code";
        $stmtSubj = $conn->prepare($sqlSubj);
        $stmtSubj->bind_param("s", $student_id);
        $stmtSubj->execute();
        $resSubj = $stmtSubj->get_result();

        $subjPercents = [];
        while ($r = $resSubj->fetch_assoc()) {
            $subjPercents[] = $r['percent'];
        }

        if(count($subjPercents) > 0){
            $studentPerc = array_sum($subjPercents)/count($subjPercents);
            $campusSum += $studentPerc;
            $campusCount++;
        }
    }
}
$campusAvg = ($campusCount > 0) ? round($campusSum/$campusCount,2) : "NA";

// --- BRANCH AVERAGES (Respect filters) ---
foreach ($branchMap as $branch => $deptFull) {
    if($filterBranch && $branch !== $filterBranch){
        continue; // Skip branches not matching filter
    }

    $sqlStudents = "SELECT studentID, section, year 
                    FROM userstudent 
                    WHERE TRIM(UPPER(dept)) = TRIM(UPPER(?))";
    if($filterYear){
        $sqlStudents .= " AND year = ?";
    }

    $stmt = $conn->prepare($sqlStudents);
    if($filterYear){
        $stmt->bind_param("ss", $deptFull, $filterYear);
    } else {
        $stmt->bind_param("s", $deptFull);
    }
    $stmt->execute();
    $studentsResult = $stmt->get_result();

    $studentPercents = [];
    while ($row = $studentsResult->fetch_assoc()) {
        $student_id = $row['studentID'];

        $sqlSubj = "SELECT a.subject_code,
                           COUNT(CASE WHEN a.status='P' THEN 1 END) AS attended,
                           COUNT(*) AS total_classes,
                           ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) AS percent
                    FROM attendance a
                    WHERE a.student_id = ?
                    GROUP BY a.subject_code";

        $stmtSubj = $conn->prepare($sqlSubj);
        $stmtSubj->bind_param("s", $student_id);
        $stmtSubj->execute();
        $resSubj = $stmtSubj->get_result();

        $subjPercents = [];
        while ($r = $resSubj->fetch_assoc()) {
            $subjPercents[] = $r['percent'];
        }

        if(count($subjPercents) > 0){
            $studentPerc = array_sum($subjPercents)/count($subjPercents);
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dean Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>
        function applyFilter() {
            const year = document.getElementById("year").value;
            const branch = document.getElementById("branch").value;
            let url = "?";
            if(year) url += "year=" + year + "&";
            if(branch) url += "branch=" + branch + "&";
            window.location.href = url;
        }
    </script>
        <style>
        table, th, td { border: 1px solid black; border-collapse: collapse; padding: 8px;    }
        th { background-color: #f2f2f2; }
        .header-container {text-align: right; padding: 10px; background-color: #f8f9fa; }
    </style>
</head>
<body class="container mt-4">
    <div class="d-flex justify-content-center align-items-center mb-4 position-relative">
    <h2 class="text-center m-0">
        Dean of Academics<br> Attendance Statistics
    </h2>
    <a href="index.php" class="btn btn-primary position-absolute end-0">
        Logout
    </a>
</div>
<br>

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-md-3">
            <select id="year" class="form-select" onchange="applyFilter()">
                <option value="">All Years</option>
                <option value="E1" <?= ($filterYear=="E1"?"selected":"") ?>>E1</option>
                <option value="E2" <?= ($filterYear=="E2"?"selected":"") ?>>E2</option>
                <option value="E3" <?= ($filterYear=="E3"?"selected":"") ?>>E3</option>
                <option value="E4" <?= ($filterYear=="E4"?"selected":"") ?>>E4</option>
            </select>
        </div>
        <div class="col-md-3">
            <select id="branch" class="form-select" onchange="applyFilter()">
                <option value="">All Branches</option>
                <?php foreach($branchMap as $code => $name): ?>
                    <option value="<?= $code ?>" <?= ($filterBranch==$code?"selected":"") ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
<br>
    <!-- Branch Averages -->
    <table class="table table-bordered table-striped table-hover shadow-sm">
        <thead>
            <tr class="table-info">
                <th>Branch</th>
                <th>Average Attendance %</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($branchAverages as $branch => $avg): ?>
            <tr>
                <td><?= htmlspecialchars($branchMap[$branch]); ?></td>
                <td><?= htmlspecialchars($avg); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr >
                <td><strong>Overall Campus Average</strong></td>
                <td><strong><?= $campusAvg; ?></strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
