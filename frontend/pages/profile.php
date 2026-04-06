<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$allowedRoles = ['Resident', 'SME'];

// Check if user is logged in AND has the correct role
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    // Redirect unauthorized users
    header("Location: ../pages/dashboard.php?page=home");
    exit();
}

$user_ref_no = $_SESSION['user_id'];
$role        = $_SESSION['role'];

// Fetch base user details
$user_result = mysqli_query($conn, "SELECT * FROM users WHERE user_id = '$user_ref_no' LIMIT 1");
$user        = mysqli_fetch_assoc($user_result);

// Fetch area name
$area_name = 'N/A';
if (!empty($user['area_id'])) {
    $area_result = mysqli_query($conn, "SELECT area_name FROM areas WHERE area_id = '{$user['area_id']}' LIMIT 1");
    $area_row    = mysqli_fetch_assoc($area_result);
    $area_name   = $area_row['area_name'] ?? 'N/A';
}

// Fetch profile details based on role
$profile = [];
if ($role_id == 2) {
    // SME
    $profile_result = mysqli_query($conn, "SELECT * FROM sme_profiles WHERE user_ref_no = '$user_ref_no' LIMIT 1");
    $profile        = mysqli_fetch_assoc($profile_result) ?? [];
} else {
    // Resident, Council Member, Council Admin
    $profile_result = mysqli_query($conn, "SELECT * FROM resident_profiles WHERE user_id = '$user_ref_no' LIMIT 1");
    $profile        = mysqli_fetch_assoc($profile_result) ?? [];
}

// Role display name
$roleLabels = [
    1 => 'Resident',
    2 => 'SME',
    3 => 'Council Member',
    4 => 'Council Administrator'
];
$role_display = $roleLabels[$role_id] ?? 'Unknown';

// Status
$status = $profile['approval_status'] ?? 'N/A';

?>

<div class="profile-wrapper">
    <?php
       $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>';
       $title = "Profile";
       $subtitle = "Your profile details at a glance.";
       include '../db_connection.php';
       include '../components/section_header.php';
     ?> 

    <div class="profile-cards-wrapper">

        <!-- Personal Information -->
        <div class="profile-card">
            <h2>Personal Information</h2>

            <?php if ($role_id == 2) : ?>
                <div class="profile-row">
                    <div class="profile-label">Business Name</div>
                    <div class="profile-value"><?= htmlspecialchars($profile['business_name'] ?? 'N/A') ?></div>
                </div>
            <?php else : ?>
                <div class="profile-row">
                    <div class="profile-label">Given Name</div>
                    <div class="profile-value"><?= htmlspecialchars($profile['given_name'] ?? 'N/A') ?></div>
                </div>
                <div class="profile-row">
                    <div class="profile-label">Family Name</div>
                    <div class="profile-value"><?= htmlspecialchars($profile['family_name'] ?? 'N/A') ?></div>
                </div>
                <div class="profile-row">
                    <div class="profile-label">Date of Birth</div>
                    <div class="profile-value"><?= htmlspecialchars($profile['dob'] ?? 'N/A') ?></div>
                </div>
                <div class="profile-row">
                    <div class="profile-label">Gender</div>
                    <div class="profile-value"><?= htmlspecialchars($profile['gender'] ?? 'N/A') ?></div>
                </div>
            <?php endif; ?>

            <div class="profile-row">
                <div class="profile-label">Email</div>
                <div class="profile-value"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></div>
            </div>

            <div class="profile-row">
                <div class="profile-label">Phone</div>
                <div class="profile-value"><?= htmlspecialchars($profile['phone'] ?? 'N/A') ?></div>
            </div>

            <div class="profile-row">
                <div class="profile-label">Role</div>
                <div class="profile-value"><?= $role_display ?></div>
            </div>
        </div>

        <!-- Location Information -->
        <div class="profile-card">
            <h3>Location Information</h3>

            <div class="profile-row">
                <div class="profile-label">Address</div>
                <div class="profile-value"><?= htmlspecialchars($profile['address'] ?? 'N/A') ?></div>
            </div>

            <div class="profile-row">
                <div class="profile-label">Postcode</div>
                <div class="profile-value"><?= htmlspecialchars($profile['post_code'] ?? 'N/A') ?></div>
            </div>

            <div class="profile-row">
                <div class="profile-label">Area</div>
                <div class="profile-value"><?= htmlspecialchars($area_name) ?></div>
            </div>
        </div>

        <!-- Account Details -->
        <div class="profile-card">
            <h3>Account Details</h3>

            <div class="profile-row">
                <div class="profile-label">User Code</div>
                <div class="profile-value"><?= htmlspecialchars($user['user_code'] ?? 'N/A') ?></div>
            </div>

            <div class="profile-row">
                <div class="profile-label">Status</div>
                <div class="profile-value">
                    <span class="status <?= strtolower($status) ?>">
                        <?= ucfirst($status) ?>
                    </span>
                </div>
            </div>

            <div class="profile-row">
                <div class="profile-label">Date Joined</div>
                <div class="profile-value">
                    <?= isset($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : 'N/A' ?>
                </div>
            </div>

            <div class="profile-row">
                <div class="profile-label">Settings</div>
                <div class="profile-value">
                    <a href="?page=settings" class="profile-settings-link">Manage Settings →</a>
                </div>
            </div>
        </div>

        <!-- SME Business Information -->
        <?php if ($role_id == 2) : ?>
        <div class="profile-card">
            <h3>Business Information</h3>

            <div class="profile-row">
                <div class="profile-label">Business Reg No</div>
                <div class="profile-value"><?= htmlspecialchars($profile['business_reg_no'] ?? 'N/A') ?></div>
            </div>

            <div class="profile-row">
                <div class="profile-label">Category</div>
                <div class="profile-value"><?= htmlspecialchars($profile['category'] ?? 'N/A') ?></div>
            </div>

            <div class="profile-row">
                <div class="profile-label">Description</div>
                <div class="profile-value"><?= htmlspecialchars($profile['business_description'] ?? 'N/A') ?></div>
            </div>
        </div>
        <?php endif; ?>

    </div>
    

</div>

