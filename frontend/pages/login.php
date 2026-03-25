<?php
// test-login.php
session_start();

/* FRONT-END TEST LOGIN PAGE
   Purpose: This page allows different user roles testing as
   sidebar menu and dashboard render role-specific options.
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set the session role based on selected user type
    $_SESSION['user_role'] = $_POST['role'] ?? 'Guest';
    $_SESSION['user_name'] = $_POST['name'] ?? 'Test User'; // optional name for welcome message

    // Redirect to dashboard
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Test Login - Dashboard</title>
<style>
  body {
    font-family: Arial, sans-serif;
    display:flex;
    height:100vh;
    justify-content:center;
    align-items:center;
    background:#f5f5f5;
  }
  .login-container {
    background:white;
    padding:30px;
    border-radius:8px;
    box-shadow:0 4px 12px rgba(0,0,0,0.2);
    width:300px;
  }
  h2 {
    text-align:center;
    margin-bottom:20px;
  }
  select, input, button {
    width:100%;
    padding:10px;
    margin:10px 0;
    border-radius:6px;
    border:1px solid #ccc;
    font-size:14px;
  }
  button {
    background:#E00180;
    color:white;
    border:none;
    cursor:pointer;
    transition: background 0.2s ease;
  }
  button:hover {
    background:#c3006d;
  }
  .note {
    font-size:12px;
    color:#555;
    margin-top:10px;
    text-align:center;
  }
</style>
</head>
<body>

<?php
    /*
    Import  header component*/
    include "../components/header.php";
?>

<div class="login-container">
    <h2>Test Login</h2>
    <form method="POST">
        <label for="name">Your Name:</label>
        <input type="text" name="name" id="name" placeholder="Enter name (optional)">
        
        <label for="role">Select User Role:</label>
        <select name="role" id="role">
            <option value="Council Administrator">Council Admin</option>
            <option value="Council_member">Council Member</option>
            <option value="Resident">Resident</option>
            <option value="SME">SME</option>
        </select>
        <button type="submit">Login</button>
    </form>
    <p class="note">This login is only for front-end testing. Sidebar menu adapts to the selected role.</p>
</div>

</body>
</html>