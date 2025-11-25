<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title ?? 'Material Request'); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* Style tambahan jika perlu */
        .btn-xs { padding: .125rem .25rem; font-size: .75rem; line-height: 1.5; border-radius: .2rem; }
        .table th, .table td { vertical-align: middle; }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php //$this->load->view('templates/sidebar'); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php //$this->load->view('templates/topbar'); ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-clipboard-list text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
                        <?php if (check_access('material_request', 'add')): ?>
                            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addMrModal">
                                <i class="fas fa-plus-circle mr-1"></i> Buat MR Baru
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body py-2">
                            <form method="get" action="<?= base_url('material_request/index'); ?>" class="form-inline">
                                <div class="form-group mr-2 mb-2">
                                    <label for="start_date" class="mr-2">Tgl Mulai:</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['start_date'] ?? ''); ?>">
                                </div>
                                <div class="form-group mr-3 mb-2">
                                    <label for="end_date" class="mr-2">Tgl Akhir:</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['end_date'] ?? ''); ?>">
                                </div>
                                <div class="form-group mr-3 mb-2 flex-grow-1">
                                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="Cari No MR..." value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                                </div>
                                <div class="form-group mb-2">
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter fa-sm mr-1"></i> Filter</button>
                                    <a href="<?= base_url('material_request'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo fa-sm mr-1"></i> Reset</a>
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
                                            <th>No. MR</th>
                                            <th>Tgl Permintaan</th>
                                            <th>Dibuat Oleh</th>
                                            <th>Status</th>
                                            <th width="150">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(!empty($mrs)): foreach($mrs as $mr): ?>
                                        <tr class="text-center">
                                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($mr->mr_number); ?></td>
                                            <td><?= date('d M Y', strtotime($mr->request_date)); ?></td>
                                            <td class="text-left"><?= htmlspecialchars($mr->requester_name ?? '-'); ?></td>
                                            <td>
                                                <?php
                                                    $badge_class = 'secondary'; // Default
                                                    if ($mr->status == 'Draft') $badge_class = 'warning';
                                                    if ($mr->status == 'Submitted') $badge_class = 'info';
                                                    if ($mr->status == 'Issued') $badge_class = 'primary';
                                                    if ($mr->status == 'Received') $badge_class = 'success';
                                                    if ($mr->status == 'Closed') $badge_class = 'dark';
                                                ?>
                                                <span class="badge badge-<?= $badge_class; ?>"><?= htmlspecialchars($mr->status); ?></span>
                                            </td>
                                            <td>
                                                <a href="<?= base_url('material_request/detail/'.$mr->id); ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> Detail
                                                </a>
                                                <?php if ($mr->status == 'Draft' && check_access('material_request', 'delete')): ?>
                                                <a href="<?= base_url('material_request/delete_mr/'.$mr->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" data-message="Yakin ingin menghapus MR ini?">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data Material Request</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div></div><?php //$this->load->view('templates/footer_content'); ?>
        </div></div><a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <div class="modal fade" id="addMrModal" tabindex="-1" aria-labelledby="addMrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="addMrForm" method="post" action="<?= base_url('material_request/create_header'); ?>">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-gradient-primary text-white">
                        <h5 class="modal-title" id="addMrModalLabel"><i class="fas fa-plus-circle mr-2"></i>Buat Material Request Baru</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="mr_request_date">Tanggal Permintaan</label>
                            <input type="date" name="request_date" id="mr_request_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                            <div class="invalid-feedback">Tanggal wajib diisi.</div>
                        </div>
                        <div class="form-group">
                            <label for="mr_remarks">Catatan</label>
                            <textarea name="remarks" id="mr_remarks" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-arrow-right mr-1"></i> Simpan & Lanjut ke Item
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(function() {

        // --- Validasi Client-Side sebelum Submit Form Header MR ---
        $('#addMrForm').on('submit', function(event) {
            console.log("Attempting to submit addMrForm...");

            // Hapus tanda error sebelumnya
            $('#mr_request_date').removeClass('is-invalid').closest('.form-group').find('.invalid-feedback').hide();

            let isValid = true;
            const reqDate = $('#mr_request_date').val();

            // Validasi Tanggal
            if (!reqDate || reqDate === '') {
                 console.error("Validation failed: Request Date is empty.");
                 $('#mr_request_date').addClass('is-invalid').closest('.form-group').find('.invalid-feedback').show();
                 isValid = false;
            } else {
                 console.log("Request Date for submission:", reqDate);
            }

            // Jika tidak valid, hentikan submit dan beri pesan
            if (!isValid) {
                console.warn("Form submission prevented due to validation errors.");
                event.preventDefault(); // Mencegah form disubmit
                Swal.fire({
                    title: 'Input Tidak Lengkap',
                    text: 'Harap isi Tanggal Permintaan.',
                    icon: 'warning',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
                return false;
            }

            // Jika valid, log data dan biarkan submit
            console.log("Form data seems valid. Submitting form with Date:", reqDate);
            // Menonaktifkan tombol submit secara manual
            $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

            // Form akan disubmit secara normal
        });

        // SweetAlert untuk konfirmasi hapus di tabel utama
        $('.btn-delete').on('click', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            const message = $(this).data('message') || 'Yakin ingin menghapus data ini?';
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
                    Swal.fire({ title: 'Menghapus...', text: 'Mohon tunggu...', didOpen: () => { Swal.showLoading() }, allowOutsideClick: false });
                    window.location.href = url;
                }
            });
        });

    }); // Akhir $(function() {})
    </script>

</body>
</html>