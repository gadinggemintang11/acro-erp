<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('stockopname'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali ke Daftar</a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong class="text-primary">No. Dokumen:</strong><br>
                    <?= $header->opname_no; ?>
                </div>
                <div class="col-md-3">
                    <strong class="text-primary">Tanggal:</strong><br>
                    <?= date('d M Y', strtotime($header->opname_date)); ?>
                </div>
                <div class="col-md-3">
                    <strong class="text-primary">Tipe Item:</strong><br>
                    <span class="badge badge-info"><?= $header->item_type; ?></span>
                </div>
                <div class="col-md-3">
                    <strong class="text-primary">Status:</strong><br>
                    <span class="badge badge-warning"><?= $header->status; ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <form action="<?= base_url('stockopname/save_counts/' . $header->id); ?>" method="POST">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Item untuk Dihitung</h6>
                <div>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-save"></i> Simpan Hasil Hitungan
                    </button>
                    <a href="<?= base_url('stockopname/confirm/' . $header->id); ?>" class="btn btn-primary btn-sm" onclick="return confirm('ANDA YAKIN INGIN KONFIRMASI?\nSelisih stok akan otomatis disesuaikan (Adjustment) dan tidak bisa diubah lagi.');">
                        <i class="fas fa-check-circle"></i> Konfirmasi Opname
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="opnameTable" width="100%" cellspacing="0">
                        <thead>
                            <tr class="text-center">
                                <th style="width: 25%;">Nama Item</th>
                                <th style="width: 15%;">Qty Sistem</th>
                                <th style="width: 20%;">Qty Fisik (Input)</th>
                                <th style="width: 15%;">Selisih</th>
                                <th style="width: 25%;">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($details as $d): 
                                $selisih = ($d->qty_fisik !== NULL) ? $d->qty_fisik - $d->qty_sistem : 0 - $d->qty_sistem;
                                $color = 'secondary';
                                if ($selisih > 0) $color = 'success'; // Fisik > Sistem (Plus)
                                if ($selisih < 0) $color = 'danger';  // Fisik < Sistem (Minus)
                            ?>
                            <tr>
                                <td>
                                    <?= $d->item_name; ?>
                                    <small class="text-muted d-block"><?= $d->item_unit; ?></small>
                                </td>
                                <td class="text-right align-middle">
                                    <strong><?= (float)$d->qty_sistem; ?></strong>
                                </td>
                                <td class="p-1 align-middle">
                                    <input type="number" 
                                           name="details[<?= $d->id; ?>][qty_fisik]" 
                                           class="form-control form-control-sm qty-fisik" 
                                           value="<?= $d->qty_fisik; ?>"
                                           step="0.0001" 
                                           data-row-id="<?= $d->id; ?>"
                                           data-qty-sistem="<?= $d->qty_sistem; ?>"
                                           required>
                                </td>
                                <td class="text-right align-middle">
                                    <strong id="selisih-<?= $d->id; ?>" class="text-<?= $color; ?>">
                                        <?= (float)$selisih; ?>
                                    </strong>
                                </td>
                                <td class="p-1 align-middle">
                                    <input type="text" 
                                           name="details[<?= $d->id; ?>][notes]" 
                                           class="form-control form-control-sm"
                                           value="<?= $d->notes; ?>"
                                           placeholder="cth: rusak, hilang, dll">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const table = document.getElementById("opnameTable");
    
    table.addEventListener("input", function(e) {
        if (e.target.classList.contains("qty-fisik")) {
            const input = e.target;
            const rowId = input.dataset.rowId;
            const qtySistem = parseFloat(input.dataset.qtySistem);
            const qtyFisik = parseFloat(input.value) || 0;
            
            const selisih = qtyFisik - qtySistem;
            
            const selisihEl = document.getElementById(`selisih-${rowId}`);
            selisihEl.textContent = selisih.toFixed(4).replace(/\.?0+$/, ''); // Format angka
            
            selisihEl.classList.remove('text-secondary', 'text-success', 'text-danger');
            if (selisih > 0) {
                selisihEl.classList.add('text-success');
            } else if (selisih < 0) {
                selisihEl.classList.add('text-danger');
            } else {
                selisihEl.classList.add('text-secondary');
            }
        }
    });
});
</script>