<?php
// Start session
session_start();
$allowedRoles = ['Council Administrator', 'Council_member', 'Resident', 'SME'];

// Check if user is logged in AND has the correct role
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    // Redirect unauthorized users
    header("Location: ../pages/dashboard.php?page=home");
    exit();
}
?>

<div class="profile-wrapper">

    <div class="profile-header">
        <div class="icon-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
        </div>
        <div class="Profile-text">
            <h2>Profile</h2>
        </div>
    </div>


    <div class="profile-card">
        <h2>Personal Information</h2>

        <div class="profile-row">
            <div class="profile-label">Name</div>
            <div class="profile-value"><?= $user['name']; ?></div>
        </div>

        <div class="profile-row">
            <div class="profile-label">Email</div>
            <div class="profile-value"><?= $user['email']; ?></div>
        </div>

        <div class="profile-row">
            <div class="profile-label">Role</div>
            <div class="profile-value"><?= $user['role_name']; ?></div>
        </div>
    </div>

    <div class="profile-card">
        <h3>Location Information</h3>

        <div class="profile-row">
            <div class="profile-label">Address</div>
            <div class="profile-value"><?= $user['address']; ?></div>
        </div>

        <div class="profile-row">
            <div class="profile-label">Area</div>
            <div class="profile-value"><?= $user['area_name']; ?></div>
        </div>
    </div>

    <div class="profile-card">
        <h3>Account Details</h3>

        <div class="profile-row">
            <div class="profile-label">Status</div>
            <div class="profile-value">
                <span class="status <?= strtolower($user['status']); ?>">
                    <?= ucfirst($user['status']); ?>
                </span>
            </div>
        </div>

        <div class="profile-row">
            <div class="profile-label">Date Joined</div>
            <div class="profile-value"><?= $user['created_at']; ?></div>
        </div>
    </div>

    <?php if ($user['role_name'] == 'SME'): 
    ?>
    <div class="profile-card">
        <h3>Business Information</h3>

        <div class="profile-row">
        <div class="profile-label">Business Name</div>
        <div class="profile-value"><?= $sme['business_name']; ?></div>
        </div>

        <div class="profile-row">
        <div class="profile-label">Business Type</div>
        <div class="profile-value"><?= $sme['business_type']; ?></div>
        </div>

        <div class="profile-row">
        <div class="profile-label">Phone</div>
        <div class="profile-value"><?= $sme['phone']; ?></div>
        </div>
    </div>
    <?php endif; ?>

</div>

