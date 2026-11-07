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
<?php
    // IPR sekarang menu GABUNGAN General+Jasa+Asset (user 2026-09-06, sidebar
    // Jasa & Asset digabung ke sini, pola sama spt GMPO — lihat memory
    // pr_controllers_merge.md) -- tiap baris pr_header py `type` masing2
    // (non_produksi/jasa/asset), jadi link Detail/Delete per baris HARUS
    // di-route ke controller ASLI tipe itu.
    $pr_type_routes = [
        'non_produksi' => ['route' => 'purchase_request_general', 'menu_key' => 'purchase_request_general', 'label' => 'General', 'badge' => 'secondary'],
        'jasa'         => ['route' => 'purchase_request_jasa',    'menu_key' => 'purchase_request_jasa',    'label' => 'Jasa',    'badge' => 'info'],
        'asset'        => ['route' => 'purchase_request_asset',   'menu_key' => 'purchase_request_asset',   'label' => 'Asset',   'badge' => 'dark'],
    ];
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-0"><i class="fas fa-file-alt text-primary mr-2"></i> <?= lang_line('page_purchase_request_title'); ?></h3>
            <small class="text-muted"><?= lang_line('purchase_request_approval_subtitle'); ?></small>
        </div>
        <?php
        // Buat PR manual dibuka lagi (2026-11-05, rebrand Acropoly): PR dibuat
        // Draft, diisi item manual / "Tarik dari Permintaan Barang", lalu
        // Submit -> approval 1 tahap. Tipe yang bisa dipilih ikut izin 'add'.
        $create_types = [];
        foreach (['non_produksi' => 'General', 'jasa' => 'Jasa', 'asset' => 'Asset'] as $t => $lbl) {
            if (check_access($pr_type_routes[$t]['menu_key'], 'add')) $create_types[$t] = $lbl;
        }
        ?>
        <?php if (!empty($create_types)): ?>
            <button type="button" class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addPrModal">
                <i class="fas fa-plus fa-sm mr-1"></i> <?= lang_line('purchase_request_btn_create', 'Buat PR'); ?>
            </button>
        <?php endif; ?>
    </div>

    <div class="alert alert-info py-2 mb-3">
        <i class="fas fa-info-circle mr-1"></i>
        <?= lang_line('pr_general_flow_info', 'PR dibuat manual (tombol Buat PR) atau dari menu Outstanding Permintaan Barang. Item bisa diketik manual atau ditarik dari Permintaan Barang, lalu Submit — PR perlu 1 kali approval sebelum bisa dibuatkan PO.'); ?>
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
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="<?= lang_line('purchase_request_search_placeholder'); ?>" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="form-group mr-2 mb-1">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">-- <?= lang_line('text_all_status'); ?> --</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= ($filters['status'] === $s) ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1"><i class="fas fa-search"></i> <?= lang_line('btn_filter'); ?></button>
                <a href="<?= base_url('purchase_request_general') ?>" class="btn btn-sm btn-secondary mb-1 mr-1"><?= lang_line('btn_reset'); ?></a>
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
                        <a href="<?= base_url('purchase_request_general') . '?' . http_build_query($qs_all) ?>" class="btn btn-sm btn-outline-dark mr-1">
                            <i class="fas fa-users"></i> <?= lang_line('pr_view_all_btn', 'Lihat Semua PR'); ?>
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('purchase_request_general') . ($qs_mine ? '?' . http_build_query($qs_mine) : '') ?>" class="btn btn-sm btn-dark mr-1">
                            <i class="fas fa-user"></i> <?= lang_line('pr_view_mine_btn', 'Tampilkan Punya Saya Saja'); ?>
                        </a>
                        <span class="badge badge-dark align-middle mr-1"><i class="fas fa-eye mr-1"></i><?= lang_line('pr_view_all_active', 'Mode Admin: semua PR semua user'); ?></span>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="<?= base_url('purchase_request_general/export_list_excel') . '?' . http_build_query($_GET) ?>" class="btn btn-sm btn-success no-loader mr-1" target="_blank"><i class="fas fa-file-excel"></i> <?= lang_line('text_excel'); ?></a>
                <a href="<?= base_url('purchase_request_general/export_list_pdf') . '?' . http_build_query($_GET) ?>" class="btn btn-sm btn-danger no-loader" target="_blank"><i class="fas fa-file-pdf"></i> <?= lang_line('text_pdf'); ?></a>
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
                            <th><?= lang_line('purchase_request_number'); ?></th>
                            <th><?= lang_line('po_kategori', 'Kategori'); ?></th>
                            <th><?= lang_line('text_date'); ?></th>
                            <th><?= lang_line('purchase_request_requester'); ?></th>
                            <th><?= lang_line('purchase_request_purpose'); ?></th>
                            <th><?= lang_line('text_status'); ?></th>
                            <th><?= lang_line('purchase_request_po_col', 'PO'); ?></th>
                            <th width="160"><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($prs)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i> <?= lang_line('purchase_request_no_data'); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($prs as $pr): ?>
                            <?php
                            $badge = [
                                'Draft'     => 'secondary',
                                'Submitted' => 'info',
                                'Approved'  => 'success',
                                'Rejected'  => 'danger',
                            ][$pr->status] ?? 'secondary';
                            $rt = $pr_type_routes[$pr->type] ?? $pr_type_routes['non_produksi'];
                            ?>
                            <tr>
                                <td><strong class="text-primary"><?= htmlspecialchars($pr->pr_number) ?></strong></td>
                                <td><span class="badge badge-<?= $rt['badge']; ?>"><?= $rt['label']; ?></span></td>
                                <td><?= date('d M Y', strtotime($pr->pr_date)) ?></td>
                                <td><?= htmlspecialchars($pr->requester_name ?? '-') ?></td>
                                <td><?= htmlspecialchars(mb_strimwidth($pr->purpose ?? '-', 0, 50, '...')) ?></td>
                                <td><span class="badge badge-<?= $badge ?>"><?= $pr->status ?></span></td>
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
                                    <a href="<?= base_url($rt['route'] . '/detail/' . $pr->id) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> <?= lang_line('btn_detail'); ?>
                                    </a>
                                    <?php if ($pr->status === 'Draft' && check_access($rt['menu_key'], 'delete')): ?>
                                        <a href="<?= base_url($rt['route'] . '/delete_header/' . $pr->id) ?>"
                                           class="btn btn-sm btn-outline-danger btn-delete"
                                           data-message="<?= lang_line('purchase_request_delete_confirm'); ?>">
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

<!-- Modal "Create New PR" DIHAPUS (ERP Revision Notes 2026-09-02 — PR
     General tidak boleh dibuat manual lagi, lihat catatan di atas dekat
     tombol yang juga sudah dihapus). Endpoint `add_header()` di controller
     ikut di-block server-side, bukan cuma UI-nya yang hilang. -->

<script>
$(document).ready(function() {
    // Confirm delete
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        var msg = $(this).data('message') || 'Yakin?';
        if (confirm(msg)) window.location.href = $(this).attr('href');
    });
});
</script>

<?php if (!empty($create_types)): ?>
<!-- Modal Buat PR (manual) — 2026-11-05 -->
<div class="modal fade" id="addPrModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" id="addPrForm" action="<?= base_url($pr_type_routes[array_key_first($create_types)]['route'] . '/add_header') ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i> <?= lang_line('purchase_request_create_title', 'Buat Purchase Request'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label><?= lang_line('purchase_request_type_label', 'Tipe PR'); ?> <span class="text-danger">*</span></label>
                            <select id="addPrType" class="form-control" required>
                                <?php foreach ($create_types as $t => $lbl): ?>
                                    <option value="<?= $t ?>" data-action="<?= base_url($pr_type_routes[$t]['route'] . '/add_header') ?>"><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label><?= lang_line('purchase_request_date_label', 'Tanggal'); ?> <span class="text-danger">*</span></label>
                            <input type="date" name="pr_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label><?= lang_line('purchase_request_department_label', 'Departemen'); ?></label>
                            <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($this->session->userdata('departemen') ?? '') ?>" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('purchase_request_purpose_label', 'Tujuan Permintaan'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="purpose" class="form-control" required>
                    </div>
                    <div class="form-group mb-0">
                        <label><?= lang_line('purchase_request_remarks_label', 'Catatan'); ?></label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                    <small class="text-muted d-block mt-2"><i class="fas fa-info-circle"></i> <?= lang_line('purchase_request_create_hint', 'Setelah dibuat (Draft), tambahkan item manual atau tarik dari Permintaan Barang, lalu Submit untuk approval.'); ?></small>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-arrow-right mr-1"></i> <?= lang_line('purchase_request_create_submit', 'Buat & Lanjut Isi Item'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel', 'Batal'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var sel = document.getElementById('addPrType'), form = document.getElementById('addPrForm');
    if (sel && form) sel.addEventListener('change', function () { form.action = sel.options[sel.selectedIndex].getAttribute('data-action'); });
    // Dibuka dari Stock Alert / link lain: purchase_request_general?open=create
    if (new URLSearchParams(window.location.search).get('open') === 'create' && window.jQuery) {
        jQuery('#addPrModal').modal('show');
    }
});
</script>
<?php endif; ?>
