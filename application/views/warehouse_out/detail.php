<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid mt-4">
    <!-- HEADER INFO CARD -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-0"><?= lang_line('warehouse_out_detail_title_prefix', 'Return #'); ?><?php echo isset($header->out_number) && $header->out_number ? $header->out_number : lang_line('warehouse_out_detail_title_draft', 'Draft'); ?></h5>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge 
                        <?php 
                            if ($header->status == 'Draft') echo 'bg-secondary';
                            elseif ($header->status == 'Pending Approval') echo 'bg-warning';
                            elseif ($header->status == 'Approved') echo 'bg-info';
                            elseif ($header->status == 'Confirmed') echo 'bg-primary';
                            elseif ($header->status == 'Verified') echo 'bg-success';
                            elseif ($header->status == 'Completed') echo 'bg-success';
                            elseif ($header->status == 'Cancelled') echo 'bg-danger';
                        ?>">
                        <?php echo $header->status; ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <p><strong><?= lang_line('warehouse_out_label_date', 'Date:'); ?></strong> <?php
                        $d = $header->out_date ?? $header->created_at ?? null;
                        echo $d ? date('d M Y', strtotime($d)) : '-';
                    ?></p>
                    <p><strong><?= lang_line('warehouse_out_label_type', 'Type:'); ?></strong> <?php echo $header->base_on ?? '-'; ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong><?= lang_line('warehouse_out_label_warehouse', 'Warehouse:'); ?></strong> <?php echo $header->warehouse_type ?? '-'; ?></p>
                    <p><strong><?= lang_line('warehouse_out_label_created_by', 'Created by:'); ?></strong> <?php echo $header->created_by_name ?? $header->created_by ?? '-'; ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong><?= lang_line('warehouse_out_label_partner', 'Partner:'); ?></strong> <?php echo $header->nama_supplier ?? $header->supplier_name ?? $header->partner_name ?? '-'; ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong><?= lang_line('warehouse_out_label_bc_info', 'BC Info:'); ?></strong> <?php echo ($header->bc_type_code ? $header->bc_type_code . ' / ' . $header->bc_number : '-'); ?></p>
                    <?php if ($header->status == 'Draft' && check_access('warehouse_out', 'edit')): ?>
                        <div class="mt-2">
                            <label class="form-label small mb-1"><?= lang_line('warehouse_out_label_currency', 'Currency'); ?></label>
                            <select id="headerCurrency" class="form-select form-select-sm">
                                <?php $cur = $header->currency_code ?? 'IDR'; ?>
                                <option value="IDR" <?= $cur=='IDR' ? 'selected' : '' ?>>IDR</option>
                                <option value="USD" <?= $cur=='USD' ? 'selected' : '' ?>>USD</option>
                                <option value="EUR" <?= $cur=='EUR' ? 'selected' : '' ?>>EUR</option>
                            </select>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($header->currency_code)): ?>
                            <p class="small">Currency: <?php echo htmlspecialchars($header->currency_code); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <p><strong><?= lang_line('warehouse_out_label_notes', 'Notes:'); ?></strong> <?php echo nl2br($header->reference_note ?? '') ?: '-'; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- ACTION BUTTONS -->
    <div class="mb-3">
        <?php if ($header->status == 'Draft' && check_access('warehouse_out', 'edit')): ?>
            <?php if (check_access('warehouse_out', 'submit')): ?>
            <a href="<?php echo base_url('warehouse_out/submit_for_approval/' . $header->id); ?>" class="btn btn-warning btn-sm" onclick="return confirm('<?= lang_line('warehouse_out_button_submit_approval', 'Submit for Approval?'); ?>')">
                <i class="fas fa-arrow-right"></i> <?= lang_line('warehouse_out_button_submit_approval', 'Submit for Approval'); ?>
            </a>
            <?php endif; ?>
            <a href="<?php echo base_url('warehouse_out'); ?>" class="btn btn-secondary btn-sm"><?= lang_line('warehouse_out_button_back', 'Back'); ?></a>
        <?php endif; ?>

        <?php if ($header->status == 'Pending Approval' && check_access('warehouse_out', 'approve')): ?>
            <button type="button" class="btn btn-success btn-sm btn-approve-trigger" data-bs-toggle="modal" data-bs-target="#approveModal">
                <i class="fas fa-check"></i> <?= lang_line('warehouse_out_button_approve', 'Approve'); ?>
            </button>
            <button type="button" class="btn btn-danger btn-sm btn-reject-trigger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="fas fa-times"></i> <?= lang_line('warehouse_out_button_reject', 'Reject'); ?>
            </button>
        <?php endif; ?>

        <?php if ($header->status == 'Approved' && check_access('warehouse_out', 'edit')): ?>
            <a href="<?php echo base_url('warehouse_out/confirm/' . $header->id); ?>" class="btn btn-primary btn-sm" onclick="return confirm('<?= lang_line('warehouse_out_confirm_dialog', 'Confirm goods issue? Inventory will be updated.'); ?>')">
                <i class="fas fa-check-circle"></i> <?= lang_line('warehouse_out_button_confirm', 'Confirm'); ?>
            </a>
        <?php endif; ?>

        <?php if ($header->status == 'Confirmed' && check_access('warehouse_out', 'edit')): ?>
            <a href="<?php echo base_url('warehouse_out/unconfirm/' . $header->id); ?>" class="btn btn-warning btn-sm" onclick="return confirm('<?= lang_line('warehouse_out_unconfirm_dialog', 'Cancel confirmation?'); ?>')">
                <i class="fas fa-undo"></i> <?= lang_line('warehouse_out_button_unconfirm', 'Cancel Confirmation'); ?>
            </a>
        <?php endif; ?>

        <a href="<?php echo base_url('warehouse_out/export_pdf/' . $header->id); ?>" class="btn btn-secondary btn-sm" target="_blank">
            <i class="fas fa-file-pdf"></i> <?= lang_line('warehouse_out_button_export_pdf', 'Export PDF'); ?>
        </a>
        <a href="<?php echo base_url('warehouse_out/export_excel/' . $header->id); ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-file-excel"></i> <?= lang_line('warehouse_out_button_export_excel', 'Export Excel'); ?>
        </a>
    </div>

    <!-- ITEMS CARD -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0"><?= lang_line('warehouse_out_section_items', 'Item Details'); ?></h5>
                </div>
                <div class="col-md-6 text-end">
                        <?php if ($header->status == 'Draft' && check_access('warehouse_out', 'edit')): ?>
                            <?php if ($header->base_on === 'NG'): ?>
                                <button type="button" id="btnPullNgQa" class="btn btn-sm btn-danger me-2">
                                    <i class="fas fa-exclamation-triangle"></i> <?= lang_line('warehouse_out_button_pull_ng', 'Pull NG Items from QA'); ?>
                                </button>
                            <?php elseif ($header->base_on === 'Production Return'): ?>
                                <button type="button" id="btnPullProdReject" class="btn btn-sm btn-warning me-2">
                                    <i class="fas fa-industry"></i> <?= lang_line('warehouse_out_button_pull_prod_reject', 'Pull Production Reject Items'); ?>
                                </button>
                            <?php else: ?>
                                <button type="button" id="btnPullGrn" class="btn btn-sm btn-primary me-2">
                                    <i class="fas fa-download"></i> <?= lang_line('warehouse_out_button_pull_grn', 'Pull Data from GRN'); ?>
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                <i class="fas fa-plus"></i> <?= lang_line('warehouse_out_button_add_item', 'Add Item Manual'); ?>
                            </button>
                        <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead class="table-light" style="font-size:0.82rem;">
                        <tr>
                            <th><?= lang_line('warehouse_out_table_code', 'Code'); ?></th>
                            <th><?= lang_line('warehouse_out_table_item_name', 'Item Name'); ?></th>
                            <?php if ($header->base_on === 'NG'): ?>
                                <th class="text-center"><?= lang_line('warehouse_out_table_grn_qa', 'GRN / QA'); ?></th>
                                <th class="text-center"><?= lang_line('warehouse_out_table_po_number', 'PO Number'); ?></th>
                                <th class="text-center"><?= lang_line('warehouse_out_table_qty_order', 'Qty Order'); ?></th>
                                <th class="text-center"><?= lang_line('warehouse_out_table_qty_received', 'Qty Received'); ?></th>
                                <th class="text-center"><?= lang_line('warehouse_out_table_pass_qa', 'Pass QA'); ?></th>
                                <th class="text-center text-danger"><?= lang_line('warehouse_out_table_ng_qty', 'NG Qty'); ?></th>
                                <th><?= lang_line('warehouse_out_table_ng_category', 'NG Category'); ?></th>
                            <?php elseif ($header->base_on === 'Production Return'): ?>
                                <th><?= lang_line('warehouse_out_table_grn_source', 'Source GRN'); ?></th>
                                <th><?= lang_line('warehouse_out_table_po_number', 'PO Number'); ?></th>
                                <th><?= lang_line('warehouse_out_table_supplier', 'Supplier'); ?></th>
                                <th class="text-center text-danger"><?= lang_line('warehouse_out_table_qty_reject_prod', 'Qty Reject Production'); ?></th>
                            <?php else: ?>
                                <th class="text-center"><?= lang_line('warehouse_out_table_qty_po_mir', 'QTY PO/MIR'); ?></th>
                                <th class="text-center"><?= lang_line('warehouse_out_table_qty_diterima', 'QTY RECEIVED'); ?></th>
                                <th><?= lang_line('warehouse_out_table_grn_source', 'Source GRN'); ?></th>
                            <?php endif; ?>
                            <th class="text-center"><?= lang_line('warehouse_out_table_qty_return', 'QTY RETURN'); ?></th>
                            <th><?= lang_line('warehouse_out_table_unit', 'Unit'); ?></th>
                            <th class="text-end"><?= lang_line('warehouse_out_table_unit_value', 'Unit Value'); ?></th>
                            <th class="text-end"><?= lang_line('warehouse_out_table_total_value', 'Total Value'); ?></th>
                            <th><?= lang_line('warehouse_out_table_action', 'Action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($details)): ?>
                            <?php foreach ($details as $detail): ?>
                                <?php
                                    $code        = $detail->item_code ?? '';
                                    $name        = $detail->item_name ?? '';
                                    $qty_po      = (float)($detail->qty_po ?? 0);
                                    $qty_received= (float)($detail->qty_received ?? $detail->receive_qty ?? 0);
                                    $qty_return  = (float)($detail->qty_return ?? 0);
                                    $unit        = $detail->unit ?? $detail->satuan ?? '';
                                    $unit_value  = (float)($detail->unit_value_idr ?? $detail->unit_value ?? 0);
                                    $total       = $unit_value * $qty_return;
                                    $grn_label   = $detail->grn_number ?? '-';
                                    // NG-specific fields (populated from qa_details join in get_details())
                                    $qa_pass     = (float)($detail->qa_pass_qty ?? 0);
                                    $qa_reject   = (float)($detail->qa_reject_qty ?? $qty_return);
                                    $ng_cat      = $detail->ng_category ?? '-';
                                    $po_number   = $detail->po_number ?? '-';
                                    $qa_number   = $detail->qa_number ?? '-';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($code); ?></td>
                                    <td><?php echo htmlspecialchars($name); ?></td>
                                    <?php if ($header->base_on === 'NG'): ?>
                                        <td class="text-center small">
                                            <div class="text-muted"><?= htmlspecialchars($grn_label) ?></div>
                                            <div class="text-info"><?= htmlspecialchars($qa_number) ?></div>
                                        </td>
                                        <td class="text-center small"><?= htmlspecialchars($po_number) ?></td>
                                        <td class="text-center"><?= number_format($qty_po, 2) ?></td>
                                        <td class="text-center"><?= number_format($qty_received, 2) ?></td>
                                        <td class="text-center text-success"><?= number_format($qa_pass, 2) ?></td>
                                        <td class="text-center text-danger font-weight-bold"><?= number_format($qa_reject, 2) ?></td>
                                        <td><small><?= htmlspecialchars($ng_cat) ?></small></td>
                                    <?php elseif ($header->base_on === 'Production Return'): ?>
                                        <?php
                                        $supplier_display = $detail->supplier_name ?? $detail->po_supplier_name ?? $detail->mir_supplier_name ?? '-';
                                        ?>
                                        <td><small class="text-muted"><?= htmlspecialchars($grn_label) ?></small></td>
                                        <td>
                                            <?php if (!empty($po_number) && $po_number !== '-'): ?>
                                                <span class="badge badge-primary"><?= htmlspecialchars($po_number) ?></span>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="font-weight-bold"><?= htmlspecialchars($supplier_display) ?></small>
                                        </td>
                                        <td class="text-center text-danger font-weight-bold">
                                            <?= number_format($qty_received, 2) ?>
                                        </td>
                                    <?php else: ?>
                                        <td class="text-center"><?= number_format($qty_po, 2) ?></td>
                                        <td class="text-center"><?= number_format($qty_received, 2) ?></td>
                                        <td><?= htmlspecialchars($grn_label) ?></td>
                                    <?php endif; ?>
                                    <td class="text-center">
                                        <?php if ($header->status == 'Draft' && check_access('warehouse_out', 'edit')): ?>
                                            <input type="number" step="0.01" min="0" max="<?= $qty_received ?>"
                                                   value="<?= number_format($qty_return,2,'.','') ?>"
                                                   class="form-control form-control-sm text-end qty-return-input"
                                                   data-detail-id="<?= $detail->id ?>" style="width:90px;" />
                                        <?php else: ?>
                                            <?= number_format($qty_return, 2) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($unit) ?></td>
                                    <td class="text-end">
                                        <?php if ($header->status == 'Draft' && check_access('warehouse_out', 'edit')): ?>
                                            <input type="number" step="0.01" min="0"
                                                   value="<?= number_format($unit_value,2,'.','') ?>"
                                                   class="form-control form-control-sm text-end unit-value-input"
                                                   data-detail-id="<?= $detail->id ?>" style="width:110px;" />
                                        <?php else: ?>
                                            Rp <?= number_format($unit_value, 2) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">Rp <span class="detail-total" data-detail-id="<?= $detail->id ?>"><?= number_format($total, 2) ?></span></td>
                                    <td>
                                        <?php if ($header->status == 'Draft' && check_access('warehouse_out', 'edit')): ?>
                                            <button type="button" class="btn btn-xs btn-primary save-detail-btn" data-detail-id="<?= $detail->id ?>" title="Simpan">
                                                <i class="fas fa-save"></i>
                                            </button>
                                            <a href="<?= base_url('warehouse_out/delete_item/' . $header->id . '/' . $detail->id) ?>"
                                               class="btn btn-xs btn-danger ms-1" onclick="return confirm('Hapus item?')" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="text-center text-muted"><?= lang_line('warehouse_out_empty_items', 'No items yet. Add items to start.'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Helper to show/hide Bootstrap modal compatible with BS4 (jQuery) and BS5
function showModalCompat(modalEl) {
    if (!modalEl) return null;
    try {
        if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            const inst = new bootstrap.Modal(modalEl);
            inst.show();
            return inst;
        }
    } catch(e) { console.warn('bootstrap.Modal show failed', e); }
    if (window.jQuery) { try { $(modalEl).modal('show'); return $(modalEl); } catch(e) { console.warn('jQuery modal show failed', e); } }
    return null;
}

function hideModalCompat(modalEl) {
    if (!modalEl) return;
    try {
        if (window.bootstrap && typeof window.bootstrap.Modal !== 'undefined' && typeof window.bootstrap.Modal.getInstance === 'function') {
            const inst = bootstrap.Modal.getInstance(modalEl);
            if (inst) inst.hide();
            return;
        }
        if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            // create temporary instance to hide
            try { (new bootstrap.Modal(modalEl)).hide(); return; } catch(e) { /* ignore */ }
        }
    } catch(e) { console.warn('bootstrap.Modal hide failed', e); }
    if (window.jQuery) { try { $(modalEl).modal('hide'); return; } catch(e) { console.warn('jQuery modal hide failed', e); } }
}

    document.getElementById('btnPullGrn') && document.getElementById('btnPullGrn').addEventListener('click', function(){
    // Pass current header filters (base_on and supplier) so popup only shows relevant GRNs
    const baseOn = '<?= htmlspecialchars($header->base_on ?? '') ?>';
    const supplierId = '<?= htmlspecialchars($header->supplier_id ?? '') ?>';
    const headerId = '<?= (int)($header->id ?? 0) ?>';
    const urlParams = new URLSearchParams();
    if (baseOn) urlParams.set('base_on', baseOn);
    if (supplierId) urlParams.set('supplier_id', supplierId);
    if (headerId) urlParams.set('header_id', headerId);
    const popupUrl = '<?= base_url('Warehouse_out/popup_select_grn') ?>' + (urlParams.toString() ? ('?' + urlParams.toString()) : '');
    fetch(popupUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r=>r.json()).then(j=>{
        if (j.success) {
            // inject modal fragment into body and show it (modern in-page modal)
            // remove existing modal if present
            const existing = document.getElementById('selectGrnModal');
            if (existing) existing.remove();
            const wrapper = document.createElement('div');
            wrapper.innerHTML = j.html;
            document.body.appendChild(wrapper);
            // initialize and show bootstrap modal
            const modalEl = document.getElementById('selectGrnModal');
            if (modalEl) {
                // run initializer defined below to bind events (scripts inside injected HTML won't run automatically)
                if (typeof initSelectGrnModal === 'function') {
                    try { initSelectGrnModal(modalEl); } catch(e){ console.error('initSelectGrnModal error', e); }
                }
                // show modal with compatibility helper (supports BS4 jQuery and BS5)
                showModalCompat(modalEl);
            }
        } else {
            alert('Gagal membuka popup: ' + (j.message || ''));
        }
    }).catch(err=>{ console.error(err); alert('Gagal membuka popup GRN'); });
});

// Listen for items posted back from the popup and attach them to this header
window.addEventListener('message', function(e){
    try {
        const data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data;
        if (data.type === 'warehouse_out_grn_items') {
            const items = data.items || [];
            if (items.length === 0) { alert('Tidak ada item dari GRN'); return; }
            const payload = { header_id: <?= (int)$header->id ?>, items: items };
                fetch('<?= base_url('Warehouse_out/pull_grn_items_to_header') ?>', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(payload) })
                .then(r => r.text().then(text => ({ ok: r.ok, status: r.status, text })))
                .then(res => {
                    if (!res.ok) {
                        console.error('Attach HTTP error', res.status, res.text);
                        alert('Server returned HTTP ' + res.status + '. Lihat console untuk detail.');
                        return;
                    }
                    let resp = null;
                    try { resp = JSON.parse(res.text); } catch(e) { console.error('Invalid JSON response:', res.text); alert('Server response not valid JSON. Lihat console.'); return; }
                    if (resp.success) {
                        alert('Berhasil menambahkan ' + (resp.added||0) + ' item. Halaman akan direfresh.');
                        window.location.reload();
                    } else {
                        const errMsg = resp.errors && resp.errors.length ? resp.errors.join('\n') : (resp.message || 'unknown');
                        alert('Gagal: ' + errMsg);
                    }
                }).catch(err=>{ console.error('Network error attaching items', err); alert('Gagal attach items ke header (network). Lihat console.'); });
        }
    } catch(err){ console.error('Invalid message', err); }
});
</script>

<!-- Toast Notification -->
<div id="woToastContainer" style="position:fixed; bottom:24px; right:24px; z-index:9999; min-width:260px;"></div>

<script>
/* ── Toast helper ─────────────────────────────────────── */
function showToast(msg, type) {
    type = type || 'success';
    var colors = { success: '#28a745', error: '#dc3545', warning: '#fd7e14' };
    var icons  = { success: 'fa-check-circle', error: 'fa-times-circle', warning: 'fa-exclamation-circle' };
    var id = 'toast_' + Date.now();
    var html = '<div id="' + id + '" style="'
        + 'background:#fff; border-left:4px solid ' + (colors[type]||colors.success) + ';'
        + 'box-shadow:0 4px 16px rgba(0,0,0,.15); border-radius:6px;'
        + 'padding:12px 16px; margin-top:10px; display:flex; align-items:center; gap:10px;'
        + 'animation:woSlideIn .25s ease; font-size:.88rem;">'
        + '<i class="fas ' + (icons[type]||icons.success) + '" style="color:' + (colors[type]||colors.success) + '; font-size:1.1rem; flex-shrink:0;"></i>'
        + '<span style="flex:1; color:#333;">' + msg + '</span>'
        + '<button onclick="document.getElementById(\'' + id + '\').remove()" style="background:none;border:none;color:#aaa;cursor:pointer;font-size:1rem;line-height:1;padding:0;">&times;</button>'
        + '</div>';
    document.getElementById('woToastContainer').insertAdjacentHTML('beforeend', html);
    setTimeout(function(){ var el = document.getElementById(id); if (el) el.style.animation = 'woSlideOut .3s ease forwards'; setTimeout(function(){ var el2 = document.getElementById(id); if (el2) el2.remove(); }, 300); }, 3000);
}
(function addToastStyles(){
    var s = document.createElement('style');
    s.textContent = '@keyframes woSlideIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:translateX(0)}} @keyframes woSlideOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(40px)}}';
    document.head.appendChild(s);
})();

/* ── Live recalc ──────────────────────────────────────── */
function recalcTotalForDetail(detailId) {
    var qtyEl  = document.querySelector('.qty-return-input[data-detail-id="' + detailId + '"]');
    var unitEl = document.querySelector('.unit-value-input[data-detail-id="' + detailId + '"]');
    var span   = document.querySelector('.detail-total[data-detail-id="' + detailId + '"]');
    var qty  = qtyEl  ? parseFloat(qtyEl.value)  || 0 : 0;
    var unit = unitEl ? parseFloat(unitEl.value) || 0 : 0;
    if (span) span.textContent = Number(qty * unit).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
}

document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.qty-return-input').forEach(function(inp){
        inp.addEventListener('input', function(){ recalcTotalForDetail(this.dataset.detailId); });
    });
    document.querySelectorAll('.unit-value-input').forEach(function(inp){
        inp.addEventListener('input', function(){ recalcTotalForDetail(this.dataset.detailId); });
    });

    /* ── Save button ───────────────────────────────────── */
    document.querySelectorAll('.save-detail-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var self       = this;
            var detailId   = this.dataset.detailId;
            var qtyEl      = document.querySelector('.qty-return-input[data-detail-id="' + detailId + '"]');
            var unitEl     = document.querySelector('.unit-value-input[data-detail-id="' + detailId + '"]');
            var qty        = qtyEl  ? parseFloat(qtyEl.value)  || 0 : 0;
            var unit       = unitEl ? parseFloat(unitEl.value) || 0 : 0;
            var currencyEl = document.getElementById('headerCurrency');
            var currency   = currencyEl ? currencyEl.value : '';

            var fd = new FormData();
            fd.append('detail_id', detailId);
            fd.append('qty_return', qty);
            fd.append('unit_value', unit);
            if (currency) fd.append('currency', currency);

            // Loading state
            var oldHtml = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.disabled = true;
            this.style.opacity = '0.7';

            fetch('<?= base_url('Warehouse_out/update_detail_ajax') ?>', {
                method: 'POST', body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r){ return r.json(); })
            .then(function(j){
                // Reset button
                self.disabled = false;
                self.style.opacity = '';

                if (!j.success) {
                    self.innerHTML = oldHtml;
                    showToast('Gagal: ' + (j.message || 'Terjadi kesalahan'), 'error');
                    return;
                }

                // Success — tampilkan checkmark sebentar lalu kembali ke icon save
                self.innerHTML = '<i class="fas fa-check"></i>';
                self.style.background = '#28a745';
                self.style.borderColor = '#28a745';
                setTimeout(function(){
                    self.innerHTML = oldHtml;
                    self.style.background = '';
                    self.style.borderColor = '';
                }, 1200);

                // Update total cell
                var span = document.querySelector('.detail-total[data-detail-id="' + detailId + '"]');
                if (span) span.textContent = 'Rp ' + Number(j.total).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});

                showToast('Tersimpan — Qty: ' + qty.toFixed(2) + ' | Unit Value: ' + unit.toLocaleString(), 'success');
            })
            .catch(function(err){
                console.error(err);
                self.disabled = false;
                self.innerHTML = oldHtml;
                self.style.opacity = '';
                showToast('Gagal menyimpan (koneksi terputus)', 'error');
            });
        });
    });

    // Initial recalc
    document.querySelectorAll('.detail-total').forEach(function(s){
        var id = s.getAttribute('data-detail-id');
        if (id) recalcTotalForDetail(id);
    });
});
</script>

<script>
// Initialize select GRN modal behavior for injected modal fragment
function initSelectGrnModal(modalEl) {
    if (!modalEl) return;
    const headerId = parseInt(modalEl.getAttribute('data-header-id') || '0');
    const btnLoad = modalEl.querySelector('#btnLoadSelected');
    const btnSelectAll = modalEl.querySelector('#btnSelectAll');
    const btnClearAll = modalEl.querySelector('#btnClearAll');
    const searchInput = modalEl.querySelector('#grnSearch');
    const table = modalEl.querySelector('#grnTable');

    function getCheckedIds() {
        return Array.from(table.querySelectorAll('.grn-checkbox:checked')).map(c=>parseInt(c.value));
    }

    if (btnSelectAll) btnSelectAll.addEventListener('click', function(){ table.querySelectorAll('.grn-checkbox').forEach(c=>c.checked = true); });
    if (btnClearAll) btnClearAll.addEventListener('click', function(){ table.querySelectorAll('.grn-checkbox').forEach(c=>c.checked = false); });
    if (searchInput) searchInput.addEventListener('input', function(){ const term = this.value.toLowerCase(); Array.from(table.querySelectorAll('tbody tr')).forEach(tr=>{ const text = tr.textContent.toLowerCase(); tr.style.display = text.indexOf(term) === -1 ? 'none' : ''; }); });

    if (btnLoad) btnLoad.addEventListener('click', function(){
        const ids = getCheckedIds();
        if (!ids.length) { alert('Pilih minimal satu GRN.'); return; }
        const fd = new FormData(); ids.forEach(id => fd.append('grn_ids[]', id));
        fetch('<?= base_url('Warehouse_out/load_grn_items') ?>', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r=>r.json()).then(j=>{
            if (!j.success) { alert('Gagal load items: ' + (j.message||'')); return; }
            const items = j.data || [];
            const payload = { header_id: headerId, items: items };
            fetch('<?= base_url('Warehouse_out/pull_grn_items_to_header') ?>', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(payload) })
            .then(r => r.text().then(text => ({ ok: r.ok, status: r.status, text })))
            .then(res => {
                if (!res.ok) {
                    console.error('Attach HTTP error', res.status, res.text);
                    alert('Server returned HTTP ' + res.status + '. Lihat console untuk detail.');
                    return;
                }
                let resp = null;
                try { resp = JSON.parse(res.text); } catch(e) { console.error('Invalid JSON response:', res.text); alert('Server response not valid JSON. Lihat console.'); return; }
                if (resp.success) {
                    // hide modal compatibly
                    hideModalCompat(modalEl);
                    alert('Berhasil menambahkan ' + (resp.added||0) + ' item');
                    window.location.reload();
                } else {
                    const errMsg = resp.errors && resp.errors.length ? resp.errors.join('\n') : (resp.message || 'unknown');
                    alert('Gagal: ' + errMsg);
                }
            }).catch(err=>{ console.error('Network error attaching items', err); alert('Gagal attach items ke header (network). Lihat console.'); });
        }).catch(err=>{ console.error(err); alert('Gagal load items'); });
    });
}
</script>

<!-- PRODUCTION RETURN MODAL -->
<div class="modal fade" id="prodReturnModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-industry mr-2"></i><?= lang_line('warehouse_out_modal_select_prod_reject', 'Select Production Reject Items'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" id="prSearch" class="form-control" placeholder="Cari No. Issuance, MR...">
                    </div>
                </div>
                <!-- Step 1: Pilih issuance -->
                <div id="prStep1">
                    <h6 class="text-muted mb-2">Step 1 — Pilih Issuance yang memiliki item reject:</h6>
                    <div class="table-responsive" style="max-height:220px; overflow-y:auto;">
                        <table class="table table-sm table-hover" id="prIssuanceTable">
                            <thead class="thead-light">
                                <tr>
                                    <th width="30"></th>
                                    <th>Issuance No</th>
                                    <th>MR No</th>
                                    <th>Supplier (via PO)</th>
                                    <th class="text-center">Total Items Reject</th>
                                    <th class="text-center">Total Qty Reject</th>
                                    <th class="text-center">QC Done At</th>
                                </tr>
                            </thead>
                            <tbody id="prIssuanceBody">
                                <tr><td colspan="7" class="text-center text-muted">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-warning mt-2" id="btnLoadPrItems">
                        <i class="fas fa-arrow-down"></i> Load Item Reject
                    </button>
                </div>
                <!-- Step 2: Konfirmasi item -->
                <div id="prStep2" style="display:none;">
                    <hr>
                    <h6 class="text-muted mb-2">Step 2 — Konfirmasi item dan qty return ke supplier:</h6>
                    <div class="table-responsive" style="max-height:300px; overflow-y:auto;">
                        <table class="table table-sm table-bordered" id="prItemsTable">
                            <thead class="thead-light" style="font-size:0.8rem;">
                                <tr>
                                    <th width="30"><input type="checkbox" id="prItemCheckAll" checked></th>
                                    <th>Item</th>
                                    <th>Issuance / MR</th>
                                    <th>GRN Asal</th>
                                    <th>PO</th>
                                    <th class="text-center text-danger">Reject Qty</th>
                                    <th>Catatan QC</th>
                                    <th class="text-center" style="width:110px;">Qty Return</th>
                                </tr>
                            </thead>
                            <tbody id="prItemsBody"></tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnBackToPrList">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </button>
                        <button type="button" class="btn btn-warning ml-2" id="btnPullPrConfirm">
                            <i class="fas fa-check"></i> Tambahkan ke Dokumen Return
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var headerId = <?= (int)$header->id ?>;
    var prData = []; var prItemsData = [];
    function esc(s){ return String(s||'').replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
    function fmt(n){ return (parseFloat(n)||0).toFixed(2); }

    var btnOpen = document.getElementById('btnPullProdReject');
    if (!btnOpen) return;

    btnOpen.addEventListener('click', function(){
        loadPrList('');
        $('#prodReturnModal').modal('show');
    });

    function loadPrList(search) {
        document.getElementById('prIssuanceBody').innerHTML = '<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i></td></tr>';
        document.getElementById('prStep1').style.display = '';
        document.getElementById('prStep2').style.display = 'none';
        fetch('<?= base_url('Warehouse_out/popup_select_production_return') ?>?header_id='+headerId+'&search='+encodeURIComponent(search), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(r){ return r.json(); }).then(function(j){
            if (!j.success) { document.getElementById('prIssuanceBody').innerHTML = '<tr><td colspan="7" class="text-danger text-center">'+esc(j.message||'Error')+'</td></tr>'; return; }
            prData = j.data || [];
            var html = prData.length ? prData.map(function(r){
                return '<tr><td><input type="checkbox" class="pr-iss-check" value="'+r.issuance_id+'"></td>'
                    + '<td><strong>'+esc(r.issuance_number)+'</strong></td>'
                    + '<td class="small">'+esc(r.mr_number)+'</td>'
                    + '<td class="small">'+esc(r.nama_supplier)+'</td>'
                    + '<td class="text-center"><span class="badge badge-danger">'+r.total_items+'</span></td>'
                    + '<td class="text-center text-danger font-weight-bold">'+fmt(r.total_reject_qty)+'</td>'
                    + '<td class="text-center small">'+(r.qc_done_at ? r.qc_done_at.substr(0,16) : '-')+'</td>'
                    + '</tr>';
            }).join('') : '<tr><td colspan="7" class="text-center text-muted">Tidak ada item reject produksi</td></tr>';
            document.getElementById('prIssuanceBody').innerHTML = html;
        }).catch(function(){ document.getElementById('prIssuanceBody').innerHTML = '<tr><td colspan="7" class="text-danger text-center">Gagal load</td></tr>'; });
    }

    document.getElementById('prSearch').addEventListener('input', function(){
        var t = this.value.toLowerCase();
        document.querySelectorAll('#prIssuanceBody tr').forEach(function(tr){ tr.style.display = tr.textContent.toLowerCase().indexOf(t) === -1 ? 'none' : ''; });
    });

    document.getElementById('btnLoadPrItems').addEventListener('click', function(){
        var ids = Array.from(document.querySelectorAll('.pr-iss-check:checked')).map(function(c){ return parseInt(c.value); });
        if (!ids.length) { alert('Pilih minimal satu issuance.'); return; }
        var self = this; self.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; self.disabled = true;
        fetch('<?= base_url('Warehouse_out/load_production_reject_items') ?>', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ issuance_ids: ids })
        }).then(function(r){ return r.json(); }).then(function(j){
            self.innerHTML = '<i class="fas fa-arrow-down"></i> Load Item Reject'; self.disabled = false;
            if (!j.success) { alert('Gagal: ' + (j.message||'')); return; }
            prItemsData = j.items || [];
            var html = prItemsData.length ? prItemsData.map(function(it, idx){
                var avail = parseFloat(it.reject_qty || 0);
                return '<tr><td><input type="checkbox" class="pr-item-check" value="'+idx+'" '+(avail>0?'checked':'disabled')+'></td>'
                    + '<td><strong>'+esc(it.item_code)+'</strong><div class="small text-muted">'+esc(it.item_name)+'</div></td>'
                    + '<td class="small text-muted">'+esc(it.issuance_number)+'<br>'+esc(it.mr_number)+'</td>'
                    + '<td class="small">'+esc(it.grn_number||'-')+'</td>'
                    + '<td class="small">'+esc(it.po_number||'-')+'</td>'
                    + '<td class="text-center text-danger font-weight-bold">'+fmt(avail)+'</td>'
                    + '<td class="small">'+esc(it.qc_notes||'-')+'</td>'
                    + '<td><input type="number" step="0.01" min="0" max="'+avail+'" value="'+fmt(avail)+'" class="form-control form-control-sm pr-qty-return" data-idx="'+idx+'"></td>'
                    + '</tr>';
            }).join('') : '<tr><td colspan="8" class="text-center text-muted">Tidak ada item</td></tr>';
            document.getElementById('prItemsBody').innerHTML = html;
            document.getElementById('prStep1').style.display = 'none';
            document.getElementById('prStep2').style.display = '';
            document.getElementById('prItemCheckAll').addEventListener('change', function(){
                document.querySelectorAll('.pr-item-check:not(:disabled)').forEach(function(c){ c.checked = this.checked; }.bind(this));
            });
        }).catch(function(err){ console.error(err); self.innerHTML = '<i class="fas fa-arrow-down"></i> Load'; self.disabled = false; alert('Error'); });
    });

    document.getElementById('btnBackToPrList').addEventListener('click', function(){
        document.getElementById('prStep1').style.display = ''; document.getElementById('prStep2').style.display = 'none';
    });

    document.getElementById('btnPullPrConfirm').addEventListener('click', function(){
        var checked = Array.from(document.querySelectorAll('.pr-item-check:checked'));
        if (!checked.length) { alert('Pilih minimal satu item.'); return; }
        var items = [];
        checked.forEach(function(c){
            var idx = parseInt(c.value); var it = prItemsData[idx];
            var qtyEl = document.querySelector('.pr-qty-return[data-idx="'+idx+'"]');
            var qty = qtyEl ? parseFloat(qtyEl.value)||0 : 0;
            if (qty <= 0) return;
            items.push({ grn_detail_id: it.grn_detail_id, grn_header_id: it.grn_header_id, po_header_id: it.po_header_id||null, item_id: it.item_id, item_type: it.item_type, item_code: it.item_code, item_name: it.item_name, unit: it.unit, reject_qty: it.reject_qty, unit_value: it.unit_value||0, supplier_id: it.supplier_id, nama_supplier: it.nama_supplier, qty_return: qty });
        });
        if (!items.length) { alert('Qty return harus > 0.'); return; }
        var self = this; self.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; self.disabled = true;
        fetch('<?= base_url('Warehouse_out/pull_production_reject_items_to_header') ?>', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ header_id: headerId, items: items })
        }).then(function(r){ return r.json(); }).then(function(j){
            self.innerHTML = '<i class="fas fa-check"></i> Tambahkan'; self.disabled = false;
            if (j.success) { $('#prodReturnModal').modal('hide'); alert(j.message); window.location.reload(); }
            else { alert('Gagal: ' + (j.errors ? j.errors.join('\n') : j.message)); }
        }).catch(function(err){ console.error(err); self.innerHTML = '<i class="fas fa-check"></i> Tambahkan'; self.disabled = false; alert('Error'); });
    });
})();
</script>

<!-- NG QA SELECT MODAL -->
<div class="modal fade" id="ngQaModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle mr-2"></i><?= lang_line('warehouse_out_modal_select_ng', 'Select NG Items from QA'); ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" id="ngQaSearch" class="form-control" placeholder="Cari QA Number, GRN, Supplier...">
                    </div>
                    <div class="col-md-6 text-right">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnNgSelectAll">Pilih Semua QA</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary ml-1" id="btnNgClearAll">Batal Semua</button>
                    </div>
                </div>

                <!-- Step 1: Pilih QA -->
                <div id="ngStep1">
                    <h6 class="text-muted mb-2">Step 1 — Pilih QA yang akan di-return:</h6>
                    <div class="table-responsive" style="max-height:240px; overflow-y:auto;">
                        <table class="table table-sm table-hover" id="ngQaTable">
                            <thead class="thead-light">
                                <tr>
                                    <th width="30"></th>
                                    <th>QA Number</th>
                                    <th>GRN Number</th>
                                    <th>Supplier</th>
                                    <th class="text-center">Tgl QA</th>
                                    <th class="text-center">Total NG Items</th>
                                    <th class="text-center">Total NG Qty</th>
                                </tr>
                            </thead>
                            <tbody id="ngQaTableBody">
                                <tr><td colspan="7" class="text-center text-muted">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-warning" id="btnLoadNgItems">
                            <i class="fas fa-arrow-down"></i> Load Item NG dari QA Terpilih
                        </button>
                    </div>
                </div>

                <!-- Step 2: Pilih item + input qty return -->
                <div id="ngStep2" style="display:none;">
                    <hr>
                    <h6 class="text-muted mb-2">Step 2 — Konfirmasi item NG dan qty yang akan di-return:</h6>
                    <div class="table-responsive" style="max-height:320px; overflow-y:auto;">
                        <table class="table table-sm table-bordered" id="ngItemsTable">
                            <thead class="thead-light" style="font-size:0.8rem;">
                                <tr>
                                    <th width="30"><input type="checkbox" id="ngItemCheckAll" checked></th>
                                    <th>Item</th>
                                    <th>QA / GRN</th>
                                    <th>No. PO</th>
                                    <th class="text-center">Qty Order</th>
                                    <th class="text-center">Qty Terima</th>
                                    <th class="text-center text-success">Pass QA</th>
                                    <th class="text-center text-danger">NG Qty</th>
                                    <th>NG Category</th>
                                    <th class="text-center" style="width:110px;">Qty Return</th>
                                </tr>
                            </thead>
                            <tbody id="ngItemsBody"></tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnBackToQaList">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </button>
                        <button type="button" class="btn btn-danger ml-2" id="btnPullNgConfirm">
                            <i class="fas fa-check"></i> Tambahkan ke Dokumen Return
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ===================== NG QA MODAL =====================
(function(){
    const headerId = <?= (int)$header->id ?>;
    let ngQaData = [];
    let ngItemsData = [];

    function esc(s){ return String(s||'').replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
    function fmt(n){ return (parseFloat(n)||0).toFixed(2); }

    // Open modal and load QA list
    const btnOpen = document.getElementById('btnPullNgQa');
    if (btnOpen) {
        btnOpen.addEventListener('click', function(){
            loadNgQaList('');
            $('#ngQaModal').modal('show');
        });
    }

    function loadNgQaList(search) {
        document.getElementById('ngQaTableBody').innerHTML = '<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
        document.getElementById('ngStep1').style.display = '';
        document.getElementById('ngStep2').style.display = 'none';

        fetch('<?= base_url('Warehouse_out/popup_select_ng_qa') ?>?header_id='+headerId+'&search='+encodeURIComponent(search), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r=>r.json()).then(j=>{
            if (!j.success) { document.getElementById('ngQaTableBody').innerHTML = '<tr><td colspan="7" class="text-danger text-center">'+esc(j.message||'Error')+'</td></tr>'; return; }
            ngQaData = j.data || [];
            renderNgQaTable(ngQaData);
        }).catch(err=>{ console.error(err); document.getElementById('ngQaTableBody').innerHTML = '<tr><td colspan="7" class="text-danger text-center">Gagal memuat data</td></tr>'; });
    }

    function renderNgQaTable(rows) {
        const tbody = document.getElementById('ngQaTableBody');
        if (!rows.length) { tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Tidak ada QA dengan item NG</td></tr>'; return; }
        tbody.innerHTML = rows.map(function(r){
            return '<tr>'
                + '<td><input type="checkbox" class="ng-qa-check" value="'+r.id+'"></td>'
                + '<td><strong>'+esc(r.qa_number)+'</strong></td>'
                + '<td class="small">'+esc(r.grn_number)+'</td>'
                + '<td class="small">'+esc(r.nama_supplier)+'</td>'
                + '<td class="text-center small">'+(r.qa_date||'-')+'</td>'
                + '<td class="text-center"><span class="badge badge-danger">'+r.total_ng_items+'</span></td>'
                + '<td class="text-center text-danger font-weight-bold">'+fmt(r.total_ng_qty)+'</td>'
                + '</tr>';
        }).join('');
    }

    // Search
    document.getElementById('ngQaSearch').addEventListener('input', function(){
        const t = this.value.toLowerCase();
        document.querySelectorAll('#ngQaTableBody tr').forEach(function(tr){
            tr.style.display = tr.textContent.toLowerCase().indexOf(t) === -1 ? 'none' : '';
        });
    });

    document.getElementById('btnNgSelectAll').addEventListener('click', function(){
        document.querySelectorAll('.ng-qa-check').forEach(function(c){ c.checked = true; });
    });
    document.getElementById('btnNgClearAll').addEventListener('click', function(){
        document.querySelectorAll('.ng-qa-check').forEach(function(c){ c.checked = false; });
    });

    // Load NG items from selected QAs
    document.getElementById('btnLoadNgItems').addEventListener('click', function(){
        const ids = Array.from(document.querySelectorAll('.ng-qa-check:checked')).map(function(c){ return parseInt(c.value); });
        if (!ids.length) { alert('Pilih minimal satu QA.'); return; }
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        this.disabled = true;
        const self = this;
        fetch('<?= base_url('Warehouse_out/load_ng_qa_items') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ qa_ids: ids })
        })
        .then(r=>r.json()).then(j=>{
            self.innerHTML = '<i class="fas fa-arrow-down"></i> Load Item NG dari QA Terpilih';
            self.disabled = false;
            if (!j.success) { alert('Gagal: ' + (j.message||'')); return; }
            ngItemsData = j.items || [];
            renderNgItemsTable(ngItemsData);
            document.getElementById('ngStep1').style.display = 'none';
            document.getElementById('ngStep2').style.display = '';
        }).catch(function(err){ console.error(err); self.innerHTML = '<i class="fas fa-arrow-down"></i> Load Item NG'; self.disabled = false; alert('Gagal memuat item NG'); });
    });

    function renderNgItemsTable(items) {
        const tbody = document.getElementById('ngItemsBody');
        if (!items.length) { tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">Tidak ada item NG tersedia</td></tr>'; return; }
        tbody.innerHTML = items.map(function(it, idx){
            const avail = parseFloat(it.available_return || it.qa_reject_qty || 0);
            const disabled = avail <= 0 ? 'disabled' : '';
            return '<tr>'
                + '<td><input type="checkbox" class="ng-item-check" value="'+idx+'" '+(avail>0?'checked':disabled)+'></td>'
                + '<td><strong>'+esc(it.item_code)+'</strong><div class="small text-muted">'+esc(it.item_name)+'</div></td>'
                + '<td class="small text-muted">'+esc(it.qa_number)+'<br>'+esc(it.grn_number)+'</td>'
                + '<td class="small">'+esc(it.po_number||'-')+'</td>'
                + '<td class="text-center">'+fmt(it.qty_ordered)+'</td>'
                + '<td class="text-center">'+fmt(it.receive_qty)+'</td>'
                + '<td class="text-center text-success">'+fmt(it.qa_pass_qty)+'</td>'
                + '<td class="text-center text-danger font-weight-bold">'+fmt(it.qa_reject_qty)+'</td>'
                + '<td class="small"><span class="badge badge-warning text-dark">'+esc(it.ng_category||'-')+'</span></td>'
                + '<td><input type="number" step="0.01" min="0" max="'+avail+'" value="'+fmt(avail)+'" class="form-control form-control-sm ng-qty-return" data-idx="'+idx+'" '+disabled+'></td>'
                + '</tr>';
        }).join('');

        // Check-all toggle
        document.getElementById('ngItemCheckAll').addEventListener('change', function(){
            document.querySelectorAll('.ng-item-check:not(:disabled)').forEach(function(c){ c.checked = this.checked; }.bind(this));
        });
    }

    document.getElementById('btnBackToQaList').addEventListener('click', function(){
        document.getElementById('ngStep1').style.display = '';
        document.getElementById('ngStep2').style.display = 'none';
    });

    // Confirm and pull to header
    document.getElementById('btnPullNgConfirm').addEventListener('click', function(){
        const checked = Array.from(document.querySelectorAll('.ng-item-check:checked'));
        if (!checked.length) { alert('Pilih minimal satu item.'); return; }

        const items = [];
        checked.forEach(function(c){
            const idx = parseInt(c.value);
            const it = ngItemsData[idx];
            const qtyEl = document.querySelector('.ng-qty-return[data-idx="'+idx+'"]');
            const qty = qtyEl ? parseFloat(qtyEl.value)||0 : 0;
            if (qty <= 0) return;
            items.push({
                qa_detail_id:   it.qa_detail_id,
                qa_header_id:   it.qa_header_id,
                grn_header_id:  it.grn_header_id,
                grn_detail_id:  it.grn_detail_id,
                item_id:        it.item_id,
                item_type:      it.item_type,
                item_code:      it.item_code,
                item_name:      it.item_name,
                unit:           it.unit,
                qty_ordered:    it.qty_ordered,
                receive_qty:    it.receive_qty,
                supplier_id:    it.supplier_id,
                unit_value:     it.unit_value || 0,
                qty_return:     qty
            });
        });

        if (!items.length) { alert('Pastikan qty return > 0.'); return; }

        const self = this;
        self.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        self.disabled = true;

        fetch('<?= base_url('Warehouse_out/pull_ng_qa_items_to_header') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ header_id: headerId, items: items })
        })
        .then(r=>r.json()).then(j=>{
            self.innerHTML = '<i class="fas fa-check"></i> Tambahkan ke Dokumen Return';
            self.disabled = false;
            if (j.success) {
                $('#ngQaModal').modal('hide');
                alert(j.message || ('Berhasil menambahkan ' + j.added + ' item'));
                window.location.reload();
            } else {
                alert('Gagal: ' + (j.errors ? j.errors.join('\n') : j.message));
            }
        }).catch(function(err){ console.error(err); self.innerHTML = '<i class="fas fa-check"></i> Tambahkan'; self.disabled = false; alert('Error jaringan'); });
    });
})();
</script>

<!-- ADD ITEM MODAL -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang_line('warehouse_out_modal_add_item', 'Add Item'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo base_url('warehouse_out/add_item/' . $header->id); ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= lang_line('warehouse_out_label_item_source', 'Item Source Type'); ?> <span class="text-danger">*</span></label>
                        <select name="source_type" class="form-select" id="sourceType" required>
                            <option value="">-- Pilih --</option>
                            <option value="GRN">Dari GRN (Material/Semifinished)</option>
                            <option value="FG">Dari Finished Goods</option>
                        </select>
                    </div>

                    <!-- GRN Source -->
                    <div class="mb-3" id="grnSection" style="display:none;">
                        <label class="form-label"><?= lang_line('warehouse_out_label_choose_grn', 'Select GRN'); ?> <span class="text-danger">*</span></label>
                        <select name="grn_id" class="form-select select2" id="grnSelect">
                            <option value=""><?= lang_line('warehouse_out_grn_choice', '-- Select GRN --'); ?></option>
                        </select>
                    </div>

                    <div class="mb-3" id="grnItemSection" style="display:none;">
                        <label class="form-label"><?= lang_line('warehouse_out_label_choose_item_from_grn', 'Item from GRN'); ?> <span class="text-danger">*</span></label>
                        <select name="item_id" class="form-select select2" id="itemSelect">
                            <option value="">-- Pilih Item --</option>
                        </select>
                    </div>

                    <!-- FG Source -->
                    <div class="mb-3" id="fgSection" style="display:none;">
                        <label class="form-label">Pilih Barang Jadi <span class="text-danger">*</span></label>
                        <select name="fg_id" class="form-select select2" id="fgSelect">
                            <option value="">-- Pilih FG --</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><?= lang_line('warehouse_out_label_quantity', 'Quantity'); ?> <span class="text-danger">*</span></label>
                                <input type="number" name="qty" class="form-control" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><?= lang_line('warehouse_out_label_unit', 'Unit'); ?></label>
                                <input type="text" name="satuan" class="form-control" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= lang_line('warehouse_out_label_unit_value', 'Unit Value'); ?> <span class="text-danger">*</span></label>
                        <input type="number" name="unit_value" class="form-control" step="0.01" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= lang_line('warehouse_out_label_item_notes', 'Notes'); ?></label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang_line('warehouse_out_button_cancel', 'Cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= lang_line('warehouse_out_modal_add_item', 'Add Item'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- APPROVE MODAL -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang_line('warehouse_out_modal_approve', 'Approve Goods Issue'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo base_url('warehouse_out/approve/' . $header->id); ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= lang_line('warehouse_out_label_approval_notes', 'Approval Notes'); ?></label>
                        <textarea name="approval_notes" class="form-control" rows="4" placeholder="<?= lang_line('warehouse_out_approval_placeholder', 'Approval notes...'); ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang_line('warehouse_out_button_cancel', 'Cancel'); ?></button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- REJECT MODAL -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang_line('warehouse_out_modal_reject', 'Reject Goods Issue'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo base_url('warehouse_out/reject/' . $header->id); ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= lang_line('warehouse_out_label_rejection_reason', 'Rejection Reason'); ?> <span class="text-danger">*</span></label>
                        <textarea name="rejection_notes" class="form-control" rows="4" placeholder="<?= lang_line('warehouse_out_rejection_placeholder', 'Rejection reason...'); ?>" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang_line('warehouse_out_button_cancel', 'Cancel'); ?></button>
                    <button type="submit" class="btn btn-danger"><?= lang_line('warehouse_out_button_reject', 'Reject'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2();

    // Handle source type change
    $('#sourceType').change(function() {
        const sourceType = $(this).val();
        if (sourceType === 'GRN') {
            $('#grnSection').show();
            $('#grnItemSection').show();
            $('#fgSection').hide();
        } else if (sourceType === 'FG') {
            $('#grnSection').hide();
            $('#grnItemSection').hide();
            $('#fgSection').show();
        } else {
            $('#grnSection').hide();
            $('#grnItemSection').hide();
            $('#fgSection').hide();
        }
    });

    // Load GRN items when GRN is selected
    $('#grnSelect').change(function() {
        const grnId = $(this).val();
        if (grnId) {
            $.ajax({
                url: '<?php echo base_url('warehouse_out/get_grn_items'); ?>',
                data: { grn_id: grnId },
                dataType: 'json',
                success: function(response) {
                    $('#itemSelect').html('<option value="">-- Pilih Item --</option>');
                    $.each(response.results, function(i, item) {
                        $('#itemSelect').append(`<option value="${item.id}" data-satuan="${item.satuan}" data-unit-value="${item.unit_value}">${item.text}</option>`);
                    });
                }
            });
        }
    });

    // Update satuan and unit value when item is selected
    $('#itemSelect, #fgSelect').change(function() {
        const selected = $(this).find('option:selected');
        $('input[name="satuan"]').val(selected.data('satuan') || '');
        $('input[name="unit_value"]').val(selected.data('unit-value') || '');
    });

    // Setup Select2 for remote loading
    $('#grnSelect').select2({
        ajax: {
            url: '<?php echo base_url('warehouse_out/get_grn_list'); ?>',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term
                };
            }
        }
    });

    $('#fgSelect').select2({
        ajax: {
            url: '<?php echo base_url('warehouse_out/get_fg_items'); ?>',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term
                };
            }
        }
    });
});
</script>

<script>
// Compatibility helpers: some pages use Bootstrap 5 attributes (data-bs-*) but site runs Bootstrap 4.
// Ensure approve/reject modals open and close correctly even if data-bs-* isn't recognized.
$(function(){
    // Show approve modal when trigger clicked
    $(document).on('click', '.btn-approve-trigger', function(e){
        e.preventDefault();
        $('#approveModal').modal('show');
    });

    // Show reject modal when trigger clicked
    $(document).on('click', '.btn-reject-trigger', function(e){
        e.preventDefault();
        $('#rejectModal').modal('show');
    });

    // Handle elements using data-bs-dismiss (Bootstrap5) by mapping to Bootstrap4 hide
    $(document).on('click', '[data-bs-dismiss]', function(e){
        var target = $(this).closest('.modal');
        if (target.length) { target.modal('hide'); }
    });

    // Map btn-close (Bootstrap5) to close for Bootstrap4
    $(document).on('click', '.btn-close', function(e){
        var target = $(this).closest('.modal');
        if (target.length) { target.modal('hide'); }
    });
});
</script>

<!-- Load Data Modal -->
<div class="modal fade" id="loadDataModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-download me-2"></i>Load Data dari GRN
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Pilih item yang akan di-retur</strong> dari GRN yang sudah dipilih sebelumnya.
                    Centang item yang ingin diretur dan sistem akan otomatis mengisi data.
                </div>
                
                <form id="loadDataForm">
                    <div class="table-responsive">
                        <table class="table table-hover" id="grnItemsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>Kode Item</th>
                                    <th>Nama Item</th>
                                    <th>Tipe</th>
                                    <th>Qty Diterima</th>
                                    <th>Satuan</th>
                                    <th>Unit Value</th>
                                    <th width="120">Qty Retur</th>
                                </tr>
                            </thead>
                            <tbody id="grnItemsList">
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Loading data...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-primary" onclick="loadSelectedItems()">
                    <i class="fas fa-check me-2"></i>Load Item Terpilih
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Load GRN Items when modal opens
$('#loadDataModal').on('show.bs.modal', function() {
    const grnId = '<?php echo $header->grn_id ?? ''; ?>';
    if (grnId) {
        $.ajax({
            url: '<?php echo base_url('warehouse_out/ajax_get_grn_items_for_retur'); ?>',
            method: 'POST',
            data: { grn_id: grnId },
            dataType: 'json',
            success: function(response) {
                let html = '';
                if (response.success && response.data.length > 0) {
                    $.each(response.data, function(i, item) {
                        html += `
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input item-checkbox" 
                                       value="${item.id}" data-item='${JSON.stringify(item)}'>
                            </td>
                            <td><strong>${item.item_code}</strong></td>
                            <td>${item.item_name}</td>
                            <td>
                                <span class="badge bg-secondary">${item.item_type}</span>
                            </td>
                            <td class="text-end">${parseFloat(item.receive_qty).toLocaleString()}</td>
                            <td>${item.satuan}</td>
                            <td class="text-end">Rp ${parseFloat(item.unit_value).toLocaleString()}</td>
                            <td>
                                <input type="number" class="form-control form-control-sm qty-retur" 
                                       placeholder="0" min="0" max="${item.receive_qty}" step="0.01">
                            </td>
                        </tr>`;
                    });
                } else {
                    html = `<tr><td colspan="8" class="text-center text-muted py-4">
                        <i class="fas fa-inbox me-2"></i>Tidak ada item dalam GRN ini
                    </td></tr>`;
                }
                $('#grnItemsList').html(html);
            },
            error: function() {
                $('#grnItemsList').html(`<tr><td colspan="8" class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>Error loading data
                </td></tr>`);
            }
        });
    }
});

// Select All functionality
$('#selectAll').change(function() {
    const checked = $(this).is(':checked');
    $('.item-checkbox').prop('checked', checked);
    if (checked) {
        $('.qty-retur').each(function() {
            const maxQty = $(this).attr('max');
            $(this).val(maxQty);
        });
    } else {
        $('.qty-retur').val('');
    }
});

// Individual checkbox change
$(document).on('change', '.item-checkbox', function() {
    const checked = $(this).is(':checked');
    const qtyInput = $(this).closest('tr').find('.qty-retur');
    
    if (checked) {
        const maxQty = qtyInput.attr('max');
        qtyInput.val(maxQty);
    } else {
        qtyInput.val('');
    }
});

// Load Selected Items
function loadSelectedItems() {
    const selectedItems = [];
    
    $('.item-checkbox:checked').each(function() {
        const itemData = JSON.parse($(this).data('item'));
        const qtyRetur = $(this).closest('tr').find('.qty-retur').val();
        
        if (qtyRetur && parseFloat(qtyRetur) > 0) {
            selectedItems.push({
                item_id: itemData.item_id,
                item_type: itemData.item_type,
                grn_id: '<?php echo $header->grn_id ?? ''; ?>',
                qty: qtyRetur,
                satuan: itemData.satuan,
                unit_value: itemData.unit_value,
                total_value: parseFloat(qtyRetur) * parseFloat(itemData.unit_value)
            });
        }
    });
    
    if (selectedItems.length === 0) {
        alert('Pilih minimal 1 item dan isi qty retur!');
        return;
    }
    
    // Add items via AJAX
    $.ajax({
        url: '<?php echo base_url('warehouse_out/add_multiple_items'); ?>',
        method: 'POST',
        data: {
            header_id: '<?php echo $header->id; ?>',
            items: selectedItems
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload(); // Refresh to show new items
            } else {
                alert('Error: ' + (response.message || 'Gagal menambah item'));
            }
        },
        error: function() {
            alert('Error dalam komunikasi server');
        }
    });
}
</script>
