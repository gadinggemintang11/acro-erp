<?php
// Partial View untuk Laporan 2 (Detail Mutasi)
// Konten ini di-inject ke modal-level-2-body
?>

<div class="row">
    <div class="col-md-6">
        <strong>Item:</strong> <?= htmlspecialchars($nama_barang) ?><br>
        <strong>Kode:</strong> <?= htmlspecialchars($kode_barang) ?>
    </div>
    <div class="col-md-6 text-right">
        <strong>Periode:</strong> <?= $periode ?>
    </div>
</div>

<hr style="margin-top: 10px; margin-bottom: 10px;">

<div class="table-responsive">
    <table id="table-detail" class="table table-bordered table-striped table-hover" style="width:100%">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Tipe Dok.</th>
                <th>No. Dokumen</th>
                <th>Customer</th>
                <th>No. WO</th>
                <th>No. SC</th>
                <th class="text-right">Masuk</th>
                <th class="text-right">Keluar</th>
                <th class="text-right">Saldo</th>
                <th>Trace</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data_detail as $row) : ?>
                <tr>
                    <td><?= date('d-m-Y H:i', strtotime($row->transaction_date)) ?></td>
                    <td>
                        <?php 
                        if ($row->doc_type == 'FG_RECEIPT') echo 'Pemasukan (Receipt)';
                        elseif ($row->doc_type == 'FG_DELIVER') echo 'Pengeluaran (DO)';
                        else echo $row->doc_type;
                        ?>
                    </td>
                    <td><?= htmlspecialchars($row->doc_number) ?></td>
                    <td><?= htmlspecialchars($row->customer_name) ?></td>
                    <td><?= htmlspecialchars($row->wo_number) ?></td>
                    <td><?= htmlspecialchars($row->sc_number) ?></td>
                    <td class="text-right"><?= ($row->qty_in > 0) ? number_format($row->qty_in, 2) : '-' ?></td>
                    <td class="text-right"><?= ($row->qty_out > 0) ? number_format($row->qty_out, 2) : '-' ?></td>
                    <td class="text-right"><?= number_format($row->qty_balance, 2) ?></td>
                    <td>
                        <?php if ($row->doc_type == 'FG_RECEIPT' && !empty($row->wo_id)) : ?>
                            <!-- Tombol ini akan memicu drill-down ke Laporan 3 -->
                            <button class="btn btn-xs btn-info btn-trace" 
                                    data-wo-id="<?= $row->wo_id ?>" 
                                    data-wo-number="<?= htmlspecialchars($row->wo_number) ?>">
                                <i class="fa fa-search"></i> Trace
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
