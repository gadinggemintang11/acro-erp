<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Goods_receipt extends MY_Controller {

    public function __construct()
    {
        parent::__construct(); // Wajib untuk menjalankan keamanan
        // Memuat semua model yang dibutuhkan oleh controller ini
        $this->load->model('Goods_receipt_model');
        $this->load->model('Supplier_model');
        $this->load->model('Common_code_model');
    }

    public function index()
    {
        // 1. Tangkap input filter dari URL (method GET)
        $filters = [
            'start_date' => $this->input->get('start_date'),
            'end_date'   => $this->input->get('end_date'),
            'bc_type'    => $this->input->get('bc_type')
        ];

        // 2. Siapkan data untuk view
        $data = $this->data;
        $data['title'] = 'Penerimaan Barang';
        $data['content'] = 'goods_receipt/index';
        
        // 3. Panggil model dengan filter yang sudah ditangkap
        $data['receipts'] = $this->Goods_receipt_model->getAll($filters);

        // 4. Kirim data untuk dropdown filter dan nilai filter yang sedang aktif
        $data['bc_types'] = $this->Common_code_model->getByGroup('BC_TYPE');
        $data['filters'] = $filters;

        $this->load->view('layouts/main_template', $data);
    }

    public function add()
    {
        $data = $this->data;
        $data['title'] = 'Buat Penerimaan Barang Baru';
        $data['content'] = 'goods_receipt/add_form';
        $data['suppliers'] = $this->Supplier_model->getAll();
        $data['receipt_no'] = $this->Goods_receipt_model->generateReceiptNo();
        $data['bc_types'] = $this->Common_code_model->getByGroup('BC_TYPE');
        $this->load->view('layouts/main_template', $data);
    }

    public function save()
    {
        $post = $this->input->post();
        $data_to_save = [
            'receipt_no' => $post['receipt_no'],
            'pib_no' => $post['pib_no'],
            'bc_type' => $post['bc_type'],
            'document_date' => $post['document_date'],
            'receipt_date' => $post['receipt_date'],
            'supplier_id' => $post['supplier_id'],
            'receipt_basis' => $post['receipt_basis'],
            'created_by' => $this->session->userdata('user_id'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->Goods_receipt_model->save($data_to_save);
        add_log("Membuat dokumen penerimaan barang baru: " . $post['receipt_no']);
        $this->session->set_flashdata('success', 'Dokumen penerimaan berhasil dibuat. Silakan isi item barang.');
        redirect('goods_receipt');
    }

    public function edit($id = null)
    {
        if (!$id) redirect('goods_receipt');
        
        $data = $this->data;
        $data['title'] = 'Edit Dokumen Penerimaan';
        $data['content'] = 'goods_receipt/edit_form';
        $data['receipt'] = $this->Goods_receipt_model->getById($id);
        $data['suppliers'] = $this->Supplier_model->getAll();
        $data['bc_types'] = $this->Common_code_model->getByGroup('BC_TYPE');

        if (!$data['receipt']) show_404();
        
        $this->load->view('layouts/main_template', $data);
    }

    public function update()
    {
        $post = $this->input->post();
        $id = $post['id'];
        $data_to_update = [
            'pib_no' => $post['pib_no'],
            'bc_type' => $post['bc_type'],
            'document_date' => $post['document_date'],
            'receipt_date' => $post['receipt_date'],
            'supplier_id' => $post['supplier_id'],
            'receipt_basis' => $post['receipt_basis'],
        ];

        $this->Goods_receipt_model->update($data_to_update, $id);
        add_log("Mengubah dokumen penerimaan barang: " . $post['receipt_no']);
        $this->session->set_flashdata('success', 'Dokumen penerimaan berhasil diperbarui.');
        redirect('goods_receipt');
    }

    public function delete($id = null)
    {
        if (!$id) redirect('goods_receipt');

        $receipt = $this->Goods_receipt_model->getById($id);

        if ($this->Goods_receipt_model->delete($id)) {
            add_log("Menghapus dokumen penerimaan barang beserta detailnya: " . $receipt['receipt_no']);
            $this->session->set_flashdata('success', 'Dokumen penerimaan berhasil dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus dokumen penerimaan.');
        }
        redirect('goods_receipt');
    }
}