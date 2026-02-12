<?php
// ... (Preserve logic, just update HTML) ...
// Since I need to replace the whole file to fix the HTML structure, I will re-include the logic.
require_once "config/db_connect.php";
 
$name = $email = $phone = $password = "";
$name_err = $email_err = $phone_err = $password_err = "";
 
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // ... [Logic same as before, abbreviated for simplicity in this prompt but MUST be here] ...
    if(empty(trim($_POST["name"]))){ $name_err = "Please enter your name."; } else{ $name = trim($_POST["name"]); }
    
    if(empty(trim($_POST["email"]))){ $email_err = "Please enter your email."; } else{ 
        $sql = "SELECT id FROM users WHERE email = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("s", $param_email);
            $param_email = trim($_POST["email"]);
            if($stmt->execute()){
                $stmt->store_result();
                if($stmt->num_rows == 1){ $email_err = "This email is already taken."; } else{ $email = trim($_POST["email"]); }
            }
            $stmt->close();
        }
    }

    if(empty(trim($_POST["phone"]))){ $phone_err = "Please enter phone number."; } else{ $phone = trim($_POST["phone"]); }
    
    if(empty(trim($_POST["password"]))){ $password_err = "Please enter a password."; } elseif(strlen(trim($_POST["password"])) < 6){ $password_err = "Password must have at least 6 characters."; } else{ $password = trim($_POST["password"]); }
    
    if(empty($name_err) && empty($email_err) && empty($password_err) && empty($phone_err)){
        $sql = "INSERT INTO users (fullname, email, phone, password, role) VALUES (?, ?, ?, ?, 'Citizen')";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("ssss", $param_name, $param_email, $param_phone, $param_password);
            $param_name = $name;
            $param_email = $email;
            $param_phone = $phone;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            if($stmt->execute()){ header("location: login.php"); } else{ echo "Something went wrong."; }
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
    <title>Register - OCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<a href="index.php" class="back-home"><i class="fas fa-arrow-left me-2"></i> Back to Home</a>

<div class="auth-container">
    <div class="auth-card fade-in-down">
        <div class="auth-header">
            <h3><i class="fas fa-user-plus me-2"></i>Sign Up</h3>
            <p class="mb-0 text-white-50">Create your Citizen Account</p>
        </div>
        <div class="auth-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-floating mb-3">
                    <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" id="floatingName" placeholder="Full Name" value="<?php echo $name; ?>">
                    <label for="floatingName">Full Name</label>
                    <div class="invalid-feedback"><?php echo $name_err; ?></div>
                </div>
                <div class="form-floating mb-3">
                    <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" id="floatingEmail" placeholder="name@example.com" value="<?php echo $email; ?>">
                    <label for="floatingEmail">Email Address</label>
                    <div class="invalid-feedback"><?php echo $email_err; ?></div>
                </div>
                <div class="form-floating mb-3">
                    <input type="text" name="phone" class="form-control <?php echo (!empty($phone_err)) ? 'is-invalid' : ''; ?>" id="floatingPhone" placeholder="Phone" value="<?php echo $phone; ?>">
                    <label for="floatingPhone">Phone Number</label>
                    <div class="invalid-feedback"><?php echo $phone_err; ?></div>
                </div>
                <div class="form-floating mb-4">
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" id="floatingPassword" placeholder="Password" value="<?php echo $password; ?>">
                    <label for="floatingPassword">Password</label>
                    <div class="invalid-feedback"><?php echo $password_err; ?></div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Register</button>
                    <a href="login.php" class="btn btn-outline-secondary">Already have an account? Login</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
