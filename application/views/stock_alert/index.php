<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<style>
        body {
            background-color: var(--color-bg-secondary);
            color: var(--color-text-primary);
            padding: 20px;
            transition: background-color 0.3s, color 0.3s;
        }

        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding-left: 30px;
            padding-right: 30px;
        }

        .header-section {
            background: var(--color-bg-primary);
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid var(--color-border);
        }

        .alert-badge {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: bold;
            margin-left: 10px;
        }

        .alert-badge.critical {
            background-color: #dc3545;
            color: white;
        }

        .alert-badge.warning {
            background-color: #ffc107;
            color: black;
        }

        .table-container {
            background: var(--color-bg-primary);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
            border: 1px solid var(--color-border);
        }

        .search-section {
            background: var(--color-bg-primary);
            padding: 15px 20px;
            border-bottom: 1px solid var(--color-border);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 8px 12px;
            border: 1px solid var(--color-border);
            border-radius: 4px;
            background: var(--color-bg-secondary);
            color: var(--color-text-primary);
        }

        .search-input::placeholder {
            color: var(--color-text-secondary);
        }

        .table {
            margin: 0;
            color: var(--color-text-primary);
        }

        .table thead th {
            background-color: var(--color-table-header);
            color: var(--color-table-header-text);
            border: none;
            padding: 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
        }

        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            border-color: var(--color-border);
        }

        .table tbody tr:hover {
            background-color: var(--color-table-hover);
        }

        .item-type-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .item-type-badge.material {
            background-color: #d4edff;
            color: #0066cc;
        }

        .item-type-badge.semifinished {
            background-color: #fff3cd;
            color: #856404;
        }

        .item-type-badge.inventory {
            background-color: #d4edda;
            color: #155724;
        }

        .item-type-badge.machine {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-critical {
            color: #dc3545;
            font-weight: 600;
        }

        .status-warning {
            color: #ff9800;
            font-weight: 600;
        }

        .deficit-value {
            font-weight: 600;
        }

        .no-alerts {
            text-align: center;
            padding: 40px 20px;
            color: var(--color-text-secondary);
        }

        .no-alerts i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .filter-section {
            margin-bottom: 15px;
        }

        .filter-btn {
            padding: 6px 12px;
            border: 1px solid var(--color-border);
            background: var(--color-bg-secondary);
            color: var(--color-text-primary);
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
            font-size: 12px;
            transition: all 0.3s;
        }

        .filter-btn.active {
            background-color: #343a40;
            color: white;
            border-color: #343a40;
        }

        .filter-btn:hover {
            background-color: #495057;
            color: white;
        }

        .kode-link {
            color: #0066cc;
            text-decoration: none;
            font-weight: 500;
        }

        .kode-link:hover {
            text-decoration: underline;
        }

        .last-update {
            color: var(--color-text-secondary);
            font-size: 12px;
            margin-top: 10px;
        }

        .results-info {
            font-size: 13px;
            color: var(--color-text-secondary);
        }

        .pagination-section {
            background: var(--color-bg-primary);
            padding: 15px 20px;
            border-top: 1px solid var(--color-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pagination {
            margin: 0;
        }

        .pagination a, .pagination span {
            color: var(--color-text-primary);
            border-color: var(--color-border);
        }

        .pagination .active span {
            background-color: #343a40;
        }

        .action-cell {
            white-space: nowrap;
        }

        .btn-sm {
            font-size: 12px;
            padding: 4px 8px;
        }

    </style>
    <div class="main-content">
        <div class="header-section">
            <div class="row">
                <div class="col-md-8">
                    <h2>
                        <i class="fa fa-warning" style="color: #dc3545;"></i> <?= lang_line('stock_alert_title'); ?>
                        <span class="alert-badge critical" id="alert-count">0</span>
                    </h2>
                    <p class="text-muted"><?= lang_line('stock_alert_subtitle'); ?></p>
                </div>
                <div class="col-md-4" style="text-align: right; padding-top: 10px;">
                    <a href="<?= base_url('stock_alert/export_excel') ?>" class="btn btn-success btn-sm" title="<?= lang_line('stock_alert_export_excel'); ?>">
                        <i class="fa fa-file-excel-o"></i> <?= lang_line('stock_alert_export_excel'); ?>
                    </a>
                    <a href="<?= base_url('stock_alert/export_pdf') ?>" class="btn btn-danger btn-sm" title="<?= lang_line('stock_alert_export_pdf'); ?>">
                        <i class="fa fa-file-pdf-o"></i> <?= lang_line('stock_alert_export_pdf'); ?>
                    </a>
                    <button class="btn btn-primary btn-sm" onclick="location.reload();" title="<?= lang_line('stock_alert_refresh'); ?>">
                        <i class="fa fa-refresh"></i>
                    </button>
                </div>
            </div>

            <div class="filter-section" style="margin-top: 15px;">
                <strong><?= lang_line('stock_alert_filter_by_type'); ?></strong>
                <button class="filter-btn active" data-filter="all"><?= lang_line('stock_alert_filter_all'); ?></button>
                <button class="filter-btn" data-filter="material"><?= lang_line('stock_alert_filter_material'); ?></button>
                <button class="filter-btn" data-filter="semifinished"><?= lang_line('stock_alert_filter_semifinished'); ?></button>
                <button class="filter-btn" data-filter="inventory"><?= lang_line('stock_alert_filter_inventory'); ?></button>
                <button class="filter-btn" data-filter="machine"><?= lang_line('stock_alert_filter_machine'); ?></button>
            </div>

            <div class="last-update">
                <i class="fa fa-sync-alt"></i> <?= lang_line('stock_alert_last_updated'); ?> <span id="last-update-time"><?= lang_line('stock_alert_just_now'); ?></span>
            </div>
        </div>

        <div class="table-container">
            <?php if (!empty($alerts)): ?>
                <div class="search-section">
                    <input type="text" id="search-input" class="search-input" placeholder="<?= lang_line('stock_alert_search_placeholder'); ?>">
                    <button class="btn btn-sm btn-outline-primary" onclick="filterTable()" title="<?= lang_line('stock_alert_search_button'); ?>">
                        <i class="fa fa-search"></i> <?= lang_line('stock_alert_search_button'); ?>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="resetSearch()" title="<?= lang_line('stock_alert_reset_button'); ?>">
                        <i class="fa fa-times"></i> <?= lang_line('stock_alert_reset_button'); ?>
                    </button>
                    <span class="results-info" id="results-info"></span>
                </div>

                <div style="overflow-x: auto;">
                    <table class="table table-striped" id="alerts-table">
                        <thead>
                            <tr>
                                <th style="width: 10%;"><?= lang_line('stock_alert_type_header'); ?></th>
                                <th style="width: 12%;"><?= lang_line('stock_alert_code_header'); ?></th>
                                <th style="width: 25%;"><?= lang_line('stock_alert_name_header'); ?></th>
                                <th style="width: 8%;"><?= lang_line('stock_alert_unit_header'); ?></th>
                                <th style="width: 10%;"><?= lang_line('stock_alert_current_stock'); ?></th>
                                <th style="width: 10%;"><?= lang_line('stock_alert_minimum_stock'); ?></th>
                                <th style="width: 10%;"><?= lang_line('stock_alert_deficit'); ?></th>
                                <th style="width: 12%;"><?= lang_line('stock_alert_status_header'); ?></th>
                                <th style="width: 12%;"><?= lang_line('stock_alert_table_action'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="alert-table-body">
                            <?php foreach ($alerts as $alert): ?>
                                <tr class="alert-row" data-type="<?= $alert['item_type'] ?>" data-searchtext="<?= strtolower($alert['kode_material'] ?? $alert['kode_barang'] ?? $alert['kode_mesin'] ?? '') . ' ' . strtolower($alert['nama_material'] ?? $alert['nama_barang'] ?? $alert['nama_mesin'] ?? '') . ' ' . strtolower($alert['satuan'] ?? '') ?>">
                                    <td>
                                        <span class="item-type-badge <?= strtolower($alert['item_type']) ?>">
                                            <?php 
                                                $type_display = [
                                                    'material' => 'Material',
                                                    'semifinished' => 'Semifinished',
                                                    'inventory' => 'HRGA',
                                                    'machine' => 'Mesin'
                                                ];
                                                echo $type_display[$alert['item_type']] ?? $alert['item_type'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="kode-link">
                                            <?php 
                                                echo ($alert['item_type'] == 'material') ? $alert['kode_material'] :
                                                     (($alert['item_type'] == 'machine') ? $alert['kode_mesin'] : $alert['kode_barang']);
                                            ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php 
                                            echo ($alert['item_type'] == 'material') ? $alert['nama_material'] :
                                                 (($alert['item_type'] == 'machine') ? $alert['nama_mesin'] : $alert['nama_barang']);
                                        ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= isset($alert['satuan']) ? $alert['satuan'] : 'N/A' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?= number_format($alert['current_stock'], 2, ',', '.') ?></strong>
                                    </td>
                                    <td>
                                        <strong><?= number_format($alert['minimum_stock'], 2, ',', '.') ?></strong>
                                    </td>
                                    <td>
                                        <?php 
                                            $deficit = $alert['deficit'] ?? ($alert['minimum_stock'] - $alert['current_stock']);
                                            if ($deficit > 0):
                                        ?>
                                            <span class="deficit-value status-critical">
                                                -<?= number_format($deficit, 2, ',', '.') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-success">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                            $deficit = $alert['deficit'] ?? ($alert['minimum_stock'] - $alert['current_stock']);
                                            if ($deficit > $alert['minimum_stock'] * 0.5):
                                        ?>
                                            <span class="alert-badge critical"><?= lang_line('stock_alert_critical'); ?></span>
                                        <?php else: ?>
                                            <span class="alert-badge warning"><?= lang_line('stock_alert_warning'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('purchase_request/create?item_type=' . $alert['item_type'] . '&item_id=' . $alert['item_id']) ?>" class="btn btn-sm btn-primary" title="<?= lang_line('stock_alert_btn_create_pr'); ?>">
                                            <i class="fa fa-plus"></i> <?= lang_line('stock_alert_btn_create_pr'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-section">
                    <div class="results-info">
                        <?= lang_line('stock_alert_showing'); ?> <span id="visible-count"><?= count($alerts) ?></span> <?= lang_line('stock_alert_from'); ?> <span id="total-count"><?= count($alerts) ?></span> <?= lang_line('stock_alert_data'); ?>
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination" id="pagination-list" style="margin: 0;">
                        </ul>
                    </nav>
                </div>
            <?php else: ?>
                <div class="no-alerts">
                    <i class="fa fa-check-circle"></i>
                    <h4><?= lang_line('stock_alert_all_safe'); ?></h4>
                    <p><?= lang_line('stock_alert_no_critical'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const I18N_SA = {
            prev: <?= json_encode(lang_line('stock_alert_pagination_prev')); ?>,
            next: <?= json_encode(lang_line('stock_alert_pagination_next')); ?>
        };
        const ROWS_PER_PAGE = 10;
        let currentPage = 1;
        let filteredRows = [];
        let currentFilter = 'all';

        // Update last update time
        function updateLastUpdateTime() {
            const now = new Date();
            document.getElementById('last-update-time').textContent = 
                now.toLocaleTimeString('id-ID') + ' (' + now.toLocaleDateString('id-ID') + ')';
        }

        // Filter by type
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                currentFilter = this.getAttribute('data-filter');
                currentPage = 1;
                filterTable();
            });
        });

        // Search and filter function
        function filterTable() {
            const searchInput = document.getElementById('search-input').value.toLowerCase();
            const allRows = document.querySelectorAll('.alert-row');
            
            filteredRows = [];
            
            allRows.forEach(row => {
                const typeMatch = currentFilter === 'all' || row.getAttribute('data-type') === currentFilter;
                const searchText = row.getAttribute('data-searchtext');
                const searchMatch = searchInput === '' || searchText.includes(searchInput);
                
                if (typeMatch && searchMatch) {
                    filteredRows.push(row);
                }
            });

            currentPage = 1;
            displayPage(1);
            updatePagination();
        }

        // Display page
        function displayPage(page) {
            const allRows = document.querySelectorAll('.alert-row');
            allRows.forEach(row => row.style.display = 'none');

            const start = (page - 1) * ROWS_PER_PAGE;
            const end = start + ROWS_PER_PAGE;
            
            filteredRows.slice(start, end).forEach(row => {
                row.style.display = '';
            });

            document.getElementById('visible-count').textContent = Math.min(end, filteredRows.length);
            document.getElementById('total-count').textContent = filteredRows.length;
        }

        // Update pagination buttons
        function updatePagination() {
            const paginationList = document.getElementById('pagination-list');
            paginationList.innerHTML = '';
            
            const totalPages = Math.ceil(filteredRows.length / ROWS_PER_PAGE);
            
            if (totalPages <= 1) return;

            // Previous button
            const prevBtn = document.createElement('li');
            prevBtn.className = 'page-item' + (currentPage === 1 ? ' disabled' : '');
            prevBtn.innerHTML = '<a class="page-link" href="#" onclick="goToPage(' + (currentPage - 1) + '); return false;">' + I18N_SA.prev + '</a>';
            paginationList.appendChild(prevBtn);

            // Page numbers
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) {
                const firstBtn = document.createElement('li');
                firstBtn.className = 'page-item';
                firstBtn.innerHTML = '<a class="page-link" href="#" onclick="goToPage(1); return false;">1</a>';
                paginationList.appendChild(firstBtn);

                if (startPage > 2) {
                    const ellipsis = document.createElement('li');
                    ellipsis.className = 'page-item disabled';
                    ellipsis.innerHTML = '<span class="page-link">...</span>';
                    paginationList.appendChild(ellipsis);
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('li');
                pageBtn.className = 'page-item' + (i === currentPage ? ' active' : '');
                pageBtn.innerHTML = '<a class="page-link" href="#" onclick="goToPage(' + i + '); return false;">' + i + '</a>';
                paginationList.appendChild(pageBtn);
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const ellipsis = document.createElement('li');
                    ellipsis.className = 'page-item disabled';
                    ellipsis.innerHTML = '<span class="page-link">...</span>';
                    paginationList.appendChild(ellipsis);
                }

                const lastBtn = document.createElement('li');
                lastBtn.className = 'page-item';
                lastBtn.innerHTML = '<a class="page-link" href="#" onclick="goToPage(' + totalPages + '); return false;">' + totalPages + '</a>';
                paginationList.appendChild(lastBtn);
            }

            // Next button
            const nextBtn = document.createElement('li');
            nextBtn.className = 'page-item' + (currentPage === totalPages ? ' disabled' : '');
            nextBtn.innerHTML = '<a class="page-link" href="#" onclick="goToPage(' + (currentPage + 1) + '); return false;">' + I18N_SA.next + '</a>';
            paginationList.appendChild(nextBtn);
        }

        // Go to page
        function goToPage(page) {
            const totalPages = Math.ceil(filteredRows.length / ROWS_PER_PAGE);
            if (page >= 1 && page <= totalPages) {
                currentPage = page;
                displayPage(page);
                updatePagination();
            }
        }

        // Reset search
        function resetSearch() {
            document.getElementById('search-input').value = '';
            currentFilter = 'all';
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            document.querySelector('[data-filter="all"]').classList.add('active');
            filterTable();
        }

        // Search on Enter key
        document.getElementById('search-input')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterTable();
            }
        });

        // Update alert count
        function updateAlertCount() {
            fetch('<?= base_url('stock_alert/get_alert_count') ?>')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('alert-count');
                    if (data.alert_count > 0) {
                        badge.textContent = data.alert_count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.textContent = '0';
                    }
                })
                .catch(error => console.error('Error fetching alert count:', error));
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateLastUpdateTime();
            updateAlertCount();
            
            // Initialize filtered rows
            filteredRows = Array.from(document.querySelectorAll('.alert-row'));
            displayPage(1);
            updatePagination();
            
            // Refresh every 30 seconds
            setInterval(updateAlertCount, 30000);
        });
    </script>
    </div>
