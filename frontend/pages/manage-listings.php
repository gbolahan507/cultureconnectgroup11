<?php ob_start(); ?>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// AJAX HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    include '../db_connection.php';
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
 
    $role    = $_SESSION['user_role'] ?? '';
    $sme_id  = $_SESSION['sme_id']   ?? null;
    $user_id = $_SESSION['user_id']  ?? null;
 
    $allowedRoles = ['SME', 'Council Administrator', 'Council Member'];
    if (!in_array($role, $allowedRoles)) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit();
    }
 
    // Get Listings 
    if ($_POST['action'] === 'get_listings') {
        $status_filter = $_POST['status'] ?? 'all';
 
        if (in_array($role, ['Council Administrator', 'Council Member'])) {
            $where  = $status_filter === 'all' ? '' : "AND l.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
            $sql    = "
                SELECT l.listing_id, l.title, l.caption, l.description,
                       l.price, l.status, l.created_at,
                       ps.item_name, pss.subcategory_name, pc.category_name,
                       sp.business_name
                FROM listings l
                JOIN product_service ps                ON l.item_id        = ps.item_id
                JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
                JOIN product_service_categories pc     ON pss.category_id   = pc.category_id
                JOIN sme_profiles sp                   ON l.sme_id          = sp.sme_id
                WHERE 1=1 $where
                ORDER BY l.created_at DESC
            ";
            $result   = mysqli_query($conn, $sql);
            $listings = [];
            while ($row = mysqli_fetch_assoc($result)) $listings[] = $row;
        } else {
            if (!$sme_id) { echo json_encode(['success' => false, 'message' => 'SME profile not found.']); exit(); }
            if ($status_filter === 'all') {
                $stmt = $conn->prepare("
                    SELECT l.listing_id, l.title, l.caption, l.description,
                           l.price, l.status, l.created_at,
                           ps.item_name, pss.subcategory_name, pc.category_name,
                           sp.business_name
                    FROM listings l
                    JOIN product_service ps                ON l.item_id        = ps.item_id
                    JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
                    JOIN product_service_categories pc     ON pss.category_id   = pc.category_id
                    JOIN sme_profiles sp                   ON l.sme_id          = sp.sme_id
                    WHERE l.sme_id = ?
                    ORDER BY l.created_at DESC
                ");
                $stmt->bind_param("i", $sme_id);
            } else {
                $stmt = $conn->prepare("
                    SELECT l.listing_id, l.title, l.caption, l.description,
                           l.price, l.status, l.created_at,
                           ps.item_name, pss.subcategory_name, pc.category_name,
                           sp.business_name
                    FROM listings l
                    JOIN product_service ps                ON l.item_id        = ps.item_id
                    JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
                    JOIN product_service_categories pc     ON pss.category_id   = pc.category_id
                    JOIN sme_profiles sp                   ON l.sme_id          = sp.sme_id
                    WHERE l.sme_id = ? AND l.status = ?
                    ORDER BY l.created_at DESC
                ");
                $stmt->bind_param("is", $sme_id, $status_filter);
            }
            $stmt->execute();
            $result   = $stmt->get_result();
            $listings = [];
            while ($row = $result->fetch_assoc()) $listings[] = $row;
            $stmt->close();
        }
        echo json_encode(['success' => true, 'listings' => $listings]);
        exit();
    }
 
    // Get Single Listing + Images
    if ($_POST['action'] === 'get_listing') {
        $listing_id = intval($_POST['listing_id'] ?? 0);
 
        if (in_array($role, ['Council Administrator', 'Council Member'])) {
            $stmt = $conn->prepare("
                SELECT l.listing_id, l.title, l.caption, l.description,
                       l.price, l.status, l.created_at, l.item_id,
                       ps.item_name, ps.subcategory_id,
                       pss.subcategory_name, pss.category_id,
                       pc.category_name, sp.business_name
                FROM listings l
                JOIN product_service ps                ON l.item_id        = ps.item_id
                JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
                JOIN product_service_categories pc     ON pss.category_id   = pc.category_id
                JOIN sme_profiles sp                   ON l.sme_id          = sp.sme_id
                WHERE l.listing_id = ? LIMIT 1
            ");
            $stmt->bind_param("i", $listing_id);
        } else {
            $stmt = $conn->prepare("
                SELECT l.listing_id, l.title, l.caption, l.description,
                       l.price, l.status, l.created_at, l.item_id,
                       ps.item_name, ps.subcategory_id,
                       pss.subcategory_name, pss.category_id,
                       pc.category_name, sp.business_name
                FROM listings l
                JOIN product_service ps                ON l.item_id        = ps.item_id
                JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
                JOIN product_service_categories pc     ON pss.category_id   = pc.category_id
                JOIN sme_profiles sp                   ON l.sme_id          = sp.sme_id
                WHERE l.listing_id = ? AND l.sme_id = ? LIMIT 1
            ");
            $stmt->bind_param("ii", $listing_id, $sme_id);
        }
 
        $stmt->execute();
        $listing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
 
        if (!$listing) { echo json_encode(['success' => false, 'message' => 'Listing not found.']); exit(); }
 
        $img_stmt = $conn->prepare("
            SELECT image_url FROM listing_images
            WHERE listing_id = ?
            ORDER BY is_primary DESC, image_id ASC
        ");
        $img_stmt->bind_param("i", $listing_id);
        $img_stmt->execute();
        $img_result = $img_stmt->get_result();
        $images     = [];
        while ($img = $img_result->fetch_assoc()) $images[] = $img['image_url'];
        $img_stmt->close();
 
        echo json_encode(['success' => true, 'listing' => $listing, 'images' => $images]);
        exit();
    }
 
    // Get Listing Images (with image_id for manage modal)
    if ($_POST['action'] === 'get_listing_images') {
        if ($role !== 'SME' || !$sme_id) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit();
        }
 
        $listing_id = intval($_POST['listing_id'] ?? 0);
 
        // Verify listing belongs to SME and get its status
        $check = $conn->prepare("SELECT status, title FROM listings WHERE listing_id = ? AND sme_id = ?");
        $check->bind_param("ii", $listing_id, $sme_id);
        $check->execute();
        $listing_row = $check->get_result()->fetch_assoc();
        $check->close();
 
        if (!$listing_row) {
            echo json_encode(['success' => false, 'message' => 'Listing not found.']);
            exit();
        }
        if ($listing_row['status'] === 'active') {
            echo json_encode(['success' => false, 'message' => 'active']);
            exit();
        }
 
        $img_stmt = $conn->prepare("
            SELECT image_id, image_url, is_primary
            FROM listing_images
            WHERE listing_id = ?
            ORDER BY is_primary DESC, image_id ASC
        ");
        $img_stmt->bind_param("i", $listing_id);
        $img_stmt->execute();
        $img_result = $img_stmt->get_result();
        $images     = [];
        while ($row = $img_result->fetch_assoc()) $images[] = $row;
        $img_stmt->close();
 
        echo json_encode([
            'success'    => true,
            'images'     => $images,
            'total'      => count($images),
            'slots_left' => max(0, 5 - count($images)),
            'status'     => $listing_row['status'],
            'title'      => $listing_row['title']
        ]);
        exit();
    }
 
    // Set Primary Image
    if ($_POST['action'] === 'set_primary_image') {
        if ($role !== 'SME' || !$sme_id) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit();
        }
 
        $image_id   = intval($_POST['image_id']   ?? 0);
        $listing_id = intval($_POST['listing_id'] ?? 0);
 
        // Verify listing belongs to SME
        $check = $conn->prepare("SELECT listing_id FROM listings WHERE listing_id = ? AND sme_id = ?");
        $check->bind_param("ii", $listing_id, $sme_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Listing not found.']);
            $check->close(); exit();
        }
        $check->close();
 
        // Reset all images for this listing to non-primary
        $reset = $conn->prepare("UPDATE listing_images SET is_primary = 0 WHERE listing_id = ?");
        $reset->bind_param("i", $listing_id);
        $reset->execute();
        $reset->close();
 
        // Set the selected image as primary
        $set = $conn->prepare("UPDATE listing_images SET is_primary = 1 WHERE image_id = ? AND listing_id = ?");
        $set->bind_param("ii", $image_id, $listing_id);
 
        if ($set->execute()) {
            echo json_encode(['success' => true, 'message' => 'Primary image updated.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update primary image.']);
        }
        $set->close();
        exit();
    }
 
    // Delete Single Image
    if ($_POST['action'] === 'delete_image') {
        if ($role !== 'SME' || !$sme_id) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit();
        }
 
        $image_id   = intval($_POST['image_id']   ?? 0);
        $listing_id = intval($_POST['listing_id'] ?? 0);
 
        // Verify listing belongs to SME
        $check = $conn->prepare("SELECT listing_id FROM listings WHERE listing_id = ? AND sme_id = ?");
        $check->bind_param("ii", $listing_id, $sme_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Listing not found.']);
            $check->close(); exit();
        }
        $check->close();
 
        // Count total images — must keep at least 1
        $count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM listing_images WHERE listing_id = ?");
        $count_stmt->bind_param("i", $listing_id);
        $count_stmt->execute();
        $count_row = $count_stmt->get_result()->fetch_assoc();
        $count_stmt->close();
 
        if ($count_row['total'] <= 1) {
            echo json_encode(['success' => false, 'message' => 'A listing must have at least one image. Upload a new image before deleting this one.']);
            exit();
        }
 
        // Get image details
        $img_stmt = $conn->prepare("SELECT image_url, is_primary FROM listing_images WHERE image_id = ? AND listing_id = ?");
        $img_stmt->bind_param("ii", $image_id, $listing_id);
        $img_stmt->execute();
        $img_row = $img_stmt->get_result()->fetch_assoc();
        $img_stmt->close();
 
        if (!$img_row) {
            echo json_encode(['success' => false, 'message' => 'Image not found.']);
            exit();
        }
 
        $was_primary = intval($img_row['is_primary']) === 1;
 
        // Delete from DB
        $del = $conn->prepare("DELETE FROM listing_images WHERE image_id = ? AND listing_id = ?");
        $del->bind_param("ii", $image_id, $listing_id);
 
        if ($del->execute()) {
            // Remove physical file
            $file_path = "../uploads/listings_images/" . $img_row['image_url'];
            if (file_exists($file_path)) unlink($file_path);
 
            // If deleted image was primary, promote the next image
            if ($was_primary) {
                $promote = $conn->prepare("
                    UPDATE listing_images SET is_primary = 1
                    WHERE listing_id = ?
                    ORDER BY image_id ASC LIMIT 1
                ");
                $promote->bind_param("i", $listing_id);
                $promote->execute();
                $promote->close();
            }
 
            echo json_encode(['success' => true, 'message' => $was_primary ? 'Image deleted. The next image has been set as primary.' : 'Image deleted.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete image.']);
        }
        $del->close();
        exit();
    }
 
    // Upload New Images
    if ($_POST['action'] === 'upload_images') {
        if ($role !== 'SME' || !$sme_id) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit();
        }
 
        $listing_id = intval($_POST['listing_id'] ?? 0);
 
        // Verify listing belongs to SME
        $check = $conn->prepare("SELECT listing_id FROM listings WHERE listing_id = ? AND sme_id = ?");
        $check->bind_param("ii", $listing_id, $sme_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Listing not found.']);
            $check->close(); exit();
        }
        $check->close();
 
        // Count current images
        $count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM listing_images WHERE listing_id = ?");
        $count_stmt->bind_param("i", $listing_id);
        $count_stmt->execute();
        $count_row  = $count_stmt->get_result()->fetch_assoc();
        $count_stmt->close();
        $current_count = intval($count_row['total']);
        $slots_left    = max(0, 5 - $current_count);
 
        if ($slots_left === 0) {
            echo json_encode(['success' => false, 'message' => 'Maximum of 5 images reached. Delete an image first.']);
            exit();
        }
 
        if (!isset($_FILES['new_images']) || empty($_FILES['new_images']['name'][0])) {
            echo json_encode(['success' => false, 'message' => 'No files selected.']);
            exit();
        }
 
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $uploaded      = 0;
        $errors        = [];
 
        foreach ($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
            if ($uploaded >= $slots_left) break;
 
            $error     = $_FILES['new_images']['error'][$key];
            $file_type = $_FILES['new_images']['type'][$key];
            $file_name = $_FILES['new_images']['name'][$key];
 
            if ($error !== 0) { $errors[] = "$file_name could not be uploaded."; continue; }
            if (!in_array($file_type, $allowed_types)) { $errors[] = "$file_name is not a supported format (JPG, PNG, GIF, WEBP)."; continue; }
 
            $new_name    = time() . $key . '_' . basename($file_name);
            $upload_path = "../uploads/listings_images/" . $new_name;
 
            if (move_uploaded_file($tmp_name, $upload_path)) {
                $ins = $conn->prepare("INSERT INTO listing_images (listing_id, image_url, is_primary) VALUES (?, ?, 0)");
                $ins->bind_param("is", $listing_id, $new_name);
                $ins->execute();
                $ins->close();
                $uploaded++;
            } else {
                $errors[] = "$file_name failed to save.";
            }
        }
 
        $msg = "$uploaded image(s) uploaded successfully.";
        if (!empty($errors)) $msg .= ' Errors: ' . implode(' ', $errors);
 
        echo json_encode(['success' => $uploaded > 0, 'message' => $msg, 'uploaded' => $uploaded]);
        exit();
    }
 
    // Update Status + Log (Council)
    if ($_POST['action'] === 'update_status') {
        if (!in_array($role, ['Council Administrator', 'Council Member'])) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit();
        }
 
        $listing_id       = intval($_POST['listing_id'] ?? 0);
        $new_status       = trim($_POST['status']       ?? '');
        $comment          = trim($_POST['comment']      ?? '');
        $allowed_statuses = ['active', 'inactive', 'pending'];
 
        if (!$listing_id || !in_array($new_status, $allowed_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid listing or status.']);
            exit();
        }
        if ($new_status === 'inactive' && empty($comment)) {
            echo json_encode(['success' => false, 'message' => 'Please provide a reason for rejection.']);
            exit();
        }
 
        $stmt = $conn->prepare("UPDATE listings SET status = ?, approved_by = ? WHERE listing_id = ?");
        $stmt->bind_param("sii", $new_status, $user_id, $listing_id);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
            $stmt->close(); exit();
        }
        $stmt->close();
 
        if ($new_status === 'active' || $new_status === 'inactive') {
            $decision = $new_status === 'active' ? 'approved' : 'rejected';
            $log      = $conn->prepare("INSERT INTO listing_requests (listing_id, user_id, decision, comment) VALUES (?, ?, ?, ?)");
            $log->bind_param("iiss", $listing_id, $user_id, $decision, $comment);
            $log->execute();
            $log->close();
        }
 
        $msg = $new_status === 'active' ? 'Listing approved and set to active.' :
               ($new_status === 'inactive' ? 'Listing rejected and set to inactive.' : 'Listing reset to pending.');
 
        echo json_encode(['success' => true, 'message' => $msg]);
        exit();
    }
 
    // Update Listing (SME)
    if ($_POST['action'] === 'update_listing') {
        if ($role !== 'SME' || !$sme_id) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit();
        }
 
        $listing_id  = intval($_POST['listing_id'] ?? 0);
        $item_id     = intval($_POST['item_id']    ?? 0);
        $title       = trim($_POST['title']        ?? '');
        $caption     = trim($_POST['caption']      ?? '');
        $description = trim($_POST['description']  ?? '');
        $price       = trim($_POST['price']        ?? '');
 
        if (!$listing_id || !$item_id || empty($title) || empty($description) || empty($price)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit();
        }
        if (!is_numeric($price) || floatval($price) < 0) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid price.']);
            exit();
        }
 
        $check = $conn->prepare("SELECT status FROM listings WHERE listing_id = ? AND sme_id = ?");
        $check->bind_param("ii", $listing_id, $sme_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        $check->close();
 
        if (!$row) { echo json_encode(['success' => false, 'message' => 'Listing not found.']); exit(); }
        if ($row['status'] === 'active') {
            echo json_encode(['success' => false, 'message' => 'Active listings cannot be edited. Unpublish it first.']);
            exit();
        }
 
        $item_check = $conn->prepare("SELECT item_id FROM product_service WHERE item_id = ?");
        $item_check->bind_param("i", $item_id);
        $item_check->execute();
        $item_check->store_result();
        if ($item_check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product/service selected.']);
            $item_check->close(); exit();
        }
        $item_check->close();
 
        $price_val = floatval($price);
        $stmt = $conn->prepare("
            UPDATE listings
            SET title = ?, caption = ?, description = ?, price = ?, item_id = ?, status = 'pending'
            WHERE listing_id = ? AND sme_id = ?
        ");
        $stmt->bind_param("sssdiii", $title, $caption, $description, $price_val, $item_id, $listing_id, $sme_id);
 
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Listing updated and resubmitted for approval.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update listing.']);
        }
        $stmt->close();
        exit();
    }
 
    // Unpublish (SME)
    if ($_POST['action'] === 'unpublish_listing') {
        if ($role !== 'SME' || !$sme_id) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit();
        }
 
        $listing_id = intval($_POST['listing_id'] ?? 0);
        $check = $conn->prepare("SELECT status FROM listings WHERE listing_id = ? AND sme_id = ?");
        $check->bind_param("ii", $listing_id, $sme_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        $check->close();
 
        if (!$row || $row['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => 'Only active listings can be unpublished.']);
            exit();
        }
 
        $stmt = $conn->prepare("UPDATE listings SET status = 'inactive' WHERE listing_id = ? AND sme_id = ?");
        $stmt->bind_param("ii", $listing_id, $sme_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Listing unpublished. You can now edit and resubmit it.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to unpublish listing.']);
        }
        $stmt->close();
        exit();
    }
 
    // Delete Listing
    if ($_POST['action'] === 'delete_listing') {
        $listing_id = intval($_POST['listing_id'] ?? 0);
        if (!$listing_id) { echo json_encode(['success' => false, 'message' => 'Invalid listing.']); exit(); }
 
        if (in_array($role, ['Council Administrator', 'Council Member'])) {
            $check = $conn->prepare("SELECT listing_id FROM listings WHERE listing_id = ?");
            $check->bind_param("i", $listing_id);
        } else {
            $check = $conn->prepare("SELECT status FROM listings WHERE listing_id = ? AND sme_id = ?");
            $check->bind_param("ii", $listing_id, $sme_id);
        }
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        $check->close();
 
        if (!$row) { echo json_encode(['success' => false, 'message' => 'Listing not found.']); exit(); }
        if ($role === 'SME' && isset($row['status']) && $row['status'] === 'active') {
            echo json_encode(['success' => false, 'message' => 'Active listings cannot be deleted. Unpublish it first.']);
            exit();
        }
 
        $img_stmt = $conn->prepare("SELECT image_url FROM listing_images WHERE listing_id = ?");
        $img_stmt->bind_param("i", $listing_id);
        $img_stmt->execute();
        $img_result  = $img_stmt->get_result();
        $image_files = [];
        while ($img = $img_result->fetch_assoc()) $image_files[] = $img['image_url'];
        $img_stmt->close();
 
        if (in_array($role, ['Council Administrator', 'Council Member'])) {
            $del = $conn->prepare("DELETE FROM listings WHERE listing_id = ?");
            $del->bind_param("i", $listing_id);
        } else {
            $del = $conn->prepare("DELETE FROM listings WHERE listing_id = ? AND sme_id = ?");
            $del->bind_param("ii", $listing_id, $sme_id);
        }
 
        if ($del->execute()) {
            foreach ($image_files as $filename) {
                $fp = "../uploads/listings_images/" . $filename;
                if (file_exists($fp)) unlink($fp);
            }
            echo json_encode(['success' => true, 'message' => 'Listing deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete listing.']);
        }
        $del->close();
        exit();
    }
 
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit();
}
 
// PAGE GUARD
$allowedRoles = ['SME', 'Council Administrator', 'Council Member'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: ../pages/dashboard.php?page=home");
    exit();
}
 
$ml_role          = $_SESSION['user_role'];
$ml_sme_id        = $_SESSION['sme_id']        ?? '';
$ml_business_name = $_SESSION['business_name'] ?? '';
$ml_is_council    = in_array($ml_role, ['Council Administrator', 'Council Member']);
 
$ml_all_items = [];
if ($ml_role === 'SME') {
    $items_result = mysqli_query($conn, "
        SELECT ps.item_id, ps.item_name, ps.description AS item_desc,
               pss.subcategory_id, pss.subcategory_name,
               pc.category_id, pc.category_name
        FROM product_service ps
        JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
        JOIN product_service_categories pc     ON pss.category_id   = pc.category_id
        ORDER BY pc.category_name, pss.subcategory_name, ps.item_name
    ");
    while ($row = mysqli_fetch_assoc($items_result)) $ml_all_items[] = $row;
}  

?>
<div class="ml-page">

<?php
       $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
               <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
               </svg>';
       $title = "Manage Listings";
       $subtitle = "View, edit and manage products and services.";
       include '../components/section_header.php';
     ?>

     <div class="ml-status-tabs">
        <button class="ml-tab ml-tab--active" onclick="mlSetTab(this, 'all')">All</button>
        <button class="ml-tab"                onclick="mlSetTab(this, 'active')">Active</button>
        <button class="ml-tab"                onclick="mlSetTab(this, 'pending')">Pending</button>
        <button class="ml-tab"                onclick="mlSetTab(this, 'inactive')">Inactive</button>
    </div>
 
    <div class="ml-toolbar">
        <div id="ml-count-label" class="ml-count-label"></div>
        <?php if ($ml_role === 'SME') : ?>
        <a href="dashboard.php?page=add-product" class="ml-add-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="14" height="14">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add New Listing
        </a>
        <?php endif; ?>
    </div>
 
    <div class="ml-table-wrapper">
        <table class="ml-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Business</th>
                    <th>Category / Item</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="ml-table-body">
                <tr><td colspan="8" class="ml-loading-row"><div class="ml-spinner"></div> Loading listings…</td></tr>
            </tbody>
        </table>
    </div>
 
    <div id="ml-empty-state" class="ml-empty-state" style="display:none;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="48" height="48">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
        </svg>
        <p id="ml-empty-msg">No listings found.</p>
        <?php if ($ml_role === 'SME') : ?>
        <a href="dashboard.php?page=add-product" class="ml-add-btn">Add Your First Listing</a>
        <?php endif; ?>
    </div>

 
</div>



<!-- VIEW MODAL-->
<div id="ml-view-modal" class="ml-modal-overlay" style="display:none;">
    <div class="ml-modal-box">
        <div class="ml-modal-header">
            <h3>Listing Details</h3>
            <span class="ml-modal-close-btn" onclick="mlCloseViewModal()">&times;</span>
        </div>
        <div class="ml-modal-body">
            <div id="ml-carousel" class="ml-carousel">
                <div id="ml-carousel-empty" class="ml-carousel-empty" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="36" height="36"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                    <span>No images uploaded</span>
                </div>
                <div id="ml-carousel-stage" class="ml-carousel-stage" style="display:none;">
                    <img id="ml-carousel-img" src="" alt="Listing image" class="ml-carousel-img">
                    <button id="ml-carousel-prev" class="ml-carousel-arrow ml-carousel-arrow--prev" onclick="mlCarouselPrev()">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                    </button>
                    <button id="ml-carousel-next" class="ml-carousel-arrow ml-carousel-arrow--next" onclick="mlCarouselNext()">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                    </button>
                    <div id="ml-carousel-dots" class="ml-carousel-dots"></div>
                    <div id="ml-carousel-counter" class="ml-carousel-counter"></div>
                </div>
            </div>
 
            <table class="ml-detail-table">
                <tbody id="ml-view-body"></tbody>
            </table>
 
            <?php if ($ml_is_council) : ?>
            <div class="ml-status-change-wrap">
                <div id="ml-status-alert" class="ml-alert" style="display:none;"></div>
                <div class="ml-modal-field">
                    <label for="ml-status-select">Change Status</label>
                    <select id="ml-status-select" class="ml-input" onchange="mlToggleCommentBox()">
                        <option value="pending">Pending — awaiting review</option>
                        <option value="active">Active — approve listing</option>
                        <option value="inactive">Inactive — reject listing</option>
                    </select>
                </div>
                <div class="ml-modal-field" id="ml-comment-wrap" style="display:none;">
                    <label for="ml-status-comment">Reason for Rejection <span class="ml-required">*</span></label>
                    <textarea id="ml-status-comment" class="ml-input ml-textarea" placeholder="Explain why this listing is being rejected…" rows="3"></textarea>
                </div>
                <div class="ml-modal-field" id="ml-approval-comment-wrap" style="display:none;">
                    <label for="ml-status-comment-approve">Comment <small>(optional)</small></label>
                    <textarea id="ml-status-comment-approve" class="ml-input ml-textarea" placeholder="Optional note for the SME…" rows="2"></textarea>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="ml-modal-footer">
            <button class="ml-modal-cancel-btn" onclick="mlCloseViewModal()">Close</button>
            <?php if ($ml_is_council) : ?>
            <button class="ml-modal-submit-btn" id="ml-status-save-btn" onclick="mlSaveStatus()">Update Status</button>
            <?php endif; ?>
        </div>
    </div>
</div>
 
 
<!-- EDIT MODAL (SME only) -->
<?php if ($ml_role === 'SME') : ?>
<div id="ml-edit-modal" class="ml-modal-overlay" style="display:none;">
    <div class="ml-modal-box">
        <div class="ml-modal-header">
            <h3>Edit Listing</h3>
            <span class="ml-modal-close-btn" onclick="mlCloseEditModal()">&times;</span>
        </div>
        <div class="ml-modal-body ml-modal-body--padded">
            <div id="ml-edit-alert" class="ml-alert" style="display:none;"></div>
            <input type="hidden" id="ml-edit-id">
            <div class="ml-edit-info-banner">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                Saving will resubmit this listing for council approval.
            </div>
            <div class="ml-modal-field">
                <label for="ml-edit-type">Type <span class="ml-required">*</span></label>
                <select id="ml-edit-type" class="ml-input" onchange="mlEditFilterType()">
                    <option value="" disabled>Select product or service</option>
                    <option value="Product">Product</option>
                    <option value="Service">Service</option>
                </select>
            </div>
            <div class="ml-modal-field" id="ml-edit-sub-wrap" style="display:none;">
                <label for="ml-edit-subcategory">Subcategory <span class="ml-required">*</span></label>
                <select id="ml-edit-subcategory" class="ml-input" onchange="mlEditFilterSub()">
                    <option value="" disabled>Select subcategory</option>
                </select>
            </div>
            <div class="ml-modal-field" id="ml-edit-item-wrap" style="display:none;">
                <label for="ml-edit-item">Product / Service <span class="ml-required">*</span></label>
                <select id="ml-edit-item" class="ml-input">
                    <option value="" disabled>Select item</option>
                </select>
                <div id="ml-edit-item-desc" class="ml-item-desc-box" style="display:none;"></div>
            </div>
            <div class="ml-modal-field">
                <label for="ml-edit-title">Title <span class="ml-required">*</span></label>
                <input type="text" id="ml-edit-title" class="ml-input" placeholder="Listing title">
            </div>
            <div class="ml-modal-field">
                <label for="ml-edit-caption">Caption <small>(short preview text)</small></label>
                <input type="text" id="ml-edit-caption" class="ml-input" placeholder="Short caption">
            </div>
            <div class="ml-modal-field">
                <label for="ml-edit-description">Full Description <span class="ml-required">*</span></label>
                <textarea id="ml-edit-description" class="ml-input ml-textarea" placeholder="Full description" rows="4"></textarea>
            </div>
            <div class="ml-modal-field">
                <label for="ml-edit-price">Price (£) <span class="ml-required">*</span></label>
                <input type="number" id="ml-edit-price" class="ml-input" placeholder="0.00" step="0.01" min="0">
            </div>
        </div>
        <div class="ml-modal-footer">
            <button class="ml-modal-cancel-btn" onclick="mlCloseEditModal()">Cancel</button>
            <button class="ml-modal-submit-btn" id="ml-edit-save-btn" onclick="mlSaveListing()">Save &amp; Resubmit</button>
        </div>
    </div>
</div>
 
 
<!-- MANAGE IMAGES MODAL (SME only)-->
<div id="ml-images-modal" class="ml-modal-overlay" style="display:none;">
    <div class="ml-modal-box ml-modal-box--wide">
        <div class="ml-modal-header">
            <h3>Manage Images</h3>
            <span class="ml-modal-close-btn" onclick="mlCloseImagesModal()">&times;</span>
        </div>
        <div class="ml-modal-body ml-modal-body--padded">
 
            <!-- Active listing warning -->
            <div id="ml-img-active-warning" class="ml-img-active-warning" style="display:none;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                This listing is <strong>active</strong>. Unpublish it first to manage images.
            </div>
 
            <div id="ml-img-content">
                <!-- Alert -->
                <div id="ml-img-alert" class="ml-alert" style="display:none;"></div>
 
                <!-- Image count info -->
                <div class="ml-img-count-bar">
                    <span id="ml-img-count-text" class="ml-img-count-text"></span>
                    <span class="ml-img-count-hint">Max 5 images per listing (1 primary + 4 additional)</span>
                </div>
 
                <!-- Current images grid -->
                <div id="ml-img-grid" class="ml-img-grid">
                    <!-- Populated by JS -->
                </div>
 
                <!-- Upload section -->
                <div id="ml-img-upload-section" class="ml-img-upload-section">
                    <div class="ml-img-upload-header">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        <span id="ml-img-upload-label">Add More Images</span>
                    </div>
                    <input type="file" id="ml-img-file-input" class="ml-img-file-input" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
                    <label for="ml-img-file-input" class="ml-img-file-label">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        Click to select images
                        <span class="ml-img-file-hint">JPG, PNG, GIF or WEBP</span>
                    </label>
                    <div id="ml-img-selected-files" class="ml-img-selected-files"></div>
                </div>
            </div>
 
        </div>
        <div class="ml-modal-footer">
            <button class="ml-modal-cancel-btn" onclick="mlCloseImagesModal()">Close</button>
            <button class="ml-modal-submit-btn" id="ml-img-upload-btn" onclick="mlUploadImages()" style="display:none;">
                Upload Selected
            </button>
        </div>
    </div>
</div>
<?php endif; ?>
 
 
<!-- DELETE CONFIRM MODAL-->
<div id="ml-delete-modal" class="ml-modal-overlay" style="display:none;">
    <div class="ml-modal-box ml-modal-box--sm">
        <div class="ml-modal-header ml-modal-header--danger">
            <h3>Delete Listing</h3>
            <span class="ml-modal-close-btn" onclick="mlCloseDeleteModal()">&times;</span>
        </div>
        <div class="ml-modal-body ml-modal-body--padded">
            <p class="ml-delete-confirm-text">Are you sure you want to permanently delete <strong id="ml-delete-listing-title"></strong>?</p>
            <div class="ml-delete-warning">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                This will permanently remove the listing, all its images and the full approval history. This cannot be undone.
            </div>
        </div>
        <div class="ml-modal-footer">
            <button class="ml-modal-cancel-btn" onclick="mlCloseDeleteModal()">Cancel</button>
            <button class="ml-btn-danger" id="ml-delete-confirm-btn" onclick="mlConfirmDelete()">Yes, Delete</button>
        </div>
    </div>
</div>
 
 
 <!--JAVASCRIPT-->
<script>
    const ML_IS_COUNCIL  = <?= $ml_is_council ? 'true' : 'false' ?>;
    const ML_ROLE        = '<?= htmlspecialchars($ml_role) ?>';
    const ML_ALL_ITEMS   = <?= json_encode($ml_all_items) ?>;
 
    let mlCurrentStatus   = 'all';
    let mlCurrentPage  = 1;
    const ML_PER_PAGE  = 8;
    let mlAllListings  = [];
    let mlViewListingId   = null;
    let mlDeleteListingId = null;
    let mlImagesListingId = null;
    let mlCarouselImages  = [];
    let mlCarouselIndex   = 0;
 
    document.addEventListener('DOMContentLoaded', () => mlLoadListings('all'));
 
    // Tab switch
    function mlSetTab(btn, status) {
        document.querySelectorAll('.ml-tab').forEach(t => t.classList.remove('ml-tab--active'));
        btn.classList.add('ml-tab--active');
        mlCurrentStatus = status;
        mlLoadListings(status);
    }
 
    // Load listings
    function mlLoadListings(status) {
    const tbody      = document.getElementById('ml-table-body');
    const emptyState = document.getElementById('ml-empty-state');
    const countLabel = document.getElementById('ml-count-label');

    mlCurrentPage = 1;
    emptyState.style.display = 'none';
    tbody.innerHTML = `<tr><td colspan="8" class="ml-loading-row"><div class="ml-spinner"></div> Loading listings…</td></tr>`;

    const formData = new FormData();
    formData.append('action', 'get_listings');
    formData.append('status', status);

    fetch('../pages/manage-listings.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                tbody.innerHTML = '';
                if (data.success && data.listings.length > 0) {
                    mlAllListings = data.listings;
                    mlRenderPage();
                } else {
                    mlAllListings = [];
                    countLabel.textContent = '';
                    const messages = {
                        all:      ML_IS_COUNCIL ? 'No listings found.' : 'You have no listings yet.',
                        active:   'No active listings.',
                        pending:  'No listings awaiting approval.',
                        inactive: 'No inactive listings.'
                    };
                    document.getElementById('ml-empty-msg').textContent = messages[status] || 'No listings found.';
                    emptyState.style.display = 'flex';
                    mlRemovePagination();
                }
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="8" class="ml-error-row">Failed to load listings. Please refresh.</td></tr>';
            }
        })
        .catch(() => { tbody.innerHTML = '<tr><td colspan="8" class="ml-error-row">Network error.</td></tr>'; });
    }

 
    // Build table row
    function mlRenderPage() {
    const tbody      = document.getElementById('ml-table-body');
    const countLabel = document.getElementById('ml-count-label');
    const total      = mlAllListings.length;
    const totalPages = Math.ceil(total / ML_PER_PAGE);
    const start      = (mlCurrentPage - 1) * ML_PER_PAGE;
    const paged      = mlAllListings.slice(start, start + ML_PER_PAGE);

    countLabel.textContent = `${total} listing${total !== 1 ? 's' : ''} found`;
    tbody.innerHTML = '';
    paged.forEach((l, i) => tbody.appendChild(mlBuildRow(l, start + i + 1)));

    mlRenderPagination(totalPages);
}

function mlRenderPagination(totalPages) {
    mlRemovePagination();
    if (totalPages <= 1) return;

    const wrapper = document.querySelector('.ml-table-wrapper');
    const pag     = document.createElement('div');
    pag.id        = 'ml-pagination';
    pag.className = 'ml-pagination';

    const info = document.createElement('span');
    info.className   = 'ml-page-info';
    const start      = (mlCurrentPage - 1) * ML_PER_PAGE + 1;
    const end        = Math.min(mlCurrentPage * ML_PER_PAGE, mlAllListings.length);
    info.textContent = `Showing ${start}–${end} of ${mlAllListings.length}`;
    pag.appendChild(info);

    const btns = document.createElement('div');
    btns.className = 'ml-page-btns';

    if (mlCurrentPage > 1) {
        const prev = document.createElement('button');
        prev.className   = 'ml-page-btn';
        prev.textContent = '« Prev';
        prev.onclick     = () => { mlCurrentPage--; mlRenderPage(); };
        btns.appendChild(prev);
    }

    for (let p = 1; p <= totalPages; p++) {
        const btn = document.createElement('button');
        btn.className   = 'ml-page-btn' + (p === mlCurrentPage ? ' ml-page-btn--active' : '');
        btn.textContent = p;
        btn.onclick     = ((pg) => () => { mlCurrentPage = pg; mlRenderPage(); })(p);
        btns.appendChild(btn);
    }

    if (mlCurrentPage < totalPages) {
        const next = document.createElement('button');
        next.className   = 'ml-page-btn';
        next.textContent = 'Next »';
        next.onclick     = () => { mlCurrentPage++; mlRenderPage(); };
        btns.appendChild(next);
    }

    pag.appendChild(btns);
    wrapper.after(pag);
}

function mlRemovePagination() {
    const existing = document.getElementById('ml-pagination');
    if (existing) existing.remove();
}

function mlBuildRow(l, index) {
    const tr         = document.createElement('tr');
    const badgeClass = { active: 'ml-badge--active', pending: 'ml-badge--pending', inactive: 'ml-badge--inactive' }[l.status] || '';
    const price      = '£' + parseFloat(l.price).toFixed(2);
    const created    = new Date(l.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

    let actions = `<button class="ml-view-btn" onclick="mlOpenView(${l.listing_id})">View</button>`;

    if (ML_ROLE === 'SME') {
        actions += `<button class="ml-images-btn" onclick="mlOpenImages(${l.listing_id})">Images</button>`;
        if (l.status === 'pending' || l.status === 'inactive') {
            actions += `<button class="ml-edit-btn" onclick="mlOpenEdit(${l.listing_id})">Edit</button>`;
            actions += `<button class="ml-delete-btn" onclick="mlOpenDelete(${l.listing_id}, '${mlEsc(l.title)}')">Delete</button>`;
        } else if (l.status === 'active') {
            actions += `<button class="ml-unpublish-btn" onclick="mlUnpublish(${l.listing_id}, this)">Unpublish</button>`;
        }
    } else {
        actions += `<button class="ml-delete-btn" onclick="mlOpenDelete(${l.listing_id}, '${mlEsc(l.title)}')">Delete</button>`;
    }

    tr.innerHTML = `
        <td>${index}</td>
        <td class="ml-title-cell">${mlEsc(l.title)}</td>
        <td class="ml-business-cell">${mlEsc(l.business_name ?? '—')}</td>
        <td><span class="ml-category-text">${mlEsc(l.category_name)}</span><br><small class="ml-item-text">${mlEsc(l.item_name)}</small></td>
        <td class="ml-price-cell">${price}</td>
        <td><span class="ml-badge ${badgeClass}">${mlEsc(l.status.charAt(0).toUpperCase() + l.status.slice(1))}</span></td>
        <td>${created}</td>
        <td class="ml-actions-cell">${actions}</td>
    `;
    return tr;
}
 
    // VIEW MODAL
    function mlOpenView(listing_id) {
        mlViewListingId  = listing_id;
        mlCarouselImages = [];
        mlCarouselIndex  = 0;
        document.getElementById('ml-carousel-stage').style.display = 'none';
        document.getElementById('ml-carousel-empty').style.display = 'none';
 
        const formData = new FormData();
        formData.append('action', 'get_listing');
        formData.append('listing_id', listing_id);
 
        fetch('../pages/manage-listings.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        const l       = data.listing;
                        const images  = data.images || [];
                        const created = new Date(l.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
 
                        document.getElementById('ml-view-body').innerHTML = `
                            <tr><td><strong>Title</strong></td><td>${mlEsc(l.title)}</td></tr>
                            <tr><td><strong>Business</strong></td><td>${mlEsc(l.business_name ?? '—')}</td></tr>
                            <tr><td><strong>Category</strong></td><td>${mlEsc(l.category_name)}</td></tr>
                            <tr><td><strong>Item</strong></td><td>${mlEsc(l.item_name)}</td></tr>
                            <tr><td><strong>Caption</strong></td><td>${mlEsc(l.caption ?? '—')}</td></tr>
                            <tr><td><strong>Description</strong></td><td>${mlEsc(l.description)}</td></tr>
                            <tr><td><strong>Price</strong></td><td>£${parseFloat(l.price).toFixed(2)}</td></tr>
                            <tr><td><strong>Status</strong></td><td>${mlEsc(l.status.charAt(0).toUpperCase() + l.status.slice(1))}</td></tr>
                            <tr><td><strong>Date Added</strong></td><td>${created}</td></tr>
                        `;
 
                        mlCarouselImages = images;
                        if (images.length > 0) { mlCarouselIndex = 0; mlCarouselRender(); document.getElementById('ml-carousel-stage').style.display = 'block'; }
                        else { document.getElementById('ml-carousel-empty').style.display = 'flex'; }
 
                        if (ML_IS_COUNCIL) {
                            document.getElementById('ml-status-select').value = l.status;
                            document.getElementById('ml-status-alert').style.display = 'none';
                            document.getElementById('ml-status-comment').value = '';
                            if (document.getElementById('ml-status-comment-approve')) document.getElementById('ml-status-comment-approve').value = '';
                            mlToggleCommentBox();
                        }
                        document.getElementById('ml-view-modal').style.display = 'flex';
                    } else { mlShowToast(data.message || 'Could not load listing.', 'error'); }
                } catch (e) { mlShowToast('Unexpected error.', 'error'); }
            })
            .catch(() => mlShowToast('Network error.', 'error'));
    }
 
    function mlCloseViewModal() {
        document.getElementById('ml-view-modal').style.display = 'none';
        mlViewListingId = null; mlCarouselImages = []; mlCarouselIndex = 0;
    }
 
    function mlToggleCommentBox() {
        const status = document.getElementById('ml-status-select').value;
        document.getElementById('ml-comment-wrap').style.display          = status === 'inactive' ? 'flex' : 'none';
        document.getElementById('ml-approval-comment-wrap').style.display = status === 'active'   ? 'flex' : 'none';
    }
 
    // CAROUSEL
    function mlCarouselRender() {
        const total = mlCarouselImages.length;
        document.getElementById('ml-carousel-img').src = '../uploads/listings_images/' + mlCarouselImages[mlCarouselIndex];
        document.getElementById('ml-carousel-prev').style.display = document.getElementById('ml-carousel-next').style.display = total > 1 ? 'flex' : 'none';
        document.getElementById('ml-carousel-counter').textContent = total > 1 ? `${mlCarouselIndex + 1} / ${total}` : '';
        const dots = document.getElementById('ml-carousel-dots');
        dots.innerHTML = '';
        if (total > 1) {
            mlCarouselImages.forEach((_, i) => {
                const dot = document.createElement('span');
                dot.className = 'ml-carousel-dot' + (i === mlCarouselIndex ? ' ml-carousel-dot--active' : '');
                dot.onclick   = () => { mlCarouselIndex = i; mlCarouselRender(); };
                dots.appendChild(dot);
            });
        }
    }
    function mlCarouselPrev() { if (mlCarouselImages.length < 2) return; mlCarouselIndex = (mlCarouselIndex - 1 + mlCarouselImages.length) % mlCarouselImages.length; mlCarouselRender(); }
    function mlCarouselNext() { if (mlCarouselImages.length < 2) return; mlCarouselIndex = (mlCarouselIndex + 1) % mlCarouselImages.length; mlCarouselRender(); }
 
    // SAVE STATUS (Council)
    function mlSaveStatus() {
        if (!mlViewListingId) return;
        const status  = document.getElementById('ml-status-select').value;
        const alertEl = document.getElementById('ml-status-alert');
        const saveBtn = document.getElementById('ml-status-save-btn');
 
        let comment = '';
        if (status === 'inactive') {
            comment = document.getElementById('ml-status-comment').value.trim();
            if (!comment) { alertEl.textContent = 'Please provide a reason for rejection.'; alertEl.className = 'ml-alert ml-alert--error'; alertEl.style.display = 'block'; return; }
        } else if (status === 'active') {
            comment = document.getElementById('ml-status-comment-approve').value.trim();
        }
 
        saveBtn.disabled = true; saveBtn.textContent = 'Saving…';
 
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('listing_id', mlViewListingId);
        formData.append('status', status);
        formData.append('comment', comment);
 
        fetch('../pages/manage-listings.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) { mlCloseViewModal(); mlShowToast(data.message, 'success'); mlLoadListings(mlCurrentStatus); }
                    else { alertEl.textContent = data.message || 'Failed.'; alertEl.className = 'ml-alert ml-alert--error'; alertEl.style.display = 'block'; }
                } catch (e) { alertEl.textContent = 'Unexpected response.'; alertEl.className = 'ml-alert ml-alert--error'; alertEl.style.display = 'block'; }
            })
            .catch(() => { alertEl.textContent = 'Network error.'; alertEl.className = 'ml-alert ml-alert--error'; alertEl.style.display = 'block'; })
            .finally(() => { saveBtn.disabled = false; saveBtn.textContent = 'Update Status'; });
    }
 
    // MANAGE IMAGES MODAL
    function mlOpenImages(listing_id) {
        mlImagesListingId = listing_id;
        const modal       = document.getElementById('ml-images-modal');
        const content     = document.getElementById('ml-img-content');
        const warning     = document.getElementById('ml-img-active-warning');
        const uploadBtn   = document.getElementById('ml-img-upload-btn');
        const alertEl     = document.getElementById('ml-img-alert');
 
        // Reset state
        content.style.display  = 'none';
        warning.style.display  = 'none';
        uploadBtn.style.display = 'none';
        alertEl.style.display  = 'none';
        document.getElementById('ml-img-grid').innerHTML = '<div class="ml-img-loading"><div class="ml-spinner"></div> Loading images…</div>';
        document.getElementById('ml-img-selected-files').innerHTML = '';
        document.getElementById('ml-img-file-input').value = '';
        modal.style.display = 'flex';
 
        const formData = new FormData();
        formData.append('action', 'get_listing_images');
        formData.append('listing_id', listing_id);
 
        fetch('../pages/manage-listings.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (!data.success && data.message === 'active') {
                        warning.style.display = 'flex';
                        document.getElementById('ml-img-grid').innerHTML = '';
                        return;
                    }
                    if (data.success) {
                        content.style.display = 'block';
                        mlRenderImagesGrid(data.images, data.slots_left);
                    } else {
                        mlShowImgAlert(data.message || 'Could not load images.', 'error');
                    }
                } catch (e) { mlShowImgAlert('Unexpected error.', 'error'); }
            })
            .catch(() => mlShowImgAlert('Network error.', 'error'));
    }
 
    function mlRenderImagesGrid(images, slotsLeft) {
        const grid      = document.getElementById('ml-img-grid');
        const countText = document.getElementById('ml-img-count-text');
        const uploadSec = document.getElementById('ml-img-upload-section');
        const uploadLbl = document.getElementById('ml-img-upload-label');
        const fileInput = document.getElementById('ml-img-file-input');
 
        countText.textContent = `${images.length} of 5 images used`;
        grid.innerHTML = '';
 
        if (images.length === 0) {
            grid.innerHTML = '<p class="ml-img-empty-text">No images yet. Upload one below.</p>';
        } else {
            images.forEach(img => {
                const card = document.createElement('div');
                card.className     = 'ml-img-card' + (img.is_primary ? ' ml-img-card--primary' : '');
                card.dataset.imgId = img.image_id;
                card.innerHTML = `
                    <div class="ml-img-card-thumb">
                        <img src="../uploads/listings_images/${mlEsc(img.image_url)}" alt="Listing image"
                             onerror="this.src=''; this.parentElement.classList.add('ml-img-card-thumb--broken')">
                        ${img.is_primary ? '<span class="ml-img-primary-badge"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.536.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.769-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" /></svg> Primary</span>' : ''}
                    </div>
                    <div class="ml-img-card-actions">
                        ${!img.is_primary ? `<button class="ml-img-set-primary-btn" onclick="mlSetPrimary(${img.image_id}, ${mlImagesListingId})">Set Primary</button>` : '<span class="ml-img-is-primary-label">✓ Primary</span>'}
                        <button class="ml-img-delete-btn" onclick="mlDeleteImage(${img.image_id}, ${mlImagesListingId})">Delete</button>
                    </div>
                `;
                grid.appendChild(card);
            });
        }
 
        // Upload section
        if (slotsLeft > 0) {
            uploadSec.style.display = 'block';
            uploadLbl.textContent   = `Add More Images (${slotsLeft} slot${slotsLeft !== 1 ? 's' : ''} remaining)`;
            fileInput.multiple      = slotsLeft > 1;
        } else {
            uploadSec.style.display = 'none';
        }
    }
 
    function mlSetPrimary(image_id, listing_id) {
        const formData = new FormData();
        formData.append('action',     'set_primary_image');
        formData.append('image_id',   image_id);
        formData.append('listing_id', listing_id);
 
        fetch('../pages/manage-listings.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) { mlShowToast(data.message, 'success'); mlRefreshImagesGrid(); }
                    else { mlShowImgAlert(data.message || 'Failed.', 'error'); }
                } catch (e) { mlShowImgAlert('Unexpected error.', 'error'); }
            })
            .catch(() => mlShowImgAlert('Network error.', 'error'));
    }
 
    function mlDeleteImage(image_id, listing_id) {
        if (!confirm('Delete this image? This cannot be undone.')) return;
 
        const formData = new FormData();
        formData.append('action',     'delete_image');
        formData.append('image_id',   image_id);
        formData.append('listing_id', listing_id);
 
        fetch('../pages/manage-listings.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) { mlShowToast(data.message, 'success'); mlRefreshImagesGrid(); }
                    else { mlShowImgAlert(data.message || 'Failed.', 'error'); }
                } catch (e) { mlShowImgAlert('Unexpected error.', 'error'); }
            })
            .catch(() => mlShowImgAlert('Network error.', 'error'));
    }
 
    function mlRefreshImagesGrid() {
        const formData = new FormData();
        formData.append('action',     'get_listing_images');
        formData.append('listing_id', mlImagesListingId);
 
        fetch('../pages/manage-listings.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) mlRenderImagesGrid(data.images, data.slots_left);
                } catch (e) {}
            });
    }
 
    // File input preview
    document.addEventListener('DOMContentLoaded', () => {
        const fileInput = document.getElementById('ml-img-file-input');
        if (!fileInput) return;
        fileInput.addEventListener('change', () => {
            const preview    = document.getElementById('ml-img-selected-files');
            const uploadBtn  = document.getElementById('ml-img-upload-btn');
            const files      = Array.from(fileInput.files);
 
            if (files.length === 0) {
                preview.innerHTML = '';
                uploadBtn.style.display = 'none';
                return;
            }
 
            preview.innerHTML = files.map(f =>
                `<span class="ml-img-file-chip">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                    ${mlEsc(f.name)}
                </span>`
            ).join('');
            uploadBtn.style.display = 'inline-block';
        });
    });
 
    function mlUploadImages() {
        const fileInput = document.getElementById('ml-img-file-input');
        const uploadBtn = document.getElementById('ml-img-upload-btn');
 
        if (!fileInput.files.length) { mlShowImgAlert('Please select at least one image.', 'error'); return; }
 
        uploadBtn.disabled    = true;
        uploadBtn.textContent = 'Uploading…';
 
        const formData = new FormData();
        formData.append('action',     'upload_images');
        formData.append('listing_id', mlImagesListingId);
        Array.from(fileInput.files).forEach(file => formData.append('new_images[]', file));
 
        fetch('../pages/manage-listings.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        mlShowToast(data.message, 'success');
                        fileInput.value = '';
                        document.getElementById('ml-img-selected-files').innerHTML = '';
                        uploadBtn.style.display = 'none';
                        mlRefreshImagesGrid();
                    } else {
                        mlShowImgAlert(data.message || 'Upload failed.', 'error');
                    }
                } catch (e) { mlShowImgAlert('Unexpected error.', 'error'); }
            })
            .catch(() => mlShowImgAlert('Network error.', 'error'))
            .finally(() => { uploadBtn.disabled = false; uploadBtn.textContent = 'Upload Selected'; });
    }
 
    function mlShowImgAlert(message, type) {
        const el = document.getElementById('ml-img-alert');
        el.textContent   = message;
        el.className     = 'ml-alert ml-alert--' + type;
        el.style.display = 'block';
    }
 
    function mlCloseImagesModal() {
        document.getElementById('ml-images-modal').style.display = 'none';
        mlImagesListingId = null;
    }
 
    // EDIT MODAL 
    function mlOpenEdit(listing_id) {
        const formData = new FormData();
        formData.append('action', 'get_listing');
        formData.append('listing_id', listing_id);
 
        fetch('../pages/manage-listings.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        const l = data.listing;
                        document.getElementById('ml-edit-id').value          = l.listing_id;
                        document.getElementById('ml-edit-title').value       = l.title;
                        document.getElementById('ml-edit-caption').value     = l.caption ?? '';
                        document.getElementById('ml-edit-description').value = l.description;
                        document.getElementById('ml-edit-price').value       = parseFloat(l.price).toFixed(2);
                        document.getElementById('ml-edit-alert').style.display = 'none';
                        mlEditPresetDropdowns(l.category_name, l.subcategory_id, l.item_id);
                        document.getElementById('ml-edit-modal').style.display = 'flex';
                    } else { mlShowToast(data.message || 'Could not load listing.', 'error'); }
                } catch (e) { mlShowToast('Unexpected error.', 'error'); }
            })
            .catch(() => mlShowToast('Network error.', 'error'));
    }
 
    function mlEditPresetDropdowns(categoryName, subcategoryId, itemId) {
        document.getElementById('ml-edit-type').value = categoryName;
        mlEditFilterType(subcategoryId, itemId);
    }
 
    function mlEditFilterType(presetSubId, presetItemId) {
        const type    = document.getElementById('ml-edit-type').value;
        const subSel  = document.getElementById('ml-edit-subcategory');
        const subWrap = document.getElementById('ml-edit-sub-wrap');
        const itemSel = document.getElementById('ml-edit-item');
        const itemWrap = document.getElementById('ml-edit-item-wrap');
        const itemDesc = document.getElementById('ml-edit-item-desc');
 
        subSel.innerHTML   = '<option value="" disabled>Select subcategory</option>';
        itemSel.innerHTML  = '<option value="" disabled>Select item</option>';
        itemWrap.style.display = 'none';
        itemDesc.style.display = 'none';
        if (!type) { subWrap.style.display = 'none'; return; }
        subWrap.style.display = 'block';
 
        const filtered = ML_ALL_ITEMS.filter(i => i.category_name === type);
        const subs     = [...new Map(filtered.map(i => [i.subcategory_id, i])).values()];
        subs.forEach(s => { const o = document.createElement('option'); o.value = s.subcategory_id; o.textContent = s.subcategory_name; subSel.appendChild(o); });
        if (presetSubId) { subSel.value = presetSubId; mlEditFilterSub(presetItemId); }
    }
 
    function mlEditFilterSub(presetItemId) {
        const subId    = document.getElementById('ml-edit-subcategory').value;
        const itemSel  = document.getElementById('ml-edit-item');
        const itemWrap = document.getElementById('ml-edit-item-wrap');
        const itemDesc = document.getElementById('ml-edit-item-desc');
 
        itemSel.innerHTML  = '<option value="" disabled>Select item</option>';
        itemDesc.style.display = 'none';
        if (!subId) { itemWrap.style.display = 'none'; return; }
        itemWrap.style.display = 'block';
 
        ML_ALL_ITEMS.filter(i => i.subcategory_id == subId).forEach(item => {
            const o = document.createElement('option');
            o.value = item.item_id; o.textContent = item.item_name;
            o.setAttribute('data-desc', item.item_desc ?? '');
            itemSel.appendChild(o);
        });
        if (presetItemId) { itemSel.value = presetItemId; mlEditShowItemDesc(); }
        itemSel.onchange = mlEditShowItemDesc;
    }
 
    function mlEditShowItemDesc() {
        const itemSel  = document.getElementById('ml-edit-item');
        const itemDesc = document.getElementById('ml-edit-item-desc');
        const selected = itemSel.options[itemSel.selectedIndex];
        const desc     = selected ? selected.getAttribute('data-desc') : '';
        itemDesc.textContent   = desc && desc.trim() ? desc : '';
        itemDesc.style.display = desc && desc.trim() ? 'block' : 'none';
    }
 
    function mlCloseEditModal() { document.getElementById('ml-edit-modal').style.display = 'none'; }
 
    function mlSaveListing() {
        const listing_id  = document.getElementById('ml-edit-id').value;
        const item_id     = document.getElementById('ml-edit-item').value;
        const title       = document.getElementById('ml-edit-title').value.trim();
        const caption     = document.getElementById('ml-edit-caption').value.trim();
        const description = document.getElementById('ml-edit-description').value.trim();
        const price       = document.getElementById('ml-edit-price').value.trim();
        const saveBtn     = document.getElementById('ml-edit-save-btn');
        const alertEl     = document.getElementById('ml-edit-alert');
 
        if (!item_id || !title || !description || !price) {
            alertEl.textContent = 'Please fill in all required fields including the product/service selection.';
            alertEl.className   = 'ml-alert ml-alert--error';
            alertEl.style.display = 'block';
            return;
        }
 
        saveBtn.disabled    = true;
        saveBtn.textContent = 'Saving…';
 
        const formData = new FormData();
        formData.append('action', 'update_listing');
        formData.append('listing_id', listing_id);
        formData.append('item_id', item_id);
        formData.append('title', title);
        formData.append('caption', caption);
        formData.append('description', description);
        formData.append('price', price);
 
        fetch('../pages/manage-listings.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) { mlCloseEditModal(); mlShowToast(data.message, 'success'); mlLoadListings(mlCurrentStatus); }
                    else { alertEl.textContent = data.message || 'Update failed.'; alertEl.className = 'ml-alert ml-alert--error'; alertEl.style.display = 'block'; }
                } catch (e) { alertEl.textContent = 'Unexpected response.'; alertEl.className = 'ml-alert ml-alert--error'; alertEl.style.display = 'block'; }
            })
            .catch(() => { alertEl.textContent = 'Network error.'; alertEl.className = 'ml-alert ml-alert--error'; alertEl.style.display = 'block'; })
            .finally(() => { saveBtn.disabled = false; saveBtn.textContent = 'Save & Resubmit'; });
    }
 
    //  UNPUBLISH
    function mlUnpublish(listing_id, btn) {
        if (!confirm('Unpublish this listing? It will become inactive and you can then edit and resubmit it.')) return;
        btn.disabled = true; btn.textContent = '…';
 
        const formData = new FormData();
        formData.append('action', 'unpublish_listing');
        formData.append('listing_id', listing_id);
 
        fetch('../pages/manage-listings.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) { mlShowToast(data.message, 'success'); mlLoadListings(mlCurrentStatus); }
                    else { mlShowToast(data.message || 'Failed.', 'error'); btn.disabled = false; btn.textContent = 'Unpublish'; }
                } catch (e) { mlShowToast('Error.', 'error'); btn.disabled = false; btn.textContent = 'Unpublish'; }
            })
            .catch(() => { mlShowToast('Network error.', 'error'); btn.disabled = false; btn.textContent = 'Unpublish'; });
    }
 
    // DELETE 
    function mlOpenDelete(listing_id, title) {
        mlDeleteListingId = listing_id;
        document.getElementById('ml-delete-listing-title').textContent = title;
        document.getElementById('ml-delete-modal').style.display = 'flex';
    }
 
    function mlCloseDeleteModal() { document.getElementById('ml-delete-modal').style.display = 'none'; mlDeleteListingId = null; }
 
    function mlConfirmDelete() {
        if (!mlDeleteListingId) return;
        const btn = document.getElementById('ml-delete-confirm-btn');
        btn.disabled = true; btn.textContent = 'Deleting…';
 
        const formData = new FormData();
        formData.append('action', 'delete_listing');
        formData.append('listing_id', mlDeleteListingId);
 
        fetch('../pages/manage-listings.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) { mlCloseDeleteModal(); mlShowToast(data.message, 'success'); mlLoadListings(mlCurrentStatus); }
                    else { mlCloseDeleteModal(); mlShowToast(data.message || 'Failed.', 'error'); }
                } catch (e) { mlCloseDeleteModal(); mlShowToast('Unexpected error.', 'error'); }
            })
            .catch(() => mlShowToast('Network error.', 'error'))
            .finally(() => { btn.disabled = false; btn.textContent = 'Yes, Delete'; });
    }
 
    // HELPERS 
    function mlEsc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
 
    function mlShowToast(message, type) {
        const existing = document.getElementById('ml-toast');
        if (existing) existing.remove();
        const toast = document.createElement('div');
        toast.id = 'ml-toast'; toast.className = 'ml-toast ml-toast--' + type; toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('ml-toast--show'), 10);
        setTimeout(() => { toast.classList.remove('ml-toast--show'); setTimeout(() => toast.remove(), 300); }, 3000);
    }
</script>