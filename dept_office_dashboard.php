<?php
session_start();
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office') {
    die("Access Denied. This page is only for Department Office users.");
}

// DB connection
include "db_connect.php";

// logged-in user id
$userID = $_SESSION['userID'];

// Get department of logged-in user
$stmt = $conn->prepare("SELECT dept FROM admin_roles WHERE username= ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$stmt->bind_result($dept);
$stmt->fetch();
$stmt->close();

// Handle exam selection submission: persist to existing current_exam table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_exam'])) {
  $selected = $_POST['selected_exam'];
  $upsert = $conn->prepare("INSERT INTO current_exam (dept, exam) VALUES (?, ?) ON DUPLICATE KEY UPDATE exam = VALUES(exam)");
  if ($upsert) {
    $upsert->bind_param("ss", $dept, $selected);
    $upsert->execute();
    $upsert->close();
  }
  // keep in session for immediate use
  $_SESSION['selected_exam'] = $selected;
  header('Location: ' . $_SERVER['PHP_SELF']);
  exit;
}

// If session doesn't have selected_exam, try to load persisted exam for this dept
if (empty($_SESSION['selected_exam']) && !empty($dept)) {
  $q = $conn->prepare("SELECT exam FROM current_exam WHERE dept = ? LIMIT 1");
  if ($q) {
    $q->bind_param("s", $dept);
    $q->execute();
    $res = $q->get_result();
    if ($row = $res->fetch_assoc()) {
      $_SESSION['selected_exam'] = $row['exam'];
    }
    $q->close();
  }
}

// Count unique subjects in subjects
$result = $conn->query("SELECT COUNT(DISTINCT subject_code) AS total_subjects FROM subjects WHERE dept='$dept'");
$total_subjects = ($result) ? $result->fetch_assoc()['total_subjects'] : 0;
// Count unique faculty in userfaculty
$result2 = $conn->query("SELECT COUNT(DISTINCT facultyId) AS total_faculty FROM userfaculty WHERE dept='$dept'");
$total_faculty = ($result2) ? $result2->fetch_assoc()['total_faculty'] : 0;

// Count total students in this department
$result3 = $conn->query("SELECT COUNT(*) AS total_students FROM userstudent WHERE dept='$dept'");
$total_students = ($result3) ? $result3->fetch_assoc()['total_students'] : 0;

$conn->close();
?>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dept Office Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { 
      background: #f8f9fa; 
      display: flex; 
      flex-direction: column; 
      min-height: 100vh; 
      overflow-x: hidden; 
    }
    .header { text-align: center; margin: 30px 0; }
    .header h1 { font-weight: bold; color: #333; }
    .header p { font-size: 1.1rem; color: #666; }
    /* Logout button: align responsively (right on desktop, full-width on small screens) */
    .logout-btn { position: static; }
    @media (max-width: 576px) {
      .logout-btn { width: 100%; display: block; margin-top: 12px; }
    }
    .card { 
      border-radius: 15px; 
      transition: transform 0.2s, box-shadow 0.2s; 
    }
    .card:hover { 
      transform: translateY(-5px); 
      box-shadow: 0 4px 15px rgba(0,0,0,0.15); 
    }
    .card-body i { font-size: 2.5rem; }
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
      position:fixed;
    }
  </style>
</head>
<body style="display:flex; flex-direction:column; min-height:100vh; overflow-x:hidden;">
<br>

<div class="position-relative mb-3 text-center">

  <!-- Centered Title -->
  <!-- <div>
    <h1>Attendance Management System</h1>
    <h2>Department Office Dashboard</h2>
    <p class="text-muted">
      Department: <strong><?php echo htmlspecialchars($dept); ?></strong>
    </p>
  </div>

  
  <div class="position-absolute top-0 end-0">
    <a href="index.php" onclick="return hi()" class="btn btn-primary">
      Logout
    </a>
  </div>
</div> -->
<!-- Header -->
  <div class="d-flex align-items-center justify-content-between mb-4" style="flex-wrap:nowrap;">
    <div class="text-center text-sm-start" style="min-width:180px; flex-shrink:1;">
      <h1 class="mb-0">Department Office Dashboard</h1>
      <p class="text-muted mb-0">
        Department: <strong><?php echo htmlspecialchars($dept); ?></strong>
      </p>
    </div>
    <div class="d-flex align-items-center" style="gap:100px; white-space:nowrap;">
      <form method="post" class="d-inline-flex align-items-center" id="examForm" onsubmit="return confirmExamSelection();" style="gap:8px; margin:0;">
        <label for="selected_exam" class="me-2 mb-0">Exam:</label>
        <select name="selected_exam" id="selected_exam" class="form-select form-select-sm" style="width:150px;">
          <option value="" <?php if(empty($_SESSION['selected_exam'])) echo 'selected'; ?>>--None--</option>
          <option value="MT-1" <?php if(isset($_SESSION['selected_exam']) && $_SESSION['selected_exam']==='MT-1') echo 'selected'; ?>>MT-1</option>
          <option value="MT-2" <?php if(isset($_SESSION['selected_exam']) && $_SESSION['selected_exam']==='MT-2') echo 'selected'; ?>>MT-2</option>
          <option value="MT-3" <?php if(isset($_SESSION['selected_exam']) && $_SESSION['selected_exam']==='MT-3') echo 'selected'; ?>>MT-3</option>
         
        </select>
        <button type="submit" class="btn btn-secondary btn-success ms-2">Set</button>
      </form>
      <a href="index.php" class="btn btn-primary logout-btn d-inline-block" onclick="return hi()">Logout</a>
    </div>
  </div>


<div class="container">
  <div class="row g-4 justify-content-center">

    <!-- Subjects -->
    <div class="col-md-3">
      <a href="all_subjects_info.php" class="text-decoration-none">
        <div class="card text-center shadow-sm h-100">
          <div class="card-body">
            <i class="bi bi-journal-text text-primary mb-3"></i>
            <h3 class="fw-bold"><?php echo $total_subjects; ?></h3>
            <p class="text-muted">View Subjects</p>
          </div>
        </div>
      </a>
    </div>

    <!-- Faculty -->
    <div class="col-md-3">
      <a href="all_faculty_info.php" class="text-decoration-none">
        <div class="card text-center shadow-sm h-100">
          <div class="card-body">
            <i class="bi bi-people text-warning mb-3"></i>
            <h3 class="fw-bold"><?php echo $total_faculty; ?></h3>
            <p class="text-muted">View Faculty</p>
          </div>
        </div>
      </a>
    </div>

    <!-- Students -->
    <div class="col-md-3">
      <a href="all_students_info.php" class="text-decoration-none">
        <div class="card text-center shadow-sm h-100">
          <div class="card-body">
            <i class="bi bi-mortarboard text-success mb-3"></i>
            <h3 class="fw-bold"><?php echo $total_students; ?></h3>
            <p class="text-muted">View Students</p>
          </div>
        </div>
      </a>
    </div>

    <!-- Register Subject
    <div class="col-md-3">
      <a href="subject_register.php" class="text-decoration-none">
        <div class="card text-center shadow-sm h-100">
          <div class="card-body">
            <i class="bi bi-plus-circle text-success mb-3"></i>
            <h3 class="fw-bold">Register</h3>
            <p class="text-muted">Subject</p>
          </div>
        </div>
      </a>
    </div> -->
    <!-- Register -->
    <div class="col-md-3">
      <a href="register.php" class="text-decoration-none">
        <div class="card text-center shadow-sm h-100">
          <div class="card-body">
            <i class="bi bi-plus-circle text-success mb-3"></i>
            <h3 class="fw-bold">Register</h3>
            <p class="text-muted">Faculty/Students/Subjects </p>
          </div>
        </div>
      </a>
    </div>
    <!-- Modify Subject -->
    <div class="col-md-3">
      <a href="modify_registered_subject.php" class="text-decoration-none">
        <div class="card text-center shadow-sm h-100">
          <div class="card-body">
            <i class="bi bi-pencil-square text-info mb-3"></i>
            <h3 class="fw-bold">Modify</h3>
            <p class="text-muted">Subjects</p>
          </div>
        </div>
      </a>
    </div>

    <!-- Faculty Allotment -->
    <div class="col-md-3">
      <a href="faculty_allotment_history.php" class="text-decoration-none">
        <div class="card text-center shadow-sm h-100">
          <div class="card-body">
            <i class="bi bi-person-lines-fill text-secondary mb-3"></i>
            <h3 class="fw-bold">Faculty Allotment</h3>
            <p class="text-muted">History</p>
          </div>
        </div>
      </a>
    </div>

    <!-- Download Attendance -->
    <div class="col-md-3">
      <a href="download_attendance_excel.php" class="text-decoration-none">
        <div class="card text-center shadow-sm h-100">
          <div class="card-body">
            <i class="bi bi-file-earmark-excel text-success mb-3"></i>
            <h3 class="fw-bold">Download</h3>
            <p class="text-muted">Attendance Sheet</p>
          </div>
        </div>
      </a>
    </div>

  </div><br>
  <footer>
  &copy; <?= date('Y') ?> Rajiv Gandhi University of Knowledge Technologies Nuzvid. All rights reserved.
</footer>

</div>

<script>
  function hi(){
    return confirm("Logging out! Are you sure?");
  }
  function confirmExamSelection(){
    var select = document.getElementById('selected_exam');
    if(!select) return true;
    var exam = select.value;
    var dept = <?php echo json_encode($dept); ?>;
    if(!exam){
      return confirm('You are about to clear the selected exam for department "' + dept + '". Confirm?');
    }
    return confirm('Set exam "' + exam + '" as the active exam for department "' + dept + '"?');
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

