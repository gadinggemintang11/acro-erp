<?php if ($detail): ?>
<table class="table table-sm table-striped">
    <tbody>
        <tr>
            <th style="width: 30%;">No. Penerimaan FG</th>
            <td><?= $detail->receipt_number; ?></td>
        </tr>
        <tr>
            <th>Tanggal Penerimaan</th>
            <td><?= date('d M Y H:i', strtotime($detail->confirmed_at)); ?></td>
        </tr>
         <tr>
            <th>Dikonfirmasi Oleh</th>
            <td><?= $detail->confirmed_by_user; ?></td>
        </tr>
        <tr>
            <th>Qty Diterima</th>
            <td class="font-weight-bold"><?= number_format($detail->qty_received, 2); ?></td>
        </tr>
        <tr>
            <th colspan="2" class="text-center bg-light">Detail Sumber Produksi</th>
        </tr>
        <tr>
            <th>No. Work Order (WO)</th>
            <td><?= $detail->wo_number; ?></td>
        </tr>
        <tr>
            <th>No. Produksi</th>
            <td><?= $detail->production_number; ?></td>
        </tr>
         <tr>
            <th>Qty Rencana WO</th>
            <td><?= number_format($detail->planned_qty, 2); ?></td>
        </tr>
         <tr>
            <th>Catatan</th>
            <td><?= $detail->notes ?: '-'; ?></td>
        </tr>
        <tr>
            <th>Size Breakdown</th>
            <td>
                <?php
                    $sizes = json_decode($detail->size_breakdown);
                    if ($sizes && is_array($sizes) && count($sizes) > 0) {
                        echo "<ul class='list-group list-group-flush'>";
                        foreach ($sizes as $size) {
                            echo "<li class='list-group-item d-flex justify-content-between align-items-center p-1'>";
                            echo "Size: <strong>" . htmlspecialchars($size->size) . "</strong>";
                            echo "<span class='badge badge-primary badge-pill'>" . number_format($size->qty) . "</span>";
                            echo "</li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "-";
                    }
                ?>
            </td>
        </tr>
    </tbody>
</table>
<?php else: ?>
<div class="alert alert-warning">Data detail penerimaan tidak ditemukan.</div>
<?php endif; ?>