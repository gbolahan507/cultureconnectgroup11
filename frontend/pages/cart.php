<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../db_connection.php';
 
// AJAX HANDLER 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
 
    // Update quantity
    if ($_POST['action'] === 'update_qty') {
        $listing_id = intval($_POST['listing_id'] ?? 0);
        $quantity   = max(1, intval($_POST['quantity'] ?? 1));
 
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['listing_id'] === $listing_id) {
                    $item['quantity'] = $quantity;
                    break;
                }
            }
            unset($item);
        }
 
        $subtotal = 0;
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
        }
 
        echo json_encode(['success' => true, 'subtotal' => number_format($subtotal, 2)]);
        exit();
    }
 
    // Remove item
    if ($_POST['action'] === 'remove_item') {
        $listing_id = intval($_POST['listing_id'] ?? 0);
 
        if (isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array_values(
                array_filter($_SESSION['cart'], fn($item) => $item['listing_id'] !== $listing_id)
            );
        }
 
        echo json_encode(['success' => true]);
        exit();
    }
 
    // Place Order
    if ($_POST['action'] === 'place_order') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'redirect' => '../pages/login.php', 'message' => 'Please log in to complete your booking.']);
            exit();
        }
 
        if (empty($_SESSION['cart'])) {
            echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
            exit();
        }
 
        $user_id = intval($_SESSION['user_id']);
 
        // Group cart items by sme_id
        $groups = [];
        foreach ($_SESSION['cart'] as $item) {
            $stmt = $conn->prepare("SELECT sme_id, price FROM listings WHERE listing_id = ? AND status = 'active'");
            $stmt->bind_param("i", $item['listing_id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
 
            if (!$row) continue;
 
            $sme_id = $row['sme_id'];
            if (!isset($groups[$sme_id])) $groups[$sme_id] = [];
            $groups[$sme_id][] = [
                'listing_id' => $item['listing_id'],
                'quantity'   => $item['quantity'],
                'price'      => floatval($row['price'])
            ];
        }
 
        if (empty($groups)) {
            echo json_encode(['success' => false, 'message' => 'No active listings found in your cart.']);
            exit();
        }
 
        // Create one order per business
        foreach ($groups as $sme_id => $items) {
            $total_amount = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
 
            $ord_stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'processing')");
            $ord_stmt->bind_param("id", $user_id, $total_amount);
            $ord_stmt->execute();
            $order_id = $conn->insert_id;
            $ord_stmt->close();
 
            foreach ($items as $item) {
                $oi_stmt = $conn->prepare("INSERT INTO order_item (order_id, listing_id, quantity, price) VALUES (?, ?, ?, ?)");
                $oi_stmt->bind_param("iiid", $order_id, $item['listing_id'], $item['quantity'], $item['price']);
                $oi_stmt->execute();
                $oi_stmt->close();
            }
        }
 
        // Clear cart
        unset($_SESSION['cart']);
 
        echo json_encode(['success' => true, 'message' => 'Booking confirmed!']);
        exit();
    }
 
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit();
}
 
// PAGE SETUP
$cart         = $_SESSION['cart'] ?? [];
$cart_count   = array_sum(array_column($cart, 'quantity'));
$is_logged_in = isset($_SESSION['user_id']);
 
// Group by business for display
$grouped = [];
foreach ($cart as $item) {
    $biz = $item['business_name'];
    if (!isset($grouped[$biz])) $grouped[$biz] = [];
    $grouped[$biz][] = $item;
}
 
$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart — CultureConnect</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="ct-page-wrap">
 
<?php include '../components/header.php'; ?>

<div class="ct-container">
 
    <div class="ct-page-header">
        <h1 class="ct-page-title">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
            </svg>
            Your Cart
            <?php if ($cart_count > 0) : ?>
            <span class="ct-title-count"><?= $cart_count ?> item<?= $cart_count !== 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </h1>
        <a href="../pages/browse.php" class="ct-back-link">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Continue Browsing
        </a>
    </div>
 
    <?php if (empty($cart)) : ?>
    <!-- ── Empty cart ─────────────────────────────────────── -->
    <div class="ct-empty">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="64" height="64">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
        </svg>
        <h2>Your cart is empty</h2>
        <p>Browse our cultural listings and add something you love.</p>
        <a href="../pages/browse.php" class="ct-browse-btn">Browse Listings</a>
    </div>
 
    <?php else : ?>
    <!-- Cart layout  -->
    <div class="ct-layout">
 
        <!-- Left: Cart items -->
        <div class="ct-items-col">
            <?php foreach ($grouped as $business => $items) : ?>
            <div class="ct-business-group">
                <div class="ct-business-header">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016 2.993 2.993 0 0 0 2.25-1.016 3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                    </svg>
                    <?= htmlspecialchars($business) ?>
                </div>
 
                <?php foreach ($items as $item) : ?>
                <div class="ct-item" id="ct-item-<?= $item['listing_id'] ?>">
                    <div class="ct-item-img-wrap">
                        <?php if (!empty($item['image'])) : ?>
                        <img src="../uploads/listings_images/<?= htmlspecialchars($item['image']) ?>"
                             alt="<?= htmlspecialchars($item['title']) ?>"
                             class="ct-item-img"
                             onerror="this.style.display='none'">
                        <?php else : ?>
                        <div class="ct-item-img-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="24" height="24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                        <?php endif; ?>
                    </div>
 
                    <div class="ct-item-details">
                        <p class="ct-item-business"><?= htmlspecialchars($item['business_name']) ?></p>
                        <h3 class="ct-item-title"><?= htmlspecialchars($item['title']) ?></h3>
                        <p class="ct-item-unit-price">£<?= number_format($item['price'], 2) ?> each</p>
                    </div>
 
                    <div class="ct-item-actions">
                        <div class="ct-qty-controls">
                            <button class="ct-qty-btn" onclick="ctChangeQty(<?= $item['listing_id'] ?>, -1)">−</button>
                            <input type="number"
                                   id="ct-qty-<?= $item['listing_id'] ?>"
                                   class="ct-qty-input"
                                   value="<?= $item['quantity'] ?>"
                                   min="1" max="99"
                                   onchange="ctUpdateQty(<?= $item['listing_id'] ?>, this.value)">
                            <button class="ct-qty-btn" onclick="ctChangeQty(<?= $item['listing_id'] ?>, 1)">+</button>
                        </div>
                        <p class="ct-item-line-total" id="ct-line-<?= $item['listing_id'] ?>">
                            £<?= number_format($item['price'] * $item['quantity'], 2) ?>
                        </p>
                        <button class="ct-remove-btn" onclick="ctRemoveItem(<?= $item['listing_id'] ?>)">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="15" height="15">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                            Remove
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
 
        <!-- Right: Summary -->
        <div class="ct-summary-col">
            <div class="ct-summary-card">
                <h2 class="ct-summary-title">Order Summary</h2>
 
                <?php foreach ($grouped as $business => $items) : ?>
                <div class="ct-summary-group">
                    <p class="ct-summary-biz"><?= htmlspecialchars($business) ?></p>
                    <?php foreach ($items as $item) : ?>
                    <div class="ct-summary-row">
                        <span class="ct-summary-item-name">
                            <?= htmlspecialchars($item['title']) ?>
                            <span class="ct-summary-qty">×<?= $item['quantity'] ?></span>
                        </span>
                        <span class="ct-summary-item-price" id="ct-summary-<?= $item['listing_id'] ?>">
                            £<?= number_format($item['price'] * $item['quantity'], 2) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
 
                <div class="ct-summary-divider"></div>
 
                <div class="ct-summary-total-row">
                    <span>Total</span>
                    <span class="ct-summary-total" id="ct-total">£<?= number_format($subtotal, 2) ?></span>
                </div>
 
                <p class="ct-summary-note">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    Payment is confirmed on booking. Each business will contact you to confirm your booking details.
                </p>
 
                <?php if (!$is_logged_in) : ?>
                <div class="ct-login-notice">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    You need to be logged in to complete your booking.
                </div>
                <a href="../pages/login.php" class="ct-confirm-btn">Log in to Confirm Booking</a>
                <?php else : ?>
                <button class="ct-confirm-btn" id="ct-confirm-btn" onclick="ctConfirmBooking()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    Confirm Booking
                </button>
                <?php endif; ?>
 
                <div id="ct-order-alert" class="ct-order-alert" style="display:none;"></div>
            </div>
        </div>
 
    </div>
    <?php endif; ?>
 
</div>
 
<!-- Success overlay -->
<div id="ct-success-overlay" class="ct-success-overlay" style="display:none;">
    <div class="ct-success-box">
        <div class="ct-success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="40" height="40">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        </div>
        <h2 class="ct-success-title">Booking Confirmed!</h2>
        <p class="ct-success-msg">Your booking has been placed successfully. Each business will contact you shortly to confirm your booking details.</p>
        <div class="ct-success-actions">
            <a href="../pages/browse.php" class="ct-success-browse-btn">Continue Browsing</a>
            <a href="../pages/dashboard.php?page=my-orders" class="ct-success-orders-btn">View My Orders</a>
        </div>
    </div>
</div>
 
<?php include '../components/footer.php'; ?>
 
<script>
    let ctCart = <?= json_encode(array_values($cart)) ?>;
 
    function ctChangeQty(listing_id, delta) {
        const input = document.getElementById('ct-qty-' + listing_id);
        let val     = parseInt(input.value) + delta;
        if (val < 1)  val = 1;
        if (val > 99) val = 99;
        input.value = val;
        ctUpdateQty(listing_id, val);
    }
 
    function ctUpdateQty(listing_id, quantity) {
        quantity = Math.max(1, parseInt(quantity));
 
        const fd = new FormData();
        fd.append('action',     'update_qty');
        fd.append('listing_id', listing_id);
        fd.append('quantity',   quantity);
 
        fetch('../pages/cart.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        const item = ctCart.find(i => i.listing_id === listing_id);
                        if (item) {
                            item.quantity   = quantity;
                            const lineTotal = (item.price * quantity).toFixed(2);
                            const lineEl    = document.getElementById('ct-line-' + listing_id);
                            const sumEl     = document.getElementById('ct-summary-' + listing_id);
                            if (lineEl) lineEl.textContent = '£' + lineTotal;
                            if (sumEl)  sumEl.textContent  = '£' + lineTotal;
                        }
                        document.getElementById('ct-total').textContent = '£' + data.subtotal;
                    }
                } catch(e) {}
            });
    }
 
    function ctRemoveItem(listing_id) {
        const fd = new FormData();
        fd.append('action',     'remove_item');
        fd.append('listing_id', listing_id);
 
        fetch('../pages/cart.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        const el = document.getElementById('ct-item-' + listing_id);
                        if (el) {
                            el.style.transition = 'opacity 0.3s, transform 0.3s';
                            el.style.opacity    = '0';
                            el.style.transform  = 'translateX(-10px)';
                            setTimeout(() => {
                                el.remove();
                                ctCart = ctCart.filter(i => i.listing_id !== listing_id);
                                if (ctCart.length === 0) location.reload();
                                else ctRecalcTotal();
                            }, 300);
                        }
                    }
                } catch(e) {}
            });
    }
 
    function ctRecalcTotal() {
        const total = ctCart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
        const el    = document.getElementById('ct-total');
        if (el) el.textContent = '£' + total.toFixed(2);
    }
 
    function ctConfirmBooking() {
        const btn     = document.getElementById('ct-confirm-btn');
        const alertEl = document.getElementById('ct-order-alert');
 
        btn.disabled         = true;
        btn.textContent      = 'Processing…';
        alertEl.style.display = 'none';
 
        const fd = new FormData();
        fd.append('action', 'place_order');
 
        fetch('../pages/cart.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        document.getElementById('ct-success-overlay').style.display = 'flex';
                    } else if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        alertEl.textContent   = data.message || 'Booking failed. Please try again.';
                        alertEl.className     = 'ct-order-alert ct-order-alert--error';
                        alertEl.style.display = 'block';
                        btn.disabled          = false;
                        btn.textContent       = 'Confirm Booking';
                    }
                } catch(e) {
                    alertEl.textContent   = 'Unexpected error. Please try again.';
                    alertEl.className     = 'ct-order-alert ct-order-alert--error';
                    alertEl.style.display = 'block';
                    btn.disabled          = false;
                    btn.textContent       = 'Confirm Booking';
                }
            })
            .catch(() => {
                alertEl.textContent   = 'Network error. Please check your connection.';
                alertEl.className     = 'ct-order-alert ct-order-alert--error';
                alertEl.style.display = 'block';
                btn.disabled          = false;
                btn.textContent       = 'Confirm Booking';
            });
    }
</script>
 
</body>
</html>