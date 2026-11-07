<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-tasks text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <?php if (check_access('production_process', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#createProcessModal">
                <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('production_process_create'); ?>
            </button>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <form method="get" action="<?= base_url('production_process/index'); ?>" class="form-inline">
                <div class="form-group mr-3 mb-2 flex-grow-1">
                    <label for="search" class="sr-only"><?= lang_line('btn_search'); ?></label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="<?= lang_line('production_search_placeholder'); ?>" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> <?= lang_line('btn_search'); ?></button>
                    <a href="<?= base_url('production_process'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo mr-1"></i> <?= lang_line('btn_reset'); ?></a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= $this->session->flashdata('success'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
    <?php if ($this->session->flashdata('warning')): ?><div class="alert alert-warning alert-dismissible fade show" role="alert"><?= $this->session->flashdata('warning'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('production_number'); ?></th>
                            <th><?= lang_line('production_wo_number'); ?></th>
                            <th><?= lang_line('production_sc_number'); ?></th>
                            <th><?= lang_line('production_style'); ?></th>
                            <th><?= lang_line('production_qty_plan'); ?></th>
                            <th><?= lang_line('production_date'); ?></th>
                            <th><?= lang_line('production_status'); ?></th>
                            <th width="150"><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($processes)): foreach($processes as $proc): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($proc->production_number); ?></td>
                            <td><?= htmlspecialchars($proc->wo_number); ?></td>
                            <td><?= htmlspecialchars($proc->sc_number); ?></td>
                            <td class="text-left"><?= htmlspecialchars($proc->fg_name); ?></td>
                            <td class="text-right"><?= number_format($proc->planned_qty); ?></td>
                            <td><?= date('d M Y', strtotime($proc->production_date)); ?></td>
                            <td>
                                <?php
                                    $badge_class = 'secondary';
                                    if ($proc->status == 'Draft') $badge_class = 'warning';
                                    if ($proc->status == 'In Progress') $badge_class = 'primary';
                                    if ($proc->status == 'Completed') $badge_class = 'success';
                                ?>
                                <span class="badge badge-<?= $badge_class; ?>"><?= htmlspecialchars($proc->status); ?></span>
                            </td>
                            <td>
                                <a href="<?= base_url('production_process/grinding/'.$proc->id); ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-cogs"></i> <?= lang_line('production_detail_action'); ?>
                                </a>
                                <?php if ($proc->status == 'Draft' && check_access('production_process', 'delete')): ?>
                                <a href="<?= base_url('production_process/delete_header/'.$proc->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" data-message="<?= lang_line('production_delete_confirm'); ?>">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-3"><?= lang_line('production_no_data'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<!-- Modal Create Process -->
<div class="modal fade" id="createProcessModal" tabindex="-1" aria-labelledby="createProcessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
             <form id="createProcessForm" method="post" action="<?= base_url('production_process/create_header'); ?>">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-gradient-primary text-white">
                        <h5 class="modal-title" id="createProcessModalLabel"><i class="fas fa-plus-circle mr-2"></i><?= lang_line('production_create_modal_title'); ?></h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
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
                            <label><?= lang_line('production_select_wo'); ?></label>
                            <input type="hidden" name="wo_header_id" id="wo_selector" value="">
                            <div class="input-group">
                                <input type="text" id="wo_display" class="form-control" readonly
                                       placeholder="<?= lang_line('production_wo_none_selected', 'Belum ada Work Order dipilih'); ?>">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-info" id="btnPickWo" data-toggle="modal" data-target="#pickWoModal">
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
                        <div id="wo_details_info" style="display: none;" class="alert alert-secondary p-2 mb-3">
                            <small>
                                Sales Order Number: <strong id="info_sc_number">-</strong><br>
                                Style: <strong id="info_fg_name">-</strong><br>
                                Qty Rencana: <strong id="info_planned_qty">-</strong>
                            </small>
                        </div>
                         <div class="row">
                             <div class="col-md-6 form-group">
                                 <label for="production_date">Tanggal Mulai Produksi</label>
                                 <input type="date" name="production_date" id="production_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                                 <div class="invalid-feedback">Tanggal wajib diisi.</div>
                             </div>
                         </div>
                        <div class="form-group">
                            <label for="remarks">Catatan</label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-arrow-right mr-1"></i> Simpan & Lanjut ke Proses Grinding
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php /*
    Modal daftar Work Order -- polanya sama dengan "Pilih dari Daftar IPO" di
    Buat Work Order dan "Tarik dari PR" di Purchase Order.
*/ ?>
<div class="modal fade" id="pickWoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-list mr-2"></i><?= lang_line('production_wo_modal_title', 'Daftar Work Order'); ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
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
    $(function() { // Shorthand for $(document).ready()

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

    }); // Akhir $(function() {})
    </script>