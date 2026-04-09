<?php ob_start(); ?>
<?php
// ============================================================
// AJAX HANDLER
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($conn)) include '../db_connection.php';
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
 
    $sme_id = $_SESSION['sme_id'] ?? null;
    if (!$sme_id) { echo json_encode(['success' => false, 'message' => 'Not authenticated.']); exit(); }
 
    // ── Get Orders ────────────────────────────────────────────
    if ($_POST['action'] === 'get_orders') {
        $status_filter = $_POST['status'] ?? 'all';
 
        if ($status_filter === 'all') {
            $stmt = $conn->prepare("
                SELECT DISTINCT o.order_id, o.status, o.created_at,
                       u.email_address AS customer_email,
                       CONCAT(rp.first_name, ' ', rp.last_name) AS customer_name,
                       COUNT(oi.order_item_id) AS item_count,
                       SUM(oi.price * oi.quantity) AS sme_total
                FROM orders o
                JOIN order_item oi  ON o.order_id   = oi.order_id
                JOIN listings l     ON oi.listing_id = l.listing_id
                JOIN users u        ON o.user_id     = u.user_id
                LEFT JOIN resident_profiles rp ON o.user_id = rp.user_id
                WHERE l.sme_id = ?
                GROUP BY o.order_id
                ORDER BY o.created_at DESC
            ");
            $stmt->bind_param("i", $sme_id);
        } else {
            $stmt = $conn->prepare("
                SELECT DISTINCT o.order_id, o.status, o.created_at,
                       u.email_address AS customer_email,
                       CONCAT(rp.first_name, ' ', rp.last_name) AS customer_name,
                       COUNT(oi.order_item_id) AS item_count,
                       SUM(oi.price * oi.quantity) AS sme_total
                FROM orders o
                JOIN order_item oi  ON o.order_id   = oi.order_id
                JOIN listings l     ON oi.listing_id = l.listing_id
                JOIN users u        ON o.user_id     = u.user_id
                LEFT JOIN resident_profiles rp ON o.user_id = rp.user_id
                WHERE l.sme_id = ? AND o.status = ?
                GROUP BY o.order_id
                ORDER BY o.created_at DESC
            ");
            $stmt->bind_param("is", $sme_id, $status_filter);
        }
 
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        while ($row = $result->fetch_assoc()) $orders[] = $row;
        $stmt->close();
 
        echo json_encode(['success' => true, 'orders' => $orders]);
        exit();
    }
 
    // ── Get Order Details ─────────────────────────────────────
    if ($_POST['action'] === 'get_order_details') {
        $order_id = intval($_POST['order_id'] ?? 0);
 
        $order_stmt = $conn->prepare("
            SELECT o.order_id, o.status, o.created_at,
                   u.email_address AS customer_email,
                   CONCAT(rp.first_name, ' ', rp.last_name) AS customer_name,
                   rp.phone AS customer_phone
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            LEFT JOIN resident_profiles rp ON o.user_id = rp.user_id
            WHERE o.order_id = ? LIMIT 1
        ");
        $order_stmt->bind_param("i", $order_id);
        $order_stmt->execute();
        $order = $order_stmt->get_result()->fetch_assoc();
        $order_stmt->close();
 
        if (!$order) { echo json_encode(['success' => false, 'message' => 'Order not found.']); exit(); }
 
        $items_stmt = $conn->prepare("
            SELECT oi.quantity, oi.price,
                   l.title, l.listing_id,
                   pc.category_name,
                   li.image_url AS primary_image
            FROM order_item oi
            JOIN listings l                        ON oi.listing_id     = l.listing_id
            JOIN product_service ps                ON l.item_id         = ps.item_id
            JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
            JOIN product_service_categories pc     ON pss.category_id   = pc.category_id
            LEFT JOIN listing_images li            ON l.listing_id      = li.listing_id AND li.is_primary = 1
            WHERE oi.order_id = ? AND l.sme_id = ?
            ORDER BY oi.order_item_id
        ");
        $items_stmt->bind_param("ii", $order_id, $sme_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        $items        = [];
        while ($row = $items_result->fetch_assoc()) $items[] = $row;
        $items_stmt->close();
 
        $sme_total = array_sum(array_map(fn($i) => floatval($i['price']) * intval($i['quantity']), $items));
 
        echo json_encode(['success' => true, 'order' => $order, 'items' => $items, 'sme_total' => number_format($sme_total, 2)]);
        exit();
    }
 
    // ── Confirm Order ─────────────────────────────────────────
    if ($_POST['action'] === 'confirm_order') {
        $order_id = intval($_POST['order_id'] ?? 0);
 
        $check = $conn->prepare("
            SELECT o.status FROM orders o
            JOIN order_item oi ON o.order_id = oi.order_id
            JOIN listings l ON oi.listing_id = l.listing_id
            WHERE o.order_id = ? AND l.sme_id = ? LIMIT 1
        ");
        $check->bind_param("ii", $order_id, $sme_id);
        $check->execute();
        $order = $check->get_result()->fetch_assoc();
        $check->close();
 
        if (!$order) { echo json_encode(['success' => false, 'message' => 'Order not found.']); exit(); }
        if ($order['status'] !== 'processing') {
            echo json_encode(['success' => false, 'message' => 'Only processing orders can be confirmed.']); exit();
        }
 
        $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Order confirmed and marked as completed.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to confirm order.']);
        }
        $stmt->close();
        exit();
    }
 
    // ── Cancel Order ──────────────────────────────────────────
    if ($_POST['action'] === 'cancel_order') {
        $order_id = intval($_POST['order_id'] ?? 0);
 
        $check = $conn->prepare("
            SELECT o.status FROM orders o
            JOIN order_item oi ON o.order_id = oi.order_id
            JOIN listings l ON oi.listing_id = l.listing_id
            WHERE o.order_id = ? AND l.sme_id = ? LIMIT 1
        ");
        $check->bind_param("ii", $order_id, $sme_id);
        $check->execute();
        $order = $check->get_result()->fetch_assoc();
        $check->close();
 
        if (!$order) { echo json_encode(['success' => false, 'message' => 'Order not found.']); exit(); }
        if ($order['status'] !== 'processing') {
            echo json_encode(['success' => false, 'message' => 'Only processing orders can be cancelled.']); exit();
        }
 
        $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Order cancelled.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to cancel order.']);
        }
        $stmt->close();
        exit();
    }
 
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit();
}
 
// ============================================================
// PAGE GUARD
// ============================================================
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'SME') {
    header('Location: ../pages/dashboard.php?page=home');
    exit();
}
?>

<div class="mgo-page">
 
    <?php
        $icon     = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                     </svg>';
        $title    = 'Manage Orders';
        $subtitle = 'View and manage bookings made for your listings.';
        include '../components/section_header.php';
    ?>
 
    <div class="mgo-tabs">
        <button class="mgo-tab mgo-tab--active" onclick="mgoSetTab(this, 'all')">All</button>
        <button class="mgo-tab" onclick="mgoSetTab(this, 'processing')">
            Processing
            <span class="mgo-tab-badge" id="mgo-processing-count" style="display:none;"></span>
        </button>
        <button class="mgo-tab" onclick="mgoSetTab(this, 'completed')">Completed</button>
        <button class="mgo-tab" onclick="mgoSetTab(this, 'cancelled')">Cancelled</button>
    </div>
 
    <div id="mgo-count-label" class="mgo-count-label"></div>
 
    <div id="mgo-loading" class="mgo-loading">
        <div class="mgo-spinner"></div>
        <p>Loading orders…</p>
    </div>
 
    <div class="mgo-table-wrapper" id="mgo-table-wrapper" style="display:none;">
        <table class="mgo-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Your Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="mgo-table-body"></tbody>
        </table>
    </div>
 
    <div id="mgo-empty" class="mgo-empty" style="display:none;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="52" height="52">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
        </svg>
        <p id="mgo-empty-msg">No orders found.</p>
    </div>
 
</div>

<!-- ORDER DETAIL MODAL -->
<div id="mgo-detail-modal" class="mgo-modal-overlay" style="display:none;">
    <div class="mgo-modal-box">
        <div class="mgo-modal-header">
            <h3>Order Details</h3>
            <span class="mgo-modal-close-btn" onclick="mgoCloseDetail()">&times;</span>
        </div>
        <div class="mgo-modal-body" id="mgo-detail-body"></div>
        <div class="mgo-modal-footer" id="mgo-detail-footer">
            <button class="mgo-modal-close-btn-footer" onclick="mgoCloseDetail()">Close</button>
        </div>
    </div>
</div>
 
 
<!-- CONFIRM ORDER MODAL -->
<div id="mgo-confirm-modal" class="mgo-modal-overlay" style="display:none;">
    <div class="mgo-modal-box mgo-modal-box--sm">
        <div class="mgo-modal-header mgo-modal-header--success">
            <h3>Confirm Order</h3>
            <span class="mgo-modal-close-btn" onclick="mgoCloseConfirmModal()">&times;</span>
        </div>
        <div class="mgo-modal-body mgo-modal-body--padded">
            <p class="mgo-confirm-text">Mark <strong id="mgo-confirm-order-label"></strong> as completed? This confirms the booking has been fulfilled.</p>
            <div class="mgo-confirm-info">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                The customer will see their order status updated to Completed.
            </div>
        </div>
        <div class="mgo-modal-footer">
            <button class="mgo-modal-close-btn-footer" onclick="mgoCloseConfirmModal()">Cancel</button>
            <button class="mgo-btn-success" id="mgo-confirm-btn" onclick="mgoConfirmOrder()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                Yes, Mark Completed
            </button>
        </div>
    </div>
</div>
 
 
<!-- CANCEL ORDER MODAL -->
<div id="mgo-cancel-modal" class="mgo-modal-overlay" style="display:none;">
    <div class="mgo-modal-box mgo-modal-box--sm">
        <div class="mgo-modal-header mgo-modal-header--danger">
            <h3>Cancel Order</h3>
            <span class="mgo-modal-close-btn" onclick="mgoCloseCancelModal()">&times;</span>
        </div>
        <div class="mgo-modal-body mgo-modal-body--padded">
            <p class="mgo-confirm-text">Are you sure you want to cancel <strong id="mgo-cancel-order-label"></strong>?</p>
            <div class="mgo-cancel-warning">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                This cannot be undone. The customer will see their order as cancelled.
            </div>
        </div>
        <div class="mgo-modal-footer">
            <button class="mgo-modal-close-btn-footer" onclick="mgoCloseCancelModal()">Keep Order</button>
            <button class="mgo-btn-danger" id="mgo-cancel-confirm-btn" onclick="mgoCancelOrder()">Yes, Cancel Order</button>
        </div>
    </div>
</div>

<script>
    let mgoCurrentTab    = 'all';
    let mgoActionOrderId = null;
 
    document.addEventListener('DOMContentLoaded', () => {
        mgoLoad('all');
        mgoLoadProcessingCount();
    });
 
    function mgoSetTab(btn, status) {
        document.querySelectorAll('.mgo-tab').forEach(t => t.classList.remove('mgo-tab--active'));
        btn.classList.add('mgo-tab--active');
        mgoCurrentTab = status;
        mgoLoad(status);
    }
 
    function mgoLoadProcessingCount() {
        const fd = new FormData();
        fd.append('action', 'get_orders');
        fd.append('status', 'processing');
 
        fetch('../pages/manage-orders.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data  = JSON.parse(text);
                    const badge = document.getElementById('mgo-processing-count');
                    if (data.success && data.orders.length > 0 && badge) {
                        badge.textContent    = data.orders.length;
                        badge.style.display  = 'inline-flex';
                    }
                } catch(e) {}
            });
    }
 
    function mgoLoad(status) {
        const loading = document.getElementById('mgo-loading');
        const wrapper = document.getElementById('mgo-table-wrapper');
        const empty   = document.getElementById('mgo-empty');
        const count   = document.getElementById('mgo-count-label');
 
        loading.style.display = 'flex';
        wrapper.style.display = 'none';
        empty.style.display   = 'none';
        count.textContent     = '';
 
        const fd = new FormData();
        fd.append('action', 'get_orders');
        fd.append('status', status);
 
        fetch('../pages/manage-orders.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    loading.style.display = 'none';
 
                    if (data.success && data.orders.length > 0) {
                        count.textContent = `${data.orders.length} order${data.orders.length !== 1 ? 's' : ''} found`;
                        const tbody = document.getElementById('mgo-table-body');
                        tbody.innerHTML = '';
                        data.orders.forEach(o => tbody.appendChild(mgoBuildRow(o)));
                        wrapper.style.display = 'block';
                    } else {
                        const msgs = { all: 'No orders yet. Orders will appear here when customers make bookings.', processing: 'No orders currently processing.', completed: 'No completed orders.', cancelled: 'No cancelled orders.' };
                        document.getElementById('mgo-empty-msg').textContent = msgs[status] || 'No orders found.';
                        empty.style.display = 'flex';
                    }
                } catch(e) { loading.style.display = 'none'; empty.style.display = 'flex'; }
            })
            .catch(() => { loading.style.display = 'none'; empty.style.display = 'flex'; });
    }
 
    function mgoBuildRow(o) {
        const tr       = document.createElement('tr');
        const badgeCls = { processing: 'mgo-badge--processing', completed: 'mgo-badge--completed', cancelled: 'mgo-badge--cancelled' }[o.status] || '';
        const date     = new Date(o.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        const customer = o.customer_name && o.customer_name.trim() !== ' ' ? o.customer_name : o.customer_email;
 
        let actions = `<button class="mgo-view-btn" onclick="mgoOpenDetail(${o.order_id})">View</button>`;
        if (o.status === 'processing') {
            actions += `<button class="mgo-confirm-btn-tbl" onclick="mgoOpenConfirmModal(${o.order_id})">Confirm</button>`;
            actions += `<button class="mgo-cancel-btn-tbl" onclick="mgoOpenCancelModal(${o.order_id})">Cancel</button>`;
        }
 
        tr.innerHTML = `
            <td class="mgo-order-id">#${o.order_id}</td>
            <td class="mgo-customer-cell">
                <p class="mgo-customer-name">${mgoEsc(customer)}</p>
                <p class="mgo-customer-email">${mgoEsc(o.customer_email)}</p>
            </td>
            <td>${date}</td>
            <td>${o.item_count} item${o.item_count != 1 ? 's' : ''}</td>
            <td class="mgo-total-cell">£${parseFloat(o.sme_total).toFixed(2)}</td>
            <td><span class="mgo-badge ${badgeCls}">${o.status.charAt(0).toUpperCase() + o.status.slice(1)}</span></td>
            <td class="mgo-actions-cell">${actions}</td>
        `;
        return tr;
    }
 
    function mgoOpenDetail(order_id) {
        mgoActionOrderId = order_id;
        const modal  = document.getElementById('mgo-detail-modal');
        const body   = document.getElementById('mgo-detail-body');
        const footer = document.getElementById('mgo-detail-footer');
 
        body.innerHTML   = '<div class="mgo-detail-loading"><div class="mgo-spinner"></div> Loading…</div>';
        footer.innerHTML = `<button class="mgo-modal-close-btn-footer" onclick="mgoCloseDetail()">Close</button>`;
        modal.style.display = 'flex';
 
        const fd = new FormData();
        fd.append('action',   'get_order_details');
        fd.append('order_id', order_id);
 
        fetch('../pages/manage-orders.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        body.innerHTML = mgoBuildDetailHTML(data.order, data.items, data.sme_total);
                        if (data.order.status === 'processing') {
                            footer.innerHTML = `
                                <button class="mgo-modal-close-btn-footer" onclick="mgoCloseDetail()">Close</button>
                                <button class="mgo-btn-danger" onclick="mgoCloseDetail(); mgoOpenCancelModal(${order_id})">Cancel Order</button>
                                <button class="mgo-btn-success" onclick="mgoCloseDetail(); mgoOpenConfirmModal(${order_id})">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    Mark Completed
                                </button>
                            `;
                        }
                    } else {
                        body.innerHTML = `<p class="mgo-detail-error">${mgoEsc(data.message)}</p>`;
                    }
                } catch(e) { body.innerHTML = '<p class="mgo-detail-error">Failed to load order details.</p>'; }
            });
    }
 
    function mgoBuildDetailHTML(order, items, sme_total) {
        const date     = new Date(order.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        const badgeCls = { processing: 'mgo-badge--processing', completed: 'mgo-badge--completed', cancelled: 'mgo-badge--cancelled' }[order.status] || '';
        const customer = order.customer_name && order.customer_name.trim() !== ' ' ? order.customer_name : 'Not provided';
 
        let html = `
            <div class="mgo-detail-meta">
                <div class="mgo-detail-meta-row"><span class="mgo-detail-label">Order</span><span class="mgo-detail-value">#${order.order_id}</span></div>
                <div class="mgo-detail-meta-row"><span class="mgo-detail-label">Date</span><span class="mgo-detail-value">${date}</span></div>
                <div class="mgo-detail-meta-row"><span class="mgo-detail-label">Status</span><span class="mgo-badge ${badgeCls}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></div>
                <div class="mgo-detail-meta-row"><span class="mgo-detail-label">Your Total</span><span class="mgo-detail-value mgo-detail-total">£${sme_total}</span></div>
            </div>
            <div class="mgo-detail-section-title">Customer Information</div>
            <div class="mgo-detail-customer">
                <div class="mgo-detail-customer-row">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                    ${mgoEsc(customer)}
                </div>
                <div class="mgo-detail-customer-row">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                    <a href="mailto:${mgoEsc(order.customer_email)}">${mgoEsc(order.customer_email)}</a>
                </div>
                ${order.customer_phone ? `<div class="mgo-detail-customer-row">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 6v.75Z" /></svg>
                    <a href="tel:${mgoEsc(order.customer_phone)}">${mgoEsc(order.customer_phone)}</a>
                </div>` : ''}
            </div>
            <div class="mgo-detail-section-title">Items Ordered</div>
        `;
 
        items.forEach(item => {
            const imgSrc = item.primary_image ? `../uploads/listings_images/${mgoEsc(item.primary_image)}` : null;
            html += `
                <div class="mgo-detail-item">
                    <div class="mgo-detail-item-img">
                        ${imgSrc ? `<img src="${imgSrc}" alt="${mgoEsc(item.title)}" onerror="this.style.display='none'">` : ''}
                    </div>
                    <div class="mgo-detail-item-info">
                        <p class="mgo-detail-item-cat">${mgoEsc(item.category_name)}</p>
                        <p class="mgo-detail-item-title">${mgoEsc(item.title)}</p>
                        <p class="mgo-detail-item-unit">£${parseFloat(item.price).toFixed(2)} × ${item.quantity}</p>
                    </div>
                    <p class="mgo-detail-item-subtotal">£${(parseFloat(item.price) * item.quantity).toFixed(2)}</p>
                </div>
            `;
        });
 
        return html;
    }
 
    function mgoCloseDetail() { document.getElementById('mgo-detail-modal').style.display = 'none'; }
 
    function mgoOpenConfirmModal(order_id) {
        mgoActionOrderId = order_id;
        document.getElementById('mgo-confirm-order-label').textContent = 'Order #' + order_id;
        document.getElementById('mgo-confirm-modal').style.display = 'flex';
    }
 
    function mgoCloseConfirmModal() { document.getElementById('mgo-confirm-modal').style.display = 'none'; mgoActionOrderId = null; }
 
    function mgoConfirmOrder() {
        if (!mgoActionOrderId) return;
        const btn = document.getElementById('mgo-confirm-btn');
        btn.disabled = true; btn.textContent = 'Confirming…';
 
        const fd = new FormData();
        fd.append('action',   'confirm_order');
        fd.append('order_id', mgoActionOrderId);
 
        fetch('../pages/manage-orders.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) { mgoCloseConfirmModal(); mgoShowToast(data.message, 'success'); mgoLoad(mgoCurrentTab); mgoLoadProcessingCount(); }
                    else mgoShowToast(data.message || 'Failed.', 'error');
                } catch(e) { mgoShowToast('Unexpected error.', 'error'); }
            })
            .catch(() => mgoShowToast('Network error.', 'error'))
            .finally(() => {
                btn.disabled  = false;
                btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg> Yes, Mark Completed`;
            });
    }
 
    function mgoOpenCancelModal(order_id) {
        mgoActionOrderId = order_id;
        document.getElementById('mgo-cancel-order-label').textContent = 'Order #' + order_id;
        document.getElementById('mgo-cancel-modal').style.display = 'flex';
    }
 
    function mgoCloseCancelModal() { document.getElementById('mgo-cancel-modal').style.display = 'none'; mgoActionOrderId = null; }
 
    function mgoCancelOrder() {
        if (!mgoActionOrderId) return;
        const btn = document.getElementById('mgo-cancel-confirm-btn');
        btn.disabled = true; btn.textContent = 'Cancelling…';
 
        const fd = new FormData();
        fd.append('action',   'cancel_order');
        fd.append('order_id', mgoActionOrderId);
 
        fetch('../pages/manage-orders.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) { mgoCloseCancelModal(); mgoShowToast(data.message, 'success'); mgoLoad(mgoCurrentTab); mgoLoadProcessingCount(); }
                    else mgoShowToast(data.message || 'Failed.', 'error');
                } catch(e) { mgoShowToast('Unexpected error.', 'error'); }
            })
            .catch(() => mgoShowToast('Network error.', 'error'))
            .finally(() => { btn.disabled = false; btn.textContent = 'Yes, Cancel Order'; });
    }
 
    function mgoShowToast(message, type) {
        const existing = document.getElementById('mgo-toast');
        if (existing) existing.remove();
        const toast = document.createElement('div');
        toast.id = 'mgo-toast'; toast.className = `mgo-toast mgo-toast--${type}`; toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('mgo-toast--show'), 10);
        setTimeout(() => { toast.classList.remove('mgo-toast--show'); setTimeout(() => toast.remove(), 300); }, 3000);
    }
 
    function mgoEsc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
</script>