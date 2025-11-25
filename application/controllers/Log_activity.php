<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Log_activity extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->database();
        $this->load->library('pagination');
    }

    public function index() {
        // Cek akses
        check_access('log_activity', 'view'); // Pastikan Anda menambahkan permission ini di 'roleaccess'

        $data['title'] = 'Log Aktivitas Pengguna';
        
        // Konfigurasi Pagination
        $config['base_url'] = base_url('log_activity/index');
        $config['total_rows'] = $this->db->count_all('user_logs');
        $config['per_page'] = 25; // Jumlah log per halaman
        $config['uri_segment'] = 3;

        // Styling pagination (opsional, sesuaikan dengan Bootstrap 4)
        $config['full_tag_open'] = '<ul class="pagination justify-content-end">';
        $config['full_tag_close'] = '</ul>';
        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="page-item active"><a class="page-link" href="#">';
        $config['cur_tag_close'] = '</a></li>';
        $config['prev_tag_open'] = '<li class="page-item">';
        $config['prev_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li class="page-item">';
        $config['next_tag_close'] = '</li>';
        $config['attributes'] = array('class' => 'page-link');

        $this->pagination->initialize($config);
        $page = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;

        // Ambil data log dengan limit dan offset
        $data['logs'] = $this->db
            ->order_by('log_time', 'DESC')
            ->limit($config['per_page'], $page)
            ->get('user_logs')
            ->result();
        
        $data['pagination'] = $this->pagination->create_links();

        // Load views
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('log_activity/index', $data); // Buat file view ini
        $this->load->view('templates/footer');
    }

    public function clear() {
        // Cek akses
        check_access('log_activity', 'delete'); // Izin khusus untuk hapus

        // Kosongkan tabel
        $this->db->truncate('user_logs');
        
        // Catat aksi pengosongan log (sebelum redirect)
        add_log('DELETE', 'Log Activity', 'Semua data log telah dikosongkan.');

        $this->session->set_flashdata('success', 'Semua log aktivitas telah berhasil dihapus.');
        redirect('log_activity');
    }
}