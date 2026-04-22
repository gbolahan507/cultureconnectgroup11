# CultureConnect Backend Implementation Guide

**Developer:** Habeeb (Backend Developer)
**Project:** CultureConnect Group 11
**Deadline:** 31st March 2026

---

## Overview

This guide outlines the step-by-step implementation of the backend PHP APIs for CultureConnect. Follow the tasks in order as later tasks depend on earlier ones.

---

## Task Summary

| Task # | Description | Marks | Status |
|--------|-------------|-------|--------|
| 1 | Database configuration file | Foundation | [ ] |
| 10 | Helper functions file | Foundation | [ ] |
| 2 | Area CRUD API | 3 | [ ] |
| 3 | Resident CRUD API | 4 | [ ] |
| 4 | Area dropdown for residents | 5 | [ ] |
| 5 | Product/Listing CRUD API | 4 | [ ] |
| 6 | Voting system | 8 | [ ] |
| 7 | Search/filter by category | 4 | [ ] |
| 8 | Filter by category + price | 8 | [ ] |
| 9 | Login/authentication system | 10 | [ ] |

**Total Backend Marks: 46/100**

---

## Phase 1: Foundation Setup

### Task #1: Database Configuration File

**File:** `/backend/config/database.php`

**Purpose:** Centralized database connection using PDO

**Implementation:**
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cultureconnect');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>
```

**Checklist:**
- [ ] Create file
- [ ] Test connection
- [ ] Verify error handling works

---

### Task #10: Helper Functions File

**File:** `/backend/includes/functions.php`

**Purpose:** Reusable utility functions for all APIs

**Functions to implement:**
```php
<?php
// Sanitize user input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Standard JSON response
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Validate email format
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Check required fields
function validateRequired($fields, $data) {
    $missing = [];
    foreach ($fields as $field) {
        if (empty($data[$field])) {
            $missing[] = $field;
        }
    }
    return $missing;
}
?>
```

**Checklist:**
- [ ] Create file
- [ ] Test each function
- [ ] Include in other files

---

## Phase 2: Core CRUD Operations

### Task #2: Area CRUD API (3 marks)

**File:** `/backend/api/areas.php`

**Endpoints:**
| Method | Action | Description |
|--------|--------|-------------|
| GET | getAreas | List all areas |
| GET | getAreaById | Get single area |
| POST | addArea | Create new area |
| PUT | updateArea | Update area |
| DELETE | deleteArea | Delete area |

**Database Table:** `areas`
```sql
- id (INT, PK, AUTO_INCREMENT)
- name (VARCHAR 100)
- description (TEXT)
- created_at (TIMESTAMP)
```

**Checklist:**
- [ ] Create file with includes
- [ ] Implement GET all areas
- [ ] Implement GET single area
- [ ] Implement POST add area
- [ ] Implement PUT update area
- [ ] Implement DELETE area
- [ ] Test all endpoints

---

### Task #3: Resident CRUD API (4 marks)

**File:** `/backend/api/residents.php`

**Endpoints:**
| Method | Action | Description |
|--------|--------|-------------|
| GET | getResidents | List all residents |
| GET | getResidentById | Get single resident |
| POST | addResident | Register new resident |
| PUT | updateResident | Update resident |
| DELETE | deleteResident | Delete resident |

**Database Table:** `users` (role = 'Resident')
```sql
- id, name, email, password_hash, role, area_id, user_code, created_at
```

**Important:**
- Hash passwords: `password_hash($password, PASSWORD_DEFAULT)`
- Role defaults to 'Resident'
- JOIN with areas table for area name

**Checklist:**
- [ ] Create file with includes
- [ ] Implement GET all residents
- [ ] Implement GET single resident
- [ ] Implement POST register resident (with password hashing)
- [ ] Implement PUT update resident
- [ ] Implement DELETE resident
- [ ] Test all endpoints

---

### Task #4: Area Dropdown API (5 marks)

**File:** `/backend/api/get_areas_dropdown.php`

**Purpose:** Provides area list for registration form dropdown

**Response Format:**
```json
{
  "success": true,
  "data": [
    {"id": 1, "name": "Hertfordshire North"},
    {"id": 2, "name": "Hertfordshire South"}
  ]
}
```

**Checklist:**
- [ ] Create endpoint
- [ ] Return JSON array of areas
- [ ] Test with frontend dropdown

---

### Task #5: Product/Listing CRUD API (4 marks)

**File:** `/backend/api/products.php`

**Endpoints:**
| Method | Action | Description |
|--------|--------|-------------|
| GET | getProducts | List all products |
| GET | getProductById | Get single product |
| POST | addProduct | Create new product |
| PUT | updateProduct | Update product |
| DELETE | deleteProduct | Delete product |

**Database Table:** `listings`
```sql
- id, sme_id, category_id, title, description, price,
- availability_status, created_at, updated_at
```

**Checklist:**
- [ ] Create file with includes
- [ ] Implement GET all products (JOIN categories)
- [ ] Implement GET single product
- [ ] Implement POST add product
- [ ] Implement PUT update product
- [ ] Implement DELETE product
- [ ] Test all endpoints

---

## Phase 3: Voting System

### Task #6: Voting System (8 marks)

**File:** `/backend/api/votes.php`

**Endpoints:**
| Method | Action | Description |
|--------|--------|-------------|
| POST | castVote | Submit vote for product |
| GET | getVoteCount | Get votes for a product |
| GET | checkVoted | Check if user voted |
| GET | getTopVoted | Get ranked products |

**Database Table:** `votes`
```sql
- id, user_id, listing_id, created_at
```

**Business Rules:**
- One vote per user per product
- Only residents can vote
- Return vote count with products

**Checklist:**
- [ ] Create file with includes
- [ ] Implement POST cast vote (with duplicate check)
- [ ] Implement GET vote count
- [ ] Implement GET check if voted
- [ ] Implement GET top voted products
- [ ] Test all endpoints

---

## Phase 4: Search & Filter

### Task #7: Category Filter (4 marks)

**File:** `/backend/api/products.php` (extend)

**New Endpoints:**
| Method | Action | Description |
|--------|--------|-------------|
| GET | getCategories | List all categories |
| GET | filterByCategory | Products by category |

**Checklist:**
- [ ] Add getCategories function
- [ ] Add category filter parameter
- [ ] Test filtering

---

### Task #8: Category + Price Filter (8 marks)

**File:** `/backend/api/products.php` (extend)

**New Endpoint:**
| Method | Action | Description |
|--------|--------|-------------|
| GET | filterProducts | Filter by category AND price |

**Query Parameters:**
- `category_id` (optional)
- `max_price` (optional, default 200)

**SQL Example:**
```sql
SELECT * FROM listings
WHERE category_id = ? AND price < ?
ORDER BY price ASC
```

**Checklist:**
- [ ] Add combined filter function
- [ ] Support optional parameters
- [ ] Test with various combinations

---

## Phase 5: Advanced Feature (10 marks)

### Task #9: Authentication System

**File:** `/backend/api/auth.php`

**Endpoints:**
| Method | Action | Description |
|--------|--------|-------------|
| POST | login | Authenticate user |
| POST | logout | End session |
| GET | checkAuth | Verify logged in |
| GET | getProfile | Get current user |

**Implementation:**
- Use PHP sessions
- Verify with `password_verify()`
- Store user data in session
- Role-based access control

**Checklist:**
- [ ] Create file with includes
- [ ] Implement login with session
- [ ] Implement logout
- [ ] Implement auth check middleware
- [ ] Implement role check
- [ ] Test all scenarios

---

## File Structure (Final)

```
backend/
├── config/
│   └── database.php          # Task #1
├── includes/
│   └── functions.php         # Task #10
└── api/
    ├── areas.php             # Task #2
    ├── residents.php         # Task #3
    ├── get_areas_dropdown.php # Task #4
    ├── products.php          # Tasks #5, #7, #8
    ├── votes.php             # Task #6
    └── auth.php              # Task #9
```

---

## Testing Checklist

### Tools
- [ ] Postman or Thunder Client for API testing
- [ ] Browser for GET requests
- [ ] phpMyAdmin to verify database changes

### Test Each Endpoint
- [ ] Valid request returns correct data
- [ ] Invalid request returns error message
- [ ] Empty fields are handled
- [ ] SQL injection is prevented (using prepared statements)

---

## Progress Log

| Date | Task | Notes |
|------|------|-------|
| | | |
| | | |
| | | |

---

## Notes

- Always use prepared statements to prevent SQL injection
- Return consistent JSON response format
- Test each endpoint before moving to next task
- Coordinate with Josephine (Frontend) for API integration
