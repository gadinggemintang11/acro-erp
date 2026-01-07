<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LapMutasiScrap_model extends CI_Model {

    private $ledger = 'stock_ledger';
    private $master = 'master_scrap';

    public function __construct() {
        parent::__construct();
    }

    /**
     * get_mutation_report
     * Mengembalikan array of assoc
     */
    public function get_mutation_report($filters = [], $limit = 25, $offset = 0) {
        $start_date = $filters['start_date'] ?? null;
        $end_date   = $filters['end_date'] ?? null;
        $search     = $filters['search'] ?? null;

        // Subquery opening: last ledger before start_date per item
        $this->db->select("item_id, MAX(id) AS max_id", false)
                 ->from($this->ledger)
                 ->where('item_type', 'scrap');
        if ($start_date) $this->db->where('transaction_date <', $start_date . ' 00:00:00');
        $this->db->group_by('item_id');
        $sub_open = $this->db->get_compiled_select();
        $this->db->reset_query();

        $this->db->select("sl.item_id, sl.balance AS opening_stock", false)
                 ->from("{$this->ledger} sl")
                 ->join("({$sub_open}) m", 'sl.id=m.max_id', 'inner');
        $sub_opening = $this->db->get_compiled_select();
        $this->db->reset_query();

        // Subquery periode: agregasi qty masuk/keluar/adj
        $this->db->select("item_id,
            SUM(qty_in) AS qty_in,
            SUM(qty_out) AS qty_out,
            SUM(CASE WHEN transaction_type LIKE 'ADJ_%' THEN (qty_in - qty_out) ELSE 0 END) AS qty_adj", false)
            ->from($this->ledger)
            ->where('item_type','scrap');
        if ($start_date) $this->db->where('transaction_date >=', $start_date . ' 00:00:00');
        if ($end_date) $this->db->where('transaction_date <=', $end_date . ' 23:59:59');
        $this->db->group_by('item_id');
        $sub_mutation = $this->db->get_compiled_select();
        $this->db->reset_query();

        // Main select — closing stock computed by last ledger up to end_date
        $safe_end = $this->db->escape(($end_date ?? date('Y-m-d')) . ' 23:59:59');
        $this->db->select("
            s.id AS item_id,
            'scrap' AS item_type,
            s.scrap_code AS item_code,
            s.scrap_name AS item_name,
            s.unit AS item_unit,
            IFNULL(o.opening_stock,0) AS opening_stock,
            IFNULL(m.qty_in,0) AS qty_in,
            IFNULL(m.qty_out,0) AS qty_out,
            IFNULL(m.qty_adj,0) AS qty_adj,
            IFNULL((SELECT sl2.balance FROM {$this->ledger} sl2
                 WHERE sl2.item_id = s.id AND sl2.item_type = 'scrap'
                 AND sl2.transaction_date <= {$safe_end}
                 ORDER BY sl2.id DESC LIMIT 1), IFNULL(o.opening_stock,0)) AS closing_stock
        ", false)
        ->from("{$this->master} s")
        ->join("({$sub_opening}) o", 's.id=o.item_id', 'left')
        ->join("({$sub_mutation}) m", 's.id=m.item_id', 'left')
        ->where('s.status','active');

        if ($search) {
            $this->db->group_start()
                     ->like('s.scrap_code',$search)
                     ->or_like('s.scrap_name',$search)
                     ->group_end();
        }

        $this->db->order_by('s.scrap_code','ASC');
        if ($limit > 0) $this->db->limit((int)$limit, (int)$offset);
        $res = $this->db->get();
        return $res ? $res->result_array() : [];
    }

    /**
     * count_report
     */
    public function count_report($filters = []) {
        $search = $filters['search'] ?? null;
        $this->db->reset_query();
        $this->db->from($this->master . ' items');
        $this->db->where('items.status', 'active');
        if ($search) {
            $this->db->group_start();
            $this->db->like('items.scrap_code', $search);
            $this->db->or_like('items.scrap_name', $search);
            $this->db->group_end();
        }
        // Count distinct items that have ledger records up to end_date
        $this->db->join($this->ledger . ' sl', "items.id = sl.item_id AND sl.item_type = 'scrap'", 'inner');
        if (!empty($filters['end_date'])) {
            $this->db->where('sl.transaction_date <=', $filters['end_date'] . ' 23:59:59');
        }
        $this->db->group_by('items.id');
        $q = $this->db->get();
        if (!$q) {
            log_message('error', 'LapMutasiScrap_model::count_report error: ' . print_r($this->db->error(), true));
            return 0;
        }
        return $q->num_rows();
    }
    /**
 * get_warehouse_origin_trace
 * Melacak asal scrap untuk transaksi IN_SCRAP_WH (mutasi antar warehouse)
 */
    public function get_warehouse_origin_trace($ledger_id)
    {
        $this->db->reset_query();
        $this->db->select('sl_prev.*, h_out.doc_no AS source_doc_no, h_out.bc_type_code, h_out.bc_number, h_out.bc_date, c.nama_customer AS destination_name');
        $this->db->from('stock_ledger sl');
        $this->db->join('stock_ledger sl_prev', 'sl_prev.item_id = sl.item_id AND sl_prev.item_type = sl.item_type AND sl_prev.id < sl.id', 'left');
        $this->db->join('scrap_out_header h_out', 'h_out.id = sl_prev.transaction_id AND sl_prev.transaction_type LIKE "OUT_SCRAP%"', 'left');
        $this->db->join('customers c', 'h_out.destination = c.id', 'left');
        $this->db->where('sl.id', $ledger_id);
        $this->db->order_by('sl_prev.id', 'DESC');
        $this->db->limit(1);

        $q = $this->db->get();
        if (!$q || $q->num_rows() == 0) {
            return null;
        }

        $r = $q->row_array();
        return [
            'source_doc_no' => $r['source_doc_no'] ?? '-',
            'bc_type_code' => $r['bc_type_code'] ?? '-',
            'bc_number' => $r['bc_number'] ?? '-',
            'bc_date' => $r['bc_date'] ?? '-',
            'destination_name' => $r['destination_name'] ?? '-'
        ];
    }
    public function get_warehouse_origin_trace_detailed($ledger_id)
    {
        // 1️⃣ Ambil data ledger (root source)
        $ledger = $this->db->get_where('stock_ledger', ['id' => $ledger_id])->row_array();
        if (!$ledger) return null;

        // Ledger tipe IN_SCRAP_WH → ambil scrap_in_header ID dari transaction_id
        if ($ledger['transaction_type'] !== 'IN_SCRAP_WH') {
            return null;
        }

        $scrap_in_header_id = $ledger['transaction_id']; // dari stock_ledger.transaction_id

        // 2️⃣ Dapatkan production_output_id dari scrap_in_detail_sources
        $query = $this->db->select('sids.production_output_id')
            ->from('scrap_in_detail_sources sids')
            ->join('scrap_in_detail sid', 'sid.id = sids.scrap_in_detail_id', 'left')
            ->join('scrap_in_header sh', 'sh.id = sid.header_id', 'left')
            ->where('sh.id', $scrap_in_header_id)
            ->get();

        if ($query->num_rows() == 0) {
            return null;
        }

        $production_output_id = $query->row()->production_output_id;

        // 3️⃣ Ambil informasi produksi (nomor produksi, WO, FG)
        $prod = $this->db->select('
                po.id AS production_output_id,
                po.stage_name AS process_name,
                ph.production_number,
                wo.wo_number,
                fg.nama_barang AS fg_name
            ')
            ->from('production_outputs po')
            ->join('production_header ph', 'ph.id = po.production_header_id', 'left')
            ->join('wo_header wo', 'wo.id = ph.wo_header_id', 'left')
            ->join('finished_goods fg', 'fg.id = ph.fg_id', 'left')
            ->where('po.id', $production_output_id)
            ->get()
            ->row_array();

        if (!$prod) return null;

        // 4️⃣ Ambil material + BC dari grn_header berdasarkan item_id produksi
        $materials = $this->db->query("
            SELECT 
                COALESCE(mm.kode_material, sf.kode_barang, fg.kode_barang, CONCAT('MAT-', pi.item_id)) AS item_code,
                COALESCE(mm.nama_material, sf.nama_barang, fg.nama_barang, 'Material Produksi') AS item_name,
                COALESCE(mm.satuan, sf.satuan, fg.satuan, 'PCS') AS unit,
                SUM(pi.qty_used) AS qty_used,
                MAX(gh.bc_type_code) AS bc_type_code,
                MAX(gh.bc_number) AS bc_number,
                MAX(gh.bc_date) AS bc_date
            FROM production_inputs pi
            LEFT JOIN materials mm ON mm.id = pi.item_id AND pi.item_type = 'material'
            LEFT JOIN semifinished_goods sf ON sf.id = pi.item_id AND pi.item_type = 'semifinished'
            LEFT JOIN finished_goods fg ON fg.id = pi.item_id AND pi.item_type = 'finished_goods'
            LEFT JOIN grn_details gd ON gd.item_id = pi.item_id
            LEFT JOIN grn_header gh ON gh.id = gd.grn_header_id
            WHERE pi.production_header_id = (
                SELECT production_header_id FROM production_outputs WHERE id = ?
            )
            GROUP BY pi.item_id, mm.kode_material, sf.kode_barang, fg.kode_barang, 
                    mm.nama_material, sf.nama_barang, fg.nama_barang, 
                    mm.satuan, sf.satuan, fg.satuan
            ORDER BY pi.id ASC
        ", [$production_output_id])->result_array();

        // 5️⃣ Return data untuk ditampilkan di view
        return [
            'production' => $prod,
            'materials' => $materials
        ];
    }
    public function get_wh_trace($ledger_id)
    {
        // ambil data produksi terkait ledger
        $this->db->select("
            po.id AS production_output_id,
            po.stage_name AS process_name,
            ph.production_number,
            wo.wo_number,
            fg.nama_barang AS fg_name,
            fg.id AS fg_id
        ");
        $this->db->from('production_outputs po');
        $this->db->join('production_header ph', 'ph.id = po.production_header_id', 'left');
        $this->db->join('wo_header wo', 'wo.id = ph.wo_header_id', 'left');
        $this->db->join('finished_goods fg', 'fg.id = ph.fg_id', 'left');
        $this->db->where('po.id', "(SELECT production_output_id FROM stock_ledger WHERE id = {$ledger_id})", false);
        $header = $this->db->get()->row_array();

        if (!$header) {
            return [];
        }

        // Ambil material asal produksi (trace input material)
        $this->db->select("
            COALESCE(mm.kode_material, ms.scrap_code, sf.kode_barang, fg.kode_barang) AS item_code,
            COALESCE(mm.nama_material, ms.scrap_name, sf.nama_barang, fg.nama_barang) AS item_name,
            COALESCE(mm.satuan, ms.unit, sf.satuan, fg.satuan, 'PCS') AS unit,
            SUM(pi.qty_used) AS qty_used,
            MAX(sl.bc_type_code) AS bc_type_code,
            MAX(sl.bc_number) AS bc_number,
            MAX(sl.bc_date) AS bc_date,
            MAX(sl.ref_id) AS bc_header_id
        ");
        $this->db->from('production_inputs pi');
        $this->db->join('materials mm', 'mm.id = pi.item_id AND pi.item_type = "material"', 'left');
        $this->db->join('master_scrap ms', 'ms.id = pi.item_id AND pi.item_type = "scrap"', 'left');
        $this->db->join('semifinished_goods sf', 'sf.id = pi.item_id AND pi.item_type = "semifinished"', 'left');
        $this->db->join('finished_goods fg', 'fg.id = pi.item_id AND pi.item_type = "finished_goods"', 'left');
        $this->db->join('stock_on_hand soh', 'soh.id = pi.stock_on_hand_id', 'left');
        $this->db->join('stock_ledger sl', 'sl.stock_on_hand_id = soh.id AND sl.transaction_type IN ("PO", "MIR", "IN_RETURN_WIP")', 'left');
        $this->db->where('pi.production_header_id', $header['production_output_id']);
        $this->db->group_by(['pi.item_id', 'pi.item_type']);
        $materials = $this->db->get()->result_array();

        // Susun hasil
        return [[
            'production_number' => $header['production_number'],
            'wo_number'         => $header['wo_number'],
            'process_name'      => $header['process_name'],
            'fg_name'           => $header['fg_name'],
            'materials'         => $materials
        ]];
    }
    public function get_material_trace($prod_output_id)
    {
        // Cek apakah output ini punya input sebelumnya
        $this->db->select("
            po.id AS production_output_id,
            ph.production_number,
            wo.wo_number,
            po.stage_name AS process_name,
            fg.nama_barang AS fg_name
        ");
        $this->db->from('production_outputs po');
        $this->db->join('production_header ph', 'ph.id = po.production_header_id', 'left');
        $this->db->join('wo_header wo', 'wo.id = ph.wo_header_id', 'left');
        $this->db->join('finished_goods fg', 'fg.id = ph.fg_id', 'left');
        $this->db->where('po.id', $prod_output_id);
        $header = $this->db->get()->row_array();

        if (!$header) return null;

        // Cari material asal dari produksi ini
        $this->db->select("
            COALESCE(mm.kode_material, ms.scrap_code, sf.kode_barang, fg.kode_barang) AS item_code,
            COALESCE(mm.nama_material, ms.scrap_name, sf.nama_barang, fg.nama_barang) AS item_name,
            COALESCE(mm.satuan, ms.unit, sf.satuan, fg.satuan, 'PCS') AS unit,
            SUM(pi.qty_used) AS qty_used,
            MAX(sl.bc_type_code) AS bc_type_code,
            MAX(sl.bc_number) AS bc_number,
            MAX(sl.bc_date) AS bc_date,
            MAX(sl.ref_id) AS bc_header_id,
            MAX(po2.id) AS production_output_id
        ");
        $this->db->from('production_inputs pi');
        $this->db->join('materials mm', 'mm.id = pi.item_id AND pi.item_type = "material"', 'left');
        $this->db->join('master_scrap ms', 'ms.id = pi.item_id AND pi.item_type = "scrap"', 'left');
        $this->db->join('semifinished_goods sf', 'sf.id = pi.item_id AND pi.item_type = "semifinished"', 'left');
        $this->db->join('finished_goods fg', 'fg.id = pi.item_id AND pi.item_type = "finished_goods"', 'left');
        $this->db->join('stock_on_hand soh', 'soh.id = pi.stock_on_hand_id', 'left');
        $this->db->join('stock_ledger sl', 'sl.stock_on_hand_id = soh.id', 'left');
        $this->db->join('production_outputs po2', 'po2.item_id = pi.item_id AND po2.item_type = pi.item_type', 'left');
        $this->db->where('pi.production_header_id', $header['production_output_id']);
        $this->db->group_by(['pi.item_id', 'pi.item_type']);
        $materials = $this->db->get()->result_array();

        $header['materials'] = $materials;
        return [$header];
    }


    public function get_bc_trace($ledger_id)
    {
        // Cari transaksi scrap keluar (OUT_SCRAP_BC)
        $ledger = $this->db->get_where('stock_ledger', ['id' => $ledger_id])->row_array();
        if (!$ledger) return null;

        // Ambil data BC 3.0 dari scrap_out (grn_header / po_header)
        $bc_30 = $this->db
            ->select('bc_type_code, bc_number, bc_date')
            ->from('grn_header')
            ->where('bc_number', $ledger['bc_number'])
            ->get()->row_array();

        // Cari hubungan dengan dokumen ekspor (BC 2.5 / BC 4.1)
        $bc_trace = $this->db
            ->select('bd.bc_type_code, bd.bc_number, bd.bc_date, bd.destination, bd.total_qty, bd.status')
            ->from('bc_documents bd')
            ->join('stock_ledger sl', 'sl.bc_number = bd.source_bc_number', 'left')
            ->where('sl.id', $ledger_id)
            ->get()->result_array();

        return [
            'bc_30' => $bc_30,
            'bc_trace' => $bc_trace,
            'scrap_code' => $ledger['item_id']
        ];
    }




    public function get_scrap_origin_trace($scrap_in_detail_id)
    {
        $sql = "
            SELECT 
                m.scrap_name,
                po.id AS production_output_id,
                ph.production_number,
                wo.wo_number,
                mat.kode_material AS material_code,
                mat.nama_material AS material_name,
                g.bc_type_code,
                g.bc_number,
                g.bc_date,
                s.nama_supplier
            FROM scrap_in_detail sid
            JOIN scrap_in_detail_sources sds ON sds.scrap_in_detail_id = sid.id
            JOIN production_outputs po ON po.id = sds.production_output_id
            JOIN production_header ph ON ph.id = po.production_header_id
            JOIN wo_header wo ON wo.id = ph.wo_header_id
            JOIN production_input_materials pim ON pim.production_header_id = ph.id
            JOIN materials mat ON mat.id = pim.material_id
            LEFT JOIN grn_detail gd ON gd.id = pim.grn_detail_id
            LEFT JOIN grn_header g ON g.id = gd.header_id
            LEFT JOIN suppliers s ON s.id = g.supplier_id
            LEFT JOIN master_scrap m ON m.id = sid.scrap_id
            WHERE sid.id = ?
        ";
        return $this->db->query($sql, [$scrap_in_detail_id])->result_array();
    }

    /**
     * get_traceability
     * Mengambil ledger history untuk item scrap tertentu
     * return array of assoc, ordered asc by date
     */
    public function get_traceability($item_id) {
        $this->db->reset_query();

        // SELECT bersih tanpa komentar SQL
        $this->db->select("
            sl.id,
            sl.transaction_date,
            sl.transaction_type,
            sl.transaction_id,
            sl.source_doc_type,
            sl.source_doc_id,
            sl.qty_in,
            sl.qty_out,
            sl.balance,
            sl.bc_type_code,
            sl.bc_number,
            sl.bc_date,
            po.id AS production_output_id,
            ph.production_number,
            ph.id AS production_header_id,
            woh.wo_number,
            soh.id AS scrap_out_id,
            soh.doc_no AS scrap_out_docno,
            soh.destination AS scrap_out_destination_id,
            dest.nama_customer AS scrap_out_destination_name,
            c.nama_customer AS partner_customer,
            sup.nama_supplier AS partner_supplier
        ", false);

        $this->db->from($this->ledger . ' sl');

        // JOIN production (IN SCRAP PROD)
        $this->db->join('production_outputs po', 'sl.source_doc_type IN ("PRODUCTION_OUTPUT","PROD_OUTPUT","IN_SCRAP_PROD") AND po.id = sl.source_doc_id', 'left');
        $this->db->join('production_header ph', 'po.production_header_id = ph.id', 'left');
        $this->db->join('wo_header woh', 'ph.wo_header_id = woh.id', 'left');

        // JOIN scrap out (OUT SCRAP)
        $this->db->join('scrap_out_header soh', 'sl.source_doc_type IN ("SCRAP_OUT","OUT_SCRAP_BC","OUT_SCRAP_SALE") AND soh.id = sl.source_doc_id', 'left');
        $this->db->join('customers dest', 'soh.destination = dest.id', 'left');

        // Jika ledger simpan doc_no bukan id
        $this->db->join('scrap_out_header soh_doc', 'sl.source_doc_type IN ("SCRAP_OUT","OUT_SCRAP_BC","OUT_SCRAP_SALE") AND soh_doc.doc_no = sl.source_doc_id', 'left');
        $this->db->join('customers dest_doc', 'soh_doc.destination = dest_doc.id', 'left');

        // Partner fallback
        $this->db->join('customers c', 'sl.source_doc_type IN ("SCRAP_OUT") AND c.id = soh.destination', 'left');
        $this->db->join('suppliers sup', 'sl.source_doc_type IN ("GRN","PO") AND sup.id = sl.source_doc_id', 'left');

        $this->db->where('sl.item_type', 'scrap');
        $this->db->where('sl.item_id', (int)$item_id);
        $this->db->order_by('sl.transaction_date', 'ASC');
        $this->db->order_by('sl.id', 'ASC');

        $q = $this->db->get();

        if (!$q) {
            log_message('error', 'LapMutasiScrap_model::get_traceability error: ' . print_r($this->db->error(), true));
            return [];
        }

        $rows = $q->result_array();

        // Normalisasi data partner dan destinasi
        foreach ($rows as &$r) {
            $r['partner'] = '';
            if (!empty($r['partner_customer'])) $r['partner'] = $r['partner_customer'];
            elseif (!empty($r['partner_supplier'])) $r['partner'] = $r['partner_supplier'];

            if (!empty($r['scrap_out_destination_name'])) {
                $r['destination_name'] = $r['scrap_out_destination_name'];
            } elseif (!empty($r['dest_doc.nama_customer'] ?? null)) {
                $r['destination_name'] = $r['dest_doc.nama_customer'];
            } else {
                $r['destination_name'] = $r['partner'] ?: '';
            }
        }

        return $rows;
    }


    /**
     * get_deep_production
     * Mengambil info produksi & material used berdasarkan production_output_id
     * Return ['header_info' => object, 'materials_used' => array]
     */
    public function get_deep_production($production_output_id) {
        $out = ['header_info' => (object)[], 'materials_used' => []];
        $id = (int)$production_output_id;
        if (!$id) return $out;

        // 1) Try find in production_outputs (most likely)
        $this->db->reset_query();
        $this->db->select('po.id AS production_output_id, po.stage_name, ph.id AS production_header_id, ph.production_number, ph.production_date, ph.fg_item_id, fg.scrap_code AS fg_code, fg.scrap_name AS fg_name, ph.wo_header_id, wo.wo_number, ph.sales_contract_id, sc.contract_number AS sales_contract', false);
        $this->db->from('production_outputs po');
        $this->db->join('production_header ph', 'po.production_header_id = ph.id', 'left');
        $this->db->join('wo_header wo', 'ph.wo_header_id = wo.id', 'left');
        $this->db->join('sales_contract sc', 'ph.sales_contract_id = sc.id', 'left');
        $this->db->join('master_scrap fg', 'ph.fg_item_id = fg.id', 'left');
        $this->db->where('po.id', $id);
        $hdr = $this->db->get()->row();

        if (!$hdr) {
            // fallback: maybe id is production_header id
            $this->db->reset_query();
            $this->db->select('ph.id AS production_header_id, ph.production_number, ph.production_date, ph.fg_item_id, fg.scrap_code AS fg_code, fg.scrap_name AS fg_name, ph.wo_header_id, wo.wo_number, ph.sales_contract_id, sc.contract_number AS sales_contract', false);
            $this->db->from('production_header ph');
            $this->db->join('wo_header wo', 'ph.wo_header_id = wo.id', 'left');
            $this->db->join('sales_contract sc', 'ph.sales_contract_id = sc.id', 'left');
            $this->db->join('master_scrap fg', 'ph.fg_item_id = fg.id', 'left');
            $this->db->where('ph.id', $id);
            $hdr = $this->db->get()->row();
        }

        $out['header_info'] = $hdr ?: (object)[];

        // 2) Materials used: try production_inputs referencing production_output_id or production_header_id
        $this->db->reset_query();
        $this->db->select('pi.item_id, m.scrap_code AS item_code, m.scrap_name AS item_name, pi.qty_used, pi.unit', false);
        $this->db->from('production_inputs pi');
        $this->db->join('master_scrap m', 'pi.item_id = m.id', 'left');
        $this->db->where('pi.production_output_id', $id);
        $q = $this->db->get();
        if ($q && $q->num_rows() > 0) {
            $out['materials_used'] = $q->result_array();
        } else {
            // fallback by production_header_id if available
            $ph_id = $hdr->production_header_id ?? null;
            if ($ph_id) {
                $this->db->reset_query();
                $this->db->select('pi.item_id, m.scrap_code AS item_code, m.scrap_name AS item_name, pi.qty_used, pi.unit', false);
                $this->db->from('production_inputs pi');
                $this->db->join('master_scrap m', 'pi.item_id = m.id', 'left');
                $this->db->where('pi.production_header_id', $ph_id);
                $res = $this->db->get();
                if ($res && $res->num_rows() > 0) $out['materials_used'] = $res->result_array();
            }
        }

        // 3) Try attach ex-BC info for each material by looking at the ledger incoming records for that material prior to production_date.
        if (!empty($out['materials_used']) && !empty($out['header_info']->production_date)) {
            $prod_date = $out['header_info']->production_date;
            foreach ($out['materials_used'] as &$mat) {
                $mat['ex_bc_type'] = null;
                $mat['ex_bc_number'] = null;
                $mat['ex_bc_date'] = null;

                // find last IN ledger for that material before prod_date
                $this->db->reset_query();
                $this->db->select('bc_type_code, bc_number, bc_date, transaction_date')
                         ->from($this->ledger)
                         ->where('item_type', 'material')
                         ->where('item_id', $mat['item_id'])
                         ->where('qty_in >', 0)
                         ->where('transaction_date <=', $prod_date . ' 23:59:59')
                         ->order_by('transaction_date', 'DESC')
                         ->order_by('id', 'DESC')
                         ->limit(1);
                $r = $this->db->get()->row();
                if ($r) {
                    $mat['ex_bc_type'] = $r->bc_type_code ?? null;
                    $mat['ex_bc_number'] = $r->bc_number ?? null;
                    $mat['ex_bc_date'] = isset($r->transaction_date) ? substr($r->transaction_date,0,10) : ($r->bc_date ?? null);
                }
            }
            unset($mat);
        }

        return $out;
    }

}
