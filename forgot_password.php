<?php
session_start();
include 'db_connect.php';
$step = 1;  
$security_question = "";
$role = "";
$userID = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['check_user'])) {
        $role = $_POST['role'];
        $userID = trim($_POST['userID']);

        if ($role == 'faculty') {
            $sql = "SELECT security_question FROM userfaculty WHERE facultyID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $userID);
        } else if ($role == 'student') {
            $sql = "SELECT security_question FROM userstudent WHERE studentID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $userID);
        } else {
            // For admin_roles -> dept_office / hod / dean
            $sql = "SELECT security_question FROM admin_roles WHERE username=? AND role=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $userID, $role);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $security_question = $row['security_question'];
            $step = 2;
        } else {
            echo "<script>alert('User not found!');</script>";
        }
        $stmt->close();
    }
    else if (isset($_POST['reset_pass'])) {
        // Handle password reset: verify answer then update password
        $role = isset($_POST['role']) ? $_POST['role'] : '';
        $userID = isset($_POST['userID']) ? trim($_POST['userID']) : '';
        $answer = isset($_POST['answer']) ? strtolower(trim($_POST['answer'])) : '';
        $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';

        // Attempt to fetch security question so we can redisplay step 2 on errors
        if ($role == 'faculty') {
            $q = "SELECT security_question FROM userfaculty WHERE facultyID=?";
            $qst = $conn->prepare($q);
            $qst->bind_param("s", $userID);
        } else if ($role == 'student') {
            $q = "SELECT security_question FROM userstudent WHERE studentID=?";
            $qst = $conn->prepare($q);
            $qst->bind_param("s", $userID);
        } else {
            $q = "SELECT security_question FROM admin_roles WHERE username=? AND role=?";
            $qst = $conn->prepare($q);
            $qst->bind_param("ss", $userID, $role);
        }
        if (isset($qst)) {
            $qst->execute();
            $qr = $qst->get_result();
            if ($qr && $qr->num_rows == 1) {
                $rrow = $qr->fetch_assoc();
                $security_question = $rrow['security_question'];
                $step = 2;
            }
            $qst->close();
        }

        // basic validations
        if ($userID == '' || $answer == '' || $new_password == '') {
            $error_message = 'Please fill all fields.';
            $step = 2;
        }

        if (!isset($error_message) && strlen($new_password) < 5) {
            $error_message = 'Password must be at least 5 characters long.';
            $step = 2;
        }

        // Find stored answer
        if ($role == 'faculty') {
            $sql = "SELECT security_answer FROM userfaculty WHERE facultyID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $userID);
        } else if ($role == 'student') {
            $sql = "SELECT security_answer FROM userstudent WHERE studentID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $userID);
        } else {
            // admin roles (dept_office / hod / dean)
            $sql = "SELECT security_answer FROM admin_roles WHERE username=? AND role=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $userID, $role);
        }

        // only continue if no earlier validation error
        if (!isset($error_message)) {
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $stored_answer = isset($row['security_answer']) ? strtolower(trim($row['security_answer'])) : '';
                if ($stored_answer !== '' && $stored_answer === $answer) {
                // Update password (note: project stores plaintext passwords elsewhere)
                if ($role == 'faculty') {
                    $upd = $conn->prepare("UPDATE userfaculty SET password=? WHERE facultyID=?");
                    $upd->bind_param("ss", $new_password, $userID);
                } else if ($role == 'student') {
                    $upd = $conn->prepare("UPDATE userstudent SET password=? WHERE studentID=?");
                    $upd->bind_param("ss", $new_password, $userID);
                } else {
                    $upd = $conn->prepare("UPDATE admin_roles SET password=? WHERE username=? AND role=?");
                    $upd->bind_param("sss", $new_password, $userID, $role);
                }
                    if ($upd->execute()) {
                        echo "<script>alert('Password reset successful. You can now login with your new password.');window.location.href='index.php';</script>";
                        $upd->close();
                        $stmt->close();
                        exit;
                    } else {
                        $error_message = 'Failed to update password. Please try again later.';
                        $step = 2;
                        $upd->close();
                        $stmt->close();
                    }

                } else {
                    $error_message = 'Security answer is incorrect.';
                    $step = 2;
                    $stmt->close();
                }
            } else {
                $error_message = 'User not found.';
                $step = 1; // send back to first step if user not found
                if ($stmt) $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root{ --primary:#002147; --muted:#f4f6f9; --card-radius:14px; }
    html,body{ height:100%; margin:0; padding:0; font-family:'Roboto',system-ui,-apple-system,'Segoe UI',Roboto,'Helvetica Neue',Arial; background:var(--muted); color:#222; }
    .page-root{ display:flex; flex-direction:column; min-height:100vh; }
    header.portal-header{ padding:0.6rem 0; }
    .portal-brand{ display:flex; align-items:center; gap:1rem; }
    .portal-brand img{ height:72px; width:auto; border-radius:8px; }
    .portal-text h1{ margin:0; font-size:1.125rem; font-weight:700; color:var(--primary); }
        .portal-text h2{ margin:0; font-size:0.95rem; font-weight:500; color:#333; }
    main{ flex:1 0 auto; display:flex; align-items:center; justify-content:center; padding:1.25rem; }
    .auth-card{ width:100%; max-width:520px; background:#fff; padding:1.5rem; border-radius:var(--card-radius); box-shadow:0 6px 22px rgba(2,17,48,0.08); }
    .muted-small{ color:#6c757d; font-size:0.9rem; }
    footer{ background:var(--primary); color:#fff; text-align:center; padding:0.8rem 0; font-size:0.9rem; }
    @media (max-width:576px){ .portal-brand img{ height:56px; } .auth-card{ padding:1rem; } }
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
            <div class="d-none d-md-block text-end"><span class="muted-small">Efficient • Reliable • Academic Excellence</span></div>
        </div>
    </header>

    <main>
        <div class="auth-card">
            <h2 class="text-center mb-3">Forgot Password</h2>
            <?php if (isset($error_message) && $error_message != '') { ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($error_message); ?></div>
            <?php } ?>

            <?php if ($step == 1) { ?>
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

                    <button type="submit" name="check_user" class="btn btn-primary w-100">Next</button>
                    <div class="text-center mt-3"><a href="index.php">Login</a></div>
                </form>

            <?php } elseif ($step == 2) { ?>
                <form method="POST" action="">
                    <input type="hidden" name="role" value="<?php echo $role; ?>">
                    <input type="hidden" name="userID" value="<?php echo $userID; ?>">
                    <input type="hidden" name="dept" value="<?php echo isset($_POST['dept']) ? $_POST['dept'] : ''; ?>">

                    <div class="mb-3">
                        <label class="form-label">Security Question</label>
                        <input type="text" class="form-control" value="<?php echo $security_question; ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Answer (one word)</label>
                        <input type="text" name="answer" class="form-control" required>
                    </div>
                    <div class="mb-3 position-relative">
                        <label class="form-label">New Password</label>
                        <div class="input-group">
                            <input id="newPassword" type="password" name="new_password" class="form-control" required>
                            <button id="toggleNewPwd" type="button" class="btn btn-outline-secondary" tabindex="-1"><i class="fa fa-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" name="reset_pass" class="btn btn-primary w-100">Reset Password</button>
                    <div class="text-center mt-3"><a href="index.php">Login</a></div>
                </form>
            <?php } ?>

        </div>
    </main>

    <footer>
        &copy; <?= date('Y') ?> Rajiv Gandhi University of Knowledge Technologies Nuzvid. All rights reserved.
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showDept(role) {
    var el = document.getElementById('deptDiv');
    if(role === 'dept_office' || role === 'hod') el.style.display = 'block'; else el.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function(){
    var newPwd = document.getElementById('newPassword');
    var toggle = document.getElementById('toggleNewPwd');
    if(toggle && newPwd){
        toggle.addEventListener('click', function(e){ e.preventDefault(); if(newPwd.type==='password'){ newPwd.type='text'; toggle.innerHTML='<i class="fa fa-eye-slash"></i>'; } else { newPwd.type='password'; toggle.innerHTML='<i class="fa fa-eye"></i>'; } });
    }
});
</script>
</body>
</html>
