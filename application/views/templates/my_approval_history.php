<?php
/**
 * Kartu "Riwayat Approval Saya" — PARTIAL BERSAMA semua halaman antrian approval.
 *
 * Dibuat 2026-09-06 (user: halaman approval "hanya menampilkan list daftar yang
 * akan di approve, seharusnya di bagian bawah ada riwayat yang user itu
 * approve"). Datanya dari Approval_history_model::get_my_history().
 *
 * Variabel yang dikirim pemanggil:
 *   $history      array baris riwayat (dari model)
 *   $route        prefix URL detail dokumen, mis. 'prf/detail' (link: route/{id})
 * Opsional:
 *   $history_title  judul kartu
 *   $route_map      ['<doc_status atau type>' => 'route'] TIDAK dipakai di sini —
 *                   modul gabungan (GMPO/IPR) cukup kirim $route controller-nya
 *                   sendiri, detail lintas-tipe tetap bisa dibuka dari sana.
 */
$history       = $history       ?? [];
$route         = $route         ?? '';
$history_title = $history_title ?? 'Riwayat Approval Saya';

$status_color = function ($s) {
    $map = [
        'Draft' => 'secondary', 'Submitted' => 'warning',
        'Approved Head Dept' => 'info', 'Endorsed' => 'primary',
        'Approved' => 'success', 'Confirmed' => 'success', 'Open' => 'primary',
        'Completed' => 'success', 'Finish' => 'success', 'Closed' => 'dark',
        'Rejected' => 'danger', 'Cancelled' => 'danger',
    ];
    return $map[$s] ?? 'secondary';
};
?>
<div class="card border-0 shadow-sm rounded mt-4" id="riwayat-approval-saya">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-user-check mr-1"></i> <?= htmlspecialchars($history_title); ?>
        </h6>
        <?php if (!empty($history)): ?>
            <span class="badge badge-light border"><?= count($history); ?> aksi terakhir</span>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($history)): ?>
            <p class="small text-muted mb-0 p-3">
                <i class="fas fa-info-circle mr-1"></i>
                Belum ada dokumen yang Anda approve/reject di modul ini.
            </p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Nomor Dokumen</th>
                        <th>Aksi Saya</th>
                        <th>Waktu Aksi</th>
                        <th>Status Sekarang</th>
                        <th width="90" class="text-center">Lihat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                    <tr>
                        <td class="font-weight-bold text-primary"><?= htmlspecialchars($h->doc_number ?? '-'); ?></td>
                        <td><span class="badge badge-<?= htmlspecialchars($h->my_action_color ?? 'secondary'); ?>"><?= htmlspecialchars($h->my_action ?? '-'); ?></span></td>
                        <td class="small"><?= !empty($h->my_action_at) ? date('d M Y H:i', strtotime($h->my_action_at)) : '-'; ?></td>
                        <td><span class="badge badge-<?= $status_color($h->doc_status ?? ''); ?>"><?= htmlspecialchars($h->doc_status ?? '-'); ?></span></td>
                        <td class="text-center">
                            <?php if ($route): ?>
                            <a href="<?= base_url($route . '/' . $h->id); ?>" class="btn btn-sm btn-outline-secondary" title="Lihat detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
