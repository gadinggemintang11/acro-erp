<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title ?? 'Material Return'); ?></title>
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
                        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-undo-alt text-warning mr-2"></i><?= htmlspecialchars($title); ?></h3>
                        <?php if (check_access('prod_return', 'add')): // Sesuaikan key permission jika perlu ?>
                            <a href="<?= base_url('ProdReturn/create') ?>" class="btn btn-warning shadow-sm"> <?php // Hapus btn-load ?>
                                <i class="fas fa-plus-circle mr-1"></i> Buat Retur Baru
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body py-2">
                            <form method="get" action="<?= base_url('ProdReturn/index'); ?>" class="form-inline flex-wrap">
                                <div class="form-group mr-3 mb-2 flex-grow-1">
                                    <label for="search" class="sr-only">Cari</label>
                                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="Cari No. Retur / No. Produksi (WO)..." value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                                </div>
                                <div class="form-group mb-2">
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search fa-sm mr-1"></i> Cari</button>
                                    <a href="<?= base_url('ProdReturn'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo fa-sm mr-1"></i> Reset</a>
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
                                            <th>No. Retur</th>
                                            <th>Tgl Retur</th>
                                            <th>No. Produksi (WO)</th>
                                            <th>Diretur Oleh</th>
                                            <th>Diterima Gudang Oleh</th>
                                            <th>Status</th>
                                            <th width="150">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php // Pastikan controller mengirim variabel $returns ?>
                                        <?php if(!empty($returns)): foreach($returns as $ret): ?>
                                        <tr class="text-center">
                                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($ret->return_number ?? 'N/A'); ?></td>
                                            <td><?= isset($ret->return_date) ? date('d M Y', strtotime($ret->return_date)) : '-'; ?></td>
                                            <td><?= htmlspecialchars($ret->production_number ?? '-'); ?></td>
                                            <td class="text-left"><?= htmlspecialchars($ret->returner_name ?? '-'); ?></td>
                                            <td class="text-left"><?= htmlspecialchars($ret->receiver_name ?? '-'); // Nama kolom dari model 'receiver_name' ?></td>
                                            <td>
                                                <?php
                                                    $status = $ret->status ?? 'Unknown';
                                                    $badge_class = 'secondary'; // Default
                                                    if ($status == 'Draft') $badge_class = 'warning';
                                                    if ($status == 'Confirmed') $badge_class = 'success';
                                                    if ($status == 'Cancelled') $badge_class = 'danger';
                                                ?>
                                                <span class="badge badge-<?= $badge_class; ?>"><?= htmlspecialchars($status); ?></span>
                                            </td>
                                            <td>
                                                <?php // PERBAIKAN: Link detail ke /view/ ?>
                                                <a href="<?= base_url('ProdReturn/view/'.$ret->id); ?>" class="btn btn-sm btn-info" title="Detail"> <?php // Hapus btn-load ?>
                                                    <i class="fas fa-eye fa-fw"></i> <?php // Tambah fa-fw ?>
                                                </a>
                                                <?php // Tombol Edit hanya jika Draft ?>
                                                <?php if ($ret->status == 'Draft' && check_access('prod_return', 'edit')): ?>
                                                <a href="<?= base_url('ProdReturn/edit/'.$ret->id); ?>" class="btn btn-sm btn-warning ml-1" title="Edit">
                                                    <i class="fas fa-pencil-alt fa-fw"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php // Tombol Hapus hanya jika Draft ?>
                                                <?php if ($ret->status == 'Draft' && check_access('prod_return', 'delete')): ?>
                                                <a href="<?= base_url('ProdReturn/delete/'.$ret->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" title="Hapus" data-message="Yakin hapus Dokumen Draft <?= htmlspecialchars(addslashes($ret->return_number ?? '')); ?> ini?">
                                                    <i class="fas fa-trash fa-fw"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php // Tombol PDF selalu ada? Atau tergantung status? ?>
                                                 <a href="<?= base_url('ProdReturn/export_pdf/'.$ret->id); ?>" target="_blank" class="btn btn-sm btn-secondary ml-1" title="Export PDF">
                                                     <i class="fas fa-file-pdf fa-fw"></i>
                                                 </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                            <tr><td colspan="7" class="text-center text-muted py-3">Belum ada data Retur Material</td></tr> <?php // Colspan disesuaikan ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div></div><?php //$this->load->view('templates/footer_content'); ?>
        </div></div><a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <?php //$this->load->view('templates/logout_modal'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function() {

        // SweetAlert untuk konfirmasi hapus
        $('body').on('click', '.btn-delete', function(e) {
            e.preventDefault(); // Mencegah link langsung dieksekusi
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
                    // Tampilkan loading sebelum redirect
                    Swal.fire({
                        title: 'Menghapus...',
                        text: 'Mohon tunggu...',
                        didOpen: () => { Swal.showLoading() },
                        allowOutsideClick: false, // Mencegah user menutup
                        allowEscapeKey: false    // Mencegah user menutup
                    });
                    window.location.href = url; // Lanjutkan ke URL hapus
                }
            });
        });

        // Hapus class btn-load jika masih ada (sementara, untuk diagnosa)
        // Jika Anda punya script global untuk btn-load, pastikan ia menangani redirect
        // $('.btn-load').removeClass('btn-load');

    }); // Akhir $(document).ready()
    </script>

</body>
</html>