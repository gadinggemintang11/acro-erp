<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Edit Role</h6></div>
    <div class="card-body">
        <form action="<?php echo base_url('role/update'); ?>" method="post">
            <input type="hidden" name="id" value="<?php echo $role['id']; ?>">
            <div class="form-group">
                <label>Nama Role</label>
                <input type="text" name="role_name" class="form-control" value="<?php echo $role['role_name']; ?>" required>
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" class="form-control" rows="3"><?php echo $role['description']; ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="<?php echo base_url('role'); ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>