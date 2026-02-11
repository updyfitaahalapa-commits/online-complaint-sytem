<?php
session_start();
include 'config/db_connect.php';

// Check if user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if($_SESSION["role"] == 'Admin'){
        header("location: admin/index.php");
    } elseif($_SESSION["role"] == 'Officer'){
        header("location: officer/index.php");
    } else {
        header("location: citizen/index.php");
    }
    exit;
}

$email = $password = "";
$email_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // ... (This logic remains the same, just keeping the visual wrapper) ...
    // Note: To save space in this replacement, I'm assuming the PHP logic block above this is handling the vars. 
    // BUT since I am replacing the WHOLE file, I must include the PHP logic.
    // Let's re-include the minimal logic needed or just the visual part if the user wants me to keep the logic.
    // The user instruction is "Refactor Public Pages". I should preserve the logic.
    
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter email.";
    } else{
        $email = trim($_POST["email"]);
    }
    
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    if(empty($email_err) && empty($password_err)){
        $sql = "SELECT id, fullname, email, password, role FROM users WHERE email = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("s", $param_email);
            $param_email = $email;
            if($stmt->execute()){
                $stmt->store_result();
                if($stmt->num_rows == 1){
                    $stmt->bind_result($id, $fullname, $email, $hashed_password, $role);
                    if($stmt->fetch()){
                        if(password_verify($password, $hashed_password)){
                            session_start();
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["fullname"] = $fullname;
                            $_SESSION["email"] = $email;
                            $_SESSION["role"] = $role;
                            
                            switch($role){
                                case 'Admin': header("location: admin/index.php"); break;
                                case 'Officer': header("location: officer/index.php"); break;
                                default: header("location: citizen/index.php"); break;
                            }
                        } else{
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else{
                    $login_err = "Invalid email or password.";
                }
            } else{
                echo "Oops! Something went wrong.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<a href="index.php" class="back-home"><i class="fas fa-arrow-left me-2"></i> Back to Home</a>

<div class="auth-container">
    <div class="auth-card fade-in-down">
        <div class="auth-header">
            <h3><i class="fas fa-shield-alt me-2"></i>OCMS Portal</h3>
            <p class="mb-0 text-white-50">Online Complaint Management System</p>
        </div>
        <div class="auth-body">
            <?php 
            if(!empty($login_err)){
                echo '<div class="alert alert-danger text-center"><i class="fas fa-exclamation-circle me-1"></i> ' . $login_err . '</div>';
            }        
            ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-floating mb-3">
                    <input type="text" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" id="floatingEmail" placeholder="name@example.com" value="<?php echo $email; ?>">
                    <label for="floatingEmail">Email Address</label>
                    <div class="invalid-feedback"><?php echo $email_err; ?></div>
                </div>
                <div class="form-floating mb-4">
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" id="floatingPassword" placeholder="Password">
                    <label for="floatingPassword">Password</label>
                    <div class="invalid-feedback"><?php echo $password_err; ?></div>
                </div>
                <div class="d-grid gap-2 mb-4">
                    <button type="submit" class="btn btn-primary btn-lg">Login</button>
                    <a href="register.php" class="btn btn-outline-secondary">Create an Account</a>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
