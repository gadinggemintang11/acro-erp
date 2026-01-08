<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SettingAkun extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        $this->load->model('SettingAkun_model', 'model');
        $this->load->model('JurnalUmum_model'); 
        $this->load->helper('form');
    }

    public function index()
    {
        $data['title'] = lang_line('setting_akun_title', 'Setting Akun Jurnal Otomatis');
        
        // 1. Ambil setting yang sudah dikelompokkan
        $data['settings_grouped'] = $this->model->get_all_settings_grouped();
        
        // 2. Ambil daftar mata uang (untuk header tabel)
        $data['currencies'] = $this->model->get_currencies();
        
        // 3. Ambil semua akun COA postable
        $data['accounts'] = $this->JurnalUmum_model->get_postable_accounts();
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/setting_akun/index', $data);
        $this->load->view('templates/footer');
    }

    public function save()
    {
        $settings_data = $this->input->post('setting');
        
        if (empty($settings_data)) {
            $this->session->set_flashdata('error', 'Tidak ada data untuk disimpan.');
            redirect('settingakun');
        }

        $result = $this->model->save_settings($settings_data);

        if ($result) {
            $this->session->set_flashdata('success', 'Setting Akun berhasil diperbarui.');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui setting.');
        }
        
        redirect('settingakun');
    }
}
