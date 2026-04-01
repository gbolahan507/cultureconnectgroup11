<?php
// Handle redirect before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_ref_no'])) {
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
// Header brings in $conn and session_start
include '../components/header.php'; 

// Now $conn is available, handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $post_email = trim($_POST['email'] ?? '');
    $password   = trim($_POST['password'] ?? '');

    // Validation
    if (empty($post_email)) {
        $errors[] = "Email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        $email_safe = mysqli_real_escape_string($conn, $post_email);
        $sql = "SELECT * FROM users WHERE email = '$email_safe' LIMIT 1";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user['password'])) {
                $user_ref_no = $user['user_ref_no'];
                $role_id     = $user['role_id'];
                $status      = null;

                // Check approval status with role id
                if ($role_id == 1) {
                // Resident — check resident_profiles
                $status_result = mysqli_query($conn, "SELECT approval_status FROM resident_profiles 
                    WHERE user_ref_no = '$user_ref_no' LIMIT 1");
                if ($row = mysqli_fetch_assoc($status_result)) {
                    $status = $row['approval_status'];
                } }

                elseif ($role_id == 2) {
                // SME — check sme_profiles
                $status_result = mysqli_query($conn, "SELECT approval_status FROM sme_profiles 
                    WHERE user_ref_no = '$user_ref_no' LIMIT 1");
                if ($row = mysqli_fetch_assoc($status_result)) {
                    $status = $row['approval_status'];
                } }
 
                elseif ($role_id == 3 || $role_id == 4) {
                // Council Member and Admin — no profile table, always approved
                $status = 'approved';
                }
                
                // Set core session variables
                if ($status === 'approved') {
                    $_SESSION['user_ref_no'] = $user['user_ref_no'];
                    $_SESSION['user_name']   = $user['name'];
                    $_SESSION['user_email']  = $user['email'];
                    $_SESSION['user_code']   = $user['user_code'];
                    $_SESSION['role_id']     = $user['role_id'];

                // Set role name
                $roles = [
                    1 => 'Resident',
                    2 => 'SME',
                    3 => 'Council_member',
                    4 => 'Council Administrator'];
                $_SESSION['user_role'] = $roles[$user['role_id']] ?? 'Unknown';

                // Fetch extra details based on role
                if ($role_id == 2) {
                    $sme_sql = "SELECT * FROM sme_profiles WHERE user_ref_no = '$user_ref_no' LIMIT 1";
                    $sme_result = mysqli_query($conn, $sme_sql);
                    if ($sme_result && mysqli_num_rows($sme_result) === 1) {
                        $sme = mysqli_fetch_assoc($sme_result);
                        $_SESSION['sme_id']        = $sme['sme_id'];
                        $_SESSION['business_name'] = $sme['business_name'];
                    }
                }     
                elseif ($role_id == 1) {
                    $res_sql = "SELECT * FROM resident_profiles WHERE user_ref_no = '$user_ref_no' LIMIT 1";
                    $res_result = mysqli_query($conn, $res_sql);
                    if ($res_result && mysqli_num_rows($res_result) === 1) {
                        $resident = mysqli_fetch_assoc($res_result);
                        $_SESSION['given_name']  = $resident['given_name'];
                        $_SESSION['family_name'] = $resident['family_name'];
                    }
                }

                   // Redirect to dashboard
                    header("Location: dashboard.php?page=home");
                    exit();
                }

            elseif ($status == 'pending') {
                $errors[] = "Your account is currently pending approval. You will be notified once a decision has been made.";
            }
            elseif ($status == 'rejected') {
            $errors[] = "Your account application was not approved. Please contact the council for more information.";
            }
            else { $errors[] = "Your account status could not be verified. Please contact support.";} 
            }
            else { $errors[] = "Incorrect email or password.";} 
            }
            else{$errors[] = "Incorrect email or password.";}
    }
}
?>
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
                <strong>Please fix the following:</strong>
                <ul>
                    <?php foreach ($errors as $error) : ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
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