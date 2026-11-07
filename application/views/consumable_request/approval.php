<?php
    // "Developer Mode" atur-kolom (ERP Revision Notes, user 2026-09-03) —
    // pola PERSIS sama dgn IRF/PRF/ERF List & PR/PO Monitoring, lihat
    // [[developer_mode_feature]]. Tombolnya HARUS di LUAR `.table-responsive`
    // (bug ditemukan user: dropdown ke-crop kalau ditaruh di dalam `<th>`).
    $devmode_page_key = 'irf_approval';
    $col_defs = [
        'irf_no'      => lang_line('cra_th_irf_no'),
        'date'        => lang_line('cra_th_date'),
        'department'  => lang_line('cra_th_department'),
        'requestor'   => lang_line('cra_th_requestor'),
        'category'    => lang_line('cr_category', 'Kategori'),
        'status'      => lang_line('cra_th_status'),
        'hod_approved'=> lang_line('cra_th_hod_approved'),
    ];
    $col = [];
    foreach ($col_defs as $ck => $clabel) {
        $col[$ck] = ui_column_visible($devmode_page_key, $ck, $clabel, true);
    }
    $col_colspan = count($col_defs) + 1; // +1 utk kolom Aksi (selalu tampil)
    // Tombol & panel "Atur Kolom" HANYA muncul kalau user memilih mode
    // 'Atur Kolom' di banner Developer Mode (user 2026-09-06) — supaya
    // tidak bentrok dengan mode edit teks.
    $devmode_active = devmode_is('column');
?>
<!-- Begin Page Content -->
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-check-double text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>
        <div>
            <?php if ($can_head_dept): ?>
                <span class="badge badge-info p-2">
                    <i class="fas fa-user-shield mr-1"></i> <?= lang_line('cra_stage_hod'); ?>
                </span>
            <?php else: ?>
                <span class="badge badge-secondary p-2"><?= lang_line('cra_no_right'); ?></span>
            <?php endif; ?>
            <?php if ($devmode_active): ?>
            <div class="dropdown d-inline-block">
                <button class="btn btn-sm btn-outline-secondary" type="button" id="devmodeColBtn" data-toggle="dropdown" title="<?= htmlspecialchars(lang_line('devmode_col_manage_title', 'Atur kolom')); ?>">
                    <i class="fas fa-columns mr-1"></i> <?= lang_line('devmode_col_manage_btn', 'Atur Kolom'); ?>
                </button>
                <div class="dropdown-menu dropdown-menu-right p-2" style="min-width:220px;" id="devmodeColMenu">
                    <div class="small font-weight-bold text-muted mb-2"><?= lang_line('devmode_col_manage_heading', 'Tampilkan kolom'); ?></div>
                    <?php foreach ($col_defs as $ck => $clabel): ?>
                    <div class="custom-control custom-checkbox mb-1 text-left">
                        <input type="checkbox" class="custom-control-input devmode-col-toggle" id="devcol_<?= $ck ?>"
                               data-page="<?= $devmode_page_key ?>" data-column="<?= $ck ?>" <?= $col[$ck] ? 'checked' : '' ?>>
                        <label class="custom-control-label small" for="devcol_<?= $ck ?>"><?= htmlspecialchars($clabel) ?></label>
                    </div>
                    <?php endforeach; ?>
                    <div class="d-flex justify-content-end mt-2 pt-2 border-top" id="devmodeColActions" style="display:none !important;">
                        <button type="button" class="btn btn-sm btn-outline-secondary mr-1" id="devmodeColCancel"><?= lang_line('btn_cancel', 'Batalkan'); ?></button>
                        <button type="button" class="btn btn-sm btn-primary" id="devmodeColSave"><?= lang_line('devmode_col_save', 'Simpan Perubahan'); ?></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $cls): ?>
        <?php if ($this->session->flashdata($key)): ?>
        <div class="alert alert-<?= $cls; ?> alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata($key); ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="dataTable">
                    <thead class="thead-light text-center">
                        <tr>
                            <th class="devmode-col devmode-col-irf_no" <?= $col['irf_no'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['irf_no']) ?></th>
                            <th class="devmode-col devmode-col-date" <?= $col['date'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['date']) ?></th>
                            <th class="devmode-col devmode-col-department" <?= $col['department'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['department']) ?></th>
                            <th class="devmode-col devmode-col-requestor" <?= $col['requestor'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['requestor']) ?></th>
                            <th class="devmode-col devmode-col-category" <?= $col['category'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['category']) ?></th>
                            <th class="devmode-col devmode-col-status" <?= $col['status'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['status']) ?></th>
                            <th class="devmode-col devmode-col-hod_approved" <?= $col['hod_approved'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['hod_approved']) ?></th>
                            <th width="200"><?= lang_line('cra_th_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($requests)): foreach ($requests as $r): ?>
                        <tr class="text-center">
                            <td class="devmode-col devmode-col-irf_no font-weight-bold text-primary" <?= $col['irf_no'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($r->request_number); ?></td>
                            <td class="devmode-col devmode-col-date" <?= $col['date'] ? '' : 'style="display:none"' ?>><?= $r->request_date ? date('d M Y', strtotime($r->request_date)) : '-'; ?></td>
                            <td class="devmode-col devmode-col-department" <?= $col['department'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($r->department ?: '-'); ?></td>
                            <td class="devmode-col devmode-col-requestor text-left" <?= $col['requestor'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($r->requestor_name ?: ($r->created_by_name ?: '-')); ?></td>
                            <td class="devmode-col devmode-col-category" <?= $col['category'] ? '' : 'style="display:none"' ?>><span class="badge badge-<?= ($r->category ?? 'General') === 'Produksi' ? 'dark' : 'secondary' ?>"><?= htmlspecialchars($r->category ?? 'General') ?></span></td>
                            <td class="devmode-col devmode-col-status" <?= $col['status'] ? '' : 'style="display:none"' ?>>
                                <?php
                                    $cls = 'secondary';
                                    if ($r->status == 'Submitted') $cls = 'warning';
                                    // 2 status antara — muncul lagi sejak IRF kembali 3 stage (2026-09-10)
                                    elseif ($r->status == 'Approved Head Dept') $cls = 'info';
                                    elseif ($r->status == 'Endorsed') $cls = 'primary';
                                    elseif ($r->status == 'Approved') $cls = 'success';
                                    elseif ($r->status == 'Released') $cls = 'primary';
                                ?>
                                <span class="badge badge-<?= $cls; ?>"><?= htmlspecialchars($r->status); ?></span>
                                <?php if (property_exists($r, 'approval_route')): // jalur approval berdasarkan nilai (2026-09-15) ?>
                                    <div class="small text-muted mt-1">
                                        1 tahap (Head of Dept)
                                        <?php if ($r->total_value !== null): ?>· Rp <?= number_format((float) $r->total_value, 0, ',', '.'); ?><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="devmode-col devmode-col-hod_approved small text-left" <?= $col['hod_approved'] ? '' : 'style="display:none"' ?>>
                                <?= htmlspecialchars($r->hod_approver_name ?: '-'); ?><br>
                                <span class="text-muted"><?= $r->hod_approved_at ? date('d M Y H:i', strtotime($r->hod_approved_at)) : ''; ?></span>
                            </td>
                            <td>
                                <?php // ?from=approval -> detail ikut menampilkan tombol Approve (user 2026-09-17) ?>
                                <a href="<?= base_url('consumable_request/detail/'.$r->id.'?from=approval'); ?>" class="btn btn-sm btn-secondary" title="<?= lang_line('cra_tt_view_detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <?php if ($r->status == 'Submitted' && $can_head_dept): ?>
                                    <a href="<?= base_url('consumable_request/approve_hod/'.$r->id.'?redirect=approval'); ?>" class="btn btn-sm btn-success btn-confirm"
                                       data-message="<?= htmlspecialchars(sprintf(lang_line('cra_msg_approve_confirm'), $r->request_number)); ?>" title="<?= lang_line('cra_tt_approve'); ?>">
                                        <i class="fas fa-check"></i> <?= lang_line('cra_btn_approve'); ?>
                                    </a>
                                <?php endif; ?>

                                <?php // Stage 2 — Endorsement. Baru ada lagi sejak IRF dikembalikan
                                      // ke 3 stage (2026-09-10); sebelumnya HOD langsung menutup
                                      // dokumen jadi tombol ini tidak pernah dibutuhkan. ?>
                                <?php if ($r->status == 'Approved Head Dept' && $can_endorsement): ?>
                                    <a href="<?= base_url('consumable_request/approve_endorsement/'.$r->id.'?redirect=approval'); ?>" class="btn btn-sm btn-primary btn-confirm"
                                       data-message="<?= htmlspecialchars(sprintf(lang_line('cra_msg_endorse_confirm', 'Endorse Permintaan Barang %s?'), $r->request_number)); ?>" title="<?= lang_line('cra_tt_endorse', 'Endorse'); ?>">
                                        <i class="fas fa-thumbs-up"></i> <?= lang_line('cra_btn_endorse', 'Endorse'); ?>
                                    </a>
                                <?php endif; ?>

                                <?php if ($r->status == 'Endorsed' && $can_approval): ?>
                                    <a href="<?= base_url('consumable_request/approve_final/'.$r->id.'?redirect=approval'); ?>" class="btn btn-sm btn-success btn-confirm"
                                       data-message="<?= htmlspecialchars(sprintf(lang_line('cra_msg_approve_confirm'), $r->request_number)); ?>" title="<?= lang_line('cra_tt_approve'); ?>">
                                        <i class="fas fa-check-double"></i> <?= lang_line('cra_btn_approve_final', 'Approve Final'); ?>
                                    </a>
                                <?php endif; ?>

                                <?php
                                    // Return/Reject boleh oleh pemegang stage yang SEDANG bertugas.
                                    $can_action_this = ($r->status == 'Submitted'          && $can_head_dept)
                                                     || ($r->status == 'Approved Head Dept' && $can_endorsement)
                                                     || ($r->status == 'Endorsed'          && $can_approval);
                                ?>
                                <?php if ($can_action_this): ?>
                                    <button type="button" class="btn btn-sm btn-warning text-white btn-return"
                                            data-id="<?= $r->id; ?>" data-doc="<?= htmlspecialchars($r->request_number); ?>" title="<?= lang_line('cra_tt_return'); ?>">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger btn-reject"
                                            data-id="<?= $r->id; ?>" data-doc="<?= htmlspecialchars($r->request_number); ?>" title="<?= lang_line('cra_tt_reject'); ?>">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="<?= $col_colspan; ?>" class="text-center text-muted py-3"><?= lang_line('cra_no_pending'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
        // Kartu "Riwayat Approval Saya" (user 2026-09-06: halaman approval
        // "hanya menampilkan list daftar yang akan di approve, seharusnya di
        // bagian bawah ada riwayat yang user itu approve"). Partial bersama
        // dipakai SEMUA halaman approval — lihat templates/my_approval_history.php.
        // Dijaga isset(): view yang dipakai bersama halaman non-approval
        // (mis. prf/list_all juga jadi PRF List) tidak ikut menampilkannya.
        if (isset($my_approval_history)) {
            $this->load->view('templates/my_approval_history', [
                'history' => $my_approval_history,
                'route'   => $my_history_route ?? '',
            ]);
        }
    ?>
</div>

<!-- Modal Return to Draft -->
<div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="" id="formReturn">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            <input type="hidden" name="redirect" value="approval">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title"><i class="fas fa-undo mr-2"></i><?= str_replace('%s', '<span id="returnDocNumber"></span>', lang_line('cra_return_modal_title')); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted"><?= lang_line('cra_return_modal_info'); ?></p>
                    <div class="form-group mb-0">
                        <label><strong><?= lang_line('cra_lbl_return_reason'); ?></strong> <span class="text-danger">*</span></label>
                        <textarea name="return_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning text-white"><i class="fas fa-undo mr-1"></i> <?= lang_line('cra_btn_return'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('cra_btn_cancel'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reject -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="" id="formReject">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            <input type="hidden" name="redirect" value="approval">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i><?= str_replace('%s', '<span id="rejectDocNumber"></span>', lang_line('cra_reject_modal_title')); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small text-danger"><?= lang_line('cra_reject_modal_info'); ?></p>
                    <div class="form-group mb-0">
                        <label><strong><?= lang_line('cra_lbl_reject_reason'); ?></strong> <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times mr-1"></i> <?= lang_line('cra_btn_reject'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('cra_btn_cancel'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const I18N_CRA = {
  confirm_title: <?= json_encode(lang_line('cra_swal_confirm_title'), JSON_UNESCAPED_UNICODE); ?>,
  default_msg:   <?= json_encode(lang_line('cra_swal_default_msg'), JSON_UNESCAPED_UNICODE); ?>,
  yes:           <?= json_encode(lang_line('cra_swal_yes'), JSON_UNESCAPED_UNICODE); ?>,
  cancel:        <?= json_encode(lang_line('cra_swal_cancel'), JSON_UNESCAPED_UNICODE); ?>
};
$(document).ready(function() {
    $(document).on('click', '.btn-confirm', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const message = $(this).data('message') || I18N_CRA.default_msg;
        Swal.fire({
            title: I18N_CRA.confirm_title, text: message, icon: 'question',
            showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonColor: '#6c757d',
            confirmButtonText: I18N_CRA.yes, cancelButtonText: I18N_CRA.cancel
        }).then((result) => { if (result.isConfirmed) window.location.href = href; });
    });

    $(document).on('click', '.btn-return', function() {
        $('#returnDocNumber').text($(this).data('doc'));
        $('#formReturn').attr('action', '<?= base_url('consumable_request/return_to_draft/'); ?>' + $(this).data('id'));
        $('#formReturn textarea').val('');
        $('#returnModal').modal('show');
    });
    $(document).on('click', '.btn-reject', function() {
        $('#rejectDocNumber').text($(this).data('doc'));
        $('#formReject').attr('action', '<?= base_url('consumable_request/reject/'); ?>' + $(this).data('id'));
        $('#formReject textarea').val('');
        $('#rejectModal').modal('show');
    });
});
</script>

<?php if ($devmode_active): ?>
<script>
// Developer Mode — atur kolom Approval Permintaan Barang Queue (ERP Revision Notes, user
// 2026-09-03). Pola PERSIS sama dgn Daftar Permintaan Barang/Payment Request List/Reimburse Request List — lihat
// komentar di consumable_request/index.php utk penjelasan lengkap staged
// Save/Cancel.
$(document).ready(function() {
    var pending = {};

    $('#devmodeColMenu').on('click', function(e) { e.stopPropagation(); });

    function refreshActionBar() {
        var dirty = Object.keys(pending).length > 0;
        $('#devmodeColActions').css('display', dirty ? 'flex' : 'none');
    }

    $('.devmode-col-toggle').on('change', function() {
        var el = $(this);
        var key = el.data('column');
        var visible = el.is(':checked');
        $('.devmode-col-' + key).toggle(visible);
        pending[key] = visible ? 0 : 1;
        refreshActionBar();
    });

    $('#devmodeColCancel').on('click', function() {
        location.reload();
    });

    $('#devmodeColSave').on('click', function() {
        var keys = Object.keys(pending);
        if (keys.length === 0) return;

        var btn = $(this).prop('disabled', true);
        var page = '<?= $devmode_page_key ?>';
        var done = 0;
        var failed = false;

        keys.forEach(function(key) {
            $.post('<?= base_url("developer_mode/save_column") ?>', {
                page_key: page, column_key: key, is_hidden: pending[key]
            }, null, 'json').fail(function() {
                failed = true;
            }).always(function() {
                done++;
                if (done === keys.length) {
                    btn.prop('disabled', false);
                    if (failed) {
                        alert('<?= lang_line('devmode_save_err', 'Gagal menyimpan.'); ?>');
                    } else {
                        pending = {};
                        refreshActionBar();
                        $('#devmodeColBtn').dropdown('toggle');
                    }
                }
            });
        });
    });
});
</script>
<?php endif; ?>
