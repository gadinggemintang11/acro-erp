<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Wo_model extends CI_Model {

    private $table_header = 'wo_header';
    private $table_details = 'wo_details_material';

    // === HEADER ===
    public function get_all($filters = [], $limit = 50, $offset = 0) {
        $this->db->select('
            wo.id, wo.wo_number, wo.planned_qty, wo.planned_start_date, wo.status,
            sc.sc_number,
            fg.nama_barang as fg_name
        ');
        $this->db->from($this->table_header . ' wo');
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');
        if (!empty($filters['search'])) {
             $term = $this->db->escape_like_str($filters['search']);
             $this->db->group_start()->like('wo.wo_number', $term)->or_like('sc.sc_number', $term)->or_like('fg.nama_barang', $term)->group_end();
        }
        $this->db->order_by('wo.id', 'DESC');
        if ($limit > 0) {
            $this->db->limit($limit, $offset);
        }
        return $this->db->get()->result();
    }
    public function count_all($filters = []) {
        $this->db->from($this->table_header . ' wo');
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');
        if (!empty($filters['search'])) {
             $term = $this->db->escape_like_str($filters['search']);
             $this->db->group_start()->like('wo.wo_number', $term)->or_like('sc.sc_number', $term)->or_like('fg.nama_barang', $term)->group_end();
        }
        return $this->db->count_all_results();
    }

    public function get_header_by_id($id) {
         // ✅ FIX: Menghapus kolom sc.po_number yang tidak ada
         $this->db->select('
            wo.*,
            sc.sc_number, sc.po_qty as sc_qty,
            fg.kode_barang as fg_code, fg.nama_barang as fg_name,
            c.nama_customer,
            creator.fullname as creator_name,
            creator.signature_path as creator_signature,
            confirmer.fullname as confirmer_name,
            confirmer.signature_path as confirmer_signature
        ');
        $this->db->from($this->table_header . ' wo');
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');
        $this->db->join('customers c', 'sc.customer_id = c.id', 'left');
        $this->db->join('users creator', 'wo.created_by = creator.id', 'left');
        $this->db->join('users confirmer', 'wo.confirmed_by = confirmer.id', 'left');
        $this->db->where('wo.id', $id);
        return $this->db->get()->row();
    }

    public function insert_header($data) {
        $this->db->insert($this->table_header, $data);
        return $this->db->insert_id();
    }

    public function update_header($id, $data) {
        unset($data['sales_contract_id'], $data['fg_id']);
        return $this->db->where('id', $id)->update($this->table_header, $data);
    }

    /**
     * ✅ FIX: Menggunakan transaksi database agar aman.
     * Menghapus header DAN semua detail materialnya.
     */
    public function delete_header($id) {
        $this->db->trans_start();
        $this->db->where('wo_header_id', $id)->delete($this->table_details); // Hapus detail dulu
        $this->db->where('id', $id)->delete($this->table_header); // Baru hapus header
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function update_status($id, $new_status, $user_id = null) {
         $data = ['status' => $new_status];
        if ($new_status == 'In Progress' && $user_id) {
            $data['confirmed_by'] = $user_id;
            $data['confirmed_at'] = date('Y-m-d H:i:s');
        } elseif ($new_status == 'Planned') {
            $data['confirmed_by'] = NULL;
            $data['confirmed_at'] = NULL;
        } elseif ($new_status == 'Finished') {
            $data['finished_at'] = date('Y-m-d H:i:s');
            if ($user_id) {
                $data['finished_by'] = $user_id;
            }
        }
        return $this->db->where('id', $id)->update($this->table_header, $data);
    }

    public function generate_wo_number() {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('work_order', 'wo_header', 'wo_number');
    }

    // === DETAILS MATERIAL ===
    public function get_planned_materials($wo_header_id) {
        $select_clause = "
            d.id, d.wo_header_id, d.planned_qty, d.unit_measure,
            d.component_item_id as item_id, d.component_item_type as item_type,
            CASE WHEN d.component_item_type = 'material' THEN m.kode_material ELSE sf.kode_barang END as item_code,
            CASE WHEN d.component_item_type = 'material' THEN m.nama_material ELSE sf.nama_barang END as item_name
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join('materials m', 'd.component_item_id = m.id AND d.component_item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'd.component_item_id = sf.id AND d.component_item_type = "semifinished"', 'left');
        $this->db->where('d.wo_header_id', $wo_header_id);
        $this->db->order_by('item_name', 'ASC');
        return $this->db->get()->result();
    }

    public function insert_material($data) {
        $component = null; $satuan = null;
        if($data['component_item_type'] == 'material'){
            $component = $this->db->select('satuan')->where('id', $data['component_item_id'])->get('materials')->row();
            $satuan = $component ? $component->satuan : null;
        } else if ($data['component_item_type'] == 'semifinished'){
             $component = $this->db->select('satuan')->where('id', $data['component_item_id'])->get('semifinished_goods')->row();
             $satuan = $component ? $component->satuan : null;
        }
        $data['unit_measure'] = $satuan ?? 'PCS';
        return $this->db->insert($this->table_details, $data);
    }

    public function delete_material($id) {
        return $this->db->where('id', $id)->delete($this->table_details);
    }

    public function update_material($id, $data) {
        return $this->db->where('id', $id)->update($this->table_details, $data);
    }

    /**
     * ✅ NEW: Ambil BOM details dengan satuan dari master material/semifinished
     */
    public function get_bom_components_detailed($fg_id) {
        $bom_header = $this->db->select('id')->where('fg_id', $fg_id)->where('status', 'Active')->get('bom_header')->row();
        if(!$bom_header) return [];
        
        $bom_header_id = $bom_header->id;
        $select_clause = "
            bd.component_item_id,
            bd.component_item_type,
            bd.quantity_required,
            CASE WHEN bd.component_item_type = 'material' THEN m.satuan ELSE sf.satuan END as unit_measure,
            CASE WHEN bd.component_item_type = 'material' THEN m.nama_material ELSE sf.nama_barang END as item_name
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from('bom_details bd');
        $this->db->join('materials m', 'bd.component_item_id = m.id AND bd.component_item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'bd.component_item_id = sf.id AND bd.component_item_type = "semifinished"', 'left');
        $this->db->where('bd.bom_header_id', $bom_header_id);
        $this->db->order_by('bd.quantity_required', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Baris untuk modal "Load from BOM" di halaman detail Work Order.
     *
     * Modal ini dulu hanya pratinjau baca-saja: semua komponen BOM ditarik
     * sekaligus dengan qty hasil hitungan, tanpa bisa dipilih atau diubah
     * (user 2026-09-30: "sebaiknya kasih checkbox di modal nya dan
     * informasinya mungkin bisa di tambahi dan qty mungkin bisa di edit").
     *
     * Tiap baris dilengkapi:
     *   - suggested_qty : planned_qty WO x quantity_required BOM (usulan awal,
     *                     tetap bisa diubah user di modal)
     *   - already_qty   : qty komponen ini yang SUDAH ada di WO tsb. Dipakai
     *                     modal untuk menandai & mengunci baris yang sudah
     *                     ditambahkan, sehingga modal bisa dibuka lagi untuk
     *                     menarik sisanya tanpa menduplikasi.
     *   - stock_qty     : stok tersedia sekarang, supaya kekurangan material
     *                     ketahuan sejak tahap perencanaan.
     */
    public function get_bom_rows_for_wo($fg_id, $planned_qty, $wo_header_id = null) {
        $components = $this->get_bom_components_detailed($fg_id);
        if (empty($components)) return [];

        // Komponen yang sudah tercatat di WO ini (kalau ada).
        $already = [];
        if (!empty($wo_header_id)) {
            $rows = $this->db->select('component_item_id, component_item_type, SUM(planned_qty) AS qty', FALSE)
                             ->from($this->table_details)
                             ->where('wo_header_id', (int) $wo_header_id)
                             ->group_by('component_item_id, component_item_type')
                             ->get()->result();
            foreach ($rows as $r) {
                $already[$r->component_item_type . ':' . (int) $r->component_item_id] = (float) $r->qty;
            }
        }

        $this->load->model('Stock_ledger_model');

        $out = [];
        foreach ($components as $c) {
            $key  = $c->component_item_type . ':' . (int) $c->component_item_id;
            $code = '';
            if ($c->component_item_type === 'material') {
                $m = $this->db->select('kode_material')->where('id', $c->component_item_id)->get('materials')->row();
                $code = $m ? $m->kode_material : '';
            } else {
                $s = $this->db->select('kode_barang')->where('id', $c->component_item_id)->get('semifinished_goods')->row();
                $code = $s ? $s->kode_barang : '';
            }

            $out[] = (object) [
                'component_item_id'   => (int) $c->component_item_id,
                'component_item_type' => $c->component_item_type,
                'item_code'           => $code,
                'item_name'           => $c->item_name,
                'unit_measure'        => $c->unit_measure ?: 'PCS',
                'qty_per_fg'          => (float) $c->quantity_required,
                'suggested_qty'       => (float) $planned_qty * (float) $c->quantity_required,
                'already_qty'         => $already[$key] ?? 0.0,
                'stock_qty'           => (float) $this->Stock_ledger_model->get_current_stock(
                                            $c->component_item_id, $c->component_item_type),
            ];
        }
        return $out;
    }

    /**
     * Simpan material terpilih dari modal "Load from BOM".
     *
     * Berbeda dengan auto_populate_materials() yang menelan SELURUH BOM:
     * di sini user memilih baris mana saja dan boleh mengubah qty-nya.
     * Komponen yang sudah ada di WO dilewati (bukan ditimpa) supaya membuka
     * modal dua kali tidak menghasilkan baris ganda.
     *
     * @return array ['inserted' => int, 'skipped' => int]
     */
    public function add_materials_from_bom($wo_header_id, $items) {
        $existing = [];
        foreach ($this->db->select('component_item_id, component_item_type')
                          ->where('wo_header_id', (int) $wo_header_id)
                          ->get($this->table_details)->result() as $r) {
            $existing[$r->component_item_type . ':' . (int) $r->component_item_id] = true;
        }

        $inserted = 0; $skipped = 0;
        $this->db->trans_start();
        foreach ($items as $it) {
            $item_id = (int) ($it['item_id'] ?? 0);
            $type    = ($it['item_type'] ?? '') === 'semifinished' ? 'semifinished' : 'material';
            $qty     = (float) ($it['qty'] ?? 0);

            if ($item_id <= 0 || $qty <= 0) { $skipped++; continue; }
            if (isset($existing[$type . ':' . $item_id])) { $skipped++; continue; }

            $this->insert_material([
                'wo_header_id'        => (int) $wo_header_id,
                'component_item_id'   => $item_id,
                'component_item_type' => $type,
                'planned_qty'         => $qty,
            ]);
            $existing[$type . ':' . $item_id] = true;
            $inserted++;
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) return ['inserted' => 0, 'skipped' => count($items)];
        return ['inserted' => $inserted, 'skipped' => $skipped];
    }

    /**
     * ✅ NEW: Bulk insert wo_details_material dari BOM
     * Formula: planned_qty_wo × quantity_required_bom
     */
    public function auto_populate_materials($wo_header_id, $fg_id, $planned_qty) {
        $bom_components = $this->get_bom_components_detailed($fg_id);
        
        if(empty($bom_components)) {
            return false; // Tidak ada BOM, tidak bisa auto-populate
        }

        $this->db->trans_start();
        
        foreach($bom_components as $bom) {
            $calculated_qty = (float)$planned_qty * (float)$bom->quantity_required;
            
            $data = [
                'wo_header_id' => $wo_header_id,
                'component_item_id' => $bom->component_item_id,
                'component_item_type' => $bom->component_item_type,
                'planned_qty' => $calculated_qty,
                'unit_measure' => $bom->unit_measure ?? 'PCS'
            ];
            
            $this->db->insert($this->table_details, $data);
        }
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    // === HELPERS ===
    /**
     * ✅ FIX: Menghapus pencarian berdasarkan sc.po_number yang tidak ada
     */
    public function search_open_sales_contracts($term) {
        $term = $this->db->escape_like_str($term);
        $this->db->select('sc.id, sc.sc_number, fg.nama_barang as fg_name');
        $this->db->from('sales_contracts sc');
        $this->db->join('finished_goods fg', 'sc.fg_id = fg.id', 'left');
        $this->db->where('sc.status', 'Open');
        $this->db->group_start();
            $this->db->like('sc.sc_number', $term);
            $this->db->or_like('fg.nama_barang', $term);
        $this->db->group_end();
        $this->db->limit(10);
        return $this->db->get()->result();
    }

    /**
     * Daftar IPO (Sales Contract) berstatus Open untuk modal "Pilih dari
     * Daftar IPO" di form Buat Work Order.
     *
     * Sebelumnya user harus mengetik nomor IPO di select2 — tidak tahu mana
     * yang masih bisa dibuat WO tanpa menghafal nomornya (user 2026-09-30:
     * "jadi user bisa tau tanpa susah cuma ngetik nomor ipo nya, jadi kaya
     * modal tarik PR di PO").
     *
     * Tiap baris dilengkapi:
     *   - qty_on_wo : total qty rencana WO yang sudah dibuat untuk IPO ini
     *                 (status Cancelled tidak dihitung)
     *   - qty_left  : sisa qty IPO yang belum tertutup WO
     *   - has_bom   : ada/tidak BOM Active untuk FG-nya. Tanpa BOM, "Tarik
     *                 Material dari BOM" di halaman detail WO tidak
     *                 menghasilkan apa-apa — lebih baik ketahuan di sini.
     *
     * IPO yang sisanya sudah 0 TETAP ditampilkan (ditandai di tampilan saja);
     * sistem memang tidak melarang WO kedua, jadi daftarnya tidak menyembunyikan
     * apa pun — biar user yang memutuskan.
     */
    public function get_open_sales_contracts_for_wo($filters = []) {
        $this->db->select("
                sc.id, sc.sc_number, sc.contract_date, sc.po_qty,
                sc.fg_id, fg.kode_barang AS fg_code, fg.nama_barang AS fg_name, fg.satuan AS fg_unit,
                COALESCE(c.nama_customer, '') AS customer_name,
                COALESCE((
                    SELECT SUM(w.planned_qty) FROM wo_header w
                     WHERE w.sales_contract_id = sc.id AND w.status <> 'Cancelled'
                ), 0) AS qty_on_wo,
                COALESCE((
                    SELECT COUNT(*) FROM wo_header w
                     WHERE w.sales_contract_id = sc.id AND w.status <> 'Cancelled'
                ), 0) AS wo_count,
                COALESCE((
                    SELECT COUNT(*) FROM bom_header b
                     WHERE b.fg_id = sc.fg_id AND b.status = 'Active'
                ), 0) AS has_bom
            ", FALSE)
            ->from('sales_contracts sc')
            ->join('finished_goods fg', 'fg.id = sc.fg_id', 'left')
            ->join('customers c', 'c.id = sc.customer_id', 'left')
            ->where('sc.status', 'Open');

        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->where("(sc.sc_number LIKE '%{$term}%'"
                          . " OR fg.nama_barang LIKE '%{$term}%'"
                          . " OR fg.kode_barang LIKE '%{$term}%'"
                          . " OR c.nama_customer LIKE '%{$term}%')", null, FALSE);
        }
        if (!empty($filters['date_from'])) $this->db->where('sc.contract_date >=', $filters['date_from']);
        if (!empty($filters['date_to']))   $this->db->where('sc.contract_date <=', $filters['date_to']);

        $rows = $this->db->order_by('sc.contract_date', 'DESC')->order_by('sc.sc_number', 'DESC')->get()->result();

        foreach ($rows as $r) {
            $r->qty_left = max(0, (float) $r->po_qty - (float) $r->qty_on_wo);
        }
        return $rows;
    }

    public function get_sc_details_for_wo($sc_id) {
        $this->db->select('sc.id, sc.sc_number, sc.po_qty, sc.fg_id, fg.nama_barang as fg_name, fg.kode_barang as fg_code');
        $this->db->from('sales_contracts sc');
        $this->db->join('finished_goods fg', 'sc.fg_id = fg.id', 'inner');
        $this->db->where('sc.id', $sc_id);
        $this->db->where('sc.status', 'Open');
        return $this->db->get()->row_array();
    }

    public function get_bom_components_by_fg($fg_id) {
        $bom_header = $this->db->select('id')->where('fg_id', $fg_id)->where('status', 'Active')->get('bom_header')->row();
        if(!$bom_header) return [];
        $bom_header_id = $bom_header->id;
        $select_clause = "
            bd.quantity_required,
            CASE WHEN bd.component_item_type = 'material' THEN m.satuan ELSE sf.satuan END as unit_measure,
            CASE WHEN bd.component_item_type = 'material' THEN m.kode_material ELSE sf.kode_barang END as item_code,
            CASE WHEN bd.component_item_type = 'material' THEN m.nama_material ELSE sf.nama_barang END as item_name,
            bd.component_item_type as item_type
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from('bom_details bd');
        $this->db->join('materials m', 'bd.component_item_id = m.id AND bd.component_item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'bd.component_item_id = sf.id AND bd.component_item_type = "semifinished"', 'left');
        $this->db->where('bd.bom_header_id', $bom_header_id);
        $this->db->order_by('item_name', 'ASC');
        return $this->db->get()->result();
    }
    
    public function search_components($term) {
        $term = $this->db->escape_like_str($term);
        $materials = $this->db->select("id, kode_material as code, nama_material as name, 'material' as type")
                                  ->group_start()->like('kode_material', $term)->or_like('nama_material', $term)->group_end()
                                  ->where('status', 'active')->limit(10)->get('materials')->result();
        $semifinished = $this->db->select("id, kode_barang as code, nama_barang as name, 'semifinished' as type")
                                    ->group_start()->like('kode_barang', $term)->or_like('nama_barang', $term)->group_end()
                                    ->where('status', 'active')->limit(10)->get('semifinished_goods')->result();
        return array_merge($materials, $semifinished);
    }
    public function search_open_work_orders($term) {
        $this->db->select('
            wo.id as wo_header_id, -- ID dari wo_header
            wo.wo_number,
            ph.id as production_header_id, -- ID dari production_header (PENTING)
            ph.production_number,
            sc.sc_number, 
            fg.nama_barang as fg_name
        ');
        $this->db->from('wo_header wo'); // Nama tabel dari screenshot
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');
        
        // PENTING: Join ke Production Header untuk dapatkan ID-nya
        // Pastikan tabel 'production_header' punya kolom 'wo_header_id'
        $this->db->join('production_header ph', 'ph.wo_header_id = wo.id', 'left'); 
        
        $this->db->where('wo.status', 'In Progress'); // Hanya cari WO yg sedang berjalan

        if (!empty($term)) {
            $term_esc = $this->db->escape_like_str($term);
            $this->db->group_start();
            $this->db->like('wo.wo_number', $term_esc);
            $this->db->or_like('sc.sc_number', $term_esc);
            $this->db->or_like('fg.nama_barang', $term_esc);
            $this->db->or_like('ph.production_number', $term_esc); // Cari No Produksi juga
            $this->db->group_end();
        }

        $this->db->where('ph.id IS NOT NULL'); // Pastikan sudah ada Production Order-nya
        $this->db->order_by('wo.id', 'DESC');
        $this->db->limit(15);
        
        $query = $this->db->get();
        
        if (!$query) {
            log_message('error', '[Wo_model Search Open WO] DB Error: ' . print_r($this->db->error(), true) . ' - Query: ' . $this->db->last_query());
            return [];
        }
        return $query->result();
    }
}