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
    max-width: 1600px;
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

.btn-modern.danger {
    background: var(--danger);
    color: white;
}

.btn-modern.danger:hover {
    background: #DC2626;
    transform: translateY(-2px);
}

.export-buttons {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
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
.stat-card-modern.info::before { background: linear-gradient(90deg, transparent, var(--info), transparent); }
.stat-card-modern.success::before { background: linear-gradient(90deg, transparent, var(--success), transparent); }
.stat-card-modern.danger::before { background: linear-gradient(90deg, transparent, var(--danger), transparent); }

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
.stat-icon-modern.info { background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(37, 99, 235, 0.25)); color: var(--info); }
.stat-icon-modern.success { background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.25)); color: var(--success); }
.stat-icon-modern.danger { background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(220, 38, 38, 0.25)); color: var(--danger); }

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

.table-modern .section-header {
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.08), rgba(79, 70, 229, 0.03));
    font-weight: 700;
    color: var(--primary);
}

.table-modern .subtotal-row {
    background: var(--light-bg);
    font-weight: 700;
}

.table-modern tfoot td {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    font-weight: 700;
    font-size: 1rem;
    padding: 1.25rem 1rem;
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

.balance-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 2rem;
}

@media (max-width: 768px) {
    .modern-container { padding: 1rem 0.5rem; }
    .page-header-modern { padding: 1.5rem; }
    .page-header-modern h1 { font-size: 1.5rem; }
    .stats-grid { grid-template-columns: 1fr; }
    .balance-section { grid-template-columns: 1fr; }
    .filter-section { flex-direction: column; align-items: stretch; }
}
</style>

<div class="modern-container">

    <!-- Page Header -->
    <div class="page-header-modern">
        <h1><i class="fas fa-balance-scale me-2"></i><?= $title; ?></h1>
        <p><i class="fas fa-calendar me-2"></i>Per Tanggal: <?= date('d F Y', strtotime($as_of_date)); ?></p>
    </div>

    <!-- Filter Section -->
    <div class="modern-card">
        <form action="<?= current_url(); ?>" method="GET">
            <div class="filter-section">
                <div class="form-group-modern">
                    <label for="as_of_date">Per Tanggal (As of Date)</label>
                    <input type="date" class="form-control-modern" id="as_of_date" name="as_of_date" value="<?= $as_of_date; ?>">
                </div>
                <button type="submit" class="btn-modern primary">
                    <i class="fas fa-filter"></i> Tampilkan
                </button>
                <a href="<?= current_url(); ?>" class="btn-modern secondary">
                    <i class="fas fa-redo"></i> Reset
                </a>
                <?php
                $queryString = http_build_query([
                    'as_of_date' => $as_of_date,
                ]);
                ?>
                <div class="export-buttons">
                    <a href="<?= current_url() . '?' . $queryString . '&export=pdf'; ?>" target="_blank" class="btn-modern danger">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="<?= current_url() . '?' . $queryString . '&export=excel'; ?>" class="btn-modern success">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                </div>
            </div>
        </form>
    </div>
    <?php
        $total_aset = $report_data['total_aset'];
        $total_liabilitas = $report_data['total_liabilitas'];
        $total_ekuitas = $report_data['total_ekuitas']; // Ini sudah termasuk Laba Tahun Berjalan
        $total_liabilitas_ekuitas = $total_liabilitas + $total_ekuitas;
        
        // Cek Balance
        $is_balance = (round($total_aset, 2) == round($total_liabilitas_ekuitas, 2));
        $balance_class = $is_balance ? 'success' : 'danger';
        $balance_text = $is_balance ? 'BALANCE' : 'NOT BALANCE';
        $balance_icon = $is_balance ? 'check-circle' : 'times-circle';
    ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card-modern primary">
            <div class="stat-layout">
                <div class="stat-icon-modern primary">
                    <i class="fas fa-archive"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Aset</div>
                    <div class="stat-value">Rp <?= number_format($total_aset, 0, ',', '.'); ?></div>
                </div>
            </div>
        </div>

        <div class="stat-card-modern info">
            <div class="stat-layout">
                <div class="stat-icon-modern info">
                    <i class="fas fa-university"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Liabilitas & Ekuitas</div>
                    <div class="stat-value">Rp <?= number_format($total_liabilitas_ekuitas, 0, ',', '.'); ?></div>
                </div>
            </div>
        </div>

        <div class="stat-card-modern <?= $balance_class; ?>">
            <div class="stat-layout">
                <div class="stat-icon-modern <?= $balance_class; ?>">
                    <i class="fas fa-<?= $balance_icon; ?>"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Status</div>
                    <div class="stat-value"><?= $balance_text; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Balance Sheet Tables -->
    <div class="balance-section"

    <!-- Balance Sheet Tables -->
    <div class="balance-section">

        <!-- ASET Table -->
        <div class="modern-card">
            <h6 style="font-size: 1.125rem; font-weight: 700; color: var(--primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-archive"></i> ASET
            </h6>
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
                                // Kita definisikan sekali, kita pakai di kedua tabel
                                //
                                // @param array $account Akun yang akan dirender
                                // @param int $level Level indentasi
                                // @param object $CI Instance CodeIgniter
                                // ======================================================
                                function render_balance_sheet_row($account, $level, $CI, $as_of_date) {
                                    $padding = $level * 20;
                                    $style = "padding-left: {$padding}px;";
                                    $class = $account['is_header'] ? 'font-weight: 700; color: var(--text-primary);' : '';
                                    $balance = $account['balance'];

                                    echo "<tr>";
                                    echo "<td style='{$style} {$class}'>{$account['account_name']}</td>";
                                    echo "<td style='text-align: right;'>Rp ". number_format($balance, 0, ',', '.') ."</td>";
                                    echo "<td style='text-align: center;'>";
                                    if ($account['is_header'] == 0 && $balance != 0) {
                                        // Buat URL untuk drill-down ke Buku Besar
                                        // PENTING: Untuk Neraca, start_date adalah awal tahun
                                        $startOfYear = date('Y-01-01', strtotime($as_of_date));
                                        $queryString = "start_date={$startOfYear}&end_date={$as_of_date}";
                                        $url = site_url('reports/general_ledger/' . $account['id'] . '?' . $queryString);
                                        echo "<a href='{$url}' class='btn-detail-modern' title='Lihat Detail Buku Besar'><i class='fas fa-search'></i></a>";
                                    }
                                    echo "</td>";
                                    echo "</tr>";

                                    if (isset($account['children'])) {
                                        foreach ($account['children'] as $child) {
                                            render_balance_sheet_row($child, $level + 1, $CI, $as_of_date);
                                        }
                                    }
                                }
                                ?>

                                <?php foreach ($report_data['aset'] as $account) : ?>
                                    <?php render_balance_sheet_row($account, 0, $CI, $as_of_date); ?>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td style="text-align: right;">TOTAL ASET</td>
                                    <td style="text-align: right;">
                                        Rp <?= number_format($report_data['total_aset'], 0, ',', '.'); ?>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        <!-- LIABILITAS & EKUITAS Table -->
        <div class="modern-card">
            <h6 style="font-size: 1.125rem; font-weight: 700; color: var(--info); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-university"></i> LIABILITAS & EKUITAS
            </h6>
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
                        <tr class="section-header">
                            <td colspan="3">LIABILITAS</td>
                        </tr>
                        <?php foreach ($report_data['liabilitas'] as $account) : ?>
                            <?php render_balance_sheet_row($account, 0, $CI, $as_of_date); ?>
                        <?php endforeach; ?>
                        <tr class="subtotal-row">
                            <td style="text-align: right;">TOTAL LIABILITAS</td>
                            <td style="text-align: right;">
                                Rp <?= number_format($report_data['total_liabilitas'], 0, ',', '.'); ?>
                            </td>
                            <td></td>
                        </tr>

                        <tr><td colspan="3" style="border: none; padding: 0.5rem;"></td></tr>
                        
                        <tr class="section-header">
                            <td colspan="3">EKUITAS</td>
                        </tr>
                        <?php foreach ($report_data['ekuitas'] as $account) : ?>
                            <?php render_balance_sheet_row($account, 0, $CI, $as_of_date); ?>
                        <?php endforeach; ?>
                        
                        <?php if (!empty($report_data['laba_periode_lalu']) && $report_data['laba_periode_lalu'] != 0): ?>
                        <tr>
                            <td style="padding-left: 20px; font-weight: 600;">Laba/Rugi Periode Lalu</td>
                            <td style="text-align: right;">Rp <?= number_format($report_data['laba_periode_lalu'], 0, ',', '.'); ?></td>
                            <td style="text-align: center;">
                                <?php
                                    // Link ke Laporan Laba Rugi periode lalu
                                    $startOfYear = date('Y-01-01', strtotime($as_of_date));
                                    $endOfLastYear = date('Y-m-d', strtotime($startOfYear . ' -1 day'));
                                    $queryString = "start_date=2020-01-01&end_date={$endOfLastYear}";
                                    $url = site_url('reports/profit_loss?' . $queryString);
                                    echo "<a href='{$url}' class='btn-detail-modern' title='Lihat Rincian Laba Rugi Periode Lalu'><i class='fas fa-search'></i></a>";
                                ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        
                        <tr>
                            <td style="padding-left: 20px; font-weight: 600;">Laba Tahun Berjalan</td>
                            <td style="text-align: right;">Rp <?= number_format($report_data['laba_tahun_berjalan'], 0, ',', '.'); ?></td>
                            <td style="text-align: center;">
                                <?php
                                    // Link ke Laporan Laba Rugi
                                    $startOfYear = date('Y-01-01', strtotime($as_of_date));
                                    $queryString = "start_date={$startOfYear}&end_date={$as_of_date}";
                                    $url = site_url('reports/profit_loss?' . $queryString);
                                    echo "<a href='{$url}' class='btn-detail-modern' title='Lihat Rincian Laba Rugi'><i class='fas fa-search'></i></a>";
                                ?>
                            </td>
                        </tr>

                        <tr class="subtotal-row">
                            <td style="text-align: right;">TOTAL EKUITAS</td>
                            <td style="text-align: right;">
                                Rp <?= number_format($report_data['total_ekuitas'], 0, ',', '.'); ?>
                            </td>
                            <td></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td style="text-align: right;">TOTAL LIABILITAS & EKUITAS</td>
                            <td style="text-align: right;">
                                Rp <?= number_format($total_liabilitas_ekuitas, 0, ',', '.'); ?>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>

</div>