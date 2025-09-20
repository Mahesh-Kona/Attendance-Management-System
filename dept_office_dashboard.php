<?php
session_start();
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office'){
    die("Access Denied. This page is only for Department Office users.");
}

$userID = $_SESSION['userID'];

$host = "localhost";
$user = "root";
$pass = "";
$db   = "attendance_management_system";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get department of logged-in user
$stmt = $conn->prepare("SELECT dept FROM admin_roles WHERE username= ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$stmt->bind_result($dept);
$stmt->fetch();
$stmt->close();

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

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dept Office Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .header { text-align: center; margin: 30px 0; }
    .header h1 { font-weight: bold; color: #333; }
    .header p { font-size: 1.1rem; color: #666; }
    .logout-btn { position: absolute; top: 20px; right: 20px; }
    .card { 
      border-radius: 15px; 
      transition: transform 0.2s, box-shadow 0.2s; 
    }
    .card:hover { 
      transform: translateY(-5px); 
      box-shadow: 0 4px 15px rgba(0,0,0,0.15); 
    }
    .card-body i { font-size: 2.5rem; }
  </style>
</head>
<body>
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
    <div class="d-flex align-items-center justify-content-center position-relative mb-4">
        <div class="text-center">
            ` <h1>Department Office Dashboard</h1>
            <p class="text-muted">
      Department: <strong><?php echo htmlspecialchars($dept); ?></strong>
    </p>
        </div>
        <a href="index.php" class="btn btn-primary position-absolute end-0" onclick=hi()>
            Logout
        </a>
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

    <!-- Register Subject -->
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

    <!-- Register People -->
    <div class="col-md-3">
      <a href="register.php" class="text-decoration-none">
        <div class="card text-center shadow-sm h-100">
          <div class="card-body">
            <i class="bi bi-person-plus text-dark mb-3"></i>
            <h3 class="fw-bold">Register</h3>
            <p class="text-muted">Faculty / Students </p>
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
            <p class="text-muted">Attendance Excel</p>
          </div>
        </div>
      </a>
    </div>

  </div>
</div>

<script>
  function hi(){
    return confirm("Logging out! Are you sure?");
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

