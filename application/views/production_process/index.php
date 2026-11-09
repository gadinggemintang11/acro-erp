<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Daftar Proses Produksi (redesign 2026-11-08).
 * Progres tiap dokumen ditampilkan sebagai stepper mini 4 tahap
 * (Cutting -> Sewing -> Assembling -> Finishing).
 */
$this->load->view('production_process/_pp_helpers');
$this->load->view('production_process/_pp_style');
$pp_stages  = $pp_stages ?? ['cutting', 'sewing', 'assembling', 'finishing'];
$cur_status = $filters['status'] ?? '';
$kpis = [
    ''            => ['label' => lang_line('pp2_kpi_all', 'Semua Produksi'), 'icon' => 'fa-layer-group',  'tone' => 'tone-brand', 'n' => $status_counts['all'] ?? 0],
    'Draft'       => ['label' => lang_line('pp_status_draft'),               'icon' => 'fa-pencil-alt',   'tone' => 'tone-amber', 'n' => $status_counts['Draft'] ?? 0],
    'In Progress' => ['label' => lang_line('pp_status_in_progress'),         'icon' => 'fa-cogs',         'tone' => 'tone-blue',  'n' => $status_counts['In Progress'] ?? 0],
    'Completed'   => ['label' => lang_line('pp_status_completed'),           'icon' => 'fa-check-double', 'tone' => 'tone-green', 'n' => $status_counts['Completed'] ?? 0],
];
$flash_icons = ['success' => 'fa-check-circle', 'error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle'];
?>
<div class="container-fluid pp2">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:.75rem">
        <div>
            <h1 class="h4 mb-0 font-weight-bold" style="color:var(--pp-text)"><?= htmlspecialchars($title); ?></h1>
            <div class="small" style="color:var(--pp-muted)"><?= lang_line('pp2_list_sub', 'Cutting → Sewing → Assembling → Finishing, lengkap dengan waktu proses tiap tahap.'); ?></div>
        </div>
        <?php if (check_access('production_process', 'add')): ?>
            <button class="btn btn-pp shadow-sm px-3" data-toggle="modal" data-target="#createProcessModal">
                <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('production_process_create'); ?>
            </button>
        <?php endif; ?>
    </div>

    <?php foreach ($flash_icons as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert">
            <i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button>
        </div>
    <?php endif; endforeach; ?>

    <div class="pp-kpis">
        <?php foreach ($kpis as $k => $kp):
            $q = array_filter(['search' => $filters['search'] ?? '', 'status' => $k]); ?>
            <a href="<?= base_url('production_process') . ($q ? '?' . http_build_query($q) : ''); ?>" class="pp-kpi <?= $kp['tone']; ?><?= $cur_status === $k ? ' is-active' : ''; ?>">
                <span class="pp-kpi-ico"><i class="fas <?= $kp['icon']; ?>"></i></span>
                <span><span class="pp-kpi-num d-block"><?= number_format((int) $kp['n']); ?></span><span class="pp-kpi-label d-block"><?= htmlspecialchars($kp['label']); ?></span></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="pp-card">
        <div class="pp-card-head">
            <form method="get" action="<?= base_url('production_process'); ?>" class="pp-toolbar w-100">
                <?php if ($cur_status !== ''): ?><input type="hidden" name="status" value="<?= htmlspecialchars($cur_status); ?>"><?php endif; ?>
                <div class="pp-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control" placeholder="<?= htmlspecialchars(lang_line('pp2_search_ph', 'Cari no. produksi, WO, Sales Order, barang jadi…')); ?>" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn btn-pp"><i class="fas fa-search mr-1"></i><?= lang_line('btn_search'); ?></button>
                <?php if (!empty($filters['search']) || $cur_status !== ''): ?>
                    <a href="<?= base_url('production_process'); ?>" class="btn btn-light"><i class="fas fa-times mr-1"></i><?= lang_line('btn_reset'); ?></a>
                <?php endif; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th><?= lang_line('production_number'); ?></th>
                    <th><?= lang_line('pp2_th_wo_so', 'Work Order / Sales Order'); ?></th>
                    <th><?= lang_line('pp2_meta_fg', 'Barang Jadi'); ?></th>
                    <th class="num"><?= lang_line('production_qty_plan'); ?></th>
                    <th><?= lang_line('pp2_th_progress', 'Progres Tahap'); ?></th>
                    <th><?= lang_line('production_status'); ?></th>
                    <th class="text-right"><?= lang_line('label_action'); ?></th>
                </tr></thead>
                <tbody>
                <?php if (!empty($processes)): foreach ($processes as $proc):
                    $ov = $stage_overview[$proc->id] ?? [];
                    // "Buka" ke tahap pertama yang belum selesai
                    $open_stage = end($pp_stages);
                    foreach ($pp_stages as $s) { if (empty($ov[$s]->finished_at)) { $open_stage = $s; break; } }
                    reset($pp_stages);
                ?>
                    <tr>
                        <td>
                            <a href="<?= base_url('production_process/' . $open_stage . '/' . $proc->id); ?>" class="pp-list-title"><?= htmlspecialchars($proc->production_number); ?></a>
                            <div class="pp-item-code"><i class="far fa-calendar mr-1"></i><?= pp_fmt_dt($proc->production_date, '-', false); ?></div>
                        </td>
                        <td>
                            <div class="font-weight-bold" style="font-size:.82rem"><?= htmlspecialchars($proc->wo_number ?: '-'); ?></div>
                            <div class="pp-item-code"><?= htmlspecialchars($proc->sc_number ?: '-'); ?></div>
                        </td>
                        <td>
                            <div class="pp-item-name"><?= htmlspecialchars($proc->fg_name ?: '-'); ?></div>
                            <?php if (!empty($proc->fg_code)): ?><div class="pp-item-code"><?= htmlspecialchars($proc->fg_code); ?></div><?php endif; ?>
                        </td>
                        <td class="num"><b><?= pp_qty($proc->planned_qty, 2); ?></b> <span class="pp-item-code"><?= htmlspecialchars($proc->fg_unit ?? ''); ?></span></td>
                        <td>
                            <div class="pp-mini">
                                <?php foreach ($pp_stages as $i => $s):
                                    $o = $ov[$s] ?? null;
                                    $has = $o && ($o->inputs || $o->outputs || $o->started_at);
                                    $done = $o && $o->finished_at;
                                    $tip = lang_line('pp_stage_' . $s) . ': ' . ($o && $o->started_at ? pp_fmt_range($o->started_at, $o->finished_at) : lang_line('pp2_time_not_set', 'Waktu belum diisi'))
                                         . ($o ? ' · ' . (int) $o->outputs . ' output' : '');
                                    if ($i): ?><span class="pp-mini-line"></span><?php endif; ?>
                                    <a href="<?= base_url('production_process/' . $s . '/' . $proc->id); ?>" class="pp-mini-dot <?= pp_stage_meta($s)['tone']; ?><?= $done ? ' is-done' : ($has ? ' has-data' : ''); ?>"
                                       title="<?= htmlspecialchars($tip); ?>" data-toggle="tooltip"><?= $done ? '<i class="fas fa-check"></i>' : '<i class="fas ' . pp_stage_meta($s)['icon'] . '"></i>'; ?></a>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td><?= pp_status_pill($proc->status); ?></td>
                        <td class="text-right">
                            <div class="pp-actions">
                                <a href="<?= base_url('production_process/' . $open_stage . '/' . $proc->id); ?>" class="btn btn-pp"><i class="fas fa-arrow-right mr-1"></i><?= lang_line('production_detail_action'); ?></a>
                                <?php if ($proc->status == 'Draft' && check_access('production_process', 'delete')): ?>
                                    <a href="<?= base_url('production_process/delete_header/' . $proc->id); ?>" class="btn btn-outline-danger btn-delete" data-message="<?= htmlspecialchars(lang_line('production_delete_confirm')); ?>" title="<?= htmlspecialchars(lang_line('btn_delete', 'Hapus')); ?>"><i class="fas fa-trash-alt"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" class="pp-empty"><i class="fas fa-industry"></i><?= lang_line('production_no_data'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($processes)): ?>
        <div class="pp-foot">
            <span><?= count($processes); ?> <?= lang_line('pp2_docs', 'dokumen'); ?></span>
            <span class="d-flex align-items-center flex-wrap" style="gap:.6rem">
                <?php foreach ($pp_stages as $s): ?><span><i class="fas <?= pp_stage_meta($s)['icon']; ?> mr-1"></i><?= htmlspecialchars(lang_line('pp_stage_' . $s)); ?></span><?php endforeach; ?>
                <span><span class="pp-chip pp-chip--ok"><i class="fas fa-check"></i></span> = <?= lang_line('pp2_legend_done', 'selesai'); ?></span>
            </span>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Create Process -->
<div class="modal fade pp-modal" id="createProcessModal" tabindex="-1" aria-labelledby="createProcessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
             <form id="createProcessForm" method="post" action="<?= base_url('production_process/create_header'); ?>">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createProcessModalLabel"><i class="fas fa-plus-circle mr-2"></i><?= lang_line('production_create_modal_title'); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <?php /*
                                WO dipilih lewat modal daftar, bukan diketik. Select2 yang lama
                                baru memunculkan hasil setelah user mengetik, dan hanya
                                menampilkan WO yang memenuhi syarat -- kalau WO-nya tidak muncul
                                tidak ada keterangan kenapa (user 2026-09-30: "di production
                                proses juga saat pilih wo dibuat modal juga ya").
                                id="wo_selector" DIPERTAHANKAN supaya validasi submit yang sudah
                                ada tidak perlu diubah.
                            */ ?>
                            <label class="pp-label"><?= lang_line('production_select_wo'); ?></label>
                            <input type="hidden" name="wo_header_id" id="wo_selector" value="">
                            <div class="input-group">
                                <input type="text" id="wo_display" class="form-control" readonly
                                       placeholder="<?= lang_line('production_wo_none_selected', 'Belum ada Work Order dipilih'); ?>">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-pp" id="btnPickWo" data-toggle="modal" data-target="#pickWoModal">
                                        <i class="fas fa-list mr-1"></i> <?= lang_line('production_btn_pick_wo', 'Pilih dari Daftar'); ?>
                                    </button>
                                </div>
                            </div>
                            <?php /*
                                d-block + display:none (TANPA !important): input pemilih WO
                                sekarang hidden, jadi aturan .is-invalid ~ .invalid-feedback
                                bawaan Bootstrap tidak pernah aktif. Validasi submit yang sudah
                                ada memanggil .show() pada elemen ini -- kalau dipasangi
                                !important, .show() kalah dan pesannya tidak pernah muncul.
                            */ ?>
                            <div class="invalid-feedback d-block" style="display:none;" id="woInvalidMsg">Work Order wajib dipilih.</div>
                        </div>
                        <div id="wo_details_info" style="display: none;" class="pp-kv mb-3">
                            <div><span class="pp-label">Sales Order</span><div class="v" id="info_sc_number">-</div></div>
                            <div><span class="pp-label"><?= lang_line('pp2_meta_fg', 'Barang Jadi'); ?></span><div class="v" id="info_fg_name">-</div></div>
                            <div><span class="pp-label"><?= lang_line('pp2_meta_planned', 'Qty Rencana'); ?></span><div class="v" id="info_planned_qty">-</div></div>
                        </div>
                         <div class="row">
                             <div class="col-md-6 form-group">
                                 <label class="pp-label" for="production_date"><?= lang_line('pp2_meta_prod_date', 'Tanggal Produksi'); ?></label>
                                 <input type="date" name="production_date" id="production_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                                 <div class="invalid-feedback">Tanggal wajib diisi.</div>
                             </div>
                         </div>
                        <div class="form-group">
                            <label class="pp-label" for="remarks"><?= lang_line('pp2_meta_remarks', 'Catatan'); ?></label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('pp_sw_cancel'); ?></button>
                        <button type="submit" class="btn btn-pp">
                            <i class="fas fa-arrow-right mr-1"></i> <?= lang_line('pp2_create_submit', 'Simpan & Lanjut ke Cutting'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>



<?php /*
    Modal daftar Work Order -- polanya sama dengan "Pilih dari Daftar IPO" di
    Buat Work Order dan "Tarik dari PR" di Purchase Order.
*/ ?>
<div class="modal fade pp-modal" id="pickWoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-list mr-2"></i><?= lang_line('production_wo_modal_title', 'Daftar Work Order'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    <?= lang_line('production_wo_modal_hint', 'Satu Work Order hanya bisa punya satu proses produksi, dan WO harus sudah dikonfirmasi (status In Progress). WO yang belum memenuhi syarat tetap ditampilkan beserta alasannya, tapi tidak bisa dipilih.'); ?>
                </p>
                <div class="form-row mb-2">
                    <div class="col-md-10 mb-1">
                        <input type="text" id="woSearch" class="form-control form-control-sm"
                               placeholder="<?= lang_line('production_wo_search_ph', 'Cari No. WO / No. Sales Order / style...'); ?>">
                    </div>
                    <div class="col-md-2 mb-1">
                        <button type="button" id="woRefresh" class="btn btn-sm btn-outline-secondary btn-block">
                            <i class="fas fa-sync-alt mr-1"></i><?= lang_line('btn_refresh', 'Muat ulang'); ?>
                        </button>
                    </div>
                </div>
                <div class="table-responsive" style="max-height:52vh; overflow-y:auto;">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light small" style="position:sticky; top:0; z-index:2;">
                            <tr class="text-center">
                                <th style="width:140px;"><?= lang_line('production_wo_th_number', 'No. WO'); ?></th>
                                <th style="width:130px;"><?= lang_line('production_wo_th_ipo', 'No. Sales Order'); ?></th>
                                <th><?= lang_line('production_wo_th_style', 'Style / Finished Good'); ?></th>
                                <th style="width:96px;"><?= lang_line('production_wo_th_qty', 'Qty Rencana'); ?></th>
                                <th style="width:100px;"><?= lang_line('production_wo_th_start', 'Tgl Mulai'); ?></th>
                                <th style="width:96px;"><?= lang_line('production_wo_th_material', 'Material'); ?></th>
                                <th style="width:150px;"><?= lang_line('production_wo_th_status', 'Status'); ?></th>
                                <th style="width:80px;"><?= lang_line('common_action', 'Aksi'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="woBody" class="small">
                            <tr><td colspan="8" class="text-center text-muted py-4"><?= lang_line('common_loading', 'Memuat…'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('common_close', 'Tutup'); ?></button>
            </div>
        </div>
    </div>
</div>

    <script>
    // DOMContentLoaded: jQuery + Bootstrap + SweetAlert dimuat oleh footer.
    document.addEventListener('DOMContentLoaded', function() { var $ = window.jQuery;
        if ($.fn.tooltip) $('.pp2 [data-toggle="tooltip"]').tooltip();

        // Inisialisasi select2 untuk WO sudah DIHAPUS -- pemilihan WO sekarang
        // lewat modal daftar di bawah (#pickWoModal).

        // =================================================================
        // Modal "Pilih dari Daftar" Work Order
        // =================================================================
        var I18N_WOP = {
            loading:  <?= json_encode(lang_line('common_loading', 'Memuat…')); ?>,
            empty:    <?= json_encode(lang_line('production_wo_empty', 'Tidak ada Work Order yang cocok.')); ?>,
            failed:   <?= json_encode(lang_line('production_wo_failed', 'Gagal memuat daftar Work Order.')); ?>,
            pick:     <?= json_encode(lang_line('production_wo_btn_choose', 'Pilih')); ?>,
            ready:    <?= json_encode(lang_line('production_wo_ready', 'Bisa dipilih')); ?>,
            no_mat:   <?= json_encode(lang_line('production_wo_no_material', 'WO ini belum punya rencana material -- proses produksi tidak akan punya bahan untuk dipakai.')); ?>
        };

        function woFmt(n) {
            n = parseFloat(n || 0);
            return (Math.round(n * 100) / 100).toLocaleString('id-ID');
        }
        function woEsc(s) { return $('<div>').text(s == null ? '' : s).html(); }

        function loadWoList() {
            var $body = $('#woBody');
            $body.html('<tr><td colspan="8" class="text-center text-muted py-4">' + I18N_WOP.loading + '</td></tr>');

            $.getJSON('<?= base_url("production_process/ajax_selectable_wo_list"); ?>',
                      { search: $('#woSearch').val() || '' })
            .done(function (res) {
                if (!res || !res.success) {
                    $body.html('<tr><td colspan="8" class="text-center text-danger py-4">' +
                        woEsc((res && res.message) || I18N_WOP.failed) + '</td></tr>');
                    return;
                }
                if (!res.rows.length) {
                    $body.html('<tr><td colspan="8" class="text-center text-muted py-4">' + I18N_WOP.empty + '</td></tr>');
                    return;
                }

                var html = '';
                res.rows.forEach(function (r) {
                    var noMat = r.eligible && r.material_count === 0;

                    html += '<tr' + (r.eligible ? (noMat ? ' class="table-warning"' : '') : ' class="table-secondary text-muted"') + '>'
                         +  '<td><strong>' + woEsc(r.wo_number) + '</strong></td>'
                         +  '<td>' + (woEsc(r.sc_number) || '<span class="text-muted">-</span>') + '</td>'
                         +  '<td>' + woEsc(r.fg_name || '-') +
                                ' <small class="text-muted">(' + woEsc(r.fg_code || '') + ')</small></td>'
                         +  '<td class="text-right">' + woFmt(r.planned_qty) + '</td>'
                         +  '<td class="text-center">' + woEsc(r.planned_start_date || '-') + '</td>'
                         +  '<td class="text-center' + (noMat ? ' text-danger font-weight-bold' : '') + '"'
                         +      (noMat ? ' title="' + woEsc(I18N_WOP.no_mat) + '"' : '') + '>'
                         +      r.material_count + '</td>'
                         +  '<td class="text-center">'
                         +      (r.eligible
                                ? '<span class="badge badge-success">' + I18N_WOP.ready + '</span>'
                                : '<span class="badge badge-secondary">' + woEsc(r.wo_status) + '</span>' +
                                  '<br><small>' + woEsc(r.reason) + '</small>')
                         +  '</td>'
                         +  '<td class="text-center">'
                         +      (r.eligible
                                ? '<button type="button" class="btn btn-primary btn-xs btn-pick-wo"' +
                                      ' data-id="' + r.id + '" data-wo="' + woEsc(r.wo_number) + '">' +
                                      '<i class="fas fa-check mr-1"></i>' + I18N_WOP.pick + '</button>'
                                : '<span class="text-muted">-</span>')
                         +  '</td>'
                         +  '</tr>';
                });
                $body.html(html);
            })
            .fail(function () {
                $body.html('<tr><td colspan="8" class="text-center text-danger py-4">' + I18N_WOP.failed + '</td></tr>');
            });
        }

        $('#pickWoModal').on('shown.bs.modal', loadWoList);
        $('#woRefresh').on('click', loadWoList);

        var woTimer = null;
        $('#woSearch').on('input', function () {
            clearTimeout(woTimer);
            woTimer = setTimeout(loadWoList, 350);
        });

        // Memilih WO: isi hidden input lalu picu 'change' supaya panel detail
        // WO yang sudah ada (ajax_get_wo_details) tetap terpakai apa adanya.
        $('#woBody').on('click', '.btn-pick-wo', function () {
            var d = $(this).data();
            $('#wo_display').val(d.wo);
            $('#wo_selector').val(d.id).trigger('change');
            $('#woInvalidMsg').hide();
            $('#pickWoModal').modal('hide');
        });

        // --- Modal bertumpuk (Bootstrap 4) ---
        // Modal daftar WO dibuka DARI dalam modal Buat Proses Produksi.
        // Backdrop yang baru ditambahkan ada di AKHIR <body>, jadi harus
        // .last() -- memakai .first() akan menaikkan backdrop modal LUAR dan
        // membuat seluruh halaman gelap permanen.
        $('#pickWoModal').on('show.bs.modal', function () {
            $(this).css('z-index', 1062);
            setTimeout(function () {
                $('.modal-backdrop').last().css('z-index', 1061).addClass('wo-backdrop');
            }, 0);
        });
        $('#pickWoModal').on('hidden.bs.modal', function () {
            $('.modal-backdrop.wo-backdrop').remove();
            if ($('.modal:visible').length) $('body').addClass('modal-open');
        });

        // Bersihkan pilihan saat modal Buat Proses Produksi ditutup.
        $('#createProcessModal').on('hidden.bs.modal', function () {
            $('#wo_selector').val('');
            $('#wo_display').val('');
            $('#wo_details_info').hide();
            $('#woInvalidMsg').hide();
        });

        // --- Ambil Detail WO saat dipilih ---
        $('#wo_selector').on('change', function() {
            const woId = $(this).val();
            $('#wo_details_info').hide(); // Sembunyikan detail
            $('#info_sc_number, #info_fg_name, #info_planned_qty').text('-'); // Reset teks

            if (woId) {
                console.log("WO ID selected:", woId); // Log WO ID yang dipilih
                $.ajax({
                    url: '<?= base_url("production_process/ajax_get_wo_details"); ?>',
                    type: 'GET',
                    data: { wo_id: woId },
                    dataType: 'json',
                    success: function(response) {
                        console.log("Response from ajax_get_wo_details:", response); // Log respons server
                        if (response && response.success && response.details) {
                            $('#info_sc_number').text(response.details.sc_number || '-');
                            $('#info_fg_name').text(response.details.fg_name || '-');
                            $('#info_planned_qty').text(response.details.planned_qty || '-');
                            $('#wo_details_info').show(); // Tampilkan detail
                        } else {
                            console.error("Gagal mendapatkan detail WO atau format respons salah.");
                            Swal.fire('Error Detail WO', response.message || 'Detail WO tidak ditemukan!', 'error');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("AJAX Error for ajax_get_wo_details:", textStatus, errorThrown, jqXHR.responseText);
                        Swal.fire('Error Detail WO', 'Gagal mengambil detail WO. Cek koneksi atau log server.', 'error');
                    }
                });
            } else {
                 console.log("WO ID deselected or empty.");
            }
        });

        // --- Logging & Validasi sebelum Submit Form Header ---
        $('#createProcessForm').on('submit', function(event) {
            console.log("Attempting to submit createProcessForm...");

            // Hapus tanda error sebelumnya
            $('#wo_selector').removeClass('is-invalid').closest('.form-group').find('.invalid-feedback').hide();
            $('#production_date').removeClass('is-invalid').closest('.form-group').find('.invalid-feedback').hide();

            let isValid = true;
            const woId = $('#wo_selector').val();
            const prodDate = $('#production_date').val();

            // Validasi WO ID
            if (!woId || woId === '') {
                console.error("Validation failed: WO ID is empty.");
                $('#wo_selector').addClass('is-invalid').closest('.form-group').find('.invalid-feedback').show(); // Tampilkan pesan error
                isValid = false;
            } else {
                 console.log("WO ID selected for submission:", woId);
            }

            // Validasi Tanggal
            if (!prodDate || prodDate === '') {
                 console.error("Validation failed: Production Date is empty.");
                 $('#production_date').addClass('is-invalid').closest('.form-group').find('.invalid-feedback').show();
                 isValid = false;
            } else {
                 console.log("Production Date for submission:", prodDate);
            }

            // Jika tidak valid, hentikan submit dan beri pesan
            if (!isValid) {
                console.warn("Form submission prevented due to validation errors.");
                event.preventDefault(); // Mencegah form disubmit
                Swal.fire({
                    title: 'Input Tidak Lengkap',
                    text: 'Harap pilih Work Order dan isi Tanggal Produksi.',
                    icon: 'warning',
                    toast: true, // Tampilkan sebagai notifikasi kecil
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
                return false; // Hentikan eksekusi lebih lanjut
            }

            // Jika valid, log data dan biarkan submit
            console.log("Form data seems valid. Submitting form with WO ID:", woId, "and Date:", prodDate);
            // Menonaktifkan tombol submit secara manual setelah validasi lolos (jika tidak pakai btn-load)
            $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

            // Form akan disubmit secara normal
        });

        // SweetAlert untuk konfirmasi hapus di tabel utama
        $('.btn-delete').on('click', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            const message = $(this).data('message') || 'Yakin ingin menghapus data ini?'; // Ambil pesan dari data-message
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tampilkan loading sebelum redirect
                    Swal.fire({ title: 'Menghapus...', text: 'Mohon tunggu...', didOpen: () => { Swal.showLoading() }, allowOutsideClick: false });
                    window.location.href = url; // Lanjutkan ke URL hapus
                }
            });
        });

    }); // Akhir DOMContentLoaded
    </script>