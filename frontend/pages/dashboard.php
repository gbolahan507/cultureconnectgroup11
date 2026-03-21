<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
</head> 

<body>

<div class="page-wrapper">

<?php
    /*
    Import  header component*/
    include "../components/header.php";
?>

<!-- Wellcome Section -->

<section class="dashboard-welcome">
  <div class="container welcome-container">
    <div class="welcome-text">
      <h1 class="welcome-title">Hi, <span id="userName">User</span>! 👋</h1>
      <p class="welcome-subtitle">Welcome to your dashboard</p>
    </div>
    <div class="role-badge">
      <p class="role-label">Role</p>
      <p class="role-name" id="userRole">Loading...</p>
    </div>
  </div>
</section>

<div class="dashboard-layout3">

   <div class="sidebar">

   <?php include "../components/sidebar.php"; ?>

   </div>

   <div class="dashboard-content">

            <h2>Dashboard Display</h2>
            <p>When a sidebar option is clicked, the content will appear here.</p>
   </div>
</div>

<?php
    /* Imports component */
    include "../components/footer.php";
?>
</div>

</body>

</html>