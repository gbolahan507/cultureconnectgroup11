<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../db_connection.php';
 
// AJAX HANDLER 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
 
    // Get Listings 
    if ($_POST['action'] === 'get_listings') {
        $search      = trim($_POST['search']        ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $sub_id      = intval($_POST['sub_id']      ?? 0);
        $area_id     = intval($_POST['area_id']     ?? 0);
        $price_max   = trim($_POST['price_max']     ?? '');
 
        $where  = ["l.status = 'active'"];
        $params = [];
        $types  = '';
 
        if (!empty($search)) {
            $where[]  = "(l.title LIKE ? OR l.caption LIKE ? OR l.description LIKE ? OR sp.business_name LIKE ?)";
            $s        = '%' . $search . '%';
            $params   = array_merge($params, [$s, $s, $s, $s]);
            $types   .= 'ssss';
        }
        if ($category_id > 0) { $where[] = "pc.category_id = ?";     $params[] = $category_id; $types .= 'i'; }
        if ($sub_id > 0)      { $where[] = "pss.subcategory_id = ?"; $params[] = $sub_id;      $types .= 'i'; }
        if ($area_id > 0)     { $where[] = "sp.area_id = ?";         $params[] = $area_id;     $types .= 'i'; }
        if ($price_max !== '' && is_numeric($price_max)) {
            $where[]  = "l.price <= ?";
            $params[] = floatval($price_max);
            $types   .= 'd';
        }
 
        $where_sql = implode(' AND ', $where);
 
        $sql = "
            SELECT l.listing_id, l.title, l.caption, l.price,
                   ps.item_name,
                   pss.subcategory_name,
                   pc.category_name, pc.category_id,
                   sp.business_name,
                   a.area_name,
                   li.image_url AS primary_image
            FROM listings l
            JOIN product_service ps                ON l.item_id         = ps.item_id
            JOIN product_service_subcategories pss ON ps.subcategory_id  = pss.subcategory_id
            JOIN product_service_categories pc     ON pss.category_id    = pc.category_id
            JOIN sme_profiles sp                   ON l.sme_id           = sp.sme_id
            JOIN areas a                           ON sp.area_id         = a.area_id
            LEFT JOIN listing_images li            ON l.listing_id       = li.listing_id AND li.is_primary = 1
            WHERE $where_sql
            ORDER BY l.created_at DESC
        ";
 
        $stmt = $conn->prepare($sql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result   = $stmt->get_result();
        $listings = [];
        while ($row = $result->fetch_assoc()) $listings[] = $row;
        $stmt->close();
 
        echo json_encode(['success' => true, 'listings' => $listings, 'count' => count($listings)]);
        exit();
    }
 
    // Get Filter Options 
    if ($_POST['action'] === 'get_filters') {
        $cats = [];
        $r    = mysqli_query($conn, "SELECT category_id, category_name FROM product_service_categories ORDER BY category_name");
        while ($row = mysqli_fetch_assoc($r)) $cats[] = $row;
 
        $subs = [];
        $r    = mysqli_query($conn, "SELECT subcategory_id, subcategory_name, category_id FROM product_service_subcategories ORDER BY subcategory_name");
        while ($row = mysqli_fetch_assoc($r)) $subs[] = $row;
 
        $areas = [];
        $r     = mysqli_query($conn, "SELECT area_id, area_name FROM areas ORDER BY area_name");
        while ($row = mysqli_fetch_assoc($r)) $areas[] = $row;
 
        $price_r   = mysqli_query($conn, "SELECT MAX(price) AS max_price FROM listings WHERE status = 'active'");
        $price_row = mysqli_fetch_assoc($price_r);
        $max_price = ceil(floatval($price_row['max_price'] ?? 200) / 10) * 10;
 
        echo json_encode([
            'success'    => true,
            'categories' => $cats,
            'subs'       => $subs,
            'areas'      => $areas,
            'max_price'  => $max_price
        ]);
        exit();
    }
 
    // Cart: Add Item 
    if ($_POST['action'] === 'add_to_cart') {
        $listing_id = intval($_POST['listing_id'] ?? 0);
        $quantity   = max(1, intval($_POST['quantity'] ?? 1));
 
        if (!$listing_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid listing.']);
            exit();
        }
 
        $stmt = $conn->prepare("
            SELECT l.listing_id, l.title, l.price,
                   sp.business_name,
                   li.image_url AS primary_image
            FROM listings l
            JOIN sme_profiles sp        ON l.sme_id     = sp.sme_id
            LEFT JOIN listing_images li ON l.listing_id = li.listing_id AND li.is_primary = 1
            WHERE l.listing_id = ? AND l.status = 'active'
            LIMIT 1
        ");
        $stmt->bind_param("i", $listing_id);
        $stmt->execute();
        $listing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
 
        if (!$listing) {
            echo json_encode(['success' => false, 'message' => 'Listing not found or not active.']);
            exit();
        }
 
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
 
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['listing_id'] === $listing_id) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        unset($item);
 
        if (!$found) {
            $_SESSION['cart'][] = [
                'listing_id'    => $listing_id,
                'title'         => $listing['title'],
                'business_name' => $listing['business_name'],
                'price'         => floatval($listing['price']),
                'quantity'      => $quantity,
                'image'         => $listing['primary_image'] ?? ''
            ];
        }
 
        $cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));
        echo json_encode(['success' => true, 'message' => 'Added to cart.', 'cart_count' => $cart_count]);
        exit();
    }
 
    // Cart: Get Count 
    if ($_POST['action'] === 'get_cart_count') {
        $count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
        echo json_encode(['success' => true, 'count' => $count]);
        exit();
    }
 
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit();
}
 
// PAGE SETUP
$cart_count   = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
$is_logged_in = isset($_SESSION['user_id']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse — CultureConnect</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="br-page-wrap">
 
<?php include '../components/header.php'; ?>

<!-- ── Hero ──────────────────────────────────────────────────── -->
<div class="br-hero">
    <div class="br-hero-inner">
        <div>
            <h1 class="br-hero-title">Browse Cultural Products &amp; Services</h1>
            <p class="br-hero-subtitle">Discover local cultural offerings from Hertfordshire businesses.</p>
        </div>
 
        <!-- Cart button -->
        <a href="../pages/cart.php" class="br-cart-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
            </svg>
            Cart
            <span class="br-cart-badge" id="br-cart-badge"><?= $cart_count > 0 ? $cart_count : '' ?></span>
        </a>
    </div>
</div>
 
<!-- ── Main container ────────────────────────────────────────── -->
<div class="br-container">
 
    <!-- Filter bar -->
    <div class="br-filter-bar">
 
        <!-- Search -->
        <div class="br-search-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18" class="br-search-icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input type="text" id="br-search" class="br-search-input"
                   placeholder="Search listings, businesses…"
                   oninput="brDebounce()">
            <button class="br-search-clear" id="br-search-clear" onclick="brClearSearch()" style="display:none;">&times;</button>
        </div>
 
        <!-- Selects row -->
        <div class="br-selects-row">
 
            <div class="br-select-group">
                <span class="br-select-label">Category</span>
                <select id="br-cat-select" class="br-select" onchange="brCategoryChange()">
                    <option value="0">All Categories</option>
                </select>
            </div>
 
            <div class="br-select-group">
                <span class="br-select-label">Subcategory</span>
                <select id="br-sub-select" class="br-select" onchange="brLoad()">
                    <option value="0">All Subcategories</option>
                </select>
            </div>
 
            <div class="br-select-group">
                <span class="br-select-label">Area</span>
                <select id="br-area-select" class="br-select" onchange="brLoad()">
                    <option value="0">All Areas</option>
                </select>
            </div>
 
            <div class="br-price-wrap">
                <span class="br-price-label">Max Price: <span class="br-price-val" id="br-price-val">Any</span></span>
                <input type="range" id="br-price-range" class="br-price-range"
                       min="0" max="200" value="200" step="5"
                       oninput="brPriceChange(this.value)">
            </div>
 
        </div>
    </div>
 
    <!-- Results bar -->
    <div class="br-results-bar">
        <span id="br-results-count" class="br-results-count"></span>
        <button class="br-reset-link" onclick="brReset()">Reset filters</button>
    </div>
 
    <!-- Loading -->
    <div id="br-loading" class="br-loading">
        <div class="br-spinner"></div>
        <p>Loading listings…</p>
    </div>
 
    <!-- Grid -->
    <div id="br-grid" class="br-grid" style="display:none;"></div>
 
    <!-- Empty -->
    <div id="br-empty" class="br-empty" style="display:none;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="52" height="52">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 16.318A4.486 4.486 0 0 0 12.016 15a4.486 4.486 0 0 0-3.198 1.318M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
        </svg>
        <p>No listings match your filters.</p>
        <button class="br-reset-link" onclick="brReset()">Clear all filters</button>
    </div>
 
</div>
 
<?php include '../components/footer.php'; ?>

<script>
    let brAllSubs  = [];
    let brDebTimer = null;
    let brMaxPrice = 200;
    let brPriceMax = '';
 
    document.addEventListener('DOMContentLoaded', () => {
        brLoadFilters();
        brUpdateBadge(<?= $cart_count ?>);
    });
 
    function brLoadFilters() {
        const fd = new FormData();
        fd.append('action', 'get_filters');
 
        fetch('../pages/browse.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (!data.success) return;
 
                    brAllSubs  = data.subs;
                    brMaxPrice = data.max_price;
 
                    const catSel = document.getElementById('br-cat-select');
                    data.categories.forEach(c => {
                        const o = document.createElement('option');
                        o.value = c.category_id; o.textContent = c.category_name;
                        catSel.appendChild(o);
                    });
 
                    const areaSel = document.getElementById('br-area-select');
                    data.areas.forEach(a => {
                        const o = document.createElement('option');
                        o.value = a.area_id; o.textContent = a.area_name;
                        areaSel.appendChild(o);
                    });
 
                    const range = document.getElementById('br-price-range');
                    range.max   = brMaxPrice;
                    range.value = brMaxPrice;
 
                    brLoad();
                } catch(e) { console.error(e); }
            });
    }
 
    function brCategoryChange() {
        const catId  = parseInt(document.getElementById('br-cat-select').value);
        const subSel = document.getElementById('br-sub-select');
        subSel.innerHTML = '<option value="0">All Subcategories</option>';
 
        if (catId > 0) {
            brAllSubs.filter(s => s.category_id == catId).forEach(s => {
                const o = document.createElement('option');
                o.value = s.subcategory_id; o.textContent = s.subcategory_name;
                subSel.appendChild(o);
            });
        }
        brLoad();
    }
 
    function brPriceChange(val) {
        const max = parseInt(document.getElementById('br-price-range').max);
        if (parseInt(val) >= max) {
            brPriceMax = '';
            document.getElementById('br-price-val').textContent = 'Any';
        } else {
            brPriceMax = val;
            document.getElementById('br-price-val').textContent = '£' + val;
        }
        brLoad();
    }
 
    function brDebounce() {
        const val = document.getElementById('br-search').value;
        document.getElementById('br-search-clear').style.display = val ? 'block' : 'none';
        clearTimeout(brDebTimer);
        brDebTimer = setTimeout(brLoad, 350);
    }
 
    function brClearSearch() {
        document.getElementById('br-search').value = '';
        document.getElementById('br-search-clear').style.display = 'none';
        brLoad();
    }
 
    function brReset() {
        document.getElementById('br-search').value = '';
        document.getElementById('br-search-clear').style.display = 'none';
        document.getElementById('br-cat-select').value  = '0';
        document.getElementById('br-sub-select').value  = '0';
        document.getElementById('br-area-select').value = '0';
        document.getElementById('br-sub-select').innerHTML = '<option value="0">All Subcategories</option>';
        const range = document.getElementById('br-price-range');
        range.value = range.max;
        brPriceMax  = '';
        document.getElementById('br-price-val').textContent = 'Any';
        brLoad();
    }
 
    function brLoad() {
        const grid    = document.getElementById('br-grid');
        const loading = document.getElementById('br-loading');
        const empty   = document.getElementById('br-empty');
        const count   = document.getElementById('br-results-count');
 
        grid.style.display    = 'none';
        empty.style.display   = 'none';
        loading.style.display = 'flex';
 
        const fd = new FormData();
        fd.append('action',      'get_listings');
        fd.append('search',      document.getElementById('br-search').value.trim());
        fd.append('category_id', document.getElementById('br-cat-select').value);
        fd.append('sub_id',      document.getElementById('br-sub-select').value);
        fd.append('area_id',     document.getElementById('br-area-select').value);
        fd.append('price_max',   brPriceMax);
 
        fetch('../pages/browse.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    loading.style.display = 'none';
 
                    if (data.success && data.listings.length > 0) {
                        count.textContent  = `Showing ${data.count} listing${data.count !== 1 ? 's' : ''}`;
                        grid.innerHTML     = '';
                        data.listings.forEach(l => grid.appendChild(brBuildCard(l)));
                        grid.style.display = 'grid';
                    } else {
                        count.textContent   = '0 listings found';
                        empty.style.display = 'flex';
                    }
                } catch(e) {
                    loading.style.display = 'none';
                    empty.style.display   = 'flex';
                }
            })
            .catch(() => {
                loading.style.display = 'none';
                empty.style.display   = 'flex';
            });
    }
 
    function brBuildCard(l) {
        const card  = document.createElement('div');
        card.className = 'br-card';
 
        const price     = parseFloat(l.price);
        const imgSrc    = l.primary_image ? `../uploads/listings_images/${brEsc(l.primary_image)}` : null;
        const priceTier = price <= 20 ? 'affordable' : price <= 50 ? 'moderate' : 'premium';
        const tierLabel = price <= 20 ? 'Affordable'  : price <= 50 ? 'Moderate'  : 'Premium';
        const catClass  = l.category_name === 'Product' ? 'br-badge-cat--product' : 'br-badge-cat--service';
 
        card.innerHTML = `
            <div class="br-card-image-wrap" onclick="brGoTo(${l.listing_id})">
                ${imgSrc
                    ? `<img src="${imgSrc}" alt="${brEsc(l.title)}" class="br-card-img"
                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
                    : ''}
                <div class="br-card-img-placeholder"${imgSrc ? ' style="display:none;"' : ''}>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="36" height="36">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </div>
                <span class="br-badge-cat ${catClass}">${brEsc(l.category_name)}</span>
                <span class="br-badge-price br-badge-price--${priceTier}">${tierLabel}</span>
            </div>
            <div class="br-card-body">
                <p class="br-card-business">${brEsc(l.business_name)}</p>
                <h3 class="br-card-title" onclick="brGoTo(${l.listing_id})">${brEsc(l.title)}</h3>
                <p class="br-card-caption">${brEsc(l.caption ?? '')}</p>
                <div class="br-cultural-benefit">
                    <strong>Subcategory:</strong> ${brEsc(l.subcategory_name)}
                </div>
                <div class="br-card-footer">
                    <span class="br-card-price">£${price.toFixed(2)}</span>
                    <span class="br-card-area">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="12" height="12">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                        ${brEsc(l.area_name)}
                    </span>
                </div>
                <button class="br-card-add-btn" onclick="brAddToCart(${l.listing_id}, this)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                    Add to Cart
                </button>
            </div>
        `;
 
        return card;
    }
 
    function brGoTo(listing_id) {
        window.location.href = '../pages/listing-detail.php?listing_id=' + listing_id;
    }
 
    function brAddToCart(listing_id, btn) {
        btn.disabled    = true;
        btn.textContent = 'Adding…';
 
        const fd = new FormData();
        fd.append('action',     'add_to_cart');
        fd.append('listing_id', listing_id);
        fd.append('quantity',   1);
 
        fetch('../pages/browse.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        brUpdateBadge(data.cart_count);
                        brShowToast('Added to cart!', 'success');
                        btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg> Added!`;
                        setTimeout(() => {
                            btn.disabled  = false;
                            btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg> Add to Cart`;
                        }, 2000);
                    } else {
                        brShowToast(data.message || 'Failed to add.', 'error');
                        btn.disabled    = false;
                        btn.textContent = 'Add to Cart';
                    }
                } catch(e) {
                    brShowToast('Error. Please try again.', 'error');
                    btn.disabled    = false;
                    btn.textContent = 'Add to Cart';
                }
            });
    }
 
    function brUpdateBadge(count) {
        const badge = document.getElementById('br-cart-badge');
        if (!badge) return;
        badge.textContent = count > 0 ? count : '';
        badge.classList.add('br-cart-badge--bump');
        setTimeout(() => badge.classList.remove('br-cart-badge--bump'), 300);
    }
 
    function brShowToast(message, type) {
        const existing = document.getElementById('br-toast');
        if (existing) existing.remove();
        const toast = document.createElement('div');
        toast.id          = 'br-toast';
        toast.className   = `br-toast br-toast--${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('br-toast--show'), 10);
        setTimeout(() => { toast.classList.remove('br-toast--show'); setTimeout(() => toast.remove(), 300); }, 2500);
    }
 
    function brEsc(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
</script>
 
</body>
</html>