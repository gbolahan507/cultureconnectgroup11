<?php
// Start session
session_start();

// Access control: only root_admin and council_member can access
//$allowedRoles = ['root_admin', 'council_member'];

//if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
    // Redirect unauthorized users to dashboard home
   // header("Location: ../dashboard.php?page=home");
   // exit();
//}


/* BACKEND DATABASE INTEGRATION REMINDER
   1. PHP Logic:
      - Fetch all pending requests:
          $query = "SELECT * FROM pending_registration ORDER BY created_at DESC";
          $requests = mysqli_query($conn, $query);

      - Loop through $requests to populate the table body:
          foreach($requests as $req){
              // Output table rows dynamically
              // Use $req['details'] for the View modal
              // Conditional buttons: Show Approve/Reject only if status == 'pending'
          }

   3. Actions:
      - Approve: Update 'status' = 'approved', move/copy relevant data to users/SME table
      - Reject: Update 'status' = 'rejected', optionally log reason or delete entry
      - View: Show $req['details'] in the modal
------------------------------------------------------------------ */
$requests = [
    ['id'=>1,'name'=>'John Doe','type'=>'Resident','email'=>'john@email.com','date_submitted'=>'2026-03-21','status'=>'pending','details'=>'Address: 123 Street, DOB: 1990-01-01'],
    ['id'=>2,'name'=>'Alice Smith','type'=>'SME','email'=>'alice@business.com','date_submitted'=>'2026-03-20','status'=>'pending','details'=>'Business: Alice Bakery, License: 12345'],
    ['id'=>3,'name'=>'Bob Johnson','type'=>'Resident','email'=>'bob@email.com','date_submitted'=>'2026-03-19','status'=>'approved','details'=>'Address: 456 Avenue, DOB: 1985-05-05'],
    ['id'=>4,'name'=>'Clara Business','type'=>'SME','email'=>'clara@shop.com','date_submitted'=>'2026-03-18','status'=>'rejected','details'=>'Business: Clara Shop, License: 67890']
];
?>

<div class="manage-users-page">

    <div class="manage-users-header">
        <div class="icon-wrapper">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
        </svg>
        </div>

    <div class="page-header">
        <h2>Manage Users</h2>
    </div>

    <!-- Tabs for filtering -->
    <div class="status-tabs">
        <button class="tab active" data-status="pending">Requests</button>
        <button class="tab" data-status="approved">Approved</button>
        <button class="tab" data-status="rejected">Rejected</button>
    </div>

    <!-- Search bar -->
    <div class="search-container">
        <input type="text" id="searchInput" placeholder="Search by name, email, or business">
    </div>

    <!-- Users Table -->
    <div class="table-scroll">
    <table class="users-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Email</th>
                <th>Date Submitted</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="usersTableBody">
            <?php foreach($requests as $req): ?>
            <tr data-status="<?= $req['status'] ?>" data-comments="<?= $req['comments'] ?? '' ?>">
                <td><?= $req['name'] ?></td>
                <td><?= $req['type'] ?></td>
                <td><?= $req['email'] ?></td>
                <td><?= $req['date_submitted'] ?></td>
                <td><?= ucfirst($req['status']) ?></td>
                <td class="actions">
                    <button class="view-btn" data-details="<?= htmlspecialchars($req['details']) ?>">View</button>
                    <?php if($req['status'] === 'pending'): ?>
                        <button class="approve-btn" data-id="<?= $req['id'] ?>">Approve</button>
                        <button class="reject-btn" data-id="<?= $req['id'] ?>">Reject</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- View Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>User Details</h3>
        <div id="modal-body"></div>
        <div id="modal-comments" style="margin-top: 10px; font-style: italic; color: #555;"></div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Reject Request</h3>
        <textarea id="rejectComment" placeholder="Add a comment (optional)" rows="4"></textarea>
        <button id="submitReject">Submit</button>
    </div>
</div>

<script>
// --- View Modal Logic ---
const modal = document.getElementById('viewModal');
const modalBody = document.getElementById('modal-body');
const modalComments = document.getElementById('modal-comments');
const closeBtn = modal.querySelector('.close');

document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        modalBody.innerHTML = btn.dataset.details.replace(/\n/g,'<br>');
        const row = btn.closest('tr');
        const comment = row.dataset.comments;
        modalComments.innerHTML = comment ? `<strong>Comment:</strong> ${comment}` : '';
        modal.style.display = 'block';
    });
});

closeBtn.onclick = () => modal.style.display = 'none';
window.onclick = e => { if(e.target === modal) modal.style.display = 'none'; }

// --- Reject Modal Logic ---
const rejectModal = document.getElementById('rejectModal');
const submitReject = document.getElementById('submitReject');
const rejectClose = rejectModal.querySelector('.close');
let currentRow = null;

document.querySelectorAll('.reject-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        currentRow = btn.closest('tr');
        rejectModal.style.display = 'block';
    });
});

submitReject.addEventListener('click', () => {
    const comment = document.getElementById('rejectComment').value;
    if(currentRow){
        currentRow.querySelector('td:nth-child(5)').textContent = 'Rejected';
        currentRow.dataset.comments = comment || '';
        currentRow.querySelector('.approve-btn')?.remove();
        currentRow.querySelector('.reject-btn')?.remove();
    }
    rejectModal.style.display = 'none';
    document.getElementById('rejectComment').value = '';
});

rejectClose.onclick = () => rejectModal.style.display = 'none';

// --- Approve Button Logic ---
document.querySelectorAll('.approve-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const row = btn.closest('tr');
        row.querySelector('td:nth-child(5)').textContent = 'Approved';
        row.querySelector('.approve-btn')?.remove();
        row.querySelector('.reject-btn')?.remove();
    });
});

// --- Tab Filtering ---
const tabs = document.querySelectorAll('.tab');
const rows = document.querySelectorAll('#usersTableBody tr');

tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        tabs.forEach(t=>t.classList.remove('active'));
        tab.classList.add('active');
        const status = tab.dataset.status;
        rows.forEach(row => {
            row.style.display = row.dataset.status === status ? '' : 'none';
        });
    });
});

// --- Search Filter ---
document.getElementById('searchInput').addEventListener('keyup', function(){
    const val = this.value.toLowerCase();
    rows.forEach(row=>{
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(val) ? '' : 'none';
    });
});
</script>