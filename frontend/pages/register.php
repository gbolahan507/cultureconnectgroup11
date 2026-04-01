<?php
// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$success = "";
$errors = [];

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>register - CultureConnect</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>

  <?php include "../components/header.php";
  // FORM SUBMISSION
  //(1)For Residents
      if ($_SERVER["REQUEST_METHOD"] == "POST") {
          $type = $_POST['form_type'] ?? '';

          if ($type === 'resident') {
              $given_name      = trim($_POST['given_name'] ?? '');
              $family_name     = trim($_POST['family_name'] ?? '');
              $email           = trim($_POST['email'] ?? '');
              $confirm_email   = trim($_POST['confirm_email'] ?? '');
              $password        = trim($_POST['password'] ?? '');
              $confirm_password = trim($_POST['confirm_password'] ?? '');
              $dob             = trim($_POST['dob'] ?? '');
              $gender          = trim($_POST['gender'] ?? '');
              $phone           = trim($_POST['phone'] ?? '');
              $address         = trim($_POST['address'] ?? '');
              $postcode        = trim($_POST['postcode'] ?? '');
              $area_id         = trim($_POST['area_id'] ?? '');

           // check that user is not already registered
           $email_safe = mysqli_real_escape_string($conn, $email);
           $check = mysqli_query($conn, "SELECT user_ref_no FROM users WHERE email = '$email_safe' LIMIT 1");
           if (mysqli_num_rows($check) > 0) {
            $errors[] = "Email is already registered.";
             }

          // if user does not exist, hash password and
          if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

          // Step 1: Insert new resident into resident table
            $given_safe   = mysqli_real_escape_string($conn, $given_name);
            $family_safe  = mysqli_real_escape_string($conn, $family_name);
            $phone_safe   = mysqli_real_escape_string($conn, $phone);
            $address_safe = mysqli_real_escape_string($conn, $address);
            $post_code_safe = mysqli_real_escape_string($conn, $postcode);

            $res_sql = "INSERT INTO resident_profiles (given_name, family_name, dob, gender, address, post_code, area_id, phone, approval_status)
                VALUES ('$given_safe', '$family_safe', '$dob', '$gender', '$address_safe', '$post_code_safe', '$area_id', '$phone_safe', 'pending')";
          
          // Step 2: Insert into users table
          if (mysqli_query($conn, $res_sql)) {
                $profile_id = mysqli_insert_id($conn);

          $sql = "INSERT INTO users (name, email, password, role_id, address, area_id)
                        VALUES ('$given_safe $family_safe',
                                '$email_safe',
                                '$hashed_password',
                                 1,
                                '$address_safe',
                                '$area_id' )";
              
          // Step 3: Generate and update User Code RES-**** E.g RES-0001
          if (mysqli_query($conn, $sql)) {
                    $user_ref_no = mysqli_insert_id($conn);

                    // Generate code
                    $user_code = 'RES-' . str_pad($user_ref_no, 4, '0', STR_PAD_LEFT);
                    mysqli_query($conn, "UPDATE users SET user_code = '$user_code' WHERE user_ref_no = '$user_ref_no'");

                    // Update resident_profiles with user_ref_no
                    mysqli_query($conn, "UPDATE resident_profiles SET user_ref_no = '$user_ref_no' WHERE profile_id = '$profile_id'");

           // Step 4: Handle verification document upload
           if (isset($_FILES['verification_doc']) && $_FILES['verification_doc']['error'] == 0) {
                        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
                        if (in_array($_FILES['verification_doc']['type'], $allowed)) {
                            $doc_name = time() . "_" . basename($_FILES['verification_doc']['name']);
                            $doc_path = "../uploads/verification_documents/" . $doc_name;
                            move_uploaded_file($_FILES['verification_doc']['tmp_name'], $doc_path);
                            $doc_type = mysqli_real_escape_string($conn, $_POST['doc_type'] ?? 'ID');
                            mysqli_query($conn, "INSERT INTO verification_documents 
                                (user_ref_no, user_type, document_type, document_file)
                                VALUES ('$user_ref_no', 'Resident', '$doc_type', '$doc_name')");
                        }
            }
            $success = "resident";
                } 
          else {
                    $errors[] = "Registration failed: " . mysqli_error($conn);
                }
            } 
          else {
                $errors[] = "Profile creation failed: " . mysqli_error($conn);
            }
        }

    }

  //(2)For SMEs
  elseif ($type === 'sme') {
        $business_name    = trim($_POST['business_name'] ?? '');
        $category         = trim($_POST['category'] ?? '');
        $new_category     = trim($_POST['new_category'] ?? '');
        $business_reg_no  = trim($_POST['business_reg_no'] ?? '');
        $address          = trim($_POST['address'] ?? '');
        $postcode         = trim($_POST['postcode'] ?? '');
        $area_id          = trim($_POST['area_id'] ?? '');
        $phone            = trim($_POST['phone'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $password         = trim($_POST['password'] ?? '');
        $description      = trim($_POST['description'] ?? '');

        // Handle new category if Other was selected
        if ($category === 'other' && !empty($new_category)) {
            $new_cat_safe = mysqli_real_escape_string($conn, $new_category);
            mysqli_query($conn, "INSERT INTO business_categories (category_name) VALUES ('$new_cat_safe')");
            $category = mysqli_insert_id($conn);
        }

        // check that user is not already registered
        $email_safe = mysqli_real_escape_string($conn, $email);
        $check = mysqli_query($conn, "SELECT user_ref_no FROM users WHERE email = '$email_safe' LIMIT 1");
        if (mysqli_num_rows($check) > 0) {
            $errors[] = "Email is already registered.";
        }

          // if user does not exist, hash password
        if (empty($errors)) {
            $hashed_password  = password_hash($password, PASSWORD_DEFAULT);
            $bname_safe       = mysqli_real_escape_string($conn, $business_name);
            $breg_safe        = mysqli_real_escape_string($conn, $business_reg_no);
            $bloc_safe        = mysqli_real_escape_string($conn, $address);
            $post_code_safe    = mysqli_real_escape_string($conn, $postcode);
            $bdesc_safe       = mysqli_real_escape_string($conn, $description);
            $bphone_safe      = mysqli_real_escape_string($conn, $phone);

          // Step 1: Insert new sme into sme_profiles table
            $sme_sql = "INSERT INTO sme_profiles (business_name, business_reg_no, business_description, category, address, post_code, area_id, phone, approval_status)
                VALUES ('$bname_safe', '$breg_safe', '$bdesc_safe', '$category', '$bloc_safe', '$post_code_safe', '$area_id', 
                        '$bphone_safe', 'pending')";

          // Step 2: Insert into users table
          if (mysqli_query($conn, $sme_sql)) {
                $sme_id = mysqli_insert_id($conn);

              $sql = "INSERT INTO users (name, email, password, role_id, address, area_id)
                      VALUES ('$bname_safe', '$email_safe', '$hashed_password', 2, '$bloc_safe', '$area_id')";

          // Step 3: Generate and update User Code SME-**** E.g SME-0001
          if (mysqli_query($conn, $sql)) {
              $user_ref_no = mysqli_insert_id($conn);

                // Generate user code SME-0001
                    $user_code = 'SME-' . str_pad($user_ref_no, 4, '0', STR_PAD_LEFT);
                    mysqli_query($conn, "UPDATE users SET user_code = '$user_code' WHERE user_ref_no = '$user_ref_no'");

                // Update sme_profiles with user_ref_no
                    mysqli_query($conn, "UPDATE sme_profiles SET user_ref_no = '$user_ref_no' WHERE sme_id = '$sme_id'");

          // Step 4: Handle verification document upload
          if (isset($_FILES['verification_doc']) && $_FILES['verification_doc']['error'] == 0) {
                        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
                        if (in_array($_FILES['verification_doc']['type'], $allowed)) {
                            $doc_name = time() . "_" . basename($_FILES['verification_doc']['name']);
                            $doc_path = "../uploads/verification_documents/" . $doc_name;
                            move_uploaded_file($_FILES['verification_doc']['tmp_name'], $doc_path);
                            $doc_type = mysqli_real_escape_string($conn, $_POST['doc_type'] ?? 'Business Document');
                            mysqli_query($conn, "INSERT INTO verification_documents 
                                (user_ref_no, user_type, document_type, document_file)
                                VALUES ('$user_ref_no', 'SME', '$doc_type', '$doc_name')");
                        }
                    }
             $success = "sme";
        }
      else {
                    $errors[] = "Registration failed: " . mysqli_error($conn);
                }
            } else {
                $errors[] = "SME profile creation failed: " . mysqli_error($conn);
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
       <div class="user-type-selector">
        <button type="button" onclick="showForm('resident')">Resident</button>
        <button type="button" onclick="showForm('sme')">SME</button>
      </div>
     
      <!-- Registration Message -->
       <?php if ($success === 'resident') : ?>
           <div class="alert-box success-box">
            <strong>Registration Submitted!</strong>
            <p>Thank you for registering. Your request is currently pending approval. 
            You will be notified once your request has been reviewed and a decision has been made.</p>
            <p><a href="../pages/login.php">Back to Login</a></p>
           </div>
      
      <?php elseif ($success === 'sme') : ?>
          <div class="alert-box success-box">
            <strong>Registration Submitted!</strong>
            <p>Thank you for registering your business. Your request is currently pending approval 
            by the council. You will be notified once your application has been reviewed and 
            a decision has been made.</p>
            <p><a href="../pages/login.php">Back to Login</a></p>
          </div>
      <?php endif; ?>

      <!-- For Form Error Messages -->
       <?php if (!empty($errors)) : ?>
            <div class="alert-box error-box">
               <?php echo $errors[0]; ?>
            </div>
       <?php endif; ?>

      <!-- RESIDENT FORM -->
      <form id="resident-form" name="residentForm"
            action="" method="POST" enctype="multipart/form-data"
             
            style="display:block;">
            <input type="hidden" name="form_type" value="resident">

          <h2>Resident Registration Form</h2>
          <div id="residentErrorBox" class="alert-box error-box" style="display:none;"></div>
 
          <div class="resident-given-name">
            <label>Given Name</label>
            <input type="text" name="given_name" placeholder="Enter given name">
        </div>

        <div class="resident-family-name">
            <label>Family Name</label>
            <input type="text" name="family_name" placeholder="Enter family name">
        </div>

        <div class="resident-dob">
            <label>Date of Birth</label>
            <input type="text" name="dob" placeholder="YYYY-MM-DD" 
                   max="<?php echo date('Y-m-d'); ?>">
        </div>

        <div class="resident-gender">
            <label>Gender</label>
            <select name="gender">
                <option value="" disabled selected>Select gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
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

        <div class="resident-confirm-email">
            <label>Confirm Email</label>
            <input type="email" name="confirm_email" placeholder="Confirm email address">
        </div>

        <div class="resident-address">
            <label>Address</label>
            <input type="text" name="address" placeholder="Enter your address">
        </div>

        <div class="resident-postcode">
            <label>Postcode</label>
            <input type="text" name="postcode" placeholder="Enter in capitals e.g. AL10 9AB">
        </div>

        <div class="resident-area">
            <label>Area</label>
            <select name="area_id">
                <option value="" disabled selected>Select your area</option>
                <?php
                $areas_result = mysqli_query($conn, "SELECT * FROM areas");
                while ($area = mysqli_fetch_assoc($areas_result)) {
                    echo "<option value='" . $area['area_id'] . "'>" . $area['area_name'] . "</option>";
                }
                ?>
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
                <option value="ID">ID (Passport, Driving Licence, National ID)</option>
                <option value="Bank Statement">Bank Statement (address confirmation)</option>
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
            style="display:none;">
            <input type="hidden" name="form_type" value="sme">

            <h2>SME Registration Form</h2>

        <div id="smeErrorBox" class="alert-box error-box" style="display:none;"></div>

        <div class="sme-business-name">
            <label>Business Name</label>
            <input type="text" name="business_name" placeholder="Enter business name">
        </div>
        
        <div class="sme-reg-number">
            <label>Business Registration Number</label>
            <input type="text" name="business_reg_no" placeholder="Enter registration number - BN 123">
        </div>

        <div class="sme-description">
            <label>Business Description</label>
            <textarea name="description" placeholder="Describe your business"></textarea>
        </div>

        <div class="sme-category">
                <label>Business Category</label>
                <select name="category" id="sme-category-select" onchange="showCategoryDescription()">
                <option value="" disabled selected>Select a category</option>
                <?php
                    $categories_result = mysqli_query($conn, "SELECT * FROM business_categories");
                    while ($cat = mysqli_fetch_assoc($categories_result)) {
                     echo "<option value='" . $cat['category_id'] . "' 
                    data-description='" . htmlspecialchars($cat['description']) . "'>" 
                     . htmlspecialchars($cat['category_name']) . 
                    "</option>";}
                ?>
                <option value="other">Other (Add New)</option>
              </select>
          <!-- Business Categories Description box -->
          <div id="sme-category-description" class="category-description-box" style="display:none;"></div>
       </div>

        <div class="sme-new-category" id="newCategoryBox" style="display:none;">
            <label>New Category Name</label>
            <input type="text" name="new_category" placeholder="Enter new category name">
        </div>

        <div class="sme-address">
            <label>Business Address</label>
            <input type="text" name="address" placeholder="Enter business address">
        </div>

        <div class="sme-postcode">
            <label>Postcode</label>
            <input type="text" name="postcode" placeholder="Enter in capitals e.g. AL10 9AB">
        </div>

        <div class="sme-area">
            <label>Area</label>
            <select name="area_id">
                <option value="" disabled selected>Select your area</option>
                <?php
                $areas_result2 = mysqli_query($conn, "SELECT * FROM areas");
                while ($area = mysqli_fetch_assoc($areas_result2)) {
                    echo "<option value='" . $area['area_id'] . "'>" . $area['area_name'] . "</option>";
                }
                ?>
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

        <div class="sme-confirm-email">
            <label>Confirm Email</label>
            <input type="email" name="confirm_email" placeholder="Confirm email address">
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
                <option value="Business Registration Document">Business Registration Document</option>
                <option value="Bank Statement">Bank Statement</option>
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
        document.getElementById('resident-form').style.display =
        (type === 'resident') ? 'block' : 'none';
        document.getElementById('sme-form').style.display =
        (type === 'sme') ? 'block' : 'none';
     }

    function toggleNewCategory(select) {
        var newCategoryBox = document.getElementById('newCategoryBox');
        newCategoryBox.style.display = (select.value === 'other') ? 'block' : 'none';
     }

     function validateResident() {
        var errorBox = document.getElementById('residentErrorBox');
        var form = document.forms['residentForm'];
        errorBox.style.display = 'none';
        errorBox.innerHTML = '';

          function showError(msg, field) {
          errorBox.style.display = 'block';
          errorBox.innerHTML = msg;
       
          if (field) field.focus();
          return false; }

       var emailRegex = /^[A-Za-z0-9._]+\@[A-Za-z]+\.[A-Za-z]{2,5}$/;
       var dobRegex   = /^\d{4}-\d{2}-\d{2}$/;

          if (form.given_name.value == '')
          return showError('Please enter your given name.', form.given_name);

          if (form.family_name.value == '')
          return showError('Please enter your family name.', form.family_name);

          if (form.dob.value == '')
          return showError('Please enter your date of birth.', form.dob);

          if (!dobRegex.test(form.dob.value))
          return showError('Date of birth must be in YYYY-MM-DD format.', form.dob);

          if (form.gender.value == '')
          return showError('Please select your gender.', form.gender);

          if (form.phone.value == '')
          return showError('Please enter your phone number.', form.phone);

          if (form.email.value == '')
          return showError('Please enter your email address.', form.email);

          if (!emailRegex.test(form.email.value))
          return showError('Please enter a valid email address.', form.email);

          if (form.confirm_email.value != form.email.value)
          return showError('Email addresses do not match.', form.confirm_email);

          if (form.address.value == '')
          return showError('Please enter your address.', form.address);

          if (form.postcode.value == '')
          return showError('Please enter your postcode.', form.postcode);

          if (form.area_id.value == '')
          return showError('Please select your area.', form.area_id);

          if (form.password.value == '')
          return showError('Please enter a password.', form.password);

          if (form.password.value.length < 8)
          return showError('Password must be at least 8 characters.', form.password);

          if (form.confirm_password.value != form.password.value)
          return showError('Passwords do not match.', form.confirm_password);

          if (form.doc_type.value == '')
          return showError('Please select a verification document type.', form.doc_type);

          if (form.verification_doc.value == '')
          return showError('Please upload a verification document.', form.verification_doc);

        return true;
       }

      function validateSME() {
         var errorBox = document.getElementById('smeErrorBox');
         var form = document.forms['smeForm'];
         errorBox.style.display = 'none';
         errorBox.innerHTML = '';

              function showError(msg, field) {
              errorBox.style.display = 'block';
              errorBox.innerHTML = msg;
              if (field) field.focus();
              return false;}

        var emailRegex = /^[A-Za-z0-9._]+\@[A-Za-z]+\.[A-Za-z]{2,5}$/;

           if (form.business_name.value == '')
           return showError('Please enter your business name.', form.business_name);

           if (form.category.value == '')
           return showError('Please select a business category.', form.category);

           if (form.category.value === 'other' && form.new_category.value == '')
           return showError('Please enter a new category name.', form.new_category);

           if (form.business_reg_no.value == '')
           return showError('Please enter your business registration number.', form.business_reg_no);

           if (form.description.value == '')
           return showError('Please enter a business description.', form.description);

           if (form.address.value == '')
           return showError('Please enter your business address.', form.address);

           if (form.postcode.value == '')
           return showError('Please enter your postcode.', form.postcode);

           if (form.area_id.value == '')
           return showError('Please select your area.', form.area_id);

           if (form.phone.value == '')
           return showError('Please enter your phone number.', form.phone);

           if (form.email.value == '')
           return showError('Please enter your email address.', form.email);

           if (!emailRegex.test(form.email.value))
           return showError('Please enter a valid email address.', form.email);

          if (form.confirm_email.value != form.email.value)
          return showError('Email addresses do not match.', form.confirm_email);

          if (form.password.value == '')
          return showError('Please enter a password.', form.password);

          if (form.password.value.length < 8)
          return showError('Password must be at least 8 characters.', form.password);

          if (form.confirm_password.value != form.password.value)
          return showError('Passwords do not match.', form.confirm_password);

         if (form.doc_type.value == '')
         return showError('Please select a verification document type.', form.doc_type);

         if (form.verification_doc.value == '')
         return showError('Please upload a verification document.', form.verification_doc);

      return true;
    }
  
  function showCategoryDescription() {
    const select      = document.getElementById('sme-category-select');
    const descBox     = document.getElementById('sme-category-description');
    const selectedOption = select.options[select.selectedIndex];
    const description = selectedOption.getAttribute('data-description');

    if (description && description.trim() !== '' && select.value !== 'other') {
        descBox.style.display = 'block';
        descBox.innerText = description;
    } else {
        descBox.style.display = 'none';
    }
    }  

  </script>

</body>
</html>