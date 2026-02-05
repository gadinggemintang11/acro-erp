<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    
    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Header Sesi Opname</h6>
        </div>
        <form action="<?= base_url('stockopname/create'); ?>" method="POST">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Tanggal Opname *</label>
                            <input type="date" name="opname_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Tipe Item yang Akan Dihitung *</label>
                            <select name="item_type" class="form-control" required>
                                <option value="">-- Pilih Tipe --</option>
                                <option value="finished_goods">Finished Goods</option>
                                <option value="material">Material</option>
                                <option value="semifinished">Semifinished</option>
                                <option value="scrap">Scrap</option>
                                <option value="inventory">Inventory</option>
                                <option value="machine">Machine</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Catatan/Keterangan *</label>
                            <input type="text" name="notes" class="form-control" placeholder="cth: Opname Bulanan Gudang Bahan Baku" required>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <a href="<?= base_url('stockopname'); ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Buat Sesi & Generate List
                </button>
            </div>
        </form>
    </div>
</div>