<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-dolly-flatbed text-primary mr-2"></i>
            <?= lang_line('sgr_page_title', 'Supplier General Item Receipt'); ?>
            <?php if (!empty($active_category)): ?>
                <span class="badge badge-<?= $active_category === 'Produksi' ? 'dark' : 'secondary' ?> align-middle"><?= htmlspecialchars($active_category) ?></span>
            <?php endif; ?>
        </h3>
        <?php if (check_access('supplier_general_item_receipt', 'add')
            || check_access('supplier_general_item_receipt_general', 'add')
            || check_access('supplier_general_item_receipt_produksi', 'add')): ?>
        <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addSgrModal">
            <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('sgr_create', 'Buat SGR Baru'); ?>
        </button>
        <?php endif; ?>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?= $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?= $this->session->flashdata('error'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('warning')): ?>
        <div class="alert alert-warning"><?= $this->session->flashdata('warning'); ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('sgr_number', 'No. SGR'); ?></th>
                            <th><?= lang_line('sgr_date', 'Tanggal'); ?></th>
                            <th><?= lang_line('sgr_warehouse_type', 'Tipe Gudang'); ?></th>
                            <th><?= lang_line('sgr_category', 'Kategori'); ?></th>
                            <th><?= lang_line('sgr_supplier', 'Supplier'); ?></th>
                            <th><?= lang_line('sgr_status', 'Status'); ?></th>
                            <th width="220"><?= lang_line('sgr_actions', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($sgrs)): foreach ($sgrs as $sgr): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($sgr->sgr_number); ?></td>
                            <td><?= date('d M Y', strtotime($sgr->sgr_date)); ?></td>
                            <td><?= htmlspecialchars(ucfirst($sgr->warehouse_type)); ?></td>
                            <td>
                                <?php $sc = $sgr->category ?? 'Lainnya'; ?>
                                <span class="badge badge-<?= $sc === 'Produksi' ? 'dark' : ($sc === 'General' ? 'secondary' : 'light text-muted') ?>"><?= htmlspecialchars($sc) ?></span>
                            </td>
                            <td class="text-left"><?= htmlspecialchars($sgr->nama_supplier ?? '-'); ?></td>
                            <td>
                                <?php
                                $st = $sgr->status ?? '';
                                if ($st === 'Completed') {
                                    echo '<span class="badge badge-success">Approved</span>';
                                } elseif ($st === 'Draft') {
                                    echo '<span class="badge badge-secondary">Draft</span>';
                                } else {
                                    echo '<span class="badge badge-light text-muted">-</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="<?= base_url('supplier_general_item_receipt/detail/' . $sgr->id); ?>"
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> <?= lang_line('detail', 'Detail'); ?>
                                </a>
                                <?php if ($sgr->status == 'Draft' && check_access('supplier_general_item_receipt', 'delete')): ?>
                                    <a href="<?= base_url('supplier_general_item_receipt/delete_header/' . $sgr->id); ?>"
                                       class="btn btn-sm btn-danger btn-delete ml-1"
                                       data-message="<?= lang_line('sgr_confirm_delete', 'Yakin ingin menghapus SGR ini?'); ?>"
                                       data-text="<?= lang_line('sgr_confirm_delete_text', 'Seluruh item di dalam SGR ini ikut terhapus dan tidak dapat dikembalikan!'); ?>">
                                        <i class="fas fa-trash"></i> <?= lang_line('delete', 'Hapus'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">
                            <?= lang_line('sgr_no_data', 'Belum ada data SGR'); ?>
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add SGR Header -->
<div class="modal fade" id="addSgrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('supplier_general_item_receipt/add_header'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle mr-2"></i>
                        <?= lang_line('sgr_create_header', 'Buat Header SGR'); ?>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('sgr_date', 'Tanggal Penerimaan'); ?></label>
                            <input type="date" name="sgr_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('sgr_delivery_note', 'Nomor Surat Jalan Supplier'); ?></label>
                            <input type="text" name="delivery_note_number" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label><?= lang_line('sgr_base_on', 'Dasar Penerimaan'); ?></label>
                            <input type="text" class="form-control" value="Purchase Order (PO)" disabled>
                        </div>
                        <div class="col-md-4 form-group">
                            <label><?= lang_line('sgr_warehouse_type', 'Tipe Gudang'); ?></label>
                            <select name="warehouse_type" class="form-control" required>
                                <option value="">-- Pilih Tipe --</option>
                                <?php foreach ($warehouse_types as $code => $desc): ?>
                                    <option value="<?= $code; ?>"><?= htmlspecialchars($desc); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label><?= lang_line('sgr_category', 'Kategori'); ?></label>
                            <?php if (!empty($active_category)): ?>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($active_category) ?>" disabled>
                                <input type="hidden" name="category" value="<?= htmlspecialchars($active_category) ?>">
                            <?php else: ?>
                                <select name="category" class="form-control" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="General">General</option>
                                    <option value="Produksi">Produksi</option>
                                    <option value="Lainnya">Lainnya (bukan dari Permintaan Barang)</option>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label><?= lang_line('sgr_bc_type', 'Tipe BC'); ?> (Optional)</label>
                            <select name="bc_type_code" class="form-control">
                                <option value="">-- Tanpa BC --</option>
                                <?php foreach ($bc_types as $bc): ?>
                                    <option value="<?= $bc->code; ?>"><?= htmlspecialchars($bc->description); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label><?= lang_line('sgr_bc_date', 'Tanggal BC'); ?> (Optional)</label>
                            <input type="date" name="bc_date" class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label><?= lang_line('sgr_bc_number', 'Nomor BC'); ?> (Optional)</label>
                            <input type="text" name="bc_number" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('sgr_remarks', 'Keterangan'); ?></label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-arrow-right mr-1"></i> <?= lang_line('sgr_save', 'Simpan & Lanjut'); ?>
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
