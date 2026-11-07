<?php
$is_draft = (strtoupper(trim($header->status)) == 'DRAFT');
?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><?= sprintf(lang_line('bom_detail_title', 'Detail BOM - %s'), html_escape($header->fg_code)); ?></h1>
        <div>
            <a href="<?= base_url('bom'); ?>" class="btn btn-secondary btn-sm shadow-sm">
                <i class="fas fa-arrow-left fa-sm"></i> <?= lang_line('btn_back', 'Kembali'); ?>
            </a>
            <a href="<?= base_url('bom/export_pdf/' . $header->id); ?>" target="_blank" class="btn btn-dark btn-sm shadow-sm">
                <i class="fas fa-file-pdf fa-sm"></i> <?= lang_line('bom_detail_pdf', 'PDF'); ?>
            </a>
            <?php if ($is_draft && check_access('bom', 'edit')): ?>
                <a href="<?= base_url('bom/confirm_bom/' . $header->id); ?>" class="btn btn-success btn-sm shadow-sm btn-confirm"
                   data-message="<?= lang_line('bom_detail_activate_confirm', 'Anda yakin ingin mengaktifkan BOM ini?'); ?>">
                    <i class="fas fa-check fa-sm"></i> <?= lang_line('bom_detail_activate', 'Aktifkan BOM'); ?>
                </a>
            <?php elseif (!$is_draft && check_access('bom', 'edit')): ?>
                <a href="<?= base_url('bom/deactivate_bom/' . $header->id); ?>" class="btn btn-warning btn-sm shadow-sm btn-confirm"
                   data-message="<?= lang_line('bom_detail_to_draft_confirm', 'Kembalikan BOM ke status Draft?'); ?>">
                    <i class="fas fa-undo fa-sm"></i> <?= lang_line('bom_detail_to_draft', 'Ke Draft'); ?>
                </a>
            <?php endif; ?>
            <?php if ($is_draft && check_access('bom', 'delete')): ?>
                <a href="<?= base_url('bom/delete_bom/' . $header->id); ?>" class="btn btn-danger btn-sm shadow-sm btn-delete"
                   data-message="<?= lang_line('bom_detail_delete_confirm', 'Hapus BOM ini?'); ?>">
                    <i class="fas fa-trash fa-sm"></i> <?= lang_line('bom_detail_delete', 'Hapus'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Flashdata -->
    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('success'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
    </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <!-- BOM Info Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('bom_detail_info_title', 'Informasi BOM'); ?></h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr><td style="width: 150px;"><strong><?= lang_line('bom_detail_style_fg', 'Style / FG'); ?></strong></td><td>: <?= html_escape($header->fg_name); ?></td></tr>
                        <tr><td><strong><?= lang_line('bom_detail_fg_code', 'Kode FG'); ?></strong></td><td>: <?= html_escape($header->fg_code); ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr><td style="width: 150px;"><strong><?= lang_line('bom_detail_status', 'Status'); ?></strong></td><td>: <span class="badge <?= $is_draft ? 'badge-warning' : 'badge-success' ?>"><?= $header->status ?></span></td></tr>
                        <tr><td><strong><?= lang_line('bom_detail_revision', 'Revisi'); ?></strong></td><td>: <?= $header->revision ?></td></tr>
                        <tr><td><strong><?= lang_line('bom_detail_created', 'Dibuat'); ?></strong></td><td>: <?= $header->created_at ? date('d M Y H:i', strtotime($header->created_at)) : 'N/A' ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- KOMPONEN & DETAIL CARD - Side by side layout -->
    <div class="row">
        <!-- LEFT: Component Form -->
        <div class="col-lg-5">
            <?php if ($is_draft): ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="m-0"><i class="fas fa-plus mr-2"></i><?= lang_line('bom_detail_add_component', 'Tambah Komponen'); ?></h6>
                </div>
                <div class="card-body">
                    <form id="addComponentForm">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                        <div class="form-group">
                            <label><strong><?= lang_line('bom_detail_component_label', 'Komponen (Material / Semi-Finished)'); ?></strong></label>
                            <select class="form-control" id="component_search" name="component_id" required></select>
                        </div>
                        <div class="form-group">
                            <label><strong><?= lang_line('bom_detail_qty_required', 'Qty Dibutuhkan'); ?></strong></label>
                            <input type="number" step="0.0001" class="form-control" name="quantity_required" placeholder="0.00" required>
                        </div>
                        <div class="form-group">
                            <label><strong><?= lang_line('bom_detail_specification', 'Spesifikasi / Deskripsi'); ?></strong></label>
                            <textarea class="form-control" name="specification" rows="4" placeholder="<?= lang_line('bom_detail_spec_placeholder', 'Masukkan spesifikasi atau deskripsi komponen'); ?>"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-lg" id="btnAddComponent">
                            <i class="fas fa-save mr-2"></i> <?= lang_line('bom_detail_btn_add_item', 'Tambah Item'); ?>
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: Detail Tabs -->
        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-2">
                    <ul class="nav nav-tabs card-header-tabs" id="bomTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="tab-komponen-link" data-toggle="tab" href="#tab-komponen" role="tab">
                                <i class="fas fa-list mr-1"></i> <?= lang_line('bom_detail_tab_material', 'Material'); ?> <span class="badge badge-primary"><?= count($details) ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-spesifikasi-link" data-toggle="tab" href="#tab-spesifikasi" role="tab">
                                <i class="fas fa-file-alt mr-1"></i> <?= lang_line('bom_detail_tab_spec', 'Spesifikasi'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-dokumen-link" data-toggle="tab" href="#tab-dokumen" role="tab">
                                <i class="fas fa-paperclip mr-1"></i> <?= lang_line('bom_detail_tab_document', 'Dokumen'); ?> <span class="badge badge-secondary doc-count">0</span>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body tab-content" style="padding: 1rem 0.75rem;">

                    <!-- TAB 1: Komponen Material List -->
                    <div class="tab-pane fade show active" id="tab-komponen" role="tabpanel">
                        <?php if (empty($details)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity: 0.3;"></i>
                            <small><?= lang_line('bom_detail_no_components', 'Belum ada komponen'); ?></small>
                        </div>
                        <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php $no = 1; foreach ($details as $item): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div style="flex: 1; min-width: 0;">
                                        <h6 class="mb-1"><span class="badge badge-<?= $item->item_type == 'material' ? 'info' : 'secondary' ?>" style="margin-right: 5px;"><?= ucfirst($item->item_type) ?></span><?= html_escape($item->component_code) ?></h6>
                                        <small class="text-muted d-block"><strong><?= html_escape($item->component_name) ?></strong></small>
                                        <small class="text-muted d-block"><?= lang_line('bom_detail_qty_label', 'Qty'); ?>: <?= number_format($item->quantity_required, 4) ?> <?= html_escape($item->component_unit) ?></small>
                                    </div>
                                    <?php if ($is_draft): ?>
                                    <div class="ml-2">
                                        <a href="<?= base_url('bom/delete_item/' . $header->id . '/' . $item->id); ?>" class="btn btn-sm btn-outline-danger btn-delete" data-message="<?= lang_line('bom_detail_delete_comp_confirm', 'Hapus komponen ini?'); ?>"><i class="fas fa-trash"></i></a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- TAB 2: Spesifikasi -->
                    <div class="tab-pane fade" id="tab-spesifikasi" role="tabpanel">
                        <?php if (empty($details)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity: 0.3;"></i>
                            <small><?= lang_line('bom_detail_no_components', 'Belum ada komponen'); ?></small>
                        </div>
                        <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php $no = 1; foreach ($details as $item): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div style="flex: 1; min-width: 0;">
                                        <small class="text-muted d-block"><?= lang_line('bom_detail_no_prefix', 'No.'); ?> <?= $no ?></small>
                                        <h6 class="mb-1"><strong><?= html_escape($item->component_code) ?></strong></h6>
                                        <small class="text-muted d-block mb-2"><?= html_escape($item->component_name) ?></small>

                                        <!-- Display or Edit Mode -->
                                        <div class="spec-display-mode" data-detail-id="<?= $item->id ?>">
                                            <div class="spec-text" style="background: #f8f9fa; padding: 8px; border-radius: 4px; margin-bottom: 8px; min-height: 40px; font-size: 0.85rem; white-space: pre-wrap; word-wrap: break-word;">
                                                <?= html_escape($item->specification ?? '-') ?>
                                            </div>
                                        </div>

                                        <div class="spec-edit-mode" data-detail-id="<?= $item->id ?>" style="display: none;">
                                            <textarea class="form-control form-control-sm spec-textarea" data-detail-id="<?= $item->id ?>" rows="3" style="resize: vertical; font-size: 0.85rem; margin-bottom: 8px;"><?= html_escape($item->specification ?? '') ?></textarea>
                                            <div class="btn-group btn-group-sm" style="width: 100%;">
                                                <button type="button" class="btn btn-success btn-save-spec flex-fill" data-detail-id="<?= $item->id ?>" title="<?= lang_line('bom_detail_btn_save_spec', 'Simpan'); ?>"><i class="fas fa-save mr-1"></i><?= lang_line('bom_detail_btn_save_spec', 'Simpan'); ?></button>
                                                <button type="button" class="btn btn-secondary btn-cancel-spec flex-fill" data-detail-id="<?= $item->id ?>" title="<?= lang_line('bom_detail_btn_cancel_spec', 'Batal'); ?>"><i class="fas fa-times mr-1"></i><?= lang_line('bom_detail_btn_cancel_spec', 'Batal'); ?></button>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($is_draft): ?>
                                    <div class="ml-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-spec" data-detail-id="<?= $item->id ?>" title="<?= lang_line('bom_detail_edit_spec_title', 'Edit spesifikasi'); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php $no++; endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- TAB 3: Dokumen Pendukung -->
                    <div class="tab-pane fade" id="tab-dokumen" role="tabpanel">
                        <?php if ($is_draft && check_access('bom', 'edit')): ?>
                        <div class="mb-3 pb-3 border-bottom">
                            <form id="uploadDocForm" enctype="multipart/form-data">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                                <div class="form-group mb-2">
                                    <label class="mb-1"><small><strong><?= lang_line('bom_doc_description', 'Deskripsi'); ?></strong></small></label>
                                    <input type="text" name="description" class="form-control form-control-sm" placeholder="<?= lang_line('bom_doc_desc_placeholder', 'Nama/deskripsi dokumen'); ?>" style="font-size: 0.85rem;">
                                </div>
                                <div class="form-group mb-2">
                                    <label class="mb-1"><small><strong><?= lang_line('bom_doc_file_label', 'File (PDF/JPG/PNG)'); ?></strong></small></label>
                                    <input type="file" name="document" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.gif" required style="font-size: 0.85rem;">
                                </div>
                                <button type="button" class="btn btn-success btn-sm btn-block" id="btnUploadDoc">
                                    <i class="fas fa-upload mr-1"></i> <?= lang_line('bom_doc_btn_upload', 'Upload'); ?>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>

                        <div id="documentList"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>

<script>
const I18N_BOM = {
    complete_fields:   <?= json_encode(lang_line('bom_js_complete_fields', 'Silakan lengkapi semua field yang diperlukan')); ?>,
    btn_adding:        <?= json_encode(lang_line('bom_js_btn_adding', 'Menambah...')); ?>,
    comp_added:        <?= json_encode(lang_line('bom_js_comp_added', 'Komponen berhasil ditambahkan')); ?>,
    comp_add_failed:   <?= json_encode(lang_line('bom_js_comp_add_failed', 'Gagal menambah komponen')); ?>,
    comp_search_ph:    <?= json_encode(lang_line('bom_js_comp_search_ph', 'Cari komponen...')); ?>,
    doc_load_failed:   <?= json_encode(lang_line('bom_js_doc_load_failed', 'Gagal memuat dokumen')); ?>,
    swal_del_title:    <?= json_encode(lang_line('bom_js_swal_del_doc_title', 'Hapus Dokumen?')); ?>,
    swal_del_text:     <?= json_encode(lang_line('bom_js_swal_del_doc_text', 'Dokumen yang dihapus tidak dapat dikembalikan!')); ?>,
    swal_yes_delete:   <?= json_encode(lang_line('bom_js_swal_yes_delete', 'Ya, hapus!')); ?>,
    swal_cancel:       <?= json_encode(lang_line('bom_js_swal_cancel', 'Batal')); ?>,
    doc_deleted:       <?= json_encode(lang_line('bom_js_doc_deleted', 'Dokumen berhasil dihapus')); ?>,
    doc_del_failed:    <?= json_encode(lang_line('bom_js_doc_del_failed', 'Gagal menghapus dokumen')); ?>,
    uploading:         <?= json_encode(lang_line('bom_js_uploading', 'Uploading...')); ?>,
    upload_failed:     <?= json_encode(lang_line('bom_js_upload_failed', 'Upload gagal')); ?>,
    spec_saved:        <?= json_encode(lang_line('bom_js_spec_saved', 'Spesifikasi disimpan')); ?>,
    spec_save_failed:  <?= json_encode(lang_line('bom_js_spec_save_failed', 'Gagal menyimpan')); ?>,
    comp_deleted:      <?= json_encode(lang_line('bom_js_comp_deleted', 'Komponen berhasil dihapus')); ?>,
    no_docs:           <?= json_encode(lang_line('bom_doc_no_docs', 'Belum ada dokumen')); ?>,
    preview_prefix:    <?= json_encode(lang_line('bom_js_preview_prefix', 'Preview: ')); ?>,
    btn_save_spec:     <?= json_encode(lang_line('bom_detail_btn_save_spec', 'Simpan')); ?>,
    btn_add_item:      <?= json_encode(lang_line('bom_detail_btn_add_item', 'Tambah Item')); ?>,
    btn_upload:        <?= json_encode(lang_line('bom_doc_btn_upload', 'Upload')); ?>,
    btn_preview:       <?= json_encode(lang_line('bom_doc_btn_preview', 'Preview')); ?>,
    btn_delete:        <?= json_encode(lang_line('bom_doc_btn_delete', 'Hapus')); ?>
};

$(document).ready(function() {
    var BASE_URL = '<?= base_url() ?>';
    var BOM_ID = <?= $header->id ?>;
    var IS_DRAFT = <?= $is_draft ? 'true' : 'false' ?>;

    // Add Component via AJAX
    $('#addComponentForm').on('submit', function(e) {
        e.preventDefault();

        var $btn = $('#btnAddComponent');
        var $form = $(this);

        // Validate form
        if (!this.checkValidity()) {
            toastr.error(I18N_BOM.complete_fields);
            return false;
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> ' + I18N_BOM.btn_adding);

        $.ajax({
            url: BASE_URL + 'bom/add_item/' + BOM_ID,
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    toastr.success(I18N_BOM.comp_added);
                    $form[0].reset();
                    $('#component_search').val(null).trigger('change');
                    // Reload tabs to show new component
                    location.reload();
                } else {
                    toastr.error(res.message || I18N_BOM.comp_add_failed);
                }
            },
            error: function(xhr, status, error) {
                console.error('Add component error:', xhr.responseText);
                toastr.error(I18N_BOM.comp_add_failed);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i> ' + I18N_BOM.btn_add_item);
            }
        });
    });

    // Select2 for component search
    $('#component_search').select2({
        ajax: {
            url: BASE_URL + 'bom/search_components',
            dataType: 'json',
            delay: 300,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) {
                return { results: data.results };
            }
        },
        minimumInputLength: 2,
        placeholder: I18N_BOM.comp_search_ph
    });

    // Load documents on tab click
    $('#tab-dokumen-link').on('click', function() { loadDocuments(); });
    loadDocuments();

    function loadDocuments() {
        $.ajax({
            url: BASE_URL + 'bom/get_documents/' + BOM_ID,
            type: 'GET',
            dataType: 'json',
            timeout: 10000,
            success: function(res) {
                if (res.success) {
                    renderDocumentList(res.documents);
                    $('.doc-count').text(res.documents.length);
                } else {
                    toastr.error(I18N_BOM.doc_load_failed + ': ' + (res.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error loading documents:', { status, error, statusCode: xhr.status, responseText: xhr.responseText });
                toastr.error(I18N_BOM.doc_load_failed + ': ' + error);
            }
        });
    }

    function renderDocumentList(docs) {
        var html = '';

        if (docs.length === 0) {
            html += '<div class="text-center text-muted py-3"><i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity: 0.3;"></i><small>' + I18N_BOM.no_docs + '</small></div>';
        } else {
            html += '<div class="list-group list-group-flush">';
            $.each(docs, function(i, doc) {
                var fileExt = doc.file_name.split('.').pop().toUpperCase();
                var fileSize = (doc.file_size / 1024).toFixed(2) + ' KB';
                var uploadDate = new Date(doc.uploaded_at).toLocaleDateString('id-ID');
                var icon = doc.file_type.includes('image') ? 'fa-image' : 'fa-file-pdf';

                html += '<div class="list-group-item">';
                html += '<div class="d-flex justify-content-between align-items-start">';
                html += '<div style="flex: 1; min-width: 0;">';
                html += '<h6 class="mb-1"><i class="fas ' + icon + ' mr-2 text-info"></i><small><strong>' + doc.file_name + '</strong></small></h6>';
                html += '<small class="text-muted d-block">' + (doc.description || '-') + '</small>';
                html += '<small class="text-muted d-block">' + fileSize + ' | ' + doc.uploaded_by_name + ' (' + uploadDate + ')</small>';
                html += '</div>';
                html += '<div class="ml-2">';
                html += '<a href="#" data-doc-preview="' + doc.file_path + '" data-doc-name="' + doc.file_name + '" data-doc-type="' + doc.file_type + '" class="btn btn-sm btn-outline-info" title="' + I18N_BOM.btn_preview + '"><i class="fas fa-eye"></i></a>';
                if (IS_DRAFT) {
                    html += ' <button type="button" class="btn btn-sm btn-outline-danger btn-delete-doc" data-doc-id="' + doc.id + '" title="' + I18N_BOM.btn_delete + '"><i class="fas fa-trash"></i></button>';
                }
                html += '</div>';
                html += '</div>';
                html += '</div>';
            });
            html += '</div>';
        }

        $('#documentList').html(html);

        // Delete document handler
        $(document).off('click', '.btn-delete-doc').on('click', '.btn-delete-doc', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var docId = $(this).data('doc-id');
            var $btn = $(this);

            Swal.fire({
                title: I18N_BOM.swal_del_title,
                text: I18N_BOM.swal_del_text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: I18N_BOM.swal_yes_delete,
                cancelButtonText: I18N_BOM.swal_cancel,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#858796',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                    $.ajax({
                        url: BASE_URL + 'bom/delete_document/' + docId,
                        type: 'POST',
                        dataType: 'json',
                        data: { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' },
                        success: function(res) {
                            if (res.success) {
                                toastr.success(res.message || I18N_BOM.doc_deleted);
                                setTimeout(function() { loadDocuments(); }, 300);
                            } else {
                                toastr.error(res.message || I18N_BOM.doc_del_failed);
                                $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Delete document error:', xhr.responseText);
                            toastr.error(I18N_BOM.doc_del_failed);
                            $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                        }
                    });
                }
            });
        });
    }

    // Upload document
    $('#btnUploadDoc').on('click', function() {
        var $btn = $(this);
        var form = $('#uploadDocForm')[0];
        var formData = new FormData(form);

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> ' + I18N_BOM.uploading);

        $.ajax({
            url: BASE_URL + 'bom/upload_document/' + BOM_ID,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 30000,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#uploadDocForm')[0].reset();
                    setTimeout(function() { loadDocuments(); }, 500);
                } else {
                    toastr.error(res.message || I18N_BOM.upload_failed);
                }
            },
            error: function(xhr, status, error) {
                console.error('Upload error:', xhr.responseText);
                toastr.error(I18N_BOM.upload_failed + ': ' + error);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-upload mr-1"></i> ' + I18N_BOM.btn_upload);
            }
        });
    });

    // Edit specification - toggle edit mode
    $(document).on('click', '.btn-edit-spec', function() {
        var detailId = $(this).data('detail-id');
        $('.spec-display-mode[data-detail-id="' + detailId + '"]').hide();
        $('.spec-edit-mode[data-detail-id="' + detailId + '"]').show();
    });

    // Cancel edit
    $(document).on('click', '.btn-cancel-spec', function() {
        var detailId = $(this).data('detail-id');
        $('.spec-display-mode[data-detail-id="' + detailId + '"]').show();
        $('.spec-edit-mode[data-detail-id="' + detailId + '"]').hide();
    });

    // Save specification
    $(document).on('click', '.btn-save-spec', function() {
        var detailId = $(this).data('detail-id');
        var $textarea = $('.spec-textarea[data-detail-id="' + detailId + '"]');
        var specification = $textarea.val();

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: BASE_URL + 'bom/update_item/' + BOM_ID + '/' + detailId,
            type: 'POST',
            dataType: 'json',
            data: {
                '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>',
                specification: specification,
                quantity_required: 0
            },
            success: function(res) {
                // Update display text
                $('.spec-text[data-detail-id="' + detailId + '"]').html(specification || '-');
                // Toggle back to display mode
                $('.spec-display-mode[data-detail-id="' + detailId + '"]').show();
                $('.spec-edit-mode[data-detail-id="' + detailId + '"]').hide();
                toastr.success(I18N_BOM.spec_saved);
            },
            error: function() {
                toastr.error(I18N_BOM.spec_save_failed);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>' + I18N_BOM.btn_save_spec);
            }
        });
    });

    // Delete component from Material tab (AJAX) - identify by URL containing delete_item
    $(document).on('click', '#tab-komponen .btn-delete', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var deleteUrl = $btn.attr('href');

        if (confirm($(this).data('message'))) {
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: deleteUrl,
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    toastr.success(I18N_BOM.comp_deleted);
                    setTimeout(function() { location.reload(); }, 300);
                },
                error: function() {
                    // Fallback: jika AJAX gagal, gunakan redirect biasa
                    window.location.href = deleteUrl;
                }
            });
        }
    });

    // Document Preview Handler
    $(document).off('click', 'a[data-doc-preview]').on('click', 'a[data-doc-preview]', function(e) {
        e.preventDefault();
        var filePath = $(this).data('doc-preview');
        var fileName = $(this).data('doc-name');
        var fileType = $(this).data('doc-type');

        if (fileType.includes('image')) {
            $('#previewImage').attr('src', BASE_URL + filePath).show();
            $('#previewPdfContainer').hide();
        } else if (fileType.includes('pdf')) {
            $('#previewImage').hide();
            $('#previewPdfLink').attr('href', BASE_URL + filePath);
            $('#previewPdfContainer').show();
        }

        $('#docPreviewLabel').text(I18N_BOM.preview_prefix + fileName);
        $('#docPreviewModal').modal('show');
    });

    // Confirm handlers (untuk button Aktifkan/Ke Draft/Hapus BOM)
    $(document).on('click', '.btn-confirm', function(e) {
        e.preventDefault();
        if (confirm($(this).data('message'))) {
            window.location.href = $(this).attr('href');
        }
    });
});
</script>

<!-- Document Preview Modal -->
<div class="modal fade" id="docPreviewModal" tabindex="-1" role="dialog" aria-labelledby="docPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="docPreviewLabel"><?= lang_line('bom_doc_preview_title', 'Preview Dokumen'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center" style="max-height: 70vh; overflow: auto;">
                <img id="previewImage" src="" alt="Preview" class="img-fluid" style="max-width: 100%;">
                <div id="previewPdfContainer" style="display: none;">
                    <p class="text-muted"><i class="fas fa-file-pdf mr-2"></i><?= lang_line('bom_doc_pdf_file', 'File PDF'); ?></p>
                    <a id="previewPdfLink" href="#" class="btn btn-primary btn-sm" target="_blank">
                        <i class="fas fa-download mr-1"></i> <?= lang_line('bom_doc_download_open', 'Download & Open'); ?>
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('bom_doc_btn_close', 'Tutup'); ?></button>
            </div>
        </div>
    </div>
</div>
