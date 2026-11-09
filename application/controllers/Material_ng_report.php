<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Daily Material NG Report -- disederhanakan 2026-11-08: tiap NG cukup
 * Qty NG + Keterangan (rincian 4 kategori & Kode NG warisan NSI dihapus).
 * Sumber: QC Production Receive & asal scrap Proses Produksi
 * (lihat Material_ng_model).
 */
class Material_ng_report extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Material_ng_model');
        $this->load->helper(['url', 'form']);
    }

    private function _filters() {
        return [
            'date_from' => $this->input->get('date_from') ?: null,
            'date_to'   => $this->input->get('date_to')   ?: null,
            'ipo'       => trim((string) $this->input->get('ipo', TRUE)),
            'model'     => trim((string) $this->input->get('model', TRUE)),
            'part'      => trim((string) $this->input->get('part', TRUE)),
            'source'    => trim((string) $this->input->get('source', TRUE)),
        ];
    }

    public function index() {
        if (!check_access('material_ng_report', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $filters = $this->_filters();
        $ready   = $this->Material_ng_model->is_ready();
        $rows    = $ready ? $this->Material_ng_model->get_rows($filters) : [];

        $data = [
            'title'      => lang_line('mng_title', 'Daily Material NG Report'),
            'filters'    => $filters,
            'rows'       => $rows,
            'recap'      => $this->Material_ng_model->recap($rows),
            'totals'     => $this->Material_ng_model->totals($rows),
            'ready'      => $ready,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_ng_report/index', $data);
        $this->load->view('templates/footer');
    }

    /** Export 2 sheet: NG Material (rincian) & Rekap per Part. */
    public function export_excel() {
        if (!check_access('material_ng_report', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $filters = $this->_filters();
        $rows    = $this->Material_ng_model->is_ready()
                 ? $this->Material_ng_model->get_rows($filters) : [];
        $recap   = $this->Material_ng_model->recap($rows);
        $totals  = $this->Material_ng_model->totals($rows);

        $ss = $this->Material_ng_model->build_spreadsheet($rows, $recap, $totals);

        $filename = 'Daily_Material_NG_Report_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        (new Xlsx($ss))->save('php://output');
        exit;
    }
}
