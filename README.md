# CultureConnect - Project Guide

## Overview
CultureConnect is a web-based platform for residents to browse and vote on cultural products and services in their local community (Hertfordshire Council). Built by Group 11, deadline: 31st March 2026.

## Team - Group 11

| Name | Role | Username | Password |
|------|------|----------|----------|
| Victor Ikekhua | Product Owner | victor@gmail.com | Test1234! |
| Oyenike Alade | Requirement Engineer, Database Admin, QA | nike@gmail.com | Test1234! |
| Kenneth Onyeabor | Scrum Master, QA | jake@gmail.com | Test1234! |
| Josephine Abioye | Frontend Developer, Project Coordinator | josephine@gmail.com | Test1234! |
| Habeeblahi Hameed | Backend Developer | habeeb@gmail.com | Test1234! |
| Admin | Council_Administrator | admin@cultureconnect.com | Test1234! |
| Council Member | Council_member | Cmember@cultureconnect.com | Test1234! |
| Harmony Wellbeing | SME Business | info@harmonywellbeing.com | Test1234! |
| Brushstroke Studio | SME Business | hello@brushstrokestudio.com | Test1234! |
| Hatfield Theatre Collective | SME Business | info@hatfieldtheatre.com | Test1234! |
| Hertfordshire Heritage Tours | SME Business | tours@hertheritagetours.com | Test1234! |
| PixelCraft Media | SME Business | studio@pixelcraftmedia.com | Test1234! |
| Hatfield Handmade Co. | SME Business | shop@hatfieldhandmade.com | Test1234! |
| Hertford Ink Publishing | SME Business | press@hertfordink.com | Test1234! |

## Features

- Browse listed cultural products and services
- User registration with location, age group, gender, and interests
- Voting system for products and services
- Dashboard with voting summary charts
- Highlights/Popular section
- Feedback system

## Tech Stack
- **Frontend:** HTML, CSS, PHP (templates), JavaScript
- **Backend:** PHP (API)
- **Database:** MySQL
- **Environment:** XAMPP / PHP built-in server with `frontend/router.php`

## Project Structure
```
├── frontend/
│   ├── components/      # header.php, sidebar.php, footer.php
│   ├── pages/           # dashboard.php, login.php, register.php, etc.
│   ├── css/styles.css   # Main stylesheet (pink/magenta theme)
│   ├── images/          # logo.png
│   ├── data/            # postcodes.json
│   ├── js/              # JavaScript files
│   ├── router.php       # URL routing (enables clean URLs without .php)
│   └── index.html       # Empty (not used)
├── backend/
│   ├── config/          # database.php (PDO connection)
│   ├── includes/        # functions.php (helpers)
│   └── api/             # API endpoints (pending)
├── database/
│   └── cultureconnect.sql  # Full schema with sample data
└── docs/
    ├── BackendImplementation.md  # Backend task guide
    └── specs/                     # Detailed specs per task
```

## Database
- **Name:** culture_connect_grp_11
- **Connection:** localhost, root, no password (XAMPP default)
- **Key tables:** users, roles, areas, categories, listings, votes, polls, poll_options, sme_businesses, orders, reviews
- **4 Roles:** Resident, SME, Council Member, Council Administrator
- **6 Areas:** North, South, East, West, Town Centre, Central Hertfordshire
- **8 Categories:** Visual Arts, Music, Performing Arts, Literature, Culinary Arts, Fashion, Digital Arts, Crafts
- **User Role-based Assess triggers**

## Getting Started

### Prerequisites

- XAMPP installed (or PHP 7.4+ with MySQL)
- Git installed

### Setup

1. Clone the repository
   ```bash
   git clone https://github.com/gbolahan507/cultureconnectgroup11.git
   ```

2. **Option A: Using XAMPP**
   - Move the project to XAMPP's htdocs folder
     ```bash
     cp -r cultureconnectgroup11 /path/to/xampp/htdocs/
     ```
   - Start Apache and MySQL in XAMPP
   - Open the project in browser: `http://localhost/cultureconnectgroup11`

3. **Option B: Using PHP Built-in Server (Recommended)**
   ```bash
   # Navigate to project directory
   cd cultureconnectgroup11

   # Start PHP dev server with clean URL routing
   php -S localhost:8000 -t frontend frontend/router.php
   ```
   - Then visit: http://localhost:8000/pages/dashboard

4. Import the database
   - Open http://localhost/phpmyadmin
   - Create a new database named `culture_connect_grp_11`
   - Import the SQL file from `/database/culture_connect_grp_11.sql`

## Frontend Architecture
- **Entry point:** `pages/dashboard.php` (requires login session)
- **Login:** `pages/login.php` (test login that sets session variables)
- **Layout:** Header + Welcome banner + Sidebar + Content area + Footer
- **Sidebar:** Role-based navigation (different menus per role)
- **Page routing:** `dashboard.php?page=manage-area` loads `pages/manage-area.php` inside dashboard layout
- **Session keys:** `user_role`, `user_name`, `user_email`, `user_id`

## Backend API Tasks (from docs/BackendImplementation.md)
1. Database config (done)
2. Area CRUD API
3. Resident CRUD API
4. Area dropdown for residents
5. Product/Listing CRUD API
6. Voting system
7. Category filter
8. Category + price filter
9. Authentication system
10. Helper functions (done)

## Key Conventions
- Sessions must use `session_status()` check before `session_start()` to avoid duplicate session warnings
- PHP pages included inside `dashboard.php` are partials (no `<html>` wrapper)
- Standalone pages (login.php, register.php, index.php) have full HTML structure
- CSS uses pink/magenta gradient theme (`#dd3ab5`, `#a81c87`, `#E00180`)
- Role-based access control: check `$_SESSION['user_role']` against allowed roles array

## Assignment Requirements

### What We're Building
A web-based prototype using PHP that implements CRUD operations with a MySQL database.

### Tech Stack (Required)
| Layer | Technology |
|-------|------------|
| **Backend** | Core PHP only (no frameworks) |
| **Database** | MySQL (via phpMyAdmin) |
| **Frontend** | HTML, CSS, Bootstrap, JavaScript |

### Key Rules
- **Use core PHP only** - No Laravel, CodeIgniter, or other frameworks
- **Bootstrap is allowed** for styling (must be integrated with PHP)
- **Include sample data** - Fictional representative records in all tables
- **UI must conform** to marking scheme criteria
- **All team members must use the same dev tools** for consistent marking

### What to Implement
1. **Database** - Create MySQL tables with sample/fictional data
2. **CRUD Operations** - Create, Read, Update, Delete functionality connecting frontend to database
3. **User Interface** - Styled with Bootstrap, functional and usable

### Submission Requirements
1. **ZIP file** of the complete PHP project from root
2. **SQL file** exported from phpMyAdmin containing:
   - CREATE statements (table structure)
   - INSERT statements (sample data)

Export SQL: In phpMyAdmin, click root of database → 'Export' tab → ensure both CREATE and INSERT statements are included.

## Marking Scheme (100 marks)
- Basic CRUD functionality: 40 marks
- Advanced feature (auth/reporting): 10 marks
- UI design & usability: 20 marks
- Team report: 20 marks
- Demo presentation: 10 marks

## Start Date
25th February 2026

## Deadline
24th April 2026

## Completed: 23rd April 2026
Software Test Report. Available at: https://docs.google.com/spreadsheets/d/1b0kasejacxnzNs-WCJHMqOmfyiApxAHaouHH0C9M2_M/edit?usp=sharing (Accessed: 23 April 2026).

Project Trello Board Group 11 (2026). Available at: https://trello.com/invite/b/69a8a0e036b4022c30eafce4/ATTIca7c3a3d438570456e1cf92eba037567C82259A1/cultureconnect-web-platform-development (Accessed: 23 April 2026).

Project Group Report Group 11
https://docs.google.com/document/d/1SSGgbmP7hXccUHz0YGNVz8St07A15YdqYIjc9J3Pwfg/edit?usp=sharing

Project Product Backlog
https://docs.google.com/spreadsheets/d/1HuqUASOlBYBrHjrtaM6-3Dgw6y8PZkkV_v0KE-Xcu_8/edit?usp=sharing
