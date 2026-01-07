<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// require FCPATH . 'vendor/autoload.php'; // Hanya jika Anda pakai export
// use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// use Dompdf\Dompdf;
// use Dompdf\Options;

class Work_order extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Wo_model'); // Tidak perlu load Sc_model jika semua helper ada di Wo_model
        $this->load->library('form_validation');
    }

    public function index() {
        if (!check_access('work_order', 'view')) { show_error('Tidak ada izin.', 403); }
        
        $filters = ['search' => $this->input->get('search')];
        $data = [
            'title'   => 'Work Order (Production Plan)',
            'wos'     => $this->Wo_model->get_all($filters),
            'filters' => $filters
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('work_order/index', $data);
        $this->load->view('work_order/_modal_create_wo'); // Memuat modal di sini
        $this->load->view('templates/footer');
    }

    public function detail($id) {
        if (!check_access('work_order', 'view')) { show_error('Tidak ada izin.', 403); }
        if (empty($id)) show_404();

        $header = $this->Wo_model->get_header_by_id($id);
        if (!$header) show_404();

        $data = [
            'title'             => 'Detail Work Order',
            'header'            => $header,
            'planned_materials' => $this->Wo_model->get_planned_materials($id),
            'bom_components'    => $this->Wo_model->get_bom_components_by_fg($header->fg_id)
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('work_order/detail', $data);
        $this->load->view('templates/footer');
    }

    public function create() {
        if (!check_access('work_order', 'add')) { show_error('Tidak ada izin.', 403); }

        $this->form_validation->set_rules('sales_contract_id', 'Sales Contract', 'required|numeric');
        $this->form_validation->set_rules('planned_qty', 'Qty Rencana', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('planned_start_date', 'Tgl Mulai', 'required');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', 'Gagal membuat WO: ' . strip_tags(validation_errors()));
            redirect('work_order');
        }

        $sc_id = $this->input->post('sales_contract_id');
        $sc_details = $this->Wo_model->get_sc_details_for_wo($sc_id);

        if (!$sc_details) {
            $this->session->set_flashdata('error', 'Sales Contract tidak valid atau statusnya bukan Open.');
            redirect('work_order');
        }

        $data = [
            'wo_number'           => $this->Wo_model->generate_wo_number(),
            'sales_contract_id'   => $sc_id,
            'fg_id'               => $sc_details['fg_id'],
            'planned_qty'         => (float) $this->input->post('planned_qty'),
            'planned_start_date'  => date('Y-m-d', strtotime($this->input->post('planned_start_date'))),
            'planned_end_date'    => $this->input->post('planned_end_date') ? date('Y-m-d', strtotime($this->input->post('planned_end_date'))) : NULL,
            'remarks'             => $this->input->post('remarks'),
            'status'              => 'Planned',
            'created_by'          => $this->session->userdata('user_id') ?? null
        ];

        $new_wo_id = $this->Wo_model->insert_header($data);
        if ($new_wo_id) {
            $this->session->set_flashdata('success', 'Work Order berhasil dibuat. Silakan tambahkan rencana material.');
            redirect('work_order/detail/' . $new_wo_id);
        } else {
            $this->session->set_flashdata('error', 'Gagal menyimpan Work Order ke database.');
            redirect('work_order');
        }
    }

    public function add_material($wo_header_id) {
        if (!check_access('work_order', 'edit')) { show_error('Tidak ada izin.', 403); }
        
        $header = $this->Wo_model->get_header_by_id($wo_header_id);
        if (!$header || $header->status != 'Planned') {
            $this->session->set_flashdata('error', 'Material hanya bisa ditambahkan pada status Planned.');
            redirect('work_order/detail/' . $wo_header_id);
        }

        $item_data = explode('|', $this->input->post('component_item'));
        if (count($item_data) < 2) { 
            $this->session->set_flashdata('error', 'Komponen yang dipilih tidak valid.');
            redirect('work_order/detail/' . $wo_header_id);
        }

        $data = [
            'wo_header_id'        => $wo_header_id,
            'component_item_id'   => $item_data[0],
            'component_item_type' => $item_data[1],
            'planned_qty'         => (float) $this->input->post('planned_qty'),
        ];
        
        $this->Wo_model->insert_material($data);
        $this->session->set_flashdata('success', 'Material berhasil ditambahkan.');
        redirect('work_order/detail/' . $wo_header_id);
    }

    public function delete_material($wo_header_id, $detail_id) {
        if (!check_access('work_order', 'delete')) { show_error('Tidak ada izin.', 403); }
        $this->Wo_model->delete_material($detail_id);
        $this->session->set_flashdata('success', 'Material berhasil dihapus.');
        redirect('work_order/detail/' . $wo_header_id);
    }

    public function delete_wo($id) {
        if (!check_access('work_order', 'delete')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Wo_model->get_header_by_id($id);
        if (!$header || $header->status != 'Planned') { 
            $this->session->set_flashdata('error', 'Hanya WO status Planned yang bisa dihapus.');
            redirect('work_order');
        }
        if ($this->Wo_model->delete_header($id)) { 
            $this->session->set_flashdata('success', 'Work Order berhasil dihapus.');
        } else { 
            $this->session->set_flashdata('error', 'Gagal menghapus Work Order.');
        }
        redirect('work_order');
    }

    // === AJAX HELPERS ===

    /**
     * ✅ FIX: Mengganti nama fungsi AJAX dan memperbaiki teks output
     */
    public function ajax_search_open_sc() {
        $term = $this->input->get('q');
        $contracts = $this->Wo_model->search_open_sales_contracts($term);
        $results = [];
        foreach ($contracts as $sc) {
            $results[] = [
                'id' => $sc->id,
                'text' => htmlspecialchars($sc->sc_number . ' (' . $sc->fg_name . ')')
            ];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }

    public function ajax_get_sc_details() {
        $sc_id = $this->input->get('sc_id');
        $details = $this->Wo_model->get_sc_details_for_wo($sc_id);
        if ($details) {
            echo json_encode(['success' => true, 'details' => $details]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    public function ajax_search_components() {
        $term = $this->input->get('q');
        $items = $this->Wo_model->search_components($term);
        $results = [];
        foreach ($items as $item) {
            $results[] = ['id' => $item->id . '|' . $item->type, 'text' => htmlspecialchars($item->name . ' (' . $item->code . ')')];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }
    public function export_pdf($id) {
        if (!check_access('work_order', 'view')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Wo_model->get_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Wo_model->get_planned_materials($id);
        $data = ['header' => $header, 'details' => $details];
        
        // Baris ini memanggil view yang hilang:
        $html = $this->load->view('work_order/pdf_template', $data, true); 
        
        $options = new \Dompdf\Options(); $options->set('isHtml5ParserEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html); $dompdf->setPaper('A4', 'portrait'); $dompdf->render();
        $filename = "WO_".str_replace('/', '-', $header->wo_number).".pdf";
        $dompdf->stream($filename, ["Attachment" => 0]);
    }
    public function confirm_wo($id) {
        if (!check_access('work_order', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Wo_model->get_header_by_id($id);
        if (!$header || $header->status != 'Planned') { redirect('work_order/detail/' . $id); }
        
        $user_id = $this->session->userdata('user_id') ?? null;
        if ($this->Wo_model->update_status($id, 'In Progress', $user_id)) {
            $this->session->set_flashdata('success', 'Work Order dikonfirmasi, status In Progress.');
        } else { $this->session->set_flashdata('error', 'Gagal konfirmasi Work Order.'); }
        redirect('work_order/detail/' . $id);
    }
    public function ajax_search_open_wo_for_return() {
        $term = $this->input->get('q');
        $work_orders = $$this->Wo_model->search_open_work_orders($term); // Panggil fungsi model baru
        
        $results = [];
        foreach ($work_orders as $wo) {
            // Pastikan production_header_id ada
            if (!empty($wo->production_header_id)) {
                $results[] = [
                    'id' => $wo->production_header_id, // Kirim ID Produksi (bukan WO ID)
                    'text' => htmlspecialchars($wo->production_number . ' (WO: ' . $wo->wo_number . ' | Style: ' . $wo->fg_name . ')'),
                    'wo_header_id' => $wo->wo_header_id // Kirim WO ID (untuk disimpan di header retur)
                ];
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }

}