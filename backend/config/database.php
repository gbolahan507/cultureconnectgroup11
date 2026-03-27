<?php
/**
 * Database Configuration
 * CultureConnect - Group 11
 *
 * Centralized database connection settings.
 * All API files should include this file.
 */

// Database credentials
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'cultureconnect');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get database connection
 *
 * @return PDO Database connection object
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;

    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>
