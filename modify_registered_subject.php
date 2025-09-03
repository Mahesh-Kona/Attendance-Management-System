<?php
session_start();
include("db_connect.php");

//  Dept office login
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office') {
    die("Access Denied.");
}

$dept = $_SESSION['dept'];

//  Handle update
if (isset($_POST['update'])) {
    $old_subject_code = $_POST['old_subject_code'];
    $old_faculty_id   = $_POST['old_faculty_id'];

    $subject_code = $_POST['subject_code'];
    $subject_name = $_POST['subject_name'];
    $credits = $_POST['credits'];
    $year = $_POST['year'];
    $section = $_POST['section'];
    $semester = $_POST['semester'];
    $faculty_id = $_POST['faculty_id'];
    $faculty_name = $_POST['faculty_name'];
    $date_time = $_POST['date_time'];

    // Check date_time within last month
    $check_sql = "SELECT date_time FROM subjects WHERE subject_code=? AND faculty_id=? AND dept=?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("sis", $old_subject_code, $old_faculty_id, $dept);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $row = $check_result->fetch_assoc();

    $current_date = date("Y-m-d H:i:s");
    $one_month_ago = date("Y-m-d H:i:s", strtotime("-1 month"));

    if ($row['date_time'] >= $one_month_ago && $row['date_time'] <= $current_date) {
        $update_sql = "UPDATE subjects SET subject_code=?, subject_name=?, credits=?, year=?, section=?, semester=?, faculty_id=?, faculty_name=?, date_time=? WHERE subject_code=? AND faculty_id=? AND dept=?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssisisisssis",
            $subject_code, $subject_name, $credits, $year, $section, $semester,
            $faculty_id, $faculty_name, $date_time,
            $old_subject_code, $old_faculty_id, $dept
        );
        if ($stmt->execute()) {
            echo "<script>alert(' Subject updated successfully!');</script>";
        } else {
            echo "<script>alert(' Update failed!');</script>";
        }
    } else {
        echo "<script>alert(' Cannot edit subjects older than 1 month or future subjects!');</script>";
    }
}

// ✅ Fetch all subjects for this dept
$sql = "SELECT * FROM subjects WHERE dept=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $dept);
$stmt->execute();
$result = $stmt->get_result();
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
<div class="d-flex justify-content-between align-items-center mb-4">
       <h2 class="text-center">Update the details of the registered subjects-<?php echo htmlspecialchars($dept); ?></h2>
     <a href="dept_office_dashboard.php" class="btn btn-primary">Dashboard</a>
</div>    
   
    
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
                <td><span class="text"><?php echo $row['subject_code']; ?></span><input class="input" type="text" name="subject_code" value="<?php echo $row['subject_code']; ?>" style="display:none;"></td>
                <td><span class="text"><?php echo $row['subject_name']; ?></span><input class="input" type="text" name="subject_name" value="<?php echo $row['subject_name']; ?>" style="display:none;"></td>
                <td><span class="text"><?php echo $row['credits']; ?></span><input class="input" type="number" name="credits" value="<?php echo $row['credits']; ?>" style="display:none;"></td>
                <td><span class="text"><?php echo $row['year']; ?></span><input class="input" type="text" name="year" value="<?php echo $row['year']; ?>" style="display:none;"></td>
                <td><span class="text"><?php echo $row['section']; ?></span><input class="input" type="text" name="section" value="<?php echo $row['section']; ?>" style="display:none;"></td>
                <td><span class="text"><?php echo $row['semester']; ?></span><input class="input" type="number" name="semester" value="<?php echo $row['semester']; ?>" style="display:none;"></td>
                <td><span class="text"><?php echo $row['faculty_id']; ?></span><input class="input" type="text" name="faculty_id" value="<?php echo $row['faculty_id']; ?>" style="display:none;"></td>
                <td><span class="text"><?php echo $row['faculty_name']; ?></span><input class="input" type="text" name="faculty_name" value="<?php echo $row['faculty_name']; ?>" style="display:none;"></td>
                <td><span class="text"><?php echo $row['date_time']; ?></span><input class="input" type="datetime-local" name="date_time" value="<?php echo date('Y-m-d\TH:i', strtotime($row['date_time'])); ?>" style="display:none;"></td>
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
</table>

<p style="text-align:center; color:#555; font-style:italic; margin-top:10px;">
<b>Note:</b>Only the subjects whose registered time is less than or equal to a month can be edited.
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
