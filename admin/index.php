<?php
include '../config/db_connect.php';
include '../includes/functions.php';
session_start();

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'Admin'){
    header("location: ../login.php");
    exit;
}

// Handle Assignment Logic
$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_complaint'])) {
    $complaint_id = $_POST['complaint_id'];
    $officer_id = $_POST['officer_id'];
    
    if(!empty($officer_id)) {
        $sql = "UPDATE complaints SET assigned_to = ?, status = 'In Process' WHERE id = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("ii", $officer_id, $complaint_id);
            if($stmt->execute()){
                $msg = '<div class="alert alert-success alert-dismissible fade show" role="alert">Complaint assigned successfully!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                $msg = '<div class="alert alert-danger">Error assigning complaint.</div>';
            }
            $stmt->close();
        }
    }
}

// Fetch Statistics
$total_complaints = $conn->query("SELECT COUNT(*) FROM complaints")->fetch_row()[0];
$pending_complaints = $conn->query("SELECT COUNT(*) FROM complaints WHERE status = 'Pending'")->fetch_row()[0];
$resolved_complaints = $conn->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved'")->fetch_row()[0];

// Fetch Officers for Dropdown
$officers = $conn->query("SELECT id, fullname FROM users WHERE role = 'Officer'");
$officers_list = [];
while($row = $officers->fetch_assoc()){
    $officers_list[] = $row;
}

// Fetch Complaints (Search & Filter)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT c.id, c.subject, c.created_at, c.status, d.name as department_name, u.fullname as user_name, c.assigned_to 
        FROM complaints c 
        JOIN departments d ON c.department_id = d.id 
        JOIN users u ON c.user_id = u.id 
        WHERE 1=1";

$types = "";
$params = [];

if(!empty($search)){
    $sql .= " AND (c.subject LIKE ? OR u.fullname LIKE ?)";
    $search_param = "%" . $search . "%";
    $types .= "ss";
    $params[] = $search_param;
    $params[] = $search_param;
}

if(!empty($status_filter)){
    $sql .= " AND c.status = ?";
    $types .= "s";
    $params[] = $status_filter;
}

$sql .= " ORDER BY CASE WHEN c.status = 'Pending' THEN 1 ELSE 2 END, c.created_at DESC";

if(!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - OCMS</title>
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

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-primary">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="ms-auto">
                    <span class="navbar-text">Welcome, Admin</span>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card card-stats bg-white p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Total</h5>
                                <h3 class="card-number"><?php echo $total_complaints; ?></h3>
                            </div>
                            <div class="icon-big text-primary">
                                <i class="fas fa-folder-open"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stats bg-white p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Pending</h5>
                                <h3 class="card-number text-danger"><?php echo $pending_complaints; ?></h3>
                            </div>
                            <div class="icon-big text-danger">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stats bg-white p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Resolved</h5>
                                <h3 class="card-number text-success"><?php echo $resolved_complaints; ?></h3>
                            </div>
                            <div class="icon-big text-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="card mb-4 shadow-sm main-table-card">
                <div class="card-body">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-md-6">
                            <input type="text" name="search" class="form-control" placeholder="Search by Subject or Citizen Name" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="Pending" <?php if($status_filter == 'Pending') echo 'selected'; ?>>Pending</option>
                                <option value="In Process" <?php if($status_filter == 'In Process') echo 'selected'; ?>>In Process</option>
                                <option value="Resolved" <?php if($status_filter == 'Resolved') echo 'selected'; ?>>Resolved</option>
                                <option value="Closed" <?php if($status_filter == 'Closed') echo 'selected'; ?>>Closed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Complaints Table -->
            <?php echo $msg; ?>
            <div class="card shadow-sm main-table-card">
                <div class="card-header bg-white">
                    <h4 class="mb-0 text-primary"><i class="fas fa-tasks"></i> Manage Complaints</h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Citizen</th>
                                    <th>Subject</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Assign Officer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['user_name']); ?></td>
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
                                            <td>
                                                <?php if($row['status'] == 'Pending' || $row['status'] == 'In Process'): ?>
                                                    <form action="" method="POST" class="d-flex">
                                                        <input type="hidden" name="complaint_id" value="<?php echo $row['id']; ?>">
                                                        <select name="officer_id" class="form-select form-select-sm me-2" required>
                                                            <option value="">Select Officer</option>
                                                            <?php foreach($officers_list as $officer): ?>
                                                                <option value="<?php echo $officer['id']; ?>" <?php echo ($row['assigned_to'] == $officer['id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($officer['fullname']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" name="assign_complaint" class="btn btn-outline-primary btn-sm">Assign</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted small">No Action Needed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No complaints found.</td>
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

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarCollapse').addEventListener('click', function () {
        document.getElementById('sidebar').classList.toggle('active');
    });
</script>
</body>
</html>
