<?php
session_start();

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'faculty') {
    die("Access Denied. Faculty only.");
}

// Check which exam (month/test) the Department Office has selected for this session
$selected_exam = isset($_SESSION['selected_exam']) ? trim($_SESSION['selected_exam']) : '';

// Set timezone to India (IST)
date_default_timezone_set("Asia/Kolkata");

include 'db_connect.php';

// Get data from query params
$faculty_id   = $_GET['faculty_id'] ?? '';
$faculty_name = $_GET['faculty_name'] ?? '';
$subject_code = $_GET['subject_code'] ?? '';
$subject_name = $_GET['subject_name'] ?? '';
$section      = $_GET['section'] ?? '';
$year         = $_GET['year'] ?? '';   
$semester     = $_GET['semester'] ?? 1; //modify this for every sem
// Get department from subjects table
// Determine department for this class.
// Prefer the explicit dept passed in the URL (faculty_dashboard.php includes it).
$subject_dept = $_GET['dept'] ?? '';
// If dept not provided, try to resolve it using subject_code + faculty_id to support
// cases where same subject_code is used across multiple departments.
if (empty($subject_dept) && !empty($subject_code)) {
    // Prefer matching subject_code + faculty_id (if faculty_id provided)
    if (!empty($faculty_id)) {
        $sql = "SELECT dept FROM subjects WHERE subject_code = ? AND faculty_id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ss", $subject_code, $faculty_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $subject_dept = $row['dept'];
            }
            $stmt->close();
        }
    }
    // Fallback: if still empty, pick the first dept for this subject_code
    if (empty($subject_dept)) {
        $sql = "SELECT dept FROM subjects WHERE subject_code = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $subject_code);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $subject_dept = $row['dept'];
            }
            $stmt->close();
        }
    }
}
// If session doesn't have selected_exam, try to load persisted exam for this subject's department
if (empty($selected_exam) && !empty($subject_dept)) {
    $q = $conn->prepare("SELECT exam FROM current_exam WHERE dept = ? LIMIT 1");
    if ($q) {
        $q->bind_param("s", $subject_dept);
        $q->execute();
        $r = $q->get_result();
        if ($rr = $r->fetch_assoc()) {
            $_SESSION['selected_exam'] = $rr['exam'];
            $selected_exam = trim($rr['exam']);
        }
        $q->close();
    }
}
$academic_year='2025-26';
// Fetch students
// If dept or section or year are missing, avoid running an ambiguous query.
$students_data = [];
if (empty($subject_dept)) {
    // No dept resolved - show no students and inform user (dept should be passed from faculty dashboard)
    $students = null;
} else {
    $sql = "SELECT studentId, studentName, year, academic_year, section, dept, '1' as dummy
            FROM userstudent
            WHERE section = ? AND dept = ? AND year = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sss", $section, $subject_dept, $year);
        $stmt->execute();
        $students = $stmt->get_result();
        $stmt->close();
        while ($row = $students->fetch_assoc()) {
            $students_data[] = $row;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure Department Office has selected an exam for this session
    if (empty($selected_exam)) {
        echo "<script>alert('Department Office has not selected an exam/month. Please contact the Department Office to set the exam before saving attendance.'); window.location='faculty_dashboard.php';</script>";
        exit;
    }

    $subject_code  = $_POST['subject_code'];
    $subject_name  = $_POST['subject_name'];
    $faculty_name  = $_POST['faculty_name'];
    $section       = $_POST['section'];
    $dept          = $_POST['dept'];
    // Use the exam selected by Department Office to avoid tampering
    $month         = $selected_exam;

    // New: Get no_of_periods and period(s)
    $no_of_periods = isset($_POST['no_of_periods']) ? (int)$_POST['no_of_periods'] : 0;
    $period_input = $_POST['period'];
    $periods = array_map('trim', explode(',', $period_input));
    if ($no_of_periods > 0) {
        $periods = array_slice($periods, 0, $no_of_periods);
    }

    //modify it for every sem
    foreach ($_POST['student_name'] as $student_id => $student_name) {
        $year_db       = $_POST['year'][$student_id];
        $academic_year = '2025-26';
        $semester      = (int)$_POST['semester'][$student_id];
        $status = isset($_POST['attendance'][$student_id]) ? 'P' : 'A';

        foreach ($periods as $period) {
            $period = (int)$period;
            $insert = "INSERT INTO attendance 
                      (student_id, student_name, year,academic_year, subject_code, subject_name, section, faculty_name, status, `time`, period, dept, semester, month) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert);
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param(
                "sssssssssisis",
                $student_id,    // s
                $student_name,  // s
                $year_db,       // s
                $academic_year, // s
                $subject_code,  // s
                $subject_name,  // s
                $section,       // s
                $faculty_name,  // s
                $status,        // s
                $period,        // i
                $dept,          // s
                $semester,      // i
                $month          // s
            );
            if (!$stmt->execute()) {
                die("Insert failed: " . $stmt->error);
            }
            $stmt->close();
        }
    }

    echo "<script>alert('Attendance Saved Successfully!'); window.location='faculty_dashboard.php';</script>";
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Take Attendance</title>
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
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
            padding: 20px 0 0 0;
        }
        .main-content {
            flex: 1 0 auto;
        }
        h1, h2, h5 { color: #333; }

        .header {
            position: relative;
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 { margin: 0; }
        .header a {
            position: absolute;
            right: 0;
            top: 0;
        }

        .card {
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background: #fff;
            margin-bottom: 20px;
        }

        /* Search bar style (matching all_students_info.php) */
        .search-bar {
            width: 100%;
            max-width: 420px;
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

        table th, table td { vertical-align: middle; }
        
        footer {
            background: #002147;
            color: #fff;
            text-align: center;
            padding: 15px 0;
            font-size: 0.9rem;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            margin-top: auto;
        }
    </style>
</head>
<body style="display:flex; flex-direction:column; min-height:100vh; padding-bottom:0;">

    <!-- Header -->
    <div class="header">
        
        <h1>Attendance Dashboard</h1>
        <a href="faculty_dashboard.php" class="btn btn-primary">Dashboard</a>
    </div>
    <div class="main-content">
       
        <form method="POST">
            <!-- Faculty/Class Details -->
            <div class="card">
                <!-- <h5><strong><center>Class & Faculty Details</strong></center></h5><br> -->
                <div class="row">
                    <!-- Left column (Class details) -->
                    <div class="col-md-6">
                        <p>

                           <b>Subject:</b> <?php echo htmlspecialchars($subject_name); ?> (<?php echo htmlspecialchars($subject_code); ?>)<br>
                           <b>Year:</b> <?php echo htmlspecialchars($year); ?><br>
                           <b>Semester:</b> <?php echo htmlspecialchars($semester); ?><br>
                           <b>Department:</b> <?php echo htmlspecialchars($subject_dept); ?><br>
                           <b>Section:</b> <?php echo htmlspecialchars($section); ?><br>
                           <b>Academic Year:</b> <?php echo htmlspecialchars($academic_year);?><br>
                           <b>Date & Time:</b> <?php echo date("d-m-Y H:i:s"); ?><br>
                        </p>
                    </div>

                    <!-- Right column (Faculty + Inputs) -->
                    <div class="col-md-6">
                        <p>
                           <!-- No of Periods (optional) -->
                           <b>No of Periods (optional):</b>
                           <input type="number" name="no_of_periods" min="1" max="7" placeholder="Enter no of periods" 
                                  class="form-control mb-3" style="width: 150px;">

                           <!-- Period(s) -->
                           <b>Period(s):</b>
                           <input type="text" name="period" placeholder="Comma Seperated" 
                                  class="form-control mb-3" style="width: 150px;" required>

                           <!-- Month/Test: show department-selected exam or a warning -->
                           <b>Exam:</b>
                           <?php if (!empty($selected_exam)) { ?>
                               <input type="text" class="form-control mb-3" style="width:160px;" value="<?php echo htmlspecialchars($selected_exam); ?>" readonly>
                           <?php } else { ?>
                               <div class="alert alert-warning p-2" style="width:320px;">Exam is not selected by your Dept Office</div>
                           <?php } ?>
                           <!-- Keep a hidden input so server receives subject_code etc. month is set server-side from session -->
                        </p>
                    </div>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><strong>Mark Attendance</strong></h5>
                    <div class="ms-3" style="min-width:260px; max-width:50%;">
                        <input id="searchInput" type="text" class="search-bar" placeholder="🔍 Search students..." aria-label="Search students">
                    </div>
                </div>
                <table id="studentsTable" class="table table-bordered table-striped">
                    <thead class="table-primary">
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Roll No</th>

                            <th>
                                Present/Absent
                               &nbsp&nbsp&nbsp
                               <label for="checkAll">All</label>
                                <input type="checkbox" id="checkAll" onclick="toggleAll(this)" checked>
                                
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($students_data)) { ?>
                        <?php foreach ($students_data as $i => $row) { ?>
                            <tr>
                                <td class="student-id"><?php echo htmlspecialchars($row['studentId']); ?></td>
                                <td class="student-name"><?php echo htmlspecialchars($row['studentName']); ?></td>
                                <td class="student-roll"><?php echo $i + 1; ?></td>
                                <td>
                                    <input type="checkbox" class="student-check" name="attendance[<?php echo $row['studentId']; ?>]" value="P" checked>
                                </td>
                            </tr>
                            <!-- Hidden fields -->
                            <input type="hidden" name="student_name[<?php echo $row['studentId']; ?>]" value="<?php echo htmlspecialchars($row['studentName']); ?>">
                            <input type="hidden" name="year[<?php echo $row['studentId']; ?>]" value="<?php echo htmlspecialchars($row['year']); ?>">
                            <input type="hidden" name="semester[<?php echo $row['studentId']; ?>]" value="<?php echo 1; ?>">
                            <input type="hidden" name="academic_year[<?php echo $row['studentId']; ?>]" value="<?php htmlspecialchars($academic_year); ?>">
                            <input type="hidden" name="subject_code" value="<?php echo htmlspecialchars($subject_code); ?>">
                            <input type="hidden" name="subject_name" value="<?php echo htmlspecialchars($subject_name); ?>">
                            <input type="hidden" name="faculty_name" value="<?php echo htmlspecialchars($faculty_name); ?>">
                            <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
                            <input type="hidden" name="dept" value="<?php echo htmlspecialchars($subject_dept); ?>">
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No students available</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>

                <button type="submit" id="saveBtn" class="btn btn-success" <?php if (empty($selected_exam)) echo 'disabled'; ?>>Save Attendance</button>
            </div>
        </form>
    </div>
    <footer>
    &copy; <?= date('Y') ?> Rajiv Gandhi University of Knowledge Technologies Nuzvid. All rights reserved.
    </footer>

    <script>
    function toggleAll(source) {
        var checkboxes = document.querySelectorAll('.student-check');
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = source.checked;
        }
    }
    // Filter students by ID, Name or Roll No
    (function() {
        var input = document.getElementById('searchInput');
        if (!input) return;
        var table = document.getElementById('studentsTable');
        var tbody = table ? table.tBodies[0] : null;
        input.addEventListener('input', function() {
            var q = input.value.trim().toLowerCase();
            if (!tbody) return;
            var visibleCount = 0;
            for (var i = 0; i < tbody.rows.length; i++) {
                var row = tbody.rows[i];
                // skip any rows that are used as placeholders (like a single-cell 'No students available')
                if (row.cells.length < 4) continue;
                var id = (row.querySelector('.student-id') || {textContent: ''}).textContent.trim().toLowerCase();
                var name = (row.querySelector('.student-name') || {textContent: ''}).textContent.trim().toLowerCase();
                var roll = (row.querySelector('.student-roll') || {textContent: ''}).textContent.trim().toLowerCase();
                if (q === '' || id.indexOf(q) !== -1 || name.indexOf(q) !== -1 || roll.indexOf(q) !== -1) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }

            // Manage "No students found" row
            var noRow = document.getElementById('noSearchResultsRow');
            if (visibleCount === 0) {
                if (!noRow) {
                    noRow = document.createElement('tr');
                    noRow.id = 'noSearchResultsRow';
                    var td = document.createElement('td');
                    td.colSpan = 4;
                    td.className = 'text-center text-muted';
                    td.textContent = 'No students found.';
                    noRow.appendChild(td);
                    tbody.appendChild(noRow);
                }
            } else {
                if (noRow) noRow.remove();
            }
        });
    })();
    </script>
    <script>
    // Prevent form submission if department exam not selected (double-check client-side)
    (function(){
        var form = document.querySelector('form');
        var saveBtn = document.getElementById('saveBtn');
        var examSelected = <?php echo !empty($selected_exam) ? 'true' : 'false'; ?>;
        if (!form) return;
        form.addEventListener('submit', function(e){
            if (!examSelected) {
                e.preventDefault();
                alert('Cannot save attendance: Department Office has not selected an exam/month. Please contact them to set the exam first.');
                return false;
            }
            // otherwise allow submit; server will also validate
        });
    })();
    </script>
</body>
</html>
