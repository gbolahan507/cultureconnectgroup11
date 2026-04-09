# Cultural Connect

A platform for residents to browse and vote on cultural products and services in their local community.

## Features

- Browse listed cultural products and services
- User registration with location, age group, gender, and interests
- Voting system for products and services
- Dashboard with voting summary charts
- Highlights/Popular section
- Feedback system

## Tech Stack

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Local Environment:** XAMPP

## Team - Group 11

| Name | Role | Username | password | 
|------|------|------| ------|
| Victor Ikekhua| Product Owner | victor@gmail.com | Test1234!|
| Oyenike Alade | Requirement Engineer, Database Admin, QA | nike@gmail.com | Test1234!|
| Kenneth Onyeabor | Scrum Master, QA | jake@gmail.com | Test1234!|
| Josephine Abioye| Frontend Developer, Project Coordinator | josephine@gmail.com | Test1234!|
| Habeeblahi Hameed | Backend Developer | habeeb@gmail.com | Test1234!|
| Admin | Council_Administrator | admin@cultureconnect.com | Test1234!|
| Council Member | Council_member |Cmember@cultureconnect.com | Test1234!|
| Harmony Wellbeing | SME Business | info@harmonywellbeing.com | Test1234!|
| Brushstroke Studio | SME Business | hello@brushstrokestudio.com | Test1234!|
| Hatfield Theatre Collective| SME Business | info@hatfieldtheatre.com | Test1234!|
| Hertfordshire Heritage Tours | SME Business | tours@hertheritagetours.com | Test1234!|
| PixelCraft Media| SME Business | studio@pixelcraftmedia.com | Test1234!|
| Hatfield Handmade Co. | SME Business | shop@hatfieldhandmade.com| Test1234!|
| Hertford Ink Publishing | SME Business | press@hertfordink.com | Test1234!|





## Getting Started

### Prerequisites

- XAMPP installed
- Git installed

### Setup

1. Clone the repository
   ```bash
   git clone https://github.com/gbolahan507/cultureconnectgroup11.git
   ```

2. Move the project to XAMPP's htdocs folder
   ```bash
   cp -r cultureconnectgroup11 /path/to/xampp/htdocs/
   ```

3. Start Apache and MySQL in XAMPP

4. Import the database
   - Open http://localhost/phpmyadmin
   - Create a new database
   - Import the SQL file from the `/database` folder

5. Open the project in browser
   ```
   http://localhost/cultureconnectgroup11
   ```

## Project Structure

```
cultureconnectgroup11/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── includes/
├── database/
├── pages/
└── index.php
```

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

## Deadline

31st March 2026
