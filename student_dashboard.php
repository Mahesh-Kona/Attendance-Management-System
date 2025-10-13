<?php
session_start();
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student'){
    die("Access Denied. Only students can access this page.");
}

$studentID = $_SESSION['userID'];

include 'db_connect.php';

// Fetch student details including year, dept, section
//modify for every sem
$student_sql = "SELECT studentID, studentName, year, dept, section, 1 
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
$semester = 1;

//modify this for every sem
$subjects_sql = "
SELECT 
    s.subject_name,
    s.subject_code,
    s.faculty_name,
    s.section,
        -- Total lectures (counted as distinct days when the subject was taught)
        (
            SELECT COUNT(DISTINCT CONCAT(DATE(att.time), '-', att.period))
                FROM attendance att
                WHERE att.subject_code = s.subject_code
                    AND att.year = s.year
                    AND att.section = s.section
                    AND att.dept = s.dept
                    AND att.semester = 1
        ) AS total_lectures,
    -- Total attended by this student
        (
                -- Count distinct days the student was PRESENT for this subject
            SELECT COUNT(DISTINCT CONCAT(DATE(att2.time), '-', att2.period))
                FROM attendance att2
                WHERE att2.subject_code = s.subject_code
                    AND att2.year = s.year
                    AND att2.section = s.section
                    AND att2.dept = s.dept
                    AND att2.semester = 1
                    AND att2.student_id = ?
                    AND att2.status = 'P'
        ) AS days_attended
FROM subjects s
WHERE s.year = ?
  AND s.dept = ?
  AND s.section = ?
  AND s.semester = 1
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
      html, body {
        height: 100%;
        margin: 0;
        padding: 0;
      }
      body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        background: #f5f5f5;
        font-family: Arial, sans-serif;
        padding: 20px 0 0 0;
      }
      .main-content {
        flex: 1 0 auto;
      }
      h1, h5 { color: #2c3e50; }
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
          padding: 25px; 
          box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
          background: #fff; 
      }
      table th, table td { vertical-align: middle; }
      .low-attendance { background-color: #fde2e1 !important; color: #c0392b; }
      .note-text { font-size: 0.9rem; color: #555; }
      footer {
        background: #002147;
        color: #fff;
        text-align: center;
        padding: 15px 0;
        font-size: 0.9rem;
        left: 0;
        right: 0;
        bottom: 0;
        margin-top: auto;
      }
  </style>
</head>
<body style="display:flex; flex-direction:column; min-height:100vh; padding-bottom:0;">

  <!-- Header -->
  <div class="header">
      <h1>Student Dashboard</h1>
      <a href="index.php" class="btn btn-primary">Logout</a>
  </div>

  <div class="container main-content">
      <!-- Student Details Card -->
      <div class="card mb-4">
          <h5 class="mb-3"><strong>Details</strong></h5>
          <div class="row">
              <div class="col-md-6">
                  <p><strong>ID:</strong> <?= htmlspecialchars($student['studentID']); ?></p>
                  <p><strong>Name:</strong> <?= htmlspecialchars($student['studentName']); ?></p>
                  <p><strong>Year:</strong> <?= htmlspecialchars($year); ?></p>
              </div>
              <div class="col-md-6">
                  <p><strong>Semester:</strong><?= htmlspecialchars($semester); ?></p>
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
                          <th>S.No</th>
                          <th>Subject Name</th>
                          <th>Subject Code</th>
                          <th>Faculty Name</th>
                          <th>Total Conducted</th>
                          <th>Total Attended</th>
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
  </div><br>
  <footer>
    &copy; <?= date('Y') ?> Rajiv Gandhi University of Knowledge Technologies Nuzvid. All rights reserved.
  </footer>
</body>
</html>



