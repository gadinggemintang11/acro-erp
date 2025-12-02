<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ini adalah Controller Induk (Master Controller)
 * Semua controller Anda akan extend ke sini, bukan ke CI_Controller
 */
class MY_Controller extends CI_Controller {

    /**
     * Nama modul yang akan dicatat di log.
     * Override (timpa) properti ini di setiap controller anak.
     * @var string
     */
    protected $module_name = 'Undefined Module';

    public function __construct() {
        parent::__construct();
        
        // Load helper log secara otomatis untuk semua controller
        $this->load->helper('log_helper');
        
        // Setup multi-language
        $this->_setup_language();
    }

    /**
     * Fungsi wrapper untuk logging yang "lebih cerdas".
     * Fungsi ini akan otomatis mengambil nama modul dari properti $module_name.
     *
     * @param string $action      Aksi (CREATE, UPDATE, DELETE, APPROVE, etc.)
     * @param string $description Deskripsi detail dari aksi
     */
    protected function _log($action, $description) {
        
        // Kita tidak perlu memanggil helper `add_log()` secara manual lagi.
        // Kita panggil fungsi global `add_log()` dari helper.
        add_log($action, $this->module_name, $description);
    }

    /**
     * Setup bahasa berdasarkan session atau cookie
     */
    private function _setup_language() {
        // Cek apakah ada request untuk ganti bahasa
        if ($this->input->get('lang')) {
            $lang = $this->input->get('lang');
            $this->session->set_userdata('site_lang', $lang);
            
            // Set cookie untuk 30 hari
            setcookie('site_lang', $lang, time() + (86400 * 30), '/');
        }
        
        // Ambil bahasa dari session atau cookie atau default
        $lang = $this->session->userdata('site_lang');
        if (!$lang) {
            $lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : 'indonesian';
        }
        
        // Validasi bahasa yang tersedia
        $available_langs = ['indonesian', 'english', 'chinese'];
        if (!in_array($lang, $available_langs)) {
            $lang = 'indonesian';
        }
        
        // Set bahasa CodeIgniter
        $this->config->set_item('language', $lang);
        $this->lang->load('app', $lang);
    }

    /**
     * Helper function untuk translate text
     * 
     * @param string $key Language key
     * @param string $default Default text jika key tidak ditemukan
     * @return string Translated text
     */
    protected function lang($key, $default = '') {
        $text = $this->lang->line($key);
        return $text ? $text : ($default ? $default : $key);
    }

}