<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Edit User</h6></div>
    <div class="card-body">
        <form action="<?php echo base_url('user/update'); ?>" method="post">
            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
            <div class="form-group"><label>Username</label><input type="text" name="username" class="form-control" value="<?php echo $user['username']; ?>" required></div>
            <div class="form-group"><label>Nama Lengkap</label><input type="text" name="full_name" class="form-control" value="<?php echo $user['full_name']; ?>" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control"><small class="form-text text-muted">Kosongkan jika tidak ingin mengubah password.</small></div>
            <div class="form-group"><label>Role</label>
                <select name="role_id" class="form-control" required>
                    <?php foreach($roles as $role): ?>
                    <option value="<?php echo $role['id']; ?>" <?php echo ($user['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                        <?php echo $role['role_name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Status</label>
                <select name="is_active" class="form-control" required>
                    <option value="1" <?php echo ($user['is_active'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                    <option value="0" <?php echo ($user['is_active'] == 0) ? 'selected' : ''; ?>>Tidak Aktif</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="<?php echo base_url('user'); ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>