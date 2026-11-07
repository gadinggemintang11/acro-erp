<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-warehouse text-warning mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <a href="<?= base_url('Scrap_In'); ?>" class="btn btn-secondary btn-sm btn-load">
            <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('scrap_in_back_to_list', 'Kembali ke List'); ?>
        </a>
    </div>

    <form action="<?= base_url('Scrap_In/store_warehouse'); ?>" method="post" id="warehouse-form">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-warning"><?= lang_line('scrap_in_header_doc_draft', 'Header Dokumen (Draft)'); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label><?= lang_line('scrap_in_doc_no_draft', 'No. Dokumen (Draft)'); ?></label>
                        <input type="text" name="doc_no" class="form-control" value="<?= htmlspecialchars($doc_no); ?>" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label><?= lang_line('scrap_in_doc_date', 'Tgl Dokumen'); ?></label>
                        <input type="date" name="doc_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label><?= lang_line('scrap_in_source', 'Sumber'); ?></label>
                        <input type="text" class="form-control" value="<?= lang_line('scrap_in_wh_source', 'Kerusakan Warehouse'); ?>" readonly style="background-color: #fff3cd;">
                    </div>
                </div>
                <div class="form-group">
                    <label><?= lang_line('scrap_in_notes_optional', 'Catatan (Opsional)'); ?></label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="<?= lang_line('scrap_in_notes_placeholder', 'Catatan tambahan...'); ?>"></textarea>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-warning"><?= lang_line('scrap_in_wh_damaged_items', 'Item Warehouse yang Rusak'); ?></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="warehouse-table">
                        <thead class="thead-light">
                            <tr class="text-center">
                                <th width="30"><?= lang_line('scrap_in_wh_table_no', '#'); ?></th>
                                <th><?= lang_line('scrap_in_wh_table_item_type', 'Tipe Item'); ?></th>
                                <th><?= lang_line('scrap_in_wh_table_warehouse_item', 'Item Gudang'); ?></th>
                                <th width="50"><?= lang_line('scrap_in_table_unit', 'Satuan'); ?></th>
                                <th width="80"><?= lang_line('scrap_in_wh_table_stock', 'Stock'); ?></th>
                                <th><?= lang_line('scrap_in_qty_damaged', 'Qty Rusak'); ?></th>
                                <th><?= lang_line('scrap_in_wh_table_reason', 'Alasan Kerusakan'); ?></th>
                                <th>→</th>
                                <th><?= lang_line('scrap_in_wh_table_scrap_target', 'Scrap Target'); ?></th>
                                <th width="50"><?= lang_line('scrap_in_table_unit', 'Satuan'); ?></th>
                                <th><?= lang_line('scrap_in_table_qty_scrap', 'Qty Scrap'); ?></th>
                                <th width="50"><?= lang_line('scrap_in_table_action', 'Aksi'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="warehouse-items-body">
                            <tr class="warehouse-row">
                                <td class="text-center row-number">1</td>
                                <td>
                                    <select class="form-control form-control-sm item-type-select" required>
                                        <option value=""><?= lang_line('scrap_in_select_type', '-- Pilih Tipe --'); ?></option>
                                        <option value="material">Material</option>
                                        <option value="semifinished">Semi Finished</option>
                                        <option value="finished_goods">Finished Goods</option>
                                        <option value="inventory">Inventory</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control form-control-sm source-item-select" data-type="material" required>
                                        <option value=""><?= lang_line('scrap_in_select_item', '-- Pilih Item --'); ?></option>
                                    </select>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="item-satuan-display small text-muted">-</span>
                                </td>
                                <td class="text-right align-middle">
                                    <span class="item-stock-display badge badge-info">-</span>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm qty-damaged" step="0.01" min="0" required>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm damage-reason" placeholder="<?= lang_line('scrap_in_damage_placeholder', 'Rusak, Expired, etc'); ?>">
                                </td>
                                <td class="text-center align-middle">→</td>
                                <td>
                                    <select class="form-control form-control-sm scrap-select" required>
                                        <option value=""><?= lang_line('scrap_in_select_scrap', '-- Pilih Scrap --'); ?></option>
                                        <?php foreach ($master_scrap as $scrap): ?>
                                        <option value="<?= $scrap['id']; ?>" data-satuan="<?= htmlspecialchars($scrap['satuan_desc']); ?>">
                                            <?= htmlspecialchars($scrap['scrap_code'] . ' - ' . $scrap['scrap_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="scrap-satuan-display small text-muted">-</span>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm scrap-qty" step="0.01" min="0" required>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger btn-remove-row">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-primary mt-3" id="add-row">
                    <i class="fas fa-plus mr-1"></i> <?= lang_line('scrap_in_add_row', 'Tambah Baris'); ?>
                </button>
            </div>

            <!-- Preview Scrap Grouping -->
            <div class="card-body border-top">
                <h6 class="text-info mb-3">
                    <i class="fas fa-cube mr-2"></i> <?= lang_line('scrap_in_wh_preview_title', 'Preview Scrap Grouping'); ?>
                    <small class="text-muted ml-2"><?= lang_line('scrap_in_wh_preview_subtitle', '(Multi-material → 1 Scrap akan otomatis digabung)'); ?></small>
                </h6>
                <div id="scrap-grouping-preview" class="row">
                    <div class="col-12">
                        <small class="text-muted"><?= lang_line('scrap_in_wh_preview_hint', 'Pilih scrap target dan qty untuk melihat preview grouping...'); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer text-right">
            <button type="submit" class="btn btn-success btn-load">
                <i class="fas fa-save mr-1"></i> <?= lang_line('scrap_in_create_draft', 'Buat Draft'); ?>
            </button>
        </div>
    </form>
</div>

<script>
// Master data untuk items per tipe (dari controller)
const itemMasterData = <?= json_encode($master_items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
const masterScrapData = <?= json_encode($master_scrap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;

// I18n strings for JS
const SI_I18N = {
    selectType: <?= json_encode(lang_line('scrap_in_select_type', '-- Pilih Tipe --')); ?>,
    selectItem: <?= json_encode(lang_line('scrap_in_select_item', '-- Pilih Item --')); ?>,
    searchItem: <?= json_encode(lang_line('scrap_in_search_item', '-- Cari dan Pilih Item --')); ?>,
    selectScrap: <?= json_encode(lang_line('scrap_in_select_scrap', '-- Pilih Scrap --')); ?>,
    searchScrap: <?= json_encode(lang_line('scrap_in_search_scrap', '-- Cari dan Pilih Scrap --')); ?>,
    itemNotFound: <?= json_encode(lang_line('scrap_in_item_not_found', 'Item tidak ditemukan')); ?>,
    scrapNotFound: <?= json_encode(lang_line('scrap_in_scrap_not_found', 'Scrap tidak ditemukan')); ?>,
    damagePlaceholder: <?= json_encode(lang_line('scrap_in_damage_placeholder', 'Rusak, Expired, etc')); ?>,
    addMinOne: <?= json_encode(lang_line('scrap_in_add_min_one', 'Tambahkan minimal satu item')); ?>,
    fillRequired: <?= json_encode(lang_line('scrap_in_fill_all_required', 'Lengkapi semua field yang required')); ?>,
    merged: <?= json_encode(lang_line('scrap_in_merged_label', 'MERGED')); ?>,
    mergedPlaceholder: <?= json_encode(lang_line('scrap_in_merged_to_row', '⤴ Merged ke row #')); ?>,
    autoMergedTo: <?= json_encode(lang_line('scrap_in_auto_merged_to', 'Auto-merged ke row #')); ?>,
    previewHint: <?= json_encode(lang_line('scrap_in_wh_preview_hint', 'Pilih scrap target dan qty untuk melihat preview grouping...')); ?>,
    fromMaterials: <?= json_encode(lang_line('scrap_in_from_materials', 'Dari')); ?>,
    materialsSuffix: <?= json_encode(lang_line('scrap_in_from_materials_suffix', 'material:')); ?>,
    totalScrap: <?= json_encode(lang_line('scrap_in_total_scrap', 'Total Scrap')); ?>
};

console.log('Item Master Data:', itemMasterData);
console.log('Master Scrap Data:', masterScrapData);

$(document).ready(function() {
    const form = document.getElementById('warehouse-form');
    const addRowBtn = document.getElementById('add-row');
    const itemsBody = document.getElementById('warehouse-items-body');

    function hasSelect2($el) {
        return $el.length && $el.hasClass('select2-hidden-accessible');
    }

    function safeDestroySelect2($el) {
        if (hasSelect2($el)) {
            $el.select2('destroy');
        }
    }

    function buildTypeOptionsHtml() {
        return '<option value="">' + escapeHtml(SI_I18N.selectType) + '</option>' +
               '<option value="material">Material</option>' +
               '<option value="semifinished">Semi Finished</option>' +
               '<option value="finished_goods">Finished Goods</option>' +
               '<option value="inventory">Inventory</option>';
    }

    function buildScrapOptionsHtml() {
        let html = '<option value="">' + escapeHtml(SI_I18N.selectScrap) + '</option>';
        if (Array.isArray(masterScrapData)) {
            masterScrapData.forEach(scrap => {
                const satuan = scrap.satuan_desc || '';
                const label = scrap.scrap_code + ' - ' + scrap.scrap_name;
                html += '<option value="' + scrap.id + '" data-satuan="' + escapeHtml(satuan) + '">' +
                        escapeHtml(label) + '</option>';
            });
        }
        return html;
    }

    function buildItemOptionsHtml(itemType) {
        let html = '<option value="">' + escapeHtml(SI_I18N.selectItem) + '</option>';
        if (itemType && itemMasterData[itemType]) {
            itemMasterData[itemType].forEach(item => {
                const stock = parseFloat(item.stock || 0).toFixed(2);
                const label = item.code + ' - ' + item.name + ' (Stock: ' + stock + ')';
                html += '<option value="' + item.id + '|' + itemType + '">' +
                        escapeHtml(label) + '</option>';
            });
        }
        return html;
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function initializeRowSelect2($row) {
        const $typeSelect = $row.find('.item-type-select');
        const $itemSelect = $row.find('.source-item-select');
        const $scrapSelect = $row.find('.scrap-select');

        safeDestroySelect2($typeSelect);
        safeDestroySelect2($itemSelect);
        safeDestroySelect2($scrapSelect);

        $typeSelect.select2({
            width: '100%',
            placeholder: SI_I18N.selectType,
            allowClear: true
        });

        $itemSelect.select2({
            width: '100%',
            placeholder: SI_I18N.searchItem,
            allowClear: true,
            language: { noResults: function() { return SI_I18N.itemNotFound; } }
        });

        $scrapSelect.select2({
            width: '100%',
            placeholder: SI_I18N.searchScrap,
            allowClear: true,
            language: { noResults: function() { return SI_I18N.scrapNotFound; } }
        });
    }

    function updateItemOptions($row, itemType) {
        const $itemSelect = $row.find('.source-item-select');
        const stockDisplay = $row.find('.item-stock-display')[0];
        const satuanDisplay = $row.find('.item-satuan-display')[0];

        safeDestroySelect2($itemSelect);

        $itemSelect[0].innerHTML = buildItemOptionsHtml(itemType);
        $itemSelect[0].dataset.type = itemType || '';

        if (stockDisplay) {
            stockDisplay.textContent = '-';
            stockDisplay.className = 'item-stock-display badge badge-info';
        }
        if (satuanDisplay) satuanDisplay.textContent = '-';

        $itemSelect.select2({
            width: '100%',
            placeholder: SI_I18N.searchItem,
            allowClear: true,
            language: { noResults: function() { return SI_I18N.itemNotFound; } }
        });
    }

    function updateStockDisplay(selectElement) {
        const row = selectElement.closest('tr');
        const selectedValue = selectElement.value;
        const stockDisplay = row.querySelector('.item-stock-display');
        const satuanDisplay = row.querySelector('.item-satuan-display');

        if (!selectedValue) {
            stockDisplay.textContent = '-';
            satuanDisplay.textContent = '-';
            stockDisplay.className = 'item-stock-display badge badge-info';
            return;
        }

        const parts = selectedValue.split('|');
        const itemId = parts[0];
        const itemType = parts[1];
        const itemData = itemMasterData[itemType];
        const selectedItem = itemData ? itemData.find(item => parseInt(item.id) === parseInt(itemId)) : null;

        if (selectedItem) {
            const stock = parseFloat(selectedItem.stock).toFixed(2);
            stockDisplay.textContent = stock;
            satuanDisplay.textContent = selectedItem.satuan || '-';
            stockDisplay.className = 'item-stock-display badge';
            if (parseFloat(selectedItem.stock) === 0) {
                stockDisplay.classList.add('badge-danger');
            } else if (parseFloat(selectedItem.stock) < 10) {
                stockDisplay.classList.add('badge-warning');
            } else {
                stockDisplay.classList.add('badge-success');
            }
        } else {
            stockDisplay.textContent = '-';
            satuanDisplay.textContent = '-';
            stockDisplay.className = 'item-stock-display badge badge-secondary';
        }
    }

    function updateRowNumbers() {
        $(itemsBody).find('.warehouse-row').each(function(idx) {
            $(this).find('.row-number').text(idx + 1);
        });
    }

    function buildRowHtml() {
        return '<tr class="warehouse-row">' +
               '<td class="text-center row-number">1</td>' +
               '<td><select class="form-control form-control-sm item-type-select" required>' + buildTypeOptionsHtml() + '</select></td>' +
               '<td><select class="form-control form-control-sm source-item-select" data-type="" required><option value="">' + escapeHtml(SI_I18N.selectItem) + '</option></select></td>' +
               '<td class="text-center align-middle"><span class="item-satuan-display small text-muted">-</span></td>' +
               '<td class="text-right align-middle"><span class="item-stock-display badge badge-info">-</span></td>' +
               '<td><input type="number" class="form-control form-control-sm qty-damaged" step="0.01" min="0" required></td>' +
               '<td><input type="text" class="form-control form-control-sm damage-reason" placeholder="' + escapeHtml(SI_I18N.damagePlaceholder) + '"></td>' +
               '<td class="text-center align-middle">→</td>' +
               '<td><select class="form-control form-control-sm scrap-select" required>' + buildScrapOptionsHtml() + '</select></td>' +
               '<td class="text-center align-middle"><span class="scrap-satuan-display small text-muted">-</span></td>' +
               '<td><input type="number" class="form-control form-control-sm scrap-qty" step="0.01" min="0" required></td>' +
               '<td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-remove-row"><i class="fas fa-trash"></i></button></td>' +
               '</tr>';
    }

    function addWarehouseRow() {
        const $newRow = $(buildRowHtml());
        $(itemsBody).append($newRow);
        updateRowNumbers();
        initializeRowSelect2($newRow);
    }

    const $firstRow = $(itemsBody).find('.warehouse-row').first();
    initializeRowSelect2($firstRow);

    if (itemMasterData['material'] && itemMasterData['material'].length > 0) {
        const $firstTypeSelect = $firstRow.find('.item-type-select');
        $firstTypeSelect.val('material').trigger('change.select2');
        updateItemOptions($firstRow, 'material');
    }

    addRowBtn.addEventListener('click', addWarehouseRow);

    $(itemsBody).on('click', '.btn-remove-row', function() {
        const $row = $(this).closest('tr');
        safeDestroySelect2($row.find('.item-type-select'));
        safeDestroySelect2($row.find('.source-item-select'));
        safeDestroySelect2($row.find('.scrap-select'));
        $row.remove();
        updateRowNumbers();
        applyScrapMerging();
        updateScrapGroupingPreview();
    });

    $(itemsBody).on('change', '.item-type-select', function() {
        const $row = $(this).closest('tr');
        const selectedType = this.value;
        updateItemOptions($row, selectedType);
    });

    $(itemsBody).on('change', '.source-item-select', function() {
        updateStockDisplay(this);
    });

    $(itemsBody).on('change', '.scrap-select', function() {
        const $row = $(this).closest('tr');
        const selectedOption = $(this).find('option:selected');
        const satuan = selectedOption.attr('data-satuan') || '-';
        $row.find('.scrap-satuan-display').text(satuan || '-');
        applyScrapMerging();
        updateScrapGroupingPreview();
    });

    $(itemsBody).on('input change', '.scrap-qty', function() {
        updateScrapGroupingPreview();
    });

    $(itemsBody).on('change', '.source-item-select', function() {
        updateScrapGroupingPreview();
    });

    function applyScrapMerging() {
        const rows = itemsBody.querySelectorAll('.warehouse-row');
        const seenScraps = {};

        rows.forEach((row, idx) => {
            const scrapSelect = row.querySelector('.scrap-select');
            const qtyInput = row.querySelector('.scrap-qty');
            const scrapId = scrapSelect.value;

            const existingIndicator = row.querySelector('.merge-indicator');
            if (existingIndicator) existingIndicator.remove();

            if (!scrapId) {
                qtyInput.disabled = false;
                qtyInput.style.backgroundColor = '';
                qtyInput.placeholder = '';
                return;
            }

            if (seenScraps[scrapId] !== undefined) {
                qtyInput.disabled = true;
                qtyInput.value = '';
                qtyInput.style.backgroundColor = '#fff3cd';
                qtyInput.placeholder = SI_I18N.mergedPlaceholder + (seenScraps[scrapId] + 1);

                const indicator = document.createElement('small');
                indicator.className = 'merge-indicator text-warning d-block mt-1';
                indicator.style.fontSize = '0.7rem';
                indicator.innerHTML = '<i class="fas fa-link"></i> ' + escapeHtml(SI_I18N.autoMergedTo) + (seenScraps[scrapId] + 1);
                qtyInput.parentNode.appendChild(indicator);
            } else {
                qtyInput.disabled = false;
                qtyInput.style.backgroundColor = '';
                qtyInput.placeholder = '';
                seenScraps[scrapId] = idx;
            }
        });
    }

    function updateScrapGroupingPreview() {
        const previewDiv = document.getElementById('scrap-grouping-preview');
        const rows = itemsBody.querySelectorAll('.warehouse-row');
        const grouping = {};

        rows.forEach(row => {
            const scrapSelect = row.querySelector('.scrap-select');
            const scrapQty = row.querySelector('.scrap-qty');
            const itemSelect = row.querySelector('.source-item-select');

            if (scrapSelect.value && itemSelect.value) {
                const scrapId = scrapSelect.value;
                const scrapText = scrapSelect.options[scrapSelect.selectedIndex].text;
                const satuan = scrapSelect.options[scrapSelect.selectedIndex].dataset.satuan || '';
                const qty = parseFloat(scrapQty.value) || 0;
                const itemLabel = itemSelect.options[itemSelect.selectedIndex].text;

                if (!grouping[scrapId]) {
                    grouping[scrapId] = {
                        scrapText: scrapText,
                        satuan: satuan,
                        totalQty: 0,
                        materials: []
                    };
                }
                grouping[scrapId].totalQty += qty;
                grouping[scrapId].materials.push({ label: itemLabel, qty: qty });
            }
        });

        if (Object.keys(grouping).length === 0) {
            previewDiv.innerHTML = '<div class="col-12"><small class="text-muted">' + escapeHtml(SI_I18N.previewHint) + '</small></div>';
            return;
        }

        let html = '';
        Object.entries(grouping).forEach(([scrapId, data]) => {
            const isMerged = data.materials.length > 1;
            const cardClass = isMerged ? 'border-warning' : 'border-info';
            const badgeClass = isMerged ? 'badge-warning' : 'badge-info';
            const mergedLabel = isMerged ? '<span class="badge badge-warning ml-2">' + escapeHtml(SI_I18N.merged) + '</span>' : '';

            let materialList = '';
            data.materials.forEach(m => {
                materialList += '<li class="small text-muted">' + escapeHtml(m.label) + ' → ' + m.qty.toFixed(2) + ' ' + data.satuan + '</li>';
            });

            html += '<div class="col-md-6 mb-3">' +
                '<div class="alert alert-light border ' + cardClass + ' mb-0">' +
                '<strong class="text-info">' + escapeHtml(data.scrapText) + '</strong>' + mergedLabel + '<br/>' +
                '<small>' + escapeHtml(SI_I18N.fromMaterials) + ' ' + data.materials.length + ' ' + escapeHtml(SI_I18N.materialsSuffix) + '</small>' +
                '<ul class="mb-2 mt-1" style="padding-left: 1.2rem;">' + materialList + '</ul>' +
                '<span class="badge ' + badgeClass + '">' + escapeHtml(SI_I18N.totalScrap) + ': ' + data.totalQty.toFixed(2) + ' ' + escapeHtml(data.satuan) + '</span>' +
                '</div></div>';
        });
        previewDiv.innerHTML = html;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const rows = itemsBody.querySelectorAll('.warehouse-row');
        if (rows.length === 0) {
            alert(SI_I18N.addMinOne);
            return;
        }

        const items = [];
        let valid = true;
        rows.forEach(row => {
            const typeSelect = row.querySelector('.item-type-select');
            const sourceSelect = row.querySelector('.source-item-select');
            const qtyDamaged = row.querySelector('.qty-damaged');
            const damageReason = row.querySelector('.damage-reason');
            const scrapSelect = row.querySelector('.scrap-select');
            const scrapQty = row.querySelector('.scrap-qty');
            const isMerged = scrapQty.disabled;

            if (!typeSelect.value || !sourceSelect.value || !qtyDamaged.value || !scrapSelect.value) {
                valid = false;
                return;
            }
            if (!isMerged && !scrapQty.value) {
                valid = false;
                return;
            }

            const parts = sourceSelect.value.split('|');
            items.push({
                source_item_id: parseInt(parts[0]),
                source_item_type: parts[1],
                qty_damaged: parseFloat(qtyDamaged.value),
                damage_reason: damageReason.value || null,
                scrap_id: parseInt(scrapSelect.value),
                scrap_qty: isMerged ? 0 : parseFloat(scrapQty.value)
            });
        });

        if (!valid) {
            alert(SI_I18N.fillRequired);
            return;
        }

        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'warehouse_items';
        hiddenInput.value = JSON.stringify(items);
        form.appendChild(hiddenInput);
        if (window.showLoader) window.showLoader(); // loader muncul saat navigasi, bukan saat dialog konfirmasi
                form.submit();
    });
});
</script>
