<?php $CI =& get_instance(); ?>
<table class="table table-sm">
    <thead>
        <tr>
            <th>Nama Akun</th>
            <th style="text-align: right;">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <?php
        function render_balance_sheet_row_pdf($account, $level) {
            $padding = $level * 20;
            $style = "padding-left: {$padding}px;";
            $class = $account['is_header'] ? 'font-weight-bold' : '';
            $balance = $account['balance'];

            echo "<tr>";
            echo "<td class='{$class}' style='{$style}'>{$account['account_name']}</td>";
            echo "<td style='text-align: right;'>". number_format($balance, 2) ."</td>";
            echo "</tr>";

            if (isset($account['children'])) {
                foreach ($account['children'] as $child) {
                    render_balance_sheet_row_pdf($child, $level + 1);
                }
            }
        }
        ?>

        <!-- ASET -->
        <tr style="background-color: #f2f2f2;">
            <td colspan="2" style="font-weight: bold;">ASET</td>
        </tr>
        <?php foreach ($report_data['aset'] as $account) : ?>
            <?php render_balance_sheet_row_pdf($account, 0); ?>
        <?php endforeach; ?>
        <tr style="background-color: #e2efd9; font-weight: bold;">
            <td style="text-align: right;">TOTAL ASET</td>
            <td style="text-align: right;">
                <?= number_format($report_data['total_aset'], 2); ?>
            </td>
        </tr>

        <tr><td colspan="2">&nbsp;</td></tr>

        <!-- LIABILITAS & EKUITAS -->
        <tr style="background-color: #f2f2f2;">
            <td colspan="2" style="font-weight: bold;">LIABILITAS & EKUITAS</td>
        </tr>
        
        <!-- LIABILITAS -->
        <tr style="background-color: #f2f2f2;">
            <td colspan="2" style="font-weight: bold; padding-left: 20px;">LIABILITAS</td>
        </tr>
        <?php foreach ($report_data['liabilitas'] as $account) : ?>
            <?php render_balance_sheet_row_pdf($account, 1); ?>
        <?php endforeach; ?>
        <tr style="background-color: #f2f2f2;">
            <td style="font-weight: bold; text-align: right;">TOTAL LIABILITAS</td>
            <td style="font-weight: bold; text-align: right;">
                <?= number_format($report_data['total_liabilitas'], 2); ?>
            </td>
        </tr>

        <tr><td colspan="2">&nbsp;</td></tr>

        <!-- EKUITAS -->
        <tr style="background-color: #f2f2f2;">
            <td colspan="2" style="font-weight: bold; padding-left: 20px;">EKUITAS</td>
        </tr>
        <?php foreach ($report_data['ekuitas'] as $account) : ?>
            <?php render_balance_sheet_row_pdf($account, 1); ?>
        <?php endforeach; ?>
        <tr>
            <td style="padding-left: 40px;">Laba Tahun Berjalan</td>
            <td style="text-align: right;"><?= number_format($report_data['laba_tahun_berjalan'], 2); ?></td>
        </tr>
        <tr style="background-color: #f2f2f2;">
            <td style="font-weight: bold; text-align: right;">TOTAL EKUITAS</td>
            <td style="font-weight: bold; text-align: right;">
                <?= number_format($report_data['total_ekuitas'], 2); ?>
            </td>
        </tr>
        
        <tr><td colspan="2">&nbsp;</td></tr>

        <?php $total_liabilitas_ekuitas = $report_data['total_liabilitas'] + $report_data['total_ekuitas']; ?>
        <tr style="background-color: #e2efd9; font-weight: bold;">
            <td style="text-align: right;">TOTAL LIABILITAS & EKUITAS</td>
            <td style="text-align: right;">
                <?= number_format($total_liabilitas_ekuitas, 2); ?>
            </td>
        </tr>

    </tbody>
</table>