<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0"><i class="fas fa-plane-departure text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <?php if (check_access('mir', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addHeaderModal">
                <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('mir_create_new', 'Buat MIR Baru'); ?>
            </button>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('mir_number', 'No. MIR'); ?></th>
                            <th><?= lang_line('mir_request_date', 'Tanggal'); ?></th>
                            <th><?= lang_line('mir_supplier', 'Supplier'); ?></th>
                            <th><?= lang_line('mir_requester', 'Pemohon'); ?></th>
                            <th><?= lang_line('mir_status', 'Status'); ?></th>
                            <th width="200"><?= lang_line('mir_actions', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($mirs)): foreach($mirs as $mir): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($mir->mir_number); ?></td>
                            <td><?= date('d M Y', strtotime($mir->request_date)); ?></td>
                            <td class="text-left"><?= htmlspecialchars($mir->nama_supplier ?? '-'); ?></td>
                            <td class="text-left"><?= htmlspecialchars($mir->requester_name ?? 'N/A'); ?></td>
                            <td><span class="badge badge-info"><?= $mir->status; ?></span></td>
                            <td>
                                <a href="<?= base_url('mir/detail/'.$mir->id); ?>" class="btn btn-sm btn-info btn-load">
                                    <i class="fas fa-eye"></i> <?= lang_line('mir_detail_title', 'Detail'); ?>
                                </a>
                                <?php if ($mir->status == 'Draft' && check_access('mir', 'edit')): ?>
                                <button class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editHeaderModal<?= $mir->id; ?>">
                                    <i class="fas fa-edit"></i> <?= lang_line('mir_edit', 'Edit'); ?>
                                </button>
                                <?php endif; ?>
                                <?php if ($mir->status == 'Draft' && check_access('mir', 'delete')): ?>
                                <a href="<?= base_url('mir/delete_header/'.$mir->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1">
                                    <i class="fas fa-trash"></i> <?= lang_line('mir_delete', 'Hapus'); ?>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-3"><?= lang_line('mir_no_data', 'Belum ada data MIR'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if(!empty($mirs)): foreach($mirs as $mir): ?>
<div class="modal fade" id="editHeaderModal<?= $mir->id; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('mir/update_header/'.$mir->id); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i><?= lang_line('mir_edit_header', 'Edit Header MIR'); ?></h5>
                    <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <?php $header_detail = $this->Mir_model->get_header_by_id($mir->id); ?>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('mir_request_date', 'Tanggal Permintaan'); ?></label>
                            <input type="date" name="request_date" class="form-control" value="<?= $header_detail->request_date; ?>" required>
                        </div>
                         <div class="col-md-6 form-group">
                            <label><?= lang_line('mir_supplier', 'Supplier'); ?> (Impor)</label>
                            <select name="supplier_id" class="form-control" required>
                                <option value=""><?= lang_line('mir_supplier_select', '-- Pilih Supplier --'); ?></option>
                                <?php foreach($suppliers as $s): ?>
                                    <option value="<?= $s->id; ?>" <?= $header_detail->supplier_id == $s->id ? 'selected' : ''; ?>><?= htmlspecialchars($s->nama_supplier); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('mir_payment_type', 'Tipe Pembayaran'); ?></label>
                            <select name="payment_type_code" class="form-control" required>
                                <?php foreach($payment_types as $pt): ?>
                                    <option value="<?= $pt->code; ?>" <?= $header_detail->payment_type_code == $pt->code ? 'selected' : ''; ?>><?= htmlspecialchars($pt->description); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="col-md-6 form-group">
                            <label><?= lang_line('mir_currency', 'Currency'); ?></label>
                            <select name="currency_code" class="form-control">
                                <?php foreach($currencies as $curr): ?>
                                    <option value="<?= $curr->code; ?>" <?= $header_detail->currency_code == $curr->code ? 'selected' : ''; ?>><?= htmlspecialchars($curr->code); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('mir_remarks', 'Keterangan'); ?></label>
                        <textarea name="remarks" class="form-control" rows="3"><?= htmlspecialchars($header_detail->remarks); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning btn-load"><i class="fas fa-save mr-1"></i> <?= lang_line('mir_save', 'Simpan'); ?> Perubahan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('mir_cancel', 'Batal'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endforeach; endif; ?>


<div class="modal fade" id="addHeaderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('mir/add_header'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i><?= lang_line('mir_create_new', 'Buat Header MIR Baru'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('mir_request_date', 'Tanggal Permintaan'); ?></label>
                            <input type="date" name="request_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                         <div class="col-md-6 form-group">
                            <label><?= lang_line('mir_supplier', 'Supplier'); ?> (Impor)</label>
                            <select name="supplier_id" class="form-control" required>
                                <option value=""><?= lang_line('mir_supplier_select', '-- Pilih Supplier --'); ?></option>
                                <?php foreach($suppliers as $s): ?>
                                    <option value="<?= $s->id; ?>"><?= htmlspecialchars($s->nama_supplier); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('mir_payment_type', 'Tipe Pembayaran'); ?></label>
                            <select name="payment_type_code" class="form-control" required>
                                <?php foreach($payment_types as $pt): ?>
                                    <option value="<?= $pt->code; ?>"><?= htmlspecialchars($pt->description); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="col-md-6 form-group">
                            <label><?= lang_line('mir_currency', 'Currency'); ?></label>
                            <select name="currency_code" class="form-control">
                                <?php foreach($currencies as $curr): ?>
                                    <option value="<?= $curr->code; ?>"><?= htmlspecialchars($curr->code); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('mir_remarks', 'Keterangan'); ?></label>
                        <textarea name="remarks" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-arrow-right mr-1"></i> <?= lang_line('mir_save', 'Simpan'); ?> & Lanjut</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('mir_cancel', 'Batal'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>