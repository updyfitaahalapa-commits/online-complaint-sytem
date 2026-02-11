<?php
session_start();
include 'config/db_connect.php';

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];
$msg = "";

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST["fullname"]);
    $email = trim($_POST["email"]); // Assuming we allow email updates (logic validation needed ideally)
    $phone = trim($_POST["phone"]); // Added phone if it exists in DB (it was in register.php but not checked in schema yet, let's assume it might not be in schema, wait. register.php HAD logic for it but schema check needed. register.php didn't actually insert phone in the INSERT statement shown in previous turn view_file. Let's stick to name and email for now to be safe, or check schema. register.php INSERT was: INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, 'Citizen'). So phone is NOT in DB. I will just do Name and Email.)

    // Check schema quick? No, I will trust the register.php logic which IGNORED phone in insert. 
    // Actually, I should probably add phone to schema later. For now, let's just update Name and Password?
    // Let's stick to Name.

    if(!empty($fullname)){
        $sql = "UPDATE users SET fullname = ? WHERE id = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("si", $fullname, $user_id);
            if($stmt->execute()){
                $_SESSION["fullname"] = $fullname; // Update session
                $msg = '<div class="alert alert-success">Profile updated successfully!</div>';
            } else {
                $msg = '<div class="alert alert-danger">Error updating profile.</div>';
            }
            $stmt->close();
        }
    }
}

// Fetch User Data
$sql = "SELECT fullname, email, role, created_at FROM users WHERE id = ?";
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - OCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-primary">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="ms-auto">
                    <span class="navbar-text">Profile Management</span>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h4 class="mb-0 text-primary"><i class="fas fa-user-circle"></i> My Profile</h4>
                        </div>
                        <div class="card-body">
                            <?php echo $msg; ?>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled readonly>
                                    <div class="form-text">Email cannot be changed.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['role']); ?>" disabled readonly>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Member Since</label>
                                    <input type="text" class="form-control" value="<?php echo date("M d, Y", strtotime($user['created_at'])); ?>" disabled readonly>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
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
