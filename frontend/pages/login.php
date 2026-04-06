<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Handle redirect before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php?page=home");
    exit();
}

$errors = [];
$post_email = '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CultureConnect</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

<?php 
// database and Header brings in $conn and session_start
include '../db_connection.php';
include '../components/header.php'; 

// Now $conn is available, handle form submission

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $post_email = trim($_POST['email'] ?? '');
    $password   = trim($_POST['password'] ?? '');

    if (empty($post_email)) $errors[] = "Email is required.";
    if (empty($password))   $errors[] = "Password is required.";

    if (empty($errors)) {
        $email_safe = mysqli_real_escape_string($conn, $post_email);
        $sql        = "SELECT * FROM users WHERE email_address = '$email_safe' LIMIT 1";
        $result     = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user['password_hash'])) {
                $user_id = $user['user_id'];
                $role    = $user['role'];

                // Check account status
                if ($user['account_status'] !== 'approved') {
                    if ($user['account_status'] === 'pending') {
                        $errors[] = "Your account is currently pending approval. You will be notified once a decision has been made.";
                    } elseif ($user['account_status'] === 'rejected') {
                        $errors[] = "Your account application was not approved. Please contact the council for more information.";
                    } else {
                        $errors[] = "Your account status could not be verified. Please contact support.";
                    }
                } else {
                    // Set core session variables
                    $_SESSION['user_id']    = $user['user_id'];
                    $_SESSION['user_email'] = $user['email_address'];
                    $_SESSION['user_role']  = $user['role'];

                    // Update last login
                    mysqli_query($conn, "UPDATE users SET last_login = NOW() WHERE user_id = '$user_id'");

                    // Fetch extra details based on role
                    if ($role === 'SME') {
                        $sme_result = mysqli_query($conn, "SELECT * FROM sme_profiles WHERE user_id = '$user_id' LIMIT 1");
                        if ($sme_result && mysqli_num_rows($sme_result) === 1) {
                            $sme = mysqli_fetch_assoc($sme_result);
                            $_SESSION['sme_id']        = $sme['sme_id'];
                            $_SESSION['business_name'] = $sme['business_name'];
                            $_SESSION['sme_status']    = $sme['approval_status'];
                        }
                    } elseif ($role === 'Resident') {
                        $res_result = mysqli_query($conn, "SELECT * FROM resident_profiles WHERE user_id = '$user_id' LIMIT 1");
                        if ($res_result && mysqli_num_rows($res_result) === 1) {
                            $resident = mysqli_fetch_assoc($res_result);
                            $_SESSION['first_name'] = $resident['first_name'];
                            $_SESSION['last_name']  = $resident['last_name'];
                        }
                    } elseif ($role === 'Council Member' || $role === 'Council Administrator') {
                        // Fetch from resident_profiles since council members
                        // profiles are stored there
                        $res_result = mysqli_query($conn, "SELECT * FROM resident_profiles WHERE user_id = '$user_id' LIMIT 1");
                        if ($res_result && mysqli_num_rows($res_result) === 1) {
                            $resident = mysqli_fetch_assoc($res_result);
                            $_SESSION['first_name'] = $resident['first_name'];
                            $_SESSION['last_name']  = $resident['last_name'];
                        }
                    }

                    // Redirect to dashboard
                    header("Location: dashboard.php?page=home");
                    exit();
                }
            } else {
                $errors[] = "Incorrect email or password.";
            }
        } else {
            $errors[] = "Incorrect email or password.";
        }
    }
}  

?>

<!-- Login Page -->

<div class="login-page-wrapper">
<!-- Background Slideshow uses same styling with bg in register-page -->
    <div class="login-bg-slideshow">
        <div class="login-slide" style="background-image: url('../images/event1.jpg')"></div>
        <div class="login-slide" style="background-image: url('../images/event2.jpg')"></div>
        <div class="login-slide" style="background-image: url('../images/event3.jpg')"></div>
        <div class="login-slide" style="background-image: url('../images/event4.jpg')"></div>
    </div>

    <!-- Dark overlay -->
    <div class="login-overlay"></div>

<!-- Login form Content -->
<div class="login-content">
    <div class="login-card">

        <h2>Welcome Back</h2>
        <p>Login to your CultureConnect account</p>

        <!-- Error Messages -->
             <?php if (!empty($errors)) : ?>
                <div class="alert-box error-box">
                    <?php echo $errors[0]; ?>
                </div>
             <?php endif; ?>

        <!-- Login Form -->
        <form id="login-form" action="" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?php echo htmlspecialchars($post_email); ?>"
                       placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Enter your password" required>
            </div>

            <div class="form-group">
                <button type="submit" class="submit-btn">Login</button>
            </div>

            <p class="login-register-link">
                Don't have an account? 
                <a href="../pages/register.php">Register here</a>
            </p>
        </form>

    </div>
</div>
</div>

<?php include '../components/footer.php'; ?>

</body>
</html>