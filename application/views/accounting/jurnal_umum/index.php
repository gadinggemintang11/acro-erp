<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <div>
            <a href="<?= base_url('summary_ledger'); ?>" class="btn btn-info btn-icon-split mr-2"
               title="<?= lang_line('ju_btn_upload_voucher_tooltip', 'Upload jurnal massal format voucher (Summary Ledger)') ?>">
                <span class="icon text-white-50"><i class="fas fa-file-invoice-dollar"></i></span>
                <span class="text"><?= lang_line('ju_btn_upload_voucher', 'Upload Voucher') ?></span>
            </a>
            <?php /* Batch Import disembunyikan: upload jurnal sekarang pakai Upload Voucher (format Summary Ledger).
            <a href="<?= base_url('jurnalumum/batch_import'); ?>" class="btn btn-success btn-icon-split mr-2">
                <span class="icon text-white-50"><i class="fas fa-file-excel"></i></span>
                <span class="text"><?= lang_line('ju_btn_batch_import', 'Batch Import') ?></span>
            </a>
            */ ?>
            <a href="<?= base_url('jurnalumum/create'); ?>" class="btn btn-primary btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                <span class="text"><?= lang_line('ju_btn_new_journal', 'Buat Jurnal Baru') ?></span>
            </a>
            <button type="button" class="btn btn-dark btn-icon-split ml-2" data-toggle="modal" data-target="#monthEndModal"
                    title="<?= lang_line('ju_btn_month_end_tooltip', 'Export backup & hapus jurnal akhir bulan') ?>">
                <span class="icon text-white-50"><i class="fas fa-calendar-check"></i></span>
                <span class="text"><?= lang_line('ju_btn_month_end', 'Month End') ?></span>
            </button>
        </div>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <!-- Batch Actions Area -->
    <div id="batchActionArea" class="card shadow mb-4" style="display: none; background-color: #f0f7ff; border-left: 4px solid #0066cc;">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0"><strong><?= lang_line('ju_selected_label', 'Jurnal Terpilih:') ?> <span id="selectedCount">0</span></strong></p>
                </div>
                <div class="col-md-6 text-right">
                    <button type="button" id="clearSelectionBtn" class="btn btn-sm btn-warning mr-2">
                        <i class="fas fa-times"></i> <?= lang_line('ju_btn_clear_selection', 'Batal Pilihan') ?>
                    </button>
                    <button type="button" id="batchPostBtn" class="btn btn-sm btn-success">
                        <i class="fas fa-check"></i> <?= lang_line('ju_btn_post_selected', 'Posting Jurnal Terpilih') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('ju_filter_title', 'Filter & Pencarian') ?></h6>
            <div class="row mt-3">
                <div class="col-md-3">
                    <label class="small font-weight-bold"><?= lang_line('ju_lbl_date_from', 'Dari Tanggal') ?></label>
                    <input type="date" id="startDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="small font-weight-bold"><?= lang_line('ju_lbl_date_to', 'Sampai Tanggal') ?></label>
                    <input type="date" id="endDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold"><?= lang_line('ju_lbl_status', 'Status') ?></label>
                    <select id="filterStatus" class="form-control form-control-sm">
                        <option value=""><?= lang_line('ju_opt_all_status', 'Semua Status') ?></option>
                        <option value="Draft">Draft</option>
                        <option value="Posted">Posted</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small font-weight-bold d-block">&nbsp;</label>
                    <button type="button" id="filterBtn" class="btn btn-primary btn-sm">
                        <i class="fas fa-filter"></i> <?= lang_line('ju_btn_apply_filter', 'Terapkan Filter') ?>
                    </button>
                    <button type="button" id="resetBtn" class="btn btn-secondary btn-sm">
                        <i class="fas fa-redo"></i> <?= lang_line('ju_btn_reset', 'Reset') ?>
                    </button>
                    <button type="button" id="exportBtn" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> <?= lang_line('ju_btn_export_excel', 'Export Excel') ?>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="journalTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="selectAll" title="<?= lang_line('ju_th_select_all', 'Pilih Semua') ?>">
                                    <label class="custom-control-label" for="selectAll"></label>
                                </div>
                            </th>
                            <th width="11%"><?= lang_line('ju_th_journal_no', 'No. Jurnal') ?></th>
                            <th width="9%"><?= lang_line('ju_th_post_date', 'Tgl Posting') ?></th>
                            <th width="9%"><?= lang_line('ju_th_doc_date', 'Tgl Dokumen') ?></th>
                            <th width="9%"><?= lang_line('ju_th_due_date', 'Due Date') ?></th>
                            <th width="22%"><?= lang_line('ju_th_description', 'Keterangan') ?></th>
                            <th width="7%" class="text-right"><?= lang_line('ju_th_currency', 'Mata Uang') ?></th>
                            <th width="11%" class="text-right"><?= lang_line('ju_th_total', 'Total') ?></th>
                            <th width="8%" class="text-center"><?= lang_line('ju_th_status', 'Status') ?></th>
                            <th width="9%" class="text-center"><?= lang_line('ju_th_action', 'Aksi') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($journals as $j): ?>
                        <tr>
                            <td class="text-center">
                                <?php if($j->status == 'Draft'): ?>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input journal-checkbox" id="checkbox_<?= $j->id; ?>" value="<?= $j->id; ?>" title="<?= lang_line('ju_tooltip_select_journal', 'Pilih jurnal ini') ?>">
                                    <label class="custom-control-label" for="checkbox_<?= $j->id; ?>"></label>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= base_url('jurnalumum/view/' . $j->id); ?>" class="text-primary font-weight-bold">
                                    <?= $j->journal_no; ?>
                                </a>
                            </td>

                            <td data-order="<?= strtotime($j->journal_date); ?>">
                                <?= date('d/m/Y', strtotime($j->journal_date)); ?>
                            </td>

                            <td data-order="<?= !empty($j->document_date) ? strtotime($j->document_date) : 0; ?>">
                                <?= !empty($j->document_date) ? date('d/m/Y', strtotime($j->document_date)) : '<span class="text-muted">-</span>'; ?>
                            </td>

                            <td data-order="<?= !empty($j->due_date) ? strtotime($j->due_date) : 0; ?>">
                                <?php if (!empty($j->due_date)):
                                    $is_overdue = ($j->status !== 'Cancelled') && (strtotime($j->due_date) < strtotime(date('Y-m-d')));
                                ?>
                                    <span class="<?= $is_overdue ? 'text-danger font-weight-bold' : ''; ?>" <?= $is_overdue ? 'title="' . htmlspecialchars(lang_line('ju_tooltip_overdue', 'Lewat jatuh tempo'), ENT_QUOTES) . '"' : ''; ?>>
                                        <?= date('d/m/Y', strtotime($j->due_date)); ?>
                                        <?php if ($is_overdue): ?><i class="fas fa-exclamation-triangle ml-1"></i><?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>

                            <td><?= $j->description; ?></td>

                            <td class="text-right">
                                <small class="text-muted"><?= $j->currency ?? 'IDR'; ?></small>
                            </td>

                            <td class="text-right">
                                <strong><?= number_format($j->total_debit, 0, ',', '.'); ?></strong>
                            </td>

                            <td class="text-center">
                                <?php
                                    $badge = 'secondary';
                                    if ($j->status == 'Draft') $badge = 'warning';
                                    if ($j->status == 'Posted') $badge = 'success';
                                    if ($j->status == 'Cancelled') $badge = 'danger';
                                ?>
                                <span class="badge badge-<?= $badge; ?>"><?= $j->status; ?></span>
                            </td>

                            <td class="text-center">
                                <a href="<?= base_url('jurnalumum/view/' . $j->id); ?>" class="btn btn-info btn-circle btn-sm" title="<?= lang_line('ju_btn_view_detail', 'Lihat Detail') ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= base_url('summary_ledger/voucher_pdf/' . $j->id); ?>" target="_blank" class="btn btn-danger btn-circle btn-sm" title="<?= lang_line('ju_btn_voucher_pdf', 'Print Voucher (PDF)') ?>">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                <a href="<?= base_url('summary_ledger/voucher_excel/' . $j->id); ?>" class="btn btn-success btn-circle btn-sm" title="<?= lang_line('ju_btn_voucher_excel', 'Print Voucher (Excel)') ?>">
                                    <i class="fas fa-file-excel"></i>
                                </a>
                                <?php if($j->status == 'Draft'): ?>
                                <a href="<?= base_url('jurnalumum/delete/' . $j->id); ?>" class="btn btn-danger btn-circle btn-sm" title="<?= lang_line('ju_btn_delete', 'Hapus') ?>" onclick="return confirm(<?= htmlspecialchars(json_encode(lang_line('ju_confirm_delete', 'Anda yakin ingin menghapus jurnal DRAFT ini?')), ENT_QUOTES) ?>);">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Month End: Backup & Clear Modal -->
<div class="modal fade" id="monthEndModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title"><i class="fas fa-calendar-check mr-2"></i><?= lang_line('ju_me_title', 'Month End: Backup & Clear Jurnal') ?></h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group col-6">
            <label class="small font-weight-bold"><?= lang_line('ju_me_from', 'Dari Tanggal') ?></label>
            <input type="date" id="meFrom" class="form-control form-control-sm">
          </div>
          <div class="form-group col-6">
            <label class="small font-weight-bold"><?= lang_line('ju_me_to', 'Sampai Tanggal') ?></label>
            <input type="date" id="meTo" class="form-control form-control-sm">
          </div>
        </div>
        <div id="meCheckInfo" class="small text-muted mb-3"></div>
        <hr class="my-2">
        <p class="mb-1"><strong>1.</strong> <?= lang_line('ju_me_step1', 'Export Full Backup (semua jurnal Posted di periode — bisa di-restore).') ?></p>
        <a href="#" id="meExportBtn" class="btn btn-success btn-sm mb-3">
          <i class="fas fa-file-excel"></i> <?= lang_line('ju_me_export', 'Export Full Backup') ?>
        </a>
        <p class="mb-1"><strong>2.</strong> <?= lang_line('ju_me_step2', 'Clear (hapus) jurnal Posted di periode. Backup dulu!') ?></p>
        <button type="button" id="meClearBtn" class="btn btn-danger btn-sm">
          <i class="fas fa-trash"></i> <?= lang_line('ju_me_clear', 'Clear Journal') ?>
        </button>
        <hr class="my-2">
        <a href="<?= base_url('jurnalumum/import_full'); ?>" class="btn btn-outline-secondary btn-sm">
          <i class="fas fa-undo"></i> <?= lang_line('ju_me_restore', 'Import / Restore dari Backup') ?>
        </a>
      </div>
    </div>
  </div>
</div>
<form id="meClearForm" method="post" action="<?= base_url('jurnalumum/clear_period'); ?>" style="display:none;">
  <input type="hidden" name="from" id="meClearFrom">
  <input type="hidden" name="to" id="meClearTo">
</form>

<script>
(function(){
  const meFrom = document.getElementById('meFrom');
  const meTo   = document.getElementById('meTo');
  const info   = document.getElementById('meCheckInfo');
  const CHECK_URL  = '<?= base_url('jurnalumum/clear_check'); ?>';
  const EXPORT_URL = '<?= base_url('jurnalumum/export_full'); ?>';
  const L = {
    need_period: <?= json_encode(lang_line('ju_me_need_period', 'Isi periode dari & sampai tanggal dulu.')) ?>,
    attention:   <?= json_encode(lang_line('ju_swal_attention', 'Perhatian')) ?>,
    none:        <?= json_encode(lang_line('ju_me_none', 'Tidak ada jurnal Posted di periode ini.')) ?>,
    confirm_ttl: <?= json_encode(lang_line('ju_me_confirm_title', 'Konfirmasi Clear Journal')) ?>,
    confirm_yes: <?= json_encode(lang_line('ju_me_confirm_yes', 'Ya, Clear')) ?>,
    cancel:      <?= json_encode(lang_line('ju_swal_cancel', 'Batal')) ?>,
    posted_tpl:  <?= json_encode(lang_line('ju_me_posted_tpl', 'Posted di periode: {t} jurnal. Belum di-export: {u}.')) ?>,
    warn_del:    <?= json_encode(lang_line('ju_me_warn_del', 'Akan menghapus {t} jurnal Posted (periode {f} s/d {to}).')) ?>,
    warn_unexp:  <?= json_encode(lang_line('ju_me_warn_unexp', '{u} jurnal BELUM di-export. Sebaiknya Export Full Backup dulu!')) ?>,
    warn_ap:     <?= json_encode(lang_line('ju_me_warn_ap', 'Jurnal AP Payment ikut terhapus (link pembayaran di-null, status Paid tetap aman). Lanjut?')) ?>
  };

  function valid(){
    if(!meFrom.value || !meTo.value){ Swal.fire(L.attention, L.need_period, 'warning'); return false; }
    return true;
  }
  function refreshCheck(){
    if(!meFrom.value || !meTo.value){ info.textContent=''; return; }
    fetch(CHECK_URL + '?from=' + meFrom.value + '&to=' + meTo.value)
      .then(r=>r.json()).then(d=>{
        info.innerHTML = L.posted_tpl.replace('{t}','<strong>'+d.total+'</strong>')
          .replace('{u}','<strong class="'+(d.unexported>0?'text-danger':'text-success')+'">'+d.unexported+'</strong>');
      }).catch(()=>{ info.textContent=''; });
  }
  meFrom.addEventListener('change', refreshCheck);
  meTo.addEventListener('change', refreshCheck);

  document.getElementById('meExportBtn').addEventListener('click', function(e){
    e.preventDefault();
    if(!valid()) return;
    window.location.href = EXPORT_URL + '?from=' + meFrom.value + '&to=' + meTo.value;
  });

  document.getElementById('meClearBtn').addEventListener('click', function(){
    if(!valid()) return;
    const f = meFrom.value, t = meTo.value;
    fetch(CHECK_URL + '?from=' + f + '&to=' + t).then(r=>r.json()).then(d=>{
      if(d.total === 0){ Swal.fire(L.attention, L.none, 'info'); return; }
      let html = '<p>' + L.warn_del.replace('{t}','<strong>'+d.total+'</strong>').replace('{f}',f).replace('{to}',t) + '</p>';
      if(d.unexported>0){ html += '<p class="text-danger"><i class="fas fa-exclamation-triangle"></i> ' + L.warn_unexp.replace('{u}','<strong>'+d.unexported+'</strong>') + '</p>'; }
      html += '<p>' + L.warn_ap + '</p>';
      Swal.fire({ title:L.confirm_ttl, html:html, icon:'warning', showCancelButton:true, confirmButtonColor:'#dc3545', confirmButtonText:L.confirm_yes, cancelButtonText:L.cancel })
        .then(res=>{ if(res.isConfirmed){ document.getElementById('meClearFrom').value=f; document.getElementById('meClearTo').value=t; document.getElementById('meClearForm').submit(); } });
    });
  });
})();
</script>

<!-- DataTables Script -->
<script>
const I18N_JU = {
    dt_length_menu:    <?= json_encode(lang_line('ju_dt_length_menu', 'Tampilkan _MENU_ data per halaman')) ?>,
    dt_zero_records:   <?= json_encode(lang_line('ju_dt_zero_records', 'Data tidak ditemukan')) ?>,
    dt_info:           <?= json_encode(lang_line('ju_dt_info', 'Menampilkan halaman _PAGE_ dari _PAGES_')) ?>,
    dt_info_empty:     <?= json_encode(lang_line('ju_dt_info_empty', 'Tidak ada data tersedia')) ?>,
    dt_info_filtered:  <?= json_encode(lang_line('ju_dt_info_filtered', '(difilter dari _MAX_ total data)')) ?>,
    dt_search:         <?= json_encode(lang_line('ju_dt_search', 'Cari:')) ?>,
    dt_first:          <?= json_encode(lang_line('ju_dt_first', 'Pertama')) ?>,
    dt_last:           <?= json_encode(lang_line('ju_dt_last', 'Terakhir')) ?>,
    dt_next:           <?= json_encode(lang_line('ju_dt_next', 'Selanjutnya')) ?>,
    dt_previous:       <?= json_encode(lang_line('ju_dt_previous', 'Sebelumnya')) ?>,
    dt_all:            <?= json_encode(lang_line('ju_dt_all', 'Semua')) ?>,
    swal_attention:    <?= json_encode(lang_line('ju_swal_attention', 'Perhatian')) ?>,
    swal_no_journal:   <?= json_encode(lang_line('ju_swal_no_journal_selected', 'Tidak ada jurnal yang dipilih.')) ?>,
    swal_confirm_title:<?= json_encode(lang_line('ju_swal_confirm_post_title', 'Konfirmasi Posting')) ?>,
    swal_confirm_html: <?= json_encode(lang_line('ju_swal_confirm_post_html_tpl', '<p>Anda akan memposting <strong>{n} jurnal</strong>.</p><p>Proses ini tidak dapat dibatalkan setelah dikonfirmasi.</p>')) ?>,
    swal_yes_post:     <?= json_encode(lang_line('ju_swal_yes_post_now', 'Ya, Posting Sekarang')) ?>,
    swal_cancel:       <?= json_encode(lang_line('ju_swal_cancel', 'Batal')) ?>,
    swal_processing:   <?= json_encode(lang_line('ju_swal_processing', 'Sedang Memproses')) ?>,
    swal_processing_html: <?= json_encode(lang_line('ju_swal_processing_html', 'Posting jurnal sedang diproses...')) ?>,
    swal_post_done:    <?= json_encode(lang_line('ju_swal_post_done', 'Posting Selesai!')) ?>,
    swal_done:         <?= json_encode(lang_line('ju_swal_done', 'Proses Selesai')) ?>,
    swal_close:        <?= json_encode(lang_line('ju_swal_close', 'Tutup')) ?>,
    swal_failed:       <?= json_encode(lang_line('ju_swal_failed', 'Gagal')) ?>,
    swal_error:        <?= json_encode(lang_line('ju_swal_error', 'Kesalahan')) ?>,
    swal_server_error: <?= json_encode(lang_line('ju_swal_server_error', 'Terjadi kesalahan server: ')) ?>
};

$(document).ready(function() {
    // Initialize DataTable
    var table = $('#journalTable').DataTable({
        "order": [[2, "desc"]],
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, I18N_JU.dt_all]],
        "language": {
            "lengthMenu": I18N_JU.dt_length_menu,
            "zeroRecords": I18N_JU.dt_zero_records,
            "info": I18N_JU.dt_info,
            "infoEmpty": I18N_JU.dt_info_empty,
            "infoFiltered": I18N_JU.dt_info_filtered,
            "search": I18N_JU.dt_search,
            "paginate": {
                "first":    I18N_JU.dt_first,
                "last":     I18N_JU.dt_last,
                "next":     I18N_JU.dt_next,
                "previous": I18N_JU.dt_previous
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": [0, 9] }
        ],
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });

    // Custom Date Range Filter
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            var startDate = $('#startDate').val();
            var endDate = $('#endDate').val();
            var status = $('#filterStatus').val();

            var dateStr = $(table.row(dataIndex).node()).find('td:eq(2)').attr('data-order');
            var rowDate = dateStr ? new Date(parseInt(dateStr) * 1000) : null;

            var rowStatus = data[8] || '';

            var dateValid = true;
            var statusValid = true;

            if (startDate && rowDate) {
                var start = new Date(startDate);
                start.setHours(0,0,0,0);
                if (rowDate < start) dateValid = false;
            }

            if (endDate && rowDate) {
                var end = new Date(endDate);
                end.setHours(23,59,59,999);
                if (rowDate > end) dateValid = false;
            }

            if (status && rowStatus.indexOf(status) === -1) {
                statusValid = false;
            }

            return dateValid && statusValid;
        }
    );

    $('#filterBtn').on('click', function() {
        table.draw();
    });

    $('#resetBtn').on('click', function() {
        $('#startDate').val('');
        $('#endDate').val('');
        $('#filterStatus').val('');
        table.search('').draw();
    });

    $('#exportBtn').on('click', function() {
        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();
        var status = $('#filterStatus').val();

        var url = '<?= base_url('jurnalumum/export_list_excel'); ?>?';
        if (startDate) url += 'start_date=' + startDate + '&';
        if (endDate) url += 'end_date=' + endDate + '&';
        if (status) url += 'status=' + status;

        window.location.href = url;
    });

    $('#startDate, #endDate, #filterStatus').on('keypress', function(e) {
        if (e.which == 13) {
            $('#filterBtn').click();
        }
    });

    // ============================================
    // BATCH POST FUNCTIONALITY
    // ============================================

    function updateBatchActionArea() {
        var checkedCount = $('.journal-checkbox:checked').length;
        $('#selectedCount').text(checkedCount);

        if (checkedCount > 0) {
            $('#batchActionArea').slideDown(200);
        } else {
            $('#batchActionArea').slideUp(200);
        }
    }

    $('#selectAll').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('.journal-checkbox').prop('checked', isChecked);
        updateBatchActionArea();
    });

    $(document).on('change', '.journal-checkbox', function() {
        var totalCheckboxes = $('.journal-checkbox').length;
        var checkedCheckboxes = $('.journal-checkbox:checked').length;

        if (totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes) {
            $('#selectAll').prop('checked', true);
        } else {
            $('#selectAll').prop('checked', false);
        }

        updateBatchActionArea();
    });

    $('#clearSelectionBtn').on('click', function() {
        $('.journal-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false);
        updateBatchActionArea();
    });

    $('#batchPostBtn').on('click', function() {
        var selectedIds = [];
        $('.journal-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            Swal.fire(I18N_JU.swal_attention, I18N_JU.swal_no_journal, 'warning');
            return;
        }

        Swal.fire({
            title: I18N_JU.swal_confirm_title,
            html: I18N_JU.swal_confirm_html.replace('{n}', selectedIds.length),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            confirmButtonText: I18N_JU.swal_yes_post,
            cancelButtonText: I18N_JU.swal_cancel
        }).then((result) => {
            if (result.isConfirmed) {
                executeBatchPost(selectedIds);
            }
        });
    });

    function executeBatchPost(journalIds) {
        Swal.fire({
            title: I18N_JU.swal_processing,
            html: I18N_JU.swal_processing_html,
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '<?= base_url('jurnalumum/batch_post'); ?>',
            type: 'POST',
            dataType: 'json',
            data: {
                journal_ids: journalIds
            },
            success: function(response) {
                if (response.status) {
                    Swal.fire({
                        title: response.success_count > 0 ? I18N_JU.swal_post_done : I18N_JU.swal_done,
                        html: response.message,
                        icon: response.success_count > 0 ? 'success' : 'info',
                        confirmButtonText: I18N_JU.swal_close
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire(I18N_JU.swal_failed, response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire(I18N_JU.swal_error, I18N_JU.swal_server_error + error, 'error');
            }
        });
    }
});
</script>
