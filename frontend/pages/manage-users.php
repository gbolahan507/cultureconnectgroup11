<?php
// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$allowedRoles = ['Council Administrator', 'Council_member'];
// Check if user is logged in AND has the correct role
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    // Redirect unauthorized users
    header("Location: ../pages/dashboard.php?page=home");
    exit();
}

// Handle AJAX status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'update_status') {
        $user_ref_no    = (int)$_POST['user_ref_no'];
        $type           = $_POST['type'];
        $status         = $_POST['status'];
        $reject_comment = mysqli_real_escape_string($conn, trim($_POST['reject_comment'] ?? ''));

        $valid_statuses = ['pending', 'approved', 'rejected'];
        if (!in_array($status, $valid_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status.']);
            exit();
        }

        if ($type === 'sme') {
            $sql = "UPDATE sme_profiles SET approval_status = '$status', reject_comment = '$reject_comment' WHERE user_ref_no = '$user_ref_no'";
        } else {
            $sql = "UPDATE resident_profiles SET approval_status = '$status', reject_comment = '$reject_comment' WHERE user_ref_no = '$user_ref_no'";
        }

        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        }
        exit();
    }
}

// Fetch users based on status filter
$statusFilter   = $_GET['status'] ?? 'pending';
$validStatuses  = ['pending', 'approved', 'rejected'];
if (!in_array($statusFilter, $validStatuses)) $statusFilter = 'pending';

// Fetch SME profiles
$smeQuery = $conn->prepare("
    SELECT 
        s.user_ref_no,
        s.business_name AS name,
        s.created_at AS date_submitted,
        s.approval_status,
        s.reject_comment,
        s.business_reg_no,
        s.business_description,
        s.address,
        s.post_code,
        s.phone,
        'sme' AS type,
        u.email
    FROM sme_profiles s
    JOIN users u ON s.user_ref_no = u.user_ref_no
    WHERE s.approval_status = ?
    ORDER BY s.created_at DESC
");
$smeQuery->bind_param("s", $statusFilter);
$smeQuery->execute();
$smes = $smeQuery->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Resident profiles
$resQuery = $conn->prepare("
    SELECT 
        r.user_ref_no,
        CONCAT(r.given_name, ' ', r.family_name) AS name,
        r.profile_created_at AS date_submitted,
        r.approval_status,
        r.reject_comment,
        r.dob,
        r.gender,
        r.address,
        r.post_code,
        r.phone,
        u.email,
        u.role_id,
        CASE u.role_id
            WHEN 1 THEN 'resident'
            WHEN 3 THEN 'council_member'
            WHEN 4 THEN 'council_admin'
            ELSE 'resident'
        END AS type
    FROM resident_profiles r
    JOIN users u ON r.user_ref_no = u.user_ref_no
    WHERE r.approval_status = ?
    ORDER BY r.profile_created_at DESC
");
$resQuery->bind_param("s", $statusFilter);
$resQuery->execute();
$residents = $resQuery->get_result()->fetch_all(MYSQLI_ASSOC);

// Merge both
$users = array_merge($smes, $residents);
?>

<div class="manage-users-page">
    <?php
       $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
        </svg>';
       $title = "Manage Users";
       $subtitle = "Here you can View, Approve or Reject Registration Requests.";
       include '../components/section_header.php';
     ?> 

    <!-- Action Message -->
    <div id="users-action-message" class="alert-box" style="display:none;"></div>

    <!-- Status Tabs -->
    <div class="status-tabs">
        <a href="?page=manage-users&status=pending">
            <button class="tab <?= $statusFilter === 'pending' ? 'active' : '' ?>">Requests</button>
        </a>
        <a href="?page=manage-users&status=approved">
            <button class="tab <?= $statusFilter === 'approved' ? 'active' : '' ?>">Approved</button>
        </a>
        <a href="?page=manage-users&status=rejected">
            <button class="tab <?= $statusFilter === 'rejected' ? 'active' : '' ?>">Rejected</button>
        </a>
    </div>

    <!-- Search -->
    <div class="search-container">
        <input type="text" id="users-search-input" 
               placeholder="Search by name, email or type" 
               onkeyup="filterUsersTable()">
    </div>

    <!-- Users Table -->
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
                    <?php $count = 1; foreach ($users as $user) : ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td>
                                <?php
                                     $typeLabels = [
                                             'resident'       => 'Resident',
                                             'sme'            => 'SME',
                                             'council_member' => 'Council Member',
                                             'council_admin'  => 'Council Admin' ];
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
                                    '<?= $user['user_ref_no'] ?>',
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

</div>

   <!-- View Modal -->
  <div id="users-view-modal" class="users-modal-overlay" style="display:none;">
    <div class="users-modal-box">
        <div class="users-modal-header">
            <h3>User Details</h3>
            <span class="users-modal-close-btn" onclick="closeUsersModal('users-view-modal')">&times;</span>
        </div>
        <div class="users-modal-body" id="users-view-modal-body">
        </div>
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
        <input type="hidden" id="users-status-ref-no">
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

// View modal
function openUsersViewModal(user) {
    const body = document.getElementById('users-view-modal-body');
    let html = '<table class="users-detail-table">';

    if (user.type === 'sme') {
        html += `
            <tr><td><strong>Business Name</strong></td><td>${user.name}</td></tr>
            <tr><td><strong>Email</strong></td><td>${user.email}</td></tr>
            <tr><td><strong>Phone</strong></td><td>${user.phone || 'N/A'}</td></tr>
            <tr><td><strong>Address</strong></td><td>${user.address || 'N/A'}</td></tr>
            <tr><td><strong>Postcode</strong></td><td>${user.post_code || 'N/A'}</td></tr>
            <tr><td><strong>Reg Number</strong></td><td>${user.business_reg_no || 'N/A'}</td></tr>
            <tr><td><strong>Description</strong></td><td>${user.business_description || 'N/A'}</td></tr>
            <tr><td><strong>Date Submitted</strong></td><td>${user.date_submitted}</td></tr>
            <tr><td><strong>Status</strong></td><td>${user.approval_status}</td></tr>
        `;
        if (user.reject_comment) {
            html += `<tr><td><strong>Rejection Comment</strong></td><td>${user.reject_comment}</td></tr>`;
        }
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
        if (user.reject_comment) {
            html += `<tr><td><strong>Rejection Comment</strong></td><td>${user.reject_comment}</td></tr>`;
        }
    }

    html += '</table>';
    body.innerHTML = html;
    openUsersModal('users-view-modal');
}

// Status modal
function openUsersStatusModal(refNo, type, currentStatus, name) {
    document.getElementById('users-status-ref-no').value = refNo;
    document.getElementById('users-status-type').value = type;
    document.getElementById('users-status-select').value = currentStatus;
    document.getElementById('users-status-name').innerText = name;
    document.getElementById('users-reject-comment').value = '';
    document.getElementById('users-status-error').style.display = 'none';
    toggleUsersRejectComment();
    openUsersModal('users-status-modal');
}

// Show/hide reject comment
function toggleUsersRejectComment() {
    const status  = document.getElementById('users-status-select').value;
    const box     = document.getElementById('users-reject-comment-box');
    box.style.display = (status === 'rejected') ? 'block' : 'none';
}

// Submit status update
function submitUsersStatus() {
    const refNo   = document.getElementById('users-status-ref-no').value;
    const type    = document.getElementById('users-status-type').value;
    const status  = document.getElementById('users-status-select').value;
    const comment = document.getElementById('users-reject-comment').value.trim();
    const errorBox = document.getElementById('users-status-error');

    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('user_ref_no', refNo);
    formData.append('type', type);
    formData.append('status', status);
    formData.append('reject_comment', comment);

    fetch('', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeUsersModal('users-status-modal');
                const msg = document.getElementById('users-action-message');
                msg.className = 'alert-box success-box';
                msg.innerText = data.message;
                msg.style.display = 'block';
                setTimeout(() => location.reload(), 1500);
            } else {
                errorBox.style.display = 'block';
                errorBox.innerText = data.message;
            }
        })
        .catch(() => {
            errorBox.style.display = 'block';
            errorBox.innerText = 'Something went wrong. Please try again.';
        });
}

// Search filter
function filterUsersTable() {
    const input = document.getElementById('users-search-input').value.toLowerCase();
    const rows  = document.querySelectorAll('#users-table-body tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(input) ? '' : 'none';
    });
}

// Close modal on outside click
window.addEventListener('click', (e) => {
    ['users-view-modal', 'users-status-modal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal && e.target === modal) closeUsersModal(id);
    });
});

</script>