<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db_connection.php';

$allowedRoles = ['Council Administrator', 'Council Member'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: ../pages/dashboard.php?page=home");
    exit();
}

// Handle AJAX status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean(); //prevent output corruption
    header('Content-Type: application/json');
        
    if ($_POST['action'] === 'update_status') {
        $user_id = (int)$_POST['user_id'];
        $type    = $_POST['type'];
        $status  = $_POST['status'];
        $comment = mysqli_real_escape_string($conn, trim($_POST['reject_comment'] ?? ''));

        $valid_statuses = ['pending', 'approved', 'rejected'];
        if (!in_array($status, $valid_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status.']);
            exit();
        }

        // Always update users.account_status for login access
        mysqli_query($conn, "UPDATE users SET account_status = '$status' WHERE user_id = '$user_id'");

        // For SME also update sme_profiles.approval_status
        if ($type === 'sme') {
            mysqli_query($conn, "UPDATE sme_profiles SET approval_status = '$status' WHERE user_id = '$user_id'");
        }

        // Log decision - only if approved or rejected AND document exists
        if ($status === 'approved' || $status === 'rejected') {
          $admin_id   = $_SESSION['user_id'];
          $doc_result = mysqli_query($conn, "SELECT document_id FROM user_documents WHERE user_id = '$user_id' LIMIT 1");
          $doc_row    = mysqli_fetch_assoc($doc_result);
          $doc_id     = $doc_row['document_id'] ?? null;

         if ($doc_id) {
          mysqli_query($conn, "INSERT INTO user_registration_requests 
            (user_id, admin_id, decision, comments, document_id)
            VALUES ('$user_id', '$admin_id', '$status', '$comment', '$doc_id')");
    }
}

       // Return success regardless of whether log was created
        echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
        exit();
    }
}

// Status filter
$statusFilter  = $_GET['status'] ?? 'pending';
$validStatuses = ['pending', 'approved', 'rejected'];
if (!in_array($statusFilter, $validStatuses)) $statusFilter = 'pending';

// Pagination setup
$users_per_page = 8;
$user_page      = max(1, intval($_GET['user_page'] ?? 1));

// Fetch SME profiles
$smeQuery = $conn->prepare("
    SELECT 
        s.sme_id, u.user_id,
        s.business_name AS name,
        s.created_at AS date_submitted,
        s.approval_status,
        s.description AS business_description,
        s.phone, 'sme' AS type,
        u.email_address AS email,
        u.account_status
    FROM sme_profiles s
    JOIN users u ON s.user_id = u.user_id
    WHERE u.account_status = ?
    ORDER BY s.created_at DESC
");
$smeQuery->bind_param("s", $statusFilter);
$smeQuery->execute();
$smes = $smeQuery->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Resident/Council profiles
$resQuery = $conn->prepare("
    SELECT 
        r.profile_id, u.user_id,
        CONCAT(r.first_name, ' ', r.last_name) AS name,
        r.created_at AS date_submitted,
        u.account_status AS approval_status,
        r.date_of_birth AS dob,
        r.gender, r.address,
        r.postcode AS post_code,
        r.phone,
        u.email_address AS email,
        u.role,
        CASE u.role
            WHEN 'Resident'              THEN 'resident'
            WHEN 'Council Member'        THEN 'council_member'
            WHEN 'Council Administrator' THEN 'council_admin'
            ELSE 'resident'
        END AS type
    FROM resident_profiles r
    JOIN users u ON r.user_id = u.user_id
    WHERE u.account_status = ? AND u.role != 'SME'
    ORDER BY r.created_at DESC
");
$resQuery->bind_param("s", $statusFilter);
$resQuery->execute();
$residents = $resQuery->get_result()->fetch_all(MYSQLI_ASSOC);

// Merge and paginate
$all_users   = array_merge($smes, $residents);
$total_users = count($all_users);
$total_pages = max(1, ceil($total_users / $users_per_page));
$user_page   = min($user_page, $total_pages);
$offset      = ($user_page - 1) * $users_per_page;
$users       = array_slice($all_users, $offset, $users_per_page);
?>

<div class="manage-users-page">
    <?php
       $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
        </svg>';
       $title = "Manage Users";
       $subtitle = "Here you can View, Approve or Reject Registration Requests.";
       include '../db_connection.php';
       include '../components/section_header.php';
     ?> 

    <!-- Action Message -->
    <div id="users-action-message" class="alert-box" style="display:none;"></div>

    <!-- Status Tabs -->
    <div class="status-tabs">
        <button class="tab <?= $statusFilter === 'pending' ? 'active' : '' ?>"
                onclick="window.location.href='?page=manage-users&status=pending'">
            Requests
        </button>
        <button class="tab <?= $statusFilter === 'approved' ? 'active' : '' ?>"
                onclick="window.location.href='?page=manage-users&status=approved'">
            Approved
        </button>
        <button class="tab <?= $statusFilter === 'rejected' ? 'active' : '' ?>"
                onclick="window.location.href='?page=manage-users&status=rejected'">
            Rejected
        </button>
    </div>

    <!-- Search -->
    <div class="search-container">
        <input type="text" id="users-search-input"
               placeholder="Search by name, email or type"
               onkeyup="filterUsersTable()">
    </div>

    <!-- Users Table -->
    <div class="users-table-wrapper">
    <div class="table-scroll">
        <table class="users-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Email</th>
                    <th>Date Submitted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="users-table-body">
                <?php if (empty($users)) : ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:20px; color:rgba(0,0,0,0.5);">
                            No <?= $statusFilter ?> users found.
                        </td>
                    </tr>
                <?php else : ?>
                    <?php $count = $offset + 1; foreach ($users as $user) : ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td>
                                <?php
                                $typeLabels = [
                                    'resident'       => 'Resident',
                                    'sme'            => 'SME',
                                    'council_member' => 'Council Member',
                                    'council_admin'  => 'Council Admin'
                                ];
                                ?>
                                <span class="users-type-badge users-type-<?= $user['type'] ?>">
                                    <?= $typeLabels[$user['type']] ?? ucfirst($user['type']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($user['date_submitted']))) ?></td>
                            <td>
                                <span class="status <?= $user['approval_status'] ?>">
                                    <?= ucfirst($user['approval_status']) ?>
                                </span>
                            </td>
                            <td>
                                <button class="view-btn" onclick="openUsersViewModal(<?= htmlspecialchars(json_encode($user)) ?>)">
                                    View
                                </button>
                                <button class="edit-btn" onclick="openUsersStatusModal(
                                    '<?= $user['user_id'] ?>',
                                    '<?= $user['type'] ?>',
                                    '<?= $user['approval_status'] ?>',
                                    '<?= addslashes($user['name']) ?>'
                                )">
                                    Status
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1) : ?>
    <div class="users-pagination">
        <span class="users-page-info">
            Showing <?= $offset + 1 ?>–<?= min($offset + $users_per_page, $total_users) ?> of <?= $total_users ?> users
        </span>
        <div class="users-page-btns">
            <?php if ($user_page > 1) : ?>
            <a href="dashboard.php?page=manage-users&status=<?= $statusFilter ?>&user_page=<?= $user_page - 1 ?>"
               class="users-page-btn">&laquo; Prev</a>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $total_pages; $p++) : ?>
            <a href="dashboard.php?page=manage-users&status=<?= $statusFilter ?>&user_page=<?= $p ?>"
               class="users-page-btn <?= $p === $user_page ? 'users-page-btn--active' : '' ?>">
                <?= $p ?>
            </a>
            <?php endfor; ?>

            <?php if ($user_page < $total_pages) : ?>
            <a href="dashboard.php?page=manage-users&status=<?= $statusFilter ?>&user_page=<?= $user_page + 1 ?>"
               class="users-page-btn">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

</div>

   <!-- View Modal -->
  <div id="users-view-modal" class="users-modal-overlay" style="display:none;">
    <div class="users-modal-box">
        <div class="users-modal-header">
            <h3>User Details</h3>
            <span class="users-modal-close-btn" onclick="closeUsersModal('users-view-modal')">&times;</span>
        </div>
        <div class="users-modal-body" id="users-view-modal-body"></div>
        <div class="users-modal-footer">
            <button class="users-modal-cancel-btn" onclick="closeUsersModal('users-view-modal')">Close</button>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div id="users-status-modal" class="users-modal-overlay" style="display:none;">
    <div class="users-modal-box">
        <div class="users-modal-header">
            <h3>Update Status</h3>
            <span class="users-modal-close-btn" onclick="closeUsersModal('users-status-modal')">&times;</span>
        </div>
        <div id="users-status-error" class="alert-box error-box" style="display:none;"></div>
        <input type="hidden" id="users-status-user-id">
        <input type="hidden" id="users-status-type">
        <div class="users-modal-body">
            <p id="users-status-name" style="font-weight:600; color:#230c33; margin-bottom:15px;"></p>
            <div class="users-modal-field">
                <label>Status</label>
                <select id="users-status-select" onchange="toggleUsersRejectComment()">
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="users-modal-field" id="users-reject-comment-box" style="display:none;">
                <label>Rejection Comment <small>(optional)</small></label>
                <textarea id="users-reject-comment" placeholder="Enter reason for rejection"></textarea>
            </div>
        </div>
        <div class="users-modal-footer">
            <button class="users-modal-cancel-btn" onclick="closeUsersModal('users-status-modal')">Cancel</button>
            <button class="users-modal-submit-btn" onclick="submitUsersStatus()">Save</button>
        </div>
    </div>
</div>

<script>
 function openUsersModal(id) {
    document.getElementById(id).style.display = 'flex';
}

function closeUsersModal(id) {
    document.getElementById(id).style.display = 'none';
}

function openUsersViewModal(user) {
    const body = document.getElementById('users-view-modal-body');
    let html   = '<table class="users-detail-table">';

    if (user.type === 'sme') {
        html += `
            <tr><td><strong>Business Name</strong></td><td>${user.name}</td></tr>
            <tr><td><strong>Email</strong></td><td>${user.email}</td></tr>
            <tr><td><strong>Phone</strong></td><td>${user.phone || 'N/A'}</td></tr>
            <tr><td><strong>Description</strong></td><td>${user.business_description || 'N/A'}</td></tr>
            <tr><td><strong>Date Submitted</strong></td><td>${user.date_submitted}</td></tr>
            <tr><td><strong>Status</strong></td><td>${user.approval_status}</td></tr>
        `;
    } else {
        html += `
            <tr><td><strong>Full Name</strong></td><td>${user.name}</td></tr>
            <tr><td><strong>Email</strong></td><td>${user.email}</td></tr>
            <tr><td><strong>Phone</strong></td><td>${user.phone || 'N/A'}</td></tr>
            <tr><td><strong>Date of Birth</strong></td><td>${user.dob || 'N/A'}</td></tr>
            <tr><td><strong>Gender</strong></td><td>${user.gender || 'N/A'}</td></tr>
            <tr><td><strong>Address</strong></td><td>${user.address || 'N/A'}</td></tr>
            <tr><td><strong>Postcode</strong></td><td>${user.post_code || 'N/A'}</td></tr>
            <tr><td><strong>Date Submitted</strong></td><td>${user.date_submitted}</td></tr>
            <tr><td><strong>Status</strong></td><td>${user.approval_status}</td></tr>
        `;
    }

    html += '</table>';
    body.innerHTML = html;
    openUsersModal('users-view-modal');
}

function openUsersStatusModal(userId, type, currentStatus, name) {
    document.getElementById('users-status-user-id').value  = userId;
    document.getElementById('users-status-type').value     = type;
    document.getElementById('users-status-select').value   = currentStatus;
    document.getElementById('users-status-name').innerText = name;
    document.getElementById('users-reject-comment').value  = '';
    document.getElementById('users-status-error').style.display = 'none';
    toggleUsersRejectComment();
    openUsersModal('users-status-modal');
}

function toggleUsersRejectComment() {
    const status = document.getElementById('users-status-select').value;
    document.getElementById('users-reject-comment-box').style.display =
        (status === 'rejected') ? 'block' : 'none';
}

function submitUsersStatus() {
    const userId   = document.getElementById('users-status-user-id').value;
    const type     = document.getElementById('users-status-type').value;
    const status   = document.getElementById('users-status-select').value;
    const comment  = document.getElementById('users-reject-comment').value.trim();
    const errorBox = document.getElementById('users-status-error');

    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('user_id', userId);
    formData.append('type', type);
    formData.append('status', status);
    formData.append('reject_comment', comment);

    fetch('../pages/manage-users.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                closeUsersModal('users-status-modal');
                const msg = document.getElementById('users-action-message');
                msg.className     = 'alert-box success-box';
                msg.innerText     = data.message;
                msg.style.display = 'block';
                setTimeout(() => {const url = new URL(window.location.href);
                                  const status   = url.searchParams.get('status')    || 'pending';
                                  const userPage = url.searchParams.get('user_page') || 1;
                                window.location.href = `dashboard.php?page=manage-users&status=${status}&user_page=${userPage}`;}, 1500);
            } else {
                errorBox.style.display = 'block';
                errorBox.innerText     = data.message;
            }
        } catch(e) {
            errorBox.style.display = 'block';
            errorBox.innerText     = 'Something went wrong. Please try again.';
        }
    })
    .catch(() => {
        errorBox.style.display = 'block';
        errorBox.innerText     = 'Something went wrong. Please try again.';
    });
}

function filterUsersTable() {
    const input = document.getElementById('users-search-input').value.toLowerCase();
    document.querySelectorAll('#users-table-body tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(input) ? '' : 'none';
    });
}

window.addEventListener('click', (e) => {
    ['users-view-modal', 'users-status-modal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal && e.target === modal) closeUsersModal(id);
    });
});

</script>