<?php
include '../config/db_connect.php';
session_start();

// Authorization Check
if(!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== 'Admin'){
    header("location: ../login.php");
    exit;
}

// Stats Calculation
$total = $conn->query("SELECT COUNT(*) FROM complaints")->fetch_row()[0];
$resolved = $conn->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved'")->fetch_row()[0];
$pending = $conn->query("SELECT COUNT(*) FROM complaints WHERE status = 'Pending'")->fetch_row()[0];
$resolution_rate = $total > 0 ? round(($resolved / $total) * 100, 1) : 0;

// Fetch All Complaints
$result = $conn->query("SELECT c.*, u.fullname, d.name as dept_name FROM complaints c 
                        JOIN users u ON c.user_id = u.id 
                        JOIN departments d ON c.department_id = d.id 
                        ORDER BY c.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports - OCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print {
            #sidebar, #sidebarCollapse, .no-print { display: none !important; }
            #content { width: 100%; margin: 0; }
            .card { border: none !important; box-shadow: none !important; }
            .badge { border: 1px solid #000; color: #000 !important; }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-primary no-print">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="ms-auto">
                    <span class="navbar-text">System Reports</span>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <!-- Header & Print Button -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary">Complaint Statistics Report</h2>
                <button onclick="window.print()" class="btn btn-secondary no-print"><i class="fas fa-print"></i> Print Report</button>
            </div>

            <!-- Stats Row -->
            <div class="row mb-5">
                <div class="col-md-3">
                    <div class="card card-stats bg-white p-3 shadow-sm border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Total</h5>
                                <h3 class="card-number"><?php echo $total; ?></h3>
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
                                <h3 class="card-number text-success"><?php echo $resolved; ?></h3>
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
                                <h3 class="card-number text-danger"><?php echo $pending; ?></h3>
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

            <!-- Master List Table -->
            <div class="card shadow-sm main-table-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Master Complaint Log</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Citizen</th>
                                <th>Department</th>
                                <th>Subject</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo date("Y-m-d", strtotime($row['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($row['dept_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                <td class="text-center">
                                    <?php 
                                        $s = $row['status'];
                                        $color = $s == 'Pending' ? 'danger' : ($s == 'In Process' ? 'warning' : 'success');
                                        if($s == 'Closed') $color = 'secondary';
                                        echo "<span class='badge bg-$color'>$s</span>";
                                    ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-3">No records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
