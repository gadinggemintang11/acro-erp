<?php
    $ctx = $ctx ?? ['user_id' => null, 'is_admin' => false];
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-folder-open text-warning mr-2"></i><?= htmlspecialchars($dept->description ?: $dept->code); ?>
            <small class="text-muted d-block" style="font-size: .55em; font-weight: normal;"><?= lang_line('doc_sharing_title', 'Document Sharing'); ?></small>
        </h3>
        <div>
            <?php if ($can_upload): ?>
            <button type="button" class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#uploadDocModal">
                <i class="fas fa-upload mr-1"></i> <?= lang_line('doc_sharing_btn_upload', 'Upload Dokumen'); ?>
            </button>
            <?php endif; ?>
            <a href="<?= base_url('document_sharing'); ?>" class="btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('doc_sharing_btn_back', 'Kembali'); ?>
            </a>
        </div>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <form method="get" action="<?= base_url('document_sharing/folder/' . rawurlencode($dept->code)); ?>" class="form-inline">
                <div class="form-group mr-2 mb-2 flex-grow-1">
                    <input type="text" name="search" class="form-control form-control-sm w-100" placeholder="<?= lang_line('doc_sharing_search_placeholder', 'Cari nama dokumen...'); ?>" value="<?= htmlspecialchars($search ?? ''); ?>">
                </div>
                <button type="submit" class="btn btn-sm btn-outline-primary mb-2"><i class="fas fa-search"></i> <?= lang_line('common_search', 'Cari'); ?></button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('doc_sharing_th_title', 'Judul / File'); ?></th>
                            <th width="100"><?= lang_line('doc_sharing_th_size', 'Ukuran'); ?></th>
                            <th width="160"><?= lang_line('doc_sharing_th_visibility', 'Akses'); ?></th>
                            <th width="150"><?= lang_line('doc_sharing_th_uploader', 'Diupload Oleh'); ?></th>
                            <th width="130"><?= lang_line('doc_sharing_th_date', 'Tanggal'); ?></th>
                            <th width="130" class="text-center"><?= lang_line('common_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($documents)): foreach ($documents as $doc): ?>
                        <?php
                            $is_owner = $doc->uploaded_by && (int) $doc->uploaded_by === (int) $ctx['user_id'];
                            $can_delete_row = $ctx['is_admin'] || $is_owner || check_access('document_sharing', 'delete');
                        ?>
                        <tr>
                            <td>
                                <i class="fas fa-file-alt text-secondary mr-1"></i>
                                <?= htmlspecialchars($doc->title); ?>
                                <?php if ($doc->title !== $doc->original_name): ?>
                                    <div class="small text-muted"><?= htmlspecialchars($doc->original_name); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= number_format(($doc->file_size ?: 0) / 1024, 1); ?> KB</td>
                            <td>
                                <?php if ($doc->visibility === 'private'): ?>
                                    <span class="badge badge-secondary"><i class="fas fa-lock mr-1"></i><?= lang_line('doc_sharing_visibility_private', 'Privat'); ?></span>
                                    <?php if ($is_owner || $ctx['is_admin']): ?><div class="small text-muted mt-1"><?= lang_line('doc_sharing_visibility_private_hint', 'Cuma Anda yang bisa lihat'); ?></div><?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-info"><i class="fas fa-users mr-1"></i><?= lang_line('doc_sharing_visibility_roles', 'Role Tertentu'); ?></span>
                                    <div class="small text-muted mt-1"><?= htmlspecialchars($doc->role_names ?: '-'); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= htmlspecialchars($doc->uploader_name ?: '-'); ?></td>
                            <td class="small"><?= $doc->uploaded_at ? date('d M Y H:i', strtotime($doc->uploaded_at)) : '-'; ?></td>
                            <td class="text-center">
                                <a href="<?= base_url('document_sharing/view/' . $doc->id); ?>" target="_blank" class="btn btn-sm btn-link text-info" title="<?= lang_line('doc_sharing_btn_view', 'Lihat'); ?>"><i class="fas fa-eye"></i></a>
                                <a href="<?= base_url('document_sharing/download/' . $doc->id); ?>" class="btn btn-sm btn-link text-primary" title="<?= lang_line('doc_sharing_btn_download', 'Download'); ?>"><i class="fas fa-download"></i></a>
                                <?php if ($can_delete_row): ?>
                                <a href="<?= base_url('document_sharing/delete/' . $doc->id); ?>" class="btn btn-sm btn-link text-danger btn-delete-doc" data-name="<?= htmlspecialchars($doc->title); ?>" title="<?= lang_line('doc_sharing_btn_delete', 'Hapus'); ?>"><i class="fas fa-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block text-secondary"></i>
                                <?= lang_line('doc_sharing_no_documents', 'Belum ada dokumen yang bisa Anda lihat di folder ini.'); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($can_upload): ?>
<!-- Modal Upload Dokumen -->
<div class="modal fade" id="uploadDocModal" tabindex="-1" aria-labelledby="uploadDocModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="uploadDocForm" method="post" action="<?= base_url('document_sharing/upload/' . rawurlencode($dept->code)); ?>" enctype="multipart/form-data">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="uploadDocModalLabel"><i class="fas fa-upload mr-2"></i><?= lang_line('doc_sharing_btn_upload', 'Upload Dokumen'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="doc_title"><?= lang_line('doc_sharing_lbl_title', 'Judul Dokumen'); ?></label>
                        <input type="text" name="title" id="doc_title" class="form-control" placeholder="<?= lang_line('doc_sharing_title_placeholder', '(kosongkan = pakai nama file)'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="document_file"><?= lang_line('doc_sharing_lbl_file', 'Pilih File'); ?> <span class="text-danger">*</span></label>
                        <input type="file" name="document_file" id="document_file" class="form-control-file" required>
                        <small class="form-text text-muted"><?= lang_line('doc_sharing_file_hint', 'Maks. 10MB. Format: PDF, Word, Excel, PowerPoint, gambar, ZIP/RAR, CSV, TXT.'); ?></small>
                    </div>
                    <hr>
                    <div class="form-group">
                        <label class="d-block"><?= lang_line('doc_sharing_lbl_visibility', 'Siapa yang boleh melihat dokumen ini?'); ?></label>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="vis_private" name="visibility" value="private" class="custom-control-input" checked>
                            <label class="custom-control-label" for="vis_private">
                                <i class="fas fa-lock mr-1"></i> <?= lang_line('doc_sharing_visibility_private', 'Privat'); ?>
                                <div class="small text-muted"><?= lang_line('doc_sharing_visibility_private_desc', 'Cuma Anda (dan Admin) yang bisa lihat/download.'); ?></div>
                            </label>
                        </div>
                        <div class="custom-control custom-radio mt-2">
                            <input type="radio" id="vis_roles" name="visibility" value="roles" class="custom-control-input">
                            <label class="custom-control-label" for="vis_roles">
                                <i class="fas fa-users mr-1"></i> <?= lang_line('doc_sharing_visibility_roles', 'Role Tertentu'); ?>
                                <div class="small text-muted"><?= lang_line('doc_sharing_visibility_roles_desc', 'Pilih role yang boleh lihat/download, lintas departemen manapun.'); ?></div>
                            </label>
                        </div>
                    </div>
                    <div class="form-group" id="roleSelectBox" style="display:none;">
                        <label><?= lang_line('doc_sharing_lbl_roles', 'Role yang diizinkan'); ?></label>
                        <div class="border rounded p-2" style="max-height: 220px; overflow-y: auto;">
                            <?php $cur_dept = null; foreach ($roles as $r): ?>
                                <?php if ($cur_dept !== $r->department): $cur_dept = $r->department; ?>
                                    <div class="small font-weight-bold text-muted mt-2 mb-1"><?= htmlspecialchars($cur_dept ?: '-'); ?></div>
                                <?php endif; ?>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="role_<?= $r->id ?>" name="role_ids[]" value="<?= $r->id ?>">
                                    <label class="custom-control-label small" for="role_<?= $r->id ?>"><?= htmlspecialchars($r->role_name) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload mr-1"></i> <?= lang_line('doc_sharing_btn_upload', 'Upload Dokumen'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel', 'Batalkan'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
$(function() {
    $('input[name="visibility"]').on('change', function() {
        $('#roleSelectBox').toggle($('#vis_roles').is(':checked'));
    });

    $('#uploadDocForm').on('submit', function(e) {
        if ($('#vis_roles').is(':checked') && $('input[name="role_ids[]"]:checked').length === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: '<?= lang_line("doc_sharing_pick_role_title", "Pilih Role"); ?>',
                text: '<?= lang_line("doc_sharing_pick_role_text", "Pilih minimal satu role, atau pilih Privat."); ?>'
            });
            return false;
        }
        $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> <?= lang_line("common_loading", "Memuat"); ?>...');
    });

    $('.btn-delete-doc').on('click', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const name = $(this).data('name');
        Swal.fire({
            title: '<?= lang_line("doc_sharing_confirm_delete_title", "Hapus Dokumen?"); ?>',
            text: name,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<?= lang_line("btn_delete", "Hapus"); ?>!',
            cancelButtonText: '<?= lang_line("btn_cancel", "Batalkan"); ?>'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = href;
        });
    });
});
</script>
