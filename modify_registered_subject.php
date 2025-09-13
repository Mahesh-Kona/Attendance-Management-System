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
        table { border-collapse: collapse; width: 95%; margin: 20px auto; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background-color: #cce5ff; }
        button { padding: 5px 10px; }
        input[type="text"], input[type="number"], input[type="datetime-local"] { width: 100%; }
    </style>
</head>
<body>
    <br><br>
    <div class="d-flex align-items-center justify-content-center position-relative mb-4">
        <div class="text-center">
            <h2>Department of <?php echo htmlspecialchars($dept); ?></h2> 
            <h3>Manage the Registered Subjects</h3>
        </div>
        <a href="dept_office_dashboard.php" class="btn btn-primary position-absolute end-0">
            Dashboard
        </a>
    </div>

    <!-- Filter Form -->
<div class="container mb-3">
    <form method="GET" class="row g-3">
        <div class="col-md-6">
            <label for="year" class="form-label">Year</label>
            <select name="year" id="year" class="form-select" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="E1" <?php if($yearFilter=="E1") echo "selected"; ?>>E1</option>
                <option value="E2" <?php if($yearFilter=="E2") echo "selected"; ?>>E2</option>
                <option value="E3" <?php if($yearFilter=="E3") echo "selected"; ?>>E3</option>
                <option value="E4" <?php if($yearFilter=="E4") echo "selected"; ?>>E4</option>
            </select>
        </div>
        <div class="col-md-6">
            <label for="semester" class="form-label">Semester</label>
            <select name="semester" id="semester" class="form-select" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="1" <?php if($semesterFilter=="1") echo "selected"; ?>>1</option>
                <option value="2" <?php if($semesterFilter=="2") echo "selected"; ?>>2</option>
            </select>
        </div>
    </form>
</div>

    <!--  Table -->
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

    <p style="text-align:center; color:#555; font-style:italic; margin-top:10px;">
    <b>Note:</b> Only the subjects whose registered time is less than or equal to a month can be edited.
    </p>

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
</script>

</body>
</html>
