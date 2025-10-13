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
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<!-- Optional icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
        :root{
            --primary:#002147;
            --muted:#f4f6f9;
            --card-radius:14px;
        }
        html, body {
                height: 100%;
                margin: 0;
                padding: 0;
                font-family: 'Roboto', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
                background-color: var(--muted);
                color: #222;
        }
        /* Layout: header, main, footer */
        .page-root{ display:flex; flex-direction:column; min-height:100vh; }
        main{ flex:1 0 auto; display:flex; align-items:center; justify-content:center; padding:1.25rem; }

        /* Header */
        .portal-header{ background:transparent; padding:0.6rem 0; }
        .portal-brand{ display:flex; align-items:center; gap:1rem; }
        .portal-brand img{ height:72px; width:auto; border-radius:8px; }
        .portal-text h1{ margin:0; font-size:1.125rem; font-weight:700; color:var(--primary); }
        .portal-text h2{ margin:0; font-size:0.95rem; font-weight:500; color:#333; }

        /* Card */
        .auth-card{ width:100%; max-width:420px; border-radius:var(--card-radius); box-shadow:0 6px 22px rgba(2,17,48,0.08); background:#fff; padding:1.5rem; }
        .auth-card .btn{ border-radius:10px; }

        /* Footer */
        footer{ background:var(--primary); color:#fff; text-align:center; padding:0.8rem 0; font-size:0.9rem; }

        /* Small helpers */
        .muted-small{ color:#6c757d; font-size:0.9rem; }

        /* Responsive tweaks */
        @media (max-width:576px){
            .portal-brand img{ height:56px; }
            .portal-text h1{ font-size:1rem; }
            .portal-text h2{ font-size:0.85rem; }
            main{ padding:0.75rem; }
            .auth-card{ padding:1rem; }
        }
        @media (min-width:992px){
            .portal-text h1{ font-size:1.35rem; }
            .portal-text h2{ font-size:1.05rem; }
        }
        
    </style>
</head>
<body>
<div class="page-root">
    <header class="portal-header container">
        <div class="d-flex align-items-center justify-content-between py-2">
            <div class="portal-brand">
                <img src="rgukt.jpg" alt="RGUKT Logo" onerror="this.style.display='none'">
                <div class="portal-text">
                    <h1>Rajiv Gandhi University of Knowledge Technologies Nuzvid</h1>
                    <h2 class="muted-small">Attendance Management System</h2>
                </div>
            </div>
            <div class="d-none d-md-block text-end">
                <span class="muted-small">Efficient • Reliable • Academic Excellence</span>
            </div>
        </div>
    </header>

    <main>
        <div class="auth-card">
            <h2 class="text-center mb-3">Sign in</h2>
            <?php if($login_msg) echo "<div class='alert alert-warning'>$login_msg</div>"; ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Select Role</label>
                    <select name="role" id="roleSelect" class="form-select" required onchange="showDept(this.value)">
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
                    <select name="dept" class="form-select">
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

                <div class="mb-3 position-relative">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input id="passwordInput" type="password" name="password" class="form-control" required>
                        <button id="togglePwd" type="button" class="btn btn-outline-secondary" tabindex="-1"><i class="fa fa-eye"></i></button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>

            <div class="text-center mt-3">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>
        </div>
    </main>

    <footer>
        &copy; <?= date('Y') ?> Rajiv Gandhi University of Knowledge Technologies Nuzvid. All rights reserved.
    </footer>
</div>

<!-- Bootstrap JS bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showDept(role) {
        var el = document.getElementById('deptDiv');
        if(role === 'dept_office' || role === 'hod') el.style.display = 'block'; else el.style.display = 'none';
}

// Password toggle
document.addEventListener('DOMContentLoaded', function(){
    var pwd = document.getElementById('passwordInput');
    var btn = document.getElementById('togglePwd');
    if(btn && pwd){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            if(pwd.type === 'password'){ pwd.type = 'text'; btn.innerHTML = '<i class="fa fa-eye-slash"></i>'; }
            else{ pwd.type = 'password'; btn.innerHTML = '<i class="fa fa-eye"></i>'; }
        });
    }
});
</script>
</body>
</html>
