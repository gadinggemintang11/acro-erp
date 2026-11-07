<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>

<div class="container-fluid py-4">

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1 font-weight-bold text-gray-800">
                <i class="fas fa-undo-alt text-primary mr-2"></i><?= lang_line('warehouse_out_create_title', 'Create Supplier Return'); ?>
            </h4>
            <p class="text-muted mb-0 small"><?= lang_line('warehouse_out_create_subtitle', 'Create goods issue document to return to supplier'); ?></p>
        </div>
        <a href="<?= base_url('Warehouse_out') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i><?= lang_line('warehouse_out_back_button', 'Back to List'); ?>
        </a>
    </div>

    <form method="post" action="<?= base_url('Warehouse_out/save') ?>">
        <input type="hidden" name="details" id="detailsInput" value="[]">

        <div class="row">
            <!-- Main Form -->
            <div class="col-lg-8">

                <!-- Section: Informasi Utama -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 font-weight-bold text-gray-700">
                            <i class="fas fa-info-circle text-primary mr-2"></i><?= lang_line('warehouse_out_section_main_info', 'Main Information'); ?>
                        </h6>
                    </div>
                    <div class="card-body pt-4 pb-3">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-semibold text-gray-700 small mb-1">
                                    <?= lang_line('warehouse_out_base_on_label', 'Return Basis'); ?> <span class="text-danger">*</span>
                                </label>
                                <select name="base_on" id="baseOnSelect" class="form-control" onchange="updateBaseOnInfo(this.value)" required>
                                    <option value="MIR"><?= lang_line('warehouse_out_return_option_mir', 'MIR — Material Import Request'); ?></option>
                                    <option value="PO"><?= lang_line('warehouse_out_return_option_po', 'PO — Purchase Order'); ?></option>
                                    <option value="NG"><?= lang_line('warehouse_out_return_option_ng', 'NG Return — QA Reject (from Incoming QC)'); ?></option>
                                    <option value="Production Return"><?= lang_line('warehouse_out_return_option_prod', 'Production Return — QC Reject Production'); ?></option>
                                </select>
                                <div id="baseOnDesc" class="mt-1">
                                    <small class="text-muted"><?= lang_line('warehouse_out_base_desc_mir', 'Return based on MIR already received in warehouse'); ?></small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-semibold text-gray-700 small mb-1">
                                    <?= lang_line('warehouse_out_label_outgoing_date', 'Outgoing Date'); ?> <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="out_date" class="form-control"
                                       value="<?= set_value('out_date', date('Y-m-d')) ?>" required />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-1">
                                <label class="form-label font-weight-semibold text-gray-700 small mb-1">
                                    <?= lang_line('warehouse_out_label_reference_note', 'Reference / Notes'); ?>
                                </label>
                                <textarea name="reference_note" class="form-control" rows="2"
                                          placeholder="<?= lang_line('warehouse_out_reference_placeholder', 'Additional notes (optional)...'); ?>"><?= set_value('reference_note') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Info BC (Opsional) -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 font-weight-bold text-gray-700">
                            <i class="fas fa-file-invoice text-warning mr-2"></i><?= lang_line('warehouse_out_section_bc_info', 'BC Information'); ?>
                            <span class="badge badge-light text-muted font-weight-normal ml-1" style="font-size:0.72rem;"><?= lang_line('warehouse_out_section_bc_optional', 'Optional'); ?></span>
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="bcToggleBtn"
                                onclick="toggleBcSection()">
                            <i class="fas fa-chevron-down" id="bcChevron"></i>
                        </button>
                    </div>
                    <div id="bcSection" class="card-body pt-3 pb-3">
                        <div class="row">
                            <div class="col-md-5 mb-2">
                                <label class="form-label font-weight-semibold text-gray-700 small mb-1"><?= lang_line('warehouse_out_label_bc_type', 'BC Type'); ?></label>
                                <select name="bc_type_code" class="form-control">
                                    <option value=""><?= lang_line('warehouse_out_label_bc_choice', '-- Select BC Type --'); ?></option>
                                    <?php if (!empty($bc_types)): foreach($bc_types as $b):
                                        $code = is_array($b) ? ($b['code'] ?? '') : ($b->code ?? '');
                                        $desc = is_array($b) ? ($b['description'] ?? '') : ($b->description ?? '');
                                    ?>
                                        <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($desc) ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label font-weight-semibold text-gray-700 small mb-1"><?= lang_line('warehouse_out_label_bc_number', 'BC Number'); ?></label>
                                <input type="text" name="bc_number" class="form-control" placeholder="<?= lang_line('warehouse_out_bc_number_placeholder', 'BC document number'); ?>" />
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label font-weight-semibold text-gray-700 small mb-1"><?= lang_line('warehouse_out_label_bc_date', 'BC Date'); ?></label>
                                <input type="date" name="bc_date" class="form-control" value="<?= set_value('bc_date') ?>" />
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Sidebar: Info & Actions -->
            <div class="col-lg-4">

                <!-- Info Box -->
                <div class="card border-0 shadow-sm mb-4" id="infoCard">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mr-3 flex-shrink-0"
                                 style="width:38px; height:38px;">
                                <i class="fas fa-lightbulb text-white" style="font-size:0.9rem;"></i>
                            </div>
                            <div>
                                <p class="font-weight-bold mb-1 text-gray-800" style="font-size:0.88rem;" id="infoTitle">
                                    <?= lang_line('warehouse_out_info_title_mir', 'Return via MIR'); ?>
                                </p>
                                <p class="text-muted mb-0" style="font-size:0.82rem;" id="infoBody">
                                    <?= lang_line('warehouse_out_info_desc_mir', 'After saving, use the "Pull Data from GRN" button on the detail page to select items to return.'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-block mb-2">
                            <i class="fas fa-save mr-2"></i><?= lang_line('warehouse_out_button_save_draft', 'Save as Draft'); ?>
                        </button>
                        <a href="<?= base_url('Warehouse_out') ?>" class="btn btn-outline-secondary btn-block">
                            <i class="fas fa-times mr-2"></i><?= lang_line('warehouse_out_button_cancel', 'Cancel'); ?>
                        </a>
                        <hr class="my-3">
                        <p class="text-muted small mb-0">
                            <i class="fas fa-info-circle mr-1"></i>
                            <?= lang_line('warehouse_out_save_draft_info', 'Document will be saved as Draft. Items can be added after saving.'); ?>
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
var baseOnInfo = {
    'MIR': {
        title: '<?= lang_line('warehouse_out_info_title_mir', 'Return via MIR'); ?>',
        desc: '<?= lang_line('warehouse_out_info_desc_mir', 'After saving, use the "Pull Data from GRN" button on the detail page to select items to return.'); ?>',
        fieldDesc: '<?= lang_line('warehouse_out_base_desc_mir', 'Return based on MIR already received in warehouse'); ?>'
    },
    'PO': {
        title: '<?= lang_line('warehouse_out_info_title_po', 'Return via PO'); ?>',
        desc: '<?= lang_line('warehouse_out_info_desc_po', 'After saving, use the "Pull Data from GRN" button on the detail page to select PO items to return.'); ?>',
        fieldDesc: '<?= lang_line('warehouse_out_base_desc_po', 'Return based on Purchase Order already received in warehouse'); ?>'
    },
    'NG': {
        title: '<?= lang_line('warehouse_out_info_title_ng', 'NG Return — QA Reject'); ?>',
        desc: '<?= lang_line('warehouse_out_info_desc_ng', 'After saving, use the "Pull NG Items from QA" button on the detail page to select items rejected during incoming QC.'); ?>',
        fieldDesc: '<?= lang_line('warehouse_out_base_desc_ng', 'Return NG items from incoming goods Quality Assurance'); ?>'
    },
    'Production Return': {
        title: '<?= lang_line('warehouse_out_info_title_prod', 'Production Return — QC Reject'); ?>',
        desc: '<?= lang_line('warehouse_out_info_desc_prod', 'After saving, use the "Pull Production Reject Items" button on the detail page to select items rejected during production QC.'); ?>',
        fieldDesc: '<?= lang_line('warehouse_out_base_desc_prod', 'Return items rejected during production Quality Check'); ?>'
    }
};

function updateBaseOnInfo(val) {
    var info = baseOnInfo[val] || baseOnInfo['MIR'];
    document.getElementById('infoTitle').textContent  = info.title;
    document.getElementById('infoBody').innerHTML     = info.body || info.desc;
    document.getElementById('baseOnDesc').innerHTML   = '<small class="text-muted">' + info.fieldDesc + '</small>';
}

function toggleBcSection() {
    var sec = document.getElementById('bcSection');
    var chevron = document.getElementById('bcChevron');
    if (sec.style.display === 'none') {
        sec.style.display = '';
        chevron.classList.replace('fa-chevron-right', 'fa-chevron-down');
    } else {
        sec.style.display = 'none';
        chevron.classList.replace('fa-chevron-down', 'fa-chevron-right');
    }
}

// Init on load
document.addEventListener('DOMContentLoaded', function(){
    updateBaseOnInfo(document.getElementById('baseOnSelect').value);
});

// ── Existing logic (do not modify) ──────────────────────────
document.getElementById('btnSelectGrn') && document.getElementById('btnSelectGrn').addEventListener('click', function(){
    fetch('<?= base_url('Warehouse_out/popup_select_grn') ?>', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r=>r.json()).then(j=>{
        if (j.success) {
            const w = window.open('', 'select_grn', 'width=800,height=600');
            w.document.write(j.html);
            w.document.close();
        } else {
            alert('<?= lang_line('warehouse_out_error_failed_popup', 'Failed to open popup:'); ?>' + (j.message || ''));
        }
    }).catch(err=>{ console.error(err); alert('<?= lang_line('warehouse_out_error_popup', 'Failed to open GRN popup'); ?>'); });
});

window.addEventListener('message', function(e){
    try {
        const data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data;
        if (data.type === 'warehouse_out_grn_items') {
            const items = (data.items || []).map(it => ({
                grn_detail_id:  it.grn_detail_id  || it.id    || null,
                grn_header_id:  it.grn_header_id  || it.grn_id || it.grnHeaderId || null,
                item_id:        it.item_id         || null,
                item_type:      it.item_type       || null,
                item_code:      it.item_code       || it.kode  || '',
                item_name:      it.item_name       || '',
                unit:           it.unit            || it.satuan || '',
                qty_po:         parseFloat(it.qty_po         || 0) || 0,
                qty_received:   parseFloat(it.qty_received   || it.receive_qty || 0) || 0,
                already_returned: parseFloat(it.already_returned || 0) || 0,
                qty_return: 0
            }));

            let html = '<table class="table table-sm table-bordered"><thead><tr><th><?= lang_line('warehouse_out_th_code'); ?></th><th><?= lang_line('warehouse_out_th_name'); ?></th><th><?= lang_line('warehouse_out_th_unit'); ?></th><th><?= lang_line('warehouse_out_th_qty_po'); ?></th><th><?= lang_line('warehouse_out_th_qty_received'); ?></th><th><?= lang_line('warehouse_out_th_already_returned'); ?></th><th><?= lang_line('warehouse_out_th_max_return'); ?></th><th><?= lang_line('warehouse_out_th_qty_return'); ?></th></tr></thead><tbody>';
            items.forEach((it, idx)=>{
                const maxRet = Math.max(0, (it.qty_received || 0) - (it.already_returned || 0));
                html += '<tr>'+
                    '<td>'+htmlEscape(it.item_code)+'</td>'+
                    '<td>'+htmlEscape(it.item_name)+'</td>'+
                    '<td>'+htmlEscape(it.unit)+'</td>'+
                    '<td class="text-end">'+formatNumber(it.qty_po)+'</td>'+
                    '<td class="text-end">'+formatNumber(it.qty_received)+'</td>'+
                    '<td class="text-end">'+formatNumber(it.already_returned)+'</td>'+
                    '<td class="text-end">'+formatNumber(maxRet)+'</td>'+
                    '<td><input type="number" step="0.01" min="0" max="'+maxRet+'" value="0" data-idx="'+idx+'" class="form-control qty-return"/></td>'+
                '</tr>';
            });
            html += '</tbody></table>';
            if (document.getElementById('selectedItems')) {
                document.getElementById('selectedItems').innerHTML = html;
            }

            document.querySelectorAll('.qty-return').forEach(inp=>{
                inp.addEventListener('input', function(){
                    const idx = parseInt(this.dataset.idx, 10);
                    const val = parseFloat(this.value) || 0;
                    const maxAllowed = parseFloat(this.max) || 0;
                    if (val > maxAllowed) {
                        this.value = maxAllowed;
                        alert('<?= lang_line('warehouse_out_qty_exceeds_available', 'Return qty exceeds available quantity. Max:'); ?> ' + maxAllowed);
                    }
                    items[idx].qty_return = parseFloat(this.value) || 0;
                    document.getElementById('detailsInput').value = JSON.stringify(items);
                });
            });
            document.getElementById('detailsInput').value = JSON.stringify(items);
        }
    } catch(e){ console.error('Invalid message', e); }
});

function htmlEscape(s){ if(!s) return ''; return String(s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
function formatNumber(n){ return (n || 0).toString(); }

document.querySelector('form').addEventListener('submit', function(ev){
    const detailsRaw = document.getElementById('detailsInput').value;
    let items = [];
    try { items = JSON.parse(detailsRaw || '[]'); } catch(e){ items = []; }
    if (!items || items.length === 0) {
        document.getElementById('detailsInput').value = JSON.stringify([]);
        return true;
    }
    const anyPositive = items.some(it => (parseFloat(it.qty_return)||0) > 0);
    if (!anyPositive) {
        ev.preventDefault();
        alert('<?= lang_line('warehouse_out_min_qty_validation', 'Select at least 1 item and fill QTY RETURN greater than 0 before saving.'); ?>');
        return false;
    }
    items = items.map(it=>({
        grn_detail_id:  it.grn_detail_id  || it.id    || null,
        grn_header_id:  it.grn_header_id  || it.grn_id || null,
        item_id:        it.item_id         || null,
        item_type:      it.item_type       || null,
        item_code:      it.item_code       || null,
        item_name:      it.item_name       || null,
        unit:           it.unit            || null,
        qty_po:         parseFloat(it.qty_po         || 0),
        qty_received:   parseFloat(it.qty_received   || 0),
        already_returned: parseFloat(it.already_returned || 0),
        qty_return:     parseFloat(it.qty_return     || 0)
    }));
    document.getElementById('detailsInput').value = JSON.stringify(items);
});
</script>
