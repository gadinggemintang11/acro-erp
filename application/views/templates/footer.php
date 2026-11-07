    </div> <!-- End of Main Content/content -->

    <!-- Footer -->
    <footer class="sticky-footer bg-white shadow-sm">
      <div class="container my-auto text-center py-2">
        <span class="text-muted small">
          <i class="far fa-copyright"></i> <?= date('Y'); ?> <?= htmlspecialchars(company_info('app_name')) ?> | Made By 
          <span class="font-weight-bold text-primary" style="transition: color 0.3s ease;" onmouseover="this.style.color='#224abe'" onmouseout="this.style.color=''">
            <?= htmlspecialchars(company_info('company_name')) ?>
          </span>
        </span>
      </div>
    </footer>
</div> <!-- End of Content Wrapper -->

<script>
  // Safety: if footer ended up outside #content-wrapper (due to a view accidentally closing wrappers),
  // move it back under #content-wrapper so layout (sticky footer) works consistently.
  (function(){
    try {
      var footer = document.querySelector('footer.sticky-footer');
      var cw = document.getElementById('content-wrapper');
      if (footer && cw && !cw.contains(footer)) {
        cw.appendChild(footer);
      }
    } catch (e) {
      // silently ignore
      console && console.warn && console.warn('Footer relocation failed', e);
    }
  })();
</script>

<!-- Scroll to Top Button -->
<a class="scroll-to-top rounded" href="#page-top">
  <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header text-white" style="background: linear-gradient(90deg, var(--brand-1), var(--brand-2));">
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

<!-- Toastr Notifications -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<!-- SB Admin 2 -->
<script src="<?= base_url('assets/js/sb-admin-2.min.js'); ?>"></script>
<!-- DataTables Global Config -->
<script src="<?= base_url('assets/js/datatables.config.js'); ?>"></script>

<!-- ==============================================================
     GLOBAL LOADER
=============================================================== -->
<?php
// Elemen loader-nya sendiri ada di templates/header.php. Dulu di sini ada
// salinan dengan id yang SAMA — tidak pernah terpakai (jQuery hanya mengambil
// elemen pertama) tapi bikin bingung. Yang tinggal di bawah cuma gaya
// tambahan (cincin dalam + posisi teks) & fungsinya.
?>

<style>
.loader {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  width: 70px; height: 70px;
  border: 6px solid #e0e0e0;
  border-top: 6px solid var(--brand-1);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}
.loader::before {
  content: "";
  position: absolute;
  top: 8px; left: 8px; right: 8px; bottom: 8px;
  border-radius: 50%;
  border: 5px solid transparent;
  border-top: 5px solid var(--brand-dark);
  animation: spin 1.5s linear infinite reverse;
}
#loader-text {
  position: absolute;
  top: calc(50% + 80px);
  left: 50%;
  transform: translateX(-50%);
  font-weight: 500;
  color: var(--brand-1);
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
// Toastr Configuration
toastr.options = {
  "closeButton": true,
  "debug": false,
  "newestOnTop": true,
  "progressBar": true,
  "positionClass": "toast-top-right",
  "preventDuplicates": false,
  "onclick": null,
  "showDuration": "300",
  "hideDuration": "1000",
  "timeOut": "3000",
  "extendedTimeOut": "1000",
  "showEasing": "swing",
  "hideEasing": "linear",
  "showMethod": "fadeIn",
  "hideMethod": "fadeOut"
};

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

  // Dipakai view lain (mis. konfirmasi dulu baru submit) — lihat catatan di
  // handler submit di bawah.
  window.showLoader = showLoader;
  window.hideLoader = hideLoader;

  // Loader saat submit form (kecuali form.no-loader). Submit yang DICEGAH
  // (ada dialog konfirmasi dulu, atau form dikirim lewat AJAX) dilewati:
  // dulu overlay-nya terlanjur muncul menutupi dialog konfirmasi dan baru
  // hilang setelah 8 detik, sehingga terasa "loading lama" padahal proses
  // simpannya sendiri cuma sepersekian detik (user 2026-09-21).
  $(document).on('submit', 'form:not(.no-loader)', function(e) {
    if (e.isDefaultPrevented()) return;
    showLoader();
    setTimeout(hideLoader, 8000); // backup jika proses lama
  });

  // Halaman dibuka lagi dari cache "Back/Forward" browser: overlay yang
  // sempat tampil saat meninggalkan halaman ikut tersimpan -> sembunyikan.
  $(window).on('pageshow', hideLoader);

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
    const cancelText = $(this).data('cancel-text') || 'Batal';
    const icon = $(this).data('icon') || 'question';
    if (!href) return;

    Swal.fire({
      title: message,
      text: text,
      icon: icon,
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
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
    <?php
        // Popup galat boleh membawa SATU tombol aksi lanjutan (opsional),
        // dikirim controller lewat flashdata 'error_action':
        //     ['label' => 'Buat PR', 'url' => base_url('...')]
        // Dipakai 2026-09-09 di Consumable_out: kalau stok tidak cukup,
        // user bisa langsung lompat membuat PR dari popup itu juga
        // (user: "sediakan juga button untuk membuat PR nya"), tidak perlu
        // menutup popup lalu mencari menunya sendiri.
        // Tanpa 'error_action', popup tampil persis seperti sebelumnya.
        $err_action = $this->session->flashdata('error_action');
        $has_action = is_array($err_action) && !empty($err_action['url']) && !empty($err_action['label']);
    ?>
    Swal.fire({
      icon: 'error',
      title: 'Gagal!',
      text: '<?= addslashes($this->session->flashdata('error')); ?>',
      confirmButtonColor: '#e74a3b',
      confirmButtonText: '<?= $has_action ? 'Tutup' : 'OK' ?>'
      <?php if ($has_action): ?>
      , showDenyButton: true
      , denyButtonText: '<?= addslashes($err_action['label']); ?>'
      , denyButtonColor: '#4e73df'
      , reverseButtons: true
      <?php endif; ?>
    })<?php if ($has_action): ?>.then(function (r) {
      if (r.isDenied) window.location.href = '<?= addslashes($err_action['url']); ?>';
    })<?php endif; ?>;
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

<!-- ==============================================================
     REVISI 2026-09-02: Button "Kembali/Back" jadi sticky-floating
     Lintas modul — auto-detect, BUKAN edit satu-satu view (ratusan
     halaman pakai label/lang_line beda-beda utk tombol back). Kalau
     ada tombol back yang TIDAK boleh ikut jadi floating, kasih class
     "no-float-back" di elemennya.

     REVISI 2026-09-04 (user lapor screenshot: tombol "Kembali" nabrak
     tombol lain di pojok kanan-atas halaman, mis. "Upload Dokumen" di
     Document Sharing): sebelumnya class `btn-back-floating` (position:
     fixed) dipasang PERMANEN sejak page-load, jadi kalau halaman itu
     KEBETULAN sudah punya tombol lain persis di pojok kanan-atas (area
     header halaman), keduanya numpuk. Diperbaiki jadi BENERAN
     "sticky-on-scroll": tombol tetap di posisi ASLINYA di dalam alur
     halaman (jadi otomatis sejajar rapi dgn tombol header lain, TIDAK
     numpuk) selama posisi itu masih kelihatan; baru begitu ke-scroll
     lewat, class `btn-back-floating` dipasang (posisi ASLI-nya sudah di
     luar layar duluan, jadi tidak mungkin numpuk apa pun lagi).
=============================================================== -->
<script>
(function() {
  // Cuma tombol PERTAMA yang cocok per halaman — position:fixed di pojok
  // yang sama, kalau ada >1 kandidat nanti malah numpuk saling tutupan.
  var candidates = document.querySelectorAll('a.btn, button.btn');
  var target = null;
  for (var i = 0; i < candidates.length; i++) {
    var el = candidates[i];
    if (el.classList.contains('no-float-back')) continue;
    if (!el.querySelector('.fa-arrow-left')) continue;
    var text = (el.textContent || '').trim().toLowerCase();
    if (!/kembali|\bback\b/.test(text)) continue;
    target = el;
    break;
  }
  if (!target) return;

  // PENTING: batas pemicu float HARUS beda dari posisi TUJUAN float
  // (`top:90px` di CSS) — kalau dipakai angka yang SAMA (90) ternyata
  // hampir SEMUA halaman di app ini punya baris header yang secara wajar
  // ada di sekitar y=90-100px juga (topbar 70px + padding container
  // `py-4`=24px), jadi perbandingannya nyaris SELALU langsung "true" pas
  // page-load — tombol ke-floating dari awal lagi persis kayak bug lama
  // (baru ketauan dari screenshot user KEDUA kali, folder Document
  // Sharing). Yang benar: pemicu float itu "posisi asli tombol sudah
  // scroll ke/lewat TEPI ATAS VIEWPORT" (~0), bukan dibandingkan ke titik
  // tujuan floating-nya.
  var SCROLL_TRIGGER = 20;
  var originalDocY = null;

  function measure() {
    // Ukur posisi ASLI (bukan floating) — cuma valid dipanggil SAAT
    // elemen belum/tidak sedang position:fixed.
    var wasFloating = target.classList.contains('btn-back-floating');
    if (wasFloating) target.classList.remove('btn-back-floating');
    var rect = target.getBoundingClientRect();
    originalDocY = rect.top + window.pageYOffset;
    if (wasFloating) target.classList.add('btn-back-floating');
  }

  function update() {
    if (originalDocY === null) return;
    var currentTop = originalDocY - window.pageYOffset;
    if (currentTop < SCROLL_TRIGGER) target.classList.add('btn-back-floating');
    else target.classList.remove('btn-back-floating');
  }

  measure();
  update();
  window.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', function() { measure(); update(); });
})();
</script>

<!-- ==============================================================
     REVISI 2026-09-02: Sidebar bisa di-drag utk diperlebar/dipersempit
     Lebar tersimpan per browser (localStorage), cuma aktif di desktop
     (>=768px) & saat sidebar tidak dalam mode toggled/collapsed.
=============================================================== -->
<script>
(function() {
  var sidebar = document.getElementById('accordionSidebar');
  var wrapper = document.getElementById('wrapper');
  if (!sidebar || !wrapper || window.innerWidth < 768) return;

  var MIN_WIDTH = 180;
  var MAX_WIDTH = 400;
  var STORAGE_KEY = 'nsi_sidebar_width';

  // sb-admin-2.css nge-set ".sidebar { width: 14rem !important; }" di desktop
  // (@media min-width:768px) — inline style biasa (style.width = ...) KALAH
  // lawan !important di stylesheet, makanya drag sebelumnya tidak kelihatan
  // efeknya sama sekali. Pakai setProperty(...,'important') supaya menang.
  //
  // Barengan itu, font sidebar (lihat --sb-font-scale di style.css) ikut
  // sedikit membesar mengikuti lebar — dari 1x di MIN_WIDTH sampai ~1.12x
  // di MAX_WIDTH, biar "dikit aja" bukan melonjak.
  function setSidebarWidth(px) {
    px = Math.max(MIN_WIDTH, Math.min(MAX_WIDTH, px));
    sidebar.style.setProperty('width', px + 'px', 'important');
    var ratio = (px - MIN_WIDTH) / (MAX_WIDTH - MIN_WIDTH);
    var scale = 1 + ratio * 0.12;
    sidebar.style.setProperty('--sb-font-scale', scale.toFixed(3));
  }
  function applyStoredWidth() {
    if (sidebar.classList.contains('toggled')) return;
    var saved = localStorage.getItem(STORAGE_KEY);
    if (saved) setSidebarWidth(saved);
  }
  applyStoredWidth();

  // Kalau sidebar di-toggle (collapse/expand) oleh tombol yang sudah ada,
  // lepas dulu lebar custom saat collapsed (biar CSS bawaan .toggled yang
  // menang), lalu pasang lagi begitu expand.
  var mo = new MutationObserver(function() {
    if (sidebar.classList.contains('toggled')) {
      sidebar.style.removeProperty('width');
    } else {
      applyStoredWidth();
    }
  });
  mo.observe(sidebar, { attributes: true, attributeFilter: ['class'] });

  var handle = document.createElement('div');
  handle.id = 'sidebarResizeHandle';
  handle.title = 'Geser untuk mengubah lebar sidebar';
  sidebar.insertAdjacentElement('afterend', handle);

  var dragging = false;
  handle.addEventListener('mousedown', function(e) {
    if (sidebar.classList.contains('toggled')) return;
    dragging = true;
    document.body.style.userSelect = 'none';
    e.preventDefault();
  });
  document.addEventListener('mousemove', function(e) {
    if (!dragging) return;
    var newWidth = Math.max(MIN_WIDTH, Math.min(MAX_WIDTH, e.clientX));
    setSidebarWidth(newWidth);
  });
  document.addEventListener('mouseup', function() {
    if (!dragging) return;
    dragging = false;
    document.body.style.userSelect = '';
    var w = parseInt(sidebar.style.width, 10);
    if (w) localStorage.setItem(STORAGE_KEY, w);
  });
})();
</script>

<?php // ==============================================================
      // "Developer Mode" — klik teks langsung di halaman utk edit
      // terjemahan (ERP Revision Notes, user 2026-09-03). Overlay ini
      // SENGAJA tidak menyentuh cara lang_line() mengembalikan nilainya —
      // cuma nambah lapisan di sisi browser SETELAH halaman selesai
      // dirender, jadi tidak beresiko merusak pemakaian lang_line() di
      // atribut HTML/JSON di tempat lain. Aktif kalau user punya akses
      // menu 'developer_mode' DAN sudah nyalain toggle-nya (topbar.php).
      // Lihat juga: application/helpers/language_helper.php
      // (get_devmode_page_calls()) & Developer_mode.php (save()/toggle()).
      // ============================================================== ?>
<?php // Overlay klik-teks-untuk-edit HANYA aktif kalau user memilih mode
      // 'Edit Teks' di banner (user 2026-09-06) — dulu selalu aktif begitu
      // Developer Mode nyala, sehingga mengganggu mode lain spt Atur Kolom. ?>
<?php if (devmode_is('text')): ?>
<script>window.__DEVMODE_CALLS__ = <?= json_encode(get_devmode_page_calls(), JSON_UNESCAPED_UNICODE); ?>;</script>
<style>
.devmode-editable { outline: 1px dashed #f6c23e; outline-offset: 1px; cursor: pointer; }
.devmode-editable:hover { background-color: rgba(246, 194, 62, .25); }
#devmodePopover {
    position: fixed; z-index: 20000; background: #fff; border: 1px solid #d1d3e2;
    border-radius: 6px; box-shadow: 0 4px 16px rgba(0,0,0,.2); padding: 10px;
    width: 320px; display: none; font-size: 13px;
}
#devmodePopover .devmode-key { color: #858796; font-size: 11px; margin-bottom: 4px; word-break: break-all; }
#devmodePopover textarea { width: 100%; font-size: 13px; border: 1px solid #d1d3e2; border-radius: 4px; padding: 4px 6px; margin-bottom: 6px; }
#devmodePopover .devmode-actions { display: flex; gap: 6px; justify-content: flex-end; }
</style>
<div id="devmodePopover">
    <div class="devmode-key" id="devmodePopoverKey"></div>
    <textarea id="devmodePopoverInput" rows="2"></textarea>
    <div class="devmode-actions">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="devmodePopoverCancel"><?= lang_line('btn_cancel', 'Batal'); ?></button>
        <button type="button" class="btn btn-sm btn-primary" id="devmodePopoverSave"><?= lang_line('devmode_btn_save', 'Simpan'); ?></button>
    </div>
    <div class="small text-muted mt-1">
        <a href="#" id="devmodePopoverAllLang" target="_blank"><?= lang_line('devmode_edit_all_lang', 'Edit semua bahasa (Developer Mode)'); ?></a>
    </div>
</div>
<script>
(function() {
    var calls = window.__DEVMODE_CALLS__ || [];
    // Peta value -> antrian key (FIFO) — teks yang sama bisa dipakai
    // banyak key beda, dicocokkan sesuai urutan render vs urutan DOM.
    var queues = {};
    calls.forEach(function(pair) {
        var k = pair[0], v = pair[1];
        if (!v || typeof v !== 'string') return;
        var trimmed = v.trim();
        if (trimmed === '') return;
        if (!queues[trimmed]) queues[trimmed] = [];
        queues[trimmed].push(k);
    });

    var SKIP_TAGS = { SCRIPT: 1, STYLE: 1, NOSCRIPT: 1, TEXTAREA: 1, INPUT: 1, TITLE: 1 };

    function wrapTextNodes(root) {
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode: function(node) {
                var p = node.parentNode;
                if (!p || !p.tagName || SKIP_TAGS[p.tagName]) return NodeFilter.FILTER_REJECT;
                // Yang dikecualikan CUMA "chrome" milik Developer Mode sendiri:
                //  - #devmodePopover : kotak editornya sendiri
                //  - #devmodeBanner  : banner pemilih mode + tombol "Akhiri
                //    Developer Mode". WAJIB tetap bisa diklik, ini satu-satunya
                //    jalan keluar dari mode edit teks (kalau ikut dibungkus,
                //    user terjebak tidak bisa mematikan modenya).
                //
                // #accordionSidebar (sidebar) SEMPAT ikut dikecualikan
                // 2026-09-06 karena DIKIRA penyebab "about:blank#blocked".
                // Dugaan itu SALAH -- akar masalahnya ternyata
                // DOMDocument::saveHTML() merusak host IPv6 di fitur Atur
                // Urutan Sidebar (lihat sidebar_save_html() di
                // sidebar_order_helper.php), sudah diperbaiki terpisah.
                // Pengecualiannya dicabut 2026-09-07 atas permintaan user
                // ("harusnya kan semua bisa") -- teks sidebar sekarang bisa
                // diedit klik-langsung seperti bagian halaman lain. Aman
                // karena mode edit teks sekarang OPT-IN (dipilih di banner),
                // jadi navigasi normal tidak terganggu.
                if (p.closest && (p.closest('#devmodePopover') || p.closest('#devmodeBanner'))) return NodeFilter.FILTER_REJECT;
                var text = node.nodeValue.trim();
                if (text === '' || !queues[text] || !queues[text].length) return NodeFilter.FILTER_REJECT;
                return NodeFilter.FILTER_ACCEPT;
            }
        });

        var nodes = [];
        var n;
        while ((n = walker.nextNode())) nodes.push(n);

        nodes.forEach(function(node) {
            var text = node.nodeValue.trim();
            if (!queues[text] || !queues[text].length) return; // sudah "diklaim" node lain di batch ini
            var key = queues[text].shift();

            var span = document.createElement('span');
            span.className = 'devmode-editable';
            span.setAttribute('data-devmode-key', key);
            span.textContent = node.nodeValue;
            node.parentNode.replaceChild(span, node);
        });
    }

    // Judul segmen sidebar (<h6 data-gk="sidebar_finance_group">FINANCE</h6>)
    // dicocokkan lewat ATRIBUT, bukan lewat teksnya. Alasannya: teks "REPORT:"
    // dipakai 6 segmen berbeda, dan urutan sidebar bisa diubah lewat "Atur
    // Urutan Sidebar" sehingga urutan DOM tidak lagi sama dengan urutan
    // pemanggilan lang_line() -- pencocokan berdasarkan teks bisa mengenai
    // segmen yang salah (user 2026-09-16: "ganti text untuk segment seperti
    // finance, report dll itu bisa diganti?").
    function wrapByKeyAttribute(root) {
        Array.prototype.forEach.call(root.querySelectorAll('[data-gk]'), function(el) {
            var key = el.getAttribute('data-gk');
            // Hanya elemen berisi teks polos (judul segmen). Kalau ada elemen
            // anak, isinya tidak boleh ditimpa.
            if (!key || el.children.length || el.querySelector('.devmode-editable')) return;
            var text = (el.textContent || '').trim();
            if (text === '') return;
            // Lepas key ini dari antrian teks supaya tidak diklaim dua kali.
            if (queues[text]) {
                var i = queues[text].indexOf(key);
                if (i >= 0) queues[text].splice(i, 1);
            }
            var span = document.createElement('span');
            span.className = 'devmode-editable';
            span.setAttribute('data-devmode-key', key);
            span.textContent = el.textContent;
            el.textContent = '';
            el.appendChild(span);
        });
    }

    function init() {
        wrapByKeyAttribute(document.body);
        wrapTextNodes(document.body);

        var popover      = document.getElementById('devmodePopover');
        var popoverKey    = document.getElementById('devmodePopoverKey');
        var popoverInput  = document.getElementById('devmodePopoverInput');
        var activeSpan    = null;

        document.body.addEventListener('click', function(e) {
            var span = e.target.closest ? e.target.closest('.devmode-editable') : null;
            if (!span) {
                if (!e.target.closest || !e.target.closest('#devmodePopover')) popover.style.display = 'none';
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            activeSpan = span;

            var rect = span.getBoundingClientRect();
            popoverKey.textContent   = span.getAttribute('data-devmode-key');
            popoverInput.value       = span.textContent;
            popover.style.display    = 'block';
            var left = rect.left;
            if (left + 320 > window.innerWidth) left = window.innerWidth - 330;
            popover.style.top  = (rect.bottom + 6) + 'px';
            popover.style.left = Math.max(8, left) + 'px';
            popoverInput.focus();

            document.getElementById('devmodePopoverAllLang').href =
                '<?= base_url('developer_mode?search='); ?>' + encodeURIComponent(span.getAttribute('data-devmode-key'));
        }, true);

        document.getElementById('devmodePopoverCancel').addEventListener('click', function() {
            popover.style.display = 'none';
        });

        document.getElementById('devmodePopoverSave').addEventListener('click', function() {
            if (!activeSpan) return;
            var key   = activeSpan.getAttribute('data-devmode-key');
            var value = popoverInput.value;

            fetch('<?= base_url('developer_mode/save'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'lang_key=' + encodeURIComponent(key) + '&language=<?= get_current_language(); ?>&value=' + encodeURIComponent(value)
            }).then(function(r) { return r.json(); }).then(function(res) {
                if (res.success) {
                    activeSpan.textContent = value;
                    popover.style.display = 'none';
                } else {
                    alert(res.message || '<?= lang_line('devmode_save_err', 'Gagal menyimpan.'); ?>');
                }
            }).catch(function() {
                alert('<?= lang_line('devmode_save_err', 'Gagal menyimpan.'); ?>');
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
<?php endif; ?>

</div> <!-- End of wrapper -->
</body>
</html>

