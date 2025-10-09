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

// --- Students below selected percent ---
$students = [];
if($filterYear && $filterBranch && $filterAY && $month && $percentFilter != '') {
    $deptFull = $branchMap[$filterBranch] ?? '';

    if ($month === "Total Semester") {
        $studentSql = "
          SELECT a.student_id, u.studentName, a.section,
            ROUND(AVG(percent),2) as percent
          FROM (
            SELECT a.student_id, a.section,
                   ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) as percent
            FROM attendance a
            WHERE TRIM(UPPER(a.dept)) = TRIM(UPPER(?))
              AND a.year = ?
              AND a.academic_year = ?
            GROUP BY a.student_id, a.section, a.subject_code, a.month
          ) as a
          JOIN userstudent u ON a.student_id = u.studentID
          GROUP BY a.student_id, a.section
          HAVING percent < ?
          ORDER BY a.section ASC, percent ASC
        ";
        $stmt = $conn->prepare($studentSql);
        $stmt->bind_param("sssi", $deptFull, $filterYear, $filterAY, $percentFilter);
    } else {
        $studentSql = "
          SELECT a.student_id, u.studentName, a.section,
            ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) AS percent
          FROM attendance a
          JOIN userstudent u ON a.student_id = u.studentID
          WHERE TRIM(UPPER(a.dept)) = TRIM(UPPER(?))
            AND a.year = ?
            AND a.academic_year = ?
            AND a.month = ?
          GROUP BY a.student_id, a.section
          HAVING percent < ?
          ORDER BY a.section ASC, percent ASC
        ";
        $stmt = $conn->prepare($studentSql);
        $stmt->bind_param("ssssi", $deptFull, $filterYear, $filterAY, $month, $percentFilter);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
      $students[] = $row;
    }
    $stmt->close();
}

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

            if ($month === 'Total Semester') {
                $sqlSubj = "SELECT ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) AS percent
                            FROM attendance a
                            WHERE a.student_id = ?
                            GROUP BY a.subject_code, a.month";
                $stmtSubj = $conn->prepare($sqlSubj);
                $stmtSubj->bind_param("s", $student_id);
                $stmtSubj->execute();
                $resSubj = $stmtSubj->get_result();
                $subjPercents = [];
                while ($r = $resSubj->fetch_assoc()) {
                    $subjPercents[] = $r['percent'];
                }
                if (count($subjPercents) > 0) {
                    $studentPerc = array_sum($subjPercents) / count($subjPercents);
                    if ($percentFilter !== '' && $studentPerc >= (int)$percentFilter) continue;
                    $campusSum += $studentPerc;
                    $campusCount++;
                }
            } else {
                $sqlSubj = "SELECT ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) AS percent
                            FROM attendance a
                            WHERE a.student_id = ?";
                if ($month) $sqlSubj .= " AND a.month = ?";
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
                    if ($percentFilter !== '' && $studentPerc >= (int)$percentFilter) continue;
                    $campusSum += $studentPerc;
                    $campusCount++;
                }
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

            if ($month === 'Total Semester') {
                $sqlSubj = "SELECT ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) AS percent
                            FROM attendance a
                            WHERE a.student_id = ?
                            GROUP BY a.subject_code, a.month";
                $stmtSubj = $conn->prepare($sqlSubj);
                $stmtSubj->bind_param("s", $student_id);
                $stmtSubj->execute();
                $resSubj = $stmtSubj->get_result();
                $subjPercents = [];
                while ($r = $resSubj->fetch_assoc()) {
                    $subjPercents[] = $r['percent'];
                }
                if (count($subjPercents) > 0) {
                    $studentPerc = array_sum($subjPercents) / count($subjPercents);
                    if ($percentFilter !== '' && $studentPerc >= (int)$percentFilter) continue;
                    $studentPercents[] = $studentPerc;
                }
            } else {
                $sqlSubj = "SELECT ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) AS percent
                            FROM attendance a
                            WHERE a.student_id = ?";
                if ($month) $sqlSubj .= " AND a.month = ?";
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
                    if($percentFilter !== '' && $studentPerc >= (int)$percentFilter) continue;
                    $studentPercents[] = $studentPerc;
                }
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
  <style>
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
      overflow-x: hidden;
    }
    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background: #f5f5f5;
      font-family: Arial, sans-serif;
      overflow-x: hidden;
    }
    .main-content {
      flex: 1 0 auto;
      width: 100%;
      margin: 0;
      padding: 0;
      max-width: none;
      overflow-x: auto;
    }
    .card {
      margin-bottom: 1.5rem;
      margin-left: 1.5rem;
      margin-right: 1.5rem;
    }
    .search-bar {
      width: 280px;
      border-radius: 25px;
      border: 1px solid #0d6efd;
      padding: 8px 18px;
      font-size: 15px;
      transition: box-shadow 0.2s;
      box-shadow: 0 2px 6px rgba(13,110,253,0.08);
      outline: none;
    }
    .search-bar:focus {
      box-shadow: 0 0 0 2px #0d6efd33;
      border-color: #0d6efd;
    }
    .search-bar::placeholder {
      color: #888;
      font-style: italic;
    }
    .table-responsive {
      overflow-x: auto;
      width: 100%;
    }
    .table-primary th {
      background: #e3f0ff !important;
      color: #002147 !important;
    }
    footer {
      background: #002147;
      color: #fff;
      text-align: center;
      padding: 15px 0;
      font-size: 0.9rem;
      width: 100vw;
      margin-left: calc(-50vw + 50%);
      margin-right: calc(-50vw + 50%);
      margin-top: auto;
      left: 0;
      right: 0;
      bottom: 0;
      box-sizing: border-box;
    }
  </style>
</head>
<body>
<div class="container mt-3 main-content">
   <div class="d-flex align-items-center justify-content-center position-relative mb-4">
    <div class="text-center">
      <h2 class="m-0">Dean of Academics</h2>
    </div>
    <a href="index.php" class="btn btn-primary position-absolute end-0" onclick="return hi();">
      Logout
    </a>
  </div>
  <div class="card shadow-sm mb-4 mx-4">
    <div class="card-body">
      <form method="GET" id="filterForm" class="row g-3">
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
        <div class="col-md-2">
          <label class="form-label">Branch</label>
          <select name="branch" class="form-select" onchange="this.form.submit()">
            <option value="" <?= $filterBranch==''?'selected':'' ?>>All</option>
            <?php foreach($branchMap as $code => $name): ?>
              <option value="<?= $code ?>" <?= ($filterBranch==$code)?'selected':'' ?>><?= $code ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Academic Year</label>
          <select name="ay" class="form-select" onchange="this.form.submit()">
            <option value="" disabled <?= $filterAY==''?'selected':'' ?>>Select</option>
            <option value="2025-26" <?= ($filterAY=="2025-26")?'selected':'' ?>>2025-26</option>
            <option value="2026-27" <?= ($filterAY=="2026-27")?'selected':'' ?>>2026-27</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Exam</label>
          <select name="month" class="form-select" onchange="this.form.submit()">
            <option value="" disabled <?= $month==''?'selected':'' ?>>Select</option>
            <option value="MT-1" <?= ($month=="MT-1")?'selected':'' ?>>MT-1</option>
            <option value="MT-2" <?= ($month=="MT-2")?'selected':'' ?>>MT-2</option>
            <option value="MT-3" <?= ($month=="MT-3")?'selected':'' ?>>MT-3</option>
            <option value="Total Semester" <?= ($month=="Total Semester")?'selected':'' ?>>EST</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Below %</label>
          <select name="percent" id="percentFilter" class="form-select" onchange="toggleViewStudentsBtn(); this.form.submit()">
            <option value="" <?= $percentFilter==''?'selected':'' ?>>None</option>
            <option value="65" <?= ($percentFilter=="65")?'selected':'' ?>>65%</option>
            <option value="75" <?= ($percentFilter=="75")?'selected':'' ?>>75%</option>
            <option value="85" <?= ($percentFilter=="85")?'selected':'' ?>>85%</option>
          </select>
        </div>
      </form>
    </div>
  </div>

  <?php if($filterYear && $filterBranch && $filterAY && $month): ?>
    <div class="text-center mb-3">
      <button type="button" class="btn btn-danger" id="viewStudentsBtn"
        style="<?= ($percentFilter=='') ? 'display:none;' : '' ?>"
        onclick="showStudentsTable()">
        View Students Below <?= $percentFilter ?>%
      </button>
    </div>
  <?php endif; ?>

  <?php if($filterYear && $filterBranch && $filterAY && $month && $percentFilter != ''): ?>
  <div id="studentsTableContainer" style="display:none;">
    <div class="card mb-4 mx-4">
      <div class="card-body">
        <h5 class="text-center">Students Below <b><?= $percentFilter ?>%</b> in <b><?= htmlspecialchars($filterYear) ?></b>, <b><?= htmlspecialchars($month) ?></b></h5>
        <div class="d-flex justify-content-end mb-3">
          <input type="text" id="studentSearchInput" class="search-bar" placeholder="🔍 Search...">
        </div>
        <div class="table-responsive">
          <table class="table table-bordered table-striped table-hover shadow-sm" id="studentsTable">
            <thead class="table-primary text-center">
              <tr>
                <th>S.No</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Section</th>
                <th>Attendance %</th>
              </tr>
            </thead>
            <tbody>
<?php if(count($students) === 0): ?>
  <tr id="studentNoDataRow">
    <td colspan="5" class="text-center text-muted">No students found</td>
  </tr>
<?php else: ?>

    <?php foreach($students as $i => $row): ?>
      <tr>
        <td class='text-center'></td>
        <td><?= htmlspecialchars($row['student_id']) ?></td>
        <td><?= htmlspecialchars($row['studentName']) ?></td>
        <td><?= htmlspecialchars($row['section']) ?></td>
        <td><?= htmlspecialchars($row['percent']) ?>%</td>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
</tbody>


          </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
          <nav>
            <ul id="studentPagination" class="pagination mb-0"></ul>
            <span id="studentPageInfo" class="ms-3 align-self-center text-muted"></span>
          </nav>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if($filterYear || $filterBranch || $filterAY || $month || $percentFilter): ?>
      <?php if(!empty($branchAverages)): ?>
        <div class="card mb-4 mx-4">
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
                    maxRotation: 0, 
                    minRotation: 0 
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
        <div class="alert alert-warning text-center mx-4">
          No data available for the selected filters.
        </div>
      <?php endif; ?>
  <?php endif; ?>
</div>
<footer>
  &copy; <?= date('Y') ?> Rajiv Gandhi University of Knowledge Technologies Nuzvid. All rights reserved.
</footer>
<script>
function hi(){
    return confirm("Logging out! Are you sure?");
}

// Show/hide View Students button based on percent filter
function toggleViewStudentsBtn() {
    var percent = document.getElementById('percentFilter').value;
    var btn = document.getElementById('viewStudentsBtn');
    if (btn) btn.style.display = percent ? 'inline-block' : 'none';
    var studentsDiv = document.getElementById('studentsTableContainer');
    if (studentsDiv) studentsDiv.style.display = 'none';
}

// Show students table when button is clicked
function showStudentsTable() {
    var studentsDiv = document.getElementById('studentsTableContainer');
    if (studentsDiv) studentsDiv.style.display = 'block';
    var btn = document.getElementById('viewStudentsBtn');
    if (btn) btn.style.display = 'none';
}

// Search and pagination for students table
document.addEventListener('DOMContentLoaded', function() {
    const studentsTable = document.getElementById('studentsTable');
    if (studentsTable) {
        const studentTbody = studentsTable.querySelector('tbody');
       const studentRows = Array.from(studentTbody.querySelectorAll('tr')).filter(r => r.id !== "studentNoDataRow");
        const studentSearchInput = document.getElementById('studentSearchInput');
        const studentPagination = document.getElementById('studentPagination');
        const studentPageInfo = document.getElementById('studentPageInfo');
        const studentPageSize = 10;
        let studentCurrentPage = 1;
        let studentFilteredRows = studentRows.slice();
        let studentSearchActive = false;

        function updateStudentSNo(rows, page) {
            rows.forEach((row, i) => {
                const sNoCell = row.querySelector('td');
                if (sNoCell) sNoCell.textContent = (page - 1) * studentPageSize + i + 1;
            });
        }

        function showStudentPage(page) {
            const noResultsRow = document.getElementById('studentNoResultsRow');
            if (noResultsRow) noResultsRow.remove();

            const totalPages = Math.ceil(studentFilteredRows.length / studentPageSize) || 1;
            studentCurrentPage = Math.max(1, Math.min(page, totalPages));

            studentRows.forEach(row => row.style.display = 'none');
            const start = (studentCurrentPage - 1) * studentPageSize;
            const end = start + studentPageSize;
            const pageRows = studentFilteredRows.slice(start, end);

            // Only show "No results found" if search is active and there are no results
            if (studentSearchActive && studentFilteredRows.length === 0) {
                const tr = document.createElement('tr');
                tr.id = 'studentNoResultsRow';
                const td = document.createElement('td');
                td.colSpan = 5;
                td.className = "text-center text-muted";
                td.textContent = "No results found";
                tr.appendChild(td);
                studentTbody.appendChild(tr);
                studentPagination.innerHTML = '';
                studentPageInfo.textContent = '';
                return;
            }

            pageRows.forEach(row => row.style.display = '');
            updateStudentSNo(pageRows, studentCurrentPage);
            renderStudentPagination(totalPages);
            studentPageInfo.textContent = '';
        }

        function renderStudentPagination(totalPages) {
            studentPagination.innerHTML = '';
            if (totalPages <= 1) return;

            // Prev button
            const prevLi = document.createElement('li');
            prevLi.className = 'page-item' + (studentCurrentPage === 1 ? ' disabled' : '');
            const prevA = document.createElement('a');
            prevA.className = 'page-link';
            prevA.href = '#';
            prevA.innerHTML = '&laquo; Prev';
            prevA.addEventListener('click', function(e) {
                e.preventDefault();
                if (studentCurrentPage > 1) showStudentPage(studentCurrentPage - 1);
            });
            prevLi.appendChild(prevA);
            studentPagination.appendChild(prevLi);

            // Page X of N (between Prev and Next)
            const infoLi = document.createElement('li');
            infoLi.className = 'page-item disabled';
            const infoSpan = document.createElement('span');
            infoSpan.className = 'page-link';
            infoSpan.textContent = `Page ${studentCurrentPage} of ${totalPages}`;
            infoLi.appendChild(infoSpan);
            studentPagination.appendChild(infoLi);

            // Next button
            const nextLi = document.createElement('li');
            nextLi.className = 'page-item' + (studentCurrentPage === totalPages ? ' disabled' : '');
            const nextA = document.createElement('a');
            nextA.className = 'page-link';
            nextA.href = '#';
            nextA.innerHTML = 'Next &raquo;';
            nextA.addEventListener('click', function(e) {
                e.preventDefault();
                if (studentCurrentPage < totalPages) showStudentPage(studentCurrentPage + 1);
            });
            nextLi.appendChild(nextA);
            studentPagination.appendChild(nextLi);

            studentPageInfo.textContent = '';
        }

        // Search filter
        studentSearchInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            studentSearchActive = filter.length > 0;
            studentFilteredRows = studentRows.filter(row => {
                // Search in Student ID, Name, Section, Attendance %
                for (let i = 1; i < row.cells.length; i++) {
                    if (row.cells[i].textContent.toLowerCase().includes(filter)) {
                        return true;
                    }
                }
                return false;
            });

            showStudentPage(1);
        });

        // Initial display
        showStudentPage(1);
    }
});
</script>
</body>
</html>
