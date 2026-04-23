<?php
// logout.php
session_start();

// Clear all session data
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to the login page
header("Location: ../pages/login.php");
exit();