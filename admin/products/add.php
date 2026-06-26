<?php
// admin/products/add.php
require_once '../../config/db.php';

$error = "";

// 1. Fetch ALL active top-level Categories for the initial master dropdown box selector
$cat_query = "SELECT id, name, cat_id FROM categories WHERE status = 1 ORDER BY name ASC";
$cat_result = mysqli_query($conn, $cat_query);

// 2. Process Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $subcategory_id = intval($_POST['subcategory_id']); 
    $master_price = floatval($_POST['master_price']);
    $price = floatval($_POST['price']);
    $discount_percentage = intval($_POST['discount_percentage']);

    if (empty($title) || $subcategory_id == 0 || $price <= 0 || $master_price <= 0) {
        $error = "Please provide all required product values accurately.";
    } else {
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            $file_name = $_FILES['product_image']['name'];
            $file_tmp = $_FILES['product_image']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_extensions = array("jpg", "jpeg", "png", "webp", "avif");

            if (in_array($file_ext, $allowed_extensions)) {
                $new_file_name = "prod_" . time() . "_" . uniqid() . "." . $file_ext;
                
                $target_dir = "../../assets/images/uploads/";
                $upload_destination = $target_dir . $new_file_name;
                $db_image_path = "assets/images/uploads/" . $new_file_name;
                
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true); 
                }

                if (move_uploaded_file($file_tmp, $upload_destination)) {
                    $insert_sql = "INSERT INTO products (subcategory_id, title, description, price, master_price, discount_percentage, image_url, status) 
                                   VALUES ($subcategory_id, '$title', '$description', $price, $master_price, $discount_percentage, '$db_image_path', 1)";
                    if (mysqli_query($conn, $insert_sql)) {
                        header("Location: index.php?msg=Product published successfully!");
                        exit;
                    } else {
                        $error = "Database Error: " . mysqli_error($conn);
                    }
                } else { 
                    $error = "Failed to store file image to destination disk."; 
                }
            } else { 
                $error = "Invalid format. Use JPG, JPEG, PNG, WEBP, or AVIF."; 
            }
        } else { 
            $error = "Please include a valid display thumbnail."; 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product - Urban Ladder Admin</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../admin-style.css">
    <style>
        /* ========================================================
           ⚙️ TOP NAVBAR EMBEDDED LAYOUT RULES
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

        /* Standard view layout styles */
        .form-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 20px; }
        .form-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; background-color: #FAFBFC; font-size: 15px; box-sizing: border-box; }
        .form-control:focus { border-color: var(--primary-orange); background-color: #FFFFFF; outline: none; }
        textarea.form-control { resize: vertical; min-height: 120px; }
        .btn-save { background: var(--primary-orange); color: #fff; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-cancel { background: #E2E8F0; color: #4A5568; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; text-align: center; }
        .alert-error { padding: 12px 20px; background-color: #FFF0F0; color: #DC3545; border-radius: 8px; margin-bottom: 25px; font-weight: 500; }
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
        <h1>Add New Product</h1>
        <?php if (!empty($error)): ?> <div class="alert-error"><?php echo $error; ?></div> <?php endif; ?>

        <form action="add.php" method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-card">
                    <div class="form-group">
                        <label>Product Title *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Product Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>
                    <div style="display:flex; gap:15px;">
                        <button type="submit" name="add_product" class="btn-save">Publish Product</button>
                        <a href="index.php" class="btn-cancel">Cancel</a>
                    </div>
                </div>

                <div class="form-card" style="height: fit-content;">
                    <div class="form-group">
                        <label for="category_select">Select Category *</label>
                        <select id="category_select" class="form-control" required>
                            <option value="">-- Choose Category --</option>
                            <?php while ($cat = mysqli_fetch_assoc($cat_result)): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['cat_id'] . ' ➔ ' . $cat['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subcategory_select">Select Subcategory *</label>
                        <select id="subcategory_select" name="subcategory_id" class="form-control" required disabled>
                            <option value="">-- Choose Category First --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Master MSRP Price (INR) *</label>
                        <input type="number" step="0.01" name="master_price" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Retail Selling Price (INR) *</label>
                        <input type="number" step="0.01" name="price" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Discount Percentage (%)</label>
                        <input type="number" name="discount_percentage" class="form-control" min="0" max="99" value="0">
                    </div>
                    <div class="form-group">
                        <label>Product Thumbnail *</label>
                        <input type="file" name="product_image" class="form-control" accept="image/*" required>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('category_select').addEventListener('change', function() {
        const categoryId = this.value;
        const subcategoryDropdown = document.getElementById('subcategory_select');
        
        subcategoryDropdown.innerHTML = '<option value="">-- Choose Subcategory --</option>';
        
        if (categoryId === "") {
            subcategoryDropdown.disabled = true;
            return;
        }

        fetch(`get_subcategories.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    data.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub.id; 
                        option.textContent = `${sub.sub_id} ➔ ${sub.name}`;
                        subcategoryDropdown.appendChild(option);
                    });
                    subcategoryDropdown.disabled = false;
                } else {
                    subcategoryDropdown.innerHTML = '<option value="">No subcategories found</option>';
                    subcategoryDropdown.disabled = true;
                }
            })
            .catch(error => {
                console.error('AJAX error context:', error);
            });
    });

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