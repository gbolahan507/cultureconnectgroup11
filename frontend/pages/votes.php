<?php ob_start(); ?>
<?php
// AJAX HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($conn)) include '../db_connection.php';
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
 
    $user_id = $_SESSION['user_id'] ?? null;
 
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in to vote.']);
        exit();
    }
 
    // Get open polls with vote status 
    if ($_POST['action'] === 'get_polls') {
        $today = date('Y-m-d');
 
        $stmt = $conn->prepare("
            SELECT p.poll_id, p.title, p.description, p.start_date, p.end_date,
                   COUNT(DISTINCT pv_all.vote_id) AS total_votes,
                   MAX(CASE WHEN pv_me.user_id = ? THEN 1 ELSE 0 END) AS has_voted,
                   MAX(CASE WHEN pv_me.user_id = ? THEN pv_me.option_id ELSE NULL END) AS my_option_id
            FROM poll p
            LEFT JOIN poll_votes pv_all ON p.poll_id = pv_all.poll_id
            LEFT JOIN poll_votes pv_me  ON p.poll_id = pv_me.poll_id AND pv_me.user_id = ?
            WHERE p.start_date <= ? AND p.end_date >= ?
            GROUP BY p.poll_id
            ORDER BY p.end_date ASC
        ");
        $stmt->bind_param("iiiss", $user_id, $user_id, $user_id, $today, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        $polls  = [];
        while ($row = $result->fetch_assoc()) $polls[] = $row;
        $stmt->close();
 
        echo json_encode(['success' => true, 'polls' => $polls]);
        exit();
    }
 
    // Get poll options
    if ($_POST['action'] === 'get_poll_options') {
        $poll_id = intval($_POST['poll_id'] ?? 0);
        $today   = date('Y-m-d');
 
        // Verify poll is open
        $check = $conn->prepare("SELECT poll_id FROM poll WHERE poll_id = ? AND start_date <= ? AND end_date >= ?");
        $check->bind_param("iss", $poll_id, $today, $today);
        $check->execute();
        $check->store_result();
        if ($check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Poll not found or not open.']);
            $check->close(); exit();
        }
        $check->close();
 
        // Get options with vote counts
        $stmt = $conn->prepare("
            SELECT po.option_id, po.listing_id,
                   l.title, l.caption, l.price,
                   pc.category_name,
                   pss.subcategory_name,
                   sp.business_name,
                   a.area_name,
                   li.image_url AS primary_image,
                   COUNT(pv.vote_id) AS vote_count
            FROM poll_options po
            JOIN listings l                        ON po.listing_id     = l.listing_id
            JOIN product_service ps                ON l.item_id         = ps.item_id
            JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
            JOIN product_service_categories pc     ON pss.category_id   = pc.category_id
            JOIN sme_profiles sp                   ON l.sme_id          = sp.sme_id
            JOIN areas a                           ON sp.area_id        = a.area_id
            LEFT JOIN listing_images li            ON l.listing_id      = li.listing_id AND li.is_primary = 1
            LEFT JOIN poll_votes pv                ON po.option_id      = pv.option_id
            WHERE po.poll_id = ?
            GROUP BY po.option_id
            ORDER BY po.option_id
        ");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $result  = $stmt->get_result();
        $options = [];
        while ($row = $result->fetch_assoc()) $options[] = $row;
        $stmt->close();
 
        // Check if user already voted
        $voted_stmt = $conn->prepare("SELECT option_id FROM poll_votes WHERE poll_id = ? AND user_id = ? LIMIT 1");
        $voted_stmt->bind_param("ii", $poll_id, $user_id);
        $voted_stmt->execute();
        $voted_row = $voted_stmt->get_result()->fetch_assoc();
        $voted_stmt->close();
 
        echo json_encode([
            'success'       => true,
            'options'       => $options,
            'has_voted'     => $voted_row ? true : false,
            'my_option_id'  => $voted_row ? intval($voted_row['option_id']) : null
        ]);
        exit();
    }
 
    // Cast Vote 
    if ($_POST['action'] === 'cast_vote') {
        $poll_id   = intval($_POST['poll_id']   ?? 0);
        $option_id = intval($_POST['option_id'] ?? 0);
        $today     = date('Y-m-d');
 
        if (!$poll_id || !$option_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid poll or option.']);
            exit();
        }
 
        // Verify poll is open
        $check = $conn->prepare("SELECT poll_id FROM poll WHERE poll_id = ? AND start_date <= ? AND end_date >= ?");
        $check->bind_param("iss", $poll_id, $today, $today);
        $check->execute();
        $check->store_result();
        if ($check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'This poll is not currently open.']);
            $check->close(); exit();
        }
        $check->close();
 
        // Verify option belongs to poll
        $opt_check = $conn->prepare("SELECT option_id FROM poll_options WHERE option_id = ? AND poll_id = ?");
        $opt_check->bind_param("ii", $option_id, $poll_id);
        $opt_check->execute();
        $opt_check->store_result();
        if ($opt_check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid option.']);
            $opt_check->close(); exit();
        }
        $opt_check->close();
 
        // Check if already voted
        $voted = $conn->prepare("SELECT vote_id FROM poll_votes WHERE poll_id = ? AND user_id = ?");
        $voted->bind_param("ii", $poll_id, $user_id);
        $voted->execute();
        $voted->store_result();
        if ($voted->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'You have already voted in this poll.']);
            $voted->close(); exit();
        }
        $voted->close();
 
        // Insert vote
        $stmt = $conn->prepare("INSERT INTO poll_votes (user_id, poll_id, option_id) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $user_id, $poll_id, $option_id);
 
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Your vote has been cast successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to cast vote. Please try again.']);
        }
        $stmt->close();
        exit();
    }
 
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit();
}
 

// PAGE GUARD — Residents only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Resident') {
    header('Location: ../pages/dashboard.php?page=home');
    exit();
}
?>

<div class="vt-page">
 
    <?php
        $icon     = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" /></svg>';
        $title    = 'My Votes';
        $subtitle = 'Participate in community polls and vote for your favourite listings.';
        include '../components/section_header.php';
    ?>
 
    <!-- Loading -->
    <div id="vt-loading" class="vt-loading">
        <div class="vt-spinner"></div>
        <p>Loading polls…</p>
    </div>
 
    <!-- Polls list -->
    <div id="vt-polls-wrap" style="display:none;"></div>
 
    <!-- Empty -->
    <div id="vt-empty" class="vt-empty" style="display:none;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="52" height="52">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75H6A2.25 2.25 0 0 0 3.75 6v1.5M16.5 3.75H18A2.25 2.25 0 0 1 20.25 6v1.5m0 9V18A2.25 2.25 0 0 1 18 20.25h-1.5m-9 0H6A2.25 2.25 0 0 1 3.75 18v-1.5M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        <p>No open polls at the moment. Check back later!</p>
    </div>
 
</div>
 
 
<!-- VOTE MODAL-->
<div id="vt-vote-modal" class="vt-modal-overlay" style="display:none;">
    <div class="vt-modal-box vt-modal-box--wide">
        <div class="vt-modal-header">
            <div>
                <p class="vt-modal-label">Community Poll</p>
                <h3 id="vt-modal-title">Poll Title</h3>
            </div>
            <span class="vt-modal-close-btn" onclick="vtCloseVoteModal()">&times;</span>
        </div>
        <div class="vt-modal-body">
            <div id="vt-modal-alert" class="vt-alert" style="display:none;"></div>
            <p id="vt-modal-desc" class="vt-modal-desc"></p>
            <div id="vt-modal-deadline" class="vt-modal-deadline"></div>
 
            <!-- Already voted banner -->
            <div id="vt-voted-banner" class="vt-voted-banner" style="display:none;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                You have already cast your vote in this poll. Your choice is highlighted below.
            </div>
 
            <!-- Options grid -->
            <div id="vt-options-grid" class="vt-options-grid">
                <div class="vt-options-loading"><div class="vt-spinner vt-spinner--sm"></div> Loading options…</div>
            </div>
        </div>
        <div class="vt-modal-footer">
            <button class="vt-modal-cancel-btn" onclick="vtCloseVoteModal()">Close</button>
            <button class="vt-modal-submit-btn" id="vt-cast-btn" onclick="vtCastVote()" style="display:none;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                Cast My Vote
            </button>
        </div>
    </div>
</div>
 
<!-- JAVASCRIPT -->
<script>
    let vtCurrentPollId   = null;
    let vtSelectedOption  = null;
    let vtHasVoted        = false;
    let vtMyOptionId      = null;
    let vtCurrentOptions  = [];
 
    document.addEventListener('DOMContentLoaded', () => vtLoadPolls());
 
    // Load open polls 
    function vtLoadPolls() {
        const loading = document.getElementById('vt-loading');
        const wrap    = document.getElementById('vt-polls-wrap');
        const empty   = document.getElementById('vt-empty');
 
        loading.style.display = 'flex';
        wrap.style.display    = 'none';
        empty.style.display   = 'none';
 
        const fd = new FormData();
        fd.append('action', 'get_polls');
 
        fetch('../pages/votes.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    loading.style.display = 'none';
 
                    if (data.success && data.polls.length > 0) {
                        wrap.innerHTML = '';
                        data.polls.forEach(p => wrap.appendChild(vtBuildPollCard(p)));
                        wrap.style.display = 'block';
                    } else {
                        empty.style.display = 'flex';
                    }
                } catch(e) { loading.style.display = 'none'; empty.style.display = 'flex'; }
            })
            .catch(() => { loading.style.display = 'none'; empty.style.display = 'flex'; });
    }
 
    // Build poll card
    function vtBuildPollCard(p) {
        const card     = document.createElement('div');
        card.className = 'vt-poll-card';
 
        const endDate  = new Date(p.end_date);
        const today    = new Date();
        const daysLeft = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24));
        const endFmt   = endDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        const urgency  = daysLeft <= 2 ? 'vt-days--urgent' : daysLeft <= 5 ? 'vt-days--warning' : '';
 
        card.innerHTML = `
            <div class="vt-poll-card-inner">
                <div class="vt-poll-card-left">
                    <div class="vt-poll-card-top">
                        ${p.has_voted == 1
                            ? '<span class="vt-voted-badge"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg> Voted</span>'
                            : '<span class="vt-open-badge">Open for voting</span>'
                        }
                        <span class="vt-days-left ${urgency}">
                            ${daysLeft <= 0 ? 'Closes today' : `${daysLeft} day${daysLeft !== 1 ? 's' : ''} left`}
                        </span>
                    </div>
                    <h3 class="vt-poll-card-title">${vtEsc(p.title)}</h3>
                    <p class="vt-poll-card-desc">${vtEsc(p.description || '')}</p>
                    <div class="vt-poll-card-meta">
                        <span>Closes ${endFmt}</span>
                        <span>${p.total_votes} vote${p.total_votes != 1 ? 's' : ''} cast</span>
                    </div>
                </div>
                <div class="vt-poll-card-right">
                    <button class="vt-vote-btn ${p.has_voted == 1 ? 'vt-vote-btn--voted' : ''}"
                            onclick="vtOpenVoteModal(${p.poll_id}, '${vtEsc(p.title)}', '${vtEsc(p.description || '')}', '${endFmt}')">
                        ${p.has_voted == 1
                            ? '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg> View My Vote'
                            : '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg> Vote Now'
                        }
                    </button>
                </div>
            </div>
        `;
        return card;
    }
 
    // Open vote modal
    function vtOpenVoteModal(poll_id, title, desc, deadline) {
        vtCurrentPollId  = poll_id;
        vtSelectedOption = null;
        vtHasVoted       = false;
        vtMyOptionId     = null;
        vtCurrentOptions = [];
 
        document.getElementById('vt-modal-title').textContent   = title;
        document.getElementById('vt-modal-desc').textContent    = desc;
        document.getElementById('vt-modal-deadline').textContent = 'Closes ' + deadline;
        document.getElementById('vt-modal-alert').style.display  = 'none';
        document.getElementById('vt-voted-banner').style.display = 'none';
        document.getElementById('vt-cast-btn').style.display     = 'none';
        document.getElementById('vt-options-grid').innerHTML     = '<div class="vt-options-loading"><div class="vt-spinner vt-spinner--sm"></div> Loading options…</div>';
        document.getElementById('vt-vote-modal').style.display   = 'flex';
 
        const fd = new FormData();
        fd.append('action',  'get_poll_options');
        fd.append('poll_id', poll_id);
 
        fetch('../pages/votes.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        vtHasVoted      = data.has_voted;
                        vtMyOptionId    = data.my_option_id;
                        vtCurrentOptions = data.options;
 
                        if (vtHasVoted) {
                            document.getElementById('vt-voted-banner').style.display = 'flex';
                        } else {
                            document.getElementById('vt-cast-btn').style.display = 'inline-flex';
                        }
 
                        vtRenderOptions(data.options, data.has_voted, data.my_option_id);
                    } else {
                        document.getElementById('vt-options-grid').innerHTML = `<p class="vt-options-error">${vtEsc(data.message)}</p>`;
                    }
                } catch(e) {
                    document.getElementById('vt-options-grid').innerHTML = '<p class="vt-options-error">Failed to load options.</p>';
                }
            });
    }
 
    // Render options
    function vtRenderOptions(options, hasVoted, myOptionId) {
        const grid = document.getElementById('vt-options-grid');
        grid.innerHTML = '';
 
        const totalVotes = options.reduce((sum, o) => sum + parseInt(o.vote_count), 0);
 
        options.forEach(o => {
            const card      = document.createElement('div');
            const isMyVote  = hasVoted && o.option_id == myOptionId;
            const pct       = totalVotes > 0 ? Math.round((o.vote_count / totalVotes) * 100) : 0;
            const imgSrc    = o.primary_image ? `../uploads/listings_images/${vtEsc(o.primary_image)}` : null;
            const price     = parseFloat(o.price);
            const priceTier = price <= 20 ? 'Affordable' : price <= 50 ? 'Moderate' : 'Premium';
 
            card.className = 'vt-option-card' +
                (isMyVote ? ' vt-option-card--my-vote' : '') +
                (hasVoted ? ' vt-option-card--voted' : ' vt-option-card--selectable');
 
            card.dataset.optionId = o.option_id;
 
            if (!hasVoted) {
                card.onclick = () => vtSelectOption(o.option_id);
            }
 
            card.innerHTML = `
                <div class="vt-option-img-wrap">
                    ${imgSrc
                        ? `<img src="${imgSrc}" alt="${vtEsc(o.title)}" class="vt-option-img" onerror="this.style.display='none'">`
                        : '<div class="vt-option-img-placeholder"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg></div>'
                    }
                    ${isMyVote ? '<span class="vt-my-vote-badge"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg> My Vote</span>' : ''}
                    ${!hasVoted ? '<div class="vt-option-select-indicator"><div class="vt-option-radio"></div></div>' : ''}
                </div>
                <div class="vt-option-body">
                    <p class="vt-option-business">${vtEsc(o.business_name)}</p>
                    <h4 class="vt-option-title">${vtEsc(o.title)}</h4>
                    <p class="vt-option-meta">${vtEsc(o.category_name)} · ${vtEsc(o.subcategory_name)} · £${price.toFixed(2)}</p>
                    ${hasVoted ? `
                    <div class="vt-option-results">
                        <div class="vt-result-bar-wrap">
                            <div class="vt-result-bar ${isMyVote ? 'vt-result-bar--my-vote' : ''}" style="width: ${pct}%"></div>
                        </div>
                        <div class="vt-result-stats">
                            <span>${o.vote_count} vote${o.vote_count != 1 ? 's' : ''}</span>
                            <span class="vt-result-pct">${pct}%</span>
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
 
            grid.appendChild(card);
        });
    }
 
    // Select option
    function vtSelectOption(option_id) {
        vtSelectedOption = option_id;
 
        document.querySelectorAll('.vt-option-card').forEach(card => {
            const isSelected = parseInt(card.dataset.optionId) === option_id;
            card.classList.toggle('vt-option-card--selected', isSelected);
            const radio = card.querySelector('.vt-option-radio');
            if (radio) radio.classList.toggle('vt-option-radio--active', isSelected);
        });
    }
 
    // Cast vote
    function vtCastVote() {
        const alertEl = document.getElementById('vt-modal-alert');
        const btn     = document.getElementById('vt-cast-btn');
 
        if (!vtSelectedOption) {
            alertEl.textContent   = 'Please select a listing to vote for.';
            alertEl.className     = 'vt-alert vt-alert--error';
            alertEl.style.display = 'block';
            return;
        }
 
        btn.disabled    = true;
        btn.textContent = 'Submitting…';
        alertEl.style.display = 'none';
 
        const fd = new FormData();
        fd.append('action',    'cast_vote');
        fd.append('poll_id',   vtCurrentPollId);
        fd.append('option_id', vtSelectedOption);
 
        fetch('../pages/votes.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        vtCloseVoteModal();
                        vtShowToast(data.message, 'success');
                        vtLoadPolls();
                    } else {
                        alertEl.textContent   = data.message || 'Failed to cast vote.';
                        alertEl.className     = 'vt-alert vt-alert--error';
                        alertEl.style.display = 'block';
                        btn.disabled          = false;
                        btn.innerHTML         = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg> Cast My Vote`;
                    }
                } catch(e) {
                    alertEl.textContent   = 'Unexpected error.';
                    alertEl.className     = 'vt-alert vt-alert--error';
                    alertEl.style.display = 'block';
                    btn.disabled          = false;
                    btn.textContent       = 'Cast My Vote';
                }
            });
    }
 
    function vtCloseVoteModal() {
        document.getElementById('vt-vote-modal').style.display = 'none';
        vtCurrentPollId  = null;
        vtSelectedOption = null;
    }
 
    // Toast
    function vtShowToast(message, type) {
        const existing = document.getElementById('vt-toast');
        if (existing) existing.remove();
        const toast = document.createElement('div');
        toast.id = 'vt-toast'; toast.className = `vt-toast vt-toast--${type}`; toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('vt-toast--show'), 10);
        setTimeout(() => { toast.classList.remove('vt-toast--show'); setTimeout(() => toast.remove(), 300); }, 3000);
    }
 
    function vtEsc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
</script>