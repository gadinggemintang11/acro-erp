<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Tambah Material Baru</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Form Input Material</h6>
    </div>
    <div class="card-body">
        <form action="<?php echo base_url('material/save'); ?>" method="post">
            <div class="form-group row">
                <label for="material_category" class="col-sm-2 col-form-label">Kategori</label>
                <div class="col-sm-6">
                    <select class="form-control" id="material_category" name="material_category" required>
                        <option value="Bahan Baku">Bahan Baku</option>
                        <option value="Bahan Penolong">Bahan Penolong</option>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label for="sub_category" class="col-sm-2 col-form-label">Sub-Kategori</label>
                <div class="col-sm-6">
                    <select class="form-control" id="sub_category" name="sub_category" required>
                        <option value="Kain">Kain (A)</option>
                        <option value="Outsole">Outsole (B)</option>
                        <option value="Insole">Insole (C)</option>
                        </select>
                </div>
            </div>

            <div class="form-group row">
                <label for="item_name" class="col-sm-2 col-form-label">Nama Spesifik Barang</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="item_name" name="item_name" placeholder="Contoh: Canvas 12oz, Rubber Cupsole, EVA Phylon" required>
                </div>
            </div>

            <div class="form-group row">
                <label for="item_type" class="col-sm-2 col-form-label">Tipe Barang</label>
                <div class="col-sm-4">
                    <select class="form-control" id="item_type" name="item_type" required>
                        <option value="import">IMPORT</option>
                        <option value="lokal">LOKAL</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group row">
                <label for="unit" class="col-sm-2 col-form-label">Satuan</label>
                <div class="col-sm-4">
                    <input type="text" class="form-control" id="unit" name="unit" placeholder="Contoh: MTR, PAIRS, SHEET" required>
                </div>
            </div>
             <div class="form-group row">
                <label for="hs_code" class="col-sm-2 col-form-label">HS Code</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" id="hs_code" name="hs_code">
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-10 offset-sm-2">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="<?php echo base_url('material'); ?>" class="btn btn-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>