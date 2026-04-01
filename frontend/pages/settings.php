<?php
// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$allowedRoles = ['Council Administrator', 'Council_member', 'Resident', 'SME'];

// Check if user is logged in AND has the correct role
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    // Redirect unauthorized users
    header("Location: ../pages/dashboard.php?page=home");
    exit();
}

$user_ref_no = $_SESSION['user_ref_no'];
$role        = $_SESSION['user_role'];
$success     = "";
$errors      = [];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // CHANGE PASSWORD
    if ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit();
        }

        if (strlen($new_password) < 8) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters.']);
            exit();
        }

        if ($new_password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
            exit();
        }

        // Fetch current password from database
        $result = mysqli_query($conn, "SELECT password FROM users WHERE user_ref_no = '$user_ref_no' LIMIT 1");
        $user   = mysqli_fetch_assoc($result);

        if (!password_verify($current_password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            exit();
        }

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password = '$hashed' WHERE user_ref_no = '$user_ref_no'");
        echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
        exit();
    }

    // DEACTIVATE ACCOUNT
    if ($_POST['action'] === 'deactivate') {
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Fetch current password
        $result = mysqli_query($conn, "SELECT password FROM users WHERE user_ref_no = '$user_ref_no' LIMIT 1");
        $user   = mysqli_fetch_assoc($result);

        if (!password_verify($confirm_password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Password is incorrect.']);
            exit();
        }

        // Update approval_status based on role
        if ($role === 'SME') {
            mysqli_query($conn, "UPDATE sme_profiles SET approval_status = 'pending' WHERE user_ref_no = '$user_ref_no'");
        } else {
            // Resident, Council Member
            mysqli_query($conn, "UPDATE resident_profiles SET approval_status = 'pending' WHERE user_ref_no = '$user_ref_no'");
        }

        // Destroy session and redirect to login
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Your account has been deactivated.']);
        exit();
    }
}

?>
<div class="view-analytics-page">

<?php
       $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>';
       $title = "Settings";
       $subtitle = "Manage your account and system preferences.";
       include '../components/section_header.php';
     ?>

 <!-- Action Message -->
    <div id="settings-action-message" class="alert-box" style="display:none;"></div>

    <!-- Change Password Section -->
    <div class="settings-card">
        <div class="settings-card-header">
            <h3>Change Password</h3>
            <p>Update your account password. You will need your current password to make changes.</p>
        </div>
        <div class="settings-card-body">
            <div class="settings-field">
                <label>Current Password</label>
                <input type="password" id="current-password" placeholder="Enter current password">
            </div>
            <div class="settings-field">
                <label>New Password</label>
                <input type="password" id="new-password" placeholder="Enter new password (min 8 characters)">
            </div>
            <div class="settings-field">
                <label>Confirm New Password</label>
                <input type="password" id="confirm-new-password" placeholder="Confirm new password">
            </div>
            <div id="settings-password-error" class="alert-box error-box" style="display:none;"></div>
            <div class="settings-card-footer">
                <button class="settings-save-btn" onclick="submitChangePassword()">Update Password</button>
            </div>
        </div>
    </div>

    <!-- Deactivate Account Section -->
    <?php if ($role !== 'Council Administrator') : ?>
    <div class="settings-card settings-danger-card">
        <div class="settings-card-header">
            <h3>Deactivate Account</h3>
            <p>Deactivating your account will set it to pending status. You will not be able to login until the council reactivates it.</p>
        </div>
        <div class="settings-card-body">
            <div class="settings-field">
                <label>Confirm your password to deactivate</label>
                <input type="password" id="deactivate-password" placeholder="Enter your password">
            </div>
            <div id="settings-deactivate-error" class="alert-box error-box" style="display:none;"></div>
            <div class="settings-card-footer">
                <button class="settings-danger-btn" onclick="openDeactivateModal()">Deactivate Account</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Deactivate Confirm Modal -->
<div id="settings-deactivate-modal" class="settings-modal-overlay" style="display:none;">
    <div class="settings-modal-box">
        <div class="settings-modal-header">
            <h3>Confirm Deactivation</h3>
            <span class="settings-modal-close-btn" onclick="closeDeactivateModal()">&times;</span>
        </div>
        <div class="settings-modal-body">
            <p>Are you sure you want to deactivate your account?</p>
            <p class="settings-modal-warning">Your account will be set to pending and you will be logged out immediately. You will need the council to reactivate your account before you can login again.</p>
        </div>
        <div class="settings-modal-footer">
            <button class="settings-modal-cancel-btn" onclick="closeDeactivateModal()">Cancel</button>
            <button class="settings-modal-danger-btn" onclick="submitDeactivate()">Yes, Deactivate</button>
        </div>
    </div>
</div>

<script>
function showSettingsMessage(message, type) {
    const box = document.getElementById('settings-action-message');
    box.className = 'alert-box ' + (type === 'success' ? 'success-box' : 'error-box');
    box.innerText = message;
    box.style.display = 'block';
    setTimeout(() => { box.style.display = 'none'; }, 4000);
}

function submitChangePassword() {
    const current  = document.getElementById('current-password').value.trim();
    const newPass  = document.getElementById('new-password').value.trim();
    const confirm  = document.getElementById('confirm-new-password').value.trim();
    const errorBox = document.getElementById('settings-password-error');

    errorBox.style.display = 'none';

    if (!current) { errorBox.style.display = 'block'; errorBox.innerText = 'Please enter your current password.'; return; }
    if (!newPass) { errorBox.style.display = 'block'; errorBox.innerText = 'Please enter a new password.'; return; }
    if (newPass.length < 8) { errorBox.style.display = 'block'; errorBox.innerText = 'New password must be at least 8 characters.'; return; }
    if (newPass !== confirm) { errorBox.style.display = 'block'; errorBox.innerText = 'New passwords do not match.'; return; }

    const formData = new FormData();
    formData.append('action', 'change_password');
    formData.append('current_password', current);
    formData.append('new_password', newPass);
    formData.append('confirm_password', confirm);

    fetch('', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('current-password').value = '';
                document.getElementById('new-password').value = '';
                document.getElementById('confirm-new-password').value = '';
                showSettingsMessage(data.message, 'success');
            } else {
                errorBox.style.display = 'block';
                errorBox.innerText = data.message;
            }
        })
        .catch(() => showSettingsMessage('Something went wrong. Please try again.', 'error'));
}

function openDeactivateModal() {
    const password = document.getElementById('deactivate-password').value.trim();
    const errorBox = document.getElementById('settings-deactivate-error');

    errorBox.style.display = 'none';

    if (!password) {
        errorBox.style.display = 'block';
        errorBox.innerText = 'Please enter your password to confirm deactivation.';
        return;
    }

    document.getElementById('settings-deactivate-modal').style.display = 'flex';
}

function closeDeactivateModal() {
    document.getElementById('settings-deactivate-modal').style.display = 'none';
}

function submitDeactivate() {
    const password = document.getElementById('deactivate-password').value.trim();
    const errorBox = document.getElementById('settings-deactivate-error');

    const formData = new FormData();
    formData.append('action', 'deactivate');
    formData.append('confirm_password', password);

    fetch('', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeDeactivateModal();
                showSettingsMessage(data.message, 'success');
                setTimeout(() => {
                    window.location.href = '../pages/login.php';
                }, 2000);
            } else {
                closeDeactivateModal();
                errorBox.style.display = 'block';
                errorBox.innerText = data.message;
            }
        })
        .catch(() => showSettingsMessage('Something went wrong. Please try again.', 'error'));
}

window.addEventListener('click', (e) => {
    const modal = document.getElementById('settings-deactivate-modal');
    if (modal && e.target === modal) closeDeactivateModal();
});
</script>
