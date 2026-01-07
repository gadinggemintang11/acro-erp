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
        
        // Data User untuk Topbar (Sudah benar)
        $data['username'] = $this->session->userdata('fullname');
        $data['role_name'] = $this->db
            ->get_where('roles', ['id' => $this->session->userdata('role_id')])
            ->row('role_name');

        // ====================================================================
        // Mengambil Statistik KPI ERP
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
        // Hanya Posted dan belum lunas
        $row = $this->db->select('SUM(total_invoice - COALESCE(total_paid,0)) as total_receivables')
            ->from('ar_invoice_header')
            ->where('status', 'Posted') // Hanya yang sudah di-posting
            ->where('payment_status !=', 'Paid') // Belum lunas (NULL, Unpaid, Partially Paid)
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
}