<?php $CI =& get_instance(); ?>

<style>
:root {
    --primary: var(--brand-1);
    --primary-dark: var(--brand-2);
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

.cashflow-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.cashflow-table tr.section-header td {
    background: var(--primary);
    color: white;
    padding: 1rem;
    font-weight: 700;
    font-size: 0.95rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
}

.cashflow-table tr.section-header.cfo td {
    background: var(--primary);
}

.cashflow-table tr.section-header.cfi td {
    background: var(--info);
}

.cashflow-table tr.section-header.cff td {
    background: var(--warning);
}

.cashflow-table tr.group-row td {
    padding: 0.875rem 1rem;
    font-weight: 600;
    background: var(--light-bg);
    border-bottom: 1px solid var(--border);
}

.cashflow-table tr.detail-row {
    transition: all 0.2s ease;
}

.cashflow-table tr.detail-row:hover {
    background: var(--light-bg);
}

.cashflow-table tr.detail-row td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border);
}

.cashflow-table tr.subtotal-row td {
    padding: 1rem;
    font-weight: 700;
    background: var(--light-bg);
    border-top: 2px solid var(--dark);
    font-size: 0.95rem;
}

.cashflow-table tr.final-row td {
    padding: 1rem;
    font-weight: 700;
    background: var(--success);
    color: white;
    font-size: 1rem;
}

.cashflow-table tr.spacer td {
    padding: 0.5rem;
    border: none;
}

.btn-trace {
    padding: 0.25rem 0.5rem;
    background: var(--info);
    color: white;
    border-radius: 6px;
    font-size: 0.75rem;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.btn-trace:hover {
    background: #2563EB;
    transform: scale(1.05);
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
        <h1><i class="fas fa-water"></i> <?= $title; ?></h1>
        <p><?= lang_line('rpt_cf_subtitle'); ?></p>
    </div>

    <!-- Filter Card -->
    <div class="modern-card">
        <form action="<?= current_url(); ?>" method="GET">
            <div class="filter-section">
                <div class="form-group-modern">
                    <label for="start_date">
                        <i class="far fa-calendar-alt"></i> <?= lang_line('rpt_start_date'); ?>
                    </label>
                    <input type="date" class="form-control-modern" id="start_date" name="start_date" value="<?= $start_date; ?>" required>
                </div>
                <div class="form-group-modern">
                    <label for="end_date">
                        <i class="far fa-calendar-check"></i> <?= lang_line('rpt_end_date'); ?>
                    </label>
                    <input type="date" class="form-control-modern" id="end_date" name="end_date" value="<?= $end_date; ?>" required>
                </div>
                <div>
                    <button type="submit" class="btn-modern primary">
                        <i class="fa fa-search"></i> <?= lang_line('rpt_show_report'); ?>
                    </button>
                    <a href="<?= current_url(); ?>" class="btn-modern secondary">
                        <i class="fa fa-redo"></i> <?= lang_line('rpt_reset'); ?>
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
                    <i class="fas fa-file-invoice-dollar" style="color: var(--primary);"></i> <?= lang_line('rpt_cf_heading'); ?>
                </h4>
                <p style="margin: 0.5rem 0 0 0; color: var(--text-secondary); font-size: 0.875rem;">
                    <?= lang_line('rpt_period'); ?>: <strong><?= date('d F Y', strtotime($start_date)); ?></strong> s/d <strong><?= date('d F Y', strtotime($end_date)); ?></strong>
                </p>
            </div>
            <?php
            $queryString = http_build_query([
                'start_date' => $start_date,
                'end_date' => $end_date,
            ]);
            ?>
            <div class="dropdown">
                <button class="btn-modern success dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-download"></i> <?= lang_line('rpt_export_report'); ?>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="<?= current_url() . '?' . $queryString . '&export=pdf'; ?>" target="_blank">
                        <i class="fa fa-file-pdf text-danger"></i> <?= lang_line('rpt_export_pdf'); ?>
                    </a>
                    <a class="dropdown-item" href="<?= current_url() . '?' . $queryString . '&export=excel'; ?>">
                        <i class="fa fa-file-excel text-success"></i> <?= lang_line('rpt_export_excel'); ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="cashflow-table">

                <!-- CFO Section -->
                <tr class="section-header cfo">
                    <td colspan="3"><i class="fas fa-chart-line"></i> <?= lang_line('rpt_cf_cfo_section'); ?></td>
                </tr>
                <?php
                    $cfo_groups = [];
                    foreach ($report_data['cfo'] as $item) {
                        $cfo_groups[$item['group_name']][] = $item;
                    }

                    foreach ($cfo_groups as $group_name => $items) :
                        $group_total = 0;
                        foreach ($items as $item) {
                            $group_total += $item['cash_impact'];
                        }
                ?>
                    <tr class="group-row">
                        <td style="padding-left: 20px;"><?= $group_name; ?></td>
                        <td class="text-right" style="width: 18%;"><?= number_format($group_total, 2); ?></td>
                        <td class="text-center" style="width: 5%;"></td>
                    </tr>
                    <?php foreach ($items as $item) : ?>
                        <tr class="detail-row">
                            <td style="padding-left: 40px; color: var(--text-secondary);">
                                <?= $item['account_name']; ?> <span style="color: var(--primary);">(<?= $item['account_code']; ?>)</span>
                            </td>
                            <td class="text-right" style="color: var(--text-secondary);">(<?= number_format($item['cash_impact'], 2); ?>)</td>
                            <td class="text-center">
                                <?php
                                    $queryString2 = "start_date={$start_date}&end_date={$end_date}";
                                    $url = site_url('reports/general_ledger/' . $item['account_id'] . '?' . $queryString2);
                                ?>
                                <a href="<?= $url; ?>" class="btn-trace" title="<?= lang_line('rpt_view_gl_detail'); ?>">
                                    <i class="fa fa-search"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                
                <tr class="subtotal-row">
                    <td class="text-right"><?= lang_line('rpt_cf_total_cfo'); ?></td>
                    <td class="text-right"><?= number_format($report_data['total_cfo'], 2); ?></td>
                    <td></td>
                </tr>
                <tr class="spacer"><td colspan="3"></td></tr>


                <!-- CFI Section -->
                <tr class="section-header cfi">
                    <td colspan="3"><i class="fas fa-coins"></i> <?= lang_line('rpt_cf_cfi_section'); ?></td>
                </tr>
                <?php foreach ($report_data['cfi'] as $item) : ?>
                    <tr class="detail-row">
                        <td style="padding-left: 20px;">
                            <?= $item['account_name']; ?> <span style="color: var(--info);">(<?= $item['account_code']; ?>)</span>
                        </td>
                        <td class="text-right"><?= number_format($item['cash_impact'], 2); ?></td>
                        <td class="text-center">
                            <?php
                                $queryString2 = "start_date={$start_date}&end_date={$end_date}";
                                $url = site_url('reports/general_ledger/' . $item['account_id'] . '?' . $queryString2);
                            ?>
                            <a href="<?= $url; ?>" class="btn-trace" title="<?= lang_line('rpt_view_gl_detail'); ?>">
                                <i class="fa fa-search"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="subtotal-row">
                    <td class="text-right"><?= lang_line('rpt_cf_total_cfi'); ?></td>
                    <td class="text-right"><?= number_format($report_data['total_cfi'], 2); ?></td>
                    <td></td>
                </tr>
                <tr class="spacer"><td colspan="3"></td></tr>


                <!-- CFF Section -->
                <tr class="section-header cff">
                    <td colspan="3"><i class="fas fa-hand-holding-usd"></i> <?= lang_line('rpt_cf_cff_section'); ?></td>
                </tr>
                <?php foreach ($report_data['cff'] as $item) : ?>
                    <tr class="detail-row">
                        <td style="padding-left: 20px;">
                            <?= $item['account_name']; ?> <span style="color: var(--warning);">(<?= $item['account_code']; ?>)</span>
                        </td>
                        <td class="text-right"><?= number_format($item['cash_impact'], 2); ?></td>
                        <td class="text-center">
                            <?php
                                $queryString2 = "start_date={$start_date}&end_date={$end_date}";
                                $url = site_url('reports/general_ledger/' . $item['account_id'] . '?' . $queryString2);
                            ?>
                            <a href="<?= $url; ?>" class="btn-trace" title="<?= lang_line('rpt_view_gl_detail'); ?>">
                                <i class="fa fa-search"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="subtotal-row">
                    <td class="text-right"><?= lang_line('rpt_cf_total_cff'); ?></td>
                    <td class="text-right"><?= number_format($report_data['total_cff'], 2); ?></td>
                    <td></td>
                </tr>
                <tr class="spacer"><td colspan="3"></td></tr>


                <!-- Final Summary -->
                <tr style="background: var(--light-bg); border-top: 3px solid var(--dark);">
                    <td class="text-right" style="padding: 1rem; font-weight: 700;"><?= lang_line('rpt_cf_net_cash_change'); ?></td>
                    <td class="text-right" style="padding: 1rem; font-weight: 700;"><?= number_format($report_data['peningkatan_kas'], 2); ?></td>
                    <td></td>
                </tr>
                <tr style="background: var(--light-bg);">
                    <td class="text-right" style="padding: 1rem; font-weight: 700;"><?= lang_line('rpt_cf_beginning_cash'); ?></td>
                    <td class="text-right" style="padding: 1rem; font-weight: 700;"><?= number_format($report_data['saldo_awal_kas'], 2); ?></td>
                    <td></td>
                </tr>
                <tr class="final-row">
                    <td class="text-right"><i class="fas fa-wallet"></i> <?= lang_line('rpt_cf_ending_cash'); ?></td>
                    <td class="text-right"><?= number_format($report_data['saldo_akhir_kas'], 2); ?></td>
                    <td></td>
                </tr>

            </table>
        </div>
    </div>

</div>