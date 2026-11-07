<?php
/**
 * Modal Tarik PO / Tarik AP Invoice + seluruh JavaScript form item Payment Request.
 * Pasangan prf/_items_card.php; dipakai prf/create & prf/edit (2026-11-06).
 *
 * Variabel: $billable_pos, $open_aps, $line_po_meta, $prf (edit saja)
 *
 * Catatan teknis: jQuery dimuat DUA kali (header & footer), jadi semua yang
 * memakai jQuery/select2/bootstrap dipasang di DOMContentLoaded -- saat itu
 * window.jQuery sudah instance terakhir yang punya select2 & modal.
 */
$prf_id_for_pull = isset($prf) && !empty($prf->id) ? (int) $prf->id : 0;

// Info PPN per PO untuk baris lama (edit) -> dipakai hitung ulang baris PPN di JS.
$po_ppn_seed = [];
foreach (($line_po_meta ?? []) as $m) {
    $po_ppn_seed[(int) $m['po_header_id']] = [
        'applied' => (bool) $m['ppn_applied'], 'rate' => (float) $m['ppn_rate'], 'inclusive' => (bool) $m['ppn_inclusive'],
        'label' => $m['ppn_applied'] ? po_ppn_label($m['ppn_rate'], $m['ppn_inclusive']) : '', 'po_number' => $m['po_number'],
    ];
}
$ap_js = [];
foreach (($open_aps ?? []) as $ap) {
    $ap_js[] = [
        'id' => (int) $ap->id, 'invoice_no' => $ap->invoice_no, 'invoice_date' => $ap->invoice_date, 'due_date' => $ap->due_date,
        'supplier_id' => (int) $ap->supplier_id, 'supplier_name' => $ap->nama_supplier,
        'total' => (float) $ap->total_invoice, 'paid' => (float) $ap->total_paid, 'outstanding' => (float) $ap->outstanding,
    ];
}
?>
<!-- Modal: Tarik dari PO -->
<div class="modal fade" id="pullPoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-download mr-1"></i> <?= lang_line('prf_pull_po_title', 'Tarik Item dari Purchase Order'); ?></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label><?= lang_line('prf_pull_po_select', 'Pilih Purchase Order'); ?>:</label>
                    <select id="po_select" class="form-control" style="width: 100%;">
                        <option value=""><?= lang_line('prf_select_po', '-- Pilih PO --'); ?></option>
                        <?php foreach (($billable_pos ?? []) as $po): ?>
                            <option value="<?= (int) $po->id; ?>" data-supplier-id="<?= (int) $po->supplier_id; ?>">
                                <?= htmlspecialchars($po->po_number); ?> — <?= htmlspecialchars($po->nama_supplier ?? '-'); ?>
                                (<?= (int) $po->lines_open; ?> <?= lang_line('prf_pull_po_lines', 'baris'); ?>, Rp <?= number_format($po->amount_open, 0, ',', '.'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted mt-2">
                        <?= lang_line('prf_pull_po_hint', 'Hanya PO yang masih punya sisa tagihan. Qty yang bisa ditarik = barang yang <strong>sudah datang</strong> (penerimaan barang / penyelesaian jasa / aset diterima) dikurangi yang sudah ditagih. PO Jasa boleh ditagih tanpa penerimaan. Harga = harga PO tanpa PPN; PPN dihitung otomatis per PO.'); ?>
                    </small>
                </div>
                <div id="po_ppn_info" class="alert alert-secondary py-1 small" style="display:none"></div>
                <div id="po_details_preview" style="display: none;" class="mt-2">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 4%;"><input type="checkbox" id="po_item_select_all" checked></th>
                                    <th><?= lang_line('prf_th_desc', 'Deskripsi'); ?></th>
                                    <th class="text-right"><?= lang_line('prf_pull_th_po_qty', 'Qty PO'); ?></th>
                                    <th class="text-right"><?= lang_line('prf_pull_th_received', 'Sudah Datang'); ?></th>
                                    <th class="text-right"><?= lang_line('prf_pull_th_billed', 'Sudah Ditagih'); ?></th>
                                    <th class="text-right"><?= lang_line('prf_pull_th_remaining', 'Bisa Ditagih'); ?></th>
                                    <th class="text-center"><?= lang_line('prf_th_unit', 'Unit'); ?></th>
                                    <th class="text-right"><?= lang_line('prf_pull_th_price', 'Harga (tanpa PPN)'); ?></th>
                                    <th class="text-right"><?= lang_line('prf_th_subtotal', 'Subtotal'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="po_items_preview"></tbody>
                        </table>
                    </div>
                </div>
                <div id="po_details_empty" class="text-muted small" style="display:none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('prf_btn_cancel', 'Batal'); ?></button>
                <button type="button" class="btn btn-primary" onclick="confirmPullPo()" id="btn_pull_confirm" disabled>
                    <?= lang_line('prf_pull_po_confirm', 'Tarik Item Terpilih'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Tarik dari AP Invoice -->
<div class="modal fade" id="pullApModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-invoice-dollar mr-1"></i> <?= lang_line('prf_pull_ap_title', 'Tarik dari AP Invoice (Hutang)'); ?></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">
                    <?= lang_line('prf_pull_ap_hint', 'AP Invoice yang sudah diposting Accounting (jurnal hutangnya sudah ada) dan masih punya sisa. Menarik AP Invoice <strong>tidak membuat jurnal hutang lagi</strong> — saat dibayar hanya jurnal pembayaran yang dibuat. Nominal bisa diubah di tabel item untuk bayar sebagian.'); ?>
                </p>
                <?php if (empty($ap_js)): ?>
                    <div class="alert alert-light border mb-0"><?= lang_line('prf_pull_ap_empty', 'Tidak ada AP Invoice terbuka.'); ?></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 4%;"></th>
                                <th><?= lang_line('prf_pull_ap_th_no', 'No. AP Invoice'); ?></th>
                                <th><?= lang_line('prf_lbl_supplier', 'Supplier'); ?></th>
                                <th><?= lang_line('prf_pull_ap_th_date', 'Tanggal'); ?></th>
                                <th><?= lang_line('prf_th_due_date', 'Jatuh Tempo'); ?></th>
                                <th class="text-right"><?= lang_line('prf_pull_ap_th_total', 'Total'); ?></th>
                                <th class="text-right"><?= lang_line('prf_pull_ap_th_outstanding', 'Sisa'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ap_js as $i => $ap): ?>
                            <tr>
                                <td class="text-center"><input type="checkbox" class="ap-pick" value="<?= $i; ?>"></td>
                                <td><?= htmlspecialchars($ap['invoice_no']); ?></td>
                                <td><?= htmlspecialchars($ap['supplier_name'] ?? '-'); ?></td>
                                <td><?= $ap['invoice_date'] ? date('d-m-Y', strtotime($ap['invoice_date'])) : '-'; ?></td>
                                <td><?= $ap['due_date'] ? date('d-m-Y', strtotime($ap['due_date'])) : '-'; ?></td>
                                <td class="text-right"><?= number_format($ap['total'], 2, ',', '.'); ?></td>
                                <td class="text-right font-weight-bold"><?= number_format($ap['outstanding'], 2, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('prf_btn_cancel', 'Batal'); ?></button>
                <button type="button" class="btn btn-primary" onclick="confirmPullAp()" <?= empty($ap_js) ? 'disabled' : ''; ?>>
                    <?= lang_line('prf_pull_ap_confirm', 'Tarik AP Invoice Terpilih'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const PRF_ID_FOR_PULL = <?= $prf_id_for_pull; ?>;
const OPEN_APS = <?= json_encode($ap_js); ?>;
const PO_PPN = <?= json_encode((object) $po_ppn_seed); ?>;
const PRF_TXT = <?= json_encode([
    'mixed'        => lang_line('prf_js_mixed', 'Payment Request hutang (dari PO / AP Invoice) tidak boleh dicampur item manual. Item manual yang sudah diisi akan dihapus. Lanjutkan?'),
    'supplier'     => lang_line('prf_js_supplier', 'Satu Payment Request hutang hanya untuk satu supplier. Item yang sudah ada milik supplier lain.'),
    'pick_po'      => lang_line('prf_js_pick_po', 'Pilih PO terlebih dahulu.'),
    'pick_item'    => lang_line('prf_js_pick_item', 'Pilih minimal 1 item.'),
    'nothing_open' => lang_line('prf_js_nothing_open', 'Tidak ada item PO ini yang bisa ditagih (belum ada barang datang / sudah ditagih semua).'),
    'qty_over'     => lang_line('prf_js_qty_over', 'Qty melebihi sisa yang bisa ditagih'),
    'amt_over'     => lang_line('prf_js_amt_over', 'Nominal melebihi sisa AP Invoice'),
    'max'          => lang_line('prf_hint_max_qty', 'maks'),
    'sisa'         => lang_line('prf_hint_max_amount', 'sisa'),
    'ppn_auto'     => lang_line('prf_hint_ppn_auto', 'PPN otomatis dari PO'),
    'jasa'         => lang_line('prf_js_jasa_no_grn', 'Jasa (tanpa penerimaan)'),
    'fetch_err'    => lang_line('prf_js_fetch_err', 'Gagal mengambil data PO.'),
    'ppn_info'     => lang_line('prf_js_ppn_info', 'PO ini kena'),
]); ?>;

let selectedPoData = null;
let rowIndex = document.querySelectorAll('#detail-body .detail-row').length;
let hutangSupplierId = null;

/* ---------- angka ---------- */
function parseIndoNumber(value) {
    if (value === null || value === undefined) return 0;
    value = String(value).trim().replace(/\s/g, '');
    if (value === '') return 0;
    if (value.includes(',')) { value = value.replace(/\./g, '').replace(',', '.'); }
    const n = parseFloat(value);
    return isNaN(n) ? 0 : n;
}
function formatNumber(num) {
    return (typeof num === 'number' ? num : parseIndoNumber(num)).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function formatInputNumber(value) {
    if (value === null || value === undefined || value === '') return '';
    return (typeof value === 'number' ? value : parseIndoNumber(value)).toLocaleString('id-ID', { useGrouping: false, minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function fmtQty(v) { return (Math.round(v * 100) / 100).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }
function round2(v) { return Math.round((v + Number.EPSILON) * 100) / 100; }
function htmlEscape(str) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(str ?? '').replace(/[&<>"']/g, m => map[m]);
}

/* ---------- baris ---------- */
function rows() { return Array.from(document.querySelectorAll('#detail-body .detail-row')); }
function rowsOf(kind) { return rows().filter(r => r.dataset.kind === kind); }
function isHutangMode() { return rows().some(r => r.dataset.kind === 'po' || r.dataset.kind === 'ap'); }
function manualRowFilled(r) {
    const desc = r.querySelector('.desc-input');
    if (desc && desc.value.trim() !== '') return true;
    return ['.qty-input', '.price-input'].some(sel => { const el = r.querySelector(sel); return el && parseIndoNumber(el.value) !== 0; });
}

function unitSelectHtml(idx, selected) {
    const tpl = document.getElementById('prfUnitTemplate');
    const sel = document.createElement('select');
    sel.name = `details[${idx}][unit]`;
    sel.className = 'form-control unit-select';
    sel.innerHTML = tpl ? tpl.innerHTML : '<option value="">-</option>';
    if (selected) {
        if (!Array.from(sel.options).some(o => o.value === selected)) {
            const o = document.createElement('option'); o.value = selected; o.textContent = selected; sel.appendChild(o);
        }
        sel.value = selected;
    }
    return sel.outerHTML;
}

/**
 * Bikin <tr>. kind: manual | po | ap | ppn. opt: {description, qty, unit, unit_price,
 * po_header_id, po_detail_id, ap_id, max, local_price, hint}
 */
function createDetailRow(kind, opt) {
    opt = opt || {};
    const idx = rowIndex++;
    const tr = document.createElement('tr');
    tr.className = 'detail-row' + (kind === 'ppn' ? ' table-light' : '');
    tr.dataset.idx = idx;
    tr.dataset.kind = kind;
    tr.dataset.poId = opt.po_header_id || 0;
    tr.dataset.poDetailId = opt.po_detail_id || 0;
    tr.dataset.apId = opt.ap_id || 0;
    tr.dataset.max = (opt.max !== undefined && opt.max !== null) ? opt.max : '';
    tr.dataset.localPrice = opt.local_price || opt.unit_price || 0;
    const roPrice = kind === 'po' || kind === 'ppn';
    const roQty = kind === 'ap' || kind === 'ppn';
    const unitCell = (kind === 'ppn' || kind === 'ap')
        ? `<input type="hidden" name="details[${idx}][unit]" value=""><span class="text-muted">-</span>`
        : unitSelectHtml(idx, opt.unit || '');
    tr.innerHTML = `
        <td class="row-number"></td>
        <td>
            <input type="text" name="details[${idx}][description]" class="form-control desc-input" value="${htmlEscape(opt.description || '')}" ${kind === 'ppn' ? 'readonly' : 'required'}>
            <input type="hidden" name="details[${idx}][po_header_id]" value="${opt.po_header_id || ''}">
            <input type="hidden" name="details[${idx}][po_detail_id]" value="${opt.po_detail_id || ''}">
            <input type="hidden" name="details[${idx}][ap_invoice_header_id]" value="${opt.ap_id || ''}">
            <input type="hidden" name="details[${idx}][line_type]" value="${kind === 'ppn' ? 'ppn' : 'item'}">
            <small class="text-muted row-hint">${opt.hint || ''}</small>
        </td>
        <td><input type="text" name="details[${idx}][qty]" class="form-control qty-input" inputmode="decimal" value="${opt.qty !== undefined ? formatInputNumber(opt.qty) : ''}" ${roQty ? 'readonly' : 'required'}></td>
        <td>${unitCell}</td>
        <td><input type="text" name="details[${idx}][unit_price]" class="form-control price-input" inputmode="decimal" value="${opt.unit_price !== undefined ? formatInputNumber(opt.unit_price) : ''}" ${roPrice ? 'readonly' : 'required'}></td>
        <td class="subtotal-cell text-right">0,00</td>
        <td>${kind === 'ppn' ? '' : '<button type="button" class="btn btn-sm btn-danger" onclick="deleteRow(this)"><i class="fas fa-trash"></i></button>'}</td>`;
    return tr;
}

function addRow() {
    if (isHutangMode()) return;
    document.getElementById('detail-body').appendChild(createDetailRow('manual'));
    refreshAll();
}

function deleteRow(button) {
    button.closest('tr').remove();
    if (rows().filter(r => r.dataset.kind !== 'ppn').length === 0) {
        rowsOf('ppn').forEach(r => r.remove());
        document.getElementById('detail-body').appendChild(createDetailRow('manual'));
    }
    refreshAll();
}

/* Hapus baris manual sebelum masuk mode hutang. false = user batal. */
function clearManualRowsForHutang() {
    const manual = rowsOf('manual');
    const filled = manual.filter(manualRowFilled);
    if (filled.length && !confirm(PRF_TXT.mixed)) return false;
    manual.forEach(r => r.remove());
    return true;
}

function checkSupplier(supplierId) {
    supplierId = parseInt(supplierId || 0, 10);
    if (isHutangMode() && hutangSupplierId && supplierId && hutangSupplierId !== supplierId) {
        alert(PRF_TXT.supplier);
        return false;
    }
    return true;
}

/* ---------- PPN per PO (sama dengan Ap_auto_model::ppn_for_lines) ---------- */
function recomputePpn() {
    const body = document.getElementById('detail-body');
    const groups = {};
    rowsOf('po').forEach(r => { (groups[r.dataset.poId] = groups[r.dataset.poId] || []).push(r); });
    rowsOf('ppn').forEach(r => { if (!groups[r.dataset.poId]) r.remove(); });

    Object.keys(groups).forEach(poId => {
        const meta = PO_PPN[poId] || { applied: false };
        let dpp = 0, gross = 0;
        groups[poId].forEach(r => {
            const q = parseIndoNumber(r.querySelector('.qty-input').value);
            dpp += round2(q * parseIndoNumber(r.querySelector('.price-input').value));
            gross += round2(q * parseFloat(r.dataset.localPrice || 0));
        });
        const ppn = !meta.applied ? 0 : (meta.inclusive ? round2(gross - dpp) : round2(dpp * meta.rate / 100));
        let ppnRow = rowsOf('ppn').find(r => r.dataset.poId === poId);
        if (ppn <= 0) { if (ppnRow) ppnRow.remove(); return; }
        if (!ppnRow) {
            ppnRow = createDetailRow('ppn', { po_header_id: poId, qty: 1, unit_price: 0,
                description: (meta.label || 'PPN') + ' - ' + (meta.po_number || ''),
                hint: '<i class="fas fa-percent"></i> ' + PRF_TXT.ppn_auto });
        }
        ppnRow.querySelector('.price-input').value = formatInputNumber(ppn);
        // Letakkan tepat setelah baris terakhir PO ini.
        const last = groups[poId][groups[poId].length - 1];
        if (last.nextSibling !== ppnRow) body.insertBefore(ppnRow, last.nextSibling);
    });
}

function refreshModeUi() {
    const hutang = isHutangMode();
    document.getElementById('prfHutangInfo').style.display = hutang ? '' : 'none';
    document.getElementById('manualAddWrap').style.display = hutang ? 'none' : '';
    const sel = document.getElementById('supplier_id');
    if (!sel) return;
    if (hutang && hutangSupplierId) {
        if (String(sel.value) !== String(hutangSupplierId)) {
            sel.value = hutangSupplierId;
            if (window.jQuery) jQuery(sel).trigger('change'); else sel.dispatchEvent(new Event('change'));
        }
        if (typeof fillSupplierBank === 'function') fillSupplierBank();
    }
    // Supplier PRF hutang dikunci ke supplier PO / AP (server juga memaksanya).
    sel.disabled = hutang;
    if (window.jQuery && jQuery(sel).data('select2')) jQuery(sel).prop('disabled', hutang).trigger('change.select2');
}

function updateRowNumbers() { rows().forEach((r, i) => { const c = r.querySelector('.row-number'); if (c) c.textContent = i + 1; }); }

function calculateSubtotal() {
    let total = 0;
    rows().forEach(r => {
        const q = parseIndoNumber(r.querySelector('.qty-input')?.value);
        const p = parseIndoNumber(r.querySelector('.price-input')?.value);
        const sub = round2(q * p);
        const cell = r.querySelector('.subtotal-cell');
        if (cell) cell.textContent = formatNumber(sub);
        total += sub;
    });
    document.getElementById('total-amount').textContent = formatNumber(total);
}

function refreshAll() {
    if (!isHutangMode()) hutangSupplierId = null;
    recomputePpn();
    updateRowNumbers();
    calculateSubtotal();
    refreshModeUi();
}

/* ---------- Tarik dari PO ---------- */
function loadPoItems(poId) {
    const preview = document.getElementById('po_details_preview');
    const empty = document.getElementById('po_details_empty');
    const btn = document.getElementById('btn_pull_confirm');
    const ppnInfo = document.getElementById('po_ppn_info');
    selectedPoData = null; btn.disabled = true; preview.style.display = 'none'; empty.style.display = 'none'; ppnInfo.style.display = 'none';
    if (!poId) return;

    fetch('<?= base_url('prf/get_po_items_json'); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'po_id=' + encodeURIComponent(poId) + '&prf_id=' + PRF_ID_FOR_PULL
            + '&<?= $this->security->get_csrf_token_name(); ?>=<?= $this->security->get_csrf_hash(); ?>'
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { alert(PRF_TXT.fetch_err + ' ' + (data.message || '')); return; }
        selectedPoData = data;
        PO_PPN[data.po.id] = Object.assign({}, data.ppn, { po_number: data.po.po_number });
        if (data.ppn && data.ppn.applied) {
            ppnInfo.textContent = PRF_TXT.ppn_info + ' ' + data.ppn.label + '.';
            ppnInfo.style.display = '';
        }
        if (!data.details.length) { empty.textContent = PRF_TXT.nothing_open; empty.style.display = ''; return; }
        const tbody = document.getElementById('po_items_preview');
        tbody.innerHTML = '';
        data.details.forEach((d, i) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center"><input type="checkbox" class="po-item-check" data-idx="${i}" checked></td>
                <td>${htmlEscape(d.description)}${d.is_jasa ? ' <span class="badge badge-light border">' + PRF_TXT.jasa + '</span>' : ''}</td>
                <td class="text-right">${fmtQty(d.po_qty)}</td>
                <td class="text-right">${d.is_jasa ? '-' : fmtQty(d.received_qty)}</td>
                <td class="text-right">${fmtQty(d.billed_qty)}</td>
                <td class="text-right font-weight-bold">${fmtQty(d.remaining_qty)}</td>
                <td class="text-center">${htmlEscape(d.unit || '-')}</td>
                <td class="text-right">${formatNumber(d.unit_price)}</td>
                <td class="text-right">${formatNumber(d.subtotal)}</td>`;
            tbody.appendChild(tr);
        });
        document.getElementById('po_item_select_all').checked = true;
        preview.style.display = '';
        btn.disabled = false;
    })
    .catch(err => { console.error(err); alert(PRF_TXT.fetch_err); });
}

function confirmPullPo() {
    if (!selectedPoData) { alert(PRF_TXT.pick_po); return; }
    const picked = Array.from(document.querySelectorAll('.po-item-check:checked')).map(cb => selectedPoData.details[parseInt(cb.dataset.idx, 10)]);
    if (!picked.length) { alert(PRF_TXT.pick_item); return; }
    if (!checkSupplier(selectedPoData.po.supplier_id)) return;
    if (!isHutangMode() && !clearManualRowsForHutang()) return;

    hutangSupplierId = parseInt(selectedPoData.po.supplier_id || 0, 10) || null;
    const body = document.getElementById('detail-body');
    const poId = String(selectedPoData.po.id);
    picked.forEach(d => {
        const hint = `<i class="fas fa-file-invoice"></i> PO ${htmlEscape(selectedPoData.po.po_number)} · ${PRF_TXT.max} ${fmtQty(d.remaining_qty)}`;
        const existing = rowsOf('po').find(r => r.dataset.poDetailId === String(d.po_detail_id));
        if (existing) {
            existing.dataset.max = d.remaining_qty;
            existing.querySelector('.qty-input').value = formatInputNumber(d.remaining_qty);
            existing.querySelector('.row-hint').innerHTML = hint;
            return;
        }
        // Sisipkan sesudah baris PO yang sama (supaya PPN per PO tetap di bawahnya).
        const tr = createDetailRow('po', {
            description: d.description, qty: d.remaining_qty, unit: d.unit, unit_price: d.unit_price,
            local_price: d.local_price, po_header_id: poId, po_detail_id: d.po_detail_id, max: d.remaining_qty, hint: hint,
        });
        const sameGroup = rowsOf('po').filter(r => r.dataset.poId === poId);
        if (sameGroup.length) body.insertBefore(tr, sameGroup[sameGroup.length - 1].nextSibling); else body.appendChild(tr);
    });
    refreshAll();
    if (window.jQuery) jQuery('#pullPoModal').modal('hide');
}

/* ---------- Tarik dari AP Invoice ---------- */
function confirmPullAp() {
    const picked = Array.from(document.querySelectorAll('.ap-pick:checked')).map(cb => OPEN_APS[parseInt(cb.value, 10)]);
    if (!picked.length) { alert(PRF_TXT.pick_item); return; }
    const sup = new Set(picked.map(a => a.supplier_id));
    if (sup.size > 1) { alert(PRF_TXT.supplier); return; }
    if (!checkSupplier(picked[0].supplier_id)) return;
    if (!isHutangMode() && !clearManualRowsForHutang()) return;

    hutangSupplierId = picked[0].supplier_id || null;
    const body = document.getElementById('detail-body');
    picked.forEach(a => {
        if (rowsOf('ap').some(r => r.dataset.apId === String(a.id))) return;
        body.appendChild(createDetailRow('ap', {
            description: 'Pembayaran AP Invoice ' + a.invoice_no, qty: 1, unit_price: a.outstanding,
            ap_id: a.id, max: a.outstanding,
            hint: `<i class="fas fa-file-invoice-dollar"></i> AP ${htmlEscape(a.invoice_no)} · ${PRF_TXT.sisa} Rp ${formatNumber(a.outstanding)}`,
        }));
    });
    document.querySelectorAll('.ap-pick:checked').forEach(cb => cb.checked = false);
    refreshAll();
    if (window.jQuery) jQuery('#pullApModal').modal('hide');
}

/* ---------- supplier ---------- */
function fillSupplierBank() {
    const sel = document.getElementById('supplier_id');
    if (!sel) return;
    const opt = sel.options[sel.selectedIndex];
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = v || ''; };
    set('supplier_bank_name', opt ? opt.dataset.bankName : '');
    set('supplier_bank_account', opt ? opt.dataset.bankAccount : '');
    set('supplier_bank_account_name', opt ? opt.dataset.bankAccountName : '');
}

/* ---------- validasi sebelum kirim ---------- */
function validateBeforeSubmit() {
    for (const r of rows()) {
        const max = r.dataset.max === '' ? null : parseFloat(r.dataset.max);
        if (max === null) continue;
        if (r.dataset.kind === 'po') {
            const q = parseIndoNumber(r.querySelector('.qty-input').value);
            if (q > max + 0.0001) { alert(`${PRF_TXT.qty_over} (${PRF_TXT.max} ${fmtQty(max)}): ${r.querySelector('.desc-input').value}`); return false; }
        } else if (r.dataset.kind === 'ap') {
            const a = parseIndoNumber(r.querySelector('.price-input').value);
            if (a > max + 0.01) { alert(`${PRF_TXT.amt_over} (Rp ${formatNumber(max)}): ${r.querySelector('.desc-input').value}`); return false; }
        }
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function () {
    const deptEl = document.getElementById('department_id');
    if (deptEl && deptEl.tagName !== 'SELECT' && !deptEl.value) deptEl.value = <?= json_encode($department_id ?? ''); ?>;

    // Supplier PRF hutang lama (edit) = supplier yang tersimpan.
    if (isHutangMode()) {
        const sel = document.getElementById('supplier_id');
        hutangSupplierId = sel && sel.value ? parseInt(sel.value, 10) : null;
    }
    if (rows().length === 0) document.getElementById('detail-body').appendChild(createDetailRow('manual'));

    document.getElementById('detail-body').addEventListener('input', function (e) {
        if (e.target.matches('.qty-input, .price-input')) { recomputePpn(); calculateSubtotal(); }
    });

    const form = document.getElementById('prf-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!validateBeforeSubmit()) { e.preventDefault(); return; }
            document.querySelectorAll('.qty-input, .price-input').forEach(i => { i.value = String(parseIndoNumber(i.value)); });
            // select yang di-disable tidak ikut terkirim; server tetap memakai supplier PO/AP.
            const sel = document.getElementById('supplier_id');
            if (sel) sel.disabled = false;
        });
    }

    const $ = window.jQuery;
    if ($) {
        const $sup = $('#supplier_id');
        if ($sup.length && $.fn.select2) $sup.select2({ placeholder: 'Cari...', allowClear: true, width: '100%' });
        $sup.on('change', fillSupplierBank);
        const $po = $('#po_select');
        if ($po.length && $.fn.select2) $po.select2({ placeholder: 'Cari PO...', allowClear: true, width: '100%', dropdownParent: $('#pullPoModal') });
        $po.on('change', function () { loadPoItems(this.value); });
        $(document).on('change', '#po_item_select_all', function () {
            document.querySelectorAll('.po-item-check').forEach(cb => cb.checked = this.checked);
        });
    } else {
        const sup = document.getElementById('supplier_id');
        if (sup) sup.addEventListener('change', fillSupplierBank);
        const po = document.getElementById('po_select');
        if (po) po.addEventListener('change', function () { loadPoItems(this.value); });
    }
    fillSupplierBank();
    refreshAll();
});
</script>
