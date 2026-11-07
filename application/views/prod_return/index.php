<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<style>
    .table th, .table td { vertical-align: middle; padding: 0.5rem; }
    .btn-xs { padding: .125rem .25rem; font-size: .75rem; line-height: 1.5; border-radius: .2rem; }
</style>

<div class="container-fluid">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-undo-alt text-warning mr-2"></i><?= htmlspecialchars($title); ?></h3>
                        <?php if (check_access('prod_return', 'add')): // Sesuaikan key permission jika perlu ?>
                            <a href="<?= base_url('ProdReturn/create') ?>" class="btn btn-warning shadow-sm"> <?php // Hapus btn-load ?>
                                <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('prod_return_create'); ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body py-2">
                            <form method="get" action="<?= base_url('ProdReturn/index'); ?>" class="form-inline flex-wrap">
                                <div class="form-group mr-3 mb-2 flex-grow-1">
                                    <label for="search" class="sr-only"><?= lang_line('btn_search'); ?></label>
                                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="<?= lang_line('prod_return_search_placeholder'); ?>" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                                </div>
                                <div class="form-group mb-2">
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search fa-sm mr-1"></i> <?= lang_line('btn_search'); ?></button>
                                    <a href="<?= base_url('ProdReturn'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo fa-sm mr-1"></i> <?= lang_line('btn_reset'); ?></a>
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
                                            <th><?= lang_line('prod_return_number'); ?></th>
                                            <th><?= lang_line('prod_return_date'); ?></th>
                                            <th><?= lang_line('prod_return_wo_number'); ?></th>
                                            <th><?= lang_line('prod_return_returned_by'); ?></th>
                                            <th><?= lang_line('prod_return_received_by'); ?></th>
                                            <th><?= lang_line('prod_return_status'); ?></th>
                                            <th width="150"><?= lang_line('label_action'); ?></th>
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
                                                <a href="<?= base_url('ProdReturn/delete/'.$ret->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" title="<?= lang_line('btn_delete'); ?>" data-message="<?= lang_line('prod_return_delete_confirm'); ?> <?= htmlspecialchars(addslashes($ret->return_number ?? '')); ?>?">
                                                    <i class="fas fa-trash fa-fw"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php // Tombol PDF selalu ada? Atau tergantung status? ?>
                                                 <a href="<?= base_url('ProdReturn/export_pdf/'.$ret->id); ?>" target="_blank" class="btn btn-sm btn-secondary ml-1" title="<?= lang_line('prod_return_export_pdf'); ?>">
                                                     <i class="fas fa-file-pdf fa-fw"></i>
                                                 </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                            <tr><td colspan="7" class="text-center text-muted py-3"><?= lang_line('prod_return_no_data'); ?></td></tr> <?php // Colspan disesuaikan ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

    <?php //$this->load->view('templates/logout_modal'); ?>

    <script>
    $(document).ready(function() {

        // SweetAlert untuk konfirmasi hapus
        $('body').on('click', '.btn-delete', function(e) {
            e.preventDefault(); // Mencegah link langsung dieksekusi
            const url = $(this).attr('href');
            const message = $(this).data('message') || '<?= lang_line('common_confirm_delete'); ?>';

            Swal.fire({
                title: '<?= lang_line('prod_return_delete_confirm_title'); ?>',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<?= lang_line('prod_return_confirm_delete'); ?>',
                cancelButtonText: '<?= lang_line('prod_return_confirm_cancel'); ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tampilkan loading sebelum redirect
                    Swal.fire({
                        title: '<?= lang_line('prod_return_deleting'); ?>',
                        text: '<?= lang_line('prod_return_deleting_message'); ?>',
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