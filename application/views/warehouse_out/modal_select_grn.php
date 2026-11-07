<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>

<!-- Modal fragment: pilih GRN -->
<div class="modal fade" id="selectGrnModal" tabindex="-1" aria-hidden="true" data-header-id="<?= isset($header_id) ? (int)$header_id : 0 ?>">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><?= lang_line('wo_grn_modal_title'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3 row">
          <div class="col-md-6">
            <input type="text" id="grnSearch" class="form-control" placeholder="<?= lang_line('wo_grn_modal_search_ph'); ?>">
          </div>
          <div class="col-md-6 text-end">
            <button type="button" id="btnSelectAll" class="btn btn-sm btn-outline-secondary"><?= lang_line('wo_grn_modal_btn_select_all'); ?></button>
            <button type="button" id="btnClearAll" class="btn btn-sm btn-outline-secondary"><?= lang_line('wo_grn_modal_btn_clear'); ?></button>
          </div>
        </div>

        <div class="table-responsive" style="max-height:400px; overflow:auto;">
          <table class="table table-sm table-striped table-bordered" id="grnTable">
            <thead>
              <tr>
                <th style="width:36px"></th>
                <th><?= lang_line('wo_grn_modal_th_grn_no'); ?></th>
                <th><?= lang_line('wo_grn_modal_th_date'); ?></th>
                <th><?= lang_line('wo_grn_modal_th_supplier'); ?></th>
                <th><?= lang_line('wo_grn_modal_th_notes'); ?></th>
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
        <button type="button" id="btnLoadSelected" class="btn btn-primary"><?= lang_line('wo_grn_modal_btn_load'); ?></button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang_line('wo_grn_modal_btn_close'); ?></button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const I18N_WO_GRN = {
    min_one:        <?= json_encode(lang_line('wo_grn_js_min_one'), JSON_UNESCAPED_UNICODE); ?>,
    load_failed:    <?= json_encode(lang_line('wo_grn_js_load_failed'), JSON_UNESCAPED_UNICODE); ?>,
    invalid_json:   <?= json_encode(lang_line('wo_grn_js_invalid_json'), JSON_UNESCAPED_UNICODE); ?>,
    added_n:        <?= json_encode(lang_line('wo_grn_js_added_n'), JSON_UNESCAPED_UNICODE); ?>,
    failed_prefix:  <?= json_encode(lang_line('wo_grn_js_failed_prefix'), JSON_UNESCAPED_UNICODE); ?>,
    unknown:        <?= json_encode(lang_line('wo_grn_js_unknown'), JSON_UNESCAPED_UNICODE); ?>,
    attach_fail:    <?= json_encode(lang_line('wo_grn_js_attach_fail'), JSON_UNESCAPED_UNICODE); ?>,
    load_fail:      <?= json_encode(lang_line('wo_grn_js_load_fail'), JSON_UNESCAPED_UNICODE); ?>
  };
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
    if (!ids.length) { alert(I18N_WO_GRN.min_one); return; }

    // 1) load GRN items
    const fd = new FormData();
    ids.forEach(id => fd.append('grn_ids[]', id));

    fetch('<?= base_url('Warehouse_out/load_grn_items') ?>', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r=>r.json()).then(j=>{
      if (!j.success) { alert(I18N_WO_GRN.load_failed + (j.message||'')); return; }
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
          alert(I18N_WO_GRN.invalid_json);
          return;
        }
        if (resp.success) {
          var bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
          bsModal.hide();
          alert(I18N_WO_GRN.added_n.replace('%s', resp.added||0));
          window.location.reload();
        } else {
          alert(I18N_WO_GRN.failed_prefix + (resp.message || (resp.errors ? resp.errors.join('\n') : I18N_WO_GRN.unknown)));
        }
      }).catch(err=>{ console.error('Fetch error', err); alert(I18N_WO_GRN.attach_fail); });

    }).catch(err=>{ console.error(err); alert(I18N_WO_GRN.load_fail); });
  });
})();
</script>
