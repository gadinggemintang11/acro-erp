<!DOCTYPE html><html><head><title><?= $title; ?></title><style>body{font-family:Arial,sans-serif;font-size:10px;} .table{width:100%;border-collapse:collapse;} .table th, .table td{border:1px solid #000;padding:5px;} .table th{background-color:#f2f2f2;text-align:center;} .text-right{text-align:right;} .text-center{text-align:center;} .font-weight-bold{font-weight:bold;} h3,h4,h5{text-align:center;margin:5px 0;} @page{margin:20px;}</style></head><body>
    <h3><?= $title; ?></h3>
    <h4>(<?= $item->kode_barang; ?>)</h4>
    <h5>Periode: <?= date('d M Y', strtotime($start_date)) . ' s/d ' . date('d M Y', strtotime($end_date)); ?></h5><br>
    <table class="table">
        <thead>
            <tr class="font-weight-bold">
                <td colspan="4" class="text-left">SALDO AWAL</td>
                <td colspan="3" class="text-right"><?= number_format($saldo_awal, 2); ?></td>
            </tr>
            <tr>
                <th style="width:5%;">No</th><th style="width:15%;">Tanggal</th><th style="width:20%;">Dokumen</th><th style="width:30%;">Keterangan</th><th style="width:10%;">Masuk</th><th style="width:10%;">Keluar</th><th style="width:10%;">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; $saldo = $saldo_awal; $total_masuk = 0; $total_keluar = 0;
            if (!empty($mutasi_data)): foreach ($mutasi_data as $row):
                $saldo = $row->saldo_akhir; $total_masuk += $row->qty_in; $total_keluar += $row->qty_out;
            ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= date('d-m-Y H:i', strtotime($row->transaction_date)); ?></td>
                <td><?= $row->doc_number; ?></td>
                <td><?= $row->description; ?></td>
                <td class="text-right"><?= $row->qty_in > 0 ? number_format($row->qty_in, 2) : '-'; ?></td>
                <td class="text-right"><?= $row->qty_out > 0 ? number_format($row->qty_out, 2) : '-'; ?></td>
                <td class="text-right font-weight-bold"><?= number_format($saldo, 2); ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7" class="text-center">Tidak ada transaksi.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="font-weight-bold">
                <td colspan="4" class="text-right">TOTAL MUTASI</td>
                <td class="text-right"><?= number_format($total_masuk, 2); ?></td>
                <td class="text-right"><?= number_format($total_keluar, 2); ?></td>
                <td></td>
            </tr>
            <tr class="font-weight-bold">
                <td colspan="6" class="text-right">SALDO AKHIR</td>
                <td class="text-right"><?= number_format($saldo, 2); ?></td>
            </tr>
        </tfoot>
    </table>
</body></html>