<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Hak Akses Role: <?php echo $role['role_name']; ?></h1>
</div>

<?php if($this->session->flashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $this->session->flashdata('success'); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
</div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold text-primary">Pilih Modul yang Dapat Diakses</h6>
    </div>
    <div class="card-body">
        <form action="<?php echo base_url('role/save_access'); ?>" method="post">
            <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
            <div class="row">
                <?php foreach($controllers as $controller): ?>
                <div class="col-md-3 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="controllers[]" value="<?php echo $controller; ?>" id="check_<?php echo $controller; ?>"
                            <?php echo in_array($controller, $existing_controllers) ? 'checked' : ''; ?> >
                        <label class="form-check-label" for="check_<?php echo $controller; ?>">
                            <?php echo ucfirst($controller); // Membuat huruf pertama besar ?>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <hr>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="<?php echo base_url('role'); ?>" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>