<style>
    .page-header {
        background: linear-gradient(135deg, var(--brand-1) 0%, var(--brand-2) 100%);
        color: white;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(var(--brand-rgb), 0.3);
    }
    .page-header h1 {
        margin: 0;
        font-weight: 700;
        font-size: 1.75rem;
        letter-spacing: 0.5px;
    }
    .page-header .btn {
        border: none;
        font-weight: 600;
        transition: all 0.3s ease;
        padding: 10px 20px;
        margin-left: 8px;
    }
    .btn-export-excel {
        background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
        color: white;
    }
    .btn-export-excel:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(56, 161, 105, 0.4);
        color: white;
    }
    .btn-export-pdf {
        background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
        color: white;
    }
    .btn-export-pdf:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(237, 137, 54, 0.4);
        color: white;
    }
    .btn-clear {
        background: white;
        color: #dc3545;
    }
    .btn-clear:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        color: #c82333;
    }
    .log-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        border: none;
        overflow: hidden;
    }
    .log-card .card-header {
        background: linear-gradient(135deg, var(--brand-1) 0%, var(--brand-2) 100%);
        color: white;
        padding: 18px 25px;
        border: none;
    }
    .log-card .card-header h6 {
        margin: 0;
        font-weight: 700;
        font-size: 1.1rem;
        letter-spacing: 0.5px;
    }
    .log-table {
        margin-bottom: 0;
    }
    .log-table thead {
        background: #f8f9ff;
    }
    .log-table thead th {
        border: none;
        padding: 15px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        color: var(--brand-1);
        border-bottom: 2px solid var(--brand-1);
    }
    .log-table tbody tr {
        transition: all 0.3s ease;
    }
    .log-table tbody tr:hover {
        background-color: #f8f9ff !important;
        transform: scale(1.005);
        box-shadow: 0 2px 8px rgba(var(--brand-rgb), 0.1);
    }
    .log-table tbody td {
        padding: 15px;
        vertical-align: middle;
        border-color: #e2e8f0;
    }
    .log-time {
        font-family: 'Courier New', monospace;
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
    }
    .log-username {
        font-weight: 600;
        color: var(--brand-1);
    }
    .log-module {
        font-weight: 500;
        color: #2d3748;
    }
    .badge-action {
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }
    .badge-create {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        color: white;
    }
    .badge-update {
        background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
        color: white;
    }
    .badge-delete {
        background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
        color: white;
    }
    .badge-other {
        background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
        color: white;
    }
    .log-description {
        color: #64748b;
        font-size: 0.9rem;
    }
    .empty-state {
        padding: 60px 20px;
        text-align: center;
        color: #a0aec0;
    }
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    .pagination-wrapper {
        padding: 20px 25px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }
    .no-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, var(--brand-1) 0%, var(--brand-2) 100%);
        border-radius: 50%;
        color: white;
        font-weight: 700;
        font-size: 0.85rem;
    }
</style>

<div class="container-fluid">

    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <h1><i class="fas fa-history mr-2"></i><?= lang_line('log_activity_title', $title); ?></h1>
            <small style="opacity: 0.9;"><?= lang_line('log_activity_subtitle'); ?></small>
        </div>
        <div>
            <a href="<?= base_url('log_activity/export_excel'); ?>" class="btn btn-sm btn-export-excel">
                <i class="fas fa-file-excel mr-1"></i><?= lang_line('log_activity_export_excel'); ?>
            </a>
            <a href="<?= base_url('log_activity/export_pdf'); ?>" class="btn btn-sm btn-export-pdf" target="_blank">
                <i class="fas fa-file-pdf mr-1"></i><?= lang_line('log_activity_export_pdf'); ?>
            </a>
            <?php if (check_access('log_activity', 'delete')): ?>
                <a href="<?= base_url('log_activity/clear'); ?>" class="btn btn-sm btn-clear" id="btn-clear-log">
                    <i class="fas fa-trash mr-1"></i><?= lang_line('log_activity_clear'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card log-card">
        <div class="card-header">
            <h6><i class="fas fa-list-ul mr-2"></i><?= lang_line('log_activity_list'); ?></h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table log-table" width="100%">
                    <thead>
                        <tr>
                            <th width="60" class="text-center"><?= lang_line('log_activity_no'); ?></th>
                            <th width="170"><?= lang_line('log_activity_time'); ?></th>
                            <th width="150"><?= lang_line('log_activity_username'); ?></th>
                            <th width="130"><?= lang_line('log_activity_module'); ?></th>
                            <th width="120" class="text-center"><?= lang_line('log_activity_action'); ?></th>
                            <th><?= lang_line('log_activity_description'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-clipboard-list"></i>
                                    <h5><?= lang_line('log_activity_no_data'); ?></h5>
                                    <p><?= lang_line('log_activity_no_data_desc'); ?></p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = $this->uri->segment(3) + 1; foreach ($logs as $log): ?>
                            <tr>
                                <td class="text-center">
                                    <span class="no-badge"><?= $no++; ?></span>
                                </td>
                                <td>
                                    <span class="log-time">
                                        <i class="far fa-calendar-alt mr-1"></i><?= date('d M Y, H:i:s', strtotime($log->log_time)); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="log-username">
                                        <i class="far fa-user mr-1"></i><?= html_escape($log->username); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="log-module">
                                        <i class="fas fa-cube mr-1"></i><?php 
                                            $module_name = html_escape($log->module);
                                            // Try with space first (most common format)
                                            $module_key = 'module_' . strtolower(str_replace(' ', ' ', $module_name));
                                            $translated = lang_line($module_key);
                                            
                                            // If not found, try with underscore
                                            if ($translated == $module_key) {
                                                $module_key = 'module_' . strtolower(str_replace(' ', '_', $module_name));
                                                $translated = lang_line($module_key, $module_name);
                                            }
                                            
                                            echo $translated;
                                        ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php 
                                        $action = html_escape($log->action);
                                        $action_key = 'action_' . strtolower($action);
                                        $action_text = lang_line($action_key, $action);
                                        
                                        if ($action == 'CREATE') {
                                            echo '<span class="badge badge-action badge-create"><i class="fas fa-plus-circle mr-1"></i>' . $action_text . '</span>';
                                        } elseif ($action == 'UPDATE') {
                                            echo '<span class="badge badge-action badge-update"><i class="fas fa-edit mr-1"></i>' . $action_text . '</span>';
                                        } elseif ($action == 'DELETE') {
                                            echo '<span class="badge badge-action badge-delete"><i class="fas fa-trash-alt mr-1"></i>' . $action_text . '</span>';
                                        } else {
                                            echo '<span class="badge badge-action badge-other"><i class="fas fa-info-circle mr-1"></i>' . $action_text . '</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <span class="log-description"><?= html_escape($log->description); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($logs)): ?>
            <div class="pagination-wrapper">
                <?= $pagination; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>

</div>
<script>
const I18N_LOG = {
    confirm_title: <?= json_encode(lang_line('log_activity_confirm_title')); ?>,
    confirm_text:  <?= json_encode(lang_line('log_activity_confirm_text')); ?>,
    yes_clear:     <?= json_encode(lang_line('log_activity_btn_yes_clear')); ?>,
    cancel:        <?= json_encode(lang_line('log_activity_btn_cancel')); ?>
};
$(document).ready(function() {
    // Konfirmasi SweetAlert saat hapus log
    $('#btn-clear-log').on('click', function(e) {
        e.preventDefault(); // Hentikan aksi link
        const href = $(this).attr('href'); // Ambil URL

        Swal.fire({
            title: I18N_LOG.confirm_title,
            text:  I18N_LOG.confirm_text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: I18N_LOG.yes_clear,
            cancelButtonText:  I18N_LOG.cancel
        }).then((result) => {
            if (result.isConfirmed) {
                // Arahkan ke URL penghapusan
                window.location.href = href;
            }
        });
    });
});
</script>