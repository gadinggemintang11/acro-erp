<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Edit Common Code</h6></div>
    <div class="card-body">
        <form action="<?php echo base_url('common_code/update'); ?>" method="post">
            <input type="hidden" name="id" value="<?php echo $code['id']; ?>">
            <div class="form-group"><label>Grup Kode</label><input type="text" name="code_group" class="form-control" value="<?php echo $code['code_group']; ?>" required></div>
            <div class="form-group"><label>Nama Kode</label><input type="text" name="code_name" class="form-control" value="<?php echo $code['code_name']; ?>" required></div>
            <div class="form-group"><label>Deskripsi</label><textarea name="description" class="form-control"><?php echo $code['description']; ?></textarea></div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="<?php echo base_url('common_code'); ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>