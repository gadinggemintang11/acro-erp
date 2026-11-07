<div class="container-fluid">
  <!-- Page Heading -->
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
      <i class="fas fa-bell text-warning mr-2"></i><?= lang_line('notifications_title'); ?>
    </h1>
    <div>
      <button class="btn btn-primary btn-sm" onclick="markAllAsRead()">
        <i class="fas fa-check-double mr-1"></i> <?= lang_line('notif_idx_mark_all'); ?>
      </button>
    </div>
  </div>

  <!-- Notifications List -->
  <div class="row">
    <div class="col-lg-8 mx-auto">
      <div class="card shadow mb-4">
        <div class="card-header py-3 bg-gradient-warning">
          <h6 class="m-0 font-weight-bold text-white">
            <i class="fas fa-list mr-2"></i><?= lang_line('notif_idx_list_header'); ?>
          </h6>
        </div>
        <div class="card-body p-0">
          <?php if(empty($notifications)): ?>
            <div class="text-center py-5">
              <i class="fas fa-bell-slash fa-3x text-gray-300 mb-3"></i>
              <p class="text-gray-500"><?= lang_line('notif_idx_empty'); ?></p>
            </div>
          <?php else: ?>
            <div class="list-group list-group-flush">
              <?php foreach($notifications as $notif): ?>
                <?php
                  $type_colors = array(
                    'success' => 'success',
                    'warning' => 'warning',
                    'danger' => 'danger',
                    'info' => 'info'
                  );
                  $color = isset($type_colors[$notif->type]) ? $type_colors[$notif->type] : 'primary';
                  $unread_class = $notif->is_read == 0 ? 'bg-light border-left-' . $color : '';
                ?>
                <div class="list-group-item list-group-item-action <?= $unread_class ?>" style="border-left: 3px solid; transition: all 0.2s ease;">
                  <div class="d-flex align-items-start">
                    <div class="mr-3">
                      <div class="icon-circle bg-<?= $color ?>" style="width: 45px; height: 45px;">
                        <i class="fas <?= $notif->icon ?> text-white"></i>
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <h6 class="mb-1 font-weight-bold"><?= $notif->title ?></h6>
                          <p class="mb-1 text-gray-700"><?= $notif->message ?></p>
                          <small class="text-muted">
                            <i class="far fa-clock mr-1"></i>
                            <?= date('d M Y, H:i', strtotime($notif->created_at)) ?>
                          </small>
                        </div>
                        <div class="ml-3 text-nowrap">
                          <?php if($notif->is_read == 0): ?>
                            <span class="badge badge-<?= $color ?> badge-pill"><?= lang_line('notif_idx_badge_new'); ?></span>
                          <?php endif; ?>
                          <?php if (!empty($notif->link)): ?>
                            <a class="btn btn-sm btn-link" href="<?= base_url($notif->link) ?>" title="<?= lang_line('notif_idx_open', 'Buka'); ?>">
                              <i class="fas fa-arrow-right"></i>
                            </a>
                          <?php endif; ?>
                          <button class="btn btn-sm btn-link text-danger" onclick="deleteNotification(<?= $notif->id ?>)">
                            <i class="fas fa-trash"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const I18N_NOTIF_IDX = {
  delete_title:  <?= json_encode(lang_line('notif_idx_delete_title'), JSON_UNESCAPED_UNICODE); ?>,
  delete_text:   <?= json_encode(lang_line('notif_idx_delete_text'), JSON_UNESCAPED_UNICODE); ?>,
  deleted_ok:    <?= json_encode(lang_line('notif_idx_deleted_ok'), JSON_UNESCAPED_UNICODE); ?>,
  marked_all_ok: <?= json_encode(lang_line('notif_idx_marked_all_ok'), JSON_UNESCAPED_UNICODE); ?>
};
</script>
