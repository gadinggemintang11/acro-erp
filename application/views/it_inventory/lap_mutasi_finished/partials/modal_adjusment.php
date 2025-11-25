<?php if ($detail): ?>
<div class="card shadow-sm">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-sliders-h"></i> Info Dokumen Penyesuaian</h6>
    </div>
    <div class="card-body">
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center">
                No. Dokumen: <strong><?= $detail->doc_no; ?></strong>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Tgl. Dokumen: <strong><?= date('d M Y', strtotime($detail->doc_date)); ?></strong>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Dibuat Oleh: <strong><?= $detail->created_by_user; ?></strong>
            </li>
            <li class="list-group-item">
                Catatan / Alasan:
                <p class="mb-0"><strong><?= $detail->notes; ?></strong></p>
                </li>
        </ul>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning">Data detail adjustment tidak ditemukan.</div>
<?php endif; ?>