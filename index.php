<?php 
// index.php
session_start();
require_once 'config/db.php'; 

// 1. ⚡ INTERNAL AJAX WISHLIST PROCESSOR LAYER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_wish') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'not_logged_in']);
        exit;
    }
    
    $u_id = intval($_SESSION['user_id']);
    $p_id = intval($_POST['product_id']);
    
    // Check wishlist state
    $check = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id = $u_id AND product_id = $p_id");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM wishlist WHERE user_id = $u_id AND product_id = $p_id");
        echo json_encode(['status' => 'removed']);
    } else {
        mysqli_query($conn, "INSERT INTO wishlist (user_id, product_id) VALUES ($u_id, $p_id)");
        echo json_encode(['status' => 'added']);
    }
    exit;
}

// Anti-browser back cache cleaner
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? intval($_SESSION['user_id']) : 0;

// 2. Fetch Wishlist Counter Metrics
$total_wishes = 0;
if ($is_logged_in) {
    $wish_count_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(id) AS total FROM wishlist WHERE user_id = $user_id"));
    $total_wishes = $wish_count_res['total'] ?? 0;
}

// 3. 🔍 LIVE SEARCH FILTER LOGIC
$search_query = "";
$search_where = "";
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = mysqli_real_escape_string($conn, trim($_GET['search']));
    $search_where = " AND (p.title LIKE '%$search_query%' OR p.description LIKE '%$search_query%') ";
}

// 4. Fetch Subcategories for Showcase Slider Circles
$subcategories_result = mysqli_query($conn, "SELECT name, image, image_url, id FROM subcategories WHERE status = 1 ORDER BY id DESC LIMIT 14");

// 5. Fetch Dynamic Active Products Feed Mapping Matrix
$product_query = "SELECT p.id, p.title, p.description, p.price, p.master_price, p.image_url, w.id AS is_wishlisted 
                  FROM products p 
                  LEFT JOIN wishlist w ON p.id = w.product_id AND w.user_id = $user_id 
                  WHERE p.status = 1 $search_where ORDER BY p.id DESC LIMIT 12";
$products_result = mysqli_query($conn, $product_query);

$top_announcement = "Additional up to ₹10,000 off. Use code EXTRA10K | Limited-time deal - Shop now!";
$banner_title = "Full House Fiesta";
$banner_discount = "up to 70% off";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Furniture Online - Urban Ladder Replica</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <style>
        :root {
            --primary-orange: #FF7043;
            --primary-light: #FFF3E0;
            --text-main: #2D3748;
            --text-muted: #718096;
            --border-color: #E2E8F0;
        }
        body { margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; color: var(--text-main); background-color: #FAFBFC; }
        .container { width: 88%; max-width: 1200px; margin: 0 auto; }
        
        /* Top Navigation Header Ribbon */
        .top-bar { background: #333333; color: #ffffff; font-size: 12px; padding: 10px 0; }
        .top-bar-content { display: flex; justify-content: space-between; align-items: center; }
        .store-link { color: var(--primary-orange); cursor: pointer; font-weight: 600; }
        .top-right-links a { color: #ffffff; text-decoration: none; margin-left: 15px; }

        /* Main Unified Branding Block Header */
        .main-header { background: #ffffff; border-bottom: 1px solid var(--border-color); padding: 10px 0; }
        .header-grid { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .logo img { height: 42px; display: block; }
        .services-nav { display: flex; gap: 20px; }
        .services-nav a { text-decoration: none; color: var(--text-main); font-weight: 700; font-size: 14px; white-space: nowrap; }
        .services-nav a.active { color: var(--primary-orange); }

        /* Functional Search Box Wrapper Design Box Rules */
        .search-form-box { flex-grow: 1; max-width: 420px; margin: 0 15px; }
        .search-form-box input { width: 100%; padding: 10px 18px; border: 1px solid #CBD5E0; border-radius: 20px; outline: none; font-size: 14px; box-sizing: border-box; }
        .search-form-box input:focus { border-color: var(--primary-orange); background: #fff; }

        .utility-icons { display: flex; gap: 22px; align-items: center; }
        .icon-btn { position: relative; cursor: pointer; text-decoration: none; color: #333; display: inline-flex; align-items: center; }
        .badge-count { position: absolute; top: -8px; right: -8px; background: var(--primary-orange); color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: 700; }
        .user-label { font-size: 13px; color: #4A5568; font-weight: 600; margin-right: -5px; }

        /* Premium Mega Dropdown Navbar Styles */
        .category-bar { background: #ffffff; border-bottom: 1px solid var(--border-color); position: relative; z-index: 9999; }
        .category-links { display: flex; justify-content: flex-start; gap: 25px; flex-wrap: nowrap; }
        .nav-item { position: relative; padding: 15px 0; }
        .nav-link { text-decoration: none; color: #2D3748; font-size: 13px; font-weight: 600; transition: color 0.2s ease; }
        .nav-link.highlight { color: var(--primary-orange); }
        .nav-item:hover .nav-link { color: var(--primary-orange); }
        
        /* Expanded Multi-Column Panel Architecture */
        .dropdown-panel { 
            position: absolute; 
            top: 100%; 
            left: 0; 
            background: #ffffff; 
            width: 820px; 
            box-shadow: 0 12px 24px rgba(0,0,0,0.12); 
            border: 1px solid var(--border-color); 
            display: none; 
            padding: 24px; 
            gap: 25px; 
            z-index: 10000; 
            border-radius: 0 0 6px 6px;
        }
        .nav-item:hover .dropdown-panel { display: flex; }
        .menu-column { flex: 1; min-width: 130px; }
        .menu-column h4 { font-size: 13px; font-weight: 700; color: #1A202C; margin: 0 0 12px 0; border-bottom: 1px solid #EDF2F7; padding-bottom: 6px; text-transform: uppercase; letter-spacing: 0.3px; }
        .menu-column a { display: block; padding: 5px 0 !important; color: #4A5568; text-decoration: none; font-size: 12px; font-weight: 500; }
        .menu-column a:hover { background: none !important; color: var(--primary-orange) !important; }
        
        /* Dropdown Feature Item Promotion Block Styles */
        .menu-promo-card { width: 210px; background: #FAFBFC; border-radius: 4px; overflow: hidden; border: 1px solid #E2E8F0; padding: 8px; text-align: center; display: flex; flex-direction: column; justify-content: space-between; }
        .menu-promo-card img { width: 100%; height: 135px; object-fit: cover; border-radius: 3px; }
        .menu-promo-card .promo-tag { font-size: 13px; font-weight: 700; margin-top: 8px; color: #2D3748; line-height: 1.3; }

        /* Hero Showcase Header Elements */
        .hero-section { height: 360px; background-image: linear-gradient(rgba(0,0,0,0.15), rgba(0,0,0,0.15)), url('https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=1200&q=80'); background-size: cover; background-position: center; display: flex; align-items: center; justify-content: center; color: #ffffff; text-align: center; }
        .hero-title { font-size: 42px; margin: 10px 0; font-weight: 800; text-shadow: 0 2px 4px rgba(0,0,0,0.4); }
        .discount-badge { background: var(--primary-orange); display: inline-block; padding: 6px 16px; border-radius: 4px; font-weight: 700; font-size: 16px; margin-bottom: 15px; }
        .cta-btn { display: inline-block; background: #ffffff; color: #333; padding: 12px 28px; text-decoration: none; font-weight: 700; border-radius: 4px; }
        .urgency-bar { background: #5D4037; color: #ffffff; text-align: center; padding: 12px 0; font-weight: 600; letter-spacing: 1px; }

        /* Showcase Carousel Circles System Styles */
        .category-showcase { padding: 40px 0 20px 0; }
        .section-title { font-size: 22px; font-weight: 700; color: #2D3748; margin-bottom: 24px; text-align: center; }
        .circle-grid { display: flex; justify-content: center; gap: 24px; flex-wrap: wrap; }
        .circle-item { display: flex; flex-direction: column; align-items: center; width: 105px; text-decoration: none; color: #4A5568; margin-bottom: 15px; }
        .img-wrapper { width: 95px; height: 95px; border-radius: 50%; background: #F7FAFC; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; margin-bottom: 10px; overflow: hidden; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .circle-item:hover .img-wrapper { transform: translateY(-3px); box-shadow: 0 4px 10px rgba(0,0,0,0.06); }
        .img-wrapper img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .circle-item span { font-size: 12px; font-weight: 600; text-align: center; line-height: 1.3; }

        /* Bank Discount Promo Strips Framework */
        .offers-section { margin: 25px 0 45px 0; }
        .offers-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(48%, 1fr)); gap: 20px; }
        .offer-card { background: #ffffff; border: 1px dashed #CBD5E0; border-radius: 4px; padding: 16px 20px; display: flex; align-items: center; gap: 20px; }
        .offer-bank-logo { width: 65px; height: 35px; object-fit: contain; }
        .offer-details { font-size: 14px; color: #2D3748; font-weight: 500; line-height: 1.4; }
        .offer-details strong { color: #1A202C; font-weight: 700; }

        /* Core Catalog Products Feed Rules */
        .product-section { padding: 20px 0 60px; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(265px, 1fr)); gap: 30px; }
        .product-card { background: #ffffff; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; position: relative; transition: box-shadow 0.2s ease; }
        .product-card:hover { box-shadow: 0 6px 16px rgba(0,0,0,0.06); }
        .card-img-holder { width: 100%; height: 210px; background: #FAFBFC; position: relative; }
        .card-img-holder img { width: 100%; height: 100%; object-fit: cover; }
        
        .wishlist-heart { position: absolute; top: 15px; right: 15px; background: #ffffff; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.08); cursor: pointer; border: none; outline: none; z-index: 10; }
        .wishlist-heart .material-icons { font-size: 20px; color: #A0AEC0; }
        .wishlist-heart.active .material-icons { color: #E53E3E; }
        
        .card-details { padding: 18px; }
        .prod-title { font-size: 16px; font-weight: 600; color: #2D3748; margin: 0 0 4px 0; text-transform: capitalize; }
        .prod-desc { font-size: 13px; color: var(--text-muted); margin: 0 0 12px 0; line-height: 1.4; }
        .price-row { display: flex; gap: 10px; align-items: center; }
        .sale-price { font-size: 16px; font-weight: 700; color: var(--primary-orange); }
        .old-price { font-size: 13px; text-decoration: line-through; color: #A0AEC0; }
        .empty-notice { color: var(--text-muted); font-size: 14px; padding: 40px; text-align: center; width: 100%; grid-column: 1/-1; }

        /* Authentic Footer Elements Infrastructure */
        .main-footer { background: #F9FAFB; border-top: 1px solid var(--border-color); padding: 60px 0 30px 0; margin-top: 40px; font-size: 13px; color: #4A5568; }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 40px; margin-bottom: 40px; }
        .footer-col h4 { font-size: 14px; font-weight: 700; color: #2D3748; margin: 0 0 16px 0; letter-spacing: 0.3px; }
        .footer-col ul { list-style: none; padding: 0; margin: 0; }
        .footer-col ul li { margin-bottom: 11px; }
        .footer-col ul li a { text-decoration: none; color: #718096; transition: color 0.15s ease; }
        .footer-col ul li a:hover { color: var(--primary-orange); }
        .app-download-btns { display: flex; flex-direction: column; gap: 12px; margin-top: 5px; }
        .app-download-btns img { height: 38px; width: 135px; object-fit: contain; cursor: pointer; }
        .footer-bottom { border-top: 1px solid #E2E8F0; padding-top: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .social-icons { display: flex; gap: 16px; }
        .social-icons a { color: #718096; text-decoration: none; font-size: 13px; font-weight: 600; }
        .social-icons a:hover { color: var(--primary-orange); }
    </style>
</head>
<body>

    <div class="top-bar">
        <div class="container top-bar-content">
            <span>Nearest Store - <strong class="store-link">UL Store Ghatkopar Rcity</strong></span>
            <span class="promo-text"><?php echo htmlspecialchars($top_announcement); ?></span>
            <div class="top-right-links">
                <a href="#">Gift Cards</a>
                <a href="#">Become a Franchisee</a>
                <a href="#">Help</a>
            </div>
        </div>
    </div>

    <header class="main-header">
        <div class="container header-grid">
            <div class="logo">
                <a href="index.php">
                    <img src="assets/images/urban-logo.avif" alt="Urban Ladder Logo">
                </a>
            </div>
            <nav class="services-nav">
                <a href="#" class="active">Home Interiors</a>
                <a href="#">Business Furniture</a>
                <a href="#">Repair Services</a>
            </nav>
            
            <form action="index.php" method="GET" class="search-form-box">
                <input type="text" name="search" placeholder="Search furniture, recliners, tables..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            </form>
            
            <div class="utility-icons">
                <span class="icon-btn"><span class="material-icons">storefront</span></span>
                
                <?php if ($is_logged_in): ?>
                    <span class="user-label">Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?></span>
                    <a href="logout.php" class="icon-btn" title="Log Out" style="color: #DC3545;"><span class="material-icons">logout</span></a>
                <?php else: ?>
                    <a href="login.php" class="icon-btn" title="Login"><span class="material-icons">person_outline</span></a>
                <?php endif; ?>
                
                <a href="whishlist_view.php" class="icon-btn" title="View Wishlist">
                    <span class="material-icons">favorite_border</span>
                    <span class="badge-count" id="global-wish-count"><?php echo $total_wishes; ?></span>
                </a>
                
                <a href="cart.php" class="icon-btn" title="View Cart">
                    <span class="material-icons">shopping_bag</span>
                </a>
            </div>
        </div>
    </header>

    <nav class="category-bar">
        <div class="container category-links">
            <div class="nav-item"><a href="index.php" class="nav-link highlight">New Arrivals</a></div>
            <div class="nav-item"><a href="index.php" class="nav-link highlight">Deal Zone</a></div>
            
            <?php 
            $menu_cat_result = mysqli_query($conn, "SELECT * FROM categories WHERE status = 1 ORDER BY name ASC");
            while ($cat = mysqli_fetch_assoc($menu_cat_result)):
                $cat_id = $cat['id'];
                
                // Fetch first half of subcategories for Column 1
                $menu_sub_res1 = mysqli_query($conn, "SELECT * FROM subcategories WHERE category_id = $cat_id AND status = 1 ORDER BY name ASC LIMIT 6");
                // Fetch next subcategories for Column 2
                $menu_sub_res2 = mysqli_query($conn, "SELECT * FROM subcategories WHERE category_id = $cat_id AND status = 1 ORDER BY name ASC LIMIT 6 OFFSET 6");
            ?>
                <div class="nav-item">
                    <a href="#" class="nav-link"><?php echo htmlspecialchars($cat['name']); ?></a>
                    <?php if (mysqli_num_rows($menu_sub_res1) > 0): ?>
                        <div class="dropdown-panel">
                            
                            <div class="menu-column">
                                <h4>Popular Choices</h4>
                                <?php while ($sub = mysqli_fetch_assoc($menu_sub_res1)): ?>
                                    <a href="products_view.php?sub_id=<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['name']); ?></a>
                                <?php endwhile; ?>
                            </div>
                            
                            <?php if (mysqli_num_rows($menu_sub_res2) > 0): ?>
                                <div class="menu-column">
                                    <h4>Premium Selection</h4>
                                    <?php while ($sub = mysqli_fetch_assoc($menu_sub_res2)): ?>
                                        <a href="products_view.php?sub_id=<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['name']); ?></a>
                                    <?php endwhile; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="menu-column">
                                <h4>Curated Collections</h4>
                                <a href="#">Oasis Collection</a>
                                <a href="#">Terra Collection</a>
                                <a href="#">Astra Collection</a>
                                <a href="#">Dawn Collection</a>
                            </div>
                            
                            <div class="menu-promo-card">
                                <img src="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=300&q=80" alt="Promo Image">
                                <div class="promo-tag">
                                    Featured Designs<br>
                                    <span style="color: var(--primary-orange); font-size: 11px;">starting from ₹2,512</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </nav>

    <section class="hero-section">
        <div class="hero-overlay-content">
            <p class="hero-subtitle">THE BIGGEST SALE OF THE YEAR</p>
            <h2 class="hero-title"><?php echo htmlspecialchars($banner_title); ?></h2>
            <div class="discount-badge"><?php echo htmlspecialchars($banner_discount); ?></div>
            <br>
            <a href="#" class="cta-btn">SHOP NOW</a>
        </div>
    </section>

    <div class="urgency-bar">
        <p>DISCOUNTS LIKE THESE DON'T WAIT FOREVER. ACT NOW!</p>
    </div>

    <div class="container">
        
        <section class="category-showcase">
            <h2 class="section-title">Shop by Category</h2>
            <div class="circle-grid">
                <?php if (mysqli_num_rows($subcategories_result) > 0): ?>
                    <?php while ($sub = mysqli_fetch_assoc($subcategories_result)): ?>
                        <a href="products_view.php?sub_id=<?php echo $sub['id']; ?>" class="circle-item">
                            <div class="img-wrapper">
                                <?php 
                                $img_path = 'https://via.placeholder.com/95?text=Furniture';
                                $img_file = trim((string)($sub['image_url'] ?? $sub['image'] ?? ''));

                                if (!empty($img_file)) {
                                    if (preg_match('/^(https?:\/\/|\/)/i', $img_file)) {
                                        $img_path = $img_file;
                                    } elseif (strpos($img_file, 'assets/') === 0 || strpos($img_file, 'uploads/') === 0 || strpos($img_file, 'admin/') === 0) {
                                        $img_path = $img_file;
                                    } else {
                                        $img_path = 'assets/images/uploads/' . ltrim($img_file, '/\\');
                                    }
                                }
                                ?>
                                <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($sub['name']); ?>" onerror="this.parentNode.style.backgroundColor='#E2E8F0'; this.style.display='none';">
                            </div>
                            <span><?php echo htmlspecialchars($sub['name']); ?></span>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-notice">No subcategories found in database records.</div>
                <?php endif; ?>
            </div>
        </section>

        <section class="offers-section">
            <h3 style="font-size: 16px; font-weight: 700; color: #1A202C; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.5px;">Additional Discounts and Offers</h3>
            <div class="offers-grid">
                <div class="offer-card">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/1/12/ICICI_Bank_Logo.svg" alt="ICICI Bank" class="offer-bank-logo">
                    <div class="offer-details">
                        <strong>10% Instant Discount*</strong> On ICICI Credit Card EMI Transactions
                    </div>
                </div>
                <div class="offer-card">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/c/c4/HDFC_Bank_Logo.svg" alt="HDFC Bank" class="offer-bank-logo">
                    <div class="offer-details">
                        Up to <strong>₹4,000* Instant discount</strong> on HDFC Bank Credit Card transactions
                    </div>
                </div>
            </div>
        </section>

        <section class="product-section">
            <h2 class="section-title">
                <?php echo !empty($search_query) ? "Search Results for '" . htmlspecialchars($search_query) . "'" : "Trending Catalog Releases"; ?>
            </h2>
            <div class="product-grid">
                <?php if (mysqli_num_rows($products_result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($products_result)): ?>
                        <div class="product-card">
                            <a href="product_details.php?id=<?php echo $row['id']; ?>" style="text-decoration: none; color: inherit; display: block;">
                                <div class="card-img-holder">
                                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="">
                                </div>
                            </a>
                            <button class="wishlist-heart <?php echo ($row['is_wishlisted']) ? 'active' : ''; ?>" data-id="<?php echo $row['id']; ?>">
                                <span class="material-icons"><?php echo ($row['is_wishlisted']) ? 'favorite' : 'favorite_border'; ?></span>
                            </button>
                            <a href="product_details.php?id=<?php echo $row['id']; ?>" style="text-decoration: none; color: inherit; display: block;">
                                <div class="card-details">
                                    <div class="prod-title"><?php echo htmlspecialchars($row['title']); ?></div>
                                    <div class="prod-desc"><?php echo htmlspecialchars($row['description']); ?></div>
                                    <div class="price-row">
                                        <span class="sale-price">₹<?php echo number_format($row['price'], 2); ?></span>
                                        <span class="old-price">₹<?php echo number_format($row['master_price'], 2); ?></span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-notice">No furniture matching your criteria could be located in our inventory.</div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>Download Our App</h4>
                    <div class="app-download-btns">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="App Store Download">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Play Store Download">
                    </div>
                </div>
                <div class="footer-col">
                    <h4>The Company</h4>
                    <ul>
                        <li><a href="#">Help</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>More Information</h4>
                    <ul>
                        <li><a href="#">Fees and Payments</a></li>
                        <li><a href="#">Shipping & Delivery</a></li>
                        <li><a href="#">Terms and Conditions</a></li>
                        <li><a href="#">Warranty, Return and Refund</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Address</h4>
                    <p style="line-height: 1.6; color: #718096; margin: 0;">
                        Reliance Retail Limited, 3rd Floor, Court House, Lokmanya Tilak Marg, Dhobi Talao, Mumbai, Maharashtra, India - 400002
                    </p>
                    <p style="font-size: 11px; color: #A0AEC0; margin-top: 10px;">CIN: U01100MH1999PLC120563</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div style="font-weight: 600; font-size: 12px; color: #A0AEC0;">© 2012-2026 URBAN LADDER REPLICA</div>
                <div class="social-icons">
                    <a href="#">Facebook</a>
                    <a href="#">Instagram</a>
                    <a href="#">Twitter</a>
                    <a href="#">LinkedIn</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
    document.querySelectorAll('.wishlist-heart').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const productId = this.getAttribute('data-id');
            const heartBtn = this;
            const icon = heartBtn.querySelector('.material-icons');
            
            const formData = new FormData();
            formData.append('action', 'toggle_wish'); 
            formData.append('product_id', productId);
            
            fetch('index.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'not_logged_in') {
                    alert("Authentication Required: Please sign in to save wishlist favorites.");
                    window.location.href = "login.php";
                    return;
                }
                
                const globalCount = document.getElementById('global-wish-count');
                let currentCount = parseInt(globalCount.textContent) || 0;
                
                if (data.status === 'added') {
                    heartBtn.classList.add('active');
                    icon.textContent = 'favorite';
                    globalCount.textContent = currentCount + 1;
                } else if (data.status === 'removed') {
                    heartBtn.classList.remove('active');
                    icon.textContent = 'favorite_border';
                    globalCount.textContent = Math.max(0, currentCount - 1);
                }
            })
            .catch(err => {
                console.error('Unified Pipeline Error Context Logs Track:', err);
                alert("Wishlist tracking sync issue encountered.");
            });
        });
    });
    </script>
</body>
</html>