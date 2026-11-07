<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-cogs text-primary mr-2"></i><?= lang_line('bom_title', $title); ?></h3>
        <?php if (check_access('bom', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addBomModal">
                <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('bom_create'); ?>
            </button>
        <?php endif; ?>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata('success'); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata('error'); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('warning')): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata('warning'); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <form method="get" action="<?= base_url('bom/index'); ?>" class="form-inline">
                <div class="form-group mr-3 mb-2 flex-grow-1">
                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="<?= lang_line('bom_search_placeholder'); ?>" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search fa-sm mr-1"></i> <?= lang_line('btn_search'); ?></button>
                    <a href="<?= base_url('bom'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo fa-sm mr-1"></i> <?= lang_line('btn_reset'); ?></a>
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
                            <th><?= lang_line('bom_code'); ?></th>
                            <th><?= lang_line('bom_name'); ?></th>
                            <th><?= lang_line('bom_revision'); ?></th>
                            <th><?= lang_line('bom_status'); ?></th>
                            <th width="150"><?= lang_line('label_action'); ?></th>
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
                                <a href="<?= base_url('bom/detail/'.$bom->id); ?>" class="btn btn-sm btn-info" title="<?= lang_line('bom_detail'); ?>">
                                    <i class="fas fa-list fa-fw"></i> <?= lang_line('bom_detail'); ?>
                                </a>
                                <?php if ($bom->status == 'Draft' && check_access('bom', 'delete')): ?>
                                <a href="<?= base_url('bom/delete_bom/'.$bom->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" title="<?= lang_line('btn_delete'); ?>" data-message="<?= str_replace('{0}', htmlspecialchars($bom->fg_name), lang_line('bom_delete_confirm')); ?>">
                                    <i class="fas fa-trash fa-fw"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-3"><?= lang_line('no_data'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="addBomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="createBomForm" method="post" action="<?= base_url('bom/create_header'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i><?= lang_line('bom_create'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="fg_id_selector"><?= lang_line('fg_name'); ?></label>
                        <select name="fg_id" id="fg_id_selector" class="form-control" required>
                            <option value="">-- <?= lang_line('select_option'); ?> --</option>
                            <?php if(!empty($available_fgs)): foreach($available_fgs as $fg): ?>
                                <option value="<?= $fg->id; ?>">
                                    <?= htmlspecialchars($fg->nama_barang . ' (' . $fg->kode_barang . ')'); ?>
                                </option>
                            <?php endforeach; else: ?>
                                <option value="" disabled>-- <?= lang_line('all_styles_have_bom'); ?> --</option>
                            <?php endif; ?>
                        </select>
                        <div class="invalid-feedback"><?= lang_line('fg_required'); ?></div>
                        <small class="form-text text-muted"><?= lang_line('bom_only_styles_without'); ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" <?= empty($available_fgs) ? 'disabled' : ''; ?>>
                        <i class="fas fa-arrow-right mr-1"></i> <?= lang_line('continue'); ?>
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    
    // Validasi Client-Side sebelum Submit Form Header BOM
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
            event.preventDefault();
            Swal.fire({
                title: 'Input Tidak Lengkap',
                text: 'Harap pilih Style (Finished Good) terlebih dahulu.',
                icon: 'warning',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            return false;
        }

        // Jika valid, nonaktifkan tombol dan biarkan submit
        console.log("Form data seems valid. Submitting form...");
        $(this).find('button[type="submit"]').prop('disabled', true)
               .html('<i class="fas fa-spinner fa-spin mr-1"></i> Memproses...');
    });

    // SweetAlert untuk konfirmasi hapus
    $('body').on('click', '.btn-delete', function(e) {
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
                Swal.fire({
                    title: 'Menghapus...',
                    text: 'Mohon tunggu...',
                    didOpen: () => {
                        Swal.showLoading()
                    },
                    allowOutsideClick: false
                });
                window.location.href = url;
            }
        });
    });

});
</script>