<div class="container-fluid">
    <h3 class="h4 mb-4 text-gray-800">
        <i class="fas fa-file-signature text-primary mr-2"></i> Pilih Sales Contract
    </h3>

    <form method="get" action="<?= site_url('fg_delivery/create_items'); ?>">
        <div class="form-group">
            <label for="sc_select">Sales Contract</label>
            <select id="sc_select" name="source_id" class="form-control" style="width:100%" required></select>
        </div>
        <input type="hidden" name="source_type" value="sc">
        <button type="submit" class="btn btn-primary">Lanjut <i class="fas fa-arrow-right ml-2"></i></button>
    </form>
</div>

<script>
$(function(){
    $('#sc_select').select2({
        placeholder: 'Cari nomor SC atau customer...',
        ajax: {
            url: '<?= site_url('fg_delivery/ajax_search_sc_documents'); ?>',
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => data
        },
        minimumInputLength: 1
    });
});
</script>
