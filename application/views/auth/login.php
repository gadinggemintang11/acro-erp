<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Login - Neokaber</title>
<link href="<?= base_url('assets/vendor/fontawesome-free/css/all.min.css'); ?>" rel="stylesheet">
<link href="<?= base_url('assets/css/sb-admin-2.min.css'); ?>" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body {
  background: linear-gradient(135deg, #1d3557, #457b9d);
  font-family: 'Poppins', sans-serif;
}
.card {
  border-radius: 1rem;
  box-shadow: 0 0 30px rgba(0,0,0,0.1);
}
</style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

  <div class="card p-4" style="width:400px;">
    <div class="text-center mb-4">
      <h4 class="text-primary font-weight-bold">NEOKABER SYSTEM</h4>
      <p class="text-muted">Login untuk melanjutkan</p>
    </div>
    <form method="post" action="<?= base_url('auth/login_action'); ?>">
      <div class="form-group">
        <input type="text" name="username" class="form-control" placeholder="Username" required>
      </div>
      <div class="form-group">
        <input type="password" name="password" class="form-control" placeholder="Password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-load">
        <i class="fas fa-sign-in-alt mr-1"></i> Login
      </button>
    </form>
  </div>

<?php if($this->session->flashdata('error')): ?>
<script>
Swal.fire({
  icon: 'error',
  title: 'Login Gagal!',
  text: '<?= $this->session->flashdata('error'); ?>'
});
</script>
<?php endif; ?>
</body>
</html>
