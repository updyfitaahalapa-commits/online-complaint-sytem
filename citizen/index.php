<?php
include '../config/db_connect.php';
include '../includes/functions.php';
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'Citizen'){
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION["id"];
$msg = "";

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject = trim($_POST["subject"]);
    $department_id = $_POST["department_id"];
    $description = trim($_POST["description"]);

    if (!empty($subject) && !empty($department_id) && !empty($description)) {
        $sql = "INSERT INTO complaints (user_id, department_id, subject, description) VALUES (?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("iiss", $user_id, $department_id, $subject, $description);
            if ($stmt->execute()) {
                $msg = '<div class="alert alert-success alert-dismissible fade show">Complaint lodged successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                $msg = '<div class="alert alert-danger">Something went wrong.</div>';
            }
            $stmt->close();
        }
    } else {
        $msg = '<div class="alert alert-warning">Please fill in all fields.</div>';
    }
}

// Fetch User Statistics
$total_complaints = $conn->query("SELECT COUNT(*) FROM complaints WHERE user_id = $user_id")->fetch_row()[0];
$pending_complaints = $conn->query("SELECT COUNT(*) FROM complaints WHERE user_id = $user_id AND status = 'Pending'")->fetch_row()[0];
$resolved_complaints = $conn->query("SELECT COUNT(*) FROM complaints WHERE user_id = $user_id AND status = 'Resolved'")->fetch_row()[0];
$resolution_rate = $total_complaints > 0 ? round(($resolved_complaints / $total_complaints) * 100, 1) : 0;

// Fetch Departments
$departments = $conn->query("SELECT * FROM departments");

// Fetch User's Complaints
$sql_complaints = "SELECT c.id, c.subject, c.created_at, c.status, d.name as department_name 
                   FROM complaints c 
                   JOIN departments d ON c.department_id = d.id 
                   WHERE c.user_id = ? ORDER BY c.created_at DESC";
if ($stmt = $conn->prepare($sql_complaints)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result_complaints = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Dashboard - OCMS</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-primary">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="ms-auto">
                    <span class="navbar-text">Welcome, <?php echo htmlspecialchars($_SESSION["fullname"]); ?></span>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card card-stats bg-white p-3 shadow-sm border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Total</h5>
                                <h3 class="card-number"><?php echo $total_complaints; ?></h3>
                            </div>
                            <div class="icon-big text-primary opacity-25">
                                <i class="fas fa-folder-open"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stats bg-white p-3 shadow-sm border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Resolved</h5>
                                <h3 class="card-number text-success"><?php echo $resolved_complaints; ?></h3>
                            </div>
                            <div class="icon-big text-success opacity-25">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stats bg-white p-3 shadow-sm border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Pending</h5>
                                <h3 class="card-number text-danger"><?php echo $pending_complaints; ?></h3>
                            </div>
                            <div class="icon-big text-danger opacity-25">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stats bg-white p-3 shadow-sm border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Resolution Rate</h5>
                                <h3 class="card-number text-info"><?php echo $resolution_rate; ?>%</h3>
                            </div>
                            <div class="icon-big text-info opacity-25">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="mb-4">
                <a href="lodge_complaint.php" class="btn btn-primary btn-lg shadow-sm">
                    <i class="fas fa-plus-circle me-2"></i> Lodge New Complaint
                </a>
            </div>

            <!-- Complaint History -->
            <div class="card shadow-sm main-table-card">
                <div class="card-header bg-white">
                    <h4 class="mb-0 text-dark"><i class="fas fa-history"></i> My Complaints History</h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#ID</th>
                                    <th>Subject</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($result_complaints->num_rows > 0): ?>
                                    <?php while($row = $result_complaints->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                            <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                                            <td><?php echo date("M d, Y", strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <?php 
                                                    $status = $row['status'];
                                                    $badgeClass = 'bg-secondary';
                                                    if($status == 'Pending') $badgeClass = 'status-pending';
                                                    elseif($status == 'In Process') $badgeClass = 'status-process';
                                                    elseif($status == 'Resolved') $badgeClass = 'status-resolved';
                                                    elseif($status == 'Closed') $badgeClass = 'status-closed';
                                                ?>
                                                <span class="badge <?php echo $badgeClass != 'bg-secondary' ? 'status-badge ' . $badgeClass : $badgeClass; ?>"><?php echo $status; ?></span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">You haven't lodged any complaints yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarCollapse').addEventListener('click', function () {
        document.getElementById('sidebar').classList.toggle('active');
    });
</script>
</body>
</html>
