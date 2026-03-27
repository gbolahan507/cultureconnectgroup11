<?php
/**
 * Signup API - Resident Registration
 * POST /api/auth/signup.php
 *
 * Expected fields: name, email, password, address, area_id
 * Stores in: users table (role_id = 1 for Resident)
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse('Method not allowed', false, 405);
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

// If not JSON, try form data
if (!$data) {
    $data = $_POST;
}

// Validate required fields
$missing = validateRequired(['name', 'email', 'password', 'address', 'area_id'], $data);
if (!empty($missing)) {
    jsonResponse('Missing required fields: ' . implode(', ', $missing), false, 400);
}

// Sanitize inputs
$name = sanitizeInput($data['name']);
$email = sanitizeInput($data['email']);
$password = $data['password'];
$address = sanitizeInput($data['address']);
$area_id = (int) $data['area_id'];

// Validate email format
if (!validateEmail($email)) {
    jsonResponse('Invalid email format', false, 400);
}

// Validate password length
if (strlen($password) < 6) {
    jsonResponse('Password must be at least 6 characters', false, 400);
}

// Validate area_id is numeric
if (!validateNumeric($area_id)) {
    jsonResponse('Invalid area selected', false, 400);
}

try {
    $pdo = getDBConnection();

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT user_ref_no FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        jsonResponse('Email already registered', false, 409);
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user (role_id = 1 for Resident, status = active)
    $stmt = $pdo->prepare(
        "INSERT INTO users (name, email, password, role_id, address, area_id, status)
         VALUES (?, ?, ?, 1, ?, ?, 'active')"
    );
    $stmt->execute([$name, $email, $hashedPassword, $address, $area_id]);

    $userId = $pdo->lastInsertId();

    // Fetch the created user (to get user_code from trigger)
    $stmt = $pdo->prepare("SELECT user_ref_no, name, email, user_code, status FROM users WHERE user_ref_no = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    jsonResponse([
        'message' => 'Registration successful',
        'user' => $user
    ]);

} catch (PDOException $e) {
    jsonResponse('Registration failed: ' . $e->getMessage(), false, 500);
}
