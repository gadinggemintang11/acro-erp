<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    
    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Formulir Edit Akun</h6>
        </div>
        <div class="card-body">
            <form action="<?= base_url('coa/update/' . $account->id); ?>" method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Kode Akun *</label>
                            <input type="text" name="account_code" class="form-control" value="<?= set_value('account_code', $account->account_code); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nama Akun *</label>
                            <input type="text" name="account_name" class="form-control" value="<?= set_value('account_name', $account->account_name); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tipe Akun *</label>
                            <select name="account_type" class="form-control" required>
                                <option value="Aset" <?= set_select('account_type', 'Aset', $account->account_type == 'Aset'); ?>>Aset</option>
                                <option value="Liabilitas" <?= set_select('account_type', 'Liabilitas', $account->account_type == 'Liabilitas'); ?>>Liabilitas</option>
                                <option value="Ekuitas" <?= set_select('account_type', 'Ekuitas', $account->account_type == 'Ekuitas'); ?>>Ekuitas</option>
                                <option value="Pendapatan" <?= set_select('account_type', 'Pendapatan', $account->account_type == 'Pendapatan'); ?>>Pendapatan</option>
                                <option value="HPP" <?= set_select('account_type', 'HPP', $account->account_type == 'HPP'); ?>>HPP</option>
                                <option value="Biaya" <?= set_select('account_type', 'Biaya', $account->account_type == 'Biaya'); ?>>Biaya</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Mata Uang *</label>
                            <select name="currency" class="form-control" required>
                                <?php foreach($currencies as $curr): ?>
                                    <option value="<?= $curr->code; ?>" <?= set_select('currency', $curr->code, $account->currency == $curr->code); ?>>
                                        <?= $curr->code; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Hanya akun 'Header' yang boleh memiliki mata uang berbeda dari induknya.</small>
                        </div>
                    </div>
                </div>
                <div class="row">
                     <div class="col-md-6">
                        <div class="form-group">
                            <label>Induk Akun (Parent) *</label>
                            <select name="parent_id" class="form-control" required>
                                <option value="0" <?= set_select('parent_id', '0', $account->parent_id == 0); ?>>-- Akun Puncak (Tanpa Induk) --</option>
                                <?php foreach($header_accounts as $header): ?>
                                    <option value="<?= $header->id; ?>" <?= set_select('parent_id', $header->id, $account->parent_id == $header->id); ?>>
                                        <?= $header->account_code; ?> - <?= $header->account_name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                         <div class="form-group">
                            <label>Status *</label>
                            <select name="status" class="form-control" required>
                                <option value="active" <?= set_select('status', 'active', $account->status == 'active'); ?>>Active</option>
                                <option value="inactive" <?= set_select('status', 'inactive', $account->status == 'inactive'); ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                   <div class="col-md-6">
                        <div class="form-group">
                            <label>Jenis Akun *</label>
                            <select name="is_header" class="form-control" required>
                                <option value="0" <?= set_select('is_header', '0', $account->is_header == 0); ?>>Detail (Postable)</option>
                                <option value="1" <?= set_select('is_header', '1', $account->is_header == 1); ?>>Header (Grup)</option>
                            </select>
                            <small class="form-text text-muted">Akun 'Detail' bisa digunakan untuk jurnal. Akun 'Header' hanya untuk grouping.</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Posisi Saldo Normal *</label>
                            <select name="normal_balance" id="normal_balance" class="form-control" required>
                                <option value="Debit" <?= set_select('normal_balance', 'Debit', isset($account->normal_balance) && $account->normal_balance == 'Debit'); ?>>Debit</option>
                                <option value="Kredit" <?= set_select('normal_balance', 'Kredit', isset($account->normal_balance) && $account->normal_balance == 'Kredit'); ?>>Kredit</option>
                            </select>
                            <small class="form-text text-muted">Default akan ditentukan berdasarkan tipe akun (Aset/Biaya/HPP = Debit, lainnya = Kredit).</small>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="text-right">
                    <a href="<?= base_url('coa'); ?>" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Update Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    // Auto set normal balance based on tipe akun (edit)
    (function(){
        var selectTipe = document.querySelector('select[name="account_type"]');
        var normalSel = document.getElementById('normal_balance');
        function applyDefault(){
            if (!selectTipe || !normalSel) return;
            var tipe = selectTipe.value;
            var debitNormal = ['Aset','Biaya','HPP'];
            if (debitNormal.indexOf(tipe) !== -1) {
                normalSel.value = 'Debit';
            } else {
                normalSel.value = 'Kredit';
            }
        }
        if (selectTipe) selectTipe.addEventListener('change', applyDefault);
        // Do not overwrite existing selection if user already has one; only apply if empty
        if (!document.querySelector('select[name="normal_balance"]').value) applyDefault();
    })();
</script>