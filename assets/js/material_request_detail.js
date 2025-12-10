/* ============================================================
   Material Request Detail - Batch Input & Persistent Reference
   ============================================================ */

$(document).ready(function() {
    
    let tempItems = []; // Array untuk menyimpan item sementara
    let currentWoId = null; // Menyimpan WO ID yang dipilih untuk referensi
    let currentWoText = ''; // Menyimpan WO text yang dipilih
    const mrHeaderId = $('#mr_header_id').val();
    const isDraft = $('#is_draft').val() === '1';
    
    // ============ REFERENSI WO (PERSISTEN) ============
    $('#wo_selector').select2({
        placeholder: '-- Cari No. Work Order --',
        minimumInputLength: 2,
        ajax: {
            url: BASE_URL + 'material_request/search_open_wos',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; },
            cache: true
        }
    });

    // Event saat WO di Referensi dipilih - PERSISTEN
    $('#wo_selector').on('change', function() {
        currentWoId = $(this).val();
        currentWoText = $(this).select2('data')[0]?.text || '';
        
        $('#wo_reference_div').html('<p class="text-center text-muted"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat referensi...</p>');
        
        if (currentWoId) {
            $.get(BASE_URL + 'material_request/get_wo_references_json', { wo_id: currentWoId }, function(response) {
                if(response.success) {
                    $('#wo_reference_div').html(response.html);
                    
                    // Auto-select WO di modal jika belum ada yang dipilih
                    if (isDraft && !$('#wo_selector_modal').val()) {
                        var newOption = new Option(currentWoText, currentWoId, true, true);
                        $('#wo_selector_modal').append(newOption).trigger('change');
                    }
                } else {
                    $('#wo_reference_div').html('<p class="text-center text-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Gagal memuat referensi.</p>');
                }
            }, 'json').fail(function(){
                 $('#wo_reference_div').html('<p class="text-center text-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Error koneksi.</p>');
            });
        } else {
             $('#wo_reference_div').html('<p class="text-muted text-center"><i class="fas fa-info-circle mr-2"></i>Pilih Work Order untuk menampilkan referensi.</p>');
        }
    });

    // ============ MODAL TAMBAH ITEM (BATCH MODE) ============
    if (isDraft) {
        
        $('#wo_selector_modal').select2({
            placeholder: '-- Cari No. Work Order --',
            minimumInputLength: 2,
            ajax: {
                url: BASE_URL + 'material_request/search_open_wos',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data.results }; },
                cache: true
            },
            dropdownParent: $('#addItemModal')
        });

        $('#component_search').select2({
            placeholder: '-- Cari Kode / Nama Komponen --',
            minimumInputLength: 2,
            ajax: {
                url: BASE_URL + 'material_request/search_components',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data.results }; },
            },
            dropdownParent: $('#addItemModal')
        });
        
        // Reset modal saat dibuka
        $('#addItemModal').on('show.bs.modal', function() {
            tempItems = [];
            updateTempItemsDisplay();
            $('#add_item_form')[0].reset();
            $('#wo_selector_modal').val(null).trigger('change');
            $('#component_search').val(null).trigger('change');
            
            // Pre-fill WO jika sudah dipilih di referensi
            if (currentWoId && currentWoText) {
                var newOption = new Option(currentWoText, currentWoId, true, true);
                $('#wo_selector_modal').append(newOption).trigger('change');
            }
        });
        
        // Tambah ke list sementara
        $('#btn_add_to_list').click(function() {
            const form = $('#add_item_form')[0];
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const woId = $('#wo_selector_modal').val();
            const woText = $('#wo_selector_modal').select2('data')[0]?.text || '';
            const componentId = $('#component_search').val();
            const componentText = $('#component_search').select2('data')[0]?.text || '';
            const qty = $('#requested_qty').val();
            const notes = $('#notes').val();
            
            if (!woId || !componentId || !qty) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Form Belum Lengkap',
                    text: 'Mohon isi semua field yang diperlukan',
                    toast: true,
                    position: 'top-end',
                    timer: 2000
                });
                return;
            }
            
            // Tambah ke array
            tempItems.push({
                wo_id: woId,
                wo_text: woText,
                component_id: componentId,
                component_text: componentText,
                qty: parseFloat(qty),
                notes: notes
            });
            
            // Update tampilan
            updateTempItemsDisplay();
            
            // Reset form untuk input berikutnya
            $('#component_search').val(null).trigger('change');
            $('#requested_qty').val('');
            $('#notes').val('');
            $('#component_search').select2('open'); // Auto focus ke komponen berikutnya
            
            Swal.fire({
                icon: 'success',
                title: 'Item ditambahkan!',
                text: 'Tambahkan item lain atau klik Simpan Semua',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500
            });
        });
        
        // Update tampilan temp items
        function updateTempItemsDisplay() {
            if (tempItems.length === 0) {
                $('#temp_items_container').slideUp();
                $('#btn_save_all').hide();
            } else {
                $('#temp_items_container').slideDown();
                $('#btn_save_all').show();
                $('#temp_count').text(tempItems.length);
                $('#save_count').text(tempItems.length);
                
                let html = '';
                tempItems.forEach((item, index) => {
                    html += `
                        <tr>
                            <td><small>${item.wo_text.substring(0, 20)}...</small></td>
                            <td><small>${item.component_text.substring(0, 35)}...</small></td>
                            <td class="text-right"><strong>${item.qty.toFixed(2)}</strong></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-danger btn-xs btn-remove-temp" data-index="${index}" title="Hapus">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                $('#temp_items_tbody').html(html);
            }
        }
        
        // Hapus item dari temp list
        $(document).on('click', '.btn-remove-temp', function() {
            const index = $(this).data('index');
            tempItems.splice(index, 1);
            updateTempItemsDisplay();
        });
        
        // Simpan semua items via AJAX
        $('#btn_save_all').click(function() {
            if (tempItems.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak Ada Item',
                    text: 'Belum ada item yang ditambahkan',
                    toast: true,
                    position: 'top-end',
                    timer: 2000
                });
                return;
            }
            
            const btn = $(this);
            const originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
            
            $.ajax({
                url: BASE_URL + 'material_request/add_items_batch/' + mrHeaderId,
                type: 'POST',
                data: JSON.stringify({ items: tempItems }),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    btn.prop('disabled', false).html(originalText);
                    
                    if (response.success) {
                        $('#addItemModal').modal('hide');
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: `${response.count} item berhasil ditambahkan`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // Update tabel items tanpa reload
                        response.items.forEach(function(item) {
                            addItemToTable(item);
                        });
                        
                        // REFERENSI WO TETAP TAMPIL - tidak direset!
                        
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: response.message || 'Gagal menyimpan items'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    btn.prop('disabled', false).html(originalText);
                    console.error('AJAX Error:', {xhr, status, error});
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Terjadi kesalahan koneksi. Silakan coba lagi.',
                        footer: '<small>Error: ' + error + '</small>'
                    });
                }
            });
        });
        
        // Fungsi untuk menambah item ke tabel tanpa reload
        function addItemToTable(item) {
            // Hapus empty row jika ada
            $('#empty_row').remove();
            
            const row = `
                <tr data-id="${item.id}" class="new-item">
                    <td><small>${item.wo_number}</small></td>
                    <td><small>${item.item_code}</small></td>
                    <td><small>${item.item_name}</small></td>
                    <td class="text-right">${parseFloat(item.requested_qty).toFixed(2)}</td>
                    <td><small>${item.unit_measure || '-'}</small></td>
                    <td class="text-center">
                        <button class="btn btn-danger btn-xs btn-delete-item" data-id="${item.id}" data-name="${item.item_name}" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            $('#items_tbody').append(row);
            
            // Highlight new row
            setTimeout(function() {
                $('.new-item').removeClass('new-item');
            }, 2000);
            
            // Update counter
            updateItemCount();
        }
    }
    
    // Update item count
    function updateItemCount() {
        const count = $('#items_tbody tr').not('#empty_row').length;
        $('#item_count').text(count);
    }
    
    // ============ DELETE ITEM (AJAX) ============
    $(document).on('click', '.btn-delete-item', function() {
        const itemId = $(this).data('id');
        const itemName = $(this).data('name');
        const row = $(this).closest('tr');
        
        Swal.fire({
            title: 'Hapus Item?',
            text: `Yakin ingin menghapus: ${itemName}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE_URL + 'material_request/delete_item_ajax',
                    type: 'POST',
                    data: { item_id: itemId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            row.fadeOut(300, function() {
                                $(this).remove();
                                
                                // Update counter
                                updateItemCount();
                                
                                // Tampilkan empty row jika tidak ada item
                                if ($('#items_tbody tr').not('#empty_row').length === 0) {
                                    $('#items_tbody').html(`
                                        <tr id="empty_row">
                                            <td colspan="6" class="text-center text-muted py-3">
                                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                                Belum ada material diminta.
                                            </td>
                                        </tr>
                                    `);
                                }
                            });
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Terhapus!',
                                text: 'Item berhasil dihapus',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response.message || 'Gagal menghapus item'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Terjadi kesalahan koneksi'
                        });
                    }
                });
            }
        });
    });
    
});
