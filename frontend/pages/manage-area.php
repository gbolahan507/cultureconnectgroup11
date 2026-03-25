<?php
// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$allowedRoles = ['Council Administrator', 'Council_member'];

// Check if user is logged in AND has the correct role
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    // Redirect unauthorized users
    header("Location: ../pages/dashboard.php?page=home");
    exit();
}
?>

<div class="manage-area-page">

    <div class="manage-area-header">
        <div class="icon-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
            </svg>
        </div>
        <div class="manage-area-text">
            <h2>Manage Area</h2>
            <p>Here you can View, Add, Edit, or Remove Areas under the Council's Jurisdiction.</p>
        </div>
    </div>

    <!-- Actions -->
    <div class="manage-area-actions">
        <button id="add-area-btn">+ Add Area</button>
    </div>

    <div class="table-filters">
        <input type="text" id="search-area" placeholder="Search by area name">
    </div>

    <!-- Area Table -->
    <div class="area-table-wrapper">
        <table class="area-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Area Name</th>
                    <th>Description</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="area-table-body">
                <tr>
                    <td>1</td>
                    <td>Downtown</td>
                    <td>Main business district</td>
                    <td>2026-03-21</td>
                    <td>
                        <button class="edit-btn">Edit</button>
                        <button class="delete-btn">Delete</button>
                    </td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Northside</td>
                    <td>Residential area</td>
                    <td>2026-03-19</td>
                    <td>
                        <button class="edit-btn">Edit</button>
                        <button class="delete-btn">Delete</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Add Area Form -->
    <div class="add-area-form" id="add-area-form" style="display:none;">
        <h3>Add New Area</h3>
        <form id="addAreaForm">
        <label for="area-name">Area Name:</label>
        <input type="text" id="area-name" name="area_name" placeholder="Name of the new Area" required>

        <label for="area-desc">Description:</label>
        <textarea id="area-desc" name="area_description" rows="2" placeholder="Enter a short description about the area, e.g., what it’s famous for"
    required></textarea>

        <label for="area-postcode">Postcode:</label>
        <input type="text" id="area-postcode" name="area_postcode" placeholder="Enter postcode in CAPITAL LETTERS" required>

        <label for="area-latitude">Latitude:</label>
        <input type="text" id="area-latitude" name="area_latitude" required readonly>

        <label for="area-longitude">Longitude:</label>
        <input type="text" id="area-longitude" name="area_longitude" required readonly>

        <button type="submit" id="add-area">Add Area</button>

        <button type="button" id="cancel-add">Cancel</button>
    </form>
    </div>

</div>

<!-- JavaScript validation -->
<script>
const addBtn = document.getElementById('add-area-btn');
const addForm = document.getElementById('add-area-form');
const cancelBtn = document.getElementById('cancel-add');
const form = document.getElementById('addAreaForm');

addBtn.addEventListener('click', () => {
    addForm.style.display = 'block';
    addBtn.style.display = 'none';
});

cancelBtn.addEventListener('click', () => {
    addForm.style.display = 'none';
    addBtn.style.display = 'inline-block';
    form.reset();
});

// Load JSON file
let postcodeData = {};
fetch('../data/postcodes.json')
    .then(response => response.json())
    .then(data => postcodeData = data)
    .catch(err => console.error('Could not load postcode JSON', err));

// Auto-fill latitude & longitude
document.getElementById('area-postcode').addEventListener('change', (e) => {
    const code = e.target.value.trim().toUpperCase();

    if (postcodeData[code]) {
        document.getElementById('area-latitude').value = postcodeData[code].lat;
        document.getElementById('area-longitude').value = postcodeData[code].lon;
    } else {
        document.getElementById('area-latitude').value = '';
        document.getElementById('area-longitude').value = '';
    }
});

// Form validation
form.addEventListener('submit', (e) => {
    e.preventDefault();

    const name = document.getElementById('area-name').value.trim();
    const desc = document.getElementById('area-desc').value.trim();
    const postcode = document.getElementById('area-postcode').value.trim();
    const lat = document.getElementById('area-latitude').value.trim();
    const lon = document.getElementById('area-longitude').value.trim();

    if (!name || !desc || !postcode || !lat || !lon) {
        alert("Please fill in all fields or provide a valid postcode.");
        return;
    }

    alert("Form is valid! Ready to submit to server.");
    // Here you would submit via AJAX or real form submission
});
</script>