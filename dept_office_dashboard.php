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
   .container{
    text-align: center;
   }
   .btn{
    float: right;
   }
   </style>
    
    
</head>
<body class="bg-light">
<br><div>
        <a href='index.php' class="btn btn-primary" onclick="return hi()">Logout</a>
        <script>
            function hi(){
                if(confirm("Logging out! Are you sure?")){
                 window.location.href = "index.php";
                }else{
                    return false;
                }
            }
            </script>
    </div>
<div class="container">
    <!-- Header -->
<div class="header">
    <div>
        <h1 class="fw-bold">Department Office Dashboard</h1>
        <p class="text-muted">Department: <strong><?php echo htmlspecialchars($dept); ?></strong></p>
    </div>
    
</div>

    

    <div class="row g-4 justify-content-center">

    <!-- Total Subjects -->
    <div class="col-md-3">
        <div class="card text-center shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-journal-text fs-1 text-primary mb-3"></i>
                <h3 class="card-title fw-bold"><?php echo $total_subjects; ?></h3>
                <p class="card-text text-muted">Total Subjects</p>
            </div>
        </div>
    </div>

    <!-- Total Faculty -->
    <div class="col-md-3">
        <div class="card text-center shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-people fs-1 text-warning mb-3"></i>
                <h3 class="card-title fw-bold"><?php echo $total_faculty; ?></h3>
                <p class="card-text text-muted">Total Faculty</p>
            </div>
        </div>
    </div>

    <!-- Total Students -->
    <div class="col-md-3">
        <a href="all_students_info.php" class="text-decoration-none">
        <div class="card text-center shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-mortarboard fs-1 text-success mb-3"></i>
                <h3 class="card-title fw-bold"><?php echo $total_students; ?></h3>
                <p class="card-text text-muted">View the students</p>
            </div>
        </div>
    </div>
   
    <!-- Register Subject -->
    <div class="col-md-3">
        <a href="subject_register.php" class="text-decoration-none">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <i class="bi bi-plus-circle fs-1 text-success mb-3"></i>
                    <h3 class="card-title fw-bold">Register</h3>
                    <p class="card-text text-muted">Subject</p>
                </div>
            </div>
        </a>
    </div>
   
   <!-- Modify the registered Subject -->
    <div class="col-md-3">
        <a href="modify_registered_subject.php" class="text-decoration-none">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <i class="bi bi-plus-circle fs-1 text-success mb-3"></i>
                    <h3 class="card-title fw-bold">Modify</h3>
                    <p class="card-text text-muted">The Subject</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="register.php" class="text-decoration-none">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <i class="bi bi-plus-circle fs-1 text-success mb-3"></i>
                    <h3 class="card-title fw-bold">Register</h3>
                    <p class="card-text text-muted">Students/Faculty</p>
                </div>
            </div>
        </a>
    </div>
    
    <!-- Manage Faculty -->
    <div class="col-md-3">
        <a href="faculty_allotment_history.php" class="text-decoration-none">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <i class="bi bi-person-lines-fill fs-1 text-secondary mb-3"></i>
                    <h3 class="card-title fw-bold">Faculty Allotment</h3>
                    <p class="card-text text-muted"> History</p>
                </div>
            </div>
        </a>
    </div>

     <!-- Manage Attendance -->
    <div class="col-md-3">
        <a href="manage_attendance.php" class="text-decoration-none">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <i class="bi bi-person-lines-fill fs-1 text-secondary mb-3"></i>
                    <h3 class="card-title fw-bold">Manage </h3>
                    <p class="card-text text-muted">No.of Working ays</p>
                </div>
            </div>
        </a>
    </div>
  
    

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
