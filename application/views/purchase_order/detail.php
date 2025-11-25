<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-file-invoice-dollar text-primary mr-2"></i><?= htmlspecialchars($title ?? 'Detail PO'); ?>
            <?php if (isset($header) && $header): ?>
                <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->po_number); ?></span>
            <?php endif; ?>
        </h3>
        <div>
        <?php if (isset($header) && $header): // Hanya tampilkan jika header ada ?>
            <?php if ($header->status == 'Draft' && check_access('purchase_order', 'edit')): ?>
                <a href="<?= base_url('purchase_order/submit_po/'.$header->id); ?>" class="btn btn-info btn-sm mr-1 btn-submit" data-message="Yakin ingin submit PO ini? Setelah disubmit, header tidak bisa diedit." data-icon="info" data-confirm-text="Ya, Submit"> <i class="fas fa-paper-plane fa-sm"></i> Submit PO </a>
            <?php endif; ?>
            <?php if ($header->status == 'Submitted' && check_access('purchase_order', 'edit')): ?>
                 <a href="<?= base_url('purchase_order/confirm_po/'.$header->id); ?>" class="btn btn-primary btn-sm mr-1 btn-confirm" data-message="Yakin ingin konfirmasi (approve) PO ini?" data-icon="question" data-confirm-text="Ya, Konfirmasi"> <i class="fas fa-check-circle fa-sm"></i> Confirm PO </a>
                 <a href="<?= base_url('purchase_order/reject_po/'.$header->id); ?>" class="btn btn-warning btn-sm mr-1 btn-confirm" data-message="Yakin ingin mengembalikan PO ini ke Draft?" data-icon="warning" data-confirm-text="Ya, Kembalikan"> <i class="fas fa-undo fa-sm"></i> Reject </a>
            <?php endif; ?>
             <?php if ($header->status == 'Confirmed' && check_access('purchase_order', 'edit')): ?>
                 <a href="<?= base_url('purchase_order/unconfirm_po/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1 btn-confirm" data-message="Yakin ingin membatalkan konfirmasi PO ini?" data-text="Pastikan belum ada penerimaan barang." data-icon="warning" data-confirm-text="Ya, Batalkan Konfirmasi"> <i class="fas fa-times-circle fa-sm"></i> Unconfirm </a>
            <?php endif; ?>
             <?php if (in_array($header->status, ['Draft', 'Submitted', 'Confirmed']) && check_access('purchase_order', 'delete')): ?>
                 <a href="<?= base_url('purchase_order/cancel_po/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1 btn-confirm" data-message="Yakin ingin membatalkan (cancel) PO ini?" data-text="Aksi ini tidak bisa diurungkan." data-icon="error" data-confirm-text="Ya, Batalkan PO"> <i class="fas fa-ban fa-sm"></i> Cancel PO </a>
            <?php endif; ?>
            <?php if ($header->status == 'Draft' && check_access('purchase_order', 'edit')): ?>
                 <button type="button" class="btn btn-warning btn-sm mr-1" data-toggle="modal" data-target="#editHeaderModalDetail"> <i class="fas fa-edit fa-sm"></i> Edit Header </button>
             <?php endif; ?>
             
            <a href="<?= base_url('purchase_order/export_excel/'.$header->id); ?>" target="_blank" class="btn btn-success btn-sm mr-1 no-loader"> <i class="fas fa-file-excel fa-sm"></i> Excel </a>
            <a href="<?= base_url('purchase_order/export_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1 no-loader"> <i class="fas fa-file-pdf fa-sm"></i> PDF </a>
        <?php endif; // End check header exists ?>
            <a href="<?= base_url('purchase_order'); ?>" class="btn btn-secondary btn-sm btn-load"> <i class="fas fa-arrow-left fa-sm mr-2"></i>Kembali ke Daftar </a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?> <div class="alert alert-success alert-dismissible fade show" role="alert"><?= $this->session->flashdata('success'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div> <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?> <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div> <?php endif; ?>
    <?php if ($this->session->flashdata('warning')): ?> <div class="alert alert-warning alert-dismissible fade show" role="alert"><?= $this->session->flashdata('warning'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div> <?php endif; ?>

    <?php if (isset($header) && $header): ?>
     <div class="card shadow mb-4">
        <div class="card-header py-3"> <h6 class="m-0 font-weight-bold text-primary">Informasi Purchase Order</h6> </div>
        <div class="card-body">
             <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0">
                         <tr><td style="width:35%;"><strong>Nomor PO</strong></td><td style="width:2%">:</td><td><?= htmlspecialchars($header->po_number); ?></td></tr>
                         <tr><td><strong>Supplier</strong></td><td>:</td><td><?= htmlspecialchars($header->nama_supplier); ?></td></tr>
                         <tr><td><strong>Alamat Supplier</strong></td><td>:</td><td><?= htmlspecialchars($header->alamat ?? '-'); ?></td></tr>
                         <tr><td><strong>Telepon Supplier</strong></td><td>:</td><td><?= htmlspecialchars($header->telepon ?? '-'); ?></td></tr>
                         <tr><td><strong>Dibuat Oleh</strong></td><td>:</td><td><?= htmlspecialchars($header->creator_name ?? '-'); ?></td></tr>
                         <tr><td><strong>Dikonfirmasi Oleh</strong></td><td>:</td><td><?= htmlspecialchars($header->confirmer_name ?? '-'); ?> <?= $header->confirmed_at ? '('.date('d M Y H:i', strtotime($header->confirmed_at)).')' : ''; ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                     <table class="table table-sm table-borderless mb-0">
                         <tr><td style="width:35%;"><strong>Tanggal PO</strong></td><td style="width:2%">:</td><td><?= date('d M Y', strtotime($header->po_date)); ?></td></tr>
                         <tr><td><strong>Status</strong></td><td>:</td><td><?php $color = $status_colors[$header->status] ?? 'secondary'; ?><span class="badge badge-<?= $color; ?>"><?= htmlspecialchars($header->status); ?></span></td></tr>
                         <tr><td><strong>Mode Pengiriman</strong></td><td>:</td><td><?= htmlspecialchars($header->shipping_mode_desc ?? '-'); ?></td></tr>
                         <tr><td><strong>Mode Pembayaran</strong></td><td>:</td><td><?= htmlspecialchars($header->payment_mode_desc ?? '-'); ?></td></tr>
                         <tr><td><strong>Mata Uang</strong></td><td>:</td><td><?= htmlspecialchars($header->currency_desc ?? '-'); ?> <?= $header->currency_code != 'IDR' ? '(Kurs: '.number_format((float)$header->exchange_rate, 4).')' : ''; ?></td></tr>
                         <tr><td><strong>PPN 11%</strong></td><td>:</td><td><?= $header->has_ppn ? '<span class="badge badge-success">Ya</span>' : '<span class="badge badge-secondary">Tidak</span>'; ?></td></tr>
                    </table>
                </div>
            </div>
             <?php if(!empty($header->remarks)): ?> <hr class="mt-2 mb-2"><p class="mb-0 small"><strong>Catatan / Remarks:</strong><br> <?= nl2br(htmlspecialchars($header->remarks)); ?></p><?php endif; ?>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-danger">Gagal memuat data header PO. Silakan kembali ke daftar.</div>
    <?php endif; // End check header exists ?>


    <?php if (isset($header) && $header): // Hanya tampilkan jika header ada ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Detail Item Purchase Order</h6>
            <?php if ($header->status == 'Draft' && check_access('purchase_order', 'edit')): ?>
            <button class="btn btn-success btn-sm" type="button" id="add-item-row-btn">
                <i class="fas fa-plus fa-sm"></i> Tambah Baris Item
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 table-hover">
                    <thead class="thead-light text-center">
                        <tr>
                            <th style="width: 3%;">No</th> 
                            <th style="width: 15%;">Kode Item</th> 
                            <th>Nama Item</th> 
                            <th style="width: 8%;">Satuan</th>
                            <th>Deskripsi Tambahan</th> 
                            <th style="width: 12%;">Sales Contract</th>
                            <th style="width: 8%;">Qty</th> 
                            <th style="width: 12%;">Harga Satuan (<?= $header->currency_code; ?>)</th> 
                            <th style="width: 12%;">Total (<?= $header->currency_code; ?>)</th>
                            <th style="width: 5%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="po-item-details-body">
                        <?php 
                             $grand_total_base = 0; $grand_total_local = 0; $no = 1; 
                             if(!empty($details)): 
                                 foreach($details as $d): 
                                     $row_total_base = (float)($d->qty ?? 0) * (float)($d->base_price ?? 0); $grand_total_base += $row_total_base; $grand_total_local += (float)($d->total_local ?? 0); 
                        ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($d->item_code ?? '-'); ?></td>
                            <td><?= htmlspecialchars($d->item_name ?? '-'); ?></td>
                            <td class="text-center"><?= htmlspecialchars($d->item_unit ?? '-'); ?></td>
                            <td><?= nl2br(htmlspecialchars($d->description ?? '-')); ?></td>
                            <td class="text-center"><?= htmlspecialchars($d->sc_number ?? '-'); ?></td>
                            <td class="text-right"><?= number_format((float)($d->qty ?? 0), 4, '.', ','); ?></td>
                            <td class="text-right"><?= number_format((float)($d->base_price ?? 0), 2, '.', ','); ?></td>
                            <td class="text-right"><?= number_format($row_total_base, 2, '.', ','); ?></td>
                            <td class="text-center">
                                <?php if ($header->status == 'Draft' && check_access('purchase_order', 'edit')): ?>
                                <a href="<?= base_url('purchase_order/delete_item/'.$d->po_header_id.'/'.$d->id); ?>" class="btn btn-danger btn-xs btn-delete" title="Hapus Item" data-message="Yakin hapus item <?= htmlspecialchars($d->item_name ?? 'ini'); ?>?"> <i class="fas fa-trash"></i> </a>
                                <?php else: echo '-'; endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php else: ?>
                            <tr id="no-item-row"><td colspan="10" class="text-center text-muted py-5">Belum ada item detail. Silakan tambahkan item.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    
                    <tfoot id="po-item-details-footer">
                         <?php 
                            $ppn_base = $header->has_ppn ? $grand_total_base * 0.11 : 0; 
                            $grand_total_final_base = $grand_total_base + $ppn_base;
                            $grand_total_final_local = $grand_total_local * ($header->has_ppn ? 1.11 : 1); 
                         ?>
                        <tr class="bg-light font-weight-bold"> <td colspan="8" class="text-right">Subtotal (<?= $header->currency_code; ?>)</td> <td class="text-right" id="subtotal-base"><?= number_format($grand_total_base, 2, '.', ','); ?></td> <td></td> </tr>
                         <?php if($header->has_ppn): // Tampilkan PPN jika has_ppn = 1 ?> 
                            <tr class="bg-light"> <td colspan="8" class="text-right">PPN 11% (<?= $header->currency_code; ?>)</td> <td class="text-right" id="ppn-base"><?= number_format($ppn_base, 2, '.', ','); ?></td> <td></td> </tr> 
                         <?php endif; ?>
                          <tr class="bg-primary text-white font-weight-bold"> <td colspan="8" class="text-right">Grand Total (<?= $header->currency_code; ?>)</td> <td class="text-right" id="grandtotal-base"><?= number_format($grand_total_final_base, 2, '.', ','); ?></td> <td></td> </tr>
                         <?php if($header->currency_code != 'IDR'): ?> <tr class="bg-info text-white font-weight-bold"> <td colspan="8" class="text-right">Estimasi Grand Total (IDR)</td> <td class="text-right" id="grandtotal-local"><?= number_format($grand_total_final_local, 2, '.', ','); ?></td> <td></td> </tr> <?php endif; ?>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php endif; // End check header exists ?>

</div> <?php if (isset($header) && $header && $header->status == 'Draft' && check_access('purchase_order', 'edit')): ?>
<div class="modal fade" id="editHeaderModalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('purchase_order/update_header/'.$header->id); ?>" class="needs-validation" novalidate> 
             <div class="modal-content border-0 shadow">
                 <div class="modal-header bg-warning text-dark"> <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Header PO</h5> <button type="button" class="close text-dark" data-dismiss="modal">&times;</button> </div>
                 <div class="modal-body">
                      </div>
                 <div class="modal-footer"> <button type="submit" class="btn btn-warning btn-load"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button> <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button> </div>
             </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (isset($header) && $header): // Hanya render template jika header ada ?>
<template id="new-item-row-template">
    <tr class="new-item-row">
        <td class="text-center align-middle">*</td>
        <td><input type="text" class="form-control form-control-sm item-code-display" readonly style="background-color: #e9ecef;"></td> 
        <td><select name="item_id" class="form-control form-control-sm select2-inline-item" required style="width: 100%;"><option value="">-- Cari Nama Item --</option></select></td>
        <td><input type="text" class="form-control form-control-sm item-unit-display text-center" readonly style="background-color: #e9ecef;"></td> 
        <td><input type="text" name="description" class="form-control form-control-sm" placeholder="Opsional..."></td>
        <td><select name="sales_contract_id" class="form-control form-control-sm select2-inline-sc" style="width: 100%;"><option value="">-- Opsional --</option><?php foreach(($open_contracts ?? []) as $sc): ?><option value="<?= $sc->id; ?>"><?= htmlspecialchars($sc->sc_number); ?></option><?php endforeach; ?></select></td>
        <td><input type="number" step="0.0001" name="qty" class="form-control form-control-sm text-right inline-qty" required min="0.0001" placeholder="0.0000"></td> 
        <td><input type="text" name="base_price" class="form-control form-control-sm autonumeric-2dec-inline text-right inline-price" required value="0.00"></td>
        <td class="text-right align-middle inline-total">0.00</td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-success btn-xs save-inline-item" title="Simpan Item Ini"> <i class="fas fa-check"></i> </button>
            <button type="button" class="btn btn-danger btn-xs cancel-inline-item" title="Batal"> <i class="fas fa-times"></i> </button>
        </td>
    </tr>
</template>
<?php endif; // End check header exists for template ?>

<?php $this->load->view('templates/footer'); // Load footer utama ?>

<script>
// Pastikan script ini dijalankan setelah jQuery, Select2, AutoNumeric dimuat
$(function() { 
    
    // --- Variabel Global Halaman ---
    const currentStatus = <?= isset($header->status) ? json_encode($header->status) : '"unknown"'; ?>;
    const poHeaderId = <?= isset($header->id) ? $header->id : 0; ?>;
    
    // HAPUS SEMUA REFERENSI CSRF
    console.log("[PO Detail] Document Ready. Status:", currentStatus, "ID:", poHeaderId); 
    console.log("[PO Detail] CSRF Protection is DISABLED on this page's script.");

    // --- Inisialisasi Modal Edit Header ---
    let anEditExchangeRate = null; 
    const $editModal = $('#editHeaderModalDetail'); 
    if ($editModal.length) { 
       const $editRateInput = $editModal.find('.autonumeric-4dec-edit'); 
       if ($editRateInput.length && typeof AutoNumeric === 'function') { 
           try {
                anEditExchangeRate = new AutoNumeric($editRateInput[0], { decimalPlaces: 4, modifyValueOnWheel: false }); 
                const $editRateDiv = $editModal.find('#edit_exchange_rate_div'); 
                const $editRateInputElem = $editRateDiv.find('input'); 
                const $editCurrencySelect = $editModal.find('#edit_currency_code'); 

                const toggleEditKurs = () => {
                     const selectedCurrency = $editCurrencySelect.val();
                     if (selectedCurrency && selectedCurrency !== 'IDR') { 
                         $editRateDiv.slideDown(); $editRateInputElem.prop('required', true); 
                     } else { 
                         $editRateDiv.slideUp(); $editRateInputElem.prop('required', false); 
                         if (anEditExchangeRate) anEditExchangeRate.set(1); else $editRateInputElem.val('1.0000');
                     }
                }
                toggleEditKurs(); 
                $editCurrencySelect.on('change', toggleEditKurs); 
                console.log("[PO Detail] AutoNumeric & Currency Logic for Edit Modal Initialized.");

                // Bootstrap validation for modal form
                $editModal.find('form.needs-validation').on('submit', function(event) {
                    if (this.checkValidity() === false) { event.preventDefault(); event.stopPropagation(); }
                    $(this).addClass('was-validated');
                });
           } catch(err) { console.error("[PO Detail] Error initializing AN/Currency for Edit Modal:", err); }
       } else { console.log("[PO Detail] AutoNumeric input not found or AutoNumeric lib not loaded."); }
    } else {
        console.log("[PO Detail] Edit modal not found, skipping init.");
    }

    // --- LOGIKA INLINE EDIT (Hanya jika Status Draft) ---
    if (currentStatus === 'Draft') {
        console.log("[PO Detail] Status is Draft, enabling inline add."); 
        const $itemTableBody = $('#po-item-details-body'); 
        const $itemTableFooter = $('#po-item-details-footer'); // Cache footer
        const templateNode = document.getElementById('new-item-row-template'); 
        let $noItemRow = $('#no-item-row'); 
        let inlineAutoNumericInstances = []; 

        if(!templateNode || !templateNode.content) { console.error("CRITICAL: Template #new-item-row-template not found!"); return; }
        console.log("[PO Detail] Template found:", templateNode);

        // --- Fungsi Inisialisasi Input di Baris Baru ---
        function initializeNewRowInputs($rowElement) { 
            console.log("[PO Detail] Initializing inputs for new row:", $rowElement[0]); 
            
            inlineAutoNumericInstances = inlineAutoNumericInstances.filter(instance => {
                if ($.contains($rowElement[0], instance.domElement)) {
                    try { instance.detach(); console.log("[PO Detail] Detached old AN."); } catch(e){ console.warn("[PO Detail] AN detach error", e); } return false; 
                } return true;
            });

            // 1. Inisialisasi Select2 Item Search
            const $itemSelectElement = $rowElement.find('.select2-inline-item'); 
            if (!$itemSelectElement.length) { console.error("[PO Detail] Select2 target '.select2-inline-item' NOT FOUND!"); return; }
            if (typeof $.fn.select2 === 'function') {
                 try {
                    if ($itemSelectElement.data('select2')) { $itemSelectElement.select2('destroy'); }
                    $itemSelectElement.select2({ 
                        placeholder: '-- Cari Nama Item --', minimumInputLength: 2, theme: 'bootstrap4', 
                        ajax: {
                            url: '<?= base_url("purchase_order/search_items"); ?>', dataType: 'json', delay: 400, 
                            data: function (params) { 
                                let query = { q: params.term };
                                console.log("[PO Detail] Select2 AJAX Data Sent:", query); 
                                return query; 
                            },
                            processResults: function (data, params) { 
                                console.log("[PO Detail] Select2 AJAX Response:", data); 
                                if (data && Array.isArray(data.results)) { return { results: data.results }; } 
                                else { console.error("[PO Detail] Select2: Invalid data structure:", data); return { results: [] }; }
                            },
                             error: function(jqXHR, status, error) { console.error("[PO Detail] Select2 AJAX Error:", status, error, jqXHR.status); }
                        },
                        dropdownParent: $(document.body) 
                    }).on('select2:select', function (e) {
                         var data = e.params.data; console.log("[PO Detail] Select2 Item Selected:", data); 
                         const $currentRow = $(this).closest('tr'); 
                         $currentRow.find('.item-code-display').val(data.code || '-'); 
                         $currentRow.find('.item-unit-display').val(data.unit || '-'); 
                         $currentRow.find('.inline-qty').focus();
                    }).on('select2:open', function (e) {
                         setTimeout(() => { document.querySelector('.select2-search__field')?.focus(); }, 50); 
                    });
                     console.log("[PO Detail] Select2 AJAX Initialized successfully."); 
                 } catch (err) { console.error("[PO Detail] Error during Select2 initialization:", err); }
            } else { console.error("[PO Detail] CRITICAL: jQuery.fn.select2 is NOT defined!"); }

            // 2. Inisialisasi Select2 Sales Contract 
            const $scSelect = $rowElement.find('.select2-inline-sc'); 
            if ($scSelect.length > 0 && typeof $.fn.select2 === 'function') { /* ... (init SC) ... */ }
            else if ($scSelect.length > 0) { console.error("[PO Detail] Cannot initialize Select2 SC - function not found."); }

            // 3. Inisialisasi AutoNumeric Harga
            const priceInputElement = $rowElement.find('.autonumeric-2dec-inline')[0]; 
             if (priceInputElement && typeof AutoNumeric === 'function') { /* ... (init AN) ... */ }
             else if (priceInputElement) { console.error("[PO Detail] CRITICAL: AutoNumeric function is NOT defined!"); }
             else { console.warn("[PO Detail] AutoNumeric target not found."); }
        } // Akhir initializeNewRowInputs

        // --- Fungsi Hitung Total Baris ---
        function calculateRowTotal($rowElement) { 
             const qty = parseFloat($rowElement.find('.inline-qty').val()) || 0;
             let price = 0;
             const priceInput = $rowElement.find('.autonumeric-2dec-inline')[0];
             const anInstance = AutoNumeric.getAutoNumericElement(priceInput); 
             if (anInstance) { price = anInstance.getNumber() || 0; } 
             else { price = parseFloat(($(priceInput).val() || '0').replace(/,/g, '')); } 
             const total = qty * price;
             $rowElement.find('.inline-total').text( total.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) );
         }

        // --- Event Listener Tombol "Tambah Baris Item" ---
        $('#add-item-row-btn').off('click').on('click', function() {
            console.log("[PO Detail] Add item row button clicked."); 
            $noItemRow = $('#no-item-row'); 
            if ($noItemRow.length) { console.log("[PO Detail] Removing 'no item' row."); $noItemRow.remove(); }
            if ($itemTableBody.find('.new-item-row').length > 0) {
                 alert('Selesaikan atau batalkan penambahan item sebelumnya.'); console.warn("[PO Detail] Add row blocked."); return;
            }
             if (!templateNode || !templateNode.content) { console.error("[PO Detail] Template node invalid!"); return; } 
             
             try {
                 const newRowFragment = templateNode.content.cloneNode(true);
                 // SELALU APPEND KE TBODY
                 $itemTableBody.append(newRowFragment); 
                 console.log("[PO Detail] Template appended to <tbody>.");
                 
                 const $appendedRow = $itemTableBody.find('tr.new-item-row').last(); 
                 
                 if ($appendedRow.length) {
                     console.log("[PO Detail] Row appended. Initializing inputs after delay...");
                     setTimeout(() => {
                          initializeNewRowInputs($appendedRow); 
                          $appendedRow.find('.select2-inline-item').select2('open');
                     }, 50); 
                     
                     $appendedRow.find('.inline-qty, .autonumeric-2dec-inline').on('input change keyup focusout', function() { 
                         calculateRowTotal($(this).closest('tr'));
                     });
                 } else { 
                     // Ini tidak seharusnya terjadi jika append ke tbody berhasil
                     console.error("[PO Detail] CRITICAL: New row not found after append!"); 
                 }
             } catch(err) { console.error("[PO Detail] Error cloning/appending template:", err); alert("Gagal menambahkan baris baru."); }
        });

        // --- Event Listener Tombol Save (✅) ---
        $itemTableBody.off('click', '.save-inline-item').on('click', '.save-inline-item', function() { 
             console.log("[PO Detail] Save button clicked."); 
             const button = $(this); 
             const $row = button.closest('tr.new-item-row'); // PERBAIKI: Definisikan $row
             if (!$row.length) { console.error("[PO Detail] Could not find parent row for save."); return; }
            
             const $itemSelect = $row.find('select[name="item_id"]'); 
             const $qtyInput = $row.find('input[name="qty"]');
             const $priceInput = $row.find('input[name="base_price"]');
             const $scSelect = $row.find('select[name="sales_contract_id"]');
             const $descInput = $row.find('input[name="description"]');
             
             const itemIdValue = $itemSelect.val();
             const qtyValue = parseFloat($qtyInput.val());
             let basePriceNumeric = 0;
             const anPriceInstance = AutoNumeric.getAutoNumericElement($priceInput[0]);
             if (anPriceInstance) { basePriceNumeric = anPriceInstance.getNumber(); } 
             else { basePriceNumeric = parseFloat(($priceInput.val() || '0').replace(/,/g, '')); } 
             
             // Validasi
             if (!itemIdValue) { alert('Harap pilih Item.'); if ($itemSelect.data('select2')) $itemSelect.select2('open'); return; }
             if (isNaN(qtyValue) || qtyValue <= 0) { alert('Harap isi Qty (> 0).'); $qtyInput.focus(); return; }
             if (isNaN(basePriceNumeric) || basePriceNumeric < 0) { alert('Harga Satuan tidak boleh negatif.'); $priceInput.focus(); return; }
             console.log("[PO Detail] Validation passed.");

             // Data
             const dataToSend = {
                 item_id: itemIdValue, qty: qtyValue, base_price: basePriceNumeric, 
                 sales_contract_id: $scSelect.val() || null, description: $descInput.val()
             };
             console.log("[PO Detail] Data to send:", dataToSend);

             // AJAX
             $.ajax({ 
                 url: `<?= base_url('purchase_order/ajax_add_item/'); ?>${poHeaderId}`, 
                 method: 'POST', data: dataToSend, dataType: 'json', 
                 beforeSend: function(){ 
                     button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>'); 
                     // PERBAIKAN: Gunakan $row
                     $row.find('.cancel-inline-item').prop('disabled', true); 
                 }, 
                 success: function(response){ 
                     console.log("[PO Detail] AJAX Save Success:", response);
                     if (response && response.status === 'success') { 
                         Swal.fire({ icon: 'success', title: 'Berhasil!', text: response.message || 'Item berhasil disimpan.', timer: 1500, showConfirmButton: false })
                             .then(() => window.location.reload());
                     } else { 
                          Swal.fire({ icon: 'error', title: 'Gagal!', text: response.message || 'Gagal menyimpan data.' });
                          button.prop('disabled', false).html('<i class="fas fa-check"></i>'); 
                          $row.find('.cancel-inline-item').prop('disabled', false); // Gunakan $row
                     }
                 }, 
                 error: function(xhr, status, error){ 
                      console.error("[PO Detail] AJAX Save Error:", status, error, xhr.responseText); 
                      Swal.fire({ icon: 'error', title: 'Error!', text: 'Gagal menghubungi server. Periksa koneksi Anda.' });
                      button.prop('disabled', false).html('<i class="fas fa-check"></i>'); 
                      $row.find('.cancel-inline-item').prop('disabled', false); // Gunakan $row
                 } 
             });
        }); 

        // --- Event Listener Tombol Cancel (❌) ---
         $itemTableBody.off('click', '.cancel-inline-item').on('click', '.cancel-inline-item', function() { /* ... (kode cancel sama) ... */ });

        // --- Event Listener Kalkulasi Total (Delegated) ---
        $itemTableBody.off('input change keyup focusout', '.new-item-row .inline-qty, .new-item-row .autonumeric-2dec-inline')
                     .on('input change keyup focusout', '.new-item-row .inline-qty, .new-item-row .autonumeric-2dec-inline', function() { /* ... (kode calculate total sama) ... */ });

    } else {
        console.log("[PO Detail] Status is not Draft, inline add disabled."); 
    } // End if currentStatus === 'Draft'

    // --- Event Listener Tombol Lain (di luar if Draft) ---
    if (typeof Swal !== 'undefined') {
        $(document).off('click', '.btn-delete').on('click', '.btn-delete', function(e) { /* ... (kode SweetAlert delete sama) ... */ });
        $(document).off('click', 'a.btn-confirm, a.btn-submit').on('click', 'a.btn-confirm, a.btn-submit', function(e) { /* ... (kode SweetAlert confirm sama) ... */ });
    } else {
        console.warn("[PO Detail] SweetAlert is not loaded. Using basic confirm.");
         // ... (Fallback confirm() sama) ...
    }

}); // End $(function() { ... })
</script>


<style> 
/* Style Select2 agar pas di tabel & tema bootstrap */
.select2-container--bootstrap4 .select2-selection--single { height: calc(1.5em + .5rem + 2px) !important; padding: .25rem .5rem !important; font-size: .875rem; line-height: 1.5; border-radius: .2rem; border: 1px solid #d1d3e2;}
.select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow { height: calc(1.5em + .5rem) !important; }
.select2-container--bootstrap4 .select2-dropdown { z-index: 1051 !important; border-color: #d1d3e2;} 
.select2-container { width: 100% !important; } 

/* Style untuk baris baru */
.new-item-row input, .new-item-row select { font-size: 0.8rem; height: calc(1.5em + .5rem + 2px); padding: .25rem .5rem;}
.new-item-row .btn-xs { padding: .15rem .4rem; font-size: .7rem; line-height: 1.2;}
.saving { opacity: 0.6; pointer-events: none; } 

/* Style input readonly */
.new-item-row input[readonly] { background-color: #e9ecef !important; border: 1px solid #d1d3e2 !important; box-shadow: none !important; cursor: default; color: #6e707e; }
.new-item-row .item-code-display, .new-item-row .item-unit-display { text-align: center; }

/* Sembunyikan panah di input number */
input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }
</style>