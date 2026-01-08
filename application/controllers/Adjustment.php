<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Adjustment extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        $this->load->model('Adjustment_model', 'model');
        $this->load->library('form_validation');
        $this->load->helper('form');
    }

    // Halaman Utama: Daftar Dokumen Adjustment
    public function index()
    {
        $data['title'] = 'Penyesuaian Stok (Adjustment)';
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
        $data['title'] = 'Buat Dokumen Penyesuaian Stok';
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
            $data['title'] = 'Buat Dokumen Penyesuaian Stok';
            $data['doc_no'] = $this->input->post('doc_no'); // Ambil dari post agar tidak hilang
            
            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('templates/topbar', $data); // <-- Ditambahkan
            $this->load->view('adjustment/create', $data);
            $this->load->view('templates/footer');
        } else {
            $header = [
                'doc_no' => $this->input->post('doc_no'),
                'doc_date' => $this->input->post('doc_date'),
                'notes' => $this->input->post('notes'),
                'created_by' => $this->session->userdata('user_id'), 
                'status' => 'Draft'
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
        
        $data['title'] = 'View Penyesuaian Stok: ' . $data['header']->doc_no;
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
        
        $data['title'] = 'Edit Penyesuaian Stok: ' . $header->doc_no;
        $data['header'] = $header;
        $data['details'] = $this->model->get_details_by_header_id($id);
        
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
            $header_data = [
                'doc_date' => $this->input->post('doc_date'),
                'notes' => $this->input->post('notes'),
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $this->session->userdata('user_id')
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
}