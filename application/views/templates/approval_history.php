<?php
/**
 * Kartu "Riwayat Approval" + thread balasan Back to Draft — PARTIAL BERSAMA.
 *
 * Dibuat 2026-09-06 waktu fitur ini dipasang ke PR (user: "untuk PR okee").
 * IRF/PRF/ERF/PO dulu meng-copy markup yang sama ke 11 view terpisah; di sini
 * sengaja 1 file dipakai 6 halaman PR (3 detail requestor + 3 detail approval)
 * supaya tidak divergen lagi. Kalau nanti IRF/PRF/ERF/PO mau ikut dirapikan,
 * tinggal arahkan ke partial ini juga.
 *
 * Variabel yang WAJIB dikirim pemanggil:
 *   $logs          array row log (punya: action, actor_id, actor_name, notes, created_at)
 *   $can_reply     bool — hasil can_reply_return_thread() di controller
 *   $reply_action  URL tujuan form balasan (POST)
 *   $owner_id      user_id pembuat dokumen — penentu sisi bubble (kanan = pembuat)
 * Opsional:
 *   $redirect_back nilai field hidden `redirect` (mis. 'approval') supaya
 *                  setelah kirim balasan kembali ke halaman asal
 */
$logs          = $logs          ?? [];
$can_reply     = $can_reply     ?? false;
$reply_action  = $reply_action  ?? '';
$owner_id      = (int) ($owner_id ?? 0);
$redirect_back = $redirect_back ?? null;

if (empty($logs) && empty($can_reply)) return;

$log_meta = [
    'Submit'          => ['label' => 'Submitted',                'icon' => 'paper-plane',    'color' => 'info'],
    'Approve HOD'     => ['label' => 'Approved (Head of Dept)',  'icon' => 'check',          'color' => 'success'],
    'Endorse'         => ['label' => 'Endorsed',                 'icon' => 'file-signature', 'color' => 'primary'],
    'Approve Final'   => ['label' => 'Final Approved',           'icon' => 'check-double',   'color' => 'success'],
    'Return to Draft' => ['label' => 'Returned to Draft',        'icon' => 'undo',           'color' => 'warning'],
    'Reject'          => ['label' => 'Rejected',                 'icon' => 'times',          'color' => 'danger'],
    'Reply'           => ['label' => 'Balasan',                  'icon' => 'reply',          'color' => 'secondary'],
];
?>
<div class="card shadow-sm mb-4" id="riwayat-approval">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-history mr-1"></i> Riwayat Approval</h6>
    </div>
    <div class="card-body">
        <?php if (empty($logs)): ?>
            <p class="small text-muted mb-0"><i class="fas fa-info-circle mr-1"></i> Belum ada riwayat.</p>
        <?php else: ?>
        <ul class="list-unstyled mb-0">
            <?php foreach ($logs as $log):
                $m        = $log_meta[$log->action] ?? ['label' => $log->action, 'icon' => 'circle', 'color' => 'secondary'];
                $is_reply = ($log->action === 'Reply');
                $mine     = $is_reply && ((int) ($log->actor_id ?? 0) === $owner_id);
            ?>
            <?php if ($is_reply): ?>
            <li class="d-flex mb-3 <?= $mine ? 'flex-row-reverse text-right' : ''; ?>">
                <div class="<?= $mine ? 'ml-2' : 'mr-2'; ?>" style="max-width:75%;">
                    <div class="px-3 py-2" style="background:<?= $mine ? '#e7f1ff' : '#f1f1f1'; ?>;border-radius:14px;<?= $mine ? 'border-top-right-radius:2px;' : 'border-top-left-radius:2px;'; ?>">
                        <div class="small font-weight-bold text-gray-800">
                            <?= htmlspecialchars($log->actor_name ?: '-'); ?>
                            <span class="font-weight-normal text-muted">&mdash; <?= $mine ? 'Requestor' : 'Approver'; ?></span>
                        </div>
                        <div class="small" style="word-break:break-word;"><?= nl2br(htmlspecialchars($log->notes ?? '')); ?></div>
                    </div>
                    <div class="small text-muted mt-1"><?= $log->created_at ? date('d M Y H:i', strtotime($log->created_at)) : ''; ?></div>
                </div>
            </li>
            <?php else: ?>
            <li class="d-flex mb-3">
                <div class="mr-3 text-center" style="width:34px;">
                    <span class="badge badge-<?= $m['color']; ?> d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;border-radius:50%;">
                        <i class="fas fa-<?= $m['icon']; ?>"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <div class="font-weight-bold text-<?= $m['color']; ?>"><?= htmlspecialchars($m['label']); ?></div>
                    <div class="small text-gray-800"><?= htmlspecialchars($log->actor_name ?: '-'); ?></div>
                    <div class="small text-muted"><?= $log->created_at ? date('d M Y H:i', strtotime($log->created_at)) : ''; ?></div>
                    <?php if (!empty($log->notes)): ?>
                        <div class="mt-1 px-2 py-1 small" style="background:#f8f9fc;border-left:3px solid #d1d3e2;border-radius:3px;color:#555;font-style:italic;word-break:break-word;">
                            <i class="fas fa-comment-dots text-muted mr-1"></i><?= nl2br(htmlspecialchars($log->notes)); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </li>
            <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php if (!empty($can_reply) && $reply_action): ?>
        <form method="POST" action="<?= $reply_action; ?>" class="mt-3 pt-3 border-top">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            <?php if ($redirect_back): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect_back); ?>">
            <?php endif; ?>
            <div class="form-group mb-2">
                <label class="small font-weight-bold mb-1">Balas</label>
                <textarea name="message" class="form-control form-control-sm" rows="2" placeholder="Tulis balasan/klarifikasi..." required></textarea>
            </div>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-paper-plane mr-1"></i> Kirim Balasan</button>
        </form>
        <?php endif; ?>
    </div>
</div>
