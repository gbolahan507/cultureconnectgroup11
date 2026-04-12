<?php ob_start(); ?>
<?php
// AJAX HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($conn)) include '../db_connection.php';
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
 
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) { echo json_encode(['success' => false, 'message' => 'Not authenticated.']); exit(); }
 
    //  Get Orders 
    if ($_POST['action'] === 'get_orders') {
        $status_filter = $_POST['status'] ?? 'all';
 
        if ($status_filter === 'all') {
            $stmt = $conn->prepare("
                SELECT o.order_id, o.total_amount, o.status, o.created_at,
                       COUNT(oi.order_item_id) AS item_count
                FROM orders o
                JOIN order_items oi ON o.order_id = oi.order_id
                WHERE o.user_id = ?
                GROUP BY o.order_id
                ORDER BY o.created_at DESC
            ");
            $stmt->bind_param("i", $user_id);
        } else {
            $stmt = $conn->prepare("
                SELECT o.order_id, o.total_amount, o.status, o.created_at,
                       COUNT(oi.order_item_id) AS item_count
                FROM orders o
                JOIN order_items oi ON o.order_id = oi.order_id
                WHERE o.user_id = ? AND o.status = ?
                GROUP BY o.order_id
                ORDER BY o.created_at DESC
            ");
            $stmt->bind_param("is", $user_id, $status_filter);
        }
 
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        while ($row = $result->fetch_assoc()) $orders[] = $row;
        $stmt->close();
 
        echo json_encode(['success' => true, 'orders' => $orders]);
        exit();
    }
 
    // Get Order Details
    if ($_POST['action'] === 'get_order_details') {
        $order_id = intval($_POST['order_id'] ?? 0);
 
        $check = $conn->prepare("SELECT order_id, status, total_amount, created_at FROM orders WHERE order_id = ? AND user_id = ?");
        $check->bind_param("ii", $order_id, $user_id);
        $check->execute();
        $order = $check->get_result()->fetch_assoc();
        $check->close();
 
        if (!$order) { echo json_encode(['success' => false, 'message' => 'Order not found.']); exit(); }
 
        $items_stmt = $conn->prepare("
            SELECT oi.quantity, oi.price,
                   l.title, l.listing_id,
                   pc.category_name,
                   sp.business_name,
                   li.image_url AS primary_image
            FROM order_items oi
            JOIN listings l                        ON oi.listing_id     = l.listing_id
            JOIN product_service ps                ON l.item_id         = ps.item_id
            JOIN product_service_subcategories pss ON ps.subcategory_id = pss.subcategory_id
            JOIN product_service_categories pc     ON pss.category_id   = pc.category_id
            JOIN sme_profiles sp                   ON l.sme_id          = sp.sme_id
            LEFT JOIN listing_images li            ON l.listing_id      = li.listing_id AND li.is_primary = 1
            WHERE oi.order_id = ?
            ORDER BY sp.business_name, oi.order_item_id
        ");
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        $items        = [];
        while ($row = $items_result->fetch_assoc()) $items[] = $row;
        $items_stmt->close();
 
        echo json_encode(['success' => true, 'order' => $order, 'items' => $items]);
        exit();
    }
 
    //  Cancel Order 
    if ($_POST['action'] === 'cancel_order') {
        $order_id = intval($_POST['order_id'] ?? 0);
 
        $check = $conn->prepare("SELECT status FROM orders WHERE order_id = ? AND user_id = ?");
        $check->bind_param("ii", $order_id, $user_id);
        $check->execute();
        $order = $check->get_result()->fetch_assoc();
        $check->close();
 
        if (!$order) { echo json_encode(['success' => false, 'message' => 'Order not found.']); exit(); }
        if ($order['status'] !== 'processing') {
            echo json_encode(['success' => false, 'message' => 'Only processing orders can be cancelled.']);
            exit();
        }
 
        $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Order cancelled successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to cancel order.']);
        }
        $stmt->close();
        exit();
    }
 
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit();
}
 
// PAGE GUARD
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Resident', 'Council Member', 'Council Administrator'])) {
    header('Location: ../pages/dashboard.php?page=home');
    exit();
}
?>

<div class="mo-page">
 
    <?php
        $icon     = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" /></svg>';
        $title    = 'My Orders';
        $subtitle = 'View and manage your bookings and orders.';
        include '../components/section_header.php';
    ?>
 
    <div class="mo-tabs">
        <button class="mo-tab mo-tab--active" onclick="moSetTab(this, 'all')">All</button>
        <button class="mo-tab" onclick="moSetTab(this, 'processing')">Processing</button>
        <button class="mo-tab" onclick="moSetTab(this, 'completed')">Completed</button>
        <button class="mo-tab" onclick="moSetTab(this, 'cancelled')">Cancelled</button>
    </div>
 
    <div id="mo-count-label" class="mo-count-label"></div>
 
    <div id="mo-loading" class="mo-loading">
        <div class="mo-spinner"></div>
        <p>Loading orders…</p>
    </div>
 
    <div class="mo-table-wrapper" id="mo-table-wrapper" style="display:none;">
        <table class="mo-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="mo-table-body"></tbody>
        </table>
    </div>
 
    <div id="mo-empty" class="mo-empty" style="display:none;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="52" height="52">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
        </svg>
        <p id="mo-empty-msg">No orders found.</p>
        <a href="../pages/browse.php" class="mo-browse-btn">Browse Listings</a>
    </div>
 
</div>
 
<!-- ORDER DETAIL MODAL -->
<div id="mo-detail-modal" class="mo-modal-overlay" style="display:none;">
    <div class="mo-modal-box">
        <div class="mo-modal-header">
            <h3>Order Details</h3>
            <span class="mo-modal-close-btn" onclick="moCloseDetail()">&times;</span>
        </div>
        <div class="mo-modal-body" id="mo-detail-body"></div>
        <div class="mo-modal-footer">
            <button class="mo-modal-close-btn-footer" onclick="moCloseDetail()">Close</button>
        </div>
    </div>
</div>
 
 
<!-- CANCEL CONFIRM MODAL -->
<div id="mo-cancel-modal" class="mo-modal-overlay" style="display:none;">
    <div class="mo-modal-box mo-modal-box--sm">
        <div class="mo-modal-header mo-modal-header--danger">
            <h3>Cancel Order</h3>
            <span class="mo-modal-close-btn" onclick="moCloseCancelModal()">&times;</span>
        </div>
        <div class="mo-modal-body mo-modal-body--padded">
            <p class="mo-cancel-confirm-text">Are you sure you want to cancel <strong id="mo-cancel-order-label"></strong>?</p>
            <div class="mo-cancel-warning">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                This cannot be undone. The business will be notified that your booking has been cancelled.
            </div>
        </div>
        <div class="mo-modal-footer">
            <button class="mo-modal-close-btn-footer" onclick="moCloseCancelModal()">Keep Order</button>
            <button class="mo-btn-danger" id="mo-cancel-confirm-btn" onclick="moConfirmCancel()">Yes, Cancel Order</button>
        </div>
    </div>
</div>
 
<script>
    let moCurrentTab    = 'all';
    let moAllOrders   = [];
    let moPageNum     = 1;
    const MO_PER_PAGE = 6;
    let moCancelOrderId = null;
 
    document.addEventListener('DOMContentLoaded', () => moLoad('all'));
 
    function moSetTab(btn, status) {
        document.querySelectorAll('.mo-tab').forEach(t => t.classList.remove('mo-tab--active'));
        btn.classList.add('mo-tab--active');
        moCurrentTab = status;
        moLoad(status);
    }
 
    function moLoad(status) {
    const loading = document.getElementById('mo-loading');
    const wrapper = document.getElementById('mo-table-wrapper');
    const empty   = document.getElementById('mo-empty');
    const count   = document.getElementById('mo-count-label');

    moPageNum = 1;
    loading.style.display = 'flex';
    wrapper.style.display = 'none';
    empty.style.display   = 'none';
    count.textContent     = '';

    const fd = new FormData();
    fd.append('action', 'get_orders');
    fd.append('status', status);

    fetch('../pages/my-orders.php', { method: 'POST', body: fd })
        .then(r => r.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                loading.style.display = 'none';

                if (data.success && data.orders.length > 0) {
                    moAllOrders = data.orders;
                    moRenderPage();
                } else {
                    moAllOrders = [];
                    const msgs = {
                        all:        'You have no orders yet.',
                        processing: 'No orders currently processing.',
                        completed:  'No completed orders.',
                        cancelled:  'No cancelled orders.'
                    };
                    document.getElementById('mo-empty-msg').textContent = msgs[status] || 'No orders found.';
                    empty.style.display = 'flex';
                    moRemovePagination();
                }
            } catch(e) { loading.style.display = 'none'; empty.style.display = 'flex'; }
        })
        .catch(() => { loading.style.display = 'none'; empty.style.display = 'flex'; });
}

function moRenderPage() {
    const wrapper = document.getElementById('mo-table-wrapper');
    const count   = document.getElementById('mo-count-label');
    const total   = moAllOrders.length;
    const totalPages = Math.ceil(total / MO_PER_PAGE);
    const start   = (moPageNum - 1) * MO_PER_PAGE;
    const paged   = moAllOrders.slice(start, start + MO_PER_PAGE);

    count.textContent = `${total} order${total !== 1 ? 's' : ''} found`;
    const tbody = document.getElementById('mo-table-body');
    tbody.innerHTML = '';
    paged.forEach((o, i) => tbody.appendChild(moBuildRow(o, moPageNum === 1 ? i + 1 : (moPageNum - 1) * MO_PER_PAGE + i + 1)));
    wrapper.style.display = 'block';

    moRenderPagination(totalPages);
}

function moRenderPagination(totalPages) {
    moRemovePagination();
    if (totalPages <= 1) return;

    const wrapper = document.getElementById('mo-table-wrapper');
    const pag     = document.createElement('div');
    pag.id        = 'mo-pagination';
    pag.className = 'mo-pagination';

    const info       = document.createElement('span');
    info.className   = 'mo-page-info';
    const start      = (moPageNum - 1) * MO_PER_PAGE + 1;
    const end        = Math.min(moPageNum * MO_PER_PAGE, moAllOrders.length);
    info.textContent = `Showing ${start}–${end} of ${moAllOrders.length}`;
    pag.appendChild(info);

    const btns = document.createElement('div');
    btns.className = 'mo-page-btns';

    if (moPageNum > 1) {
        const prev     = document.createElement('button');
        prev.className = 'mo-page-btn';
        prev.textContent = '« Prev';
        prev.onclick   = () => { moPageNum--; moRenderPage(); };
        btns.appendChild(prev);
    }

    for (let p = 1; p <= totalPages; p++) {
        const btn = document.createElement('button');
        btn.className   = 'mo-page-btn' + (p === moPageNum ? ' mo-page-btn--active' : '');
        btn.textContent = p;
        btn.onclick     = ((pg) => () => { moPageNum = pg; moRenderPage(); })(p);
        btns.appendChild(btn);
    }

    if (moPageNum < totalPages) {
        const next     = document.createElement('button');
        next.className = 'mo-page-btn';
        next.textContent = 'Next »';
        next.onclick   = () => { moPageNum++; moRenderPage(); };
        btns.appendChild(next);
    }

    pag.appendChild(btns);
    wrapper.after(pag);
}

   function moRemovePagination() {
      const existing = document.getElementById('mo-pagination');
      if (existing) existing.remove();
}
 
    function moBuildRow(o, rowNum) {
        const tr       = document.createElement('tr');
        const badgeCls = { processing: 'mo-badge--processing', completed: 'mo-badge--completed', cancelled: 'mo-badge--cancelled' }[o.status] || '';
        const date     = new Date(o.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
 
        let actions = `<button class="mo-view-btn" onclick="moOpenDetail(${o.order_id})">View</button>`;
        if (o.status === 'processing') {
            actions += `<button class="mo-cancel-btn" onclick="moOpenCancelModal(${o.order_id})">Cancel</button>`;
        }
 
        tr.innerHTML = `
            <td class="mo-order-id">#${rowNum}</td>
            <td>${date}</td>
            <td>${o.item_count} item${o.item_count != 1 ? 's' : ''}</td>
            <td class="mo-total-cell">£${parseFloat(o.total_amount).toFixed(2)}</td>
            <td><span class="mo-badge ${badgeCls}">${o.status.charAt(0).toUpperCase() + o.status.slice(1)}</span></td>
            <td class="mo-actions-cell">${actions}</td>
        `;
        return tr;
    }
 
    function moOpenDetail(order_id) {
        const modal = document.getElementById('mo-detail-modal');
        const body  = document.getElementById('mo-detail-body');
        body.innerHTML = '<div class="mo-detail-loading"><div class="mo-spinner"></div> Loading…</div>';
        modal.style.display = 'flex';
 
        const fd = new FormData();
        fd.append('action', 'get_order_details');
        fd.append('order_id', order_id);
 
        fetch('../pages/my-orders.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) body.innerHTML = moBuildDetailHTML(data.order, data.items);
                    else body.innerHTML = `<p class="mo-detail-error">${moEsc(data.message)}</p>`;
                } catch(e) { body.innerHTML = '<p class="mo-detail-error">Failed to load order details.</p>'; }
            });
    }
 
    function moBuildDetailHTML(order, items) {
        const date     = new Date(order.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        const badgeCls = { processing: 'mo-badge--processing', completed: 'mo-badge--completed', cancelled: 'mo-badge--cancelled' }[order.status] || '';
 
        const grouped = {};
        items.forEach(item => {
            if (!grouped[item.business_name]) grouped[item.business_name] = [];
            grouped[item.business_name].push(item);
        });
 
        let html = `
            <div class="mo-detail-meta">
                <div class="mo-detail-meta-row"><span class="mo-detail-label">Order</span><span class="mo-detail-value">#${order.order_id}</span></div>
                <div class="mo-detail-meta-row"><span class="mo-detail-label">Date</span><span class="mo-detail-value">${date}</span></div>
                <div class="mo-detail-meta-row"><span class="mo-detail-label">Status</span><span class="mo-badge ${badgeCls}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></div>
                <div class="mo-detail-meta-row"><span class="mo-detail-label">Total</span><span class="mo-detail-value mo-detail-total">£${parseFloat(order.total_amount).toFixed(2)}</span></div>
            </div>
            <div class="mo-detail-divider"></div>
        `;
 
        Object.entries(grouped).forEach(([biz, bizItems]) => {
            html += `<div class="mo-detail-biz-header">${moEsc(biz)}</div>`;
            bizItems.forEach(item => {
                const imgSrc = item.primary_image ? `../uploads/listings_images/${moEsc(item.primary_image)}` : null;
                html += `
                    <div class="mo-detail-item">
                        <div class="mo-detail-item-img">
                            ${imgSrc ? `<img src="${imgSrc}" alt="${moEsc(item.title)}" onerror="this.style.display='none'">` : ''}
                        </div>
                        <div class="mo-detail-item-info">
                            <p class="mo-detail-item-cat">${moEsc(item.category_name)}</p>
                            <p class="mo-detail-item-title">${moEsc(item.title)}</p>
                            <p class="mo-detail-item-unit">£${parseFloat(item.price).toFixed(2)} × ${item.quantity}</p>
                        </div>
                        <p class="mo-detail-item-subtotal">£${(parseFloat(item.price) * item.quantity).toFixed(2)}</p>
                    </div>
                `;
            });
        });
 
        return html;
    }
 
    function moCloseDetail() { document.getElementById('mo-detail-modal').style.display = 'none'; }
 
    function moOpenCancelModal(order_id) {
        moCancelOrderId = order_id;
        document.getElementById('mo-cancel-order-label').textContent = 'Order #' + order_id;
        document.getElementById('mo-cancel-modal').style.display = 'flex';
    }
 
    function moCloseCancelModal() { document.getElementById('mo-cancel-modal').style.display = 'none'; moCancelOrderId = null; }
 
    function moConfirmCancel() {
        if (!moCancelOrderId) return;
        const btn = document.getElementById('mo-cancel-confirm-btn');
        btn.disabled = true; btn.textContent = 'Cancelling…';
 
        const fd = new FormData();
        fd.append('action',   'cancel_order');
        fd.append('order_id', moCancelOrderId);
 
        fetch('../pages/my-orders.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) { moCloseCancelModal(); moShowToast(data.message, 'success'); moLoad(moCurrentTab); }
                    else moShowToast(data.message || 'Failed.', 'error');
                } catch(e) { moShowToast('Unexpected error.', 'error'); }
            })
            .catch(() => moShowToast('Network error.', 'error'))
            .finally(() => { btn.disabled = false; btn.textContent = 'Yes, Cancel Order'; });
    }
 
    function moShowToast(message, type) {
        const existing = document.getElementById('mo-toast');
        if (existing) existing.remove();
        const toast = document.createElement('div');
        toast.id = 'mo-toast'; toast.className = `mo-toast mo-toast--${type}`; toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('mo-toast--show'), 10);
        setTimeout(() => { toast.classList.remove('mo-toast--show'); setTimeout(() => toast.remove(), 300); }, 3000);
    }
 
    function moEsc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
</script>