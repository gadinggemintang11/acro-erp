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
        function render_profit_loss_row_pdf($account, $level) {
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
                    render_profit_loss_row_pdf($child, $level + 1);
                }
            }
        }
        ?>

        <tr style="background-color: #f2f2f2;">
            <td colspan="2" style="font-weight: bold;">PENDAPATAN</td>
        </tr>
        <?php foreach ($report_data['pendapatan'] as $account) : ?>
            <?php render_profit_loss_row_pdf($account, 0); ?>
        <?php endforeach; ?>
        <tr style="background-color: #f2f2f2;">
            <td style="font-weight: bold; text-align: right;">TOTAL PENDAPATAN</td>
            <td style="font-weight: bold; text-align: right;">
                <?= number_format($report_data['total_pendapatan'], 2); ?>
            </td>
        </tr>

        <tr><td colspan="2">&nbsp;</td></tr>

        <tr style="background-color: #f2f2f2;">
            <td colspan="2" style="font-weight: bold;">BIAYA</td>
        </tr>
        <?php foreach ($report_data['biaya'] as $account) : ?>
            <?php render_profit_loss_row_pdf($account, 0); ?>
        <?php endforeach; ?>
        <tr style="background-color: #f2f2f2;">
            <td style="font-weight: bold; text-align: right;">TOTAL BIAYA</td>
            <td style="font-weight: bold; text-align: right;">
                <?= number_format($report_data['total_biaya'], 2); ?>
            </td>
        </tr>
        
        <tr><td colspan="2">&nbsp;</td></tr>

        <?php
            $laba_text = $report_data['laba_bersih'] >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH';
        ?>
        <tr style="background-color: #e2efd9; font-weight: bold;">
            <td style="text-align: right; font-size: 1.1rem;">
                <?= $laba_text; ?>
            </td>
            <td style="text-align: right; font-size: 1.1rem;">
                <?= number_format($report_data['laba_bersih'], 2); ?>
            </td>
        </tr>
    </tbody>
</table>