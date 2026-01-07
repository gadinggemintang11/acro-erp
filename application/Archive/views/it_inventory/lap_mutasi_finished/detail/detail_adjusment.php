<?php if ($detail): ?>
<table class="table table-sm table-striped">
    <tbody>
        <tr>
            <th style="width: 30%;">No. Dokumen Adjustment</th>
            <td><?= $detail->doc_no; ?></td>
        </tr>
        <tr>
            <th>Tanggal Dokumen</th>
            <td><?= date('d M Y', strtotime($detail->doc_date)); ?></td>
        </tr>
         <tr>
            <th>Dibuat Oleh</th>
            <td><?= $detail->created_by_user; ?></td>
        </tr>
        <tr>
            <th>Catatan / Alasan</th>
            <td class="font-weight-bold"><?= $detail->notes; ?></td>
        </tr>
        </tbody>
</table>
<?php else: ?>
<div class="alert alert-warning">Data detail adjustment tidak ditemukan.</div>
<?php endif; ?>