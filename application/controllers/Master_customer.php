<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_customer extends MY_Controller {
    protected $module_name = 'Master Pelanggan';

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Customer_model');
        // ✅ Load Commoncode_model untuk data dinamis
        $this->load->model('Commoncode_model');
        $this->load->helper(['form', 'url']);
    }

    public function index() {
        // ✅ KEAMANAN: Check akses view
        if (!check_access('master_customer', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $data = [
            'title'     => 'Master Pelanggan',
            'customers' => $this->Customer_model->get_all(),
            // ✅ DINAMIS: Mengambil data jenis pelanggan dari common code
            'jenis_list' => $this->Commoncode_model->get_by_group('CUSTOMER_TYPE') // Pastikan grup ini ada
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('customer/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        // ✅ KEAMANAN: Check akses add
        if (!check_access('master_customer', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        $jenis = $this->input->post('jenis');
        $data = [
            'kode_customer' => $this->Customer_model->generate_kode($jenis),
            'nama_customer' => $this->input->post('nama_customer'),
            'jenis'         => $jenis,
            'npwp'          => $this->input->post('npwp'),
            'alamat'        => $this->input->post('alamat'),
            'telepon'       => $this->input->post('telepon'),
            'email'         => $this->input->post('email'),
            'status'        => 'active',
        ];

        $nama_customer = $this->input->post('nama_customer');

        if ($this->Customer_model->insert($data)) {
            $deskripsi = "Membuat pelanggan baru: {$nama_customer} (Kode: {$data['kode_customer']})";
            $this->_log('CREATE', $deskripsi);
            $this->session->set_flashdata('success', 'Data pelanggan berhasil ditambahkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan data!');
        }
        redirect('master_customer');
    }

    public function update($id) {
        // ✅ KEAMANAN: Check akses edit
        if (!check_access('master_customer', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }
        if (empty($id)) show_404(); // ✅ Pengecekan ID

        // Ambil data lama untuk log
        $old_data = $this->Customer_model->get_by_id($id);
        if (!$old_data) show_404();

        $data = [
            'nama_customer' => $this->input->post('nama_customer'),
            'jenis'         => $this->input->post('jenis'),
            'npwp'          => $this->input->post('npwp'),
            'alamat'        => $this->input->post('alamat'),
            'telepon'       => $this->input->post('telepon'),
            'email'         => $this->input->post('email'),
            'status'        => $this->input->post('status'),
        ];

        $nama_customer_baru = $this->input->post('nama_customer');

        if ($this->Customer_model->update($id, $data)) {
            $deskripsi = "Memperbarui data pelanggan: {$old_data->nama_customer} (ID: $id) menjadi {$nama_customer_baru}";
            $this->_log('UPDATE', $deskripsi);
            $this->session->set_flashdata('success', 'Data pelanggan berhasil diperbarui!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui data!');
        }
        redirect('master_customer');
    }

    public function delete($id) {
        // ✅ KEAMANAN: Check akses delete
        if (!check_access('master_customer', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }
        if (empty($id)) show_404(); // ✅ Pengecekan ID

        // Ambil data untuk log sebelum dihapus
        $customer_data = $this->Customer_model->get_by_id($id);

        if ($this->Customer_model->delete($id)) {
            $deskripsi = "Menonaktifkan pelanggan: " . ($customer_data->nama_customer ?? "ID: $id");
            $this->_log('UPDATE', $deskripsi); 
            // ✅ DIUBAH: Pesan disesuaikan dengan soft delete
            $this->session->set_flashdata('success', 'Data pelanggan berhasil dinonaktifkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan data!');
        }
        redirect('master_customer');
    }
}