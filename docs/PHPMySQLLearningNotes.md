# PHP & MySQL Learning Notes

**Date:** 2026-03-07

---

## Project Overview: CultureConnect

A web platform where residents browse and vote on local cultural products and services.

### Entities
| Entity | Description |
|--------|-------------|
| Area | Location/neighborhood |
| Resident | User who can vote |
| Platform | Cultural provider/business |
| Product/Service | Item offered by a platform |
| Vote | Resident's vote for a product |

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Core PHP (no frameworks) |
| Database | MySQL (via phpMyAdmin) |
| Frontend | HTML, CSS, Bootstrap, JavaScript |
| Local Server | XAMPP |

---

## Firebase vs MySQL

| Firebase (Flutter) | MySQL (PHP) |
|--------------------|-------------|
| Firestore collections | MySQL tables |
| `collection('products').get()` | `SELECT * FROM products` |
| `collection('products').add({...})` | `INSERT INTO products (...)` |
| `doc.update({...})` | `UPDATE products SET ... WHERE id = ?` |
| `doc.delete()` | `DELETE FROM products WHERE id = ?` |
| Cloud storage | Local storage (XAMPP) |

---

## Firebase Auth vs PHP Sessions

| Firebase Auth | PHP |
|---------------|-----|
| `signInWithEmailAndPassword()` | `mysqli_query()` + `$_SESSION` |
| `currentUser` | `$_SESSION['user_id']` |
| `signOut()` | `session_destroy()` |

---

## File Types

| Type | Use |
|------|-----|
| `.html` | Static pages (no database) |
| `.php` | Dynamic pages (connects to database) |

PHP files contain HTML inside them. The browser never sees PHP code - only the resulting HTML.

---

## Frontend + Backend Separation

### Folder Structure
```
/templates/           ← Frontend (HTML/Bootstrap)
    header.php
    footer.php
    product_card.php

/pages/               ← Backend (PHP logic)
    products.php
    add_product.php
```

### How They Connect
```php
<?php
// Backend: Fetch data
$products = mysqli_query($conn, "SELECT * FROM products");

// Include frontend template
include 'templates/header.php';

// Loop and display
while($product = mysqli_fetch_assoc($products)):
    include 'templates/product_card.php';
endwhile;

include 'templates/footer.php';
?>
```

---

## XAMPP

Local development environment that runs:
- **Apache** - Web server (serves PHP files)
- **MySQL** - Database server
- **phpMyAdmin** - Web UI for database management

### Data Storage
- Data stored locally on your computer
- Each developer has their own database
- Sync via `.sql` files in Git

---

## MySQL Data Types

| Type | Use | Example |
|------|-----|---------|
| INT | Numbers (IDs, counts) | `1`, `42`, `1000` |
| VARCHAR(n) | Short text (max n chars) | `"John"`, `"john@email.com"` |
| TEXT | Long text | Product descriptions |
| DECIMAL(10,2) | Money/prices | `49.99` |
| TIMESTAMP | Date/time | `2026-03-07 23:00:00` |
| ENUM | Fixed choices | `'available', 'unavailable'` |

### VARCHAR vs TEXT
- VARCHAR: Know max length, can add UNIQUE index
- TEXT: Unknown/long length, slower searching

---

## phpMyAdmin Table Settings

### Default
What value if nothing provided:
- `None` - Must provide value
- `NULL` - Empty allowed
- `CURRENT_TIMESTAMP` - Auto-fills with current time

### Null
Can field be empty?
- Unchecked = Required
- Checked = Optional

### Index
| Type | Purpose |
|------|---------|
| PRIMARY | Main ID (one per table) |
| UNIQUE | No duplicates allowed |
| INDEX | Faster searching |

### A_I (Auto Increment)
Automatically adds +1 for each new row. Use on `id` column only.

### Attributes
- UNSIGNED - No negative numbers
- ZEROFILL - Pad with zeros

### Collation
Text sorting/comparison rules. Use `utf8mb4_general_ci` (default).

---

## SQL Operations

### Create Table
```sql
CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Insert Data
```sql
INSERT INTO user (name, email, password)
VALUES ('John', 'john@example.com', SHA1('password123'));
```

### Select Data
```sql
SELECT * FROM user;
SELECT * FROM products WHERE price < 200;
```

### Update Data
```sql
UPDATE user SET name = 'John Smith' WHERE id = 1;
```

### Delete Data
```sql
DELETE FROM user WHERE id = 1;
```

---

## Functions (in phpMyAdmin Insert)

| Function | What it does |
|----------|--------------|
| MD5 | Hash value (basic) |
| SHA1 | Hash value (better) |
| NOW() | Current timestamp |
| LOWER | Convert to lowercase |
| UPPER | Convert to uppercase |

---

## Form to Database Flow

### HTML Form
```html
<form action="register.php" method="POST">
    <input type="text" name="name">
    <input type="email" name="email">
    <input type="password" name="password">
    <button type="submit">Sign Up</button>
</form>
```

### PHP Handler
```php
<?php
$conn = mysqli_connect("localhost", "root", "", "cultureconnect");

$name = $_POST['name'];
$email = $_POST['email'];
$password = sha1($_POST['password']);

$sql = "INSERT INTO user (name, email, password) VALUES ('$name', '$email', '$password')";
mysqli_query($conn, $sql);

echo "User registered!";
?>
```

### Flow
1. User fills form → clicks submit
2. Form sends data to PHP file
3. PHP grabs data with `$_POST['field']`
4. PHP builds INSERT query
5. PHP runs query → data saved
6. User sees success message

---

## Team Workflow

| Role | Responsibility |
|------|----------------|
| Database Admin (Oyin) | Create tables, manage SQL |
| Frontend (Josephine) | HTML/CSS/Bootstrap templates |
| Backend (Habeeb) | PHP logic, database queries |

### Syncing Database
1. Design tables → export `.sql` file
2. Commit to Git
3. Teammates pull and import into their XAMPP

---

## phpMyAdmin Tabs

| Tab | Use |
|-----|-----|
| Browse | View data |
| Structure | Edit columns |
| SQL | Run queries |
| Search | Find records |
| Insert | Add data manually |
| Export | Export to SQL/CSV |
| Import | Import data |

---

## Key Takeaways

1. **PHP is the backend** - No separate API needed
2. **MySQL stores data locally** - Sync via SQL files
3. **phpMyAdmin is for setup/testing** - Not daily use
4. **Forms handle data entry** - Users never see phpMyAdmin
5. **Templates separate concerns** - Frontend and backend work independently
