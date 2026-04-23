<?php

// AJAX HANDLER 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    include '../db_connection.php';
    ob_clean();
    header('Content-Type: application/json');
 
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit();
    }
 
    $role = $_SESSION['user_role'] ?? '';
 
    if ($_POST['action'] === 'update_contact') {
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
 
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email address is required.']);
            exit();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit();
        }
 
        $check = $conn->prepare("SELECT user_id FROM users WHERE email_address = ? AND user_id != ?");
        $check->bind_param("si", $email, $user_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'That email address is already in use by another account.']);
            $check->close();
            exit();
        }
        $check->close();
 
        $stmt = $conn->prepare("UPDATE users SET email_address = ? WHERE user_id = ?");
        $stmt->bind_param("si", $email, $user_id);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Failed to update email. Please try again.']);
            $stmt->close();
            exit();
        }
        $stmt->close();
 
        if ($role === 'SME') {
            $stmt2 = $conn->prepare("UPDATE sme_profiles SET phone = ? WHERE user_id = ?");
        } else {
            $stmt2 = $conn->prepare("UPDATE resident_profiles SET phone = ? WHERE user_id = ?");
        }
        $stmt2->bind_param("si", $phone, $user_id);
        if (!$stmt2->execute()) {
            echo json_encode(['success' => false, 'message' => 'Email updated but phone failed. Please try again.']);
            $stmt2->close();
            exit();
        }
        $stmt2->close();
 
        $_SESSION['user_email'] = $email;
        echo json_encode(['success' => true, 'message' => 'Contact details updated successfully.']);
        exit();
    }
 
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit();
}
 

// PAGE GUARD

$allowedRoles = ['Resident', 'SME', 'Council Member', 'Council Administrator'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: ../pages/dashboard.php?page=home");
    exit();
}
 
$user_id = $_SESSION['user_id'];
$role    = $_SESSION['user_role'];
 
// ── Fetch base user data 
$prf_user_stmt = $conn->prepare("SELECT email_address, role, account_status, last_login FROM users WHERE user_id = ? LIMIT 1");
$prf_user_stmt->bind_param("i", $user_id);
$prf_user_stmt->execute();
$prf_user = $prf_user_stmt->get_result()->fetch_assoc();
$prf_user_stmt->close();
 
// ── Fetch role-specific profile data
$prf_profile   = [];
$prf_area_name = 'N/A';
 
if ($role === 'SME') {
    $prf_p_stmt = $conn->prepare("
        SELECT sp.business_name, sp.approval_status, sp.description,
               sp.phone, sp.created_at,
               a.area_name,
               psc.subcategory_name,
               pc.category_name
        FROM sme_profiles sp
        LEFT JOIN areas a ON sp.area_id = a.area_id
        LEFT JOIN product_service_subcategories psc  ON sp.subcategory_id = psc.subcategory_id
        LEFT JOIN product_service_categories pc ON psc.category_id   = pc.category_id
        WHERE sp.user_id = ?
        LIMIT 1
    ");
    $prf_p_stmt->bind_param("i", $user_id);
    $prf_p_stmt->execute();
    $prf_profile   = $prf_p_stmt->get_result()->fetch_assoc() ?? [];
    $prf_p_stmt->close();
    $prf_area_name = $prf_profile['area_name'] ?? 'N/A';
 
} else {
    $prf_p_stmt = $conn->prepare("
        SELECT rp.first_name, rp.last_name, rp.date_of_birth, rp.gender,
               rp.address, rp.phone, rp.postcode, rp.created_at,
               a.area_name
        FROM resident_profiles rp
        LEFT JOIN areas a ON rp.area_id = a.area_id
        WHERE rp.user_id = ?
        LIMIT 1
    ");
    $prf_p_stmt->bind_param("i", $user_id);
    $prf_p_stmt->execute();
    $prf_profile   = $prf_p_stmt->get_result()->fetch_assoc() ?? [];
    $prf_p_stmt->close();
    $prf_area_name = $prf_profile['area_name'] ?? 'N/A';
}
 function prf_safe(string $val): string {
    return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
}
 
$prf_status_map = [
    'approved' => ['label' => 'Approved', 'class' => 'prf-badge--approved'],
    'pending'  => ['label' => 'Pending',  'class' => 'prf-badge--pending'],
    'rejected' => ['label' => 'Rejected', 'class' => 'prf-badge--rejected'],
];
$prf_account_status = $prf_user['account_status'] ?? 'pending';
$prf_status_info    = $prf_status_map[$prf_account_status] ?? ['label' => ucfirst($prf_account_status), 'class' => 'prf-badge--pending'];
?>

<!-- PROFILE PAGE -->
<div class="prf-wrapper">
 
    <?php
        $icon     = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>';
        $title    = 'My Profile';
        $subtitle = 'Your account information at a glance.';
        include '../components/section_header.php';
    ?>
 
    <div class="prf-cards-wrapper">
 
        <!-- Card 1: Personal / Business Information -->
        <div class="prf-card">
            <h3 class="prf-card-title">
                <?= $role === 'SME' ? 'Business Information' : 'Personal Information' ?>
            </h3>
 
            <?php if ($role === 'SME') : ?>
                <div class="prf-row">
                    <span class="prf-label">Business Name</span>
                    <span class="prf-value"><?= prf_safe($prf_profile['business_name'] ?? 'N/A') ?></span>
                </div>
                <div class="prf-row">
                    <span class="prf-label">Category</span>
                    <span class="prf-value"><?= prf_safe($prf_profile['category_name'] ?? 'N/A') ?></span>
                </div>
                <div class="prf-row">
                    <span class="prf-label">Subcategory</span>
                    <span class="prf-value"><?= prf_safe($prf_profile['subcategory_name'] ?? 'N/A') ?></span>
                </div>
                <div class="prf-row">
                    <span class="prf-label">Description</span>
                    <span class="prf-value prf-value--wrap"><?= prf_safe($prf_profile['description'] ?? 'N/A') ?></span>
                </div>
            <?php else : ?>
                <div class="prf-row">
                    <span class="prf-label">First Name</span>
                    <span class="prf-value"><?= prf_safe($prf_profile['first_name'] ?? 'N/A') ?></span>
                </div>
                <div class="prf-row">
                    <span class="prf-label">Last Name</span>
                    <span class="prf-value"><?= prf_safe($prf_profile['last_name'] ?? 'N/A') ?></span>
                </div>
                <div class="prf-row">
                    <span class="prf-label">Date of Birth</span>
                    <span class="prf-value">
                        <?= !empty($prf_profile['date_of_birth']) ? date('d M Y', strtotime($prf_profile['date_of_birth'])) : 'N/A' ?>
                    </span>
                </div>
                <div class="prf-row">
                    <span class="prf-label">Gender</span>
                    <span class="prf-value"><?= prf_safe($prf_profile['gender'] ?? 'N/A') ?></span>
                </div>
            <?php endif; ?>
 
            <div class="prf-row">
                <span class="prf-label">Role</span>
                <span class="prf-value"><?= prf_safe($role) ?></span>
            </div>
        </div>
 
        <!-- Card 2: Contact Details -->
        <div class="prf-card">
            <div class="prf-card-title-row">
                <h3 class="prf-card-title">Contact Details</h3>
                <button class="prf-edit-btn" onclick="prfOpenModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="15" height="15">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    Edit
                </button>
            </div>
            <div class="prf-row">
                <span class="prf-label">Email Address</span>
                <span class="prf-value" id="prf-display-email"><?= prf_safe($prf_user['email_address'] ?? 'N/A') ?></span>
            </div>
            <div class="prf-row">
                <span class="prf-label">Phone Number</span>
                <span class="prf-value" id="prf-display-phone"><?= prf_safe($prf_profile['phone'] ?? 'N/A') ?></span>
            </div>
        </div>
 
        <!-- Card 3: Location -->
        <div class="prf-card">
            <h3 class="prf-card-title">Location</h3>
            <?php if ($role !== 'SME') : ?>
                <div class="prf-row">
                    <span class="prf-label">Address</span>
                    <span class="prf-value"><?= prf_safe($prf_profile['address'] ?? 'N/A') ?></span>
                </div>
                <div class="prf-row">
                    <span class="prf-label">Postcode</span>
                    <span class="prf-value"><?= prf_safe($prf_profile['postcode'] ?? 'N/A') ?></span>
                </div>
            <?php endif; ?>
            <div class="prf-row">
                <span class="prf-label">Area</span>
                <span class="prf-value"><?= prf_safe($prf_area_name) ?></span>
            </div>
        </div>
 
        <!-- Card 4: Account Details -->
        <div class="prf-card">
            <h3 class="prf-card-title">Account Details</h3>
            <div class="prf-row">
                <span class="prf-label">Account Status</span>
                <span class="prf-value">
                    <span class="prf-badge <?= $prf_status_info['class'] ?>"><?= $prf_status_info['label'] ?></span>
                </span>
            </div>
            <?php if ($role === 'SME') : ?>
                <?php
                    $prf_sme_status      = $prf_profile['approval_status'] ?? 'pending';
                    $prf_sme_status_info = $prf_status_map[$prf_sme_status] ?? ['label' => ucfirst($prf_sme_status), 'class' => 'prf-badge--pending'];
                ?>
                <div class="prf-row">
                    <span class="prf-label">Business Status</span>
                    <span class="prf-value">
                        <span class="prf-badge <?= $prf_sme_status_info['class'] ?>"><?= $prf_sme_status_info['label'] ?></span>
                    </span>
                </div>
            <?php endif; ?>
            <div class="prf-row">
                <span class="prf-label">Last Login</span>
                <span class="prf-value">
                    <?= !empty($prf_user['last_login']) ? date('d M Y, H:i', strtotime($prf_user['last_login'])) : 'N/A' ?>
                </span>
            </div>
            <div class="prf-row">
                <span class="prf-label">Member Since</span>
                <span class="prf-value">
                    <?= !empty($prf_profile['created_at']) ? date('d M Y', strtotime($prf_profile['created_at'])) : 'N/A' ?>
                </span>
            </div>
            <div class="prf-row">
                <span class="prf-label">Settings</span>
                <span class="prf-value">
                    <a href="?page=settings" class="prf-settings-link">Manage Settings →</a>
                </span>
            </div>
        </div>
 
    </div>
</div>
 
<!--EDIT CONTACT  -->
<div id="prf-modal-overlay" class="prf-modal-overlay" style="display:none;" onclick="prfCloseOnOverlay(event)">
    <div class="prf-modal-box">
        <div class="prf-modal-header">
            <h3>Edit Contact Details</h3>
            <button class="prf-modal-close" onclick="prfCloseModal()" aria-label="Close">&times;</button>
        </div>
        <div class="prf-modal-body">
            <div id="prf-alert" class="prf-alert" style="display:none;"></div>
            <div class="prf-form-group">
                <label for="prf-input-email">Email Address <span class="prf-required">*</span></label>
                <input type="email" id="prf-input-email" class="prf-input"
                       value="<?= prf_safe($prf_user['email_address'] ?? '') ?>"
                       placeholder="Enter email address">
            </div>
            <div class="prf-form-group">
                <label for="prf-input-phone">Phone Number</label>
                <input type="tel" id="prf-input-phone" class="prf-input"
                       value="<?= prf_safe($prf_profile['phone'] ?? '') ?>"
                       placeholder="e.g. +44 7700 900123">
            </div>
        </div>
        <div class="prf-modal-footer">
            <button class="prf-btn-secondary" onclick="prfCloseModal()">Cancel</button>
            <button class="prf-btn-primary" id="prf-save-btn" onclick="prfSaveContact()">Save Changes</button>
        </div>
    </div>
</div>
 
 
<!-- JAVASCRIPT -->
<script>
    function prfOpenModal() {
        document.getElementById('prf-modal-overlay').style.display = 'flex';
        document.getElementById('prf-alert').style.display = 'none';
    }
 
    function prfCloseModal() {
        document.getElementById('prf-modal-overlay').style.display = 'none';
    }
 
    function prfCloseOnOverlay(e) {
        if (e.target === document.getElementById('prf-modal-overlay')) prfCloseModal();
    }
 
    function prfShowAlert(message, type) {
        const el = document.getElementById('prf-alert');
        el.textContent = message;
        el.className   = 'prf-alert prf-alert--' + type;
        el.style.display = 'block';
    }
 
    function prfSaveContact() {
        const email   = document.getElementById('prf-input-email').value.trim();
        const phone   = document.getElementById('prf-input-phone').value.trim();
        const saveBtn = document.getElementById('prf-save-btn');
 
        if (!email) { prfShowAlert('Please enter an email address.', 'error'); return; }
 
        saveBtn.disabled    = true;
        saveBtn.textContent = 'Saving…';
 
        const formData = new FormData();
        formData.append('action', 'update_contact');
        formData.append('email',  email);
        formData.append('phone',  phone);
 
        fetch('../pages/profile.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        document.getElementById('prf-display-email').textContent = email;
                        document.getElementById('prf-display-phone').textContent = phone || 'N/A';
                        prfCloseModal();
                        prfShowToast(data.message, 'success');
                    } else {
                        prfShowAlert(data.message || 'Update failed. Please try again.', 'error');
                    }
                } catch (e) {
                    prfShowAlert('Unexpected server response. Please try again.', 'error');
                }
            })
            .catch(() => prfShowAlert('Network error. Please check your connection.', 'error'))
            .finally(() => {
                saveBtn.disabled    = false;
                saveBtn.textContent = 'Save Changes';
            });
    }
 
    function prfShowToast(message, type) {
        const existing = document.getElementById('prf-toast');
        if (existing) existing.remove();
 
        const toast = document.createElement('div');
        toast.id          = 'prf-toast';
        toast.className   = 'prf-toast prf-toast--' + type;
        toast.textContent = message;
        document.body.appendChild(toast);
 
        setTimeout(() => toast.classList.add('prf-toast--show'), 10);
        setTimeout(() => {
            toast.classList.remove('prf-toast--show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
</script>