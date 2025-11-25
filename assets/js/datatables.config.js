// assets/js/datatables.config.js
$(document).ready(function() {
    // Set konfigurasi default DataTables di seluruh sistem
    $.extend(true, $.fn.dataTable.defaults, {
        pageLength: 20, // tampilkan 20 baris per halaman
        lengthMenu: [[10, 20, 50, 100, -1], [10, 20, 50, 100, "Semua"]],
        language: {
            "search": "Cari:",
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "zeroRecords": "Tidak ada data ditemukan",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            "infoEmpty": "Tidak ada data tersedia",
            "infoFiltered": "(difilter dari total _MAX_ data)",
            "paginate": {
                "first": "Awal",
                "last": "Akhir",
                "next": "Berikutnya",
                "previous": "Sebelumnya"
            }
        }
    });
});
