<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title ?? 'Bill of Materials'); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .table th, .table td { vertical-align: middle; padding: 0.5rem; }
        .btn-xs { padding: .125rem .25rem; font-size: .75rem; line-height: 1.5; border-radius: .2rem; }
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
                    <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-cogs text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
                    <?php if (check_access('bom', 'add')): ?>
                        <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addBomModal">
                            <i class="fas fa-plus-circle mr-1"></i> Buat BOM Baru
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($this->session->flashdata('success')): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= $this->session->flashdata('success'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
                <?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
                <?php if ($this->session->flashdata('warning')): ?><div class="alert alert-warning alert-dismissible fade show" role="alert"><?= $this->session->flashdata('warning'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-body py-2">
                        <form method="get" action="<?= base_url('bom/index'); ?>" class="form-inline flex-wrap">
                            <div class="form-group mr-3 mb-2 flex-grow-1">
                                <label for="search" class="sr-only">Cari</label>
                                <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="Cari Kode / Nama Style..." value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                            </div>
                            <div class="form-group mb-2">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> Cari</button>
                                <a href="<?= base_url('bom'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo mr-1"></i> Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="thead-light text-center">
                                    <tr>
                                        <th>Kode Style</th>
                                        <th>Nama Style</th>
                                        <th>Revisi</th>
                                        <th>Status</th>
                                        <th width="150">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($boms)): foreach($boms as $bom): ?>
                                    <tr class="text-center">
                                        <td class="font-weight-bold text-primary"><?= htmlspecialchars($bom->fg_code); ?></td>
                                        <td class="text-left"><?= htmlspecialchars($bom->fg_name); ?></td>
                                        <td><?= htmlspecialchars($bom->revision); ?></td>
                                        <td>
                                            <?php
                                                $badge_class = 'info';
                                                if ($bom->status == 'Draft') $badge_class = 'warning';
                                                if ($bom->status == 'Active') $badge_class = 'success';
                                                if ($bom->status == 'Obsolete') $badge_class = 'secondary';
                                            ?>
                                            <span class="badge badge-<?= $badge_class; ?>"><?= htmlspecialchars($bom->status); ?></span>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('bom/detail/'.$bom->id); ?>" class="btn btn-sm btn-info" title="Detail">
                                                <i class="fas fa-list fa-fw"></i> Detail
                                            </a>
                                            <?php if ($bom->status == 'Draft' && check_access('bom', 'delete')): ?>
                                            <a href="<?= base_url('bom/delete_bom/'.$bom->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" title="Hapus" data-message="Yakin ingin menghapus BOM <?= htmlspecialchars(addslashes($bom->fg_name)); ?>?">
                                                <i class="fas fa-trash fa-fw"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data BOM</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div></div></div></div><div class="modal fade" id="addBomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="createBomForm" method="post" action="<?= base_url('bom/create_header'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Pilih Style untuk Buat BOM</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="fg_id_selector">Pilih Style (Finished Good)</label>
                        <select name="fg_id" id="fg_id_selector" class="form-control" required>
                            <option value="">-- Pilih Style --</option>
                            <?php if(!empty($available_fgs)): foreach($available_fgs as $fg): ?>
                                <option value="<?= $fg->id; ?>">
                                    <?= htmlspecialchars($fg->nama_barang . ' (' . $fg->kode_barang . ')'); ?>
                                </option>
                            <?php endforeach; else: ?>
                                <option value="" disabled>-- Semua style sudah memiliki BOM --</option>
                            <?php endif; ?>
                        </select>
                        <div class="invalid-feedback">Style wajib dipilih.</div> <small class="form-text text-muted">Hanya menampilkan style yang belum memiliki BOM.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" <?= empty($available_fgs) ? 'disabled' : ''; ?>>
                        <i class="fas fa-arrow-right mr-1"></i> Lanjut ke Detail Komponen
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

    // --- Validasi Client-Side sebelum Submit Form Header BOM ---
    $('#createBomForm').on('submit', function(event) {
        console.log("Attempting to submit createBomForm...");

        $('#fg_id_selector').removeClass('is-invalid');
        let isValid = true;
        const fgId = $('#fg_id_selector').val();

        // Validasi FG ID
        if (!fgId || fgId === '') {
            console.error("Validation failed: Finished Good ID is empty.");
            $('#fg_id_selector').addClass('is-invalid').closest('.form-group').find('.invalid-feedback').show();
            isValid = false;
        } else {
             console.log("FG ID selected for submission:", fgId);
        }

        if (!isValid) {
            console.warn("Form submission prevented due to validation errors.");
            event.preventDefault(); // Mencegah form disubmit
            Swal.fire({
                title: 'Input Tidak Lengkap', text: 'Harap pilih Style (Finished Good) terlebih dahulu.',
                icon: 'warning', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000
            });
            return false;
        }

        // Jika valid, nonaktifkan tombol dan biarkan submit
        console.log("Form data seems valid. Submitting form...");
        $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Memproses...');
        // Form akan disubmit secara normal
    });

    // --- SweetAlert untuk konfirmasi hapus ---
    $('body').on('click', '.btn-delete', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const message = $(this).data('message') || 'Yakin ingin menghapus data ini?';
        Swal.fire({
            title: 'Konfirmasi Hapus', text: message, icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Menghapus...', text: 'Mohon tunggu...', didOpen: () => { Swal.showLoading() }, allowOutsideClick: false });
                window.location.href = url;
            }
        });
    });

    // Hapus class 'btn-load' dari link (jika ada script global)
    // $('.btn-load').removeClass('btn-load');

});
</script>

</body>
</html>