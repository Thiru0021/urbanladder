<?php
// admin/products/edit.php
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$prod_id = intval($_GET['id']);
$error = "";

// 1. Fetch current product layout values
$product_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id = $prod_id"));
if (!$product_data) { header("Location: index.php"); exit; }

// 2. Fetch dropdown mappings lists
$sub_query = "SELECT s.id AS sub_id, s.name AS sub_name, c.name AS cat_name 
              FROM subcategories s INNER JOIN categories c ON s.category_id = c.id
              WHERE s.status = 1 AND c.status = 1 ORDER BY c.name ASC, s.name ASC";
$sub_result = mysqli_query($conn, $sub_query);

// 3. Process Form Changes Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $subcategory_id = intval($_POST['subcategory_id']);
    $master_price = floatval($_POST['master_price']);
    $price = floatval($_POST['price']);
    $discount_percentage = intval($_POST['discount_percentage']);
    $db_image_path = $product_data['image_url']; // Default to keeping old path string

    if (empty($title) || $subcategory_id == 0 || $price <= 0 || $master_price <= 0) {
        $error = "Please provide all required entries accurately.";
    } else {
        // Run update query if a new image file is chosen
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            $file_name = $_FILES['product_image']['name'];
            $file_tmp = $_FILES['product_image']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = array("jpg", "jpeg", "png", "webp", "avif");

            if (in_array($file_ext, $allowed_extensions)) {
                $new_file_name = "prod_" . time() . "_" . uniqid() . "." . $file_ext;
                $upload_destination = "../../assets/images/uploads/" . $new_file_name;

                if (move_uploaded_file($file_tmp, $upload_destination)) {
                    // Delete the old file from storage disk securely
                    if (file_exists("../../" . $product_data['image_url'])) {
                        unlink("../../" . $product_data['image_url']);
                    }
                    $db_image_path = "assets/images/uploads/" . $new_file_name;
                }
            }
        }

        $update_sql = "UPDATE products SET subcategory_id=$subcategory_id, title='$title', description='$description', 
                       price=$price, master_price=$master_price, discount_percentage=$discount_percentage, image_url='$db_image_path' 
                       WHERE id=$prod_id";
        
        if (mysqli_query($conn, $update_sql)) {
            header("Location: index.php?msg=Product catalog metrics synchronized successfully.");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product - Urban Ladder Admin</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../admin-style.css">
    <style>
        /* ========================================================
           ⚙️ TOP NAVBAR EMBEDDED LAYOUT STRUCTURAL STYLES
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

        /* Existing component configurations layout grid boundaries styles */
        .form-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 20px; }
        .form-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; background-color: #FAFBFC; font-size: 15px; box-sizing: border-box; }
        .form-control:focus { border-color: var(--primary-orange); background-color: #FFFFFF; outline: none; }
        textarea.form-control { resize: vertical; min-height: 120px; }
        .btn-save { background: var(--primary-orange); color: #fff; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-cancel { background: #E2E8F0; color: #4A5568; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; text-align: center; line-height: 20px; display: inline-block; }
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
        <a href="../subcategories/subcategories_view.php"><span class="material-icons">account_tree</span>Subcategories</a>
        <a href="index.php" class="active"><span class="material-icons">chair</span>Products</a>
        <a href="../customers_view.php"><span class="material-icons">people</span>Customers</a>
    </div>

    <div class="main-content">
        <h1>Edit Product: <?php echo htmlspecialchars($product_data['prd_id'] ?? $product_data['id']); ?></h1>
        
        <?php if (!empty($error)): ?> <div style="padding: 12px 20px; background-color: #FFF0F0; color: #DC3545; border-radius: 8px; margin-bottom: 25px; font-weight: 500;"><?php echo $error; ?></div> <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-card">
                    <div class="form-group">
                        <label>Product Title *</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($product_data['title']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Product Description</label>
                        <textarea name="description" class="form-control"><?php echo htmlspecialchars($product_data['description']); ?></textarea>
                    </div>
                    <div style="display:flex; gap:15px;">
                        <button type="submit" name="update_product" class="btn-save">Save Changes</button>
                        <a href="index.php" class="btn-cancel">Cancel</a>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-group">
                        <label>Assign Subcategory *</label>
                        <select name="subcategory_id" class="form-control" required>
                            <?php while ($sub = mysqli_fetch_assoc($sub_result)): ?>
                                <option value="<?php echo $sub['sub_id']; ?>" <?php echo ($sub['sub_id'] == $product_data['subcategory_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sub['cat_name'] . ' ➔ ' . $sub['sub_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Master Price (MSRP)</label>
                        <input type="number" step="0.01" name="master_price" class="form-control" value="<?php echo $product_data['master_price']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Our Retail Price</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $product_data['price']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Discount Percentage (%)</label>
                        <input type="number" name="discount_percentage" class="form-control" min="0" max="99" value="<?php echo $product_data['discount_percentage']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Replace Thumbnail (Optional)</label>
                        <input type="file" name="product_image" class="form-control" accept="image/*">
                        <div style="margin-top:10px; font-size:12px; color:var(--text-muted); word-break: break-all;">Current: <?php echo htmlspecialchars($product_data['image_url']); ?></div>
                    </div>
                </div>
            </div>
        </form>
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