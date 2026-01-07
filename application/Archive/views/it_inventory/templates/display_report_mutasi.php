<div class="container-fluid">

    <!-- HEADER -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="fw-semibold text-dark mb-0">
            <i class="fas fa-recycle text-success me-2"></i><?= $title; ?>
        </h3>
        <div>
            <a href="<?= base_url('LapMutasiScrap/export_excel?' . http_build_query($filters)); ?>" 
               class="btn btn-success btn-sm shadow-sm">
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </a>
            <a href="<?= base_url('LapMutasiScrap/export_pdf?' . http_build_query($filters)); ?>" target="_blank"
               class="btn btn-danger btn-sm shadow-sm">
                <i class="fas fa-file-pdf me-1"></i> Export PDF
            </a>
        </div>
    </div>

    <!-- FILTER -->
    <form method="get" action="<?= base_url('LapMutasiScrap'); ?>" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label text-muted small">Periode Awal</label>
                <input type="date" name="start_date" value="<?= $filters['start_date']; ?>" class="form-control form-control-sm shadow-none">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small">Periode Akhir</label>
                <input type="date" name="end_date" value="<?= $filters['end_date']; ?>" class="form-control form-control-sm shadow-none">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small">Cari Scrap</label>
                <input type="text" name="search" placeholder="Kode / Nama Scrap" 
                       value="<?= htmlspecialchars($filters['search']); ?>" class="form-control form-control-sm shadow-none">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-sm btn-primary w-100 shadow-sm">
                    <i class="fas fa-search me-1"></i> Terapkan
                </button>
            </div>
        </div>
    </form>

    <!-- TABLE -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center border-bottom py-3">
            <h6 class="fw-bold text-dark mb-0">
                <i class="fas fa-database me-1 text-primary"></i> Ringkasan Stok Scrap
            </h6>
            <small class="text-secondary">Total Data: <?= $total_rows; ?></small>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle text-nowrap" id="tableMutasi">
                    <thead class="text-center align-middle">
                        <tr>
                            <th>No</th>
                            <th>Kode Scrap</th>
                            <th>Nama Scrap</th>
                            <th>Satuan</th>
                            <th>Saldo Awal</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th>Adjustment</th>
                            <th>Saldo Akhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($report_data)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-3">
                                    <i class="fas fa-info-circle me-1"></i> Tidak ada data untuk periode ini.
                                </td>
                            </tr>
                        <?php else: $no = $start_no; foreach($report_data as $r): ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><?= htmlspecialchars($r['item_code']); ?></td>
                                <td><?= htmlspecialchars($r['item_name']); ?></td>
                                <td class="text-center"><?= $r['item_unit']; ?></td>
                                <td class="text-end"><?= number_format($r['opening_stock'],2); ?></td>
                                <td class="text-end text-success fw-bold"><?= number_format($r['qty_in'],2); ?></td>
                                <td class="text-end text-danger fw-bold"><?= number_format($r['qty_out'],2); ?></td>
                                <td class="text-end text-info"><?= number_format($r['qty_adj'],2); ?></td>
                                <td class="text-end fw-bold text-primary"><?= number_format($r['closing_stock'],2); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-outline-primary btn-sm btn-trace shadow-sm"
                                            data-id="<?= $r['item_id']; ?>"
                                            title="Lihat Riwayat Scrap">
                                        <i class="fas fa-history"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3"><?= $pagination; ?></div>
        </div>
    </div>
</div>

<!-- ========== MODALS ========== -->

<!-- TRACE MODAL -->
<div class="modal fade" id="traceModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white py-2">
        <h6 class="modal-title"><i class="fas fa-stream me-1"></i> Traceability Scrap</h6>
        <button type="button" class="btn-close btn-close-white" data-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="traceContent" style="min-height:300px;">
        <div class="text-center py-5 text-muted">
          <div class="spinner-border text-primary mb-2" role="status"></div>
          <p class="mb-0">Memuat data traceability...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- DEEP TRACE PRODUKSI -->
<div class="modal fade" id="deepTraceModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white py-2">
        <h6 class="modal-title"><i class="fas fa-industry me-1"></i> Deep Trace Produksi</h6>
        <button type="button" class="btn-close btn-close-white" data-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="deepTraceContent">
        <div class="text-center py-5 text-muted">
          <div class="spinner-border text-success mb-2" role="status"></div>
          <p class="mb-0">Memuat detail produksi...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- DETAIL PENGELUARAN SCRAP -->
<div class="modal fade" id="outTraceModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-info text-white py-2">
        <h6 class="modal-title"><i class="fas fa-truck me-1"></i> Detail Pengeluaran Scrap</h6>
        <button type="button" class="btn-close btn-close-white" data-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="outTraceContent">
        <div class="text-center py-5 text-muted">
          <div class="spinner-border text-info mb-2" role="status"></div>
          <p class="mb-0">Memuat Data Keluar Scrap...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- SCRIPT: tetap sama, tidak diubah -->
<script>
$(function(){

    // 🔍 Tombol Lihat Traceability Scrap
    $('.btn-trace').click(function(){
        var item_id = $(this).data('id');
        $('#traceModal').modal('show');
        $('#traceContent').html('<div class="text-center py-5 text-muted"><div class="spinner-border text-primary mb-2" role="status"></div><p class="mb-0">Memuat data traceability...</p></div>');
        $.get('<?= base_url('LapMutasiScrap/traceability'); ?>', {item_id: item_id}, function(res){
            if(res.success){
                $('#traceContent').html(res.html);
            } else {
                $('#traceContent').html('<div class="alert alert-warning">'+res.message+'</div>');
            }
        }, 'json');
    });

    // 🏭 Deep Trace Produksi
    $(document).on('click','.btn-deep-trace',function(){
        var prod_id = $(this).data('prodid');
        $('#deepTraceModal').modal('show');
        $('#deepTraceContent').html('<div class="text-center py-5 text-muted"><div class="spinner-border text-success mb-2" role="status"></div><p class="mb-0">Memuat detail produksi...</p></div>');
        $.get('<?= base_url('LapMutasiScrap/ajax_get_production_trace'); ?>', {prod_id: prod_id}, function(res){
            if(res.success){
                $('#deepTraceContent').html(res.html);
            } else {
                $('#deepTraceContent').html('<div class="alert alert-warning">'+res.message+'</div>');
            }
        }, 'json');
    });

    // 🏢 Deep Trace Warehouse (mutasi antar gudang)
    $(document).on('click','.btn-deep-trace-wh',function(){
        var ledger_id = $(this).data('ledgerid');
        $('#deepTraceModal').modal('show');
        $('#deepTraceContent').html('<div class="text-center py-5 text-muted"><div class="spinner-border text-success mb-2" role="status"></div><p class="mb-0">Memuat asal warehouse...</p></div>');
        $.get('<?= base_url('LapMutasiScrap/ajax_get_wh_trace'); ?>', {ledger_id: ledger_id}, function(res){
            if(res.success){
                $('#deepTraceContent').html(res.html);
            } else {
                $('#deepTraceContent').html('<div class="alert alert-warning">'+res.message+'</div>');
            }
        }, 'json');
    });

    // 🚚 Detail Pengeluaran Scrap
    $(document).on('click','.btn-show-out',function(){
        var docid = $(this).data('docid');
        $('#outTraceModal').modal('show');
        $('#outTraceContent').html('<div class="text-center py-5 text-muted"><div class="spinner-border text-info mb-2" role="status"></div><p class="mb-0">Memuat Data Keluar Scrap...</p></div>');
        $.get('<?= base_url('LapMutasiScrap/ajax_get_out_trace'); ?>', {doc_id: docid}, function(res){
            if(res.success){
                $('#outTraceContent').html(res.html);
            } else {
                $('#outTraceContent').html('<div class="alert alert-warning">'+res.message+'</div>');
            }
        }, 'json');
    });

    // 🧩 Fix aria-hidden warning
    $(document).on('shown.bs.modal hidden.bs.modal', '.modal', function () {
        $('#wrapper').attr('aria-hidden', 'false');
    });
});
</script>

<style>
/* === TABLE & CARD === */
.table thead {
    background-color: #5C5F72 !important;
    color: #fff !important;
}
.table th, .table td {
    font-size: 13px;
    vertical-align: middle;
}
.table-hover tbody tr:hover {
    background-color: #f7f9fc !important;
    transition: 0.25s ease;
}
.card { border-radius: 10px; }
.card-header {
    border-bottom: none;
}

/* === BUTTONS === */
.btn-outline-primary {
    color: #5C5F72;
    border-color: #5C5F72;
}
.btn-outline-primary:hover {
    background-color: #5C5F72;
    color: #fff;
}
.btn {
    font-size: 13px;
    border-radius: 6px;
}

/* === MODAL === */
.modal-content {
    border-radius: 10px;
}
.modal-header {
    border-bottom: none;
}
.modal-footer {
    border-top: none;
}
.spinner-border {
    width: 2.2rem;
    height: 2.2rem;
}
</style>
