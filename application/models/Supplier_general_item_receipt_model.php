<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Supplier_general_item_receipt_model
 * Menangani penerimaan barang general (machine & inventory) dari supplier,
 * dengan source dokumen PO. Tidak melewati QA — langsung masuk stock_ledger
 * setelah di-approve.
 */
class Supplier_general_item_receipt_model extends CI_Model {

    private $table_header  = 'sgr_header';
    private $table_details = 'sgr_details';

    // === HEADER ===
    public function add_header($data)
    {
        $this->db->reset_query();
        $this->db->insert($this->table_header, $data);
        return $this->db->affected_rows() > 0 ? $this->db->insert_id() : false;
    }

    /**
     * @param array|null $categories Filter kategori HEADER (General/Produksi/Lainnya).
     *                               Null = tidak difilter (semua kategori).
     */
    public function get_all($categories = null)
    {
        $first_detail_subquery = "
            SELECT sgr_header_id, MIN(id) as first_detail_id
            FROM sgr_details
            GROUP BY sgr_header_id
        ";

        $this->db->select('
            h.id, h.sgr_number, h.sgr_date, h.status, h.base_on, h.warehouse_type, h.category,
            COALESCE(s.nama_supplier, s_po.nama_supplier) as nama_supplier
        ');
        $this->db->from($this->table_header . ' h');
        $this->db->join('suppliers s', 'h.supplier_id = s.id', 'left');
        $this->db->join("({$first_detail_subquery}) sd_first", 'h.id = sd_first.sgr_header_id', 'left');
        $this->db->join('sgr_details sd', 'sd_first.first_detail_id = sd.id', 'left');
        $this->db->join('po_header po', 'sd.source_doc_type = "PO" AND sd.source_doc_header_id = po.id', 'left');
        $this->db->join('suppliers s_po', 's_po.id = po.supplier_id', 'left');
        if (!empty($categories)) {
            $this->db->where_in('h.category', $categories);
        }
        $this->db->order_by('h.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id)
    {
        $this->db->reset_query();
        $this->db->select('
            h.*, s.nama_supplier,
            creator.fullname  as creator_name,
            creator.signature_path as creator_signature,
            approver.fullname as approver_name,
            approver.signature_path as approver_signature,
            cc_bc.description as bc_type_desc
        ');
        $this->db->from($this->table_header . ' h');
        $this->db->join('suppliers s', 'h.supplier_id = s.id', 'left');
        $this->db->join('users creator', 'h.created_by = creator.id', 'left');
        $this->db->join('users approver', 'h.approved_by = approver.id', 'left');
        $this->db->join('common_code cc_bc', 'h.bc_type_code = cc_bc.code AND cc_bc.group_code = "BC_TYPE"', 'left');
        $this->db->where('h.id', $id);
        $header = $this->db->get()->row();

        if ($header && empty($header->nama_supplier)) {
            // Fallback supplier dari PO detail pertama
            $first = $this->db->select('sd.source_doc_header_id')
                ->from($this->table_details . ' sd')
                ->where('sd.sgr_header_id', $id)
                ->limit(1)
                ->get()->row();
            if ($first) {
                $po = $this->db->select('s.nama_supplier, p.supplier_id')
                    ->from('po_header p')
                    ->join('suppliers s', 's.id = p.supplier_id', 'left')
                    ->where('p.id', $first->source_doc_header_id)
                    ->get()->row();
                if ($po) {
                    $header->nama_supplier = $po->nama_supplier;
                    if (empty($header->supplier_id)) {
                        $header->supplier_id = $po->supplier_id;
                    }
                }
            }
        }
        return $header;
    }

    public function update_header($id, $data)
    {
        unset($data['status'], $data['approved_by'], $data['approved_at']);
        $this->db->reset_query();
        return $this->db->where('id', $id)->where('status', 'Draft')->update($this->table_header, $data);
    }

    /**
     * Hapus SGR beserta seluruh item detail-nya (cascade).
     * Hanya untuk status Draft — Draft belum pernah posting ke stock_ledger,
     * jadi tidak ada stok yang perlu dikembalikan.
     */
    public function delete_header($id)
    {
        $this->db->reset_query();
        $header = $this->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            log_message('warning', 'SGR Delete Header Gagal: status bukan Draft. ID: ' . $id);
            return false;
        }

        // Defensif: pastikan tidak ada ledger nyangkut (mis. sisa unapprove yang gagal).
        $this->db->reset_query();
        $ledger_count = $this->db
            ->where('transaction_type', 'sgr_in')
            ->where('transaction_id', $id)
            ->count_all_results('stock_ledger');
        if ($ledger_count > 0) {
            log_message('warning', 'SGR Delete Header Gagal: masih ada stock_ledger. ID: ' . $id);
            return false;
        }

        $this->db->trans_start();
        $this->db->reset_query();
        $this->db->where('sgr_header_id', $id)->delete($this->table_details);
        $this->db->reset_query();
        $this->db->where('id', $id)->where('status', 'Draft')->delete($this->table_header);
        $header_deleted = $this->db->affected_rows() > 0;
        $this->db->trans_complete();

        return $this->db->trans_status() !== FALSE && $header_deleted;
    }

    public function update_status($id, $new_status, $user_id = null)
    {
        $this->db->reset_query();
        $data = ['status' => $new_status];
        if ($new_status == 'Completed' && $user_id) {
            $data['approved_by'] = $user_id;
            $data['approved_at'] = date('Y-m-d H:i:s');
        } elseif ($new_status == 'Draft') {
            $data['approved_by'] = null;
            $data['approved_at'] = null;
        }
        $this->db->where('id', $id)->update($this->table_header, $data);
        return $this->db->affected_rows() > 0;
    }

    public function generate_sgr_number()
    {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('sgr', 'sgr_header', 'sgr_number');
    }

    // === DETAILS ===
    public function get_details_by_id($sgr_id)
    {
        $this->db->reset_query();
        $select = "
            d.id, d.sgr_header_id, d.receive_qty, d.unit_value, d.total_value,
            d.source_doc_type, d.source_doc_header_id, d.source_doc_detail_id,
            d.item_id, d.item_type, d.category, d.bc_type_code, d.bc_number, d.bc_date,
            poh.po_number as source_doc_number,
            CASE WHEN d.item_type = 'machine'   THEN mc.kode_mesin
                 WHEN d.item_type = 'inventory' THEN i.kode_barang END as item_code,
            CASE WHEN d.item_type = 'machine'   THEN mc.nama_mesin
                 WHEN d.item_type = 'inventory' THEN i.nama_barang END as item_name,
            CASE WHEN d.item_type = 'machine'   THEN mc.satuan
                 WHEN d.item_type = 'inventory' THEN i.satuan END as satuan
        ";
        $this->db->select($select, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join('machines mc', 'd.item_id = mc.id AND d.item_type = "machine"', 'left');
        $this->db->join('inventories i', 'd.item_id = i.id AND d.item_type = "inventory"', 'left');
        $this->db->join('po_header poh', 'd.source_doc_header_id = poh.id AND d.source_doc_type = "PO"', 'left');
        $this->db->where('d.sgr_header_id', $sgr_id);
        return $this->db->get()->result();
    }

    public function insert_item($data)
    {
        $this->db->reset_query();
        return $this->db->insert($this->table_details, $data);
    }

    public function delete_item($id)
    {
        $this->db->reset_query();
        return $this->db->where('id', $id)->delete($this->table_details);
    }

    // === STOCK LEDGER INTEGRATION ===
    /**
     * Tulis semua items dari SGR ini ke stock_ledger.
     * Machine & inventory tidak masuk stock_on_hand (tidak butuh batch tracking).
     */
    public function add_stock_to_ledger($sgr_id)
    {
        $details = $this->get_details_by_id($sgr_id);
        if (empty($details)) return true;

        $this->load->model('Stock_ledger_model');
        $header = $this->get_header_by_id($sgr_id);
        if (!$header) {
            log_message('error', "SGR Header $sgr_id tidak ditemukan saat add_stock_to_ledger");
            return false;
        }

        $this->db->trans_start();
        foreach ($details as $item) {
            if (!isset($item->receive_qty) || (float)$item->receive_qty <= 0) continue;

            // Setiap penerimaan HARUS mendarat di salah satu bucket fisik yang
            // nyata — kalau kategori kosong (mis. baris lama sebelum fitur ini,
            // atau PO yang tidak nyambung ke IRF), default ke General.
            $category = !empty($item->category) ? $item->category : 'General';

            $last_balance = $this->Stock_ledger_model->get_last_balance($item->item_id, $item->item_type, $category);
            $new_balance  = $last_balance + (float)$item->receive_qty;

            $bc_type_code = $item->bc_type_code ?? $header->bc_type_code ?? null;
            $bc_number    = $item->bc_number    ?? $header->bc_number    ?? null;
            $bc_date      = $item->bc_date      ?? $header->bc_date      ?? null;

            $ledger = [
                'item_id'          => $item->item_id,
                'item_type'        => $item->item_type,
                'category'         => $category,
                'transaction_date' => ($header->sgr_date ?? date('Y-m-d')) . ' ' . date('H:i:s'),
                'transaction_type' => 'sgr_in',
                'transaction_id'   => $sgr_id,
                'source_doc_type'  => $item->source_doc_type,
                'source_doc_id'    => $item->source_doc_header_id,
                'qty_in'           => (float)$item->receive_qty,
                'qty_out'          => 0,
                'unit_value'       => (float)($item->unit_value ?? 0),
                'balance'          => $new_balance,
                'stock_on_hand_id' => null,
            ];
            if ($bc_type_code || $bc_number || $bc_date) {
                $ledger['bc_type_code'] = $bc_type_code;
                $ledger['bc_number']    = $bc_number;
                $ledger['bc_date']      = $bc_date;
            }

            if (!$this->Stock_ledger_model->insert($ledger)) {
                log_message('error', "SGR $sgr_id: gagal insert stock_ledger untuk item " . $item->item_id);
                $this->db->trans_rollback();
                return false;
            }
        }
        $this->db->trans_complete();
        return $this->db->trans_status() !== FALSE;
    }

    /**
     * Hapus semua ledger entry dari SGR ini (saat unapprove).
     * Validasi: stok dari SGR ini belum boleh dipakai.
     */
    public function remove_stock_from_ledger($sgr_id)
    {
        $this->load->model('Stock_ledger_model');

        // Cek apakah ada transaksi out setelah SGR ini yang membuat balance di bawah qty SGR
        // Sederhana: untuk machine/inventory kita izinkan reset selama balance >= qty yang akan dihapus.
        $details = $this->get_details_by_id($sgr_id);
        foreach ($details as $item) {
            $category = !empty($item->category) ? $item->category : 'General';
            $current = $this->Stock_ledger_model->get_last_balance($item->item_id, $item->item_type, $category);
            if ($current < (float)$item->receive_qty - 0.0001) {
                log_message('warning', "SGR $sgr_id: stok item {$item->item_id} sudah terpakai (current $current < qty {$item->receive_qty}).");
                return false;
            }
        }

        // Delete ledger entries by transaction_type + transaction_id
        return $this->Stock_ledger_model->delete_by_transaction('sgr_in', $sgr_id) !== false;
    }

    // === AJAX HELPERS ===
    /**
     * Status penerimaan per baris PO untuk SGR tertentu (analog
     * Warehouse_in_model::get_po_lines_with_receipt, tapi dari sgr_details).
     *
     * - qty_received     : dari SGR Completed (sudah masuk stok)
     * - qty_draft_other  : tertahan di SGR Draft LAIN (belum masuk stok, tapi
     *                      tetap memotong sisa supaya dua draft tidak bisa
     *                      menerima baris yang sama melebihi PO)
     * - qty_this_doc     : sudah ada di SGR ini
     *
     * $header dipakai untuk menentukan baris mana yang BOLEH diterima di SGR
     * ini: tipe gudang harus sama (machine/inventory) dan kategori PO line
     * tidak boleh bertentangan dengan kategori dokumen (kecuali 'Lainnya').
     *
     * Versi lama (get_available_po_items) hanya menganggap baris yang BELUM
     * PERNAH disentuh SGR mana pun, sehingga PO yang baru diterima sebagian
     * hilang dari modal Load PO dan sisanya tidak bisa diterima.
     */
    public function get_po_lines_with_receipt(array $po_ids, $header)
    {
        $po_ids = array_values(array_filter(array_map('intval', $po_ids)));
        if (empty($po_ids) || !$header) return [];
        $sgr_id = (int) $header->id;

        $this->db->reset_query();
        $sql = "
            SELECT
                pd.id AS po_detail_id, pd.po_header_id, pd.item_id, pd.item_type, pd.category,
                pd.qty AS qty_po, pd.local_price,
                CASE pd.item_type
                    WHEN 'machine'      THEN mc.kode_mesin    WHEN 'inventory'    THEN i.kode_barang
                    WHEN 'material'     THEN m.kode_material  WHEN 'semifinished' THEN sf.kode_barang
                END AS item_code,
                CASE pd.item_type
                    WHEN 'machine'      THEN mc.nama_mesin    WHEN 'inventory'    THEN i.nama_barang
                    WHEN 'material'     THEN m.nama_material  WHEN 'semifinished' THEN sf.nama_barang
                END AS item_name,
                CASE pd.item_type
                    WHEN 'machine'      THEN mc.satuan  WHEN 'inventory'    THEN i.satuan
                    WHEN 'material'     THEN m.satuan   WHEN 'semifinished' THEN sf.satuan
                END AS unit,
                COALESCE(SUM(CASE WHEN sh.status = 'Completed' THEN sd.receive_qty END), 0) AS qty_received,
                COALESCE(SUM(CASE WHEN sh.status = 'Draft' AND sh.id <> ? THEN sd.receive_qty END), 0) AS qty_draft_other,
                COALESCE(SUM(CASE WHEN sh.status = 'Draft' AND sh.id =  ? THEN sd.receive_qty END), 0) AS qty_this_doc,
                GROUP_CONCAT(DISTINCT CASE WHEN sh.status = 'Draft' AND sh.id <> ? THEN sh.sgr_number END SEPARATOR ', ') AS draft_other_numbers
            FROM po_details pd
            LEFT JOIN sgr_details sd ON sd.source_doc_detail_id = pd.id AND sd.source_doc_type = 'PO'
            LEFT JOIN sgr_header  sh ON sh.id = sd.sgr_header_id
            LEFT JOIN machines           mc ON mc.id = pd.item_id AND pd.item_type = 'machine'
            LEFT JOIN inventories        i  ON i.id  = pd.item_id AND pd.item_type = 'inventory'
            LEFT JOIN materials          m  ON m.id  = pd.item_id AND pd.item_type = 'material'
            LEFT JOIN semifinished_goods sf ON sf.id = pd.item_id AND pd.item_type = 'semifinished'
            WHERE pd.po_header_id IN (" . implode(',', $po_ids) . ")
            GROUP BY pd.id
            ORDER BY pd.po_header_id, pd.id";
        $rows = $this->db->query($sql, [$sgr_id, $sgr_id, $sgr_id])->result();

        foreach ($rows as $r) {
            $r->qty_po          = (float) $r->qty_po;
            $r->qty_received    = (float) $r->qty_received;
            $r->qty_draft_other = (float) $r->qty_draft_other;
            $r->qty_this_doc    = (float) $r->qty_this_doc;
            $r->qty_remaining   = round($r->qty_po - $r->qty_received - $r->qty_draft_other - $r->qty_this_doc, 4);

            // Urutan cek penting: alasan "tidak boleh di dokumen ini" dulu,
            // baru status progres penerimaannya.
            if (!in_array($r->item_type, ['machine', 'inventory'], true)) {
                $r->status_key = 'other_module';          // material -> Warehouse In
            } elseif ($r->item_type !== $header->warehouse_type) {
                $r->status_key = 'other_warehouse';       // mesin di SGR inventory, dst.
            } elseif ($header->category !== 'Lainnya' && !empty($r->category) && $r->category !== $header->category) {
                $r->status_key = 'other_category';        // PO line Produksi di SGR General, dst.
            } elseif ($r->qty_received >= $r->qty_po - 0.0001) {
                $r->status_key = 'done';
            } elseif ($r->qty_remaining <= 0.0001 && $r->qty_this_doc > 0) {
                $r->status_key = 'in_this_doc';
            } elseif ($r->qty_remaining <= 0.0001) {
                $r->status_key = 'held_in_draft';
            } elseif ($r->qty_received > 0 || $r->qty_this_doc > 0 || $r->qty_draft_other > 0) {
                $r->status_key = 'partial';
            } else {
                $r->status_key = 'not_received';
            }
            $r->allowed_here = !in_array($r->status_key, ['other_module', 'other_warehouse', 'other_category'], true);
            $r->receivable   = $r->allowed_here && $r->qty_remaining > 0.0001;
        }
        return $rows;
    }

    /**
     * List PO yang berisi item machine/inventory (untuk modal pilih PO).
     */
    public function get_available_pos($search = '')
    {
        $this->db->reset_query();
        $this->db->select('
            ph.id as po_id,
            ph.po_number,
            ph.po_date,
            s.nama_supplier as supplier_name,
            COUNT(pd.id) as total_items,
            SUM(CASE WHEN sd.id IS NULL THEN 1 ELSE 0 END) as available_items
        ');
        $this->db->from('po_header ph');
        $this->db->join('suppliers s', 's.id = ph.supplier_id', 'left');
        $this->db->join('po_details pd', 'pd.po_header_id = ph.id', 'left');
        $this->db->join('sgr_details sd', 'sd.source_doc_detail_id = pd.id AND sd.source_doc_type = "PO"', 'left');
        $this->db->where_in('ph.status', ['Confirmed', 'Partially Received']);
        $this->db->where_in('pd.item_type', ['machine', 'inventory']);
        $this->db->group_by('ph.id');
        if (!empty($search)) {
            $this->db->group_start()
                ->like('ph.po_number', $search)
                ->or_like('s.nama_supplier', $search)
                ->group_end();
        }
        $this->db->order_by('ph.po_date', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Get items dari PO yang belum diterima via SGR (machine/inventory saja).
     */
    public function get_available_po_items($po_id)
    {
        $this->db->reset_query();
        return $this->db
            ->select('pd.id as po_detail_id, pd.item_id, pd.item_type, pd.qty, pd.local_price')
            ->from('po_details pd')
            ->join('sgr_details sd', 'sd.source_doc_detail_id = pd.id AND sd.source_doc_type = "PO"', 'left')
            ->where('pd.po_header_id', $po_id)
            ->where('sd.id', NULL)
            ->where_in('pd.item_type', ['machine', 'inventory'])
            ->get()->result();
    }

    /**
     * Untuk dropdown pilih item per PO + warehouse_type (machine/inventory).
     */
    public function get_available_items($po_id, $warehouse_type)
    {
        $this->db->reset_query();
        $received_subquery = $this->db
            ->select('source_doc_detail_id, SUM(receive_qty) as total_received')
            ->from($this->table_details)
            ->where('source_doc_header_id', $po_id)
            ->where('source_doc_type', 'PO')
            ->group_by('source_doc_detail_id')
            ->get_compiled_select();
        $this->db->reset_query();

        $select = "
            pd.id as source_detail_id, pd.item_id, pd.item_type, pd.category,
            pd.qty as source_qty, pd.local_price as source_unit_price,
            GREATEST(0, pd.qty - IFNULL(received.total_received, 0)) as remaining_qty,
            CASE WHEN pd.item_type = 'machine'   THEN mc.kode_mesin
                 WHEN pd.item_type = 'inventory' THEN i.kode_barang END as item_code,
            CASE WHEN pd.item_type = 'machine'   THEN mc.nama_mesin
                 WHEN pd.item_type = 'inventory' THEN i.nama_barang END as item_name
        ";
        $this->db->select($select, FALSE);
        $this->db->from('po_details pd');
        $this->db->join('machines mc',    'pd.item_id = mc.id AND pd.item_type = "machine"', 'left');
        $this->db->join('inventories i',  'pd.item_id = i.id AND pd.item_type = "inventory"', 'left');
        $this->db->join("({$received_subquery}) received", 'pd.id = received.source_doc_detail_id', 'left');
        $this->db->where('pd.po_header_id', $po_id);
        $this->db->where('pd.item_type', $warehouse_type);
        $this->db->having('remaining_qty >', 0.001);
        return $this->db->get()->result();
    }
}
