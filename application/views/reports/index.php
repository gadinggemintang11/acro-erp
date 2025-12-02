<style>
.report-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    border: 2px solid #E5E7EB;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    color: inherit;
    display: block;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.report-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.05), transparent);
    opacity: 0;
    transition: opacity 0.3s;
}

.report-card:hover {
    transform: translateY(-8px);
    border-color: #4F46E5;
    box-shadow: 0 12px 28px rgba(79, 70, 229, 0.15);
    text-decoration: none;
    color: inherit;
}

.report-card:hover::before {
    opacity: 1;
}

.report-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: 1.5rem;
    position: relative;
    z-index: 1;
    transition: all 0.3s;
}

.report-card:hover .report-icon {
    transform: scale(1.1) rotate(5deg);
}

.report-icon.primary { background: linear-gradient(135deg, rgba(79, 70, 229, 0.15), rgba(67, 56, 202, 0.25)); color: #4F46E5; }
.report-icon.success { background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.25)); color: #10B981; }
.report-icon.info { background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(37, 99, 235, 0.25)); color: #3B82F6; }
.report-icon.warning { background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(217, 119, 6, 0.25)); color: #F59E0B; }
.report-icon.danger { background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(220, 38, 38, 0.25)); color: #EF4444; }

.report-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 1;
}

.report-description {
    font-size: 0.875rem;
    color: #6B7280;
    margin-bottom: 0;
    position: relative;
    z-index: 1;
}

.page-header {
    background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    color: white;
    box-shadow: 0 10px 40px rgba(79, 70, 229, 0.2);
}

.page-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.page-header p {
    opacity: 0.95;
    margin-bottom: 0;
}
</style>

<div class="container-fluid" style="padding: 2rem 1rem; max-width: 1400px; margin: 0 auto;">
    
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-chart-line me-2"></i>Reports</h1>
        <p><i class="fas fa-info-circle me-2"></i>Pilih laporan yang ingin Anda lihat</p>
    </div>

    <!-- Report Cards -->
    <div class="row g-4">
        
        <!-- Profit & Loss -->
        <div class="col-lg-4 col-md-6">
            <a href="<?= base_url('reports/profit_loss'); ?>" class="report-card">
                <div class="report-icon primary">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="report-title">Laba Rugi</h3>
                <p class="report-description">Laporan pendapatan, biaya, dan laba/rugi periode tertentu</p>
            </a>
        </div>

        <!-- Balance Sheet -->
        <div class="col-lg-4 col-md-6">
            <a href="<?= base_url('reports/balance_sheet'); ?>" class="report-card">
                <div class="report-icon success">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <h3 class="report-title">Neraca</h3>
                <p class="report-description">Posisi aset, kewajiban, dan ekuitas perusahaan</p>
            </a>
        </div>

        <!-- Trial Balance -->
        <div class="col-lg-4 col-md-6">
            <a href="<?= base_url('reports/trial_balance'); ?>" class="report-card">
                <div class="report-icon info">
                    <i class="fas fa-list-alt"></i>
                </div>
                <h3 class="report-title">Neraca Saldo</h3>
                <p class="report-description">Saldo debit dan kredit semua akun pada periode tertentu</p>
            </a>
        </div>

        <!-- Cash Flow -->
        <div class="col-lg-4 col-md-6">
            <a href="<?= base_url('reports/cash_flow'); ?>" class="report-card">
                <div class="report-icon warning">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h3 class="report-title">Arus Kas</h3>
                <p class="report-description">Laporan arus kas masuk dan keluar perusahaan</p>
            </a>
        </div>

        <!-- Cash Flow Detail -->
        <div class="col-lg-4 col-md-6">
            <a href="<?= base_url('reports/cash_flow_detail'); ?>" class="report-card">
                <div class="report-icon danger">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h3 class="report-title">Detail Arus Kas</h3>
                <p class="report-description">Rincian transaksi arus kas dengan detail lengkap</p>
            </a>
        </div>

        <!-- General Ledger - Note: requires account_id parameter -->
        <div class="col-lg-4 col-md-6">
            <a href="<?= base_url('coa'); ?>" class="report-card">
                <div class="report-icon primary">
                    <i class="fas fa-book"></i>
                </div>
                <h3 class="report-title">Buku Besar</h3>
                <p class="report-description">Lihat Chart of Accounts untuk memilih akun buku besar</p>
            </a>
        </div>

    </div>

    <!-- Additional Info -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(59, 130, 246, 0.05)); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 16px; color: #3B82F6;">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Info:</strong> Semua laporan dapat diekspor ke format PDF atau Excel. Gunakan filter tanggal untuk menyesuaikan periode laporan.
            </div>
        </div>
    </div>

</div>
