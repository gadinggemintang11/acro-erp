<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('adjustment'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> <?= lang_line('adj_btn_back_list', 'Kembali ke Daftar'); ?></a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('adj_section_doc_info', 'Informasi Dokumen'); ?></h6>
                    <?php if($header->status == 'Draft'): ?>
                    <div>
                        <a href="<?= base_url('adjustment/edit/' . $header->id); ?>" class="btn btn-warning btn-sm" title="<?= lang_line('adj_btn_edit_tooltip', 'Edit'); ?>">
                            <i class="fas fa-pen"></i> <?= lang_line('adj_btn_edit', 'Edit'); ?>
                        </a>
                        <a href="<?= base_url('adjustment/delete/' . $header->id); ?>" class="btn btn-danger btn-sm" title="<?= lang_line('adj_btn_delete_tooltip', 'Hapus'); ?>" onclick="return confirm('<?= lang_line('adj_confirm_delete_draft', 'Anda yakin ingin menghapus dokumen DRAFT ini?'); ?>');">
                            <i class="fas fa-trash"></i> <?= lang_line('adj_btn_delete', 'Hapus'); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= lang_line('adj_label_doc_no', 'No. Dokumen'); ?>: <strong><?= $header->doc_no; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= lang_line('adj_label_doc_date', 'Tgl. Dokumen'); ?>: <strong><?= date('d M Y', strtotime($header->doc_date)); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= lang_line('adj_label_created_by', 'Dibuat Oleh'); ?>: <strong><?= $header->creator_name; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= lang_line('adj_label_status', 'Status'); ?>:
                            <?php
                                $badge = 'secondary';
                                if ($header->status == 'Draft') $badge = 'warning';
                                if ($header->status == 'Confirmed') $badge = 'success';
                                if ($header->status == 'Cancelled') $badge = 'danger';
                            ?>
                            <strong class="badge badge-<?= $badge; ?> p-2"><?= $header->status; ?></strong>
                        </li>
                        <?php if($header->status == 'Confirmed'): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= lang_line('adj_label_confirmed_by', 'Dikonfirmasi Oleh'); ?>: <strong><?= $header->confimer_name; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= lang_line('adj_label_confirmed_date', 'Tgl. Konfirmasi'); ?>: <strong><?= date('d M Y H:i', strtotime($header->updated_at)); ?></strong>
                        </li>
                        <?php endif; ?>
                        <li class="list-group-item">
                            <?= lang_line('adj_label_notes', 'Catatan'); ?>:
                            <p class="mb-0"><strong><?= $header->notes; ?></strong></p>
                        </li>
                    </ul>

                    <?php if($header->status == 'Draft'): ?>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <a href="<?= base_url('adjustment/cancel/' . $header->id); ?>" class="btn btn-danger btn-icon-split" onclick="return confirm('<?= lang_line('adj_confirm_cancel_doc', 'Anda yakin ingin membatalkan dokumen ini?'); ?>');">
                                <span class="icon text-white-50"><i class="fas fa-times"></i></span>
                                <span class="text"><?= lang_line('adj_btn_cancel_doc', 'Batalkan'); ?></span>
                            </a>
                            <a href="<?= base_url('adjustment/confirm/' . $header->id); ?>" class="btn btn-success btn-icon-split" onclick="return confirm(<?= json_encode(lang_line('adj_confirm_stock_confirm', "ANDA YAKIN INGIN KONFIRMASI?\nStok akan diperbarui dan tidak bisa diubah lagi.")); ?>);">
                                <span class="icon text-white-50"><i class="fas fa-check"></i></span>
                                <span class="text"><?= lang_line('adj_btn_confirm_stock', 'Konfirmasi Stok'); ?></span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('adj_section_detail_items', 'Detail Item Penyesuaian'); ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th><?= lang_line('adj_th_item', 'Item'); ?></th>
                                    <th><?= lang_line('adj_th_type', 'Tipe'); ?></th>
                                    <th><?= lang_line('adj_th_qty', 'Qty'); ?></th>
                                    <th><?= lang_line('adj_th_value', 'Nilai (Rp)'); ?></th>
                                    <th><?= lang_line('adj_th_batch', 'Batch (Material Out)'); ?></th>
                                    <th><?= lang_line('adj_th_notes', 'Keterangan'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($details as $d): ?>
                                    <tr class="<?= $d->adjustment_type == 'IN' ? 'table-success' : 'table-danger'; ?>">
                                        <td><?= $d->item_name; ?></td>
                                        <td><strong><?= $d->adjustment_type; ?></strong></td>
                                        <td class="text-right"><?= number_format($d->qty, 4); ?> <?= $d->item_unit; ?></td>
                                        <td class="text-right">
                                            <?php if($d->adjustment_type == 'IN'): ?>
                                                <?= number_format($d->unit_value, 2); ?>
                                            <?php else: ?>
                                                <small><?= lang_line('adj_from_batch', '(Dari Batch)'); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $d->stock_on_hand_id ? 'Batch ID: ' . $d->stock_on_hand_id : '-'; ?>
                                        </td>
                                        <td><?= $d->remarks; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Document Upload Section -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-file-upload"></i> <?= lang_line('adj_section_upload_doc', 'Upload Dokumen Pendukung'); ?></h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="document"><?= lang_line('adj_label_pick_file', 'Pilih File'); ?></label>
                                <input type="file" id="document" name="document" class="form-control-file"
                                       accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xlsx,.xls">
                                <small class="form-text text-muted">
                                    <?= lang_line('adj_file_format_hint', 'Format: PDF, JPG, PNG, GIF, DOC, DOCX, XLS, XLSX (Max 10 MB)'); ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="document_description"><?= lang_line('adj_label_desc', 'Deskripsi (Opsional)'); ?></label>
                                <input type="text" id="document_description" name="document_description" class="form-control"
                                       placeholder="<?= lang_line('adj_desc_placeholder', 'cth: Bukti penambahan stock dari audit'); ?>">
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-success" id="btn_upload_doc">
                        <i class="fas fa-upload"></i> <?= lang_line('adj_btn_upload', 'Upload Dokumen'); ?>
                    </button>

                    <div id="upload_progress" style="display: none; margin-top: 15px;">
                        <div class="progress">
                            <div id="progress_bar" class="progress-bar progress-bar-striped progress-bar-animated"
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                        <small class="text-muted d-block mt-2"><?= lang_line('adj_upload_in_progress', 'Upload in progress...'); ?></small>
                    </div>
                </div>
            </div>

            <!-- Daftar Dokumen yang Sudah Diupload -->
            <div id="documents_section" style="display: none;" class="card shadow mb-4">
                <div class="card-header py-3 bg-success text-white">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-file-check"></i> <?= lang_line('adj_section_uploaded_docs', 'Dokumen yang Sudah Diupload'); ?></h6>
                </div>
                <div class="card-body">
                    <ul id="documents_list" class="list-group"></ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const I18N_ADJ = {
    pick_file:        <?= json_encode(lang_line('adj_js_pick_file_first', 'Silakan pilih file terlebih dahulu')); ?>,
    upload_success:   <?= json_encode(lang_line('adj_js_upload_success', 'Dokumen berhasil diupload')); ?>,
    upload_failed:    <?= json_encode(lang_line('adj_js_upload_failed', 'Gagal upload dokumen:')); ?>,
    confirm_del_doc:  <?= json_encode(lang_line('adj_js_confirm_del_doc', 'Yakin hapus dokumen ini?')); ?>,
    del_doc_success:  <?= json_encode(lang_line('adj_js_del_doc_success', 'Dokumen berhasil dihapus')); ?>,
    del_doc_failed:   <?= json_encode(lang_line('adj_js_del_doc_failed', 'Gagal hapus dokumen')); ?>,
    uploaded:         <?= json_encode(lang_line('adj_uploaded_at', 'Uploaded:')); ?>
};

document.addEventListener('DOMContentLoaded', function() {
    const adjustment_id = '<?php echo $header->id; ?>';

    // Load documents list on page load
    loadDocumentsList(adjustment_id);

    // Upload button click handler
    const uploadBtn = document.getElementById('btn_upload_doc');
    if (uploadBtn) {
        uploadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handleUpload(adjustment_id);
        });
    }
});

function handleUpload(adjustment_id) {
    var fileInput = document.getElementById('document');
    var file = fileInput.files[0];

    if (!file) {
        alert(I18N_ADJ.pick_file);
        return;
    }

    var formData = new FormData();
    formData.append('document', file);
    formData.append('description', document.getElementById('document_description').value);

    document.getElementById('upload_progress').style.display = 'block';
    var progressBar = document.getElementById('progress_bar');
    progressBar.style.width = '0%';

    $.ajax({
        url: '<?php echo base_url("adjustment/upload_document/"); ?>' + adjustment_id,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        xhrFields: {
            withCredentials: true
        },
        xhr: function() {
            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    var percentComplete = (e.loaded / e.total) * 100;
                    progressBar.style.width = percentComplete + '%';
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            document.getElementById('upload_progress').style.display = 'none';
            progressBar.style.width = '0%';

            console.log('Upload response:', response);

            if (response && response.success) {
                alert(I18N_ADJ.upload_success);
                document.getElementById('document').value = '';
                document.getElementById('document_description').value = '';
                loadDocumentsList(adjustment_id);
            } else {
                alert('Error: ' + (response ? response.message : 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            document.getElementById('upload_progress').style.display = 'none';
            progressBar.style.width = '0%';
            console.log('Upload error:', xhr, status, error);
            alert(I18N_ADJ.upload_failed + ' ' + error + '\n' + xhr.responseText);
        }
    });
}

function loadDocumentsList(adjustment_id) {
    $.ajax({
        url: '<?php echo base_url("adjustment/ajax_get_documents/"); ?>' + adjustment_id,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data.length > 0) {
                var html = '';
                response.data.forEach(function(doc) {
                    var size_kb = (doc.file_size / 1024).toFixed(1);
                    html += '<li class="list-group-item">';
                    html += '<div class="d-flex justify-content-between align-items-start">';
                    html += '<div class="flex-grow-1">';
                    html += '<a href="' + '<?php echo base_url("adjustment/view_document/"); ?>' + doc.id + '" target="_blank" class="font-weight-bold text-primary">';
                    html += '<i class="fas fa-file"></i> ' + doc.file_name;
                    html += '</a>';
                    if (doc.description) {
                        html += '<p class="mb-1 text-muted small">' + doc.description + '</p>';
                    }
                    html += '<small class="text-muted">' + I18N_ADJ.uploaded + ' ' + doc.uploaded_at + '</small>';
                    html += '</div>';
                    html += '<div class="text-right">';
                    html += '<span class="badge badge-secondary">' + size_kb + ' KB</span> ';
                    html += '<button type="button" class="btn btn-sm btn-danger" onclick="deleteDoc(' + doc.id + ')">';
                    html += '<i class="fas fa-trash"></i></button>';
                    html += '</div>';
                    html += '</div>';
                    html += '</li>';
                });
                document.getElementById('documents_list').innerHTML = html;
                document.getElementById('documents_section').style.display = 'block';
            } else {
                document.getElementById('documents_section').style.display = 'none';
            }
        }
    });
}

window.deleteDoc = function(doc_id) {
    const adjustment_id = document.querySelector('[data-adjustment-id]')?.dataset.adjustmentId || '<?php echo $header->id; ?>';
    if (confirm(I18N_ADJ.confirm_del_doc)) {
        $.ajax({
            url: '<?php echo base_url("adjustment/delete_document/"); ?>' + doc_id,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(I18N_ADJ.del_doc_success);
                    loadDocumentsList(adjustment_id);
                } else {
                    alert(I18N_ADJ.del_doc_failed);
                }
            }
        });
    }
};
</script>
