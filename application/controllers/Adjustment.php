<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Adjustment extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        
        // Allow AJAX requests to proceed (they will check session in individual methods)
        // Only redirect non-AJAX requests without session
        if (!$this->input->is_ajax_request() && !$this->session->userdata('username')) {
            redirect('auth');
        }
        
        $this->load->model('Adjustment_model', 'model');
        $this->load->library('form_validation');
        $this->load->helper('form');
        $this->load->helper('file');
        
        // Direktori upload untuk adjustment documents
        $this->upload_dir = FCPATH . 'uploads/adjustment_documents/';
    }

    // Halaman Utama: Daftar Dokumen Adjustment
    public function index()
    {
        $data['title'] = lang_line('title_adj_index');
        $data['adjustments'] = $this->model->get_all_headers();
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data); // <-- Ditambahkan
        $this->load->view('adjustment/index', $data);
        $this->load->view('templates/footer');
    }

    // Halaman Buat Dokumen Baru
    public function create()
    {
        $data['title'] = lang_line('title_adj_create');
        $data['doc_no'] = $this->model->get_new_doc_number(); 
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data); // <-- Ditambahkan
        $this->load->view('adjustment/create', $data); // <-- Memuat view create
        $this->load->view('templates/footer');
    }

    // Proses Simpan Dokumen (Status: Draft)
    public function store()
    {
        $this->form_validation->set_rules('doc_date', 'Tanggal Dokumen', 'required');
        $this->form_validation->set_rules('notes', 'Catatan', 'required');

        if (empty($this->input->post('details[0][item_type]'))) {
            $this->session->set_flashdata('error', 'Detail item tidak boleh kosong.');
            redirect('adjustment/create');
        }

        if ($this->form_validation->run() == FALSE) {
            // Muat ulang view create jika validasi gagal
            $data['title'] = lang_line('title_adj_create');
            $data['doc_no'] = $this->input->post('doc_no'); // Ambil dari post agar tidak hilang
            
            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('templates/topbar', $data); // <-- Ditambahkan
            $this->load->view('adjustment/create', $data);
            $this->load->view('templates/footer');
        } else {
            $grn_id_post = (int)$this->input->post('grn_id');
            $header = [
                'doc_no'         => $this->input->post('doc_no'),
                'doc_date'       => $this->input->post('doc_date'),
                'notes'          => $this->input->post('notes'),
                'grn_header_id'  => $grn_id_post > 0 ? $grn_id_post : null,
                'created_by'     => $this->session->userdata('user_id'),
                'status'         => 'Draft'
            ];
            $details = $this->input->post('details');

            $result = $this->model->insert_adjustment($header, $details);

            if ($result) {
                $this->session->set_flashdata('success', 'Dokumen adjustment berhasil disimpan sebagai Draft.');
                redirect('adjustment/view/' . $result);
            } else {
                $this->session->set_flashdata('error', 'Gagal menyimpan dokumen.');
                redirect('adjustment/create');
            }
        }
    }

    // Halaman Lihat Dokumen
    public function view($id)
    {
        $data['header'] = $this->model->get_header_by_id($id);
        if (!$data['header']) {
            show_404();
        }
        
        $data['title'] = sprintf(lang_line('title_adj_view'), $data['header']->doc_no);
        $data['details'] = $this->model->get_details_by_header_id($id);
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data); // <-- Ditambahkan
        $this->load->view('adjustment/view', $data);
        $this->load->view('templates/footer');
    }

    // Halaman Edit Dokumen (Hanya Draft)
    public function edit($id)
    {
        $header = $this->model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya dokumen Draft yang bisa di-edit.');
            redirect('adjustment/view/' . $id);
        }
        
        $data['title'] = sprintf(lang_line('title_adj_edit'), $header->doc_no);
        $data['header'] = $header;
        $data['details'] = $this->model->get_details_by_header_id($id);

        // Load info GRN yang sudah dikaitkan (untuk ditampilkan di Select2)
        $data['grn_info'] = null;
        if (!empty($header->grn_header_id)) {
            $data['grn_info'] = $this->model->get_grn_info_for_select($header->grn_header_id);
        }
        
        $data['form_data'] = [];
        foreach ($data['details'] as $i => $detail) {
            $data['form_data'][$i]['items'] = $this->model->get_item_list_by_type($detail->item_type);
            if($detail->item_type == 'material' && $detail->adjustment_type == 'OUT') {
                $data['form_data'][$i]['batches'] = $this->model->get_batches_for_item($detail->item_id, $detail->stock_on_hand_id);
            } else {
                $data['form_data'][$i]['batches'] = [];
            }
        }
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data); // <-- Ditambahkan
        $this->load->view('adjustment/edit', $data); // <-- Memuat view edit
        $this->load->view('templates/footer');
    }

    // Proses Update Dokumen (Hanya Draft)
    public function update($id)
    {
        $this->form_validation->set_rules('doc_date', 'Tanggal Dokumen', 'required');
        $this->form_validation->set_rules('notes', 'Catatan', 'required');
        
        if ($this->form_validation->run() == FALSE) {
            $this->edit($id); // Kembali ke form edit jika validasi gagal
        } else {
            $grn_id_upd = (int)$this->input->post('grn_id');
            $header_data = [
                'doc_date'      => $this->input->post('doc_date'),
                'notes'         => $this->input->post('notes'),
                'grn_header_id' => $grn_id_upd > 0 ? $grn_id_upd : null,
                'updated_at'    => date('Y-m-d H:i:s'),
                'updated_by'    => $this->session->userdata('user_id')
            ];
            $details_data = $this->input->post('details');
            
            $result = $this->model->update_adjustment($id, $header_data, $details_data);

            if ($result) {
                $this->session->set_flashdata('success', 'Dokumen Draft berhasil diperbarui.');
            } else {
                $this->session->set_flashdata('error', 'Gagal memperbarui dokumen. Pastikan status masih Draft.');
            }
            redirect('adjustment/view/' . $id);
        }
    }

    // Proses Delete Dokumen (Hanya Draft)
    public function delete($id)
    {
        $result = $this->model->delete_draft_adjustment($id);
        
        if ($result) {
            $this->session->set_flashdata('success', 'Dokumen Draft berhasil dihapus.');
            redirect('adjustment');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus dokumen. Hanya status Draft yang bisa dihapus.');
            redirect('adjustment/view/' . $id);
        }
    }

    // Proses Konfirmasi Dokumen (Update Stok)
    public function confirm($id)
    {
        $result = $this->model->confirm_adjustment($id, $this->session->userdata('user_id'));
        
        if ($result['status'] == TRUE) {
            $this->session->set_flashdata('success', 'Dokumen berhasil dikonfirmasi dan stok telah diperbarui.');
        } else {
            $this->session->set_flashdata('error', 'Gagal konfirmasi: ' . $result['message']);
        }
        redirect('adjustment/view/' . $id);
    }

    // Proses Batalkan Dokumen (Hanya jika masih Draft)
    public function cancel($id)
    {
        $result = $this->model->cancel_adjustment($id, $this->session->userdata('user_id'));
        
        if ($result) {
            $this->session->set_flashdata('success', 'Dokumen berhasil dibatalkan.');
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan dokumen. Hanya status Draft yang bisa dibatalkan.');
        }
        redirect('adjustment/view/' . $id);
    }

    /**
     * AJAX HELPERS
     */
    public function ajax_get_items()
    {
        $type = $this->input->get('type');
        $items = $this->model->get_item_list_by_type($type);
        echo json_encode($items);
    }

    public function ajax_get_batches()
    {
        $item_id = $this->input->get('item_id');
        $batches = $this->model->get_batches_for_item($item_id);
        echo json_encode($batches);
    }

    /**
     * Get adjustment details for modal display
     */
    public function ajax_get_details()
    {
        if (!$this->input->is_ajax_request()) { 
            show_error('Forbidden', 403); 
            return; 
        }

        $adjustment_id = (int)$this->input->get('adjustment_id');
        if (empty($adjustment_id)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => FALSE, 'data' => []]);
            return;
        }

        $details = $this->model->get_details_by_header_id($adjustment_id);
        header('Content-Type: application/json');
        echo json_encode(['success' => TRUE, 'data' => $details]);
    }

    /**
     * ===== METODE BARU: AJAX GRN LOOKUP (SELECT2) =====
     */
    public function ajax_search_grn()
    {
        if (!$this->input->is_ajax_request()) { show_error('Forbidden', 403); return; }
        
        $search = $this->input->get('q', TRUE);
        $results = $this->model->search_grn_for_adjustment($search);
        
        $data = [];
        foreach ($results as $grn) {
            $data[] = [
                'id' => $grn->id,
                'text' => $grn->grn_number . ' (' . $grn->nama_supplier . ')'
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode(['results' => $data]);
    }

    /**
     * Ambil detail items dari GRN yang dipilih (untuk ditampilkan di form)
     */
    public function ajax_get_grn_details()
    {
        if (!$this->input->is_ajax_request()) { show_error('Forbidden', 403); return; }
        
        $grn_id = (int)$this->input->get('grn_id');
        if (empty($grn_id)) {
            echo json_encode(['success' => FALSE, 'message' => 'GRN ID tidak valid']);
            return;
        }
        
        $details = $this->model->get_grn_details_for_adjustment($grn_id);
        echo json_encode(['success' => TRUE, 'data' => $details]);
    }

    /**
     * Test AJAX session - for debugging
     */
    public function test_session()
    {
        header('Content-Type: application/json');
        echo json_encode([
            'session_id' => $this->session->session_id,
            'username' => $this->session->userdata('username'),
            'user_id' => $this->session->userdata('user_id'),
            'is_ajax' => $this->input->is_ajax_request()
        ]);
    }

    /**
     * ===== FITUR UPLOAD DOKUMEN =====
     */
    public function upload_document($adjustment_id = 0)
    {
        // Check if user is logged in (AJAX requests must have valid session)
        if (!$this->session->userdata('username')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => FALSE, 'message' => 'Session expired. Please login again.']);
            return;
        }
        
        if (!$this->input->is_ajax_request()) { 
            show_error('Forbidden', 403); 
            return; 
        }
        
        $adjustment_id = (int)$adjustment_id;
        log_message('DEBUG', 'Upload document called with ID: ' . $adjustment_id);
        
        if (empty($adjustment_id)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => FALSE, 'message' => 'Adjustment ID tidak valid']);
            return;
        }

        // Cek apakah adjustment ada
        $header = $this->model->get_header_by_id($adjustment_id);
        if (!$header) {
            header('Content-Type: application/json');
            echo json_encode(['success' => FALSE, 'message' => 'Adjustment tidak ditemukan']);
            return;
        }

        // Konfigurasi upload
        $config = [
            'upload_path' => $this->upload_dir,
            'allowed_types' => 'pdf|jpg|jpeg|png|gif|doc|docx|xlsx|xls',
            'max_size' => 10240, // 10MB
            'encrypt_name' => TRUE
        ];

        log_message('DEBUG', 'Upload config: ' . json_encode($config));

        $this->load->library('upload', $config);
        $this->lang->load('upload', 'english');

        if (!$this->upload->do_upload('document')) {
            $error = $this->upload->display_errors();
            log_message('ERROR', 'Upload failed: ' . $error);
            header('Content-Type: application/json');
            echo json_encode(['success' => FALSE, 'message' => $error]);
            return;
        }

        $upload_data = $this->upload->data();
        $description = $this->input->post('description', TRUE);

        log_message('DEBUG', 'File uploaded: ' . $upload_data['full_path']);

        // Simpan data dokumen ke database
        $result = $this->model->save_document(
            $adjustment_id,
            $upload_data['orig_name'],
            $upload_data['full_path'],
            $upload_data['file_type'],
            $upload_data['file_size'],
            $this->session->userdata('user_id'),
            $description
        );

        header('Content-Type: application/json');
        if ($result) {
            log_message('DEBUG', 'Document saved to DB with ID: ' . $result);
            echo json_encode(['success' => TRUE, 'message' => 'Dokumen berhasil diupload', 'document_id' => $result]);
        } else {
            log_message('ERROR', 'Failed to save document to database');
            echo json_encode(['success' => FALSE, 'message' => 'Gagal menyimpan data dokumen']);
        }
    }

    /**
     * Tampilkan daftar dokumen yang sudah diupload
     */
    public function ajax_get_documents($adjustment_id = 0)
    {
        if (!$this->input->is_ajax_request()) { show_error('Forbidden', 403); return; }
        
        $adjustment_id = (int)$adjustment_id;
        if (empty($adjustment_id)) {
            echo json_encode(['success' => FALSE, 'data' => []]);
            return;
        }

        $documents = $this->model->get_adjustment_documents($adjustment_id);
        echo json_encode(['success' => TRUE, 'data' => $documents]);
    }

    /**
     * Download/View dokumen
     */
    public function view_document($document_id = 0)
    {
        // Check session for document access (but don't redirect, just show 404)
        if (!$this->session->userdata('username')) {
            show_404();
            return;
        }
        
        $document_id = (int)$document_id;
        if (empty($document_id)) {
            show_404();
            return;
        }

        $document = $this->model->get_document_by_id($document_id);
        if (!$document || !file_exists($document->file_path)) {
            show_404();
            return;
        }

        // Bypass CodeIgniter output untuk file serving
        $this->output->set_header('Content-Type: ' . $document->file_type);
        $this->output->set_header('Content-Disposition: inline; filename="' . $document->file_name . '"');
        $this->output->set_header('Content-Length: ' . filesize($document->file_path));
        $this->output->set_output(file_get_contents($document->file_path));
    }

    /**
     * Hapus dokumen
     */
    public function delete_document($document_id = 0)
    {
        if (!$this->input->is_ajax_request()) { show_error('Forbidden', 403); return; }
        
        $document_id = (int)$document_id;
        if (empty($document_id)) {
            echo json_encode(['success' => FALSE, 'message' => 'Document ID tidak valid']);
            return;
        }

        $document = $this->model->get_document_by_id($document_id);
        if (!$document) {
            echo json_encode(['success' => FALSE, 'message' => 'Dokumen tidak ditemukan']);
            return;
        }

        // Cek permission: hanya user yang upload atau admin yang bisa delete
        $user_id = $this->session->userdata('user_id');
        if ($document->uploaded_by != $user_id && !in_array($this->session->userdata('role'), ['admin', 'manager'])) {
            echo json_encode(['success' => FALSE, 'message' => 'Anda tidak memiliki izin menghapus dokumen ini']);
            return;
        }

        if ($this->model->delete_document($document_id)) {
            echo json_encode(['success' => TRUE, 'message' => 'Dokumen berhasil dihapus']);
        } else {
            echo json_encode(['success' => FALSE, 'message' => 'Gagal menghapus dokumen']);
        }
    }
}