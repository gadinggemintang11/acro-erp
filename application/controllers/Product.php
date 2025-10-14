<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Product_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data = $this->data; 
        $data['title'] = 'Master Barang Jadi & SFG';
        $data['content'] = 'product/index';
        $data['products'] = $this->Product_model->getAll();
        $this->load->view('layouts/main_template', $data);
    }

    public function add()
    {
        $data = $this->data; 
        $data['title'] = 'Tambah Produk Baru';
        $data['content'] = 'product/add_form';
        $this->load->view('layouts/main_template', $data);
    }

    public function save()
    {
        $post = $this->input->post();
        
        // --- LOGIKA AUTO-GENERATE KODE ---
        $type_prefix = ($post['product_type'] == 'Barang Jadi') ? 'FG' : 'SFG';
        $category_prefix = strtoupper($post['product_category']); // Misal: 'SH'
        $year = date('y');
        $prefix = $type_prefix . '-' . $category_prefix . '-' . $year . '-';

        $last_product = $this->Product_model->getLastByPrefix($prefix);
        
        $new_num = $last_product ? (int)substr($last_product['product_code'], -5) + 1 : 1;
        $sequence = str_pad($new_num, 5, '0', STR_PAD_LEFT);
        $product_code = $prefix . $sequence;
        // --- END LOGIKA ---
        
        $data_to_save = [
            'product_code' => $product_code,
            'product_name' => $post['product_name'],
            'product_type' => $post['product_type'],
            'product_category' => $category_prefix,
            'unit' => $post['unit'],
            'created_at' => date('Y-m-d H:i:s')
        ];

            $this->Product_model->save($data_to_save);
            add_log("Menambah produk baru dengan kode: " . $product_code);
            $this->session->set_flashdata('success', 'Produk baru berhasil dibuat dengan kode: <strong>' . $product_code . '</strong>');
            redirect('product');
    }

    public function edit($id = null)
    {
        
        if (!$id) redirect('product');
        $data = $this->data; 
        $data['title'] = 'Edit Produk';
        $data['content'] = 'product/edit_form';
        $data['product'] = $this->Product_model->getById($id);
        if (!$data['product']) show_404();
        $this->load->view('layouts/main_template', $data);
    }

    public function update()
    {
        $post = $this->input->post();
        $id = $post['id'];
        $data_to_update = [
            'product_name' => $post['product_name'],
            'product_type' => $post['product_type'],
            'product_category' => strtoupper($post['product_category']),
            'unit' => $post['unit'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

            $this->Product_model->update($data_to_update, $id);
            $product = $this->Product_model->getById($id); // Ambil data setelah update
            add_log("Mengubah data produk: " . $product['product_code']);
            $this->session->set_flashdata('success', 'Data produk berhasil diperbarui.');
            redirect('product');
    }

    public function delete($id = null) {
        if (!$id) redirect('product');
        $product = $this->Product_model->getById($id); // Ambil data dulu
        if ($this->Product_model->delete($id)) {
            add_log("Menghapus produk: " . $product['product_code']);
            $this->session->set_flashdata('success', 'Data produk berhasil dihapus.');
        }
        redirect('product');
    }
}