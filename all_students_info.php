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
$ayFilter=isset($_GET['academic_year']) && $_GET['academic_year'] !== '' ? $_GET['academic_year'] : null;
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
if($ayFilter){
    $sql .= " AND academic_year = ?";
    $types .= "s";
    $params[] = $ayFilter;
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
    <title>All Students Info</title>
    <style>
        table, th, td {border-collapse: collapse; padding: 8px; }
        th { background-color: #f2f2f2; }
        .header-container { text-align: right; padding: 10px; background-color: #f8f9fa; }
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
<body style="display:flex; flex-direction:column; min-height:100vh; overflow-x:hidden;">
   

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
            <div class="col-md-4">
                <label for="year" class="form-label">Year</label>
                <select name="year" id="year" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="E1" <?php if($yearFilter=="E1") echo "selected"; ?>>E1</option>
                    <option value="E2" <?php if($yearFilter=="E2") echo "selected"; ?>>E2</option>
                    <option value="E3" <?php if($yearFilter=="E3") echo "selected"; ?>>E3</option>
                    <option value="E4" <?php if($yearFilter=="E4") echo "selected"; ?>>E4</option>
                </select>
            </div>
             <div class="col-md-4">
                <label for="academic_year" class="form-label">Academic Year</label>
                <select name="academic_year" id="academic_year" class="form-select" onchange="this.form.submit()">
                    
                    <option value="2025-26" <?php if($ayFilter=="2025-26") echo "selected"; ?>>2025-26</option>
                    <option value="2026-27" <?php if($ayFilter=="2026-27") echo "selected"; ?>>2026-27</option>
                    
                </select>
            </div>
            <div class="col-md-4">
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

        <!-- Search Bar -->
        <div class="d-flex justify-content-end mb-3">
            <input type="text" id="searchInput" class="search-bar" placeholder="🔍 Search Student...">
        </div>

        <!-- Students Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover shadow-sm" id="studentsTable">
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
        <!-- Pagination block below the table -->
<div class="d-flex justify-content-center mt-3">
    <nav>
        <ul id="pagination" class="pagination mb-0"></ul>
        <span id="pageInfo" class="ms-3 align-self-center text-muted"></span>
    </nav>
</div>

<script>
const table = document.getElementById('studentsTable');
const tbody = table.querySelector('tbody');
const allRows = Array.from(tbody.querySelectorAll('tr')).filter(row => row.cells.length === 6);
const pagination = document.getElementById('pagination');
const pageInfo = document.getElementById('pageInfo');
const searchInput = document.getElementById('searchInput');
const pageSize = 10;
let currentPage = 1;
let filteredRows = allRows.slice();

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
        if (row.cells.length < 6) return false;
        const studentId = row.cells[1].textContent.toLowerCase();
        const studentName = row.cells[2].textContent.toLowerCase();
        const year = row.cells[3].textContent.toLowerCase();
        const section = row.cells[4].textContent.toLowerCase();
        const contact = row.cells[5].textContent.toLowerCase();
        return (
            studentId.includes(filter) ||
            studentName.includes(filter) ||
            year.includes(filter) ||
            section.includes(filter) ||
            contact.includes(filter)
        );
    });
    showPage(1);
});

// Initial display
showPage(1);
</script>
   </div>
   <footer>
      &copy; <?= date('Y') ?> Rajiv Gandhi University of Knowledge Technologies Nuzvid. All rights reserved.
    </footer>

    <script>
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('keyup', function() {
    var filter = this.value.toLowerCase();
    var rows = document.querySelectorAll('#studentsTable tbody tr');
    var visibleCount = 0;

    rows.forEach(function(row) {
        // Only search/filter among rows currently visible from PHP filtering
        if (filter !== "") {
            var studentId = row.cells[1].textContent.toLowerCase();
            var studentName = row.cells[2].textContent.toLowerCase();
            var year = row.cells[3].textContent.toLowerCase();
            var section = row.cells[4].textContent.toLowerCase();
            var contact = row.cells[5].textContent.toLowerCase();
            if (
                studentId.includes(filter) ||
                studentName.includes(filter) ||
                year.includes(filter) ||
                section.includes(filter) ||
                contact.includes(filter)
            ) {
                row.sactyle.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        } else {
            // If search bar is empty, restore rows to their original PHP-filtered state
            row.style.display = '';
            visibleCount++;
        }
    });

    

    
});
</script>
</body>
</html>

