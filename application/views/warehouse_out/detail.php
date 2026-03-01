<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid mt-4">
    <!-- HEADER INFO CARD -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-0">Retur #<?php echo isset($header->out_number) && $header->out_number ? $header->out_number : 'Draft'; ?></h5>
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
                    <p><strong>Tanggal:</strong> <?php
                        $d = $header->out_date ?? $header->created_at ?? null;
                        echo $d ? date('d M Y', strtotime($d)) : '-';
                    ?></p>
                    <p><strong>Tipe:</strong> <?php echo $header->base_on ?? '-'; ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>Gudang:</strong> <?php echo $header->warehouse_type ?? '-'; ?></p>
                    <p><strong>Dibuat oleh:</strong> <?php echo $header->created_by_name ?? $header->created_by ?? '-'; ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>Partner:</strong> <?php echo $header->nama_supplier ?? $header->supplier_name ?? $header->partner_name ?? '-'; ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>BC Info:</strong> <?php echo ($header->bc_type_code ? $header->bc_type_code . ' / ' . $header->bc_number : '-'); ?></p>
                    <?php if ($header->status == 'Draft' && check_access('warehouse_out', 'edit')): ?>
                        <div class="mt-2">
                            <label class="form-label small mb-1">Currency</label>
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
                    <p><strong>Catatan:</strong> <?php echo nl2br($header->reference_note ?? '') ?: '-'; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- ACTION BUTTONS -->
    <div class="mb-3">
        <?php if ($header->status == 'Draft' && check_access('warehouse_out', 'edit')): ?>
            <a href="<?php echo base_url('warehouse_out/submit_for_approval/' . $header->id); ?>" class="btn btn-warning btn-sm" onclick="return confirm('Submit untuk approval?')">
                <i class="fas fa-arrow-right"></i> Submit untuk Approval
            </a>
            <a href="<?php echo base_url('warehouse_out'); ?>" class="btn btn-secondary btn-sm">Kembali</a>
        <?php endif; ?>

        <?php if ($header->status == 'Pending Approval' && check_access('warehouse_out', 'approve')): ?>
            <button type="button" class="btn btn-success btn-sm btn-approve-trigger" data-bs-toggle="modal" data-bs-target="#approveModal">
                <i class="fas fa-check"></i> Approve
            </button>
            <button type="button" class="btn btn-danger btn-sm btn-reject-trigger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="fas fa-times"></i> Reject
            </button>
        <?php endif; ?>

        <?php if ($header->status == 'Approved' && check_access('warehouse_out', 'edit')): ?>
            <a href="<?php echo base_url('warehouse_out/confirm/' . $header->id); ?>" class="btn btn-primary btn-sm" onclick="return confirm('Konfirmasi pengeluaran? Stok akan diperbarui.')">
                <i class="fas fa-check-circle"></i> Confirm
            </a>
        <?php endif; ?>

        <?php if ($header->status == 'Confirmed' && check_access('warehouse_out', 'edit')): ?>
            <a href="<?php echo base_url('warehouse_out/unconfirm/' . $header->id); ?>" class="btn btn-warning btn-sm" onclick="return confirm('Batalkan konfirmasi?')">
                <i class="fas fa-undo"></i> Batalkan Konfirmasi
            </a>
        <?php endif; ?>

        <a href="<?php echo base_url('warehouse_out/export_pdf/' . $header->id); ?>" class="btn btn-secondary btn-sm" target="_blank">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
        <a href="<?php echo base_url('warehouse_out/export_excel/' . $header->id); ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
    </div>

    <!-- ITEMS CARD -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">Detail Item</h5>
                </div>
                <div class="col-md-6 text-end">
                        <?php if ($header->status == 'Draft' && check_access('warehouse_out', 'edit')): ?>
                            <button type="button" id="btnPullGrn" class="btn btn-sm btn-primary me-2">
                                <i class="fas fa-download"></i> Tarik Data dari GRN
                            </button>
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                <i class="fas fa-plus"></i> Tambah Item Manual
                            </button>
                        <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th>QTY PO/MIR</th>
                            <th>QTY DITERIMA</th>
                            <th>QTY RETUR</th>
                            <th>Satuan</th>
                            <th>Unit Value</th>
                            <th>Total Value</th>
                            <th>GRN Asal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($details)): ?>
                            <?php foreach ($details as $detail): ?>
                                <?php
                                    $code = $detail->item_code ?? $detail->kode_barang ?? '';
                                    $name = $detail->item_name ?? $detail->nama_barang ?? '';
                                    $qty_po = isset($detail->qty_po) ? (float)$detail->qty_po : 0;
                                    $qty_received = isset($detail->qty_received) ? (float)$detail->qty_received : (isset($detail->receive_qty) ? (float)$detail->receive_qty : 0);
                                    $qty_return = isset($detail->qty_return) ? (float)$detail->qty_return : 0;
                                    $unit = $detail->unit ?? $detail->satuan ?? '';
                                    $unit_value = isset($detail->unit_value_idr) ? (float)$detail->unit_value_idr : (isset($detail->unit_value) ? (float)$detail->unit_value : 0);
                                    $total = isset($detail->total_value) ? (float)$detail->total_value : ($unit_value * $qty_return);
                                    $grn_label = $detail->grn_number ?? (isset($detail->grn_id) ? $detail->grn_id : '-');
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($code); ?></td>
                                    <td><?php echo htmlspecialchars($name); ?></td>
                                    <td class="text-end"><?php echo number_format($qty_po, 2); ?></td>
                                    <td class="text-end"><?php echo number_format($qty_received, 2); ?></td>
                                    <td class="text-end">
                                        <?php if ($header->status == 'Draft' && check_access('warehouse_out', 'edit')): ?>
                                            <input type="number" step="0.01" min="0" max="<?php echo $qty_received; ?>" value="<?php echo number_format($qty_return,2,'.',''); ?>" class="form-control form-control-sm text-end qty-return-input" data-detail-id="<?= $detail->id ?>" />
                                        <?php else: ?>
                                            <?php echo number_format($qty_return, 2); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($unit); ?></td>
                                    <td class="text-end">
                                        <?php if ($header->status == 'Draft' && check_access('warehouse_out', 'edit')): ?>
                                            <input type="number" step="0.01" min="0" value="<?php echo number_format($unit_value,2,'.',''); ?>" class="form-control form-control-sm text-end unit-value-input" data-detail-id="<?= $detail->id ?>" />
                                        <?php else: ?>
                                            Rp <?php echo number_format($unit_value, 2); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">Rp <span class="detail-total" data-detail-id="<?= $detail->id ?>"><?php echo number_format($total, 2); ?></span></td>
                                    <td><?php echo htmlspecialchars($grn_label); ?></td>
                                    <td>
                                        <?php if ($header->status == 'Draft' && check_access('warehouse_out', 'edit')): ?>
                                            <button type="button" class="btn btn-xs btn-primary save-detail-btn" data-detail-id="<?= $detail->id ?>" title="Simpan">
                                                <i class="fas fa-save"></i>
                                            </button>
                                            <a href="<?php echo base_url('warehouse_out/delete_item/' . $header->id . '/' . $detail->id); ?>" 
                                               class="btn btn-xs btn-danger ms-1" onclick="return confirm('Hapus item?')" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">Belum ada item. Tambahkan item untuk memulai.</td>
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

<script>
// Live recalc and Save per-row for qty_return + unit_value
function recalcTotalForDetail(detailId) {
    const qtyEl = document.querySelector('.qty-return-input[data-detail-id="' + detailId + '"]');
    const unitEl = document.querySelector('.unit-value-input[data-detail-id="' + detailId + '"]');
    const span = document.querySelector('.detail-total[data-detail-id="' + detailId + '"]');
    const qty = qtyEl ? parseFloat(qtyEl.value) || 0 : 0;
    const unit = unitEl ? parseFloat(unitEl.value) || 0 : 0;
    const total = qty * unit;
    if (span) span.textContent = Number(total).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
}

document.addEventListener('DOMContentLoaded', function(){
    // bind live recalc on inputs
    document.querySelectorAll('.qty-return-input').forEach(function(inp){
        inp.addEventListener('input', function(){
            recalcTotalForDetail(this.dataset.detailId);
        });
    });
    document.querySelectorAll('.unit-value-input').forEach(function(inp){
        inp.addEventListener('input', function(){
            recalcTotalForDetail(this.dataset.detailId);
        });
    });

    // bind save buttons
    document.querySelectorAll('.save-detail-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            const detailId = this.dataset.detailId;
            const qtyEl = document.querySelector('.qty-return-input[data-detail-id="' + detailId + '"]');
            const unitEl = document.querySelector('.unit-value-input[data-detail-id="' + detailId + '"]');
            const qty = qtyEl ? parseFloat(qtyEl.value) || 0 : 0;
            const unit = unitEl ? parseFloat(unitEl.value) || 0 : 0;
            const currencyEl = document.getElementById('headerCurrency');
            const currency = currencyEl ? currencyEl.value : '';
            const fd = new FormData();
            fd.append('detail_id', detailId);
            fd.append('qty_return', qty);
            fd.append('unit_value', unit);
            if (currency) fd.append('currency', currency);
            const btnOldHtml = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.disabled = true;
            fetch('<?= base_url('Warehouse_out/update_detail_ajax') ?>', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r=>r.json()).then(j=>{
                this.disabled = false; this.innerHTML = btnOldHtml;
                if (!j.success) { alert('Gagal simpan: ' + (j.message || (j.errors ? j.errors.join('\n') : 'unknown'))); return; }
                // update total cell
                const span = document.querySelector('.detail-total[data-detail-id="'+detailId+'"]');
                if (span) span.textContent = Number(j.total).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
            }).catch(err=>{ console.error(err); this.disabled = false; this.innerHTML = btnOldHtml; alert('Gagal menyimpan perubahan (network)'); });
        });
    });

    // initial recalc for existing rows
    document.querySelectorAll('.detail-total').forEach(function(s){
        const id = s.getAttribute('data-detail-id');
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

<!-- ADD ITEM MODAL -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo base_url('warehouse_out/add_item/' . $header->id); ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tipe Sumber <span class="text-danger">*</span></label>
                        <select name="source_type" class="form-select" id="sourceType" required>
                            <option value="">-- Pilih --</option>
                            <option value="GRN">Dari GRN (Material/Semifinished)</option>
                            <option value="FG">Dari Finished Goods</option>
                        </select>
                    </div>

                    <!-- GRN Source -->
                    <div class="mb-3" id="grnSection" style="display:none;">
                        <label class="form-label">Pilih GRN <span class="text-danger">*</span></label>
                        <select name="grn_id" class="form-select select2" id="grnSelect">
                            <option value="">-- Pilih GRN --</option>
                        </select>
                    </div>

                    <div class="mb-3" id="grnItemSection" style="display:none;">
                        <label class="form-label">Item dari GRN <span class="text-danger">*</span></label>
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
                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="qty" class="form-control" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Satuan</label>
                                <input type="text" name="satuan" class="form-control" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Unit Value <span class="text-danger">*</span></label>
                        <input type="number" name="unit_value" class="form-control" step="0.01" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah Item</button>
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
                <h5 class="modal-title">Approve Pengeluaran Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo base_url('warehouse_out/approve/' . $header->id); ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Catatan Approval</label>
                        <textarea name="approval_notes" class="form-control" rows="4" placeholder="Catatan approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
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
                <h5 class="modal-title">Reject Pengeluaran Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo base_url('warehouse_out/reject/' . $header->id); ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="rejection_notes" class="form-control" rows="4" placeholder="Alasan penolakan..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
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
