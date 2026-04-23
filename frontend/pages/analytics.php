<?php ob_start(); ?>
<?php
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Council Administrator', 'Council Member'])) {
    header('Location: ../pages/dashboard.php?page=home');
    exit();
}
if (!isset($conn)) include '../db_connection.php';

// Overview Stats
$total_users      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM users"))['n'] ?? 0;
$total_residents  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM resident_profiles"))['n'] ?? 0;
$total_smes       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM sme_profiles WHERE approval_status='approved'"))['n'] ?? 0;
$pending_listings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM listings WHERE status='pending'"))['n'] ?? 0;
$active_listings  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM listings WHERE status='active'"))['n'] ?? 0;
$total_orders     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM orders"))['n'] ?? 0;
$total_likes      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM listing_votes WHERE vote_type='like'"))['n'] ?? 0;
$total_dislikes   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM listing_votes WHERE vote_type='dislike'"))['n'] ?? 0;
$total_lv         = intval($total_likes) + intval($total_dislikes);

// Orders breakdown
$order_stats_r = mysqli_query($conn, "SELECT status, COUNT(*) AS count, SUM(total_amount) AS revenue FROM orders GROUP BY status");
$order_stats   = [];
while ($row = mysqli_fetch_assoc($order_stats_r)) $order_stats[$row['status']] = $row;
$total_revenue     = array_sum(array_column($order_stats, 'revenue'));
$completed_orders  = $order_stats['completed']['count']   ?? 0;
$processing_orders = $order_stats['processing']['count']  ?? 0;
$cancelled_orders  = $order_stats['cancelled']['count']   ?? 0;
$completed_revenue = $order_stats['completed']['revenue'] ?? 0;
$avg_order_value   = $total_orders > 0 ? $total_revenue / $total_orders : 0;

// User distribution
$user_dist_r = mysqli_query($conn, "SELECT role, COUNT(*) AS count FROM users GROUP BY role ORDER BY count DESC");
$user_dist   = [];
while ($row = mysqli_fetch_assoc($user_dist_r)) $user_dist[] = $row;

// Active listings by units sold
$listings_r = mysqli_query($conn, "
    SELECT l.listing_id, l.title, l.price,
           sp.business_name, pc.category_name,
           COALESCE(SUM(oi.quantity), 0) AS units_sold
    FROM listings l
    JOIN product_service ps                ON l.item_id         = ps.item_id
    JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
    JOIN product_service_categories pc     ON pss.category_id   = pc.category_id
    JOIN sme_profiles sp                   ON l.sme_id          = sp.sme_id
    LEFT JOIN order_items oi               ON l.listing_id      = oi.listing_id
    WHERE l.status = 'active'
    GROUP BY l.listing_id
    ORDER BY units_sold DESC
    LIMIT 10
");
$active_listings_data = [];
while ($row = mysqli_fetch_assoc($listings_r)) $active_listings_data[] = $row;

// Community votes
$votes_r = mysqli_query($conn, "
    SELECT product_name, listing_title, price, total_likes, total_dislikes, score
    FROM resident_product_service_interest
    WHERE (total_likes + total_dislikes) > 0
    ORDER BY score DESC
    LIMIT 10
");
$community_votes = [];
while ($row = mysqli_fetch_assoc($votes_r)) $community_votes[] = $row;

// Use platform-wide totals from listing_votes table (not just top 10)
$cv_total_likes    = intval($total_likes);
$cv_total_dislikes = intval($total_dislikes);
$cv_total          = $total_lv;
$cv_positive_rate  = $cv_total > 0 ? round(($cv_total_likes / $cv_total) * 100) : 0;

// Business performance
$biz_r = mysqli_query($conn, "
    SELECT sp.business_name,
           COUNT(DISTINCT l.listing_id)                 AS listing_count,
           COUNT(DISTINCT oi.order_item_id)              AS order_count,
           COALESCE(SUM(oi.price * oi.quantity), 0)     AS revenue
    FROM sme_profiles sp
    LEFT JOIN listings l     ON sp.sme_id    = l.sme_id AND l.status = 'active'
    LEFT JOIN order_items oi ON l.listing_id = oi.listing_id
    WHERE sp.approval_status = 'approved'
    GROUP BY sp.sme_id
    ORDER BY revenue DESC
");
$biz_perf = [];
while ($row = mysqli_fetch_assoc($biz_r)) $biz_perf[] = $row;

// Area activity 
$area_r = mysqli_query($conn, "
    SELECT a.area_name,
           COUNT(DISTINCT sp.sme_id)     AS biz_count,
           COUNT(DISTINCT l.listing_id)  AS listing_count,
           COUNT(DISTINCT rp.profile_id) AS resident_count
    FROM areas a
    LEFT JOIN sme_profiles sp      ON a.area_id = sp.area_id AND sp.approval_status = 'approved'
    LEFT JOIN listings l           ON sp.sme_id  = l.sme_id  AND l.status = 'active'
    LEFT JOIN resident_profiles rp ON a.area_id  = rp.area_id
    GROUP BY a.area_id
    ORDER BY listing_count DESC
");
$areas = [];
while ($row = mysqli_fetch_assoc($area_r)) $areas[] = $row;
$max_area_listings = !empty($areas) ? max(array_column($areas, 'listing_count')) : 1;

// to convert to JSON string
$json_user_dist = json_encode($user_dist);
$json_biz_perf  = json_encode($biz_perf);

?>

<div class="an-page">

<?php
    $icon     = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>';
    $title    = 'Analytics';
    $subtitle = 'Click any card below to explore platform data.';
    include '../components/section_header.php';
?>

<!-- STAT CARDS -->
<div class="an-stats-grid">

    <div class="an-stat-card an-stat-card--purple an-stat-card--clickable" data-panel="users">
        <div class="an-stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
        </div>
        <div class="an-stat-info">
            <span class="an-stat-num"><?= number_format($total_users) ?></span>
            <span class="an-stat-lbl">Total Users</span>
        </div>
        <div class="an-stat-sub"><?= $total_residents ?> residents · <?= $total_smes ?> businesses</div>
        <span class="an-card-hint">Click to explore</span>
    </div>

    <div class="an-stat-card an-stat-card--fuchsia an-stat-card--clickable" data-panel="listings">
        <div class="an-stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" /></svg>
        </div>
        <div class="an-stat-info">
            <span class="an-stat-num"><?= number_format($active_listings) ?></span>
            <span class="an-stat-lbl">Active Listings</span>
        </div>
        <div class="an-stat-sub"><?= $pending_listings ?> pending approval</div>
        <span class="an-card-hint">Click to explore</span>
    </div>

    <div class="an-stat-card an-stat-card--orchid an-stat-card--clickable" data-panel="orders">
        <div class="an-stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
        </div>
        <div class="an-stat-info">
            <span class="an-stat-num"><?= number_format($total_orders) ?></span>
            <span class="an-stat-lbl">Total Orders</span>
        </div>
        <div class="an-stat-sub">£<?= number_format($completed_revenue, 2) ?> revenue</div>
        <span class="an-card-hint">Click to explore</span>
    </div>

    <div class="an-stat-card an-stat-card--fuchsia an-stat-card--clickable" data-panel="votes">
        <div class="an-stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" /></svg>
        </div>
        <div class="an-stat-info">
            <span class="an-stat-num"><?= number_format($total_lv) ?></span>
            <span class="an-stat-lbl">Total Listing Votes</span>
        </div>
        <div class="an-stat-sub"><?= $total_likes ?> likes · <?= $total_dislikes ?> dislikes</div>
        <span class="an-card-hint">Click to explore</span>
    </div>

    <div class="an-stat-card an-stat-card--darkviolet an-stat-card--clickable" data-panel="business">
        <div class="an-stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016 2.993 2.993 0 0 0 2.25-1.016 3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" /></svg>
        </div>
        <div class="an-stat-info">
            <span class="an-stat-num"><?= count($biz_perf) ?></span>
            <span class="an-stat-lbl">Business Performance</span>
        </div>
        <div class="an-stat-sub"><?= count($biz_perf) ?> approved businesses</div>
        <span class="an-card-hint">Click to explore</span>
    </div>

    <div class="an-stat-card an-stat-card--softviolet an-stat-card--clickable" data-panel="areas">
        <div class="an-stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
        </div>
        <div class="an-stat-info">
            <span class="an-stat-num"><?= count($areas) ?></span>
            <span class="an-stat-lbl">Area Activity</span>
        </div>
        <div class="an-stat-sub">Hertfordshire coverage</div>
        <span class="an-card-hint">Click to explore</span>
    </div>

</div>

<!-- ── DYNAMIC PANEL ──────────────────────────────────────── -->
<div id="an-dynamic" style="display:none; margin-top:1.5rem;">

    <!-- 1. USER DISTRIBUTION -->
    <div id="an-panel-users" class="an-panel an-section" style="display:none;">
        <h2 class="an-section-title">User Distribution</h2>
        <div class="an-pie-wrap">
            <canvas id="an-pie-chart" width="260" height="260"></canvas>
            <div class="an-pie-legend" id="an-pie-legend"></div>
        </div>
    </div>

    <!-- 2. ACTIVE LISTINGS BY UNITS SOLD -->
    <div id="an-panel-listings" class="an-panel an-section" style="display:none;">
        <h2 class="an-section-title">Active Listings by Units Sold</h2>
        <div class="an-toggle-row">
            <button class="an-toggle-btn an-toggle-btn--active" data-filter="all">All</button>
            <button class="an-toggle-btn" data-filter="Product">Products</button>
            <button class="an-toggle-btn" data-filter="Service">Services</button>
        </div>
        <div class="an-table-wrap">
            <table class="an-table" id="an-listings-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Listing</th>
                        <th>Business</th>
                        <th>Type</th>
                        <th>Units Sold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_listings_data as $i => $l) : ?>
                    <tr data-category="<?= htmlspecialchars($l['category_name']) ?>">
                        <td class="an-rank an-listing-rank"><?= $i + 1 ?></td>
                        <td class="an-listing-title"><?= htmlspecialchars($l['title']) ?></td>
                        <td><?= htmlspecialchars($l['business_name']) ?></td>
                        <td><span class="an-cat-badge"><?= htmlspecialchars($l['category_name']) ?></span></td>
                        <td class="an-num"><?= intval($l['units_sold']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. ORDERS BREAKDOWN -->
    <div id="an-panel-orders" class="an-panel an-section" style="display:none;">
        <h2 class="an-section-title">Orders Breakdown</h2>
        <div class="an-breakdown-grid">
            <div class="an-breakdown-card">
                <span class="an-breakdown-num"><?= number_format($total_orders) ?></span>
                <span class="an-breakdown-lbl">Total Orders</span>
            </div>
            <div class="an-breakdown-card an-breakdown-card--fuchsia">
                <span class="an-breakdown-num">£<?= number_format($avg_order_value, 2) ?></span>
                <span class="an-breakdown-lbl">Avg Order Value</span>
            </div>
            <div class="an-breakdown-card an-breakdown-card--orchid">
                 <span class="an-breakdown-num">£<?= number_format($completed_revenue, 2) ?></span>
                 <span class="an-breakdown-lbl">Total Revenue</span>
            </div>
            <div class="an-breakdown-card an-breakdown-card--darkviolet">
                <span class="an-breakdown-num"><?= number_format($completed_orders) ?></span>
                <span class="an-breakdown-lbl">Completed Orders</span>
            </div>
            <div class="an-breakdown-card an-breakdown-card--processing">
                <span class="an-breakdown-num"><?= number_format($processing_orders) ?></span>
                <span class="an-breakdown-lbl">Processing</span>
            </div>
            <div class="an-breakdown-card an-breakdown-card--cancelled">
                <span class="an-breakdown-num"><?= number_format($cancelled_orders) ?></span>
                <span class="an-breakdown-lbl">Cancelled</span>
            </div>
        </div>
    </div>

    <!-- 4. COMMUNITY LISTING VOTES -->
    <div id="an-panel-votes" class="an-panel an-section" style="display:none;">
        <h2 class="an-section-title">Community Listing Votes</h2>
        <div class="an-vote-summary">
            <div class="an-vote-summary-card an-vote-summary-card--total">
                <span class="an-vote-summary-num"><?= number_format($cv_total) ?></span>
                <span class="an-vote-summary-lbl">Total Votes</span>
            </div>
            <div class="an-vote-summary-card an-vote-summary-card--like">
                <span class="an-vote-summary-num"><?= number_format($cv_total_likes) ?></span>
                <span class="an-vote-summary-lbl">Total Likes</span>
            </div>
            <div class="an-vote-summary-card an-vote-summary-card--dislike">
                <span class="an-vote-summary-num"><?= number_format($cv_total_dislikes) ?></span>
                <span class="an-vote-summary-lbl">Total Dislikes</span>
            </div>
            <?php if ($cv_total > 0) : ?>
            <div class="an-vote-summary-card an-vote-summary-card--pct">
                <span class="an-vote-summary-num"><?= $cv_positive_rate ?>%</span>
                <span class="an-vote-summary-lbl">Positive Rate</span>
            </div>
            <?php endif; ?>
        </div>
        <?php if (empty($community_votes)) : ?>
        <div class="an-empty">No votes cast yet.</div>
        <?php else : ?>
        <div class="an-table-wrap">
            <table class="an-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Listing</th>
                        <th>Product / Service</th>
                        <th>Price</th>
                        <th>Likes</th>
                        <th>Dislikes</th>
                        <th>Score</th>
                        <th>Sentiment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($community_votes as $i => $vr) :
                        $likes   = intval($vr['total_likes']);
                        $dislikes= intval($vr['total_dislikes']);
                        $total   = $likes + $dislikes;
                        $likePct = $total > 0 ? round(($likes / $total) * 100) : 0;
                        $disPct  = 100 - $likePct;
                        $score = intval($vr['score']);
                    ?>
                    <tr>
                        <td class="an-rank"><?= $i + 1 ?></td>
                        <td class="an-listing-title"><?= htmlspecialchars($vr['listing_title']) ?></td>
                        <td><?= htmlspecialchars($vr['product_name']) ?></td>
                        <td class="an-revenue">£<?= number_format(floatval($vr['price']), 2) ?></td>
                        <td><span class="an-vote-pill an-vote-pill--like"><?= $likes ?></span></td>
                        <td><span class="an-vote-pill an-vote-pill--dislike"><?= $dislikes ?></span></td>
                        <td><span class="an-score <?= $score >= 0 ? 'an-score--pos' : 'an-score--neg' ?>"><?= $score >= 0 ? '+' : '' ?><?= $score ?></span></td>
                        <td>
                            <div class="an-vote-bar">
                                <div class="an-vote-bar-like"    style="width:<?= $likePct ?>%"></div>
                                <div class="an-vote-bar-dislike" style="width:<?= $disPct ?>%"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- 5. BUSINESS PERFORMANCE -->
    <div id="an-panel-business" class="an-panel an-section" style="display:none;">
        <h2 class="an-section-title">Business Performance</h2>
        <div class="an-toggle-row" id="an-biz-toggles">
            <button class="an-toggle-btn an-toggle-btn--active" data-biz-mode="revenue">By Revenue</button>
            <button class="an-toggle-btn" data-biz-mode="orders">By Orders</button>
        </div>
        <p class="an-chart-note">Chart shows top 6 businesses</p>
        <div class="an-bar-chart-wrap">
            <canvas id="an-biz-chart" height="320"></canvas>
        </div>
        <!-- Full table -->
        <h3 class="an-biz-table-heading" style="margin-top:1.5rem;">All Businesses</h3>
        <div class="an-table-wrap">
            <table class="an-table">
                <thead>
                    <tr><th>#</th><th>Business</th><th>Active Listings</th><th>Total Orders</th><th>Revenue</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($biz_perf as $i => $b) : ?>
                    <tr>
                        <td class="an-rank"><?= $i + 1 ?></td>
                        <td class="an-listing-title"><?= htmlspecialchars($b['business_name']) ?></td>
                        <td class="an-num"><?= $b['listing_count'] ?></td>
                        <td class="an-num"><?= $b['order_count'] ?></td>
                        <td class="an-revenue">£<?= number_format($b['revenue'] ?? 0, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 6. AREA ACTIVITY -->
    <div id="an-panel-areas" class="an-panel an-section" style="display:none;">
        <h2 class="an-section-title">Area Activity</h2>
        <?php if (empty($areas)) : ?>
        <div class="an-empty">No area data available.</div>
        <?php else : ?>
        <div class="an-area-grid">
            <?php foreach ($areas as $area) :
                $pct = $max_area_listings > 0 ? round(($area['listing_count'] / $max_area_listings) * 100) : 0;
            ?>
            <div class="an-area-card">
                <h4 class="an-area-name"><?= htmlspecialchars($area['area_name']) ?></h4>
                <div class="an-area-stats">
                    <div class="an-area-stat"><span class="an-area-num"><?= $area['listing_count'] ?></span><span class="an-area-lbl">Listings</span></div>
                    <div class="an-area-stat"><span class="an-area-num"><?= $area['biz_count'] ?></span><span class="an-area-lbl">Businesses</span></div>
                    <div class="an-area-stat"><span class="an-area-num"><?= $area['resident_count'] ?></span><span class="an-area-lbl">Residents</span></div>
                </div>
                <div class="an-area-bar-wrap"><div class="an-area-bar" style="width:<?= $pct ?>%"></div></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /#an-dynamic -->
</div><!-- /.an-page -->

<script>
(function() {
    var AN_USER_DIST = <?= $json_user_dist ?>;
    var AN_BIZ_PERF  = <?= $json_biz_perf ?>;
    var anCurrentPanel = null;
    var anBizMode = 'revenue';

    // ── Card clicks ───────────────────────────────────────────
    document.querySelectorAll('.an-stat-card--clickable').forEach(function(card) {
        card.addEventListener('click', function() {
            var panel = card.getAttribute('data-panel');
            anShow(panel);
        });
    });

    function anShow(panel) {
        var dynamic = document.getElementById('an-dynamic');

        // Toggle off
        if (anCurrentPanel === panel) {
            dynamic.style.display = 'none';
            document.querySelectorAll('.an-stat-card--clickable').forEach(function(c) {
                c.classList.remove('an-stat-card--active');
            });
            anCurrentPanel = null;
            return;
        }

        anCurrentPanel = panel;

        // Hide all panels
        document.querySelectorAll('.an-panel').forEach(function(p) {
            p.style.display = 'none';
        });

        // Remove active from all cards
        document.querySelectorAll('.an-stat-card--clickable').forEach(function(c) {
            c.classList.remove('an-stat-card--active');
        });

        // Show selected
        var target = document.getElementById('an-panel-' + panel);
        if (!target) return;
        target.style.display = 'block';
        dynamic.style.display = 'block';

        // Activate card
        var activeCard = document.querySelector('[data-panel="' + panel + '"]');
        if (activeCard) activeCard.classList.add('an-stat-card--active');

        // Scroll
        setTimeout(function() {
            dynamic.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 50);

        // Charts
        if (panel === 'users')    setTimeout(anDrawPie, 150);
        if (panel === 'business') setTimeout(function() { anDrawBiz(anBizMode); }, 150);
    }

    // ── Listing filter toggles ────────────────────────────────
    document.querySelectorAll('.an-toggle-row .an-toggle-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var filter = btn.getAttribute('data-filter');
            if (!filter) return;
            document.querySelectorAll('.an-toggle-row .an-toggle-btn').forEach(function(b) {
                b.classList.remove('an-toggle-btn--active');
            });
            btn.classList.add('an-toggle-btn--active');
            var rank = 1;
            document.querySelectorAll('#an-listings-table tbody tr').forEach(function(row) {
                var cat  = row.getAttribute('data-category');
                var show = filter === 'all' || cat === filter;
                row.style.display = show ? '' : 'none';
                if (show) row.querySelector('.an-listing-rank').textContent = rank++;
            });
        });
    });

    // ── Biz chart mode toggles ────────────────────────────────
    document.querySelectorAll('#an-biz-toggles .an-toggle-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            anBizMode = btn.getAttribute('data-biz-mode');
            document.querySelectorAll('#an-biz-toggles .an-toggle-btn').forEach(function(b) {
                b.classList.remove('an-toggle-btn--active');
            });
            btn.classList.add('an-toggle-btn--active');
            anDrawBiz(anBizMode);
        });
    });

    // ── Pie chart ─────────────────────────────────────────────
    function anDrawPie() {
        var canvas = document.getElementById('an-pie-chart');
        if (!canvas || !canvas.getContext) return;
        var ctx    = canvas.getContext('2d');
        var colors = ['#230c33','#bf2ab9','#e00180','#059669','#1d4ed8','#d97706'];
        var total  = AN_USER_DIST.reduce(function(s, r) { return s + parseInt(r.count); }, 0);
        if (total === 0) return;

        var startAngle = -Math.PI / 2;
        var cx = canvas.width / 2, cy = canvas.height / 2, r = 100;
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        AN_USER_DIST.forEach(function(row, i) {
            var slice = (parseInt(row.count) / total) * 2 * Math.PI;
            ctx.beginPath();
            ctx.moveTo(cx, cy);
            ctx.arc(cx, cy, r, startAngle, startAngle + slice);
            ctx.closePath();
            ctx.fillStyle = colors[i % colors.length];
            ctx.fill();
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 2;
            ctx.stroke();
            startAngle += slice;
        });

        // Donut hole
        ctx.beginPath();
        ctx.arc(cx, cy, 50, 0, 2 * Math.PI);
        ctx.fillStyle = '#fff';
        ctx.fill();

        // Centre text
        ctx.fillStyle = '#230c33';
        ctx.font = 'bold 20px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(total, cx, cy - 8);
        ctx.font = '11px Arial';
        ctx.fillStyle = '#6b7280';
        ctx.fillText('Total', cx, cy + 12);

        // Legend
        var legend = document.getElementById('an-pie-legend');
        if (legend) {
            legend.innerHTML = AN_USER_DIST.map(function(row, i) {
                var pct = Math.round((parseInt(row.count) / total) * 100);
                return '<div class="an-legend-item">'
                    + '<span class="an-legend-dot" style="background:' + colors[i % colors.length] + '"></span>'
                    + '<span class="an-legend-label">' + row.role + '</span>'
                    + '<span class="an-legend-val">' + row.count + ' (' + pct + '%)</span>'
                    + '</div>';
            }).join('');
        }
    }

    // ── Bar chart ─────────────────────────────────────────────
    function anDrawBiz(mode) {
        var canvas = document.getElementById('an-biz-chart');
        if (!canvas || !canvas.getContext) return;

        // Sort and cap at top 6
        var sorted = AN_BIZ_PERF.slice().sort(function(a, b) {
            return mode === 'revenue'
                ? parseFloat(b.revenue) - parseFloat(a.revenue)
                : parseInt(b.order_count) - parseInt(a.order_count);
        }).slice(0, 6);

        var data   = sorted.map(function(b) { return mode === 'revenue' ? parseFloat(b.revenue) : parseInt(b.order_count); });
        var labels = sorted.map(function(b) { return b.business_name.length > 14 ? b.business_name.substring(0, 12) + '…' : b.business_name; });
        var maxVal = Math.max.apply(null, data.concat([1]));

        var barW  = 55, gap = 24, padL = 70, padB = 80, padT = 24, chartH = 240;
        var totalW = padL + data.length * (barW + gap) + gap;
        canvas.width  = Math.max(totalW, 300);
        canvas.height = chartH + padB + padT;

        var ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Gridlines + Y labels
        for (var i = 0; i <= 4; i++) {
            var y = padT + chartH - (i / 4) * chartH;
            ctx.strokeStyle = '#f0e6f6';
            ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(padL, y); ctx.lineTo(canvas.width - 10, y); ctx.stroke();
            ctx.fillStyle = '#6b7280';
            ctx.font = '10px Arial';
            ctx.textAlign = 'right';
            var val = maxVal * i / 4;
            ctx.fillText(mode === 'revenue' ? '£' + anFmt(val) : Math.round(val), padL - 6, y + 4);
        }

        // Bars
        data.forEach(function(val, i) {
            var x    = padL + gap + i * (barW + gap);
            var barH = maxVal > 0 ? (val / maxVal) * chartH : 0;
            var y    = padT + chartH - barH;

            var grad = ctx.createLinearGradient(0, y, 0, y + barH);
            grad.addColorStop(0, '#bf2ab9');
            grad.addColorStop(1, '#e00180');
            ctx.fillStyle = grad;
            ctx.beginPath();
            ctx.rect(x, y, barW, barH);
            ctx.fill();

            // Value label
            ctx.fillStyle = '#230c33';
            ctx.font = 'bold 10px Arial';
            ctx.textAlign = 'center';
            ctx.fillText(mode === 'revenue' ? '£' + anFmt(val) : val, x + barW / 2, y - 5);

            // X label (angled)
            ctx.fillStyle = '#6b7280';
            ctx.font = '10px Arial';
            ctx.save();
            ctx.translate(x + barW / 2, padT + chartH + 10);
            ctx.rotate(-Math.PI / 4);
            ctx.textAlign = 'right';
            ctx.fillText(labels[i], 0, 0);
            ctx.restore();
        });
    }

    function anFmt(n) {
        if (n >= 1000) return (n / 1000).toFixed(1) + 'k';
        return Math.round(n);
    }

})();
</script>