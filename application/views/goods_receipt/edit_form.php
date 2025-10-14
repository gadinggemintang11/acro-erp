<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Edit Dokumen Penerimaan Barang</h6></div>
    <div class="card-body">
        <form action="<?php echo base_url('goods_receipt/update'); ?>" method="post">
            <input type="hidden" name="id" value="<?php echo $receipt['id']; ?>">
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">No. Penerimaan</label>
                <div class="col-sm-9"><input type="text" name="receipt_no" class="form-control" value="<?php echo $receipt['receipt_no']; ?>" readonly></div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Tipe BC</label>
                <div class="col-sm-9">
                    <select name="bc_type" class="form-control" required>
                        <?php foreach($bc_types as $type): ?>
                        <option value="<?php echo $type['code_name']; ?>" <?php echo ($receipt['bc_type'] == $type['code_name']) ? 'selected' : ''; ?>>
                            <?php echo $type['code_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">No. Dok. Pabean (PIB)</label>
                <div class="col-sm-9"><input type="text" name="pib_no" class="form-control" value="<?php echo $receipt['pib_no']; ?>"></div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Tanggal Dokumen</label>
                <div class="col-sm-9"><input type="date" name="document_date" class="form-control" value="<?php echo $receipt['document_date']; ?>" required></div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Tanggal Penerimaan</label>
                <div class="col-sm-9"><input type="date" name="receipt_date" class="form-control" value="<?php echo $receipt['receipt_date']; ?>" required></div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Supplier</label>
                <div class="col-sm-9">
                    <select name="supplier_id" class="form-control" required>
                        <?php foreach($suppliers as $supplier): ?>
                        <option value="<?php echo $supplier['id']; ?>" <?php echo ($receipt['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                            <?php echo $supplier['supplier_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
             <div class="form-group row">
                <label class="col-sm-3 col-form-label">Dasar Penerimaan</label>
                <div class="col-sm-9">
                    <select name="receipt_basis" class="form-control" required>
                        <option value="Material Master" <?php echo ($receipt['receipt_basis'] == 'Material Master') ? 'selected' : ''; ?>>Dari Master Material</option>
                        <option value="Import Request" disabled>Dari Import Request</option>
                    </select>
                </div>
            </div>
            <hr>
            <button type="submit" class="btn btn-primary">Update Dokumen</button>
            <a href="<?php echo base_url('goods_receipt'); ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>