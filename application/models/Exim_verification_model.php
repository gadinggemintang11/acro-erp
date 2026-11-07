<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Exim_verification_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get list GRN untuk verification (Pemasukan)
     */
    public function get_incoming_list($filters = []) {
        $this->db->select('
            grn.id,
            grn.grn_number,
            grn.grn_date,
            grn.base_on,
            grn.bc_type_code,
            grn.bc_number,
            grn.bc_date,
            grn.status,
            grn.exim_verified,
            grn.exim_verified_at,
            COALESCE(s_grn.nama_supplier, s_po.nama_supplier, s_mir.nama_supplier, "-") as nama_supplier,
            u.fullname as verified_by_name,
            COUNT(DISTINCT grnd.id) as num_items,
            SUM(grnd.receive_qty) as total_qty
        ', FALSE);
        $this->db->from('grn_header grn');
        // JOIN supplier dari grn_header.supplier_id (paling reliable — di-set saat
        // Goods Return load atau add_item dari PO/MIR; cover semua base_on termasuk
        // Goods Return dari NG QA & Production Return).
        $this->db->join('suppliers s_grn', 'grn.supplier_id = s_grn.id', 'left');
        // JOIN untuk items details
        $this->db->join('grn_details grnd', 'grn.id = grnd.grn_header_id', 'left');
        // Fallback: supplier PO (jika base_on = PO dan grn_header.supplier_id null)
        $this->db->join('po_header ph', 'grnd.source_doc_type = "PO" AND grnd.source_doc_header_id = ph.id', 'left');
        $this->db->join('suppliers s_po', 'ph.supplier_id = s_po.id', 'left');
        // Fallback: supplier MIR (jika base_on = MIR dan grn_header.supplier_id null)
        $this->db->join('mir_header mh', 'grnd.source_doc_type = "MIR" AND grnd.source_doc_header_id = mh.id', 'left');
        $this->db->join('suppliers s_mir', 'mh.supplier_id = s_mir.id', 'left');
        // JOIN untuk verified_by user
        $this->db->join('users u', 'grn.exim_verified_by = u.id', 'left');

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $this->db->where('grn.grn_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('grn.grn_date <=', $filters['date_to'] . ' 23:59:59');
        }

        // Filter by supplier
        if (!empty($filters['supplier_id'])) {
            $this->db->where('grn.supplier_id', $filters['supplier_id']);
        }

        // Filter by BC type
        if (!empty($filters['bc_type'])) {
            $this->db->where('grn.bc_type_code', $filters['bc_type']);
        }

        // Filter by verification status
        if (isset($filters['verified_status'])) {
            if ($filters['verified_status'] === 'verified') {
                $this->db->where('grn.exim_verified', 1);
            } elseif ($filters['verified_status'] === 'unverified') {
                $this->db->where('grn.exim_verified', 0);
            }
            // 'all' = no filter
        }

        // Only show Posted/Completed GRN
        $this->db->where_in('grn.status', ['Posted', 'Completed']);

        $this->db->group_by('grn.id');
        $this->db->order_by('grn.grn_date', 'DESC');

        return $this->db->get()->result();
    }

    /**
     * Get list pengeluaran untuk verification (FG Out, Scrap Out)
     */
    public function get_outgoing_list($filters = []) {
        $results = [];

        // Get FG Delivery (if not filtered by doc_type or doc_type = FG_OUT)
        if (empty($filters['doc_type']) || $filters['doc_type'] === 'FG_OUT') {
            $this->db->select('
                fgd.id,
                fgd.delivery_number as doc_number,
                fgd.delivery_date as doc_date,
                fgd.bc_type_id,
                cc.description as bc_type_description,
                fgd.bc_number,
                fgd.bc_date,
                fgd.status,
                fgd.exim_verified,
                fgd.exim_verified_at,
                c.nama_customer as customer_name,
                u.fullname as verified_by_name,
                "FG_OUT" as doc_type,
                COUNT(DISTINCT fgdd.id) as num_items,
                SUM(fgdd.qty_shipped) as total_qty
            ');
            $this->db->from('fg_delivery_header fgd');
            $this->db->join('customers c', 'fgd.customer_id = c.id', 'left');
            $this->db->join('fg_delivery_details fgdd', 'fgd.id = fgdd.fg_delivery_header_id', 'left');
            $this->db->join('common_code cc', 'cc.group_code = "BC_TYPE" AND cc.code = fgd.bc_type_id', 'left');
            $this->db->join('users u', 'fgd.exim_verified_by = u.id', 'left');

            // Apply filters
            if (!empty($filters['date_from'])) {
                $this->db->where('fgd.delivery_date >=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $this->db->where('fgd.delivery_date <=', $filters['date_to'] . ' 23:59:59');
            }
            if (!empty($filters['bc_type'])) {
                $this->db->where('fgd.bc_type_id', $filters['bc_type']);
            }
            if (isset($filters['verified_status'])) {
                if ($filters['verified_status'] === 'verified') {
                    $this->db->where('fgd.exim_verified', 1);
                } elseif ($filters['verified_status'] === 'unverified') {
                    $this->db->where('fgd.exim_verified', 0);
                }
            }

            // Filter by status - include Confirmed status (not just Posted/Completed)
            $this->db->where_in('fgd.status', ['Posted', 'Completed', 'Confirmed']);
            $this->db->group_by('fgd.id');

            $fg_results = $this->db->get()->result();
            $results = array_merge($results, $fg_results);
        }

        // Get Scrap Out (if not filtered by doc_type or doc_type = SCRAP_OUT)
        if (empty($filters['doc_type']) || $filters['doc_type'] === 'SCRAP_OUT') {
            $this->db->select('
                so.id,
                so.doc_no as doc_number,
                so.doc_date as doc_date,
                so.bc_type_code as bc_type_id,
                cc2.description as bc_type_description,
                so.bc_number,
                so.bc_date,
                so.status,
                so.exim_verified,
                so.exim_verified_at,
                c.nama_customer as customer_name,
                u.fullname as verified_by_name,
                "SCRAP_OUT" as doc_type,
                COUNT(DISTINCT sod.id) as num_items,
                SUM(sod.qty) as total_qty
            ');
            $this->db->from('scrap_out_header so');
            $this->db->join('scrap_out_detail sod', 'so.id = sod.header_id', 'left');
            $this->db->join('common_code cc2', 'cc2.group_code = "BC_TYPE" AND cc2.code = so.bc_type_code', 'left');
            $this->db->join('customers c', 'so.destination = c.id', 'left');
            $this->db->join('users u', 'so.exim_verified_by = u.id', 'left');

            // Apply filters
            if (!empty($filters['date_from'])) {
                $this->db->where('so.doc_date >=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $this->db->where('so.doc_date <=', $filters['date_to'] . ' 23:59:59');
            }
            if (!empty($filters['bc_type'])) {
                $this->db->where('so.bc_type_code', $filters['bc_type']);
            }
            if (isset($filters['verified_status'])) {
                if ($filters['verified_status'] === 'verified') {
                    $this->db->where('so.exim_verified', 1);
                } elseif ($filters['verified_status'] === 'unverified') {
                    $this->db->where('so.exim_verified', 0);
                }
            }

            // Filter by status - include Confirmed status
            $this->db->where_in('so.status', ['Posted', 'Completed', 'Confirmed']);
            $this->db->group_by('so.id');

            $scrap_results = $this->db->get()->result();
            $results = array_merge($results, $scrap_results);
        }

        // Get Subkon Out (if not filtered by doc_type or doc_type = SUBKON_OUT)
        if (empty($filters['doc_type']) || $filters['doc_type'] === 'SUBKON_OUT') {
            $this->db->select('
                sub.id,
                sub.subkon_out_no as doc_number,
                sub.shipping_date as doc_date,
                sub.bc_type_code as bc_type_id,
                sub.bc_number,
                sub.bc_date,
                sub.status,
                sub.exim_verified,
                sub.exim_verified_at,
                s.nama_supplier as customer_name,
                u.fullname as verified_by_name,
                "SUBKON_OUT" as doc_type,
                COUNT(DISTINCT subd.id) as num_items,
                SUM(subd.qty_kirim) as total_qty
            ');
            $this->db->from('subkon_out_header sub');
            $this->db->join('suppliers s', 'sub.supplier_id = s.id', 'left');
            $this->db->join('subkon_out_details subd', 'sub.id = subd.subkon_out_id', 'left');
            $this->db->join('users u', 'sub.exim_verified_by = u.id', 'left');

            // Apply filters
            if (!empty($filters['date_from'])) {
                $this->db->where('sub.shipping_date >=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $this->db->where('sub.shipping_date <=', $filters['date_to'] . ' 23:59:59');
            }
            if (!empty($filters['bc_type'])) {
                $this->db->where('sub.bc_type_code', $filters['bc_type']);
            }
            if (isset($filters['verified_status'])) {
                if ($filters['verified_status'] === 'verified') {
                    $this->db->where('sub.exim_verified', 1);
                } elseif ($filters['verified_status'] === 'unverified') {
                    $this->db->where('sub.exim_verified', 0);
                }
            }

            // Filter by status - include Confirmed status
            $this->db->where_in('sub.status', ['Posted', 'Completed', 'Confirmed']);
            $this->db->group_by('sub.id');

            $subkon_results = $this->db->get()->result();
            $results = array_merge($results, $subkon_results);
        }

        // Get RTN Supplier (warehouse_out) if not filtered or explicitly requested
        if (empty($filters['doc_type']) || $filters['doc_type'] === 'RTN_SUPPLIER') {
            $this->db->select(
                'wh.id, '
                . 'wh.out_number as doc_number, '
                . 'wh.out_date as doc_date, '
                . 'wh.bc_type_code as bc_type_id, '
                . 'cc.description as bc_type_description, '
                . 'wh.bc_number, '
                . 'wh.bc_date, '
                . 'wh.status, '
                . 'wh.exim_verified, '
                . 'wh.exim_verified_at, '
                . 'COALESCE(sup.nama_supplier, wh.nama_supplier, sup_gh.nama_supplier, "") as customer_name, '
                . 'u.fullname as verified_by_name, '
                . '"RTN_SUPPLIER" as doc_type, '
                . 'COUNT(DISTINCT wod.id) as num_items, '
                . 'SUM(wod.qty_return) as total_qty'
            , FALSE);

            $this->db->from('warehouse_out_header wh');
            $this->db->join('warehouse_out_details wod', 'wh.id = wod.header_id', 'left');
            // supplier lookup from header
            $this->db->join('suppliers sup', 'wh.supplier_id = sup.id', 'left');
            // join grn header from details as fallback for supplier
            $this->db->join('grn_header gh', 'wod.grn_id = gh.id', 'left');
            $this->db->join('suppliers sup_gh', 'gh.supplier_id = sup_gh.id', 'left');
            $this->db->join('common_code cc', 'cc.group_code = "BC_TYPE" AND cc.code = wh.bc_type_code', 'left');
            $this->db->join('users u', 'wh.exim_verified_by = u.id', 'left');

            if (!empty($filters['date_from'])) {
                $this->db->where('wh.out_date >=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $this->db->where('wh.out_date <=', $filters['date_to'] . ' 23:59:59');
            }
            if (!empty($filters['bc_type'])) {
                $this->db->where('wh.bc_type_code', $filters['bc_type']);
            }
            if (isset($filters['verified_status'])) {
                if ($filters['verified_status'] === 'verified') {
                    $this->db->where('wh.exim_verified', 1);
                } elseif ($filters['verified_status'] === 'unverified') {
                    $this->db->where('wh.exim_verified', 0);
                }
            }

            // Consider Confirmed statuses as eligible
            $this->db->where_in('wh.status', ['Posted', 'Completed', 'Confirmed']);
            $this->db->group_by('wh.id');

            $rtn_results = $this->db->get()->result();
            $results = array_merge($results, $rtn_results);
        }

        // Sort by date descending
        usort($results, function($a, $b) {
            return strtotime($b->doc_date) - strtotime($a->doc_date);
        });

        return $results;
    }

    /**
     * Verify GRN
     */
    public function verify_grn($grn_id, $user_id) {
        $data = [
            'exim_verified' => 1,
            'exim_verified_by' => $user_id,
            'exim_verified_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id', $grn_id);
        return $this->db->update('grn_header', $data);
    }

    /**
     * Unverify GRN
     */
    public function unverify_grn($grn_id) {
        $data = [
            'exim_verified' => 0,
            'exim_verified_by' => NULL,
            'exim_verified_at' => NULL
        ];

        $this->db->where('id', $grn_id);
        return $this->db->update('grn_header', $data);
    }

    /**
     * Verify Outgoing document (FG Out or Scrap Out)
     */
    public function verify_outgoing($doc_type, $doc_id, $user_id) {
        $data = [
            'exim_verified' => 1,
            'exim_verified_by' => $user_id,
            'exim_verified_at' => date('Y-m-d H:i:s')
        ];

        if ($doc_type === 'FG_OUT') {
            $this->db->where('id', $doc_id);
            return $this->db->update('fg_delivery_header', $data);
        } elseif ($doc_type === 'SCRAP_OUT') {
            $this->db->where('id', $doc_id);
            return $this->db->update('scrap_out_header', $data);
        } elseif ($doc_type === 'RTN_SUPPLIER') {
            $this->db->where('id', $doc_id);
            return $this->db->update('warehouse_out_header', $data);
        }

        return false;
    }

    /**
     * Unverify Outgoing document
     */
    public function unverify_outgoing($doc_type, $doc_id) {
        $data = [
            'exim_verified' => 0,
            'exim_verified_by' => NULL,
            'exim_verified_at' => NULL
        ];

        if ($doc_type === 'FG_OUT') {
            $this->db->where('id', $doc_id);
            return $this->db->update('fg_delivery_header', $data);
        } elseif ($doc_type === 'SCRAP_OUT') {
            $this->db->where('id', $doc_id);
            return $this->db->update('scrap_out_header', $data);
        } elseif ($doc_type === 'RTN_SUPPLIER') {
            $this->db->where('id', $doc_id);
            return $this->db->update('warehouse_out_header', $data);
        }

        return false;
    }

    /**
     * Get suppliers for filter dropdown
     */
    public function get_suppliers() {
        $this->db->select('id, nama_supplier');
        $this->db->from('suppliers');
        $this->db->order_by('nama_supplier', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get BC types for filter dropdown
     */
    public function get_bc_types() {
        $this->db->select('code, description');
        $this->db->from('common_code');
        $this->db->where('group_code', 'BC_TYPE');
        $this->db->order_by('code', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get GRN detail items
     */
    public function get_grn_details($grn_id) {
        $this->db->select("
            grnd.id,
            grnd.receive_qty,
            grnd.unit_value,
            grnd.item_type,
            CASE 
                WHEN grnd.item_type = 'material' THEN m.kode_material
                WHEN grnd.item_type = 'semifinished' THEN sf.kode_barang
                WHEN grnd.item_type = 'machine' THEN mc.kode_mesin
                WHEN grnd.item_type = 'inventory' THEN inv.kode_barang
                ELSE NULL
            END AS item_code,
            CASE 
                WHEN grnd.item_type = 'material' THEN m.nama_material
                WHEN grnd.item_type = 'semifinished' THEN sf.nama_barang
                WHEN grnd.item_type = 'machine' THEN mc.nama_mesin
                WHEN grnd.item_type = 'inventory' THEN inv.nama_barang
                ELSE NULL
            END AS item_name,
            CASE 
                WHEN grnd.item_type = 'material' THEN m.satuan
                WHEN grnd.item_type = 'semifinished' THEN sf.satuan
                WHEN grnd.item_type = 'machine' THEN mc.satuan
                WHEN grnd.item_type = 'inventory' THEN inv.satuan
                ELSE NULL
            END AS item_unit
        ", FALSE);
        $this->db->from('grn_details grnd');
        $this->db->join('materials m', "grnd.item_id = m.id AND grnd.item_type = 'material'", 'left');
        $this->db->join('semifinished_goods sf', "grnd.item_id = sf.id AND grnd.item_type = 'semifinished'", 'left');
        $this->db->join('machines mc', "grnd.item_id = mc.id AND grnd.item_type = 'machine'", 'left');
        $this->db->join('inventories inv', "grnd.item_id = inv.id AND grnd.item_type = 'inventory'", 'left');
        $this->db->where('grnd.grn_header_id', $grn_id);
        $this->db->order_by('grnd.id', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get FG Delivery detail items
     */
    public function get_fg_delivery_details($delivery_id) {
        $this->db->select('
            fgdd.id,
            fgdd.qty_shipped,
            fg.kode_barang as item_code,
            fg.nama_barang as item_name,
            fg.satuan as item_unit,
            sc.sc_number as contract_number,
            sc.unit_price,
            sc.currency_code
        ');
        $this->db->from('fg_delivery_details fgdd');
        $this->db->join('finished_goods fg', 'fgdd.fg_id = fg.id', 'left');
        $this->db->join('sales_contracts sc', 'fgdd.sales_contract_id = sc.id', 'left');
        $this->db->where('fgdd.fg_delivery_header_id', $delivery_id);
        $this->db->order_by('fgdd.id', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get Scrap Out detail items
     */
    public function get_scrap_out_details($header_id) {
        $this->db->select('
            sod.id,
            sod.qty,
            sod.sales_value_per_unit,
            scr.scrap_code as item_code,
            scr.scrap_name as item_name,
            scr.unit as item_unit
        ');
        $this->db->from('scrap_out_detail sod');
        $this->db->join('master_scrap scr', 'sod.scrap_id = scr.id', 'left');
        $this->db->where('sod.header_id', $header_id);
        $this->db->order_by('sod.id', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get Subkon Out detail items
     */
    public function get_subkon_out_details($header_id) {
        $this->db->select("
            sod.id,
            sod.qty_kirim as qty,
            CASE 
                WHEN sod.item_type = 'material' THEN m.kode_material
                WHEN sod.item_type = 'semifinished' THEN sf.kode_barang
                WHEN sod.item_type = 'finished' THEN fg.kode_barang
                ELSE NULL
            END AS item_code,
            CASE 
                WHEN sod.item_type = 'material' THEN m.nama_material
                WHEN sod.item_type = 'semifinished' THEN sf.nama_barang
                WHEN sod.item_type = 'finished' THEN fg.nama_barang
                ELSE NULL
            END AS item_name,
            CASE 
                WHEN sod.item_type = 'material' THEN m.satuan
                WHEN sod.item_type = 'semifinished' THEN sf.satuan
                WHEN sod.item_type = 'finished' THEN fg.satuan
                ELSE NULL
            END AS item_unit,
            sod.item_type
        ", FALSE);
        $this->db->from('subkon_out_details sod');
        $this->db->join('materials m', "sod.item_id = m.id AND sod.item_type = 'material'", 'left');
        $this->db->join('semifinished_goods sf', "sod.item_id = sf.id AND sod.item_type = 'semifinished'", 'left');
        $this->db->join('finished_goods fg', "sod.item_id = fg.id AND sod.item_type = 'finished'", 'left');
        $this->db->where('sod.subkon_out_id', $header_id);
        $this->db->order_by('sod.id', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Update BC info for GRN
     */
    public function update_grn_bc($grn_id, $bc_type_code, $bc_number, $bc_date) {
        $data = [
            'bc_type_code' => $bc_type_code,
            'bc_number' => $bc_number,
            'bc_date' => $bc_date
        ];
        $this->db->where('id', $grn_id);
        return $this->db->update('grn_header', $data);
    }

    /**
     * Update BC info for FG Delivery
     */
    public function update_fg_delivery_bc($delivery_id, $bc_type_id, $bc_number, $bc_date) {
        $data = [
            'bc_type_id' => $bc_type_id,
            'bc_number' => $bc_number,
            'bc_date' => $bc_date
        ];
        $this->db->where('id', $delivery_id);
        return $this->db->update('fg_delivery_header', $data);
    }

    /**
     * Update BC info for Scrap Out
     */
    public function update_scrap_out_bc($header_id, $bc_type_code, $bc_number, $bc_date) {
        $data = [
            'bc_type_code' => $bc_type_code,
            'bc_number' => $bc_number,
            'bc_date' => $bc_date
        ];
        $this->db->where('id', $header_id);
        return $this->db->update('scrap_out_header', $data);
    }

    /**
     * Update BC info for Subkon Out
     */
    public function update_subkon_out_bc($header_id, $bc_type_code, $bc_number, $bc_date) {
        $data = [
            'bc_type_code' => $bc_type_code,
            'bc_number' => $bc_number,
            'bc_date' => $bc_date
        ];
        $this->db->where('id', $header_id);
        return $this->db->update('subkon_out_header', $data);
    }

    /**
     * Verify Subkon Out
     */
    public function verify_subkon_out($header_id, $user_id) {
        $data = [
            'exim_verified' => 1,
            'exim_verified_by' => $user_id,
            'exim_verified_at' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $header_id);
        return $this->db->update('subkon_out_header', $data);
    }

    /**
     * Unverify Subkon Out
     */
    public function unverify_subkon_out($header_id) {
        $data = [
            'exim_verified' => 0,
            'exim_verified_by' => NULL,
            'exim_verified_at' => NULL
        ];
        $this->db->where('id', $header_id);
        return $this->db->update('subkon_out_header', $data);
    }

    /**
     * Get dashboard statistics for incoming verification
     */
    public function get_incoming_stats() {
        // Total unverified GRN
        $this->db->from('grn_header');
        $this->db->where('exim_verified', 0);
        $this->db->where_in('status', ['Posted', 'Completed']);
        $unverified = $this->db->count_all_results();

        // Total verified GRN
        $this->db->from('grn_header');
        $this->db->where('exim_verified', 1);
        $this->db->where_in('status', ['Posted', 'Completed']);
        $verified = $this->db->count_all_results();

        // Recent unverified (last 7 days)
        $this->db->from('grn_header');
        $this->db->where('exim_verified', 0);
        $this->db->where_in('status', ['Posted', 'Completed']);
        $this->db->where('grn_date >=', date('Y-m-d', strtotime('-7 days')));
        $recent_unverified = $this->db->count_all_results();

        // Recent verified (last 7 days)
        $this->db->from('grn_header');
        $this->db->where('exim_verified', 1);
        $this->db->where_in('status', ['Posted', 'Completed']);
        $this->db->where('exim_verified_at >=', date('Y-m-d H:i:s', strtotime('-7 days')));
        $recent_verified = $this->db->count_all_results();

        return [
            'unverified' => $unverified,
            'verified' => $verified,
            'total' => $unverified + $verified,
            'recent_unverified' => $recent_unverified,
            'recent_verified' => $recent_verified,
            'verification_rate' => ($unverified + $verified) > 0 
                ? round(($verified / ($unverified + $verified)) * 100, 1) 
                : 0
        ];
    }

    /**
     * Get dashboard statistics for outgoing verification
     */
    public function get_outgoing_stats() {
        $stats = [
            'unverified' => 0,
            'verified' => 0,
            'recent_unverified' => 0,
            'recent_verified' => 0,
            'by_type' => []
        ];

        $doc_types = [
            'FG_OUT' => ['table' => 'fg_delivery_header', 'date_field' => 'delivery_date'],
            'SCRAP_OUT' => ['table' => 'scrap_out_header', 'date_field' => 'doc_date'],
            'SUBKON_OUT' => ['table' => 'subkon_out_header', 'date_field' => 'shipping_date'],
            'RTN_SUPPLIER' => ['table' => 'warehouse_out_header', 'date_field' => 'out_date']
        ];

        foreach ($doc_types as $type => $config) {
            // Unverified count
            $this->db->from($config['table']);
            $this->db->where('exim_verified', 0);
            $this->db->where_in('status', ['Posted', 'Completed', 'Confirmed']);
            $type_unverified = $this->db->count_all_results();

            // Verified count
            $this->db->from($config['table']);
            $this->db->where('exim_verified', 1);
            $this->db->where_in('status', ['Posted', 'Completed', 'Confirmed']);
            $type_verified = $this->db->count_all_results();

            // Recent unverified (last 7 days)
            $this->db->from($config['table']);
            $this->db->where('exim_verified', 0);
            $this->db->where_in('status', ['Posted', 'Completed', 'Confirmed']);
            $this->db->where($config['date_field'] . ' >=', date('Y-m-d', strtotime('-7 days')));
            $type_recent_unverified = $this->db->count_all_results();

            // Recent verified (last 7 days)
            $this->db->from($config['table']);
            $this->db->where('exim_verified', 1);
            $this->db->where_in('status', ['Posted', 'Completed', 'Confirmed']);
            $this->db->where('exim_verified_at >=', date('Y-m-d H:i:s', strtotime('-7 days')));
            $type_recent_verified = $this->db->count_all_results();

            $stats['unverified'] += $type_unverified;
            $stats['verified'] += $type_verified;
            $stats['recent_unverified'] += $type_recent_unverified;
            $stats['recent_verified'] += $type_recent_verified;

            $stats['by_type'][$type] = [
                'unverified' => $type_unverified,
                'verified' => $type_verified,
                'total' => $type_unverified + $type_verified
            ];
        }

        $total = $stats['unverified'] + $stats['verified'];
        $stats['total'] = $total;
        $stats['verification_rate'] = $total > 0 ? round(($stats['verified'] / $total) * 100, 1) : 0;

        return $stats;
    }
}
