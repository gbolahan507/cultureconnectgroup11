<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php?page=home");
    exit();
}

$success = "";
$errors  = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CultureConnect</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

<?php
include '../db_connection.php';
include "../components/header.php";

// Fetch areas
$areas_result = mysqli_query($conn, "SELECT * FROM areas ORDER BY area_name");
$areas        = [];
while ($row = mysqli_fetch_assoc($areas_result)) {
    $areas[] = $row;
}

// Fetch subcategories for SME
$sub_result    = mysqli_query($conn, "
    SELECT ps.subcategory_id, ps.subcategory_name, psc.category_name 
    FROM product_service_subcategories ps
    JOIN product_service_categories psc ON ps.category_id = psc.category_id
    ORDER BY psc.category_name, ps.subcategory_name
");
$subcategories = [];
while ($row = mysqli_fetch_assoc($sub_result)) {
    $subcategories[] = $row;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['form_type'] ?? '';

    // ================================
    // RESIDENT REGISTRATION
    // ================================
    if ($type === 'resident') {
        $first_name  = trim($_POST['first_name'] ?? '');
        $last_name   = trim($_POST['last_name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $password    = trim($_POST['password'] ?? '');
        $dob         = trim($_POST['dob'] ?? '');
        $gender      = trim($_POST['gender'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $address     = trim($_POST['address'] ?? '');
        $area_id     = trim($_POST['area_id'] ?? '');

        // Get postcode from selected area
        $area_safe   = mysqli_real_escape_string($conn, $area_id);
        $area_row    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT postcode FROM areas WHERE area_id = '$area_safe' LIMIT 1"));
        $postcode    = $area_row['postcode'] ?? '';

        // Validation
        if (empty($first_name))  $errors[] = "First name is required.";
        if (empty($last_name))   $errors[] = "Last name is required.";
        if (empty($email))       $errors[] = "Email is required.";
        if (empty($password))    $errors[] = "Password is required.";
        if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
        
        if (empty($dob)) { $errors[] = "Date of birth is required.";
            } else { $dobDate  = new DateTime($dob);
                     $today    = new DateTime();
                     $age      = $today->diff($dobDate)->y;
        if ($age < 16) $errors[] = "You must be at least 16 years old to register.";}
        
        if (empty($gender))      $errors[] = "Please select a gender.";
        if (empty($phone))       $errors[] = "Phone number is required.";
        if (empty($address))     $errors[] = "Address is required.";
        if (empty($area_id))     $errors[] = "Please select an area.";

        // Check email not already registered
        if (empty($errors)) {
            $email_safe = mysqli_real_escape_string($conn, $email);
            $check      = mysqli_query($conn, "SELECT user_id FROM users WHERE email_address = '$email_safe' LIMIT 1");
            if (mysqli_num_rows($check) > 0) {
                $errors[] = "This email is already registered.";
            }
        }

        // Document validation
        if (!isset($_FILES['verification_doc']) || $_FILES['verification_doc']['error'] != 0) {
            $errors[] = "A verification document is required.";
        }

        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $first_safe      = mysqli_real_escape_string($conn, $first_name);
            $last_safe       = mysqli_real_escape_string($conn, $last_name);
            $phone_safe      = mysqli_real_escape_string($conn, $phone);
            $address_safe    = mysqli_real_escape_string($conn, $address);
            $postcode_safe   = mysqli_real_escape_string($conn, $postcode);
            $doc_type        = $_POST['doc_type'] ?? 'Driver_License';

            // Step 1: Insert into users
            $sql = "INSERT INTO users (password_hash, account_status, role, email_address)
                    VALUES ('$hashed_password', 'pending', 'Resident', '$email_safe')";

            if (mysqli_query($conn, $sql)) {
                $user_id = mysqli_insert_id($conn);

                // Step 2: Insert into resident_profiles
                // Trigger will auto set area_id from postcode
                $res_sql = "INSERT INTO resident_profiles 
                            (user_id, first_name, last_name, date_of_birth, gender, address, phone, postcode)
                            VALUES ('$user_id', '$first_safe', '$last_safe', '$dob', '$gender', '$address_safe', '$phone_safe', '$postcode_safe')";

                if (mysqli_query($conn, $res_sql)) {

                    // Step 3: Handle document upload
                    if (isset($_FILES['verification_doc']) && $_FILES['verification_doc']['error'] == 0) {
                        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
                        if (in_array($_FILES['verification_doc']['type'], $allowed)) {
                            $doc_name  = time() . "_" . basename($_FILES['verification_doc']['name']);
                            $doc_path  = "../uploads/verification_documents/" . $doc_name;
                            move_uploaded_file($_FILES['verification_doc']['tmp_name'], $doc_path);

                            $doc_safe  = mysqli_real_escape_string($conn, $doc_path);
                            $type_safe = mysqli_real_escape_string($conn, $doc_type);
                            mysqli_query($conn, "INSERT INTO user_documents (user_id, document_type, file_path)
                                                VALUES ('$user_id', '$type_safe', '$doc_safe')");
                        }
                    }

                    $success = "resident";
                     // check error
                     error_log("Registration successful for: " . $email);


                } else {
                    $errors[] = "Profile creation failed: " . mysqli_error($conn);
                    // Rollback user insert
                    mysqli_query($conn, "DELETE FROM users WHERE user_id = '$user_id'");
                }
            } else {
                $errors[] = "Registration failed: " . mysqli_error($conn);
            }
        }
    }

    // ================================
    // SME REGISTRATION
    // ================================
    elseif ($type === 'sme') {
        $business_name   = trim($_POST['business_name'] ?? '');
        $subcategory_id  = trim($_POST['subcategory_id'] ?? '');
        $description     = trim($_POST['description'] ?? '');
        $area_id         = trim($_POST['area_id'] ?? '');
        $phone           = trim($_POST['phone'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $password        = trim($_POST['password'] ?? '');

        // Validation
        if (empty($business_name))  $errors[] = "Business name is required.";
        if (empty($subcategory_id)) $errors[] = "Please select a business subcategory.";
        if (empty($description))    $errors[] = "Business description is required.";
        if (empty($area_id))        $errors[] = "Please select an area.";
        if (empty($phone))          $errors[] = "Phone number is required.";
        if (empty($email))          $errors[] = "Email is required.";
        if (empty($password))       $errors[] = "Password is required.";
        if (strlen($password) < 8)  $errors[] = "Password must be at least 8 characters.";

        // Check email not already registered
        if (empty($errors)) {
            $email_safe = mysqli_real_escape_string($conn, $email);
            $check      = mysqli_query($conn, "SELECT user_id FROM users WHERE email_address = '$email_safe' LIMIT 1");
            if (mysqli_num_rows($check) > 0) {
                $errors[] = "This email is already registered.";
            }
        }

        // Document validation
        if (!isset($_FILES['verification_doc']) || $_FILES['verification_doc']['error'] != 0) {
            $errors[] = "A verification document is required.";
        }

        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $bname_safe      = mysqli_real_escape_string($conn, $business_name);
            $bdesc_safe      = mysqli_real_escape_string($conn, $description);
            $bphone_safe     = mysqli_real_escape_string($conn, $phone);
            $doc_type        = $_POST['doc_type'] ?? 'Bank_Statement';

            // Step 1: Insert into users
            $sql = "INSERT INTO users (password_hash, account_status, role, email_address)
                    VALUES ('$hashed_password', 'pending', 'SME', '$email_safe')";

            if (mysqli_query($conn, $sql)) {
                $user_id = mysqli_insert_id($conn);

                // Step 2: Insert into sme_profiles
                $sme_sql = "INSERT INTO sme_profiles 
                            (user_id, business_name, description, phone, area_id, subcategory_id, approval_status)
                            VALUES ('$user_id', '$bname_safe', '$bdesc_safe', '$bphone_safe', '$area_id', '$subcategory_id', 'pending')";

                if (mysqli_query($conn, $sme_sql)) {

                    // Step 3: Handle document upload
                    if (isset($_FILES['verification_doc']) && $_FILES['verification_doc']['error'] == 0) {
                        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
                        if (in_array($_FILES['verification_doc']['type'], $allowed)) {
                            $doc_name  = time() . "_" . basename($_FILES['verification_doc']['name']);
                            $doc_path  = "../uploads/verification_documents/" . $doc_name;
                            move_uploaded_file($_FILES['verification_doc']['tmp_name'], $doc_path);

                            $doc_safe  = mysqli_real_escape_string($conn, $doc_path);
                            $type_safe = mysqli_real_escape_string($conn, $doc_type);
                            mysqli_query($conn, "INSERT INTO user_documents (user_id, document_type, file_path)
                                                VALUES ('$user_id', '$type_safe', '$doc_safe')");
                        }
                    }

                    $success = "sme";
                } else {
                    $errors[] = "SME profile creation failed: " . mysqli_error($conn);
                    mysqli_query($conn, "DELETE FROM users WHERE user_id = '$user_id'");
                }
            } else {
                $errors[] = "Registration failed: " . mysqli_error($conn);
            }
        }
    }
}
?>

   <div class="register-page-wrapper">
      <!-- Background Slideshow -->
       <div class="register-bg-slideshow">
        <div class="register-slide" style="background-image: url('../images/event1.jpg')"></div>
        <div class="register-slide" style="background-image: url('../images/event2.jpg')"></div>
        <div class="register-slide" style="background-image: url('../images/event3.jpg')"></div>
        <div class="register-slide" style="background-image: url('../images/event4.jpg')"></div>
       </div>
      
      <!-- Dark overlay so form is readable -->
       <div class="register-overlay"></div>

      <div class="register-content">

      <!-- Toggle Buttons -->
       <div class="user-type-selector" <?php echo (!empty($success)) ? 'style="display:none;"' : ''; ?>>
            <button type="button" id="btn-resident" onclick="showForm('resident')">Resident</button>
            <button type="button" id="btn-sme" onclick="showForm('sme')">SME</button>
        </div>
     
      <!-- Registration Error Message -->
       <?php if ($success === 'resident') : ?>
            <div class="alert-box success-box">
                <strong>Registration Submitted!</strong>
                <p>Thank you for registering. Your request is pending approval. You will be notified once a decision has been made.</p>
                <p><a href="../pages/login.php">Back to Login</a></p>
            </div>
        <?php elseif ($success === 'sme') : ?>
            <div class="alert-box success-box">
                <strong>Registration Submitted!</strong>
                <p>Thank you for registering your business. Your application is pending approval by the council. You will be notified once a decision has been made.</p>
                <p><a href="../pages/login.php">Back to Login</a></p>
            </div>
        <?php endif; ?>

      <!-- For Form Error Messages -->
       <?php if (!empty($errors)) : ?>
            <div class="alert-box error-box"><?= $errors[0] ?></div>
        <?php endif; ?>

      <!-- RESIDENT FORM -->
      <form id="resident-form" name="residentForm"
              action="" method="POST" enctype="multipart/form-data"
              onsubmit="return validateResident()"
              style="<?php echo ($success === 'resident' || $success === 'sme') ? 'display:none;' : 'display:block;'; ?>">

            <input type="hidden" name="form_type" value="resident">
            <h2>Resident Registration Form</h2>
            <div id="residentErrorBox" class="alert-box error-box" style="display:none;"></div>

            <div class="resident-first-name">
                <label>First Name</label>
                <input type="text" name="first_name" placeholder="Enter first name">
            </div>

            <div class="resident-last-name">
                <label>Last Name</label>
                <input type="text" name="last_name" placeholder="Enter last name">
            </div>

            <div class="resident-dob">
                <label>Date of Birth</label>
                <input type="date" name="dob" placeholder="YYYY-MM-DD" max="<?= date('Y-m-d', strtotime('-16 years')) ?>">
            </div>

            <div class="resident-gender">
                <label>Gender</label>
                <select name="gender">
                    <option value="" disabled selected>Select gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Non-binary">Non-binary</option>
                    <option value="Transgender">Transgender</option>
                    <option value="Genderqueer">Genderqueer</option>
                    <option value="Genderfluid">Genderfluid</option>
                    <option value="Agender">Agender</option>
                    <option value="Intersex">Intersex</option>
                    <option value="Other">Other</option>
                    <option value="Prefer not to say">Prefer not to say</option>
                </select>
            </div>

            <div class="resident-phone">
                <label>Phone Number</label>
                <input type="text" name="phone" placeholder="Include country code e.g. +44 7911 123456">
            </div>

            <div class="resident-email">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter email address">
            </div>

            <div class="resident-address">
                <label>Address</label>
                <input type="text" name="address" placeholder="Enter your address">
            </div>

            <div class="resident-area">
                <label>Area</label>
                <select name="area_id">
                    <option value="" disabled selected>Select your area</option>
                    <?php foreach ($areas as $area) : ?>
                        <option value="<?= $area['area_id'] ?>">
                            <?= htmlspecialchars($area['area_name']) ?> (<?= $area['postcode'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="resident-password">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password (min 8 characters)">
            </div>

            <div class="resident-confirm-password">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm your password">
            </div>

            <div class="resident-doc-type">
                <label>Verification Document Type</label>
                <select name="doc_type">
                    <option value="" disabled selected>Select document type</option>
                    <option value="Driver_License">Driver License</option>
                    <option value="Bank_Statement">Bank Statement</option>
                    <option value="Utility_Bill">Utility Bill</option>
                </select>
            </div>

            <div class="resident-doc-upload">
                <label>Upload Verification Document <small>(PDF, JPG or PNG)</small></label>
                <input type="file" name="verification_doc" accept=".pdf,.jpg,.jpeg,.png">
            </div>

            <div class="resident-submit">
                <button type="submit" class="submit-btn">Register</button>
            </div>

        </form>

      <!-- SME FORM -->
      <form id="sme-form" name="smeForm"
              action="" method="POST" enctype="multipart/form-data"
              onsubmit="return validateSME()"
              style="<?php echo ($success === 'resident' || $success === 'sme') ? 'display:none;' : 'display:none;'; ?>">

            <input type="hidden" name="form_type" value="sme">
            <h2>SME Registration Form</h2>
            <div id="smeErrorBox" class="alert-box error-box" style="display:none;"></div>

            <div class="sme-business-name">
                <label>Business Name</label>
                <input type="text" name="business_name" placeholder="Enter business name">
            </div>

            <div class="sme-subcategory">
                <label>Business Subcategory</label>
                <select name="subcategory_id" id="sme-subcategory-select">
                    <option value="" disabled selected>Select a subcategory</option>
                    <?php
                    $current_category = '';
                    foreach ($subcategories as $sub) :
                        if ($sub['category_name'] !== $current_category) {
                            if ($current_category !== '') echo '</optgroup>';
                            echo '<optgroup label="' . htmlspecialchars($sub['category_name']) . '">';
                            $current_category = $sub['category_name'];
                        }
                    ?>
                        <option value="<?= $sub['subcategory_id'] ?>">
                            <?= htmlspecialchars($sub['subcategory_name']) ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if ($current_category !== '') echo '</optgroup>'; ?>
                </select>
            </div>

            <div class="sme-description">
                <label>Business Description</label>
                <textarea name="description" placeholder="Describe your business"></textarea>
            </div>

            <div class="sme-area">
                <label>Area</label>
                <select name="area_id">
                    <option value="" disabled selected>Select your area</option>
                    <?php foreach ($areas as $area) : ?>
                        <option value="<?= $area['area_id'] ?>">
                            <?= htmlspecialchars($area['area_name']) ?> (<?= $area['postcode'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sme-phone">
                <label>Phone Number</label>
                <input type="text" name="phone" placeholder="Include country code e.g. +44 7911 123456">
            </div>

            <div class="sme-email">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter email address">
            </div>

            <div class="sme-password">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password (min 8 characters)">
            </div>

            <div class="sme-confirm-password">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm your password">
            </div>

            <div class="sme-doc-type">
                <label>Verification Document Type</label>
                <select name="doc_type">
                    <option value="" disabled selected>Select document type</option>
                    <option value="Bank_Statement">Bank Statement</option>
                    <option value="Utility_Bill">Utility Bill</option>
                </select>
            </div>

            <div class="sme-doc-upload">
                <label>Upload Verification Document <small>(PDF, JPG or PNG)</small></label>
                <input type="file" name="verification_doc" accept=".pdf,.jpg,.jpeg,.png">
            </div>

            <div class="sme-submit">
                <button type="submit" class="submit-btn">Register</button>
            </div>

        </form>
             
      </div>

    </div>
  <?php include "../components/footer.php"; ?>

  <script>
function showForm(type) {
    document.getElementById('resident-form').style.display = (type === 'resident') ? 'block' : 'none';
    document.getElementById('sme-form').style.display      = (type === 'sme') ? 'block' : 'none';

    document.getElementById('btn-resident').classList.toggle('active', type === 'resident');
    document.getElementById('btn-sme').classList.toggle('active', type === 'sme');
}

function validateResident() {
    var errorBox = document.getElementById('residentErrorBox');
    var form     = document.forms['residentForm'];
    errorBox.style.display = 'none';

    function showError(msg, field) {
        errorBox.style.display = 'block';
        errorBox.innerHTML     = msg;
        if (field) field.focus();
        return false;
    }

    if (form.first_name.value == '')      return showError('Please enter your first name.', form.first_name);
    if (form.last_name.value == '')       return showError('Please enter your last name.', form.last_name);
    
    if (form.dob.value == '') return showError('Please enter your date of birth.', form.dob);
            const dob    = new Date(form.dob.value);
            const today  = new Date();
            let age      = today.getFullYear() - dob.getFullYear();
            const month  = today.getMonth() - dob.getMonth();
    if (month < 0 || (month === 0 && today.getDate() < dob.getDate())) age--;
    if (age < 16) return showError('You must be at least 16 years old to register.', form.dob);
    
    if (form.gender.value == '')          return showError('Please select your gender.', form.gender);
    if (form.phone.value == '')           return showError('Please enter your phone number.', form.phone);
    if (form.email.value == '')           return showError('Please enter your email address.', form.email);
    if (form.address.value == '')         return showError('Please enter your address.', form.address);
    if (form.area_id.value == '')         return showError('Please select your area.', form.area_id);
    if (form.password.value == '')        return showError('Please enter a password.', form.password);
    if (form.password.value.length < 8)  return showError('Password must be at least 8 characters.', form.password);
    if (form.confirm_password.value !== form.password.value) return showError('Passwords do not match.', form.confirm_password);
    if (form.doc_type.value == '')        return showError('Please select a verification document type.', form.doc_type);
    if (form.verification_doc.value == '') return showError('Please upload a verification document.', form.verification_doc);

    return true;
}

function validateSME() {
    var errorBox = document.getElementById('smeErrorBox');
    var form     = document.forms['smeForm'];
    errorBox.style.display = 'none';

    function showError(msg, field) {
        errorBox.style.display = 'block';
        errorBox.innerHTML     = msg;
        if (field) field.focus();
        return false;
    }

    if (form.business_name.value == '')   return showError('Please enter your business name.', form.business_name);
    if (form.subcategory_id.value == '')  return showError('Please select a business subcategory.', form.subcategory_id);
    if (form.description.value == '')     return showError('Please enter a business description.', form.description);
    if (form.area_id.value == '')         return showError('Please select your area.', form.area_id);
    if (form.phone.value == '')           return showError('Please enter your phone number.', form.phone);
    if (form.email.value == '')           return showError('Please enter your email address.', form.email);
    if (form.password.value == '')        return showError('Please enter a password.', form.password);
    if (form.password.value.length < 8)  return showError('Password must be at least 8 characters.', form.password);
    if (form.confirm_password.value !== form.password.value) return showError('Passwords do not match.', form.confirm_password);
    if (form.doc_type.value == '')        return showError('Please select a verification document type.', form.doc_type);
    if (form.verification_doc.value == '') return showError('Please upload a verification document.', form.verification_doc);

    return true;
}

     // Set resident as default active tab on page load
     document.addEventListener('DOMContentLoaded', () => showForm('resident'));
  </script>

</body>
</html>