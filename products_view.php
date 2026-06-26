<?php
// products_view.php
session_start();
require_once 'config/db.php';

if (!isset($_GET['sub_id'])) {
    header("Location: index.php");
    exit;
}

$sub_id = intval($_GET['sub_id']);
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? intval($_SESSION['user_id']) : 0;

// Fetch Subcategory & Parent Category Metadata
$meta_query = "SELECT s.name AS sub_name, c.name AS cat_name 
               FROM subcategories s
               JOIN categories c ON s.category_id = c.id
               WHERE s.id = $sub_id AND s.status = 1";
$meta_result = mysqli_query($conn, $meta_query);
$meta = mysqli_fetch_assoc($meta_result);

if (!$meta) {
    header("Location: index.php");
    exit;
}

// Fetch Wishlist Counter Metrics
$total_wishes = 0;
if ($is_logged_in) {
    $wish_count_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(id) AS total FROM wishlist WHERE user_id = $user_id"));
    $total_wishes = $wish_count_res['total'] ?? 0;
}

// Fetch Targeted Products Belonging to This Subcategory
$product_query = "SELECT p.id, p.title, p.description, p.price, p.master_price, p.image_url, w.id AS is_wishlisted 
                  FROM products p 
                  LEFT JOIN wishlist w ON p.id = w.product_id AND w.user_id = $user_id 
                  WHERE p.subcategory_id = $sub_id AND p.status = 1 ORDER BY p.id DESC";
$products_result = mysqli_query($conn, $product_query);
$total_products = mysqli_num_rows($products_result);

$top_announcement = "Additional up to ₹10,000 off. Use code EXTRA10K | Limited-time deal - Shop now!";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($meta['sub_name']); ?> - Urban Ladder Replica</title>
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

        /* Filter Product Spec View Layouts (Ref Image: image_92f426.jpg) */
        .breadcrumbs { padding: 20px 0 8px 0; font-size: 12px; color: var(--text-muted); }
        .breadcrumbs a { color: var(--text-muted); text-decoration: none; }
        .breadcrumbs span { margin: 0 5px; color: #CBD5E0; }

        .page-title-row { display: flex; align-items: baseline; gap: 10px; margin-bottom: 15px; }
        .page-title-row h1 { font-size: 22px; font-weight: 700; color: #1A202C; margin: 0; }
        .product-count-tag { font-size: 14px; color: var(--text-muted); font-weight: 500; }

        .filter-ribbon { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); padding: 12px 0; margin-bottom: 30px; background: #ffffff; }
        .filter-left-group { display: flex; align-items: center; gap: 20px; }
        .all-filters-btn { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; color: #2D3748; cursor: pointer; text-transform: uppercase; }
        .filter-dropdown { display: inline-flex; align-items: center; gap: 4px; font-size: 13px; font-weight: 600; color: #4A5568; cursor: pointer; text-transform: uppercase; }
        .filter-dropdown .material-icons { font-size: 16px; color: #A0AEC0; }
        .sort-by-selector { display: inline-flex; align-items: center; gap: 4px; font-size: 13px; font-weight: 600; color: #4A5568; cursor: pointer; text-transform: uppercase; }

        /* Product Grid Framework */
        .catalog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(265px, 1fr)); gap: 30px; margin-bottom: 60px; }
        .catalog-card { background: #ffffff; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; position: relative; transition: box-shadow 0.2s ease; }
        .catalog-card:hover { box-shadow: 0 6px 16px rgba(0,0,0,0.06); }
        .card-img-wrapper { width: 100%; height: 210px; background: #FAFBFC; position: relative; }
        .card-img-wrapper img { width: 100%; height: 100%; object-fit: cover; }
        
        .bestseller-badge { position: absolute; top: 12px; left: 12px; background: #FF7043; color: white; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 3px; text-transform: capitalize; }
        .wishlist-heart { position: absolute; top: 15px; right: 15px; background: #ffffff; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.08); cursor: pointer; border: none; outline: none; z-index: 10; }
        .wishlist-heart .material-icons { font-size: 20px; color: #A0AEC0; }
        .wishlist-heart.active .material-icons { color: #E53E3E; }

        .card-meta-details { padding: 18px; }
        .brand-label { font-size: 11px; color: var(--text-muted); font-weight: 600; margin-bottom: 4px; text-transform: uppercase; }
        .item-title { font-size: 16px; font-weight: 600; color: #2D3748; margin: 0 0 4px 0; text-transform: capitalize; }
        .price-row { display: flex; gap: 10px; align-items: center; margin-top: 10px; }
        .current-price { font-size: 16px; font-weight: 700; color: var(--primary-orange); }
        .list-price { font-size: 13px; text-decoration: line-through; color: #A0AEC0; }
        .empty-notice { color: var(--text-muted); font-size: 14px; padding: 40px; text-align: center; width: 100%; grid-column: 1/-1; }

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
            <a href="#"><?php echo htmlspecialchars($meta['cat_name']); ?></a><span>/</span>
            <a href="#"><?php echo htmlspecialchars($meta['sub_name']); ?></a>
        </div>

        <div class="page-title-row">
            <h1><?php echo htmlspecialchars($meta['sub_name']); ?></h1>
            <span class="product-count-tag"><?php echo $total_products; ?> Products</span>
        </div>

        <div class="filter-ribbon">
            <div class="filter-left-group">
                <div class="all-filters-btn"><span class="material-icons" style="font-size:18px;">tune</span> All Filters</div>
                <div class="filter-dropdown">Primary Material <span class="material-icons">expand_more</span></div>
                <div class="filter-dropdown">Brand <span class="material-icons">expand_more</span></div>
                <div class="filter-dropdown">Seating Capacity <span class="material-icons">expand_more</span></div>
                <div class="filter-dropdown">Storage Availability <span class="material-icons">expand_more</span></div>
                <div class="filter-dropdown">Mechanism <span class="material-icons">expand_more</span></div>
            </div>
            <div class="sort-by-selector">Sort By <span class="material-icons">expand_more</span></div>
        </div>

        <div class="catalog-grid">
            <?php if ($total_products > 0): ?>
                <?php 
                $counter = 0;
                while ($row = mysqli_fetch_assoc($products_result)): 
                    $counter++;
                ?>
                    <div class="catalog-card">
                        <button class="wishlist-heart <?php echo ($row['is_wishlisted']) ? 'active' : ''; ?>" data-id="<?php echo $row['id']; ?>">
                            <span class="material-icons"><?php echo ($row['is_wishlisted']) ? 'favorite' : 'favorite_border'; ?></span>
                        </button>
                        <a href="product_details.php?id=<?php echo $row['id']; ?>" style="text-decoration: none; color: inherit; display: block;">
                            <div class="card-img-wrapper">
                                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="">
                                <?php if ($counter === 1): ?><div class="bestseller-badge">Bestseller</div><?php endif; ?>
                            </div>
                            <div class="card-meta-details">
                                <div class="brand-label">Urban Ladder</div>
                                <h3 class="item-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                                <div style="font-size: 13px; color: var(--text-muted); line-height: 1.4; height: 36px; overflow: hidden; margin-bottom: 8px;">
                                    <?php echo htmlspecialchars($row['description']); ?>
                                </div>
                                <div class="price-row">
                                    <span class="current-price">₹<?php echo number_format($row['price'], 2); ?></span>
                                    <span class="list-price">₹<?php echo number_format($row['master_price'], 2); ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-notice">No products available in this subcategory context yet.</div>
            <?php endif; ?>
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
                    if(globalCount) globalCount.textContent = currentCount + 1;
                } else if (data.status === 'removed') {
                    heartBtn.classList.remove('active');
                    icon.textContent = 'favorite_border';
                    if(globalCount) globalCount.textContent = Math.max(0, currentCount - 1);
                }
            });
        });
    });
    </script>
</body>
</html>