<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Tambah Aset Baru</h6></div>
    <div class="card-body">
        <form action="<?php echo base_url('asset/save'); ?>" method="post">
            <div class="form-group"><label>Kode Aset</label><input type="text" name="asset_code" class="form-control" required></div>
            <div class="form-group"><label>Nama Aset</label><input type="text" name="asset_name" class="form-control" required></div>
            <div class="form-group"><label>Tipe Aset</label>
                <select name="asset_type" class="form-control" required>
                    <option value="Mesin">Mesin</option>
                    <option value="Peralatan">Peralatan</option>
                    <option value="Inventaris Kantor">Inventaris Kantor</option>
                    <option value="Aset Lainnya">Aset Lainnya</option>
                </select>
            </div>
            <div class="form-group"><label>Tanggal Perolehan</label><input type="date" name="acquisition_date" class="form-control"></div>
            <div class="form-group"><label>Deskripsi</label><textarea name="description" class="form-control" rows="3"></textarea></div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="<?php echo base_url('asset'); ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>