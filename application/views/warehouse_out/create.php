<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><?= $title ?></h1>

    <form method="post" action="<?= base_url('Warehouse_out/save') ?>">
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Base On</label>
                        <select name="base_on" class="form-select">
                            <option value="MIR">MIR</option>
                            <option value="PO">PO</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tanggal Pengeluaran</label>
                        <input type="date" name="out_date" class="form-control" value="<?= set_value('out_date', date('Y-m-d')) ?>" />
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Jenis BC</label>
                        <select name="bc_type_code" class="form-select">
                            <option value="">-- Pilih BC Type --</option>
                            <?php if (!empty($bc_types)): foreach($bc_types as $b):
                                $code = is_array($b) ? ($b['code'] ?? '') : ($b->code ?? '');
                                $desc = is_array($b) ? ($b['description'] ?? '') : ($b->description ?? '');
                            ?>
                                <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($desc) ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Nomor BC</label>
                        <input type="text" name="bc_number" class="form-control" />
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Tanggal BC</label>
                        <input type="date" name="bc_date" class="form-control" value="<?= set_value('bc_date') ?>" />
                    </div>
                </div>

                <div class="form-group mt-3">
                    <label class="form-label">Referensi / Catatan</label>
                    <textarea name="reference_note" class="form-control" rows="2"><?= set_value('reference_note') ?></textarea>
                </div>

                <div class="alert alert-info">
                    Pilih GRN hanya dapat dilakukan setelah Anda menyimpan header (Simpan Draft).
                    Setelah disimpan, Anda akan diarahkan ke halaman detail di mana tombol "Tarik Data dari GRN" tersedia.
                </div>

                <input type="hidden" name="details" id="detailsInput" value="[]">

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Simpan Draft</button>
                    <a href="<?= base_url('Warehouse_out') ?>" class="btn btn-secondary">Batal</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('btnSelectGrn') && document.getElementById('btnSelectGrn').addEventListener('click', function(){
    fetch('<?= base_url('Warehouse_out/popup_select_grn') ?>', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r=>r.json()).then(j=>{
        if (j.success) {
            const w = window.open('', 'select_grn', 'width=800,height=600');
            w.document.write(j.html);
            w.document.close();
        } else {
            alert('Gagal membuka popup: ' + (j.message || ''));
        }
    }).catch(err=>{ console.error(err); alert('Gagal membuka popup GRN'); });
});

// Note: modal window will call opener.postMessage with selected items (see modal_select_grn view)
window.addEventListener('message', function(e){
    try {
        const data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data;
        if (data.type === 'warehouse_out_grn_items') {
            // normalize items: ensure backend-friendly fields
            const items = (data.items || []).map(it => ({
                grn_detail_id: it.grn_detail_id || it.id || null,
                grn_header_id: it.grn_header_id || it.grn_id || it.grnHeaderId || null,
                item_id: it.item_id || null,
                item_type: it.item_type || null,
                item_code: it.item_code || it.kode || '',
                item_name: it.item_name || '',
                unit: it.unit || it.satuan || '',
                qty_po: parseFloat(it.qty_po || 0) || 0,
                qty_received: parseFloat(it.qty_received || it.receive_qty || 0) || 0,
                already_returned: parseFloat(it.already_returned || 0) || 0,
                qty_return: 0
            }));

            // render table with allowed max and input constraints
            let html = '<table class="table table-sm table-bordered"><thead><tr><th>Kode</th><th>Nama</th><th>Satuan</th><th>QTY PO/MIR</th><th>QTY DITERIMA</th><th>Sudah Retur</th><th>Max Retur</th><th>QTY RETUR</th></tr></thead><tbody>';
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
            document.getElementById('selectedItems').innerHTML = html;

            // attach change listener to update hidden details JSON and enforce max
            document.querySelectorAll('.qty-return').forEach(inp=>{
                inp.addEventListener('input', function(){
                    const idx = parseInt(this.dataset.idx,10);
                    const val = parseFloat(this.value) || 0;
                    const maxAllowed = parseFloat(this.max) || 0;
                    if (val > maxAllowed) {
                        this.value = maxAllowed;
                        alert('Qty retur melebihi jumlah yang tersedia. Maks: '+maxAllowed);
                    }
                    items[idx].qty_return = parseFloat(this.value) || 0;
                    document.getElementById('detailsInput').value = JSON.stringify(items);
                });
            });

            // initialize hidden input
            document.getElementById('detailsInput').value = JSON.stringify(items);
        }
    } catch(e){ console.error('Invalid message', e); }
});

// helper functions
function htmlEscape(s){ if(!s) return ''; return String(s).replace(/[&<>\"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":"&#39;"}[c]; }); }
function formatNumber(n){ return (n || 0).toString(); }

// Prevent submitting invalid details, but allow saving Draft without any items selected
document.querySelector('form').addEventListener('submit', function(ev){
    const detailsRaw = document.getElementById('detailsInput').value;
    let items = [];
    try { items = JSON.parse(detailsRaw || '[]'); } catch(e){ items = []; }

    // If no items were selected, allow saving draft (user will pull GRN from detail page later)
    if (!items || items.length === 0) {
        // ensure we submit a normalized empty array
        document.getElementById('detailsInput').value = JSON.stringify([]);
        return true;
    }

    // If items exist, require at least one positive qty_return
    const anyPositive = items.some(it => (parseFloat(it.qty_return)||0) > 0);
    if (!anyPositive) {
        ev.preventDefault();
        alert('Pilih minimal 1 item dan isi QTY RETUR lebih dari 0 sebelum menyimpan.');
        return false;
    }

    // ensure each item has grn_detail_id and grn_header_id for backend
    items = items.map(it=>({
        grn_detail_id: it.grn_detail_id || it.id || null,
        grn_header_id: it.grn_header_id || it.grn_id || null,
        item_id: it.item_id || null,
        item_type: it.item_type || null,
        item_code: it.item_code || null,
        item_name: it.item_name || null,
        unit: it.unit || null,
        qty_po: parseFloat(it.qty_po||0),
        qty_received: parseFloat(it.qty_received||0),
        already_returned: parseFloat(it.already_returned||0),
        qty_return: parseFloat(it.qty_return||0)
    }));
    document.getElementById('detailsInput').value = JSON.stringify(items);
});
</script>
