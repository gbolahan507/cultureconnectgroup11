# Task #1: Database Configuration - Specification

**Task:** Set up database configuration file
**File:** `/backend/config/database.php`
**Priority:** Foundation (Required for all other tasks)
**Assigned to:** Habeeb (Backend)

---

## Purpose

Create a centralized database configuration file that all API files will use to connect to the MySQL database. This eliminates hardcoding credentials in multiple files and makes it easy to change settings in one place.

---

## Requirements

### Functional Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| FR1 | Store database connection credentials as constants | Must |
| FR2 | Provide reusable function to get PDO connection | Must |
| FR3 | Handle connection errors gracefully | Must |
| FR4 | Return PDO object configured for error exceptions | Must |

### Non-Functional Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR1 | Use PDO (not mysqli) for database abstraction | Must |
| NFR2 | Use prepared statements support (enabled by default in PDO) | Must |
| NFR3 | Connection should be reusable across multiple queries | Should |

---

## Configuration Values

```
DB_HOST     = 'localhost'
DB_NAME     = 'cultureconnect'
DB_USER     = 'root'
DB_PASS     = ''
DB_CHARSET  = 'utf8mb4'
```

---

## Function Specification

### `getDBConnection()`

**Description:** Creates and returns a PDO database connection

**Parameters:** None

**Returns:** `PDO` object

**Throws:** Terminates script with error message on connection failure

**PDO Options to Set:**
| Option | Value | Reason |
|--------|-------|--------|
| `PDO::ATTR_ERRMODE` | `PDO::ERRMODE_EXCEPTION` | Throw exceptions on errors |
| `PDO::ATTR_DEFAULT_FETCH_MODE` | `PDO::FETCH_ASSOC` | Return associative arrays |
| `PDO::ATTR_EMULATE_PREPARES` | `false` | Use real prepared statements |

---

## File Structure

```php
<?php
/**
 * Database Configuration
 * CultureConnect - Group 11
 *
 * This file contains database connection settings
 * and provides a reusable connection function.
 */

// Database credentials
define('DB_HOST', 'localhost');
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
    // Implementation here
}
?>
```

---

## Usage Example

Other API files will use this configuration:

```php
<?php
require_once '../config/database.php';

// Get connection
$pdo = getDBConnection();

// Use connection
$stmt = $pdo->prepare("SELECT * FROM areas WHERE id = ?");
$stmt->execute([$id]);
$area = $stmt->fetch();
?>
```

---

## Error Handling

| Scenario | Behavior |
|----------|----------|
| Database server not running | Display error message, terminate |
| Wrong credentials | Display error message, terminate |
| Database not found | Display error message, terminate |
| Connection successful | Return PDO object |

**Error Message Format:**
```
Connection failed: [PDO error message]
```

---

## Testing Checklist

| # | Test Case | Expected Result | Pass |
|---|-----------|-----------------|------|
| 1 | Include file with XAMPP running | No errors | [ ] |
| 2 | Call `getDBConnection()` | Returns PDO object | [ ] |
| 3 | Execute simple query `SELECT 1` | Returns result | [ ] |
| 4 | Stop MySQL, call function | Shows error message | [ ] |
| 5 | Use wrong database name | Shows error message | [ ] |

---

## Test Script

Create `/backend/config/test_connection.php` for testing:

```php
<?php
require_once 'database.php';

try {
    $pdo = getDBConnection();

    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM areas");
    $result = $stmt->fetch();

    echo "Connection successful!\n";
    echo "Areas in database: " . $result['count'];

} catch (Exception $e) {
    echo "Test failed: " . $e->getMessage();
}
?>
```

---

## Dependencies

**Requires:**
- XAMPP installed and running
- MySQL service started
- `cultureconnect` database exists (from SQL import)

**Required by:**
- Task #2: Area CRUD API
- Task #3: Resident CRUD API
- Task #4: Area Dropdown API
- Task #5: Product CRUD API
- Task #6: Voting System
- Task #7-8: Search/Filter
- Task #9: Auth System

---

## Acceptance Criteria

- [ ] File created at `/backend/config/database.php`
- [ ] Constants defined for all credentials
- [ ] `getDBConnection()` function works
- [ ] PDO configured with exception mode
- [ ] Test script confirms connection
- [ ] Error handling works when MySQL is stopped

---

## Notes

- Never commit real passwords to git (this uses default XAMPP empty password)
- For production, credentials should be in environment variables
- PDO is preferred over mysqli for better security and flexibility
