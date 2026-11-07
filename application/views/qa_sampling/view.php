<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?> — <?= htmlspecialchars($header->doc_no); ?></h1>
        <div>
            <a href="<?= base_url('qa_sampling'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> <?= lang_line('btn_back', 'Kembali'); ?></a>
            <a href="<?= base_url('qa_sampling/export_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf"></i> PDF</a>
        </div>
    </div>

    <?php if ($msg = $this->session->flashdata('success')): ?><div class="alert alert-success"><?= $msg; ?></div><?php endif; ?>
    <?php if ($msg = $this->session->flashdata('error')): ?><div class="alert alert-danger"><?= $msg; ?></div><?php endif; ?>

    <div class="card shadow mb-3">
        <div class="card-header py-3 d-flex justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('qas_section_doc_info', 'Info Dokumen'); ?></h6>
            <?php
            $badge = ['Draft'=>'secondary','Submitted'=>'info','Approved'=>'primary',
                      'Posted'=>'success','Closed'=>'dark','Cancelled'=>'danger'];
            $b = $badge[$header->status] ?? 'secondary';
            ?>
            <span class="badge badge-<?= $b; ?> p-2"><?= $header->status; ?></span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><strong><?= lang_line('qas_label_doc_no', 'No Dokumen'); ?>:</strong><br><?= htmlspecialchars($header->doc_no); ?></div>
                <div class="col-md-3"><strong><?= lang_line('qas_label_date', 'Tanggal'); ?>:</strong><br><?= date('d-m-Y', strtotime($header->doc_date)); ?></div>
                <div class="col-md-3"><strong><?= lang_line('qas_label_requester', 'Requester'); ?>:</strong><br><?= htmlspecialchars($header->requester_name ?? '-'); ?></div>
                <div class="col-md-3"><strong><?= lang_line('qas_label_dept', 'Departemen Requester'); ?>:</strong><br><?= htmlspecialchars($header->requester_dept ?? '-'); ?></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6"><strong><?= lang_line('qas_label_purpose_view', 'Tujuan'); ?>:</strong><br><?= htmlspecialchars($header->purpose); ?></div>
                <div class="col-md-6"><strong><?= lang_line('qas_label_notes_view', 'Catatan'); ?>:</strong><br><?= nl2br(htmlspecialchars($header->notes ?? '-')); ?></div>
            </div>
            <?php if ($header->approved_by): ?>
            <div class="row mt-2 text-muted small">
                <div class="col-md-4"><?= sprintf(lang_line('qas_approved_by_at', 'Approved by: %s @ %s'), htmlspecialchars($header->approver_name), $header->approved_at); ?></div>
                <?php if ($header->posted_at): ?><div class="col-md-4"><?= sprintf(lang_line('qas_posted_at', 'Posted: %s'), $header->posted_at); ?></div><?php endif; ?>
                <?php if ($header->closed_at): ?><div class="col-md-4"><?= sprintf(lang_line('qas_closed_at', 'Closed: %s'), $header->closed_at); ?></div><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-3">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?= lang_line('qas_section_detail_items', 'Detail Item'); ?></h6></div>
        <div class="card-body">
            <table class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr>
                        <th><?= lang_line('label_no', 'No'); ?></th>
                        <th><?= lang_line('qas_th_source', 'Sumber'); ?></th>
                        <th><?= lang_line('qas_th_type', 'Tipe'); ?></th>
                        <th><?= lang_line('qas_th_code', 'Kode'); ?></th>
                        <th><?= lang_line('qas_th_name', 'Nama'); ?></th>
                        <th><?= lang_line('qas_th_qty', 'Qty'); ?></th>
                        <th><?= lang_line('qas_th_uom', 'UoM'); ?></th>
                        <th><?= lang_line('qas_th_item_notes', 'Catatan Item'); ?></th>
                        <th><?= lang_line('qas_th_test_status', 'Status Test'); ?></th>
                        <th><?= lang_line('qas_th_test_result', 'Hasil Test'); ?></th>
                        <?php if (in_array($header->status, ['Posted','Closed'])): ?><th><?= lang_line('label_action', 'Aksi'); ?></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $no=1; foreach ($details as $d):
                        $sBadge = ['Pending'=>'warning','Pass'=>'success','Fail'=>'danger'];
                        $sb = $sBadge[$d->test_status] ?? 'secondary';
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><span class="badge badge-<?= $d->source==='wip'?'warning':'info'; ?>"><?= strtoupper($d->source); ?></span></td>
                        <td><?= htmlspecialchars($item_types[$d->item_type] ?? $d->item_type); ?></td>
                        <td><?= htmlspecialchars($d->item_code ?? '-'); ?></td>
                        <td><?= htmlspecialchars($d->item_name ?? '-'); ?>
                            <?php if ($d->source === 'wip' && $d->production_number): ?>
                                <br><small class="text-muted"><?= sprintf(lang_line('qas_prod_prefix', 'Prod: %s'), $d->production_number); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-right"><?= number_format($d->qty_sample, 4, ',', '.'); ?></td>
                        <td><?= htmlspecialchars($d->uom ?? '-'); ?></td>
                        <td><?= htmlspecialchars($d->notes ?? '-'); ?></td>
                        <td><span class="badge badge-<?= $sb; ?>"><?= $d->test_status; ?></span>
                            <?php if ($d->tested_at): ?><br><small class="text-muted"><?= $d->tester_name; ?><br><?= $d->tested_at; ?></small><?php endif; ?>
                        </td>
                        <td><?= nl2br(htmlspecialchars($d->test_result_notes ?? '-')); ?></td>
                        <?php if (in_array($header->status, ['Posted','Closed']) && check_access('qa_sampling','edit')): ?>
                        <td>
                            <button class="btn btn-primary btn-sm btn-test"
                                    data-id="<?= $d->id; ?>"
                                    data-status="<?= $d->test_status; ?>"
                                    data-notes="<?= htmlspecialchars($d->test_result_notes ?? '', ENT_QUOTES); ?>">
                                <i class="fas fa-edit"></i> <?= lang_line('qas_btn_test_result', 'Hasil'); ?>
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="mt-3">
                <?php if ($header->status === 'Submitted' && check_access('qa_sampling','edit')): ?>
                    <a href="<?= base_url('qa_sampling/approve/'.$header->id); ?>" class="btn btn-primary"
                       onclick="return confirm('<?= lang_line('qas_confirm_approve', 'Approve dokumen ini?'); ?>')"><i class="fas fa-check"></i> <?= lang_line('qas_btn_approve', 'Approve'); ?></a>
                    <a href="<?= base_url('qa_sampling/edit/'.$header->id); ?>" class="btn btn-warning"><i class="fas fa-edit"></i> <?= lang_line('qas_btn_edit', 'Edit'); ?></a>
                <?php endif; ?>
                <?php if ($header->status === 'Approved' && check_access('qa_sampling','edit')): ?>
                    <a href="<?= base_url('qa_sampling/post_sampling/'.$header->id); ?>" class="btn btn-success"
                       onclick="return confirm('<?= lang_line('qas_confirm_post', 'Post sampling? Stock akan dikurangi.'); ?>')">
                       <i class="fas fa-paper-plane"></i> <?= lang_line('qas_btn_post', 'Post (Kurangi Stock)'); ?>
                    </a>
                <?php endif; ?>
                <?php if (in_array($header->status, ['Draft','Submitted','Approved']) && check_access('qa_sampling','edit')): ?>
                    <a href="<?= base_url('qa_sampling/cancel/'.$header->id); ?>" class="btn btn-danger"
                       onclick="return confirm('<?= lang_line('qas_confirm_cancel_doc', 'Cancel dokumen ini?'); ?>')"><i class="fas fa-ban"></i> <?= lang_line('qas_btn_cancel_doc', 'Cancel'); ?></a>
                <?php endif; ?>
            </div>

            <?php if (!empty($counts) && (int)$counts->total > 0): ?>
            <div class="text-muted small mt-3">
                <?= sprintf(lang_line('qas_counts_summary', 'Total item: %d &middot; Pending: %d &middot; Pass: %d &middot; Fail: %d'),
                    $counts->total, $counts->pending, $counts->pass_cnt, $counts->fail_cnt); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal hasil test -->
<div class="modal fade" id="modalTest" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form id="frmTest">
        <div class="modal-header">
            <h5 class="modal-title"><?= lang_line('qas_modal_test_title', 'Update Hasil Test'); ?></h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="t_detail_id">
            <div class="form-group">
                <label><?= lang_line('qas_modal_test_status', 'Status'); ?></label>
                <select id="t_status" class="form-control">
                    <option value="Pending">Pending</option>
                    <option value="Pass">Pass</option>
                    <option value="Fail">Fail</option>
                </select>
            </div>
            <div class="form-group">
                <label><?= lang_line('qas_modal_test_notes', 'Catatan Hasil Test'); ?></label>
                <textarea id="t_notes" class="form-control" rows="4"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel', 'Batal'); ?></button>
            <button type="submit" class="btn btn-primary"><?= lang_line('btn_save', 'Simpan'); ?></button>
        </div>
    </form>
</div></div></div>

<script>
const I18N_QAS_VIEW = {
    save_failed: <?= json_encode(lang_line('qas_js_save_failed', 'Gagal simpan.')); ?>
};
$(function() {
    $('.btn-test').click(function() {
        $('#t_detail_id').val($(this).data('id'));
        $('#t_status').val($(this).data('status'));
        $('#t_notes').val($(this).data('notes'));
        $('#modalTest').modal('show');
    });
    $('#frmTest').submit(function(e) {
        e.preventDefault();
        $.post('<?= base_url('qa_sampling/update_test'); ?>', {
            detail_id: $('#t_detail_id').val(),
            status: $('#t_status').val(),
            notes: $('#t_notes').val()
        }, function(res) {
            if (res.success) location.reload();
            else alert(res.message || I18N_QAS_VIEW.save_failed);
        }, 'json');
    });
});
</script>
