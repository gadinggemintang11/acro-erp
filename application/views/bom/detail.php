<?php
// Ambil status header dan bersihkan (untuk pengecekan if)
$is_draft = (strtoupper(trim($header->status)) == 'DRAFT');
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800">Detail BOM - <?= html_escape($header->fg_code); ?></h1>
        
        <!-- Tombol Aksi Dinamis Sesuai Status -->
        <div>
            <a href="<?= base_url('bom'); ?>" class="btn btn-secondary btn-sm shadow-sm">
                <i class="fas fa-arrow-left fa-sm"></i> Kembali ke Daftar
            </a>
            <a href="<?= base_url('bom/export_pdf/' . $header->id); ?>" target="_blank" class="btn btn-dark btn-sm shadow-sm">
                <i class="fas fa-file-pdf fa-sm"></i> PDF
            </a>
            
            <?php if ($is_draft): ?>
                <!-- JIKA STATUS DRAFT -->
                <?php if (check_access('bom', 'edit')): // atau 'approve' ?>
                <a href="<?= base_url('bom/confirm_bom/' . $header->id); ?>" class="btn btn-success btn-sm shadow-sm btn-confirm" 
                   data-message="Anda yakin ingin mengaktifkan BOM ini? BOM yang sudah aktif tidak bisa diubah komponennya.">
                    <i class="fas fa-check fa-sm"></i> Aktifkan BOM
                </a>
                <?php endif; ?>
                
                <?php if (check_access('bom', 'delete')): ?>
                <a href="<?= base_url('bom/delete_bom/' . $header->id); ?>" class="btn btn-danger btn-sm shadow-sm btn-delete"
                   data-message="Anda yakin ingin menghapus BOM (Draft) ini beserta semua komponennya?">
                    <i class="fas fa-trash fa-sm"></i> Hapus BOM
                </a>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- JIKA STATUS ACTIVE (atau lainnya) -->
                <?php if (check_access('bom', 'edit')): // atau 'approve' ?>
                <a href="<?= base_url('bom/deactivate_bom/' . $header->id); ?>" class="btn btn-warning btn-sm shadow-sm btn-confirm"
                   data-message="Anda yakin ingin mengembalikan BOM ini ke status Draft? Ini akan mengizinkan komponen diubah kembali.">
                    <i class="fas fa-undo fa-sm"></i> Kembalikan ke Draft
                </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Tampilkan Flashdata -->
    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('success'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Informasi Header BOM -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Informasi BOM</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td style="width: 150px;"><strong>Style / FG</strong></td>
                            <td>: <?= html_escape($header->fg_name); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Kode FG</strong></td>
                            <td>: <?= html_escape($header->fg_code); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td style="width: 150px;"><strong>Status</strong></td>
                            <td>: 
                                <?php if ($is_draft): ?>
                                    <span class="badge badge-warning">Draft</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Active</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Revisi</strong></td>
                            <td>: <?= html_escape($header->revision); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Dibuat Pada</strong></td>
                            <td>: <?= $header->created_at ? date('d M Y H:i', strtotime($header->created_at)) : 'N/A'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bagian Komponen -->
    <div class="row">
        
        <?php if ($is_draft): ?>
        <!-- HANYA TAMPIL JIKA DRAFT: Form Tambah Komponen -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tambah Komponen</h6>
                </div>
                <div class="card-body">
                    <form action="<?= base_url('bom/add_item/' . $header->id); ?>" method="POST">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                        
                        <div class="form-group">
                            <label for="component_id">Komponen (Material / Semi-Finished)</label>
                            <select class="form-control" id="component_search" name="component_id" required></select>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity_required">Qty Dibutuhkan</label>
                            <input type="number" step="0.0001" class="form-control" name="quantity_required" 
                                   placeholder="0.00" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-plus"></i> Tambah Item
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Daftar Komponen -->
        <div class="<?= $is_draft ? 'col-lg-8' : 'col-lg-12'; ?>">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Komponen Material / Semi-Finished</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tipe</th>
                                    <th>Kode Komponen</th>
                                    <th>Nama Komponen</th>
                                    <th>Satuan</th>
                                    <th>Qty Dibutuhkan</th>
                                    <?php if ($is_draft): ?>
                                    <th style="width: 150px;">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($details)): ?>
                                    <tr>
                                        <td colspan="<?= $is_draft ? 7 : 6; ?>" class="text-center">Belum ada komponen di BOM ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($details as $item): ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td>
                                            <span class="badge badge-<?= $item->item_type == 'material' ? 'info' : 'secondary'; ?>">
                                                <?= ucfirst($item->item_type); ?>
                                            </span>
                                        </td>
                                        <td><?= html_escape($item->component_code); ?></td>
                                        <td><?= html_escape($item->component_name); ?></td>
                                        <td><?= html_escape($item->component_unit); ?></td>
                                        
                                        <?php if ($is_draft): ?>
                                            <!-- JIKA DRAFT: Tampilkan form update qty -->
                                            <form action="<?= base_url('bom/update_item/' . $header->id . '/' . $item->id); ?>" method="POST" class="d-flex">
                                                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                                                <td>
                                                    <input type="number" step="0.0001" class="form-control form-control-sm" 
                                                           name="quantity_required_<?= $item->id; ?>" 
                                                           value="<?= html_escape($item->quantity_required); ?>" required>
                                                </td>
                                                <td>
                                                    <button type="submit" class="btn btn-success btn-sm btn-block mb-1">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                    <a href="<?= base_url('bom/delete_item/' . $header->id . '/' . $item->id); ?>" 
                                                       class="btn btn-danger btn-sm btn-block btn-delete"
                                                       data-message="Anda yakin ingin menghapus item '<?= html_escape($item->component_name); ?>' dari BOM ini?">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </form>
                                        <?php else: ?>
                                            <!-- JIKA ACTIVE: Tampilkan teks saja -->
                                            <td><?= html_escape($item->quantity_required); ?></td>
                                        <?php endif; ?>
                                        
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<!-- CSS dan JS Khusus untuk Select2 (Tambahkan di footer jika belum ada) -->
<!-- Pastikan Select2 CSS sudah ada di header.php -->
<!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> -->
<!-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> -->

<script>
$(document).ready(function() {
    
    // Inisialisasi Select2 untuk pencarian komponen
    $('#component_search').select2({
        placeholder: 'Cari Kode atau Nama...',
        minimumInputLength: 2,
        ajax: {
            url: '<?= base_url('bom/search_components'); ?>',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.results
                };
            },
            cache: true
        }
    });

    // Konfirmasi SweetAlert untuk Hapus Item
    $('.btn-delete').on('click', function(e) {
        e.preventDefault(); 
        const href = $(this).attr('href');
        const message = $(this).data('message') || 'Anda yakin ingin menghapus data ini?';

        Swal.fire({
            title: 'Anda Yakin?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });
    
    // Konfirmasi SweetAlert untuk Aksi (Aktifkan/Deaktivasi)
    $('.btn-confirm').on('click', function(e) {
        e.preventDefault(); 
        const href = $(this).attr('href');
        const message = $(this).data('message') || 'Anda yakin ingin melanjutkan aksi ini?';

        Swal.fire({
            title: 'Konfirmasi Aksi',
            text: message,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, lanjutkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });

});
</script>
