<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<style>
    .modern-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .modern-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
    }
    
    .modern-subtitle {
        font-size: 0.95rem;
        opacity: 0.95;
        margin-top: 0.5rem;
    }
    
    .modern-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        background: white;
    }
    
    .modern-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 1.5rem;
    }
    
    .modern-card-title {
        color: white;
        font-weight: 700;
        font-size: 1.2rem;
        margin: 0;
        display: flex;
        align-items: center;
        letter-spacing: 0.3px;
    }
    
    .info-box {
        background: linear-gradient(135deg, #f6f9fc 0%, #e9f0f5 100%);
        border-left: 4px solid #667eea;
        padding: 1.25rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    
    .info-box i {
        color: #667eea;
        font-size: 1.2rem;
        margin-right: 0.75rem;
    }
    
    .info-box p {
        margin: 0;
        color: #475569;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    #settingTable {
        font-size: 0.9rem;
    }
    
    #settingTable thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    #settingTable thead th {
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        padding: 1rem;
        border: none;
        vertical-align: middle;
    }
    
    #settingTable tbody tr {
        transition: all 0.3s ease;
    }
    
    #settingTable tbody tr:hover {
        background: linear-gradient(to right, #f8f9fe 0%, #ffffff 100%);
        transform: scale(1.001);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    #settingTable tbody td {
        padding: 1.25rem 1rem;
        vertical-align: middle;
        border-color: #e2e8f0;
    }
    
    #settingTable tbody td strong {
        color: #1e293b;
        font-size: 1rem;
    }
    
    #settingTable tbody td small {
        background: #f1f5f9;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        color: #64748b;
        font-family: 'Courier New', monospace;
    }
    
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .select2-container--default .select2-selection--single:focus,
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 34px;
        color: #334155;
        padding-left: 12px;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 34px;
    }
    
    .select2-dropdown {
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .modern-footer {
        background: #f8fafc;
        padding: 1.5rem;
        border-top: 2px solid #e2e8f0;
    }
    
    .btn-save {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .btn-save i {
        margin-right: 0.5rem;
    }
    
    .currency-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-block;
    }
    
    .table-wrapper {
        overflow-x: auto;
        border-radius: 8px;
        box-shadow: inset 0 0 0 1px #e2e8f0;
    }
    
    .not-applicable {
        text-align: center;
        color: #94a3b8;
        font-style: italic;
        font-size: 0.85rem;
    }
</style>

<div class="container-fluid">
    <!-- Modern Header -->
    <div class="modern-header">
        <div class="modern-title">
            <i class="fas fa-cogs mr-3"></i><?= $title; ?>
        </div>
        <div class="modern-subtitle">
            <i class="fas fa-info-circle mr-2"></i>Konfigurasi akun COA untuk transaksi otomatis berdasarkan mata uang
        </div>
    </div>
    
    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="card modern-card mb-4">
                <div class="modern-card-header">
                    <h6 class="modern-card-title">
                        <i class="fas fa-list-alt mr-2"></i>Pengaturan Akun Otomatis
                    </h6>
                </div>
                <form action="<?= base_url('settingakun/save'); ?>" method="POST">
                    <div class="card-body">
                        <div class="info-box">
                            <i class="fas fa-lightbulb"></i>
                            <p>Pilih akun COA yang sesuai untuk setiap transaksi otomatis berdasarkan mata uang. Akun yang dipilih harus berjenis <strong>Detail (Postable)</strong>.</p>
                        </div>
                        
                        <div class="table-wrapper">
                            <table class="table table-hover mb-0" id="settingTable">
                                <thead>
                                    <tr>
                                        <th class="align-middle" style="width: 25%;">
                                            <i class="fas fa-tag mr-2"></i>Nama Setting
                                        </th>
                                        <?php foreach($currencies as $curr): ?>
                                            <th class="text-center">
                                                <i class="fas fa-money-bill-wave mr-2"></i><?= $curr->code; ?>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($settings_grouped as $key => $setting): ?>
                                    <tr>
                                        <td class="align-middle">
                                            <div class="d-flex flex-column">
                                                <strong><?= $setting['name']; ?></strong>
                                                <small class="mt-2"><?= $key; ?></small>
                                            </div>
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
                                                <div class="not-applicable">
                                                    <i class="fas fa-minus-circle"></i> Tidak Berlaku
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modern-footer text-right">
                        <button type="submit" class="btn btn-save">
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
        // Initialize Select2 with custom styling
        $('.select-akun').select2({
            placeholder: "-- Pilih Akun --",
            allowClear: true,
            width: '100%',
            theme: 'default'
        });
        
        // Add animation on form submit
        $('form').on('submit', function() {
            $('.btn-save').html('<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...');
            $('.btn-save').prop('disabled', true);
        });
        
        // Add smooth scroll animation
        $('html, body').css('scroll-behavior', 'smooth');
    });
</script>