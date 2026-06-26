<?php
// admin/subcategories/subcategories_edit.php
require_once '../../config/db.php';

if (!isset($_GET['id'])) {
    header("Location: subcategories_view.php");
    exit;
}

$sub_id = intval($_GET['id']);
$error = "";

// 1. Process Update Form Data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_subcategory'])) {
    $subcategory_name = mysqli_real_escape_string($conn, $_POST['subcategory_name']);
    $category_id = intval($_POST['category_id']);
    
    // Fetch current details to know the old image name (for potential deletion or fallback)
    $current_sub_query = mysqli_query($conn, "SELECT image FROM subcategories WHERE id = $sub_id");
    $current_sub_data = mysqli_fetch_assoc($current_sub_query);
    $image_name = $current_sub_data['image']; // Default to old image

    if (!empty($subcategory_name) && $category_id > 0) {
        
        // Handle Image Upload if a file is provided
        if (isset($_FILES['subcategory_image']) && $_FILES['subcategory_image']['error'] == 0) {
            $file_name = $_FILES['subcategory_image']['name'];
            $file_tmp  = $_FILES['subcategory_image']['tmp_name'];
            $file_size = $_FILES['subcategory_image']['size'];
            
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = array("jpg", "jpeg", "png", "webp");
            
            if (in_array($file_ext, $allowed_exts)) {
                if ($file_size <= 2097152) { // 2MB Limit
                    // Create a unique name to avoid overwriting existing files
                    $new_image_name = time() . '_' . uniqid() . '.' . $file_ext;
                    $upload_dir = '../../uploads/';
                    
                    // Create folder if it doesn't exist
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    if (move_uploaded_file($file_tmp, $upload_dir . $new_image_name)) {
                        // Delete old image file if it exists and isn't empty
                        if (!empty($image_name) && file_exists($upload_dir . $image_name)) {
                            unlink($upload_dir . $image_name);
                        }
                        $image_name = $new_image_name; // Update variable to save in DB
                    } else {
                        $error = "Failed to upload image file.";
                    }
                } else {
                    $error = "Image size must be less than 2MB.";
                }
            } else {
                $error = "Invalid file type. Only JPG, JPEG, PNG, and WEBP are allowed.";
            }
        }

        // Proceed to update if there are no errors
        if (empty($error)) {
            $update_query = "UPDATE subcategories SET category_id = $category_id, name = '$subcategory_name', image = '$image_name' WHERE id = $sub_id";
            if (mysqli_query($conn, $update_query)) {
                header("Location: subcategories_view.php?msg=Subcategory details updated successfully");
                exit;
            } else {
                $error = "Error updating database: " . mysqli_error($conn);
            }
        }
    } else {
        $error = "All fields are required.";
    }
}

// 2. Fetch current subcategory details
$current_sub = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM subcategories WHERE id = $sub_id"));
if (!$current_sub) {
    header("Location: subcategories_view.php");
    exit;
}

// 3. Fetch active categories for the selector dropdown
$parent_categories = mysqli_query($conn, "SELECT * FROM categories WHERE status = 1 ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Subcategory - Urban Ladder Admin</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../admin-style.css">
    <style>
        /* ========================================================
           ⚙️ TOP NAVBAR EMBEDDED LAYOUT OVERRIDES
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

        /* Standard Local Style Elements Configuration */
        .form-container { max-width: 500px; margin-top: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-control { width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; background-color: #FAFBFC; font-size: 15px; box-sizing: border-box; }
        .form-control:focus { border-color: var(--primary-orange); background-color: #FFFFFF; outline: none; }
        .btn-group { display: flex; gap: 12px; margin-top: 25px; }
        .btn-save { background: var(--primary-orange); color: #FFFFFF; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-cancel { background: #E2E8F0; color: #4A5568; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; text-align: center; line-height: 20px; display: inline-block; }
        
        .image-preview-wrapper { display: flex; align-items: center; gap: 15px; margin-top: 10px; }
        .img-preview { width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid #E2E8F0; background: #F7FAFC; }
        .error-banner { background-color: #FED7D7; color: #C53030; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; font-size: 14px; }
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
                            <a href="../admin_profile.php" class="dropdown-menu-item">
                                <span class="material-icons">manage_accounts</span>
                                <div class="item-text-grid">
                                    <span class="item-title">My Profile</span>
                                    <span class="item-desc">View personal account logs</span>
                                </div>
                            </a>
                            <a href="../admin_profile.php" class="dropdown-menu-item">
                                <span class="material-icons">settings</span>
                                <div class="item-text-grid">
                                    <span class="item-title">Admin Settings</span>
                                    <span class="item-desc">Configure system preferences</span>
                                </div>
                            </a>
                        </div>
                        <div class="dropdown-footer-action">
                            <a href="../../logout.php" class="logout-action-btn">
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
        <a href="../index.php"><span class="material-icons">dashboard</span>Dashboard</a>
        <a href="../categories/categories_view.php"><span class="material-icons">folder</span>Categories</a>
        <a href="subcategories_view.php" class="active"><span class="material-icons">account_tree</span>Subcategories</a>
        <a href="../products/index.php"><span class="material-icons">chair</span>Products</a>
        <a href="../customers_view.php"><span class="material-icons">people</span>Customers</a>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1>Edit Subcategory</h1>
            <div style="color: var(--text-muted); font-size: 14px;">Modify placement structures</div>
        </div>

        <div class="content-card form-container">
            
            <?php if (!empty($error)): ?>
                <div class="error-banner"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="category_id">Parent Category</label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <?php while($cat = mysqli_fetch_assoc($parent_categories)): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $current_sub['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subcategory_name">Subcategory Name</label>
                    <input type="text" id="subcategory_name" name="subcategory_name" class="form-control" value="<?php echo htmlspecialchars($current_sub['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="subcategory_image">Subcategory Image</label>
                    <input type="file" id="subcategory_image" name="subcategory_image" class="form-control" accept="image/*">
                    
                    <div class="image-preview-wrapper">
                        <?php 
                        $image_path = (!empty($current_sub['image']) && file_exists('../../uploads/' . $current_sub['image'])) 
                                      ? '../../uploads/' . $current_sub['image'] 
                                      : 'https://via.placeholder.com/80?text=No+Image';
                        ?>
                        <img id="preview" src="<?php echo $image_path; ?>" alt="Preview" class="img-preview">
                        <small style="color: var(--text-muted); font-size: 12px; display: block; margin-top: 4px;">Leave empty to keep current image. Max size: 2MB (JPG, PNG, WEBP)</small>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" name="update_subcategory" class="btn-save">Update Changes</button>
                    <a href="subcategories_view.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('subcategory_image').onchange = function (evt) {
            const [file] = this.files;
            if (file) {
                document.getElementById('preview').src = URL.createObjectURL(file);
            }
        };

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