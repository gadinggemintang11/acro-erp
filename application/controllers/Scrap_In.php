<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Gunakan library dari Composer
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Scrap_In extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login(); // 2026-11-05: halaman ini sebelumnya bisa dibuka TANPA login
        $this->load->model('Scrap_In_model');
        $this->load->model('Stock_ledger_model');
        $this->load->library('form_validation');
        $this->load->library('session');
        $this->load->helper('url');
    }

    public function index() {
        // if (!check_access('scrap_in', 'view')) redirect('denied');
        $data['title'] = lang_line('title_si_index');
        
        // Ambil filter
        $filters = ['search' => $this->input->get('search')];
        $data['filters'] = $filters;

        $data['documents'] = $this->Scrap_In_model->get_all($filters);
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('scrap_in/list', $data); 
        $this->load->view('templates/footer');
    }

    // --- REVISI: create() - Production Scrap ---
    public function create() {
        // if (!check_access('scrap_in', 'add')) redirect('denied');
        $data['title'] = lang_line('title_si_collect');
        // Ambil data scrap PER BARIS production_outputs
        $data['uncollected_scrap_rows'] = $this->Scrap_In_model->get_uncollected_production_scrap();
        $data['doc_no'] = $this->Scrap_In_model->generate_doc_no();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('scrap_in/collect_form', $data); 
        $this->load->view('templates/footer');
    }

    // --- FUNGSI BARU: create_warehouse() - Warehouse Scrap (NEW) ---
    public function create_warehouse() {
        // if (!check_access('scrap_in', 'add')) redirect('denied');
        $data['title'] = lang_line('title_si_warehouse');
        $data['doc_no'] = $this->Scrap_In_model->generate_doc_no();
        $data['master_scrap'] = $this->Scrap_In_model->get_master_scrap();

        // Get warehouse items dengan stock terkini (struktur array asosiatif untuk view)
        $data['master_items'] = [
            'material' => $this->_get_items_with_stock('material'),
            'semifinished' => $this->_get_items_with_stock('semifinished'),
            'finished_goods' => $this->_get_items_with_stock('finished_goods'),
            'inventory' => $this->_get_items_with_stock('inventory')
        ];

        // Juga pass warehouse_items untuk kompatibilitas
        $data['warehouse_items'] = array_merge(
            $data['master_items']['material'] ?? [],
            $data['master_items']['semifinished'] ?? [],
            $data['master_items']['finished_goods'] ?? [],
            $data['master_items']['inventory'] ?? []
        );

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('scrap_in/warehouse_form', $data);
        $this->load->view('templates/footer');
    }

    // --- FUNGSI HELPER: ambil items dengan stock ---
    private function _get_items_with_stock($item_type) {
        $items = [];

        // Query dengan status filter (case-insensitive)
        switch ($item_type) {
            case 'material':
                $items = $this->db->select('id, kode_material as code, nama_material as name, satuan')
                    ->where_in('status', ['Active', 'active'])
                    ->get('materials')->result_array();
                break;
            case 'semifinished':
                $items = $this->db->select('id, kode_barang as code, nama_barang as name, satuan')
                    ->where_in('status', ['Active', 'active'])
                    ->get('semifinished_goods')->result_array();
                break;
            case 'finished_goods':
                $items = $this->db->select('id, kode_barang as code, nama_barang as name, satuan')
                    ->where_in('status', ['Active', 'active'])
                    ->get('finished_goods')->result_array();
                break;
            case 'inventory':
                $items = $this->db->select('id, kode_barang as code, nama_barang as name, satuan')
                    ->where_in('status', ['Active', 'active'])
                    ->get('inventories')->result_array();
                break;
        }

        // Jika tidak ada item, ambil semua tanpa filter status
        if (empty($items)) {
            switch ($item_type) {
                case 'material':
                    $items = $this->db->select('id, kode_material as code, nama_material as name, satuan')
                        ->get('materials')->result_array();
                    break;
                case 'semifinished':
                    $items = $this->db->select('id, kode_barang as code, nama_barang as name, satuan')
                        ->get('semifinished_goods')->result_array();
                    break;
                case 'finished_goods':
                    $items = $this->db->select('id, kode_barang as code, nama_barang as name, satuan')
                        ->get('finished_goods')->result_array();
                    break;
                case 'inventory':
                    $items = $this->db->select('id, kode_barang as code, nama_barang as name, satuan')
                        ->get('inventories')->result_array();
                    break;
            }
        }

        // Tambah stock terkini untuk setiap item - direct query dari stock_ledger
        foreach ($items as &$item) {
            $stock = 0;

            // Method 1: coba dengan model
            try {
                $balance = $this->Stock_ledger_model->get_last_balance($item['id'], $item_type);
                if ($balance !== null && $balance !== false) {
                    $stock = (float)$balance;
                }
            } catch (Exception $e) {
                log_message('error', 'Error get_last_balance: ' . $e->getMessage());
            }

            // Method 2: jika method 1 gagal, coba direct query
            if ($stock == 0) {
                $row = $this->db->select('balance')
                    ->where('item_id', $item['id'])
                    ->where('item_type', $item_type)
                    ->order_by('id', 'DESC')
                    ->limit(1)
                    ->get('stock_ledger')
                    ->row();

                if ($row && isset($row->balance)) {
                    $stock = (float)$row->balance;
                }
            }

            $item['stock'] = $stock;
            $item['type'] = $item_type;
        }

        return $items;
    }

    // --- REVISI: process_collection() ---
    public function process_collection() {
        // if (!check_access('scrap_in', 'add')) redirect('denied');

        $this->form_validation->set_rules('doc_date', 'Tanggal Dokumen', 'required');
        
        // Ambil ID production_outputs yang diceklis
        $selected_output_ids = $this->input->post('selected_outputs'); // Array dari checkbox
        
        if (empty($selected_output_ids) || !$this->form_validation->run()) {
             $this->session->set_flashdata('error', 'Tidak ada baris scrap yang dipilih atau tanggal tidak valid.');
             redirect('Scrap_In/create');
             return;
        }

        // Ambil detail data scrap HANYA untuk ID yang dipilih
        $collected_data = $this->Scrap_In_model->get_production_scrap_details_by_ids($selected_output_ids); 
        // ^^^ Anda perlu membuat fungsi ini di model ^^^ 
        // Fungsi ini mirip get_uncollected.., tapi di-filter by ID dan hasilnya lebih detail
        
        if (empty($collected_data['summary']) || empty($collected_data['sources_map'])) {
             $this->session->set_flashdata('error', 'Gagal mengambil detail scrap yang dipilih.');
             redirect('Scrap_In/create');
             return;
        }

        $header_data = [
            'doc_no' => $this->input->post('doc_no'),
            'doc_date' => $this->input->post('doc_date'),
            'notes' => $this->input->post('notes'),
            'status' => 'Draft',
            'source_type' => 'production',
            'created_by' => $this->session->userdata('user_id'),
        ];
        
        // Kirim summary (total per scrap_id) dan mapping sources ke model
        $result = $this->Scrap_In_model->create_draft_from_collection(
            $header_data, 
            $collected_data['summary'], 
            $collected_data['sources_map'] 
        );

        if ($result) {
            $this->session->set_flashdata('success', 'Draft penerimaan scrap ' . $header_data['doc_no'] . ' berhasil dibuat.');
            redirect('Scrap_In');
        } else {
            $this->session->set_flashdata('error', 'Gagal membuat draft.');
            redirect('Scrap_In/create');
        }
    }

    // --- FUNGSI BARU: store_warehouse() - Save warehouse scrap draft ---
    public function store_warehouse() {
        // if (!check_access('scrap_in', 'add')) redirect('denied');

        $this->form_validation->set_rules('doc_date', 'Tanggal Dokumen', 'required');

        $warehouse_items = $this->input->post('warehouse_items'); // JSON dari frontend

        if (!$this->form_validation->run() || empty($warehouse_items)) {
            $this->session->set_flashdata('error', 'Tanggal tidak valid atau tidak ada item yang diinput.');
            redirect('Scrap_In/create_warehouse');
            return;
        }

        // Parse JSON dari frontend
        $items_array = json_decode($warehouse_items, true);
        if (empty($items_array) || !is_array($items_array)) {
            $this->session->set_flashdata('error', 'Format data item tidak valid.');
            redirect('Scrap_In/create_warehouse');
            return;
        }

        $header_data = [
            'doc_no' => $this->input->post('doc_no'),
            'doc_date' => $this->input->post('doc_date'),
            'notes' => $this->input->post('notes'),
            'status' => 'Draft',
            'source_type' => 'warehouse',
            'created_by' => $this->session->userdata('user_id'),
        ];

        $result = $this->Scrap_In_model->create_draft_warehouse($header_data, $items_array);

        if ($result) {
            $this->session->set_flashdata('success', 'Draft scrap warehouse ' . $header_data['doc_no'] . ' berhasil dibuat.');
            redirect('Scrap_In');
        } else {
            $this->session->set_flashdata('error', 'Gagal membuat draft warehouse.');
            redirect('Scrap_In/create_warehouse');
        }
    }

    // --- view(), confirm(), delete() tetap sama ---
    public function view($id) {
        // if (!check_access('scrap_in', 'view')) redirect('denied');
        $data['data'] = $this->Scrap_In_model->get_by_id($id);
        if (empty($data['data'])) {
            $this->session->set_flashdata('error', 'Data tidak ditemukan.');
            redirect('Scrap_In');
        }
        $data['title'] = sprintf(lang_line('title_si_view'), $data['data']['header']->doc_no);
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('scrap_in/view_detail', $data); 
        $this->load->view('templates/footer');
    }

    public function confirm($id) {
        // if (!check_access('scrap_in', 'confirm')) redirect('denied');
        $result = $this->Scrap_In_model->confirm_scrap_in($id, $this->session->userdata('user_id'));
        if ($result['status']) { $this->session->set_flashdata('success', $result['message']); } 
        else { $this->session->set_flashdata('error', $result['message']); }
        redirect('Scrap_In/view/' . $id);
    }

    public function unconfirm($id) {
        // if (!check_access('scrap_in', 'edit')) redirect('denied');
        $result = $this->Scrap_In_model->unconfirm_scrap_in($id);
        if ($result['status']) { $this->session->set_flashdata('success', $result['message']); } 
        else { $this->session->set_flashdata('error', $result['message']); }
        redirect('Scrap_In/view/' . $id);
    }
    
    public function delete($id) {
        // if (!check_access('scrap_in', 'delete')) redirect('denied');
        $result = $this->Scrap_In_model->delete_draft($id);
        if ($result['status']) { $this->session->set_flashdata('success', $result['message']); } 
        else { $this->session->set_flashdata('error', $result['message']); }
        redirect('Scrap_In');
    }

    // --- FUNGSI BARU: EXPORT PDF ---
    public function export_pdf($id) {
        $data['data'] = $this->Scrap_In_model->get_by_id($id);
        if (empty($data['data'])) {
            $this->session->set_flashdata('error', 'Data not found.');
            redirect('Scrap_In');
        }
        
        $data['title'] = sprintf(lang_line('title_si_proof'), $data['data']['header']->doc_no);
        $html = $this->load->view('scrap_in/print_pdf', $data, TRUE);
        
        // Configure Mpdf with writable temp directory
        $temp_dir = '/tmp/mingbao_mpdf';
        if (!is_dir($temp_dir)) {
            @mkdir($temp_dir, 0777, true);
        }
        
        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'tempDir' => $temp_dir
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output('ScrapIn-' . $data['data']['header']->doc_no . '.pdf', 'I'); 
    }

    // --- FUNGSI BARU: EXPORT EXCEL ---
     public function export_excel() {
        $filters = ['search' => $this->input->get('search')];
        $documents = $this->Scrap_In_model->get_all($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'No. Dokumen');
        $sheet->setCellValue('B1', 'Tgl Dokumen');
        $sheet->setCellValue('C1', 'Dibuat Oleh');
        $sheet->setCellValue('D1', 'Status');
        $sheet->setCellValue('E1', 'Catatan');

        $row = 2;
        foreach ($documents as $doc) {
            $sheet->setCellValue('A' . $row, $doc->doc_no);
            $sheet->setCellValue('B' . $row, date('d-m-Y', strtotime($doc->doc_date)));
            $sheet->setCellValue('C' . $row, $doc->created_by_name);
            $sheet->setCellValue('D' . $row, $doc->status);
            $sheet->setCellValue('E' . $row, $doc->notes);
            $row++;
        }

        foreach(range('A','E') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $writer = new Xlsx($spreadsheet);
        $filename = 'scrap_in_export_' . date('YmdHis') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    // --- FUNGSI BARU: Ambil detail scrap by ID (untuk model) ---
    // Anda perlu membuat fungsi ini di model
    

}