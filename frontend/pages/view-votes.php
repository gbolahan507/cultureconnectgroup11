<?php ob_start(); ?>
<?php
// PAGE GUARD TO PREVENT UNAUTHORIZED USERS
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'SME') {
    header('Location: ../pages/dashboard.php?page=home');
    exit();
}
if (!isset($conn)) include '../db_connection.php';
 
// AJAX HANDLER 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
 
    $user_id = $_SESSION['user_id'] ?? null;
    $sme_id  = $_SESSION['sme_id']  ?? null;
 
    if (!$user_id || !$sme_id) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit();
    }
 
    // Get vote data for all SME listings
    if ($_POST['action'] === 'get_votes') {
        $stmt = $conn->prepare("
            SELECT
                l.listing_id,
                l.title,
                l.price,
                pc.category_name,
                pss.subcategory_name,
                li.image_url AS primary_image,
                COALESCE(SUM(lv.vote_type = 'like'),  0) AS likes,
                COALESCE(SUM(lv.vote_type = 'dislike'), 0) AS dislikes,
                COUNT(lv.vote_id)                          AS total_votes
            FROM listings l
            JOIN product_service ps  ON l.item_id  = ps.item_id
            JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
            JOIN product_service_categories pc  ON pss.category_id   = pc.category_id
            LEFT JOIN listing_images li ON l.listing_id  = li.listing_id AND li.is_primary = 1
            LEFT JOIN listing_votes lv  ON l.listing_id  = lv.listing_id
            WHERE l.sme_id = ? AND l.status = 'active'
            GROUP BY l.listing_id
            ORDER BY total_votes DESC, likes DESC
        ");
        $stmt->bind_param("i", $sme_id);
        $stmt->execute();
        $result   = $stmt->get_result();
        $listings = [];
        while ($row = $result->fetch_assoc()) $listings[] = $row;
        $result->free();
        $stmt->close();
 
        $total_likes    = array_sum(array_column($listings, 'likes'));
        $total_dislikes = array_sum(array_column($listings, 'dislikes'));
        $total_votes    = array_sum(array_column($listings, 'total_votes'));
        $most_liked     = !empty($listings) ? $listings[0]['title'] : null;
 
        echo json_encode([
            'success'        => true,
            'listings'       => $listings,
            'total_likes'    => $total_likes,
            'total_dislikes' => $total_dislikes,
            'total_votes'    => $total_votes,
            'most_liked'     => $most_liked
        ]);
        exit();
    }
 
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit();
}
 
// PAGE DATA
$sme_id = $_SESSION['sme_id'];
 
// Fetch ALL reviews for stats (avg, total count)
$all_reviews_r = $conn->prepare("
    SELECT r.rating
    FROM product_service_reviews r
    JOIN listings l      ON r.listing_id = l.listing_id
    JOIN sme_profiles sp ON l.sme_id     = sp.sme_id
    WHERE sp.sme_id = ?
");
$all_reviews_r->bind_param("i", $sme_id);
$all_reviews_r->execute();
$all_result    = $all_reviews_r->get_result();
$all_ratings   = [];
while ($row = $all_result->fetch_assoc()) $all_ratings[] = $row['rating'];
$all_result->free();
$all_reviews_r->close();
 
$total_reviews = count($all_ratings);
$avg_rating    = $total_reviews > 0 ? round(array_sum($all_ratings) / $total_reviews, 1) : 0;
 
// Reviews pagination
$rev_per_page  = 6;
$rev_page      = max(1, intval($_GET['reviews_page'] ?? 1));
$rev_total_p   = $total_reviews;
$rev_pages     = max(1, ceil($rev_total_p / $rev_per_page));
$rev_page      = min($rev_page, $rev_pages);
$rev_offset    = ($rev_page - 1) * $rev_per_page;
$base_url      = 'dashboard.php?page=view-votes';
 
// Fetch paged reviews
$reviews_r = $conn->prepare("
    SELECT r.review_id, r.rating, r.comment, r.created_at,
           l.title AS listing_title,
           rp.first_name, rp.last_name
    FROM product_service_reviews r
    JOIN listings l  ON r.listing_id = l.listing_id
    JOIN sme_profiles sp  ON l.sme_id     = sp.sme_id
    JOIN resident_profiles rp ON r.user_id    = rp.user_id
    WHERE sp.sme_id = ?
    ORDER BY r.created_at DESC
    LIMIT ? OFFSET ?
");
$reviews_r->bind_param("iii", $sme_id, $rev_per_page, $rev_offset);
$reviews_r->execute();
$reviews_result = $reviews_r->get_result();
$sme_reviews    = [];
while ($row = $reviews_result->fetch_assoc()) $sme_reviews[] = $row;
$reviews_result->free();
$reviews_r->close();
?>
 
 <div class="vv-page">
 
    <?php
        $icon     = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" /></svg>';
        $title    = 'Votes & Reviews';
        $subtitle = 'See how residents are voting and reviewing your listings.';
        include '../components/section_header.php';
    ?>
 
    <!-- Vote summary stats -->
    <div class="vv-stats-row" id="vv-stats-row" style="display:none;">
        <div class="vv-stat-card">
            <div class="vv-stat-icon vv-stat-icon--total">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                </svg>
            </div>
            <div class="vv-stat-info">
                <span class="vv-stat-value" id="vv-total-votes">0</span>
                <span class="vv-stat-label">Total Votes</span>
            </div>
        </div>
        <div class="vv-stat-card">
            <div class="vv-stat-icon vv-stat-icon--like">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />
                </svg>
            </div>
            <div class="vv-stat-info">
                <span class="vv-stat-value vv-stat-value--like" id="vv-total-likes">0</span>
                <span class="vv-stat-label">Total Likes</span>
            </div>
        </div>
        <div class="vv-stat-card">
            <div class="vv-stat-icon vv-stat-icon--dislike">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.498 15.25H4.372c-1.026 0-1.945-.694-2.054-1.715a12.137 12.137 0 0 1-.068-1.285c0-2.848.992-5.464 2.649-7.521C5.287 4.247 5.886 4 6.504 4h4.016a4.5 4.5 0 0 1 1.423.23l3.114 1.04a4.5 4.5 0 0 0 1.423.23h1.294M7.498 15.25c.618 0 .991.724.725 1.282A7.471 7.471 0 0 0 7.5 19.75 2.25 2.25 0 0 0 9.75 22a.75.75 0 0 0 .75-.75v-.633c0-.573.11-1.14.322-1.672.304-.76.93-1.33 1.653-1.715a9.04 9.04 0 0 0 2.86-2.4c.498-.634 1.226-1.08 2.032-1.08h.384m-10.253 1.5H9.7m8.075-9.75c.01.05.027.1.05.148.593 1.2.925 2.55.925 3.977 0 1.487-.36 2.89-.999 4.125m.023-8.25c-.076-.365.183-.75.575-.75h.908c.889 0 1.713.518 1.972 1.368.339 1.11.521 2.287.521 3.507 0 1.553-.295 3.036-.831 4.398-.306.774-1.086 1.227-1.918 1.227h-1.053c-.472 0-.745-.556-.5-.96a8.95 8.95 0 0 0 .303-.54" />
                </svg>
            </div>
            <div class="vv-stat-info">
                <span class="vv-stat-value vv-stat-value--dislike" id="vv-total-dislikes">0</span>
                <span class="vv-stat-label">Total Dislikes</span>
            </div>
        </div>
        <div class="vv-stat-card">
            <div class="vv-stat-icon vv-stat-icon--top">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                </svg>
            </div>
            <div class="vv-stat-info">
                <span class="vv-stat-value vv-stat-value--top" id="vv-most-liked" style="font-size:0.8rem;line-height:1.3;">—</span>
                <span class="vv-stat-label">Most Liked Listing</span>
            </div>
        </div>
    </div>
 
    <!-- Loading -->
    <div id="vv-loading" class="vv-loading">
        <div class="vv-spinner"></div>
        <p>Loading vote data…</p>
    </div>
 
    <!-- Votes table -->
    <div id="vv-table-wrapper" class="vv-table-wrapper" style="display:none;">
        <div class="vv-table-header-row">
            <span id="vv-count-label" class="vv-count-label"></span>
        </div>
        <table class="vv-table">
            <thead>
                <tr>
                    <th>Listing</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th class="vv-th-center">Likes</th>
                    <th class="vv-th-center">Dislikes</th>
                    <th>Sentiment</th>
                </tr>
            </thead>
            <tbody id="vv-table-body"></tbody>
        </table>
        <div id="vv-pagination"></div>
    </div>
 
    <!-- Empty state for votes -->
    <div id="vv-empty" class="vv-empty" style="display:none;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="52" height="52">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />
        </svg>
        <p>No votes yet on your listings.</p>
        <p class="vv-empty-sub">When residents like or dislike your listings, their feedback will appear here.</p>
    </div>
 
    <!--  Reviews Section  -->
    <div class="vv-reviews-section">
 
        <div class="vv-section-heading">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
            </svg>
            Listing Reviews
        </div>
 
        <!-- Review summary stats -->
        <div class="vv-review-stats">
            <div class="vv-review-stat-card">
                <span class="vv-review-stat-num"><?= $total_reviews ?></span>
                <span class="vv-review-stat-lbl">Total Reviews</span>
            </div>
            <div class="vv-review-stat-card vv-review-stat-card--gold">
                <span class="vv-review-stat-num"><?= $avg_rating > 0 ? $avg_rating . '/10' : '—' ?></span>
                <span class="vv-review-stat-lbl">Average Rating</span>
            </div>
        </div>
 
        <?php if ($total_reviews === 0) : ?>
        <div class="vv-empty" style="padding:2rem 0;">
            <p>No reviews yet on your listings.</p>
            <p class="vv-empty-sub">Residents who purchase and complete orders can leave reviews.</p>
        </div>
        <?php else : ?>
 
        <div class="vv-review-page-info">
            Showing <?= $rev_offset + 1 ?>–<?= min($rev_offset + $rev_per_page, $total_reviews) ?> of <?= $total_reviews ?> review<?= $total_reviews !== 1 ? 's' : '' ?>
        </div>
 
        <div class="vv-reviews-list">
            <?php foreach ($sme_reviews as $rev) : ?>
            <div class="vv-review-card">
                <div class="vv-review-card-header">
                    <div>
                        <p class="vv-review-listing"><?= htmlspecialchars($rev['listing_title']) ?></p>
                        <p class="vv-review-author"><?= htmlspecialchars($rev['first_name'] . ' ' . $rev['last_name']) ?></p>
                    </div>
                    <div class="vv-review-rating-wrap">
                        <span class="vv-review-rating"><?= $rev['rating'] ?><span class="vv-review-rating-max">/10</span></span>
                        <span class="vv-review-date"><?= date('d M Y', strtotime($rev['created_at'])) ?></span>
                    </div>
                </div>
                <?php if (!empty($rev['comment'])) : ?>
                <p class="vv-review-comment">"<?= htmlspecialchars($rev['comment']) ?>"</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
 
        <!-- Reviews Pagination -->
        <?php if ($rev_pages > 1) : ?>
        <div class="vv-pagination" style="margin-top:1rem;">
            <span class="vv-page-info">
                Page <?= $rev_page ?> of <?= $rev_pages ?>
            </span>
            <div class="vv-page-btns">
                <?php if ($rev_page > 1) : ?>
                <a href="<?= $base_url ?>&reviews_page=<?= $rev_page - 1 ?>" class="vv-page-btn">« Prev</a>
                <?php endif; ?>
                <?php for ($p = 1; $p <= $rev_pages; $p++) : ?>
                <a href="<?= $base_url ?>&reviews_page=<?= $p ?>"
                   class="vv-page-btn <?= $p === $rev_page ? 'vv-page-btn--active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if ($rev_page < $rev_pages) : ?>
                <a href="<?= $base_url ?>&reviews_page=<?= $rev_page + 1 ?>" class="vv-page-btn">Next »</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
 
        <?php endif; ?>
    </div>
 
</div>
 
<script>
    let vvAllListings = [];
    let vvPageNum     = 1;
    const VV_PER_PAGE = 8;
 
    document.addEventListener('DOMContentLoaded', () => vvLoad());
 
    function vvLoad() {
        const fd = new FormData();
        fd.append('action', 'get_votes');
 
        fetch('../pages/view-votes.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    document.getElementById('vv-loading').style.display = 'none';
 
                    if (!data.success) { document.getElementById('vv-empty').style.display = 'flex'; return; }
 
                    document.getElementById('vv-total-votes').textContent    = data.total_votes;
                    document.getElementById('vv-total-likes').textContent    = data.total_likes;
                    document.getElementById('vv-total-dislikes').textContent = data.total_dislikes;
                    document.getElementById('vv-most-liked').textContent     = data.most_liked ?? '—';
                    document.getElementById('vv-stats-row').style.display   = 'grid';
 
                    if (data.listings.length === 0) {
                        document.getElementById('vv-empty').style.display = 'flex';
                        return;
                    }
 
                    vvAllListings = data.listings;
                    vvRenderPage();
                } catch(e) {
                    document.getElementById('vv-loading').style.display = 'none';
                    document.getElementById('vv-empty').style.display   = 'flex';
                }
            })
            .catch(() => {
                document.getElementById('vv-loading').style.display = 'none';
                document.getElementById('vv-empty').style.display   = 'flex';
            });
    }
 
    function vvRenderPage() {
        const total      = vvAllListings.length;
        const totalPages = Math.ceil(total / VV_PER_PAGE);
        const start      = (vvPageNum - 1) * VV_PER_PAGE;
        const paged      = vvAllListings.slice(start, start + VV_PER_PAGE);
 
        document.getElementById('vv-count-label').textContent = `${total} listing${total !== 1 ? 's' : ''}`;
 
        const tbody = document.getElementById('vv-table-body');
        tbody.innerHTML = '';
        paged.forEach(l => tbody.appendChild(vvBuildRow(l)));
 
        document.getElementById('vv-table-wrapper').style.display = 'block';
        vvRenderPagination(totalPages);
    }
 
    function vvBuildRow(l) {
        const tr         = document.createElement('tr');
        const likes      = parseInt(l.likes);
        const dislikes   = parseInt(l.dislikes);
        const total      = likes + dislikes;
        const likePct    = total > 0 ? Math.round((likes / total) * 100) : 0;
        const dislikePct = total > 0 ? 100 - likePct : 0;
 
        let sentiment, sentClass;
        if (total === 0)          { sentiment = 'No votes yet'; sentClass = 'vv-sentiment--neutral'; }
        else if (likePct >= 70)   { sentiment = 'Positive';     sentClass = 'vv-sentiment--positive'; }
        else if (dislikePct >= 70){ sentiment = 'Negative';     sentClass = 'vv-sentiment--negative'; }
        else                      { sentiment = 'Mixed';         sentClass = 'vv-sentiment--mixed'; }
 
        const imgSrc = l.primary_image
            ? `../uploads/listings_images/${vvEsc(l.primary_image)}`
            : null;
 
        tr.innerHTML = `
            <td>
                <div class="vv-listing-cell">
                    <div class="vv-listing-thumb">
                        ${imgSrc
                            ? `<img src="${imgSrc}" alt="${vvEsc(l.title)}" onerror="this.style.display='none'">`
                            : `<div class="vv-thumb-placeholder"></div>`}
                    </div>
                    <div class="vv-listing-info">
                        <p class="vv-listing-title">${vvEsc(l.title)}</p>
                        <p class="vv-listing-sub">${vvEsc(l.subcategory_name)}</p>
                    </div>
                </div>
            </td>
            <td><span class="vv-cat-badge vv-cat-badge--${l.category_name === 'Product' ? 'product' : 'service'}">${vvEsc(l.category_name)}</span></td>
            <td class="vv-price">£${parseFloat(l.price).toFixed(2)}</td>
            <td class="vv-td-center"><span class="vv-count vv-count--like">${likes}</span></td>
            <td class="vv-td-center"><span class="vv-count vv-count--dislike">${dislikes}</span></td>
            <td>
                <div class="vv-bar-wrap">
                    <div class="vv-bar">
                        <div class="vv-bar-like"    style="width:${likePct}%"></div>
                        <div class="vv-bar-dislike" style="width:${dislikePct}%"></div>
                    </div>
                    <span class="vv-sentiment ${sentClass}">${sentiment}</span>
                </div>
            </td>
        `;
        return tr;
    }
 
    function vvRenderPagination(totalPages) {
        const pag = document.getElementById('vv-pagination');
        pag.innerHTML = '';
        if (totalPages <= 1) return;
 
        pag.className = 'vv-pagination';
 
        const info       = document.createElement('span');
        info.className   = 'vv-page-info';
        const start      = (vvPageNum - 1) * VV_PER_PAGE + 1;
        const end        = Math.min(vvPageNum * VV_PER_PAGE, vvAllListings.length);
        info.textContent = `Showing ${start}–${end} of ${vvAllListings.length}`;
        pag.appendChild(info);
 
        const btns = document.createElement('div');
        btns.className = 'vv-page-btns';
 
        if (vvPageNum > 1) {
            const prev = document.createElement('button');
            prev.className = 'vv-page-btn'; prev.textContent = '« Prev';
            prev.onclick = () => { vvPageNum--; vvRenderPage(); };
            btns.appendChild(prev);
        }
        for (let p = 1; p <= totalPages; p++) {
            const btn = document.createElement('button');
            btn.className   = 'vv-page-btn' + (p === vvPageNum ? ' vv-page-btn--active' : '');
            btn.textContent = p;
            btn.onclick     = ((pg) => () => { vvPageNum = pg; vvRenderPage(); })(p);
            btns.appendChild(btn);
        }
        if (vvPageNum < totalPages) {
            const next = document.createElement('button');
            next.className = 'vv-page-btn'; next.textContent = 'Next »';
            next.onclick = () => { vvPageNum++; vvRenderPage(); };
            btns.appendChild(next);
        }
 
        pag.appendChild(btns);
    }
 
    function vvEsc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
</script>
