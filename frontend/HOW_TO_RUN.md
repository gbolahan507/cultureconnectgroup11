# How to Run the Project Locally

## Step 1: Clone the repo
```bash
git clone https://github.com/gbolahan507/cultureconnectgroup11.git
cd cultureconnectgroup11
```

## Step 2: Set up the database
1. Open **XAMPP** and start **MySQL** (click Manage Servers tab, select MySQL, click Start)
2. Open **phpMyAdmin** at http://localhost/phpmyadmin
3. Go to the **Import** tab
4. Choose the file `database/cultureconnect.sql` from the project and click **Go**
   - This creates the `cultureconnect` database with all tables and sample data automatically

## Step 3: Start the server
```bash
php -S localhost:8000 -t . router.php
```

## Step 3: Open in browser

| Page | URL |
|---|---|
| Dashboard | http://localhost:8000/frontend/pages/dashboard.php |
| Login | http://localhost:8000/frontend/pages/login.php |
| Register | http://localhost:8000/frontend/pages/register.php |
| API Docs (Swagger) | http://localhost:8000/backend/docs/index.html |

## API Base URL
```
http://localhost:8000/backend/api
```

### Auth Endpoints
| Method | Endpoint | Description |
|---|---|---|
| POST | /backend/api/auth/signup.php | Register new Resident |
| POST | /backend/api/auth/login.php | Login user |
| POST | /backend/api/auth/logout.php | Logout user |

### Signup Request Body (JSON)
```json
{
    "name": "John Doe",
    "email": "john@email.com",
    "password": "mypassword",
    "address": "Hatfield",
    "area_id": 1
}
```

### Login Request Body (JSON)
```json
{
    "email": "john@email.com",
    "password": "mypassword"
}
```

### Area IDs for Dropdown
| ID | Area |
|---|---|
| 1 | Hertfordshire North |
| 2 | Hertfordshire South |
| 3 | Hertfordshire East |
| 4 | Hertfordshire West |
| 5 | Hertfordshire Town Centre |
| 6 | Hertfordshire Central |

### Test Users (already in database)
| Email | Password | Role |
|---|---|---|
| nike.alade01@gmail.com | alade123 | Resident |
| Josephineabioye@gmail.com | JojoOnDaBeat2026 | SME |
| victorehizefua@herts.co.uk | ProudlyCouncilMember96 | Council Member |
| admin@herts.co.uk | HertsBeatsF@ster! | Council Administrator |

## Frontend JavaScript Examples (using fetch — no libraries needed)

### Signup Form
```javascript
document.getElementById('register-form').addEventListener('submit', function(e) {
    e.preventDefault();

    fetch('/backend/api/auth/signup.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            password: document.getElementById('password').value,
            address: document.getElementById('address').value,
            area_id: document.getElementById('area').value
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Registration successful!');
            window.location.href = 'login.php';
        } else {
            alert(data.error);
        }
    });
});
```

### Login Form
```javascript
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();

    fetch('/backend/api/auth/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            email: document.getElementById('email').value,
            password: document.getElementById('password').value
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'dashboard.php';
        } else {
            alert(data.error);
        }
    });
});
```

### Logout
```javascript
fetch('/backend/api/auth/logout.php', { method: 'POST' })
.then(res => res.json())
.then(data => {
    window.location.href = 'login.php';
});
```

## Notes
- Make sure PHP is installed (comes with Mac or use XAMPP)
- The database must be imported into MySQL — use `database/cultureconnect.sql`
- After login, the session handles everything (sidebar, role, name)
