<?php $CI =& get_instance(); ?>

<style>
:root {
    --primary: #4F46E5;
    --primary-dark: #4338CA;
    --success: #10B981;
    --warning: #F59E0B;
    --danger: #EF4444;
    --info: #3B82F6;
    --dark: #1F2937;
    --light-bg: #F9FAFB;
    --card-bg: #FFFFFF;
    --text-primary: #111827;
    --text-secondary: #6B7280;
    --border: #E5E7EB;
}

.modern-container {
    padding: 2rem 1rem;
    max-width: 1400px;
    margin: 0 auto;
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.page-header-modern {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    color: white;
    box-shadow: 0 10px 40px rgba(79, 70, 229, 0.2);
}

.page-header-modern h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.page-header-modern p {
    opacity: 0.95;
    margin-bottom: 0;
}

.modern-card {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid var(--border);
    margin-bottom: 2rem;
}

.filter-section {
    display: flex;
    gap: 1rem;
    align-items: end;
    flex-wrap: wrap;
}

.form-group-modern {
    flex: 1;
    min-width: 200px;
}

.form-group-modern label {
    display: block;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.form-control-modern {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--border);
    border-radius: 12px;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.form-control-modern:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.btn-modern {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-modern.primary {
    background: var(--primary);
    color: white;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.btn-modern.primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
}

.btn-modern.secondary {
    background: var(--light-bg);
    color: var(--text-primary);
    border: 2px solid var(--border);
}

.btn-modern.secondary:hover {
    background: white;
    border-color: var(--text-secondary);
}

.btn-modern.info {
    background: var(--info);
    color: white;
}

.btn-modern.info:hover {
    background: #2563EB;
    transform: translateY(-2px);
}

.btn-modern.success {
    background: var(--success);
    color: white;
}

.btn-modern.success:hover {
    background: #059669;
    transform: translateY(-2px);
}

.export-buttons {
    display: block !important;
    margin-bottom: 1rem;
}

.export-buttons a {
    display: inline-flex;
    margin-right: 0.75rem;
}

.dropdown-modern {
    position: relative;
    display: inline-block;
}

.dropdown-content-modern {
    display: none;
    position: absolute;
    right: 0;
    background: white;
    min-width: 160px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    border-radius: 12px;
    z-index: 1000;
    overflow: hidden;
    margin-top: 0.5rem;
}

.dropdown-modern:hover .dropdown-content-modern {
    display: block;
}

.dropdown-item-modern {
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-primary);
    text-decoration: none;
    font-size: 0.875rem;
    transition: background 0.2s;
}

.dropdown-item-modern:hover {
    background: var(--light-bg);
    text-decoration: none;
    color: var(--primary);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card-modern {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 1.75rem;
    border: 1px solid var(--border);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.stat-card-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    transition: transform 0.6s ease;
    transform: translateX(-100%);
}

.stat-card-modern.primary::before { background: linear-gradient(90deg, transparent, var(--primary), transparent); }
.stat-card-modern.danger::before { background: linear-gradient(90deg, transparent, var(--danger), transparent); }
.stat-card-modern.success::before { background: linear-gradient(90deg, transparent, var(--success), transparent); }

.stat-card-modern:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
}

.stat-card-modern:hover::before {
    transform: translateX(100%);
}

.stat-layout {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon-modern {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.stat-icon-modern.primary { background: linear-gradient(135deg, rgba(79, 70, 229, 0.15), rgba(67, 56, 202, 0.25)); color: var(--primary); }
.stat-icon-modern.danger { background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(220, 38, 38, 0.25)); color: var(--danger); }
.stat-icon-modern.success { background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.25)); color: var(--success); }

.stat-content {
    flex: 1;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1;
}

.table-modern {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.table-modern thead th {
    background: var(--light-bg);
    padding: 1rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
    border-bottom: 2px solid var(--border);
    position: sticky;
    top: 0;
    z-index: 10;
}

.table-modern tbody tr {
    transition: background 0.2s;
}

.table-modern tbody tr:hover {
    background: rgba(79, 70, 229, 0.02);
}

.table-modern td {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--border);
    font-size: 0.875rem;
}

.table-modern .section-header-pendapatan {
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.08), rgba(79, 70, 229, 0.03));
    font-weight: 700;
    color: var(--primary);
}

.table-modern .section-header-biaya {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.08), rgba(239, 68, 68, 0.03));
    font-weight: 700;
    color: var(--danger);
}

.table-modern .subtotal-row {
    background: var(--light-bg);
    font-weight: 700;
}

.table-modern .result-row-profit {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.08));
    font-weight: 700;
    color: var(--success);
    font-size: 1.1rem;
}

.table-modern .result-row-loss {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.08));
    font-weight: 700;
    color: var(--danger);
    font-size: 1.1rem;
}

.btn-detail-modern {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: var(--info);
    color: white;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    font-size: 0.875rem;
}

.btn-detail-modern:hover {
    background: #2563EB;
    transform: scale(1.1);
    color: white;
    text-decoration: none;
}

.report-header-section {
    display: block !important;
    margin-bottom: 1.5rem;
}

.report-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--text-primary);
    display: block !important;
    margin: 0 !important;
}

@media (max-width: 768px) {
    .modern-container { padding: 1rem 0.5rem; }
    .page-header-modern { padding: 1.5rem; }
    .page-header-modern h1 { font-size: 1.5rem; }
    .stats-grid { grid-template-columns: 1fr; }
    .filter-section { flex-direction: column; align-items: stretch; }
}
</style>

<div class="modern-container">

    <!-- Page Header -->
    <div class="page-header-modern">
        <h1><i class="fas fa-chart-line me-2"></i><?= $title; ?></h1>
        <p><i class="fas fa-calendar me-2"></i>Periode: <?= date('d F Y', strtotime($start_date)); ?> - <?= date('d F Y', strtotime($end_date)); ?></p>
    </div>

    <!-- Filter Section -->
    <div class="modern-card">
        <form action="<?= current_url(); ?>" method="GET">
            <div class="filter-section">
                <div class="form-group-modern">
                    <label for="start_date">Tanggal Mulai</label>
                    <input type="date" class="form-control-modern" id="start_date" name="start_date" value="<?= $start_date; ?>">
                </div>
                <div class="form-group-modern">
                    <label for="end_date">Tanggal Selesai</label>
                    <input type="date" class="form-control-modern" id="end_date" name="end_date" value="<?= $end_date; ?>">
                </div>
                <button type="submit" class="btn-modern primary">
                    <i class="fas fa-filter"></i> Tampilkan
                </button>
                <a href="<?= current_url(); ?>" class="btn-modern secondary">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </div>
        </form>
    </div>
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card-modern primary">
            <div class="stat-layout">
                <div class="stat-icon-modern primary">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Pendapatan</div>
                    <div class="stat-value">Rp <?= number_format($report_data['total_pendapatan'], 0, ',', '.'); ?></div>
                </div>
            </div>
        </div>

        <div class="stat-card-modern danger">
            <div class="stat-layout">
                <div class="stat-icon-modern danger">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Biaya</div>
                    <div class="stat-value">Rp <?= number_format($report_data['total_biaya'], 0, ',', '.'); ?></div>
                </div>
            </div>
        </div>

        <?php
            $laba_class = $report_data['laba_bersih'] >= 0 ? 'success' : 'danger';
            $laba_text = $report_data['laba_bersih'] >= 0 ? 'Laba Bersih' : 'Rugi Bersih';
        ?>
        <div class="stat-card-modern <?= $laba_class; ?>">
            <div class="stat-layout">
                <div class="stat-icon-modern <?= $laba_class; ?>">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label"><?= $laba_text; ?></div>
                    <div class="stat-value">Rp <?= number_format($report_data['laba_bersih'], 0, ',', '.'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="modern-card">
        <?php
        $queryString = http_build_query([
            'start_date' => $start_date,
            'end_date' => $end_date,
        ]);
        ?>
        <div class="report-header-section" style="display: block !important;">
            <div class="export-buttons" style="display: block !important; margin-bottom: 1rem; clear: both;">
                <a href="<?= current_url() . '?' . $queryString . '&export=pdf'; ?>" target="_blank" class="btn-modern danger" style="display: inline-block; margin-right: 0.75rem;">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="<?= current_url() . '?' . $queryString . '&export=excel'; ?>" class="btn-modern success" style="display: inline-block;">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            </div>
            <h6 class="report-title" style="display: block !important; margin: 0; clear: both;">
                <i class="fas fa-file-invoice-dollar"></i> Laporan Laba Rugi
            </h6>
        </div>

        <div style="overflow-x: auto;">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Nama Akun</th>
                        <th style="text-align: right;">Saldo</th>
                        <th style="text-align: center; width: 80px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>

                        <?php
                        // ======================================================
                        // FUNGSI REKURSIF UNTUK MERENDER TREE
                        // ======================================================
                        // Fungsi ini akan dipanggil berulang kali untuk
                        // menampilkan akun dan sub-akunnya (children)
                        //
                        // @param array $account Akun yang akan dirender
                        // @param int $level Level indentasi
                        // @param object $CI Instance CodeIgniter
                        // ======================================================
                        function render_profit_loss_row($account, $level, $CI) {
                            
                            // 1. Tentukan padding (indentasi)
                            // Setiap level +1, kita tambahkan 20px padding
                            $padding = $level * 20;
                            $style = "padding-left: {$padding}px;";

                            // 2. Tentukan class (tebal untuk header)
                            $class = $account['is_header'] ? 'font-weight: 700; color: var(--text-primary);' : '';

                            // 3. Ambil saldo (sudah dihitung di Model)
                            $balance = $account['balance'];

                            // 4. Render baris (TR)
                            echo "<tr>";
                            // Kolom Nama Akun
                            echo "<td style='{$style} {$class}'>{$account['account_name']}</td>";
                            
                            // Kolom Saldo
                            echo "<td style='text-align: right;'>Rp ". number_format($balance, 0, ',', '.') ."</td>";

                            // Kolom Aksi (Drill Down)
                            echo "<td style='text-align: center;'>";
                            // Tampilkan tombol HANYA jika dia akun postable (bukan header)
                            // dan saldonya bukan 0 (opsional, tapi lebih rapi)
                            if ($account['is_header'] == 0 && $balance != 0) {
                                // Buat URL untuk drill-down ke Buku Besar
                                // Kita teruskan juga query string (filter tanggal)
                                $url = site_url('reports/general_ledger/' . $account['id'] . '?' . $_SERVER['QUERY_STRING']);
                                echo "<a href='{$url}' class='btn-detail-modern' title='Lihat Detail Buku Besar'><i class='fas fa-search'></i></a>";
                            }
                            echo "</td>";
                            echo "</tr>";

                            // 5. Panggil fungsi ini lagi untuk semua anak (CHILDREN)
                            if (isset($account['children'])) {
                                foreach ($account['children'] as $child) {
                                    // Level + 1 untuk indentasi lebih dalam
                                    render_profit_loss_row($child, $level + 1, $CI);
                                }
                            }
                        }
                        ?>

                        <tr class="section-header-pendapatan">
                            <td colspan="3">PENDAPATAN</td>
                        </tr>
                        <?php foreach ($report_data['pendapatan'] as $account) : ?>
                            <?php render_profit_loss_row($account, 0, $CI); ?>
                        <?php endforeach; ?>
                        <tr class="subtotal-row">
                            <td style="text-align: right;">TOTAL PENDAPATAN</td>
                            <td style="text-align: right;">
                                Rp <?= number_format($report_data['total_pendapatan'], 0, ',', '.'); ?>
                            </td>
                            <td></td>
                        </tr>

                        <tr><td colspan="3" style="border: none; padding: 0.5rem;"></td></tr>

                        <tr class="section-header-biaya">
                            <td colspan="3">BIAYA</td>
                        </tr>
                        <?php foreach ($report_data['biaya'] as $account) : ?>
                            <?php render_profit_loss_row($account, 0, $CI); ?>
                        <?php endforeach; ?>
                        <tr class="subtotal-row">
                            <td style="text-align: right;">TOTAL BIAYA</td>
                            <td style="text-align: right;">
                                Rp <?= number_format($report_data['total_biaya'], 0, ',', '.'); ?>
                            </td>
                            <td></td>
                        </tr>
                        
                        <tr><td colspan="3" style="border: none; padding: 0.5rem;"></td></tr>

                        <?php
                            $result_row_class = $report_data['laba_bersih'] >= 0 ? 'result-row-profit' : 'result-row-loss';
                        ?>
                        <tr class="<?= $result_row_class; ?>">
                            <td style="text-align: right; padding: 1.25rem 1rem;">
                                <?= $laba_text; ?>
                            </td>
                            <td style="text-align: right; padding: 1.25rem 1rem;">
                                Rp <?= number_format($report_data['laba_bersih'], 0, ',', '.'); ?>
                            </td>
                            <td></td>
                        </tr>

                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>