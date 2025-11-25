<?php
// Partial View untuk Laporan 3 (Traceability)
// View ini pintar: bisa dipakai untuk Modal (AJAX) dan PDF
// Variabel $is_pdf (boolean) dikirim dari controller
$is_pdf = isset($is_pdf) ? $is_pdf : false;
?>

<?php if ($is_pdf) : ?>
    <!-- Tampilan khusus untuk PDF -->
    <html>
    <head>
        <title><?= $title ?></title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            h1 { font-size: 16px; text-align: center; }
            h2 { font-size: 14px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #000; padding: 4px; }
            th { background-color: #f0f0f0; text-align: center; }
            .info-table { width: 50%; border: none; }
            .info-table td { border: none; padding: 2px; }
            .text-right { text-align: right; }
        </style>
    </head>
    <body>
        <h1><?= $title ?></h1>
        <table class="info-table">
            <tr>
                <td><strong>No. WO</strong></td>
                <td>: <?= htmlspecialchars($wo_number) ?></td>
            </tr>
             <tr>
                <td><strong>No. SC</strong></td>
                <td>: <?= htmlspecialchars($sc_number) ?></td>
            </tr>
            <tr>
                <td><strong>Barang Jadi</strong></td>
                <td>: <?= htmlspecialchars($fg_name) ?> (<?= htmlspecialchars($fg_code) ?>)</td>
            </tr>
        </table>

<?php else : ?>
    <!-- Tampilan untuk Modal (AJAX) -->
    <div class="row">
        <div class="col-md-7">
            <!-- Info Header Modal -->
            <dl class="dl-horizontal">
                <dt>No. WO</dt>
                <dd><?= htmlspecialchars($wo_number) ?></dd>
                <dt>No. SC</dt>
                <dd><?= htmlspecialchars($sc_number) ?></dd>
                <dt>Barang Jadi</dt>
                <dd><?= htmlspecialchars($fg_name) ?> (<?= htmlspecialchars($fg_code) ?>)</dd>
            </dl>
        </div>
        <div class="col-md-5 text-right">
            <!-- Tombol Export -->
            <a href="<?= base_url('lapMutasiFinished/export_excel/' . $wo_id) ?>" class="btn btn-sm btn-success">
                <i class="fa fa-file-excel-o"></i> Export ke Excel
            </a>
            <a href="<?= base_url('lapMutasiFinished/export_pdf/' . $wo_id) ?>" target="_blank" class="btn btn-sm btn-danger">
                <i class="fa fa-file-pdf-o"></i> Export ke PDF
            </a>
        </div>
    </div>
    <hr style="margin-top: 0;">
<?php endif; ?>

<!-- Tabel Data (dipakai oleh Modal dan PDF) -->
<div class="table-responsive">
    <table class="table table-bordered table-striped" style="width:100%;" <?= !$is_pdf ? 'id="table-trace"' : '' ?>>
        <thead>
            <tr>
                <th>No.</th>
                <th>Kode Material</th>
                <th>Nama Material</th>
                <th class="text-right">Qty Issue</th>
                <th class="text-right">Qty Terpakai</th>
                <th>Satuan</th>
                <th>No. GRN</th>
                <th>Tgl. GRN</th>
                <th>No. PO</th>
                <th>Tgl. PO</th>
                <th>Supplier</th>
                <th>Tipe BC</th>
                <th>No. BC</th>
                <th>Tgl. BC</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($data_trace as $row) : ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row->kode_material) ?></td>
                    <td><?= htmlspecialchars($row->nama_material) ?></td>
                    <td class="text-right"><?= number_format($row->issued_qty, 2) ?></td>
                    <td class="text-right"><?= number_format($row->qty_used_from_this_batch, 2) ?></td>
                    <td><?= htmlspecialchars($row->satuan) ?></td>
                    <td><?= htmlspecialchars($row->grn_number) ?></td>
                    <td><?= $row->grn_date ? date('d-m-Y', strtotime($row->grn_date)) : '' ?></td>
                    <td><?= htmlspecialchars($row->po_number) ?></td>
                    <td><?= $row->po_date ? date('d-m-Y', strtotime($row->po_date)) : '' ?></td>
                    <td><?= htmlspecialchars($row->nama_supplier) ?></td>
                    <td><?= htmlspecialchars($row->bc_type_code) ?></td>
                    <td><?= htmlspecialchars($row->bc_number) ?></td>
                    <td><?= $row->bc_date ? date('d-m-Y', strtotime($row->bc_date)) : '' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($data_trace)) : ?>
                <tr>
                    <td colspan="14" class="text-center">Data material tidak ditemukan untuk WO ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($is_pdf) : ?>
    <!-- Penutup HTML untuk PDF -->
    </body>
    </html>
<?php endif; ?>
