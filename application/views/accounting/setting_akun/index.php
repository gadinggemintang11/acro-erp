<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    
    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Akun Otomatis</h6>
                </div>
                <form action="<?= base_url('settingakun/save'); ?>" method="POST">
                    <div class="card-body">
                        <p>Pilih akun COA yang sesuai untuk setiap transaksi otomatis berdasarkan mata uang. Akun yang dipilih harus berjenis **Detail (Postable)**.</p>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="settingTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="align-middle">Nama Setting</th>
                                        <?php foreach($currencies as $curr): ?>
                                            <th class="text-center"><?= $curr->code; ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($settings_grouped as $key => $setting): ?>
                                    <tr>
                                        <td class="align-middle">
                                            <strong><?= $setting['name']; ?></strong>
                                            <br><small class="text-muted"><?= $key; ?></small>
                                        </td>
                                        
                                        <?php foreach($currencies as $curr): 
                                            // Cek apakah setting untuk currency ini ada
                                            $current_setting = $setting['currencies'][$curr->code] ?? null;
                                            $current_account_id = $current_setting ? $current_setting->account_id : null;
                                        ?>
                                        <td style="min-width: 300px;">
                                            <?php if($current_setting): ?>
                                            <select name="setting[<?= $key; ?>][<?= $curr->code; ?>]" class="form-control form-control-sm select-akun">
                                                <option value="">-- Pilih Akun (<?= $curr->code; ?>) --</option>
                                                <?php foreach($accounts as $acc): ?>
                                                    <?php // Hanya tampilkan akun yang mata uangnya SAMA ?>
                                                    <?php if($acc->currency == $curr->code): ?> 
                                                    <option value="<?= $acc->id; ?>" <?= ($acc->id == $current_account_id) ? 'selected' : ''; ?>>
                                                        <?= $acc->account_code; ?> - <?= $acc->account_name; ?>
                                                    </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php else: ?>
                                                <small class="text-muted">(Tidak Berlaku)</small>
                                            <?php endif; ?>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select-akun').select2();
    });
</script>