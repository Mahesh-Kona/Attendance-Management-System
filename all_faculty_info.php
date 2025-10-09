<?php
session_start();
// Ensure only department office users can access this page
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office'){
    die("Access Denied. This action is only allowed for Department Office users.");
}

include 'db_connect.php'; 

// Fetch all students in the department with filters
$dept = $_SESSION['dept'];


$sql = "SELECT facultyID, facultyName,contact 
        FROM userfaculty
        WHERE dept = ?";


$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $dept);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Faculty Info</title>
    <style>
        table, th, td { border-collapse: collapse; padding: 8px; }
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
            <h3>Faculty Data</h3>
        </div>
        <a href="dept_office_dashboard.php" class="btn btn-primary position-absolute end-0">
            Dashboard
        </a>
    </div>

    <!-- Search Bar -->
    <div class="d-flex justify-content-end mb-3">
        <input type="text" id="searchInput" class="search-bar" placeholder="🔍 Search Faculty...">
    </div>

    <!-- Faculty Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover shadow-sm" id="facultyTable">
            <thead class="table-primary text-center">
                <tr>
                    <th>S.No</th>
                    <th>Faculty ID</th>
                    <th>Faculty Name</th>
                    <th>Contact</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                    <tr class="align-middle">
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($row['facultyID']); ?></td>
                        <td><?php echo htmlspecialchars($row['facultyName']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">No Faculty found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Replace the button-based pagination block with page number pagination -->
    <div class="d-flex justify-content-center mt-3">
        <nav>
            <ul id="pagination" class="pagination"></ul>
        </nav>
    </div>
</div>
<br>
<footer>
  &copy; <?= date('Y') ?> Rajiv Gandhi University of Knowledge Technologies Nuzvid. All rights reserved.
</footer>

<script>
const table = document.getElementById('facultyTable');
const tbody = table.querySelector('tbody');
const allRows = Array.from(tbody.querySelectorAll('tr'));
const pagination = document.getElementById('pagination');
const searchInput = document.getElementById('searchInput');
const pageSize = 10;
let currentPage = 1;
let filteredRows = allRows.filter(row => row.cells.length === 4);

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
        td.colSpan = 4;
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
        if (row.cells.length < 4) return false;
        const facultyID = row.cells[1].textContent.toLowerCase();
        const facultyName = row.cells[2].textContent.toLowerCase();
        const contact = row.cells[3].textContent.toLowerCase();
        return (
            facultyID.includes(filter) ||
            facultyName.includes(filter) ||
            contact.includes(filter)
        );
    });
    showPage(1);
});

// Initial display
showPage(1);
</script>
</body>
</html>
