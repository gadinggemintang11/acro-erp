<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Consumable_out_model extends CI_Model {

    private $table_header  = 'consumable_out_header';
    private $table_details = 'consumable_out_details';
    private $table_items   = 'inventories';

    /**
     * Item type yang bisa dikeluarkan lewat Consumable Out.
     * 'inventory' = tabel inventories, 'machine' = tabel machines (Stock Machine).
     */
    const ITEM_TYPES = ['inventory', 'machine'];

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('Stock_ledger_model');
    }

    /**
     * Form mengirim item sebagai string gabungan "type-id" (mis. "machine-15")
     * karena id inventories & machines bisa bentrok. Nilai lama yang cuma angka
     * tetap dianggap 'inventory' (backward compatible).
     *
     * @return array{item_id:int, item_type:string}
     */
    public function resolve_item_ref($item) {
        $raw = trim((string) ($item['item_id'] ?? ''));

        if (strpos($raw, '-') !== false) {
            list($type, $id) = explode('-', $raw, 2);
            $type = trim($type);
            $id   = (int) $id;
            if (in_array($type, self::ITEM_TYPES, true) && $id > 0) {
                return ['item_id' => $id, 'item_type' => $type];
            }
        }

        $item_type = 'inventory';
        if (!empty($item['item_type']) && in_array($item['item_type'], self::ITEM_TYPES, true)) {
            $item_type = $item['item_type'];
        }
        return ['item_id' => (int) $raw, 'item_type' => $item_type];
    }

    // -------------------------------------------------------
    // Ambil semua dokumen dengan filter
    // -------------------------------------------------------
    public function get_all($filters = []) {
        $this->db->select('h.*, u.fullname as created_by_name, u2.fullname as confirmed_by_name');
        $this->db->from($this->table_header . ' h');
        $this->db->join('users u',  'h.created_by   = u.id',  'left');
        $this->db->join('users u2', 'h.confirmed_by = u2.id', 'left');

        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('h.doc_number',  $term);
            $this->db->or_like('h.department', $term);
            $this->db->or_like('h.recipient',  $term);
            $this->db->group_end();
        }
        if (!empty($filters['status'])) {
            $this->db->where('h.status', $filters['status']);
        }
        if (!empty($filters['category'])) {
            if (is_array($filters['category'])) {
                $this->db->where_in('h.category', $filters['category']);
            } else {
                $this->db->where('h.category', $filters['category']);
            }
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('h.issuance_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('h.issuance_date <=', $filters['date_to']);
        }

        $this->db->order_by('h.id', 'DESC');
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    // -------------------------------------------------------
    // Ambil satu header
    // -------------------------------------------------------
    public function get_header($id) {
        $this->db->select('h.*, u.fullname as created_by_name, u.signature_path as created_by_signature, u2.fullname as confirmed_by_name, u2.signature_path as confirmed_by_signature');
        $this->db->from($this->table_header . ' h');
        $this->db->join('users u',  'h.created_by   = u.id',  'left');
        $this->db->join('users u2', 'h.confirmed_by = u2.id', 'left');
        $this->db->where('h.id', $id);
        $query = $this->db->get();
        return $query ? $query->row() : null;
    }

    /**
     * List IRF (consumable_request) yang sudah Approved & masih ada qty
     * yang belum di-issue (outstanding > 0). Untuk ditampilkan di
     * Consumable Out index sebagai antrian "siap dikeluarkan".
     *
     * "pending" = dokumen pengeluaran yang masih Created (belum Confirmed).
     * Dulu ditulis `status != 'Confirmed'`, jadi dokumen yang DIBATALKAN ikut
     * mengurangi sisa -> IRF bisa hilang dari antrian walau barangnya belum
     * pernah keluar (koreksi 2026-09-15).
     */
    public function get_pending_irfs($category = null) {
        $category_filter = '';
        if (!empty($category)) {
            $cats = is_array($category) ? $category : [$category];
            $cats_escaped = array_map([$this->db, 'escape'], $cats);
            $category_filter = ' AND cr_h.category IN (' . implode(',', $cats_escaped) . ')';
        }
        $sql = "
            SELECT
                cr_h.id, cr_h.request_number, cr_h.request_date, cr_h.department,
                cr_h.category, cr_h.requestor_name, cr_h.purpose,
                cr_h.hod_approved_at,
                u.fullname AS approved_by_name,
                SUM(cr_d.qty_requested) AS total_requested,
                COALESCE(SUM(issued.qty_issued_sum), 0) AS total_issued,
                COALESCE(SUM(pending.qty_pending_sum), 0) AS total_pending,
                (SUM(cr_d.qty_requested) - COALESCE(SUM(issued.qty_issued_sum), 0) - COALESCE(SUM(pending.qty_pending_sum), 0)) AS total_outstanding
            FROM consumable_request_header cr_h
            INNER JOIN consumable_request_details cr_d ON cr_d.header_id = cr_h.id
            LEFT JOIN users u ON u.id = cr_h.hod_approved_by
            LEFT JOIN (
                SELECT co_h.request_id, co_d.item_id, co_d.item_type, SUM(co_d.qty_issued) AS qty_issued_sum
                FROM consumable_out_details co_d
                INNER JOIN consumable_out_header co_h ON co_h.id = co_d.header_id
                WHERE co_h.status = 'Confirmed' AND co_h.request_id IS NOT NULL
                GROUP BY co_h.request_id, co_d.item_id, co_d.item_type
            ) issued ON issued.request_id = cr_h.id AND issued.item_id = cr_d.item_id AND issued.item_type = cr_d.item_type
            LEFT JOIN (
                SELECT co_h.request_id, co_d.item_id, co_d.item_type, SUM(co_d.qty_issued) AS qty_pending_sum
                FROM consumable_out_details co_d
                INNER JOIN consumable_out_header co_h ON co_h.id = co_d.header_id
                WHERE co_h.status = 'Created' AND co_h.request_id IS NOT NULL
                GROUP BY co_h.request_id, co_d.item_id, co_d.item_type
            ) pending ON pending.request_id = cr_h.id AND pending.item_id = cr_d.item_id AND pending.item_type = cr_d.item_type
            WHERE cr_h.status = 'Approved'
            $category_filter
            GROUP BY cr_h.id
            HAVING total_outstanding > 0
            ORDER BY cr_h.request_date ASC, cr_h.id ASC
        ";
        $query = $this->db->query($sql);
        return $query ? $query->result() : [];
    }

    // -------------------------------------------------------
    // Ambil semua detail baris
    // -------------------------------------------------------
    public function get_details($header_id) {
        $query = $this->db->get_where($this->table_details, ['header_id' => $header_id]);
        return $query ? $query->result() : [];
    }

    // -------------------------------------------------------
    // Ambil daftar item inventaris aktif
    // -------------------------------------------------------
    public function get_inventory_items() {
        $this->db->select('id, kode_barang, nama_barang, satuan, kategori');
        $this->db->from($this->table_items);
        $this->db->where('status', 'Active');
        $this->db->order_by('nama_barang', 'ASC');
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    // -------------------------------------------------------
    // Ambil satu item master sesuai tipenya
    // (inventory = inventories, machine = machines)
    // -------------------------------------------------------
    public function get_item_master($item_id, $item_type = 'inventory') {
        $item_id = (int) $item_id;
        if ($item_type === 'machine') {
            $sql = "SELECT id, kode_mesin AS kode_barang, nama_mesin AS nama_barang,
                           COALESCE(satuan, 'UNIT') AS satuan, kategori,
                           'machine' AS item_type
                    FROM machines WHERE id = ?";
        } else {
            $sql = "SELECT id, kode_barang, nama_barang, satuan, kategori,
                           'inventory' AS item_type
                    FROM inventories WHERE id = ?";
        }
        $query = $this->db->query($sql, [$item_id]);
        return $query ? $query->row() : null;
    }

    /** @deprecated pakai get_item_master() — dipertahankan untuk kode lama. */
    public function get_inventory_item($item_id) {
        return $this->get_item_master($item_id, 'inventory');
    }

    // -------------------------------------------------------
    // Ambil satu item dengan stock dari stock_ledger
    // -------------------------------------------------------
    public function get_inventory_item_with_stock($item_id, $item_type = 'inventory') {
        $item_id = (int) $item_id;
        if ($item_type === 'machine') {
            $sql = "SELECT m.id, m.kode_mesin AS kode_barang, m.nama_mesin AS nama_barang,
                           COALESCE(m.satuan, 'UNIT') AS satuan, m.kategori,
                           'machine' AS item_type,
                           COALESCE((SELECT balance FROM stock_ledger
                                     WHERE item_id = m.id AND item_type = 'machine'
                                     ORDER BY id DESC LIMIT 1), 0) as stock
                    FROM machines m
                    WHERE m.id = ?";
        } else {
            $sql = "SELECT i.id, i.kode_barang, i.nama_barang, i.satuan, i.kategori,
                           'inventory' AS item_type,
                           COALESCE((SELECT balance FROM stock_ledger
                                     WHERE item_id = i.id AND item_type = 'inventory'
                                     ORDER BY id DESC LIMIT 1), 0) as stock
                    FROM inventories i
                    WHERE i.id = ?";
        }
        $query = $this->db->query($sql, [$item_id]);
        return $query ? $query->row() : null;
    }

    // -------------------------------------------------------
    // Ambil semua item (inventories + machines) dengan stock terbaru.
    // `item_key` ("type-id") dipakai sebagai value <option> di form karena
    // id kedua master bisa bentrok.
    // -------------------------------------------------------
    /**
     * @param string $category 'General'/'Produksi' — stok yang ditampilkan
     *        adalah saldo bucket kategori ini SAJA (gudang fisik terpisah),
     *        bukan gabungan General+Produksi.
     */
    public function get_inventory_items_with_stock($category = 'General') {
        $cat = $this->db->escape($category);
        $sql = "
            SELECT * FROM (
                SELECT i.id,
                       CONCAT('inventory-', i.id) AS item_key,
                       'inventory' AS item_type,
                       i.kode_barang, i.nama_barang, i.satuan, i.kategori,
                       COALESCE((SELECT balance FROM stock_ledger
                                 WHERE item_id = i.id AND item_type = 'inventory' AND category = $cat
                                 ORDER BY id DESC LIMIT 1), 0) as stock
                FROM inventories i
                WHERE i.status = 'Active'

                UNION ALL

                SELECT m.id,
                       CONCAT('machine-', m.id) AS item_key,
                       'machine' AS item_type,
                       m.kode_mesin AS kode_barang, m.nama_mesin AS nama_barang,
                       COALESCE(m.satuan, 'UNIT') AS satuan, m.kategori,
                       COALESCE((SELECT balance FROM stock_ledger
                                 WHERE item_id = m.id AND item_type = 'machine' AND category = $cat
                                 ORDER BY id DESC LIMIT 1), 0) as stock
                FROM machines m
                WHERE m.status IS NULL OR m.status <> 'Nonaktif'
            ) x
            ORDER BY x.item_type ASC, x.nama_barang ASC";
        $query = $this->db->query($sql);
        return $query ? $query->result() : [];
    }

    // -------------------------------------------------------
    // Generate nomor dokumen: CO/YYYYMM/0001
    // -------------------------------------------------------
    public function generate_doc_number() {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('goods_issue', 'consumable_out_header', 'doc_number');
    }

    // -------------------------------------------------------
    // Validasi stock tersedia untuk items yang dikeluarkan
    // -------------------------------------------------------
    /**
     * Cek ketersediaan stok SEMUA item sekaligus.
     *
     * KOREKSI 2026-09-10 (user: "misal ada 2 list item dengan tidak ada
     * stocknya semua, kenapa yang outstanding hanya 1?"). Sebelumnya fungsi
     * ini `return` begitu ketemu item pertama yang stoknya kurang, jadi:
     *   - pesan galat cuma menyebut 1 dari sekian item yang bermasalah, dan
     *   - tombol "Buat PR" di popup ikut menunjuk 1 item itu saja, sehingga
     *     halaman IRF Outstanding-nya ter-filter ke satu barang dan yang lain
     *     seolah hilang.
     * Sekarang seluruh item diperiksa dulu, baru dilaporkan sekaligus.
     *
     * Item yang TIDAK ADA di master tetap gagal cepat — itu galat data,
     * bukan soal stok, dan tidak ada gunanya dilanjutkan.
     */
    public function validate_stock_availability($items, $category = 'General') {
        $short = [];   // daftar item yang stoknya kurang

        foreach ($items as $item) {
            $ref        = $this->resolve_item_ref($item);
            $item_id    = $ref['item_id'];
            $item_type  = $ref['item_type'];
            $qty_needed = (float)$item['qty'];
            $inv = $this->get_item_master($item_id, $item_type);

            if (!$inv) {
                return [
                    'valid' => false,
                    'message' => 'Item tidak ditemukan: ' . ($item['item_name'] ?? 'Unknown')
                ];
            }

            // Ambil stock terbaru dari stock_ledger — dipecah per kategori
            // (gudang General & Produksi fisik terpisah, beda bucket beda angka).
            $current_stock = $this->Stock_ledger_model->get_last_balance($item_id, $item_type, $category);

            if ($current_stock < $qty_needed) {
                $short[] = [
                    'name'      => $inv->nama_barang,
                    'available' => $current_stock,
                    'needed'    => $qty_needed,
                ];
            }
        }

        if (!empty($short)) {
            $parts = [];
            foreach ($short as $x) {
                $parts[] = $x['name'] . ' (tersedia ' . (float) $x['available'] . ', diminta ' . (float) $x['needed'] . ')';
            }
            $message = count($short) === 1
                ? 'Stok ' . $category . ' tidak cukup untuk item: ' . $parts[0] . '.'
                : 'Stok ' . $category . ' tidak cukup untuk ' . count($short) . ' item: ' . implode('; ', $parts) . '.';

            // `short`/`short_items` dipakai controller untuk memunculkan tombol
            // "Buat PR" di popup galat (lihat Consumable_out::store()).
            return [
                'valid'       => false,
                'short'       => true,
                'short_items' => array_column($short, 'name'),
                'message'     => $message,
            ];
        }

        return ['valid' => true];
    }

    // -------------------------------------------------------
    // Buat dokumen baru — status: Created (no stock impact)
    // -------------------------------------------------------
    public function create($header_data, $items) {
        // Validasi stock availability dulu (per kategori dokumen ini)
        $validation = $this->validate_stock_availability($items, $header_data['category'] ?? 'General');
        if (!$validation['valid']) {
            return [
                'success'     => false,
                'message'     => $validation['message'],
                'short'       => !empty($validation['short']),
                'short_items' => $validation['short_items'] ?? [],
            ];
        }

        $this->db->trans_start();

        $this->db->insert($this->table_header, $header_data);
        $header_id = $this->db->insert_id();

        if (!$header_id) {
            $this->db->trans_rollback();
            return ['success' => false, 'message' => 'Gagal menyimpan header.'];
        }

        foreach ($items as $item) {
            $ref       = $this->resolve_item_ref($item);
            $item_id   = $ref['item_id'];
            $item_type = $ref['item_type'];
            $qty       = (float)$item['qty'];
            $inv       = $this->get_item_master($item_id, $item_type);

            $detail = [
                'header_id'  => $header_id,
                'item_id'    => $item_id,
                'item_type'  => $item_type,
                'item_code'  => $inv ? $inv->kode_barang  : ($item['item_code']  ?? ''),
                'item_name'  => $inv ? $inv->nama_barang  : ($item['item_name']  ?? ''),
                'item_unit'  => $inv ? $inv->satuan       : ($item['item_unit']  ?? ''),
                'qty_issued' => $qty,
                'notes'      => $item['notes'] ?? null,
            ];
            $this->db->insert($this->table_details, $detail);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Terjadi kesalahan database.'];
        }

        return [
            'success'    => true,
            'id'         => $header_id,
            'doc_number' => $header_data['doc_number'],
        ];
    }

    // -------------------------------------------------------
    // CONFIRM — proses stock (FIFO) dan update status → Confirmed
    // -------------------------------------------------------
    public function process_confirm($header_id, $user_id) {
        $this->db->trans_start();

        $co_header = $this->db->select('request_id, category')
            ->get_where($this->table_header, ['id' => $header_id])->row();
        // Saldo inventory/machine benar-benar dipecah per kategori (gudang fisik
        // terpisah) — dokumen ini sudah punya category sendiri di header-nya.
        $category = $co_header->category ?? 'General';

        $details = $this->get_details($header_id);
        if (empty($details)) {
            $this->db->trans_rollback();
            return ['success' => false, 'message' => 'Tidak ada item untuk dikonfirmasi.'];
        }

        foreach ($details as $d) {
            $item_id      = (int)$d->item_id;
            $item_type    = !empty($d->item_type) ? $d->item_type : 'inventory';
            $qty_needed   = (float)$d->qty_issued;

            if ($qty_needed <= 0) continue;

            // Ambil batch FIFO dari stock_on_hand
            $available_batches = $this->db
                ->select('id, available_qty, unit_value')
                ->from('stock_on_hand')
                ->where('item_id',   $item_id)
                ->where('item_type', $item_type)
                ->where('available_qty >', 0)
                ->order_by('id', 'ASC') // FIFO: batch lama dulu
                ->get()->result();

            if (!empty($available_batches)) {
                // Ada batch: FIFO
                foreach ($available_batches as $batch) {
                    if ($qty_needed <= 0) break;

                    $batch_available = (float)$batch->available_qty;
                    $batch_cost      = (float)$batch->unit_value;
                    $qty_to_take     = min($batch_available, $qty_needed);
                    $new_batch_qty   = $batch_available - $qty_to_take;

                    // Kurangi available_qty di stock_on_hand
                    $this->db->where('id', $batch->id)
                             ->update('stock_on_hand', ['available_qty' => $new_batch_qty]);

                    // Insert ke stock_ledger
                    $last_balance = $this->Stock_ledger_model->get_last_balance($item_id, $item_type, $category);
                    $new_balance  = $last_balance - $qty_to_take;

                    $ledger_data = [
                        'item_id'          => $item_id,
                        'item_type'        => $item_type,
                        'category'         => $category,
                        'transaction_date' => date('Y-m-d H:i:s'),
                        'transaction_type' => 'OUT_CONSUMABLE',
                        'transaction_id'   => $header_id,
                        'source_doc_type'  => 'CO',
                        'source_doc_id'    => $header_id,
                        'qty_in'           => 0,
                        'qty_out'          => $qty_to_take,
                        'unit_value'       => $batch_cost,
                        'balance'          => $new_balance,
                        'stock_on_hand_id' => $batch->id,
                    ];

                    if (!$this->Stock_ledger_model->insert($ledger_data)) {
                        $this->db->trans_rollback();
                        return ['success' => false, 'message' => 'Gagal insert stock ledger untuk item: ' . $d->item_name];
                    }

                    $qty_needed -= $qty_to_take;
                }

                if ($qty_needed > 0) {
                    $this->db->trans_rollback();
                    return ['success' => false, 'short' => true, 'short_items' => [$d->item_name],
                            'message' => 'Stok tidak cukup untuk item: ' . $d->item_name . '. Kekurangan: ' . $qty_needed];
                }
            } else {
                // Tidak ada batch di stock_on_hand - check balance di stock_ledger
                $last_balance = $this->Stock_ledger_model->get_last_balance($item_id, $item_type, $category);
                $new_balance  = $last_balance - $qty_needed;

                // Validasi: stok tidak boleh minus
                if ($new_balance < 0) {
                    $this->db->trans_rollback();
                    return ['success' => false, 'short' => true, 'short_items' => [$d->item_name],
                            'message' => 'Stok ' . $category . ' tidak cukup untuk item: ' . $d->item_name . '. Stok tersedia: ' . $last_balance . ', Diminta: ' . $qty_needed];
                }

                $ledger_data = [
                    'item_id'          => $item_id,
                    'item_type'        => $item_type,
                    'category'         => $category,
                    'transaction_date' => date('Y-m-d H:i:s'),
                    'transaction_type' => 'OUT_CONSUMABLE',
                    'transaction_id'   => $header_id,
                    'source_doc_type'  => 'CO',
                    'source_doc_id'    => $header_id,
                    'qty_in'           => 0,
                    'qty_out'          => $qty_needed,
                    'unit_value'       => 0,
                    'balance'          => $new_balance,
                ];

                if (!$this->Stock_ledger_model->insert($ledger_data)) {
                    $this->db->trans_rollback();
                    return ['success' => false, 'message' => 'Gagal insert stock ledger untuk item: ' . $d->item_name];
                }
            }
        }

        // Update header status → Confirmed
        $this->db->where('id', $header_id)->update($this->table_header, [
            'status'       => 'Confirmed',
            'confirmed_by' => $user_id,
            'confirmed_at' => date('Y-m-d H:i:s'),
        ]);

        // Sync status IRF: jika seluruh item sudah ter-issue penuh → 'Released'
        if ($co_header && !empty($co_header->request_id)) {
            $this->_sync_request_status($co_header->request_id);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Terjadi kesalahan database saat konfirmasi.'];
        }

        return ['success' => true];
    }

    // -------------------------------------------------------
    // CANCEL — reverse stock dan update status → Cancelled
    // -------------------------------------------------------
    public function process_cancel($header_id, $user_id) {
        $this->db->trans_start();

        $co_header = $this->db->select('request_id')
            ->get_where($this->table_header, ['id' => $header_id])->row();

        // Ambil semua entri ledger yang terkait dokumen ini
        $ledger_entries = $this->db->get_where('stock_ledger', [
            'transaction_type' => 'OUT_CONSUMABLE',
            'transaction_id'   => $header_id,
        ])->result();

        if (!empty($ledger_entries)) {
            foreach ($ledger_entries as $entry) {
                $qty_to_return   = (float)$entry->qty_out;
                $stock_on_hand_id = $entry->stock_on_hand_id ?? null;

                // Kembalikan ke batch stock_on_hand jika ada
                if ($stock_on_hand_id) {
                    $this->db->where('id', $stock_on_hand_id)
                             ->set('available_qty', "available_qty + $qty_to_return", FALSE)
                             ->update('stock_on_hand');
                }

                // Insert reversal ke stock_ledger — pakai category yang sama persis
                // dengan entry OUT aslinya, supaya balik ke bucket yang benar.
                $last_balance = $this->Stock_ledger_model->get_last_balance($entry->item_id, $entry->item_type, $entry->category ?? null);
                $new_balance  = $last_balance + $qty_to_return;

                $reversal = [
                    'item_id'          => $entry->item_id,
                    'item_type'        => $entry->item_type,
                    'category'         => $entry->category ?? null,
                    'transaction_date' => date('Y-m-d H:i:s'),
                    'transaction_type' => 'IN_RETURN_CONSUMABLE',
                    'transaction_id'   => $header_id,
                    'source_doc_type'  => 'CO_CANCEL',
                    'source_doc_id'    => $header_id,
                    'qty_in'           => $qty_to_return,
                    'qty_out'          => 0,
                    'unit_value'       => $entry->unit_value,
                    'balance'          => $new_balance,
                    'stock_on_hand_id' => $stock_on_hand_id,
                ];

                $this->Stock_ledger_model->insert($reversal);
            }
        }

        // Update header status → Cancelled
        $this->db->where('id', $header_id)->update($this->table_header, [
            'status'    => 'Cancelled',
            'cancelled_by' => $user_id,
            'cancelled_at' => date('Y-m-d H:i:s'),
        ]);

        // Sync status IRF: pembatalan CO Confirmed bisa memunculkan kembali
        // outstanding, sehingga IRF 'Released' harus balik ke 'Approved'.
        if ($co_header && !empty($co_header->request_id)) {
            $this->_sync_request_status($co_header->request_id);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Terjadi kesalahan database saat pembatalan.'];
        }

        return ['success' => true];
    }

    // -------------------------------------------------------
    // Hapus dokumen (hanya Created)
    // -------------------------------------------------------
    public function delete($id) {
        $this->db->where('id', $id);
        return $this->db->delete($this->table_header);
    }

    /**
     * Sinkronisasi status IRF berdasarkan total issued (CO Confirmed saja):
     * - 'Approved' + semua item ter-issue penuh  → 'Released'
     * - 'Released' + outstanding muncul lagi (CO dibatalkan) → 'Approved'
     * Status lain (Draft/Submitted/Rejected/Cancelled) tidak pernah disentuh.
     */
    private function _sync_request_status($request_id) {
        $row = $this->db->query("
            SELECT cr_h.status,
                SUM(CASE WHEN (cr_d.qty_requested - COALESCE(issued.qty_issued_sum, 0)) > 0 THEN 1 ELSE 0 END) AS open_items
            FROM consumable_request_header cr_h
            INNER JOIN consumable_request_details cr_d ON cr_d.header_id = cr_h.id
            LEFT JOIN (
                SELECT co_h.request_id, co_d.item_id, co_d.item_type, SUM(co_d.qty_issued) AS qty_issued_sum
                FROM consumable_out_details co_d
                INNER JOIN consumable_out_header co_h ON co_h.id = co_d.header_id
                WHERE co_h.status = 'Confirmed' AND co_h.request_id IS NOT NULL
                GROUP BY co_h.request_id, co_d.item_id, co_d.item_type
            ) issued ON issued.request_id = cr_d.header_id AND issued.item_id = cr_d.item_id AND issued.item_type = cr_d.item_type
            WHERE cr_h.id = ?
            GROUP BY cr_h.id, cr_h.status
        ", [(int)$request_id])->row();

        if (!$row) return;

        if ($row->status === 'Approved' && (int)$row->open_items === 0) {
            $this->db->where('id', $request_id)
                     ->update('consumable_request_header', ['status' => 'Released']);
        } elseif ($row->status === 'Released' && (int)$row->open_items > 0) {
            $this->db->where('id', $request_id)
                     ->update('consumable_request_header', ['status' => 'Approved']);
        }
    }

    // ============================================================
    // ATTACHMENTS — "Bukti Pengeluaran" (ERP Revision Notes 2026-09-02,
    // Fase 2 poin 8, Item Release). Pola sama persis dgn erf_attachments.
    // ============================================================

    public function get_attachments($header_id) {
        return $this->db->where('consumable_out_header_id', $header_id)
            ->order_by('id', 'DESC')->get('consumable_out_attachments')->result();
    }

    public function get_attachment($id) {
        return $this->db->where('id', $id)->get('consumable_out_attachments')->row();
    }

    /**
     * Lampiran "Bukti Pengeluaran" dari SEMUA dokumen pengeluaran milik satu IRF.
     *
     * Dipakai halaman detail IRF supaya requestor ikut melihat bukti yang
     * diupload gudang (user 2026-09-14: "kan itu sama"). Sengaja TIDAK
     * menyalin baris ke consumable_request_attachments: satu IRF bisa punya
     * beberapa dokumen pengeluaran (issuance bertahap -- di data produksi ada
     * IRF dengan 5 dokumen), jadi menyalin akan menghasilkan duplikat yang
     * tidak jelas miliknya siapa. Nomor dokumennya ikut dibawa supaya jelas
     * bukti itu berasal dari pengeluaran yang mana.
     */
    /**
     * Header dokumen pengeluaran milik satu IRF. Dipakai halaman detail IRF
     * untuk menampilkan tombol Export PDF per dokumen (user 2026-09-14) --
     * satu IRF bisa punya beberapa pengeluaran kalau barangnya dikeluarkan
     * bertahap, jadi tombolnya per dokumen, bukan satu untuk semuanya.
     */
    public function get_headers_by_request($request_id) {
        $request_id = (int) $request_id;
        if ($request_id <= 0) return [];
        return $this->db->select('id, doc_number, issuance_date, status, category, recipient')
            ->from('consumable_out_header')
            ->where('request_id', $request_id)
            ->order_by('id', 'ASC')
            ->get()->result();
    }

    public function get_attachments_by_request($request_id) {
        $request_id = (int) $request_id;
        if ($request_id <= 0) return [];
        return $this->db->select('a.*, h.doc_number, h.issuance_date')
            ->from('consumable_out_attachments a')
            ->join('consumable_out_header h', 'h.id = a.consumable_out_header_id', 'inner')
            ->where('h.request_id', $request_id)
            ->order_by('a.id', 'DESC')
            ->get()->result();
    }

    public function add_attachment($data) {
        $this->db->insert('consumable_out_attachments', $data);
        return $this->db->insert_id();
    }

    public function delete_attachment($id) {
        return $this->db->where('id', $id)->delete('consumable_out_attachments');
    }
}
