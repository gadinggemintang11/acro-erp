<?php
    $badge = [
        'pending' => ['secondary', 'Belum dijalankan'],
        'applied' => ['success',   'Berhasil'],
        'skipped' => ['info',      'Sudah ada / dilewati'],
        'failed'  => ['danger',    'GAGAL'],
    ];
    $csrf_n = $this->security->get_csrf_token_name();
    $csrf_v = $this->security->get_csrf_hash();
    $log    = $this->session->flashdata('migration_log');
?>
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-0"><i class="fas fa-database text-primary mr-2"></i> <?= htmlspecialchars($title); ?></h3>
            <small class="text-muted">
                Menjalankan file SQL di folder <code>db/</code> sesuai urutan di <code>deploy_migrations.sh</code>.
                Database aktif: <strong><?= htmlspecialchars($db_name); ?></strong>
            </small>
        </div>
    </div>

    <?php foreach (['success'=>'success','error'=>'danger'] as $k=>$cls): ?>
        <?php if ($this->session->flashdata($k)): ?>
        <div class="alert alert-<?= $cls; ?> alert-dismissible fade show">
            <?= $this->session->flashdata($k); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Ringkasan -->
    <div class="row mb-3">
        <?php
            $cards = [
                ['Belum dijalankan', $count['pending'] ?? 0, 'secondary', 'fa-clock'],
                ['Berhasil',         $count['applied'] ?? 0, 'success',   'fa-check'],
                ['Sudah ada',        $count['skipped'] ?? 0, 'info',      'fa-forward'],
                ['Gagal',            $count['failed']  ?? 0, 'danger',    'fa-times'],
            ];
            if (!empty($count['missing'])) $cards[] = ['File hilang', $count['missing'], 'warning', 'fa-exclamation'];
        ?>
        <?php foreach ($cards as [$lbl,$n,$c,$ic]): ?>
        <div class="col">
            <div class="card border-left-<?= $c; ?> shadow-sm h-100 py-2">
                <div class="card-body py-2">
                    <div class="text-xs font-weight-bold text-<?= $c; ?> text-uppercase mb-1"><i class="fas <?= $ic; ?> mr-1"></i><?= $lbl; ?></div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= (int) $n; ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Peringatan + tombol jalankan semua -->
    <div class="card border-left-warning shadow-sm mb-4">
        <div class="card-body">
            <h6 class="font-weight-bold text-warning mb-2"><i class="fas fa-exclamation-triangle mr-1"></i> Baca dulu sebelum menjalankan</h6>
            <ul class="small mb-3 pl-3">
                <li><strong>Backup database dulu</strong> lewat cPanel / phpMyAdmin. MySQL melakukan commit otomatis di tiap perintah <code>CREATE</code>/<code>ALTER TABLE</code>, jadi migrasi yang gagal di tengah <strong>tidak bisa dibatalkan otomatis</strong>.</li>
                <li>Migrasi dijalankan <strong>berurutan</strong> dan <strong>berhenti di kegagalan pertama</strong> — sisanya tidak dijalankan supaya kerusakan tidak menumpuk.</li>
                <li>Kalau sebuah migrasi ternyata <strong>sudah pernah dijalankan manual</strong>, perintahnya akan dilewati otomatis (ditandai "Sudah ada"), bukan dianggap gagal.</li>
                <li>Setiap file hanya dijalankan sekali — riwayatnya dicatat di tabel <code>schema_migrations</code>.</li>
            </ul>

            <form method="post" action="<?= base_url('db_migration/run'); ?>" id="runAllForm">
                <input type="hidden" name="<?= $csrf_n; ?>" value="<?= $csrf_v; ?>">
                <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="confirmBackup" name="confirm_backup" value="1">
                    <label class="custom-control-label" for="confirmBackup">
                        Saya sudah <strong>membackup database</strong> <code><?= htmlspecialchars($db_name); ?></code> dan siap menjalankan migrasi.
                    </label>
                </div>
                <button type="submit" class="btn btn-primary" id="runAllBtn" disabled>
                    <i class="fas fa-play mr-1"></i>
                    Jalankan Semua yang Belum (<?= (int) ($count['pending'] ?? 0); ?>)
                </button>
                <span class="small text-muted ml-2">Centang dulu kotak di atas untuk mengaktifkan tombol.</span>
            </form>
        </div>
    </div>

    <?php if (!empty($log)): ?>
    <!-- Hasil eksekusi terakhir -->
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-terminal mr-1"></i> Hasil Eksekusi Terakhir</h6></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="thead-light"><tr><th>File</th><th width="120">Hasil</th><th width="90">Perintah</th><th>Keterangan</th></tr></thead>
                <tbody>
                <?php foreach ($log as $l): $b = $badge[$l['status']] ?? ['secondary', $l['status']]; ?>
                    <tr>
                        <td><code class="small"><?= htmlspecialchars($l['file']); ?></code></td>
                        <td><span class="badge badge-<?= $b[0]; ?>"><?= $b[1]; ?></span></td>
                        <td class="small"><?= (int) $l['run']; ?> jalan<?= $l['skipped'] ? ', ' . (int) $l['skipped'] . ' lewat' : ''; ?></td>
                        <td class="small" style="word-break:break-word"><?= nl2br(htmlspecialchars($l['message'] ?: '-')); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Daftar migrasi -->
    <div class="card shadow-sm">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Daftar Migrasi (<?= count($migrations); ?>)</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th width="45">#</th>
                            <th>File</th>
                            <th width="150">Status</th>
                            <th width="140">Dijalankan</th>
                            <th width="210">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($migrations as $m): $b = $badge[$m->status] ?? ['secondary', $m->status]; ?>
                        <tr>
                            <td class="text-muted"><?= $m->no; ?></td>
                            <td>
                                <code class="small"><?= htmlspecialchars($m->filename); ?></code>
                                <?php if (!$m->exists): ?>
                                    <span class="badge badge-warning ml-1">file tidak ada</span>
                                <?php endif; ?>
                                <?php if ($m->desc !== ''): ?>
                                    <div class="small text-muted" style="max-width:640px"><?= htmlspecialchars(mb_strimwidth($m->desc, 0, 200, '…')); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($m->message)): ?>
                                    <div class="small <?= $m->status === 'failed' ? 'text-danger' : 'text-muted'; ?>" style="max-width:640px"><?= htmlspecialchars(mb_strimwidth($m->message, 0, 300, '…')); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-<?= $b[0]; ?>"><?= $b[1]; ?></span></td>
                            <td class="small text-muted"><?= $m->applied_at ? date('d M Y H:i', strtotime($m->applied_at)) : '-'; ?></td>
                            <td>
                                <?php if ($m->exists && in_array($m->status, ['pending', 'failed'], true)): ?>
                                    <form method="post" action="<?= base_url('db_migration/run'); ?>" class="d-inline form-run-one">
                                        <input type="hidden" name="<?= $csrf_n; ?>" value="<?= $csrf_v; ?>">
                                        <input type="hidden" name="file" value="<?= htmlspecialchars($m->filename); ?>">
                                        <input type="hidden" name="confirm_backup" value="1">
                                        <button class="btn btn-sm btn-outline-primary" title="Jalankan file ini saja"><i class="fas fa-play"></i></button>
                                    </form>
                                    <form method="post" action="<?= base_url('db_migration/mark'); ?>" class="d-inline form-mark">
                                        <input type="hidden" name="<?= $csrf_n; ?>" value="<?= $csrf_v; ?>">
                                        <input type="hidden" name="file" value="<?= htmlspecialchars($m->filename); ?>">
                                        <button class="btn btn-sm btn-outline-secondary" title="Tandai sudah dijalankan (tanpa eksekusi)"><i class="fas fa-check"></i></button>
                                    </form>
                                <?php elseif ($m->status !== 'pending'): ?>
                                    <form method="post" action="<?= base_url('db_migration/forget'); ?>" class="d-inline form-forget">
                                        <input type="hidden" name="<?= $csrf_n; ?>" value="<?= $csrf_v; ?>">
                                        <input type="hidden" name="file" value="<?= htmlspecialchars($m->filename); ?>">
                                        <button class="btn btn-sm btn-outline-warning" title="Hapus catatan supaya bisa dijalankan ulang"><i class="fas fa-undo"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    $('#confirmBackup').on('change', function () { $('#runAllBtn').prop('disabled', !this.checked); });

    $('#runAllForm').on('submit', function (e) {
        if (!confirm('Jalankan SEMUA migrasi yang belum dijalankan?\n\nPastikan database sudah di-backup. Proses berhenti otomatis kalau ada yang gagal.')) e.preventDefault();
    });
    $('.form-run-one').on('submit', function (e) {
        if (!confirm('Jalankan migrasi ini sekarang?\n\nPastikan database sudah di-backup.')) e.preventDefault();
    });
    $('.form-mark').on('submit', function (e) {
        if (!confirm('Tandai sebagai SUDAH dijalankan tanpa benar-benar menjalankannya?\n\nPakai ini hanya kalau Anda yakin migrasi ini sudah pernah dijalankan manual.')) e.preventDefault();
    });
    $('.form-forget').on('submit', function (e) {
        if (!confirm('Hapus catatan migrasi ini supaya bisa dijalankan ulang?')) e.preventDefault();
    });
});
</script>
