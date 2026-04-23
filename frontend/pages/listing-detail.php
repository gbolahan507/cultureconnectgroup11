<?php
// SESSION
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../db_connection.php';
 
// AJAX HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
 
    // Add to Cart
    if ($_POST['action'] === 'add_to_cart') {
        $listing_id = intval($_POST['listing_id'] ?? 0);
        $quantity   = max(1, intval($_POST['quantity'] ?? 1));
        if (!$listing_id) { echo json_encode(['success' => false, 'message' => 'Invalid listing.']); exit(); }
 
        $stmt = $conn->prepare("
            SELECT l.listing_id, l.title, l.price, sp.business_name, li.image_url AS primary_image
            FROM listings l
            JOIN sme_profiles sp  ON l.sme_id  = sp.sme_id
            LEFT JOIN listing_images li ON l.listing_id = li.listing_id AND li.is_primary = 1
            WHERE l.listing_id = ? AND l.status = 'active' LIMIT 1
        ");
        $stmt->bind_param("i", $listing_id);
        $stmt->execute();
        $result  = $stmt->get_result();
        $listing = $result->fetch_assoc();
        $result->free();
        $stmt->close();
 
        if (!$listing) { echo json_encode(['success' => false, 'message' => 'Listing not found or not active.']); exit(); }
 
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['listing_id'] === $listing_id) { $item['quantity'] += $quantity; $found = true; break; }
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
 
    // Toggle Vote
    if ($_POST['action'] === 'toggle_vote') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Resident') {
            echo json_encode(['success' => false, 'message' => 'You must be logged in as a resident to vote.']);
            exit();
        }
        $user_id    = intval($_SESSION['user_id']);
        $listing_id = intval($_POST['listing_id'] ?? 0);
        $vote_type  = $_POST['vote_type'] ?? '';
 
        if (!$listing_id || !in_array($vote_type, ['like', 'dislike'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid vote.']); exit();
        }
 
        $check = $conn->prepare("SELECT vote_id, vote_type FROM listing_votes WHERE user_id = ? AND listing_id = ?");
        $check->bind_param("ii", $user_id, $listing_id);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();
 
        if ($existing) {
            if ($existing['vote_type'] === $vote_type) {
                $del = $conn->prepare("DELETE FROM listing_votes WHERE vote_id = ?");
                $del->bind_param("i", $existing['vote_id']);
                $del->execute(); $del->close();
                $new_vote = null;
            } else {
                $upd = $conn->prepare("UPDATE listing_votes SET vote_type = ?, created_at = NOW() WHERE vote_id = ?");
                $upd->bind_param("si", $vote_type, $existing['vote_id']);
                $upd->execute(); $upd->close();
                $new_vote = $vote_type;
            }
        } else {
            $ins = $conn->prepare("INSERT INTO listing_votes (user_id, listing_id, vote_type) VALUES (?, ?, ?)");
            $ins->bind_param("iis", $user_id, $listing_id, $vote_type);
            $ins->execute(); $ins->close();
            $new_vote = $vote_type;
        }
 
        $counts = $conn->prepare("SELECT COALESCE(SUM(vote_type='like'),0) AS likes, COALESCE(SUM(vote_type='dislike'),0) AS dislikes FROM listing_votes WHERE listing_id = ?");
        $counts->bind_param("i", $listing_id);
        $counts->execute();
        $row = $counts->get_result()->fetch_assoc();
        $counts->close();
 
        echo json_encode(['success' => true, 'new_vote' => $new_vote, 'likes' => intval($row['likes']), 'dislikes' => intval($row['dislikes'])]);
        exit();
    }

    // Get reviews for this listing
if ($_POST['action'] === 'get_reviews') {
    $listing_id = intval($_POST['listing_id'] ?? 0);
    $stmt = $conn->prepare("
        SELECT r.rating, r.comment, r.created_at,
               rp.first_name, rp.last_name
        FROM product_service_reviews r
        JOIN resident_profiles rp ON r.user_id = rp.user_id
        WHERE r.listing_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->bind_param("i", $listing_id);
    $stmt->execute();
    $result  = $stmt->get_result();
    $reviews = [];
    while ($row = $result->fetch_assoc()) $reviews[] = $row;
    $result->free();
    $stmt->close();

    $avg = !empty($reviews) ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1) : 0;
    echo json_encode(['success' => true, 'reviews' => $reviews, 'avg' => $avg]);
    exit();
}

// Submit review from listing detail page
if ($_POST['action'] === 'submit_review') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Resident') {
        echo json_encode(['success' => false, 'message' => 'Only residents can leave reviews.']);
        exit();
    }
    $user_id    = intval($_SESSION['user_id']);
    $listing_id = intval($_POST['listing_id'] ?? 0);
    $rating     = intval($_POST['rating']     ?? 0);
    $comment    = trim($_POST['comment']      ?? '');

    if ($rating < 1 || $rating > 10) {
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 10.']);
        exit();
    }

    // Verify purchase
    $check = $conn->prepare("
        SELECT oi.order_item_id FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        WHERE o.user_id = ? AND oi.listing_id = ? AND o.status = 'completed' LIMIT 1
    ");
    $check->bind_param("ii", $user_id, $listing_id);
    $check->execute();
    $purchased = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$purchased) {
        echo json_encode(['success' => false, 'message' => 'You can only review listings you have purchased.']);
        exit();
    }

    // Check duplicate
    $dup = $conn->prepare("SELECT review_id FROM product_service_reviews WHERE user_id = ? AND listing_id = ?");
    $dup->bind_param("ii", $user_id, $listing_id);
    $dup->execute();
    if ($dup->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this listing.']);
        exit();
    }
    $dup->close();

    $stmt = $conn->prepare("INSERT INTO product_service_reviews (user_id, listing_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $user_id, $listing_id, $rating, $comment);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Review submitted!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit review.']);
    }
    $stmt->close();
    exit();
}
 
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit();
}
 

// PAGE SETUP
$listing_id = intval($_GET['listing_id'] ?? 0);
if (!$listing_id) { header('Location: ../pages/browse.php'); exit(); }
 
// Fetch listing
$stmt = $conn->prepare("
    SELECT l.listing_id, l.title, l.caption, l.description, l.price, l.status, l.created_at,
           ps.item_name, pss.subcategory_name, pc.category_name,
           sp.business_name, sp.description AS business_description, sp.phone AS business_phone,
           u.email_address AS business_email,
           a.area_name, a.description AS area_description
    FROM listings l
    JOIN product_service ps ON l.item_id  = ps.item_id
    JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
    JOIN product_service_categories pc ON pss.category_id  = pc.category_id
    JOIN sme_profiles sp  ON l.sme_id  = sp.sme_id
    JOIN users u ON sp.user_id = u.user_id
    JOIN areas a  ON sp.area_id = a.area_id
    WHERE l.listing_id = ? AND l.status = 'active' LIMIT 1
");
$stmt->bind_param("i", $listing_id);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$listing) { header('Location: ../pages/browse.php'); exit(); }
 
// Fetch images
$img_stmt = $conn->prepare("SELECT image_url FROM listing_images WHERE listing_id = ? ORDER BY is_primary DESC, image_id ASC");
$img_stmt->bind_param("i", $listing_id);
$img_stmt->execute();
$img_result = $img_stmt->get_result();
$images     = [];
while ($img = $img_result->fetch_assoc()) $images[] = $img['image_url'];
$img_result->free();
$img_stmt->close();
 
// Fetch vote counts
$vc_stmt = $conn->prepare("SELECT COALESCE(SUM(vote_type='like'),0) AS likes, COALESCE(SUM(vote_type='dislike'),0) AS dislikes FROM listing_votes WHERE listing_id = ?");
$vc_stmt->bind_param("i", $listing_id);
$vc_stmt->execute();
$vc_result   = $vc_stmt->get_result();
$vote_counts = $vc_result->fetch_assoc();
$vc_result->free();
$vc_stmt->close();
 
// Fetch user's current vote
$user_vote = null;
if (isset($_SESSION['user_id'])) {
    $uv = $conn->prepare("SELECT vote_type FROM listing_votes WHERE user_id = ? AND listing_id = ?");
    $uv->bind_param("ii", $_SESSION['user_id'], $listing_id);
    $uv->execute();
    $uv_result = $uv->get_result();
    $uv_row    = $uv_result->fetch_assoc();
    $uv_result->free();
    $user_vote = $uv_row['vote_type'] ?? null;
    $uv->close();
}

// Check if resident has purchased and reviewed this listing
$can_review     = false;
$already_reviewed = false;
if ($is_resident) {
    $uid = intval($_SESSION['user_id']);
    $pur = $conn->prepare("
        SELECT oi.order_item_id FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        WHERE o.user_id = ? AND oi.listing_id = ? AND o.status = 'completed' LIMIT 1
    ");
    $pur->bind_param("ii", $uid, $listing_id);
    $pur->execute();
    $pur_result = $pur->get_result();
    $can_review = $pur_result->fetch_assoc() ? true : false;
    $pur_result->free();
    $pur->close();

    if ($can_review) {
        $rev = $conn->prepare("SELECT review_id, rating FROM product_service_reviews WHERE user_id = ? AND listing_id = ?");
        $rev->bind_param("ii", $uid, $listing_id);
        $rev->execute();
        $rev_result     = $rev->get_result();
        $existing_review = $rev_result->fetch_assoc();
        $already_reviewed = $existing_review ? true : false;
        $rev_result->free();
        $rev->close();
    }
  }
 
// Fetch related listings
$rel_stmt = $conn->prepare("
    SELECT l.listing_id, l.title, l.price, pc.category_name, li.image_url AS primary_image
    FROM listings l
    JOIN sme_profiles sp   ON l.sme_id  = sp.sme_id
    JOIN product_service ps    ON l.item_id  = ps.item_id
    JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
    JOIN product_service_categories pc  ON pss.category_id   = pc.category_id
    LEFT JOIN listing_images li    ON l.listing_id      = li.listing_id AND li.is_primary = 1
    WHERE sp.business_name = ? AND l.listing_id != ? AND l.status = 'active'
    LIMIT 3
");
$rel_stmt->bind_param("si", $listing['business_name'], $listing_id);
$rel_stmt->execute();
$rel_result = $rel_stmt->get_result();
$related    = [];
while ($row = $rel_result->fetch_assoc()) $related[] = $row;
$rel_result->free();
$rel_stmt->close();
 
function ld_safe(string $val): string { return htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); }
 
$cart_count  = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
$is_logged_in = isset($_SESSION['user_id']);
$is_resident  = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Resident';
$price        = floatval($listing['price']);
$price_tier   = $price <= 20 ? 'Affordable' : ($price <= 50 ? 'Moderate' : 'Premium');
$cat_type     = $listing['category_name'] === 'Product' ? 'Product' : 'Service';
$btn_label    = $cat_type === 'Product' ? 'Buy Now' : 'Book Now';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ld_safe($listing['title']) ?> — CultureConnect</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="ld-page-wrap">
 
<?php include '../components/header.php'; ?>
 
<!-- Sub-nav -->
<div class="ld-subnav">
    <div class="ld-subnav-inner">
        <nav class="ld-breadcrumb">
            <a href="../pages/browse.php">Browse</a>
            <span> › </span><span><?= ld_safe($listing['category_name']) ?></span>
            <span> › </span><span><?= ld_safe($listing['subcategory_name']) ?></span>
            <span> ›</span><span class="ld-breadcrumb-current"><?= ld_safe($listing['title']) ?></span>
        </nav>
        <a href="../pages/cart.php" class="ld-cart-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
            </svg>
            Cart <span class="ld-cart-badge" id="ld-cart-badge"><?= $cart_count > 0 ? $cart_count : '' ?></span>
        </a>
    </div>
</div>
 
<div class="ld-container">
    <div class="ld-main-grid">
 
        <!-- LEFT: Images -->
        <div class="ld-left">
            <div class="ld-carousel">
                <?php if (!empty($images)) : ?>
                <div class="ld-carousel-stage" id="ld-stage">
                    <img id="ld-main-img" src="../uploads/listings_images/<?= ld_safe($images[0]) ?>" alt="<?= ld_safe($listing['title']) ?>" class="ld-carousel-img">
                    <?php if (count($images) > 1) : ?>
                    <button class="ld-carousel-arrow ld-carousel-arrow--prev" onclick="ldPrev()">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                    </button>
                    <button class="ld-carousel-arrow ld-carousel-arrow--next" onclick="ldNext()">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                    </button>
                    <div class="ld-carousel-dots" id="ld-dots"></div>
                    <div class="ld-carousel-counter" id="ld-counter">1 / <?= count($images) ?></div>
                    <?php endif; ?>
                </div>
                <?php if (count($images) > 1) : ?>
                <div class="ld-thumbs">
                    <?php foreach ($images as $i => $img) : ?>
                    <div class="ld-thumb <?= $i === 0 ? 'ld-thumb--active' : '' ?>" id="ld-thumb-<?= $i ?>" onclick="ldGoTo(<?= $i ?>)">
                        <img src="../uploads/listings_images/<?= ld_safe($img) ?>" alt="Image <?= $i + 1 ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php else : ?>
                <div class="ld-carousel-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="52" height="52"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                    <p>No images available</p>
                </div>
                <?php endif; ?>
            </div>
 
            <div class="ld-info-card" style="margin-top:20px;">
                <p class="ld-section-title">About This Listing</p>
                <p class="ld-description"><?= ld_safe($listing['description']) ?></p>
            </div>

            <!-- Reviews Section -->
<div class="ld-reviews-section">
    <div class="ld-info-card">
        <p class="ld-section-title">
            Reviews
            <span id="ld-review-count" class="ld-review-count-badge"></span>
        </p>

        <!-- Review form — only for residents that bought a listing -->
        <?php if ($is_resident && $can_review && !$already_reviewed) : ?>
        <div class="ld-review-form-wrap" id="ld-review-form-wrap">
            <p class="ld-review-form-label">You purchased this — leave a review!</p>
            <div class="ld-star-row" id="ld-stars">
                <?php for ($n = 1; $n <= 10; $n++) : ?>
                <button class="ld-star-btn" onclick="ldSetRating(<?= $n ?>)" data-val="<?= $n ?>">★</button>
                <?php endfor; ?>
            </div>
            <span class="ld-rating-display" id="ld-rating-display">Select a rating</span>
            <textarea class="ld-review-textarea" id="ld-review-comment"
                placeholder="Share your experience (optional)..."></textarea>
            <button class="ld-submit-review-btn" onclick="ldSubmitReview()">Submit Review</button>
        </div>
        <?php elseif ($is_resident && $already_reviewed) : ?>
        <div class="ld-already-reviewed">
            You reviewed this listing — <?= htmlspecialchars($existing_review['rating']) ?>/10
        </div>
        <?php endif; ?>

        <!-- Reviews list -->
        <div id="ld-reviews-list">
            <div class="ld-reviews-loading">Loading reviews…</div>
        </div>
        </div>
        </div>
        </div>
 
        <!-- Info -->
        <div class="ld-right">
            <div class="ld-info-card">
                <div class="ld-badges">
                    <span class="ld-badge ld-badge--category"><?= ld_safe($listing['category_name']) ?></span>
                    <span class="ld-badge ld-badge--sub"><?= ld_safe($listing['subcategory_name']) ?></span>
                    <span class="ld-badge ld-badge--item"><?= ld_safe($listing['item_name']) ?></span>
                </div>
 
                <p class="ld-business-label"><?= ld_safe($listing['business_name']) ?></p>
                <h1 class="ld-title"><?= ld_safe($listing['title']) ?></h1>
                <p class="ld-caption"><?= ld_safe($listing['caption']) ?></p>
 
                <div class="ld-price-row">
                    <span class="ld-price">£<?= number_format($price, 2) ?></span>
                    <span class="ld-price-tier ld-price-tier--<?= strtolower($price_tier) ?>"><?= $price_tier ?></span>
                </div>
 
                <p class="ld-area-tag">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                    <?= ld_safe($listing['area_name']) ?>
                </p>
 
                <!-- Vote buttons -->
                <div class="ld-vote-row">
                    <button class="ld-vote-btn ld-vote-btn--like <?= $user_vote === 'like' ? 'ld-vote-btn--active-like' : '' ?>"
                            id="ld-like-btn" onclick="ldVote('like')">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" /></svg>
                        <span id="ld-likes-count"><?= intval($vote_counts['likes']) ?></span>
                    </button>
                    <button class="ld-vote-btn ld-vote-btn--dislike <?= $user_vote === 'dislike' ? 'ld-vote-btn--active-dislike' : '' ?>"
                            id="ld-dislike-btn" onclick="ldVote('dislike')">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M7.498 15.25H4.372c-1.026 0-1.945-.694-2.054-1.715a12.137 12.137 0 0 1-.068-1.285c0-2.848.992-5.464 2.649-7.521C5.287 4.247 5.886 4 6.504 4h4.016a4.5 4.5 0 0 1 1.423.23l3.114 1.04a4.5 4.5 0 0 0 1.423.23h1.294M7.498 15.25c.618 0 .991.724.725 1.282A7.471 7.471 0 0 0 7.5 19.75 2.25 2.25 0 0 0 9.75 22a.75.75 0 0 0 .75-.75v-.633c0-.573.11-1.14.322-1.672.304-.76.93-1.33 1.653-1.715a9.04 9.04 0 0 0 2.86-2.4c.498-.634 1.226-1.08 2.032-1.08h.384m-10.253 1.5H9.7m8.075-9.75c.01.05.027.1.05.148.593 1.2.925 2.55.925 3.977 0 1.487-.36 2.89-.999 4.125m.023-8.25c-.076-.365.183-.75.575-.75h.908c.889 0 1.713.518 1.972 1.368.339 1.11.521 2.287.521 3.507 0 1.553-.295 3.036-.831 4.398-.306.774-1.086 1.227-1.918 1.227h-1.053c-.472 0-.745-.556-.5-.96a8.95 8.95 0 0 0 .303-.54" /></svg>
                        <span id="ld-dislikes-count"><?= intval($vote_counts['dislikes']) ?></span>
                    </button>
                </div>
 
                <!-- Order box -->
                <div class="ld-order-box">
                    <div class="ld-qty-wrap">
                        <label class="ld-qty-label" for="ld-qty">Quantity</label>
                        <div class="ld-qty-controls">
                            <button class="ld-qty-btn" onclick="ldChangeQty(-1)">−</button>
                            <input type="number" id="ld-qty" class="ld-qty-input" value="1" min="1" max="99">
                            <button class="ld-qty-btn" onclick="ldChangeQty(1)">+</button>
                        </div>
                    </div>
 
                    <button class="ld-add-cart-btn" id="ld-add-cart-btn" onclick="ldAddToCart()">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
                        Add to Cart
                    </button>
 
                    <?php if ($is_resident) : ?>
                    <a href="../pages/cart.php" class="ld-checkout-btn">
                        <?= $btn_label ?> — £<span id="ld-total"><?= number_format($price, 2) ?></span>
                    </a>
                    <?php else : ?>
                    <button class="ld-checkout-btn" onclick="ldShowLoginModal('book')">
                        <?= $btn_label ?> — £<?= number_format($price, 2) ?>
                    </button>
                    <?php endif; ?>
                </div>
 
                <?php if ($is_resident) : ?>
                <p class="ld-payment-note">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                    Payment is confirmed on booking. The business will contact you shortly after your order is placed.
                </p>
                <?php else : ?>
                <p class="ld-payment-note">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                    Only residents can place orders.
                    <a href="../pages/login.php" style="color:#e00180;font-weight:600;">Log in</a> or
                    <a href="../pages/register.php" style="color:#e00180;font-weight:600;">register</a>.
                </p>
                <?php endif; ?>
            </div>
 
            <!-- Business card -->
            <div class="ld-info-card">
                <p class="ld-section-title">About the Business</p>
                <p class="ld-business-name-lg"><?= ld_safe($listing['business_name']) ?></p>
                <?php if (!empty($listing['business_description'])) : ?>
                <p class="ld-business-desc"><?= ld_safe($listing['business_description']) ?></p>
                <?php endif; ?>
                <?php if (!empty($listing['business_phone'])) : ?>
                <div class="ld-contact-row">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 6v.75Z" /></svg>
                    <a href="tel:<?= ld_safe($listing['business_phone']) ?>"><?= ld_safe($listing['business_phone']) ?></a>
                </div>
                <?php endif; ?>
                <?php if (!empty($listing['business_email'])) : ?>
                <div class="ld-contact-row">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                    <a href="mailto:<?= ld_safe($listing['business_email']) ?>"><?= ld_safe($listing['business_email']) ?></a>
                </div>
                <?php endif; ?>
            </div>
 
            <!-- Area card -->
            <div class="ld-info-card">
                <p class="ld-section-title">Location</p>
                <p class="ld-business-name-lg"><?= ld_safe($listing['area_name']) ?></p>
                <?php if (!empty($listing['area_description'])) : ?>
                <p class="ld-business-desc"><?= ld_safe($listing['area_description']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
 
    <!-- Related -->
    <?php if (!empty($related)) : ?>
    <div class="ld-related-section">
        <h2 class="ld-related-title">More from <?= ld_safe($listing['business_name']) ?></h2>
        <div class="ld-related-grid">
            <?php foreach ($related as $r) : ?>
            <a href="listing-detail.php?listing_id=<?= intval($r['listing_id']) ?>" class="ld-related-card">
                <div class="ld-related-img-wrap">
                    <?php if (!empty($r['primary_image'])) : ?>
                    <img src="../uploads/listings_images/<?= ld_safe($r['primary_image']) ?>" alt="<?= ld_safe($r['title']) ?>" onerror="this.parentElement.classList.add('ld-related-img-wrap--broken')">
                    <?php endif; ?>
                </div>
                <div class="ld-related-body">
                    <p class="ld-related-cat"><?= ld_safe($r['category_name']) ?></p>
                    <p class="ld-related-title-text"><?= ld_safe($r['title']) ?></p>
                    <p class="ld-related-price">£<?= number_format(floatval($r['price']), 2) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
 
</div>
 
<!-- Login prompt  -->
<div id="ld-login-modal" class="ld-login-modal-overlay" style="display:none;">
    <div class="ld-login-modal-box">
        <button class="ld-login-modal-close" onclick="ldCloseLoginModal()">&times;</button>
        <div class="ld-login-modal-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="32" height="32">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
        </div>
        <h3 id="ld-modal-heading">Resident Account Required</h3>
        <p id="ld-modal-message">You need a resident account to continue.</p>
        <div class="ld-login-modal-actions">
            <a href="../pages/login.php"    class="ld-login-modal-btn ld-login-modal-btn--primary">Log In</a>
            <a href="../pages/register.php" class="ld-login-modal-btn ld-login-modal-btn--secondary">Create Account</a>
        </div>
        <button class="ld-login-modal-dismiss" onclick="ldCloseLoginModal()">Maybe later</button>
    </div>
</div>
 
<?php include '../components/footer.php'; ?>
 
<script>
    const LD_IMAGES      = <?= json_encode($images) ?>;
    const LD_PRICE       = <?= $price ?>;
    const LD_LISTING_ID  = <?= $listing_id ?>;
    const LD_IS_LOGGED   = <?= $is_logged_in ? 'true' : 'false' ?>;
    const LD_IS_RESIDENT = <?= $is_resident  ? 'true' : 'false' ?>;
    let ldIndex = 0;
    let ldSelectedRating = 0;
 
    document.addEventListener('DOMContentLoaded', () => {
        // Build dots
        const dotsEl = document.getElementById('ld-dots');
        if (dotsEl && LD_IMAGES.length > 1) {
            LD_IMAGES.forEach((_, i) => {
                const btn = document.createElement('button');
                btn.className = 'ld-carousel-dot' + (i === 0 ? ' ld-carousel-dot--active' : '');
                btn.onclick = () => ldGoTo(i);
                dotsEl.appendChild(btn);
            });
        }
        ldUpdateTotal();
        ldLoadReviews();
        const qtyInput = document.getElementById('ld-qty');
        if (qtyInput) qtyInput.addEventListener('input', ldUpdateTotal);
    });
 
    document.addEventListener('click', (e) => {
        const modal = document.getElementById('ld-login-modal');
        if (modal && e.target === modal) ldCloseLoginModal();
    });
 
    // Carousel 
    function ldGoTo(index) {
        ldIndex = index;
        const imgEl = document.getElementById('ld-main-img');
        if (imgEl) imgEl.src = '../uploads/listings_images/' + LD_IMAGES[index];
        const counter = document.getElementById('ld-counter');
        if (counter) counter.textContent = `${index + 1} / ${LD_IMAGES.length}`;
        document.querySelectorAll('.ld-carousel-dot').forEach((d, i) => d.classList.toggle('ld-carousel-dot--active', i === index));
        document.querySelectorAll('.ld-thumb').forEach((t, i) => t.classList.toggle('ld-thumb--active', i === index));
    }
    function ldPrev() { ldGoTo((ldIndex - 1 + LD_IMAGES.length) % LD_IMAGES.length); }
    function ldNext() { ldGoTo((ldIndex + 1) % LD_IMAGES.length); }
 
    //  Quantity 
    function ldChangeQty(delta) {
        const input = document.getElementById('ld-qty');
        let val = Math.min(99, Math.max(1, parseInt(input.value) + delta));
        input.value = val;
        ldUpdateTotal();
    }
    function ldUpdateTotal() {
        const qty = parseInt(document.getElementById('ld-qty')?.value ?? 1);
        const el  = document.getElementById('ld-total');
        if (el) el.textContent = (LD_PRICE * qty).toFixed(2);
    }
 
    // Add to Cart 
    function ldAddToCart() {
        const qty = parseInt(document.getElementById('ld-qty').value ?? 1);
        const btn = document.getElementById('ld-add-cart-btn');
        btn.disabled = true; btn.textContent = 'Adding…';
 
        const fd = new FormData();
        fd.append('action',     'add_to_cart');
        fd.append('listing_id', LD_LISTING_ID);
        fd.append('quantity',   qty);
 
        fetch('../pages/listing-detail.php?listing_id=' + LD_LISTING_ID, { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        const badge = document.getElementById('ld-cart-badge');
                        if (badge) { badge.textContent = data.cart_count; badge.classList.add('ld-cart-badge--bump'); setTimeout(() => badge.classList.remove('ld-cart-badge--bump'), 300); }
                        ldShowToast('Added to cart!', 'success');
                        btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg> Added!`;
                        setTimeout(() => { btn.disabled = false; btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg> Add to Cart`; }, 2000);
                    } else { ldShowToast(data.message || 'Failed to add.', 'error'); btn.disabled = false; btn.textContent = 'Add to Cart'; }
                } catch(e) { ldShowToast('Error. Please try again.', 'error'); btn.disabled = false; btn.textContent = 'Add to Cart'; }
            });
    }
 
    // Voting
    function ldVote(vote_type) {
        if (!LD_IS_LOGGED) {
            document.getElementById('ld-modal-heading').textContent = 'Want to vote on this listing?';
            document.getElementById('ld-modal-message').textContent = 'Create a free resident account or log in to like and dislike listings.';
            ldShowLoginModal();
            return;
        }
        if (!LD_IS_RESIDENT) { ldShowToast('Only residents can like or dislike listings.', 'error'); return; }
 
        const fd = new FormData();
        fd.append('action',     'toggle_vote');
        fd.append('listing_id', LD_LISTING_ID);
        fd.append('vote_type',  vote_type);
 
        fetch('../pages/listing-detail.php?listing_id=' + LD_LISTING_ID, { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        document.getElementById('ld-likes-count').textContent    = data.likes;
                        document.getElementById('ld-dislikes-count').textContent = data.dislikes;
                        document.getElementById('ld-like-btn').classList.toggle('ld-vote-btn--active-like',       data.new_vote === 'like');
                        document.getElementById('ld-dislike-btn').classList.toggle('ld-vote-btn--active-dislike', data.new_vote === 'dislike');
                    } else { ldShowToast(data.message || 'Could not vote.', 'error'); }
                } catch(e) {}
            });
    }
 
    // Login
    function ldShowLoginModal(context) {
        if (context === 'book') {
            document.getElementById('ld-modal-heading').textContent = 'Resident Account Required';
            document.getElementById('ld-modal-message').textContent = 'You need a resident account to place orders and make bookings.';
        }
        document.getElementById('ld-login-modal').style.display = 'flex';
    }
    function ldCloseLoginModal() { document.getElementById('ld-login-modal').style.display = 'none'; }
 
    // Toast
    function ldShowToast(message, type) {
        const existing = document.getElementById('ld-toast');
        if (existing) existing.remove();
        const toast = document.createElement('div');
        toast.id = 'ld-toast'; toast.className = `ld-toast ld-toast--${type}`; toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('ld-toast--show'), 10);
        setTimeout(() => { toast.classList.remove('ld-toast--show'); setTimeout(() => toast.remove(), 300); }, 2500);
    }

    function ldLoadReviews() {
    const fd = new FormData();
    fd.append('action',     'get_reviews');
    fd.append('listing_id', LD_LISTING_ID);

    fetch('../pages/listing-detail.php?listing_id=' + LD_LISTING_ID, { method: 'POST', body: fd })
        .then(r => r.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                const list  = document.getElementById('ld-reviews-list');
                const badge = document.getElementById('ld-review-count');

                if (!data.success || !data.reviews.length) {
                    list.innerHTML = '<p class="ld-no-reviews">No reviews yet. Be the first to review!</p>';
                    if (badge) badge.textContent = '';
                    return;
                }

                if (badge) badge.textContent = `${data.reviews.length} review${data.reviews.length !== 1 ? 's' : ''} · Avg ${data.avg}/10`;

                list.innerHTML = data.reviews.map(r => `
                    <div class="ld-review-card">
                        <div class="ld-review-card-header">
                            <span class="ld-reviewer-name">${ldEsc(r.first_name)} ${ldEsc(r.last_name[0])}.
                            </span>
                            <div style="text-align:right;">
                                <span class="ld-review-rating-badge">${r.rating}/10</span>
                                <span class="ld-review-date">${new Date(r.created_at).toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'})}</span>
                            </div>
                        </div>
                        ${r.comment ? `<p class="ld-review-comment">"${ldEsc(r.comment)}"</p>` : ''}
                    </div>
                `).join('');
            } catch(e) {}
        });
}

function ldSetRating(val) {
    ldSelectedRating = val;
    document.querySelectorAll('#ld-stars .ld-star-btn').forEach(btn => {
        btn.classList.toggle('ld-star-btn--active', parseInt(btn.dataset.val) <= val);
    });
    document.getElementById('ld-rating-display').textContent = `${val} / 10`;
}

function ldSubmitReview() {
    if (!ldSelectedRating) { ldShowToast('Please select a rating.', 'error'); return; }
    const comment = document.getElementById('ld-review-comment').value.trim();

    const fd = new FormData();
    fd.append('action',     'submit_review');
    fd.append('listing_id', LD_LISTING_ID);
    fd.append('rating',     ldSelectedRating);
    fd.append('comment',    comment);

    fetch('../pages/listing-detail.php?listing_id=' + LD_LISTING_ID, { method: 'POST', body: fd })
        .then(r => r.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    ldShowToast(data.message, 'success');
                    document.getElementById('ld-review-form-wrap').innerHTML =
                        `<div class="ld-already-reviewed">Thank you! Your review has been submitted.</div>`;
                    ldLoadReviews();
                } else {
                    ldShowToast(data.message || 'Failed to submit.', 'error');
                }
            } catch(e) { ldShowToast('Unexpected error.', 'error'); }
        });
}

function ldEsc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>
 
</body>
</html>