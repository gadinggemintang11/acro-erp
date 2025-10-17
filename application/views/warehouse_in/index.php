<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0"><i class="fas fa-truck-loading text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <?php if (check_access('warehouse_in', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addHeaderModal">
                <i class="fas fa-plus-circle mr-1"></i> Buat GRN Baru
            </button>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>No. GRN</th>
                            <th>Tanggal</th>
                            <th>Dasar</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th width="200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($grns)): foreach($grns as $grn): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($grn->grn_number); ?></td>
                            <td><?= date('d M Y', strtotime($grn->grn_date)); ?></td>
                            <td><?= htmlspecialchars($grn->base_on); ?></td>
                            <td class="text-left"><?= htmlspecialchars($grn->nama_supplier ?? '-'); ?></td>
                            <td><span class="badge badge-<?= $grn->status == 'Completed' ? 'success' : 'info'; ?>"><?= $grn->status; ?></span></td>
                            <td>
                                <a href="<?= base_url('warehouse_in/detail/'.$grn->id); ?>" class="btn btn-sm btn-info btn-load">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                                <?php if ($grn->status == 'Draft' && check_access('warehouse_in', 'edit')): ?>
                                <button class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editHeaderModal<?= $grn->id; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php endif; ?>
                                <?php if ($grn->status == 'Draft' && check_access('warehouse_in', 'delete')): ?>
                                <a href="<?= base_url('warehouse_in/delete_header/'.$grn->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" data-message="Yakin ingin menghapus GRN ini? Semua item yang sudah diterima akan ikut terhapus!">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Belum ada data Penerimaan Barang</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addHeaderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('warehouse_in/add_header'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Buat Header Penerimaan Barang</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Tanggal Penerimaan</label>
                            <input type="date" name="grn_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                         <div class="col-md-6 form-group">
                            <label>Nomor Surat Jalan Supplier</label>
                            <input type="text" name="delivery_note_number" class="form-control">
                        </div>
                    </div>
                     <div class="row">
                         <div class="col-md-6 form-group">
                            <label>Dasar Penerimaan</label>
                            <select name="base_on" class="form-control" required>
                                <option value="PO">Purchase Order (PO)</option>
                                <option value="MIR">Material Import Request (MIR)</option>
                            </select>
                        </div>
                         <div class="col-md-6 form-group">
                            <label>Penerimaan Gudang Untuk</label>
                            <select name="warehouse_type" class="form-control" required>
                                <option value="">-- Pilih Tipe --</option>
                                <?php foreach($warehouse_types as $code => $desc): ?>
                                    <option value="<?= $code; ?>"><?= htmlspecialchars($desc); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Tipe BC (Optional)</label>
                            <select name="bc_type_code" class="form-control">
                                <option value="">-- Tanpa BC --</option>
                                <?php foreach($bc_types as $bc): ?>
                                    <option value="<?= $bc->code; ?>"><?= htmlspecialchars($bc->description); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="col-md-4 form-group">
                            <label>Tanggal BC (Optional)</label>
                            <input type="date" name="bc_date" class="form-control">
                        </div>
                         <div class="col-md-4 form-group">
                            <label>Nomor BC (Optional)</label>
                            <input type="text" name="bc_number" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-arrow-right mr-1"></i> Simpan & Lanjut</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>


<?php if(!empty($grns)): foreach($grns as $grn): ?>
<div class="modal fade" id="editHeaderModal<?= $grn->id; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('warehouse_in/update_header/'.$grn->id); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Header GRN</h5>
                    <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <?php
                        // Ambil detail header lagi jika diperlukan (atau lewatkan dari controller jika lebih efisien)
                        $header_detail = $this->Warehouse_in_model->get_header_by_id($grn->id);
                        if(!$header_detail) continue;
                    ?>
                     <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Nomor GRN</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($header_detail->grn_number); ?>" disabled>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Tanggal Penerimaan</label>
                            <input type="date" name="grn_date" class="form-control" value="<?= $header_detail->grn_date; ?>" required>
                        </div>
                    </div>
                     <div class="row">
                         <div class="col-md-6 form-group">
                            <label>Dasar Penerimaan</label>
                            <input type="text" class="form-control" value="<?= $header_detail->base_on; ?>" disabled>
                        </div>
                         <div class="col-md-6 form-group">
                            <label>Penerimaan Gudang Untuk</label>
                            <input type="text" class="form-control" value="<?= ucfirst($header_detail->warehouse_type); ?>" disabled>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Nomor Surat Jalan Supplier</label>
                            <input type="text" name="delivery_note_number" class="form-control" value="<?= htmlspecialchars($header_detail->delivery_note_number); ?>">
                        </div>
                        <div class="col-md-6 form-group">
                           <label>Supplier</label>
                           <input type="text" class="form-control" value="<?= htmlspecialchars($header_detail->nama_supplier ?? '-'); ?>" disabled>
                       </div>
                   </div>
                     <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Tipe BC (Optional)</label>
                            <select name="bc_type_code" class="form-control">
                                <option value="">-- Tanpa BC --</option>
                                <?php foreach($bc_types as $bc): ?>
                                    <option value="<?= $bc->code; ?>" <?= $header_detail->bc_type_code == $bc->code ? 'selected' : ''; ?>><?= htmlspecialchars($bc->description); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="col-md-4 form-group">
                            <label>Tanggal BC (Optional)</label>
                            <input type="date" name="bc_date" class="form-control" value="<?= $header_detail->bc_date; ?>">
                        </div>
                         <div class="col-md-4 form-group">
                            <label>Nomor BC (Optional)</label>
                            <input type="text" name="bc_number" class="form-control" value="<?= htmlspecialchars($header_detail->bc_number); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="remarks" class="form-control" rows="2"><?= htmlspecialchars($header_detail->remarks); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning btn-load"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endforeach; endif; ?>