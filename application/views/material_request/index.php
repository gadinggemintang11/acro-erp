<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<style>
    .btn-xs { padding: .125rem .25rem; font-size: .75rem; line-height: 1.5; border-radius: .2rem; }
    .table th, .table td { vertical-align: middle; }
</style>

<div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-clipboard-list text-primary mr-2"></i><?= lang_line('material_request_title', $title); ?></h3>
                        <?php if (check_access('material_request', 'add')): ?>
                            <a href="<?= base_url('material_request/add'); ?>" class="btn btn-primary shadow-sm">
                                <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('material_request_create'); ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body py-2">
                            <form method="get" action="<?= base_url('material_request/index'); ?>" class="form-inline">
                                <div class="form-group mr-2 mb-2">
                                    <label for="start_date" class="mr-2"><?= lang_line('from_date'); ?>:</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['start_date'] ?? ''); ?>">
                                </div>
                                <div class="form-group mr-3 mb-2">
                                    <label for="end_date" class="mr-2"><?= lang_line('to_date'); ?>:</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['end_date'] ?? ''); ?>">
                                </div>
                                <div class="form-group mr-3 mb-2 flex-grow-1">
                                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="<?= lang_line('material_request_search_placeholder'); ?>" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                                </div>
                                <div class="form-group mb-2">
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter fa-sm mr-1"></i> <?= lang_line('common_filter'); ?></button>
                                    <a href="<?= base_url('material_request'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo fa-sm mr-1"></i> <?= lang_line('btn_reset'); ?></a>
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
                                            <th><?= lang_line('material_request_number'); ?></th>
                                            <th><?= lang_line('material_request_date'); ?></th>
                                            <th><?= lang_line('adjustment_created_by'); ?></th>
                                            <th><?= lang_line('label_status'); ?></th>
                                            <th width="150"><?= lang_line('label_action'); ?></th>
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
                                                    if ($mr->status == 'Draft')               $badge_class = 'warning';
                                                    if ($mr->status == 'Submitted')           $badge_class = 'info';
                                                    if ($mr->status == 'Approved Head Dept')  $badge_class = 'primary';
                                                    if ($mr->status == 'Endorsed')            $badge_class = 'primary';
                                                    if ($mr->status == 'Approved')            $badge_class = 'success';
                                                    if ($mr->status == 'Partial Issued')      $badge_class = 'warning';
                                                    if ($mr->status == 'Issued')              $badge_class = 'primary';
                                                    if ($mr->status == 'Received')            $badge_class = 'success';
                                                    if ($mr->status == 'Closed')              $badge_class = 'dark';
                                                ?>
                                                <span class="badge badge-<?= $badge_class; ?>"><?= htmlspecialchars($mr->status); ?></span>
                                            </td>
                                            <td>
                                                <a href="<?= base_url('material_request/detail/'.$mr->id); ?>" class="btn btn-sm btn-info" title="<?= lang_line('btn_detail'); ?>">
                                                    <i class="fas fa-eye"></i> <?= lang_line('btn_detail'); ?>
                                                </a>
                                                <?php if ($mr->status == 'Draft' && can_dept_access('material_request', 'delete', $mr->department_code ?? null)): ?>
                                                <a href="<?= base_url('material_request/delete_mr/'.$mr->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" data-message="<?= lang_line('material_request_delete_confirm'); ?>" title="<?= lang_line('btn_delete'); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                            <tr><td colspan="5" class="text-center text-muted py-3"><?= lang_line('material_request_no_data'); ?></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

    <?php // Modal "Buat MR Baru" (request_date+remarks) DIPINDAH ke halaman
          // tersendiri material_request/add.php (ERP Revision Notes, user
          // 2026-09-04 minta MRF ikut pola IRF: klik Create langsung dapat
          // form, bukan list). Validasi client-side-nya ikut pindah kesana. ?>

    <script>
    $(function() {

        // SweetAlert untuk konfirmasi hapus di tabel utama
        $('.btn-delete').on('click', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            const message = $(this).data('message') || '<?= lang_line("common_confirm_delete"); ?>';
            Swal.fire({
                title: '<?= lang_line("common_error"); ?>',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<?= lang_line("btn_delete"); ?>!',
                cancelButtonText: '<?= lang_line("btn_cancel"); ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: '<?= lang_line("material_request_loading"); ?>...', text: '<?= lang_line("common_processing"); ?>...', didOpen: () => { Swal.showLoading() }, allowOutsideClick: false });
                    window.location.href = url;
                }
            });
        });

    }); // Akhir $(function() {})
    </script>
