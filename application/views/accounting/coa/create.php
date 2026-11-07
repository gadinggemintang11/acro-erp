<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('coa_form_create_title'); ?></h6>
        </div>
        <div class="card-body">
            <form action="<?= base_url('coa/store'); ?>" method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?= lang_line('coa_label_code'); ?></label>
                            <input type="text" name="account_code" class="form-control" value="<?= set_value('account_code'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?= lang_line('coa_label_name'); ?></label>
                            <input type="text" name="account_name" class="form-control" value="<?= set_value('account_name'); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?= lang_line('coa_label_type'); ?></label>
                            <select name="account_type" class="form-control" required>
                                <option value=""><?= lang_line('coa_select_type'); ?></option>
                                <option value="Aset" <?= set_select('account_type', 'Aset'); ?>><?= lang_line('coa_type_aset'); ?></option>
                                <option value="Liabilitas" <?= set_select('account_type', 'Liabilitas'); ?>><?= lang_line('coa_type_liabilitas'); ?></option>
                                <option value="Ekuitas" <?= set_select('account_type', 'Ekuitas'); ?>><?= lang_line('coa_type_ekuitas'); ?></option>
                                <option value="Pendapatan" <?= set_select('account_type', 'Pendapatan'); ?>><?= lang_line('coa_type_pendapatan'); ?></option>
                                <option value="HPP" <?= set_select('account_type', 'HPP'); ?>><?= lang_line('coa_type_hpp'); ?></option>
                                <option value="Biaya" <?= set_select('account_type', 'Biaya'); ?>><?= lang_line('coa_type_biaya'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?= lang_line('coa_label_currency'); ?></label>
                            <select name="currency" class="form-control" required>
                                <?php foreach($currencies as $curr): ?>
                                    <option value="<?= $curr->code; ?>" <?= set_select('currency', $curr->code, $curr->code == 'IDR'); ?>>
                                        <?= $curr->code; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted"><?= lang_line('coa_currency_help_create'); ?></small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?= lang_line('coa_label_parent'); ?></label>
                            <select name="parent_id" class="form-control" required>
                                <option value="0"><?= lang_line('coa_parent_no_parent'); ?></option>
                                <?php foreach($header_accounts as $header): ?>
                                    <option value="<?= $header->id; ?>" <?= set_select('parent_id', $header->id); ?>>
                                        <?= $header->account_code; ?> - <?= $header->account_name; ?> (<?= $header->currency; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                         <div class="form-group">
                            <label><?= lang_line('coa_label_status'); ?></label>
                            <select name="status" class="form-control" required>
                                <option value="active" <?= set_select('status', 'active'); ?>><?= lang_line('coa_badge_active'); ?></option>
                                <option value="inactive" <?= set_select('status', 'inactive'); ?>><?= lang_line('coa_badge_inactive'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                   <div class="col-md-6">
                        <div class="form-group">
                            <label><?= lang_line('coa_label_kind'); ?></label>
                            <select name="is_header" class="form-control" required>
                                <option value="0" <?= set_select('is_header', '0'); ?>><?= lang_line('coa_kind_detail'); ?></option>
                                <option value="1" <?= set_select('is_header', '1'); ?>><?= lang_line('coa_kind_header'); ?></option>
                            </select>
                            <small class="form-text text-muted"><?= lang_line('coa_kind_help'); ?></small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?= lang_line('coa_label_normal_balance'); ?></label>
                            <select name="normal_balance" id="normal_balance" class="form-control" required>
                                <option value="Debit" <?= set_select('normal_balance', 'Debit'); ?>><?= lang_line('acct_th_debit'); ?></option>
                                <option value="Kredit" <?= set_select('normal_balance', 'Kredit'); ?>><?= lang_line('acct_th_credit'); ?></option>
                            </select>
                            <small class="form-text text-muted"><?= lang_line('coa_normal_help'); ?></small>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="text-right">
                    <a href="<?= base_url('coa'); ?>" class="btn btn-secondary"><?= lang_line('acct_btn_cancel'); ?></a>
                    <button type="submit" class="btn btn-primary"><?= lang_line('coa_btn_save_account'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
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
        applyDefault();
    })();
</script>
