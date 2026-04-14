<?php
session_start();

if (!isset($_SESSION['user_role'])) {
    header("Location: ../pages/login.php");
    exit();
}

$role = $_SESSION['user_role'] ?? '';

$allowedPages = [
    'analytics',
    'add-product',
    'cart',
    'manage-area',
    'manage-users',
    'manage-orders',
    'manage-listings',
    'my-orders',
    'review-products',
    'reports',
    'profile',
    'settings',
    'view-votes'
];

$defaultPage = match($role) {
    'Resident'              => 'profile',
    'SME'                   => 'manage-listings',
    'Council Member'        => 'analytics',
    'Council Administrator' => 'analytics',
    default                 => 'profile'
};

$page = $_GET['page'] ?? $defaultPage;

if (!in_array($page, $allowedPages)) {
    $page = $defaultPage;
}
?>
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
    include '../db_connection.php';
    include "../components/header.php";
?>

<!-- Welcome Section -->
<section class="dashboard-welcome">
  <div class="container welcome-container">
    <div class="welcome-text">
      <h1 class="welcome-title">Hi,
      <span id="userName">
        <?php
          $role = $_SESSION['user_role'] ?? '';

          if ($role === 'SME') {
              echo htmlspecialchars($_SESSION['business_name'] ?? 'User');
          } elseif ($role === 'Resident') {
              echo htmlspecialchars($_SESSION['first_name'] ?? 'User');
          } else {
              // Council Member and Council Administrator
              echo htmlspecialchars($_SESSION['first_name'] ?? 'User');
          }
        ?>
      </span>! 👋</h1>
      <p class="welcome-subtitle">Welcome to your dashboard</p>
    </div>
    <div class="role-badge">
      <p class="role-label">Role</p>
      <p class="role-name" id="userRole">
        <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Unknown'); ?>
      </p>
    </div>
  </div>
</section>

<div class="dashboard-layout3">

   <div class="sidebar">

   <?php include "../components/sidebar.php"; ?>

   </div>

   <div class="dashboard-content">
   <?php
   $file = "../pages/" . $page . ".php";

   if (file_exists($file)) {
    include $file;
   } else {
    include "../pages/" . $defaultPage . ".php";
   }?>
   
   </div>
</div>

<?php
    /* Imports component */
    include "../components/footer.php";
?>
</div>

</body>

</html>