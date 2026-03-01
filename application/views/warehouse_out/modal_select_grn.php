<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>

<!-- Modal fragment: pilih GRN -->
<div class="modal fade" id="selectGrnModal" tabindex="-1" aria-hidden="true" data-header-id="<?= isset($header_id) ? (int)$header_id : 0 ?>">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Pilih GRN untuk diretur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3 row">
          <div class="col-md-6">
            <input type="text" id="grnSearch" class="form-control" placeholder="Cari GRN atau supplier...">
          </div>
          <div class="col-md-6 text-end">
            <button type="button" id="btnSelectAll" class="btn btn-sm btn-outline-secondary">Pilih Semua</button>
            <button type="button" id="btnClearAll" class="btn btn-sm btn-outline-secondary">Batal Pilih Semua</button>
          </div>
        </div>

        <div class="table-responsive" style="max-height:400px; overflow:auto;">
          <table class="table table-sm table-striped table-bordered" id="grnTable">
            <thead>
              <tr>
                <th style="width:36px"></th>
                <th>GRN No</th>
                <th>Tgl</th>
                <th>Supplier</th>
                <th>Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($grns as $g): ?>
                <tr>
                  <td class="text-center"><input type="checkbox" class="grn-checkbox" value="<?= (int)$g->id ?>"></td>
                  <td><?= htmlspecialchars($g->grn_number) ?></td>
                  <td><?= $g->grn_date ? date('d M Y', strtotime($g->grn_date)) : '-' ?></td>
                  <td><?= htmlspecialchars($g->supplier_name ?? '-') ?></td>
                  <td><?= htmlspecialchars($g->notes ?? '') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="btnLoadSelected" class="btn btn-primary">Load Items dari GRN Terpilih</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const modalEl = document.getElementById('selectGrnModal');
  const headerId = modalEl ? parseInt(modalEl.getAttribute('data-header-id') || '0') : 0;
  const btnLoad = modalEl.querySelector('#btnLoadSelected');
  const btnSelectAll = modalEl.querySelector('#btnSelectAll');
  const btnClearAll = modalEl.querySelector('#btnClearAll');
  const searchInput = modalEl.querySelector('#grnSearch');
  const table = modalEl.querySelector('#grnTable');

  function getCheckedIds() {
    return Array.from(table.querySelectorAll('.grn-checkbox:checked')).map(c=>parseInt(c.value));
  }

  btnSelectAll.addEventListener('click', function(){
    table.querySelectorAll('.grn-checkbox').forEach(c=>c.checked = true);
  });
  btnClearAll.addEventListener('click', function(){
    table.querySelectorAll('.grn-checkbox').forEach(c=>c.checked = false);
  });

  // simple client-side filter
  searchInput.addEventListener('input', function(){
    const term = this.value.toLowerCase();
    Array.from(table.querySelectorAll('tbody tr')).forEach(tr=>{
      const text = tr.textContent.toLowerCase();
      tr.style.display = text.indexOf(term) === -1 ? 'none' : '';
    });
  });

  btnLoad.addEventListener('click', function(){
    const ids = getCheckedIds();
    if (!ids.length) { alert('Pilih minimal satu GRN.'); return; }

    // 1) load GRN items
    const fd = new FormData();
    ids.forEach(id => fd.append('grn_ids[]', id));

    fetch('<?= base_url('Warehouse_out/load_grn_items') ?>', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r=>r.json()).then(j=>{
      if (!j.success) { alert('Gagal load items: ' + (j.message||'')); return; }
      const items = j.data || [];

      // 2) attach items to header directly
      const payload = { header_id: headerId, items: items };
      fetch('<?= base_url('Warehouse_out/pull_grn_items_to_header') ?>', {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(payload)
      }).then(r => r.text()).then(text => {
        // Try to parse JSON, but if server emitted warnings/html, show it for debugging
        let resp = null;
        try {
          resp = JSON.parse(text);
        } catch (e) {
          console.error('Invalid JSON response from server:', text);
          alert('Server response not valid JSON. See console for details.');
          return;
        }
        if (resp.success) {
          var bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
          bsModal.hide();
          alert('Berhasil menambahkan ' + (resp.added||0) + ' item');
          window.location.reload();
        } else {
          alert('Gagal: ' + (resp.message || (resp.errors ? resp.errors.join('\n') : 'unknown')));
        }
      }).catch(err=>{ console.error('Fetch error', err); alert('Gagal attach items ke header (network error)'); });

    }).catch(err=>{ console.error(err); alert('Gagal load items'); });
  });
})();
</script>
<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<html><head><title>Pilih GRN</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head><body>
<div class="container">
    <h4>Pilih GRN untuk diretur</h4>
    <form id="grnForm">
    <table class="table table-sm table-bordered">
        <thead><tr><th></th><th>GRN No</th><th>Tgl</th><th>Supplier</th><th>Keterangan</th></tr></thead>
        <tbody>
        <?php foreach($grns as $g): ?>
            <tr>
                <td><input type="checkbox" name="grn_ids[]" value="<?= $g->id ?>"></td>
                <td><?= htmlspecialchars($g->grn_number) ?></td>
                <td><?= date('d M Y', strtotime($g->grn_date)) ?></td>
                <td><?= htmlspecialchars($g->supplier_name ?? '-') ?></td>
                <td><?= htmlspecialchars($g->notes ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="form-group">
        <button type="button" id="btnLoadItems" class="btn btn-primary">Load Items</button>
        <button type="button" onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>
    </form>
</div>
<script>
document.getElementById('btnLoadItems').addEventListener('click', function(){
    const form = document.getElementById('grnForm');
    const fd = new FormData(form);
    fetch('<?= base_url('Warehouse_out/load_grn_items') ?>', {method:'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }})
    .then(r=>r.json()).then(j=>{
        if (j.success) {
            // Send items to opener window
            window.opener.postMessage(JSON.stringify({type:'warehouse_out_grn_items', items: j.data}), '*');
            window.close();
        } else {
            alert('Gagal load items');
        }
    });
});
</script>
</body></html>
