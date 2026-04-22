# Task #10: Helper Functions - Specification

**Task:** Create helper functions file
**File:** `/backend/includes/functions.php`
**Priority:** Foundation (Required for all API tasks)
**Assigned to:** Habeeb (Backend)

---

## Purpose

Create a centralized file with reusable utility functions that all API files will use. This promotes code reuse, consistency, and easier maintenance across the entire backend.

---

## Requirements

### Functional Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| FR1 | Sanitize user input to prevent XSS | Must |
| FR2 | Provide standard JSON response function | Must |
| FR3 | Validate email format | Must |
| FR4 | Check for required fields in form data | Must |
| FR5 | Validate numeric values | Should |
| FR6 | Validate price format | Should |

---

## Function Specifications

### 1. `sanitizeInput($data)`

**Purpose:** Clean user input to prevent XSS (Cross-Site Scripting) attacks

**Parameters:**
| Name | Type | Description |
|------|------|-------------|
| $data | string | Raw user input |

**Returns:** `string` - Cleaned input

**Logic:**
1. Trim whitespace from start/end
2. Remove HTML tags
3. Convert special characters to HTML entities

**Example:**
```php
$name = sanitizeInput($_POST['name']);
// Input:  "  <script>alert('hack')</script>John  "
// Output: "John"
```

---

### 2. `jsonResponse($data, $success, $status)`

**Purpose:** Send consistent JSON response to frontend

**Parameters:**
| Name | Type | Default | Description |
|------|------|---------|-------------|
| $data | mixed | required | Data to return (array, string, etc.) |
| $success | bool | true | Whether operation succeeded |
| $status | int | 200 | HTTP status code |

**Returns:** void (outputs JSON and exits)

**Response Format:**
```json
{
  "success": true,
  "data": { ... }
}
```

**Error Format:**
```json
{
  "success": false,
  "error": "Error message here"
}
```

**HTTP Status Codes:**
| Code | Meaning | When to use |
|------|---------|-------------|
| 200 | OK | Successful GET, PUT |
| 201 | Created | Successful POST (new record) |
| 400 | Bad Request | Validation error, missing fields |
| 404 | Not Found | Record doesn't exist |
| 500 | Server Error | Database error, exception |

**Example:**
```php
// Success response
jsonResponse($areas, true, 200);

// Error response
jsonResponse("Email is required", false, 400);
```

---

### 3. `validateEmail($email)`

**Purpose:** Check if email format is valid

**Parameters:**
| Name | Type | Description |
|------|------|-------------|
| $email | string | Email to validate |

**Returns:** `bool` - true if valid, false if invalid

**Example:**
```php
validateEmail("test@email.com");  // true
validateEmail("test@");           // false
validateEmail("notemail");        // false
```

---

### 4. `validateRequired($fields, $data)`

**Purpose:** Check if all required fields are present and not empty

**Parameters:**
| Name | Type | Description |
|------|------|-------------|
| $fields | array | List of required field names |
| $data | array | Form data (e.g., $_POST) |

**Returns:** `array` - List of missing field names (empty if all present)

**Example:**
```php
$required = ['name', 'email', 'area_id'];
$missing = validateRequired($required, $_POST);

if (!empty($missing)) {
    jsonResponse("Missing fields: " . implode(', ', $missing), false, 400);
}
```

---

### 5. `validateNumeric($value)`

**Purpose:** Check if value is a valid number

**Parameters:**
| Name | Type | Description |
|------|------|-------------|
| $value | mixed | Value to check |

**Returns:** `bool` - true if numeric, false otherwise

**Example:**
```php
validateNumeric(100);     // true
validateNumeric("50");    // true
validateNumeric("abc");   // false
```

---

### 6. `validatePrice($price)`

**Purpose:** Check if value is a valid price (positive number with max 2 decimals)

**Parameters:**
| Name | Type | Description |
|------|------|-------------|
| $price | mixed | Price value to validate |

**Returns:** `bool` - true if valid price, false otherwise

**Example:**
```php
validatePrice(19.99);   // true
validatePrice(100);     // true
validatePrice(-5);      // false
validatePrice("free");  // false
```

---

## File Structure

```php
<?php
/**
 * Helper Functions
 * CultureConnect - Group 11
 *
 * Reusable utility functions for all API files.
 */

/**
 * Sanitize user input
 */
function sanitizeInput($data) {
    // Implementation
}

/**
 * Send JSON response
 */
function jsonResponse($data, $success = true, $status = 200) {
    // Implementation
}

/**
 * Validate email format
 */
function validateEmail($email) {
    // Implementation
}

/**
 * Check required fields
 */
function validateRequired($fields, $data) {
    // Implementation
}

/**
 * Check if numeric
 */
function validateNumeric($value) {
    // Implementation
}

/**
 * Validate price format
 */
function validatePrice($price) {
    // Implementation
}
?>
```

---

## Usage Example

How API files will use these functions:

```php
<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Sanitize input
$name = sanitizeInput($_POST['name']);
$email = sanitizeInput($_POST['email']);

// Validate required fields
$missing = validateRequired(['name', 'email'], $_POST);
if (!empty($missing)) {
    jsonResponse("Missing: " . implode(', ', $missing), false, 400);
}

// Validate email
if (!validateEmail($email)) {
    jsonResponse("Invalid email format", false, 400);
}

// Success - return data
jsonResponse($result, true, 201);
?>
```

---

## Testing Checklist

| # | Test Case | Input | Expected Output | Pass |
|---|-----------|-------|-----------------|------|
| 1 | sanitizeInput with script tag | `<script>x</script>` | empty or escaped | [ ] |
| 2 | sanitizeInput with spaces | `"  John  "` | `"John"` | [ ] |
| 3 | jsonResponse success | `["a","b"]` | `{"success":true,"data":["a","b"]}` | [ ] |
| 4 | jsonResponse error | `"Error"`, false | `{"success":false,"error":"Error"}` | [ ] |
| 5 | validateEmail valid | `test@email.com` | true | [ ] |
| 6 | validateEmail invalid | `test@` | false | [ ] |
| 7 | validateRequired all present | `['name']`, `['name'=>'John']` | `[]` (empty) | [ ] |
| 8 | validateRequired missing | `['name','email']`, `['name'=>'John']` | `['email']` | [ ] |
| 9 | validateNumeric number | `100` | true | [ ] |
| 10 | validateNumeric string | `"abc"` | false | [ ] |
| 11 | validatePrice valid | `19.99` | true | [ ] |
| 12 | validatePrice negative | `-5` | false | [ ] |

---

## Test Script

Create `/backend/includes/test_functions.php`:

```php
<?php
require_once 'functions.php';

echo "Testing Helper Functions\n";
echo "========================\n\n";

// Test sanitizeInput
echo "1. sanitizeInput:\n";
echo "   '<script>hack</script>' => '" . sanitizeInput('<script>hack</script>') . "'\n";
echo "   '  John  ' => '" . sanitizeInput('  John  ') . "'\n\n";

// Test validateEmail
echo "2. validateEmail:\n";
echo "   'test@email.com' => " . (validateEmail('test@email.com') ? 'true' : 'false') . "\n";
echo "   'invalid' => " . (validateEmail('invalid') ? 'true' : 'false') . "\n\n";

// Test validateRequired
echo "3. validateRequired:\n";
$missing = validateRequired(['name', 'email'], ['name' => 'John']);
echo "   Missing fields: " . (empty($missing) ? 'none' : implode(', ', $missing)) . "\n\n";

// Test validateNumeric
echo "4. validateNumeric:\n";
echo "   100 => " . (validateNumeric(100) ? 'true' : 'false') . "\n";
echo "   'abc' => " . (validateNumeric('abc') ? 'true' : 'false') . "\n\n";

// Test validatePrice
echo "5. validatePrice:\n";
echo "   19.99 => " . (validatePrice(19.99) ? 'true' : 'false') . "\n";
echo "   -5 => " . (validatePrice(-5) ? 'true' : 'false') . "\n\n";

echo "Tests complete!\n";
?>
```

---

## Dependencies

**Requires:**
- None (standalone utilities)

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

- [ ] File created at `/backend/includes/functions.php`
- [ ] `sanitizeInput()` removes tags and trims whitespace
- [ ] `jsonResponse()` outputs correct JSON format
- [ ] `validateEmail()` correctly validates emails
- [ ] `validateRequired()` returns missing fields
- [ ] `validateNumeric()` checks numeric values
- [ ] `validatePrice()` validates price format
- [ ] Test script passes all tests

---

## Security Notes

- Always use `sanitizeInput()` on ALL user input
- Never trust data from `$_POST`, `$_GET`, or `$_REQUEST`
- Use prepared statements for database queries (handled in API files)
- These functions are the first line of defense against XSS
