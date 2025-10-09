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
if($yearFilter && $yearFilter !== 'All'){
    $sql .= " AND year = ?";
    $types .= "s";
    $params[] = $yearFilter;
}
if($semesterFilter && $semesterFilter !== 'All'){
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
      padding: 20px 0 0 0;
    }
    .main-content {
      flex: 1 0 auto;
    }
    .header {
      width: 100%;
      box-sizing: border-box;
      text-align: center;
      margin-bottom: 30px;
      padding: 0 10px;
      overflow-x: auto;
    }
    .header h1, .header h2 {
      margin: 0;
      font-weight: 600;
      word-break: break-word;
      font-size: 2rem;
    }
    .header a {
      position: absolute;
      right: 10px;
      top: 10px;
      white-space: nowrap;
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
<body style="display:flex; flex-direction:column; min-height:100vh;">
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
            <option value="All" <?php if($yearFilter=="All" || !$yearFilter) echo "selected"; ?>>All</option>
            <option value="E1" <?php if($yearFilter=="E1") echo "selected"; ?>>E1</option>
            <option value="E2" <?php if($yearFilter=="E2") echo "selected"; ?>>E2</option>
            <option value="E3" <?php if($yearFilter=="E3") echo "selected"; ?>>E3</option>
            <option value="E4" <?php if($yearFilter=="E4") echo "selected"; ?>>E4</option>
        </select>
    </div>
    <div class="col-md-4">
        <label for="semester" class="form-label">Semester</label>
        <select name="semester" id="semester" class="form-select" onchange="this.form.submit()">
            <option value="All" <?php if($semesterFilter=="All" || !$semesterFilter) echo "selected"; ?>>All</option>
            <option value="1" <?php if($semesterFilter=="1") echo "selected"; ?>>1</option>
            <option value="2" <?php if($semesterFilter=="2") echo "selected"; ?>>2</option>
        </select>
    </div>
    <div class="col-md-4">
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

        <!-- Subjects Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover shadow-sm" id="subjectsTable">
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
        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-3">
    <nav>
        <ul id="pagination" class="pagination"></ul>
    </nav>
</div>
   </div>
   <footer>
  &copy; <?= date('Y') ?> Rajiv Gandhi University of Knowledge Technologies Nuzvid. All rights reserved.
</footer>
</body>
</html>

<script>
const table = document.getElementById('subjectsTable');
const tbody = table.querySelector('tbody');
const allRows = Array.from(tbody.querySelectorAll('tr'));
const pagination = document.getElementById('pagination');
const searchInput = document.getElementById('searchInput');
const pageSize = 10;
let currentPage = 1;
let filteredRows = allRows.filter(row => row.cells.length === 6);

function updateSNo(rows, page) {
    rows.forEach((row, i) => {
        const sNoCell = row.querySelector('td');
        if (sNoCell) sNoCell.textContent = (page - 1) * pageSize + i + 1;
    });
}

function showPage(page) {
    // Remove "No results found" row if present
    const noResultsRow = document.getElementById('noResultsRow');
    if (noResultsRow) noResultsRow.remove();

    const totalPages = Math.ceil(filteredRows.length / pageSize) || 1;
    currentPage = Math.max(1, Math.min(page, totalPages));

    // Hide all rows first
    allRows.forEach(row => row.style.display = 'none');

    // Show only the rows for the current page
    const start = (currentPage - 1) * pageSize;
    const end = start + pageSize;
    const pageRows = filteredRows.slice(start, end);

    if (filteredRows.length === 0) {
        // Show "No results found"
        const tr = document.createElement('tr');
        tr.id = 'noResultsRow';
        const td = document.createElement('td');
        td.colSpan = 6;
        td.className = "text-center text-muted";
        td.textContent = "No results found";
        tr.appendChild(td);
        tbody.appendChild(tr);
        pagination.innerHTML = '';
        return;
    }

    pageRows.forEach(row => row.style.display = '');
    updateSNo(pageRows, currentPage);

    renderPagination(totalPages);
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

    // Page X of N
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
}

searchInput.addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    filteredRows = allRows.filter(row => {
        // Only filter actual data rows
        if (row.cells.length < 6) return false;
        const code = row.cells[1].textContent.toLowerCase();
        const name = row.cells[2].textContent.toLowerCase();
        const year = row.cells[3].textContent.toLowerCase();
        const semester = row.cells[4].textContent.toLowerCase();
        const academicYear = row.cells[5].textContent.toLowerCase();
        return (
            code.includes(filter) ||
            name.includes(filter) ||
            year.includes(filter) ||
            semester.includes(filter) ||
            academicYear.includes(filter)
        );
    });
    showPage(1);
});

// Initial display
showPage(1);
</script>
