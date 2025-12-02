<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Log Aktivitas Pengguna</title>
<style>
    @page {
        margin: 15mm;
    }
    body {
        font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        font-size: 9pt;
        color: #1e293b;
        line-height: 1.6;
    }
    .header-section {
        text-align: center;
        border-bottom: 3px solid #1e293b;
        padding-bottom: 20px;
        margin-bottom: 25px;
    }
    .header-section h1 {
        margin: 0 0 10px 0;
        font-size: 22pt;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    .header-section .subtitle {
        color: #64748b;
        font-size: 10pt;
        margin: 5px 0;
    }
    .export-info {
        background: white;
        border: 1px solid #cbd5e1;
        border-left: 3px solid #1e293b;
        padding: 10px 15px;
        margin-bottom: 20px;
        font-size: 9pt;
    }
    .export-info strong {
        color: #1e293b;
    }
    .section-title {
        font-size: 11pt;
        font-weight: 700;
        color: #1e293b;
        margin: 20px 0 12px 0;
        padding: 10px 0;
        border-bottom: 2px solid #1e293b;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    table.log-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #1e293b;
        margin-top: 10px;
    }
    table.log-table thead {
        background: white;
        border-bottom: 2px solid #1e293b;
    }
    table.log-table th {
        padding: 10px 6px;
        text-align: center;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 7.5pt;
        letter-spacing: 0.5px;
        border: 1px solid #1e293b;
        color: #1e293b;
    }
    table.log-table td {
        border: 1px solid #cbd5e1;
        padding: 8px 6px;
        font-size: 8pt;
        color: #1e293b;
        background: white;
        vertical-align: top;
    }
    table.log-table tbody tr:nth-child(even) {
        background-color: #f8fafc;
    }
    .text-center {
        text-align: center;
    }
    .log-time {
        font-family: 'Courier New', monospace;
        font-weight: 600;
        font-size: 7.5pt;
    }
    .log-username {
        font-weight: 600;
        color: #667eea;
    }
    .badge-action {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-weight: 700;
        font-size: 7pt;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        text-align: center;
    }
    .badge-create {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    .badge-update {
        background: #fed7aa;
        color: #92400e;
        border: 1px solid #fbbf24;
    }
    .badge-delete {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
    .badge-other {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #93c5fd;
    }
    .log-description {
        color: #475569;
        font-size: 7.5pt;
        line-height: 1.4;
    }
    .empty-state {
        text-align: center;
        color: #a0aec0;
        padding: 40px 20px;
        font-style: italic;
    }
    .footer-note {
        margin-top: 20px;
        text-align: center;
        font-size: 7pt;
        color: #64748b;
        border-top: 1px solid #cbd5e1;
        padding-top: 10px;
    }
    .summary-box {
        background: white;
        border: 1px solid #cbd5e1;
        padding: 12px 15px;
        margin-bottom: 15px;
        display: table;
        width: 100%;
    }
    .summary-item {
        display: table-cell;
        padding: 5px 10px;
        text-align: center;
        border-right: 1px solid #e2e8f0;
    }
    .summary-item:last-child {
        border-right: none;
    }
    .summary-label {
        font-size: 7pt;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 3px;
    }
    .summary-value {
        font-size: 14pt;
        font-weight: 700;
        color: #1e293b;
    }
</style>
</head>
<body>
    <div class="header-section">
        <h1>Log Aktivitas Pengguna</h1>
        <div class="subtitle">Riwayat Aktivitas Sistem ERP</div>
    </div>

    <div class="export-info">
        <strong>Diekspor pada:</strong> <?= $export_date ?>
    </div>

    <?php
        // Calculate statistics
        $total_logs = count($logs);
        $create_count = 0;
        $update_count = 0;
        $delete_count = 0;
        $other_count = 0;
        
        foreach ($logs as $log) {
            switch ($log->action) {
                case 'CREATE': $create_count++; break;
                case 'UPDATE': $update_count++; break;
                case 'DELETE': $delete_count++; break;
                default: $other_count++; break;
            }
        }
    ?>

    <div class="summary-box">
        <div class="summary-item">
            <div class="summary-label">Total Log</div>
            <div class="summary-value"><?= $total_logs ?></div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Create</div>
            <div class="summary-value" style="color: #38a169;"><?= $create_count ?></div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Update</div>
            <div class="summary-value" style="color: #dd6b20;"><?= $update_count ?></div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Delete</div>
            <div class="summary-value" style="color: #e53e3e;"><?= $delete_count ?></div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Lainnya</div>
            <div class="summary-value" style="color: #3182ce;"><?= $other_count ?></div>
        </div>
    </div>

    <div class="section-title">Detail Riwayat Aktivitas</div>

    <table class="log-table">
        <thead>
            <tr>
                <th width="30">No</th>
                <th width="100">Waktu</th>
                <th width="80">Username</th>
                <th width="100">Modul</th>
                <th width="60">Aksi</th>
                <th>Deskripsi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($logs)): $no = 1; ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="text-center"><?= $no++; ?></td>
                        <td class="text-center">
                            <span class="log-time"><?= date('d M Y, H:i:s', strtotime($log->log_time)); ?></span>
                        </td>
                        <td class="text-center">
                            <span class="log-username"><?= htmlspecialchars($log->username); ?></span>
                        </td>
                        <td class="text-center"><?= htmlspecialchars($log->module); ?></td>
                        <td class="text-center">
                            <?php
                                $action = htmlspecialchars($log->action);
                                if ($action == 'CREATE') {
                                    echo '<span class="badge-action badge-create">CREATE</span>';
                                } elseif ($action == 'UPDATE') {
                                    echo '<span class="badge-action badge-update">UPDATE</span>';
                                } elseif ($action == 'DELETE') {
                                    echo '<span class="badge-action badge-delete">DELETE</span>';
                                } else {
                                    echo '<span class="badge-action badge-other">' . $action . '</span>';
                                }
                            ?>
                        </td>
                        <td>
                            <span class="log-description"><?= htmlspecialchars($log->description); ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="empty-state">Tidak ada data log aktivitas</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer-note">
        Dokumen ini digenerate secara otomatis oleh sistem ERP pada <?= $export_date ?>
    </div>
</body>
</html>
