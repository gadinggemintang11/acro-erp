<?php
    // $reset_url eksplisit (dipakai Po_monitoring, krn route-nya 'po_monitoring'
    // bukan '<label>_list') — fallback ke pola lama kalau tidak dikirim
    // (Pmpo_list/Gmpo_list kini juga mengirim reset_url eksplisit, karena labelnya
    // jadi "PO Material"/"PO General" -- bukan nama rute lagi).
    $base_url = $reset_url ?? (strtolower($po_type_label) . '_list');
    $status_meta = [
        'Draft'     => 'secondary',
        'Submitted' => 'info',
        'Confirmed' => 'success',
        'Closed'    => 'dark',
        'Cancelled' => 'danger',
    ];
    $status_label_map = [
        'Draft'     => lang_line('pdb_status_draft'),
        'Submitted' => lang_line('pdb_status_submitted'),
        'Confirmed' => lang_line('pdb_status_confirmed'),
        'Closed'    => lang_line('pdb_status_closed'),
        'Cancelled' => lang_line('pdb_status_cancelled'),
    ];
    // Label per tipe PO — dipakai baik utk subtitle (mode 1-tipe-terkunci) MAUPUN
    // badge kolom Type per-baris (mode gabungan). ERP Revision Notes,
    // [[erp_revision_monitoring_lists]]: view ini awalnya cuma tahu 2 tipe
    // (Pmpo_list/Gmpo_list), sekarang dipakai juga oleh Po_monitoring (gabungan
    // 4 tipe termasuk jasa/asset yg dulu tidak ke-cover di list manapun).
    $type_labels = [
        'produksi'     => lang_line('pdb_label_production_mat'),
        'non_produksi' => lang_line('pdb_label_general_mat'),
        'jasa'         => lang_line('pdb_label_jasa', 'Jasa'),
        'asset'        => lang_line('pdb_label_asset', 'Asset'),
    ];
    $po_label_translated = $type_labels[$po_type] ?? lang_line('pdb_label_all_types', 'Semua Tipe');
    // Mode gabungan (Po_monitoring) vs mode 1-tipe-terkunci (Pmpo_list/Gmpo_list,
    // TIDAK mengirim variabel ini sama sekali) — default TRUE supaya perilaku
    // lama (tanpa dropdown filter Type, edit_route 1 string) persis tidak berubah.
    $type_locked   = $type_locked   ?? true;
    $edit_route_map = $edit_route_map ?? [];
    $can_edit_map   = $can_edit_map   ?? [];

    // "Developer Mode" atur-kolom (ERP Revision Notes, user 2026-09-03) —
    // pola PERSIS sama dgn IRF/PRF/ERF List & PR Monitoring, lihat
    // [[developer_mode_feature]]. page_key SAMA rata utk Pmpo_list/
    // Gmpo_list/Po_monitoring krn ketiganya reuse view file ini persis
    // (struktur tabel identik) — preferensi kolom jadi konsisten di
    // manapun view ini dibuka. "No" (nomor baris), "Type" (digerbang
    // $type_locked, konsep beda dari preferensi tampilan biasa), & "Aksi"
    // SENGAJA tidak dimasukkan ke set yang bisa di-toggle.
    $devmode_page_key = 'po_monitoring';
    $col_defs = [
        'po_number'      => lang_line('pdb_th_po_number'),
        'pr_number'      => lang_line('pdb_th_pr_number'),
        'po_date'        => lang_line('pdb_th_po_date'),
        'supplier'       => lang_line('pdb_th_supplier'),
        'requester'      => lang_line('pdb_th_requester'),
        'status'         => lang_line('pdb_th_status'),
        'receipt'        => lang_line('pdb_th_receipt'),
        'qty_po'         => lang_line('pdb_th_qty_po'),
        'qty_received'   => lang_line('pdb_th_qty_received'),
        'total_value'    => lang_line('pdb_th_total_value', 'Total'),
        'first_grn'      => lang_line('pdb_th_first_grn'),
        'days'           => lang_line('pdb_th_days'),
    ];
    $col = [];
    foreach ($col_defs as $ck => $clabel) {
        $col[$ck] = ui_column_visible($devmode_page_key, $ck, $clabel, true);
    }
    // "No" + "Aksi" selalu tampil (+2), "Type" tergantung $type_locked (+0/1).
    $colspan_dynamic = count(array_filter($col)) + 2 + ($type_locked ? 0 : 1);
    // Tombol & panel "Atur Kolom" HANYA muncul kalau user memilih mode
    // 'Atur Kolom' di banner Developer Mode (user 2026-09-06) — supaya
    // tidak bentrok dengan mode edit teks.
    $devmode_active = devmode_is('column');
?>
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-1">
                <i class="fas fa-clipboard-list text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            </h3>
            <p class="text-muted small mb-0"><?= sprintf(lang_line('pdb_subtitle_list'), htmlspecialchars($po_type_label)); ?>
                (<?= $po_label_translated; ?>).
            </p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-table mr-1"></i> <?= lang_line('pdb_total'); ?> <?= number_format(count($pos)); ?> PO
                <?php if ($devmode_active): ?>
                <?php
                    // Tombol "Atur Kolom" (ERP Revision Notes, user 2026-09-03) SENGAJA
                    // di LUAR tabel (bukan di dalam <th>) — dropdown-menu jadi keturunan
                    // `.table-responsive` (overflow-x:auto) ke-crop kalau ditaruh di sana,
                    // kelihatan aneh terutama pas tabel pendek (data kosong). Bug
                    // ditemukan user via screenshot.
                ?>
                <div class="dropdown d-inline-block ml-2">
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="devmodeColBtn" data-toggle="dropdown" title="<?= htmlspecialchars(lang_line('devmode_col_manage_title', 'Atur kolom')); ?>">
                        <i class="fas fa-columns mr-1"></i> <?= lang_line('devmode_col_manage_btn', 'Atur Kolom'); ?>
                    </button>
                    <div class="dropdown-menu p-2" style="min-width:220px;" id="devmodeColMenu">
                        <div class="small font-weight-bold text-muted mb-2"><?= lang_line('devmode_col_manage_heading', 'Tampilkan kolom'); ?></div>
                        <?php foreach ($col_defs as $ck => $clabel): ?>
                        <div class="custom-control custom-checkbox mb-1 text-left">
                            <input type="checkbox" class="custom-control-input devmode-col-toggle" id="devcol_<?= $ck ?>"
                                   data-page="<?= $devmode_page_key ?>" data-column="<?= $ck ?>" <?= $col[$ck] ? 'checked' : '' ?>>
                            <label class="custom-control-label small" for="devcol_<?= $ck ?>"><?= htmlspecialchars($clabel) ?></label>
                        </div>
                        <?php endforeach; ?>
                        <div class="d-flex justify-content-end mt-2 pt-2 border-top" id="devmodeColActions" style="display:none !important;">
                            <button type="button" class="btn btn-sm btn-outline-secondary mr-1" id="devmodeColCancel"><?= lang_line('btn_cancel', 'Batalkan'); ?></button>
                            <button type="button" class="btn btn-sm btn-primary" id="devmodeColSave"><?= lang_line('devmode_col_save', 'Simpan Perubahan'); ?></button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </h6>
            <form method="get" class="form-inline flex-wrap mt-2 mt-md-0">
                <input type="text" name="search" class="form-control form-control-sm mr-2"
                       placeholder="<?= htmlspecialchars(lang_line('pdb_search_placeholder')); ?>"
                       value="<?= htmlspecialchars($filters['search'] ?? ''); ?>" style="min-width:180px;">
                <select name="status" class="form-control form-control-sm mr-2">
                    <option value=""><?= lang_line('pdb_opt_all_status'); ?></option>
                    <?php foreach (array_keys($status_meta) as $st): ?>
                        <option value="<?= $st; ?>" <?= ($filters['status'] ?? '') == $st ? 'selected' : ''; ?>><?= $status_label_map[$st] ?? $st; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="receipt_status" class="form-control form-control-sm mr-2">
                    <option value=""><?= lang_line('pdb_opt_all_receipt'); ?></option>
                    <option value="Not Received" <?= ($filters['receipt_status'] ?? '') == 'Not Received' ? 'selected' : ''; ?>><?= lang_line('pdb_receipt_not_received'); ?></option>
                    <option value="Partial"      <?= ($filters['receipt_status'] ?? '') == 'Partial' ? 'selected' : ''; ?>><?= lang_line('pdb_receipt_partial'); ?></option>
                    <option value="Complete"     <?= ($filters['receipt_status'] ?? '') == 'Complete' ? 'selected' : ''; ?>><?= lang_line('pdb_receipt_complete'); ?></option>
                </select>
                <select name="supplier_id" class="form-control form-control-sm mr-2">
                    <option value=""><?= lang_line('pdb_opt_all_supplier'); ?></option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s->id; ?>" <?= ($filters['supplier_id'] ?? '') == $s->id ? 'selected' : ''; ?>><?= htmlspecialchars($s->nama_supplier); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!$type_locked): ?>
                <select name="type" class="form-control form-control-sm mr-2">
                    <option value=""><?= lang_line('pdb_opt_all_type', 'Semua Tipe'); ?></option>
                    <?php foreach ($type_labels as $tk => $tl): ?>
                        <option value="<?= $tk; ?>" <?= ($filters['type'] ?? '') === $tk ? 'selected' : ''; ?>><?= htmlspecialchars($tl); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary btn-sm mr-1"><i class="fas fa-search fa-sm"></i></button>
                <a href="<?= base_url($base_url); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-redo fa-sm"></i></a>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('pdb_th_no'); ?></th>
                            <th class="devmode-col devmode-col-po_number" <?= $col['po_number'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['po_number']) ?></th>
                            <?php if (!$type_locked): ?><th><?= lang_line('pdb_th_type', 'Tipe'); ?></th><?php endif; ?>
                            <th class="devmode-col devmode-col-pr_number" <?= $col['pr_number'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['pr_number']) ?></th>
                            <th class="devmode-col devmode-col-po_date" <?= $col['po_date'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['po_date']) ?></th>
                            <th class="devmode-col devmode-col-supplier" <?= $col['supplier'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['supplier']) ?></th>
                            <th class="devmode-col devmode-col-requester" <?= $col['requester'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['requester']) ?></th>
                            <th class="devmode-col devmode-col-status" <?= $col['status'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['status']) ?></th>
                            <th class="devmode-col devmode-col-receipt" <?= $col['receipt'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['receipt']) ?></th>
                            <th class="devmode-col devmode-col-qty_po text-right" <?= $col['qty_po'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['qty_po']) ?></th>
                            <th class="devmode-col devmode-col-qty_received text-right" <?= $col['qty_received'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['qty_received']) ?></th>
                            <th class="devmode-col devmode-col-total_value text-right" <?= $col['total_value'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['total_value']) ?></th>
                            <th class="devmode-col devmode-col-first_grn" <?= $col['first_grn'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['first_grn']) ?></th>
                            <th class="devmode-col devmode-col-days text-right" <?= $col['days'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['days']) ?></th>
                            <th width="150"><?= lang_line('pdb_th_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pos)): $i = 1; foreach ($pos as $p):
                            $status_cls  = $status_meta[$p->status] ?? 'secondary';
                            $receipt_cls = ['Not Received'=>'warning','Partial'=>'info','Complete'=>'success'][$p->receipt_status] ?? 'secondary';
                            $receipt_label_map = [
                                'Not Received' => lang_line('pdb_receipt_not_received'),
                                'Partial'      => lang_line('pdb_receipt_partial'),
                                'Complete'     => lang_line('pdb_receipt_complete'),
                            ];
                        ?>
                        <?php
                            // Mode gabungan (Po_monitoring): edit_route & can_edit di-resolve
                            // PER BARIS sesuai tipe-nya sendiri (edit_route_map/can_edit_map).
                            // Mode 1-tipe-terkunci (Pmpo_list/Gmpo_list): tetap pakai
                            // $edit_route/$can_edit tunggal spt sebelumnya (map-nya kosong).
                            $row_edit_route = $edit_route_map[$p->type] ?? $edit_route;
                            $row_can_edit   = array_key_exists($p->type, $can_edit_map) ? $can_edit_map[$p->type] : $can_edit;
                        ?>
                        <tr class="text-center">
                            <td><?= $i++; ?></td>
                            <td class="devmode-col devmode-col-po_number font-weight-bold text-primary" <?= $col['po_number'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($p->po_number); ?></td>
                            <?php if (!$type_locked): ?>
                            <td><span class="badge badge-light border"><?= htmlspecialchars($type_labels[$p->type] ?? $p->type); ?></span></td>
                            <?php endif; ?>
                            <td class="devmode-col devmode-col-pr_number" <?= $col['pr_number'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($p->pr_number ?: '-'); ?></td>
                            <td class="devmode-col devmode-col-po_date" <?= $col['po_date'] ? '' : 'style="display:none"' ?>><?= $p->po_date ? date('d M Y', strtotime($p->po_date)) : '-'; ?></td>
                            <td class="devmode-col devmode-col-supplier text-left" <?= $col['supplier'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($p->nama_supplier ?: '-'); ?></td>
                            <td class="devmode-col devmode-col-requester" <?= $col['requester'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($p->requester_name ?: '-'); ?></td>
                            <td class="devmode-col devmode-col-status" <?= $col['status'] ? '' : 'style="display:none"' ?>><span class="badge badge-<?= $status_cls; ?>"><?= htmlspecialchars($status_label_map[$p->status] ?? $p->status); ?></span></td>
                            <td class="devmode-col devmode-col-receipt" <?= $col['receipt'] ? '' : 'style="display:none"' ?>><span class="badge badge-<?= $receipt_cls; ?>"><?= htmlspecialchars($receipt_label_map[$p->receipt_status] ?? $p->receipt_status); ?></span></td>
                            <td class="devmode-col devmode-col-qty_po text-right" <?= $col['qty_po'] ? '' : 'style="display:none"' ?>><?= number_format((float)$p->total_po_qty, 2); ?></td>
                            <td class="devmode-col devmode-col-qty_received text-right" <?= $col['qty_received'] ? '' : 'style="display:none"' ?>><?= number_format((float)$p->total_qty_received, 2); ?></td>
                            <td class="devmode-col devmode-col-total_value text-right small" <?= $col['total_value'] ? '' : 'style="display:none"' ?>>
                                <?php
                                    $row_total = (float)($p->total_value ?? 0);
                                    if (!empty($p->has_ppn)) { $row_total *= (1 + ((float)($p->ppn_rate ?? 10) / 100)); }
                                ?>
                                Rp <?= number_format($row_total, 0, ',', '.'); ?>
                            </td>
                            <td class="devmode-col devmode-col-first_grn small" <?= $col['first_grn'] ? '' : 'style="display:none"' ?>><?= $p->first_grn_date ? date('d M Y', strtotime($p->first_grn_date)) : '-'; ?></td>
                            <td class="devmode-col devmode-col-days text-right small text-muted" <?= $col['days'] ? '' : 'style="display:none"' ?>><?= number_format((int)$p->days_to_warehouse_in); ?>d</td>
                            <td>
                                <a href="<?= base_url($detail_route . '/' . $p->id); ?>" class="btn btn-sm btn-primary" title="<?= lang_line('pdb_btn_detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php
                                    // Tombol Edit menuju halaman PO-nya. Dua bentuk:
                                    //  - Draft  : langsung edit seperti biasa (butuh izin edit modul PO).
                                    //  - Submitted/Confirmed + Admin : ikut alur buka-kunci, jadi
                                    //    mendarat di detail dengan mode edit Admin sudah aktif.
                                    // Status lain (Completed/Cancelled/Rejected) tidak dapat tombol.
                                    $edit_draft = !empty($row_edit_route) && !empty($row_can_edit) && $p->status === 'Draft';
                                    $edit_admin = !empty($row_edit_route) && can_unlock_po_admin_edit($p);
                                ?>
                                <?php if ($edit_draft): ?>
                                    <a href="<?= base_url($row_edit_route . '/' . $p->id); ?>" class="btn btn-sm btn-warning" title="<?= lang_line('pdb_btn_edit', 'Edit PO'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                <?php elseif ($edit_admin): ?>
                                    <a href="<?= base_url($row_edit_route . '/' . $p->id); ?>?admin_edit=1"
                                       class="btn btn-sm btn-outline-warning btn-confirm no-loader"
                                       title="<?= lang_line('pdb_btn_edit_admin', 'Edit PO (Admin)'); ?>"
                                       data-message="<?= htmlspecialchars(sprintf(lang_line('pdb_confirm_edit_admin', 'Buka kunci edit PO %s?'), $p->po_number)); ?>"
                                       data-text="PO sudah <?= htmlspecialchars($p->status); ?>. Membuka kunci memungkinkan Anda mengubahnya sebagai Admin. Approval TIDAK direset."
                                       data-icon="warning" data-confirm-text="Ya, Buka Kunci">
                                        <i class="fas fa-user-shield"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($can_delete) && $p->status === 'Draft' && !empty($delete_route)): ?>
                                    <a href="<?= base_url($delete_route . '/' . $p->id); ?>"
                                       class="btn btn-sm btn-danger"
                                       title="<?= lang_line('pdb_btn_delete_draft'); ?>"
                                       onclick="return confirm('<?= htmlspecialchars(addslashes(sprintf(lang_line('pdb_confirm_delete'), $p->po_number))); ?>');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="<?= $colspan_dynamic; ?>" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                <?= htmlspecialchars(sprintf(lang_line('pdb_no_data'), $po_type_label)); ?>
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
