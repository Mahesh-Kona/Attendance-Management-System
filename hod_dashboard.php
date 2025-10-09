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
  if($month === 'Total Semester') {
    // Get all MT-1, MT-2, MT-3 and average them per section
    $sql = "SELECT a.section,
             AVG(percent) as percent
        FROM (
          SELECT a.section,
               ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) AS percent
          FROM attendance a
          WHERE a.dept=? AND a.year=? AND a.academic_year=? AND a.month IN ('MT-1','MT-2','MT-3')
          GROUP BY a.section, a.month
        ) as a
        GROUP BY a.section";
    $params = [$dept, $year, $academicYear];
    $types = "sss";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $sectionPercents = [];
    while($row = $res->fetch_assoc()){
      if ($percentFilter !== '' && $row['percent'] >= (int)$percentFilter) {
        continue;
      }
      $sectionData["Section-".$row['section']] = round($row['percent'],2);
      $sectionPercents[] = $row['percent'];
    }
    if(count($sectionPercents) > 0){
      $deptAvg = round(array_sum($sectionPercents)/count($sectionPercents), 2);
    }
  } else {
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
}

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
<body style= min-height:100vh;">
<br>

<div class="container mt-3 main-content">
  <!-- Header -->
    <div class="d-flex align-items-center justify-content-center position-relative mb-4">
        <div class="text-center">
          <h2 class="m-0">Head of the Department, <?= htmlspecialchars($dept); ?></h2>
           
        </div>
        <a href="index.php" class="btn btn-primary position-absolute end-0" onclick="hi()">
            Logout
        </a>
    </div>
  <div class="card shadow-sm mb-4 mx-4">
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
            <option value="2026-27" <?= ($academicYear=="2026-27")?'selected':'' ?>>2026-27</option>
          </select>
        </div>

        <!-- Month -->
        <div class="col-md-3">
          <label class="form-label">Exam</label>
          <select name="month" class="form-select" required onchange="this.form.submit()">
            <option value="" disabled <?= $month==''?'selected':'' ?>>Select</option>
            <option value="MT-1" <?= ($month=="MT-1")?'selected':'' ?>>MT-1</option>
            <option value="MT-2" <?= ($month=="MT-2")?'selected':'' ?>>MT-2</option>
            <option value="MT-3" <?= ($month=="MT-3")?'selected':'' ?>>MT-3</option>
            <option value="Total Semester" <?= ($month=="Total Semester")?'selected':'' ?>>EST</option>
          </select>
        </div>

        <!-- Percentage -->
        <div class="col-md-3">
          <label class="form-label">Below %</label>
          <select name="percent" class="form-select" onchange="this.form.submit()">
            <option value="" <?= $percentFilter==''?'selected':'' ?>>None</option>
            <option value="65" <?= ($percentFilter=="65")?'selected':'' ?>>65%</option>
            <option value="75" <?= ($percentFilter=="75")?'selected':'' ?>>75%</option>
            <option value="85" <?= ($percentFilter=="85")?'selected':'' ?>>85%</option>
          </select>
        </div>
      </form>
    </div>
  </div>

  <?php if($dept && $year && $academicYear && $month): ?>
    <!-- <p class="text-center fw-bold fs-5 mt-3">
      Overall Department Average: <?= $deptAvg; ?>%
    </p> -->
     <!-- The button, add id and style -->
<div class="text-center mb-3">
  <button type="button" class="btn btn-danger" id="viewStudentsBtn"
    style="<?= ($percentFilter=='') ? 'display:none;' : '' ?>"
    onclick="document.getElementById('belowPercentStudents').style.display='block';">
    View Students Below <?= $percentFilter ?>%
  </button>
</div>
    <?php if($dept && $year && $academicYear && $month && $percentFilter != ''): ?>
    <div id="belowPercentStudents" style="display:none;">
      <div class="card mb-4 mx-4">
        <div class="card-body">
          <h5 class="text-center">Students Below<b> <?= $percentFilter ?></b>% in <b><?= htmlspecialchars($year) ?></b>, <b><?= htmlspecialchars($month) ?></b></h5>
          
<div class="d-flex justify-content-end mb-3">
    <input type="text" id="studentSearchInput" class="search-bar" placeholder="🔍 Search...">
</div>

<!-- Table -->
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
            <?php
            // Query students below the selected percent, ORDER BY section ASC, percent ASC
            if($month === 'Total Semester') {
                $studentSql = "
                    SELECT a.student_id, u.studentName, a.section,
                        ROUND(AVG(percent),2) AS percent
                    FROM (
                        SELECT a.student_id, a.section,
                            ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) AS percent
                        FROM attendance a
                        WHERE a.dept=? AND a.year=? AND a.academic_year=? AND a.month IN ('MT-1','MT-2','MT-3')
                        GROUP BY a.student_id, a.section, a.month
                    ) as a
                    JOIN userstudent u ON a.student_id = u.studentID
                    GROUP BY a.student_id, a.section
                    HAVING percent < ?
                    ORDER BY a.section ASC, percent ASC
                ";
                $stmt = $conn->prepare($studentSql);
                $stmt->bind_param("sssi", $dept, $year, $academicYear, $percentFilter);
            } else {
                $studentSql = "
                    SELECT a.student_id, u.studentName, a.section,
                        ROUND((COUNT(CASE WHEN a.status='P' THEN 1 END)/COUNT(*))*100,2) AS percent
                    FROM attendance a
                    JOIN userstudent u ON a.student_id = u.studentID
                    WHERE a.dept=? AND a.year=? AND a.academic_year=? AND a.month=?
                    GROUP BY a.student_id, a.section
                    HAVING percent < ?
                    ORDER BY a.section ASC, percent ASC
                ";
                $stmt = $conn->prepare($studentSql);
                $stmt->bind_param("ssssi", $dept, $year, $academicYear, $month, $percentFilter);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $students = [];
            while($row = $result->fetch_assoc()){
                $students[] = $row;
            }
            $stmt->close();
            if(count($students) > 0){
                foreach($students as $i => $row){
                    echo "<tr>
                        <td class='text-center'></td>
                        <td>{$row['student_id']}</td>
                        <td>{$row['studentName']}</td>
                        <td>{$row['section']}</td>
                        <td>{$row['percent']}%</td>
                    </tr>";
                }
            } else {
                echo "<tr class='no-students-row'><td colspan='5' class='text-center text-muted'>No students found</td></tr>";
            }
            ?>
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
    <div class="card mb-4 mx-4">
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
<footer>
  &copy; <?= date('Y') ?> Rajiv Gandhi University of Knowledge Technologies Nuzvid. All rights reserved.
</footer>
<script>
function hi(){
    return confirm("Logging out! Are you sure?");
}
  </script>
<script>
const studentsTable = document.getElementById('studentsTable');
if (studentsTable) {
    const studentTbody = studentsTable.querySelector('tbody');
    const studentRows = Array.from(studentTbody.querySelectorAll('tr'));
    const studentSearchInput = document.getElementById('studentSearchInput');
    const studentPagination = document.getElementById('studentPagination');
    const studentPageInfo = document.getElementById('studentPageInfo');
    const studentPageSize = 10;
    let studentCurrentPage = 1;
    let studentFilteredRows = studentRows.slice();
    let studentSearchActive = false;

    function updateStudentSNo(rows, page) {
        rows.forEach((row, i) => {
            // Only update S.No if row is not "No students found"
            if (!row.classList.contains('no-students-row')) {
                const sNoCell = row.querySelector('td');
                if (sNoCell) sNoCell.textContent = (page - 1) * studentPageSize + i + 1;
            }
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

function toggleViewStudentsBtn() {
    var percent = document.getElementById('percentFilter').value;
    var btn = document.getElementById('viewStudentsBtn');
    if (btn) btn.style.display = percent ? 'inline-block' : 'none';
    // Optionally hide the students table when filter changes
    var studentsDiv = document.getElementById('belowPercentStudents');
    if (studentsDiv) studentsDiv.style.display = 'none';
}
</script>
</body>
</html>