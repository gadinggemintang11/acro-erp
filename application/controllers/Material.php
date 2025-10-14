<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Material extends MY_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Material_model');
    }

    public function index()
    {
        $data = $this->data; // <-- TAMBAHKAN INI
        $data['title'] = 'Master Material';
        $data['content'] = 'material/index';
        $data['materials'] = $this->Material_model->getAll();
        $this->load->view('layouts/main_template', $data);
    }
    public function add()
    {
        $data = $this->data; // <-- TAMBAHKAN INI
        $data['title'] = 'Tambah Material Baru';
        $data['content'] = 'material/add_form';
        $this->load->view('layouts/main_template', $data);
    }
    public function save()
    {
        // 1. Ambil semua data dari form
        $category = $this->input->post('material_category');
        $sub_category_value = $this->input->post('sub_category');
        $item_name = $this->input->post('item_name');
        $type = $this->input->post('item_type');
        $unit = $this->input->post('unit');
        $hs_code = $this->input->post('hs_code');

        // 2. Logika untuk membuat kode barang otomatis
        $sub_category_codes = [
            'Kain' => 'A', 'Outsole' => 'B', 'Insole' => 'C'
        ];
        $category_prefix = ($category == 'Bahan Baku') ? 'BB' : 'BP';
        $sub_category_prefix = $sub_category_codes[$sub_category_value];
        $type_prefix = ($type == 'import') ? 'IMP' : 'LOK';
        $year = date('y');
        $prefix = $category_prefix . '-' . $sub_category_prefix . '-' . $type_prefix . '-' . $year . '-';

        // 3. Panggil model untuk mencari kode terakhir dan buat nomor urut
        $last_material = $this->Material_model->getLastByPrefix($prefix);
        $new_num = $last_material ? (int)substr($last_material['item_code'], -5) + 1 : 1;
        $sequence = str_pad($new_num, 5, '0', STR_PAD_LEFT);
        $item_code = $prefix . $sequence;

        // 4. Siapkan data untuk disimpan
        $data_to_save = [
            'item_code'         => $item_code,
            'item_name'         => $item_name,
            'material_category' => $category,
            'sub_category'      => $sub_category_value,
            'item_type'         => $type,
            'unit'              => $unit,
            'hs_code'           => $hs_code,
            'created_at'        => date('Y-m-d H:i:s')
        ];

        // 5. Panggil model untuk menyimpan data
        $this->Material_model->save($data_to_save);

        // 6. ## CATAT LOG AKTIVITAS ##
        add_log("Menambah material baru dengan kode: " . $item_code);

        // 7. Buat notifikasi sukses
        $this->session->set_flashdata('success', 'Material baru berhasil dibuat dengan kode: <strong>' . $item_code . '</strong>');

        // 8. Arahkan kembali pengguna (di paling akhir)
        redirect('material');
    }
    public function delete($id = null)
    {
        if (!$id) redirect('material');

        // Ambil data dulu untuk dicatat di log
        $material = $this->Material_model->getById($id);

        if ($this->Material_model->delete($id)) {
            // ## TAMBAHKAN LOG DI SINI ##
            add_log("Menghapus material dengan kode: " . $material['item_code']);
            $this->session->set_flashdata('success', 'Data material berhasil dihapus.');
        }
        redirect('material');
    }
    public function edit($id = null)
    {
        // ... (kode if dan lainnya) ...
        $data = $this->data; // <-- TAMBAHKAN INI
        $data['title'] = 'Edit Material';
        $data['content'] = 'material/edit_form';
        $data['material'] = $this->Material_model->getById($id);
        $this->load->view('layouts/main_template', $data);
    }
    public function update()
    {
        if ($this->input->post()) {
            $item_code = $this->input->post('item_code_display'); // Kita ambil kode dari form
            $this->Material_model->update();

            // ## TAMBAHKAN LOG DI SINI ##
            add_log("Mengubah data material dengan kode: " . $item_code);

            $this->session->set_flashdata('success', 'Data material berhasil diperbarui.');
        }
        redirect('material');
    }
}