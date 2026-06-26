<?php
// product_details.php
session_start();
require_once 'config/db.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$product_id = intval($_GET['id']);
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? intval($_SESSION['user_id']) : 0;

// Fetch Detailed Product Specs
$query = "SELECT p.*, s.name AS sub_name, c.name AS cat_name, w.id AS is_wishlisted
          FROM products p
          JOIN subcategories s ON p.subcategory_id = s.id
          JOIN categories c ON s.category_id = c.id
          LEFT JOIN wishlist w ON p.id = w.product_id AND w.user_id = $user_id
          WHERE p.id = $product_id AND p.status = 1";
$result = mysqli_query($conn, $query);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    header("Location: index.php");
    exit;
}

$savings_percent = 0;
if ($product['master_price'] > $product['price']) {
    $savings_percent = round((($product['master_price'] - $product['price']) / $product['master_price']) * 100);
}

// Fetch Wishlist Counter Metrics
$total_wishes = 0;
if ($is_logged_in) {
    $wish_count_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(id) AS total FROM wishlist WHERE user_id = $user_id"));
    $total_wishes = $wish_count_res['total'] ?? 0;
}

$top_announcement = "Additional up to ₹10,000 off. Use code EXTRA10K | Limited-time deal - Shop now!";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> - Urban Ladder Replica</title>
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

        /* Unified Navigation & Header Styles */
        .top-bar { background: #333333; color: #ffffff; font-size: 12px; padding: 10px 0; }
        .top-bar-content { display: flex; justify-content: space-between; align-items: center; }
        .store-link { color: var(--primary-orange); cursor: pointer; font-weight: 600; }
        .top-right-links a { color: #ffffff; text-decoration: none; margin-left: 15px; }
        .main-header { background: #ffffff; border-bottom: 1px solid var(--border-color); padding: 10px 0; }
        .header-grid { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .logo img { height: 42px; display: block; }
        .services-nav { display: flex; gap: 20px; }
        .services-nav a { text-decoration: none; color: var(--text-main); font-weight: 700; font-size: 14px; white-space: nowrap; }
        .services-nav a.active { color: var(--primary-orange); }
        .search-form-box { flex-grow: 1; max-width: 420px; margin: 0 15px; }
        .search-form-box input { width: 100%; padding: 10px 18px; border: 1px solid #CBD5E0; border-radius: 20px; outline: none; font-size: 14px; box-sizing: border-box; }
        .search-form-box input:focus { border-color: var(--primary-orange); background: #fff; }
        .utility-icons { display: flex; gap: 22px; align-items: center; }
        .icon-btn { position: relative; cursor: pointer; text-decoration: none; color: #333; display: inline-flex; align-items: center; }
        .badge-count { position: absolute; top: -8px; right: -8px; background: var(--primary-orange); color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: 700; }
        .user-label { font-size: 13px; color: #4A5568; font-weight: 600; margin-right: -5px; }

        /* Mega Dropdown Navbar Styles */
        .category-bar { background: #ffffff; border-bottom: 1px solid var(--border-color); position: relative; z-index: 9999; }
        .category-links { display: flex; justify-content: flex-start; gap: 25px; flex-wrap: nowrap; }
        .nav-item { position: relative; padding: 15px 0; }
        .nav-link { text-decoration: none; color: #2D3748; font-size: 13px; font-weight: 600; transition: color 0.2s ease; }
        .nav-link.highlight { color: var(--primary-orange); }
        .nav-item:hover .nav-link { color: var(--primary-orange); }
        .dropdown-panel { position: absolute; top: 100%; left: 0; background: #ffffff; width: 820px; box-shadow: 0 12px 24px rgba(0,0,0,0.12); border: 1px solid var(--border-color); display: none; padding: 24px; gap: 25px; z-index: 10000; border-radius: 0 0 6px 6px; }
        .nav-item:hover .dropdown-panel { display: flex; }
        .menu-column { flex: 1; min-width: 130px; }
        .menu-column h4 { font-size: 13px; font-weight: 700; color: #1A202C; margin: 0 0 12px 0; border-bottom: 1px solid #EDF2F7; padding-bottom: 6px; text-transform: uppercase; letter-spacing: 0.3px; }
        .menu-column a { display: block; padding: 5px 0 !important; color: #4A5568; text-decoration: none; font-size: 12px; font-weight: 500; }
        .menu-column a:hover { background: none !important; color: var(--primary-orange) !important; }
        .menu-promo-card { width: 210px; background: #FAFBFC; border-radius: 4px; overflow: hidden; border: 1px solid #E2E8F0; padding: 8px; text-align: center; display: flex; flex-direction: column; justify-content: space-between; }
        .menu-promo-card img { width: 100%; height: 135px; object-fit: cover; border-radius: 3px; }
        .menu-promo-card .promo-tag { font-size: 13px; font-weight: 700; margin-top: 8px; color: #2D3748; line-height: 1.3; }

        /* Detail Structural Elements */
        .breadcrumbs { padding: 20px 0; font-size: 12px; color: var(--text-muted); }
        .breadcrumbs a { color: var(--text-muted); text-decoration: none; }
        .breadcrumbs span { margin: 0 5px; color: #CBD5E0; }

        .product-view-wrapper { display: flex; gap: 50px; margin-bottom: 60px; flex-wrap: wrap; background: #ffffff; padding: 30px; border-radius: 8px; border: 1px solid var(--border-color); }
        .gallery-column { flex: 1.2; min-width: 450px; }
        .details-column { flex: 1; min-width: 380px; }

        .main-image-box { width: 100%; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden; background: #FAFBFC; }
        .main-image-box img { width: 100%; height: auto; max-height: 500px; object-fit: cover; display: block; }

        .brand-tag { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .product-title { font-size: 26px; font-weight: 700; color: #1A202C; margin: 0 0 10px 0; text-transform: capitalize; }
        
        .price-panel { background: #FAFBFC; border-radius: 6px; padding: 20px; margin-bottom: 25px; border: 1px solid var(--border-color); }
        .price-metrics { display: flex; align-items: center; gap: 14px; margin-bottom: 6px; }
        .offer-price { font-size: 24px; font-weight: 800; color: var(--primary-orange); }
        .original-mrp { font-size: 16px; text-decoration: line-through; color: #A0AEC0; }
        .discount-tag { font-size: 13px; font-weight: 700; color: #38A169; background: #E6FFFA; padding: 3px 8px; border-radius: 3px; }
        .tax-info { font-size: 12px; color: var(--text-muted); font-weight: 500; }

        .purchase-actions-box { display: flex; gap: 16px; margin-bottom: 30px; }
        .btn-add-cart { flex: 2; background: var(--primary-orange); color: #ffffff; border: none; padding: 15px; border-radius: 6px; font-size: 15px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
        
        .btn-wishlist-toggle { flex: 0.5; background: #ffffff; border: 1px solid #CBD5E0; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #718096; }
        .btn-wishlist-toggle.active { color: #E53E3E; background: #FFF5F5; border-color: #FED7D7; }

        .info-card { border-top: 1px solid var(--border-color); padding-top: 20px; }
        .info-card h3 { font-size: 15px; font-weight: 700; color: #2D3748; margin: 0 0 10px 0; text-transform: uppercase; }
        .info-card p { font-size: 14px; color: #4A5568; line-height: 1.6; margin: 0; }

        /* Unified Footer Matrix */
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
                <a href="index.php"><img src="assets/images/urban-logo.avif" alt="Urban Ladder Logo"></a>
            </div>
            <nav class="services-nav">
                <a href="#" class="active">Home Interiors</a>
                <a href="#">Business Furniture</a>
                <a href="#">Repair Services</a>
            </nav>
            <form action="index.php" method="GET" class="search-form-box">
                <input type="text" name="search" placeholder="Search furniture, recliners, tables...">
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
                <span class="icon-btn"><span class="material-icons">shopping_bag</span></span>
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
                $menu_sub_res1 = mysqli_query($conn, "SELECT * FROM subcategories WHERE category_id = $cat_id AND status = 1 ORDER BY name ASC LIMIT 6");
                $menu_sub_res2 = mysqli_query($conn, "SELECT * FROM subcategories WHERE category_id = $cat_id AND status = 1 ORDER BY name ASC LIMIT 6 OFFSET 6");
            ?>
                <div class="nav-item">
                    <a href="#" class="nav-link"><?php echo htmlspecialchars($cat['name']); ?></a>
                    <?php if (mysqli_num_rows($menu_sub_res1) > 0): ?>
                        <div class="dropdown-panel">
                            <div class="menu-column">
                                <h4>Popular Choices</h4>
                                <?php while ($sub_item = mysqli_fetch_assoc($menu_sub_res1)): ?>
                                    <a href="products_view.php?sub_id=<?php echo $sub_item['id']; ?>"><?php echo htmlspecialchars($sub_item['name']); ?></a>
                                <?php endwhile; ?>
                            </div>
                            <?php if (mysqli_num_rows($menu_sub_res2) > 0): ?>
                                <div class="menu-column">
                                    <h4>Premium Selection</h4>
                                    <?php while ($sub_item = mysqli_fetch_assoc($menu_sub_res2)): ?>
                                        <a href="products_view.php?sub_id=<?php echo $sub_item['id']; ?>"><?php echo htmlspecialchars($sub_item['name']); ?></a>
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
                                <img src="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=300&q=80" alt="Promo">
                                <div class="promo-tag">Featured Designs<br><span style="color: var(--primary-orange); font-size: 11px;">starting from ₹2,512</span></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </nav>

    <div class="container">
        <div class="breadcrumbs">
            <a href="index.php">Home</a><span>/</span>
            <a href="#"><?php echo htmlspecialchars($product['cat_name']); ?></a><span>/</span>
            <a href="products_view.php?sub_id=<?php echo $product['subcategory_id']; ?>"><?php echo htmlspecialchars($product['sub_name']); ?></a><span>/</span>
            <a href="#"><?php echo htmlspecialchars($product['title']); ?></a>
        </div>

        <div class="product-view-wrapper">
            <div class="gallery-column">
                <div class="main-image-box">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="">
                </div>
            </div>

            <div class="details-column">
                <div class="brand-tag">Urban Ladder</div>
                <h1 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                
                <div class="price-panel">
                    <div class="price-metrics">
                        <span class="offer-price">₹<?php echo number_format($product['price'], 2); ?></span>
                        <?php if ($savings_percent > 0): ?>
                            <span class="original-mrp">₹<?php echo number_format($product['master_price'], 2); ?></span>
                            <span class="discount-tag"><?php echo $savings_percent; ?>% OFF</span>
                        <?php endif; ?>
                    </div>
                    <div class="tax-info">Price inclusive of all local ecosystem tariffs.</div>
                </div>

                <div class="purchase-actions-box">
                    <button class="btn-add-cart" id="add-to-cart-trigger">
                        <span class="material-icons">shopping_bag</span> Add To Shopping Bag
                    </button>
                    <button class="btn-wishlist-toggle <?php echo ($product['is_wishlisted']) ? 'active' : ''; ?>" id="detail-wish-trigger" data-id="<?php echo $product['id']; ?>">
                        <span class="material-icons"><?php echo ($product['is_wishlisted']) ? 'favorite' : 'favorite_border'; ?></span>
                    </button>
                </div>

                <div class="info-card">
                    <h3>Product Overview</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>Download Our App</h4>
                    <div class="app-download-btns">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="App Store">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Play Store">
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
                    <p style="line-height: 1.6; color: #718096; margin: 0;">Reliance Retail Limited, 3rd Floor, Court House, Lokmanya Tilak Marg, Dhobi Talao, Mumbai, Maharashtra, India - 400002</p>
                    <p style="font-size: 11px; color: #A0AEC0; margin-top: 10px;">CIN: U01100MH1999PLC120563</p>
                </div>
            </div>
            <div class="footer-bottom">
                <div style="font-weight: 600; font-size: 12px; color: #A0AEC0;">© 2012-2026 URBAN LADDER REPLICA</div>
                <div class="social-icons">
                    <a href="#">Facebook</a><a href="#">Instagram</a><a href="#">Twitter</a><a href="#">LinkedIn</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
    document.getElementById('detail-wish-trigger').addEventListener('click', function() {
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
                alert("Please log in to manage your favorites.");
                window.location.href = "login.php";
                return;
            }
            const globalCount = document.getElementById('global-wish-count');
            let currentCount = parseInt(globalCount.textContent) || 0;
            
            if (data.status === 'added') {
                heartBtn.classList.add('active');
                icon.textContent = 'favorite';
                if(globalCount) globalCount.textContent = currentCount + 1;
            } else if (data.status === 'removed') {
                heartBtn.classList.remove('active');
                icon.textContent = 'favorite_border';
                if(globalCount) globalCount.textContent = Math.max(0, currentCount - 1);
            }
        });
    });

    document.getElementById('add-to-cart-trigger').addEventListener('click', function() {
        alert("Item added to shopping bag!");
    });

    document.getElementById('add-to-cart-trigger').addEventListener('click', function() {
    const productId = document.getElementById('detail-wish-trigger').getAttribute('data-id');
    
    const formData = new FormData();
    formData.append('action', 'add_to_cart');
    formData.append('product_id', productId);
    
    fetch('cart.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            alert("Success: Product added to your shopping bag!");
            window.location.href = "cart.php";
        }
    });
});
    </script>
    
</body>
</html>