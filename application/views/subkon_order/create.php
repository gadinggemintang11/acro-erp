<div class="container-fluid">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="h5 text-gray-800 mb-0">
            <i class="fas fa-plus-circle text-primary me-2"></i><?= $title; ?>
        </h3>
        <a href="<?= base_url('subkon_order'); ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?= $this->load->view('templates/partials/alerts', [], true); ?>

    <form action="<?= base_url('subkon_order/save_full_order'); ?>" method="post" id="formSubkon">
        <!-- HEADER KONTRAK -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-light py-2">
                <h6 class="m-0 fw-bold text-dark"><i class="fas fa-file-contract"></i> Header Kontrak</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplier_id" class="form-control form-control-sm select2" required></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Order</label>
                        <input type="date" name="order_date" value="<?= date('Y-m-d'); ?>" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Pajak (%)</label>
                        <input type="number" step="0.01" name="pajak_persen" id="pajak_persen" class="form-control form-control-sm text-end" value="11">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Catatan kontrak...">
                    </div>
                </div>
            </div>
        </div>

        <!-- ITEM HASIL -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark"><i class="fas fa-cubes"></i> Item Hasil (Output)</h6>
                <button type="button" id="addOutputRow" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Tambah Item
                </button>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle" id="tableOutput">
                        <thead class="text-center text-white" style="background-color:#5C5F72;">
                            <tr>
                                <th>No</th>
                                <th>Item Hasil</th>
                                <th>Qty</th>
                                <th>Jasa per pcs (Rp)</th>
                                <th>Total Jasa</th>
                                <th>BOM Material</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <th colspan="4" class="text-end">Subtotal</th>
                                <th class="text-end" id="subtotalCell">0.00</th>
                                <th colspan="2"></th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-end">Pajak (<span id="pajakLabel">11</span>%)</th>
                                <th class="text-end text-danger" id="pajakCell">0.00</th>
                                <th colspan="2"></th>
                            </tr>
                            <tr class="table-primary">
                                <th colspan="4" class="text-end fw-bold">Grand Total</th>
                                <th class="text-end fw-bold text-primary" id="grandCell">0.00</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-success shadow-sm px-4">
                <i class="fas fa-save"></i> Simpan Kontrak
            </button>
        </div>
    </form>
</div>

<!-- Template Output -->
<template id="tplOutputRow">
<tr>
    <td class="text-center align-middle row-no"></td>
    <td>
        <select name="output[IDX][item_id_type]" class="form-control form-control-sm select2 hasil-item" style="width:100%;" required></select>
    </td>
    <td><input type="number" step="0.01" name="output[IDX][qty]" class="form-control form-control-sm text-end qty" required></td>
    <td><input type="number" step="0.01" name="output[IDX][jasa_per_pcs]" class="form-control form-control-sm text-end jasa" required></td>
    <td class="text-end total-cell fw-bold">0.00</td>
    <td>
        <button type="button" class="btn btn-outline-info btn-sm btn-add-bom"><i class="fas fa-plus"></i> BOM</button>
        <div class="bom-container mt-2"></div>
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-danger btn-sm btn-delete-row"><i class="fas fa-trash"></i></button>
    </td>
</tr>
</template>

<!-- Template BOM -->
<template id="tplBomRow">
<div class="d-flex align-items-center mb-1">
    <select name="bom[PARENT_IDX][IDX][item_id_type]" class="form-control form-control-sm select2 bom-item" style="width: 55%;" required></select>
    <input type="number" step="0.001" name="bom[PARENT_IDX][IDX][qty_per_pcs]" placeholder="Qty / pcs" class="form-control form-control-sm text-end mx-2" style="width: 25%;" required>
    <button type="button" class="btn btn-outline-danger btn-sm btn-del-bom"><i class="fas fa-times"></i></button>
</div>
</template>

<script>
$(function(){
    let outputIndex = 0;

    // Tambah Item
    $('#addOutputRow').click(function(){
        let tpl = $('#tplOutputRow').html().replaceAll('IDX', outputIndex);
        $('#tableOutput tbody').append(tpl);
        updateNumbers();
        initSelect2($('#tableOutput tbody tr:last'));
        outputIndex++;
    });

    // Hapus Row
    $(document).on('click', '.btn-delete-row', function(){
        $(this).closest('tr').remove();
        updateNumbers();
        recalcTotal();
    });

    // Input Qty/Jasa berubah
    $(document).on('input', '.qty, .jasa', function(){
        let tr = $(this).closest('tr');
        let qty = parseFloat(tr.find('.qty').val()) || 0;
        let jasa = parseFloat(tr.find('.jasa').val()) || 0;
        let total = qty * jasa;
        tr.find('.total-cell').text(total.toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2}));
        recalcTotal();
    });

    // Tambah BOM
    $(document).on('click', '.btn-add-bom', function(){
        let parentTr = $(this).closest('tr');
        let parentIndex = parentTr.index();
        let bomContainer = parentTr.find('.bom-container');
        let bomTpl = $('#tplBomRow').html().replaceAll('PARENT_IDX', parentIndex).replaceAll('IDX', Date.now());
        bomContainer.append(bomTpl);
        initSelect2(bomContainer);
    });

    // Hapus BOM
    $(document).on('click', '.btn-del-bom', function(){
        $(this).closest('.d-flex').remove();
    });

    // Hitung Total
    function recalcTotal(){
        let subtotal = 0;
        $('#tableOutput tbody tr').each(function(){
            let qty = parseFloat($(this).find('.qty').val()) || 0;
            let jasa = parseFloat($(this).find('.jasa').val()) || 0;
            subtotal += qty * jasa;
        });
        let pajakRate = parseFloat($('#pajak_persen').val()) || 0;
        let pajak = subtotal * pajakRate / 100;
        let grand = subtotal + pajak;

        $('#subtotalCell').text(subtotal.toLocaleString('id-ID', {minimumFractionDigits:2}));
        $('#pajakCell').text(pajak.toLocaleString('id-ID', {minimumFractionDigits:2}));
        $('#grandCell').text(grand.toLocaleString('id-ID', {minimumFractionDigits:2}));
        $('#pajakLabel').text(pajakRate);
    }

    $('#pajak_persen').on('input', recalcTotal);

    // Helpers
    function updateNumbers(){
        $('#tableOutput tbody tr').each(function(i){
            $(this).find('.row-no').text(i+1);
        });
    }

    function initSelect2(ctx){
        ctx.find('.hasil-item').select2({
            ajax:{
                url:'<?= base_url('subkon_order/ajax_search_hasil_item'); ?>',
                dataType:'json', delay:300,
                data:params=>({q:params.term}),
                processResults:data=>data
            },width:'100%',placeholder:'Pilih Item Hasil'
        });
        ctx.find('.bom-item').select2({
            ajax:{
                url:'<?= base_url('subkon_order/ajax_search_bom_item'); ?>',
                dataType:'json', delay:300,
                data:params=>({q:params.term}),
                processResults:data=>data
            },width:'100%',placeholder:'Pilih Material BOM'
        });
        $('#supplier_id').select2({
            ajax:{
                url:'<?= base_url('subkon_order/ajax_search_suppliers'); ?>',
                dataType:'json', delay:300,
                data:params=>({q:params.term}),
                processResults:data=>data
            },width:'100%',placeholder:'Cari Supplier'
        });
    }

    initSelect2($(document));
});
</script>

<style>
.table th, .table td { font-size:13px; vertical-align:middle; }
tfoot th { font-weight:600; font-size:13px; }
.table-primary { background-color:#eaf3ff!important; }
.select2-container .select2-selection--single { height:31px!important; }
.select2-selection__rendered { font-size:13px!important; }
.btn { font-size:13px; border-radius:6px; }
</style>
