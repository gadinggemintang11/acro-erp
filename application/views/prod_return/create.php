<div class="container-fluid">
    <h4 class="mb-3"><i class="fas fa-undo-alt text-primary mr-2"></i><?= $title; ?></h4>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?= $this->session->flashdata('error'); ?></div>
    <?php elseif ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?= $this->session->flashdata('success'); ?></div>
    <?php endif; ?>

    <form action="<?= site_url('ProdReturn/store'); ?>" method="post" id="formReturn">
        <div class="card mb-3">
            <div class="card-header bg-warning text-dark font-weight-bold">Header Retur</div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-4">
                        <label>No. Retur</label>
                        <input type="text" name="prod_return_no" class="form-control" value="<?= $return_no; ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>Tanggal Retur</label>
                        <input type="date" name="return_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label>Work Order (WO)</label>
                        <select name="work_order_id" id="work_order_id" class="form-control" required>
                            <option value="">-- Pilih Work Order --</option>
                            <?php foreach($work_orders as $wo): ?>
                                <option value="<?= $wo->id; ?>"><?= $wo->wo_number . ' - ' . $wo->product_name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label>Catatan</label>
                <textarea name="remarks" class="form-control" rows="2" placeholder="Catatan retur"></textarea>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-warning text-dark font-weight-bold d-flex justify-content-between">
                <span>Detail Material Retur</span>
                <button type="button" id="btnAddRow" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Tambah Item</button>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="tableDetail">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:5%">#</th>
                                <th>Material</th>
                                <th style="width:15%">Item Type</th>
                                <th style="width:15%">Qty Return</th>
                                <th style="width:25%">Alasan</th>
                                <th style="width:5%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyDetail"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-right">
            <a href="<?= site_url('ProdReturn'); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Draft</button>
        </div>
    </form>
</div>

<script>
$(document).ready(function(){
    let index = 0;
    let materialList = '';

    // Load material saat pilih WO
    $('#work_order_id').on('change', function(){
        const wo_id = $(this).val();
        if(!wo_id) return;
        $.getJSON("<?= site_url('ProdReturn/get_wip_details_ajax/'); ?>" + wo_id, function(res){
            if(res.success){
                materialList = '<option value="">-- Pilih Material --</option>';
                $.each(res.data, function(i, m){
                    materialList += `<option value="${m.material_id}">${m.material_code} - ${m.material_name}</option>`;
                });
                $('#tbodyDetail').html('');
                addRow(); // auto 1 baris
            }else{
                alert(res.message);
            }
        }).fail(function(){
            alert("Gagal memuat data material dari server.");
        });
    });

    // Tambah baris
    $('#btnAddRow').click(function(){
        if(materialList === ''){
            alert('Pilih WO dulu supaya material bisa dimuat.');
            return;
        }
        addRow();
    });

    function addRow(){
        const html = `
        <tr>
            <td class="text-center">${index + 1}</td>
            <td>
                <select name="details[${index}][material_id]" class="form-control" required>
                    ${materialList}
                </select>
            </td>
            <td>
                <select name="details[${index}][item_type]" class="form-control">
                    <option value="material">Material</option>
                    <option value="semi_finished">Semi-Finished</option>
                    <option value="finished">Finished</option>
                </select>
            </td>
            <td><input type="number" step="0.01" min="0.01" name="details[${index}][qty_return]" class="form-control text-right" required></td>
            <td><input type="text" name="details[${index}][reason]" class="form-control"></td>
            <td class="text-center"><button type="button" class="btn btn-danger btn-sm btnRemove"><i class="fas fa-trash"></i></button></td>
        </tr>`;
        $('#tbodyDetail').append(html);
        index++;
    }

    // Hapus baris
    $(document).on('click', '.btnRemove', function(){
        $(this).closest('tr').remove();
        resetNo();
    });

    function resetNo(){
        $('#tbodyDetail tr').each(function(i){
            $(this).find('td:first').text(i + 1);
        });
        index = $('#tbodyDetail tr').length;
    }

    // Debug saat submit
    $('#formReturn').on('submit', function(e){
        if($('#tbodyDetail tr').length === 0){
            alert('Minimal satu item material harus diisi.');
            e.preventDefault();
        } else {
            console.log("🧩 Debug Data Form:");
            $(this).find(':input[name]').each(function(){
                console.log($(this).attr('name') + ' = ' + $(this).val());
            });
        }
    });
});
</script>
