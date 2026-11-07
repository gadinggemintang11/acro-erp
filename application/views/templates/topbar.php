<nav class="navbar navbar-expand navbar-light topbar brand-topbar mb-4 static-top shadow">

  <!-- Sidebar Toggle (Mobile) - Always visible on mobile -->
  <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3 d-lg-none">
    <i class="fa fa-bars" style="font-size: 1.25rem; color: #fff;"></i>
  </button>

  <!-- Live Clock -->
  <div id="topbar-clock" class="topbar-clock d-flex align-items-center" aria-live="polite">
    <i class="far fa-clock mr-2" style="font-size: 1rem;"></i>
    <span id="topbar-clock-text">--</span>
  </div>

  <!-- Topbar Navbar -->
  <ul class="navbar-nav ml-auto">

    <!-- Dashboard Link -->
    <li class="nav-item mx-1">
      <a class="nav-link btn-load d-flex align-items-center" href="<?= base_url('dashboard'); ?>" title="Dashboard" style="padding: 0.5rem 0.75rem;">
        <i class="fas fa-home fa-fw text-primary" style="font-size: 1.1rem;"></i>
        <span class="d-none d-lg-inline ml-2 text-gray-700 small font-weight-bold">Dashboard</span>
      </a>
    </li>

    <!-- Language Selector -->
    <li class="nav-item dropdown no-arrow mx-1">
      <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="languageDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="padding: 0.5rem 0.75rem;">
        <i class="fas fa-globe fa-fw text-success" style="font-size: 1.1rem;"></i>
        <span class="d-none d-lg-inline ml-2 text-gray-700 small">
          <?php 
            $this->load->helper('language_helper');
            $current_lang = get_current_language();
            $languages = get_available_languages();
            echo isset($languages[$current_lang]) ? $languages[$current_lang]['flag'] . ' ' . $languages[$current_lang]['name'] : '🌐 Language';
          ?>
        </span>
      </a>
      <div class="dropdown-menu dropdown-menu-right shadow-lg animated--grow-in border-0" aria-labelledby="languageDropdown" style="min-width: 220px;">
        <h6 class="dropdown-header bg-gradient-primary text-white">
          <i class="fas fa-language mr-2"></i>Select Language
        </h6>
        <?php foreach (get_available_languages() as $lang): ?>
          <a class="dropdown-item <?= get_current_language() == $lang['code'] ? 'active bg-light' : '' ?>" 
             href="<?= current_url() . '?lang=' . $lang['code']; ?>"
             style="padding: 0.75rem 1.25rem; transition: all 0.2s ease;">
            <span style="font-size: 1.3rem;"><?= $lang['flag']; ?></span>
            <span class="ml-2 font-weight-500"><?= $lang['name']; ?></span>
            <?php if (get_current_language() == $lang['code']): ?>
              <i class="fas fa-check-circle text-success float-right mt-1"></i>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </li>

    <!-- Notifications -->
    <li class="nav-item dropdown no-arrow mx-1">
      <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="padding: 0.5rem 0.75rem; position: relative;">
        <i class="fas fa-bell fa-fw text-warning" style="font-size: 1.1rem;"></i>
        <span class="badge badge-danger badge-counter" id="notificationBadge" style="position: absolute; top: 5px; right: 5px; font-size: 0.65rem; display: none;">0</span>
      </a>
      <div class="dropdown-list dropdown-menu dropdown-menu-right shadow-lg animated--grow-in border-0" aria-labelledby="alertsDropdown" style="min-width: 380px; max-height: 500px; overflow-y: auto;">
        <h6 class="dropdown-header bg-gradient-warning text-white d-flex justify-content-between align-items-center">
          <span><i class="fas fa-bell mr-2"></i>Recent Activity</span>
        </h6>
        <div id="notificationList">
          <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="sr-only">Loading...</span>
            </div>
          </div>
        </div>
        <a class="dropdown-item text-center small text-primary font-weight-bold py-3" href="<?= base_url('log_activity'); ?>" style="border-top: 1px solid #e3e6f0;">
          <i class="fas fa-list mr-2"></i>View All Activity Logs
        </a>
      </div>
    </li>

    <div class="topbar-divider d-none d-sm-block"></div>

    <!-- Dark Mode Toggle Button -->
    <li class="nav-item dropdown no-arrow">
      <button id="dark-mode-toggle-topbar" class="nav-link d-flex align-items-center justify-content-center" title="Toggle Dark/Light Mode" style="padding: 0.5rem 0.75rem; background: none; border: none; cursor: pointer; height: 4.375rem; display: flex !important;">
        <i class="fas fa-moon fa-fw text-info" style="font-size: 1.1rem;"></i>
        <span class="d-none d-lg-inline ml-2 text-gray-700 small font-weight-500" id="dark-mode-label">Dark Mode</span>
      </button>
    </li>

    <div class="topbar-divider d-none d-sm-block"></div>

    <!-- User Information -->
    <li class="nav-item dropdown no-arrow">
      <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="padding: 0.5rem 1rem;">
        <div class="mr-3 d-none d-lg-block text-right">
          <span class="d-block text-gray-800 small font-weight-bold">
            <?= $this->session->userdata('username') ?? 'Administrator'; ?>
          </span>
          <span class="d-block text-gray-600" style="font-size: 0.75rem;">
            <?php
              // Semua role user (Role Level 2026-09-15). Versi lama mencetak
              // SELURUH isi session (termasuk session_token) ke HTML kalau
              // role_name kosong -- dihapus.
              $role_names = function_exists('current_role_names') ? current_role_names() : [];
              $role_label = $role_names ? $role_names[0] . (count($role_names) > 1 ? ' +' . (count($role_names) - 1) : '') : ($this->session->userdata('role_name') ?: '-');
            ?>
            <span title="<?= htmlspecialchars(implode(', ', $role_names)); ?>"><?= htmlspecialchars($role_label); ?></span>
          </span>
        </div>
        <div class="rounded-circle bg-gradient-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
          <span class="text-white font-weight-bold" style="font-size: 1rem;">
            <?= strtoupper(substr($this->session->userdata('username') ?? 'A', 0, 1)); ?>
          </span>
        </div>
      </a>
      <div class="dropdown-menu dropdown-menu-right shadow-lg animated--grow-in border-0" aria-labelledby="userDropdown" style="min-width: 200px;">
        <div class="dropdown-header bg-gradient-primary text-white">
          <i class="fas fa-user-circle mr-2"></i>Account
        </div>
        <a class="dropdown-item btn-load" href="<?= base_url('account/profile'); ?>" style="padding: 0.75rem 1.25rem; transition: all 0.2s ease;">
          <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
          My Profile
        </a>
        <a class="dropdown-item btn-load" href="<?= base_url('account/settings'); ?>" style="padding: 0.75rem 1.25rem; transition: all 0.2s ease;">
          <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
          Settings
        </a>
        <a class="dropdown-item btn-load" href="<?= base_url('log_activity'); ?>" style="padding: 0.75rem 1.25rem; transition: all 0.2s ease;">
          <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
          Activity Log
        </a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item text-danger font-weight-bold" href="<?= base_url('auth/logout'); ?>" style="padding: 0.75rem 1.25rem; transition: all 0.2s ease;">
          <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2"></i>
          Logout
        </a>
      </div>
    </li>

  </ul>
</nav>

<?php // "Developer Mode" — banner aktif (ERP Revision Notes, user 2026-09-03).
      // User klarifikasi: masuknya lewat klik menu sidebar "Developer Mode"
      // (langsung nyalain & bawa ke tampilan ERP asli, BUKAN halaman terpisah
      // dulu), keluarnya lewat tombol "Akhiri Developer Mode" yang jelas
      // kelihatan — bukan toggle kecil di topbar (makanya toggle lama di
      // topbar dihapus, diganti banner ini). Lihat templates/sidebar.php
      // (link entry point) & footer.php (overlay klik-teks-nya). ?>
<?php if (devmode_active()): ?>
<?php
    // id="devmodeBanner" dipakai footer.php buat MENGECUALIKAN kontrol banner
    // ini dari overlay klik-utk-edit (beda dari isi halaman yang MEMANG harus
    // bisa diklik-edit) — tanpa ini, klik "Atur Kolom" dkk malah kebuka jadi
    // popover edit teks (bug ditemukan user 2026-09-03).
    //
    // 2026-09-06: banner ini sekarang jadi PEMILIH MODE. Developer Mode nyala
    // TIDAK langsung mengaktifkan overlay edit teks — user pilih dulu mau
    // mengedit apa, dan hanya SATU mode inline yang aktif dalam satu waktu
    // (user: "supaya saat mau edit kolom tidak terganggu mode edit teks").
    $dm_mode   = devmode_mode();
    $dm_back   = urlencode(current_url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    $dm_modes  = [
        'text'   => ['icon' => 'fa-font',    'label' => lang_line('devmode_mode_text', 'Edit Teks'),
                     'hint' => lang_line('devmode_mode_text_hint', 'Klik teks mana pun di halaman untuk mengubahnya.')],
        'column' => ['icon' => 'fa-table',   'label' => lang_line('devmode_mode_column', 'Atur Kolom'),
                     'hint' => lang_line('devmode_mode_column_hint', 'Tombol "Atur Kolom" muncul di halaman-halaman list.')],
    ];
?>
<div id="devmodeBanner" class="px-3 py-2" style="background:#f6c23e;color:#5a4400;">
    <div class="d-flex align-items-center justify-content-between flex-wrap">
        <div class="d-flex align-items-center flex-wrap">
            <strong class="mr-3">
                <i class="fas fa-pencil-alt mr-1"></i><?= lang_line('devmode_banner_title', 'Developer Mode Aktif'); ?>
            </strong>
            <span class="mr-2 small"><?= lang_line('devmode_mode_pick', 'Mau mengedit apa?'); ?></span>
            <?php foreach ($dm_modes as $mk => $mv): $on = ($dm_mode === $mk); ?>
                <a href="<?= base_url('developer_mode/set_mode?mode=' . $mk . '&redirect=' . $dm_back); ?>"
                   class="btn btn-sm mr-2 <?= $on ? 'btn-dark' : 'btn-outline-dark'; ?>"
                   title="<?= htmlspecialchars($mv['hint']); ?>">
                    <i class="fas <?= $mv['icon']; ?> mr-1"></i><?= htmlspecialchars($mv['label']); ?>
                    <?php if ($on): ?><i class="fas fa-check ml-1"></i><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <a href="<?= base_url('developer_mode/toggle?redirect=' . $dm_back); ?>" class="btn btn-sm btn-dark">
            <i class="fas fa-power-off mr-1"></i> <?= lang_line('devmode_banner_end', 'Akhiri Developer Mode'); ?>
        </a>
    </div>
    <div class="small mt-1" style="opacity:.9">
        <?php if ($dm_mode === ''): ?>
            <i class="fas fa-info-circle mr-1"></i><?= lang_line('devmode_mode_none_hint', 'Belum ada mode aktif — halaman berperilaku normal. Pilih salah satu tombol di atas untuk mulai mengedit.'); ?>
        <?php else: ?>
            <i class="fas fa-info-circle mr-1"></i><?= $dm_modes[$dm_mode]['hint']; ?>
            <?= lang_line('devmode_mode_off_hint', 'Klik lagi tombol modenya untuk mematikan.'); ?>
        <?php endif; ?>
        <span class="ml-2">
            <a href="<?= base_url('developer_mode'); ?>" style="color:#5a4400;text-decoration:underline;"><?= lang_line('devmode_banner_manage_link', 'Kelola semua teks'); ?></a>
            <a href="<?= base_url('developer_mode/assets'); ?>" class="ml-2" style="color:#5a4400;text-decoration:underline;"><?= lang_line('devmode_banner_manage_assets_link', 'Kelola logo & gambar'); ?></a>
            <a href="<?= base_url('developer_mode/columns'); ?>" class="ml-2" style="color:#5a4400;text-decoration:underline;"><?= lang_line('devmode_banner_manage_columns_link', 'Kelola kolom list'); ?></a>
            <a href="<?= base_url('developer_mode/sidebar_order'); ?>" class="ml-2" style="color:#5a4400;text-decoration:underline;"><?= lang_line('devmode_banner_manage_sidebar_order_link', 'Atur urutan sidebar'); ?></a>
        </span>
    </div>
</div>
<?php endif; ?>

<style>
/* Icon Circle for Notifications */
.icon-circle {
  height: 2.5rem;
  width: 2.5rem;
  border-radius: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Dropdown Hover Effects */
.dropdown-item:hover {
  background-color: #f8f9fc !important;
  transform: translateX(3px);
}

.dropdown-item.active {
  background-color: var(--brand-soft) !important;
  color: var(--brand-1) !important;
}

/* Badge Counter Animation */
.badge-counter {
  animation: pulse-badge 2s infinite;
}

@keyframes pulse-badge {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

/* Smooth transitions for nav links */
.nav-link {
  transition: all 0.2s ease;
  border-radius: 0.35rem;
}

.nav-link:hover {
  background-color: #f8f9fc;
}

/* Logo hover effect */
.navbar-brand:hover img {
  transform: scale(1.05);
  transition: transform 0.3s ease;
}

/* Topbar shadow enhancement */
.topbar.shadow {
  box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}

/* Notification item styles */
.notification-item {
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
}

.notification-item:hover {
  background-color: var(--brand-soft) !important;
  border-left-color: var(--brand-1);
}

.notification-item.unread {
  background-color: #fef2f2;
  border-left-color: var(--brand-1);
}

.notification-item .icon-circle {
  flex-shrink: 0;
}

.notification-delete-btn {
  opacity: 0;
  transition: opacity 0.2s ease;
}

.notification-item:hover .notification-delete-btn {
  opacity: 1;
}

/* Notification type colors */
.notification-type-success { border-left-color: #1cc88a !important; }
.notification-type-warning { border-left-color: #f6c23e !important; }
.notification-type-danger { border-left-color: #e74a3b !important; }
.notification-type-info { border-left-color: #36b9cc !important; }

/* Dark Mode Toggle Button Styling */
#dark-mode-toggle-topbar {
  display: flex !important;
  align-items: center;
  justify-content: center;
  height: 4.375rem;
  line-height: 4.375rem;
  color: #858796;
  transition: all 0.2s ease;
}

#dark-mode-toggle-topbar:hover {
  color: var(--brand-1);
  text-decoration: none;
}

#dark-mode-toggle-topbar:focus {
  outline: none;
}

/* Dark Mode Label */
#dark-mode-label {
  font-weight: 500;
  margin-left: 0.5rem;
}

/* Dark Mode - Button Styling in Dark Mode */
body.dark-mode #dark-mode-toggle-topbar {
  color: var(--color-text-primary);
}

body.dark-mode #dark-mode-toggle-topbar:hover {
  color: #7fb3ff;
}

body.dark-mode #dark-mode-toggle-topbar i {
  color: #ffc107 !important;
}

body.dark-mode #dark-mode-label {
  color: var(--color-text-primary) !important;
}

/* ============================================
   BRAND RED TOPBAR THEME
   ============================================ */
.brand-topbar {
  background: linear-gradient(90deg, var(--brand-1) 0%, var(--brand-2) 100%) !important;
  color: #fff !important;
  border-bottom: 0 !important;
}

/* Live clock */
.topbar-clock {
  color: #fff;
  font-weight: 700;
  font-size: 0.95rem;
  letter-spacing: 0.5px;
  padding: 0.5rem 1rem;
  text-transform: uppercase;
}
.topbar-clock #topbar-clock-text { white-space: nowrap; }

/* Nav link defaults: white text on red */
.brand-topbar .nav-link,
.brand-topbar .nav-link i,
.brand-topbar #dark-mode-toggle-topbar,
.brand-topbar #dark-mode-toggle-topbar i,
.brand-topbar #dark-mode-label {
  color: #fff !important;
}
.brand-topbar .nav-link:hover,
.brand-topbar .nav-link:hover i {
  background-color: rgba(255,255,255,0.12) !important;
  color: #fff !important;
}

/* Hide colored circle accents on icons (was yellow bell, blue moon, etc) */
.brand-topbar .nav-link .text-success,
.brand-topbar .nav-link .text-warning,
.brand-topbar .nav-link .text-info,
.brand-topbar .nav-link .text-primary,
.brand-topbar #dark-mode-toggle-topbar .text-info,
.brand-topbar #dark-mode-toggle-topbar .text-warning {
  color: #fff !important;
}

/* User block text — only on direct nav-link children, NOT inside dropdowns */
.brand-topbar > ul > li > .nav-link .text-gray-800,
.brand-topbar > ul > li > .nav-link .text-gray-700,
.brand-topbar > ul > li > .nav-link .text-gray-600 {
  color: #fff !important;
}

/* Notification & dropdown content keeps dark text */
.brand-topbar .dropdown-menu .text-gray-800 { color: #1f2937 !important; }
.brand-topbar .dropdown-menu .text-gray-700 { color: #374151 !important; }
.brand-topbar .dropdown-menu .text-gray-600 { color: #4b5563 !important; }
.brand-topbar .dropdown-menu .text-gray-500 { color: #6b7280 !important; }
.brand-topbar .dropdown-menu .notification-item .font-weight-bold { color: #1f2937 !important; }

/* User avatar circle */
.brand-topbar .navbar-nav .rounded-circle.bg-gradient-primary {
  background: rgba(255,255,255,0.18) !important;
  border: 2px solid rgba(255,255,255,0.35);
}

/* Divider on red bg */
.brand-topbar .topbar-divider {
  border-left: 1px solid rgba(255,255,255,0.25) !important;
}

/* Notification badge: merah = arti "perlu perhatian" (dulu kuning krn
   topbar-nya merah; sejak topbar abu-abu merah justru paling menonjol). */
.brand-topbar #notificationBadge {
  background: var(--brand-2) !important;
  color: #fff !important;
  font-weight: 700;
}

/* Dropdown panels remain white (readable) */
.brand-topbar .dropdown-menu {
  color: var(--text-primary, #1f2937);
}
.brand-topbar .dropdown-menu .dropdown-item { color: #333 !important; }
.brand-topbar .dropdown-menu .dropdown-item:hover { color: var(--brand-1) !important; background: var(--brand-soft) !important; }
.brand-topbar .dropdown-menu .dropdown-header.bg-gradient-primary,
.brand-topbar .dropdown-menu .dropdown-header.bg-gradient-warning {
  background: linear-gradient(90deg, var(--brand-1), var(--brand-2)) !important;
}

/* Mobile: clock smaller */
@media (max-width: 576px) {
  .topbar-clock { font-size: 0.78rem; padding: 0.35rem 0.6rem; }
  .topbar-clock i { display: none; }
}

/* Dark mode override (warna tema, versi redup) */
body.dark-mode .brand-topbar {
  background: linear-gradient(90deg, var(--brand-dm-1) 0%, var(--brand-dm-2) 100%) !important;
}
</style>

<script>
// Load notifications on page load
$(document).ready(function() {
  loadNotifications();
  
  // Refresh notifications every 30 seconds
  setInterval(loadNotifications, 30000);
  
  // Load notifications when dropdown is opened
  $('#alertsDropdown').on('show.bs.dropdown', function() {
    loadNotifications();
  });

  // Klik 1 notifikasi -> tandai dibaca + navigasi ke link-nya (kalau ada).
  // Delegated ke #notificationList (bukan .notification-item langsung) krn
  // isinya di-render ulang tiap loadNotifications() — elemen lama sudah
  // dibuang tiap refresh, delegated listener tetap kepakai ke elemen baru.
  $('#notificationList').on('click', '.notification-item', function() {
    const id = $(this).data('id');
    const link = $(this).data('link');
    markNotificationRead(id, link ? '<?= base_url(); ?>' + link : null);
  });
});

// Load notifications via AJAX
function loadNotifications() {
  $.ajax({
    url: '<?= base_url("notification/get_notifications"); ?>',
    type: 'GET',
    dataType: 'json',
    success: function(response) {
      if (response.status) {
        updateNotificationBadge(response.unread_count);
        renderNotifications(response.notifications);
      } else {
        $('#notificationList').html('<div class="text-center text-warning py-3"><i class="fas fa-exclamation-triangle"></i><br><small>' + (response.message || 'Failed to load') + '</small></div>');
      }
    },
    error: function(xhr, status, error) {
      console.error('Notification Error:', error);
      $('#notificationList').html('<div class="text-center text-muted py-3"><i class="fas fa-exclamation-circle"></i><br><small>Please create notification table first</small></div>');
      $('#notificationBadge').hide();
    }
  });
}

// Update notification badge
function updateNotificationBadge(count) {
  const badge = $('#notificationBadge');
  if (count > 0) {
    badge.text(count > 99 ? '99+' : count).show();
  } else {
    badge.hide();
  }
}

// Render notifications
function renderNotifications(notifications) {
  const container = $('#notificationList');
  
  if (notifications.length === 0) {
    container.html('<div class="text-center text-muted py-4"><i class="fas fa-bell-slash fa-2x mb-2"></i><br>No recent activity</div>');
    return;
  }
  
  let html = '';
  notifications.forEach(function(notification) {
    const typeColors = {
      'success': 'bg-success',
      'warning': 'bg-warning',
      'danger': 'bg-danger',
      'info': 'bg-info'
    };
    
    const iconColor = typeColors[notification.type] || 'bg-primary';
    const unreadClass = notification.is_read == 0 ? 'unread' : '';
    const typeClass = 'notification-type-' + notification.type;
    const timeAgo = formatTimeAgo(notification.created_at);
    
    html += `
      <div class="dropdown-item notification-item ${unreadClass} ${typeClass} d-flex align-items-center border-bottom"
           data-id="${notification.id}" data-link="${notification.link || ''}"
           style="padding: 0.75rem 1rem; cursor: pointer;">
        <div class="mr-3">
          <div class="icon-circle ${iconColor}">
            <i class="fas ${notification.icon} text-white" style="font-size: 0.9rem;"></i>
          </div>
        </div>
        <div style="flex: 1; min-width: 0;">
          <div class="small text-gray-500">
            ${timeAgo} • ${notification.created_by || 'System'}
          </div>
          <div class="font-weight-bold text-gray-800" style="font-size: 0.85rem;">${notification.title}</div>
          <div class="small text-gray-600" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${notification.message}</div>
        </div>
      </div>
    `;
  });
  
  container.html(html);
}

// Format time ago
function formatTimeAgo(dateString) {
  const date = new Date(dateString);
  const now = new Date();
  const seconds = Math.floor((now - date) / 1000);
  
  if (seconds < 60) return 'Just now';
  if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
  if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
  if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
  return date.toLocaleDateString();
}

// Mark notification as read
function markNotificationRead(id, link) {
  $.ajax({
    url: '<?= base_url("notification/mark_as_read"); ?>',
    type: 'POST',
    data: { id: id },
    dataType: 'json',
    success: function(response) {
      if (response.status) {
        loadNotifications();
        if (link && link !== '#') {
          window.location.href = link;
        }
      }
    }
  });
}

// Mark all notifications as read
function markAllAsRead() {
  $.ajax({
    url: '<?= base_url("notification/mark_all_as_read"); ?>',
    type: 'POST',
    dataType: 'json',
    success: function(response) {
      if (response.status) {
        loadNotifications();
        Swal.fire({
          icon: 'success',
          title: 'Success',
          text: 'All notifications marked as read',
          timer: 1500,
          showConfirmButton: false
        });
      }
    }
  });
}

// Live clock
(function startTopbarClock() {
  var el = document.getElementById('topbar-clock-text');
  if (!el) return;
  var months = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
  function tick() {
    var d = new Date();
    var day = d.getDate();
    var ord = (day % 10 === 1 && day !== 11) ? 'st' : (day % 10 === 2 && day !== 12) ? 'nd' : (day % 10 === 3 && day !== 13) ? 'rd' : 'th';
    var hh = d.getHours();
    var ampm = hh >= 12 ? 'PM' : 'AM';
    hh = hh % 12; if (hh === 0) hh = 12;
    var mm = String(d.getMinutes()).padStart(2, '0');
    el.textContent = months[d.getMonth()] + ' ' + day + ord + ', ' + d.getFullYear() + ', ' + hh + ':' + mm + ' ' + ampm;
  }
  tick();
  setInterval(tick, 30000);
})();

// Delete notification
function deleteNotification(id) {
  Swal.fire({
    title: 'Delete Notification?',
    text: 'This notification will be permanently deleted',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e74a3b',
    cancelButtonColor: '#858796',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: '<?= base_url("notification/delete"); ?>',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
          if (response.status) {
            loadNotifications();
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: 'Notification has been deleted',
              timer: 1500,
              showConfirmButton: false
            });
          }
        }
      });
    }
  });
}
</script>