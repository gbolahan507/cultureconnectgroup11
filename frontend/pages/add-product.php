<?php
// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$allowedRoles = ['SME'];

// Check if user is logged in AND has the correct role
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: ../pages/dashboard.php?page=home");
    exit();
}

$user_ref_no = $_SESSION['user_ref_no'];
$business_name = $_SESSION['business_name'] ?? '';

$errors  = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title             = trim($_POST['title'] ?? '');
    $caption           = trim($_POST['caption'] ?? '');
    $description       = trim($_POST['description'] ?? '');
    $type              = trim($_POST['type'] ?? '');
    $category_id       = trim($_POST['category_id'] ?? '');
    $subcategory_id    = trim($_POST['subcategory_id'] ?? '');
    $cultural_benefits = trim($_POST['cultural_benefits'] ?? '');
    $price             = trim($_POST['price'] ?? '');

    // Validation
    if (empty($title))             $errors[] = "Title is required.";
    if (empty($caption))           $errors[] = "Caption is required.";
    if (empty($description))       $errors[] = "Description is required.";
    if (empty($type))              $errors[] = "Please select product or service.";
    if (empty($category_id))       $errors[] = "Please select a category.";
    if (empty($subcategory_id))    $errors[] = "Please select a subcategory.";
    if (empty($cultural_benefits)) $errors[] = "Cultural benefits are required.";
    if (empty($price))             $errors[] = "Price is required.";

    // Image validation
    // Primary image
$primary_image = "";
if (!isset($_FILES['primary_image']) || $_FILES['primary_image']['error'] != 0) {
    $errors[] = "A primary product image is required.";
} else {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($_FILES['primary_image']['type'], $allowed_types)) {
        $errors[] = "Primary image must be JPG, PNG, GIF or WEBP format.";
    } else {
        $primary_image = time() . "_" . basename($_FILES['primary_image']['name']);
        $upload_path   = "../uploads/listings_images" . $primary_image;
        move_uploaded_file($_FILES['primary_image']['tmp_name'], $upload_path);
    }
}

if (empty($errors)) {
    $title             = mysqli_real_escape_string($conn, $title);
    $caption           = mysqli_real_escape_string($conn, $caption);
    $description       = mysqli_real_escape_string($conn, $description);
    $type              = mysqli_real_escape_string($conn, $type);
    $cultural_benefits = mysqli_real_escape_string($conn, $cultural_benefits);
    $price             = mysqli_real_escape_string($conn, $price);

    // Insert listing with primary image
    $sql = "INSERT INTO listings 
            (user_ref_no, title, caption, description, type, category_id, subcategory_id, cultural_benefits, price, image, status)
            VALUES 
            ('$user_ref_no', '$title', '$caption', '$description', '$type', '$category_id', '$subcategory_id', '$cultural_benefits', '$price', '$primary_image', 'inactive')";

    if (mysqli_query($conn, $sql)) {
        $listing_id = mysqli_insert_id($conn);

        // Save primary image to listing_images as is_primary = 1
        $primary_safe = mysqli_real_escape_string($conn, $primary_image);
        mysqli_query($conn, "INSERT INTO listing_images (listing_id, image_url, is_primary) 
                             VALUES ('$listing_id', '$primary_safe', 1)");

        // Handle additional images
        if (isset($_FILES['additional_images']) && !empty($_FILES['additional_images']['name'][0])) {
            $allowed_types  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $uploaded_count = 0;

            foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                if ($uploaded_count >= 4) break; // max 4 additional images

                $file_type = $_FILES['additional_images']['type'][$key];
                $error     = $_FILES['additional_images']['error'][$key];

                if ($error == 0 && in_array($file_type, $allowed_types)) {
                    $add_image_name = time() . $key . "_" . basename($_FILES['additional_images']['name'][$key]);
                    $add_upload_path = "../uploads/listings_images" . $add_image_name;
                    move_uploaded_file($tmp_name, $add_upload_path);

                    $add_image_safe = mysqli_real_escape_string($conn, $add_image_name);
                    mysqli_query($conn, "INSERT INTO listing_images (listing_id, image_url, is_primary) 
                                       VALUES ('$listing_id', '$add_image_safe', 0)");
                    $uploaded_count++;
                }
            }
        }

        $success = "Listing added successfully! You can publish it from Manage Listings.";
    } else {
        $errors[] = "Database error: " . mysqli_error($conn);
    }
}
}

// Fetch all categories with type for JavaScript
$cat_result = mysqli_query($conn, "SELECT * FROM product_service_category ORDER BY type, category_name");
$all_categories = [];
while ($row = mysqli_fetch_assoc($cat_result)) {
    $all_categories[] = $row;
}

// Fetch all subcategories for JavaScript
$sub_result = mysqli_query($conn, "SELECT * FROM product_service_subcategory ORDER BY category_id, subcategory_name");
$all_subcategories = [];
while ($row = mysqli_fetch_assoc($sub_result)) {
    $all_subcategories[] = $row;
}

?>

<div class="add-product-page">
    <?php
       $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
        </svg>';
       $title = "Add New Listing";
       $subtitle = "Product and Service.";
       include '../components/section_header.php';
     ?> 

    <!-- Success Message -->
    <?php if (!empty($success)) : ?>
        <div class="alert-box success-box"><?= $success ?></div>
    <?php endif; ?>

    <!-- Error Message -->
    <?php if (!empty($errors)) : ?>
        <div class="alert-box error-box"><?= $errors[0] ?></div>
    <?php endif; ?>

    <div class="add-product-form-wrapper">
        <form id="add-product-form" action="" method="POST" enctype="multipart/form-data">

            <!-- Type -->
            <div class="add-listing-field">
                <label>Type</label>
                <select id="listing-type" name="type" onchange="filterCategories()" required>
                    <option value="" disabled selected>Select product or service</option>
                    <option value="product">Product</option>
                    <option value="service">Service</option>
                </select>
            </div>

            <!-- Category -->
            <div class="add-listing-field">
                <label>Category</label>
                <select id="listing-category" name="category_id" onchange="filterSubcategories()" required>
                    <option value="" disabled selected>Select type first</option>
                </select>
            </div>

            <!-- Subcategory -->
            <div class="add-listing-field" id="subcategory-wrapper" style="display:none;">
                <label>Subcategory</label>
                <select id="listing-subcategory" name="subcategory_id" required>
                    <option value="" disabled selected>Select category first</option>
                </select>
                <div id="subcategory-description" class="category-description-box" style="display:none;"></div>
            </div>

            <!-- Title -->
            <div class="add-listing-field">
                <label>Title</label>
                <input type="text" name="title" placeholder="Enter product or service title" required>
            </div>

            <!-- Caption -->
            <div class="add-listing-field">
                <label>Caption <small>(short description shown in preview)</small></label>
                <input type="text" name="caption" placeholder="Enter a short caption">
            </div>

            <!-- Description -->
            <div class="add-listing-field">
                <label>Full Description</label>
                <textarea name="description" placeholder="Enter full description" required></textarea>
            </div>

            <!-- Price -->
            <div class="add-listing-field">
                <label>Price (£)</label>
                <input type="number" name="price" placeholder="Enter price e.g. 25.00" step="0.01" min="0" required>
            </div>

            <!-- Cultural Benefits -->
            <div class="add-listing-field">
                <label>Cultural Benefits</label>
                <textarea name="cultural_benefits" placeholder="Describe the cultural benefits of this product or service" required></textarea>
            </div>

            <!-- Business Name (readonly) -->
            <div class="add-listing-field">
                <label>Business Name</label>
                <input type="text" value="<?= htmlspecialchars($business_name) ?>" readonly>
            </div>

            <!-- Primary Image -->
            <div class="add-listing-field">
                <label>Primary Image <small>(shown on browse page)</small></label>
                <input type="file" name="primary_image" accept="image/*" required>
             </div>

             <!-- Additional Images -->
            <div class="add-listing-field">
                 <label>Additional Images <small>(optional, up to 4 more)</small></label>
                 <input type="file" name="additional_images[]" accept="image/*" multiple>
                 <small class="add-listing-hint">Hold Ctrl (Windows) or Cmd (Mac) to select multiple images</small>
            </div>

            <!-- Submit -->
            <div class="add-listing-field">
                <button type="submit" class="submit-btn">Add</button>
            </div>

        </form>
    </div>
  
</div>

<script>
const allCategories    = <?= json_encode($all_categories) ?>;
const allSubcategories = <?= json_encode($all_subcategories) ?>;

function filterCategories() {
    const type          = document.getElementById('listing-type').value;
    const catSelect     = document.getElementById('listing-category');
    const subWrapper    = document.getElementById('subcategory-wrapper');
    const subSelect     = document.getElementById('listing-subcategory');
    const subDesc       = document.getElementById('subcategory-description');

    // Reset
    catSelect.innerHTML = '<option value="" disabled selected>Select a category</option>';
    subSelect.innerHTML = '<option value="" disabled selected>Select category first</option>';
    subWrapper.style.display = 'none';
    subDesc.style.display    = 'none';

    // Filter categories by type
    const filtered = allCategories.filter(cat => cat.type === type);
    filtered.forEach(cat => {
        const option = document.createElement('option');
        option.value       = cat.category_id;
        option.textContent = cat.category_name;
        catSelect.appendChild(option);
    });
}

function filterSubcategories() {
    const categoryId = document.getElementById('listing-category').value;
    const subSelect  = document.getElementById('listing-subcategory');
    const subWrapper = document.getElementById('subcategory-wrapper');
    const subDesc    = document.getElementById('subcategory-description');

    // Reset
    subSelect.innerHTML     = '<option value="" disabled selected>Select a subcategory</option>';
    subDesc.style.display   = 'none';
    subWrapper.style.display = 'block';

    // Filter subcategories by category
    const filtered = allSubcategories.filter(sub => sub.category_id == categoryId);
    filtered.forEach(sub => {
        const option = document.createElement('option');
        option.value                        = sub.subcategory_id;
        option.textContent                  = sub.subcategory_name;
        option.setAttribute('data-desc', sub.description ?? '');
        subSelect.appendChild(option);
    });

    // Show description on subcategory change
        subSelect.onchange = function() {
        const selected = subSelect.options[subSelect.selectedIndex];
        const desc     = selected.getAttribute('data-desc');
        if (desc && desc.trim() !== '') {
            subDesc.style.display = 'block';
            subDesc.innerText     = desc;
        } else {
            subDesc.style.display = 'none';
        }
    };
}
</script>
