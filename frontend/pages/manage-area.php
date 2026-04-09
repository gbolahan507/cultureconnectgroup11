<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db_connection.php';

$allowedRoles = ['Council Administrator', 'Council Member'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: ../pages/dashboard.php?page=home");
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean();
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // ADD AREA
    if ($action === 'add') {
        $name        = mysqli_real_escape_string($conn, trim($_POST['area_name'] ?? ''));
        $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
        $postcode    = mysqli_real_escape_string($conn, strtoupper(trim($_POST['postcode'] ?? '')));

        if (empty($name) || empty($description) || empty($postcode)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit();
        }

        $sql = "INSERT INTO areas (area_name, description, postcode) VALUES ('$name', '$description', '$postcode')";
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Area added successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        }
        exit();
    }

    // EDIT AREA
    if ($action === 'edit') {
        $area_id     = (int)$_POST['area_id'];
        $name        = mysqli_real_escape_string($conn, trim($_POST['area_name'] ?? ''));
        $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
        $postcode    = mysqli_real_escape_string($conn, strtoupper(trim($_POST['postcode'] ?? '')));

        if (empty($name) || empty($description) || empty($postcode)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit();
        }

        $sql = "UPDATE areas SET area_name = '$name', description = '$description', postcode = '$postcode' WHERE area_id = '$area_id'";
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Area updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        }
        exit();
    }

   // DELETE AREA
    if ($action === 'delete') {
    $area_id = (int)$_POST['area_id'];

    // Check resident_profiles
    $check2 = mysqli_query($conn, "SELECT COUNT(*) as total FROM resident_profiles WHERE area_id = '$area_id'");
    $row2   = mysqli_fetch_assoc($check2);

    // Check sme_profiles
    $check3 = mysqli_query($conn, "SELECT COUNT(*) as total FROM sme_profiles WHERE area_id = '$area_id'");
    $row3   = mysqli_fetch_assoc($check3);

    if ($row2['total'] > 0 || $row3['total'] > 0) {
        echo json_encode(['success' => false, 'message' => 'This area cannot be deleted as it has registered users assigned to it.']);
        exit();
    }

    $sql = "DELETE FROM areas WHERE area_id = '$area_id'";
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Area deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit();
   } 

    // If no action matched
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit();
}

     // Pagination setup
          $areas_per_page = 6;
          $area_page      = max(1, intval($_GET['area_page'] ?? 1));
          $total_areas    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM areas"))['n'];
          $total_pages    = max(1, ceil($total_areas / $areas_per_page));
          $area_page      = min($area_page, $total_pages);
          $offset         = ($area_page - 1) * $areas_per_page;

          $areas = mysqli_query($conn, "SELECT * FROM areas ORDER BY area_id ASC LIMIT $areas_per_page OFFSET $offset");
?>

<div class="manage-area-page">

     <?php
       $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
            </svg>';
       $title = "Manage Area";
       $subtitle = "Here you can View, Add, Edit, or Remove Areas under the Council's Jurisdiction.";
       include '../db_connection.php';
       include '../components/section_header.php';
     ?>

    <!-- Action Message -->
    <div id="area-action-message" class="alert-box" style="display:none;"></div>

    <!-- Add Button -->
    <div class="manage-area-actions">
        <button id="area-add-trigger-btn" onclick="openAreaAddModal()">+ Add Area</button>
    </div>

    <!-- Search Bar-->
    <div class="table-filters">
        <input type="text" id="area-search-input" placeholder="Search by area name" onkeyup="filterAreaTable()">
    </div>

    <!-- Area Table -->
    <div class="area-table-wrapper">
    <table class="area-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Area Name</th>
                <th>Description</th>
                <th>Postcode</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="area-table-body">
            <?php
            $count = $offset + 1;
            while ($area = mysqli_fetch_assoc($areas)) : ?>
            <tr>
                <td><?php echo $count++; ?></td>
                <td><?php echo htmlspecialchars($area['area_name']); ?></td>
                <td><?php echo htmlspecialchars($area['description']); ?></td>
                <td><?php echo htmlspecialchars($area['postcode']); ?></td>
                <td>
                    <button class="edit-btn" onclick="openAreaEditModal(
                             <?php echo $area['area_id']; ?>,
                            '<?php echo addslashes($area['area_name']); ?>',
                            '<?php echo addslashes($area['description']); ?>',
                            '<?php echo addslashes($area['postcode']); ?>'
                    )">Edit</button>
                    <button class="delete-btn" onclick="openAreaDeleteModal(
                            <?php echo $area['area_id']; ?>,
                           '<?php echo addslashes($area['area_name']); ?>'
                    )">Delete</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1) : ?>
<div class="area-pagination">
    <span class="area-page-info">
        Showing <?= $offset + 1 ?>–<?= min($offset + $areas_per_page, $total_areas) ?> of <?= $total_areas ?> areas
    </span>
    <div class="area-page-btns">
        <?php if ($area_page > 1) : ?>
        <a href="dashboard.php?page=manage-area&area_page=<?= $area_page - 1 ?>" class="area-page-btn">
            &laquo; Prev
        </a>
        <?php endif; ?>

        <?php for ($p = 1; $p <= $total_pages; $p++) : ?>
        <a href="dashboard.php?page=manage-area&area_page=<?= $p ?>"
           class="area-page-btn <?= $p === $area_page ? 'area-page-btn--active' : '' ?>">
            <?= $p ?>
        </a>
        <?php endfor; ?>

        <?php if ($area_page < $total_pages) : ?>
        <a href="dashboard.php?page=manage-area&area_page=<?= $area_page + 1 ?>" class="area-page-btn">
            Next &raquo;
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
</div>

<!-- Add Area Modal Form -->
    <div id="add-area-modal" class="area-modal-overlay" style="display:none;">
        <div class="area-modal-box">

            <div class="area-modal-header">
            <h3>Add New Area</h3>
            <span class="area-modal-close-btn" onclick="closeAreaModal('add-area-modal')">&times;</span>
            </div>
            
            <div id="area-add-error" class="alert-box error-box" style="display:none;"></div>

           <div class="area-modal-body">
                <div class="area-modal-field">
                   <label>Area Name</label>
                   <input type="text" id="area-add-name" placeholder="Enter area name">
                </div>
                <div class="area-modal-field">
                   <label>Description</label>
                   <textarea id="area-add-desc" placeholder="Enter a short description of the area"></textarea>
                </div>
                <div class="area-modal-field">
                   <label>Postcode</label>
                   <input type="text" id="area-add-postcode" placeholder="Enter in capitals e.g. AL10 9AB">
                </div>
            </div>

            <div class="area-modal-footer">
               <button class="area-modal-cancel-btn" onclick="closeAreaModal('add-area-modal')">Cancel</button>
               <button class="area-modal-submit-btn" onclick="submitAreaAdd()">Add Area</button>
            </div>
        </div>
    </div>

<!-- Edit Area Modal Form -->
 <div id="area-edit-modal" class="area-modal-overlay" style="display:none;">
    <div class="area-modal-box">

        <div class="area-modal-header">
             <h3>Edit Area</h3>
             <span class="area-modal-close-btn" onclick="closeAreaModal('area-edit-modal')">&times;</span>
        </div>

        <div id="area-edit-error" class="alert-box error-box" style="display:none;"></div>
        <input type="hidden" id="area-edit-id">

        <div class="area-modal-body">
            <div class="area-modal-field">
                <label>Area Name</label>
                <input type="text" id="area-edit-name" placeholder="Enter area name">
            </div>
            <div class="area-modal-field">
                <label>Description</label>
                <textarea id="area-edit-desc" placeholder="Enter a short description"></textarea>
            </div>
            <div class="area-modal-field">
                <label>Postcode</label>
                <input type="text" id="area-edit-postcode" placeholder="Enter in capitals e.g. AL10 9AB">
            </div>
        </div>

        <div class="area-modal-footer">
            <button class="area-modal-cancel-btn" onclick="closeAreaModal('area-edit-modal')">Cancel</button>
            <button class="area-modal-submit-btn" onclick="submitAreaEdit()">Save Changes</button>
        </div>
    </div>
</div>

<!-- Delete Area Modal -->
<div id="area-delete-modal" class="area-modal-overlay" style="display:none;">
    <div class="area-modal-box area-modal-box-small">

        <div class="area-modal-header">
            <h3>Confirm Delete</h3>
            <span class="area-modal-close-btn" onclick="closeAreaModal('area-delete-modal')">&times;</span>
        </div>

        <div class="area-modal-body">
            <p>Are you sure you want to delete <strong id="area-delete-name"></strong>?</p>
            <p class="area-modal-warning">This action cannot be undone.</p>
        </div>

        <input type="hidden" id="area-delete-id">
        <div class="area-modal-footer">
            <button class="area-modal-cancel-btn" onclick="closeAreaModal('area-delete-modal')">Cancel</button>
            <button class="area-modal-delete-btn" onclick="submitAreaDelete()">Yes, Delete</button>
        </div>
    </div>
</div>

<!-- JavaScript validation -->
<script>
function openAreaAddModal() {
    document.getElementById('area-add-name').value = '';
    document.getElementById('area-add-desc').value = '';
    document.getElementById('area-add-postcode').value = '';
    document.getElementById('area-add-error').style.display = 'none';
    openAreaModal('add-area-modal');
}

function openAreaModal(id) {
    document.getElementById(id).style.display = 'flex';
}

function closeAreaModal(id) {
    document.getElementById(id).style.display = 'none';
}

function openAreaEditModal(id, name, desc, postcode) {
    document.getElementById('area-edit-id').value = id;
    document.getElementById('area-edit-name').value = name;
    document.getElementById('area-edit-desc').value = desc;
    document.getElementById('area-edit-postcode').value = postcode;
    document.getElementById('area-edit-error').style.display = 'none';
    openAreaModal('area-edit-modal');
}

function openAreaDeleteModal(id, name) {
    document.getElementById('area-delete-id').value = id;
    document.getElementById('area-delete-name').innerText = name;
    openAreaModal('area-delete-modal');
}

function showAreaMessage(message, type) {
    const box = document.getElementById('area-action-message');
    box.className = 'alert-box ' + (type === 'success' ? 'success-box' : 'error-box');
    box.innerText = message;
    box.style.display = 'block';
    setTimeout(() => { box.style.display = 'none'; }, 4000);
}

function sendAreaRequest(data, callback) {
    const formData = new FormData();
    for (const key in data) formData.append(key, data[key]);
    fetch('../pages/manage-area.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(callback)
        .catch(() => showAreaMessage('Something went wrong. Please try again.', 'error'));
}

function submitAreaAdd() {
    const name     = document.getElementById('area-add-name').value.trim();
    const desc     = document.getElementById('area-add-desc').value.trim();
    const postcode = document.getElementById('area-add-postcode').value.trim();
    const errorBox = document.getElementById('area-add-error');

    if (!name)     { errorBox.style.display = 'block'; errorBox.innerText = 'Please enter an area name.'; return; }
    if (!desc)     { errorBox.style.display = 'block'; errorBox.innerText = 'Please enter a description.'; return; }
    if (!postcode) { errorBox.style.display = 'block'; errorBox.innerText = 'Please enter a postcode.'; return; }

    sendAreaRequest({ action: 'add', area_name: name, description: desc, postcode: postcode }, (res) => {
        if (res.success) {
            closeAreaModal('area-add-modal');
            showAreaMessage(res.message, 'success');
            setTimeout(() => {const url = new URL(window.location.href);
            window.location.href = 'dashboard.php?page=manage-area&area_page=' + (url.searchParams.get('area_page') || 1);}, 1500);
        } else {
            errorBox.style.display = 'block';
            errorBox.innerText = res.message;
        }
    });
}

function submitAreaEdit() {
    const id       = document.getElementById('area-edit-id').value;
    const name     = document.getElementById('area-edit-name').value.trim();
    const desc     = document.getElementById('area-edit-desc').value.trim();
    const postcode = document.getElementById('area-edit-postcode').value.trim();
    const errorBox = document.getElementById('area-edit-error');

    if (!name)     { errorBox.style.display = 'block'; errorBox.innerText = 'Please enter an area name.'; return; }
    if (!desc)     { errorBox.style.display = 'block'; errorBox.innerText = 'Please enter a description.'; return; }
    if (!postcode) { errorBox.style.display = 'block'; errorBox.innerText = 'Please enter a postcode.'; return; }

    sendAreaRequest({ action: 'edit', area_id: id, area_name: name, description: desc, postcode: postcode }, (res) => {
        if (res.success) {
            closeAreaModal('area-edit-modal');
            showAreaMessage(res.message, 'success');
            setTimeout(() => {const url = new URL(window.location.href);
            window.location.href = 'dashboard.php?page=manage-area&area_page=' + (url.searchParams.get('area_page') || 1);}, 1500);
        } else {
            errorBox.style.display = 'block';
            errorBox.innerText = res.message;
        }
    });
}

function submitAreaDelete() {
    const id = document.getElementById('area-delete-id').value;
    sendAreaRequest({ action: 'delete', area_id: id }, (res) => {
        closeAreaModal('area-delete-modal');
        showAreaMessage(res.message, res.success ? 'success' : 'error');
        if (res.success) setTimeout(() => {const url = new URL(window.location.href);
         window.location.href = 'dashboard.php?page=manage-area&area_page=' + (url.searchParams.get('area_page') || 1);}, 1500);
    });
}

function filterAreaTable() {
    const input = document.getElementById('area-search-input').value.toLowerCase();
    const rows  = document.querySelectorAll('#area-table-body tr');
    rows.forEach(row => {
        const name = row.cells[1].innerText.toLowerCase();
        row.style.display = name.includes(input) ? '' : 'none';
    });
}

window.addEventListener('click', (e) => {
    ['add-area-modal', 'area-edit-modal', 'area-delete-modal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal && e.target === modal) closeAreaModal(id);
    });
});
</script>