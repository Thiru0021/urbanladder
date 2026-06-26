<?php
// admin/admin_login.php
session_start();

// Intelligent database configuration path loader
if (file_exists('../config/db.php')) {
    require_once '../config/db.php';
} else if (file_exists('../../config/db.php')) {
    require_once '../../config/db.php';
} else {
    die("Database configuration file missing. Verify your database connection files!");
}

// Redirect to dashboard if the admin is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = "";

// Process Authentication Check on Form Post Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_admin'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both your email address and password code.";
    } else {
        // Query looks into the 'users' table since your admin profile details exist there
        $query = "SELECT id, full_name, password FROM users WHERE email = '$email' LIMIT 1";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $admin_data = mysqli_fetch_assoc($result);

            // Verifies the encrypted password hash securely
            if (password_verify($password, $admin_data['password'])) {
                // Initialize Admin Panel Session Tokens flags
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['user_id'] = $admin_data['id'];
                $_SESSION['user_name'] = $admin_data['full_name'];

                header("Location: index.php");
                exit;
            } else {
                $error = "Access Denied: Invalid password phrase provided.";
            }
        } else {
            $error = "Access Denied: No administrative profile matches this email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Authentication - Urban Ladder Control Panel</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #FF7043;
            --primary-hover: #E55E35;
            --bg-shade: #1A202C;
            --surface: #FFFFFF;
            --text-dark: #2D3748;
            --text-muted: #A0AEC0;
            --border: #E2E8F0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: var(--bg-shade);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        /* Premium Center-Aligned Login Card Cardboard layout block */
        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--surface);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            box-sizing: border-box;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .brand-header .material-icons {
            font-size: 44px;
            color: var(--primary-orange);
            margin-bottom: 10px;
        }

        .brand-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-dark);
            margin: 0;
            letter-spacing: 0.5px;
        }
        .brand-title span { color: var(--primary-orange); }

        .brand-subtitle {
            font-size: 13px;
            color: #718096;
            margin-top: 5px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 22px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper .material-icons {
            position: absolute;
            left: 14px;
            color: var(--text-muted);
            font-size: 20px;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #CBD5E0;
            border-radius: 6px;
            font-size: 15px;
            outline: none;
            background-color: #FAFBFC;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary-orange);
            background-color: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(255, 112, 67, 0.15);
        }

        .btn-login {
            width: 100%;
            background-color: var(--primary-orange);
            color: #FFFFFF;
            border: none;
            padding: 14px;
            font-size: 15px;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s ease;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            background-color: var(--primary-hover);
        }

        .error-alert {
            background-color: #FFF0F0;
            color: #DC3545;
            border: 1px solid #FED7D7;
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .error-alert .material-icons { font-size: 18px; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand-header">
            <span class="material-icons">admin_panel_settings</span>
            <h1 class="brand-title">Urban<span>Ladder.</span></h1>
            <div class="brand-subtitle">Management Administration Console Portal</div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-alert">
                <span class="material-icons">error_outline</span>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Administrator Email</label>
                <div class="input-wrapper">
                    <span class="material-icons">email</span>
                    <input type="email" name="email" class="form-control" placeholder="admin@urbanladder.com" required autocomplete="off">
                </div>
            </div>

            <div class="form-group">
                <label>Secure Security Code</label>
                <div class="input-wrapper">
                    <span class="material-icons">lock</span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" name="login_admin" class="btn-login">
                <span>Authenticate Credentials</span>
                <span class="material-icons" style="font-size: 18px;">arrow_forward</span>
            </button>
        </form>
    </div>

</body>
</html>