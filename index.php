<?php
session_start();
include("db_connect.php"); 

$login_msg = ""; // message to show after login attempt

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $userID = trim($_POST['userID']);
    $password = trim($_POST['password']);
    $dept = isset($_POST['dept']) ? trim($_POST['dept']) : null;

    $stmt = null;

    if ($role == 'faculty') {
        $sql = "SELECT password FROM userfaculty WHERE facultyID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $userID);

    } else if ($role == 'student') {
        $sql = "SELECT password FROM userstudent WHERE studentID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $userID);

    } else if ($role == 'dept_office') {
        if (!$dept) { $login_msg = "Please select a department"; }
        $sql = "SELECT password FROM admin_roles WHERE role='dept_office' AND dept=? AND username=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $dept, $userID);

    } else if ($role == 'hod') {
        if (!$dept) { $login_msg = "Please select a department"; }
        $sql = "SELECT password FROM admin_roles WHERE role='hod' AND dept=? AND username=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $dept, $userID);

    } else if ($role == 'dean') {
        $sql = "SELECT password FROM admin_roles WHERE role='dean' AND username=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $userID);

    } else {
        $login_msg = "Invalid role selected";
    }

    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $db_password = $row['password'];

            if ($password === $db_password) { 
                $_SESSION['userID'] = $userID;
                $_SESSION['role'] = $role;
                $_SESSION['dept'] = $dept ?? null;

                // Redirect based on role
                if ($role === 'faculty') {
                    $redirect = 'faculty_dashboard.php';
                } else if ($role === 'student') {
                    $redirect = 'student_dashboard.php';
                } else if ($role === 'dept_office') {
                    $redirect = 'dept_office_dashboard.php';
                } else if ($role === 'hod') {
                    $redirect = 'hod_dashboard.php';
                }
                else if ($role === 'dean') {
                    $redirect = 'dean_dashboard.php';
                } else {
                    $redirect = 'index.php';
                }

                echo "<script> window.location.href='$redirect';</script>";
                exit();
            } else {
                $login_msg = "Incorrect password!";
            }

        } else {
            $login_msg = "User not found!";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
      background-color: #f4f6f9;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .portal-header {
      background: #002147;
      color: white;
      padding: 20px 0;
      text-align: center;
      margin-bottom: 40px;
    }
    .portal-header h1 {
      margin: 0;
      font-size: 2.2rem;
      font-weight: bold;
      letter-spacing: 1px;
    }
    .card {
      border-radius: 15px;
      box-shadow: 0px 4px 12px rgba(0,0,0,0.1);
      transition: transform 0.2s;
    }
    .card:hover {
      transform: translateY(-5px);
    }
    .list-group-item {
      font-size: 1.1rem;
      font-weight: 500;
    }
    .list-group-item a {
      text-decoration: none;
      color: #002147;
    }
    .list-group-item a:hover {
      color: #0056b3;
    }
    footer {
      background: #002147;
      color: #fff;
      text-align: center;
      padding: 15px 0;
      margin-top: 50px;
      font-size: 0.9rem;
    }
  </style>
</head>
<body class="bg-light">
 
  <div class="portal-header">
    <h1>Rajiv Gandhi University of Knowledge Technologies Nuzvid</h1>
    <h2>Attendance Management System</h2>
    <p class="mb-0">Efficient | Reliable | Academic Excellence</p>
  </div>
<div class="container mt-5">
    <div class="card shadow p-4 mx-auto" style="max-width: 400px">
        <h2 class="text-center mb-4">Login</h2>
        <?php if($login_msg) echo "<div class='alert alert-warning'>$login_msg</div>"; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Select Role</label>
                <select name="role" id="roleSelect" class="form-control" required onchange="showDept(this.value)">
                    <option value="">--Select Role--</option>
                    <option value="faculty">Faculty</option>
                    <option value="student">Student</option>
                    <option value="dept_office">Department Office</option>
                    <option value="hod">HOD</option>
                    <option value="dean">Dean of Academics</option>
                </select>
            </div>

            <div class="mb-3" id="deptDiv" style="display:none;">
                <label class="form-label">Select Department</label>
                <select name="dept" class="form-control">
                    <option value="">--Select Department--</option>
                    <option value="Computer Science & Engineering">CSE</option>
                    <option value="Electronics & Communication Engineering">ECE</option>
                    <option value="Electrical & Electronics Engineering">EEE</option>
                    <option value="Mechanical Engineering">MECH</option>
                    <option value="Civil Engineering">CIVIL</option>
                    <option value="Chemical Engineering">CHEMICAL</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">User ID</label>
                <input type="text" name="userID" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <p class="text-center mt-3">
           
            <a href="forgot_password.php">Forgot Password?</a>
        </p>
    </div>
</div>

<script>
function showDept(role) {
    if(role === 'dept_office' || role === 'hod') {
        document.getElementById('deptDiv').style.display = 'block';
    } else {
        document.getElementById('deptDiv').style.display = 'none';
    }
}
</script>

</body>
</html>
