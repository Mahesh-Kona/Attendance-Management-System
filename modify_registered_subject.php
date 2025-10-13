<?php
session_start();
include("db_connect.php");

// Dept office login
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office') {
    die("Access Denied.");
}

$dept = $_SESSION['dept'];

// Handle update
// Handle update
if (isset($_POST['update'])) {
    $old_subject_code = $_POST['old_subject_code'];
    $old_faculty_id   = $_POST['old_faculty_id'];

    $faculty_id   = $_POST['faculty_id'];
    $faculty_name = $_POST['faculty_name'];

    // Check date_time within last month
    $check_sql = "SELECT date_time FROM subjects WHERE subject_code=? AND faculty_id=? AND dept=?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("sis", $old_subject_code, $old_faculty_id, $dept);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $row = $check_result->fetch_assoc();

    $current_date = date("Y-m-d H:i:s");
    $one_month_ago = date("Y-m-d H:i:s", strtotime("-1 month"));

    if ($row && $row['date_time'] >= $one_month_ago && $row['date_time'] <= $current_date) {
        $update_sql = "UPDATE subjects SET faculty_id=?, faculty_name=? WHERE subject_code=? AND faculty_id=? AND dept=?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssis", $faculty_id, $faculty_name, $old_subject_code, $old_faculty_id, $dept);

        if ($stmt->execute()) {
            echo "<script>alert('Faculty updated successfully!');</script>";
        } else {
            echo "<script>alert('Update failed!');</script>";
        }
    } else {
        echo "<script>alert('Cannot edit subjects older than 1 month or future subjects!');</script>";
    }
}

// =======================
// Fetch subjects for table
// =======================
$yearFilter = isset($_GET['year']) && $_GET['year'] !== '' ? $_GET['year'] : null;
$semesterFilter = isset($_GET['semester']) && $_GET['semester'] !== '' ? $_GET['semester'] : null;
$sectionFilter = isset($_GET['section']) && $_GET['section'] !== '' ? $_GET['section'] : null;

$sql = "SELECT * FROM subjects WHERE dept=?";
$params = [$dept];
$types = "s";

if ($yearFilter) {
    $sql .= " AND year=?";
    $params[] = $yearFilter;
    $types .= "s";
}
if ($semesterFilter) {
    $sql .= " AND semester=?";
    $params[] = $semesterFilter;
    $types .= "s";
}
if ($sectionFilter) {
    $sql .= " AND section=?";
    $params[] = $sectionFilter;
    $types .= "s";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

?>


<!DOCTYPE html>
<html>
<head>
    
    <title>Modify Registered Subjects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        }
        .main-content {
            flex: 1 0 auto;
            width: 100%;
        }
        table { border-collapse: collapse; width: 95%; margin: 20px auto; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background-color: #cce5ff; }
        button { padding: 5px 10px; }
        input[type="text"], input[type="number"], input[type="datetime-local"] { width: 30%; }
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
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex align-items-center justify-content-center position-relative mb-4">
            <div class="text-center">
                <h2>Department of <?php echo htmlspecialchars($dept); ?></h2> 
                <h3>Manage the subjects</h3>
            </div>
            <a href="dept_office_dashboard.php" class="btn btn-primary position-absolute end-0">
                Dashboard
            </a>
        </div>
        <!-- Filter Form -->
        <div class="container mb-3">
            <form method="GET" class="row g-3">
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
                    <label for="semester" class="form-label">Semester</label>
                    <select name="semester" id="semester" class="form-select" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="1" <?php if($semesterFilter=="1") echo "selected"; ?>>1</option>
                        <option value="2" <?php if($semesterFilter=="2") echo "selected"; ?>>2</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="section" class="form-label">Section</label>
                    <select name="section" id="section" class="form-select" onchange="this.form.submit()">
                        <option value="">All</option>
                        <?php for($i=1;$i<=6;$i++): ?>
                            <option value="<?= $i ?>" <?php if(isset($_GET['section']) && $_GET['section']==$i) echo "selected"; ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </form>
        </div>
        <!-- Search Bar Top Right -->
        <div class="container">
            <div class="row">
                <div class="col-12 d-flex justify-content-end align-items-center mb-2">
                    <input type="text" id="searchInput" class="search-bar" placeholder="🔍 Search...">
                </div>
            </div>
        </div>
        <!-- Table -->
        <div class="table-responsive">
            <table id="subjectsTable">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Credits</th>
                        <th>Year</th>
                        <th>Section</th>
                        <th>Semester</th>
                        <th>Faculty ID</th>
                        <th>Faculty Name</th>
                        <th>Date & Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sn=1; 
                    $current_date = date("Y-m-d H:i:s");
                    $one_month_ago = date("Y-m-d H:i:s", strtotime("-1 month"));
                    while($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <form method="POST" action="">
                            <td><?php echo $sn++; ?></td>
                            <td><?php echo $row['subject_code']; ?></td>
                            <td><?php echo $row['subject_name']; ?></td>
                            <td><?php echo $row['credits']; ?></td>
                            <td><?php echo $row['year']; ?></td>
                            <td><?php echo $row['section']; ?></td>
                            <td><?php echo $row['semester']; ?></td>
                            <!-- Faculty ID (editable) -->
                            <td>
                                <span class="text"><?php echo $row['faculty_id']; ?></span>
                                <input class="input" type="text" name="faculty_id" value="<?php echo $row['faculty_id']; ?>" style="display:none;">
                            </td>
                            <!-- Faculty Name (editable) -->
                            <td>
                                <span class="text"><?php echo $row['faculty_name']; ?></span>
                                <input class="input" type="text" name="faculty_name" value="<?php echo $row['faculty_name']; ?>" style="display:none;">
                            </td>
                            <td><?php echo $row['date_time']; ?></td>
                            <td>
                                <?php if ($row['date_time'] >= $one_month_ago && $row['date_time'] <= $current_date) { ?>
                                    <input type="hidden" name="old_subject_code" value="<?php echo $row['subject_code']; ?>">
                                    <input type="hidden" name="old_faculty_id" value="<?php echo $row['faculty_id']; ?>">
                                    <button type="button" class="editBtn">Edit</button>
                                    <button type="submit" name="update" class="saveBtn" style="display:none;">Save</button>
                                    <button type="button" class="cancelBtn" style="display:none;">Cancel</button>
                                <?php } else { ?>
                                    <span style="color:gray;">Not Editable</span>
                                <?php } ?>
                            </td>
                        </form>
                    </tr>
                    <?php } ?>
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
    </div>
    <p style="text-align:center; color:#555; font-style:italic; margin-top:10px;">
        <b>Note:</b> Only the subjects whose registered time is less than or equal to a month can be edited.
    </p>

    <br>
    <footer>
        &copy; <?= date('Y') ?> Rajiv Gandhi University of Knowledge Technologies Nuzvid. All rights reserved.
    </footer>
    <script>
        document.querySelectorAll('.editBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                let tr = btn.closest('tr');
                tr.querySelectorAll('.text').forEach(span => span.style.display='none');
                tr.querySelectorAll('.input').forEach(input => input.style.display='block');
                tr.querySelector('.saveBtn').style.display='inline-block';
                tr.querySelector('.cancelBtn').style.display='inline-block';
                btn.style.display='none';
            });
        });

        document.querySelectorAll('.cancelBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                let tr = btn.closest('tr');
                tr.querySelectorAll('.text').forEach(span => span.style.display='inline');
                tr.querySelectorAll('.input').forEach(input => input.style.display='none');
                tr.querySelector('.saveBtn').style.display='none';
                tr.querySelector('.editBtn').style.display='inline-block';
                btn.style.display='none';
            });
        });

        const table = document.getElementById('subjectsTable');
        const tbody = table.querySelector('tbody');
        const allRows = Array.from(tbody.querySelectorAll('tr')).filter(row => row.cells.length === 11);
        const searchInput = document.getElementById('searchInput');
        const pagination = document.getElementById('pagination');
        const pageInfo = document.getElementById('pageInfo');
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

            if (filteredRows.length === 0) {
                // Show "No results found"
                const tr = document.createElement('tr');
                tr.id = 'noResultsRow';
                const td = document.createElement('td');
                td.colSpan = 11;
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
                const subjectCode = row.cells[1].textContent.toLowerCase();
                const subjectName = row.cells[2].textContent.toLowerCase();
                const credits = row.cells[3].textContent.toLowerCase();
                const year = row.cells[4].textContent.toLowerCase();
                const section = row.cells[5].textContent.toLowerCase();
                const semester = row.cells[6].textContent.toLowerCase();
                const facultyId = row.cells[7].textContent.toLowerCase();
                const facultyName = row.cells[8].textContent.toLowerCase();
                const dateTime = row.cells[9].textContent.toLowerCase();
                return (
                    subjectCode.includes(filter) ||
                    subjectName.includes(filter) ||
                    credits.includes(filter) ||
                    year.includes(filter) ||
                    section.includes(filter) ||
                    semester.includes(filter) ||
                    facultyId.includes(filter) ||
                    facultyName.includes(filter) ||
                    dateTime.includes(filter)
                );
            });
            showPage(1);
        });

        // Initial display
        showPage(1);
    </script>
</body>
</html>



