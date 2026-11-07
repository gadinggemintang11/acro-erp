<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Quality Assurance Model
 * 
 * Menangani logic untuk Quality Control sebelum barang masuk ke stock
 * Workflow:
 * 1. GRN di-confirm (stock tidak langsung masuk, pending QA)
 * 2. QA Header dibuat, Inspector melakukan QC
 * 3. Item yang PASS masuk ke stock, REJECT disimpan dengan dokumentasi
 * 4. Atasan bisa review dan approve QA
 */
class Quality_assurance_model extends CI_Model {

    private $table_qa_header = 'qa_header';
    private $table_qa_details = 'qa_details';
    private $table_qa_attachments = 'qa_reject_attachments';
    private $table_qa_approval = 'qa_approval_log';
    private $table_grn_header = 'grn_header';
    private $table_grn_details = 'grn_details';

    // =====================================================
    // QA HEADER FUNCTIONS
    // =====================================================

    /**
     * Generate QA Number (format: QA/202504/0001)
     */
    public function generate_qa_number() {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('qa', 'qa_header', 'qa_number');
    }

    /**
     * Create QA Header from confirmed GRN
     * Called automatically when GRN is confirmed
     */
    public function create_qa_header_from_grn($grn_header_id, $created_by = null) {
        $this->db->reset_query();
        
        // Get GRN header
        $grn_header = $this->db->select('id, grn_number, grn_date, qa_header_id')
            ->from($this->table_grn_header)
            ->where('id', $grn_header_id)
            ->get()
            ->row();

        if (!$grn_header) {
            log_message('error', 'QA: GRN header not found for ID: ' . $grn_header_id);
            return false;
        }

        // ===== NEW: Check if QA already exists =====
        if (!empty($grn_header->qa_header_id)) {
            // QA sudah ada, return existing QA ID (prevent duplicate)
            log_message('info', 'QA: QA header already exists for GRN ' . $grn_header->grn_number . ' (ID: ' . $grn_header->qa_header_id . ')');
            return $grn_header->qa_header_id;
        }
        // ===== END NEW: Check if QA already exists =====

        // Create QA Header (status = In Progress so QA can start immediately)
        $qa_number = $this->generate_qa_number();
        $qa_data = [
            'grn_header_id' => $grn_header_id,
            'grn_number' => $grn_header->grn_number,
            'qa_number' => $qa_number,
            'qa_date' => $grn_header->grn_date,
            'status' => 'In Progress',
            'qa_checked_by' => $created_by,
            'qa_checked_at' => date('Y-m-d H:i:s'),
            'created_by' => $created_by
        ];

        $this->db->insert($this->table_qa_header, $qa_data);
        $qa_header_id = $this->db->insert_id();

        if (!$qa_header_id) {
            log_message('error', 'QA: Failed to create QA header for GRN: ' . $grn_header_id);
            return false;
        }

        // Update GRN header dengan QA info
        $this->db->where('id', $grn_header_id)
            ->update($this->table_grn_header, [
                'qa_header_id' => $qa_header_id,
                'qa_status' => 'In Progress'
            ]);

        // Create QA Details from GRN Details
        $grn_details = $this->db->select('*')
            ->from($this->table_grn_details)
            ->where('grn_header_id', $grn_header_id)
            ->get()
            ->result();

        foreach ($grn_details as $detail) {
            $qa_detail_data = [
                'qa_header_id' => $qa_header_id,
                'grn_header_id' => $grn_header_id,
                'grn_detail_id' => $detail->id,
                'item_id' => $detail->item_id,
                'item_type' => $detail->item_type,
                'receive_qty' => $detail->receive_qty,
                'qa_status' => 'Pending'
            ];
            $this->db->insert($this->table_qa_details, $qa_detail_data);
        }

        log_message('info', 'QA: Created QA header ' . $qa_number . ' for GRN ' . $grn_header->grn_number);
        return $qa_header_id;
    }

    /**
     * Get QA header by ID
     */
    public function get_qa_header_by_id($qa_header_id) {
        $this->db->reset_query();
        $this->db->select('
            qh.*,
            gh.grn_number, gh.grn_date, gh.supplier_id, gh.base_on, gh.warehouse_type,
            creator.fullname as created_by_name,
            inspector.fullname as qa_checked_by_name
        ', FALSE);
        $this->db->from($this->table_qa_header . ' qh');
        $this->db->join($this->table_grn_header . ' gh', 'qh.grn_header_id = gh.id', 'left');
        $this->db->join('users creator', 'qh.created_by = creator.id', 'left');
        $this->db->join('users inspector', 'qh.qa_checked_by = inspector.id', 'left');
        $this->db->where('qh.id', $qa_header_id);
        $this->db->group_by('qh.id');

        $header = $this->db->get()->row();

        // Enhance supplier info from PO or MIR if not set
        if ($header) {
            $header = $this->enhance_supplier_info($header);
        }

        return $header;
    }

    /**
     * Enhance supplier info from PO, MIR, or Warehouse Out details
     */
    public function enhance_supplier_info($qa_header) {
        if (empty($qa_header) || (isset($qa_header->nama_supplier) && !empty($qa_header->nama_supplier) && $qa_header->nama_supplier !== '-')) {
            return $qa_header;
        }

        // Get first detail to find supplier from PO, MIR, or Warehouse Out
        $first_detail = $this->db->select('source_doc_header_id, source_doc_type')
            ->from('grn_details')
            ->where('grn_header_id', $qa_header->grn_header_id)
            ->limit(1)
            ->get()
            ->row();

        if ($first_detail) {
            if ($first_detail->source_doc_type == 'PO') {
                // Get supplier from PO
                $supplier = $this->db->select('s.nama_supplier')
                    ->from('po_header ph')
                    ->join('suppliers s', 's.id = ph.supplier_id', 'left')
                    ->where('ph.id', $first_detail->source_doc_header_id)
                    ->get()
                    ->row();
                if ($supplier) {
                    $qa_header->nama_supplier = $supplier->nama_supplier;
                }
            } else if ($first_detail->source_doc_type == 'MIR') {
                // Get supplier from MIR
                $mir = $this->db->select('supplier_id')
                    ->from('mir_header')
                    ->where('id', $first_detail->source_doc_header_id)
                    ->get()
                    ->row();

                if ($mir && !empty($mir->supplier_id)) {
                    $supplier = $this->db->select('nama_supplier')
                        ->from('suppliers')
                        ->where('id', $mir->supplier_id)
                        ->get()
                        ->row();
                    if ($supplier) {
                        $qa_header->nama_supplier = $supplier->nama_supplier;
                    }
                }
            } else if ($first_detail->source_doc_type == 'WO') {
                // Get supplier from Warehouse Out (return/NG goods)
                $wo_header = $this->db->select('s.nama_supplier, COALESCE(s.nama_supplier, wo.nama_supplier) as supplier_name')
                    ->from('warehouse_out_header wo')
                    ->join('suppliers s', 's.id = wo.supplier_id', 'left')
                    ->where('wo.id', $first_detail->source_doc_header_id)
                    ->get()
                    ->row();

                if ($wo_header && !empty($wo_header->supplier_name)) {
                    $qa_header->nama_supplier = $wo_header->supplier_name;
                } elseif ($wo_header && !empty($wo_header->nama_supplier)) {
                    $qa_header->nama_supplier = $wo_header->nama_supplier;
                }
            }
        }

        // Default if still empty
        if (empty($qa_header->nama_supplier)) {
            $qa_header->nama_supplier = '-';
        }

        return $qa_header;
    }

    /**
     * Get QA header by GRN ID
     */
    public function get_qa_header_by_grn_id($grn_header_id) {
        $this->db->reset_query();
        $this->db->select('*')
            ->from($this->table_qa_header)
            ->where('grn_header_id', $grn_header_id);
        
        return $this->db->get()->row();
    }

    /**
     * Get all QA headers (with pagination support)
     * Smart supplier detection dari GRN header, PO, MIR, atau Warehouse Out
     */
    public function get_all_qa_headers($limit = 10, $offset = 0, $filters = []) {
        $this->db->reset_query();
        $this->db->select('
            qh.*,
            gh.supplier_id,
            gh.base_on,
            COALESCE(s_grn.nama_supplier, s_po.nama_supplier, s_mir.nama_supplier, s_wo.nama_supplier, "-") as nama_supplier,
            COUNT(DISTINCT qad.id) as total_items,
            SUM(CASE WHEN qad.qa_status = "Pass" THEN 1 ELSE 0 END) as pass_items,
            SUM(CASE WHEN qad.qa_status IN ("Reject", "Partial") AND qad.qa_reject_qty > 0 THEN 1 ELSE 0 END) as reject_items,
            SUM(COALESCE(qad.qa_pass_qty, 0)) as pass_qty,
            SUM(COALESCE(qad.qa_reject_qty, 0)) as reject_qty
        ', FALSE);
        $this->db->from($this->table_qa_header . ' qh');
        $this->db->join($this->table_grn_header . ' gh', 'qh.grn_header_id = gh.id', 'left');

        // Priority 1: Supplier dari GRN header
        $this->db->join('suppliers s_grn', 'gh.supplier_id = s_grn.id AND gh.supplier_id > 0', 'left');

        // Priority 2: Supplier dari PO
        $this->db->join('grn_details gd_po', 'gh.id = gd_po.grn_header_id AND gd_po.source_doc_type = \'PO\'', 'left');
        $this->db->join('po_header po', 'gd_po.source_doc_header_id = po.id', 'left');
        $this->db->join('suppliers s_po', 'po.supplier_id = s_po.id AND s_grn.id IS NULL', 'left');

        // Priority 3: Supplier dari MIR
        $this->db->join('grn_details gd_mir', 'gh.id = gd_mir.grn_header_id AND gd_mir.source_doc_type = \'MIR\'', 'left');
        $this->db->join('mir_header mh', 'gd_mir.source_doc_header_id = mh.id AND s_grn.id IS NULL AND s_po.id IS NULL', 'left');
        $this->db->join('suppliers s_mir', 'mh.supplier_id = s_mir.id', 'left');

        // Priority 4: Supplier dari Warehouse Out (untuk return/NG goods)
        $this->db->join('grn_details gd_wo', 'gh.id = gd_wo.grn_header_id AND gd_wo.source_doc_type = \'WO\'', 'left');
        $this->db->join('warehouse_out_header wo', 'gd_wo.source_doc_header_id = wo.id AND s_grn.id IS NULL AND s_po.id IS NULL AND s_mir.id IS NULL', 'left');
        $this->db->join('suppliers s_wo', 'wo.supplier_id = s_wo.id', 'left');

        $this->db->join($this->table_qa_details . ' qad', 'qh.id = qad.qa_header_id', 'left');

        // Apply filters
        if (!empty($filters['status'])) {
            $this->db->where('qh.status', $filters['status']);
        }
        if (!empty($filters['qa_status'])) {
            // Filter by overall QA status
            $qa_status_map = [
                'Pending' => 'Pending',
                'In Progress' => 'In Progress',
                'Passed' => 'Passed',
                'Partial Passed' => 'Partial Passed',
                'Rejected' => 'Rejected'
            ];
            if (isset($qa_status_map[$filters['qa_status']])) {
                $this->db->where('gh.qa_status', $qa_status_map[$filters['qa_status']]);
            }
        }
        if (!empty($filters['search'])) {
            $this->db->group_start()
                ->like('qh.qa_number', $filters['search'])
                ->or_like('gh.grn_number', $filters['search'])
                ->or_like('s_po.nama_supplier', $filters['search'])
                ->or_like('s_mir.nama_supplier', $filters['search'])
                ->or_like('s_wo.nama_supplier', $filters['search'])
                ->group_end();
        }

        $this->db->group_by('qh.id');
        $this->db->order_by('qh.created_at', 'DESC');
        $this->db->limit($limit, $offset);

        return $this->db->get()->result();
    }

    /**
     * Count total QA headers (for pagination)
     */
    public function count_qa_headers($filters = []) {
        $this->db->reset_query();
        $this->db->select('COUNT(DISTINCT qh.id) as total')
            ->from($this->table_qa_header . ' qh')
            ->join($this->table_grn_header . ' gh', 'qh.grn_header_id = gh.id', 'left');

        if (!empty($filters['status'])) {
            $this->db->where('qh.status', $filters['status']);
        }
        if (!empty($filters['qa_status'])) {
            $qa_status_map = [
                'Pending' => 'Pending',
                'In Progress' => 'In Progress',
                'Passed' => 'Passed',
                'Partial Passed' => 'Partial Passed',
                'Rejected' => 'Rejected'
            ];
            if (isset($qa_status_map[$filters['qa_status']])) {
                $this->db->where('gh.qa_status', $qa_status_map[$filters['qa_status']]);
            }
        }

        $result = $this->db->get()->row();
        return $result->total ?? 0;
    }

    // =====================================================
    // QA DETAILS FUNCTIONS
    // =====================================================

    /**
     * Get QA details by QA header ID
     */
    public function get_qa_details_by_header($qa_header_id) {
        $this->db->reset_query();
        $this->db->select('
            qad.*,
            gd.source_doc_type, gd.source_doc_header_id,
            gd.unit_value
        ');
        $this->db->from($this->table_qa_details . ' qad');
        $this->db->join($this->table_grn_details . ' gd', 'qad.grn_detail_id = gd.id', 'left');
        $this->db->where('qad.qa_header_id', $qa_header_id);
        $this->db->order_by('qad.id', 'ASC');

        return $this->db->get()->result();
    }

    /**
     * Get QA detail by ID
     */
    public function get_qa_detail_by_id($qa_detail_id) {
        $this->db->reset_query();
        $this->db->select('
            qad.*,
            qh.qa_number, qh.status as qa_status_header,
            gd.receive_qty as original_receive_qty
        ');
        $this->db->from($this->table_qa_details . ' qad');
        $this->db->join($this->table_qa_header . ' qh', 'qad.qa_header_id = qh.id', 'left');
        $this->db->join($this->table_grn_details . ' gd', 'qad.grn_detail_id = gd.id', 'left');
        $this->db->where('qad.id', $qa_detail_id);

        return $this->db->get()->row();
    }

    /**
     * Update QA Detail dengan hasil QC
     * $data harus contain: qa_pass_qty, qa_reject_qty, qa_status, checked_by, checked_at
     */
    public function update_qa_detail($qa_detail_id, $data) {
        $this->db->reset_query();
        
        // Validasi qty
        if (!isset($data['qa_pass_qty']) || !isset($data['qa_reject_qty'])) {
            log_message('error', 'QA Detail Update: Missing pass or reject qty');
            return false;
        }

        $qa_detail = $this->get_qa_detail_by_id($qa_detail_id);
        if (!$qa_detail) {
            return false;
        }

        // Validasi total qty tidak boleh lebih dari receive_qty
        $total_qc_qty = (float)$data['qa_pass_qty'] + (float)$data['qa_reject_qty'];
        if ($total_qc_qty > $qa_detail->receive_qty + 0.01) {
            log_message('error', 'QA Detail Update: Total QC qty exceeds receive qty. Detail ID: ' . $qa_detail_id);
            return false;
        }

        // Set status otomatis
        if ($data['qa_reject_qty'] > 0.001) {
            if ($data['qa_pass_qty'] > 0.001) {
                $data['qa_status'] = 'Partial';
            } else {
                $data['qa_status'] = 'Reject';
            }
        } else {
            $data['qa_status'] = 'Pass';
        }

        $data['checked_at'] = date('Y-m-d H:i:s');

        $this->db->where('id', $qa_detail_id)
            ->update($this->table_qa_details, $data);

        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * Add reject attachment untuk QA detail
     */
    public function add_reject_attachment($qa_detail_id, $url, $type = null, $description = null, $uploaded_by = null) {
        $this->db->reset_query();
        
        $attachment_data = [
            'qa_detail_id' => $qa_detail_id,
            'attachment_url' => $url,
            'attachment_type' => $type,
            'attachment_description' => $description,
            'uploaded_by' => $uploaded_by
        ];

        $this->db->insert($this->table_qa_attachments, $attachment_data);
        return $this->db->insert_id();
    }

    /**
     * Get reject attachments untuk QA detail
     */
    public function get_reject_attachments($qa_detail_id) {
        $this->db->reset_query();
        $this->db->select('
            qra.*,
            u.fullname as uploaded_by_name
        ');
        $this->db->from($this->table_qa_attachments . ' qra');
        $this->db->join('users u', 'qra.uploaded_by = u.id', 'left');
        $this->db->where('qa_detail_id', $qa_detail_id);
        $this->db->order_by('qra.uploaded_at', 'DESC');

        return $this->db->get()->result();
    }

    /**
     * Delete attachment
     */
    public function delete_attachment($attachment_id) {
        $this->db->reset_query();
        return $this->db->where('id', $attachment_id)
            ->delete($this->table_qa_attachments) ? true : false;
    }

    // =====================================================
    // QA COMPLETION & STOCK INTEGRATION
    // =====================================================

    /**
     * Complete QA process dan update stock
     * Hanya barang dengan qa_status = 'Pass' atau 'Partial' (pass qty > 0) yang masuk stock
     */
    public function complete_qa($qa_header_id, $user_id = null) {
        $this->db->trans_start();

        try {
            $qa_header = $this->get_qa_header_by_id($qa_header_id);
            if (!$qa_header) {
                throw new Exception('QA Header not found');
            }

            // QC selesai → status 'Draft' (menunggu approval atasan).
            // GRN.qa_status & stock-in akan di-set saat approve_qa.
            $this->db->where('id', $qa_header_id)
                ->update($this->table_qa_header, [
                    'status' => 'Draft',
                    'qa_checked_by' => $user_id,
                    'qa_checked_at' => date('Y-m-d H:i:s')
                ]);

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                log_message('error', 'QA Complete: Transaction failed for QA ID: ' . $qa_header_id);
                return false;
            }

            log_message('info', 'QA Complete: QA ' . $qa_header->qa_number . ' moved to Draft, awaiting approval');
            return true;

        } catch (Exception $e) {
            $this->db->trans_complete();
            log_message('error', 'QA Complete Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate overall QA status (Passed / Partial Passed / Rejected) from qa_details.
     */
    public function calculate_overall_qa_status($qa_header_id) {
        $qa_details = $this->get_qa_details_by_header($qa_header_id);

        $has_pass = false;
        $has_reject = false;

        foreach ($qa_details as $detail) {
            if ($detail->qa_status === 'Reject') {
                $has_reject = true;
            } else if ($detail->qa_status === 'Pass' || $detail->qa_status === 'Partial') {
                $has_pass = true;
                if ($detail->qa_status === 'Partial') {
                    $has_reject = true;
                }
            }
        }

        if ($has_reject && !$has_pass) return 'Rejected';
        if ($has_reject && $has_pass)  return 'Partial Passed';
        return 'Passed';
    }

    /**
     * Approve QA (dari atasan): status Draft → Completed, set GRN.qa_status.
     * Stock-in dilakukan terpisah oleh controller via Warehouse_in_model::add_stock_from_qa.
     */
    public function approve_qa_header($qa_header_id) {
        $this->db->trans_start();

        try {
            $qa_header = $this->get_qa_header_by_id($qa_header_id);
            if (!$qa_header || $qa_header->status !== 'Draft') {
                throw new Exception('QA cannot be approved (must be in Draft status)');
            }

            $overall_qa_status = $this->calculate_overall_qa_status($qa_header_id);

            $this->db->where('id', $qa_header_id)
                ->update($this->table_qa_header, [
                    'status' => 'Completed'
                ]);

            $this->db->where('id', $qa_header->grn_header_id)
                ->update($this->table_grn_header, [
                    'qa_status' => $overall_qa_status
                ]);

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                log_message('error', 'QA Approve: Transaction failed for QA ID: ' . $qa_header_id);
                return false;
            }

            log_message('info', 'QA Approved: ' . $qa_header->qa_number . ' status set to Completed (' . $overall_qa_status . ')');
            return true;

        } catch (Exception $e) {
            $this->db->trans_complete();
            log_message('error', 'QA Approve Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Unapprove QA (dari atasan): status Completed → Draft.
     * Stock ledger entries + SOH batches dari approve sebelumnya dihapus.
     * BLOCK kalau stock sudah terpakai (available_qty < initial_qty) untuk cegah corruption.
     *
     * Return: ['ok' => bool, 'message' => string]
     */
    public function unapprove_qa($qa_header_id) {
        $qa_header = $this->get_qa_header_by_id($qa_header_id);
        if (!$qa_header || $qa_header->status !== 'Completed') {
            return ['ok' => false, 'message' => 'QA harus berstatus Completed untuk di-unapprove'];
        }

        // Cek dulu: ada SOH batch dari GRN ini yang sudah terpakai?
        $used_batches = $this->db
            ->select('id, item_id, item_type, initial_qty, available_qty')
            ->from('stock_on_hand')
            ->where('grn_header_id', $qa_header->grn_header_id)
            ->where('available_qty < initial_qty', null, false)
            ->get()->result();

        if (!empty($used_batches)) {
            return [
                'ok' => false,
                'message' => 'Tidak bisa unapprove: stok dari QA ini sudah terpakai di transaksi lain (' . count($used_batches) . ' batch). Lakukan adjustment manual jika perlu.'
            ];
        }

        $this->db->trans_start();

        try {
            // Reuse logic existing: delete SOH yang belum terpakai + delete ledger 'in' by transaction_id.
            $this->load->model('Warehouse_in_model');
            $removed = $this->Warehouse_in_model->remove_stock_from_ledger_and_batch($qa_header->grn_header_id);
            if (!$removed) {
                throw new Exception('Gagal hapus stock ledger / SOH batch');
            }

            // Revert status QA Header ke Draft + clear GRN.qa_status
            $this->db->where('id', $qa_header_id)
                ->update($this->table_qa_header, [
                    'status' => 'Draft'
                ]);

            $this->db->where('id', $qa_header->grn_header_id)
                ->update($this->table_grn_header, [
                    'qa_status' => null
                ]);

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                log_message('error', 'QA Unapprove: Transaction failed for QA ID: ' . $qa_header_id);
                return ['ok' => false, 'message' => 'Transaction gagal'];
            }

            log_message('info', 'QA Unapproved: ' . $qa_header->qa_number . ' reverted to Draft, stock ledger cleaned');
            return ['ok' => true, 'message' => 'QA berhasil di-unapprove'];

        } catch (Exception $e) {
            $this->db->trans_complete();
            log_message('error', 'QA Unapprove Error: ' . $e->getMessage());
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Final reject QA (dari atasan): status Draft → Rejected.
     * qa_details TIDAK di-overwrite (data QC asli tersimpan untuk audit & recovery).
     * Stock tidak masuk ledger karena tidak pernah disetujui.
     */
    public function final_reject_qa($qa_header_id) {
        $this->db->trans_start();

        try {
            $qa_header = $this->get_qa_header_by_id($qa_header_id);
            if (!$qa_header || $qa_header->status !== 'Draft') {
                throw new Exception('QA cannot be rejected (must be in Draft status)');
            }

            $this->db->where('id', $qa_header_id)
                ->update($this->table_qa_header, [
                    'status' => 'Rejected'
                ]);

            $this->db->where('id', $qa_header->grn_header_id)
                ->update($this->table_grn_header, [
                    'qa_status' => 'Rejected'
                ]);

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                log_message('error', 'QA Reject: Transaction failed for QA ID: ' . $qa_header_id);
                return false;
            }

            log_message('info', 'QA Rejected (final): ' . $qa_header->qa_number);
            return true;

        } catch (Exception $e) {
            $this->db->trans_complete();
            log_message('error', 'QA Reject Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reopen QA untuk re-check.
     * - Dari 'Draft'    → reset qa_details, status balik ke 'In Progress' (re-QC dari awal)
     * - Dari 'Rejected' → recovery salah klik, status balik ke 'Draft' tanpa reset details
     * Stock-in belum pernah terjadi di Draft/Rejected, jadi tidak ada cleanup stock yang perlu.
     */
    public function reopen_qa($qa_header_id) {
        $this->db->trans_start();

        try {
            $qa_header = $this->get_qa_header_by_id($qa_header_id);
            if (!$qa_header || !in_array($qa_header->status, ['Draft', 'Rejected'])) {
                throw new Exception('QA cannot be reopened (must be Draft or Rejected)');
            }

            if ($qa_header->status === 'Rejected') {
                // Recovery dari final reject: balik ke Draft, tidak reset details.
                $this->db->where('id', $qa_header_id)
                    ->update($this->table_qa_header, [
                        'status' => 'Draft'
                    ]);

                // Bersihkan GRN.qa_status (akan di-set ulang saat approve)
                $this->db->where('id', $qa_header->grn_header_id)
                    ->update($this->table_grn_header, [
                        'qa_status' => null
                    ]);

                log_message('info', 'QA Reopened (Rejected→Draft): ' . $qa_header->qa_number);
            } else {
                // Dari Draft → reset semua hasil QC, status In Progress untuk re-QC.
                $this->db->where('id', $qa_header_id)
                    ->update($this->table_qa_header, [
                        'status' => 'In Progress',
                        'qa_checked_by' => null,
                        'qa_checked_at' => null
                    ]);

                $this->db->where('qa_header_id', $qa_header_id)
                    ->update($this->table_qa_details, [
                        'qa_pass_qty' => 0,
                        'qa_reject_qty' => 0,
                        'qa_status' => 'Pending',
                        'checked_by' => null,
                        'checked_at' => null,
                        'reject_reason' => null,
                        'reject_description' => null
                    ]);

                log_message('info', 'QA Reopened (Draft→In Progress): ' . $qa_header->qa_number);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                log_message('error', 'QA Reopen Transaction Failed for QA ID: ' . $qa_header_id);
                return false;
            }

            return true;

        } catch (Exception $e) {
            $this->db->trans_complete();
            log_message('error', 'QA Reopen Error: ' . $e->getMessage());
            return false;
        }
    }

    // =====================================================
    // QA APPROVAL FUNCTIONS
    // =====================================================

    /**
     * Add approval/review log
     */
    public function add_approval_log($qa_header_id, $approver_id, $status = 'Pending Review', $notes = null) {
        $this->db->reset_query();
        
        $approval_data = [
            'qa_header_id' => $qa_header_id,
            'approver_id' => $approver_id,
            'approval_status' => $status,
            'approval_notes' => $notes,
            'approval_date' => date('Y-m-d H:i:s')
        ];

        $this->db->insert($this->table_qa_approval, $approval_data);
        return $this->db->insert_id();
    }

    /**
     * Get approval logs untuk QA header
     */
    public function get_approval_logs($qa_header_id) {
        $this->db->reset_query();
        $this->db->select('
            qal.*,
            u.fullname as approver_name
        ');
        $this->db->from($this->table_qa_approval . ' qal');
        $this->db->join('users u', 'qal.approver_id = u.id', 'left');
        $this->db->where('qa_header_id', $qa_header_id);
        $this->db->order_by('qal.approval_date', 'DESC');

        return $this->db->get()->result();
    }

    /**
     * Get latest approval untuk QA header
     */
    public function get_latest_approval($qa_header_id) {
        $this->db->reset_query();
        $this->db->select('*')
            ->from($this->table_qa_approval)
            ->where('qa_header_id', $qa_header_id)
            ->order_by('approval_date', 'DESC')
            ->limit(1);

        return $this->db->get()->row();
    }

    // =====================================================
    // QA REPORT FUNCTIONS
    // =====================================================

    /**
     * Get supplier rejection report data
     * Menampilkan ringkasan reject per supplier dengan automatic supplier detection
     * Cari supplier dari multiple sources: grn.supplier_id, PO, MIR, atau Warehouse Out
     */
    public function get_supplier_rejection_report($filters = []) {
        $this->db->reset_query();

        // Smart supplier detection: coba dari grn_header dulu, lalu PO, lalu MIR, lalu WO
        $this->db->select('
            COALESCE(
                NULLIF(s_grn.id, 0),
                NULLIF(s_po.id, 0),
                NULLIF(s_mir.id, 0),
                NULLIF(s_wo.id, 0),
                0
            ) as supplier_id,
            COALESCE(
                NULLIF(s_grn.nama_supplier, ""),
                NULLIF(s_po.nama_supplier, ""),
                NULLIF(s_mir.nama_supplier, ""),
                NULLIF(s_wo.nama_supplier, ""),
                NULLIF(wo.nama_supplier, ""),
                "Unknown"
            ) as supplier_name,
            COUNT(DISTINCT qh.id) as total_qa,
            COUNT(DISTINCT qad.id) as total_items,
            SUM(CASE WHEN qad.qa_status IN ("Reject", "Partial") THEN 1 ELSE 0 END) as total_reject_items,
            SUM(CASE WHEN qad.qa_status IN ("Reject", "Partial") THEN qad.qa_reject_qty ELSE 0 END) as total_reject_qty,
            ROUND(SUM(CASE WHEN qad.qa_status IN ("Reject", "Partial") THEN qad.qa_reject_qty ELSE 0 END) /
                  NULLIF(SUM(qad.receive_qty), 0) * 100, 2) as reject_percentage
        ', FALSE);

        $this->db->from($this->table_qa_header . ' qh');
        $this->db->join($this->table_grn_header . ' gh', 'qh.grn_header_id = gh.id', 'left');

        // Supplier dari GRN header (Priority 1)
        $this->db->join('suppliers s_grn', 'gh.supplier_id = s_grn.id AND gh.supplier_id > 0', 'left');

        // Supplier dari PO (Priority 2)
        $this->db->join('grn_details gd_po', 'gh.id = gd_po.grn_header_id AND gd_po.source_doc_type = \'PO\'', 'left');
        $this->db->join('po_header po', 'gd_po.source_doc_header_id = po.id', 'left');
        $this->db->join('suppliers s_po', 'po.supplier_id = s_po.id AND s_grn.id IS NULL', 'left');

        // Supplier dari MIR (Priority 3)
        $this->db->join('grn_details gd_mir', 'gh.id = gd_mir.grn_header_id AND gd_mir.source_doc_type = \'MIR\'', 'left');
        $this->db->join('mir_header mh', 'gd_mir.source_doc_header_id = mh.id AND s_grn.id IS NULL AND s_po.id IS NULL', 'left');
        $this->db->join('suppliers s_mir', 'mh.supplier_id = s_mir.id', 'left');

        // Supplier dari Warehouse Out (Priority 4)
        $this->db->join('grn_details gd_wo', 'gh.id = gd_wo.grn_header_id AND gd_wo.source_doc_type = \'WO\'', 'left');
        $this->db->join('warehouse_out_header wo', 'gd_wo.source_doc_header_id = wo.id AND s_grn.id IS NULL AND s_po.id IS NULL AND s_mir.id IS NULL', 'left');
        $this->db->join('suppliers s_wo', 'wo.supplier_id = s_wo.id', 'left');

        $this->db->join($this->table_qa_details . ' qad', 'qh.id = qad.qa_header_id', 'left');

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $this->db->where('DATE(qh.qa_date) >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('DATE(qh.qa_date) <=', $filters['date_to']);
        }

        // Filter by supplier
        if (!empty($filters['supplier_id'])) {
            $this->db->group_start()
                ->where('s_grn.id', $filters['supplier_id'])
                ->or_where('s_po.id', $filters['supplier_id'])
                ->or_where('s_mir.id', $filters['supplier_id'])
                ->or_where('s_wo.id', $filters['supplier_id'])
                ->or_where('gh.supplier_id', $filters['supplier_id'])
                ->group_end();
        }

        // Filter by QA header status - ONLY filter if explicitly set (untuk control flow, tapi tidak filter reject items)
        // Reject items harus ditampilkan regardless of header status
        // Jika user tidak specify status filter, show all reject items dari semua QA status
        if (!empty($filters['qa_status']) && $filters['qa_status'] !== '') {
            $this->db->where('qh.status', $filters['qa_status']);
        }

        // Group by unique supplier ID
        $this->db->group_by('COALESCE(NULLIF(s_grn.id, 0), NULLIF(s_po.id, 0), NULLIF(s_mir.id, 0), NULLIF(s_wo.id, 0), 0)');

        $this->db->order_by('total_reject_items', 'DESC');

        $results = $this->db->get()->result();

        // Filter suppliers yang punya reject/partial items (total_reject_items > 0)
        $results = array_filter($results, function($row) {
            return ($row->total_reject_items > 0);
        });

        // Apply reject percentage filter if needed
        if (!empty($filters['min_reject_percentage'])) {
            $results = array_filter($results, function($row) use ($filters) {
                return ($row->reject_percentage >= $filters['min_reject_percentage']);
            });
        }

        return $results;
    }

    /**
     * Get detail reject items per supplier (untuk view detail)
     * Smart supplier detection dari multiple sources (PO, MIR, WO)
     */
    public function get_supplier_reject_details($supplier_id, $filters = []) {
        if (empty($supplier_id) || $supplier_id == 0) {
            return [];
        }

        $this->db->reset_query();
        $this->db->select('
            qh.qa_number,
            qh.qa_date,
            gh.grn_number,
            COALESCE(po.po_number, mir.mir_number, wo.out_number, "-") as source_doc_number,
            COALESCE(po.id, mir.id, wo.id) as source_doc_id,
            qad.id as qa_detail_id,
            qad.qa_header_id,
            qad.receive_qty,
            qad.qa_pass_qty,
            qad.qa_reject_qty,
            qad.reject_reason,
            qad.reject_description,
            qad.qa_status,
            qad.item_id,
            qad.item_type
        ', FALSE);

        $this->db->from($this->table_qa_header . ' qh');
        $this->db->join($this->table_grn_header . ' gh', 'qh.grn_header_id = gh.id', 'left');

        // Supplier dari GRN header
        $this->db->join('suppliers s_grn', 'gh.supplier_id = s_grn.id AND gh.supplier_id > 0', 'left');

        // Supplier & PO Number
        $this->db->join('grn_details gd_po', 'gh.id = gd_po.grn_header_id AND gd_po.source_doc_type = \'PO\'', 'left');
        $this->db->join('po_header po', 'gd_po.source_doc_header_id = po.id', 'left');
        $this->db->join('suppliers s_po', 'po.supplier_id = s_po.id AND s_grn.id IS NULL', 'left');

        // Supplier & MIR Number
        $this->db->join('grn_details gd_mir', 'gh.id = gd_mir.grn_header_id AND gd_mir.source_doc_type = \'MIR\'', 'left');
        $this->db->join('mir_header mir', 'gd_mir.source_doc_header_id = mir.id AND s_grn.id IS NULL AND s_po.id IS NULL', 'left');
        $this->db->join('suppliers s_mir', 'mir.supplier_id = s_mir.id', 'left');

        // Supplier & Warehouse Out Number
        $this->db->join('grn_details gd_wo', 'gh.id = gd_wo.grn_header_id AND gd_wo.source_doc_type = \'WO\'', 'left');
        $this->db->join('warehouse_out_header wo', 'gd_wo.source_doc_header_id = wo.id AND s_grn.id IS NULL AND s_po.id IS NULL AND s_mir.id IS NULL', 'left');
        $this->db->join('suppliers s_wo', 'wo.supplier_id = s_wo.id', 'left');

        $this->db->join($this->table_qa_details . ' qad', 'qh.id = qad.qa_header_id', 'left');

        // Filter by supplier (check all possible sources)
        $this->db->group_start()
            ->where('s_grn.id', $supplier_id)
            ->or_where('s_po.id', $supplier_id)
            ->or_where('s_mir.id', $supplier_id)
            ->or_where('s_wo.id', $supplier_id)
            ->or_where('gh.supplier_id', $supplier_id)
            ->group_end();

        // Filter reject items (both Reject and Partial status yang punya reject qty)
        $this->db->where_in('qad.qa_status', ['Reject', 'Partial']);

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $this->db->where('DATE(qh.qa_date) >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('DATE(qh.qa_date) <=', $filters['date_to']);
        }

        // Search
        if (!empty($filters['search'])) {
            $this->db->group_start()
                ->like('qh.qa_number', $filters['search'])
                ->or_like('gh.grn_number', $filters['search'])
                ->group_end();
        }

        $this->db->order_by('qh.qa_date', 'DESC');
        $this->db->order_by('qh.qa_number', 'DESC');

        return $this->db->get()->result();
    }

}
