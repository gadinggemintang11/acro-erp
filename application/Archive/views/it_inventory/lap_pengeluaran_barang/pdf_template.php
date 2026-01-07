<!DOCTYPE html>
<html>
<head>
    <title>Laporan Pengeluaran Barang</title>
    <style>
        body { font-family: Tahoma, sans-serif; font-size: 8pt; margin: 10mm; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h3 { margin-bottom: 2px; }
        .info-box { border: 1px solid #ddd; padding: 8px; font-size: 9pt; width: 45%; float: left; margin-right: 20px; }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px;
            table-layout: fixed; /* Penting untuk lebar kolom di PDF */
        }
        th, td { 
            border: 1px solid #333; 
            padding: 4px 6px; 
            vertical-align: middle; 
            word-wrap: break-word; /* Mencegah overflow */
        }
        thead th { background-color: #343a40; color: #fff; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .small-text { font-size: 7pt; }
    </style>
</head>
<body>

    <div class="header">
        <h3>LAPORAN PENGELUARAN BARANG EKSTERNAL</h3>
        <p>Periode: **<?= date('d M Y', strtotime($filters['start_date'])); ?>** s/d **<?= date('d M Y', strtotime($filters['end_date'])); ?>**</p>
    </div>

    <table>
        <thead class="text-center">
            <tr>
                <th rowspan="2" style="width: 2%;">NO</th>
                <th colspan="3" style="width: 20%;">DATA DOKUMEN PABEAN</th>
                <th colspan="3" style="width: 25%;">BUKTI PENGELUARAN BARANG</th>
                <th colspan="4" style="width: 30%;">DETAIL BARANG</th>
                <th colspan="2" style="width: 13%;">NILAI KELUAR</th>
            </tr>
            <tr>
                <th style="width: 7%;">JENIS BC</th>
                <th style="width: 7%;">NOMOR BC</th>
                <th style="width: 6%;">TANGGAL BC</th>
                
                <th style="width: 9%;">NOMOR PENGELUARAN</th>
                <th style="width: 8%;">TANGGAL PENGELUARAN</th>
                <th style="width: 8%;">PENERIMA BARANG</th>
                
                <th style="width: 8%;">KODE BARANG</th>
                <th style="width: 15%;">NAMA BARANG</th>
                <th class="text-right" style="width: 7%;">JUMLAH</th>
                <th style="width: 5%;">SATUAN</th>
                
                <th style="width: 4%;">MATA UANG</th>
                <th class="text-right" style="width: 9%;">NILAI BARANG</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; 
            $grand_total_idr = 0;
            if (!empty($report_data)): 
                foreach ($report_data as $row): 
                    $bc_jenis = $row->jenis_bc ?? '-';
                    $nilai_idr = $row->nilai_barang_idr ?? 0;
                    $nilai_fc = $row->nilai_barang_fc ?? 0;
                    $mata_uang = $row->mata_uang ?? 'IDR';
                    
                    // Total Nilai IDR untuk grand total
                    $grand_total_idr += $nilai_idr;
            ?>
                <tr>
                    <td class="text-center small-text"><?= $no++; ?></td>
                    <td class="small-text"><?= htmlspecialchars($bc_jenis); ?></td>
                    <td class="small-text"><?= htmlspecialchars($row->nomor_bc ?? '-'); ?></td>
                    <td class="small-text text-nowrap"><?= $row->tanggal_bc ? date('d/m/Y', strtotime($row->tanggal_bc)) : '-'; ?></td>
                    
                    <td class="small-text font-weight-bold"><?= htmlspecialchars($row->nomor_pengeluaran); ?></td>
                    <td class="small-text text-nowrap"><?= date('d/m/Y', strtotime($row->tanggal_pengeluaran)); ?></td>
                    <td class="small-text"><?= htmlspecialchars($row->partner_name ?? '-'); ?></td>

                    <td class="small-text"><?= htmlspecialchars($row->item_code); ?></td>
                    <td class="small-text"><?= htmlspecialchars($row->item_name); ?></td>
                    <td class="small-text text-right"><?= number_format($row->jumlah, 2); ?></td>
                    <td class="small-text"><?= htmlspecialchars($row->item_unit ?? '-'); ?></td>
                    
                    <td class="small-text text-center"><?= htmlspecialchars($mata_uang); ?></td>
                    <td class="small-text text-right">
                        <?php 
                            // Tampilkan Nilai Valuta Asing jika ada, jika tidak tampilkan IDR
                            if ($mata_uang != 'IDR' && $nilai_fc > 0) {
                                echo number_format($nilai_fc, 2);
                            } else {
                                echo number_format($nilai_idr, 2);
                            }
                        ?>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="12" class="text-center py-3">Tidak ada data pengeluaran ditemukan dalam periode ini.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
             <tr style="background-color: #f2f2f2;">
                <td colspan="11" class="text-right font-weight-bold">GRAND TOTAL NILAI BARANG KELUAR (IDR)</td>
                <td class="text-center font-weight-bold">IDR</td>
                <td class="text-right font-weight-bold"><?= number_format($grand_total_idr, 2); ?></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>