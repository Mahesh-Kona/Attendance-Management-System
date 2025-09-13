<?php
session_start();
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student'){
    die("Access Denied. Only students can access this page.");
}

$studentID = $_SESSION['userID'];

$host = "localhost";
$user = "root";
$pass = "";
$db   = "attendance_management_system";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch student details including year, dept, section
$student_sql = "SELECT studentID, studentName, year, dept, section, semester 
                FROM userstudent 
                WHERE studentID=?";
$stmt = $conn->prepare($student_sql);
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

$year     = $student['year'];
$dept     = $student['dept'];
$section  = $student['section'];
$semester = $student['semester'];

// Fetch subjects allotted to this student filtered by year, dept, section
// Count distinct sessions (class_date + subject_code + section + faculty)
$subjects_sql = "
SELECT 
    s.subject_name, 
    s.subject_code, 
    s.faculty_name, 
    s.section,
    -- Total lectures = distinct sessions conducted
    (SELECT COUNT(DISTINCT DATE(att.time), HOUR(att.time), MINUTE(att.time)) 
     FROM attendance att 
     WHERE att.subject_code = s.subject_code 
       AND att.year = s.year 
       AND att.section = s.section 
       AND att.dept = s.dept
    ) AS total_lectures,
    -- Days attended = distinct sessions student was present
    (SELECT COUNT(DISTINCT DATE(att2.time), HOUR(att2.time), MINUTE(att2.time)) 
     FROM attendance att2 
     WHERE att2.subject_code = s.subject_code 
       AND att2.year = s.year 
       AND att2.section = s.section 
       AND att2.dept = s.dept
       AND att2.student_id = ? 
       AND att2.status = 'P'
    ) AS days_attended
FROM subjects s
WHERE s.year = ? 
  AND s.dept = ? 
  AND s.section = ?
GROUP BY s.subject_code, s.subject_name, s.faculty_name, s.section
ORDER BY s.subject_name
";

$stmt = $conn->prepare($subjects_sql);
$stmt->bind_param("ssss", $studentID, $year, $dept, $section);
$stmt->execute();
$subjects_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
      body { 
          background-color: #f5f7fa; 
          font-family: "Segoe UI", Arial, sans-serif; 
          padding: 20px; 
      }
      h1, h2, h5 { color: #2c3e50; }
      .header {
          position: relative;
          text-align: center;
          margin-bottom: 30px;
      }
      .header h2 {
          margin: 0;
          font-weight: 600;
      }
      .header a {
          position: absolute;
          right: 0;
          top: 0;
      }
      .card { 
          border-radius: 15px; 
          padding: 30px; 
          box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
          background: #fff; 
      }
      table th, table td { vertical-align: middle; }
      .low-attendance { background-color: #fde2e1 !important; color: #c0392b; }
      .note-text { font-size: 0.9rem; color: #555; }
  </style>
</head>
<body>

  <!-- Header -->
  <div class="header">
    <h1>Attendance Management System</h1>
      <h2>Student Dashboard</h2>
      <a href="index.php" class="btn btn-primary">Logout</a>
  </div>

  <div class="container">
      <!-- Student Details Card -->
      <div class="card mb-4">
          <h5 class="mb-3"><strong>Student Details</strong></h5>
          <div class="row">
              <div class="col-md-6">
                  <p><strong>ID:</strong> <?= htmlspecialchars($student['studentID']); ?></p>
                  <p><strong>Name:</strong> <?= htmlspecialchars($student['studentName']); ?></p>
                  <p><strong>Year:</strong> <?= htmlspecialchars($year); ?></p>
              </div>
              <div class="col-md-6">
                  <p><strong>Semester:</strong> <?= htmlspecialchars($semester); ?></p>
                  <p><strong>Department:</strong> <?= htmlspecialchars($dept); ?></p>
                  <p><strong>Section:</strong> <?= htmlspecialchars($student['section'] ?? 'N/A'); ?></p>
              </div>
          </div>
      </div>

      <!-- Attendance Details -->
      <div class="card">
          <h5 class="mb-3"><strong>Attendance Details</strong></h5>
          <div class="table-responsive">
              <table class="table table-bordered table-striped align-middle">
                  <thead class="table-primary">
                      <tr>
                          <th>Serial No</th>
                          <th>Subject Name</th>
                          <th>Subject Code</th>
                          <th>Faculty Name</th>
                          <th>Total Lectures</th>
                          <th>Days Attended</th>
                          <th>Attendance %</th>
                          <th>EST Eligibility</th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php 
                      $serial = 1;
                      while($row = $subjects_result->fetch_assoc()):
                          $days_attended  = $row['days_attended'] ?? 0;
                          $total_lectures = $row['total_lectures'] ?? 0;
                          $percent = ($total_lectures > 0) ? ($days_attended / $total_lectures) * 100 : 0;
                          $lowAttendance = $percent < 75;
                      ?>
                      <tr class="<?= $lowAttendance ? 'low-attendance' : ''; ?>">
                          <td><?= $serial++; ?></td>
                          <td><?= htmlspecialchars($row['subject_name']); ?></td>
                          <td><?= htmlspecialchars($row['subject_code']); ?></td>
                          <td><?= htmlspecialchars($row['faculty_name']); ?></td>
                          <td><?= $total_lectures; ?></td>
                          <td><?= $days_attended; ?></td>
                          <td><?= number_format($percent, 2); ?>%</td>
                          <td>
                              <?= $lowAttendance ? '<b>Not Allowed</b>' : 'Allowed'; ?>
                          </td>
                      </tr>
                      <?php endwhile; ?>
                  </tbody>
              </table>
          </div>
          <p class="note-text mt-2"><strong>Note:</strong> Attendance must be greater than or equal to <strong>75%</strong> to be eligible for EST.</p>
      </div>
  </div>

</body>
</html>

