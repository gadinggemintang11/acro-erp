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
    text-decoration: none;
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
    color: white;
}

.btn-modern.secondary {
    background: var(--light-bg);
    color: var(--text-primary);
    border: 2px solid var(--border);
}

.btn-modern.secondary:hover {
    background: white;
    border-color: var(--text-secondary);
    color: var(--text-primary);
}

.btn-modern.success {
    background: var(--success);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-modern.success:hover {
    background: #059669;
    transform: translateY(-2px);
    color: white;
}

.report-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.report-table thead th {
    background: var(--dark);
    color: white;
    padding: 1rem;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.report-table thead th:first-child {
    border-radius: 12px 0 0 0;
}

.report-table thead th:last-child {
    border-radius: 0 12px 0 0;
}

.report-table tbody tr {
    transition: all 0.2s ease;
}

.report-table tbody tr:hover {
    background: var(--light-bg);
}

.report-table tbody td {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--border);
}

.report-table tfoot td {
    padding: 1rem;
    font-weight: 700;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 1rem;
}

.status-badge.balance {
    background: var(--success);
    color: white;
}

.status-badge.not-balance {
    background: var(--danger);
    color: white;
}

.dropdown-menu {
    border-radius: 12px !important;
    border: none !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
}

.dropdown-item {
    padding: 0.75rem 1.5rem;
}

.dropdown-item:hover {
    background: var(--light-bg);
}
</style>

<div class="modern-container">

    <!-- Page Header -->
    <div class="page-header-modern">
        <h1><i class="fas fa-balance-scale"></i> <?= $title; ?></h1>
        <p>Neraca Saldo - Trial Balance Report</p>
    </div>

    <!-- Filter Card -->
    <div class="modern-card">
        <form action="<?= current_url(); ?>" method="GET">
            <div class="filter-section">
                <div class="form-group-modern">
                    <label for="as_of_date">
                        <i class="far fa-calendar-alt"></i> Per Tanggal (As of Date)
                    </label>
                    <input type="date" class="form-control-modern" id="as_of_date" name="as_of_date" value="<?= $as_of_date; ?>" required>
                </div>
                <div>
                    <button type="submit" class="btn-modern primary">
                        <i class="fa fa-search"></i> Tampilkan Laporan
                    </button>
                    <a href="<?= current_url(); ?>" class="btn-modern secondary">
                        <i class="fa fa-redo"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Report Card -->
    <div class="modern-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <div>
                <h4 style="margin: 0; color: var(--text-primary); font-weight: 700;">
                    <i class="fas fa-file-invoice-dollar" style="color: var(--primary);"></i> Neraca Saldo
                </h4>
                <p style="margin: 0.5rem 0 0 0; color: var(--text-secondary); font-size: 0.875rem;">
                    Per Tanggal: <strong><?= date('d F Y', strtotime($as_of_date)); ?></strong>
                </p>
            </div>
            <?php
            $queryString = http_build_query([
                'as_of_date' => $as_of_date,
            ]);
            ?>
            <div class="dropdown">
                <button class="btn-modern success dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-download"></i> Ekspor Laporan
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="<?= current_url() . '?' . $queryString . '&export=pdf'; ?>" target="_blank">
                        <i class="fa fa-file-pdf text-danger"></i> Ekspor ke PDF
                    </a>
                    <a class="dropdown-item" href="<?= current_url() . '?' . $queryString . '&export=excel'; ?>">
                        <i class="fa fa-file-excel text-success"></i> Ekspor ke Excel
                    </a>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="text-align: left;">Kode Akun</th>
                        <th style="text-align: left;">Nama Akun</th>
                        <th style="text-align: right; width: 18%;">Debit (Rp)</th>
                        <th style="text-align: right; width: 18%;">Kredit (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $total_debit = 0;
                        $total_kredit = 0;
                    ?>
                    <?php foreach ($report_data as $account) : ?>
                        <?php
                            $total_debit += $account['debit'];
                            $total_kredit += $account['kredit'];
                        ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--primary);"><?= $account['account_code']; ?></td>
                            <td><?= $account['account_name']; ?></td>
                            <td style="text-align: right; font-weight: 600; color: var(--success);">
                                <?= $account['debit'] > 0 ? number_format($account['debit'], 2) : '-'; ?>
                            </td>
                            <td style="text-align: right; font-weight: 600; color: var(--danger);">
                                <?= $account['kredit'] > 0 ? number_format($account['kredit'], 2) : '-'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: var(--light-bg); border-top: 3px solid var(--dark);">
                        <td colspan="2" style="text-align: right; font-size: 1rem;">
                            <i class="fas fa-calculator"></i> TOTAL
                        </td>
                        <td style="text-align: right; color: var(--success); font-size: 1rem;">
                            <?= number_format($total_debit, 2); ?>
                        </td>
                        <td style="text-align: right; color: var(--danger); font-size: 1rem;">
                            <?= number_format($total_kredit, 2); ?>
                        </td>
                    </tr>
                    <?php
                        $is_balance = (round($total_debit, 2) == round($total_kredit, 2));
                    ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 1.5rem; background: var(--light-bg); border-radius: 0 0 12px 12px;">
                            <?php if ($is_balance): ?>
                                <span class="status-badge balance">
                                    <i class="fas fa-check-circle"></i> BALANCE
                                </span>
                            <?php else: ?>
                                <span class="status-badge not-balance">
                                    <i class="fas fa-times-circle"></i> NOT BALANCE
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>