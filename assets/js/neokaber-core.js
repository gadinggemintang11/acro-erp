/* ============================================================
   Neokaber Core JavaScript (Stable Build)
   - Loader Handler
   - SweetAlert Global (Delete & Flash)
   - Safe Modal Integration
   - AJAX Loader
   ============================================================ */

$(document).ready(function() {

  /* =========================================================
   * UNIVERSAL LOADER HANDLER
   * ========================================================= */
  const showLoader = () => $("#loader-wrapper").fadeIn(200);
  const hideLoader = () => $("#loader-wrapper").fadeOut(300);

  // Saat window selesai load
  $(window).on('load', hideLoader);

  // Loader saat form submit
  $(document).on('submit', 'form', function() {
    showLoader();
    // jangan pernah block form submit
    setTimeout(hideLoader, 8000); // fallback aman
  });

  // Loader saat klik tombol dengan class .btn-load
  $(document).on('click', '.btn-load', function() {
    showLoader();
  });

  // Loader saat pindah halaman (window unload)
  window.addEventListener("beforeunload", function() {
    showLoader();
    setTimeout(hideLoader, 10000);
  });

  /* =========================================================
   * DELETE CONFIRMATION (SweetAlert)
   * ========================================================= */
  $(document).off('click', '.btn-delete').on('click', '.btn-delete', function(e) {
    e.preventDefault();

    const href = $(this).attr('href') || $(this).data('url');
    if (!href) return;

    // Tutup modal aktif agar SweetAlert tidak bentrok
    const activeModal = $('.modal.show');
    if (activeModal.length) {
      activeModal.modal('hide');
      activeModal.one('hidden.bs.modal', function() {
        confirmDelete(href);
      });
    } else {
      confirmDelete(href);
    }
  });

  function confirmDelete(href) {
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
  }

  /* =========================================================
   * FLASH MESSAGE HANDLER
   * ========================================================= */
  if (typeof flashSuccess !== 'undefined' && flashSuccess) {
    Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: flashSuccess,
      timer: 2000,
      showConfirmButton: false,
      timerProgressBar: true
    });
  }
  if (typeof flashError !== 'undefined' && flashError) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal!',
      text: flashError,
      confirmButtonColor: '#d33'
    });
  }
  if (typeof flashWarning !== 'undefined' && flashWarning) {
    Swal.fire({
      icon: 'warning',
      title: 'Peringatan!',
      text: flashWarning,
      confirmButtonColor: '#f39c12'
    });
  }

  /* =========================================================
   * AJAX SUPPORT (Loader otomatis saat request aktif)
   * ========================================================= */
  $(document).ajaxStart(showLoader).ajaxStop(hideLoader);

});
