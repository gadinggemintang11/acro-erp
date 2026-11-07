<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Asset Register — satu baris per UNIT FISIK aset.
 *
 * Beli 5 laptop = 1 baris po_details, tapi 5 baris di sini. Alasannya tiap
 * unit punya nasib sendiri: disusutkan terpisah, bisa pindah departemen,
 * dan dilepas pada waktu berbeda.
 *
 * Register ini juga titik masuk aset ke pembukuan. Kolom `journal_header_id`
 * mengikuti pola PRF (lihat db/migration_prf_journal_link.sql): NULL berarti
 * belum dijurnal dan siap ditarik Finance lewat Jurnal Umum.
 */
class Asset_register_model extends CI_Model {

    private $table = 'asset_register';

    /**
     * Nomor aset unik: AST-YYYY-NNNN, sequence per tahun perolehan.
     */
    public function generate_no_asset($year = null) {
        $year = $year ?: date('Y');
        $prefix = 'AST-' . $year . '-';
        $offset = strlen($prefix) + 1;

        $row = $this->db->query(
            "SELECT MAX(CAST(SUBSTRING(no_asset, ?) AS UNSIGNED)) AS max_seq
             FROM {$this->table}
             WHERE no_asset LIKE ?",
            [$offset, $prefix . '%']
        )->row();

        $next = ($row && $row->max_seq) ? ((int)$row->max_seq + 1) : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Baris PO Asset beserta sisa unit yang belum diterima.
     *
     * "Sudah diterima" = jumlah BARIS asset_register untuk po_detail itu,
     * bukan SUM(qty) -- karena satu baris register selalu mewakili satu unit.
     */
    public function get_receivable_items($po_header_id) {
        $this->db->reset_query();
        $received_subquery = $this->db
            ->select('po_detail_id, COUNT(*) as total_received')
            ->from($this->table)
            ->where('po_header_id', $po_header_id)
            ->group_by('po_detail_id')
            ->get_compiled_select();
        $this->db->reset_query();

        $select = "
            d.id as detail_id, d.item_id, d.qty as ordered_qty,
            d.local_price as unit_price, d.description,
            IFNULL(received.total_received, 0) as received_qty,
            GREATEST(0, d.qty - IFNULL(received.total_received, 0)) as remaining_qty,
            a.kode_asset as item_code, a.nama_asset as item_name,
            COALESCE(a.satuan, 'UNIT') as item_unit,
            a.umur_manfaat_bulan, a.metode_penyusutan, a.nilai_residu_persen,
            a.coa_asset_id, a.coa_akum_id, a.coa_beban_id
        ";
        $this->db->select($select, FALSE);
        $this->db->from('po_details d');
        $this->db->join('assets a', 'd.item_id = a.id', 'left');
        $this->db->join("({$received_subquery}) received", 'd.id = received.po_detail_id', 'left');
        $this->db->where('d.po_header_id', $po_header_id);
        $this->db->where('d.item_type', 'asset');
        $this->db->order_by('d.id', 'ASC');

        return $this->db->get()->result();
    }

    /**
     * Catat penerimaan aset dari PO: 1 unit = 1 baris register.
     *
     * @param array $items  tiap elemen: detail_id, qty, tanggal_perolehan,
     *                      department, lokasi, pic
     * @return array ['success' => bool, 'message' => string, 'created' => int]
     */
    public function receive_from_po($po_header_id, $items, $user_id = null) {
        $this->load->model('Po_model');

        $header = $this->Po_model->get_po_header_by_id($po_header_id);
        if (!$header || $header->type !== 'asset') {
            return ['success' => false, 'message' => 'PO tidak ditemukan atau bukan PO Asset.'];
        }
        if ($header->status !== 'Confirmed') {
            return ['success' => false, 'message' => 'Hanya PO berstatus Confirmed yang bisa diterima asetnya.'];
        }

        // Sisa per detail diambil ulang dari DB, bukan dari input form, supaya
        // dua orang yang menerima bersamaan tidak bisa melebihi qty dipesan.
        $available = [];
        foreach ($this->get_receivable_items($po_header_id) as $it) {
            $available[$it->detail_id] = $it;
        }

        $to_create = [];
        foreach ($items as $in) {
            $detail_id = (int)($in['detail_id'] ?? 0);
            $qty       = (int)($in['qty'] ?? 0);
            if ($qty <= 0 || !isset($available[$detail_id])) continue;

            $src = $available[$detail_id];
            if ($qty > (int)$src->remaining_qty) {
                return [
                    'success' => false,
                    'message' => "Qty terima untuk {$src->item_name} ({$qty}) melebihi sisa yang belum diterima ({$src->remaining_qty}).",
                ];
            }
            $to_create[] = ['src' => $src, 'qty' => $qty, 'in' => $in];
        }

        if (empty($to_create)) {
            return ['success' => false, 'message' => 'Tidak ada unit yang dipilih untuk diterima.'];
        }

        $this->db->trans_begin();

        $created = 0;
        foreach ($to_create as $row) {
            $src  = $row['src'];
            $in   = $row['in'];
            $unit_price = (float)$src->unit_price;

            $tanggal = !empty($in['tanggal_perolehan']) ? $in['tanggal_perolehan'] : date('Y-m-d');

            // Penyusutan lazimnya dimulai bulan berikutnya setelah perolehan.
            $mulai = date('Y-m-01', strtotime($tanggal . ' first day of next month'));

            $residu = round($unit_price * ((float)$src->nilai_residu_persen / 100), 2);

            for ($i = 0; $i < $row['qty']; $i++) {
                $data = [
                    'no_asset'             => $this->generate_no_asset(date('Y', strtotime($tanggal))),
                    'asset_id'             => $src->item_id,
                    'po_header_id'         => $po_header_id,
                    'po_detail_id'         => $src->detail_id,
                    'po_number'            => $header->po_number,
                    'supplier_id'          => $header->supplier_id,
                    'tanggal_perolehan'    => $tanggal,
                    'nilai_perolehan'      => $unit_price,
                    'department'           => $in['department'] ?: ($header->department ?? null),
                    'lokasi'               => $in['lokasi'] ?: null,
                    'pic'                  => $in['pic'] ?: null,
                    'umur_manfaat_bulan'   => (int)$src->umur_manfaat_bulan ?: 48,
                    'metode_penyusutan'    => $src->metode_penyusutan ?: 'GARIS_LURUS',
                    'nilai_residu'         => $residu,
                    'akumulasi_penyusutan' => 0,
                    'nilai_buku'           => $unit_price,
                    'mulai_penyusutan'     => $mulai,
                    'status'               => 'Aktif',
                    'created_by'           => $user_id,
                ];

                if (!$this->db->insert($this->table, $data)) {
                    $this->db->trans_rollback();
                    return ['success' => false, 'message' => 'Gagal menyimpan unit aset ke register.'];
                }
                $created++;
            }
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return ['success' => false, 'message' => 'Transaksi gagal, penerimaan dibatalkan.'];
        }

        $this->db->trans_commit();

        return [
            'success' => true,
            'created' => $created,
            'message' => $created . ' unit aset berhasil dicatat ke Asset Register.',
        ];
    }

    /**
     * Unit yang sudah tercatat dari sebuah PO — untuk ditampilkan di detail PO.
     */
    public function get_by_po($po_header_id) {
        $this->db->select('ar.*, a.nama_asset, a.kode_asset');
        $this->db->from($this->table . ' ar');
        $this->db->join('assets a', 'a.id = ar.asset_id', 'left');
        $this->db->where('ar.po_header_id', $po_header_id);
        $this->db->order_by('ar.no_asset', 'ASC');
        return $this->db->get()->result();
    }
}
