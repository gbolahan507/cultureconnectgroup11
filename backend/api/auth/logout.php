<?php
/**
 * Logout API
 * POST /api/auth/logout.php
 *
 * Destroys the current session
 */

session_start();
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Destroy session
session_unset();
session_destroy();

jsonResponse(['message' => 'Logged out successfully']);
