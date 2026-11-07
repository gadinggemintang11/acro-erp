/* ============================================================
   Material Request - Inline Input (STAGED, form "Buat MRF Baru")
   ============================================================
   Sama logic pencarian WO/komponen dgn mr_inline_input.js (dipakai
   material_request/detail.php utk MR yang headernya SUDAH ada di DB),
   TAPI di halaman "Buat MRF Baru" (material_request/add.php) header
   BELUM ada sama sekali -- semua item yang ditambah (baik lewat baris
   quick-add ataupun modal "Load dari WO") cuma ditahan di array
   `stagedItems` di browser, dirender ke tabel scr client-side, BARU
   dikirim SEKALIGUS (header+semua item jadi 1 request) begitu user
   klik "Simpan MRF" -> Material_request::store() (baru).
   ERP Revision Notes, permintaan user 2026-09-04 ("header dan form
   item nya jadikan 1").
   ============================================================ */
$(document).ready(function() {

    let selectedWoId = null;
    let selectedWoText = '';
    let stagedItems = [];
    let nextKey = 1;

    // ============ SELECT2 INIT ============

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

    // select2 utk #modal_wo_selector DIHAPUS — WO sekarang dipilih dari daftar
    // di dalam modal (langkah 1), bukan diketik. Elemennya jadi input hidden
    // yang hanya menyimpan id WO terpilih.

    // ============ WO SELECTION & AUTO-FILL (baris quick-add) ============

    $('#selected_wo_main').on('change', function() {
        selectedWoId = $(this).val();
        selectedWoText = $(this).select2('data')[0]?.text || '';

        $('#btn_apply_wo').prop('disabled', !selectedWoId);

        if (selectedWoId) {
            applyWoToInputRow();
            if (!$('#wo_selector').val()) {
                const newOption = new Option(selectedWoText, selectedWoId, true, true);
                $('#wo_selector').append(newOption).trigger('change');
            }
        }
    });

    $('#btn_apply_wo').click(function() {
        if (selectedWoId) {
            applyWoToInputRow();
            $('#input_component').select2('open');
        }
    });

    function applyWoToInputRow() {
        $('#input_wo_id').val(selectedWoId);
        $('#input_wo_display').val(selectedWoText);
        validateInputRow();
    }

    // ============ REFERENSI WO (panel kiri, murni informasi) ============

    $('#wo_selector').on('change', function() {
        const woId = $(this).val();
        const woText = $(this).select2('data')[0]?.text || '';

        $('#wo_reference_div').html('<p class="text-center text-muted"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat referensi...</p>');

        if (woId) {
            if (!selectedWoId) {
                selectedWoId = woId;
                selectedWoText = woText;
                const newOption = new Option(woText, woId, true, true);
                $('#selected_wo_main').append(newOption).trigger('change');
            }

            $.get(BASE_URL + 'material_request/get_wo_references_json', { wo_id: woId }, function(response) {
                if (response.success) {
                    $('#wo_reference_div').html(response.html);
                } else {
                    $('#wo_reference_div').html('<p class="text-center text-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Gagal memuat referensi.</p>');
                }
            }, 'json').fail(function() {
                $('#wo_reference_div').html('<p class="text-center text-danger">Error koneksi.</p>');
            });
        } else {
            $('#wo_reference_div').html('<p class="text-muted text-center"><i class="fas fa-info-circle mr-2"></i>Pilih Work Order untuk menampilkan referensi.</p>');
        }
    });

    // ============ VALIDASI BARIS QUICK-ADD ============

    function validateInputRow() {
        const woId = $('#input_wo_id').val();
        const componentId = $('#input_component').val();
        const qty = parseFloat($('#input_qty').val());

        const isValid = woId && componentId && qty > 0;
        $('#btn_add_item').prop('disabled', !isValid);
        return isValid;
    }

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
        validateInputRow();
    });

    // ============ TAMBAH ITEM (staged, BUKAN AJAX) ============

    $('#btn_add_item').click(function() { addItem(); });

    $('#input_row input, #input_row select').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (!$('#btn_add_item').prop('disabled')) addItem();
        }
    });

    function addItem() {
        if (!validateInputRow()) {
            Swal.fire({
                icon: 'warning', title: 'Form Belum Lengkap', text: 'Pastikan WO, Item, dan Qty sudah diisi',
                toast: true, position: 'top-end', timer: 2000, showConfirmButton: false
            });
            return;
        }

        const componentData = $('#input_component').val().split('|');
        const selData = $('#input_component').select2('data')[0] || {};

        stagedItems.push({
            key: nextKey++,
            wo_header_id: $('#input_wo_id').val(),
            wo_number: $('#input_wo_display').val(),
            component_id: componentData[0],
            component_type: componentData[1],
            item_code: selData.code || '',
            item_name: selData.name || '',
            unit_measure: selData.unit || '',
            stock: selData.stock,
            requested_qty: parseFloat($('#input_qty').val()),
            notes: $('#input_notes').val()
        });
        renderItemsTable();

        $('#input_component').val(null).trigger('change');
        $('#input_qty').val('');
        $('#input_notes').val('');
        $('#input_unit').text('-');

        setTimeout(function() { $('#input_component').select2('open'); }, 100);

        Swal.fire({
            icon: 'success', title: 'Item ditambahkan!', text: selData.name || '',
            toast: true, position: 'top-end', timer: 1200, showConfirmButton: false
        });
    }

    // ============ LOAD DARI WO (bulk, staged) ============

    // ---- Langkah 1: daftar WO di dalam modal ----
    function mrShowWoPick() {
        $('#mr_wo_pick_step').show();
        $('#mr_wo_chosen').hide();
        $('#modal_materials_list').hide();
        $('#modal_no_materials').hide();
        $('#modal_wo_selector').val('');
        $('#modalLoadWOMaterials').removeData('wo-id').removeData('wo-number');
    }

    function mrLoadWoList() {
        var $body = $('#mr_wo_body');
        $body.html('<tr><td colspan="7" class="text-center text-muted py-4">Memuat…</td></tr>');

        $.getJSON(BASE_URL + 'material_request/ajax_wo_list', { search: $('#mr_wo_search').val() || '' })
            .done(function (res) {
                if (!res || !res.success) {
                    $body.html('<tr><td colspan="7" class="text-center text-danger py-4">' +
                        escapeAttr((res && res.message) || 'Gagal memuat daftar Work Order.') + '</td></tr>');
                    return;
                }
                if (!res.rows.length) {
                    $body.html('<tr><td colspan="7" class="text-center text-muted py-4">' +
                        'Tidak ada Work Order berstatus In Progress yang cocok.</td></tr>');
                    return;
                }

                var html = '';
                res.rows.forEach(function (r) {
                    // WO tanpa rencana material tidak ada gunanya ditarik ke MRF —
                    // ditandai, tapi tetap bisa dipilih.
                    var noMat = r.material_count === 0;
                    html += '<tr' + (noMat ? ' class="table-warning"' : '') + '>'
                         +  '<td><strong>' + escapeAttr(r.wo_number) + '</strong></td>'
                         +  '<td>' + (escapeAttr(r.sc_number) || '<span class="text-muted">-</span>') + '</td>'
                         +  '<td>' + escapeAttr(r.fg_name || '-') +
                                ' <small class="text-muted">(' + escapeAttr(r.fg_code || '') + ')</small></td>'
                         +  '<td class="text-right">' + parseFloat(r.planned_qty || 0).toFixed(2) + '</td>'
                         +  '<td class="text-center">' + escapeAttr(r.planned_start_date || '-') + '</td>'
                         +  '<td class="text-center' + (noMat ? ' text-danger font-weight-bold' : '') + '"'
                         +      (noMat ? ' title="WO ini belum punya rencana material"' : '') + '>'
                         +      r.material_count + '</td>'
                         +  '<td class="text-center">'
                         +      '<button type="button" class="btn btn-primary btn-xs mr-pick-wo"'
                         +          ' data-id="' + r.id + '"'
                         +          ' data-wo="' + escapeAttr(r.wo_number) + '"'
                         +          ' data-sub="' + escapeAttr((r.fg_name || '') + ' · ' + (r.sc_number || '')) + '">'
                         +          '<i class="fas fa-check mr-1"></i>Pilih</button>'
                         +  '</td>'
                         +  '</tr>';
                });
                $body.html(html);
            })
            .fail(function () {
                $body.html('<tr><td colspan="7" class="text-center text-danger py-4">Gagal memuat daftar Work Order.</td></tr>');
            });
    }

    $('#mr_wo_refresh').on('click', mrLoadWoList);
    var mrWoTimer = null;
    $('#mr_wo_search').on('input', function () {
        clearTimeout(mrWoTimer);
        mrWoTimer = setTimeout(mrLoadWoList, 350);
    });

    // ---- Langkah 2: material rencana WO terpilih ----
    $('#mr_wo_body').on('click', '.mr-pick-wo', function () {
        var d = $(this).data();
        $('#modal_wo_selector').val(d.id);
        $('#mr_wo_chosen_text').text(d.wo);
        $('#mr_wo_chosen_sub').text(d.sub ? ' — ' + d.sub : '');
        $('#mr_wo_pick_step').hide();
        $('#mr_wo_chosen').show();
        loadWOMaterials(d.id);
    });

    $('#mr_wo_change').on('click', function () {
        mrShowWoPick();
        mrLoadWoList();
    });

    $('#chk-select-all').on('change', function() {
        $('#modal_materials_tbody input[type="checkbox"]').prop('checked', $(this).is(':checked'));
    });

    $('#btn-load-wo-materials').on('click', function() {
        $('#modal_materials_tbody').html('');
        $('#chk-select-all').prop('checked', false);
        mrShowWoPick();
        $('#modalLoadWOMaterials').modal('show');
        mrLoadWoList();
    });

    $('#btn-confirm-load-materials').on('click', function() { confirmLoadMaterials(); });

    function loadWOMaterials(wo_id) {
        $.ajax({
            url: BASE_URL + 'material_request/ajax_get_wo_materials?wo_id=' + wo_id,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var html = '';
                    response.materials.forEach(function(mat) {
                        html += '<tr>';
                        html += '<td class="text-center"><input type="checkbox" class="material-checkbox"'
                            + ' data-id="' + mat.id + '" data-type="' + mat.type + '"'
                            + ' data-code="' + escapeAttr(mat.code) + '" data-name="' + escapeAttr(mat.name) + '"'
                            + ' data-unit="' + escapeAttr(mat.unit) + '" data-stock="' + (mat.stock || 0) + '"></td>';
                        html += '<td><small>' + mat.code + '</small></td>';
                        html += '<td><small>' + mat.name + '</small></td>';
                        html += '<td class="text-right"><small>' + parseFloat(mat.planned_qty).toFixed(2) + '</small></td>';
                        var stockVal = parseFloat(mat.stock || 0);
                        var stockClass = stockVal < parseFloat(mat.planned_qty) ? 'text-danger font-weight-bold' : 'text-success';
                        html += '<td class="text-right"><small class="' + stockClass + '">' + stockVal.toFixed(2) + '</small></td>';
                        html += '<td><input type="number" step="0.01" class="form-control form-control-sm request-qty" value="' + parseFloat(mat.planned_qty).toFixed(2) + '" min="0"></td>';
                        html += '<td><small>' + mat.unit + '</small></td>';
                        html += '</tr>';
                    });

                    $('#modal_materials_tbody').html(html);
                    $('#modal_materials_list').show();
                    $('#modal_no_materials').hide();
                    $('#chk-select-all').prop('checked', false);
                    $('#modalLoadWOMaterials').data('wo-id', wo_id).data('wo-number', response.wo_number || '');
                } else {
                    $('#modal_materials_list').hide();
                    $('#modal_no_materials').html('<i class="fas fa-exclamation-circle mr-2"></i>' + response.message).show();
                }
            },
            error: function() {
                $('#modal_materials_list').hide();
                $('#modal_no_materials').html('<i class="fas fa-exclamation-circle mr-2"></i>Gagal memuat data.').show();
            }
        });
    }

    function confirmLoadMaterials() {
        const wo_id = $('#modalLoadWOMaterials').data('wo-id');
        const wo_number = $('#modalLoadWOMaterials').data('wo-number') || '';

        if (!wo_id) {
            alert('⚠️ Pilih Work Order terlebih dahulu.');
            return;
        }

        let addedCount = 0;
        $('#modal_materials_tbody input[type="checkbox"]:checked').each(function() {
            const row = $(this).closest('tr');
            const qty = parseFloat(row.find('.request-qty').val()) || 0;
            if (qty <= 0) return;

            stagedItems.push({
                key: nextKey++,
                wo_header_id: wo_id,
                wo_number: wo_number,
                component_id: $(this).data('id'),
                component_type: $(this).data('type'),
                item_code: $(this).data('code'),
                item_name: $(this).data('name'),
                unit_measure: $(this).data('unit'),
                stock: $(this).data('stock'),
                requested_qty: qty,
                notes: ''
            });
            addedCount++;
        });

        if (addedCount === 0) {
            alert('⚠️ Pilih minimal 1 material dengan qty > 0.');
            return;
        }

        renderItemsTable();
        $('#modalLoadWOMaterials').modal('hide');
        Swal.fire({
            icon: 'success', title: addedCount + ' item ditambahkan',
            toast: true, position: 'top-end', timer: 1500, showConfirmButton: false
        });
    }

    // ============ HAPUS ITEM (staged) ============

    $(document).on('click', '.btn-delete-item', function() {
        const key = $(this).data('key');
        const itemName = $(this).data('name');

        Swal.fire({
            title: 'Hapus Item?', text: 'Yakin ingin menghapus: ' + itemName + '?', icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                stagedItems = stagedItems.filter(function(it) { return it.key != key; });
                renderItemsTable();
            }
        });
    });

    // ============ RENDER TABEL ============

    function renderItemsTable() {
        $('#items_table tbody tr.existing-item').remove();
        $('#empty_row').remove();

        if (stagedItems.length === 0) {
            $('#input_row').before(
                '<tr id="empty_row">' +
                '<td colspan="8" class="text-center text-muted py-3" style="font-size: 0.9rem;">' +
                '<i class="fas fa-inbox fa-2x mb-2 d-block text-secondary"></i>' +
                '<strong>Belum ada item.</strong><br>' +
                '<small>Pilih Work Order di atas, lalu isi baris biru untuk menambah item</small>' +
                '</td></tr>'
            );
        } else {
            stagedItems.forEach(function(item) {
                const stockNum = parseFloat(item.stock);
                const reqNum = parseFloat(item.requested_qty || 0);
                const hasStock = !isNaN(stockNum);
                const stockClass = hasStock ? (stockNum < reqNum ? 'text-danger font-weight-bold' : 'text-success') : 'text-muted';
                const stockTxt = hasStock ? stockNum.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '-';

                const row = '<tr class="existing-item new-item">' +
                    '<td><small>' + escapeHtml(item.wo_number) + '</small></td>' +
                    '<td><small>' + escapeHtml(item.item_code) + '</small></td>' +
                    '<td><small>' + escapeHtml(item.item_name) + '</small></td>' +
                    '<td class="text-right">' + reqNum.toFixed(2) + '</td>' +
                    '<td><small>' + escapeHtml(item.unit_measure || '-') + '</small></td>' +
                    '<td class="text-right"><small class="' + stockClass + '">' + stockTxt + '</small></td>' +
                    '<td><small>' + escapeHtml(item.notes || '') + '</small></td>' +
                    '<td class="text-center">' +
                    '<button type="button" class="btn btn-danger btn-xs btn-delete-item" data-key="' + item.key + '" data-name="' + escapeAttr(item.item_name) + '">' +
                    '<i class="fas fa-trash"></i></button></td></tr>';
                $('#input_row').before(row);
            });
            setTimeout(function() { $('.new-item').removeClass('new-item'); }, 2000);
        }

        $('#item_count').text(stagedItems.length);
    }

    function escapeHtml(str) {
        return $('<div>').text(str === null || typeof str === 'undefined' ? '' : str).html();
    }
    function escapeAttr(str) {
        return escapeHtml(str).replace(/"/g, '&quot;');
    }

    // ============ SUBMIT AKHIR: header + semua item sekaligus ============

    $('#mrAddForm').on('submit', function(e) {
        $('#mr_request_date').removeClass('is-invalid');

        if (!$('#mr_request_date').val()) {
            e.preventDefault();
            $('#mr_request_date').addClass('is-invalid');
            Swal.fire({
                icon: 'warning', title: 'Tanggal Wajib Diisi',
                text: 'Tanggal request wajib diisi.',
                toast: true, position: 'top-end', timer: 2500, showConfirmButton: false
            });
            return false;
        }

        if (stagedItems.length === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning', title: 'Belum Ada Item',
                text: 'Tambahkan minimal satu item sebelum menyimpan Material Request.'
            });
            return false;
        }

        const payload = stagedItems.map(function(it) {
            return {
                wo_header_id: it.wo_header_id,
                component_id: it.component_id,
                component_type: it.component_type,
                qty: it.requested_qty,
                notes: it.notes
            };
        });
        $('#items_payload').val(JSON.stringify(payload));
        $('#btnSubmitMr').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
    });

    renderItemsTable();
});
