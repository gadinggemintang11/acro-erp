<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Muat library Composer
require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class Subkon_order extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model([
            'Subkon_order_model', 
            'Supplier_model', 
            'Commoncode_model',
            'Stock_ledger_model' // Untuk cari item
        ]);
        $this->load->library(['pagination', 'form_validation']);
        $this->load->helper('url');
    }

    /**
     * Halaman Index (Daftar Kontrak Subkon) + Filter
     */
    public function index() {
        if (!check_access('subkon_order', 'view')) { show_error('Akses ditolak', 403); }

        $filters = [
            'start_date' => $this->input->get('start_date') ?: date('Y-m-01'),
            'end_date'   => $this->input->get('end_date') ?: date('Y-m-t'),
            'status'     => $this->input->get('status'),
            'search'     => $this->input->get('search')
        ];

        $config['base_url'] = base_url('subkon_order/index');
        $config['total_rows'] = $this->Subkon_order_model->count_all($filters);
        $config['per_page'] = 25;
        $config['reuse_query_string'] = TRUE;
        $config['use_page_numbers'] = TRUE;
        
        $config['full_tag_open'] = '<nav><ul class="pagination pagination-sm justify-content-end">';
        $config['full_tag_close'] = '</ul></nav>';
        $config['first_link'] = 'First'; $config['first_tag_open'] = '<li class="page-item">'; $config['first_tag_close'] = '</li>';
        $config['last_link'] = 'Last'; $config['last_tag_open'] = '<li class="page-item">'; $config['last_tag_close'] = '</li>';
        $config['next_link'] = '&raquo;'; $config['next_tag_open'] = '<li class="page-item">'; $config['next_tag_close'] = '</li>';
        $config['prev_link'] = '&laquo;'; $config['prev_tag_open'] = '<li class="page-item">'; $config['prev_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="page-item active"><a class="page-link" href="#">'; $config['cur_tag_close'] = '</a></li>';
        $config['num_tag_open'] = '<li class="page-item">'; $config['num_tag_close'] = '</li>';
        $config['attributes'] = array('class' => 'page-link');
        
        $this->pagination->initialize($config);
        $page = ($this->uri->segment(3)) ? (int)$this->uri->segment(3) : 1;
        $offset = ($page > 0) ? ($page - 1) * $config['per_page'] : 0;

        $data = [
            'title'       => 'Daftar Kontrak Subkon (Subkon Order)',
            'filters'     => $filters,
            'orders'      => $this->Subkon_order_model->get_all($filters, $config['per_page'], $offset),
            'pagination'  => $this->pagination->create_links(),
            'start_no'    => $offset + 1,
            'total_rows'  => $config['total_rows']
        ];

        $this->load_views('subkon_order/index', $data);
    }

    /**
     * Halaman Create (HANYA memuat view)
     */
    public function create() {
        if (!check_access('subkon_order', 'add')) { show_error('Akses ditolak', 403); }
        $data = [
            'title' => 'Buat Kontrak Subkon Baru'
        ];
        $this->load_views('subkon_order/create', $data);
    }

    /**
     * Menyimpan Kontrak Subkon secara Lengkap (Header, Item Hasil, dan BOM)
     */
    public function save_full_order() {
        if (!check_access('subkon_order', 'add')) { show_error('Akses ditolak', 403); }

        // 1. Validasi Dasar Header
        $this->form_validation->set_rules('supplier_id', 'Supplier', 'required|numeric');
        $this->form_validation->set_rules('order_date', 'Tanggal Order', 'required');
        
        if (empty($this->input->post('output'))) {
            $this->session->set_flashdata('error', 'Kontrak Gagal Disimpan: Minimal harus ada satu Item Hasil (Output).');
            redirect('subkon_order/create');
            return;
        }
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', 'Validasi Gagal pada Header Kontrak. ' . validation_errors());
            redirect('subkon_order/create');
            return;
        }

        $this->db->trans_start(); 

        $header_data = [
            'subkon_order_no' => $this->Subkon_order_model->generate_order_no(),
            'order_date'      => $this->input->post('order_date'),
            'supplier_id'     => $this->input->post('supplier_id'),
            'pajak_persen'    => (float) $this->input->post('pajak_persen'),
            'notes'           => $this->input->post('notes'),
            'status'          => 'Draft',
            'created_by'      => $this->session->userdata('user_id'),
            'total_jasa'      => 0 
        ];
        $order_id = $this->Subkon_order_model->insert_header($header_data);
        
        if (!$order_id) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Gagal menyimpan header.');
            redirect('subkon_order/create');
            return;
        }
        
        $output_items = $this->input->post('output'); 
        $bom_items_input = $this->input->post('bom');

        if (!empty($output_items)) {
            foreach ($output_items as $output_index => $output_data_row) {
                
                $output_item_full_id = $output_data_row['item_id_type'];
                list($item_id, $item_type) = explode('|', $output_item_full_id);
                $qty_ordered = (float) $output_data_row['qty'];
                $jasa_per_pcs = (float) $output_data_row['jasa_per_pcs'];
                $item_master = $this->Stock_ledger_model->get_item_master_detail($output_item_full_id);

                if(!$item_master) { 
                    $this->db->trans_rollback();
                    $this->session->set_flashdata('error', 'Gagal menyimpan, item master (hasil) tidak ditemukan.');
                    redirect('subkon_order/create');
                    return;
                }

                $output_data = [
                    'subkon_order_id' => $order_id,
                    'item_id'         => $item_id,
                    'item_type'       => $item_type,
                    'qty'             => $qty_ordered,
                    'satuan'          => $item_master->item_unit,
                    'jasa_per_pcs'    => $jasa_per_pcs,
                    'total_jasa_item' => $qty_ordered * $jasa_per_pcs
                ];
                $output_item_id = $this->Subkon_order_model->insert_output_item($output_data);
                
                if (!$output_item_id) {
                    $this->db->trans_rollback();
                    $this->session->set_flashdata('error', 'Gagal menyimpan Item Hasil ke database.');
                    redirect('subkon_order/create');
                    return;
                }

                // b. Simpan Material BOM yang terkait (jika ada)
                $bom_items_for_output = $bom_items_input[$output_index] ?? [];

                foreach ($bom_items_for_output as $bom_sub_index => $bom_data_row) {
                    if (empty($bom_data_row['item_id_type'])) continue;
                    
                    $bom_item_full_id = $bom_data_row['item_id_type'];
                    list($bom_item_id, $bom_item_type) = explode('|', $bom_item_full_id);
                    $qty_per_pcs_hasil = (float) $bom_data_row['qty_per_pcs'];
                    $bom_item_master = $this->Stock_ledger_model->get_item_master_detail($bom_item_full_id);
                    
                    if(!$bom_item_master) {
                        $this->db->trans_rollback();
                        $this->session->set_flashdata('error', 'Gagal menyimpan, item master (BOM) tidak ditemukan.');
                        redirect('subkon_order/create');
                        return;
                    }

                    $bom_data = [
                        'subkon_order_id' => $order_id,
                        'output_item_id'  => $output_item_id, 
                        'item_id'         => $bom_item_id,
                        'item_type'       => $bom_item_type,
                        'qty_per_pcs'     => $qty_per_pcs_hasil,
                        'total_qty'       => $qty_per_pcs_hasil * $qty_ordered,
                        'satuan'          => $bom_item_master->item_unit
                    ];
                    
                    if (!$this->Subkon_order_model->insert_bom_item($bom_data)) {
                        $this->db->trans_rollback();
                        $this->session->set_flashdata('error', 'Gagal menyimpan detail Material BOM.');
                        redirect('subkon_order/create');
                        return;
                    }
                }
            }
        }

        $this->Subkon_order_model->recalculate_total_jasa($order_id);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Transaksi GAGAL TOTAL. Cek log database.');
        } else {
            $this->session->set_flashdata('success', 'Kontrak Subkon ' . $header_data['subkon_order_no'] . ' berhasil disimpan lengkap.');
        }
        redirect('subkon_order/detail/' . $order_id);
    }

    /**
     * Halaman Detail (untuk VIEW)
     */
    public function detail($id) {
        if (!check_access('subkon_order', 'view')) { show_error('Akses ditolak', 403); }
        
        $header = $this->Subkon_order_model->get_header_by_id($id);
        if (!$header) { show_404(); return; }

        $output_items = $this->Subkon_order_model->get_output_details($id);
        
        $bom_items_grouped = [];
        if (!empty($output_items)) {
            $all_bom_items = $this->Subkon_order_model->get_bom_details($id); 
            foreach ($all_bom_items as $bom) {
                $bom_items_grouped[$bom->output_item_id][] = $bom;
            }
        }

        $data = [
            'title'             => 'Detail Kontrak Subkon: ' . $header->subkon_order_no,
            'header'            => $header,
            'output_items'      => $output_items,
            'bom_items_grouped' => $bom_items_grouped
        ];
        $this->load_views('subkon_order/detail', $data);
    }

    /**
     * Aksi Tambah Item BOM (di halaman detail)
     */
    public function add_bom_item($order_id) {
        if (!check_access('subkon_order', 'edit')) { show_error('Akses ditolak', 403); }
        
        $header = $this->Subkon_order_model->get_header_by_id($order_id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya kontrak status DRAFT yang bisa diubah.');
            redirect('subkon_order/detail/' . $order_id);
            return;
        }
        
        $output_item_id = $this->input->post('output_item_id');
        $item_id_type = $this->input->post('item_id_type');
        list($item_id, $item_type) = explode('|', $item_id_type);
        $qty_per_pcs = (float) $this->input->post('qty_per_pcs');
        
        $output_item = $this->Subkon_order_model->get_output_item_by_id($output_item_id); 
        if (!$output_item || $output_item->subkon_order_id != $order_id) { // Validasi keamanan
             $this->session->set_flashdata('error', 'Item Hasil tidak valid.');
             redirect('subkon_order/detail/' . $order_id);
             return;
        }

        $item_master = $this->Stock_ledger_model->get_item_master_detail($item_id_type);

        $data = [
            'subkon_order_id' => $order_id,
            'output_item_id'  => $output_item_id,
            'item_id'         => $item_id,
            'item_type'       => $item_type,
            'qty_per_pcs'     => $qty_per_pcs,
            'total_qty'       => $qty_per_pcs * $output_item->qty,
            'satuan'          => $item_master->item_unit
        ];

        if ($this->Subkon_order_model->insert_bom_item($data)) {
            $this->session->set_flashdata('success', 'Item BOM berhasil ditambahkan.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambah item BOM.');
        }
        redirect('subkon_order/detail/' . $order_id);
    }

    /**
     * Aksi Hapus Item BOM (di halaman detail)
     */
    public function delete_bom_item($order_id, $bom_id) {
        if (!check_access('subkon_order', 'delete')) { show_error('Akses ditolak', 403); }
        // TODO: Tambah validasi status DRAFT
        $this->Subkon_order_model->delete_bom_item($bom_id);
        $this->session->set_flashdata('success', 'Item BOM berhasil dihapus.');
        redirect('subkon_order/detail/' . $order_id);
    }
    
    /**
     * Aksi Hapus Item Hasil (di halaman detail)
     */
    public function delete_output_item($order_id, $output_id) {
        if (!check_access('subkon_order', 'delete')) { show_error('Akses ditolak', 403); }
        // TODO: Tambah validasi status DRAFT
        $this->Subkon_order_model->delete_output_item($output_id); 
        $this->Subkon_order_model->recalculate_total_jasa($order_id);
        $this->session->set_flashdata('success', 'Item Hasil (dan BOM terkait) berhasil dihapus.');
        redirect('subkon_order/detail/' . $order_id);
    }

    /**
     * Aksi Konfirmasi Kontrak
     */
    public function confirm($id) {
        if (!check_access('subkon_order', 'edit')) { show_error('Akses ditolak', 403); }
        
        $output_items = $this->Subkon_order_model->get_output_details($id);
        if (empty($output_items)) {
            $this->session->set_flashdata('error', 'Konfirmasi Gagal! Minimal harus ada 1 Item Hasil.');
            redirect('subkon_order/detail/' . $id);
            return;
        }

        if ($this->Subkon_order_model->update_status($id, 'Confirmed', $this->session->userdata('user_id'))) {
            $this->session->set_flashdata('success', 'Kontrak Subkon berhasil dikonfirmasi.');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengonfirmasi kontrak.');
        }
        redirect('subkon_order/detail/' . $id);
    }

    /**
     * Aksi Batal Konfirmasi Kontrak
     */
    public function unconfirm($id) {
        if (!check_access('subkon_order', 'edit')) { show_error('Akses ditolak', 403); }
        
        if ($this->Subkon_order_model->check_if_used($id)) {
            $this->session->set_flashdata('error', 'Batal Konfirmasi Gagal! Kontrak ini sudah digunakan di Surat Jalan (DO Subkon).');
            redirect('subkon_order/detail/' . $id);
            return;
        }

        if ($this->Subkon_order_model->update_status($id, 'Draft', $this->session->userdata('user_id'))) {
            $this->session->set_flashdata('success', 'Konfirmasi Kontrak Subkon berhasil dibatalkan.');
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan konfirmasi.');
        }
        redirect('subkon_order/detail/' . $id);
    }
    
    /**
     * Ekspor PDF per Dokumen
     */
    public function export_pdf($id) {
        if (!check_access('subkon_order', 'view')) { show_error('Akses ditolak', 403); }
        
        $header = $this->Subkon_order_model->get_header_by_id($id);
        if (!$header) { show_404(); return; }

        $output_items = $this->Subkon_order_model->get_output_details($id);
        $bom_items_grouped = [];
        if (!empty($output_items)) {
            $all_bom_items = $this->Subkon_order_model->get_bom_details($id); 
            foreach ($all_bom_items as $bom) {
                $bom_items_grouped[$bom->output_item_id][] = $bom;
            }
        }
        
        $data = [
            'title'             => 'Kontrak Subkon ' . $header->subkon_order_no,
            'header'            => $header,
            'output_items'      => $output_items,
            'bom_items_grouped' => $bom_items_grouped
        ];

        $html = $this->load->view('subkon_order/pdf_template', $data, true); 
        $mpdf = new Mpdf(['format' => 'A4']);
        $mpdf->SetTitle('Kontrak Subkon ' . $header->subkon_order_no);
        $mpdf->WriteHTML($html);
        $mpdf->Output($header->subkon_order_no . '.pdf', 'I');
    }

    /**
     * Ekspor Excel (Daftar List)
     */
    public function export_list_excel() {
        if (!check_access('subkon_order', 'view')) { show_error('Akses ditolak', 403); }
        
        $filters = [
            'start_date' => $this->input->get('start_date') ?: date('Y-m-01'),
            'end_date'   => $this->input->get('end_date') ?: date('Y-m-t'),
            'status'     => $this->input->get('status'),
            'search'     => $this->input->get('search')
        ];
        
        $list = $this->Subkon_order_model->get_all($filters, 0, 0); 

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Kontrak Subkon');
        
        $sheet->setCellValue('A1', 'Daftar Kontrak Subkon');
        $sheet->mergeCells('A1:E1'); 
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        $headers = ['No. Kontrak', 'Tanggal', 'Supplier', 'Total Jasa (IDR)', 'Status'];
        $sheet->fromArray($headers, NULL, 'A3');
        $sheet->getStyle('A3:E3')->getFont()->setBold(true);

        $rowNum = 4;
        foreach ($list as $row) {
            $sheet->fromArray([
                $row->subkon_order_no,
                $row->order_date,
                $row->nama_supplier,
                (float) $row->total_jasa,
                $row->status
            ], NULL, 'A'.$rowNum);
            $sheet->getStyle('D'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('B'.$rowNum)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            $rowNum++;
        }
        
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Daftar_Kontrak_Subkon_'.date('Ymd').'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'. $filename.'"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    // ======================================================
    // AJAX HELPERS (Dipanggil oleh Select2 di Views)
    // ======================================================

    public function ajax_search_suppliers() {
        if (!$this->input->is_ajax_request()) { exit('No direct script access allowed'); }
        $term = $this->input->get('q');
        $data = $this->Subkon_order_model->search_suppliers($term);
        echo json_encode(['results' => $data]);
    }

    public function ajax_search_hasil_item() {
        if (!$this->input->is_ajax_request()) { exit('No direct script access allowed'); }
        $term = $this->input->get('q');
        $data = $this->Subkon_order_model->search_hasil_items($term);
        
        $results = [];
        foreach ($data as $item) {
            $results[] = [
                'id' => $item->id . '|' . $item->item_type,
                'text' => $item->text
            ];
        }
        echo json_encode(['results' => $results]);
    }

    public function ajax_search_bom_item() {
        if (!$this->input->is_ajax_request()) { exit('No direct script access allowed'); }
        $term = $this->input->get('q');
        $data = $this->Subkon_order_model->search_bom_items($term);
        
        $results = [];
        foreach ($data as $item) {
            $results[] = [
                'id' => $item->id . '|' . $item->item_type,
                'text' => $item->text
            ];
        }
        echo json_encode(['results' => $results]);
    }

    /**
     * Helper Load Views
     */
    private function load_views($main_view, $data = []) {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($main_view, $data);
        $this->load->view('templates/footer');
    }

}