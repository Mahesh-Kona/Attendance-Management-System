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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4 mx-auto" style="max-width: 500px;">
        <h2 class="text-center mb-4">Forgot Password</h2>

        <?php if ($step == 1) { ?>
            <!-- Step 1: Enter Role and User ID -->
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

                

                <div class="mb-3">
                    <label class="form-label">User ID</label>
                    <input type="text" name="userID" class="form-control" required>
                </div>
                <button type="submit" name="check_user" class="btn btn-primary w-100">Next</button><br><br>
                <center>
                <a href="index.php" class="link">Login</a></center>
        </form>

        <?php } elseif ($step == 2) { ?>
            <!-- Step 2: Show security question and ask for answer + new password -->
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
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <button type="submit" name="reset_pass" class="btn btn-primary w-100">Reset Password</button><br><br>
                <center>
                <a href="index.php" class="link">Login</a></center>
            </form>
        <?php } ?>

    </div>
</div>

</body>
</html>
