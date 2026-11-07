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

        // Subquery opname: untuk mendapat hasil pencacahan terakhir
        $this->db->select("item_id, qty_fisik", false)
                 ->from('opname_details od')
                 ->join('opname_header oh', 'od.opname_header_id = oh.id', 'inner')
                 ->where('oh.status', 'Completed')
                 ->where('od.item_type', 'scrap');
        if ($start_date) $this->db->where('oh.opname_date >=', $start_date);
        if ($end_date) $this->db->where('oh.opname_date <=', $end_date);
        $this->db->where('oh.opname_date = (SELECT MAX(oh_inner.opname_date) 
                         FROM opname_details od_inner
                         JOIN opname_header oh_inner ON od_inner.opname_header_id = oh_inner.id
                         WHERE od_inner.item_id = od.item_id 
                           AND od_inner.item_type = "scrap"
                           AND oh_inner.status = "Completed")');
        $sub_opname = $this->db->get_compiled_select();
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
                 ORDER BY sl2.id DESC LIMIT 1), IFNULL(o.opening_stock,0)) AS closing_stock,
            op.qty_fisik AS hasil_pencacahan,
            CASE 
                WHEN op.qty_fisik IS NOT NULL THEN 
                    IFNULL((SELECT sl3.balance FROM {$this->ledger} sl3
                         WHERE sl3.item_id = s.id AND sl3.item_type = 'scrap'
                         AND sl3.transaction_date <= {$safe_end}
                         ORDER BY sl3.id DESC LIMIT 1), IFNULL(o.opening_stock,0)) - op.qty_fisik
                ELSE NULL
            END AS jumlah_selisih
        ", false)
        ->from("{$this->master} s")
        ->join("({$sub_opening}) o", 's.id=o.item_id', 'left')
        ->join("({$sub_mutation}) m", 's.id=m.item_id', 'left')
        ->join("({$sub_opname}) op", 's.id=op.item_id', 'left')
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
        // Cek apakah output ini punya input sebelumnya (SCRAP OUTPUT)
        $this->db->select("
            po.id AS production_output_id,
            po.item_id AS scrap_id,
            ms.scrap_code,
            ms.scrap_name,
            ms.unit,
            po.qty_scrap AS qty_output,
            ph.production_number,
            wo.wo_number,
            po.stage_name AS process_name,
            fg.nama_barang AS fg_name,
            po.qc_description,
            po.qc_photo_url
        ");
        $this->db->from('production_outputs po');
        $this->db->join('production_header ph', 'ph.id = po.production_header_id', 'left');
        $this->db->join('wo_header wo', 'wo.id = ph.wo_header_id', 'left');
        $this->db->join('finished_goods fg', 'fg.id = ph.fg_id', 'left');
        $this->db->join('master_scrap ms', 'ms.id = po.item_id AND po.item_type = "scrap"', 'left');
        $this->db->where('po.id', $prod_output_id);
        $header = $this->db->get()->row_array();

        if (!$header) return null;

        // Cari SOURCE material dari scrap ini (dari production_scrap_sources)
        $this->db->select("
            pss.id AS source_id,
            pss.source_qty,
            pi.qty_used AS source_qty_input,
            COALESCE(mm.kode_material, sf.kode_barang) AS item_code,
            COALESCE(mm.nama_material, sf.nama_barang) AS item_name,
            pi.item_type,
            COALESCE(mm.satuan, sf.satuan, 'PCS') AS unit
        ");
        $this->db->from('production_scrap_sources pss');
        $this->db->join('production_inputs pi', 'pi.id = pss.source_input_id', 'left');
        $this->db->join('materials mm', 'mm.id = pi.item_id AND pi.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'sf.id = pi.item_id AND pi.item_type = "semifinished"', 'left');
        $this->db->where('pss.production_output_id', $prod_output_id);
        $this->db->order_by('pss.id', 'ASC');
        $scrap_sources = $this->db->get()->result_array();

        $header['scrap_sources'] = $scrap_sources;
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
     * Mengambil history transaksi untuk item scrap tertentu dari berbagai sumber
     * return array of assoc, ordered by date
     */
    public function get_traceability($item_id) {
        // Query langsung dari stock_ledger untuk mendapatkan saldo yang benar dan urutan yang tepat
        $sql = "
        SELECT 
            sl.id as ledger_id,
            sl.id,
            sl.transaction_type,
            sl.transaction_date,
            sl.transaction_id,
            sl.qty_in,
            sl.qty_out,
            sl.balance,
            
            -- Dokumen Sumber dengan format yang benar
            CASE 
                WHEN sl.transaction_type = 'IN_SCRAP_WH' THEN 'SCRAP_IN'
                WHEN sl.transaction_type LIKE 'OUT_SCRAP%' THEN 'SCRAP_OUT'
                WHEN sl.transaction_type = 'ADJUSTMENT' THEN 'ADJ'
                ELSE sl.transaction_type
            END as source_doc_type,
            
            -- Source Doc Number (bukan ID)
            CASE 
                WHEN sl.transaction_type = 'IN_SCRAP_WH' THEN sih.doc_no
                WHEN sl.transaction_type LIKE 'OUT_SCRAP%' THEN soh.doc_no
                WHEN sl.transaction_type = 'ADJUSTMENT' THEN ah.doc_no
                ELSE CAST(sl.transaction_id AS CHAR)
            END as source_doc_no,
            
            sl.transaction_id as source_doc_id,
            
            -- BC Info
            COALESCE(soh.bc_type_code, NULL) as bc_type_code,
            COALESCE(soh.bc_number, NULL) as bc_number,
            COALESCE(soh.bc_date, NULL) as bc_date,
            
            -- Production info untuk IN_SCRAP_WH
            po.id as production_output_id,
            ph.id as production_header_id,
            ph.production_number,
            
            -- WO info
            wo.id as work_order_id,
            wo.wo_number,
            
            -- Scrap IN info
            sih.id as scrap_in_id,
            sih.doc_no as scrap_in_docno,
            
            -- Scrap OUT info
            soh.id as scrap_out_id,
            soh.doc_no as scrap_out_docno,
            soh.destination as scrap_out_destination_id,
            CASE 
                WHEN c.nama_customer IS NOT NULL THEN c.nama_customer
                WHEN soh.destination IS NOT NULL THEN soh.destination
                ELSE NULL
            END as scrap_out_destination_name,
            
            -- Partner names (untuk IN dan OUT) - DIPERBAIKI
            CASE 
                WHEN sl.transaction_type = 'IN_SCRAP_WH' AND ph.production_number IS NOT NULL 
                    THEN ph.production_number  -- nomor sudah berawalan PROD/ (dulu jadi PROD/PROD/...)
                WHEN sl.transaction_type LIKE 'OUT_SCRAP%' AND c.nama_customer IS NOT NULL 
                    THEN c.nama_customer
                WHEN sl.transaction_type LIKE 'OUT_SCRAP%' 
                    THEN soh.destination
                ELSE NULL
            END as partner_name,
            
            -- Adjustment info
            ah.id as adjustment_header_id,
            ah.doc_no as adjustment_doc_no,
            ah.notes as adjustment_notes
        FROM stock_ledger sl
        LEFT JOIN scrap_in_header sih ON sl.transaction_type = 'IN_SCRAP_WH' AND sl.transaction_id = sih.id
        LEFT JOIN scrap_in_detail sid ON sih.id = sid.header_id
        LEFT JOIN scrap_in_detail_sources sids ON sid.id = sids.scrap_in_detail_id
        LEFT JOIN production_outputs po ON sids.production_output_id = po.id
        LEFT JOIN production_header ph ON po.production_header_id = ph.id
        LEFT JOIN scrap_out_header soh ON (
            sl.transaction_type LIKE 'OUT_SCRAP%' AND sl.transaction_id = soh.id
        )
        LEFT JOIN customers c ON soh.destination = c.id
        LEFT JOIN wo_header wo ON sih.work_order_id = wo.id
        LEFT JOIN adjustment_header ah ON sl.transaction_type = 'ADJUSTMENT' AND sl.transaction_id = ah.id
        WHERE sl.item_id = ? AND sl.item_type = 'scrap'
        ORDER BY sl.transaction_date ASC, sl.id ASC
        ";
        
        $query = $this->db->query($sql, [$item_id]);
        
        if (!$query) {
            log_message('error', 'LapMutasiScrap_model::get_traceability error: ' . print_r($this->db->error(), true));
            return [];
        }
        
        $results = $query->result_array();
        
        // DEBUG: Log hasil query untuk OUT_SCRAP
        foreach ($results as $row) {
            if (stripos($row['transaction_type'], 'OUT_SCRAP') !== false) {
                log_message('debug', 'get_traceability OUT_SCRAP row: '.json_encode([
                    'ledger_id' => $row['id'],
                    'type' => $row['transaction_type'],
                    'transaction_id' => $row['transaction_id'],
                    'scrap_out_id' => $row['scrap_out_id'],
                    'scrap_out_docno' => $row['scrap_out_docno']
                ]));
            }
        }
        
        return $results;
    }


    /**
     * get_deep_production
     * Mengambil info produksi & material used berdasarkan production_output_id
     * Return ['header_info' => object, 'materials_used' => array]
     */
    /**
     * Detail "Deep Trace Produksi" untuk SATU baris scrap hasil produksi.
     *
     * $production_output_id = production_outputs.id (dikirim tombol trace di
     * laporan mutasi scrap).
     *
     * Material yang ditampilkan diambil dari `production_scrap_sources`, yaitu
     * material yang DITUNJUK user sebagai asal scrap itu beserta qty-nya —
     * bukan seluruh input produksi. Sebelum 2026-09-21 fungsi ini memakai
     * `WHERE pi.production_header_id = ...` sehingga menampilkan semua material
     * di semua tahap dengan qty pemakaian penuh (user: "harusnya yang muncul
     * ya yang saya input itu aja"). Contoh nyata: output scrap 144 asalnya 1
     * baris (SPRING 10) tapi laporan menampilkan SPRING 111 + HC 4251 111.
     *
     * BC/supplier ditelusuri lewat batch yang benar-benar dipakai, berurutan:
     *   1. production_inputs.stock_on_hand_id (input langsung dari batch gudang)
     *   2. batch yang dikeluarkan utk MRF work order ini (issuance_details)
     * Kalau tidak ketemu, dikosongkan — TIDAK menebak dari GRN lain yang
     * kebetulan memuat item sama (perilaku lama, bisa salah BC & menggandakan
     * baris kalau item pernah diterima beberapa kali).
     *
     * @return array [
     *   'header_info'    => object,
     *   'materials_used' => array baris material,
     *   'source_mode'    => 'declared' (dari input user) | 'fallback' (belum dirinci),
     * ]
     */
    public function get_deep_production($production_output_id) {
        $out = ['header_info' => (object)[], 'materials_used' => [], 'source_mode' => 'declared'];
        $id = (int)$production_output_id;
        if (!$id) return $out;

        // 1) Header: id yang dikirim laporan adalah production_outputs.id.
        $this->db->reset_query();
        $this->db->select('po.id AS production_output_id, po.stage_name, po.qty_produced AS scrap_qty,
                           ph.id AS production_header_id, ph.production_number, ph.production_date,
                           ph.fg_id, fg.scrap_code AS fg_code, fg.scrap_name AS fg_name,
                           ph.wo_header_id, wo.wo_number', false);
        $this->db->from('production_outputs po');
        $this->db->join('production_header ph', 'po.production_header_id = ph.id', 'left');
        $this->db->join('wo_header wo', 'ph.wo_header_id = wo.id', 'left');
        $this->db->join('master_scrap fg', 'ph.fg_id = fg.id', 'left');
        $this->db->where('po.id', $id);
        $hdr = $this->db->get()->row();

        $output_id = $hdr ? (int) $hdr->production_output_id : 0;
        if (!$hdr) {
            // fallback lama: id ternyata production_header.id
            $this->db->reset_query();
            $this->db->select('ph.id AS production_header_id, ph.production_number, ph.production_date, ph.fg_id, fg.scrap_code AS fg_code, fg.scrap_name AS fg_name, ph.wo_header_id, wo.wo_number', false);
            $this->db->from('production_header ph');
            $this->db->join('wo_header wo', 'ph.wo_header_id = wo.id', 'left');
            $this->db->join('master_scrap fg', 'ph.fg_id = fg.id', 'left');
            $this->db->where('ph.id', $id);
            $hdr = $this->db->get()->row();
        }
        $out['header_info'] = $hdr ?: (object)[];
        if (!$hdr) return $out;

        $ph_id = $hdr->production_header_id ?? null;
        if (!$ph_id) return $out;

        // 2) Material asal scrap = yang ditunjuk user di production_scrap_sources.
        $rows = [];
        if ($output_id) {
            $this->db->reset_query();
            $this->db->select("
                pss.source_qty AS qty_used,
                pi.qty_used    AS qty_input_total,
                pi.stage_name,
                pi.item_id, pi.item_type, pi.stock_on_hand_id, pi.mr_detail_id,
                COALESCE(mm.kode_material, sf.kode_barang, fgd.kode_barang) AS item_code,
                COALESCE(mm.nama_material, sf.nama_barang, fgd.nama_barang) AS item_name,
                COALESCE(mm.satuan, sf.satuan, fgd.satuan, 'PCS') AS unit
            ", false);
            $this->db->from('production_scrap_sources pss');
            $this->db->join('production_inputs pi', 'pi.id = pss.source_input_id');
            $this->db->join('materials mm', 'mm.id = pi.item_id AND pi.item_type = "material"', 'left');
            $this->db->join('semifinished_goods sf', 'sf.id = pi.item_id AND pi.item_type = "semifinished"', 'left');
            $this->db->join('finished_goods fgd', 'fgd.id = pi.item_id AND pi.item_type = "finished_goods"', 'left');
            $this->db->where('pss.production_output_id', $output_id);
            $this->db->order_by('pss.id', 'ASC');
            $rows = $this->db->get()->result_array();
        }

        // 3) Dokumen lama belum punya rincian asal scrap -> tampilkan input
        //    TAHAP YANG SAMA saja (bukan seluruh produksi), ditandai supaya
        //    pembaca tahu ini perkiraan, bukan pilihan user.
        if (empty($rows)) {
            $out['source_mode'] = 'fallback';
            $this->db->reset_query();
            $this->db->select("
                pi.qty_used,
                pi.qty_used AS qty_input_total,
                pi.stage_name,
                pi.item_id, pi.item_type, pi.stock_on_hand_id, pi.mr_detail_id,
                COALESCE(mm.kode_material, sf.kode_barang, fgd.kode_barang) AS item_code,
                COALESCE(mm.nama_material, sf.nama_barang, fgd.nama_barang) AS item_name,
                COALESCE(mm.satuan, sf.satuan, fgd.satuan, 'PCS') AS unit
            ", false);
            $this->db->from('production_inputs pi');
            $this->db->join('materials mm', 'mm.id = pi.item_id AND pi.item_type = "material"', 'left');
            $this->db->join('semifinished_goods sf', 'sf.id = pi.item_id AND pi.item_type = "semifinished"', 'left');
            $this->db->join('finished_goods fgd', 'fgd.id = pi.item_id AND pi.item_type = "finished_goods"', 'left');
            $this->db->where('pi.production_header_id', $ph_id);
            if (!empty($hdr->stage_name)) $this->db->where('pi.stage_name', $hdr->stage_name);
            $this->db->order_by('pi.id', 'ASC');
            $rows = $this->db->get()->result_array();
        }

        // 4) BC & supplier per baris, dari batch yang memang dipakai.
        foreach ($rows as &$r) {
            $bc = $this->_trace_input_origin($r, $hdr->wo_header_id ?? null);
            $r['bc_type_code']  = $bc['bc_type_code'];
            $r['bc_number']     = $bc['bc_number'];
            $r['bc_date']       = $bc['bc_date'];
            $r['supplier_name'] = $bc['supplier_name'];
            // Nama kolom lama tetap diisi supaya export Excel/PDF tidak berubah.
            $r['ex_bc_type']    = $bc['bc_type_code'];
            $r['ex_bc_number']  = $bc['bc_number'];
            $r['ex_bc_date']    = $bc['bc_date'];
        }
        unset($r);

        $out['materials_used'] = $rows;
        return $out;
    }

    /**
     * Asal (BC + supplier) satu baris input produksi, dari batch penerimaan
     * yang benar-benar dipakai. Mengembalikan nilai kosong kalau tidak bisa
     * ditelusuri — lebih baik kosong daripada menebak BC milik penerimaan lain.
     */
    private function _trace_input_origin(array $row, $wo_header_id = null) {
        $empty = ['bc_type_code' => null, 'bc_number' => null, 'bc_date' => null, 'supplier_name' => null];

        // a) Input diambil langsung dari batch gudang.
        if (!empty($row['stock_on_hand_id'])) {
            $r = $this->db->query("
                SELECT soh.bc_type_code, soh.bc_number, soh.bc_date,
                       COALESCE(s.nama_supplier, s_mir.nama_supplier) AS supplier_name
                FROM stock_on_hand soh
                LEFT JOIN grn_details gd ON gd.id = soh.grn_detail_id
                LEFT JOIN grn_header gh  ON gh.id = COALESCE(soh.grn_header_id, gd.grn_header_id)
                LEFT JOIN suppliers s    ON s.id = gh.supplier_id
                LEFT JOIN mir_header mh  ON mh.id = gd.source_doc_header_id AND gd.source_doc_type = 'MIR'
                LEFT JOIN suppliers s_mir ON s_mir.id = mh.supplier_id
                WHERE soh.id = ?
                LIMIT 1", [(int) $row['stock_on_hand_id']])->row_array();
            if ($r) return array_merge($empty, $r);
        }

        // b) Material WIP: ditelusuri lewat batch yang BENAR-BENAR dikeluarkan
        //    gudang untuk work order ini. Pengeluaran material mencatat batch
        //    FIFO-nya di stock_ledger (transaction_type OUT_ISSUE +
        //    stock_on_hand_id) — lihat Material_issuance_model::process_issuance().
        //    Satu material bisa terpakai dari beberapa batch -> semuanya ditampilkan.
        $conds = ["sl.transaction_type = 'OUT_ISSUE'", 'sl.item_id = ?', 'sl.item_type = ?'];
        $args  = [(int) $row['item_id'], (string) $row['item_type']];
        if (!empty($row['mr_detail_id'])) {
            $conds[] = 'sl.transaction_id = (SELECT mr_header_id FROM mr_details WHERE id = ?)';
            $args[]  = (int) $row['mr_detail_id'];
        } elseif ($wo_header_id) {
            $conds[] = 'sl.source_doc_id = ?';
            $args[]  = (int) $wo_header_id;
        } else {
            return $empty;
        }

        $rows = $this->db->query("
            SELECT DISTINCT soh.bc_type_code, soh.bc_number, soh.bc_date,
                   COALESCE(s.nama_supplier, s_mir.nama_supplier) AS supplier_name
            FROM stock_ledger sl
            JOIN stock_on_hand soh   ON soh.id = sl.stock_on_hand_id
            LEFT JOIN grn_details gd ON gd.id = soh.grn_detail_id
            LEFT JOIN grn_header gh  ON gh.id = COALESCE(soh.grn_header_id, gd.grn_header_id)
            LEFT JOIN suppliers s    ON s.id = gh.supplier_id
            LEFT JOIN mir_header mh  ON mh.id = gd.source_doc_header_id AND gd.source_doc_type = 'MIR'
            LEFT JOIN suppliers s_mir ON s_mir.id = mh.supplier_id
            WHERE " . implode(' AND ', $conds) . "
              AND sl.stock_on_hand_id IS NOT NULL
        ", $args)->result_array();

        if (empty($rows)) return $empty;
        if (count($rows) === 1) return array_merge($empty, $rows[0]);

        // Beberapa batch: tampilkan semuanya, jangan pilih salah satu.
        $join = function ($key) use ($rows) {
            $v = array_values(array_unique(array_filter(array_column($rows, $key))));
            return $v ? implode(', ', $v) : null;
        };
        return [
            'bc_type_code'  => $join('bc_type_code'),
            'bc_number'     => $join('bc_number'),
            'bc_date'       => $join('bc_date'),
            'supplier_name' => $join('supplier_name'),
        ];
    }

}
