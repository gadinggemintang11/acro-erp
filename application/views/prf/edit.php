<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="mb-0"><?= lang_line('prf_edit_title', 'Edit Payment Request'); ?></h1>
        </div>
        <div class="col text-right">
            <a href="<?php echo base_url('prf/detail/' . $prf->id) . (!empty($admin_edit_mode) ? '?admin_edit=1' : ''); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?= lang_line('prf_back', 'Kembali'); ?>
            </a>
        </div>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <?php if (!empty($admin_edit_mode)): ?>
    <div class="alert alert-warning border-left-warning" role="alert">
        <h6 class="font-weight-bold mb-1"><i class="fas fa-user-shield mr-1"></i> <?= lang_line('prf_admin_banner_title', 'Mode Edit Admin'); ?></h6>
        <p class="mb-0 small">
            <?= sprintf(
                lang_line('prf_admin_banner_edit_text', 'Payment Request ini berstatus <strong>%s</strong>. Perubahan item/nominal di sini <strong>tidak mereset approval</strong> — status Payment Request tetap sama setelah disimpan, dan semuanya tercatat di Log Activity.'),
                htmlspecialchars($prf->status)
            ); ?>
        </p>
    </div>
    <?php endif; ?>

    <form action="<?php echo base_url('prf/update/' . $prf->id); ?>" method="POST" id="prf-form">
        <?php if (!empty($admin_edit_mode)): ?><input type="hidden" name="admin_edit" value="1"><?php endif; ?>
        <!-- Header Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="m-0"><?= lang_line('prf_section_info', 'Informasi Payment Request'); ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('prf_th_no', 'No. Payment Request'); ?></label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($prf->prf_number); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('prf_lbl_date', 'Tanggal Payment Request'); ?> *</label>
                            <input type="date" name="prf_date" class="form-control" value="<?php echo $prf->prf_date; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('prf_lbl_department', 'Departemen'); ?></label>
                            <input type="hidden" id="department_id" name="department_id" value="<?php echo $prf->department_id ?? ''; ?>">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(isset($department_name) ? $department_name : 'Unknown'); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('prf_lbl_creator', 'Pembuat'); ?></label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($prf->requester_name); ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= lang_line('prf_lbl_supplier', 'Supplier / Vendor'); ?> <small class="text-muted">(<?= lang_line('prf_optional', 'Opsional'); ?>)</small></label>
                            <select name="supplier_id" id="supplier_id" class="form-control">
                                <option value=""><?= lang_line('prf_no_supplier', '-- Tanpa Supplier / Vendor --'); ?></option>
                                <?php foreach (($suppliers ?? []) as $sup): ?>
                                    <option value="<?php echo $sup->id; ?>"
                                        data-bank-name="<?php echo htmlspecialchars($sup->bank_name ?? ''); ?>"
                                        data-bank-account="<?php echo htmlspecialchars($sup->bank_account ?? ''); ?>"
                                        data-bank-account-name="<?php echo htmlspecialchars($sup->bank_account_name ?? ''); ?>"
                                        <?php echo ((int)($prf->supplier_id ?? 0) === (int)$sup->id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sup->nama_supplier); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= lang_line('prf_lbl_bank_name', 'Nama Bank'); ?></label>
                            <input type="text" id="supplier_bank_name" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= lang_line('prf_lbl_bank_account', 'No. Rekening'); ?></label>
                            <input type="text" id="supplier_bank_account" class="form-control" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label><?= lang_line('prf_lbl_notes_optional', 'Catatan (Opsional)'); ?></label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="<?= lang_line('prf_ph_notes', 'Catatan tambahan...'); ?>"><?php echo htmlspecialchars($prf->notes ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Item Payment Request (manual / tarik PO / tarik AP Invoice) -->
        <?php $this->load->view('prf/_items_card'); ?>

        <!-- Action Buttons -->
        <div class="row">
            <div class="col">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= lang_line('prf_btn_save_changes', 'Simpan Perubahan'); ?>
                </button>
                <a href="<?php echo base_url('prf/detail/' . $prf->id) . (!empty($admin_edit_mode) ? '?admin_edit=1' : ''); ?>" class="btn btn-outline-secondary">
                    <?= lang_line('prf_btn_cancel', 'Batalkan'); ?>
                </a>
            </div>
        </div>
    </form>
</div>

<?php $this->load->view('prf/_items_script'); ?>
