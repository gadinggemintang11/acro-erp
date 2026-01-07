<!DOCTYPE html>
<html>
<head>
    <title>Laporan Mutasi Material</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #000; padding: 4px; }
        .table th { background-color: #f2f2f2; text-align: center; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-weight-bold { font-weight: bold; }
        h3, h4 { text-align: center; margin: 5px 0; }
    </style>
</head>
<body>
    <h3>Laporan Mutasi Material & Semifinished</h3>
    <h4>Periode: <?= date('d M Y', strtotime($start)) . ' s/d ' . date('d M Y', strtotime($end)); ?></h4>
    <br>
    
    <table class="table">
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Kode</th>
                <th rowspan="2">Nama Barang</th>
                <th rowspan="2">Satuan</th>
                <th rowspan="2">Tipe</th>
                <th rowspan="2">Saldo Awal</th>
                <th colspan="4">Mutasi</th>
                <th rowspan="2">Saldo Akhir (Sistem)</th>
                <th rowspan="2">Hasil Pencacahan</th>
                <th rowspan="2">Jumlah Selisih</th>
            </tr>
            <tr>
                <th>Pemasukan</th>
                <th>Retur Prod.</th>
                <th>Pengeluaran</th>
                <th>Penyesuaian</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if (!empty($rows)):
                foreach ($rows as $r): 
            ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= $r['kode_barang']; ?></td>
                    <td><?= $r['nama_barang']; ?></td>
                    <td class="text-center"><?= $r['satuan']; ?></td>
                    <td class="text-center"><?= $r['item_type']; ?></td>
                    <td class="text-right"><?= number_format($r['saldo_awal'], 2); ?></td>
                    <td class="text-right"><?= number_format($r['pemasukan'], 2); ?></td>
                    <td class="text-right"><?= number_format($r['retur'], 2); ?></td>
                    <td class="text-right"><?= number_format($r['pengeluaran'], 2); ?></td>
                    <td class="text-right"><?= number_format($r['penyesuaian'], 2); ?></td>
                    <td class="text-right font-weight-bold"><?= number_format($r['saldo_akhir'], 2); ?></td>
                    <td class="text-right">
                        <?= ($r['hasil_pencacahan'] !== NULL) ? number_format($r['hasil_pencacahan'], 2) : ''; ?>
                    </td>
                    <td class="text-right font-weight-bold">
                        <?= ($r['jumlah_selisih'] !== NULL) ? number_format($r['jumlah_selisih'], 2) : ''; ?>
                    </td>
                </tr>
            <?php 
                endforeach;
            else:
            ?>
                <tr>
                    <td colspan="13" class="text-center">Tidak ada data untuk periode yang dipilih.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>