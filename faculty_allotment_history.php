<?php
session_start();
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office'){
    die("Access Denied. This page is only for Department Office users.");
}

$userID = $_SESSION['userID']; 

include "db_connect.php";

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
$academicYearFilter = $_GET['academic_year'] ?? 'All';  // 🔹 new filter
$semesterFilter=$_GET['semester'] ?? 'All';
// Fetch all faculty and their subjects (if any) for this department
$sql = "
    SELECT u.facultyId, u.facultyName, s.subject_code, s.year, s.section, s.subject_name, 
           s.credits, s.semester, s.date_time, s.academic_year
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
if ($semesterFilter !== "All") {   // 🔹 apply filter
    $sql .= " AND s.semester = ? ";
    $params[] = $semesterFilter;
    $types .= "s";
}
if ($sectionFilter !== "All") {
    $sql .= " AND s.section = ? ";
    $params[] = $sectionFilter;
    $types .= "s";
}
if ($academicYearFilter !== "All") {   // 🔹 apply filter
    $sql .= " AND s.academic_year = ? ";
    $params[] = $academicYearFilter;
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
<style>
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }
    body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        background: #f5f5f5;
    }
    .main-content {
        flex: 1 0 auto;
    }
    footer {
        background: #002147;
        color: #fff;
        text-align: center;
        padding: 15px 0;
        font-size: 0.9rem;
        width: 100%;
        left: 0;
        right: 0;
        bottom: 0;
        box-sizing: border-box;
        position: static;
        margin-top: auto;
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
</style>
</head>
<body>
<div class="main-content">
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
        <form method="get" class="row g-3 mb-4 align-items-end">
            <div class="col-md-3">
                <label for="year" class="form-label">Year</label>
                <select name="year" id="year" class="form-select" onchange="this.form.submit()">
                    <option value="All" <?php if($yearFilter=="All") echo "selected"; ?>>All</option>
                    <option value="E1" <?php if($yearFilter=="E1") echo "selected"; ?>>E1</option>
                    <option value="E2" <?php if($yearFilter=="E2") echo "selected"; ?>>E2</option>
                    <option value="E3" <?php if($yearFilter=="E3") echo "selected"; ?>>E3</option>
                    <option value="E4" <?php if($yearFilter=="E4") echo "selected"; ?>>E4</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="semester" class="form-label">Semester</label>
                <select name="semester" id="semester" class="form-select" onchange="this.form.submit()">
                    <option value="All" <?php if($semesterFilter=="All") echo "selected"; ?>>All</option>
                    <option value="1" <?php if($semesterFilter=="1") echo "selected"; ?>>1</option>
                    <option value="2" <?php if($semesterFilter=="2") echo "selected"; ?>>2</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="section" class="form-label">Section</label>
                <select name="section" id="section" class="form-select" onchange="this.form.submit()">
                    <option value="All" <?php if($sectionFilter=="All") echo "selected"; ?>>All</option>
                    <option value="1" <?php if($sectionFilter=="1") echo "selected"; ?>>1</option>
                    <option value="2" <?php if($sectionFilter=="2") echo "selected"; ?>>2</option>
                    <option value="3" <?php if($sectionFilter=="3") echo "selected"; ?>>3</option>
                    <option value="4" <?php if($sectionFilter=="4") echo "selected"; ?>>4</option>
                    <option value="5" <?php if($sectionFilter=="5") echo "selected"; ?>>5</option>
                    <option value="6" <?php if($sectionFilter=="6") echo "selected"; ?>>6</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="academic_year" class="form-label">Academic Year</label>
                <select name="academic_year" id="academic_year" class="form-select" onchange="this.form.submit()">
                    <option value="2025-26" <?php if($academicYearFilter=="2025-26") echo "selected"; ?>>2025-26</option>
                    <option value="2026-27" <?php if($academicYearFilter=="2026-27") echo "selected"; ?>>2026-27</option>
                </select>
            </div>
        </form>

        <!-- Search Bar -->
        <div class="d-flex justify-content-end mb-3">
            <input type="text" id="searchInput" class="search-bar" placeholder="🔍 Search...">
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover shadow-sm" id="facultyTable">
                <thead class="table-primary text-center">
                    <tr>
                        <th>S.No</th>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Credits</th>
                        <th>Year</th>
                        <th>academic_year</th>
                        <th>Section</th>
                        <th>Semester</th>
                        <th>Faculty ID</th>
                        <th>Faculty Name</th>
                        <th>Reg Date</th>
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
                            <td class="text-center"><?php echo htmlspecialchars($fs['academic_year'] ?? 'N/A'); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($fs['section'] ?? 'N/A'); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($fs['semester'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($fs['facultyId']); ?></td>
                            <td><?php echo htmlspecialchars($fs['facultyName']); ?></td>
                            <td><?php echo htmlspecialchars($fs['date_time'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center text-muted">No data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-3">
            <nav>
                <ul id="pagination" class="pagination mb-0"></ul>
                <span id="pageInfo" class="ms-3 align-self-center text-muted"></span>
            </nav>
        </div>
    </div>
</div>
<footer>
  &copy; <?= date('Y') ?> Rajiv Gandhi University of Knowledge Technologies Nuzvid. All rights reserved.
</footer>
<script>
const table = document.getElementById('facultyTable');
const tbody = table.querySelector('tbody');
const allRows = Array.from(tbody.querySelectorAll('tr')).filter(row => row.cells.length === 11);
const searchInput = document.getElementById('searchInput');
const pagination = document.getElementById('pagination');
const pageInfo = document.getElementById('pageInfo');
const pageSize = 10;
let currentPage = 1;
let filteredRows = allRows.slice();
let searchActive = false;

function updateSNo(rows, page) {
    rows.forEach((row, i) => {
        const sNoCell = row.querySelector('td');
        if (sNoCell) sNoCell.textContent = (page - 1) * pageSize + i + 1;
    });
}

function showPage(page) {
    const noResultsRow = document.getElementById('noResultsRow');
    if (noResultsRow) noResultsRow.remove();

    const totalPages = Math.ceil(filteredRows.length / pageSize) || 1;
    currentPage = Math.max(1, Math.min(page, totalPages));

    allRows.forEach(row => row.style.display = 'none');
    const start = (currentPage - 1) * pageSize;
    const end = start + pageSize;
    const pageRows = filteredRows.slice(start, end);

    // Only show "No results found" if search is active and there are no results
    if (searchActive && filteredRows.length === 0) {
        const tr = document.createElement('tr');
        tr.id = 'noResultsRow';
        const td = document.createElement('td');
        td.colSpan = 11;
        td.className = "text-center text-muted";
        td.textContent = "No results found";
        tr.appendChild(td);
        tbody.appendChild(tr);
        pagination.innerHTML = '';
        pageInfo.textContent = '';
        return;
    }

    pageRows.forEach(row => row.style.display = '');
    updateSNo(pageRows, currentPage);
    renderPagination(totalPages);
    pageInfo.textContent = '';
}

function renderPagination(totalPages) {
    pagination.innerHTML = '';
    if (totalPages <= 1) return;

    // Prev button
    const prevLi = document.createElement('li');
    prevLi.className = 'page-item' + (currentPage === 1 ? ' disabled' : '');
    const prevA = document.createElement('a');
    prevA.className = 'page-link';
    prevA.href = '#';
    prevA.innerHTML = '&laquo; Prev';
    prevA.addEventListener('click', function(e) {
        e.preventDefault();
        if (currentPage > 1) showPage(currentPage - 1);
    });
    prevLi.appendChild(prevA);
    pagination.appendChild(prevLi);

    // Page X of N (between Prev and Next)
    const infoLi = document.createElement('li');
    infoLi.className = 'page-item disabled';
    const infoSpan = document.createElement('span');
    infoSpan.className = 'page-link';
    infoSpan.textContent = `Page ${currentPage} of ${totalPages}`;
    infoLi.appendChild(infoSpan);
    pagination.appendChild(infoLi);

    // Next button
    const nextLi = document.createElement('li');
    nextLi.className = 'page-item' + (currentPage === totalPages ? ' disabled' : '');
    const nextA = document.createElement('a');
    nextA.className = 'page-link';
    nextA.href = '#';
    nextA.innerHTML = 'Next &raquo;';
    nextA.addEventListener('click', function(e) {
        e.preventDefault();
        if (currentPage < totalPages) showPage(currentPage + 1);
    });
    nextLi.appendChild(nextA);
    pagination.appendChild(nextLi);

    // Remove pageInfo outside pagination
    pageInfo.textContent = '';
}

// Search filter
searchInput.addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    searchActive = filter.length > 0;
    filteredRows = allRows.filter(row => {
        if (row.cells.length < 11) return false;
        for (let i = 1; i < row.cells.length; i++) {
            if (row.cells[i].textContent.toLowerCase().includes(filter)) {
                return true;
            }
        }
        return false;
    });
    showPage(1);
});

// Initial display
showPage(1);
</script>
</body>
</html>
