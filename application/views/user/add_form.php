<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Tambah User Baru</h6></div>
    <div class="card-body">
        <form action="<?php echo base_url('user/save'); ?>" method="post">
            <div class="form-group"><label>Username</label><input type="text" name="username" class="form-control" required></div>
            <div class="form-group"><label>Nama Lengkap</label><input type="text" name="full_name" class="form-control" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" required></div>
            <div class="form-group"><label>Role</label>
                <select name="role_id" class="form-control" required>
                    <option value="">-- Pilih Role --</option>
                    <?php foreach($roles as $role): ?>
                    <option value="<?php echo $role['id']; ?>"><?php echo $role['role_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Status</label>
                <select name="is_active" class="form-control" required>
                    <option value="1">Aktif</option>
                    <option value="0">Tidak Aktif</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="<?php echo base_url('user'); ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>