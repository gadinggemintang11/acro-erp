<!DOCTYPE html><html><head><title><?= $title; ?></title><style>body{font-family:Arial,sans-serif;font-size:9px;} .table{width:100%;border-collapse:collapse;} .table th, .table td{border:1px solid #000;padding:4px;} .table th{background-color:#f2f2f2;text-align:center;font-weight:bold;} .text-right{text-align:right;} .text-center{text-align:center;} .font-weight-bold{font-weight:bold;} h3,h4{text-align:center;margin:5px 0;} @page{margin:20px;}</style></head><body>
    <h3><?= $title; ?></h3>
    <h4>Periode: <?= date('d M Y', strtotime($start_date)) . ' s/d ' . date('d M Y', strtotime($end_date)); ?></h4><br>
    
    <table class="table">
        <thead>
            <tr>
                <th rowspan="2">NO</th>
                <th rowspan="2">KODE BARANG</th>
                <th rowspan="2">NAMA BARANG</th>
                <th rowspan="2">SATUAN</th>
                <th rowspan="2">SALDO AWAL</th>
                <th colspan="3">MUTASI</th>
                <th rowspan="2">SALDO AKHIR<br>(SISTEM)</th>
                <th rowspan="2">HASIL<br>PENCACAHAN</th>
                <th rowspan="2">JUMLAH<br>SELISIH</th>
            </tr>
            <tr>
                <th>PEMASUKAN</th>
                <th>PENGELUARAN</th>
                <th>PENYESUAIAN</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if (!empty($rekap_data)):
                foreach ($rekap_data as $row): 
            ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= $row->kode_barang; ?></td>
                    <td><?= $row->nama_barang; ?></td>
                    <td class="text-center"><?= $row->satuan; ?></td>
                    <td class="text-right"><?= number_format($row->saldo_awal, 2); ?></td>
                    <td class="text-right"><?= number_format($row->pemasukan, 2); ?></td>
                    <td class="text-right"><?= number_format($row->pengeluaran, 2); ?></td>
                    <td class="text-right"><?= number_format($row->penyesuaian, 2); ?></td>
                    <td class="text-right font-weight-bold"><?= number_format($row->saldo_akhir, 2); ?></td>
                    <td class="text-right">
                        <?= ($row->hasil_pencacahan !== NULL) ? number_format($row->hasil_pencacahan, 2) : ''; ?>
                    </td>
                    <td class="text-right font-weight-bold">
                        <?= ($row->jumlah_selisih !== NULL) ? number_format($row->jumlah_selisih, 2) : ''; ?>
                    </td>
                </tr>
            <?php 
                endforeach;
            else:
            ?>
                <tr>
                    <td colspan="11" class="text-center">Tidak ada data untuk periode yang dipilih.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body></html>