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
        $this->load->model('Wo_model');
        $this->load->library(['form_validation', 'pagination']);
    }

    public function index() {
        if (!check_access('work_order', 'view')) { show_error('Tidak ada izin.', 403); }
        
        $filters = ['search' => $this->input->get('search')];
        
        // Pagination setup
        $per_page = 25;
        $offset = ($this->uri->segment(3)) ? (int)$this->uri->segment(3) : 0;
        $total_rows = $this->Wo_model->count_all($filters);
        
        $config = [
            'base_url' => base_url('work_order/index'),
            'total_rows' => $total_rows,
            'per_page' => $per_page,
            'uri_segment' => 3,
            'attributes' => ['class' => 'page-link btn-load'],
            'full_tag_open' => '<ul class="pagination justify-content-end">',
            'full_tag_close' => '</ul>',
            'cur_tag_open' => '<li class="page-item active"><span class="page-link">',
            'cur_tag_close' => '</span></li>',
            'num_tag_open' => '<li class="page-item">',
            'num_tag_close' => '</li>',
        ];
        $this->pagination->initialize($config);
        
        $data = [
            'title'      => lang_line('title_wo_index'),
            'wos'        => $this->Wo_model->get_all($filters, $per_page, $offset),
            'filters'    => $filters,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $total_rows
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
            'title'             => lang_line('title_wo_detail'),
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

    public function update_material() {
        if (!$this->input->is_ajax_request() || !check_access('work_order', 'edit')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $wo_id = $this->input->post('wo_id');
        $detail_id = $this->input->post('detail_id');
        $planned_qty = (float)$this->input->post('planned_qty');

        if (empty($wo_id) || empty($detail_id) || $planned_qty <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            return;
        }

        $data = ['planned_qty' => $planned_qty];
        $update_result = $this->Wo_model->update_material($detail_id, $data);

        header('Content-Type: application/json');
        if ($update_result) {
            echo json_encode(['success' => true, 'message' => 'Material quantity updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update material']);
        }
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
    /**
     * TIDAK DIPAKAI LAGI sejak modal "Pilih dari Daftar IPO" (2026-09-30):
     * form Buat WO dulu memakai select2 yang menembak endpoint ini sambil user
     * mengetik. Digantikan ajax_open_ipo_list() yang menampilkan seluruh IPO
     * Open beserta sisa qty & status BOM-nya. Dibiarkan ada (tidak dihapus)
     * kalau-kalau masih ada pemanggil di luar repo ini.
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

    /**
     * Isi modal "Pilih dari Daftar IPO" pada form Buat Work Order.
     * Mengembalikan SELURUH IPO Open (tidak dipotong limit seperti select2),
     * lengkap dengan qty yang sudah tertutup WO, sisa, dan ada/tidaknya BOM.
     */
    public function ajax_open_ipo_list() {
        header('Content-Type: application/json');

        if (!check_access('work_order', 'add')) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada izin.']);
            return;
        }

        $rows = $this->Wo_model->get_open_sales_contracts_for_wo([
            'search'    => trim((string) $this->input->get('search', TRUE)),
            'date_from' => $this->input->get('date_from') ?: null,
            'date_to'   => $this->input->get('date_to') ?: null,
        ]);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'            => (int) $r->id,
                'sc_number'     => $r->sc_number,
                'contract_date' => $r->contract_date,
                'customer_name' => $r->customer_name,
                'fg_code'       => $r->fg_code,
                'fg_name'       => $r->fg_name,
                'fg_unit'       => $r->fg_unit,
                'po_qty'        => (float) $r->po_qty,
                'qty_on_wo'     => (float) $r->qty_on_wo,
                'qty_left'      => (float) $r->qty_left,
                'wo_count'      => (int) $r->wo_count,
                'has_bom'       => ((int) $r->has_bom) > 0,
            ];
        }

        echo json_encode(['success' => true, 'rows' => $out]);
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

    /**
     * Isi modal "Load from BOM" di halaman detail Work Order.
     *
     * Dulu hanya mengembalikan angka yang sudah diformat untuk ditampilkan.
     * Sekarang mengirim angka mentah supaya qty-nya bisa diubah user, plus
     * stok tersedia dan qty yang sudah ada di WO ini.
     */
    public function ajax_get_bom_preview() {
        header('Content-Type: application/json');

        if (!check_access('work_order', 'view')) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada izin.']);
            return;
        }

        $wo_id       = (int) $this->input->get('wo_id');
        $fg_id       = (int) $this->input->get('fg_id');
        $planned_qty = (float) $this->input->get('planned_qty');

        $rows = $this->Wo_model->get_bom_rows_for_wo($fg_id, $planned_qty, $wo_id ?: null);

        if (empty($rows)) {
            echo json_encode([
                'success' => false,
                'message' => 'Tidak ada BOM aktif untuk Finished Good ini'
            ]);
            return;
        }

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'item_id'       => $r->component_item_id,
                'item_type'     => $r->component_item_type,
                'item_code'     => $r->item_code,
                'item_name'     => $r->item_name,
                'unit_measure'  => $r->unit_measure,
                'qty_per_fg'    => (float) $r->qty_per_fg,
                'suggested_qty' => (float) $r->suggested_qty,
                'already_qty'   => (float) $r->already_qty,
                'stock_qty'     => (float) $r->stock_qty,
            ];
        }

        echo json_encode([
            'success'     => true,
            'bom_count'   => count($out),
            'planned_qty' => $planned_qty,
            'materials'   => $out,
        ]);
    }

    /**
     * Simpan material yang DIPILIH di modal "Load from BOM".
     * POST: items[] => [{item_id, item_type, qty}, ...]
     *
     * Menggantikan ajax_auto_populate_materials() yang menarik seluruh BOM
     * sekaligus tanpa bisa dipilih. Komponen yang sudah ada di WO dilewati,
     * jadi modal boleh dibuka berkali-kali untuk menarik sisanya.
     */
    public function ajax_add_materials_from_bom() {
        header('Content-Type: application/json');

        if (!check_access('work_order', 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada izin.']);
            return;
        }

        $wo_id  = (int) $this->input->post('wo_id');
        $header = $this->Wo_model->get_header_by_id($wo_id);

        if (!$header || $header->status != 'Planned') {
            echo json_encode([
                'success' => false,
                'message' => 'Work Order tidak ditemukan atau status bukan Planned'
            ]);
            return;
        }

        $items = $this->input->post('items');
        if (empty($items) || !is_array($items)) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada material yang dipilih.']);
            return;
        }

        $res = $this->Wo_model->add_materials_from_bom($wo_id, $items);

        if ($res['inserted'] === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Tidak ada material yang ditambahkan (qty 0 atau semuanya sudah ada di WO ini).'
            ]);
            return;
        }

        $msg = $res['inserted'] . ' material ditambahkan dari BOM.';
        if ($res['skipped'] > 0) {
            $msg .= ' ' . $res['skipped'] . ' dilewati karena qty 0 atau sudah ada di WO ini.';
        }
        echo json_encode(['success' => true, 'message' => $msg]);
    }

    /**
     * ✅ NEW: AJAX endpoint untuk auto-populate materials dari BOM
     */
    public function ajax_auto_populate_materials() {
        if (!check_access('work_order', 'edit')) { 
            echo json_encode(['success' => false, 'message' => 'Tidak ada izin.']);
            return;
        }

        $wo_id = $this->input->post('wo_id');
        $header = $this->Wo_model->get_header_by_id($wo_id);

        if(!$header || $header->status != 'Planned') {
            echo json_encode([
                'success' => false, 
                'message' => 'Work Order tidak ditemukan atau status bukan Planned'
            ]);
            return;
        }

        // Cek apakah sudah ada material, jika ada tanya konfirmasi dulu
        $existing_materials = $this->Wo_model->get_planned_materials($wo_id);
        if(!empty($existing_materials)) {
            echo json_encode([
                'success' => false, 
                'message' => 'WO sudah memiliki material. Silakan hapus terlebih dahulu sebelum auto-populate.'
            ]);
            return;
        }

        // Auto-populate dari BOM
        if($this->Wo_model->auto_populate_materials($wo_id, $header->fg_id, $header->planned_qty)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Material berhasil diisi otomatis dari BOM'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Gagal melakukan auto-populate (pastikan ada BOM aktif untuk FG ini)'
            ]);
        }
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
        if (!check_access('work_order', 'confirm')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Wo_model->get_header_by_id($id);
        if (!$header || $header->status != 'Planned') { redirect('work_order/detail/' . $id); }
        
        $user_id = $this->session->userdata('user_id') ?? null;
        if ($this->Wo_model->update_status($id, 'In Progress', $user_id)) {
            $this->session->set_flashdata('success', 'Work Order dikonfirmasi, status In Progress.');
        } else { $this->session->set_flashdata('error', 'Gagal konfirmasi Work Order.'); }
        redirect('work_order/detail/' . $id);
    }

    public function unconfirm_wo($id) {
        if (!check_access('work_order', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Wo_model->get_header_by_id($id);
        if (!$header || $header->status != 'In Progress') { redirect('work_order/detail/' . $id); }
        
        $user_id = $this->session->userdata('user_id') ?? null;
        if ($this->Wo_model->update_status($id, 'Planned', $user_id)) {
            $this->session->set_flashdata('success', 'Work Order dibatalkan, status kembali ke Planned.');
        } else { $this->session->set_flashdata('error', 'Gagal batalkan Work Order.'); }
        redirect('work_order/detail/' . $id);
    }
    public function ajax_search_open_wo_for_return() {
        $term = $this->input->get('q');
        $work_orders = $this->Wo_model->search_open_work_orders($term); // Panggil fungsi model baru
        
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