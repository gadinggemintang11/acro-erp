<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daily Material NG Report.
 *
 * Menggabungkan dua titik pencatatan NG material jadi satu tabel yang
 * kolomnya mengikuti sheet "MATERIAL" pada
 * assets/DAILY PRODUCTION REPORT (PQC & MATERIAL) (1).xlsx:
 *
 *   1. production_scrap_sources -- material yang jadi scrap saat proses
 *      produksi. SUMBER UTAMA: punya rantai lengkap ke atas
 *      (production_header -> wo_header -> sales_contracts) sehingga IPO NO
 *      & MODEL ikut terisi.
 *
 *   2. issuance_qc_results -- material yang ditolak saat QC penerimaan
 *      produksi. PELENGKAP: mr_header TIDAK menyimpan work order maupun
 *      sales contract, jadi IPO NO & MODEL baris-baris ini memang kosong.
 *
 * Kolom PART CHINESE sengaja tidak diambil dari database (user 2026-09-30:
 * "part chinese skip aja, biar user isi sendiri") -- di export kolomnya tetap
 * ada tapi kosong, untuk diketik manual.
 *
 * Semua query DIJAGA dengan pengecekan skema: kalau
 * db/migration_material_ng_detail.sql belum dijalankan, laporan mengembalikan
 * array kosong, bukan error.
 */
class Material_ng_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * TRUE kalau minimal satu sumber sudah siap dibaca.
     */
    public function is_ready() {
        return $this->scrap_ready() || $this->qc_ready();
    }

    private function scrap_ready() {
        return $this->db->table_exists('production_scrap_sources')
            && ng_table_ready('production_scrap_sources');
    }

    private function qc_ready() {
        return $this->db->table_exists('issuance_qc_results')
            && ng_table_ready('issuance_qc_results');
    }

    /**
     * Baris laporan, sudah digabung & diurutkan.
     *
     * @param array $f filter: date_from, date_to, ipo, model, part, ng_code, source
     * @return array of object
     */
    public function get_rows($f = []) {
        $rows = [];
        if ($this->scrap_ready()) $rows = array_merge($rows, $this->_scrap_rows($f));
        if ($this->qc_ready())    $rows = array_merge($rows, $this->_qc_rows($f));

        // TOTAL dihitung di PHP, bukan di SQL, supaya rumusnya satu tempat
        // (ng_total di ng_helper) dan sama persis dengan yang dipakai form.
        foreach ($rows as $r) {
            $r->total       = ng_total($r);
            $r->reason      = ng_code_description($r->ng_code);
            $r->part_chinese = '';   // diisi manual oleh user di file hasil export
        }

        // Baris tanpa rincian NG sama sekali tidak menarik untuk laporan ini.
        $rows = array_values(array_filter($rows, function ($r) {
            return $r->total > 0 || ($r->ng_code !== null && $r->ng_code !== '');
        }));

        usort($rows, function ($a, $b) {
            $c = strcmp((string) $a->ng_date, (string) $b->ng_date);
            if ($c !== 0) return $c;
            $c = strcmp((string) $a->ipo_no, (string) $b->ipo_no);
            if ($c !== 0) return $c;
            return strcmp((string) $a->part_name, (string) $b->part_name);
        });

        return $rows;
    }

    /**
     * Sumber 1: scrap dari proses produksi.
     */
    private function _scrap_rows($f) {
        $this->db->reset_query();
        $this->db->select("
                ph.production_date                                   AS ng_date,
                COALESCE(sc.sc_number, '')                           AS ipo_no,
                COALESCE(fg.nama_barang, '')                         AS model,
                COALESCE(NULLIF(m.nama_material, ''), COALESCE(sf.nama_barang, ''))   AS part_name,
                COALESCE(NULLIF(m.kode_material, ''), COALESCE(sf.kode_barang, ''))   AS part_number,
                pss.ng_human, pss.ng_material, pss.ng_mechanic, pss.ng_others,
                pss.ng_code,
                COALESCE(pss.notes, '')                              AS remark,
                pss.source_qty                                       AS qty_ref,
                ph.production_number                                 AS doc_number,
                'scrap'                                              AS source
            ", FALSE)
            ->from('production_scrap_sources pss')
            ->join('production_outputs po', 'po.id = pss.production_output_id', 'inner')
            ->join('production_header ph', 'ph.id = po.production_header_id', 'inner')
            ->join('production_inputs pi', 'pi.id = pss.source_input_id', 'inner')
            ->join('wo_header wo', 'wo.id = ph.wo_header_id', 'left')
            ->join('sales_contracts sc', 'sc.id = wo.sales_contract_id', 'left')
            ->join('finished_goods fg', 'fg.id = wo.fg_id', 'left')
            ->join('materials m', "m.id = pi.item_id AND pi.item_type = 'material'", 'left')
            ->join('semifinished_goods sf', "sf.id = pi.item_id AND pi.item_type = 'semifinished'", 'left');

        $this->_apply_filters($f, 'ph.production_date', 'pss.ng_code', 'sc.sc_number', 'fg.nama_barang');
        if (!empty($f['source']) && $f['source'] !== 'scrap') return [];

        return $this->db->get()->result();
    }

    /**
     * Sumber 2: reject saat QC penerimaan produksi.
     *
     * IPO NO & MODEL dikosongkan dengan sengaja: jalur ini tidak punya
     * hubungan ke work order (mr_header tidak menyimpannya).
     */
    private function _qc_rows($f) {
        $this->db->reset_query();
        $this->db->select("
                DATE(COALESCE(qr.qc_done_at, mi.received_at))        AS ng_date,
                ''                                                   AS ipo_no,
                ''                                                   AS model,
                COALESCE(NULLIF(m.nama_material, ''), COALESCE(sf.nama_barang, ''))   AS part_name,
                COALESCE(NULLIF(m.kode_material, ''), COALESCE(sf.kode_barang, ''))   AS part_number,
                qr.ng_human, qr.ng_material, qr.ng_mechanic, qr.ng_others,
                qr.ng_code,
                COALESCE(qr.qc_notes, '')                            AS remark,
                qr.reject_qty                                        AS qty_ref,
                mi.issuance_number                                   AS doc_number,
                'qc'                                                 AS source
            ", FALSE)
            ->from('issuance_qc_results qr')
            ->join('mr_issuances mi', 'mi.id = qr.issuance_id', 'left')
            ->join('materials m', "m.id = qr.item_id AND qr.item_type = 'material'", 'left')
            ->join('semifinished_goods sf', "sf.id = qr.item_id AND qr.item_type = 'semifinished'", 'left')
            ->where('qr.reject_qty >', 0);

        $this->_apply_filters($f, 'DATE(COALESCE(qr.qc_done_at, mi.received_at))', 'qr.ng_code', null, null);
        if (!empty($f['source']) && $f['source'] !== 'qc') return [];
        // Filter IPO/model tidak berlaku di jalur ini -- baris ini memang tidak
        // punya keduanya, jadi begitu user memfilternya, jalur ini dikeluarkan.
        if (!empty($f['ipo']) || !empty($f['model'])) return [];

        return $this->db->get()->result();
    }

    private function _apply_filters($f, $date_col, $code_col, $ipo_col, $model_col) {
        if (!empty($f['date_from'])) $this->db->where($date_col . ' >=', $f['date_from']);
        if (!empty($f['date_to']))   $this->db->where($date_col . ' <=', $f['date_to']);
        if (!empty($f['ng_code']))   $this->db->where($code_col, $f['ng_code']);
        if (!empty($f['ipo'])   && $ipo_col)   $this->db->like($ipo_col, $f['ipo']);
        if (!empty($f['model']) && $model_col) $this->db->like($model_col, $f['model']);
        if (!empty($f['part'])) {
            $term = $this->db->escape_like_str($f['part']);
            $this->db->group_start()
                     ->where("(m.nama_material LIKE '%{$term}%' OR m.kode_material LIKE '%{$term}%'"
                           . " OR sf.nama_barang LIKE '%{$term}%' OR sf.kode_barang LIKE '%{$term}%')", null, FALSE)
                     ->group_end();
        }
    }

    /**
     * Rekap ala sheet "PIVOT": dikelompokkan per IPO / MODEL / PART / DATE /
     * REMARK, dengan penjumlahan tiap kategori.
     */
    public function pivot($rows) {
        $out = [];
        foreach ($rows as $r) {
            $key = implode('||', [$r->ipo_no, $r->model, $r->part_name, $r->part_number, $r->ng_date, $r->remark]);
            if (!isset($out[$key])) {
                $out[$key] = (object) [
                    'ipo_no'      => $r->ipo_no,
                    'model'       => $r->model,
                    'part_name'   => $r->part_name,
                    'part_number' => $r->part_number,
                    'ng_date'     => $r->ng_date,
                    'remark'      => $r->remark,
                    'ng_human'    => 0.0,
                    'ng_material' => 0.0,
                    'ng_mechanic' => 0.0,
                    'ng_others'   => 0.0,
                    'total'       => 0.0,
                ];
            }
            foreach (array_keys(ng_categories()) as $col) {
                $out[$key]->$col += (float) $r->$col;
            }
            $out[$key]->total += (float) $r->total;
        }
        return array_values($out);
    }

    /**
     * Total keseluruhan per kategori, untuk baris Grand Total.
     */
    public function totals($rows) {
        $t = ['total' => 0.0];
        foreach (array_keys(ng_categories()) as $col) $t[$col] = 0.0;
        foreach ($rows as $r) {
            foreach (array_keys(ng_categories()) as $col) $t[$col] += (float) $r->$col;
            $t['total'] += (float) $r->total;
        }
        return $t;
    }

    /**
     * Susun workbook export: sheet MATERIAL (rincian) + sheet PIVOT (rekap),
     * judul kolom sama persis dengan file klien supaya hasilnya bisa langsung
     * ditempel ke template mereka.
     *
     * Ditaruh di model (bukan controller) supaya bisa diuji tanpa melewati
     * check_login/check_access.
     */
    public function build_spreadsheet($rows, $pivot, $totals) {
        require_once FCPATH . 'vendor/autoload.php';
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // ---------- Sheet MATERIAL ----------
        $sh = $ss->getActiveSheet();
        $sh->setTitle('MATERIAL');
        $head = [
            'DATE日期', 'SALES ORDER NO. 销售订单编号', 'MODEL 型号', 'PART NAME', 'PART NUMBER',
            'PART CHINESE', 'HUMAN ERROR', 'MATERIAL ISSUE', 'MECHANIC ISSUE',
            'OTHERS ISSUE', 'TOTAL', 'NOT GOOD CODE 缺陷代码', 'REASON DEFECT 缺陷', 'REMARK',
        ];
        $sh->fromArray($head, null, 'A1', true);
        $sh->getStyle('A1:N1')->getFont()->setBold(true);

        $r = 2;
        foreach ($rows as $x) {
            $sh->fromArray([
                $x->ng_date,
                $x->ipo_no,
                $x->model,
                $x->part_name,
                $x->part_number,
                $x->part_chinese,          // sengaja kosong -- diisi manual oleh user
                (float) $x->ng_human,
                (float) $x->ng_material,
                (float) $x->ng_mechanic,
                (float) $x->ng_others,
                (float) $x->total,
                $x->ng_code,
                $x->reason,
                $x->remark,
            ], null, 'A' . $r, true);
            $r++;
        }

        if ($r > 2) {
            $sh->setCellValue('F' . $r, 'GRAND TOTAL');
            $sh->setCellValue('G' . $r, $totals['ng_human']);
            $sh->setCellValue('H' . $r, $totals['ng_material']);
            $sh->setCellValue('I' . $r, $totals['ng_mechanic']);
            $sh->setCellValue('J' . $r, $totals['ng_others']);
            $sh->setCellValue('K' . $r, $totals['total']);
            $sh->getStyle('F' . $r . ':K' . $r)->getFont()->setBold(true);
        }
        foreach (range('A', 'N') as $c) $sh->getColumnDimension($c)->setAutoSize(true);

        // ---------- Sheet PIVOT ----------
        $pv = $ss->createSheet();
        $pv->setTitle('PIVOT');
        $pv->fromArray([
            'SALES ORDER NO. 销售订单编号', 'MODEL 型号', 'PART NAME', 'PART NUMBER', 'DATE日期', 'REMARK',
            'Sum of HUMAN ERROR', 'Sum of OTHERS ISSUE', 'Sum of MATERIAL ISSUE', 'Sum of MECHANIC ISSUE',
        ], null, 'A1', true);
        $pv->getStyle('A1:J1')->getFont()->setBold(true);

        $r = 2;
        foreach ($pivot as $x) {
            $pv->fromArray([
                $x->ipo_no, $x->model, $x->part_name, $x->part_number, $x->ng_date, $x->remark,
                (float) $x->ng_human, (float) $x->ng_others, (float) $x->ng_material, (float) $x->ng_mechanic,
            ], null, 'A' . $r, true);
            $r++;
        }
        if ($r > 2) {
            $pv->setCellValue('F' . $r, 'Grand Total');
            $pv->setCellValue('G' . $r, $totals['ng_human']);
            $pv->setCellValue('H' . $r, $totals['ng_others']);
            $pv->setCellValue('I' . $r, $totals['ng_material']);
            $pv->setCellValue('J' . $r, $totals['ng_mechanic']);
            $pv->getStyle('F' . $r . ':J' . $r)->getFont()->setBold(true);
        }
        foreach (range('A', 'J') as $c) $pv->getColumnDimension($c)->setAutoSize(true);

        $ss->setActiveSheetIndex(0);


        return $ss;
    }

}
