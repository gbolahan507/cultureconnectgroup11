<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
include '../db_connection.php';
 
$allowedRoles = ['SME'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: ../pages/dashboard.php?page=home");
    exit();
}
 
$sme_id        = $_SESSION['sme_id'] ?? '';
$business_name = $_SESSION['business_name'] ?? '';
$user_id       = intval($_SESSION['user_id'] ?? 0);
$errors        = [];
$success       = "";
 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
 
    $title       = trim($_POST['title']       ?? '');
    $caption     = trim($_POST['caption']     ?? '');
    $description = trim($_POST['description'] ?? '');
    $item_id     = trim($_POST['item_id']     ?? '');
    $price       = trim($_POST['price']       ?? '');
 
    // Validation
    if (empty($title))       $errors[] = "Title is required.";
    if (empty($caption))     $errors[] = "Caption is required.";
    if (empty($description)) $errors[] = "Description is required.";
    if (empty($item_id))     $errors[] = "Please select a product or service.";
    if (empty($price))       $errors[] = "Price is required.";
 
    // Primary image validation
    $primary_image = "";
    if (!isset($_FILES['primary_image']) || $_FILES['primary_image']['error'] != 0) {
        $errors[] = "A primary image is required.";
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($_FILES['primary_image']['type'], $allowed_types)) {
            $errors[] = "Primary image must be JPG, PNG, GIF or WEBP format.";
        } else {
            $primary_image = time() . "_" . basename($_FILES['primary_image']['name']);
            $upload_path   = "../uploads/listings_images/" . $primary_image;
            move_uploaded_file($_FILES['primary_image']['tmp_name'], $upload_path);
        }
    }
 
    if (empty($errors)) {
        $title_safe       = mysqli_real_escape_string($conn, $title);
        $caption_safe     = mysqli_real_escape_string($conn, $caption);
        $description_safe = mysqli_real_escape_string($conn, $description);
        $price_safe       = mysqli_real_escape_string($conn, $price);
 

        $sql = "INSERT INTO listings
                    (sme_id, title, caption, description, item_id, price, status, approved_by)
                VALUES
                    ('$sme_id', '$title_safe', '$caption_safe', '$description_safe',
                     '$item_id', '$price_safe', 'pending', '$user_id')";
 
        if (mysqli_query($conn, $sql)) {
            $listing_id = mysqli_insert_id($conn);
 
            // Save primary image to listing_images
            $primary_safe = mysqli_real_escape_string($conn, $primary_image);
            mysqli_query($conn, "INSERT INTO listing_images (listing_id, image_url, is_primary)
                                 VALUES ('$listing_id', '$primary_safe', 1)");
 
            // Handle additional images
            if (isset($_FILES['additional_images']) && !empty($_FILES['additional_images']['name'][0])) {
                $allowed_types  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $uploaded_count = 0;
 
                foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                    if ($uploaded_count >= 4) break;
 
                    $file_type = $_FILES['additional_images']['type'][$key];
                    $error     = $_FILES['additional_images']['error'][$key];
 
                    if ($error == 0 && in_array($file_type, $allowed_types)) {
                        $add_image_name  = time() . $key . "_" . basename($_FILES['additional_images']['name'][$key]);
                        $add_upload_path = "../uploads/listings_images/" . $add_image_name;
                        move_uploaded_file($tmp_name, $add_upload_path);
 
                        $add_image_safe = mysqli_real_escape_string($conn, $add_image_name);
                        mysqli_query($conn, "INSERT INTO listing_images (listing_id, image_url, is_primary)
                                            VALUES ('$listing_id', '$add_image_safe', 0)");
                        $uploaded_count++;
                    }
                }
            }
 
            $success = "Listing added successfully! You can manage it from Manage Listings.";
        } else {
            $errors[] = "Database error: " . mysqli_error($conn);
        }
    }
}
 
// Fetch product_service items grouped by subcategory for JavaScript
$items_result = mysqli_query($conn, "
    SELECT
        ps.item_id,
        ps.item_name,
        ps.description,
        pss.subcategory_name,
        pss.subcategory_id,
        psc.category_name,
        psc.category_id
    FROM product_service ps
    JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
    JOIN product_service_categories psc    ON pss.category_id   = psc.category_id
    ORDER BY psc.category_name, pss.subcategory_name, ps.item_name
");
 
$all_items = [];
while ($row = mysqli_fetch_assoc($items_result)) {
    $all_items[] = $row;
}
?>

<div class="add-product-page">
    <?php
       $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
        </svg>';
       $title    = "Add New Listing";
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
 
            <!-- Category Type -->
            <div class="add-listing-field">
                <label>Type</label>
                <select id="listing-type" onchange="filterByType()" required>
                    <option value="" disabled selected>Select product or service</option>
                    <option value="Product">Product</option>
                    <option value="Service">Service</option>
                </select>
            </div>
 
            <!-- Subcategory -->
            <div class="add-listing-field" id="subcategory-wrapper" style="display:none;">
                <label>Subcategory</label>
                <select id="listing-subcategory" onchange="filterBySubcategory()" required>
                    <option value="" disabled selected>Select subcategory</option>
                </select>
            </div>
 
            <!-- Product/Service Item -->
            <div class="add-listing-field" id="item-wrapper" style="display:none;">
                <label>Product / Service</label>
                <select id="listing-item" name="item_id" required>
                    <option value="" disabled selected>Select item</option>
                </select>
                <div id="item-description" class="category-description-box" style="display:none;"></div>
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
 
            <!-- Business Name -->
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
const allItems = <?= json_encode($all_items) ?>;
 
function filterByType() {
    const type        = document.getElementById('listing-type').value;
    const subSelect   = document.getElementById('listing-subcategory');
    const subWrapper  = document.getElementById('subcategory-wrapper');
    const itemWrapper = document.getElementById('item-wrapper');
    const itemSelect  = document.getElementById('listing-item');
    const itemDesc    = document.getElementById('item-description');
 
    subSelect.innerHTML  = '<option value="" disabled selected>Select subcategory</option>';
    itemSelect.innerHTML = '<option value="" disabled selected>Select item</option>';
    itemWrapper.style.display = 'none';
    itemDesc.style.display    = 'none';
    subWrapper.style.display  = 'block';
 
    const filtered      = allItems.filter(item => item.category_name === type);
    const subcategories = [...new Map(filtered.map(item => [item.subcategory_id, item])).values()];
 
    subcategories.forEach(sub => {
        const option       = document.createElement('option');
        option.value       = sub.subcategory_id;
        option.textContent = sub.subcategory_name;
        subSelect.appendChild(option);
    });
}
 
function filterBySubcategory() {
    const subcategoryId = document.getElementById('listing-subcategory').value;
    const itemSelect    = document.getElementById('listing-item');
    const itemWrapper   = document.getElementById('item-wrapper');
    const itemDesc      = document.getElementById('item-description');
 
    itemSelect.innerHTML   = '<option value="" disabled selected>Select item</option>';
    itemDesc.style.display = 'none';
    itemWrapper.style.display = 'block';
 
    const filtered = allItems.filter(item => item.subcategory_id == subcategoryId);
    filtered.forEach(item => {
        const option = document.createElement('option');
        option.value       = item.item_id;
        option.textContent = item.item_name;
        option.setAttribute('data-desc', item.description ?? '');
        itemSelect.appendChild(option);
    });
 
    itemSelect.onchange = function () {
        const selected = itemSelect.options[itemSelect.selectedIndex];
        const desc     = selected.getAttribute('data-desc');
        if (desc && desc.trim() !== '') {
            itemDesc.style.display = 'block';
            itemDesc.innerText     = desc;
        } else {
            itemDesc.style.display = 'none';
        }
    };
}
</script>


