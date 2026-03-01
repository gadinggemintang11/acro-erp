<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login(); // Pastikan user sudah login

        $this->load->library('session');
        $this->load->database();
        // Load models needed for dashboard KPIs
        $this->load->model('Report_model');
        $this->load->model('Fg_stock_ledger_model');
        $this->load->model('Stock_ledger_model');
        $this->load->model('Sc_model');
        // Additional models used by sc_trace
        $this->load->model('Mr_model');
        $this->load->model('Production_model');
        $this->load->model('Material_issuance_model');
    }

    public function index() {
        $data['title'] = 'Dashboard';
        
        // Data User untuk Topbar
        $data['username'] = $this->session->userdata('fullname');
        $role_id = $this->session->userdata('role_id');
        $data['role_name'] = $this->db
            ->get_where('roles', ['id' => $role_id])
            ->row('role_name');

        // ====================================================================
        // CEK ROLE: Jika Bea Cukai, redirect ke dashboard khusus BC
        // ====================================================================
        $role_name_lower = strtolower($data['role_name']);
        if (in_array($role_name_lower, ['bc', 'bea cukai', 'beacukai', 'customs'])) {
            return $this->bc_dashboard();
        }

        // ====================================================================
        // DASHBOARD NORMAL (Internal Company)
        // ====================================================================

        // 1. PO Baru Bulan Ini (dari 'po_header')
        // Pastikan 'po_date' sudah benar
        $data['new_po_count'] = $this->db
            ->where('MONTH(po_date)', date('m'))
            ->where('YEAR(po_date)', date('Y'))
            ->count_all_results('po_header'); // OK

        // 2. Work Order Aktif (dari 'wo_header')
        // Pastikan 'status' dan 'WIP' sudah benar
        $data['active_wo_count'] = $this->db
            ->where('status', 'WIP') 
            ->count_all_results('wo_header'); // OK

        // 3. Pengiriman Hari Ini (dari 'fg_delivery_header')
        // Pastikan 'delivery_date' sudah benar
        $data['shipments_today_count'] = $this->db
            ->where('DATE(delivery_date)', date('Y-m-d')) 
            ->count_all_results('fg_delivery_header'); // OK

        // 4. Total User (Statistik Admin, tetap oke)
        $data['user_count'] = $this->db->count_all_results('users');

        // 5. Total Sales Contract (dari 'sales_contracts')
        $data['sales_contract_count'] = $this->db->count_all_results('sales_contracts');

        // 6. Total FG Delivery (dari 'fg_delivery_header')
        $data['fg_delivery_count'] = $this->db->count_all_results('fg_delivery_header');

        // ====================================================================
        // Tambahan KPI cepat (Receivables, Cash, Pending/Confirmed DO, Inventory)
        // ====================================================================

        // Pending DO (Draft)
        $data['pending_do_count'] = (int) $this->db->where('status', 'Draft')->count_all_results('fg_delivery_header');

        // Confirmed DO
        $data['confirmed_do_count'] = (int) $this->db->where('status', 'Confirmed')->count_all_results('fg_delivery_header');

        // Total Receivables (sisa piutang) dari ar_invoice_header
        $row = $this->db->select('SUM(total_invoice - COALESCE(total_paid,0)) as total_receivables')
            ->from('ar_invoice_header')
            ->where('status', 'Posted')
            ->where("(payment_status IS NULL OR payment_status != 'Paid')", NULL, FALSE)
            ->get()->row();
        $data['total_receivables'] = (float) ($row->total_receivables ?? 0);

        // Cash balance: gunakan report_model helper (kas per bulan sampai hari ini)
        try {
            $cash_flow = $this->Report_model->get_cash_flow(date('Y-m-01'), date('Y-m-d'));
            $data['cash_balance'] = isset($cash_flow['saldo_akhir_kas']) ? (float) $cash_flow['saldo_akhir_kas'] : 0.0;
        } catch (Exception $ex) {
            // fallback ke 0 jika terjadi error
            $data['cash_balance'] = 0.0;
        }

        // Inventory - Finished Goods: jumlah unit (sum current_stock)
        $fg_summary = $this->Fg_stock_ledger_model->get_current_stock_summary();
        $total_fg_qty = 0.0;
        $low_stock_items = [];
        foreach ($fg_summary as $f) {
            $qty = (float) ($f->current_stock ?? 0);
            $total_fg_qty += $qty;
            if ($qty <= 0) {
                $low_stock_items[] = $f;
            }
        }
        $data['inventory_fg_qty'] = $total_fg_qty;
        // top 8 low stock
        $data['low_stock_alerts'] = array_slice($low_stock_items, 0, 8);

        // Inventory - WIP / Semifinished: gunakan Stock_ledger_model report (closing_stock)
        $wip_rows = $this->Stock_ledger_model->get_stock_summary_report(['warehouse_type' => 'semifinished', 'limit' => 0]);
        $total_wip_qty = 0.0;
        foreach ($wip_rows as $w) {
            $total_wip_qty += (float) ($w->closing_stock ?? 0);
        }
        $data['inventory_wip_qty'] = $total_wip_qty;


        // ====================================================================
        // Data untuk List "Aktivitas Terbaru" (FIXED)
        // ====================================================================
        
        // 5. Mengambil 5 PO Terbaru (dari 'po_header' join 'suppliers')
        $data['recent_pos'] = $this->db
            ->select('po.id, po.po_number, po.po_date, s.nama_supplier') // <-- TAMBAHKAN po.id
            ->from('po_header po')
            ->join('suppliers s', 's.id = po.supplier_id', 'left')
            ->order_by('po.po_date', 'DESC')
            ->limit(5)
            ->get()
            ->result();

        // Load semua view
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('dashboard/index', $data); 
        $this->load->view('templates/footer');
    }

    /**
     * JSON endpoint: aggregate Sales Contract quantities for charting
     */
    public function sc_chart_data()
    {
        // Return top 12 recent sales contracts with po_qty, produced_qty, delivered_qty
        $limit = 12;
        $sql = "
            SELECT sc.id, sc.sc_number, COALESCE(sc.po_qty,0) AS po_qty,
                COALESCE((
                    SELECT SUM(frd.qty_received)
                    FROM fg_receipt_details frd
                    JOIN fg_receipt_header frh ON frd.fg_receipt_header_id = frh.id
                    JOIN production_header ph ON frh.production_header_id = ph.id
                    JOIN wo_header wo ON ph.wo_header_id = wo.id
                    WHERE wo.sales_contract_id = sc.id
                ),0) AS produced_qty,
                COALESCE((
                    SELECT SUM(fdd.qty_shipped) FROM fg_delivery_details fdd WHERE fdd.sales_contract_id = sc.id
                ),0) AS delivered_qty
            FROM sales_contracts sc
            WHERE sc.status != 'Cancelled'
            ORDER BY sc.contract_date DESC
            LIMIT ?
        ";
        $rows = $this->db->query($sql, [$limit])->result();

        // Format JSON
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int)$r->id,
                'sc_number' => $r->sc_number,
                'po_qty' => (float)$r->po_qty,
                'produced_qty' => (float)$r->produced_qty,
                'delivered_qty' => (float)$r->delivered_qty
            ];
        }
        header('Content-Type: application/json');
        echo json_encode($out);
    }

    /**
     * JSON endpoint for Select2 async search of Sales Contracts
     * Query param: q
     */
    public function sc_search()
    {
        $q = $this->input->get('q', true);
        $this->db->select('sc.id, sc.sc_number, c.nama_customer');
        $this->db->from('sales_contracts sc');
        $this->db->join('customers c', 'sc.customer_id = c.id', 'left');
        if (!empty($q)) {
            $this->db->group_start();
            $this->db->like('sc.sc_number', $q);
            $this->db->or_like('c.nama_customer', $q);
            $this->db->group_end();
        }
        $this->db->where('sc.status !=', 'Cancelled');
        $this->db->order_by('sc.contract_date', 'DESC');
        $this->db->limit(50);
        $rows = $this->db->get()->result();

        $out = [];
        foreach ($rows as $r) {
            $out[] = ['id' => $r->id, 'text' => $r->sc_number . ' - ' . ($r->nama_customer ?? '')];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $out]);
    }

    /**
     * JSON endpoint: trace a sales contract => work orders, productions, receipts, deliveries
     */
    public function sc_trace($sc_id = null)
    {
        if (!$sc_id || !is_numeric($sc_id)) {
            show_404();
            return;
        }

        // Get SC header
        $sc = $this->Sc_model->get_by_id($sc_id);
        if (!$sc) { show_404(); return; }

        // Work orders for this SC
        $this->db->select('id, wo_number, product_name, status');
        $this->db->from('wo_header');
        $this->db->where('sales_contract_id', $sc_id);
        $this->db->order_by('id','ASC');
        $wos = $this->db->get()->result();

        $wo_list = [];
        foreach ($wos as $wo) {
            // produced qty for this WO via fg_receipt -> frh.production_header_id -> ph.wo_header_id
            $sql_prod = "
                SELECT COALESCE(SUM(frd.qty_received),0) as produced_qty
                FROM fg_receipt_details frd
                JOIN fg_receipt_header frh ON frd.fg_receipt_header_id = frh.id
                JOIN production_header ph ON frh.production_header_id = ph.id
                WHERE ph.wo_header_id = ?
            ";
            $prod_row = $this->db->query($sql_prod, [$wo->id])->row();

            // productions list
            $prods = $this->db->select('id, production_number, status, process_type, planned_qty, actual_qty, started_at, finished_at')
                ->from('production_header')
                ->where('wo_header_id', $wo->id)
                ->order_by('id','ASC')
                ->get()->result();

            // enrich productions with inputs and outputs
            foreach ($prods as &$p) {
                $p->inputs = $this->db->select('item_id, item_type, qty_used, stock_on_hand_id, unit_value')
                    ->from('production_inputs')
                    ->where('production_header_id', $p->id)
                    ->get()->result();
                $p->outputs = $this->db->select('item_id, item_type, qty_produced')
                    ->from('production_outputs')
                    ->where('production_header_id', $p->id)
                    ->get()->result();
            }

            $wo_list[] = [
                'id' => (int)$wo->id,
                'wo_number' => $wo->wo_number,
                'product_name' => $wo->product_name,
                'status' => $wo->status,
                'produced_qty' => (float)($prod_row->produced_qty ?? 0),
                'productions' => $prods
            ];
        }

        // Deliveries for this SC (aggregated)
        $this->db->select('fdh.id as header_id, fdh.delivery_number, fdh.delivery_date, SUM(fdd.qty_shipped) as total_shipped');
        $this->db->from('fg_delivery_header fdh');
        $this->db->join('fg_delivery_details fdd', 'fdd.fg_delivery_header_id = fdh.id', 'left');
        $this->db->where('fdd.sales_contract_id', $sc_id);
        $this->db->group_by('fdh.id');
        $this->db->order_by('fdh.delivery_date','ASC');
        $deliveries = $this->db->get()->result();

        // --- NEW: Material Requests (MR) and Issuances related to this SC ---
        $sql_mr = "
            SELECT mr.id, mr.mr_number, mr.request_date, mr.status, mr.issuer_id, mr.issued_at, mr.issuance_number,
                COALESCE((SELECT SUM(d.requested_qty) FROM mr_details d WHERE d.mr_header_id = mr.id),0) AS total_requested,
                COALESCE((SELECT SUM(d.issued_qty) FROM mr_details d WHERE d.mr_header_id = mr.id),0) AS total_issued
            FROM mr_header mr
            JOIN mr_details md ON md.mr_header_id = mr.id
            JOIN wo_header wo ON md.wo_header_id = wo.id
            WHERE wo.sales_contract_id = ?
            GROUP BY mr.id
            ORDER BY mr.request_date ASC
        ";
        $mr_list = $this->db->query($sql_mr, [$sc_id])->result();

        // Attach MR details for each MR
        foreach ($mr_list as &$mr) {
            $mr->details = $this->Mr_model->get_details_by_id($mr->id);
        }

        $result = [
            'sc' => $sc,
            'work_orders' => $wo_list,
            'mrs' => $mr_list,
            'deliveries' => $deliveries
        ];

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * ====================================================================
     * DASHBOARD KHUSUS BEA CUKAI
     * Hanya menampilkan data customs/BC tanpa data internal perusahaan
     * ====================================================================
     */
    public function bc_dashboard()
    {
        $data['title'] = lang_line('dashboard_bc_title', 'Dashboard Bea Cukai');
        
        // Data User
        $data['username'] = $this->session->userdata('fullname');
        $data['role_name'] = $this->db
            ->get_where('roles', ['id' => $this->session->userdata('role_id')])
            ->row('role_name');

        // ====================================================================
        // STATISTIK BEA CUKAI (BC Related Only)
        // ====================================================================

        // 1. Total Dokumen BC Masuk (GRN) bulan ini
        $data['bc_grn_count'] = $this->db
            ->where('MONTH(grn_date)', date('m'))
            ->where('YEAR(grn_date)', date('Y'))
            ->where('bc_type_code IS NOT NULL')
            ->count_all_results('grn_header');

        // 2. Total Dokumen BC Keluar (Subkon Out + FG Delivery) bulan ini
        $subkon_out = $this->db
            ->where('MONTH(shipping_date)', date('m'))
            ->where('YEAR(shipping_date)', date('Y'))
            ->where('bc_type_code IS NOT NULL')
            ->count_all_results('subkon_out_header');

        $fg_delivery = $this->db
            ->where('MONTH(delivery_date)', date('m'))
            ->where('YEAR(delivery_date)', date('Y'))
            ->where('bc_type_id IS NOT NULL')
            ->count_all_results('fg_delivery_header');

        $scrap_out_count = $this->db
            ->where('MONTH(doc_date)', date('m'))
            ->where('YEAR(doc_date)', date('Y'))
            ->where('bc_type_code IS NOT NULL')
            ->count_all_results('scrap_out_header');

        // Include RTN (Return Supplier) in outgoing BC count
        $rtn_out_count = $this->db
            ->where('MONTH(out_date)', date('m'))
            ->where('YEAR(out_date)', date('Y'))
            ->where('bc_type_code IS NOT NULL')
            ->count_all_results('warehouse_out_header');

        $data['bc_out_count'] = $subkon_out + $fg_delivery + $scrap_out_count + $rtn_out_count;

        // 3. Total Dokumen BC (All Time)
        $grn_total = $this->db->where('bc_type_code IS NOT NULL')->count_all_results('grn_header');
        $subkon_total = $this->db->where('bc_type_code IS NOT NULL')->count_all_results('subkon_out_header');
        $fg_total = $this->db->where('bc_type_id IS NOT NULL')->count_all_results('fg_delivery_header');
        $scrap_total = $this->db->where('bc_type_code IS NOT NULL')->count_all_results('scrap_out_header');
        $rtn_total = $this->db->where('bc_type_code IS NOT NULL')->count_all_results('warehouse_out_header');

        $data['total_bc_documents'] = $grn_total + $subkon_total + $fg_total + $scrap_total + $rtn_total;

        // 4. Pending Verification (Status Draft/Pending)
        $data['pending_verification'] = $this->db
            ->where('bc_type_code IS NOT NULL')
            ->where_in('status', ['Draft', 'Pending'])
            ->count_all_results('grn_header');

        // ====================================================================
        // RECENT BC INCOMING DOCUMENTS (Barang Masuk dengan BC)
        // ====================================================================
                // Recent BC incoming: show supplier from header, or fallback to supplier from PO/MIR referenced in grn_details
                $detail_supplier_sub = "(
                        SELECT gd.grn_header_id, COALESCE(sp.nama_supplier, sm.nama_supplier, '') AS detail_supplier
                        FROM grn_details gd
                        LEFT JOIN po_header p ON p.id = gd.source_doc_header_id AND gd.source_doc_type = 'PO'
                        LEFT JOIN suppliers sp ON sp.id = p.supplier_id
                        LEFT JOIN mir_header m ON m.id = gd.source_doc_header_id AND gd.source_doc_type = 'MIR'
                        LEFT JOIN suppliers sm ON sm.id = m.supplier_id
                        GROUP BY gd.grn_header_id
                ) AS src_sup";

                $data['recent_bc_incoming'] = $this->db
                        ->select('grn.id, grn.grn_number, grn.grn_date, grn.bc_type_code, grn.bc_number, grn.bc_date, grn.status, COALESCE(s.nama_supplier, src_sup.detail_supplier, "") as nama_supplier, cc.description as bc_type_name', false)
                        ->from('grn_header grn')
                        ->join('suppliers s', 's.id = grn.supplier_id', 'left')
                        ->join($detail_supplier_sub, 'src_sup.grn_header_id = grn.id', 'left', false)
                        ->join('common_code cc', "cc.code = grn.bc_type_code AND cc.group_code = 'BC_TYPE'", 'left')
                        ->where('grn.bc_type_code IS NOT NULL')
                        ->order_by('grn.grn_date', 'DESC')
                        ->limit(10)
                        ->get()
                        ->result();

        // ====================================================================
        // RECENT BC OUTGOING DOCUMENTS (Barang Keluar dengan BC)
        // ====================================================================
        
        // Subkon Out
        $subkon = $this->db
            ->select('so.id, so.subkon_out_no as doc_number, so.shipping_date as doc_date, so.bc_type_code, so.bc_number, 
                      so.bc_date, so.status, s.nama_supplier, cc.description as bc_type_name, 
                      "SUBKON" as doc_type')
            ->from('subkon_out_header so')
            ->join('suppliers s', 's.id = so.supplier_id', 'left')
            ->join('common_code cc', "cc.code = so.bc_type_code AND cc.group_code = 'BC_TYPE'", 'left')
            ->where('so.bc_type_code IS NOT NULL')
            ->order_by('so.shipping_date', 'DESC')
            ->limit(5)
            ->get()
            ->result();

        // FG Delivery
        $fg_del = $this->db
            ->select('fd.id, fd.delivery_number as doc_number, fd.delivery_date as doc_date, 
                      fd.bc_type_id as bc_type_code, fd.bc_number, fd.bc_date, fd.status, 
                      c.nama_customer as nama_supplier, cc.description as bc_type_name,
                      "FG_DELIVERY" as doc_type')
            ->from('fg_delivery_header fd')
            ->join('customers c', 'c.id = fd.customer_id', 'left')
            ->join('common_code cc', "(cc.code = fd.bc_type_id OR cc.id = fd.bc_type_id) AND cc.group_code = 'BC_TYPE'", 'left')
            ->where('fd.bc_type_id IS NOT NULL')
            ->order_by('fd.delivery_date', 'DESC')
            ->limit(5)
            ->get()
            ->result();

        // Scrap Out (jika ada BC untuk scrap keluar)
        $scrap_out = $this->db
            ->select('so.id, so.doc_no as doc_number, so.doc_date as doc_date, so.bc_type_code, so.bc_number, so.bc_date, so.status, c.nama_customer as nama_supplier, cc.description as bc_type_name, "SCRAP_OUT" as doc_type')
            ->from('scrap_out_header so')
            ->join('customers c', 'c.id = so.destination', 'left')
            ->join('common_code cc', "cc.code = so.bc_type_code AND cc.group_code = 'BC_TYPE'", 'left')
            ->where('so.bc_type_code IS NOT NULL')
            ->order_by('so.doc_date', 'DESC')
            ->limit(5)
            ->get()
            ->result();

        // Return Supplier (RTN) - keluar dengan BC (warehouse_out_header)
        $rtn_out = $this->db
            ->select('h.id, h.out_number as doc_number, h.out_date as doc_date, h.bc_type_code, h.bc_number, h.bc_date, h.status, COALESCE(s.nama_supplier, h.nama_supplier) as nama_supplier, cc.description as bc_type_name, "RTN_SUPPLIER" as doc_type')
            ->from('warehouse_out_header h')
            ->join('suppliers s', 's.id = h.supplier_id', 'left')
            ->join('common_code cc', "cc.code = h.bc_type_code AND cc.group_code = 'BC_TYPE'", 'left')
            ->where('h.bc_type_code IS NOT NULL')
            ->order_by('h.out_date', 'DESC')
            ->limit(5)
            ->get()
            ->result();

        // Merge dan sort by date (termasuk scrap_out)
        $data['recent_bc_outgoing'] = array_merge($subkon, $fg_del, $scrap_out, $rtn_out);
        usort($data['recent_bc_outgoing'], function($a, $b) {
            return strtotime($b->doc_date) - strtotime($a->doc_date);
        });
        $data['recent_bc_outgoing'] = array_slice($data['recent_bc_outgoing'], 0, 10);

        // ====================================================================
        // BC STATISTICS BY TYPE (Chart Data)
        // ====================================================================
        $bc_stats = [];
        $bc_types = $this->db
            ->select('id, code, description')
            ->from('common_code')
            ->where('group_code', 'BC_TYPE')
            ->get()
            ->result();

        foreach ($bc_types as $type) {
            $incoming = $this->db->where('bc_type_code', $type->code)->count_all_results('grn_header');
            $outgoing_subkon = $this->db->where('bc_type_code', $type->code)->count_all_results('subkon_out_header');
            // bc_type_id in fg_delivery_header can store either the code (e.g. '4.0') or the common_code.id (numeric), so
            // count matches where bc_type_id equals either the code or the id.
            $this->db->where_in('bc_type_id', [$type->code, $type->id]);
            $outgoing_fg = $this->db->count_all_results('fg_delivery_header');
            $outgoing_scrap = $this->db->where('bc_type_code', $type->code)->count_all_results('scrap_out_header');
            // Include RTN (warehouse_out_header) in outgoing counts
            $outgoing_rtn = $this->db->where('bc_type_code', $type->code)->count_all_results('warehouse_out_header');

            $bc_stats[] = [
                'type' => $type->description,
                'incoming' => $incoming,
                'outgoing' => $outgoing_subkon + $outgoing_fg + $outgoing_scrap + $outgoing_rtn
            ];
        }
        $data['bc_statistics'] = $bc_stats;

        // Load BC Dashboard View
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('dashboard/bc_dashboard', $data);
        $this->load->view('templates/footer');
    }
}
