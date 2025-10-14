<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Tambah Role Baru</h6></div>
    <div class="card-body">
        <form action="<?php echo base_url('role/save'); ?>" method="post">
            <div class="form-group">
                <label>Nama Role</label>
                <input type="text" name="role_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="<?php echo base_url('role'); ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>