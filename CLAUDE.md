# CultureConnect - Project Guide

## Overview
CultureConnect is a web-based platform for residents to browse and vote on cultural products and services in their local community (Hertfordshire Council). Built by Group 11, deadline: 31st March 2026.

## Team
- Victor (Product Owner)
- Oyin (Requirement Engineer, Database Admin, QA)
- Jake (Scrum Master, QA)
- Josephine (Frontend Developer, Project Coordinator)
- Habeeb (Backend Developer)

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
- **Name:** cultureconnect
- **Connection:** localhost, root, no password (XAMPP default)
- **Key tables:** users, roles, areas, categories, listings, votes, polls, poll_options, sme_businesses, orders, reviews
- **4 Roles:** Resident, SME, Council Member, Council Administrator
- **6 Areas:** North, South, East, West, Town Centre, Central Hertfordshire
- **8 Categories:** Visual Arts, Music, Performing Arts, Literature, Culinary Arts, Fashion, Digital Arts, Crafts
- **User code trigger:** Auto-generates codes (RES-XXXX, SME-XXXX, CNS-XXXX, ADM-XXXX)

## Frontend Architecture
- **Entry point:** `pages/dashboard.php` (requires login session)
- **Login:** `pages/login.php` (test login that sets session variables)
- **Layout:** Header + Welcome banner + Sidebar + Content area + Footer
- **Sidebar:** Role-based navigation (different menus per role)
- **Page routing:** `dashboard.php?page=manage-area` loads `pages/manage-area.php` inside dashboard layout
- **Session keys:** `user_role`, `user_name`, `user_email`, `user_id`

## Running Locally
```bash
# Start PHP dev server with clean URL routing
php -S localhost:8000 -t frontend frontend/router.php
```
Then visit: http://localhost:8000/pages/dashboard

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

## Marking Scheme (100 marks)
- Basic CRUD functionality: 40 marks
- Advanced feature (auth/reporting): 10 marks
- UI design & usability: 20 marks
- Team report: 20 marks
- Demo presentation: 10 marks
