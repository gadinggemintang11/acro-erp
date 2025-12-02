        </div> <!-- End of Main Content -->
    </div> <!-- End of Content Wrapper -->
</div> <!-- End of Page Wrapper -->

<!-- Footer -->
<footer class="sticky-footer bg-white shadow-sm">
  <div class="container my-auto text-center text-muted small py-2">
    © <?= date('Y'); ?> ERP MINGBAO | Made By PT MINGBAO LIANCHUANG INTERNATIONAL
  </div>
</footer>

<!-- Scroll to Top Button -->
<a class="scroll-to-top rounded" href="#page-top">
  <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-gradient-primary text-white">
        <h5 class="modal-title" id="logoutModalLabel">Yakin ingin Logout?</h5>
        <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">Pilih "Logout" di bawah jika Anda siap mengakhiri sesi Anda saat ini.</div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
        <a class="btn btn-primary" href="<?= base_url('auth/logout'); ?>">Logout</a>
      </div>
    </div>
  </div>
</div>

<!-- ==============================================================
     JAVASCRIPT CORE
=============================================================== -->
<script src="<?= base_url('assets/vendor/jquery/jquery.min.js'); ?>"></script>
<script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?= base_url('assets/vendor/jquery-easing/jquery.easing.min.js'); ?>"></script>

<!-- Select2 -->
<script src="<?= base_url('assets/vendor/select2/js/select2.min.js'); ?>"></script>

<!-- AutoNumeric -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.6.0/autoNumeric.min.js"></script>

<!-- DataTables -->
<script src="<?= base_url('assets/vendor/datatables/jquery.dataTables.min.js'); ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/dataTables.bootstrap4.min.js'); ?>"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- SB Admin 2 -->
<script src="<?= base_url('assets/js/sb-admin-2.min.js'); ?>"></script>
<!-- DataTables Global Config -->
<script src="<?= base_url('assets/js/datatables.config.js'); ?>"></script>

<!-- ==============================================================
     GLOBAL LOADER
=============================================================== -->
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
  position: absolute;
  top: calc(50% + 80px);
  left: 50%;
  transform: translateX(-50%);
  font-weight: 500;
  color: #1d3557;
  font-size: 14px;
}
@keyframes spin { 
  0% { transform: rotate(0deg); } 
  100% { transform: rotate(360deg); } 
}
</style>

<!-- ==============================================================
     MAIN SCRIPT
=============================================================== -->
<script>
$(function() {

  // Sidebar Toggle Fix
  $('#sidebarToggle, #sidebarToggleTop').off('click').on('click', function() {
    $('body').toggleClass('sidebar-toggled');
    $('.sidebar').toggleClass('toggled');
    if ($('.sidebar').hasClass('toggled')) {
      $('.sidebar .collapse').collapse('hide');
    }
  });

  // Loader functions
  const showLoader = (text = 'Mohon tunggu, sedang memproses...') => {
    $("#loader-text").text(text);
    $("#loader-wrapper").fadeIn(200);
  };
  const hideLoader = () => $("#loader-wrapper").fadeOut(300);

  // Saat halaman selesai dimuat
  $(window).on('load', hideLoader);
  if (document.readyState === 'complete') hideLoader();

  // Loader saat submit form (kecuali form.no-loader)
  $(document).on('submit', 'form:not(.no-loader)', function() {
    showLoader();
    setTimeout(hideLoader, 8000); // backup jika proses lama
  });

  // Loader saat klik tombol/link dengan class .btn-load
  $(document).on('click', 'a.btn-load, button.btn-load', function() {
    if ($(this).closest('form').length === 0) {
      showLoader();
    }
  });

  // Cegah double submit form
  $(document).on('submit', 'form', function(e) {
    var $form = $(this);
    if ($form.data('submitted') === true) {
      e.preventDefault();
    } else {
      $form.data('submitted', true);
      $form.find('button[type="submit"]').prop('disabled', true).addClass('disabled');
    }
  });

  // DELETE handler
  $(document).on('click', '.btn-delete', function(e) {
    e.preventDefault();
    const href = $(this).attr('href');
    const message = $(this).data('message') || 'Yakin ingin hapus data ini?';
    const text = $(this).data('text') || 'Data yang dihapus tidak dapat dikembalikan!';
    if (!href) return;

    Swal.fire({
      title: message,
      text: text,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#e74a3b',
      cancelButtonColor: '#858796',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        showLoader('Menghapus data...');
        setTimeout(() => { window.location.href = href; }, 300);
      }
    });
  });

  // CONFIRM handler
  $(document).on('click', '.btn-confirm, .btn-submit', function(e) {
    e.preventDefault();
    const href = $(this).attr('href');
    const message = $(this).data('message') || 'Apakah Anda yakin ingin melanjutkan?';
    const text = $(this).data('text') || '';
    const confirmText = $(this).data('confirm-text') || 'Ya, lanjutkan!';
    const icon = $(this).data('icon') || 'question';
    if (!href) return;

    Swal.fire({
      title: message,
      text: text,
      icon: icon,
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: 'Batal',
      confirmButtonColor: '#1cc88a',
      cancelButtonColor: '#858796',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        showLoader('Memproses...');
        setTimeout(() => { window.location.href = href; }, 300);
      }
    });
  });

  // Flash message global
  <?php if ($this->session->flashdata('success')): ?>
    Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: '<?= addslashes($this->session->flashdata('success')); ?>',
      timer: 2500,
      showConfirmButton: false
    });
  <?php endif; ?>

  <?php if ($this->session->flashdata('error')): ?>
    Swal.fire({
      icon: 'error',
      title: 'Gagal!',
      text: '<?= addslashes($this->session->flashdata('error')); ?>',
      confirmButtonColor: '#e74a3b'
    });
  <?php endif; ?>

  <?php if ($this->session->flashdata('warning')): ?>
    Swal.fire({
      icon: 'warning',
      title: 'Perhatian!',
      text: '<?= addslashes($this->session->flashdata('warning')); ?>',
      confirmButtonColor: '#f6c23e'
    });
  <?php endif; ?>

  // Optional: Auto init DataTables / Select2
  // $('table.table').DataTable();
  // $('select').select2();

});
</script>

</body>
</html>
