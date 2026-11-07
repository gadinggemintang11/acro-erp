<?php
    $cat_slug = strtolower($category_slug ?? '');
    $base_index_url = 'consumable_out' . ($locked_category ? '/index/' . $cat_slug : '');
    $add_url = 'consumable_out/add' . ($locked_category ? '/' . $cat_slug : '');

    // Tombol "Create New Issuance" (buat pengeluaran bebas dari halaman ini)
    // DISEMBUNYIKAN atas permintaan user 2026-09-09. Pengeluaran barang tetap
    // dibuat lewat tombol "Proses Pengeluaran" di tabel IRF outstanding di
    // bawah (endpoint consumable_out/add?request_id=... TIDAK dinonaktifkan,
    // masih dipakai tombol itu). Untuk memunculkan lagi: ubah ke true.
    $show_create_button = false;
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-box-open text-primary mr-2"></i><?= lang_line('consumable_out_title', 'Pengeluaran Barang Habis Pakai'); ?>
            <?php if ($locked_category): ?>
                <span class="badge badge-<?= $locked_category === 'Produksi' ? 'dark' : 'secondary' ?> align-middle"><?= htmlspecialchars($locked_category) ?></span>
            <?php endif; ?>
        </h3>
        <?php if ($show_create_button && !empty($can_add)): ?>
        <a href="<?= base_url($add_url); ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-plus fa-sm mr-1"></i> <?= lang_line('consumable_out_create', 'Buat Pengeluaran Baru'); ?>
        </a>
        <?php endif; ?>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $this->session->flashdata('success'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <!-- ============================================================
         Permintaan Barang Approved — antrian siap dikeluarkan
    ============================================================= -->
    <?php if (!empty($pending_irfs)): ?>
    <div class="card shadow mb-4 border-warning">
        <div class="card-header bg-warning text-white py-2 d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-inbox mr-2"></i>Permintaan Barang Approved — Siap Dikeluarkan (<?= count($pending_irfs); ?>)</strong>
            <small>Klik <em>Proses Pengeluaran</em> untuk fulfill Permintaan Barang.</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>No. Permintaan Barang</th>
                            <th>Tanggal</th>
                            <th>Departemen</th>
                            <th>Kategori</th>
                            <th>Requestor</th>
                            <th>Approved By</th>
                            <th>Outstanding Qty</th>
                            <th width="170">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_irfs as $irf): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($irf->request_number); ?></td>
                            <td><?= date('d M Y', strtotime($irf->request_date)); ?></td>
                            <td><?= htmlspecialchars($irf->department ?: '-'); ?></td>
                            <td><span class="badge badge-<?= ($irf->category ?? 'General') === 'Produksi' ? 'dark' : 'secondary' ?>"><?= htmlspecialchars($irf->category ?? 'General') ?></span></td>
                            <td class="text-left"><?= htmlspecialchars($irf->requestor_name ?: '-'); ?></td>
                            <td class="small text-left">
                                <?= htmlspecialchars($irf->approved_by_name ?: '-'); ?><br>
                                <span class="text-muted"><?= $irf->hod_approved_at ? date('d M Y H:i', strtotime($irf->hod_approved_at)) : ''; ?></span>
                            </td>
                            <td class="text-right">
                                <span class="badge badge-warning"><?= number_format($irf->total_outstanding, 2); ?></span>
                                <small class="text-muted d-block"><?= number_format($irf->total_issued, 2); ?> / <?= number_format($irf->total_requested, 2); ?></small>
                            </td>
                            <td>
                                <a href="<?= base_url('consumable_request/detail/'.$irf->id); ?>" class="btn btn-sm btn-secondary" title="Lihat Detail Permintaan Barang">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (check_access('consumable_out_' . strtolower($irf->category ?? 'General'), 'add') || check_access('consumable_out', 'add')): ?>
                                <a href="<?= base_url('consumable_out/add?request_id='.$irf->id); ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-sign-out-alt"></i> Proses Pengeluaran
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================================
         Daftar Pengeluaran (history) — dengan filter inline di header
    ============================================================= -->
    <div class="card border-0 shadow-sm rounded">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-history mr-1"></i>
                <?= lang_line('consumable_out_list_title', 'Daftar Pengeluaran Barang Habis Pakai'); ?>
            </h6>
            <form method="get" action="<?= base_url($base_index_url); ?>" class="form-inline flex-wrap mt-2 mt-md-0">
                <div class="form-group mr-2">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="<?= lang_line('consumable_out_search_placeholder', 'Cari...'); ?>"
                           value="<?= htmlspecialchars($filters['search'] ?? ''); ?>" style="min-width:200px;">
                </div>
                <div class="form-group mr-2">
                    <select name="status" class="form-control form-control-sm">
                        <option value=""><?= lang_line('common_all'); ?> <?= lang_line('label_status'); ?></option>
                        <option value="Created"   <?= ($filters['status'] ?? '') == 'Created'   ? 'selected' : ''; ?>><?= lang_line('consumable_out_status_created', 'Created'); ?></option>
                        <option value="Confirmed" <?= ($filters['status'] ?? '') == 'Confirmed' ? 'selected' : ''; ?>><?= lang_line('consumable_out_status_confirmed', 'Confirmed'); ?></option>
                        <option value="Cancelled" <?= ($filters['status'] ?? '') == 'Cancelled' ? 'selected' : ''; ?>><?= lang_line('consumable_out_status_cancelled', 'Cancelled'); ?></option>
                    </select>
                </div>
                <?php if ($locked_category): ?>
                    <!-- Kategori terkunci sesuai pintu masuk menu -> tidak perlu dropdown. -->
                <?php elseif (count($categories) > 1): ?>
                <div class="form-group mr-2">
                    <select name="category" class="form-control form-control-sm">
                        <option value=""><?= lang_line('common_all'); ?> <?= lang_line('cr_category', 'Kategori'); ?></option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= (($filters['category'] ?? '') === $c) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="form-group mr-2">
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['date_from'] ?? ''); ?>" title="<?= lang_line('from_date'); ?>">
                </div>
                <div class="form-group mr-2">
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['date_to'] ?? ''); ?>" title="<?= lang_line('to_date'); ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-sm mr-1"><i class="fas fa-search fa-sm"></i></button>
                    <a href="<?= base_url($base_index_url); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-redo fa-sm"></i></a>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('consumable_out_doc_number', 'No. Dokumen'); ?></th>
                            <th><?= lang_line('label_date'); ?></th>
                            <th><?= lang_line('consumable_department'); ?></th>
                            <th><?= lang_line('cr_category', 'Kategori'); ?></th>
                            <th><?= lang_line('consumable_out_recipient', 'Penerima'); ?></th>
                            <th><?= lang_line('consumable_out_purpose', 'Keperluan'); ?></th>
                            <th><?= lang_line('label_status'); ?></th>
                            <th><?= lang_line('adjustment_created_by'); ?></th>
                            <th width="160"><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($records)): foreach ($records as $r): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($r->doc_number); ?></td>
                            <td><?= date('d M Y', strtotime($r->issuance_date)); ?></td>
                            <td class="text-left"><?= htmlspecialchars($r->department ?? '-'); ?></td>
                            <td><span class="badge badge-<?= ($r->category ?? 'General') === 'Produksi' ? 'dark' : 'secondary' ?>"><?= htmlspecialchars($r->category ?? 'General') ?></span></td>
                            <td class="text-left"><?= htmlspecialchars($r->recipient ?? '-'); ?></td>
                            <td class="text-left" style="max-width:200px;">
                                <?= htmlspecialchars(mb_strimwidth($r->purpose ?? '-', 0, 60, '...')); ?>
                            </td>
                            <td>
                                <?php
                                    $badge = 'secondary';
                                    if ($r->status == 'Created')   $badge = 'warning';
                                    if ($r->status == 'Confirmed') $badge = 'success';
                                    if ($r->status == 'Cancelled') $badge = 'danger';
                                ?>
                                <span class="badge badge-<?= $badge; ?>"><?= $r->status; ?></span>
                            </td>
                            <td><?= htmlspecialchars($r->created_by_name ?? '-'); ?></td>
                            <td>
                                <a href="<?= base_url('consumable_out/detail/' . $r->id); ?>"
                                   class="btn btn-sm btn-info btn-load" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= base_url('consumable_out/export_pdf/' . $r->id); ?>"
                                   class="btn btn-sm btn-secondary" target="_blank" title="PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                <?php if ($r->status == 'Created' && (check_access('consumable_out_' . strtolower($r->category ?? 'General'), 'delete') || check_access('consumable_out', 'delete'))): ?>
                                <a href="<?= base_url('consumable_out/delete/' . $r->id); ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Hapus dokumen ini?');" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
