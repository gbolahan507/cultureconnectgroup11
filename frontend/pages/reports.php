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

?>
<div class="view-analytics-page">

<?php
       $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 0 0 4.5 9.75v7.5a2.25 2.25 0 0 0 2.25 2.25h7.5a2.25 2.25 0 0 0 2.25-2.25v-7.5a2.25 2.25 0 0 0-2.25-2.25h-.75m-6 3.75 3 3m0 0 3-3m-3 3V1.5m6 9h.75a2.25 2.25 0 0 1 2.25 2.25v7.5a2.25 2.25 0 0 1-2.25 2.25h-7.5a2.25 2.25 0 0 1-2.25-2.25v-.75" />
                </svg>';
       $title = "Generate Reports";
       $subtitle = "Retrieve, print, and export reports";
       include '../db_connection.php';
       include '../components/section_header.php';
     ?>

</div>