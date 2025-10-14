<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Edit Aset</h6></div>
    <div class="card-body">
        <form action="<?php echo base_url('asset/update'); ?>" method="post">
            <input type="hidden" name="id" value="<?php echo $asset['id']; ?>">
            <div class="form-group"><label>Kode Aset</label><input type="text" name="asset_code" class="form-control" value="<?php echo $asset['asset_code']; ?>" required></div>
            <div class="form-group"><label>Nama Aset</label><input type="text" name="asset_name" class="form-control" value="<?php echo $asset['asset_name']; ?>" required></div>
            <div class="form-group"><label>Tipe Aset</label>
                <select name="asset_type" class="form-control" required>
                    <option value="Mesin" <?php echo ($asset['asset_type'] == 'Mesin') ? 'selected' : ''; ?>>Mesin</option>
                    <option value="Peralatan" <?php echo ($asset['asset_type'] == 'Peralatan') ? 'selected' : ''; ?>>Peralatan</option>
                    <option value="Inventaris Kantor" <?php echo ($asset['asset_type'] == 'Inventaris Kantor') ? 'selected' : ''; ?>>Inventaris Kantor</option>
                    <option value="Aset Lainnya" <?php echo ($asset['asset_type'] == 'Aset Lainnya') ? 'selected' : ''; ?>>Aset Lainnya</option>
                </select>
            </div>
            <div class="form-group"><label>Tanggal Perolehan</label><input type="date" name="acquisition_date" class="form-control" value="<?php echo $asset['acquisition_date']; ?>"></div>
            <div class="form-group"><label>Deskripsi</label><textarea name="description" class="form-control" rows="3"><?php echo $asset['description']; ?></textarea></div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="<?php echo base_url('asset'); ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>