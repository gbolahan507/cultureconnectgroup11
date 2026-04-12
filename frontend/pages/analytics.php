<?php ob_start(); ?>
<?php
// PAGE GUARD
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Council Administrator', 'Council Member'])) {
    header('Location: ../pages/dashboard.php?page=home');
    exit();
}
 
$base_url     = 'dashboard.php?page=analytics';

if (!isset($conn)) include '../db_connection.php';
 
// DATA QUERIES
// Overview Stats
$total_users      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM users"))['n'] ?? 0;
$total_residents  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM resident_profiles"))['n'] ?? 0;
$total_smes       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM sme_profiles WHERE approval_status = 'approved'"))['n'] ?? 0;
$pending_smes     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM sme_profiles WHERE approval_status = 'pending'"))['n'] ?? 0;
$active_listings  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM listings WHERE status = 'active'"))['n'] ?? 0;
$pending_listings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM listings WHERE status = 'pending'"))['n'] ?? 0;
$total_orders     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM orders"))['n'] ?? 0;
$total_likes      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM listing_votes WHERE vote_type = 'like'"))['n'] ?? 0;
$total_dislikes   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM listing_votes WHERE vote_type = 'dislike'"))['n'] ?? 0;
$total_lv         = intval($total_likes) + intval($total_dislikes);
 
// Orders by Status
$order_stats_r = mysqli_query($conn, "
    SELECT status, COUNT(*) AS count, SUM(total_amount) AS revenue
    FROM orders GROUP BY status
");
$order_stats = [];
while ($row = mysqli_fetch_assoc($order_stats_r)) $order_stats[$row['status']] = $row;
$total_revenue = array_sum(array_column($order_stats, 'revenue'));
 
// Community Listing Votes from resident_product_service_interest view
// Fetch all voted listings
$votes_r = mysqli_query($conn, "
    SELECT product_name, listing_title, price,
           total_likes, total_dislikes, ranking
    FROM resident_product_service_interest
    WHERE (total_likes + total_dislikes) > 0
    ORDER BY ranking DESC
");
$vote_rankings = [];
while ($row = mysqli_fetch_assoc($votes_r)) $vote_rankings[] = $row;

// Pagination
$votes_per_page  = 8;
$votes_page      = max(1, intval($_GET['votes_page'] ?? 1));
$total_votes_p   = count($vote_rankings);
$votes_pages     = ceil($total_votes_p / $votes_per_page);
$votes_page      = min($votes_page, max(1, $votes_pages));
$votes_offset    = ($votes_page - 1) * $votes_per_page;
$paged_votes     = array_slice($vote_rankings, $votes_offset, $votes_per_page);
$max_votes = !empty($vote_rankings)
    ? max(array_map(fn($r) => intval($r['total_likes']) + intval($r['total_dislikes']), $vote_rankings))
    : 1;
 
// Top Listings by Orders
$top_listings_r = mysqli_query($conn, "
    SELECT l.listing_id, l.title, l.price,
           sp.business_name,
           pc.category_name,
           COUNT(oi.order_item_id)      AS order_count,
           SUM(oi.quantity)             AS units_sold,
           SUM(oi.price * oi.quantity)  AS revenue
    FROM listings l
    JOIN product_service ps                ON l.item_id         = ps.item_id
    JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
    JOIN product_service_categories pc     ON pss.category_id   = pc.category_id
    JOIN sme_profiles sp                   ON l.sme_id          = sp.sme_id
    JOIN order_items oi                    ON l.listing_id       = oi.listing_id
    GROUP BY l.listing_id
    ORDER BY order_count DESC
");
$top_listings = [];
while ($row = mysqli_fetch_assoc($top_listings_r)) $top_listings[] = $row;

// Add pagination
$top_per_page   = 8;
$top_page       = max(1, intval($_GET['top_page'] ?? 1));
$total_top      = count($top_listings);
$top_pages      = ceil($total_top / $top_per_page);
$top_page       = min($top_page, max(1, $top_pages));
$top_offset     = ($top_page - 1) * $top_per_page;
$paged_top      = array_slice($top_listings, $top_offset, $top_per_page);
$max_orders     = !empty($top_listings) ? $top_listings[0]['order_count'] : 1;
 
// Area Activity
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
 
// SME Performance
$sme_perf_r = mysqli_query($conn, "
    SELECT sp.business_name,
           COUNT(DISTINCT l.listing_id)     AS listing_count,
           COUNT(DISTINCT oi.order_item_id) AS order_count,
           SUM(oi.price * oi.quantity)      AS revenue
    FROM sme_profiles sp
    LEFT JOIN listings l     ON sp.sme_id     = l.sme_id AND l.status = 'active'
    LEFT JOIN order_items oi ON l.listing_id  = oi.listing_id
    WHERE sp.approval_status = 'approved'
    GROUP BY sp.sme_id
    ORDER BY order_count DESC
");
$sme_perf = [];
while ($row = mysqli_fetch_assoc($sme_perf_r)) $sme_perf[] = $row;
?>
 
 <!-- ANALYTICS PAGE -->
<div class="an-page">
 
    <?php
        $icon     = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>';
        $title    = 'Analytics';
        $subtitle = 'Platform-wide statistics, community votes and cultural insights.';
        include '../components/section_header.php';
    ?>
 
    <!-- OVERVIEW STATS -->
    <div class="an-stats-grid">
 
        <div class="an-stat-card an-stat-card--purple">
            <div class="an-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </div>
            <div class="an-stat-info">
                <span class="an-stat-num"><?= number_format($total_users) ?></span>
                <span class="an-stat-lbl">Total Users</span>
            </div>
            <div class="an-stat-sub"><?= $total_residents ?> residents · <?= $total_smes ?> businesses</div>
        </div>
 
        <div class="an-stat-card an-stat-card--fuchsia">
            <div class="an-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                </svg>
            </div>
            <div class="an-stat-info">
                <span class="an-stat-num"><?= number_format($active_listings) ?></span>
                <span class="an-stat-lbl">Active Listings</span>
            </div>
            <div class="an-stat-sub"><?= $pending_listings ?> pending approval</div>
        </div>
 
        <div class="an-stat-card an-stat-card--green">
            <div class="an-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <div class="an-stat-info">
                <span class="an-stat-num"><?= number_format($total_orders) ?></span>
                <span class="an-stat-lbl">Total Orders</span>
            </div>
            <div class="an-stat-sub">£<?= number_format($total_revenue, 2) ?> total revenue</div>
        </div>
 
        <div class="an-stat-card an-stat-card--blue">
            <div class="an-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />
                </svg>
            </div>
            <div class="an-stat-info">
                <span class="an-stat-num"><?= number_format($total_lv) ?></span>
                <span class="an-stat-lbl">Total Listing Votes</span>
            </div>
            <div class="an-stat-sub"><?= $total_likes ?> likes · <?= $total_dislikes ?> dislikes</div>
        </div>
 
    </div>
 
 
    <!-- ORDERS SUMMARY  -->
    <div class="an-section">
        <h2 class="an-section-title">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
            </svg>
            Orders Summary
        </h2>
        <div class="an-orders-grid">
            <?php
            $statuses = [
                'processing' => ['label' => 'Processing', 'cls' => 'an-order-stat--processing'],
                'completed'  => ['label' => 'Completed',  'cls' => 'an-order-stat--completed'],
                'cancelled'  => ['label' => 'Cancelled',  'cls' => 'an-order-stat--cancelled'],
            ];
            foreach ($statuses as $key => $s) :
                $data = $order_stats[$key] ?? ['count' => 0, 'revenue' => 0];
            ?>
            <div class="an-order-stat <?= $s['cls'] ?>">
                <span class="an-order-stat-num"><?= $data['count'] ?></span>
                <span class="an-order-stat-lbl"><?= $s['label'] ?></span>
                <span class="an-order-stat-rev">£<?= number_format($data['revenue'] ?? 0, 2) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
 
 
    <!-- COMMUNITY LISTING VOTES -->
    <div class="an-section">
        <h2 class="an-section-title">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />
            </svg>
            Community Listing Votes
        </h2>
 
        <!-- Vote totals summary row -->
        <div class="an-vote-summary">
            <div class="an-vote-summary-card an-vote-summary-card--total">
                <span class="an-vote-summary-num"><?= number_format($total_lv) ?></span>
                <span class="an-vote-summary-lbl">Total Votes</span>
            </div>
            <div class="an-vote-summary-card an-vote-summary-card--like">
                <span class="an-vote-summary-num"><?= number_format($total_likes) ?></span>
                <span class="an-vote-summary-lbl"> Total Likes</span>
            </div>
            <div class="an-vote-summary-card an-vote-summary-card--dislike">
                <span class="an-vote-summary-num"><?= number_format($total_dislikes) ?></span>
                <span class="an-vote-summary-lbl"> Total Dislikes</span>
            </div>
            <?php if ($total_lv > 0) : ?>
            <div class="an-vote-summary-card an-vote-summary-card--pct">
                <span class="an-vote-summary-num"><?= round(($total_likes / $total_lv) * 100) ?>%</span>
                <span class="an-vote-summary-lbl">Positive Rate</span>
            </div>
            <?php endif; ?>
        </div>
 
        <?php if (empty($vote_rankings)) : ?>
        <div class="an-empty">No votes have been cast on listings yet.</div>
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
                        <th>Sentiment</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paged_votes as $i => $vr) :
                        $likes      = intval($vr['total_likes']);
                        $dislikes   = intval($vr['total_dislikes']);
                        $total      = $likes + $dislikes;
                        $likePct    = $total > 0 ? round(($likes / $total) * 100) : 0;
                        $disPct     = 100 - $likePct;
                        $score      = $likes - $dislikes;
 
                        if ($likePct >= 70)      { $sentiment = 'Positive'; $sentCls = 'an-sentiment--positive'; }
                        elseif ($disPct >= 70)   { $sentiment = 'Negative'; $sentCls = 'an-sentiment--negative'; }
                        else                     { $sentiment = 'Mixed';    $sentCls = 'an-sentiment--mixed'; }
                    ?>
                    <tr>
                        <td class="an-rank"><?= $votes_offset + $i + 1 ?></td>
                        <td class="an-listing-title"><?= htmlspecialchars($vr['listing_title']) ?></td>
                        <td><?= htmlspecialchars($vr['product_name']) ?></td>
                        <td class="an-revenue">£<?= number_format(floatval($vr['price']), 2) ?></td>
                        <td>
                            <span class="an-vote-pill an-vote-pill--like"><?= $likes ?></span>
                        </td>
                        <td>
                            <span class="an-vote-pill an-vote-pill--dislike"><?= $dislikes ?></span>
                        </td>
                        <td>
                            <div class="an-vote-bar-wrap">
                                <div class="an-vote-bar">
                                    <div class="an-vote-bar-like"    style="width:<?= $likePct ?>%"></div>
                                    <div class="an-vote-bar-dislike" style="width:<?= $disPct ?>%"></div>
                                </div>
                                <span class="an-sentiment <?= $sentCls ?>"><?= $sentiment ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="an-score <?= $score >= 0 ? 'an-score--pos' : 'an-score--neg' ?>">
                                <?= $score >= 0 ? '+' : '' ?><?= $score ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($votes_pages > 1) : ?>
<div class="an-pagination">
    <?php if ($votes_page > 1) : ?>
    <a href="<?= $base_url ?>&votes_page=<?= $votes_page - 1 ?>" class="an-page-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
        Prev
    </a>
    <?php endif; ?>
    <?php for ($p = 1; $p <= $votes_pages; $p++) : ?>
    <a href="<?= $base_url ?>&votes_page=<?= $p ?>" class="an-page-btn <?= $p === $votes_page ? 'an-page-btn--active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
    <?php if ($votes_page < $votes_pages) : ?>
    <a href="<?= $base_url ?>&votes_page=<?= $votes_page + 1 ?>" class="an-page-btn">
        Next
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
    </a>
    <?php endif; ?>
    </div>
    <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
 
 
    <!-- TOP LISTINGS BY ORDERS -->
    <div class="an-section">
        <h2 class="an-section-title">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
            </svg>
            Top Listings by Orders
        </h2>
 
        <?php if (empty($top_listings)) : ?>
        <div class="an-empty">No orders placed yet.</div>
        <?php else : ?>
        <div class="an-table-wrap">
            <table class="an-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Listing</th>
                        <th>Business</th>
                        <th>Category</th>
                        <th>Orders</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                        <th>Popularity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paged_top as $i => $l) :
                        $pct = $max_orders > 0 ? round(($l['order_count'] / $max_orders) * 100) : 0;
                    ?>
                    <tr>
                        <td class="an-rank"><?= $top_offset + $i + 1 ?></td>
                        <td class="an-listing-title"><?= htmlspecialchars($l['title']) ?></td>
                        <td><?= htmlspecialchars($l['business_name']) ?></td>
                        <td><span class="an-cat-badge"><?= htmlspecialchars($l['category_name']) ?></span></td>
                        <td class="an-num"><?= $l['order_count'] ?></td>
                        <td class="an-num"><?= $l['units_sold'] ?></td>
                        <td class="an-revenue">£<?= number_format($l['revenue'], 2) ?></td>
                        <td class="an-bar-cell">
                            <div class="an-mini-bar-wrap">
                                <div class="an-mini-bar" style="width:<?= $pct ?>%"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($top_pages > 1) : ?>
<div class="an-pagination">
    <?php if ($top_page > 1) : ?>
    <a href="<?= $base_url ?>&top_page=<?= $top_page - 1 ?>" class="an-page-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
        Prev
    </a>
    <?php endif; ?>
             <?php for ($p = 1; $p <= $top_pages; $p++) : ?>
             <a href="<?= $base_url ?>&top_page=<?= $p ?>" class="an-page-btn <?= $p === $top_page ? 'an-page-btn--active' : '' ?>"><?= $p ?></a>
             <?php endfor; ?>
    <?php if ($top_page < $top_pages) : ?>
             <a href="<?= $base_url ?>&top_page=<?= $top_page + 1 ?>" class="an-page-btn"> Next
             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
             </a>
    <?php endif; ?>
    </div>
    <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
 
 
    <!-- BUSINESS PERFORMANCE -->
    <div class="an-section">
        <h2 class="an-section-title">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016 2.993 2.993 0 0 0 2.25-1.016 3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
            </svg>
            Business Performance
        </h2>
 
        <?php
        $biz_per_page = 6;
        $biz_page     = max(1, intval($_GET['biz_page'] ?? 1));
        $total_biz_p  = count($sme_perf);
        $total_pages  = ceil($total_biz_p / $biz_per_page);
        $biz_page     = min($biz_page, max(1, $total_pages));
        $offset       = ($biz_page - 1) * $biz_per_page;
        $paged_smes   = array_slice($sme_perf, $offset, $biz_per_page);
        ?>
 
        <?php if (empty($sme_perf)) : ?>
        <div class="an-empty">No business data available.</div>
        <?php else : ?>
 
        <div class="an-biz-page-info">
            Showing <?= $offset + 1 ?>–<?= min($offset + $biz_per_page, $total_biz_p) ?> of <?= $total_biz_p ?> businesses
        </div>
 
        <div class="an-table-wrap">
            <table class="an-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Business</th>
                        <th>Active Listings</th>
                        <th>Total Orders</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paged_smes as $i => $s) : ?>
                    <tr>
                        <td class="an-rank"><?= $offset + $i + 1 ?></td>
                        <td class="an-listing-title"><?= htmlspecialchars($s['business_name']) ?></td>
                        <td class="an-num"><?= $s['listing_count'] ?></td>
                        <td class="an-num"><?= $s['order_count'] ?></td>
                        <td class="an-revenue">£<?= number_format($s['revenue'] ?? 0, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
 
        <?php if ($total_pages > 1) : ?>
        <div class="an-pagination">
            <?php if ($biz_page > 1) : ?>
            <a href="<?= $base_url ?>&biz_page=<?= $biz_page - 1 ?>" class="an-page-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                Prev
            </a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $total_pages; $p++) : ?>
            <a href="<?= $base_url ?>&biz_page=<?= $p ?>" class="an-page-btn <?= $p === $biz_page ? 'an-page-btn--active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($biz_page < $total_pages) : ?>
            <a href="<?= $base_url ?>&biz_page=<?= $biz_page + 1 ?>" class="an-page-btn">
                Next
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
 
 
    <!-- AREA ACTIVITY -->
    <div class="an-section">
        <h2 class="an-section-title">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
            </svg>
            Area Activity
        </h2>
 
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
                    <div class="an-area-stat">
                        <span class="an-area-num"><?= $area['listing_count'] ?></span>
                        <span class="an-area-lbl">Listings</span>
                    </div>
                    <div class="an-area-stat">
                        <span class="an-area-num"><?= $area['biz_count'] ?></span>
                        <span class="an-area-lbl">Businesses</span>
                    </div>
                    <div class="an-area-stat">
                        <span class="an-area-num"><?= $area['resident_count'] ?></span>
                        <span class="an-area-lbl">Residents</span>
                    </div>
                </div>
                <div class="an-area-bar-wrap">
                    <div class="an-area-bar" style="width:<?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
 
</div>