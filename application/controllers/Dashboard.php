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
        // Outstanding tasks (per-user, per-permission)
        $this->load->library('outstanding_lib');
    }

    public function index() {
        $data['title'] = lang_line('title_dashboard');

        // Data User untuk Topbar
        $data['username'] = $this->session->userdata('fullname');
        $role_id = $this->session->userdata('role_id');
        $data['role_name'] = $this->db
            ->get_where('roles', ['id' => $role_id])
            ->row('role_name');

        // ====================================================================
        // CEK ROLE: Jika Bea Cukai, redirect ke dashboard khusus BC
        // ====================================================================
        // Dashboard khusus BC hanya untuk akun yang SEMUA rolenya BC — user
        // multi-role (mis. BC + Logistic) tetap dapat dashboard normal.
        $bc_names = ['bc', 'bea cukai', 'beacukai', 'customs'];
        $role_names_lower = array_map('strtolower', current_role_names());
        if (!empty($role_names_lower) && !array_diff($role_names_lower, $bc_names)) {
            return $this->bc_dashboard();
        }

        // ====================================================================
        // DASHBOARD BARU (rebrand Acropoly 2026-11-06)
        // --------------------------------------------------------------------
        // User: "keuangan gausa ditampilkan di dashboard karna ga semua harus
        // tau keuangan" -> saldo kas & piutang DIHAPUS (juga perhitungannya).
        // Fokus: tugas pribadi (Perlu Tindakan), KPI operasional, alur
        // produksi bulan ini, progres 5 Sales Order terbaru, dokumen terbaru.
        // ====================================================================
        $month_start = date('Y-m-01');
        $today       = date('Y-m-d');
        $count = function ($table, callable $where) {
            $this->db->reset_query();
            $where($this->db);
            return (int) $this->db->count_all_results($table);
        };

        $data['kpi'] = [
            'so_open'     => $count('sales_contracts', function ($db) { $db->where('status', 'Open'); }),
            'so_waiting'  => $count('sales_contracts', function ($db) { $db->where_in('status', ['Submitted', 'Pending Endorsement', 'Endorsed']); }),
            // Status WO: Planned / In Progress / Active / Finished. Dashboard lama
            // menghitung 'WIP' (status yang tidak pernah ada) -> selalu 0.
            'wo_active'   => $count('wo_header', function ($db) { $db->where_in('status', ['Planned', 'In Progress', 'Active']); }),
            'wo_running'  => $count('wo_header', function ($db) { $db->where_in('status', ['In Progress', 'Active']); }),
            'po_month'    => $count('po_header', function ($db) use ($month_start) { $db->where('po_date >=', $month_start); }),
            'po_waiting'  => $count('po_header', function ($db) { $db->where('status', 'Submitted'); }),
            'pb_month'    => $count('consumable_request_header', function ($db) use ($month_start) { $db->where('request_date >=', $month_start); }),
            'pb_waiting'  => $count('consumable_request_header', function ($db) { $db->where('status', 'Submitted'); }),
            'do_today'    => $count('fg_delivery_header', function ($db) use ($today) { $db->where('DATE(delivery_date)', $today)->where('status !=', 'Cancelled'); }),
            'do_draft'    => $count('fg_delivery_header', function ($db) { $db->where('status', 'Draft'); }),
        ];

        // Alur produksi bulan ini: SO -> WO -> Produksi -> FG -> Kirim
        $data['pipeline'] = [
            ['key' => 'so',   'label' => lang_line('db2_pipe_so', 'Sales Order Baru'),     'icon' => 'fa-file-signature', 'url' => 'sales_contract',     'menu' => 'sales_contract',
             'value' => $count('sales_contracts', function ($db) use ($month_start) { $db->where('contract_date >=', $month_start)->where('status !=', 'Cancelled'); })],
            ['key' => 'wo',   'label' => lang_line('db2_pipe_wo', 'Work Order Aktif'),      'icon' => 'fa-clipboard-list', 'url' => 'work_order',         'menu' => 'work_order',
             'value' => $data['kpi']['wo_active']],
            ['key' => 'prod', 'label' => lang_line('db2_pipe_prod', 'Produksi Berjalan'),   'icon' => 'fa-industry',       'url' => 'production_process', 'menu' => 'production_process',
             'value' => $count('production_header', function ($db) { $db->where_in('status', ['Draft', 'In Progress']); })],
            ['key' => 'fg',   'label' => lang_line('db2_pipe_fg', 'Barang Jadi Diterima'),  'icon' => 'fa-boxes',          'url' => 'fg_receipt',         'menu' => 'fg_receipt',
             'value' => $count('fg_receipt_header', function ($db) use ($month_start) { $db->where('receipt_date >=', $month_start)->where_in('status', ['Confirmed', 'Approved']); })],
            ['key' => 'do',   'label' => lang_line('db2_pipe_do', 'Terkirim'),              'icon' => 'fa-truck',          'url' => 'fg_delivery',        'menu' => 'fg_delivery',
             'value' => $count('fg_delivery_header', function ($db) use ($month_start) { $db->where('delivery_date >=', $month_start)->where('status', 'Confirmed'); })],
        ];

        // Stok barang jadi & WIP (unit, bukan nilai rupiah)
        $fg_summary = $this->Fg_stock_ledger_model->get_current_stock_summary();
        $total_fg_qty = 0.0; $empty_fg = [];
        foreach ($fg_summary as $f) {
            $qty = (float) ($f->current_stock ?? 0);
            $total_fg_qty += $qty;
            if ($qty <= 0) $empty_fg[] = $f;
        }
        $data['inventory_fg_qty']  = $total_fg_qty;
        $data['fg_item_count']     = count($fg_summary);
        $data['low_stock_alerts']  = array_slice($empty_fg, 0, 6);
        $data['low_stock_total']   = count($empty_fg);
        $wip_rows = $this->Stock_ledger_model->get_stock_summary_report(['warehouse_type' => 'semifinished', 'limit' => 0]);
        $total_wip_qty = 0.0;
        foreach ($wip_rows as $w) $total_wip_qty += (float) ($w->closing_stock ?? 0);
        $data['inventory_wip_qty'] = $total_wip_qty;

        // Progres 5 Sales Order terbaru (user: "grafik sales order itu dibatasi 5 terbaru")
        $data['so_progress'] = $this->_latest_so_progress(5);

        // Tugas pribadi (sesuai hak akses)
        $data['outstanding_tasks'] = $this->outstanding_lib->get_for_user();
        $data['outstanding_total'] = array_sum(array_column($data['outstanding_tasks'], 'count'));

        // Dokumen terbaru (hanya jenis dokumen yang boleh dilihat user)
        $data['recent_docs'] = $this->_recent_documents(8);

        // Aksi cepat (hanya yang boleh dibuat user)
        $data['quick_actions'] = array_values(array_filter([
            ['label' => lang_line('db2_qa_pb', 'Permintaan Barang'), 'icon' => 'fa-hand-holding', 'url' => 'consumable_request/add',          'perm' => ['consumable_request', 'add']],
            ['label' => lang_line('db2_qa_mr', 'Material Request'),  'icon' => 'fa-dolly',        'url' => 'material_request/add',            'perm' => ['material_request', 'add']],
            ['label' => lang_line('db2_qa_pr', 'Purchase Request'),  'icon' => 'fa-file-invoice', 'url' => 'purchase_request_general?open=create', 'perm' => ['purchase_request_general', 'add']],
            ['label' => lang_line('db2_qa_po', 'Purchase Order'),    'icon' => 'fa-shopping-cart','url' => 'purchase_order',                  'perm' => ['purchase_order', 'add']],
            ['label' => lang_line('db2_qa_so', 'Sales Order'),       'icon' => 'fa-file-signature','url' => 'sales_contract',                 'perm' => ['sales_contract', 'add']],
            ['label' => lang_line('db2_qa_wo', 'Work Order'),        'icon' => 'fa-cogs',         'url' => 'work_order?open=create',          'perm' => ['work_order', 'add']],
            ['label' => lang_line('db2_qa_do', 'Pengiriman'),        'icon' => 'fa-truck-loading','url' => 'fg_delivery/create',              'perm' => ['fg_delivery', 'add']],
            ['label' => lang_line('db2_qa_report', 'Laporan'),       'icon' => 'fa-chart-pie',    'url' => 'reports',                         'perm' => ['reports', 'view']],
        ], function ($a) { return check_access($a['perm'][0], $a['perm'][1]); }));

        $data['can_trace_so']    = check_access('sales_contract', 'view');
        $data['can_stock_alert'] = check_access('stock_alert', 'view');

        // Load semua view
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('dashboard/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * N Sales Order terbaru (bukan Cancelled) + qty dipesan / diproduksi / dikirim.
     * Sama dgn hitungan sc_chart_data(), ditambah nama customer & persentase.
     */
    private function _latest_so_progress($limit = 5) {
        $rows = $this->db->query("
            SELECT sc.id, sc.sc_number, sc.status, sc.contract_date, c.nama_customer,
                COALESCE(sc.po_qty, 0) AS po_qty,
                COALESCE((
                    SELECT SUM(frd.qty_received)
                    FROM fg_receipt_details frd
                    JOIN fg_receipt_header frh ON frd.fg_receipt_header_id = frh.id
                    JOIN production_header ph ON frh.production_header_id = ph.id
                    JOIN wo_header wo ON ph.wo_header_id = wo.id
                    WHERE wo.sales_contract_id = sc.id
                ), 0) AS produced_qty,
                COALESCE((
                    SELECT SUM(fdd.qty_shipped) FROM fg_delivery_details fdd WHERE fdd.sales_contract_id = sc.id
                ), 0) AS delivered_qty
            FROM sales_contracts sc
            LEFT JOIN customers c ON c.id = sc.customer_id
            WHERE sc.status != 'Cancelled'
            ORDER BY sc.contract_date DESC, sc.id DESC
            LIMIT ?
        ", [(int) $limit])->result();
        foreach ($rows as $r) {
            $base = max((float) $r->po_qty, 0.0001);
            $r->pct_produced  = (int) min(100, round((float) $r->produced_qty / $base * 100));
            $r->pct_delivered = (int) min(100, round((float) $r->delivered_qty / $base * 100));
        }
        return $rows;
    }

    /**
     * Dokumen terbaru lintas modul (non-keuangan), hanya jenis yang boleh
     * dilihat user. Query terpisah per jenis (bukan UNION) supaya aman dari
     * perbedaan collation antar tabel.
     */
    private function _recent_documents($limit = 8) {
        $sources = [
            ['menu' => 'consumable_request', 'label' => 'Permintaan Barang', 'icon' => 'fa-hand-holding',  'color' => 'teal',
             'table' => 'consumable_request_header', 'no' => 'request_number', 'date' => 'request_date', 'url' => 'consumable_request/detail/'],
            ['menu' => 'material_request',   'label' => 'Material Request',  'icon' => 'fa-dolly',         'color' => 'indigo',
             'table' => 'mr_header',          'no' => 'mr_number',      'date' => 'request_date',  'url' => 'material_request/detail/'],
            ['menu' => 'sales_contract',     'label' => 'Sales Order',       'icon' => 'fa-file-signature','color' => 'blue',
             'table' => 'sales_contracts',    'no' => 'sc_number',      'date' => 'contract_date', 'url' => 'sales_contract/detail/'],
            ['menu' => 'work_order',         'label' => 'Work Order',        'icon' => 'fa-cogs',          'color' => 'orange',
             'table' => 'wo_header',          'no' => 'wo_number',      'date' => 'planned_start_date', 'url' => 'work_order/detail/'],
            ['menu' => 'fg_delivery',        'label' => 'Pengiriman',        'icon' => 'fa-truck',         'color' => 'green',
             'table' => 'fg_delivery_header', 'no' => 'delivery_number','date' => 'delivery_date', 'url' => 'fg_delivery/show/'],
        ];
        // PR & PO: rute & izin berbeda per tipe
        $pr_routes = ['produksi' => 'purchase_request', 'non_produksi' => 'purchase_request_general', 'jasa' => 'purchase_request_jasa', 'asset' => 'purchase_request_asset'];
        $po_routes = ['produksi' => 'purchase_order', 'non_produksi' => 'purchase_order_general', 'jasa' => 'purchase_order_jasa', 'asset' => 'purchase_order_asset'];

        $docs = [];
        $push = function ($rows, $meta, $url_of) use (&$docs) {
            foreach ($rows as $r) {
                $docs[] = (object) [
                    'label' => $meta['label'], 'icon' => $meta['icon'], 'color' => $meta['color'],
                    'number' => $r->no, 'date' => $r->d, 'status' => $r->status,
                    'sort' => $r->created_at ?: $r->d, 'url' => $url_of($r),
                ];
            }
        };
        foreach ($sources as $src) {
            if (!check_access($src['menu'], 'view')) continue;
            $rows = $this->db->select("id, {$src['no']} AS no, {$src['date']} AS d, status, created_at")
                ->from($src['table'])->order_by('created_at', 'DESC')->order_by('id', 'DESC')->limit($limit)->get()->result();
            $push($rows, $src, function ($r) use ($src) { return $src['url'] . $r->id; });
        }
        foreach (['pr' => [$pr_routes, 'pr_header', 'pr_number', 'pr_date', 'Purchase Request', 'fa-file-invoice', 'purple'],
                  'po' => [$po_routes, 'po_header', 'po_number', 'po_date', 'Purchase Order', 'fa-shopping-cart', 'pink']] as $k => $cfg) {
            [$routes, $table, $no, $date, $label, $icon, $color] = $cfg;
            $types = array_keys(array_filter($routes, function ($menu) { return check_access($menu, 'view'); }));
            if (empty($types)) continue;
            $rows = $this->db->select("id, type, {$no} AS no, {$date} AS d, status, created_at")
                ->from($table)->where_in('type', $types)->order_by('created_at', 'DESC')->order_by('id', 'DESC')->limit($limit)->get()->result();
            $push($rows, ['label' => $label, 'icon' => $icon, 'color' => $color], function ($r) use ($routes) {
                return ($routes[$r->type] ?? reset($routes)) . '/detail/' . $r->id;
            });
        }
        usort($docs, function ($a, $b) { return strcmp((string) $b->sort, (string) $a->sort); });
        return array_slice($docs, 0, $limit);
    }

    /**
     * JSON endpoint: aggregate Sales Contract quantities for charting
     */
    public function sc_chart_data()
    {
        // 5 Sales Order terbaru (user 2026-11-06: "grafik sales order dibatasi 5 terbaru")
        $limit = 5;
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
