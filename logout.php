<?php
// logout.php
session_start();

// 1. Detect if the session being killed belongs to an Administrator
$redirect_target = "login.php?msg=Logged out successfully"; // Default frontend customer login fallback
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $redirect_target = "admin/admin_login.php?msg=Logged out successfully"; // Admin panel fallback
}

// 2. Clear out and completely unset memory tracking session state variables
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// 3. ⚡ ANTI-BROWSER BACK CACHE DESTROY HEADERS
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 

// 4. Redirect to the dynamically detected target screen page
header("Location: " . $redirect_target);
exit;