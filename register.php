<?php
session_start();

// autoload PhpSpreadsheet (always at the top)
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// Check if user is logged in
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office'){
    die("Access Denied. Only department office users can register faculty.");
}

include 'db_connect.php';

// Map department codes to friendly names
$dept_names = [
    'CSE' => 'Computer Science & Engineering',
    'ECE' => 'Electronics & Communication Engineering',
    'MECH' => 'Mechanical Engineering',
    'EEE' => 'Electrical & Electronics Engineering',
    'CIVIL' => 'Civil Engineering',
    'MME' => 'Metallurgical & Material Science Engineering',
    'CHEMICAL' => 'Chemical Engineering'
];

$userID = $_SESSION['userID'];
// Get department of logged-in user
$stmt = $conn->prepare("SELECT dept FROM admin_roles WHERE username= ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$stmt->bind_result($dept);
$stmt->fetch();
$stmt->close();

$role = isset($_GET['role']) ? $_GET['role'] : '';  // role selection

// Handle Faculty Excel Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_faculty_excel'])) {
    if (isset($_FILES['faculty_excel']) && $_FILES['faculty_excel']['error'] == 0) {
        $filePath = $_FILES['faculty_excel']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $inserted = 0;
            $updated  = 0;
            $skipped  = 0;

            // Skip header (row 0)
            for ($i = 1; $i < count($rows); $i++) {
                $facultyID   = trim($rows[$i][1]);
                $facultyName = trim($rows[$i][2]);
                $password    = trim($rows[$i][3]);
                $contact     = trim($rows[$i][4]);
                $deptExcel   = isset($dept_names[$dept]) ? $dept_names[$dept] : $dept;
                $question    = trim($rows[$i][6]);
                $answer      = strtolower(trim($rows[$i][7]));

                if (!empty($facultyID)) {
                    $sql = "INSERT INTO userfaculty 
                            (facultyID, facultyName, password, contact, dept, security_question, security_answer) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE 
                                facultyName=VALUES(facultyName),
                                password=VALUES(password),
                                contact=VALUES(contact),
                                dept=VALUES(dept),
                                security_question=VALUES(security_question),
                                security_answer=VALUES(security_answer)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssssss", $facultyID, $facultyName, $password, $contact, $deptExcel, $question, $answer);
                    $stmt->execute();

                    if ($stmt->affected_rows == 1) {
                        $inserted++;
                    } elseif ($stmt->affected_rows == 2) {
                        $updated++;
                    } else {
                        $skipped++;
                    }

                    $stmt->close();
                } else {
                    $skipped++;
                }
            }
            echo "<script>
                    alert('Faculty Upload Completed! Inserted: $inserted | Updated: $updated | Skipped: $skipped');
                    window.location.href='dept_office_dashboard.php';
                  </script>";
            exit;
        } catch (Exception $e) {
            echo "<script>alert('Error Reading Excel: " . $e->getMessage() . "');</script>";
        }
    } else {
        echo "<script>alert('Please upload a valid Excel file!');</script>";
    }
}

// Handle Student Excel Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_student_excel'])) {
    if (isset($_FILES['student_excel']) && $_FILES['student_excel']['error'] == 0) {
        $filePath = $_FILES['student_excel']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $inserted = 0;
            $updated  = 0;
            $skipped  = 0;

            // Skip header (row 0)
            for ($i = 1; $i < count($rows); $i++) {
                $studentID   = trim($rows[$i][1]);
                $studentName = trim($rows[$i][2]);
                $password    = trim($rows[$i][3]);
                $contact     = trim($rows[$i][4]);
                $deptExcel   = isset($dept_names[$dept]) ? $dept_names[$dept] : $dept;
                $question    = trim($rows[$i][5]);
                $answer      = strtolower(trim($rows[$i][6]));
                $section     = trim($rows[$i][7]);
                $year        = trim($rows[$i][8]);
                $academicYear= trim($rows[$i][9]);
                $semester=trim($rows[$i][10]);

                if (!empty($studentID)) {
                    $sql = "INSERT INTO userstudent 
                            (studentID, studentName, password, contact, dept, security_question, security_answer, section, year, academic_year,semester) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)
                            ON DUPLICATE KEY UPDATE 
                                studentName=VALUES(studentName),
                                password=VALUES(password),
                                contact=VALUES(contact),
                                dept=VALUES(dept),
                                security_question=VALUES(security_question),
                                security_answer=VALUES(security_answer),
                                section=VALUES(section),
                                year=VALUES(year),
                                academic_year=VALUES(academic_year)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssssssssi", $studentID, $studentName, $password, $contact, $deptExcel, $question, $answer, $section, $year, $academicYear,$semester);
                    $stmt->execute();

                    if ($stmt->affected_rows == 1) {
                        $inserted++;
                    } elseif ($stmt->affected_rows == 2) {
                        $updated++;
                    } else {
                        $skipped++;
                    }

                    $stmt->close();
                } else {
                    $skipped++;
                }
            }
            echo "<script>
                    alert('Student Upload Completed! Inserted: $inserted | Updated: $updated | Skipped: $skipped');
                    window.location.href='dept_office_dashboard.php';
                  </script>";
            exit;
        } catch (Exception $e) {
            echo "<script>alert('Error Reading Excel: " . $e->getMessage() . "');</script>";
        }
    } else {
        echo "<script>alert('Please upload a valid Excel file!');</script>";
    }
}

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
         h1{
        margin-left: 100px;
    }
        </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-3">
   <div class="d-flex align-items-center justify-content-between mb-5">
    <h1 class="text-center flex-grow-1">
        Department of <?php echo htmlspecialchars($dept); ?>
    </h1>
    <a href="dept_office_dashboard.php" class="btn btn-primary ms-3">
        Dashboard
    </a>
</div>

    <?php if ($role == '') { ?>
          
        <!-- Show options first -->
        <div class="card shadow p-4 mx-auto" style="max-width: 400px;">
          
            <h2 class="text-center mb-4">Register</h2>
            <div class="d-grid gap-3">
                <a href="register.php?role=faculty" class="btn btn-primary">Register Faculty</a>
                <a href="register.php?role=student" class="btn btn-success">Register Students </a>
            </div>
        </div>
    <?php } ?>


    <?php if ($role == 'faculty') { ?>
        <!-- Faculty Excel Upload -->
        <div class="card shadow p-4 mx-auto mt-4" style="max-width: 500px;">
            <h2 class="mb-4 text-center">Faculty Excel Upload</h2>
            <form method="POST" enctype="multipart/form-data" action="">
                <input type="hidden" name="upload_faculty_excel" value="1">
                <div class="mb-3">
                   
                    <label class="form-label">Upload Excel File</label>
                    <input type="file" name="faculty_excel" class="form-control" accept=".xlsx,.xls" required>
                     s.no|facultyID|facultyName|password|contact|dept|security_question|security_answer|<br>
                </div>
                <button type="submit" class="btn btn-success w-100">Upload Faculty Data</button>
            </form>
        </div>
    <?php } ?>

   <?php if ($role == 'student') { ?>
    <!-- Student Excel Upload -->
    <div class="card shadow p-4 mx-auto mt-4" style="max-width: 500px;">
        <h2 class="mb-4 text-center">Student Excel Upload</h2>
        <form method="POST" enctype="multipart/form-data" action="">
            <input type="hidden" name="upload_student_excel" value="1">
            <div class="mb-3">
                <label class="form-label">Upload Excel File</label>
                <input type="file" name="student_excel" class="form-control" accept=".xlsx,.xls" required>
                s.no|studentID|studentName|password|contact|dept|security_question|security_answer|section|year|academic_year|<br>
            </div>
            <button type="submit" class="btn btn-success w-100">Upload Student Data</button>
        </form>
    </div>
<?php } ?>


</div>

</body>
</html>
