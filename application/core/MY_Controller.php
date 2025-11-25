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

}