<?php
    // Tujuan klik badge "PO" di kolom baru — tiap tipe PO punya controller
    // sendiri (user 2026-09-11: "bisa diklik dia di PO berapa langsung
    // menuju halaman PO-nya"). Cermin $edit_route_map di Po_monitoring.php.
    $po_link_routes = [
        'produksi'     => 'purchase_order',
        'non_produksi' => 'purchase_order_general',
        'jasa'         => 'purchase_order_jasa',
        'asset'        => 'purchase_order_asset',
    ];
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-0"><i class="fas fa-concierge-bell text-primary mr-2"></i> Purchase Request Jasa</h3>
            <small class="text-muted">Ajukan kebutuhan jasa sebelum dibuatkan PO Jasa</small>
        </div>
        <?php // Tombol "Tambah PR Jasa" manual DIHAPUS (ERP Revision Notes
              // 2026-09-02 & 03 — PR Jasa selalu berawal dari IRF, tidak
              // boleh dibuat bebas/tanpa sumber lagi). Lihat alert info di
              // bawah + [[revision_irf_auto_pr_at_issuance]] di memory Claude. ?>
    </div>

    <div class="alert alert-info py-2 mb-3">
        <i class="fas fa-info-circle mr-1"></i>
        PR Jasa sekarang selalu berawal dari Permintaan Barang — tidak bisa dibuat manual/bebas lagi. Buat PR-nya dari
        <a href="<?= base_url('consumable_outstanding'); ?>">menu Outstanding Permintaan Barang</a> utk item Jasa yang sudah di-approve.
    </div>

    <!-- Flash messages -->
    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle mr-1"></i> <?= $this->session->flashdata('success'); ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle mr-1"></i> <?= $this->session->flashdata('error'); ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="form-inline flex-wrap gap-2">
                <div class="form-group mr-2 mb-1">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="No PR, Supplier, Keperluan..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="form-group mr-2 mb-1">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">-- Semua Status --</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= ($filters['status'] === $s) ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1"><i class="fas fa-search"></i> Filter</button>
                <a href="<?= base_url('purchase_request_jasa') ?>" class="btn btn-sm btn-secondary mb-1 mr-1">Reset</a>
            </form>
            <div class="mt-1">
                <?php
                    // Tombol "Lihat Semua PR" — KHUSUS ADMIN (user 2026-09-09).
                    // Default listing ini memang cuma menampilkan PR milik user
                    // yang login (disengaja, bukan bug). Admin butuh pandangan
                    // penuh, jadi dikasih toggle ?all=1. Gate sesungguhnya ada di
                    // controller (_list_view_all()), yang di sini cuma tampilan.
                    // Link Excel/PDF di atas ikut otomatis karena mereka
                    // meneruskan seluruh $_GET.
                    $qs_all  = array_merge($_GET, ['all' => 1]);
                    $qs_mine = $_GET; unset($qs_mine['all']);
                ?>
                <?php if (!empty($can_view_all)): ?>
                    <?php if (empty($view_all)): ?>
                        <a href="<?= base_url('purchase_request_jasa') . '?' . http_build_query($qs_all) ?>" class="btn btn-sm btn-outline-dark mr-1">
                            <i class="fas fa-users"></i> <?= lang_line('pr_view_all_btn', 'Lihat Semua PR'); ?>
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('purchase_request_jasa') . ($qs_mine ? '?' . http_build_query($qs_mine) : '') ?>" class="btn btn-sm btn-dark mr-1">
                            <i class="fas fa-user"></i> <?= lang_line('pr_view_mine_btn', 'Tampilkan Punya Saya Saja'); ?>
                        </a>
                        <span class="badge badge-dark align-middle mr-1"><i class="fas fa-eye mr-1"></i><?= lang_line('pr_view_all_active', 'Mode Admin: semua PR semua user'); ?></span>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="<?= base_url('purchase_request_jasa/export_list_excel') . '?' . http_build_query($_GET) ?>" class="btn btn-sm btn-success no-loader mr-1" target="_blank"><i class="fas fa-file-excel"></i> Excel</a>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>PR Number</th>
                            <th>Tanggal</th>
                            <th>Requester</th>
                            <th>Keperluan</th>
                            <th>Status</th>
                            <th>PO</th>
                            <th width="160">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($prs)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i> Belum ada data Purchase Request Jasa.</td></tr>
                        <?php else: ?>
                            <?php foreach ($prs as $pr): ?>
                            <?php
                            $badge = [
                                'Draft'     => 'secondary',
                                'Submitted' => 'info',
                                'Approved'  => 'success',
                                'Rejected'  => 'danger',
                            ][$pr->status] ?? 'secondary';
                            ?>
                            <tr>
                                <td><strong class="text-primary"><?= htmlspecialchars($pr->pr_number) ?></strong></td>
                                <td><?= date('d M Y', strtotime($pr->pr_date)) ?></td>
                                <td><?= htmlspecialchars($pr->requester_name ?? '-') ?></td>
                                <td><?= htmlspecialchars(mb_strimwidth($pr->purpose ?? '-', 0, 50, '...')) ?></td>
                                <td><span class="badge badge-<?= $badge ?>"><?= $pr->status ?></span></td>
                                <?php
                                    // Sudah ditarik ke PO atau belum (user 2026-09-11).
                                    // PO batal tetap ditampilkan (dicoret) sbg jejak.
                                    $pr_pos = $po_links[$pr->id] ?? [];
                                ?>
                                <td>
                                    <?php if (empty($pr_pos)): ?>
                                        <span class="badge badge-light border text-muted"><?= lang_line('purchase_request_po_none', 'Belum di-PO'); ?></span>
                                    <?php else: foreach ($pr_pos as $po): ?>
                                        <a href="<?= base_url(($po_link_routes[$po['type']] ?? 'purchase_order_general') . '/detail/' . $po['po_id']) ?>"
                                           class="badge badge-<?= $po['void'] ? 'light border text-muted' : 'success' ?> mr-1 mb-1 d-inline-block"
                                           style="<?= $po['void'] ? 'text-decoration:line-through;' : '' ?>"
                                           title="<?= htmlspecialchars($po['po_number'] . ' — ' . $po['status']) ?>">
                                            <i class="fas fa-file-invoice mr-1"></i><?= htmlspecialchars($po['po_number']) ?>
                                        </a>
                                    <?php endforeach; endif; ?>
                                </td>
                                <?php
                                    // Sudah ditarik ke PO atau belum. PO batal
                                    // tetap ditampilkan (dicoret) supaya jejaknya
                                    // tidak hilang, tapi jelas bukan pembelian aktif.
                                    $pr_pos = $po_links[$pr->id] ?? [];
                                ?>
                                <td>
                                    <?php if (empty($pr_pos)): ?>
                                        <span class="badge badge-light border text-muted"><?= lang_line('purchase_request_po_none', 'Belum di-PO'); ?></span>
                                    <?php else: foreach ($pr_pos as $po): ?>
                                        <a href="<?= base_url(($po_link_routes[$po['type']] ?? 'purchase_order_general') . '/detail/' . $po['po_id']) ?>"
                                           class="badge badge-<?= $po['void'] ? 'light border text-muted' : 'success' ?> mr-1 mb-1 d-inline-block"
                                           style="<?= $po['void'] ? 'text-decoration:line-through;' : '' ?>"
                                           title="<?= htmlspecialchars($po['po_number'] . ' — ' . $po['status']) ?>">
                                            <i class="fas fa-file-invoice mr-1"></i><?= htmlspecialchars($po['po_number']) ?>
                                        </a>
                                    <?php endforeach; endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('purchase_request_jasa/detail/' . $pr->id) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                    <?php if ($pr->status === 'Draft' && check_access('purchase_request_jasa', 'delete')): ?>
                                        <a href="<?= base_url('purchase_request_jasa/delete_header/' . $pr->id) ?>"
                                           class="btn btn-sm btn-outline-danger btn-delete"
                                           data-message="Yakin hapus PR Draft ini?">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php // Modal "Create New PR Jasa" DIHAPUS bareng tombolnya (lihat catatan di
      // atas) — endpoint add_header() di controller di-block server-side juga. ?>

<script>
$(document).ready(function() {
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        var msg = $(this).data('message') || 'Yakin?';
        if (confirm(msg)) window.location.href = $(this).attr('href');
    });
});
</script>
