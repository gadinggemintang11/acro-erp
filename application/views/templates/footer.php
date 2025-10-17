    </div> <!-- End Content -->
  </div> <!-- End Content Wrapper -->
</div> <!-- End Wrapper -->

<!-- Footer -->
<footer class="sticky-footer bg-white shadow-sm">
  <div class="container my-auto text-center text-muted small py-2">
    © <?= date('Y'); ?> Neokaber System | Made By Gading Gemintang
  </div>
</footer>

<!-- Core JS -->

<script src="<?= base_url('assets/vendor/jquery/jquery.min.js'); ?>"></script>

<script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>

<script src="<?= base_url('assets/'); ?>vendor/select2/js/select2.min.js"></script>

<script src="<?= base_url('assets/js/sb-admin-2.min.js'); ?>"></script>


<!-- Universal Loader -->
<div id="loader-wrapper"
     style="display:none;position:fixed;z-index:9999;top:0;left:0;width:100%;height:100%;
            background:rgba(255,255,255,0.95);backdrop-filter:blur(2px);text-align:center;">
  <div class="loader"></div>
  <div id="loader-text">Mohon tunggu, sedang memproses...</div>
</div>

<style>
.loader {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  width: 70px; height: 70px;
  border: 6px solid #e0e0e0;
  border-top: 6px solid #457b9d;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}
.loader::before {
  content: "";
  position: absolute;
  top: 8px; left: 8px; right: 8px; bottom: 8px;
  border-radius: 50%;
  border: 5px solid transparent;
  border-top: 5px solid #1d3557;
  animation: spin 1.5s linear infinite reverse;
}
#loader-text {
  position: absolute; top: 58%; left: 50%;
  transform: translateX(-50%);
  font-weight: 500; color: #1d3557;
}
@keyframes spin { 
  0% { transform: rotate(0deg); } 
  100% { transform: rotate(360deg); } 
}
</style>

<!-- SweetAlert & Loader Logic -->
<script>
$(function() {
  const showLoader = () => $("#loader-wrapper").fadeIn(150);
  const hideLoader = () => $("#loader-wrapper").fadeOut(300);

  // Hide loader when page ready
  $(window).on('load', hideLoader);

  // Loader saat submit form
  $(document).on('submit', 'form', function() {
    showLoader();
    setTimeout(hideLoader, 7000); // safety agar tak nyangkut
  });

  // Loader saat klik tombol/link dengan class btn-load
  $(document).on('click', '.btn-load', function() {
    showLoader();
  });

  // DELETE handler fix (modal-safe)
  $(document).off('click', '.btn-delete').on('click', '.btn-delete', function(e) {
    e.preventDefault();
    const href = $(this).attr('href');
    if (!href) return;

    // Tutup modal aktif (hindari aria-hidden error)
    const activeModal = $('.modal.show');
    if (activeModal.length) activeModal.modal('hide');

    Swal.fire({
      title: 'Yakin ingin hapus data ini?',
      text: 'Data yang dihapus tidak dapat dikembalikan!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        showLoader();
        setTimeout(() => { window.location.href = href; }, 300);
      }
    });
  });

  // Flash message global (success, error)
  <?php if ($this->session->flashdata('success')): ?>
  Swal.fire({
    icon: 'success',
    title: 'Berhasil!',
    text: '<?= $this->session->flashdata('success'); ?>',
    timer: 2000,
    showConfirmButton: false
  });
  <?php endif; ?>

  <?php if ($this->session->flashdata('error')): ?>
  Swal.fire({
    icon: 'error',
    title: 'Gagal!',
    text: '<?= $this->session->flashdata('error'); ?>',
    confirmButtonColor: '#d33'
  });
  <?php endif; ?>

  <?php if ($this->session->flashdata('warning')): ?>
  Swal.fire({
    icon: 'warning',
    title: 'Perhatian!',
    text: '<?= $this->session->flashdata('warning'); ?>',
    confirmButtonColor: '#f39c12'
  });
  <?php endif; ?>
});
</script>

</body>
</html>
