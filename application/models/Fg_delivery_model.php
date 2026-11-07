<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fg_delivery_model extends CI_Model {

    private $table_header = 'fg_delivery_header';
    private $table_details = 'fg_delivery_details';
    private $table_stock = 'stock_ledger';
    private $table_customers = 'customers';
    private $table_sc = 'sales_contracts';
    private $table_fg = 'finished_goods';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('Fg_stock_ledger_model'); // untuk get_last_balance
    }

    /**
     * Get list deliveries with filter
     */
    public function get_all($filters = []) {
        // Nomor IPO diambil dari BARIS DETAIL (satu pengiriman bisa mencakup
        // beberapa IPO); kolom sales_contract_id di header hanya peninggalan lama
        // dan pada dokumen baru isinya NULL. Sebelumnya query ini men-join dari
        // header saja, sehingga kolom IPO di daftar SELALU kosong padahal di
        // halaman detail nomornya muncul -- detail memang membacanya dari baris
        // detail (user 2026-09-30: "kenapa di detail nomor sc nya keluar di
        // index list kenapa ga keluar").
        $sc_agg = "(
            SELECT d.fg_delivery_header_id,
                   GROUP_CONCAT(DISTINCT s.sc_number ORDER BY s.sc_number SEPARATOR ', ') AS sc_numbers
              FROM {$this->table_details} d
              JOIN {$this->table_sc} s ON s.id = d.sales_contract_id
             GROUP BY d.fg_delivery_header_id
        )";

        $this->db->select("h.id, h.delivery_number, h.delivery_date, h.status,
                           COALESCE(agg.sc_numbers, sc.sc_number) AS sc_number,
                           c.nama_customer, h.bc_number, h.bc_date", FALSE);
        $this->db->from($this->table_header . ' h');
        $this->db->join($sc_agg . ' agg', 'agg.fg_delivery_header_id = h.id', 'left');
        $this->db->join($this->table_sc . ' sc', 'h.sales_contract_id = sc.id', 'left');
        $this->db->join($this->table_customers . ' c', 'h.customer_id = c.id', 'left');

        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('h.delivery_number', $term);
            $this->db->or_like('sc.sc_number', $term);
            $this->db->or_like('agg.sc_numbers', $term);
            $this->db->or_like('c.nama_customer', $term);
            $this->db->group_end();
        }
        if (!empty($filters['start_date'])) $this->db->where('h.delivery_date >=', $filters['start_date']);
        if (!empty($filters['end_date'])) $this->db->where('h.delivery_date <=', $filters['end_date']);
        if (!empty($filters['customer_id'])) $this->db->where('h.customer_id', $filters['customer_id']);
        if (!empty($filters['status'])) $this->db->where('h.status', $filters['status']);

        $this->db->order_by('h.id', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Get header by id
     */
    public function get_header_by_id($id)
    {
        $this->db->select("
            h.*,
            c.nama_customer,
            c.alamat AS customer_master_address,
            COALESCE(NULLIF(TRIM(h.shipping_address), ''), c.alamat) AS customer_address,
            sc.sc_number,
            cc.description AS bc_type_desc
        ");
        $this->db->from("fg_delivery_header h");
        $this->db->join("customers c", "h.customer_id = c.id", "left");
        $this->db->join("sales_contracts sc", "h.sales_contract_id = sc.id", "left");
        $this->db->join("common_code cc", "cc.id = h.bc_type_id AND cc.group_code = 'BC_TYPE'", "left");
        $this->db->where("h.id", $id);

        $query = $this->db->get();
        return $query->row();
    }


    /**
     * Get details by header id
     */
    public function get_details_by_header_id($header_id) {
        // unit_price & currency_code diambil dari IPO-nya -- dipakai kolom Harga
        // & Total Harga pada cetak Delivery Note.
        // d.* sudah mencakup nw & gw kalau migrasi berat sudah dijalankan.
        $this->db->select("d.*, fg.kode_barang, fg.nama_barang, fg.satuan,
                           sc.sc_number, sc.unit_price, sc.currency_code", FALSE);
        $this->db->from($this->table_details . " d");
        $this->db->join($this->table_fg . " fg", 'd.fg_id = fg.id', 'left');
        $this->db->join($this->table_sc . " sc", 'd.sales_contract_id = sc.id', 'left');
        $this->db->where('d.fg_delivery_header_id', $header_id);
        return $this->db->get()->result();
    }

    /**
     * Get list customers (simple)
     */
    public function get_customers() {
        return $this->db->get($this->table_customers)->result();
    }

    /**
     * Get customer by id
     */
    public function get_customer_by_id($id) {
        return $this->db->get_where($this->table_customers, ['id' => $id])->row();
    }

    /**
     * Generate delivery number
     */
    public function generate_delivery_number() {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('fg_delivery', 'fg_delivery_header', 'delivery_number');
    }

    /**
     * Insert header and return id
     */
    public function insert_header($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table_header, $data);
        return $this->db->insert_id();
    }

    /**
     * insert delivery details (after header exists)
     */
    public function insert_delivery($header_id, $details = []) {
        $header = $this->db->get_where($this->table_header, ['id' => $header_id])->row();
        if (!$header) return false;

        $this->db->trans_start();
        // delete any existing details (safe)
        $this->db->where('fg_delivery_header_id', $header_id)->delete($this->table_details);

        if (!empty($details)) {
            foreach ($details as &$d) {
                // ensure header id set
                $d['fg_delivery_header_id'] = $header_id;
            }
            unset($d);
            $this->db->insert_batch($this->table_details, $details);
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Update header
     */
    public function update_header($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update($this->table_header, $data);
    }

    /**
     * Delete a single delivery detail item
     */
    public function delete_detail($detail_id) {
        // Check if the detail exists and belongs to a Draft header
        $this->db->select('fg_delivery_header_id');
        $this->db->from($this->table_details);
        $this->db->where('id', $detail_id);
        $detail = $this->db->get()->row();

        if (!$detail) return false; // Detail not found

        $header = $this->get_header_by_id($detail->fg_delivery_header_id);
        if (!$header || $header->status != 'Draft') return false; // Only allow deletion from Draft headers

        $this->db->trans_start();
        $this->db->where('id', $detail_id)->delete($this->table_details);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    /**
     * Delete delivery (Draft only)
     */
    public function delete_delivery($id) {
        $h = $this->get_header_by_id($id);
        if (!$h || $h->status != 'Draft') return false;
        $this->db->trans_start();
        $this->db->where('fg_delivery_header_id', $id)->delete($this->table_details);
        $this->db->where('id', $id)->delete($this->table_header);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Cancel delivery (Confirmed -> Cancelled) -> create reversal ledger entries
     */
    public function cancel_delivery($id) {
        $h = $this->get_header_by_id($id);
        if (!$h || $h->status != 'Confirmed') return false;
        $details = $this->get_details_by_header_id($id);
        if (empty($details)) return false;

        $this->db->trans_start();
        $this->db->where('id', $id)->update($this->table_header, ['status' => 'Cancelled']);

        // for each detail, create reversal (increase stock)
        $now = date('Y-m-d H:i:s');
        foreach ($details as $d) {
            $last_balance = $this->Fg_stock_ledger_model->get_last_balance($d->fg_id);
            $ledger = [
                'item_id' => $d->fg_id,
                'item_type' => 'finished_goods',
                'transaction_date' => $now,
                'transaction_type' => 'fg_out_reversal',
                'transaction_id' => $d->id,
                'source_doc_type' => 'FG_CANCEL',
                'source_doc_id' => $id,
                'qty_in' => $d->qty_shipped,
                'qty_out' => 0,
                'balance' => $last_balance + $d->qty_shipped,
                'bc_type_code' => $h->bc_type_id,
                'bc_number' => $h->bc_number,
                'bc_date' => $h->bc_date
            ];
            $this->db->insert($this->table_stock, $ledger);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Ship delivery (confirm) -> insert stock_ledger fg_out entries and set header status Confirmed
     */
    /**
     * Selaraskan status IPO (Sales Contract) dengan qty yang BENAR-BENAR sudah
     * terkirim.
     *
     * Dipanggil setelah pengiriman dikonfirmasi maupun dibatalkan, jadi
     * hasilnya selalu dihitung ulang dari data -- bukan ditebak dari kejadian
     * terakhir. Aman dipanggil berulang.
     *
     * Dua hal yang diperbaiki di sini (dilaporkan user 2026-10-01: "kapan
     * status open berganti menjadi finish"):
     *
     *  1. Dulu IPO diambil dari `fg_delivery_header.sales_contract_id` --
     *     kolom peninggalan lama yang pada dokumen baru NULL, sehingga
     *     syaratnya tidak pernah terpenuhi dan status IPO tidak pernah
     *     berubah sama sekali. Sekarang diambil dari BARIS pengiriman, yang
     *     juga menangani satu DO berisi beberapa IPO.
     *
     *  2. Dulu IPO langsung ditandai Finish pada pengiriman PERTAMA, berapa
     *     pun jumlahnya. IPO 1.200 unit yang baru dikirim 100 ikut tertutup.
     *     Sekarang Finish hanya kalau total terkirim sudah mencapai PO Qty.
     *
     * Hanya menyentuh IPO yang statusnya Open atau Finish -- Draft, Rejected
     * & Cancelled tidak boleh ikut terbuka lagi karena urusan pengiriman.
     *
     * @return array [sc_id => status baru] untuk dicatat di log
     */
    public function sync_sc_status($sc_ids) {
        $sc_ids = array_values(array_unique(array_filter((array) $sc_ids)));
        if (empty($sc_ids)) return [];

        $this->load->model('Sc_model');
        $hasil = [];

        foreach ($sc_ids as $sc_id) {
            $sc = $this->db->select('id, status, po_qty')
                           ->where('id', (int) $sc_id)
                           ->get($this->table_sc)->row();
            if (!$sc) continue;
            if (!in_array($sc->status, ['Open', 'Finish'], true)) continue;

            // Yang dihitung hanya pengiriman yang SUDAH dikirim (Confirmed);
            // Draft belum keluar gudang jadi belum boleh menutup IPO.
            $row = $this->db->select_sum('d.qty_shipped', 'terkirim')
                ->from($this->table_details . ' d')
                ->join($this->table_header . ' h', 'h.id = d.fg_delivery_header_id', 'inner')
                ->where('d.sales_contract_id', (int) $sc_id)
                ->where('h.status', 'Confirmed')
                ->get()->row();
            $terkirim = $row ? (float) $row->terkirim : 0.0;
            $po_qty   = (float) $sc->po_qty;

            // po_qty 0/kosong tidak bisa dipakai jadi patokan -- biarkan apa adanya.
            if ($po_qty <= 0) continue;

            $baru = ($terkirim + 0.0001 >= $po_qty) ? 'Finish' : 'Open';
            if ($baru !== $sc->status) {
                $this->Sc_model->update_status($sc_id, $baru);
                log_message('info', "SC {$sc_id}: {$sc->status} -> {$baru} (terkirim {$terkirim} dari {$po_qty}).");
            }
            $hasil[$sc_id] = $baru;
        }
        return $hasil;
    }

    /**
     * Daftar IPO yang tersentuh satu dokumen pengiriman, dibaca dari BARIS-nya
     * (satu DO bisa memuat beberapa IPO), dengan kolom header sbg cadangan utk
     * dokumen lama.
     */
    private function _sc_ids_of_delivery($header_id, $header = null) {
        $ids = [];
        foreach ($this->db->select('DISTINCT sales_contract_id', FALSE)
                          ->where('fg_delivery_header_id', (int) $header_id)
                          ->where('sales_contract_id IS NOT NULL', null, FALSE)
                          ->get($this->table_details)->result() as $r) {
            $ids[] = (int) $r->sales_contract_id;
        }
        if (empty($ids) && $header && !empty($header->sales_contract_id)) {
            $ids[] = (int) $header->sales_contract_id;
        }
        return array_values(array_unique($ids));
    }

    public function ship_delivery($id, $userId = null) {
        $h = $this->get_header_by_id($id);
        if (!$h || $h->status != 'Draft') return false;
        $details = $this->get_details_by_header_id($id);
        if (empty($details)) return false;

        // validate stock availability
        foreach ($details as $d) {
            $available = $this->Fg_stock_ledger_model->get_last_balance($d->fg_id);
            if ($d->qty_shipped > $available + 0.0001) {
                // insufficient stock
                $this->session->set_flashdata('error', 'Stok tidak cukup untuk item: ' . ($d->kode_barang ?? $d->fg_id));
                return false;
            }
        }

        $this->db->trans_start();

        // update header status
        $this->db->where('id', $id)->update($this->table_header, [
            'status' => 'Confirmed',
            'shipped_at' => date('Y-m-d H:i:s'),
            'shipped_by_user' => $userId
        ]);

        // insert ledger entries
        $now = date('Y-m-d H:i:s');
        foreach ($details as $d) {
            $last_balance = $this->Fg_stock_ledger_model->get_last_balance($d->fg_id);
            $ledger = [
                'item_id' => $d->fg_id,
                'item_type' => 'finished_goods',
                'transaction_date' => $now,
                'transaction_type' => 'fg_out',
                'transaction_id' => $d->id,
                'source_doc_type' => 'FG_DELIVERY',
                'source_doc_id' => $id,
                'qty_in' => 0,
                'qty_out' => $d->qty_shipped,
                'balance' => $last_balance - $d->qty_shipped,
                'bc_type_code' => $h->bc_type_id,
                'bc_number' => $h->bc_number,
                'bc_date' => $h->bc_date
            ];
            $this->db->insert($this->table_stock, $ledger);
        }

        // Selaraskan status IPO dgn qty yang sudah benar-benar terkirim.
        // IPO diambil dari baris pengiriman, bukan kolom header yang NULL.
        $this->sync_sc_status($this->_sc_ids_of_delivery($id, $h));

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Unship (unconfirm) -> DELETE the original fg_out entries and recalculate balances
     * PERBAIKAN: Jangan insert reversal entry, cukup hapus fg_out original
     */
    public function unship_delivery($id) {
        $h = $this->get_header_by_id($id);
        if (!$h || $h->status != 'Confirmed') return false;
        
        // Cegah unship jika sudah diverifikasi di EXIM
        if ($h->exim_verified) {
            return false; // Silent fail - akan dicek di controller
        }
        
        $details = $this->get_details_by_header_id($id);
        if (empty($details)) return false;

        $this->db->trans_start();

        // set header back to Draft
        $this->db->where('id', $id)->update($this->table_header, [
            'status' => 'Draft',
            'shipped_at' => NULL,
            'shipped_by_user' => NULL
        ]);

        // DELETE fg_out entries (don't insert reversal, just remove the original)
        foreach ($details as $d) {
            $this->db->where('transaction_type', 'fg_out')
                    ->where('transaction_id', $d->id)
                    ->where('source_doc_type', 'FG_DELIVERY')
                    ->where('source_doc_id', $id)
                    ->delete($this->table_stock);
        }
        
        // RECALCULATE all balances for affected items
        // Get all affected FG items
        $affected_fgs = [];
        foreach ($details as $d) {
            $affected_fgs[] = $d->fg_id;
        }

        if (!empty($affected_fgs)) {
            // For each affected FG, recalculate all balances from scratch
            foreach ($affected_fgs as $fg_id) {
                $this->_recalculate_fg_balance($fg_id);
            }
        }

        // Hitung ulang, bukan asal dikembalikan ke Open: kalau masih ada
        // pengiriman lain yang menutupi PO Qty, IPO-nya tetap Finish.
        $this->sync_sc_status($this->_sc_ids_of_delivery($id, $h));

        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    
    /**
     * Helper: Recalculate running balance for a finished good
     */
    private function _recalculate_fg_balance($fg_id) {
        // Get all entries for this FG, ordered by ID (creation order)
        $entries = $this->db->select('id, qty_in, qty_out')
                            ->where('item_id', $fg_id)
                            ->where('item_type', 'finished_goods')
                            ->order_by('id', 'ASC')
                            ->get($this->table_stock)
                            ->result();
        
        $running_balance = 0;
        foreach ($entries as $e) {
            $running_balance += ((float)$e->qty_in - (float)$e->qty_out);
            $this->db->where('id', $e->id)->update($this->table_stock, ['balance' => $running_balance]);
        }
    }

    /**
     * Get FG ready for a customer (based on receipts & stock_ledger)
     * returns list of items with current_stock, link to receipt_detail id + production/wo info
     * FIXED: Group by SC + FG, tunjukkan qty available per SC
     */
    public function get_fg_ready_for_customer($customer_id) {
        $sql = "
            SELECT 
                MIN(frd.id) AS receipt_detail_id,
                GROUP_CONCAT(DISTINCT frh.receipt_number ORDER BY frh.receipt_number SEPARATOR ', ') AS receipt_number,
                MAX(frh.receipt_date) AS receipt_date,
                MAX(wo.id) AS wo_id,
                GROUP_CONCAT(DISTINCT wo.wo_number ORDER BY wo.wo_number SEPARATOR ', ') AS wo_number,
                sc.id AS sc_id,
                sc.sc_number,
                c.nama_customer,
                fg.id AS fg_id,
                fg.kode_barang,
                fg.nama_barang,
                fg.satuan,
                NULL AS so_detail_id,
                -- Total yang diterima dari receipt untuk SC + FG ini
                SUM(frd.qty_received) AS total_received,
                -- Total yang sudah dikirim untuk SC + FG ini
                IFNULL(shipped.total_shipped, 0) AS already_shipped,
                -- Available untuk SC ini = total_received - already_shipped
                (SUM(frd.qty_received) - IFNULL(shipped.total_shipped, 0)) AS current_stock
            FROM fg_receipt_details frd
            LEFT JOIN fg_receipt_header frh ON frd.fg_receipt_header_id = frh.id
            -- WO & IPO disimpan di BARIS DETAIL (satu penerimaan bisa mencakup
            -- beberapa WO); kolom yang sama di header hanya peninggalan lama dan
            -- pada dokumen baru isinya NULL. Query ini dulu men-join dari HEADER,
            -- sehingga sc selalu NULL dan syarat sc.customer_id tidak pernah
            -- terpenuhi -- daftar barang siap kirim selalu kosong. Pola COALESCE
            -- detail-dulu-baru-header ini sama dengan yang dipakai
            -- Fg_receipt_model (lihat wo_legacy/sc_legacy di sana).
            LEFT JOIN production_header ph ON ph.id = COALESCE(frd.production_header_id, frh.production_header_id)
            LEFT JOIN wo_header wo         ON wo.id = COALESCE(frd.wo_header_id, frh.wo_header_id)
            LEFT JOIN sales_contracts sc   ON sc.id = COALESCE(frd.sales_contract_id, wo.sales_contract_id, frh.sales_contract_id)
            LEFT JOIN customers c ON sc.customer_id = c.id
            LEFT JOIN finished_goods fg ON frd.fg_id = fg.id
            
            -- Hitung total yang sudah dikirim untuk kombinasi SC + FG ini
            LEFT JOIN (
                SELECT 
                    fdd.fg_id,
                    fdd.sales_contract_id,
                    SUM(fdd.qty_shipped) AS total_shipped
                FROM fg_delivery_details fdd
                JOIN fg_delivery_header fdh ON fdd.fg_delivery_header_id = fdh.id
                WHERE fdh.status IN ('Draft', 'Confirmed')
                GROUP BY fdd.fg_id, fdd.sales_contract_id
            ) AS shipped ON shipped.fg_id = fg.id AND shipped.sales_contract_id = sc.id
            
            -- Alur Penerimaan Barang Jadi berakhir di status 'Approved'
            -- (approve_final, dan di situ pula stok fg_in dibuat). 'Confirmed'
            -- adalah nama status LAMA sebelum ada alur approval -- lihat
            -- catatan di Fg_receipt_model::confirm_receipt(): status Confirmed
            -- lama dianggap sama dengan Approved.
            --
            -- Query ini tidak ikut diperbarui waktu itu, jadi menyaring nama
            -- status yang tidak pernah lagi dihasilkan: daftar barang siap
            -- kirim SELALU kosong untuk dokumen yang dibuat lewat alur baru
            -- (dilaporkan user 2026-09-30). Keduanya diterima supaya baris
            -- lama yang masih berstatus 'Confirmed' tetap terbaca.
            WHERE frh.status IN ('Approved', 'Confirmed')
              AND sc.customer_id = ?
            
            GROUP BY sc.id, fg.id, c.nama_customer
            HAVING current_stock > 0.001
            ORDER BY sc.sc_number, fg.kode_barang
        ";
        
        return $this->db->query($sql, [$customer_id])->result();
    }
}
