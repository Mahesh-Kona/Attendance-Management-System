<?php
session_start();

// autoload PhpSpreadsheet
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// Check login
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office'){
    die("Access Denied. Only department office users can upload subjects.");
}

include 'db_connect.php';

// Map dept names
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
// Get dept of logged in user
$stmt = $conn->prepare("SELECT dept FROM admin_roles WHERE username = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$stmt->bind_result($dept);
$stmt->fetch();
$stmt->close();

$dept_full = $dept_names[$dept] ?? $dept;

// Handle Subjects Excel Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_subject_excel'])) {
    if (isset($_FILES['subject_excel']) && $_FILES['subject_excel']['error'] == 0) {
        $filePath = $_FILES['subject_excel']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $inserted = 0;
            $updated  = 0;
            $skipped  = 0;

            // SQL query with ON DUPLICATE KEY UPDATE
            $sql = "INSERT INTO subjects 
                        (dept, subject_code, subject_name, credits, academic_year, semester, date_time, faculty_name, faculty_id, year, section) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        subject_name = VALUES(subject_name),
                        credits = VALUES(credits),
                        academic_year = VALUES(academic_year),
                        semester = VALUES(semester),
                        date_time = VALUES(date_time),
                        faculty_name = VALUES(faculty_name),
                        year = VALUES(year),
                        section = VALUES(section)";

            $stmt = $conn->prepare($sql);

            // Skip header row
            for ($i = 1; $i < count($rows); $i++) {
                list($s_no, $deptExcel, $subject_code, $subject_name, $credits, 
                     $academic_year, $semester, $date_time, $faculty_name, 
                     $faculty_id, $year, $section) = $rows[$i];

                // Trim all inputs
                $subject_code = trim($subject_code);
                $faculty_id   = trim($faculty_id);
                $section      = trim($section);

                if (empty($subject_code) || empty($faculty_id)) { 
                    $skipped++; 
                    continue; 
                }

                // Always force dept from session
                $deptExcel = $dept;

                // Bind values
                $stmt->bind_param(
                    "sssisissssi",   // year treated as string
                    $deptExcel, 
                    $subject_code, 
                    $subject_name, 
                    $credits, 
                    $academic_year, 
                    $semester, 
                    $date_time, 
                    $faculty_name, 
                    $faculty_id, 
                    $year, 
                    $section
                );

                if ($stmt->execute()) {
                    if ($stmt->affected_rows == 1) {
                        $inserted++;
                    } elseif ($stmt->affected_rows == 2) {
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    error_log("MySQL Error: " . $stmt->error);
                    $skipped++;
                }
            }

            $stmt->close();

            echo "<script>
                    alert('Subjects Upload Completed! Inserted: $inserted | Updated: $updated | Skipped: $skipped');
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
  <title>Upload Subjects Excel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .card { border-radius: 15px; }
  </style>
</head>
<body>
<div class="card shadow-lg p-4 mx-auto mt-5" style="max-width: 600px;">
    <h2 class="text-center mb-3">Upload Subjects Excel</h2>
    <p class="text-center text-muted">
        Department: <strong><?php echo htmlspecialchars($dept_full); ?></strong>
    </p>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Choose Excel File</label>
            <input type="file" name="subject_excel" class="form-control" accept=".xls,.xlsx" required>
        </div>
        s_no|dept|subject_code|subject_name|credits|academic_year|semester|date_time|faculty_name|faculty_id|year|section

        <button type="submit" name="upload_subject_excel" class="btn btn-success w-100">
            Upload
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="dept_office_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    </div>
</div>
</body>
</html>
