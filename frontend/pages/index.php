<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../db_connection.php';
 
$is_logged_in = isset($_SESSION['user_id']);
$user_name    = '';
if ($is_logged_in) {
    if (!empty($_SESSION['first_name']))      $user_name = $_SESSION['first_name'];
    elseif (!empty($_SESSION['business_name'])) $user_name = $_SESSION['business_name'];
  }
 
$cart_count = isset($_SESSION['cart'])
    ? array_sum(array_column($_SESSION['cart'], 'quantity'))
    : 0;
 
// Display Popular Listings 
$popular_stmt = $conn->prepare("
    SELECT l.listing_id, l.title, l.caption, l.price,
           pc.category_name,
           sp.business_name,
           a.area_name,
           li.image_url AS primary_image,
           COUNT(oi.order_item_id) AS order_count
    FROM listings l
    JOIN product_service ps ON l.item_id = ps.item_id
    JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
    JOIN product_service_categories pc ON pss.category_id   = pc.category_id
    JOIN sme_profiles sp ON l.sme_id = sp.sme_id
    JOIN areas a ON sp.area_id = a.area_id
    LEFT JOIN listing_images li ON l.listing_id = li.listing_id AND li.is_primary = 1
    LEFT JOIN order_items oi ON l.listing_id = oi.listing_id
    WHERE l.status = 'active'
    GROUP BY l.listing_id
    ORDER BY order_count DESC, l.created_at DESC
    LIMIT 6");
              $popular_stmt->execute();
         $popular_listings = $popular_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
         $popular_stmt->close();
 
// Display Recently Added Listings 
   $recent_stmt = $conn->prepare("
    SELECT l.listing_id, l.title, l.caption, l.price,
           pc.category_name,
           sp.business_name,
           a.area_name,
           li.image_url AS primary_image
    FROM listings l
    JOIN product_service ps ON l.item_id = ps.item_id
    JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
    JOIN product_service_categories pc ON pss.category_id   = pc.category_id
    JOIN sme_profiles sp    ON l.sme_id = sp.sme_id
    JOIN areas a ON sp.area_id = a.area_id
    LEFT JOIN listing_images li ON l.listing_id = li.listing_id AND li.is_primary = 1
    WHERE l.status = 'active'
    ORDER BY l.created_at DESC
    LIMIT 6");
      $recent_stmt->execute();
       $recent_listings = $recent_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
      $recent_stmt->close();
 
//  Stats 
$stats_r         = mysqli_query($conn, "SELECT COUNT(*) AS total FROM listings WHERE status = 'active'");
$active_listings = mysqli_fetch_assoc($stats_r)['total'] ?? 0;
 
$biz_r     = mysqli_query($conn, "SELECT COUNT(*) AS total FROM sme_profiles WHERE approval_status = 'approved'");
$total_biz = mysqli_fetch_assoc($biz_r)['total'] ?? 0;
 
$res_r     = mysqli_query($conn, "SELECT COUNT(*) AS total FROM resident_profiles");
$total_res = mysqli_fetch_assoc($res_r)['total'] ?? 0;
 
// Past Events 
$events_r = mysqli_query($conn, "
    SELECT * FROM past_events
    WHERE is_visible = 1
    ORDER BY display_order ASC
    LIMIT 5
");
$past_events = [];
while ($row = mysqli_fetch_assoc($events_r)) $past_events[] = $row;

// Honored Residents
$honored_r = $conn->query("
    SELECT h.honor_id, h.user_id, h.title, h.reason, h.honored_date,
           h.is_visible, h.display_order,
           rp.first_name, rp.last_name,
           a.area_name
    FROM honored_residents h
    LEFT JOIN resident_profiles rp ON h.user_id = rp.user_id
    LEFT JOIN areas a ON h.area_id = a.area_id
    WHERE h.is_visible = 1
    ORDER BY h.display_order ASC
    LIMIT 4
");
$honored_residents = $honored_r ? $honored_r->fetch_all(MYSQLI_ASSOC) : [];
 
function ix_safe(string $val): string { return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CultureConnect — Hertfordshire</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="ix-page-wrap">
 
<?php include '../components/header.php'; ?>

<!-- HERO CARD-->
<section class="ix-hero">
    <div class="ix-hero-inner">
        <div class="ix-hero-text">
            <?php if ($is_logged_in && $user_name) : ?>
            <p class="ix-hero-welcome">Welcome back, <?= ix_safe($user_name) ?> </p>
            <?php endif; ?>
            <h1 class="ix-hero-title">Discover Hertfordshire's Cultural Community</h1>
            <p class="ix-hero-subtitle">Explore local products, services and cultural events from businesses and residents across Hertfordshire.</p>
            <div class="ix-hero-actions">
                <a href="../pages/browse.php" class="ix-hero-btn ix-hero-btn--primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    Browse Listings
                </a>
                <?php if (!$is_logged_in) : ?>
                <a href="../pages/register.php" class="ix-hero-btn ix-hero-btn--secondary">Join the Community</a>
                <?php else : ?>
                <a href="../pages/dashboard.php" class="ix-hero-btn ix-hero-btn--secondary">Go to Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
 
        <!-- Cart button -->
        <a href="../pages/cart.php" class="ix-cart-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
            </svg>
            Cart
            <span class="ix-cart-badge" id="ix-cart-badge"><?= $cart_count > 0 ? $cart_count : '' ?></span>
        </a>
    </div>
</section>
 
 
<!-- COMMUNITY EVENTS CAROUSEL-->
<section class="ix-section">
    <div class="ix-section-inner">
        <div class="ix-section-header">
            <div>
                <h2 class="ix-section-title">Community Events</h2>
                <p class="ix-section-subtitle">Highlights from the Hertfordshire cultural calendar</p>
            </div>
        </div>
 
        <div class="ix-carousel-wrap">
                    <?php foreach ($past_events as $i => $event) : ?>
            <div class="ix-event-slide <?= $i === 0 ? 'ix-event-slide--active' : '' ?>" id="ix-slide-<?= $i ?>">
              <div class="ix-event-img-wrap">
              <img src="../images/council_events/<?= ix_safe($event['image_url']) ?>"
                 alt="<?= ix_safe($event['title']) ?>"
                 class="ix-event-img"
                 onerror="this.parentElement.classList.add('ix-event-img-wrap--fallback')">
             <div class="ix-event-overlay">
                 <span class="ix-event-date"><?= date('d M Y', strtotime($event['event_date'])) ?></span>
                 <h3 class="ix-event-title"><?= ix_safe($event['title']) ?></h3>
                 <p class="ix-event-desc"><?= ix_safe($event['description']) ?></p>
             </div>
           </div>
       </div>
         <?php endforeach; ?>
 
            <button class="ix-carousel-arrow ix-carousel-arrow--prev" onclick="ixCarouselPrev()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
            <button class="ix-carousel-arrow ix-carousel-arrow--next" onclick="ixCarouselNext()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>
 
            <div class="ix-carousel-dots">
                <?php foreach ($past_events as $i => $event) : ?>
                <button class="ix-carousel-dot <?= $i === 0 ? 'ix-carousel-dot--active' : '' ?>"
                        onclick="ixCarouselGoTo(<?= $i ?>)"></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
 
 
<!-- POPULAR LISTINGS -->
<section class="ix-section ix-section--alt">
    <div class="ix-section-inner">
        <div class="ix-section-header">
            <div>
                <h2 class="ix-section-title">Popular in the Community</h2>
                <p class="ix-section-subtitle">The most booked and ordered cultural offerings</p>
            </div>
            <a href="../pages/browse.php" class="ix-view-all-btn">View All</a>
        </div>
 
        <?php if (!empty($popular_listings)) : ?>
        <div class="ix-listings-scroll">
            <?php foreach ($popular_listings as $l) : ?>
            <?= ixListingCard($l) ?>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <p class="ix-no-listings">No listings available yet.</p>
        <?php endif; ?>
    </div>
</section>
 
 
<!-- RECENTLY ADDED LISTINGS-->
<section class="ix-section">
    <div class="ix-section-inner">
        <div class="ix-section-header">
            <div>
                <h2 class="ix-section-title">Recently Added</h2>
                <p class="ix-section-subtitle">The latest products and services from Hertfordshire businesses</p>
            </div>
            <a href="../pages/browse.php" class="ix-view-all-btn">View All</a>
        </div>
 
        <?php if (!empty($recent_listings)) : ?>
        <div class="ix-listings-scroll">
            <?php foreach ($recent_listings as $l) : ?>
            <?= ixListingCard($l) ?>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <p class="ix-no-listings">No listings available yet.</p>
        <?php endif; ?>
    </div>
</section>
 
 
<!-- HONOURED RESIDENTS-->
<section class="ix-section ix-section--alt">
    <div class="ix-section-inner">
        <div class="ix-section-header">
            <div>
                <h2 class="ix-section-title">Honoured Residents</h2>
                <p class="ix-section-subtitle">Celebrating community members making a difference in Hertfordshire</p>
            </div>
        </div>
 
        <div class="ix-honoured-grid">
            <?php foreach ($honored_residents as $h) : ?>
             <div class="ix-honoured-card">
             <div class="ix-honoured-info">
                      <h3 class="ix-honoured-name"><?= ix_safe($h['first_name'] . ' ' . $h['last_name']) ?></h3>
                      <p class="ix-honoured-area">
                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="12" height="12">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                     </svg>
                       <?= ix_safe($h['area_name'] ?? 'Hertfordshire') ?> </p>
                   <span class="ix-honoured-badge"><?= ix_safe($h['title']) ?></span>
                   <p class="ix-honoured-reason"><?= ix_safe($h['reason']) ?></p>
               </div>
         </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
 
 
<!--STATS STRIP-->
<section class="ix-section">
    <div class="ix-section-inner">
        <div class="ix-stats-strip">
            <div class="ix-stats-inner">
                <div class="ix-stat">
                    <span class="ix-stat-number"><?= $active_listings ?></span>
                    <span class="ix-stat-label">Active Listings</span>
                </div>
                <div class="ix-stat-divider"></div>
                <div class="ix-stat">
                    <span class="ix-stat-number"><?= $total_biz ?></span>
                    <span class="ix-stat-label">Local Businesses</span>
                </div>
                <div class="ix-stat-divider"></div>
                <div class="ix-stat">
                    <span class="ix-stat-number"><?= $total_res ?></span>
                    <span class="ix-stat-label">Community Members</span>
                </div>
                <div class="ix-stat-divider"></div>
                <div class="ix-stat">
                    <span class="ix-stat-number">6</span>
                    <span class="ix-stat-label">Areas Covered</span>
                </div>
            </div>
        </div>
    </div>
</section>
 
 
<!-- CTA (guests only)-->
<?php if (!$is_logged_in) : ?>
<section class="ix-cta">
    <div class="ix-cta-inner">
        <h2 class="ix-cta-title">Join the CultureConnect Community</h2>
        <p class="ix-cta-subtitle">Register as a resident to browse, vote and book local cultural services, or sign up as a business to showcase your products and services.</p>
        <div class="ix-cta-actions">
            <a href="../pages/register.php" class="ix-cta-btn ix-cta-btn--primary">Create an Account</a>
            <a href="../pages/login.php"    class="ix-cta-btn ix-cta-btn--secondary">Sign In</a>
        </div>
    </div>
</section>
<?php endif; ?>
 
 
<?php include '../components/footer.php'; ?>
 
 
<?php

function ixListingCard(array $l): string {
    $price     = floatval($l['price']);
    $imgSrc    = !empty($l['primary_image']) ? '../uploads/listings_images/' . htmlspecialchars($l['primary_image'], ENT_QUOTES) : null;
    $priceTier = $price <= 20 ? 'affordable' : ($price <= 50 ? 'moderate' : 'premium');
    $tierLabel = $price <= 20 ? 'Affordable'  : ($price <= 50 ? 'Moderate'  : 'Premium');
    $catClass  = $l['category_name'] === 'Product' ? 'ix-badge-cat--product' : 'ix-badge-cat--service';
    $link      = '../pages/listing-detail.php?listing_id=' . intval($l['listing_id']);
 
    $imgHtml = $imgSrc
        ? '<img src="' . $imgSrc . '" alt="' . htmlspecialchars($l['title'], ENT_QUOTES) . '" class="ix-card-img" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'">'
        : '';
 
    return '
    <a href="' . $link . '" class="ix-listing-card">
        <div class="ix-card-image-wrap">
            ' . $imgHtml . '
            <div class="ix-card-img-placeholder"' . ($imgSrc ? ' style="display:none;"' : '') . '>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="28" height="28">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
            </div>
            <span class="ix-badge-cat ' . $catClass . '">' . htmlspecialchars($l['category_name'], ENT_QUOTES) . '</span>
            <span class="ix-badge-price ix-badge-price--' . $priceTier . '">' . $tierLabel . '</span>
        </div>
        <div class="ix-card-body">
            <p class="ix-card-business">' . htmlspecialchars($l['business_name'], ENT_QUOTES) . '</p>
            <h3 class="ix-card-title">' . htmlspecialchars($l['title'], ENT_QUOTES) . '</h3>
            <p class="ix-card-caption">' . htmlspecialchars($l['caption'] ?? '', ENT_QUOTES) . '</p>
            <div class="ix-card-footer">
                <span class="ix-card-price">£' . number_format($price, 2) . '</span>
                <span class="ix-card-area">' . htmlspecialchars($l['area_name'], ENT_QUOTES) . '</span>
            </div>
        </div>
    </a>';
}
?>
 
 <script>
    let ixSlideIndex  = 0;
    const ixSlideCount = <?= count($past_events) ?>;
    let ixAutoTimer   = null;
 
    document.addEventListener('DOMContentLoaded', () => ixStartAuto());
 
    function ixCarouselGoTo(index) {
        document.querySelectorAll('.ix-event-slide').forEach((s, i) => {
            s.classList.toggle('ix-event-slide--active', i === index);
        });
        document.querySelectorAll('.ix-carousel-dot').forEach((d, i) => {
            d.classList.toggle('ix-carousel-dot--active', i === index);
        });
        ixSlideIndex = index;
    }
 
    function ixCarouselPrev() { ixResetAuto(); ixCarouselGoTo((ixSlideIndex - 1 + ixSlideCount) % ixSlideCount); }
    function ixCarouselNext() { ixResetAuto(); ixCarouselGoTo((ixSlideIndex + 1) % ixSlideCount); }
    function ixStartAuto()    { ixAutoTimer = setInterval(() => ixCarouselGoTo((ixSlideIndex + 1) % ixSlideCount), 5000); }
    function ixResetAuto()    { clearInterval(ixAutoTimer); ixStartAuto(); }
</script>
 
</body>
</html>