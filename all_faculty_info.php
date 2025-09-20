<?php
session_start();
// Ensure only department office users can access this page
if(!isset($_SESSION['userID']) || $_SESSION['role'] !== 'dept_office'){
    die("Access Denied. This action is only allowed for Department Office users.");
}

include 'db_connect.php'; 

// Fetch all students in the department with filters
$dept = $_SESSION['dept'];


$sql = "SELECT facultyID, facultyName,contact 
        FROM userfaculty
        WHERE dept = ?";


$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $dept);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Faculty Info</title>
    <style>
        table, th, td { border: 1px solid black; border-collapse: collapse; padding: 8px; }
        th { background-color: #f2f2f2; }
        .header-container { text-align: right; padding: 10px; background-color: #f8f9fa; }
    </style>
</head>
<body>
  

    
   <div class="container mt-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-center position-relative mb-4">
        <div class="text-center">
            <h2>Department of <?php echo htmlspecialchars($dept); ?></h2> 
            <h3>Faculty Data</h3>
        </div>
        <a href="dept_office_dashboard.php" class="btn btn-primary position-absolute end-0">
            Dashboard
        </a>
    </div>

       

        <!--    Facu;ty Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover shadow-sm">
                <thead class="table-primary text-center">
                    <tr>
                        <th>S.No</th>
                        <th>Faculty ID</th>
                        <th>Faculty Name</th>
                        <th>Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                        <tr class="align-middle">
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($row['facultyID']); ?></td>
                            <td><?php echo htmlspecialchars($row['facultyName']); ?></td>
                            <td><?php echo htmlspecialchars($row['contact']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No Faculty found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
   </div>
</body>
</html>
