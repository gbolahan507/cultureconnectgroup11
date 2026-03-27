<?php
/**
 * Helper Functions
 * CultureConnect - Group 11
 *
 * Reusable utility functions for all API files.
 */

/**
 * Sanitize user input to prevent XSS attacks
 *
 * @param string $data Raw user input
 * @return string Cleaned input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Send JSON response to frontend
 *
 * @param mixed $data Data to return or error message
 * @param bool $success Whether operation succeeded
 * @param int $status HTTP status code
 */
function jsonResponse($data, $success = true, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');

    if ($success) {
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $data
        ]);
    }
    exit;
}

/**
 * Validate email format
 *
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if required fields are present and not empty
 *
 * @param array $fields List of required field names
 * @param array $data Form data to check
 * @return array List of missing field names
 */
function validateRequired($fields, $data) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Check if value is numeric
 *
 * @param mixed $value Value to check
 * @return bool True if numeric, false otherwise
 */
function validateNumeric($value) {
    return is_numeric($value);
}

/**
 * Validate price format (positive number)
 *
 * @param mixed $price Price value to validate
 * @return bool True if valid price, false otherwise
 */
function validatePrice($price) {
    if (!is_numeric($price)) {
        return false;
    }
    return (float)$price > 0;
}
?>
