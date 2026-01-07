<?php if ($detail): ?>
<table class="table table-sm table-striped">
    <tbody>
        <tr>
            <th style="width: 30%;">No. Surat Jalan (DO)</th>
            <td><?= $detail->delivery_number; ?></td>
        </tr>
        <tr>
            <th>Tanggal Pengiriman</th>
            <td><?= date('d M Y H:i', strtotime($detail->shipped_at)); ?></td>
        </tr>
        <tr>
            <th>Dikirim Oleh</th>
            <td><?= $detail->shipped_by_user; ?></td>
        </tr>
        <tr>
            <th>Qty Dikirim</th>
            <td class="font-weight-bold"><?= number_format($detail->qty_shipped, 2); ?></td>
        </tr>
        <tr>
            <th colspan="2" class="text-center bg-light">Detail Customer & Dokumen</th>
        </tr>
        <tr>
            <th>Customer</th>
            <td><?= $detail->nama_customer; ?></td>
        </tr>
        <tr>
            <th>Alamat Customer</th>
            <td><?= $detail->customer_address; ?></td>
        </tr>
        <tr>
            <th>No. Sales Contract</th>
            <td><?= $detail->sc_number; ?></td>
        </tr>
        <tr>
            <th>Dokumen BC</th>
            <td>
                <?= $detail->bc_type_id; ?> / <?= $detail->bc_number; ?> / <?= $detail->bc_date ? date('d M Y', strtotime($detail->bc_date)) : '-'; ?>
            </td>
        </tr>
         <tr>
            <th>Catatan</th>
            <td><?= $detail->notes ?: '-'; ?></td>
        </tr>
    </tbody>
</table>
<?php else: ?>
<div class="alert alert-warning">Data detail pengiriman tidak ditemukan.</div>
<?php endif; ?>