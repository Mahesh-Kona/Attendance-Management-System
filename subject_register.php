<?php
session_start();
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office'){
    die("Access Denied. This action is only allowed for Department Office users.");
}

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

$dept_code = $_SESSION['dept'];
$dept_full = $dept_names[$dept_code] ?? $dept_code;

$host = "localhost";
$user = "root";
$pass = "";
$db   = "attendance_management_system";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error) die("Connection failed: " . $conn->connect_error);

require 'vendor/autoload.php'; // PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;

$success = "";
$error = "";

// Handle Excel upload
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['excel_file'])){
    $file_mime = mime_content_type($_FILES['excel_file']['tmp_name']);
    $allowed_mimes = [
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    if(in_array($file_mime, $allowed_mimes)){
        $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Skip header row (assuming first row is column names)
        array_shift($rows);

        $inserted = 0;
        $failed = 0;

        foreach($rows as $row){
            list($s_no,$dept, $subject_code, $subject_name, $credits, $academic_year, $semester, $date_time, $faculty_name, $faculty_id, $year, $no_of_working_days,$section) = $row;

            if(empty($subject_code)) continue;

            $sql = "INSERT INTO subjects 
                (dept, subject_code, subject_name, credits, academic_year, semester, date_time, faculty_name, faculty_id, year, no_of_working_days,section) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssissssisii", 
                $dept, $subject_code, $subject_name, $credits, 
                $academic_year, $semester, $date_time, $faculty_name, 
                $faculty_id, $year, $no_of_working_days,$section
            );

            if($stmt->execute()){
                $inserted++;
            } else {
                $failed++;
            }
            $stmt->close();
        }

        $success = "Upload completed. Inserted: $inserted | Failed: $failed";
    } else {
        $error = "Invalid file type. Please upload an Excel file (.xls or .xlsx).";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dept Office - Upload Subjects Excel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #f8f9fa; }
    .card { border-radius: 15px; }
</style>
</head>
<body>
<div class="card shadow-lg p-4 mx-auto mt-5" style="max-width: 600px;">
    <h2 class="text-center mb-3">Upload Subjects Excel</h2>
    <p class="text-center text-muted">Department: <strong><?php echo htmlspecialchars($dept_full); ?></strong></p>

    <?php if($success) echo "<div class='alert alert-success text-center'>$success</div>"; ?>
    <?php if($error) echo "<div class='alert alert-danger text-center'>$error</div>"; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Choose Excel File</label>
            <input type="file" name="excel_file" class="form-control" accept=".xls,.xlsx" required>
        </div>
        <button type="submit" class="btn btn-success w-100">Upload</button>
    </form>

    <div class="text-center mt-3">
        <a href="dept_office_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>
</body>
</html>
