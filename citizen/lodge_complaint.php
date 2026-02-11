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
                $msg = '<div class="alert alert-success alert-dismissible fade show">Complaint lodged successfully! Redirecting... <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                header("refresh:2;url=index.php"); // Redirect back to dashboard after 2 seconds
            } else {
                $msg = '<div class="alert alert-danger">Something went wrong.</div>';
            }
            $stmt->close();
        }
    } else {
        $msg = '<div class="alert alert-warning">Please fill in all fields.</div>';
    }
}

// Fetch Departments
$departments = $conn->query("SELECT * FROM departments");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lodge Complaint - OCMS</title>
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
                    <span class="navbar-text">Logged in as <?php echo htmlspecialchars($_SESSION["fullname"]); ?></span>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h4 class="mb-0 text-primary"><i class="fas fa-edit"></i> Lodge a New Complaint</h4>
                        </div>
                        <div class="card-body">
                            <?php echo $msg; ?>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <div class="form-floating mb-3">
                                    <input type="text" name="subject" class="form-control" id="subject" placeholder="Subject" required>
                                    <label for="subject">Subject</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <select name="department_id" class="form-select" id="dept" required>
                                        <option value="">Select Category</option>
                                        <?php while($row = $departments->fetch_assoc()): ?>
                                            <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <label for="dept">Category</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <textarea name="description" class="form-control" placeholder="Description" id="desc" style="height: 150px" required></textarea>
                                    <label for="desc">Description of the issue...</label>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Submit Complaint</button>
                                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
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
