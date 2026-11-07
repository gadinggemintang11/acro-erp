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
        $this->load->model('Ap_auto_model');
        $this->load->helper('form');
    }

    /** Simpan setting hanya untuk yang punya hak edit menu Setting Akun. */
    private function _require_edit()
    {
        if (!check_access('settingakun', 'edit')) {
            show_error('Anda tidak memiliki izin mengubah Setting Akun.', 403);
        }
    }

    public function index()
    {
        if (!check_access('settingakun', 'view')) {
            show_error('Anda tidak memiliki akses ke Setting Akun.', 403);
        }
        $data['title'] = lang_line('setting_akun_title', 'Setting Akun Jurnal Otomatis');

        // 1. Ambil setting yang sudah dikelompokkan
        $data['settings_grouped'] = $this->model->get_all_settings_grouped();

        // 2. Ambil daftar mata uang (untuk header tabel)
        $data['currencies'] = $this->model->get_currencies();

        // 3. Ambil semua akun COA postable
        $data['accounts'] = $this->JurnalUmum_model->get_postable_accounts();

        // 4. Pemetaan akun barang (jurnal hutang otomatis dari PO / Payment Request, 2026-11-06)
        $types = $this->Ap_auto_model->item_types();
        $categories = [];
        foreach (array_keys($types) as $t) $categories[$t] = $this->Ap_auto_model->category_options($t);
        $data['item_types']     = $types;
        $data['item_categories'] = $categories;
        $data['item_map']       = $this->Ap_auto_model->get_map_grouped();
        $data['can_edit']       = check_access('settingakun', 'edit');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/setting_akun/index', $data);
        $this->load->view('templates/footer');
    }

    public function save()
    {
        $this->_require_edit();
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

    /** Simpan akun default per jenis barang + per kategori. */
    public function save_item_map()
    {
        $this->_require_edit();
        $ok = $this->Ap_auto_model->save_map(
            $this->input->post('map_default') ?: [],
            $this->input->post('map_category') ?: [],
            (int) $this->session->userdata('user_id')
        );
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok
            ? 'Pemetaan akun barang berhasil disimpan.'
            : 'Gagal menyimpan pemetaan akun barang.');
        redirect('settingakun#item-map');
    }

    /** Tambah / ubah pengecualian akun untuk satu barang. */
    public function add_item_override()
    {
        $this->_require_edit();
        $res = $this->Ap_auto_model->save_item_override(
            (string) $this->input->post('item_type'),
            (int) $this->input->post('item_id'),
            (int) $this->input->post('account_id'),
            (int) $this->session->userdata('user_id')
        );
        $this->session->set_flashdata($res === true ? 'success' : 'error', $res === true
            ? 'Pengecualian akun barang disimpan.'
            : htmlspecialchars((string) $res));
        redirect('settingakun#item-map');
    }

    public function delete_item_override($id)
    {
        $this->_require_edit();
        if ($this->input->method() !== 'post') show_error('Metode tidak diizinkan.', 405);
        $this->Ap_auto_model->delete_map_row((int) $id);
        $this->session->set_flashdata('success', 'Pengecualian akun barang dihapus.');
        redirect('settingakun#item-map');
    }

    /** AJAX (select2): cari barang master per jenis. */
    public function search_items()
    {
        if (!check_access('settingakun', 'view')) show_error('Forbidden', 403);
        $type = (string) $this->input->get('type');
        $q = trim((string) $this->input->get('q'));
        $rows = $this->Ap_auto_model->search_items($type, $q);
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['id' => (int) $r->id, 'text' => $r->code . ' - ' . $r->name . ($r->kategori ? ' [' . $r->kategori . ']' : '')];
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode(['results' => $out]));
    }
}
