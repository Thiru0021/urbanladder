<?php
// cart.php
session_start();
require_once 'config/db.php';

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$is_logged_in = true;

// ========================================================
// ⚡ INTERNAL AJAX CART ENGINE PROCESSOR LAYER
// ========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $p_id = intval($_POST['product_id'] ?? 0);

    // Handler A: Add Item to Bag Component
    if ($_POST['action'] === 'add_to_cart') {
        $check = mysqli_query($conn, "SELECT id, quantity FROM cart WHERE user_id = $user_id AND product_id = $p_id");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE cart SET quantity = quantity + 1 WHERE user_id = $user_id AND product_id = $p_id");
        } else {
            mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $p_id, 1)");
        }
        echo json_encode(['status' => 'success']);
        exit;
    }

    // Handler B: Modify Quantity Counter Matrix
    if ($_POST['action'] === 'update_qty') {
        $new_qty = intval($_POST['quantity'] ?? 1);
        if ($new_qty > 0) {
            mysqli_query($conn, "UPDATE cart SET quantity = $new_qty WHERE user_id = $user_id AND product_id = $p_id");
            echo json_encode(['status' => 'updated']);
        }
        exit;
    }

    // Handler C: Drop Item Row Target
    if ($_POST['action'] === 'remove_item') {
        mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id AND product_id = $p_id");
        echo json_encode(['status' => 'deleted']);
        exit;
    }
}

// Fetch Header Notification Badges Metrics
$wish_count_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(id) AS total FROM wishlist WHERE user_id = $user_id"));
$total_wishes = $wish_count_res['total'] ?? 0;

$cart_count_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity) AS total FROM cart WHERE user_id = $user_id"));
$total_cart_items = $cart_count_res['total'] ?? 0;

// Fetch Cart Products Compiled Records Data
$cart_sql = "SELECT p.*, c.quantity FROM products p 
             INNER JOIN cart c ON p.id = c.product_id 
             WHERE c.user_id = $user_id ORDER BY c.id DESC";
$cart_result = mysqli_query($conn, $cart_sql);

$top_announcement = "Additional up to ₹10,000 off. Use code EXTRA10K | Limited-time deal - Shop now!";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Bag - Urban Ladder Replica</title>
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
        
        /* Nav Layout Structural Alignments */
        .top-bar { background: #333333; color: #ffffff; font-size: 12px; padding: 10px 0; }
        .top-bar-content { display: flex; justify-content: space-between; align-items: center; }
        .main-header { background: #ffffff; border-bottom: 1px solid var(--border-color); padding: 10px 0; }
        .header-grid { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .logo img { height: 42px; display: block; }
        .utility-icons { display: flex; gap: 22px; align-items: center; }
        .icon-btn { position: relative; cursor: pointer; text-decoration: none; color: #333; display: inline-flex; align-items: center; }
        .badge-count { position: absolute; top: -8px; right: -8px; background: var(--primary-orange); color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: 700; }
        .user-label { font-size: 13px; color: #4A5568; font-weight: 600; }

        /* Cart Viewport Structures Grid Splitting Panels */
        .cart-wrapper { display: flex; gap: 40px; padding: 40px 0 80px 0; align-items: flex-start; }
        .cart-items-panel { flex: 1.8; }
        .order-summary-panel { flex: 1; background: white; border: 1px solid var(--border-color); border-radius: 6px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }

        /* Cart Row Items Component Layout rules */
        .cart-item-row { display: flex; gap: 20px; background: white; border: 1px solid var(--border-color); border-radius: 6px; padding: 20px; margin-bottom: 20px; align-items: center; position: relative; }
        .cart-img-holder { width: 110px; height: 110px; border-radius: 4px; overflow: hidden; background: #F7FAFC; border: 1px solid var(--border-color); }
        .cart-img-holder img { width: 100%; height: 100%; object-fit: cover; }
        
        .item-specs { flex: 2; }
        .item-title-text { font-size: 16px; font-weight: 600; color: #2D3748; margin: 0 0 4px 0; text-transform: capitalize; }
        .item-desc-text { font-size: 12px; color: var(--text-muted); margin-bottom: 12px; }
        
        .qty-controls { display: flex; align-items: center; gap: 5px; }
        .qty-input { width: 45px; padding: 6px; border: 1px solid #CBD5E0; text-align: center; border-radius: 4px; font-weight: 600; }
        
        .item-pricing-target { text-align: right; min-width: 120px; }
        .row-total-val { font-size: 16px; font-weight: 700; color: #1A202C; }
        .row-unit-val { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
        
        .btn-drop-row { background: none; border: none; color: #A0AEC0; cursor: pointer; transition: color 0.15s ease; }
        .btn-drop-row:hover { color: #E53E3E; }

        /* Premium Empty State Layout (Screenshot Reference: image_95a6f9.png) */
        .empty-cart-state { text-align: center; padding: 100px 0; width: 100%; background: #ffffff; border-radius: 8px; border: 1px solid var(--border-color); }
        .empty-cart-state h2 { font-size: 24px; font-weight: 600; color: #1A202C; margin-bottom: 8px; }
        .empty-cart-state p { color: var(--text-muted); margin-bottom: 24px; font-size: 14px; }
        .continue-btn { background: var(--primary-orange); color: white; text-decoration: none; padding: 12px 32px; font-weight: 600; border-radius: 4px; display: inline-block; transition: background 0.15s ease; }
        .continue-btn:hover { background: #E65100; }

        /* Order Invoice Pricing Sidebar Modules */
        .summary-title { font-size: 16px; font-weight: 700; border-bottom: 1px solid #EDF2F7; padding-bottom: 15px; margin: 0 0 20px 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .summary-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 14px; color: #4A5568; }
        .summary-total-row { display: flex; justify-content: space-between; font-size: 16px; font-weight: 700; border-top: 1px solid #EDF2F7; padding-top: 15px; margin-top: 15px; color: #1A202C; }
        .btn-checkout { width: 100%; background: #38A169; color: white; border: none; padding: 14px; border-radius: 4px; font-size: 14px; font-weight: 700; margin-top: 25px; cursor: pointer; letter-spacing: 0.5px; }
        
        /* Unified Footer */
        .main-footer { background: #F9FAFB; border-top: 1px solid var(--border-color); padding: 60px 0 30px 0; font-size: 13px; color: #4A5568; }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 40px; margin-bottom: 40px; }
        .footer-col h4 { font-size: 14px; font-weight: 700; color: #2D3748; margin: 0 0 16px 0; }
        .footer-col ul { list-style: none; padding: 0; margin: 0; }
        .footer-col ul li { margin-bottom: 11px; }
        .footer-col ul li a { text-decoration: none; color: #718096; }
        .footer-bottom { border-top: 1px solid #E2E8F0; padding-top: 25px; display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>

    <div class="top-bar">
        <div class="container top-bar-content">
            <span>Nearest Store - <strong>UL Store Ghatkopar Rcity</strong></span>
            <span class="promo-text"><?php echo htmlspecialchars($top_announcement); ?></span>
        </div>
    </div>

    <header class="main-header">
        <div class="container header-grid">
            <div class="logo">
                <a href="index.php"><img src="assets/images/urban-logo.avif" alt="Urban Ladder Logo"></a>
            </div>
            <div class="utility-icons">
                <span class="user-label">Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?></span>
                <a href="wishlist_view.php" class="icon-btn" title="View Wishlist">
                    <span class="material-icons">favorite_border</span>
                    <span class="badge-count"><?php echo $total_wishes; ?></span>
                </a>
                <a href="cart.php" class="icon-btn" title="View Cart">
                    <span class="material-icons">shopping_bag</span>
                    <span class="badge-count" id="global-cart-count"><?php echo $total_cart_items; ?></span>
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (mysqli_num_rows($cart_result) > 0): ?>
            <div class="cart-wrapper" id="cart-main-container">
                
                <div class="cart-items-panel">
                    <?php 
                    $subtotal = 0;
                    while ($row = mysqli_fetch_assoc($cart_result)): 
                        $row_total = $row['price'] * $row['quantity'];
                        $subtotal += $row_total;
                    ?>
                        <div class="cart-item-row" id="item-row-<?php echo $row['id']; ?>">
                            <div class="cart-img-holder">
                                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="">
                            </div>
                            <div class="item-specs">
                                <h3 class="item-title-text"><?php echo htmlspecialchars($row['title']); ?></h3>
                                <div class="item-desc-text"><?php echo htmlspecialchars(substr($row['description'], 0, 75)) . '...'; ?></div>
                                
                                <div class="qty-controls">
                                    <span style="font-size:13px; color:var(--text-muted); margin-right:5px;">Qty:</span>
                                    <input type="number" class="qty-input" value="<?php echo $row['quantity']; ?>" min="1" data-id="<?php echo $row['id']; ?>" data-price="<?php echo $row['price']; ?>">
                                    <button class="btn-drop-row remove-cart-trigger" data-id="<?php echo $row['id']; ?>" style="margin-left:15px;" title="Remove item">
                                        <span class="material-icons" style="font-size:18px;">delete_outline</span>
                                    </button>
                                </div>
                            </div>
                            <div class="item-pricing-target">
                                <div class="row-total-val" id="row-total-<?php echo $row['id']; ?>">临₹<?php echo number_format($row_total, 2); ?></div>
                                <div class="row-unit-val">₹<?php echo number_format($row['price'], 2); ?> each</div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="order-summary-panel">
                    <h3 class="summary-title">Order Billing Summary</h3>
                    <div class="summary-row"><span>Cart Subtotal</span><span id="summary-subtotal">₹<?php echo number_format($subtotal, 2); ?></span></div>
                    <div class="summary-row"><span>Estimated Shipping</span><span style="color:#38A169; font-weight:600;">FREE</span></div>
                    <div class="summary-total-row"><span>Total Amount</span><span id="summary-grandtotal">₹<?php echo number_format($subtotal, 2); ?></span></div>
                    <button class="btn-checkout" onclick="alert('Proceeding to secure checkout payment structures layer execution context...')">Proceed To Checkout</button>
                </div>

            </div>
        <?php else: ?>
            <div class="cart-wrapper">
                <div class="empty-cart-state">
                    <h2>Your cart has room for designs.</h2>
                    <p>Start shopping for exquisite furniture now.</p>
                    <a href="index.php" class="continue-btn">Continue Shopping</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>Download Our App</h4>
                    <div class="app-download-btns">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="">
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
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Address</h4>
                    <p style="line-height: 1.6; color: #718096; margin: 0;">Reliance Retail Limited, 3rd Floor, Court House, Lokmanya Tilak Marg, Dhobi Talao, Mumbai, Maharashtra, India - 400002</p>
                </div>
            </div>
            <div class="footer-bottom">
                <div>© 2012-2026 URBAN LADDER REPLICA</div>
            </div>
        </div>
    </footer>

    <script>
    function updateCartTotals() {
        let subtotal = 0;
        let globalItemsCount = 0;
        
        document.querySelectorAll('.qty-input').forEach(input => {
            const qty = parseInt(input.value) || 0;
            const price = parseFloat(input.getAttribute('data-price'));
            subtotal += qty * price;
            globalItemsCount += qty;
        });

        const subtotalElement = document.getElementById('summary-subtotal');
        const grandtotalElement = document.getElementById('summary-grandtotal');
        const globalCartBadge = document.getElementById('global-cart-count');

        if (subtotalElement) subtotalElement.textContent = '₹' + subtotal.toLocaleString('en-IN', { minimumFractionDigits: 2 });
        if (grandtotalElement) grandtotalElement.textContent = '₹' + subtotal.toLocaleString('en-IN', { minimumFractionDigits: 2 });
        if (globalCartBadge) globalCartBadge.textContent = globalItemsCount;
    }

    // Bind Event Watcher listeners to handle Quantity Counter changes
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function() {
            let val = parseInt(this.value) || 1;
            if (val < 1) { val = 1; this.value = 1; }
            
            const productId = this.getAttribute('data-id');
            const price = parseFloat(this.getAttribute('data-price'));
            const rowTotalField = document.getElementById(`row-total-${productId}`);
            
            const formData = new FormData();
            formData.append('action', 'update_qty');
            formData.append('product_id', productId);
            formData.append('quantity', val);
            
            fetch('cart.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(rowTotalField) {
                    let totalVal = val * price;
                    rowTotalField.textContent = '临₹' + totalVal.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                }
                updateCartTotals();
            });
        });
    });

    // Bind Event Watcher listeners to handle Item row drops removals
    document.querySelectorAll('.remove-cart-trigger').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const targetRow = document.getElementById(`item-row-${productId}`);
            
            const formData = new FormData();
            formData.append('action', 'remove_item');
            formData.append('product_id', productId);
            
            fetch('cart.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'deleted') {
                    targetRow.remove();
                    updateCartTotals();
                    
                    // Reload viewport cleanly if zero rows remain to pop up the original empty state view context
                    if(document.querySelectorAll('.cart-item-row').length === 0) {
                        window.location.reload();
                    }
                }
            });
        });
    });
    </script>
</body>
</html>