
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="mb-0"><?= lang_line('prf_create_title', 'Buat Payment Request'); ?></h1>
        </div>
        <div class="col text-right">
            <a href="<?php echo base_url('prf'); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?= lang_line('prf_back', 'Kembali'); ?>
            </a>
        </div>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <form action="<?php echo base_url('prf/store'); ?>" method="POST" id="prf-form">
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
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($prf_number); ?>" readonly>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('prf_lbl_date', 'Tanggal Payment Request'); ?> *</label>
                            <input type="date" name="prf_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('prf_lbl_department', 'Departemen'); ?> *</label>
                            <?php if (count($dept_options ?? []) > 1): ?>
                            <!-- Departemen dipilih dari role user (Role Level tahap 3); menentukan siapa yang submit & approve. -->
                            <select id="department_id" name="department_id" class="form-control" required>
                                <?php foreach ($dept_options as $code => $name): ?>
                                <option value="<?= htmlspecialchars($code); ?>" <?= $code === ($department_id ?? null) ? 'selected' : ''; ?>><?= htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Nomor Payment Request final dibuat sesuai departemen saat disimpan.</small>
                            <?php else: ?>
                            <input type="hidden" id="department_id" name="department_id" value="<?php echo isset($department_id) ? htmlspecialchars($department_id) : ''; ?>">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(isset($department_name) ? $department_name : 'Unknown'); ?>" readonly>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('prf_lbl_creator', 'Pembuat'); ?></label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($this->session->userdata('fullname')); ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= lang_line('prf_lbl_supplier', 'Supplier / Vendor'); ?> <small class="text-muted">(<?= lang_line('prf_optional', 'Opsional'); ?>)</small></label>
                            <select name="supplier_id" id="supplier_id" class="form-control select2" style="width: 100%;">
                                <option value=""><?= lang_line('prf_no_supplier', '-- Tanpa Supplier / Vendor --'); ?></option>
                                <?php foreach (($suppliers ?? []) as $sup): ?>
                                    <option value="<?php echo $sup->id; ?>"
                                        data-bank-name="<?php echo htmlspecialchars($sup->bank_name ?? ''); ?>"
                                        data-bank-account="<?php echo htmlspecialchars($sup->bank_account ?? ''); ?>"
                                        data-bank-account-name="<?php echo htmlspecialchars($sup->bank_account_name ?? ''); ?>">
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

                <?php /*
                    Nama pemilik rekening dari Master Supplier. Inilah yang
                    dicetak di baris ACCOUNT pada PRF; sebelum 2026-10-02 nama
                    itu ditebak dari nama vendor. Kosong = cetakan jatuh kembali
                    ke nama vendor seperti perilaku lama.
                */ ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label><?= lang_line('prf_lbl_bank_account_name', 'Nama Akun'); ?></label>
                            <input type="text" id="supplier_bank_account_name" class="form-control" readonly
                                   placeholder="<?= lang_line('prf_bank_account_name_ph', 'Belum diisi di Master Supplier — cetakan memakai nama vendor'); ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label><?= lang_line('prf_lbl_notes_optional', 'Catatan (Opsional)'); ?></label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="<?= lang_line('prf_ph_notes', 'Catatan tambahan...'); ?>"></textarea>
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
                    <i class="fas fa-save"></i> <?= lang_line('prf_btn_save', 'Simpan Payment Request'); ?>
                </button>
                <a href="<?php echo base_url('prf'); ?>" class="btn btn-outline-secondary">
                    <?= lang_line('prf_btn_cancel', 'Batalkan'); ?>
                </a>
            </div>
        </div>
    </form>
</div>

<?php $this->load->view('prf/_items_script'); ?>
