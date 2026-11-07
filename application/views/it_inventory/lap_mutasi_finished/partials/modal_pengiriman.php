<?php if ($detail): ?>
<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm mb-3">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-truck"></i> Info Dokumen Pengiriman (DO)</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        No. Surat Jalan: <strong><?= $detail->delivery_number; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Tgl. Pengiriman: <strong><?= date('d M Y H:i', strtotime($detail->shipped_at)); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Dikirim Oleh: <strong><?= $detail->shipped_by_user; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Qty Dikirim: <strong class="h5 text-danger"><?= number_format($detail->qty_shipped, 2); ?> PCS</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Catatan: <strong><?= $detail->notes ?: '-'; ?></strong>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card shadow-sm mb-3">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-file-alt"></i> Info Dokumen Pendukung</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Sales Order Number: <strong><?= $detail->sc_number; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Jenis BC: <strong><?= $detail->bc_type_description; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        No. BC: <strong><?= $detail->bc_number; ?></strong>
                    </li>
                     <li class="list-group-item d-flex justify-content-between align-items-center">
                        Tgl. BC: <strong><?= $detail->bc_date ? date('d M Y', strtotime($detail->bc_date)) : '-'; ?></strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm mb-3">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-tie"></i> Info Customer</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Nama Customer: <strong><?= $detail->nama_customer; ?></strong>
                    </li>
                    <li class="list-group-item">
                        Alamat:
                        <p class="mb-0"><strong><?= $detail->customer_address; ?></strong></p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning">Data detail pengiriman tidak ditemukan.</div>
<?php endif; ?>