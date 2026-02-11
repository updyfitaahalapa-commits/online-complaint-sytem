<?php
include '../config/db_connect.php';
include '../includes/functions.php';
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'Officer'){
    header("location: ../login.php");
    exit;
}

$officer_id = $_SESSION["id"];
$msg = "";

// Handle Status Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $complaint_id = $_POST['complaint_id'];
    $status = $_POST['status'];
    $remark = trim($_POST['remark']);

    if (!empty($status) && !empty($remark)) {
        // Append remark to description or store separately? 
        // For simplicity, let's append it to description or strict separation?
        // The previous implementation might have just updated status.
        // Let's assume we just update status and maybe append remark to description for now as schema doesn't have 'remarks' column.
        // Wait, I should check schema. DB schema showed `description` text. No `remarks`.
        // I will append the remark to the description for now: " [Officer Remark: ...]"
        
        $sql = "UPDATE complaints SET status = ?, description = CONCAT(description, ?) WHERE id = ? AND assigned_to = ?";
        if ($stmt = $conn->prepare($sql)) {
            $append_remark = "\n\n[Officer Update]: " . $remark;
            $stmt->bind_param("ssii", $status, $append_remark, $complaint_id, $officer_id);
            if ($stmt->execute()) {
                $msg = '<div class="alert alert-success alert-dismissible fade show">Complaint status updated! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                $msg = '<div class="alert alert-danger">Error updating status.</div>';
            }
            $stmt->close();
        }
    } else {
        $msg = '<div class="alert alert-warning">Status and Remark are required.</div>';
    }
}

// Fetch Assigned Complaints
$sql = "SELECT c.id, c.subject, c.description, c.created_at, c.status, u.fullname as user_name 
        FROM complaints c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.assigned_to = ? 
        ORDER BY CASE WHEN c.status = 'In Process' THEN 1 ELSE 2 END, c.created_at DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $officer_id);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard - OCMS</title>
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
                    <span class="navbar-text">Welcome, Officer <?php echo htmlspecialchars($_SESSION["fullname"]); ?></span>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <?php echo $msg; ?>
            <div class="card shadow-sm main-table-card">
                <div class="card-header bg-white">
                    <h4 class="mb-0 text-primary"><i class="fas fa-clipboard-list"></i> Assigned Complaints</h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Citizen</th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
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
                                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $row['id']; ?>">
                                                    Update
                                                </button>

                                                <!-- Update Modal -->
                                                <div class="modal fade" id="updateModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Update Complaint #<?php echo $row['id']; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form action="" method="POST">
                                                                <div class="modal-body">
                                                                    <p><strong>Subject:</strong> <?php echo htmlspecialchars($row['subject']); ?></p>
                                                                    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                                                                    <hr>
                                                                    <input type="hidden" name="complaint_id" value="<?php echo $row['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">New Status</label>
                                                                        <select name="status" class="form-select" required>
                                                                            <option value="In Process" <?php echo ($row['status'] == 'In Process') ? 'selected' : ''; ?>>In Process</option>
                                                                            <option value="Resolved" <?php echo ($row['status'] == 'Resolved') ? 'selected' : ''; ?>>Resolved</option>
                                                                            <option value="Closed" <?php echo ($row['status'] == 'Closed') ? 'selected' : ''; ?>>Closed</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Remark / Resolution Note</label>
                                                                        <textarea name="remark" class="form-control" rows="3" required></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    <button type="submit" name="update_status" class="btn btn-primary">Save Changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No assigned complaints found.</td>
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
