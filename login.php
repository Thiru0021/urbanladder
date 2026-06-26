<?php
// login.php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_user'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both your email and password entry lines.";
    } else {
        $user_query = "SELECT * FROM users WHERE email = '$email'";
        $user_result = mysqli_query($conn, $user_query);

        if (mysqli_num_rows($user_result) === 1) {
            $user_data = mysqli_fetch_assoc($user_result);
            
            // Validate hashed signatures securely
            if (password_verify($password, $user_data['password'])) {
                $_SESSION['user_id'] = $user_data['id'];
                $_SESSION['user_name'] = $user_data['full_name'];
                
                header("Location: index.php");
                exit;
            } else {
                $error = "Incorrect password credentials entered.";
            }
        } else {
            $error = "No user account match located for this email address.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log In - Urban Ladder</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-container { max-width: 400px; margin: 100px auto; padding: 35px; background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .auth-title { font-size: 24px; font-weight: 700; color: #2D3748; margin-bottom: 25px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #4A5568; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #CBD5E0; border-radius: 6px; font-size: 15px; outline: none; box-sizing: border-box; }
        .form-control:focus { border-color: #FF7043; }
        .btn-auth { width: 100%; background: #FF7043; color: white; border: none; padding: 12px; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .btn-auth:hover { background: #c84616; }
        .auth-switch { text-align: center; margin-top: 20px; font-size: 14px; color: #718096; }
        .auth-switch a { color: #FF7043; text-decoration: none; font-weight: 600; }
        .alert-box { padding: 12px; border-radius: 6px; font-size: 14px; font-weight: 500; margin-bottom: 20px; text-align: center; background: #FFF0F0; color: #DC3545; border: 1px solid #FED7D7; }
    </style>
</head>
<body style="background: #FAFBFC;">

    <div class="auth-container">
        <h2 class="auth-title">Welcome Back</h2>

        <?php if (!empty($error)): ?> <div class="alert-box"><?php echo $error; ?></div> <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" name="login_user" class="btn-auth">Log In</button>
        </form>

        <div class="auth-switch">
            New to Urban Ladder? <a href="register.php">Sign Up Now</a>
        </div>
    </div>

</body>
</html>