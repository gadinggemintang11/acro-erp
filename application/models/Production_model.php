<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Production_model extends CI_Model {

    private $table_header = 'production_header';
    private $table_inputs = 'production_inputs';
    private $table_outputs = 'production_outputs';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        // Load Stock_ledger_model HANYA jika benar-benar dibutuhkan di fungsi tertentu
        // $this->load->model('Stock_ledger_model');
    }

    // === HEADER ===
    public function get_all($filters = []) {
        $this->db->select('
            p.id, p.production_number, p.production_date, p.planned_qty, p.status,
            wo.wo_number, sc.sc_number, fg.nama_barang as fg_name, fg.kode_barang as fg_code, fg.satuan as fg_unit
        ');
        $this->db->from($this->table_header . ' p');
        $this->db->join('wo_header wo', 'p.wo_header_id = wo.id', 'left');
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join('finished_goods fg', 'p.fg_id = fg.id', 'left');

        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('p.production_number', $term);
            $this->db->or_like('wo.wo_number', $term);
            $this->db->or_like('sc.sc_number', $term);
            $this->db->or_like('fg.nama_barang', $term);
            $this->db->group_end();
        }
        if (!empty($filters['status'])) {
            $this->db->where('p.status', $filters['status']);
        }

        $this->db->order_by('p.id', 'DESC');
        $query = $this->db->get();
        return $query ? $query->result() : []; // Return array kosong jika gagal
    }

    /** Jumlah dokumen produksi per status (kartu ringkasan di daftar). */
    public function count_by_status() {
        $out = ['all' => 0, 'Draft' => 0, 'In Progress' => 0, 'Completed' => 0];
        $rows = $this->db->select('status, COUNT(*) AS n', FALSE)
            ->from($this->table_header)->group_by('status')->get()->result();
        foreach ($rows as $r) {
            $out['all'] += (int) $r->n;
            if (isset($out[$r->status])) $out[$r->status] = (int) $r->n;
        }
        return $out;
    }

    public function get_header_by_id($id) {
         $this->db->select('
            p.*,
            wo.wo_number, sc.sc_number,
            fg.nama_barang as fg_name, fg.kode_barang as fg_code, fg.satuan as fg_unit,
            creator.fullname as creator_name,
            confirmer.fullname as confirmer_name
        ');
        $this->db->from($this->table_header . ' p');
        $this->db->join('wo_header wo', 'p.wo_header_id = wo.id', 'left');
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join('finished_goods fg', 'p.fg_id = fg.id', 'left');
        $this->db->join('users creator', 'p.created_by = creator.id', 'left');
        $this->db->join('users confirmer', 'p.confirmed_by = confirmer.id', 'left');
        $this->db->where('p.id', $id);
        $query = $this->db->get();
         return $query ? $query->row() : null; // Return null jika tidak ditemukan
    }

    /**
     * Output Barang Jadi WAJIB sama dengan Finished Good pada Work Order-nya.
     *
     * Sebelum ini FG output dipilih bebas dari master lewat select2: kalau WO
     * dibuat untuk FG A tapi operator memilih FG B, hasil produksi tidak
     * pernah ketemu dengan kebutuhan di WO/IPO dan tidak ada apa pun yang
     * menahannya (user 2026-09-30: "andai wo nya fg A sedangkan di production
     * proses nya nanti kita salah input FG B maka nanti ga akan ketemu itu
     * kebutuhan produksi dengan hasil produksinya").
     *
     * Ditaruh di model supaya aturannya SATU tempat — dipakai baik oleh
     * save_finishing_output() maupun _save_stage_output(), dan bisa diuji
     * tanpa melewati redirect/flashdata.
     *
     * Tipe output selain finished_goods (semifinished, scrap) tidak diperiksa.
     *
     * @return array ['ok' => bool, 'message' => string]
     */
    public function check_output_fg($prod_id, $item_id, $item_type) {
        if ($item_type !== 'finished_goods') {
            return ['ok' => true, 'message' => ''];
        }

        $header = $this->get_header_by_id($prod_id);
        if (!$header) {
            return ['ok' => false, 'message' => 'Dokumen produksi tidak ditemukan.'];
        }
        if (empty($header->fg_id)) {
            return ['ok' => false, 'message' =>
                'Work Order ini tidak punya Finished Good, output tidak bisa dicatat.'];
        }
        if ((int) $item_id !== (int) $header->fg_id) {
            return ['ok' => false, 'message' =>
                'Barang Jadi tidak sesuai Work Order. ' . ($header->wo_number ?: 'Work Order') .
                ' dibuat untuk "' . ($header->fg_name ?: '-') . '"' .
                (!empty($header->sc_number) ? ' (Sales Order ' . $header->sc_number . ')' : '') .
                ', jadi output produksinya harus barang itu juga.'];
        }
        return ['ok' => true, 'message' => ''];
    }

    public function insert_header($data) {
        $insert = $this->db->insert($this->table_header, $data);
        return $insert ? $this->db->insert_id() : false; // Return false jika gagal
    }

    public function update_header($id, $data) {
        unset($data['status'], $data['confirmed_by'], $data['confirmed_at']);
        return $this->db->where('id', $id)->update($this->table_header, $data); // Akan return true/false
    }

    /**
     * Delete Header + Reversal Stok (Memanggil Stock_ledger_model)
     */
    public function delete_header($id) {
        $this->load->model('Stock_ledger_model');
        $this->db->trans_start();
        $inputs = $this->db->get_where($this->table_inputs, ['production_header_id' => $id])->result();
        $outputs = $this->db->get_where($this->table_outputs, ['production_header_id' => $id])->result();
        $now = date('Y-m-d H:i:s');

        foreach ($inputs as $input) {
            // ============ PERBAIKAN LOGIKA DELETE ============
            // Kita harus mereversal SEMUA wip_out, bukan hanya yg dari SOH
            // Pemakaian SFG (source_output_id) juga membuat ledger wip_out
            $ledger_reversal_type = '';
            if (!empty($input->stock_on_hand_id)) {
                $ledger_reversal_type = 'wip_in_reversal'; // Kembali ke SOH
            } else if (!empty($input->source_output_id)) {
                $ledger_reversal_type = 'wip_in_reversal_sfg'; // Kembali ke WIP SFG
            }

            if ($ledger_reversal_type) {
                $ledger_reversal = [
                    'item_id'           => $input->item_id,
                    'item_type'         => $input->item_type,
                    'transaction_date'  => $now,
                    'transaction_type'  => $ledger_reversal_type,
                    'transaction_id'    => $input->id,
                    'source_doc_type'   => 'PROD_DELETE',
                    'source_doc_id'     => $id,
                    'qty_in'            => $input->qty_used,
                    'qty_out'           => 0,
                    'stock_on_hand_id'  => $input->stock_on_hand_id // Akan NULL jika SFG, dan itu benar
                ];
                $this->Stock_ledger_model->insert($ledger_reversal);
            }
            // ============ AKHIR PERBAIKAN ============
        }

        foreach ($outputs as $output) {
            if ($output->item_type == 'semifinished' && $output->qty_produced > 0) {
                $ledger_reversal = [
                    'item_id'           => $output->item_id,
                    'item_type'         => $output->item_type,
                    'transaction_date'  => $now,
                    'transaction_type'  => 'wip_out_reversal',
                    'transaction_id'    => $output->id,
                    'source_doc_type'   => 'PROD_DELETE',
                    'source_doc_id'     => $id,
                    'qty_in'            => 0,
                    'qty_out'           => $output->qty_produced
                ];
                $this->Stock_ledger_model->insert($ledger_reversal);
            }
        }

        $this->db->where('production_header_id', $id)->delete($this->table_inputs);
        $this->db->where('production_header_id', $id)->delete($this->table_outputs);
        $this->db->where('id', $id)->delete($this->table_header);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Production_model->delete_header Gagal melakukan transaksi DB.');
            return false;
        }
        return true;
    }

    /**
     * Generate Nomor Produksi (Query ini HARUS cepat setelah index dibuat)
     */
    public function generate_prod_number() {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('production', 'production_header', 'production_number');
    }

    // === HELPERS ===
    /**
     * Daftar Work Order untuk modal "Pilih dari Daftar" di form Buat Proses
     * Produksi.
     *
     * Sebelumnya WO dipilih lewat select2 yang baru memunculkan hasil setelah
     * user mengetik, dan HANYA menampilkan yang memenuhi syarat -- kalau WO
     * yang dicari tidak muncul, tidak ada keterangan apa pun kenapa
     * (user 2026-09-30: "di production proses juga saat pilih wo dibuat modal
     * juga ya").
     *
     * Syarat sebuah WO bisa dibuatkan proses produksi -- sama persis dengan
     * search_open_wos() yang lama:
     *   - status WO = 'In Progress' (WO 'Planned' harus dikonfirmasi dulu)
     *   - belum punya production_header (1 WO = 1 proses produksi)
     *
     * Bedanya, di sini WO yang TIDAK memenuhi syarat ikut ditampilkan beserta
     * alasannya (tidak bisa dipilih), supaya user tidak menebak-nebak kenapa
     * WO-nya tidak ada di daftar.
     *
     * Ditambah `material_count`: jumlah baris rencana material WO. Proses
     * produksi mengambil material dari sana, jadi WO tanpa material akan
     * mentok di tahap berikutnya -- lebih baik kelihatan sejak awal.
     */
    public function get_selectable_wos($filters = []) {
        $this->db->select("
                wo.id, wo.wo_number, wo.planned_qty, wo.planned_start_date, wo.status AS wo_status,
                COALESCE(sc.sc_number, '') AS sc_number,
                COALESCE(fg.kode_barang, '') AS fg_code,
                COALESCE(fg.nama_barang, '') AS fg_name,
                (SELECT COUNT(*) FROM wo_details_material d WHERE d.wo_header_id = wo.id) AS material_count,
                (SELECT p.production_number FROM production_header p
                  WHERE p.wo_header_id = wo.id ORDER BY p.id LIMIT 1)               AS production_number
            ", FALSE)
            ->from('wo_header wo')
            ->join('sales_contracts sc', 'sc.id = wo.sales_contract_id', 'left')
            ->join('finished_goods fg', 'fg.id = wo.fg_id', 'left')
            ->where_in('wo.status', ['In Progress', 'Planned']);

        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->where("(wo.wo_number LIKE '%{$term}%'"
                          . " OR sc.sc_number LIKE '%{$term}%'"
                          . " OR fg.nama_barang LIKE '%{$term}%'"
                          . " OR fg.kode_barang LIKE '%{$term}%')", null, FALSE);
        }

        $rows = $this->db->order_by('wo.planned_start_date', 'DESC')
                         ->order_by('wo.wo_number', 'DESC')
                         ->get()->result();

        foreach ($rows as $r) {
            $r->material_count = (int) $r->material_count;
            $r->has_production = !empty($r->production_number);

            if ($r->has_production) {
                $r->eligible = false;
                $r->reason   = 'sudah punya proses produksi ' . $r->production_number;
            } elseif ($r->wo_status !== 'In Progress') {
                $r->eligible = false;
                $r->reason   = 'WO belum dikonfirmasi (masih ' . $r->wo_status . ')';
            } else {
                $r->eligible = true;
                $r->reason   = '';
            }
        }
        return $rows;
    }

    public function search_open_wos($term) {
    // Escape term hanya jika tidak kosong
        $safe_term = !empty($term) ? $this->db->escape_like_str($term) : '';

        $this->db->select('wo.id, wo.wo_number, fg.nama_barang as fg_name');
        $this->db->from('wo_header wo');
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');
        $this->db->join('production_header p', 'wo.id = p.wo_header_id', 'left'); 
        $this->db->where('wo.status', 'In Progress');
        $this->db->where('p.id IS NULL'); 

        if (!empty($safe_term)) {
            $this->db->group_start();
            $this->db->like('wo.wo_number', $safe_term);
            $this->db->or_like('fg.nama_barang', $safe_term);
            $this->db->group_end();
        }
        
        $this->db->limit(10); 
        $this->db->order_by('wo.wo_number', 'ASC'); 

        $query = $this->db->get();
        
        // --- PERBAIKAN UTAMA ADA DI SINI ---
        if (!$query) {
            log_message('error', 'Production_model->search_open_wos DB Error: ' . print_r($this->db->error(), true));
            return []; // Selalu kembalikan array kosong jika query gagal
        }
        
        // Jika query berhasil tapi tidak ada hasil, result() akan mengembalikan array kosong
        return $query->result(); 
        // --- AKHIR PERBAIKAN ---
    }

    // application/models/Production_model.php

    public function get_wo_details_for_prod($wo_id) {
        // --- AWAL PERBAIKAN ---
        // Hapus komentar dari dalam string select
        $this->db->select('
            wo.id,
            wo.planned_qty,
            sc.sc_number,
            fg.id as fg_id,
            fg.nama_barang as fg_name
        ');
        // --- AKHIR PERBAIKAN ---

        // Tabel utama adalah wo_header, alias 'wo'
        $this->db->from('wo_header wo');

        // Join ke tabel sales_contracts berdasarkan ID Sales Contract di WO
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');

        // Join ke tabel finished_goods berdasarkan ID Barang Jadi di WO
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');

        // Filter berdasarkan ID Work Order yang diberikan
        $this->db->where('wo.id', $wo_id);

        // Eksekusi query dan kembalikan satu baris hasil sebagai array asosiatif
        $query = $this->db->get();

        // Tambahkan pengecekan jika query gagal atau tidak ada hasil
        if (!$query) {
            log_message('error', 'Production_model->get_wo_details_for_prod DB Error: ' . print_r($this->db->error(), true));
            return null; // Kembalikan null jika query error
        }

        return $query->row_array(); // Kembalikan hasil (bisa null jika WO tidak ditemukan)
    }

    // =================================================================
    // WAKTU PROSES PER TAHAP (2026-11-08)
    // User: "kadang ga semua input hari ini saat user input di sistem, bisa
    // aja itu produksi lama". Tiap tahap (cutting/sewing/assembling/
    // finishing) punya Mulai & Selesai sendiri di production_stage_times.
    // Semua input/output tahap itu dicatat di stok WIP pada waktu MULAI
    // tahapnya (stage_tx_date), bukan jam user mengetik.
    // =================================================================

    /** Tabel sudah dibuat? (migration_tahap_produksi_acropoly.sql) */
    public function stage_times_ready()
    {
        static $ready = null;
        if ($ready === null) {
            $ready = $this->db->table_exists('production_stage_times');
        }
        return $ready;
    }

    /** [stage_name => row(started_at, finished_at, updated_by, updated_at, updated_by_name)] */
    public function get_stage_times($prod_id)
    {
        if (!$this->stage_times_ready()) return [];
        $rows = $this->db->select('t.*, u.fullname AS updated_by_name')
            ->from('production_stage_times t')
            ->join('users u', 'u.id = t.updated_by', 'left')
            ->where('t.production_header_id', (int) $prod_id)
            ->get()->result();
        $out = [];
        foreach ($rows as $r) $out[$r->stage_name] = $r;
        return $out;
    }

    /** Waktu mulai tahap (Y-m-d H:i:s) atau null kalau belum diisi. */
    public function get_stage_start($prod_id, $stage)
    {
        $times = $this->get_stage_times($prod_id);
        return !empty($times[$stage]->started_at) ? $times[$stage]->started_at : null;
    }

    /**
     * Tanggal transaksi stok untuk input/output tahap ini = waktu mulai
     * tahap; fallback sekarang kalau belum diisi / tabel belum ada.
     */
    public function stage_tx_date($prod_id, $stage)
    {
        $start = $this->get_stage_start($prod_id, $stage);
        return $start ?: date('Y-m-d H:i:s');
    }

    /**
     * Simpan Mulai/Selesai tahap (upsert) lalu samakan tanggal transaksi stok
     * WIP semua input/output tahap itu dengan waktu mulai yang baru, supaya
     * Laporan Mutasi WIP (filter stock_ledger.transaction_date) ikut benar.
     * Validasi format/urutan waktu dilakukan di controller.
     */
    public function save_stage_time($prod_id, $stage, $started_at, $finished_at, $user_id)
    {
        if (!$this->stage_times_ready()) return false;
        $prod_id = (int) $prod_id;
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();

        $exists = $this->db->get_where('production_stage_times',
            ['production_header_id' => $prod_id, 'stage_name' => $stage])->row();
        $data = [
            'started_at'  => $started_at,
            'finished_at' => $finished_at,
            'updated_by'  => $user_id ?: null,
            'updated_at'  => $now,
        ];
        if ($exists) {
            $this->db->where('id', $exists->id)->update('production_stage_times', $data);
        } else {
            $this->db->insert('production_stage_times', $data + [
                'production_header_id' => $prod_id,
                'stage_name'           => $stage,
                'created_at'           => $now,
            ]);
        }

        // Sinkron tanggal stok WIP: wip_out (pemakaian/input) & wip_in (hasil
        // semi-finished) yang dibuat oleh input/output tahap ini.
        $this->db->query("
            UPDATE stock_ledger sl
            JOIN {$this->table_inputs} pi ON pi.id = sl.transaction_id
            SET sl.transaction_date = ?
            WHERE sl.source_doc_type = 'PROD' AND sl.transaction_type = 'wip_out'
              AND pi.production_header_id = ? AND pi.stage_name = ?
        ", [$started_at, $prod_id, $stage]);
        $this->db->query("
            UPDATE stock_ledger sl
            JOIN {$this->table_outputs} po ON po.id = sl.transaction_id
            SET sl.transaction_date = ?
            WHERE sl.source_doc_type = 'PROD' AND sl.transaction_type = 'wip_in'
              AND po.production_header_id = ? AND po.stage_name = ?
        ", [$started_at, $prod_id, $stage]);
        // received_at hasil tahap ini juga = waktu mulai tahap (diisi begitu saat simpan output).
        $this->db->where(['production_header_id' => $prod_id, 'stage_name' => $stage])
                 ->update($this->table_outputs, ['received_at' => $started_at]);

        $this->db->trans_complete();
        return $this->db->trans_status() !== FALSE;
    }

    /**
     * Ringkasan per tahap untuk stepper & daftar produksi:
     * [prod_id][stage] => (object) started_at, finished_at, inputs, qty_in,
     * outputs, qty_good (semi-finished/FG), qty_scrap, qc_pending.
     */
    public function get_stage_overview_bulk(array $prod_ids, array $stages)
    {
        $prod_ids = array_values(array_filter(array_map('intval', $prod_ids)));
        $out = [];
        foreach ($prod_ids as $pid) {
            foreach ($stages as $st) {
                $out[$pid][$st] = (object) [
                    'started_at' => null, 'finished_at' => null,
                    'inputs' => 0, 'qty_in' => 0.0,
                    'outputs' => 0, 'qty_good' => 0.0, 'qty_scrap' => 0.0, 'qc_pending' => 0,
                ];
            }
        }
        if (empty($prod_ids)) return $out;

        $in_rows = $this->db->select('production_header_id AS pid, stage_name, COUNT(*) AS n, SUM(qty_used) AS qty', FALSE)
            ->from($this->table_inputs)->where_in('production_header_id', $prod_ids)
            ->group_by(['production_header_id', 'stage_name'])->get()->result();
        foreach ($in_rows as $r) {
            if (!isset($out[$r->pid][$r->stage_name])) continue;
            $out[$r->pid][$r->stage_name]->inputs = (int) $r->n;
            $out[$r->pid][$r->stage_name]->qty_in = (float) $r->qty;
        }

        $out_rows = $this->db->select("production_header_id AS pid, stage_name, COUNT(*) AS n,
                SUM(CASE WHEN item_type <> 'scrap' THEN qty_produced ELSE 0 END) AS qty_good,
                SUM(CASE WHEN item_type = 'scrap' THEN qty_produced ELSE 0 END) AS qty_scrap,
                SUM(CASE WHEN qc_checked_by IS NULL THEN 1 ELSE 0 END) AS qc_pending", FALSE)
            ->from($this->table_outputs)->where_in('production_header_id', $prod_ids)
            ->group_by(['production_header_id', 'stage_name'])->get()->result();
        foreach ($out_rows as $r) {
            if (!isset($out[$r->pid][$r->stage_name])) continue;
            $o = $out[$r->pid][$r->stage_name];
            $o->outputs = (int) $r->n;
            $o->qty_good = (float) $r->qty_good;
            $o->qty_scrap = (float) $r->qty_scrap;
            $o->qc_pending = (int) $r->qc_pending;
        }

        if ($this->stage_times_ready()) {
            $t_rows = $this->db->where_in('production_header_id', $prod_ids)
                ->get('production_stage_times')->result();
            foreach ($t_rows as $r) {
                if (!isset($out[$r->production_header_id][$r->stage_name])) continue;
                $out[$r->production_header_id][$r->stage_name]->started_at  = $r->started_at;
                $out[$r->production_header_id][$r->stage_name]->finished_at = $r->finished_at;
            }
        }
        return $out;
    }

    public function get_stage_overview($prod_id, array $stages)
    {
        $all = $this->get_stage_overview_bulk([$prod_id], $stages);
        return $all[(int) $prod_id] ?? [];
    }

    // === STAGES ===

    /**
     * Mengambil material WIP per BATCH dan menghitung sisa stok
     */
    public function get_wip_materials_with_remaining($wo_id) {
         $this->load->model('Stock_ledger_model'); // Load model di sini

         $this->db->reset_query();
         // 1. Dapatkan semua material yang ada di WIP (wip_in dan IN_RETURN_WIP) PLUS pending OUT_ISSUE yang belum diterima
         // IN_RETURN_WIP adalah return dari WIP ke warehouse, jadi menambah stock WIP yang available
         $this->db->select("
             CONCAT(sl.item_id, '_', sl.item_type) as batch_id,
             sl.item_id,
             sl.item_type,
             -- Total yang ada di WIP = wip_in + IN_RETURN_WIP + pending OUT_ISSUE (belum diterima)
             SUM(CASE
                 WHEN sl.transaction_type = 'wip_in' THEN sl.qty_in
                 WHEN sl.transaction_type = 'IN_RETURN_WIP' THEN sl.qty_in
                 WHEN sl.transaction_type = 'OUT_ISSUE' THEN sl.qty_out
                 ELSE 0
             END) as total_issued_to_wip,
             MAX(sl.unit_value) as cost_from_batch,
             sl.source_doc_id,
             CASE WHEN sl.item_type = 'material' THEN m.kode_material ELSE sf.kode_barang END as item_code,
             CASE WHEN sl.item_type = 'material' THEN m.nama_material ELSE sf.nama_barang END as item_name,
             CASE WHEN sl.item_type = 'material' THEN m.satuan ELSE sf.satuan END as item_unit,
             'WIP' as grn_number,
             -- Ambil BC info (bisa dari wip_in atau IN_RETURN_WIP, prioritas yang terbaru)
             -- Gunakan MAX dengan filter NOT NULL agar tidak return NULL kalau ada campuran NULL dan value
             MAX(CASE WHEN sl.bc_type_code IS NOT NULL THEN sl.bc_type_code END) as bc_type_code,
             MAX(CASE WHEN sl.bc_number IS NOT NULL THEN sl.bc_number END) as bc_number,
             MAX(CASE WHEN sl.bc_date IS NOT NULL THEN sl.bc_date END) as bc_date
         ", FALSE);
         $this->db->from('stock_ledger sl');
         $this->db->join('materials m', 'sl.item_id = m.id AND sl.item_type = "material"', 'left');
         $this->db->join('semifinished_goods sf', 'sl.item_id = sf.id AND sl.item_type = "semifinished"', 'left');
         // Include wip_in, IN_RETURN_WIP (returns to WIP), and OUT_ISSUE (pending issuances not yet received)
         $this->db->where_in('sl.transaction_type', ['wip_in', 'IN_RETURN_WIP', 'OUT_ISSUE']);
         $this->db->where('sl.source_doc_id', $wo_id); // Filter by WO ID
         $this->db->where('sl.item_id IS NOT NULL'); // Ensure item_id is not null
         $this->db->where('sl.item_id != \'\''); // Ensure item_id is not empty
         $this->db->group_by('sl.item_id, sl.item_type');

         $issued_batches = $this->db->get()->result();

         if (empty($issued_batches)) { return []; }

         // 2. Hitung total pemakaian & retur untuk setiap item (bukan per batch karena tidak ada stock_on_hand_id)
         foreach ($issued_batches as $batch) {
             $item_id = $batch->item_id;
             $item_type = $batch->item_type;

             // Total terpakai (wip_out) untuk item ini di WO ini
             $this->db->reset_query();
             $this->db->select_sum('qty_out', 'total_used');
             $this->db->where('item_id', $item_id);
             $this->db->where('item_type', $item_type);
             $this->db->where('transaction_type', 'wip_out');
             $this->db->where('source_doc_id', $wo_id); // Hanya untuk WO ini
             $usage = $this->db->get('stock_ledger')->row();
             $batch->total_used = $usage ? (float)$usage->total_used : 0;

             // Hitung total wip_in yang sudah diterima (untuk menghindari double-count dengan OUT_ISSUE)
             $this->db->reset_query();
             $this->db->select_sum('qty_in', 'total_wip_in');
             $this->db->where('item_id', $item_id);
             $this->db->where('item_type', $item_type);
             $this->db->where('transaction_type', 'wip_in');
             $this->db->where('source_doc_id', $wo_id);
             $wip_in_row = $this->db->get('stock_ledger')->row();
             $total_wip_in = $wip_in_row ? (float)$wip_in_row->total_wip_in : 0;

             // Jika ada OUT_ISSUE tapi belum ada wip_in, gunakan OUT_ISSUE (pending issuance)
             // Jika sudah ada wip_in, gunakan wip_in (untuk menghindari double-count)
             if ($total_wip_in > 0) {
                 $batch->total_issued_to_wip = $total_wip_in;
             }
             // Else: tetap gunakan total_issued_to_wip dari query (OUT_ISSUE amount)

             $batch->total_returned = 0; // IN_RETURN_WIP sudah di total_issued_to_wip

             // 3. Hitung sisa = total di WIP - yang sudah terpakai
             $batch->remaining_qty = round((float)$batch->total_issued_to_wip - $batch->total_used, 5);
         }

         // 4. Filter sisa > 0
         return array_filter($issued_batches, function($b) {
             return $b->remaining_qty > 0.00001;
         });
     }    /**
     * Simpan pemakaian material (Input)
     * Akan memanggil Stock_ledger_model HANYA jika sumbernya dari SOH
     */
    public function save_material_usage($data) {
        try {
            error_log("=== SAVE_MATERIAL_USAGE START ===");
            error_log("Data received: " . json_encode($data));
            
            if (empty($data['production_header_id']) || empty($data['item_id']) || empty($data['item_type']) ||
                !isset($data['qty_used']) || $data['qty_used'] <= 0 || empty($data['stage_name'])) {
                    error_log('Validation failed: data tidak lengkap');
                    log_message('error', 'Production: save_material_usage gagal, data tidak lengkap. Data: ' . json_encode($data));
                    return false;
            }
        } catch (Exception $e) {
            error_log("EXCEPTION in validation: " . $e->getMessage());
            return false;
        }

        error_log("Validation passed");

        // Extract wo_header_id dan BC info untuk ledger (tidak disimpan di production_inputs)
        $wo_header_id = !empty($data['wo_header_id']) ? $data['wo_header_id'] : null;
        $bc_type_code = !empty($data['_bc_type_code']) ? $data['_bc_type_code'] : NULL;
        $bc_number = !empty($data['_bc_number']) ? $data['_bc_number'] : NULL;
        $bc_date = !empty($data['_bc_date']) ? $data['_bc_date'] : NULL;
        
        error_log("Extracted BC - type: $bc_type_code, number: $bc_number, date: $bc_date");
        error_log("Extracted BC - type: $bc_type_code, number: $bc_number, date: $bc_date");
        
        // DEBUG: Log data yang diterima
        log_message('error', '=== START save_material_usage ===');
        log_message('error', 'Data sebelum cleanup: ' . json_encode($data));
        log_message('error', 'BC extracted - type: ' . $bc_type_code . ', number: ' . $bc_number . ', date: ' . $bc_date);
        
        error_log("Before cleanup");
        // Hapus SEMUA field yang tidak ada di tabel production_inputs
        unset($data['mr_detail_id']);
        unset($data['wo_header_id']);
        unset($data['_bc_type_code']);
        unset($data['_bc_number']);
        unset($data['_bc_date']);
        // Hapus juga jika ada yang tanpa underscore (untuk safety)
        unset($data['bc_type_code']);
        unset($data['bc_number']);
        unset($data['bc_date']);
        
        error_log("After cleanup - data: " . json_encode($data));
        
        // DEBUG: Log data setelah cleanup
        log_message('error', 'Data setelah cleanup: ' . json_encode($data));
        
        error_log("Starting transaction");
        $this->db->trans_start();
        error_log("Transaction started");

        // 1. Insert production_inputs (HANYA kolom yang ada di tabel)
        error_log('Attempting insert to production_inputs...');
        error_log('Insert data: ' . json_encode($data));
        
        try {
            $insert_input = $this->db->insert($this->table_inputs, $data);
            error_log('Insert result: ' . ($insert_input ? 'TRUE' : 'FALSE'));
        } catch (Exception $e) {
            error_log('EXCEPTION on insert: ' . $e->getMessage());
            $this->db->trans_rollback();
            return false;
        }
        
        if (!$insert_input) {
            $db_error = $this->db->error();
            log_message('error', 'GAGAL insert production_inputs! Error code: ' . $db_error['code'] . ', Message: ' . $db_error['message']);
            log_message('error', 'Data yang gagal: ' . json_encode($data));
            $this->db->trans_rollback();
            return false;
        }
        $input_id = $this->db->insert_id();
        log_message('error', 'SUCCESS insert production_inputs, ID: ' . $input_id);
        error_log('Insert production_inputs SUCCESS, ID: ' . $input_id);

        // 2. SELALU Buat ledger 'wip_out'
        error_log('Loading Stock_ledger_model...');
        $this->load->model('Stock_ledger_model');
        error_log('Stock_ledger_model loaded');
        
        $ledger_data = [
            'item_id'           => $data['item_id'],
            'item_type'         => $data['item_type'],
            // Tanggal = waktu mulai tahap (bukan jam input), lihat stage_tx_date().
            'transaction_date'  => $this->stage_tx_date($data['production_header_id'], $data['stage_name']),
            'transaction_type'  => 'wip_out', // Pemakaian WIP
            'transaction_id'    => $input_id,
            'source_doc_type'   => 'PROD',
            'source_doc_id'     => $wo_header_id ? $wo_header_id : $data['production_header_id'], // Use WO ID if available
            'qty_in'            => 0,
            'qty_out'           => (float)$data['qty_used'],
            'unit_value'        => 0.00, // Default cost
            'stock_on_hand_id'  => NULL,   // Default NULL
            // BC info dari parameter yang sudah di-extract (dari get_wip_materials_with_remaining)
            'bc_type_code'      => $bc_type_code,
            'bc_number'         => $bc_number,
            'bc_date'           => $bc_date
        ];

        // 3. Ambil cost dan BC info jika sumbernya dari SOH (Material dari GRN)
        if (!empty($data['stock_on_hand_id']))
        {
            $soh_data = $this->db->select('unit_value, bc_type_code, bc_number, bc_date')
                                 ->where('id', $data['stock_on_hand_id'])
                                 ->get('stock_on_hand')
                                 ->row();
            if($soh_data) { 
                $ledger_data['unit_value'] = (float)$soh_data->unit_value;
                // Override BC info dengan data dari SOH jika ada
                if (!empty($soh_data->bc_type_code)) {
                    $ledger_data['bc_type_code'] = $soh_data->bc_type_code;
                    $ledger_data['bc_number'] = $soh_data->bc_number;
                    $ledger_data['bc_date'] = $soh_data->bc_date;
                }
            }
            $ledger_data['stock_on_hand_id'] = $data['stock_on_hand_id'];
        }
        // Jika sumbernya dari IN_RETURN_WIP (tidak ada SOH ID),
        // BC info sudah diambil dari data yang dikirim (dari stock_ledger sebelumnya).
        // unit_value tetap 0 karena sudah pernah di-cost saat pertama kali masuk WIP.

        // 4. Masukkan ke ledger
        log_message('error', 'Attempting insert to stock_ledger...');
        log_message('error', 'Ledger data: ' . json_encode($ledger_data));
        error_log('Attempting insert to stock_ledger...');
        error_log('Ledger data: ' . json_encode($ledger_data));
        
        try {
            $ledger_insert = $this->Stock_ledger_model->insert($ledger_data);
            error_log('Ledger insert result: ' . ($ledger_insert ? 'TRUE' : 'FALSE'));
        } catch (Exception $e) {
            error_log('EXCEPTION on ledger insert: ' . $e->getMessage());
            log_message('error', 'EXCEPTION ledger insert: ' . $e->getMessage());
            $this->db->trans_rollback();
            return false;
        }
        
        if (!$ledger_insert) {
            log_message('error', 'GAGAL insert stock ledger untuk wip_out!');
            log_message('error', 'Ledger data yang gagal: ' . json_encode($ledger_data));
            $this->db->trans_rollback();
            return false;
        }
        log_message('error', 'SUCCESS insert stock_ledger');

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Transaction FAILED on trans_complete!');
            return false;
        }
        log_message('error', '=== END save_material_usage SUCCESS ===');
        return true;
    }

    /**
     * Simpan hasil produksi (Output)
     * Akan memanggil Stock_ledger_model HANYA jika outputnya semifinished
     */
    public function save_production_output($data) {
        $qty_produced = (float)($data['qty_produced'] ?? 0);
        $qty_scrap = (float)($data['qty_scrap'] ?? 0);

        // Validasi data dasar
        if (!isset($data['production_header_id']) || $data['production_header_id'] === '' ||
            !isset($data['item_id'])          || $data['item_id'] === ''          || $data['item_id'] === null ||
            !isset($data['item_type'])        || $data['item_type'] === ''        ||
            !isset($data['stage_name'])       || $data['stage_name'] === '' ) {
                log_message('error', 'Production: save_production_output gagal, data header/item/stage kosong atau null. Data: '.json_encode($data));
                return false;
        }
        // Validasi Qty
        if (($qty_produced <= 0) && ($qty_scrap <= 0)) {
             log_message('error', 'Production: save_production_output gagal, qty 0. Data: '.json_encode($data));
             return false;
        }

        $this->db->trans_start();

        // 1. Insert production_outputs
        $insert_output = $this->db->insert($this->table_outputs, $data);
        if (!$insert_output) {
           log_message('error', 'Production: Gagal insert production_outputs! DB Error: ' . print_r($this->db->error(), true) . ' Data: ' . json_encode($data));
           $this->db->trans_rollback();
           return false;
        }
        $output_id = $this->db->insert_id();

        // 2. Buat ledger HANYA jika outputnya semifinished
        if ($data['item_type'] == 'semifinished' && $qty_produced > 0) {
            $this->load->model('Stock_ledger_model'); // Load model di sini

            // TODO: Kalkulasi Cost untuk Semifinished (jika diperlukan)
            $unit_cost = 0.00;

            $ledger_data = [
                'item_id'           => $data['item_id'],
                'item_type'         => $data['item_type'],
                // Tanggal = waktu mulai tahap (bukan jam input), lihat stage_tx_date().
                'transaction_date'  => $this->stage_tx_date($data['production_header_id'], $data['stage_name']),
                'transaction_type'  => 'wip_in', // Hasil produksi masuk WIP
                'transaction_id'    => $output_id,
                'source_doc_type'   => 'PROD',
                'source_doc_id'     => $data['production_header_id'],
                'qty_in'            => $qty_produced,
                'qty_out'           => 0,
                'unit_value'        => $unit_cost
                // Balance akan dihitung oleh Stock_ledger_model->insert()
            ];

            if (!$this->Stock_ledger_model->insert($ledger_data)) {
                 log_message('error', 'Production: Gagal insert stock ledger untuk wip_in. Data: ' . json_encode($ledger_data));
                 $this->db->trans_rollback();
                 return false;
            }
        }
        // Jika output FG atau Scrap, tidak ada ledger dibuat di sini.

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Production_model->save_production_output Gagal melakukan transaksi DB.');
            return false;
        }
        
        // Cek apakah WO sudah selesai dan update statusnya
        $this->check_and_update_wo_status($data['production_header_id']);
        
        return true;
    }
    
    /**
     * Cek apakah WO sudah selesai berdasarkan qty output finishing
     * Update status WO menjadi 'Finished' jika qty sudah terpenuhi
     */
    private function check_and_update_wo_status($production_header_id) {
        // Ambil info production header dan WO
        $this->db->select('ph.wo_header_id, wo.planned_qty');
        $this->db->from('production_header ph');
        $this->db->join('wo_header wo', 'ph.wo_header_id = wo.id', 'left');
        $this->db->where('ph.id', $production_header_id);
        $prod_info = $this->db->get()->row();
        
        if (!$prod_info || !$prod_info->wo_header_id) {
            return false;
        }
        
        $wo_id = $prod_info->wo_header_id;
        $qty_plan = (float)$prod_info->planned_qty;
        
        // Hitung total qty finished goods dari stage finishing untuk WO ini
        $this->db->select('COALESCE(SUM(po.qty_produced), 0) as total_fg_produced');
        $this->db->from('production_outputs po');
        $this->db->join('production_header ph', 'po.production_header_id = ph.id');
        $this->db->where('ph.wo_header_id', $wo_id);
        $this->db->where('po.stage_name', 'finishing');
        $this->db->where('po.item_type', 'finished_goods');
        $result = $this->db->get()->row();
        
        $total_produced = (float)($result->total_fg_produced ?? 0);
        
        // Jika qty sudah terpenuhi, update status WO menjadi Finished
        if ($total_produced >= $qty_plan) {
            $this->load->model('Wo_model');
            $this->Wo_model->update_status($wo_id, 'Finished');
            log_message('info', "WO ID {$wo_id} status updated to Finished. Produced: {$total_produced}, Plan: {$qty_plan}");
        }
        
        return true;
    }
    public function get_production_inputs_for_trace($prod_id, $stage) {
        $this->db->reset_query();
        $this->db->select("
            pi.qty_used, pi.item_id, pi.item_type,
            pi.stock_on_hand_id, /* Kunci traceability */
            
            /* Info Item (bisa material atau semifinished) */
            CASE 
                WHEN pi.item_type = 'material' THEN m.kode_material
                WHEN pi.item_type = 'semifinished' THEN sf.kode_barang
                ELSE NULL
            END AS item_code,
            CASE 
                WHEN pi.item_type = 'material' THEN m.nama_material
                WHEN pi.item_type = 'semifinished' THEN sf.nama_barang
                ELSE NULL
            END AS item_name,
            CASE 
                WHEN pi.item_type = 'material' THEN m.satuan
                WHEN pi.item_type = 'semifinished' THEN sf.satuan
                ELSE NULL
            END AS item_unit
        ", false);

        $this->db->from($this->table_inputs . ' pi');
        // Join ke master item
        $this->db->join('materials m', 'pi.item_id = m.id AND pi.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'pi.item_id = sf.id AND pi.item_type = "semifinished"', 'left');
        
        // Join ke batch (SOH) untuk traceability
        $this->db->join('stock_on_hand soh', 'pi.stock_on_hand_id = soh.id', 'left');

        $this->db->where('pi.production_header_id', $prod_id);
        $this->db->where('pi.stage_name', $stage);
        $this->db->order_by('pi.id', 'ASC');

        $query = $this->db->get();
        return $query ? $query->result() : [];
    }
    public function search_semifinished_goods($term) {
        $term = $this->db->escape_like_str($term);
        $this->db->select("id, kode_barang as code, nama_barang as name, 'semifinished' as type");
        $this->db->group_start()->like('kode_barang', $term)->or_like('nama_barang', $term)->group_end();
        $this->db->where('status', 'active');
        $this->db->limit(10);
        return $this->db->get('semifinished_goods')->result();
    }

    public function search_scrap_items($term) {
        $term = $this->db->escape_like_str($term);
        $this->db->select("id, scrap_code as code, scrap_name as name, 'scrap' as type, unit");
        $this->db->group_start()->like('scrap_code', $term)->or_like('scrap_name', $term)->group_end();
        $this->db->where('status', 'active');
        $this->db->limit(10);
        return $this->db->get('master_scrap')->result();
    }

    
    public function get_production_trace_details($prod_id, $stage)
    {
        $data = [];
        $this->db->reset_query();

        // === 1. Ambil Info Header (FG, WO, SC) ===
        $data['header_info'] = $this->get_header_by_id($prod_id);

        // === 2. Ambil Material yang digunakan di stage itu + Asal BC ===
        $this->db->select("
            pi.id AS input_id,
            pi.item_id, 
            pi.item_type,
            pi.qty_used,
            pi.stage_name,

            CASE 
                WHEN pi.item_type = 'material' THEN m.kode_material
                WHEN pi.item_type = 'semifinished' THEN sf.kode_barang
                ELSE NULL
            END AS item_code,

            CASE 
                WHEN pi.item_type = 'material' THEN m.nama_material
                WHEN pi.item_type = 'semifinished' THEN sf.nama_barang
                ELSE NULL
            END AS item_name,

            CASE 
                WHEN pi.item_type = 'material' THEN m.satuan
                WHEN pi.item_type = 'semifinished' THEN sf.satuan
                ELSE NULL
            END AS item_unit,

            /* Asal MR dan GRN */
            mrh.mr_number,
            grn.grn_number,
            grn.bc_type_code,
            grn.bc_number,
            grn.bc_date,
            sup.nama_supplier
        ", false);

        $this->db->from('production_inputs pi');
        $this->db->join('materials m', 'pi.item_id = m.id AND pi.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'pi.item_id = sf.id AND pi.item_type = "semifinished"', 'left');

        $this->db->join('mr_details mrd', 'pi.mr_detail_id = mrd.id', 'left');
        $this->db->join('mr_header mrh', 'mrd.mr_header_id = mrh.id', 'left');
        $this->db->join('grn_details grd', 'mrd.source_grn_detail_id = grd.id', 'left');
        $this->db->join('grn_header grn', 'grd.grn_header_id = grn.id', 'left');
        $this->db->join('suppliers sup', 'grn.supplier_id = sup.id', 'left');

        $this->db->where('pi.production_header_id', $prod_id);
        $this->db->where('pi.stage_name', $stage);
        $this->db->order_by('pi.id', 'ASC');

        $materials = $this->db->get()->result();

        $data['materials_used'] = $materials;
        return $data;
    }

    public function get_production_inputs($prod_id, $stage) {
        $this->db->select('
            pi.*, 
            CASE 
                WHEN pi.item_type = "material" THEN m.nama_material 
                WHEN pi.item_type = "semifinished" THEN sf_item.nama_barang
            END as item_name,
            CASE
                WHEN pi.item_type = "material" THEN m.kode_material
                WHEN pi.item_type = "semifinished" THEN sf_item.kode_barang
            END as item_code,
            CASE
                WHEN pi.item_type = "material" THEN m.satuan
                WHEN pi.item_type = "semifinished" THEN sf_item.satuan
            END as item_unit,
            mr_h.mr_number,
            po_source.stage_name as source_output_stage,
            CASE 
                 WHEN po_source.item_type = "semifinished" THEN sf_source.nama_barang 
            END as source_output_item_name

        ', FALSE);
        $this->db->from($this->table_inputs . ' pi');
        // Join untuk nama item yg dipakai
        $this->db->join('materials m', 'pi.item_id = m.id AND pi.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf_item', 'pi.item_id = sf_item.id AND pi.item_type = "semifinished"', 'left');
        // Join untuk info sumber (jika dari MR)
        $this->db->join('mr_details mr_d', 'pi.mr_detail_id = mr_d.id', 'left');
        $this->db->join('mr_header mr_h', 'mr_d.mr_header_id = mr_h.id', 'left');
        // Join untuk info sumber (jika dari output stage lain)
        $this->db->join($this->table_outputs . ' po_source', 'pi.source_output_id = po_source.id', 'left');
        $this->db->join('semifinished_goods sf_source', 'po_source.item_id = sf_source.id AND po_source.item_type = "semifinished"', 'left');

        $this->db->where('pi.production_header_id', $prod_id);
        $this->db->where('pi.stage_name', $stage); // Filter berdasarkan stage
        $this->db->order_by('pi.id', 'ASC');
        return $this->db->get()->result();
    }

    public function get_production_outputs($production_id, $stage)
    {
        $this->db->select("
            o.*, 
            CASE 
                WHEN o.item_type IN ('finished','finished_goods') THEN fg.nama_barang
                WHEN o.item_type = 'semifinished' THEN sf.nama_barang
                WHEN o.item_type = 'scrap' THEN sc.scrap_name
                ELSE NULL
            END AS item_name,
            CASE 
                WHEN o.item_type IN ('finished','finished_goods') THEN fg.kode_barang
                WHEN o.item_type = 'semifinished' THEN sf.kode_barang
                WHEN o.item_type = 'scrap' THEN sc.scrap_code
                ELSE NULL
            END AS item_code,
            CASE 
                WHEN o.item_type IN ('finished','finished_goods') THEN fg.satuan
                WHEN o.item_type = 'semifinished' THEN sf.satuan
                WHEN o.item_type = 'scrap' THEN sc.unit
                ELSE NULL
            END AS item_unit
        ");
        $this->db->from('production_outputs o');
        $this->db->join('finished_goods fg', 'fg.id = o.item_id AND o.item_type IN ("finished","finished_goods")', 'left');
        $this->db->join('semifinished_goods sf', 'sf.id = o.item_id AND o.item_type = "semifinished"', 'left');
        $this->db->join('master_scrap sc', 'sc.id = o.item_id AND o.item_type = "scrap"', 'left');
        // Join users to get receiver (who recorded the output) and QC checker name
        $this->db->join('users receiver', 'receiver.id = o.received_by', 'left');
        $this->db->join('users qc', 'qc.id = o.qc_checked_by', 'left');
        // expose readable names
        $this->db->select('receiver.fullname as receiver_name, qc.fullname as qc_checked_by_name', FALSE);
        $this->db->where('o.production_header_id', $production_id);
        $this->db->where('o.stage_name', $stage);
        $this->db->order_by('o.id', 'ASC');
        return $this->db->get()->result();
    }



    public function complete_production($id) {
        $data = ['status' => 'Completed'];
        $result = $this->db->where('id', $id)
                           ->where('status', 'In Progress')
                           ->update($this->table_header, $data);

        if ($result) {
            // Update WO status to Finished when production is completed
            $prod_info = $this->db->select('wo_header_id')
                                  ->from('production_header')
                                  ->where('id', $id)
                                  ->get()->row();
            if ($prod_info && $prod_info->wo_header_id) {
                $this->load->model('Wo_model');
                $this->Wo_model->update_status($prod_info->wo_header_id, 'Finished');
                log_message('info', "WO ID {$prod_info->wo_header_id} status updated to Finished after production {$id} completed.");
            }
        }

        return $result;
    }

    public function confirm_qc_output($output_id, $user_id) {
        $data = [
            'qc_checked_by' => $user_id,
            'qc_checked_at' => date('Y-m-d H:i:s')
        ];
        return $this->db->where('id', $output_id)->update($this->table_outputs, $data);
    }
    public function unconfirm_qc_output($output_id) {
        $data = [
            'qc_checked_by' => NULL,
            'qc_checked_at' => NULL
        ];
        return $this->db->where('id', $output_id)->update($this->table_outputs, $data);
    }
    public function confirm_production($id, $user_id) {
        $data = [
            'status' => 'In Progress',
            'confirmed_by' => $user_id,
            'confirmed_at' => date('Y-m-d H:i:s')
        ];
        return $this->db->where('id', $id)->where('status', 'Draft')->update($this->table_header, $data);
    }
    public function unconfirm_production($id) {
        $data = [
            'status' => 'Draft',
            'confirmed_by' => NULL,
            'confirmed_at' => NULL
        ];
        // Hanya bisa unconfirm jika status 'In Progress'
        return $this->db->where('id', $id)->where('status', 'In Progress')->update($this->table_header, $data);
    }

    /**
     * FUNGSI BARU: Hapus 1 baris input dan HAPUS langsung stock_ledger terkait
     */
    public function delete_input_by_id($input_id) {
        $this->db->trans_start();
        $input = $this->db->get_where($this->table_inputs, ['id' => $input_id])->row();

        if ($input) {
            // ============ HAPUS LANGSUNG STOCK LEDGER ============
            // Hapus ledger wip_out yang dibuat saat input disimpan
            // wip_out dibuat dengan transaction_id = input_id
            $this->db->where('transaction_type', 'wip_out')
                     ->where('transaction_id', $input_id)
                     ->delete('stock_ledger');
            
            log_message('info', 'Deleted wip_out ledger for input_id: ' . $input_id);
            // ============ AKHIR PERBAIKAN ============
            
            $this->db->where('id', $input_id)->delete($this->table_inputs);
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }



    /**
     * Hapus 1 baris output + HAPUS langsung stock_ledger terkait
     */
    public function delete_output_by_id($output_id) {
         $this->db->trans_start();
         $output = $this->db->get_where($this->table_outputs, ['id' => $output_id])->row();

         if ($output) {
             // ============ HAPUS LANGSUNG STOCK LEDGER ============
             // Hapus ledger wip_in yang dibuat saat output disimpan (untuk semifinished)
             // wip_in dibuat dengan transaction_id = output_id
             if ($output->item_type == 'semifinished') {
                 $this->db->where('transaction_type', 'wip_in')
                          ->where('transaction_id', $output_id)
                          ->where('item_type', 'semifinished')
                          ->delete('stock_ledger');
                 
                 log_message('info', 'Deleted wip_in ledger for output_id: ' . $output_id);
             }
             // ============ AKHIR PERBAIKAN ============
             
             $this->db->where('id', $output_id)->delete($this->table_outputs);
         }
         $this->db->trans_complete();
         return $this->db->trans_status();
    }
    public function insert_output($data)
    {
        // Validasi minimum
        if (empty($data['production_header_id']) || empty($data['item_id']) || empty($data['item_type'])) {
            log_message('error', 'insert_output gagal: data tidak lengkap. Data: ' . json_encode($data));
            return false;
        }

        // Siapkan array data sesuai struktur tabel
        $insert = [
            'production_header_id' => $data['production_header_id'],
            'stage_name'           => $data['stage_name'] ?? 'finishing', // default ke finishing
            'item_id'              => $data['item_id'],
            'item_type'            => $data['item_type'],
            'qty_produced'         => $data['qty_produced'] ?? 0,
            'qty_scrap'            => $data['qty_scrap'] ?? 0,
            'created_at'           => date('Y-m-d H:i:s'),
            'received_by'          => $this->session->userdata('user_id') ?? null
        ];

        // Insert ke tabel
        $this->db->insert('production_outputs', $insert);

        // Jika gagal, catat log untuk debugging
        if (!$this->db->affected_rows()) {
            log_message('error', 'insert_output gagal: ' . print_r($this->db->error(), true));
            return false;
        }

        return $this->db->insert_id();
    }



    public function get_available_previous_stage_outputs($prod_id, $current_stage = 'sewing', $previous_stage = 'cutting') {
        // 1. Ambil semua output semi-finished dari stage sebelumnya
        $this->db->select("
            po.id as output_id, 
            po.item_id, 
            po.item_type, 
            po.qty_produced,
            CASE WHEN po.item_type = 'semifinished' THEN sf.kode_barang ELSE NULL END as item_code,
            CASE WHEN po.item_type = 'semifinished' THEN sf.nama_barang ELSE NULL END as item_name,
            CASE WHEN po.item_type = 'semifinished' THEN sf.satuan ELSE NULL END as item_unit
        ");
        $this->db->from($this->table_outputs . ' po');
        $this->db->join('semifinished_goods sf', 'po.item_id = sf.id AND po.item_type = "semifinished"', 'left');
        $this->db->where('po.production_header_id', $prod_id);
        $this->db->where('po.stage_name', $previous_stage);
        $this->db->where('po.item_type', 'semifinished'); // Hanya ambil yg semi-finished
        $this->db->where('po.qc_checked_by IS NOT NULL'); // Hanya yg sudah QC
        $outputs = $this->db->get()->result();

        // 2. Hitung sisa qty yang belum terpakai di stage SEKARANG
        foreach ($outputs as $out) {
            $this->db->select_sum('qty_used');
            $this->db->where('production_header_id', $prod_id);
            $this->db->where('stage_name', $current_stage);
            $this->db->where('source_output_id', $out->output_id); // Cek pemakaian dari output ini
            $used_result = $this->db->get($this->table_inputs)->row();
            $total_used = $used_result ? (float)$used_result->qty_used : 0;

            $out->total_used = $total_used;
            $out->remaining_qty = (float)$out->qty_produced - $total_used;
        }

        return $outputs;
    }
    public function get_additional_materials_with_remaining($wo_id, $prod_id, $stage = 'sewing') {
        // 1. Ambil SEMUA batch material yg diterima dari MR utk WO ini
        $batches = $this->get_wip_material_for_wo($wo_id); // Fungsi ini mengambil dari mr_details

        if (empty($batches)) {
             return []; // Kembalikan array kosong jika tidak ada batch
        }

        foreach ($batches as $batch) {
            // Pastikan batch adalah object dan punya property
            if (!is_object($batch) || !property_exists($batch, 'mr_detail_id')) {
                 continue; // Lewati jika data batch tidak valid
            }

            // 2. Hitung total yg sudah terpakai di SEMUA stage DI PRODUKSI INI utk batch MR ini
            $this->db->reset_query(); // Reset query builder
            $this->db->select_sum('qty_used');
            $this->db->where('production_header_id', $prod_id); // Hanya di produksi ini
            $this->db->where('mr_detail_id', $batch->mr_detail_id); // Hanya dari batch MR ini
            
            // --- BARIS INI (Sekitar Line 394 Anda) HARUS SEPERTI INI ---
            $used_result = $this->db->get($this->table_inputs)->row(); 
            // ---------------------------------------------------
            
            $total_used = $used_result ? (float)$used_result->qty_used : 0;
            
            // 3. Hitung sisa
            $batch->total_used_in_this_prod = $total_used;
            $received_qty = isset($batch->total_received_wip) ? (float)$batch->total_received_wip : 0;
            $batch->remaining_qty = round($received_qty - $total_used, 5); // Bulatkan sisa
        }
         // Filter hanya yang sisanya > 0
         return array_filter($batches, function($b){ 
             return is_object($b) && property_exists($b, 'remaining_qty') && $b->remaining_qty > 0.00001; 
         });
    }
}