<?php
include '../config/db_connect.php';
include '../includes/functions.php';
session_start();

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'Officer'){
    header("location: ../login.php");
    exit;
}

if(!isset($_GET['id']) && !isset($_POST['complaint_id'])){
    header("location: index.php");
    exit;
}

$officer_id = $_SESSION['id'];
$msg = "";

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $complaint_id = $_POST['complaint_id'];
    $remark = trim($_POST['remark']);
    
    if(!empty($remark)){
        // 1. Insert into feedback
        $sql_feedback = "INSERT INTO complaint_feedback (complaint_id, officer_id, remark) VALUES (?, ?, ?)";
        if($stmt = $conn->prepare($sql_feedback)){
            $stmt->bind_param("iis", $complaint_id, $officer_id, $remark);
            if($stmt->execute()){
                // 2. Update complaint status
                $sql_update = "UPDATE complaints SET status = 'Resolved' WHERE id = ?";
                if($stmt_update = $conn->prepare($sql_update)){
                    $stmt_update->bind_param("i", $complaint_id);
                    $stmt_update->execute();
                    $stmt_update->close();
                }
                
                // Redirect with success (simplification)
                header("location: index.php");
                exit;
            } else {
                $msg = '<div class="alert alert-danger">Error submitting feedback.</div>';
            }
            $stmt->close();
        }
    } else {
        $msg = '<div class="alert alert-warning">Please enter a remark.</div>';
    }
}

// Fetch Complaint Details for Display
$complaint_id = isset($_GET['id']) ? $_GET['id'] : $_POST['complaint_id'];
$sql = "SELECT c.id, c.subject, c.description, c.created_at, d.name as department_name, u.fullname as complainant_name 
        FROM complaints c 
        JOIN departments d ON c.department_id = d.id 
        JOIN users u ON c.user_id = u.id 
        WHERE c.id = ? AND c.assigned_to = ? AND c.status = 'In Process'";

if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("ii", $complaint_id, $officer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 1){
        $complaint = $result->fetch_assoc();
    } else {
        // Complaint not found or not assigned to this officer
        echo "Access Denied or Invalid Complaint.";
        exit;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resolve Complaint - OCMS</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Resolve Complaint #<?php echo $complaint['id']; ?></h4>
                </div>
                <div class="card-body">
                    <?php echo $msg; ?>
                    
                    <div class="mb-4">
                        <h5>Complaint Details</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 30%;">Subject</th>
                                <td><?php echo htmlspecialchars($complaint['subject']); ?></td>
                            </tr>
                            <tr>
                                <th>Category</th>
                                <td><?php echo htmlspecialchars($complaint['department_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Complainant</th>
                                <td><?php echo htmlspecialchars($complaint['complainant_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Date Lodged</th>
                                <td><?php echo date("M d, Y", strtotime($complaint['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td><?php echo nl2br(htmlspecialchars($complaint['description'])); ?></td>
                            </tr>
                        </table>
                    </div>

                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Officer's Remarks / Resolution Details</label>
                            <textarea name="remark" class="form-control" rows="5" placeholder="Describe the action taken and final resolution..." required></textarea>
                            <div class="form-text">This feedback will be visible to the citizen and admin.</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success">Mark as Resolved</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
