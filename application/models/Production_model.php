<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Production_model extends CI_Model {

    private $table_header = 'production_header';
    private $table_inputs = 'production_inputs';
    private $table_outputs = 'production_outputs';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('Stock_ledger_model');
    }

    // === HEADER ===
    public function get_all($filters = []) {
        $this->db->select('
            p.id, p.production_number, p.production_date, p.planned_qty, p.status,
            wo.wo_number, sc.sc_number, fg.nama_barang as fg_name
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
            $this->db->group_end();
        }

        $this->db->order_by('p.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id) {
        $this->db->select('p.*, wo.wo_number, sc.sc_number, fg.nama_barang as fg_name');
        $this->db->from($this->table_header . ' p');
        $this->db->join('wo_header wo', 'p.wo_header_id = wo.id', 'left');
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join('finished_goods fg', 'p.fg_id = fg.id', 'left');
        $this->db->where('p.id', $id);
        return $this->db->get()->row();
    }

    public function insert_header($data) {
        $this->db->insert($this->table_header, $data);
        return $this->db->insert_id();
    }

    public function update_header($id, $data) {
        return $this->db->where('id', $id)->update($this->table_header, $data);
    }

    /**
     * PERBAIKAN KRITIS:
     * Fungsi delete_header sekarang menggunakan transaksi untuk:
     * 1. Membalikkan (reversal) semua stok ledger yang terkait.
     * 2. Menghapus data inputs, outputs, dan header.
     * Ini mencegah orphan data dan menjaga integritas stok.
     */
    public function delete_header($id) {
        $this->db->trans_start();

        // 1. Ambil semua inputs
        $inputs = $this->db->get_where($this->table_inputs, ['production_header_id' => $id])->result();
        
        // 2. Ambil semua outputs
        $outputs = $this->db->get_where($this->table_outputs, ['production_header_id' => $id])->result();

        $now = date('Y-m-d H:i:s');

        // 3. Reversal Stock Ledger untuk INPUTS (WIP Keluar -> dikembalikan jadi WIP Masuk)
        foreach ($inputs as $input) {
            $last_balance = $this->Stock_ledger_model->get_last_balance($input->item_id, $input->item_type);
            $ledger_reversal = [
                'item_id'           => $input->item_id,
                'item_type'         => $input->item_type,
                'transaction_date'  => $now,
                'transaction_type'  => 'wip_in_reversal',
                'transaction_id'    => $input->id,
                'source_doc_type'   => 'PROD_DELETE',
                'source_doc_id'     => $id,
                'qty_in'            => $input->qty_used,
                'qty_out'           => 0,
                'balance'           => $last_balance + $input->qty_used
            ];
            $this->Stock_ledger_model->insert($ledger_reversal);
        }

        // 4. Reversal Stock Ledger untuk OUTPUTS (WIP Masuk -> dikembalikan jadi WIP Keluar)
        foreach ($outputs as $output) {
            // Hanya non-scrap yang memengaruhi stok
            if ($output->item_type != 'scrap') {
                $last_balance = $this->Stock_ledger_model->get_last_balance($output->item_id, $output->item_type);
                $ledger_reversal = [
                    'item_id'           => $output->item_id,
                    'item_type'         => $output->item_type,
                    'transaction_date'  => $now,
                    'transaction_type'  => 'wip_out_reversal',
                    'transaction_id'    => $output->id,
                    'source_doc_type'   => 'PROD_DELETE',
                    'source_doc_id'     => $id,
                    'qty_in'            => 0,
                    'qty_out'           => $output->qty_produced,
                    'balance'           => $last_balance - $output->qty_produced
                ];
                $this->Stock_ledger_model->insert($ledger_reversal);
            }
        }

        // 5. Hapus detail
        $this->db->where('production_header_id', $id)->delete($this->table_inputs);
        $this->db->where('production_header_id', $id)->delete($this->table_outputs);

        // 6. Hapus header
        $this->db->where('id', $id)->delete($this->table_header);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function generate_prod_number() {
        $prefix = 'PROD/' . date('Ym') . '/';
        $this->db->like('production_number', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get($this->table_header, 1)->row();
        $new_num = $last ? (int) substr($last->production_number, -4) + 1 : 1;
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }

    // === HELPERS ===
    public function search_open_wos($term) {
        $term = $this->db->escape_like_str($term);
        $this->db->select('wo.id, wo.wo_number, fg.nama_barang as fg_name');
        $this->db->from('wo_header wo');
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');
        $this->db->join('production_header p', 'wo.id = p.wo_header_id', 'left');
        $this->db->where('wo.status', 'In Progress');
        $this->db->where('p.id IS NULL');
        $this->db->group_start();
        $this->db->like('wo.wo_number', $term);
        $this->db->or_like('fg.nama_barang', $term);
        $this->db->group_end();
        $this->db->limit(10);
        return $this->db->get()->result();
    }

    public function get_wo_details_for_prod($wo_id) {
        $this->db->select('wo.id, wo.planned_qty, sc.sc_number, fg.id as fg_id, fg.nama_barang as fg_name');
        $this->db->from('wo_header wo');
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');
        $this->db->where('wo.id', $wo_id);
        return $this->db->get()->row_array();
    }

    // === TAHAP 1: CUTTING ===

    /**
     * FUNGSI BARU:
     * Mengambil material WIP dan menghitung sisa stok yang sebenarnya.
     * Sisa = (Total Diterima dari MR) - (Total Sudah Terpakai di Produksi ini)
     */
    public function get_wip_materials_with_remaining($wo_id, $prod_id, $stage = 'cutting') {
        // 1. Ambil SEMUA batch material yang diterima (tanpa group by)
        $batches = $this->get_wip_material_for_wo($wo_id); // <-- Memanggil fungsi di atas

        foreach ($batches as $batch) {
            // 2. Ambil total yang sudah terpakai SPESIFIK untuk batch ini
            $used = $this->get_total_material_usage_by_batch($prod_id, $batch->mr_detail_id, $stage);
            
            // 3. Hitung sisa
            $batch->total_used = (float)$used;
            // Baris ini (Line 179) akan error jika get_wip_material_for_wo() salah
            $batch->remaining_qty = (float)$batch->total_received_wip - (float)$used; 
        }
        return $batches;
    }

    // Fungsi ini (get_wip_material_for_wo) tetap sama, mengambil total penerimaan
    public function get_wip_material_for_wo($wo_id) {
        $select_clause = "
            d.id as mr_detail_id, 
            d.component_item_id as item_id, 
            d.component_item_type as item_type,
            d.issued_qty as total_received_wip,  /* <--- INI ADALAH ALIAS YANG HILANG */
            CASE WHEN d.component_item_type = 'material' THEN m.kode_material ELSE sf.kode_barang END as item_code,
            CASE WHEN d.component_item_type = 'material' THEN m.nama_material ELSE sf.nama_barang END as item_name,
            CASE WHEN d.component_item_type = 'material' THEN m.satuan ELSE sf.satuan END as item_unit
        ";

        $this->db->select($select_clause, FALSE); 
        $this->db->from('mr_details d');
        $this->db->join('mr_header h', 'd.mr_header_id = h.id');
        $this->db->join('materials m', 'd.component_item_id = m.id AND d.component_item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'd.component_item_id = sf.id AND d.component_item_type = "semifinished"', 'left');
        $this->db->where('d.wo_header_id', $wo_id);
        $this->db->where('h.status', 'Received');
        // PASTIKAN TIDAK ADA GROUP BY DI SINI
        return $this->db->get()->result();
    }

    // Fungsi ini (get_total_material_usage) tetap sama, mengambil total pemakaian
    public function get_total_material_usage_by_batch($prod_id, $mr_detail_id, $stage = 'cutting') {
        $this->db->select_sum('qty_used');
        $this->db->where('production_header_id', $prod_id);
        $this->db->where('mr_detail_id', $mr_detail_id); // <-- Kunci Perubahan
        $this->db->where('stage_name', $stage);
        $result = $this->db->get($this->table_inputs)->row();
        return $result ? (float)$result->qty_used : 0;
    }

    /**
     * PERBAIKAN:
     * Fungsi ini sekarang aman karena controller akan mengirim
     * 'item_id' dan 'item_type' dalam $data.
     */
    public function save_material_usage($data) {
        $this->db->trans_start();

        // Data (termasuk item_id, item_type, dan mr_detail_id) di-insert
        // 'mr_detail_id' sekarang DIJAMIN tidak null.
        $this->db->insert($this->table_inputs, $data);
        
        // Buat ledger (WIP Keluar)
        $last_balance = $this->Stock_ledger_model->get_last_balance($data['item_id'], $data['item_type']);
        $ledger_data = [
            'item_id'           => $data['item_id'], 
            'item_type'         => $data['item_type'],
            'transaction_date'  => date('Y-m-d H:i:s'),
            'transaction_type'  => 'wip_out',
            'transaction_id'    => $data['production_header_id'], 
            'source_doc_type'   => 'PROD',
            'source_doc_id'     => $data['production_header_id'],
            'qty_in'            => 0,
            'qty_out'           => $data['qty_used'], 
            'balance'           => $last_balance - $data['qty_used']
        ];
        $this->Stock_ledger_model->insert($ledger_data);
        
        $this->db->trans_complete();
        return $this->db->trans_status();
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

    public function save_production_output($data) {
        $this->db->trans_start();
        $this->db->insert($this->table_outputs, $data);
        $output_id = $this->db->insert_id();
        
        // Hanya item 'semifinished' (bukan 'scrap') yang masuk ledger
        if ($data['item_type'] != 'scrap') {
            $last_balance = $this->Stock_ledger_model->get_last_balance($data['item_id'], $data['item_type']);
            $ledger_data = [
                'item_id'           => $data['item_id'], 
                'item_type'         => $data['item_type'],
                'transaction_date'  => date('Y-m-d H:i:s'),
                'transaction_type'  => 'wip_in',
                'transaction_id'    => $output_id,
                'source_doc_type'   => 'PROD',
                'source_doc_id'     => $data['production_header_id'],
                'qty_in'            => $data['qty_produced'],
                'qty_out'           => 0,
                'balance'           => $last_balance + $data['qty_produced']
            ];
            $this->Stock_ledger_model->insert($ledger_data);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function get_production_inputs($prod_id, $stage = 'cutting') {
        $this->db->select('pi.*, CASE WHEN pi.item_type = "material" THEN m.nama_material ELSE sf.nama_barang END as item_name', FALSE);
        $this->db->from($this->table_inputs . ' pi');
        $this->db->join('materials m', 'pi.item_id = m.id AND pi.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'pi.item_id = sf.id AND pi.item_type = "semifinished"', 'left');
        $this->db->where('pi.production_header_id', $prod_id);
        $this->db->where('pi.stage_name', $stage);
        return $this->db->get()->result();
    }

    public function get_production_outputs($prod_id, $stage = 'cutting') {
        $this->db->select('o.*, u_rec.fullname as receiver_name, u_qc.fullname as qc_name,
            CASE 
                WHEN o.item_type = "semifinished" THEN sf.kode_barang 
                WHEN o.item_type = "scrap" THEN ms.scrap_code 
            END as item_code,
            CASE 
                WHEN o.item_type = "semifinished" THEN sf.nama_barang 
                WHEN o.item_type = "scrap" THEN ms.scrap_name 
            END as item_name
        ', FALSE);
        $this->db->from($this->table_outputs . ' o');
        $this->db->join('semifinished_goods sf', 'o.item_id = sf.id AND o.item_type = "semifinished"', 'left');
        $this->db->join('master_scrap ms', 'o.item_id = ms.id AND o.item_type = "scrap"', 'left');
        $this->db->join('users u_rec', 'o.received_by = u_rec.id', 'left');
        $this->db->join('users u_qc', 'o.qc_checked_by = u_qc.id', 'left');
        $this->db->where('o.production_header_id', $prod_id);
        $this->db->where('o.stage_name', $stage);
        return $this->db->get()->result();
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

    /**
     * FUNGSI BARU: Hapus 1 baris input dan kembalikan stok
     */
    public function delete_input_by_id($input_id) {
        $this->db->trans_start();

        // 1. Ambil data input
        $input = $this->db->get_where($this->table_inputs, ['id' => $input_id])->row();

        if ($input) {
            // 2. Reversal Stock Ledger (WIP Keluar -> dikembalikan jadi WIP Masuk)
            $last_balance = $this->Stock_ledger_model->get_last_balance($input->item_id, $input->item_type);
            $ledger_reversal = [
                'item_id'           => $input->item_id,
                'item_type'         => $input->item_type,
                'transaction_date'  => date('Y-m-d H:i:s'),
                'transaction_type'  => 'wip_in_reversal',
                'transaction_id'    => $input_id,
                'source_doc_type'   => 'PROD_IN_DEL',
                'source_doc_id'     => $input->production_header_id,
                'qty_in'            => $input->qty_used,
                'qty_out'           => 0,
                'balance'           => $last_balance + $input->qty_used
            ];
            $this->Stock_ledger_model->insert($ledger_reversal);

            // 3. Hapus data input
            $this->db->where('id', $input_id)->delete($this->table_inputs);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * FUNGSI BARU: Hapus 1 baris output dan kembalikan stok
     */
    public function delete_output_by_id($output_id) {
        $this->db->trans_start();

        // 1. Ambil data output
        $output = $this->db->get_where($this->table_outputs, ['id' => $output_id])->row();

        if ($output) {
            // 2. Reversal Stock Ledger (Hanya jika bukan scrap)
            if ($output->item_type != 'scrap') {
                $last_balance = $this->Stock_ledger_model->get_last_balance($output->item_id, $output->item_type);
                $ledger_reversal = [
                    'item_id'           => $output->item_id,
                    'item_type'         => $output->item_type,
                    'transaction_date'  => date('Y-m-d H:i:s'),
                    'transaction_type'  => 'wip_out_reversal',
                    'transaction_id'    => $output_id,
                    'source_doc_type'   => 'PROD_OUT_DEL',
                    'source_doc_id'     => $output->production_header_id,
                    'qty_in'            => 0,
                    'qty_out'           => $output->qty_produced,
                    'balance'           => $last_balance - $output->qty_produced
                ];
                $this->Stock_ledger_model->insert($ledger_reversal);
            }

            // 3. Hapus data output
            $this->db->where('id', $output_id)->delete($this->table_outputs);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}