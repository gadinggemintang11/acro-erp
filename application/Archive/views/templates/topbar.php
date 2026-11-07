<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

  <!-- Sidebar Toggle (Mobile) -->
  <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
    <i class="fa fa-bars text-gray-600"></i>
  </button>

  <!-- Logo -->
  <a class="btn-load navbar-brand d-flex align-items-center" href="<?= base_url('dashboard'); ?>" style="text-decoration: none;">
    <img src="<?= base_url('assets/img/logo_mini.png'); ?>" alt="Logo" style="height: 40px; width: auto; margin-right: 12px;">
    <div class="d-none d-md-block">
      <div style="font-size: 16px; font-weight: 700; color: #5a5c69; line-height: 1.2;">PT NEW SPECIALIFE INDUSTRIES</div>
      <div style="font-size: 11px; color: #858796; font-weight: 500;">Enterprise Resource Planning</div>
    </div>
  </a>

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

    <!-- User Information -->
    <li class="nav-item dropdown no-arrow">
      <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="padding: 0.5rem 1rem;">
        <div class="mr-3 d-none d-lg-block text-right">
          <span class="d-block text-gray-800 small font-weight-bold">
            <?= $this->session->userdata('username') ?? 'Administrator'; ?>
          </span>
          <span class="d-block text-gray-600" style="font-size: 0.75rem;">
            <?= $this->session->userdata('role_name') ?? 'Superadmin'; ?>
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
        <a class="dropdown-item" href="#" style="padding: 0.75rem 1.25rem; transition: all 0.2s ease;">
          <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
          My Profile
        </a>
        <a class="dropdown-item" href="#" style="padding: 0.75rem 1.25rem; transition: all 0.2s ease;">
          <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
          Settings
        </a>
        <a class="dropdown-item" href="#" style="padding: 0.75rem 1.25rem; transition: all 0.2s ease;">
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
  background-color: #e3f2fd !important;
  color: #1e88e5 !important;
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
  background-color: #f8f9fc !important;
  border-left-color: #c62828;
}

.notification-item.unread {
  background-color: #f0f7ff;
  border-left-color: #c62828;
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