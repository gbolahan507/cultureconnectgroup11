<?php ob_start(); ?>
<?php
// AJAX HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($conn)) include '../db_connection.php';
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
 
    $user_id = $_SESSION['user_id'] ?? null;
    $role    = $_SESSION['user_role'] ?? '';
 
    if (!in_array($role, ['Council Administrator', 'Council Member'])) {
        echo json_encode(['success' => false, 'message' => 'Permission denied.']);
        exit();
    }
 
    // ── Get all active listings (for poll options selector) ───
    if ($_POST['action'] === 'get_listings') {
        $result   = mysqli_query($conn, "
            SELECT l.listing_id, l.title, l.price,
                   pc.category_name,
                   sp.business_name
            FROM listings l
            JOIN product_service ps                ON l.item_id        = ps.item_id
            JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
            JOIN product_service_categories pc     ON pss.category_id   = pc.category_id
            JOIN sme_profiles sp                   ON l.sme_id          = sp.sme_id
            WHERE l.status = 'active'
            ORDER BY pc.category_name, l.title
        ");
        $listings = [];
        while ($row = mysqli_fetch_assoc($result)) $listings[] = $row;
        echo json_encode(['success' => true, 'listings' => $listings]);
        exit();
    }
 
    // Get all polls 
    if ($_POST['action'] === 'get_polls') {
        $result = mysqli_query($conn, "
            SELECT p.poll_id, p.title, p.description, p.start_date, p.end_date,
                   u.email_address AS created_by_email,
                   CONCAT(rp.first_name, ' ', rp.last_name) AS created_by_name,
                   COUNT(DISTINCT po.option_id) AS option_count,
                   COUNT(DISTINCT pv.vote_id)   AS vote_count
            FROM poll p
            JOIN users u ON p.created_by = u.user_id
            LEFT JOIN resident_profiles rp ON p.created_by = rp.user_id
            LEFT JOIN poll_options po ON p.poll_id = po.poll_id
            LEFT JOIN poll_votes pv   ON p.poll_id = pv.poll_id
            GROUP BY p.poll_id
            ORDER BY p.start_date DESC
        ");
        $polls = [];
        while ($row = mysqli_fetch_assoc($result)) $polls[] = $row;
        echo json_encode(['success' => true, 'polls' => $polls]);
        exit();
    }
 
    // Get single poll details 
    if ($_POST['action'] === 'get_poll') {
        $poll_id = intval($_POST['poll_id'] ?? 0);
 
        $stmt = $conn->prepare("
            SELECT p.poll_id, p.title, p.description, p.start_date, p.end_date
            FROM poll p WHERE p.poll_id = ? LIMIT 1
        ");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $poll = $stmt->get_result()->fetch_assoc();
        $stmt->close();
 
        if (!$poll) { echo json_encode(['success' => false, 'message' => 'Poll not found.']); exit(); }
 
        // Get options (listings)
        $opt_stmt = $conn->prepare("
            SELECT po.option_id, l.listing_id, l.title, l.price,
                   pc.category_name, sp.business_name
            FROM poll_options po
            JOIN listings l                        ON po.listing_id     = l.listing_id
            JOIN product_service ps                ON l.item_id         = ps.item_id
            JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
            JOIN product_service_categories pc     ON pss.category_id   = pc.category_id
            JOIN sme_profiles sp                   ON l.sme_id          = sp.sme_id
            WHERE po.poll_id = ?
            ORDER BY po.option_id
        ");
        $opt_stmt->bind_param("i", $poll_id);
        $opt_stmt->execute();
        $options = [];
        while ($row = $opt_stmt->get_result()->fetch_assoc()) $options[] = $row;
        $opt_stmt->close();
 
        echo json_encode(['success' => true, 'poll' => $poll, 'options' => $options]);
        exit();
    }
 
    // Create Poll
    if ($_POST['action'] === 'create_poll') {
        $title      = trim($_POST['title']       ?? '');
        $description = trim($_POST['description'] ?? '');
        $start_date  = trim($_POST['start_date']  ?? '');
        $end_date    = trim($_POST['end_date']    ?? '');
        $listing_ids = $_POST['listing_ids']      ?? [];
 
        if (empty($title) || empty($start_date) || empty($end_date)) {
            echo json_encode(['success' => false, 'message' => 'Title, start date and end date are required.']);
            exit();
        }
 
        if (count($listing_ids) < 2) {
            echo json_encode(['success' => false, 'message' => 'Please select at least 2 listings as poll options.']);
            exit();
        }
 
        if ($end_date <= $start_date) {
            echo json_encode(['success' => false, 'message' => 'End date must be after start date.']);
            exit();
        }
 
        // Insert poll
        $stmt = $conn->prepare("
            INSERT INTO poll (title, description, start_date, end_date, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssi", $title, $description, $start_date, $end_date, $user_id);
 
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Failed to create poll.']);
            $stmt->close(); exit();
        }
 
        $poll_id = $conn->insert_id;
        $stmt->close();
 
        // Insert poll options
        foreach ($listing_ids as $listing_id) {
            $listing_id = intval($listing_id);
            $opt = $conn->prepare("INSERT INTO poll_options (poll_id, listing_id) VALUES (?, ?)");
            $opt->bind_param("ii", $poll_id, $listing_id);
            $opt->execute();
            $opt->close();
        }
 
        echo json_encode(['success' => true, 'message' => 'Poll created successfully.']);
        exit();
    }
 
    // Delete Poll 
    if ($_POST['action'] === 'delete_poll') {
        $poll_id = intval($_POST['poll_id'] ?? 0);
 
        // Delete votes first, then options, then poll
        $conn->query("DELETE FROM poll_votes WHERE poll_id = $poll_id");
        $conn->query("DELETE FROM poll_options WHERE poll_id = $poll_id");
 
        $stmt = $conn->prepare("DELETE FROM poll WHERE poll_id = ?");
        $stmt->bind_param("i", $poll_id);
 
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Poll deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete poll.']);
        }
        $stmt->close();
        exit();
    }
 
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit();
}
 
// PAGE GUARD
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Council Administrator', 'Council Member'])) {
    header('Location: ../pages/dashboard.php?page=home');
    exit();
}
?>

<div class="mv-page">
 
    <?php
        $icon     = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75H6A2.25 2.25 0 0 0 3.75 6v1.5M16.5 3.75H18A2.25 2.25 0 0 1 20.25 6v1.5m0 9V18A2.25 2.25 0 0 1 18 20.25h-1.5m-9 0H6A2.25 2.25 0 0 1 3.75 18v-1.5M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>';
        $title    = 'Manage Polls';
        $subtitle = 'Create and manage community voting polls.';
        include '../components/section_header.php';
    ?>
 
    <!-- Toolbar -->
    <div class="mv-toolbar">
        <div id="mv-count-label" class="mv-count-label"></div>
        <button class="mv-create-btn" onclick="mvOpenCreateModal()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="14" height="14">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Create New Poll
        </button>
    </div>
 
    <!-- Loading -->
    <div id="mv-loading" class="mv-loading">
        <div class="mv-spinner"></div>
        <p>Loading polls…</p>
    </div>
 
    <!-- Polls grid -->
    <div id="mv-polls-grid" class="mv-polls-grid" style="display:none;"></div>
 
    <!-- Empty -->
    <div id="mv-empty" class="mv-empty" style="display:none;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="52" height="52">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75H6A2.25 2.25 0 0 0 3.75 6v1.5M16.5 3.75H18A2.25 2.25 0 0 1 20.25 6v1.5m0 9V18A2.25 2.25 0 0 1 18 20.25h-1.5m-9 0H6A2.25 2.25 0 0 1 3.75 18v-1.5M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        <p>No polls created yet. Create your first poll to start collecting community votes.</p>
        <button class="mv-create-btn" onclick="mvOpenCreateModal()">Create First Poll</button>
    </div>
 
</div>

<!-- CREATE POLL MODAL -->
<div id="mv-create-modal" class="mv-modal-overlay" style="display:none;">
    <div class="mv-modal-box mv-modal-box--wide">
        <div class="mv-modal-header">
            <h3>Create New Poll</h3>
            <span class="mv-modal-close-btn" onclick="mvCloseCreateModal()">&times;</span>
        </div>
        <div class="mv-modal-body mv-modal-body--padded">
 
            <div id="mv-create-alert" class="mv-alert" style="display:none;"></div>
 
            <!-- Title -->
            <div class="mv-field">
                <label for="mv-poll-title">Poll Title <span class="mv-required">*</span></label>
                <input type="text" id="mv-poll-title" class="mv-input"
                       placeholder="e.g. Vote for your favourite cultural listing this month">
            </div>
 
            <!-- Description -->
            <div class="mv-field">
                <label for="mv-poll-desc">Description</label>
                <textarea id="mv-poll-desc" class="mv-input mv-textarea" rows="3"
                          placeholder="Explain the purpose of this poll to residents…"></textarea>
            </div>
 
            <!-- Dates -->
            <div class="mv-dates-row">
                <div class="mv-field">
                    <label for="mv-start-date">Start Date <span class="mv-required">*</span></label>
                    <input type="date" id="mv-start-date" class="mv-input">
                </div>
                <div class="mv-field">
                    <label for="mv-end-date">End Date <span class="mv-required">*</span></label>
                    <input type="date" id="mv-end-date" class="mv-input">
                </div>
            </div>
 
            <!-- Listings selector -->
            <div class="mv-field">
                <label>Poll Options — Select Listings <span class="mv-required">*</span></label>
                <p class="mv-field-hint">Select at least 2 active listings for residents to vote on.</p>
                <div class="mv-search-box">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="15" height="15">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input type="text" id="mv-listing-search" class="mv-search-input"
                           placeholder="Search listings…" oninput="mvFilterListings()">
                </div>
                <div id="mv-listings-list" class="mv-listings-list">
                    <div class="mv-listings-loading"><div class="mv-spinner mv-spinner--sm"></div> Loading listings…</div>
                </div>
                <div id="mv-selected-count" class="mv-selected-count">0 listings selected</div>
            </div>
 
        </div>
        <div class="mv-modal-footer">
            <button class="mv-modal-cancel-btn" onclick="mvCloseCreateModal()">Cancel</button>
            <button class="mv-modal-submit-btn" id="mv-create-submit-btn" onclick="mvCreatePoll()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Create Poll
            </button>
        </div>
    </div>
</div>
 
 
<!-- VIEW POLL MODAL -->
<div id="mv-view-modal" class="mv-modal-overlay" style="display:none;">
    <div class="mv-modal-box">
        <div class="mv-modal-header">
            <h3>Poll Details</h3>
            <span class="mv-modal-close-btn" onclick="mvCloseViewModal()">&times;</span>
        </div>
        <div class="mv-modal-body" id="mv-view-body"></div>
        <div class="mv-modal-footer">
            <button class="mv-modal-cancel-btn" onclick="mvCloseViewModal()">Close</button>
        </div>
    </div>
</div>
 
 
<!--  DELETE CONFIRM MODAL -->
<div id="mv-delete-modal" class="mv-modal-overlay" style="display:none;">
    <div class="mv-modal-box mv-modal-box--sm">
        <div class="mv-modal-header mv-modal-header--danger">
            <h3>Delete Poll</h3>
            <span class="mv-modal-close-btn" onclick="mvCloseDeleteModal()">&times;</span>
        </div>
        <div class="mv-modal-body mv-modal-body--padded">
            <p class="mv-delete-text">Are you sure you want to delete <strong id="mv-delete-poll-title"></strong>?</p>
            <div class="mv-delete-warning">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                This will permanently delete the poll, all its options and all votes cast. This cannot be undone.
            </div>
        </div>
        <div class="mv-modal-footer">
            <button class="mv-modal-cancel-btn" onclick="mvCloseDeleteModal()">Cancel</button>
            <button class="mv-btn-danger" id="mv-delete-confirm-btn" onclick="mvConfirmDelete()">Yes, Delete Poll</button>
        </div>
    </div>
</div>

<!-- JAVASCRIPT -->
<script>
    let mvAllListings   = [];
    let mvAllPolls    = [];
    let mvPollPage    = 1;
    const MV_PER_PAGE = 6;
    let mvDeletePollId  = null;
 
    document.addEventListener('DOMContentLoaded', () => {
        mvLoadPolls();
        mvLoadListings();
    });
 
    // Load polls 
    function mvLoadPolls() {
    const loading = document.getElementById('mv-loading');
    const grid    = document.getElementById('mv-polls-grid');
    const empty   = document.getElementById('mv-empty');
    const count   = document.getElementById('mv-count-label');

    mvPollPage = 1;
    loading.style.display = 'flex';
    grid.style.display    = 'none';
    empty.style.display   = 'none';

    const fd = new FormData();
    fd.append('action', 'get_polls');

    fetch('../pages/manage-votes.php', { method: 'POST', body: fd })
        .then(r => r.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                loading.style.display = 'none';

                if (data.success && data.polls.length > 0) {
                    mvAllPolls = data.polls;
                    mvRenderPollPage();
                } else {
                    mvAllPolls          = [];
                    count.textContent   = '';
                    empty.style.display = 'flex';
                    mvRemovePollPagination();
                }
            } catch(e) { loading.style.display = 'none'; empty.style.display = 'flex'; }
        })
        .catch(() => { loading.style.display = 'none'; empty.style.display = 'flex'; });
}

function mvRenderPollPage() {
    const grid  = document.getElementById('mv-polls-grid');
    const count = document.getElementById('mv-count-label');
    const total = mvAllPolls.length;
    const totalPages = Math.ceil(total / MV_PER_PAGE);
    const start = (mvPollPage - 1) * MV_PER_PAGE;
    const paged = mvAllPolls.slice(start, start + MV_PER_PAGE);

    count.textContent = `${total} poll${total !== 1 ? 's' : ''} found`;
    grid.innerHTML    = '';
    paged.forEach(p => grid.appendChild(mvBuildPollCard(p)));
    grid.style.display = 'grid';

    mvRenderPollPagination(totalPages);
}

function mvRenderPollPagination(totalPages) {
    mvRemovePollPagination();
    if (totalPages <= 1) return;

    const grid = document.getElementById('mv-polls-grid');
    const pag  = document.createElement('div');
    pag.id        = 'mv-pagination';
    pag.className = 'mv-pagination';

    const info       = document.createElement('span');
    info.className   = 'mv-page-info';
    const start      = (mvPollPage - 1) * MV_PER_PAGE + 1;
    const end        = Math.min(mvPollPage * MV_PER_PAGE, mvAllPolls.length);
    info.textContent = `Showing ${start}–${end} of ${mvAllPolls.length}`;
    pag.appendChild(info);

    const btns = document.createElement('div');
    btns.className = 'mv-page-btns';

    if (mvPollPage > 1) {
        const prev     = document.createElement('button');
        prev.className = 'mv-page-btn';
        prev.textContent = '« Prev';
        prev.onclick   = () => { mvPollPage--; mvRenderPollPage(); };
        btns.appendChild(prev);
    }

    for (let p = 1; p <= totalPages; p++) {
        const btn = document.createElement('button');
        btn.className   = 'mv-page-btn' + (p === mvPollPage ? ' mv-page-btn--active' : '');
        btn.textContent = p;
        btn.onclick     = ((pg) => () => { mvPollPage = pg; mvRenderPollPage(); })(p);
        btns.appendChild(btn);
    }

    if (mvPollPage < totalPages) {
        const next     = document.createElement('button');
        next.className = 'mv-page-btn';
        next.textContent = 'Next »';
        next.onclick   = () => { mvPollPage++; mvRenderPollPage(); };
        btns.appendChild(next);
    }

    pag.appendChild(btns);
    grid.after(pag);
   }

    function mvRemovePollPagination() {
    const existing = document.getElementById('mv-pagination');
    if (existing) existing.remove();
    }
 
    // Build poll card
    function mvBuildPollCard(p) {
        const card      = document.createElement('div');
        const today     = new Date().toISOString().split('T')[0];
        const isOpen    = today >= p.start_date && today <= p.end_date;
        const isUpcoming = today < p.start_date;
        const statusCls = isOpen ? 'mv-status--open' : isUpcoming ? 'mv-status--upcoming' : 'mv-status--closed';
        const statusLbl = isOpen ? 'Open'            : isUpcoming ? 'Upcoming'            : 'Closed';
 
        const startFmt = new Date(p.start_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        const endFmt   = new Date(p.end_date).toLocaleDateString('en-GB',   { day: '2-digit', month: 'short', year: 'numeric' });
 
        card.className = 'mv-poll-card';
        card.innerHTML = `
            <div class="mv-poll-card-header">
                <span class="mv-status-badge ${statusCls}">${statusLbl}</span>
                <div class="mv-poll-card-actions">
                    <button class="mv-icon-btn" title="View details" onclick="mvOpenView(${p.poll_id})">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </button>
                    <button class="mv-icon-btn mv-icon-btn--danger" title="Delete poll" onclick="mvOpenDelete(${p.poll_id}, '${mvEsc(p.title)}')">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                    </button>
                </div>
            </div>
            <h3 class="mv-poll-title">${mvEsc(p.title)}</h3>
            <p class="mv-poll-desc">${mvEsc(p.description || 'No description provided.')}</p>
            <div class="mv-poll-meta">
                <div class="mv-poll-meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    ${startFmt} — ${endFmt}
                </div>
            </div>
            <div class="mv-poll-stats">
                <div class="mv-poll-stat">
                    <span class="mv-poll-stat-num">${p.option_count}</span>
                    <span class="mv-poll-stat-lbl">Options</span>
                </div>
                <div class="mv-poll-stat">
                    <span class="mv-poll-stat-num">${p.vote_count}</span>
                    <span class="mv-poll-stat-lbl">Votes Cast</span>
                </div>
            </div>
        `;
        return card;
    }
 
    // Load listings for selector
    function mvLoadListings() {
        const fd = new FormData();
        fd.append('action', 'get_listings');
 
        fetch('../pages/manage-votes.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        mvAllListings = data.listings;
                        mvRenderListings(data.listings);
                    }
                } catch(e) {}
            });
    }
 
    function mvRenderListings(listings) {
        const container = document.getElementById('mv-listings-list');
        if (!container) return;
 
        if (listings.length === 0) {
            container.innerHTML = '<p class="mv-listings-empty">No active listings found.</p>';
            return;
        }
 
        container.innerHTML = listings.map(l => `
            <label class="mv-listing-option" id="mv-opt-${l.listing_id}">
                <input type="checkbox" value="${l.listing_id}" onchange="mvCountSelected()">
                <div class="mv-listing-option-info">
                    <span class="mv-listing-option-title">${mvEsc(l.title)}</span>
                    <span class="mv-listing-option-meta">${mvEsc(l.business_name)} · ${mvEsc(l.category_name)} · £${parseFloat(l.price).toFixed(2)}</span>
                </div>
            </label>
        `).join('');
    }
 
    function mvFilterListings() {
        const q        = document.getElementById('mv-listing-search').value.toLowerCase();
        const filtered = mvAllListings.filter(l =>
            l.title.toLowerCase().includes(q) ||
            l.business_name.toLowerCase().includes(q) ||
            l.category_name.toLowerCase().includes(q)
        );
        mvRenderListings(filtered);
    }
 
    function mvCountSelected() {
        const checked = document.querySelectorAll('#mv-listings-list input[type="checkbox"]:checked').length;
        document.getElementById('mv-selected-count').textContent = `${checked} listing${checked !== 1 ? 's' : ''} selected`;
    }
 
    // Create modal
    function mvOpenCreateModal() {
        document.getElementById('mv-poll-title').value  = '';
        document.getElementById('mv-poll-desc').value   = '';
        document.getElementById('mv-start-date').value  = '';
        document.getElementById('mv-end-date').value    = '';
        document.getElementById('mv-listing-search').value = '';
        document.getElementById('mv-create-alert').style.display = 'none';
        document.getElementById('mv-selected-count').textContent = '0 listings selected';
        mvRenderListings(mvAllListings);
        document.getElementById('mv-create-modal').style.display = 'flex';
    }
 
    function mvCloseCreateModal() {
        document.getElementById('mv-create-modal').style.display = 'none';
    }
 
    function mvCreatePoll() {
        const title      = document.getElementById('mv-poll-title').value.trim();
        const desc       = document.getElementById('mv-poll-desc').value.trim();
        const start_date = document.getElementById('mv-start-date').value;
        const end_date   = document.getElementById('mv-end-date').value;
        const checked    = Array.from(document.querySelectorAll('#mv-listings-list input[type="checkbox"]:checked')).map(cb => cb.value);
        const alertEl    = document.getElementById('mv-create-alert');
        const btn        = document.getElementById('mv-create-submit-btn');
 
        if (!title || !start_date || !end_date) {
            alertEl.textContent   = 'Please fill in all required fields.';
            alertEl.className     = 'mv-alert mv-alert--error';
            alertEl.style.display = 'block';
            return;
        }
 
        if (checked.length < 2) {
            alertEl.textContent   = 'Please select at least 2 listings as poll options.';
            alertEl.className     = 'mv-alert mv-alert--error';
            alertEl.style.display = 'block';
            return;
        }
 
        if (end_date <= start_date) {
            alertEl.textContent   = 'End date must be after start date.';
            alertEl.className     = 'mv-alert mv-alert--error';
            alertEl.style.display = 'block';
            return;
        }
 
        btn.disabled    = true;
        btn.textContent = 'Creating…';
 
        const fd = new FormData();
        fd.append('action',      'create_poll');
        fd.append('title',       title);
        fd.append('description', desc);
        fd.append('start_date',  start_date);
        fd.append('end_date',    end_date);
        checked.forEach(id => fd.append('listing_ids[]', id));
 
        fetch('../pages/manage-votes.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        mvCloseCreateModal();
                        mvShowToast(data.message, 'success');
                        mvLoadPolls();
                    } else {
                        alertEl.textContent   = data.message || 'Failed to create poll.';
                        alertEl.className     = 'mv-alert mv-alert--error';
                        alertEl.style.display = 'block';
                    }
                } catch(e) {
                    alertEl.textContent   = 'Unexpected error.';
                    alertEl.className     = 'mv-alert mv-alert--error';
                    alertEl.style.display = 'block';
                }
            })
            .catch(() => {
                alertEl.textContent   = 'Network error.';
                alertEl.className     = 'mv-alert mv-alert--error';
                alertEl.style.display = 'block';
            })
            .finally(() => {
                btn.disabled  = false;
                btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg> Create Poll`;
            });
    }
 
    // View poll modal
    function mvOpenView(poll_id) {
        const modal = document.getElementById('mv-view-modal');
        const body  = document.getElementById('mv-view-body');
        body.innerHTML = '<div class="mv-detail-loading"><div class="mv-spinner mv-spinner--sm"></div> Loading…</div>';
        modal.style.display = 'flex';
 
        const fd = new FormData();
        fd.append('action',  'get_poll');
        fd.append('poll_id', poll_id);
 
        fetch('../pages/manage-votes.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        const p      = data.poll;
                        const today  = new Date().toISOString().split('T')[0];
                        const isOpen = today >= p.start_date && today <= p.end_date;
                        const isUp   = today < p.start_date;
                        const sLbl   = isOpen ? 'Open' : isUp ? 'Upcoming' : 'Closed';
                        const sCls   = isOpen ? 'mv-status--open' : isUp ? 'mv-status--upcoming' : 'mv-status--closed';
                        const startFmt = new Date(p.start_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                        const endFmt   = new Date(p.end_date).toLocaleDateString('en-GB',   { day: '2-digit', month: 'short', year: 'numeric' });
 
                        let optionsHtml = data.options.map(o => `
                            <div class="mv-view-option">
                                <div class="mv-view-option-dot"></div>
                                <div>
                                    <p class="mv-view-option-title">${mvEsc(o.title)}</p>
                                    <p class="mv-view-option-meta">${mvEsc(o.business_name)} · ${mvEsc(o.category_name)} · £${parseFloat(o.price).toFixed(2)}</p>
                                </div>
                            </div>
                        `).join('');
 
                        body.innerHTML = `
                            <div class="mv-view-meta">
                                <div class="mv-view-meta-row"><span class="mv-view-label">Status</span><span class="mv-status-badge ${sCls}">${sLbl}</span></div>
                                <div class="mv-view-meta-row"><span class="mv-view-label">Title</span><span class="mv-view-val">${mvEsc(p.title)}</span></div>
                                <div class="mv-view-meta-row"><span class="mv-view-label">Description</span><span class="mv-view-val">${mvEsc(p.description || '—')}</span></div>
                                <div class="mv-view-meta-row"><span class="mv-view-label">Open</span><span class="mv-view-val">${startFmt}</span></div>
                                <div class="mv-view-meta-row"><span class="mv-view-label">Closes</span><span class="mv-view-val">${endFmt}</span></div>
                            </div>
                            <div class="mv-view-section-title">${data.options.length} Poll Option${data.options.length !== 1 ? 's' : ''}</div>
                            <div class="mv-view-options">${optionsHtml}</div>
                        `;
                    } else {
                        body.innerHTML = `<p class="mv-detail-error">${mvEsc(data.message)}</p>`;
                    }
                } catch(e) { body.innerHTML = '<p class="mv-detail-error">Failed to load poll.</p>'; }
            });
    }
 
    function mvCloseViewModal() { document.getElementById('mv-view-modal').style.display = 'none'; }
 
    // Delete modal 
    function mvOpenDelete(poll_id, title) {
        mvDeletePollId = poll_id;
        document.getElementById('mv-delete-poll-title').textContent = title;
        document.getElementById('mv-delete-modal').style.display = 'flex';
    }
 
    function mvCloseDeleteModal() { document.getElementById('mv-delete-modal').style.display = 'none'; mvDeletePollId = null; }
 
    function mvConfirmDelete() {
        if (!mvDeletePollId) return;
        const btn = document.getElementById('mv-delete-confirm-btn');
        btn.disabled = true; btn.textContent = 'Deleting…';
 
        const fd = new FormData();
        fd.append('action',  'delete_poll');
        fd.append('poll_id', mvDeletePollId);
 
        fetch('../pages/manage-votes.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) { mvCloseDeleteModal(); mvShowToast(data.message, 'success'); mvLoadPolls(); }
                    else mvShowToast(data.message || 'Failed.', 'error');
                } catch(e) { mvShowToast('Unexpected error.', 'error'); }
            })
            .catch(() => mvShowToast('Network error.', 'error'))
            .finally(() => { btn.disabled = false; btn.textContent = 'Yes, Delete Poll'; });
    }
 
    // Toast 
    function mvShowToast(message, type) {
        const existing = document.getElementById('mv-toast');
        if (existing) existing.remove();
        const toast = document.createElement('div');
        toast.id = 'mv-toast'; toast.className = `mv-toast mv-toast--${type}`; toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('mv-toast--show'), 10);
        setTimeout(() => { toast.classList.remove('mv-toast--show'); setTimeout(() => toast.remove(), 300); }, 3000);
    }
 
    function mvEsc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
</script>