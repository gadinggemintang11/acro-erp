<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class Qa_sampling extends MY_Controller {

    private $item_types = [
        'material'       => 'Material',
        'semifinished'   => 'Semifinished',
        'finished_goods' => 'Finished Goods',
        'inventory'      => 'Inventory'
    ];

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Qa_sampling_model', 'm');
        $this->load->library('pagination');
        $this->load->library('session');
        $this->load->helper(['url','form']);
    }

    // ============================================================
    // INDEX
    // ============================================================
    public function index() {
        if (!check_access('qa_sampling', 'view')) {
            show_error('Tidak ada izin.', 403);
        }

        $filters = [
            'status'    => $this->input->get('status'),
            'date_from' => $this->input->get('date_from'),
            'date_to'   => $this->input->get('date_to'),
            'search'    => $this->input->get('search'),
        ];

        $per_page = 25;
        $page = max(1, (int)($this->input->get('page') ?: 1));
        $offset = ($page - 1) * $per_page;

        $total = $this->m->count_all($filters);
        $rows  = $this->m->get_all($filters, $per_page, $offset);
        $total_pages = max(1, ceil($total / $per_page));

        $data = [
            'title'       => lang_line('title_qas_index'),
            'rows'        => $rows,
            'filters'     => $filters,
            'page'        => $page,
            'total_pages' => $total_pages,
            'total'       => $total,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('qa_sampling/index', $data);
        $this->load->view('templates/footer');
    }

    // ============================================================
    // CREATE / EDIT
    // ============================================================
    public function create() {
        if (!check_access('qa_sampling', 'add')) show_error('Tidak ada izin.', 403);

        if ($this->input->method() === 'post') {
            return $this->_save(null);
        }

        $data = [
            'title'      => lang_line('title_qas_create'),
            'doc_no'     => $this->m->generate_doc_no(),
            'header'     => null,
            'details'    => [],
            'item_types' => $this->item_types
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('qa_sampling/create', $data);
        $this->load->view('templates/footer');
    }

    public function edit($id) {
        if (!check_access('qa_sampling', 'edit')) show_error('Tidak ada izin.', 403);
        $header = $this->m->get_header($id);
        if (!$header) show_404();
        if (!in_array($header->status, ['Draft','Submitted'])) {
            $this->session->set_flashdata('error', 'Hanya dokumen Draft/Submitted yang bisa diedit.');
            redirect('qa_sampling/view/'.$id);
            return;
        }

        if ($this->input->method() === 'post') {
            return $this->_save($id);
        }

        $data = [
            'title'      => lang_line('title_qas_edit'),
            'doc_no'     => $header->doc_no,
            'header'     => $header,
            'details'    => $this->m->get_details($id),
            'item_types' => $this->item_types
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('qa_sampling/create', $data);
        $this->load->view('templates/footer');
    }

    private function _save($id = null) {
        $user_id = $this->session->userdata('user_id');
        $now = date('Y-m-d H:i:s');

        $doc_date = $this->input->post('doc_date') ?: date('Y-m-d');
        $purpose  = trim($this->input->post('purpose'));
        $notes    = trim($this->input->post('notes'));
        $requester_dept = $this->input->post('requester_dept');
        $items_json = $this->input->post('items_json');
        $items = json_decode($items_json, true);

        if (empty($purpose)) {
            $this->session->set_flashdata('error', 'Tujuan sampling wajib diisi.');
            redirect($id ? 'qa_sampling/edit/'.$id : 'qa_sampling/create');
            return;
        }
        if (empty($items) || !is_array($items)) {
            $this->session->set_flashdata('error', 'Minimal 1 item harus dimasukkan.');
            redirect($id ? 'qa_sampling/edit/'.$id : 'qa_sampling/create');
            return;
        }

        if ($id) {
            $this->m->update_header($id, [
                'doc_date'       => $doc_date,
                'purpose'        => $purpose,
                'notes'          => $notes,
                'requester_dept' => $requester_dept
            ]);
            $this->m->delete_details_by_header($id);
            $header_id = $id;
        } else {
            $header_id = $this->m->create_header([
                'doc_no'         => $this->m->generate_doc_no(),
                'doc_date'       => $doc_date,
                'purpose'        => $purpose,
                'notes'          => $notes,
                'requester_id'   => $user_id,
                'requester_dept' => $requester_dept,
                'status'         => 'Submitted',
                'created_by'     => $user_id,
                'created_at'     => $now
            ]);
        }

        foreach ($items as $it) {
            // Validasi qty available
            $source = ($it['source'] ?? 'warehouse') === 'wip' ? 'wip' : 'warehouse';
            $available = ($source === 'wip')
                ? $this->m->get_wip_balance($it['item_id'], $it['item_type'])
                : $this->m->get_warehouse_balance($it['item_id'], $it['item_type']);

            $qty = (float)($it['qty_sample'] ?? 0);
            if ($qty <= 0) continue;
            if ($qty > $available + 0.0001) {
                $this->session->set_flashdata('error',
                    'Stok item '. ($it['item_code'] ?? $it['item_id']) .' tidak mencukupi (tersedia: '.$available.').');
                redirect('qa_sampling/edit/'.$header_id);
                return;
            }

            $this->m->insert_detail([
                'header_id'            => $header_id,
                'source'               => $source,
                'item_id'              => $it['item_id'],
                'item_type'            => $it['item_type'],
                'production_header_id' => !empty($it['production_header_id']) ? $it['production_header_id'] : null,
                'qty_sample'           => $qty,
                'uom'                  => $it['uom'] ?? null,
                'notes'                => $it['notes'] ?? null,
                'test_status'          => 'Pending'
            ]);
        }

        $this->session->set_flashdata('success', 'QA Sampling disimpan.');
        redirect('qa_sampling/view/'.$header_id);
    }

    // ============================================================
    // VIEW
    // ============================================================
    public function view($id) {
        if (!check_access('qa_sampling', 'view')) show_error('Tidak ada izin.', 403);
        $header = $this->m->get_header($id);
        if (!$header) show_404();

        $data = [
            'title'      => lang_line('title_qas_detail'),
            'header'     => $header,
            'details'    => $this->m->get_details($id),
            'item_types' => $this->item_types,
            'counts'     => $this->m->detail_counts($id)
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('qa_sampling/view', $data);
        $this->load->view('templates/footer');
    }

    // ============================================================
    // APPROVE
    // ============================================================
    public function approve($id) {
        if (!check_access('qa_sampling', 'edit')) show_error('Tidak ada izin.', 403);
        $header = $this->m->get_header($id);
        if (!$header) show_404();
        if ($header->status !== 'Submitted') {
            $this->session->set_flashdata('error', 'Hanya dokumen Submitted yang bisa di-approve.');
            redirect('qa_sampling/view/'.$id);
            return;
        }
        $this->m->update_header($id, [
            'status'      => 'Approved',
            'approved_by' => $this->session->userdata('user_id'),
            'approved_at' => date('Y-m-d H:i:s')
        ]);
        $this->session->set_flashdata('success', 'Dokumen di-approve.');
        redirect('qa_sampling/view/'.$id);
    }

    // ============================================================
    // POST (kurangi stock)
    // ============================================================
    public function post_sampling($id) {
        if (!check_access('qa_sampling', 'edit')) show_error('Tidak ada izin.', 403);
        $result = $this->m->post_sampling($id, $this->session->userdata('user_id'));
        if ($result['status']) {
            $this->session->set_flashdata('success', $result['message']);
        } else {
            $this->session->set_flashdata('error', $result['message']);
        }
        redirect('qa_sampling/view/'.$id);
    }

    // ============================================================
    // CANCEL / DELETE (Draft only)
    // ============================================================
    public function cancel($id) {
        if (!check_access('qa_sampling', 'edit')) show_error('Tidak ada izin.', 403);
        $header = $this->m->get_header($id);
        if (!$header) show_404();
        if (!in_array($header->status, ['Draft','Submitted','Approved'])) {
            $this->session->set_flashdata('error', 'Tidak bisa cancel dokumen status '. $header->status);
            redirect('qa_sampling/view/'.$id);
            return;
        }
        $this->m->update_header($id, ['status' => 'Cancelled']);
        $this->session->set_flashdata('success', 'Dokumen di-cancel.');
        redirect('qa_sampling/view/'.$id);
    }

    // ============================================================
    // UPDATE HASIL TEST (AJAX)
    // ============================================================
    public function update_test() {
        if (!$this->input->is_ajax_request()) show_error('Forbidden', 403);
        if (!check_access('qa_sampling', 'edit')) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success'=>false,'message'=>'No permission']));
        }
        $detail_id = (int)$this->input->post('detail_id');
        $status    = $this->input->post('status');
        $notes     = $this->input->post('notes');
        if (!in_array($status, ['Pending','Pass','Fail'])) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success'=>false,'message'=>'Status tidak valid']));
        }
        $this->m->update_test_result($detail_id, $status, $notes, $this->session->userdata('user_id'));

        // Auto close header jika semua detail sudah ada hasil (tidak Pending)
        $detail = $this->m->get_detail($detail_id);
        if ($detail) {
            $c = $this->m->detail_counts($detail->header_id);
            if ($c && (int)$c->pending === 0 && (int)$c->total > 0) {
                $h = $this->m->get_header($detail->header_id);
                if ($h && $h->status === 'Posted') {
                    $this->m->update_header($detail->header_id, [
                        'status'    => 'Closed',
                        'closed_by' => $this->session->userdata('user_id'),
                        'closed_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }

        return $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success'=>true]));
    }

    // ============================================================
    // AJAX search item
    // ============================================================
    public function search_items() {
        if (!$this->input->is_ajax_request()) show_error('Forbidden', 403);
        $source  = $this->input->get('source') === 'wip' ? 'wip' : 'warehouse';
        $keyword = $this->input->get('q') ?: '';
        if (strlen($keyword) < 2) {
            return $this->output->set_content_type('application/json')->set_output(json_encode([]));
        }
        $items = ($source === 'wip')
            ? $this->m->search_wip_items($keyword)
            : $this->m->search_warehouse_items($keyword);
        return $this->output->set_content_type('application/json')->set_output(json_encode($items));
    }

    // ============================================================
    // REPORT
    // ============================================================
    public function report() {
        if (!check_access('qa_sampling_report', 'view') && !check_access('qa_sampling', 'view')) {
            show_error('Tidak ada izin.', 403);
        }
        $filters = [
            'date_from'   => $this->input->get('date_from') ?: date('Y-m-01'),
            'date_to'     => $this->input->get('date_to')   ?: date('Y-m-t'),
            'source'      => $this->input->get('source'),
            'test_status' => $this->input->get('test_status'),
            'item_type'   => $this->input->get('item_type'),
        ];
        $rows = $this->m->get_report($filters);

        $data = [
            'title'      => lang_line('title_qas_report'),
            'rows'       => $rows,
            'filters'    => $filters,
            'item_types' => $this->item_types
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('qa_sampling/report', $data);
        $this->load->view('templates/footer');
    }

    // ============================================================
    // EXPORT EXCEL (report)
    // ============================================================
    public function export_excel() {
        if (!check_access('qa_sampling_report', 'view') && !check_access('qa_sampling', 'view')) {
            show_error('Tidak ada izin.', 403);
        }
        $filters = [
            'date_from'   => $this->input->get('date_from'),
            'date_to'     => $this->input->get('date_to'),
            'source'      => $this->input->get('source'),
            'test_status' => $this->input->get('test_status'),
            'item_type'   => $this->input->get('item_type'),
        ];
        $rows = $this->m->get_report($filters);

        try {
            $sp = new Spreadsheet();
            $sh = $sp->getActiveSheet();
            $sh->setTitle('QA Sampling');
            $sh->setCellValue('A1', 'LAPORAN QA SAMPLING');
            $sh->mergeCells('A1:L1');
            $sh->setCellValue('A2', 'Periode: '.($filters['date_from'] ?: '-').' s.d. '.($filters['date_to'] ?: '-'));
            $sh->mergeCells('A2:L2');

            $headers = ['No Dokumen','Tanggal','Sumber','Tipe Item','Kode','Nama','Qty Sample','UoM',
                        'Tujuan','Hasil Test','Catatan Test','Requester'];
            $sh->fromArray($headers, NULL, 'A4');
            $sh->getStyle('A4:L4')->getFont()->setBold(true);

            $r = 5;
            foreach ($rows as $row) {
                $sh->fromArray([
                    $row->doc_no, $row->doc_date,
                    strtoupper($row->source),
                    ucfirst(str_replace('_',' ', $row->item_type)),
                    $row->item_code, $row->item_name,
                    (float)$row->qty_sample, $row->uom,
                    $row->purpose, $row->test_status,
                    $row->test_result_notes, $row->requester_name
                ], NULL, 'A'.$r);
                $r++;
            }
            foreach (range('A','L') as $c) $sh->getColumnDimension($c)->setAutoSize(true);

            $filename = 'Laporan_QA_Sampling_'.date('Ymd_His').'.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'.$filename.'"');
            header('Cache-Control: max-age=0');
            (new Xlsx($sp))->save('php://output');
            exit;
        } catch (Exception $e) {
            log_message('error', 'QA Sampling Excel: '.$e->getMessage());
            show_error('Gagal export Excel.');
        }
    }

    // ============================================================
    // EXPORT PDF (single document)
    // ============================================================
    public function export_pdf($id) {
        if (!check_access('qa_sampling', 'view')) show_error('Tidak ada izin.', 403);
        $header = $this->m->get_header($id);
        if (!$header) show_404();

        $data = [
            'header'     => $header,
            'details'    => $this->m->get_details($id),
            'item_types' => $this->item_types
        ];
        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $html = $this->load->view('qa_sampling/pdf_template', $data, true);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream('QA_Sampling_'.$header->doc_no.'.pdf', ['Attachment'=>0]);
            exit;
        } catch (Exception $e) {
            log_message('error', 'QA Sampling PDF: '.$e->getMessage());
            show_error('Gagal generate PDF.');
        }
    }
}
