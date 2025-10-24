<?php
session_start();

// Check if faculty is logged in
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'faculty'){
    die("Access Denied. This action is only allowed for Faculty users.");
}

include "db_connect.php";
$facultyID = $_SESSION['userID'];

// Fetch faculty details from userfaculty
$sql = "SELECT facultyID, facultyName, dept FROM userfaculty WHERE facultyID=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $facultyID);
$stmt->execute();
$result = $stmt->get_result();
$faculty = $result->fetch_assoc();
$stmt->close();

$facultyName = $faculty['facultyName'];
$dept = $faculty['dept'];

// Fetch subjects allotted to this faculty from subjects table modify this for every sem
$sql = "SELECT subject_code, subject_name, credits, date_time, year, section, dept
    FROM subjects WHERE faculty_id=? AND semester='1' AND academic_year='2025-26' ";
$semester=1;
$academic_year='2025-26';        
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $facultyID);
$stmt->execute();
$subjects = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Faculty Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Reset and base */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden; /* Prevent horizontal scroll bar */
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(180deg, #f7f9fc 0%, #f5f7fb 100%);
            font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            padding: 20px;
            color: #222;
            box-sizing: border-box;
        }
        .main-content {
            flex: 1 0 auto; /* allow main to grow */
            /* Give space for the fixed footer so content isn't hidden behind it */
            padding-bottom: 80px;
        }
    h1 { color: #1f2937; margin: 0; font-weight: 600; font-size: 1.6rem; }

    /* Header */
    .header { margin-bottom: 20px; }
    .header .brand { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .brand .welcome { color: #6b7280; font-size: 0.95rem; }

        /* Info card */
        .info-box {
            background: #fff;
            padding: 18px;
            border-radius: 10px;
            max-width: 920px;
            margin: 0 auto 20px auto;
            box-shadow: 0 6px 18px rgba(20,30,50,0.06);
            text-align: left;
        }

        /* Links and buttons */
        a.button { display: inline-block; padding: 8px 14px; color: #fff; text-decoration: none; border-radius: 6px; }
       

        /* Footer fixed to the viewport bottom and full width */
        footer {
            background: #03203f;
            color: #e6eef8;
            text-align: center;
            padding: 14px 12px;
            font-size: 0.9rem;
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            box-sizing: border-box;
            border-radius: 0;
            margin: 0;
            /* subtle top shadow so footer stands out above content */
            box-shadow: 0 -4px 12px rgba(0,0,0,0.08);
            z-index: 9999; /* keep footer above other elements */
        }

    /* keep the bottom padding so the fixed footer doesn't overlap content */

          /* Make tables responsive and look clean on small screens */
          .table thead th { vertical-align: middle; }
          .action-btns a { margin-right: 6px; margin-bottom: 6px; }

          /* Increase table horizontal length so columns have more room.
              .table-wide sets a minimum width and allows horizontal scrolling
              inside the .table-responsive wrapper. Adjust min-width as needed. */
          .table-responsive { overflow-x: auto; }
          .table-wide { min-width: 1200px; white-space: nowrap; }

        @media (max-width: 576px) {
            .info-box { padding: 14px; margin: 0 8px 16px; }
            h1 { font-size: 1.5rem; }
            .header .btn { padding: 6px 10px; font-size: 0.85rem; }
            /* Logout button full width on very small screens */
            .logout-btn { width: 100%; }
        }
        @media (min-width: 577px) {
            .logout-btn { width: auto; }
        }
    </style>
</head>
<body style="position:relative; min-height:100vh;">

    <div class="container-fluid main-content">
        <!-- Header -->
        <div class="row align-items-center mb-3">
            <div class="col-12 col-sm-9 mb-2 mb-sm-0">
                <div class="brand">
                    <h1 class="mb-0">Faculty Dashboard</h1>
                </div>
            </div>
            <div class="col-12 col-sm-3 text-sm-end">
                <a href="index.php" class="btn btn-primary logout-btn">Logout</a>
            </div>
        </div>
<br>
        <!-- Faculty Info Box -->
        <div class="info-box">
            <div class="row">
                <div class="col-12 col-md-4">
                    <p class="mb-1"><strong>Faculty ID</strong></p>
                    <div class="text-muted small"><?php echo htmlspecialchars($facultyID); ?></div>
                </div>
                <div class="col-12 col-md-5">
                    <p class="mb-1"><strong>Faculty Name</strong></p>
                    <div class="text-muted small"><?php echo htmlspecialchars($facultyName); ?></div>
                </div>
                <div class="col-12 col-md-3">
                    <p class="mb-1"><strong>Department</strong></p>
                    <div class="text-muted small"><?php echo htmlspecialchars($dept); ?></div>
                </div>
            </div>
        </div>

        <h4 class="mt-3">Your Allotted Subjects</h4>

        <!-- Responsive table wrapper -->
        <div class="table-responsive shadow-sm rounded bg-white mt-2">
            <table class="table table-bordered table-striped mb-0">
            <thead class="table-primary">
                <tr>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Credits</th>
                    <th>Department</th>
                    <th>Year</th>
                    <th>Academic Year</th>
                    <th>Semester</th>
                    <th>Reg Date</th>
                    <th>Section</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($subjects->num_rows > 0) { ?>
                    <?php while ($row = $subjects->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['credits']); ?></td>
                             <td><?php echo htmlspecialchars($row['dept']); ?></td>
                            <td><?php echo htmlspecialchars($row['year']); ?></td>
                            <td><?php echo htmlspecialchars($academic_year); ?></td>
                            <td><?php echo htmlspecialchars($semester); ?></td>
                            <td><?php echo htmlspecialchars($row['date_time']); ?></td>
                             <td><?php echo htmlspecialchars($row['section']); ?></td>
                            <td>
                                          <a class="btn btn-primary btn-sm" 
                                              href="attendance_dashboard_for_faculty.php?subject_code=<?php echo urlencode($row['subject_code']); ?>&subject_name=<?php echo urlencode($row['subject_name']); ?>&faculty_id=<?php echo urlencode($facultyID); ?>&faculty_name=<?php echo urlencode($facultyName); ?>&section=<?php echo urlencode($row['section']); ?>&year=<?php echo urlencode($row['year']); ?>&dept=<?php echo urlencode($row['dept']); ?>&semester=<?php echo urlencode($semester); ?>&academic_year=<?php echo urlencode($academic_year); ?>">
                                    Take Attendance
                                </a>
                                <a class="btn btn-success btn-sm" 
                                              href="view_attendance_records.php?subject_code=<?php echo urlencode($row['subject_code']); ?>&subject_name=<?php echo urlencode($row['subject_name']); ?>&faculty_id=<?php echo urlencode($facultyID); ?>&faculty_name=<?php echo urlencode($facultyName); ?>&section=<?php echo urlencode($row['section']); ?>&year=<?php echo urlencode($row['year']); ?>&dept=<?php echo urlencode($row['dept']); ?>&semester=<?php echo urlencode($semester); ?>&academic_year=<?php echo urlencode($academic_year); ?>">
                                    View Records
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted">No data available</td>
                    </tr>
                <?php } ?>
            </tbody>
            </table>
        </div>
    </div>

    <footer>
        &copy; <?= date('Y') ?> Rajiv Gandhi University of Knowledge Technologies Nuzvid. All rights reserved.
    </footer>
</body>
</html>
