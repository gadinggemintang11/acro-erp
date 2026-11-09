<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daily Material NG Report.
 *
 * Disederhanakan 2026-11-08 (user Acropoly: "ketika ada NG ... qty nya berapa
 * dan keterangannya gimana, gitu aja"). Rincian 4 kategori (Human / Material /
 * Mechanic / Others), Kode NG, dan format sheet MATERIAL/PIVOT warisan NSI
 * dihapus. Tiap baris NG sekarang cukup: Qty NG + Keterangan.
 *
 * Sumber data (dua titik pencatatan NG material):
 *
 *   1. issuance_qc_results -- material NG saat QC Production Receive
 *      (reject_qty = Qty NG, qc_notes = Keterangan). mr_header tidak menyimpan
 *      work order, jadi kolom Sales Order & Model untuk baris ini kosong.
 *
 *   2. production_scrap_sources -- material yang jadi scrap di Proses Produksi
 *      (source_qty = Qty NG, notes = Keterangan). Punya rantai ke
 *      production_header -> wo_header -> sales_contracts.
 *
 * Kolom ng_* lama di kedua tabel dibiarkan (data historis) tapi tidak dipakai.
 */
class Material_ng_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /** TRUE kalau minimal satu sumber tabelnya ada. */
    public function is_ready() {
        return $this->db->table_exists('production_scrap_sources') || $this->db->table_exists('issuance_qc_results');
    }

    /**
     * Baris laporan, sudah digabung & diurutkan (tanggal, dokumen, part).
     * @param array $f filter: date_from, date_to, ipo, model, part, source
     */
    public function get_rows($f = []) {
        $rows = [];
        if ($this->db->table_exists('issuance_qc_results'))       $rows = array_merge($rows, $this->_qc_rows($f));
        if ($this->db->table_exists('production_scrap_sources'))  $rows = array_merge($rows, $this->_scrap_rows($f));

        usort($rows, function ($a, $b) {
            $c = strcmp((string) $a->ng_date, (string) $b->ng_date);
            if ($c !== 0) return $c;
            $c = strcmp((string) $a->doc_number, (string) $b->doc_number);
            if ($c !== 0) return $c;
            return strcmp((string) $a->part_name, (string) $b->part_name);
        });
        return $rows;
    }

    /** Sumber 1: NG saat QC Production Receive. */
    private function _qc_rows($f) {
        if (!empty($f['source']) && $f['source'] !== 'qc') return [];
        // Jalur ini tidak punya Sales Order / Model -- begitu difilter, dikeluarkan.
        if (!empty($f['ipo']) || !empty($f['model'])) return [];

        $this->db->reset_query();
        $this->db->select("
                DATE(COALESCE(qr.qc_done_at, mi.received_at))                         AS ng_date,
                ''                                                                    AS ipo_no,
                ''                                                                    AS model,
                COALESCE(NULLIF(m.nama_material, ''), COALESCE(sf.nama_barang, ''))   AS part_name,
                COALESCE(NULLIF(m.kode_material, ''), COALESCE(sf.kode_barang, ''))   AS part_number,
                COALESCE(NULLIF(m.satuan, ''), COALESCE(sf.satuan, ''))               AS unit,
                qr.reject_qty                                                         AS qty_ng,
                COALESCE(qr.qc_notes, '')                                             AS remark,
                COALESCE(mi.issuance_number, '')                                      AS doc_number,
                'qc'                                                                  AS source
            ", FALSE)
            ->from('issuance_qc_results qr')
            ->join('mr_issuances mi', 'mi.id = qr.issuance_id', 'left')
            ->join('materials m', "m.id = qr.item_id AND qr.item_type = 'material'", 'left')
            ->join('semifinished_goods sf', "sf.id = qr.item_id AND qr.item_type = 'semifinished'", 'left')
            ->where('qr.reject_qty >', 0);
        $this->_apply_filters($f, 'DATE(COALESCE(qr.qc_done_at, mi.received_at))', null, null);
        return $this->db->get()->result();
    }

    /** Sumber 2: material jadi scrap di Proses Produksi. */
    private function _scrap_rows($f) {
        if (!empty($f['source']) && $f['source'] !== 'scrap') return [];

        $notes = db_has_column('production_scrap_sources', 'notes') ? "COALESCE(pss.notes, '')" : "''";
        $this->db->reset_query();
        $this->db->select("
                ph.production_date                                                    AS ng_date,
                COALESCE(sc.sc_number, '')                                            AS ipo_no,
                COALESCE(fg.nama_barang, '')                                          AS model,
                COALESCE(NULLIF(m.nama_material, ''), COALESCE(sf.nama_barang, ''))   AS part_name,
                COALESCE(NULLIF(m.kode_material, ''), COALESCE(sf.kode_barang, ''))   AS part_number,
                COALESCE(NULLIF(m.satuan, ''), COALESCE(sf.satuan, ''))               AS unit,
                pss.source_qty                                                        AS qty_ng,
                {$notes}                                                              AS remark,
                ph.production_number                                                  AS doc_number,
                'scrap'                                                               AS source
            ", FALSE)
            ->from('production_scrap_sources pss')
            ->join('production_outputs po', 'po.id = pss.production_output_id', 'inner')
            ->join('production_header ph', 'ph.id = po.production_header_id', 'inner')
            ->join('production_inputs pi', 'pi.id = pss.source_input_id', 'inner')
            ->join('wo_header wo', 'wo.id = ph.wo_header_id', 'left')
            ->join('sales_contracts sc', 'sc.id = wo.sales_contract_id', 'left')
            ->join('finished_goods fg', 'fg.id = wo.fg_id', 'left')
            ->join('materials m', "m.id = pi.item_id AND pi.item_type = 'material'", 'left')
            ->join('semifinished_goods sf', "sf.id = pi.item_id AND pi.item_type = 'semifinished'", 'left')
            ->where('pss.source_qty >', 0);
        $this->_apply_filters($f, 'ph.production_date', 'sc.sc_number', 'fg.nama_barang');
        return $this->db->get()->result();
    }

    private function _apply_filters($f, $date_col, $ipo_col, $model_col) {
        if (!empty($f['date_from'])) $this->db->where($date_col . ' >=', $f['date_from']);
        if (!empty($f['date_to']))   $this->db->where($date_col . ' <=', $f['date_to']);
        if (!empty($f['ipo'])   && $ipo_col)   $this->db->like($ipo_col, $f['ipo']);
        if (!empty($f['model']) && $model_col) $this->db->like($model_col, $f['model']);
        if (!empty($f['part'])) {
            $term = $this->db->escape_like_str($f['part']);
            $this->db->where("(m.nama_material LIKE '%{$term}%' OR m.kode_material LIKE '%{$term}%'"
                           . " OR sf.nama_barang LIKE '%{$term}%' OR sf.kode_barang LIKE '%{$term}%')", null, FALSE);
        }
    }

    /** Rekap per part (part + satuan): total Qty NG & jumlah kejadian. */
    public function recap($rows) {
        $out = [];
        foreach ($rows as $r) {
            $key = $r->part_number . '||' . $r->part_name . '||' . $r->unit;
            if (!isset($out[$key])) {
                $out[$key] = (object) [
                    'part_name' => $r->part_name, 'part_number' => $r->part_number, 'unit' => $r->unit,
                    'qty_qc' => 0.0, 'qty_scrap' => 0.0, 'qty_ng' => 0.0, 'events' => 0,
                ];
            }
            $o = $out[$key];
            $o->qty_ng += (float) $r->qty_ng;
            if ($r->source === 'qc') $o->qty_qc += (float) $r->qty_ng; else $o->qty_scrap += (float) $r->qty_ng;
            $o->events++;
        }
        $out = array_values($out);
        usort($out, function ($a, $b) { return $b->qty_ng <=> $a->qty_ng; });
        return $out;
    }

    /** Ringkasan untuk kartu atas & baris total. */
    public function totals($rows) {
        $t = ['qty_ng' => 0.0, 'qty_qc' => 0.0, 'qty_scrap' => 0.0, 'events' => count($rows), 'parts' => 0];
        $parts = [];
        foreach ($rows as $r) {
            $t['qty_ng'] += (float) $r->qty_ng;
            if ($r->source === 'qc') $t['qty_qc'] += (float) $r->qty_ng; else $t['qty_scrap'] += (float) $r->qty_ng;
            $parts[$r->part_number . '|' . $r->part_name] = true;
        }
        $t['parts'] = count($parts);
        return $t;
    }

    /** Label sumber. */
    public static function source_label($source) {
        return $source === 'qc'
            ? lang_line('mng_source_qc', 'QC Penerimaan')
            : lang_line('mng_source_scrap', 'Proses Produksi');
    }

    /**
     * Workbook export: sheet "NG Material" (rincian) + "Rekap per Part".
     * Di model supaya bisa diuji tanpa melewati check_login/check_access.
     */
    public function build_spreadsheet($rows, $recap, $totals) {
        require_once FCPATH . 'vendor/autoload.php';
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $sh = $ss->getActiveSheet();
        $sh->setTitle('NG Material');
        $sh->fromArray(['Tanggal', 'Sumber', 'No. Dokumen', 'Sales Order', 'Model', 'Part Name', 'Part Number', 'Qty NG', 'Satuan', 'Keterangan'], null, 'A1', true);
        $sh->getStyle('A1:J1')->getFont()->setBold(true);
        $r = 2;
        foreach ($rows as $x) {
            $sh->fromArray([
                $x->ng_date, self::source_label($x->source), $x->doc_number, $x->ipo_no, $x->model,
                $x->part_name, $x->part_number, (float) $x->qty_ng, $x->unit, $x->remark,
            ], null, 'A' . $r, true);
            $r++;
        }
        if ($r > 2) {
            $sh->setCellValue('G' . $r, 'TOTAL');
            $sh->setCellValue('H' . $r, $totals['qty_ng']);
            $sh->getStyle('G' . $r . ':H' . $r)->getFont()->setBold(true);
        }
        foreach (range('A', 'J') as $c) $sh->getColumnDimension($c)->setAutoSize(true);

        $rc = $ss->createSheet();
        $rc->setTitle('Rekap per Part');
        $rc->fromArray(['Part Name', 'Part Number', 'Satuan', 'NG QC Penerimaan', 'NG Proses Produksi', 'Total Qty NG', 'Jumlah Kejadian'], null, 'A1', true);
        $rc->getStyle('A1:G1')->getFont()->setBold(true);
        $r = 2;
        foreach ($recap as $x) {
            $rc->fromArray([$x->part_name, $x->part_number, $x->unit, (float) $x->qty_qc, (float) $x->qty_scrap, (float) $x->qty_ng, (int) $x->events], null, 'A' . $r, true);
            $r++;
        }
        foreach (range('A', 'G') as $c) $rc->getColumnDimension($c)->setAutoSize(true);

        $ss->setActiveSheetIndex(0);
        return $ss;
    }
}
