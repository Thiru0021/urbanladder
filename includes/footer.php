<?php 
// Include database configuration if active later
// require_once 'config/db.php'; 

// Mock dynamic data for now (Later fetched from database via admin panel)
$top_announcement = "Additional up to ₹10,000 off. Use code EXTRA10K | Limited-time deal - Shop now!";
$banner_title = "Full House Fiesta";
$banner_discount = "up to 70% off";
$banner_bg = "assets/images/hero_dining.jpg"; // Match image_af1380.jpg aesthetic
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Sofas at Upto 70% Off in India - Urban Ladder Replica</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- 1. Top Announcement Bar -->
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

    <!-- 2. Main Navigation -->
    <header class="main-header">
        <div class="container header-grid">
            <div class="logo">
                <span class="logo-box"></span>
                <h1>Urban Ladder</h1>
            </div>
            <nav class="services-nav">
                <a href="#" class="active">Home Interiors</a>
                <a href="#">Business Furniture</a>
                <a href="#">Repair Services</a>
            </nav>
            <div class="search-container">
                <input type="text" placeholder="Search">
            </div>
            <div class="utility-icons">
                <!-- Simple placeholders for icon fonts or SVGs -->
                <span class="icon">🏪</span>
                <span class="icon">👤</span>
                <span class="icon">❤️</span>
                <span class="icon">🛒</span>
            </div>
        </div>
    </header>

    <!-- 3. Category Bar -->
    <nav class="category-bar">
        <div class="container category-links">
            <a href="#" class="highlight">New Arrivals</a>
            <a href="#" class="highlight">Deal Zone</a>
            <a href="#">Sofas & Recliners</a>
            <a href="#">Living</a>
            <a href="#">Bedroom</a>
            <a href="#">Dining & Kitchen</a>
            <a href="#">Mattresses</a>
            <a href="#">Study</a>
            <a href="#">Storage Furniture</a>
            <a href="#">Lighting & Decor</a>
            <a href="#">Furnishing</a>
        </div>
    </nav>

    <!-- 4. Hero Banner Section -->
    <section class="hero-section" style="background-image: linear-gradient(rgba(0,0,0,0.1), rgba(0,0,0,0.1)), url('<?php echo $banner_bg; ?>');">
        <div class="hero-overlay-content">
            <p class="hero-subtitle">THE BIGGEST SALE OF THE YEAR</p>
            <h2 class="hero-title"><?php echo htmlspecialchars($banner_title); ?></h2>
            <div class="discount-badge"><?php echo htmlspecialchars($banner_discount); ?></div>
            <a href="#" class="cta-btn">SHOP NOW</a>
        </div>
    </section>

    <!-- 5. Urgency Footer Bar -->
    <div class="urgency-bar">
        <p>DISCOUNTS LIKE THESE DON'T WAIT FOREVER. ACT NOW!</p>
    </div>

</body>
</html>