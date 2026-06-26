<?php
// admin/customers_view.php
session_start();

// Intelligent path resolver: Checks if it's inside a subfolder or directly in the admin folder
if (file_exists('../config/db.php')) {
    require_once '../config/db.php';
} else if (file_exists('../../config/db.php')) {
    require_once '../../config/db.php';
} else {
    die("Database config file could not be located. Check your folder structures!");
}

// FIXED: Using your exact database schema columns: id, full_name, email, created_at
$customer_sql = "SELECT id, full_name, email, created_at FROM users ORDER BY id DESC";
$customers_result = mysqli_query($conn, $customer_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Base Management - Control Panel</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        /* ========================================================
           ⚙️ TOP NAVBAR EMBEDDED DESIGN LAYOUT VALUES
           ======================================================== */
        .admin-top-navbar {
            width: 100%;
            background: #2D3748;
            color: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 0;
            left: 0;
            z-index: 99999;
            font-family: 'Segoe UI', system-ui, sans-serif;
            box-sizing: border-box;
        }
        .nav-container {
            width: 100%;
            padding: 12px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-sizing: border-box;
        }
        .nav-brand-zone { display: flex; align-items: center; gap: 10px; }
        .nav-brand-zone .Control-icon { color: #FF7043; font-size: 26px; }
        .brand-text { font-size: 18px; font-weight: 700; letter-spacing: 0.3px; }
        .panel-tag { font-size: 11px; background: rgba(255, 112, 67, 0.2); color: #FF7043; padding: 2px 8px; border-radius: 4px; font-weight: 600; margin-left: 5px; text-transform: uppercase; }

        .nav-actions-zone { display: flex; align-items: center; gap: 25px; }
        .nav-action-item { position: relative; color: #A0AEC0; cursor: pointer; display: flex; align-items: center; }
        .nav-action-item:hover { color: #ffffff; }
        .action-alert-dot { position: absolute; top: 0; right: 2px; width: 6px; height: 6px; background: #E53E3E; border-radius: 50%; }

        .admin-profile-trigger {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            position: relative;
            padding: 4px 8px;
            border-radius: 6px;
            transition: background 0.2s;
            user-select: none;
        }
        .admin-profile-trigger:hover { background: rgba(255, 255, 255, 0.05); }
        .admin-avatar-mini { width: 34px; height: 34px; border-radius: 50%; border: 2px solid #FF7043; overflow: hidden; }
        .admin-avatar-mini img { width: 100%; height: 100%; object-fit: cover; }
        .admin-user-name { font-size: 14px; font-weight: 600; color: #E2E8F0; }
        .admin-profile-trigger .arrow-icon { font-size: 18px; color: #A0AEC0; transition: transform 0.2s; }
        .admin-profile-trigger.active .arrow-icon { transform: rotate(180deg); }

        .profile-card-dropdown {
            position: absolute;
            top: 130%;
            right: 0;
            width: 290px;
            background: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            display: none; 
            flex-direction: column;
            overflow: hidden;
            z-index: 100000;
        }
        .dropdown-header { display: flex; align-items: center; gap: 14px; padding: 20px; background: #F7FAFC; border-bottom: 1px solid #E2E8F0; text-align: left; }
        .header-avatar { width: 50px; height: 50px; border-radius: 50%; overflow: hidden; border: 2px solid #E2E8F0; flex-shrink: 0; }
        .header-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-fullname { font-size: 15px; font-weight: 700; color: #2D3748; margin-bottom: 2px; }
        .user-role { font-size: 12px; color: #718096; font-weight: 500; }

        .dropdown-body-matrix { padding: 8px 0; }
        .dropdown-menu-item { display: flex; align-items: center; gap: 14px; padding: 12px 20px; text-decoration: none; color: #4A5568; transition: background 0.15s; text-align: left; }
        .dropdown-menu-item:hover { background: #FFF3E0; }
        .dropdown-menu-item:hover .material-icons { color: #FF7043; }
        .dropdown-menu-item .material-icons { color: #A0AEC0; font-size: 22px; }
        .item-text-grid { display: flex; flex-direction: column; }
        .item-title { font-size: 14px; font-weight: 600; color: #2D3748; }
        .item-desc { font-size: 11px; color: #718096; margin-top: 1px; }

        .dropdown-footer-action { border-top: 1px solid #E2E8F0; padding: 8px 0; background: #FAFBFC; }
        .logout-action-btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 0; text-decoration: none; color: #E53E3E; font-size: 14px; font-weight: 600; transition: background 0.15s; }
        .logout-action-btn:hover { background: #FFF5F5; }
        .logout-action-btn .material-icons { font-size: 18px; }

        body { padding-top: 0; }
        .sidebar { top: 65px; height: calc(100vh - 65px); }
        .main-content { margin-top: 20px; }

        /* Datagrid Table Component Properties mapping styles */
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .admin-table th, .admin-table td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        .admin-table th { background-color: #FAFBFC; color: var(--text-muted); font-weight: 600; font-size: 14px; }
        
        .customer-initial-avatar { width: 40px; height: 40px; border-radius: 50%; background-color: var(--primary-light); color: var(--primary-orange); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; border: 1px solid var(--border-color); }
        .badge { padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-block; }
        .badge-active { background: #E2FBE8; color: #1E7E34; }
    </style>
</head>
<body>

    <div class="admin-top-navbar">
        <div class="nav-container">
            <div class="nav-brand-zone">
                <span class="material-icons Control-icon">dashboard</span>
                <span class="brand-text">Urban Ladder <span class="panel-tag">Admin Panel</span></span>
            </div>

            <div class="nav-actions-zone">
                <div class="nav-action-item" title="System Notifications">
                    <span class="material-icons">notifications_none</span>
                    <span class="action-alert-dot"></span>
                </div>
                
                <div class="admin-profile-trigger" id="profileDropdownTrigger">
                    <div class="admin-avatar-mini">
                        <img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=80&h=80&q=80" alt="Admin Profile">
                    </div>
                    <span class="admin-user-name">Thirumalai Ravi</span>
                    <span class="material-icons arrow-icon">expand_more</span>

                    <div class="profile-card-dropdown" id="adminProfileMenu">
                        <div class="dropdown-header">
                            <div class="header-avatar">
                                <img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=100&h=100&q=80" alt="Admin Avatar">
                            </div>
                            <div class="header-info">
                                <div class="user-fullname">Thirumalai Ravi</div>
                                <div class="user-role">Super Administrator</div>
                            </div>
                        </div>
                        <div class="dropdown-body-matrix">
                            <a href="admin_profile.php" class="dropdown-menu-item">
                                <span class="material-icons">manage_accounts</span>
                                <div class="item-text-grid">
                                    <span class="item-title">My Profile</span>
                                    <span class="item-desc">View personal account logs</span>
                                </div>
                            </a>
                            <a href="admin_profile.php" class="dropdown-menu-item">
                                <span class="material-icons">settings</span>
                                <div class="item-text-grid">
                                    <span class="item-title">Admin Settings</span>
                                    <span class="item-desc">Configure system preferences</span>
                                </div>
                            </a>
                        </div>
                        <div class="dropdown-footer-action">
                            <a href="../logout.php" class="logout-action-btn">
                                <span class="material-icons">logout</span>
                                <span>Sign Out Securely</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar">
        <div class="brand">Urban<span>Ladder.</span></div>
        <a href="index.php"><span class="material-icons">dashboard</span>Dashboard</a>
        <a href="categories/categories_view.php"><span class="material-icons">folder</span>Categories</a>
        <a href="subcategories/subcategories_view.php"><span class="material-icons">account_tree</span>Subcategories</a>
        <a href="products/index.php"><span class="material-icons">chair</span>Products</a>
        <a href="customers_view.php" class="active"><span class="material-icons">people</span>Customers</a>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1>Registered Customer Base</h1>
            <div style="color: var(--text-muted); font-size: 14px;">Monitor store user profiles and accounts activity records</div>
        </div>

        <div class="content-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Avatar</th>
                        <th>User ID</th>
                        <th>Customer Name</th>
                        <th>Email Contact</th>
                        <th>Registration Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($customers_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($customers_result)): ?>
                            <tr>
                                <td>
                                    <div class="customer-initial-avatar">
                                        <?php echo strtoupper(substr($row['full_name'], 0, 1)); ?>
                                    </div>
                                </td>
                                <td><strong>#USR-<?php echo $row['id']; ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                <td style="color: #2B6CB0;"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <span class="badge badge-active">Verified User</span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">No registered user accounts are logged inside the system database.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    document.getElementById('profileDropdownTrigger').addEventListener('click', function(e) {
        e.stopPropagation(); 
        const menu = document.getElementById('adminProfileMenu');
        const trigger = this;
        
        if (menu.style.display === 'flex') {
            menu.style.display = 'none';
            trigger.classList.remove('active');
        } else {
            menu.style.display = 'flex';
            trigger.classList.add('active');
        }
    });

    window.addEventListener('click', function() {
        const menu = document.getElementById('adminProfileMenu');
        const trigger = document.getElementById('profileDropdownTrigger');
        if (menu) {
            menu.style.display = 'none';
            if (trigger) trigger.classList.remove('active');
        }
    });
    </script>
</body>
</html>