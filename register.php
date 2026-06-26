<?php
// register.php
session_start();
require_once 'config/db.php';

// If a customer is already signed in, push them back to the main homepage
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_user'])) {
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "All registration fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long for security.";
    } else {
        // Double-check if this email address already occupies a record slot
        $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check_email) > 0) {
            $error = "This email address is already registered to an account.";
        } else {
            // Hash the password cleanly using standard enterprise standards
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            $insert_query = "INSERT INTO users (full_name, email, password) VALUES ('$full_name', '$email', '$hashed_password')";
            if (mysqli_query($conn, $insert_query)) {
                $success = "Account created successfully! Redirecting you to login...";
                header("Refresh: 2; url=login.php");
            } else {
                $error = "Registration storage execution error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account - Urban Ladder</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-container { max-width: 400px; margin: 80px auto; padding: 35px; background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .auth-title { font-size: 24px; font-weight: 700; color: #2D3748; margin-bottom: 25px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #4A5568; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #CBD5E0; border-radius: 6px; font-size: 15px; outline: none; box-sizing: border-box; }
        .form-control:focus { border-color: #FF7043; }
        .btn-auth { width: 100%; background: #FF7043; color: white; border: none; padding: 12px; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .btn-auth:hover { background: #c84616; }
        .auth-switch { text-align: center; margin-top: 20px; font-size: 14px; color: #718096; }
        .auth-switch a { color: #FF7043; text-decoration: none; font-weight: 600; }
        .alert-box { padding: 12px; border-radius: 6px; font-size: 14px; font-weight: 500; margin-bottom: 20px; text-align: center; }
        .alert-error { background: #FFF0F0; color: #DC3545; border: 1px solid #FED7D7; }
        .alert-success { background: #F0FFF4; color: #38A169; border: 1px solid #C6F6D5; }
    </style>
</head>
<body style="background: #FAFBFC;">

    <div class="auth-container">
        <h2 class="auth-title">Join Urban Ladder</h2>

        <?php if (!empty($error)): ?> <div class="alert-box alert-error"><?php echo $error; ?></div> <?php endif; ?>
        <?php if (!empty($success)): ?> <div class="alert-box alert-success"><?php echo $success; ?></div> <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" placeholder="e.g., John Doe" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
            </div>
            <button type="submit" name="register_user" class="btn-auth">Create Account</button>
        </form>

        <div class="auth-switch">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>

</body>
</html>