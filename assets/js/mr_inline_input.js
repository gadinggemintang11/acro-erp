/* ============================================================
   Material Request - Inline Input (Spreadsheet-like)
   ============================================================ */

$(document).ready(function() {
    
    const mrHeaderId = $('#mr_header_id').val();
    const isDraft = $('#is_draft').val() === '1';
    let selectedWoId = null;
    let selectedWoText = '';
    
    if (!isDraft) return; // Only for Draft status
    
    // ============ SELECT2 INITIALIZATION ============
    
    // Main WO Selector (Header)
    $('#selected_wo_main').select2({
        placeholder: '-- Cari No. Work Order --',
        minimumInputLength: 2,
        ajax: {
            url: BASE_URL + 'material_request/search_open_wos',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; }
        }
    });
    
    // Referensi WO selector (left panel)
    $('#wo_selector').select2({
        placeholder: '-- Cari No. Work Order --',
        minimumInputLength: 2,
        ajax: {
            url: BASE_URL + 'material_request/search_open_wos',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; }
        }
    });
    
    // Component selector (Input row)
    $('#input_component').select2({
        placeholder: '-- Ketik untuk mencari material/component --',
        minimumInputLength: 2,
        ajax: {
            url: BASE_URL + 'material_request/search_components',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; }
        }
    });
    
    // ============ WO SELECTION & AUTO-FILL ============
    
    // When main WO selector changes
    $('#selected_wo_main').on('change', function() {
        selectedWoId = $(this).val();
        selectedWoText = $(this).select2('data')[0]?.text || '';
        
        $('#btn_apply_wo').prop('disabled', !selectedWoId);
        
        if (selectedWoId) {
            // Auto-apply to input row
            applyWoToInputRow();
            
            // Also sync with referensi WO
            if (!$('#wo_selector').val()) {
                const newOption = new Option(selectedWoText, selectedWoId, true, true);
                $('#wo_selector').append(newOption).trigger('change');
            }
        }
    });
    
    // Manual apply button
    $('#btn_apply_wo').click(function() {
        if (selectedWoId) {
            applyWoToInputRow();
            $('#input_component').select2('open'); // Focus to component
        }
    });
    
    function applyWoToInputRow() {
        $('#input_wo_id').val(selectedWoId);
        $('#input_wo_display').val(selectedWoText);
        validateInputRow();
    }
    
    // ============ REFERENSI WO (Persistent) ============
    
    $('#wo_selector').on('change', function() {
        const woId = $(this).val();
        const woText = $(this).select2('data')[0]?.text || '';
        
        $('#wo_reference_div').html('<p class="text-center text-muted"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat referensi...</p>');
        
        if (woId) {
            // Sync with main selector if empty
            if (!selectedWoId) {
                selectedWoId = woId;
                selectedWoText = woText;
                const newOption = new Option(woText, woId, true, true);
                $('#selected_wo_main').append(newOption).trigger('change');
            }
            
            $.get(BASE_URL + 'material_request/get_wo_references_json', { wo_id: woId }, function(response) {
                if(response.success) {
                    $('#wo_reference_div').html(response.html);
                } else {
                    $('#wo_reference_div').html('<p class="text-center text-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Gagal memuat referensi.</p>');
                }
            }, 'json').fail(function(){
                 $('#wo_reference_div').html('<p class="text-center text-danger">Error koneksi.</p>');
            });
        } else {
             $('#wo_reference_div').html('<p class="text-muted text-center"><i class="fas fa-info-circle mr-2"></i>Pilih Work Order untuk menampilkan referensi.</p>');
        }
    });
    
    // ============ INPUT ROW VALIDATION ============
    
    function validateInputRow() {
        const woId = $('#input_wo_id').val();
        const componentId = $('#input_component').val();
        const qty = parseFloat($('#input_qty').val());
        
        const isValid = woId && componentId && qty > 0;
        $('#btn_add_item').prop('disabled', !isValid);
        
        return isValid;
    }
    
    // Component change - get unit & stock
    $('#input_component').on('change', function() {
        const componentData = $(this).val();
        if (componentData) {
            const data = $(this).select2('data')[0] || {};
            $('#input_unit').text(data.unit || '...');
            if (typeof data.stock !== 'undefined' && data.stock !== null) {
                const stockNum = parseFloat(data.stock);
                const stockTxt = stockNum.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const $stock = $('#input_stock');
                $stock.text(stockTxt).removeClass('text-muted text-danger text-success');
                const qty = parseFloat($('#input_qty').val()) || 0;
                $stock.addClass(stockNum < qty && qty > 0 ? 'text-danger' : 'text-success');
            } else {
                $('#input_stock').text('-').removeClass('text-danger text-success').addClass('text-muted');
            }
        } else {
            $('#input_unit').text('-');
            $('#input_stock').text('-').removeClass('text-danger text-success').addClass('text-muted');
        }
        validateInputRow();
    });

    $('#input_qty').on('input', function() {
        const data = $('#input_component').select2('data')[0];
        if (data && typeof data.stock !== 'undefined') {
            const qty = parseFloat($(this).val()) || 0;
            const stockNum = parseFloat(data.stock);
            const $stock = $('#input_stock');
            $stock.removeClass('text-danger text-success text-muted');
            $stock.addClass(stockNum < qty && qty > 0 ? 'text-danger' : 'text-success');
        }
    });
    
    $('#input_qty').on('input', validateInputRow);
    
    // ============ ADD ITEM (INLINE) ============
    
    $('#btn_add_item').click(function() {
        addItem();
    });
    
    // Enter key to add item
    $('#input_row input, #input_row select').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (!$('#btn_add_item').prop('disabled')) {
                addItem();
            }
        }
    });
    
    function addItem() {
        if (!validateInputRow()) {
            Swal.fire({
                icon: 'warning',
                title: 'Form Belum Lengkap',
                text: 'Pastikan WO, Item, dan Qty sudah diisi',
                toast: true,
                position: 'top-end',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }
        
        const componentData = $('#input_component').val().split('|');
        const componentText = $('#input_component').select2('data')[0]?.text || '';
        
        const data = {
            wo_header_id: $('#input_wo_id').val(),
            component_id: componentData[0],
            component_type: componentData[1],
            requested_qty: parseFloat($('#input_qty').val()),
            notes: $('#input_notes').val()
        };
        
        // Disable button and show loading
        const btn = $('#btn_add_item');
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: BASE_URL + 'material_request/add_item_ajax/' + mrHeaderId,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html(originalHtml);
                
                if (response.success) {
                    // Add row to table
                    addRowToTable(response.item);
                    
                    // Clear input row (except WO)
                    $('#input_component').val(null).trigger('change');
                    $('#input_qty').val('');
                    $('#input_notes').val('');
                    $('#input_unit').text('-');
                    
                    // Focus back to component for next input
                    setTimeout(function() {
                        $('#input_component').select2('open');
                    }, 100);
                    
                    // Toast notification
                    Swal.fire({
                        icon: 'success',
                        title: 'Item ditambahkan!',
                        text: componentText,
                        toast: true,
                        position: 'top-end',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: response.message || 'Gagal menambahkan item'
                    });
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false).html(originalHtml);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Terjadi kesalahan koneksi',
                    footer: '<small>' + error + '</small>'
                });
            }
        });
    }
    
    function addRowToTable(item) {
        // Remove empty row if exists
        $('#empty_row').remove();

        const stockNum = parseFloat(item.stock || 0);
        const reqNum = parseFloat(item.requested_qty || 0);
        const stockClass = stockNum < reqNum ? 'text-danger font-weight-bold' : 'text-success';
        const stockTxt = stockNum.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const row = `
            <tr data-id="${item.id}" class="new-item">
                <td><small>${item.wo_number}</small></td>
                <td><small>${item.item_code}</small></td>
                <td><small>${item.item_name}</small></td>
                <td class="text-right">${parseFloat(item.requested_qty).toFixed(2)}</td>
                <td><small>${item.unit_measure || '-'}</small></td>
                <td class="text-right"><small class="${stockClass}">${stockTxt}</small></td>
                <td><small>${item.notes || ''}</small></td>
                <td class="text-center">
                    <button class="btn btn-danger btn-xs btn-delete-item" data-id="${item.id}" data-name="${item.item_name}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        // Insert before input row
        $('#input_row').before(row);
        
        // Highlight animation
        setTimeout(function() {
            $('.new-item').removeClass('new-item');
        }, 2000);
        
        // Update counter
        updateItemCount();
    }
    
    // ============ DELETE ITEM ============
    
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
                                updateItemCount();
                                
                                // Show empty row if no items
                                if ($('.existing-item').length === 0) {
                                    $('#input_row').before(`
                                        <tr id="empty_row">
                                            <td colspan="8" class="text-center text-muted py-3">
                                                <i class="fas fa-inbox fa-2x mb-2 d-block text-secondary"></i>
                                                <strong>Belum ada item.</strong><br>
                                                <small>Pilih WO di atas, lalu isi baris biru untuk menambah item</small>
                                            </td>
                                        </tr>
                                    `);
                                }
                            });
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Terhapus!',
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
    
    // ============ HELPERS ============
    
    function updateItemCount() {
        const count = $('.existing-item').length + $('.new-item').length;
        $('#item_count').text(count);
    }
    
});
