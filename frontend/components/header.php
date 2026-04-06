<header>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);

// Check if user is logged in based on user_role
$logged_in = isset($_SESSION['user_id']);

// include database connection
include '../db_connection.php';

?>

<div class="header-container">
    <div class="logo-section">
        <img src="../images/logo.png" alt="CultureConnect Logo" class="logo">
        <h2>CultureConnect</h2>
    </div>

    <!-- Navigation menu -->
    <nav class="nav-menu">
        <ul>
            <!-- Common links always shown -->
            <li>
                <a href="../pages/index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
                    Home
                </a>
            </li>

            <li>
                <a href="../pages/browse.php" class="<?= $current_page == 'browse.php' ? 'active' : '' ?>">
                    Browse
                </a>
            </li>

            <!-- Conditional links based on login -->
            <?php if (!$logged_in): ?>
                <li>
                    <a href="../pages/login.php" class="<?= $current_page == 'login.php' ? 'active' : '' ?>">
                        Login
                    </a>
                </li>
                <li>
                    <a href="../pages/register.php" class="<?= $current_page == 'register.php' ? 'active' : '' ?>">
                        Register
                    </a>
                </li>
            <?php else: ?>
                <li>
                    <a href="../pages/dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="../pages/logout.php">Logout</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
</header>