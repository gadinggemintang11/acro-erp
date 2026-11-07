<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Filter Neraca</h6>
                </div>
                <div class="card-body">
                    <form id="filterForm">
                        <div class="form-group">
                            <label for="date">Per Tanggal</label>
                            <input type-="date" class="form-control" id="date" name="date" value="<?= date('Y-m-d') ?>">
                        </div>
                        <button type="button" id="viewButton" class="btn btn-primary">Tampilkan</button>
                        <button type="button" id="pdfButton" class="btn btn-danger">Export PDF</button>
                        <button type="button" id="excelButton" class="btn btn-success">Export Excel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Laporan Neraca PT NEW SPECIALIFE INDUSTRIES</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive" id="neracaTableContainer">
                        <!-- Neraca table will be loaded here via AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
$(document).ready(function(){
    function loadNeraca(date) {
        $.ajax({
            url: '<?= base_url('neraca/get_neraca_data') ?>',
            type: 'POST',
            data: {date: date},
            dataType: 'json',
            success: function(data) {
                let html = '<table class="table table-bordered">';
                html += '<thead><tr><th>Akun</th><th>Nama Akun</th><th>Saldo</th></tr></thead>';
                html += '<tbody>';

                html += '<tr><td colspan="3" class="font-weight-bold">Aktiva</td></tr>';
                let totalAssets = 0;
                $.each(data.assets, function(i, item){
                    html += '<tr>';
                    html += '<td><a href="<?= base_url('neraca/detail/') ?>'+item.id+'/'+date+'">'+item.account+'</a></td>';
                    html += '<td>'+item.name+'</td>';
                    html += '<td class="text-right">'+new Intl.NumberFormat().format(item.balance)+'</td>';
                    html += '</tr>';
                    totalAssets += parseFloat(item.balance);
                });
                html += '<tr><td colspan="2" class="text-right font-weight-bold">Total Aktiva</td><td class="text-right font-weight-bold">'+new Intl.NumberFormat().format(totalAssets)+'</td></tr>';

                html += '<tr><td colspan="3" class="font-weight-bold">Kewajiban</td></tr>';
                let totalLiabilities = 0;
                $.each(data.liabilities, function(i, item){
                    html += '<tr>';
                    html += '<td><a href="<?= base_url('neraca/detail/') ?>'+item.id+'/'+date+'">'+item.account+'</a></td>';
                    html += '<td>'+item.name+'</td>';
                    html += '<td class="text-right">'+new Intl.NumberFormat().format(item.balance)+'</td>';
                    html += '</tr>';
                    totalLiabilities += parseFloat(item.balance);
                });
                html += '<tr><td colspan="2" class="text-right font-weight-bold">Total Kewajiban</td><td class="text-right font-weight-bold">'+new Intl.NumberFormat().format(totalLiabilities)+'</td></tr>';

                html += '<tr><td colspan="3" class="font-weight-bold">Ekuitas</td></tr>';
                let totalEquity = 0;
                $.each(data.equity, function(i, item){
                    html += '<tr>';
                    html += '<td><a href="<?= base_url('neraca/detail/') ?>'+item.id+'/'+date+'">'+item.account+'</a></td>';
                    html += '<td>'+item.name+'</td>';
                    html += '<td class="text-right">'+new Intl.NumberFormat().format(item.balance)+'</td>';
                    html += '</tr>';
                    totalEquity += parseFloat(item.balance);
                });
                html += '<tr><td colspan="2" class="text-right font-weight-bold">Total Ekuitas</td><td class="text-right font-weight-bold">'+new Intl.NumberFormat().format(totalEquity)+'</td></tr>';

                html += '<tr><td colspan="2" class="text-right font-weight-bold">Total Kewajiban dan Ekuitas</td><td class="text-right font-weight-bold">'+new Intl.NumberFormat().format(totalLiabilities + totalEquity)+'</td></tr>';

                html += '</tbody></table>';
                $('#neracaTableContainer').html(html);
            }
        });
    }

    $('#viewButton').on('click', function(){
        let date = $('#date').val();
        loadNeraca(date);
    });

    $('#pdfButton').on('click', function(){
        let date = $('#date').val();
        window.open('<?= base_url('neraca/pdf/') ?>' + date, '_blank');
    });

    $('#excelButton').on('click', function(){
        let date = $('#date').val();
        window.open('<?= base_url('neraca/excel/') ?>' + date, '_blank');
    });

    // Initial load
    loadNeraca($('#date').val());
});
</script>
