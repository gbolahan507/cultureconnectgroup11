<?php
/**
 * Login API
 * POST /api/auth/login.php
 *
 * Expected fields: email, password
 * Sets session: user_ref_no, user_name, user_email, user_role, area_id
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse('Method not allowed', false, 405);
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    $data = $_POST;
}

// Validate required fields
$missing = validateRequired(['email', 'password'], $data);
if (!empty($missing)) {
    jsonResponse('Missing required fields: ' . implode(', ', $missing), false, 400);
}

$email = sanitizeInput($data['email']);
$password = $data['password'];

// Validate email format
if (!validateEmail($email)) {
    jsonResponse('Invalid email format', false, 400);
}

try {
    $pdo = getDBConnection();

    // Fetch user with role name
    $stmt = $pdo->prepare(
        "SELECT u.user_ref_no, u.name, u.email, u.password, u.address, u.area_id, u.status, u.user_code,
                r.role_name, a.area_name
         FROM users u
         JOIN roles r ON u.role_id = r.role_id
         LEFT JOIN areas a ON u.area_id = a.area_id
         WHERE u.email = ?"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse('Invalid email or password', false, 401);
    }

    // Check password (support both hashed and plain text for existing sample data)
    if (!password_verify($password, $user['password']) && $password !== $user['password']) {
        jsonResponse('Invalid email or password', false, 401);
    }

    // Check account status
    if ($user['status'] === 'rejected') {
        jsonResponse('Your account has been rejected', false, 403);
    }

    // Set session variables
    $_SESSION['user_ref_no'] = $user['user_ref_no'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = trim($user['role_name']);
    $_SESSION['user_address'] = $user['address'];
    $_SESSION['user_area'] = $user['area_name'];
    $_SESSION['area_id'] = $user['area_id'];
    $_SESSION['user_code'] = $user['user_code'];
    $_SESSION['user_status'] = $user['status'];
    $_SESSION['logged_in'] = true;

    jsonResponse([
        'message' => 'Login successful',
        'user' => [
            'user_ref_no' => $user['user_ref_no'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => trim($user['role_name']),
            'area' => $user['area_name'],
            'status' => $user['status']
        ]
    ]);

} catch (PDOException $e) {
    jsonResponse('Login failed: ' . $e->getMessage(), false, 500);
}
