<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?> — <?= htmlspecialchars($doc_no); ?></h1>
        <a href="<?= base_url('qa_sampling'); ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> <?= lang_line('btn_back', 'Kembali'); ?>
        </a>
    </div>

    <?php if ($msg = $this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?= $msg; ?></div>
    <?php endif; ?>

    <form method="post" id="frmSampling">
        <div class="card shadow mb-3">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?= lang_line('qas_section_header', 'Header'); ?></h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label><?= lang_line('qas_label_date', 'Tanggal'); ?> <span class="text-danger">*</span></label>
                        <input type="date" name="doc_date" class="form-control form-control-sm" required
                               value="<?= htmlspecialchars($header->doc_date ?? date('Y-m-d')); ?>">
                    </div>
                    <div class="col-md-5">
                        <label><?= lang_line('qas_label_purpose', 'Tujuan Sampling'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="purpose" class="form-control form-control-sm" required
                               placeholder="<?= lang_line('qas_label_purpose_ph', 'mis. Tensile test laboratorium'); ?>"
                               value="<?= htmlspecialchars($header->purpose ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label><?= lang_line('qas_label_dept', 'Departemen Requester'); ?></label>
                        <input type="text" name="requester_dept" class="form-control form-control-sm"
                               placeholder="<?= lang_line('qas_label_dept_ph', 'QA / QC'); ?>"
                               value="<?= htmlspecialchars($header->requester_dept ?? 'QA'); ?>">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <label><?= lang_line('qas_label_notes', 'Catatan'); ?></label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($header->notes ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-3">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('qas_section_items', 'Item Sample'); ?></h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label><?= lang_line('qas_label_sample_source', 'Sumber Sample'); ?></label>
                        <select id="src" class="form-control">
                            <option value="warehouse"><?= lang_line('qas_source_warehouse', 'Gudang (Material/Semifinished/FG/Inventory)'); ?></option>
                            <option value="wip"><?= lang_line('qas_source_wip', 'WIP Produksi (Material/Semifinished)'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-9">
                        <label><?= lang_line('qas_label_pick_item', 'Pilih Item Sample'); ?></label>
                        <select id="itemSelect" class="form-control" style="width:100%;">
                            <option value=""><?= lang_line('qas_pick_item_ph', '-- Ketik kode/nama item (min 2 huruf) --'); ?></option>
                        </select>
                        <small class="text-muted"><?= lang_line('qas_pick_item_hint', 'Stok tersedia ditampilkan otomatis. Klik untuk tambah ke tabel di bawah.'); ?></small>
                    </div>
                </div>

                <table class="table table-bordered table-sm" id="tblItems">
                    <thead class="thead-light">
                        <tr>
                            <th width="80"><?= lang_line('qas_th_source', 'Sumber'); ?></th>
                            <th width="120"><?= lang_line('qas_th_type', 'Tipe'); ?></th>
                            <th width="120"><?= lang_line('qas_th_code', 'Kode'); ?></th>
                            <th><?= lang_line('qas_th_name', 'Nama'); ?></th>
                            <th width="120"><?= lang_line('qas_th_available', 'Tersedia'); ?></th>
                            <th width="120"><?= lang_line('qas_th_qty_sample', 'Qty Sample'); ?></th>
                            <th width="80"><?= lang_line('qas_th_uom', 'UoM'); ?></th>
                            <th><?= lang_line('qas_th_notes', 'Catatan'); ?></th>
                            <th width="60"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <input type="hidden" name="items_json" id="items_json">
        <button class="btn btn-primary" type="button" id="btnSubmit"><i class="fas fa-save"></i> <?= lang_line('qas_btn_save_submit', 'Simpan & Submit'); ?></button>
        <a href="<?= base_url('qa_sampling'); ?>" class="btn btn-secondary"><?= lang_line('btn_cancel', 'Batal'); ?></a>
    </form>
</div>

<script>
const I18N_QAS = {
    select2_ph: <?= json_encode(lang_line('qas_select2_ph', 'Ketik kode/nama item (min 2 huruf)...')); ?>,
    duplicate: <?= json_encode(lang_line('qas_js_duplicate_item', 'Item sudah ada di tabel.')); ?>,
    min_one: <?= json_encode(lang_line('qas_js_min_one_item', 'Minimal 1 item harus dimasukkan.')); ?>
};

$(function() {
    const existing = <?= json_encode(array_map(function($d){
        return [
            'source' => $d->source,
            'item_id' => $d->item_id,
            'item_type' => $d->item_type,
            'item_code' => $d->item_code,
            'item_name' => $d->item_name,
            'qty_sample' => (float)$d->qty_sample,
            'uom' => $d->uom,
            'notes' => $d->notes,
            'balance' => null,
            'production_header_id' => $d->production_header_id
        ];
    }, $details ?? [])); ?>;

    function addRow(it) {
        const tr = $(`
            <tr>
                <td><span class="badge badge-${it.source==='wip'?'warning':'info'}">${it.source.toUpperCase()}</span>
                    <input type="hidden" class="f-source" value="${it.source}">
                    <input type="hidden" class="f-item_id" value="${it.item_id}">
                    <input type="hidden" class="f-item_type" value="${it.item_type}">
                </td>
                <td>${it.item_type}</td>
                <td>${it.item_code||''}</td>
                <td>${it.item_name||''}</td>
                <td class="text-right">${it.balance!==null && it.balance!==undefined ? Number(it.balance).toFixed(4) : '-'}</td>
                <td><input type="number" step="0.0001" min="0.0001" class="form-control form-control-sm f-qty" value="${it.qty_sample||''}" required></td>
                <td><input type="text" class="form-control form-control-sm f-uom" value="${it.uom||''}"></td>
                <td><input type="text" class="form-control form-control-sm f-notes" value="${it.notes||''}"></td>
                <td><button type="button" class="btn btn-danger btn-sm btn-del"><i class="fas fa-times"></i></button></td>
            </tr>
        `);
        $('#tblItems tbody').append(tr);
    }

    existing.forEach(addRow);

    $('#tblItems').on('click', '.btn-del', function() {
        $(this).closest('tr').remove();
    });

    function initItemSelect2() {
        $('#itemSelect').select2({
            placeholder: I18N_QAS.select2_ph,
            minimumInputLength: 2,
            allowClear: true,
            ajax: {
                url: '<?= base_url('qa_sampling/search_items'); ?>',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term, source: $('#src').val() };
                },
                processResults: function(items) {
                    return {
                        results: (items || []).map(function(it) {
                            return {
                                id: it.item_type + '|' + it.item_id,
                                text: it.code + ' — ' + it.name + ' [' + it.item_type + '] · stok: ' + Number(it.balance).toFixed(4) + ' ' + (it.uom || ''),
                                raw: it
                            };
                        })
                    };
                },
                cache: true
            }
        });
    }
    initItemSelect2();

    $('#src').on('change', function() {
        $('#itemSelect').val(null).trigger('change');
    });

    $('#itemSelect').on('select2:select', function(e) {
        const it = e.params.data.raw;
        const src = $('#src').val();
        // Cegah duplikasi
        let dup = false;
        $('#tblItems tbody tr').each(function() {
            const tr = $(this);
            if (tr.find('.f-source').val() === src &&
                tr.find('.f-item_id').val() == it.item_id &&
                tr.find('.f-item_type').val() === it.item_type) dup = true;
        });
        if (dup) { alert(I18N_QAS.duplicate); }
        else {
            addRow({
                source: src, item_id: it.item_id, item_type: it.item_type,
                item_code: it.code, item_name: it.name, balance: it.balance, uom: it.uom
            });
        }
        $('#itemSelect').val(null).trigger('change');
    });

    $('#btnSubmit').click(function() {
        const items = [];
        $('#tblItems tbody tr').each(function() {
            const tr = $(this);
            items.push({
                source: tr.find('.f-source').val(),
                item_id: tr.find('.f-item_id').val(),
                item_type: tr.find('.f-item_type').val(),
                qty_sample: tr.find('.f-qty').val(),
                uom: tr.find('.f-uom').val(),
                notes: tr.find('.f-notes').val()
            });
        });
        if (!items.length) { alert(I18N_QAS.min_one); return; }
        $('#items_json').val(JSON.stringify(items));
        $('#frmSampling').submit();
    });

});
</script>
