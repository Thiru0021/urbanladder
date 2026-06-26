<?php
// admin/subcategories/subcategories_view.php
require_once '../../config/db.php';

// 1. Handle Status Toggle Action
if (isset($_GET['action']) && $_GET['action'] == 'toggle' && isset($_GET['id'])) {
    $sub_id = intval($_GET['id']);
    $current_status = intval($_GET['status']);
    $new_status = ($current_status == 1) ? 0 : 1;

    $toggle_query = "UPDATE subcategories SET status = $new_status WHERE id = $sub_id";
    if (mysqli_query($conn, $toggle_query)) {
        header("Location: subcategories_view.php?msg=Subcategory status updated");
        exit;
    }
}

// 2. Handle Delete Action with Media Asset Cleanup
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $sub_id = intval($_GET['id']);
    
    // Fetch image URL to remove the physical file from your local storage disk
    $img_fetch = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image_url FROM subcategories WHERE id = $sub_id"));
    if ($img_fetch && !empty($img_fetch['image_url'])) {
        $physical_file = "../../" . $img_fetch['image_url'];
        if (file_exists($physical_file)) {
            unlink($physical_file);
        }
    }

    $delete_query = "DELETE FROM subcategories WHERE id = $sub_id";
    if (mysqli_query($conn, $delete_query)) {
        header("Location: subcategories_view.php?msg=Subcategory removed successfully");
        exit;
    }
}

// 3. Handle Add New Subcategory Form Submission (With File Upload Processing)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subcategory'])) {
    $subcategory_name = mysqli_real_escape_string($conn, $_POST['subcategory_name']);
    $category_id = intval($_POST['category_id']);
    $db_image_path = "";

    if (!empty($subcategory_name) && $category_id > 0) {
        
        // Handle file image upload logic
        if (isset($_FILES['sub_image']) && $_FILES['sub_image']['error'] == 0) {
            $file_name = $_FILES['sub_image']['name'];
            $file_tmp = $_FILES['sub_image']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_extensions = array("jpg", "jpeg", "png", "webp", "avif");

            if (in_array($file_ext, $allowed_extensions)) {
                $new_file_name = "sub_" . time() . "_" . uniqid() . "." . $file_ext;
                $upload_destination = "../../assets/images/uploads/" . $new_file_name;
                $target_dir = "../../assets/images/uploads/";
                
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                if (move_uploaded_file($file_tmp, $upload_destination)) {
                    // Store the relational reference path cleanly for database tracking
                    $db_image_path = "assets/images/uploads/" . $new_file_name;
                }
            }
        }

        // We leave sub_id out of the INSERT because the database trigger handles it automatically!
        $insert_query = "INSERT INTO subcategories (category_id, name, image_url, status) 
                         VALUES ($category_id, '$subcategory_name', '$db_image_path', 1)";
        if (mysqli_query($conn, $insert_query)) {
            header("Location: subcategories_view.php?msg=New subcategory added successfully");
            exit;
        }
    }
}

// 4. Fetch active parent categories for dropdown selector
$parent_categories = mysqli_query($conn, "SELECT * FROM categories WHERE status = 1 ORDER BY name ASC");

// 5. Fetch all subcategories for display loop
$sub_query = "SELECT subcategories.*, categories.name AS parent_name 
              FROM subcategories 
              INNER JOIN categories ON subcategories.category_id = categories.id 
              ORDER BY subcategories.id DESC";
$sub_result = mysqli_query($conn, $sub_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subcategories - Urban Ladder Admin</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../admin-style.css">
    <style>
        /* ========================================================
           ⚙️ TOP NAVBAR EMBEDDED DESIGN METRICS
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

        /* Existing Styles Structure Grid Maps */
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .admin-table th, .admin-table td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        .admin-table th { background-color: #FAFBFC; color: var(--text-muted); font-weight: 600; font-size: 14px; }
        
        .sub-thumb { width: 55px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color); background: #f5f5f5; display: block; }
        
        .form-row { display: flex; gap: 20px; margin-top: 15px; flex-wrap: wrap; align-items: flex-end; }
        .form-control-group { flex: 1; min-width: 200px; }
        .form-control-group label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; background-color: #FAFBFC; outline: none; font-size: 14px; box-sizing: border-box; }
        .form-control:focus { border-color: var(--primary-orange); background-color: #FFFFFF; }
        .btn-submit { background: var(--primary-orange); color: #FFFFFF; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; height: 45px; }
        .badge { padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; }
        .badge-active { background: #E2FBE8; color: #1E7E34; }
        .badge-inactive { background: #FFF0F0; color: #DC3545; }
        .actions-cell { display: flex; gap: 15px; align-items: center; }
        .action-link { color: var(--text-muted); text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; transition: 0.2s; }
        .action-link:hover { color: var(--primary-orange); }
        .action-delete:hover { color: #DC3545; }
        .alert-msg { padding: 12px 20px; background-color: var(--primary-light); color: var(--primary-orange); border-radius: 8px; margin-bottom: 25px; font-weight: 500; }
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
                <div class="nav-action-item" title="System Notifications"><span class="material-icons">notifications_none</span><span class="action-alert-dot"></span></div>
                <div class="admin-profile-trigger" id="profileDropdownTrigger">
                    <div class="admin-avatar-mini"><img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=80&h=80&q=80" alt="Admin Profile"></div>
                    <span class="admin-user-name">Thirumalai Ravi</span>
                    <span class="material-icons arrow-icon">expand_more</span>
                    <div class="profile-card-dropdown" id="adminProfileMenu">
                        <div class="dropdown-header">
                            <div class="header-avatar"><img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=100&h=100&q=80" alt="Admin Avatar"></div>
                            <div class="header-info"><div class="user-fullname">Thirumalai Ravi</div><div class="user-role">Super Administrator</div></div>
                        </div>
                        <div class="dropdown-body-matrix">
                            <a href="../admin_profile.php" class="dropdown-menu-item"><span class="material-icons">manage_accounts</span><div class="item-text-grid"><span class="item-title">My Profile</span><span class="item-desc">View personal account logs</span></div></a>
                            <a href="../admin_profile.php" class="dropdown-menu-item"><span class="material-icons">settings</span><div class="item-text-grid"><span class="item-title">Admin Settings</span><span class="item-desc">Configure system preferences</span></div></a>
                        </div>
                        <div class="dropdown-footer-action"><a href="../../logout.php" class="logout-action-btn"><span class="material-icons">logout</span><span>Sign Out Securely</span></a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar">
        <div class="brand">Urban<span>Ladder.</span></div>
        <a href="../index.php"><span class="material-icons">dashboard</span>Dashboard</a>
        <a href="../categories/categories_view.php"><span class="material-icons">folder</span>Categories</a>
        <a href="subcategories_view.php" class="active"><span class="material-icons">account_tree</span>Subcategories</a>
        <a href="../products/index.php"><span class="material-icons">chair</span>Products</a>
        <a href="../customers_view.php"><span class="material-icons">people</span>Customers</a>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1>Subcategories Management</h1>
            <div style="color: var(--text-muted); font-size: 14px;">Structure product catalog sub-menus</div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert-msg"><?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>

        <div class="content-card" style="margin-bottom: 30px;">
            <h2 style="font-size: 18px;">Add New Subcategory</h2>
            <form action="subcategories_view.php" method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-control-group">
                        <label for="category_id">Parent Category</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">-- Choose Main Category --</option>
                            <?php while($cat = mysqli_fetch_assoc($parent_categories)): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-control-group">
                        <label for="subcategory_name">Subcategory Name</label>
                        <input type="text" id="subcategory_name" name="subcategory_name" class="form-control" placeholder="e.g., Study Tables" required>
                    </div>

                    <div class="form-control-group">
                        <label for="sub_image">Subcategory Image</label>
                        <input type="file" id="sub_image" name="sub_image" class="form-control" accept="image/*" required>
                    </div>

                    <button type="submit" name="add_subcategory" class="btn-submit">Save Subcategory</button>
                </div>
            </form>
        </div>

        <div class="content-card">
            <h2 style="font-size: 18px;">Existing Subcategories</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Subcategory Name</th>
                        <th>Parent Category</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($sub_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($sub_result)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['sub_id']); ?></strong></td>
                                <td>
                                    <?php if(!empty($row['image_url'])): ?>
                                        <img src="../../<?php echo htmlspecialchars($row['image_url']); ?>" class="sub-thumb" alt="">
                                    <?php else: ?>
                                        <div class="sub-thumb" style="display:flex; align-items:center; justify-content:center; color:var(--text-muted); font-size:11px;">No Img</div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['parent_name']); ?></td>
                                <td>
                                    <?php if ($row['status'] == 1): ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td align="right">
                                    <div class="actions-cell" style="justify-content: flex-end;">
                                        <a href="subcategories_view.php?action=toggle&id=<?php echo $row['id']; ?>&status=<?php echo $row['status']; ?>" class="action-link" title="Toggle Status">
                                            <span class="material-icons">visibility</span>
                                        </a>
                                        <a href="subcategories_edit.php?id=<?php echo $row['id']; ?>" class="action-link" title="Edit Subcategory">
                                            <span class="material-icons">edit</span>
                                        </a>
                                        <a href="subcategories_view.php?action=delete&id=<?php echo $row['id']; ?>" class="action-link action-delete" title="Delete Subcategory" onclick="return confirm('Wipe out this subcategory? All linked store products will be deleted as well.');">
                                            <span class="material-icons">delete</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">No subcategories created yet.</td>
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