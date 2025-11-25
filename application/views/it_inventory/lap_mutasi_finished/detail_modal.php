<div class="modal fade" id="modalDetail" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-box-open"></i> Detail Mutasi - <?= htmlspecialchars($item['nama_barang'] ?? '') ?></h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <?php if(empty($trace)): ?>
          <div class="alert alert-info">Tidak ada data transaksi.</div>
        <?php else: ?>
        <table class="table table-bordered table-sm">
          <thead class="table-secondary text-center">
            <tr><th>No Produksi</th><th>Tanggal</th><th>Item Keluar</th><th>Qty</th></tr>
          </thead><tbody>
          <?php foreach($trace as $t): ?>
            <tr>
              <td><?= $t['production_number'] ?></td>
              <td><?= $t['production_date'] ?></td>
              <td><?= $t['material_name'] ?? $t['semifinished_name'] ?></td>
              <td class="text-right"><?= number_format($t['qty_used'],2) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button class="btn btn-success btn-sm btnRecursive" data-id="<?= $item['id'] ?? $item['fg_id'] ?>"><i class="fas fa-project-diagram"></i> Recursive Trace</button>
        <button class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
$(document).on('click','.btnRecursive',function(){
  const id=$(this).data('id');
  $('#modalDetail').modal('hide');
  setTimeout(()=>{
    $.get('<?= site_url("LapMutasiFinished/trace_recursive/") ?>'+id, function(html){
      $('#modalGlobal').html(html);
      $('#modalRecursive').modal('show');
    });
  },300);
});
</script>
