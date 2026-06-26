<?php 
// wishlist_view.php
session_start();
require_once 'config/db.php';

// Prevent browser caching so clicking "Back" after logging out won't show private data
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$is_logged_in = true;

// Fetch Wishlist Counter Metrics
$wish_count_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(id) AS total FROM wishlist WHERE user_id = $user_id"));
$total_wishes = $wish_count_res['total'] ?? 0;

// Fetch user items from wishlist joined with product info
$wish_sql = "SELECT p.*, w.id AS wish_row_id 
             FROM products p
             INNER JOIN wishlist w ON p.id = w.product_id
             WHERE w.user_id = $user_id ORDER BY w.id DESC";
$wish_result = mysqli_query($conn, $wish_sql);

$top_announcement = "Additional up to ₹10,000 off. Use code EXTRA10K | Limited-time deal - Shop now!";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Urban Ladder Replica</title>
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
        
        /* Unified Header Navigation Framework Styles */
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

        /* Wishlist Grid & Content Styles */
        .page-wrapper { padding: 30px 0 60px; }
        .back-home { display: inline-flex; align-items: center; gap: 8px; color: var(--primary-orange); text-decoration: none; font-weight: 600; margin-bottom: 25px; font-size: 14px; }
        .wish-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(265px, 1fr)); gap: 30px; margin-top: 20px; }
        .product-card { background: white; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; position: relative; transition: box-shadow 0.2s ease; }
        .product-card:hover { box-shadow: 0 6px 16px rgba(0,0,0,0.06); }
        .card-img-holder { width: 100%; height: 210px; background: #F7FAFC; position: relative; }
        .card-img-holder img { width: 100%; height: 100%; object-fit: cover; }
        
        .wishlist-heart { position: absolute; top: 15px; right: 15px; background: white; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.08); cursor: pointer; border: none; outline: none; }
        .wishlist-heart .material-icons { font-size: 20px; color: #E53E3E; }
        
        .card-details { padding: 18px; }
        .prod-title { font-size: 16px; font-weight: 600; color: #2D3748; margin: 0 0 8px 0; text-transform: capitalize; }
        .sale-price { font-size: 16px; font-weight: 700; color: var(--primary-orange); }
        
        .empty-state { text-align: center; padding: 80px 0; color: var(--text-muted); grid-column: 1 / -1; width: 100%; }
        .empty-state .material-icons { font-size: 56px; margin-bottom: 15px; color: #CBD5E0; }

        /* Unified Footer Matrix Styles */
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
                <input type="text" name="search" placeholder="Search furniture, recliners, tables...">
            </form>
            
            <div class="utility-icons">
                <span class="icon-btn"><span class="material-icons">storefront</span></span>
                
                <span class="user-label">Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?></span>
                <a href="logout.php" class="icon-btn" title="Log Out" style="color: #DC3545;"><span class="material-icons">logout</span></a>
                
                <a href="wishlist_view.php" class="icon-btn" title="View Wishlist">
                    <span class="material-icons">favorite</span>
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
                                <div class="promo-tag">Featured Designs<br><span style="color: var(--primary-orange); font-size: 11px;">starting from ₹2,512</span></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </nav>

    <div class="container page-wrapper">
        <a href="index.php" class="back-home">
            <span class="material-icons">arrow_back</span> Continue Shopping
        </a>
        
        <h1 style="font-size: 24px; font-weight: 700; color: #2D3748; margin: 0 0 5px 0;">My Personal Wishlist</h1>
        <div style="color: #718096; font-size: 14px; margin-bottom: 30px;">Review or remove your saved favorite items</div>

        <div class="wish-grid" id="wishlist-container">
            <?php if (mysqli_num_rows($wish_result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($wish_result)): ?>
                    <div class="product-card" id="card-item-<?php echo $row['id']; ?>">
                        <div class="card-img-holder">
                            <a href="product_details.php?id=<?php echo $row['id']; ?>">
                                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="">
                            </a>
                            <button class="wishlist-heart" data-id="<?php echo $row['id']; ?>" title="Remove from list">
                                <span class="material-icons">delete_outline</span>
                            </button>
                        </div>
                        <div class="card-details">
                            <div class="prod-title"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="sale-price">₹<?php echo number_format($row['price'], 2); ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <span class="material-icons">heart_broken</span>
                    <h3>Your wishlist space is currently empty</h3>
                </div>
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
            const productId = this.getAttribute('data-id');
            const targetCard = document.getElementById(`card-item-${productId}`);
            
            const formData = new FormData();
            formData.append('action', 'toggle_wish'); // Syncing parameters seamlessly with index.php endpoint layer
            formData.append('product_id', productId);
            
            fetch('index.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'removed') {
                    targetCard.remove();
                    
                    // Update global layout tracker counters
                    const globalCount = document.getElementById('global-wish-count');
                    let currentCount = parseInt(globalCount.textContent) || 0;
                    if(globalCount) globalCount.textContent = Math.max(0, currentCount - 1);

                    const container = document.getElementById('wishlist-container');
                    if (container.querySelectorAll('.product-card').length === 0) {
                        container.innerHTML = `
                            <div class="empty-state">
                                <span class="material-icons">heart_broken</span>
                                <h3>Your wishlist space is currently empty</h3>
                            </div>`;
                    }
                }
            })
            .catch(err => console.error('Wishlist removal sync failed:', err));
        });
    });
    </script>
</body>
</html>