<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Pastikan autoload vendor aktif di config/config.php
require FCPATH . 'vendor/autoload.php'; 
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class Purchase_order extends MY_Controller { // Asumsi extends MY_Controller

    public function __construct() {
        parent::__construct();
        check_login(); // Pastikan helper/fungsi ini ada

        // Load model yang benar
        $this->load->model([
            'Po_model', // Nama model yang di-load
            'Supplier_model',
            'Commoncode_model', 
            'Sc_model' // Untuk data Sales Contract
        ]);

        $this->load->helper(['url', 'translation']);
        $this->load->library('user_agent');
        $this->load->library('session'); 
        $this->load->library('form_validation'); 
    }

    /**
     * Menampilkan daftar PO (Halaman Index)
     */
    public function index() {
        if (!check_access('purchase_order', 'view')) { show_error('Izin Ditolak.', 403); }
        
        // Ambil filter jika ada (contoh sederhana)
        $filters = ['search' => $this->input->get('search')];

        $data = [
            'title'            => 'Purchase Order (PO)',
            'pos'              => $this->Po_model->get_all_po($filters), // Kirim filter ke model jika perlu
            'suppliers'        => $this->Supplier_model->get_all(['status' => 'active']), // Ambil supplier aktif saja
            'shipping_modes'   => $this->Commoncode_model->get_by_group('SHIPPING_MODE'),
            'payment_modes'    => $this->Commoncode_model->get_by_group('PAYMENT_MODE'),
            'currencies'       => $this->Commoncode_model->get_by_group('CURRENCY'),
            'filters'          => $filters // Kirim filter ke view untuk ditampilkan di input search
        ];
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data); // Pastikan view topbar ada
        $this->load->view('purchase_order/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Menampilkan halaman detail PO
     */
    public function detail($id) {
        if (!check_access('purchase_order', 'view')) { show_error('Izin Ditolak.', 403); }
        $id = (int)$id; // Pastikan ID integer
        if ($id <= 0) show_404();
        
        $header = $this->Po_model->get_po_header_by_id($id); 
        if (!$header) show_404();

        $data = [
            'title'            => 'Detail Purchase Order',
            'header'           => $header,
            'details'          => $this->Po_model->get_po_details_by_id($id), 
            'shipping_modes'   => $this->Commoncode_model->get_by_group('SHIPPING_MODE'),
            'payment_modes'    => $this->Commoncode_model->get_by_group('PAYMENT_MODE'),
            'currencies'       => $this->Commoncode_model->get_by_group('CURRENCY'),
            'open_contracts'   => $this->Sc_model->get_open_contracts(), // Pastikan fungsi ini ada
             // Data untuk warna status badge
            'status_colors'    => [ 
                'Draft' => 'warning', 'Submitted' => 'info', 'Confirmed' => 'primary', 
                'Completed' => 'success', 'Cancelled' => 'danger', 'Rejected' => 'secondary'
            ]
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('purchase_order/detail', $data); 
        // Footer di-load oleh view detail.php
    }

    /**
     * Menyimpan header PO baru dari modal
     */
    public function add_header() {
        if (!check_access('purchase_order', 'add')) { show_error('Izin Ditolak.', 403); }
        if ($this->input->method() !== 'post') { show_error('Metode Ilegal.', 405); return; }

        // --- Perbaikan Validasi ---
        
        // 1. Bersihkan input kurs (exchange_rate) dari koma
        // Kita set $_POST agar form_validation membacanya
        $exchange_rate_input = $this->input->post('exchange_rate');
        if ($exchange_rate_input) {
            // Hapus semua karakter non-numerik KECUALI titik
            $_POST['exchange_rate'] = preg_replace('/[^\d.]/', '', $exchange_rate_input);
        }

        // 2. Set aturan validasi
        $this->form_validation->set_rules('supplier_id', 'Supplier', 'required|numeric|greater_than[0]', ['greater_than' => 'Supplier wajib dipilih.']);
        // 3. REVISI Validasi Tanggal (lebih sederhana, percaya pada input type="date")
        $this->form_validation->set_rules('po_date', 'Tanggal PO', 'required'); 
        $this->form_validation->set_rules('currency_code', 'Mata Uang', 'required');
        
        // 4. Aturan validasi kurs (sekarang divalidasi setelah dibersihkan)
        if ($this->input->post('currency_code') != 'IDR') {
            $this->form_validation->set_rules('exchange_rate', 'Kurs Tukar', 'required|numeric|greater_than[0]', ['greater_than' => 'Kurs Tukar harus lebih besar dari 0 jika mata uang bukan IDR.']);
        } else {
            $_POST['exchange_rate'] = 1; // Set 1 jika IDR
        }
        // --- Akhir Perbaikan Validasi ---


        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors('<p class="mb-1">', '</p>')); 
            redirect('purchase_order'); return;
        }

        $currency_code = $this->input->post('currency_code');
        // Ambil nilai yang sudah bersih dari $_POST
        $exchange_rate = (float)$this->input->post('exchange_rate');
        
        // Get PO number - either manual input or auto-generate
        $po_number = trim($this->input->post('po_number'));
        
        // DEBUG: Log semua input POST untuk melihat apakah remarks diterima
        $remarks_input = $this->input->post('remarks');
        log_message('debug', '[add_header] remarks input: ' . var_export($remarks_input, true));

        $data = [
            'po_number'           => $po_number, // Pass empty string or manual PO number to model
            'supplier_id'         => $this->input->post('supplier_id'),
            'po_date'             => $this->input->post('po_date'),
            'shipping_mode_code'  => $this->input->post('shipping_mode_code') ?: NULL, 
            'payment_mode_code'   => $this->input->post('payment_mode_code') ?: NULL, 
            'currency_code'       => $currency_code,
            'exchange_rate'       => $exchange_rate,
            'has_ppn'             => $this->input->post('has_ppn') ? 1 : 0,
            'remarks'             => !empty($remarks_input) ? trim($remarks_input) : '',
            'status'              => 'Draft', 
            'created_by'          => $this->session->userdata('user_id') ?? null
        ];
        
        // DEBUG: Log data yang akan disimpan
        log_message('debug', '[add_header] data to insert: ' . var_export($data, true));

        $new_po_id = $this->Po_model->insert_header($data); 
        if ($new_po_id) {
            $this->session->set_flashdata('success', 'Header PO berhasil dibuat. Silakan tambahkan item detail.');
            redirect('purchase_order/detail/' . $new_po_id);
        } else {
            $db_error = $this->db->error(); 
            log_message('error','Gagal insert header PO: '. print_r($db_error, true));
            if ($this->Po_model->check_po_number_exists($po_number) && !empty($po_number)) {
                $this->session->set_flashdata('error', 'Nomor PO sudah ada! Gunakan nomor yang berbeda atau biarkan kosong untuk auto-generate.');
            } else {
                $this->session->set_flashdata('error', 'Gagal membuat header PO! Terjadi kesalahan database.');
            }
            redirect('purchase_order');
        }
    }

    /**
     * Update header PO (hanya Draft)
     */
    public function update_header($id) {
        if (!check_access('purchase_order', 'edit')) { show_error('Izin Ditolak.', 403); }
        $id = (int)$id; if ($id <= 0) show_404();
        if ($this->input->method() !== 'post') { show_error('Metode Ilegal.', 405); return; }

        $header = $this->Po_model->get_po_header_by_id($id); 
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Header PO tidak bisa diedit (Status bukan Draft)!');
            redirect('purchase_order/detail/' . $id);
            return; 
        }

        // --- Validasi Input ---
        $this->form_validation->set_rules('po_date', 'Tanggal PO', 'required|callback_validate_date', ['validate_date' => 'Format Tanggal PO tidak valid.']);
        $this->form_validation->set_rules('currency_code', 'Mata Uang', 'required');
         if ($this->input->post('currency_code') != 'IDR') {
             $this->form_validation->set_rules('exchange_rate', 'Kurs Tukar', 'required|numeric|greater_than[0]',['greater_than' => 'Kurs Tukar harus lebih besar dari 0 jika mata uang bukan IDR.']);
        } else {
            $_POST['exchange_rate'] = 1;
        }
        if ($this->form_validation->run() == FALSE) {
             $this->session->set_flashdata('error', validation_errors('<p class="mb-1">', '</p>'));
             redirect('purchase_order/detail/' . $id); return;
        }
        // --- Akhir Validasi ---

        $currency_code = $this->input->post('currency_code');
        $exchange_rate_input = preg_replace('/[^\d.]/', '', $this->input->post('exchange_rate'));
        $exchange_rate = ($currency_code == 'IDR') ? 1 : (float)$exchange_rate_input;
        
        // DEBUG: Log semua input POST
        $remarks_input = $this->input->post('remarks');
        log_message('debug', '[update_header] PO ID: '.$id.' | remarks input: ' . var_export($remarks_input, true));
        
        $data = [
            'po_date'             => $this->input->post('po_date'),
            'shipping_mode_code'  => $this->input->post('shipping_mode_code') ?: NULL,
            'payment_mode_code'   => $this->input->post('payment_mode_code') ?: NULL,
            'currency_code'       => $currency_code,
            'exchange_rate'       => $exchange_rate,
            'has_ppn'             => $this->input->post('has_ppn') ? 1 : 0,
            'remarks'             => !empty($remarks_input) ? trim($remarks_input) : '',
        ];
        
        // DEBUG: Log data yang akan diupdate
        log_message('debug', '[update_header] data to update: ' . var_export($data, true));

        if ($this->Po_model->update_header($id, $data)) { 
            $this->session->set_flashdata('success', 'Header PO berhasil diperbarui!');
        } else {
             if ($this->db->affected_rows() == 0) { 
                 $this->session->set_flashdata('warning', 'Tidak ada perubahan data header.');
             } else {
                 $db_error = $this->db->error();
                 log_message('error','Gagal update header PO ID '.$id.': '. print_r($db_error, true));
                $this->session->set_flashdata('error', 'Gagal memperbarui header PO! Terjadi kesalahan database.');
             }
        }
        redirect('purchase_order/detail/' . $id);
    }

    /**
     * Hapus header PO (hanya Draft dan item kosong)
     */
    public function delete_header($id) {
        if (!check_access('purchase_order', 'delete')) { show_error('Izin Ditolak.', 403); }
        $id = (int)$id; if ($id <= 0) show_404();

        $header = $this->Po_model->get_po_header_by_id($id); 
        if (!$header) {
            $this->session->set_flashdata('error', 'PO tidak ditemukan!');
            redirect('purchase_order'); return;
        }
        if ($header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya PO status Draft yang bisa dihapus!');
            redirect('purchase_order/detail/' . $id); return;
        }
        
        if ($this->Po_model->delete_header($id)) { 
            $this->session->set_flashdata('success', 'PO berhasil dihapus!');
            redirect('purchase_order'); 
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus PO! Pastikan tidak ada item detail atau terjadi kesalahan database.');
            redirect('purchase_order/detail/' . $id);
        }
    }

    /**
     * AJAX: Mencari item untuk Select2 inline
     */
    public function search_items() {
        // Cek jika ini request AJAX (opsional tapi baik)
        // if (!$this->input->is_ajax_request()) {
        //     show_error('Akses langsung tidak diizinkan.', 403);
        //     return;
        // }
        
        $term = $this->input->get('q'); 
        $results_formatted = []; // Gunakan nama variabel berbeda untuk hasil format

        if ($term && strlen($term) >= 2) { 
            $items_from_model = $this->Po_model->search_all_items($term); // Panggil model
            
            // Format data untuk Select2
            foreach ($items_from_model as $item) {
                // Pastikan $item adalah object sebelum akses property
                if(is_object($item) && isset($item->item_type, $item->id, $item->name, $item->code)){
                     $results_formatted[] = [
                        'id'        => $item->item_type . '-' . $item->id, 
                        'text'      => $item->name . ' (' . $item->code . ') - [' . ucfirst($item->item_type) . ']', 
                        'code'      => $item->code,
                        'unit'      => $item->unit ?? '', // Ambil unit jika ada
                        'item_type' => $item->item_type 
                        // item_id_numeric tidak wajib di sini, bisa diambil dari 'id' di JS
                    ];
                } else {
                    log_message('error', '[search_items] Invalid item structure from model: ' . print_r($item, true));
                }
            }
        }

        // --- BAGIAN KRITIS ---
        // Bungkus array hasil format ke dalam object dengan key 'results'
        $output = ['results' => $results_formatted];

        // Tambahkan CSRF hash baru jika perlu (opsional, tergantung config)
        // $output[$this->security->get_csrf_token_name()] = $this->security->get_csrf_hash(); 

        // Set header dan output sebagai JSON
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($output)); 
        // Jangan gunakan exit setelah ini jika menggunakan $this->output
    }

     /**
     * Fungsi AJAX untuk menyimpan item baru dari baris inline
     */
    public function ajax_add_item($po_header_id) {
        // --- Output JSON & Exit Awal Jika Error ---
        $this->output->set_content_type('application/json');
        
        $po_header_id = (int)$po_header_id;
        if ($po_header_id <= 0) {
             echo json_encode(['status' => 'error', 'message' => 'Invalid PO ID.']); exit;
        }
        if ($this->input->method() !== 'post') { 
            echo json_encode(['status' => 'error', 'message' => 'Invalid method.']); exit;
        }
        // Tambahkan cek CSRF
        // if ($this->security->get_csrf_hash() != $this->input->post($this->security->get_csrf_token_name())) {
        //      echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']); exit;
        // }


        $header = $this->Po_model->get_po_header_by_id($po_header_id); 
        if (!$header || $header->status != 'Draft' /* || !check_access('purchase_order', 'edit') */ ) {
             echo json_encode(['status' => 'error', 'message' => 'PO tidak dalam status Draft atau Anda tidak punya akses.']); exit;
        }
        
        $this->form_validation->set_rules('item_id', 'Item', 'required');
        $this->form_validation->set_rules('qty', 'Quantity', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('base_price', 'Harga Satuan', 'required|numeric|greater_than_equal_to[0]');

        if ($this->form_validation->run() == FALSE) {
            echo json_encode(['status' => 'error', 'message' => validation_errors()]); exit;
        }

        $item_info = $this->Po_model->get_item_details($this->input->post('item_id')); 
        if (!$item_info) {
             echo json_encode(['status' => 'error', 'message' => 'Item tidak ditemukan.']); exit;
        }

        $base_price = (float)$this->input->post('base_price');
        $exchange_rate = (float)$header->exchange_rate ?: 1; 
        $local_price = $base_price * $exchange_rate;
        $qty = (float)$this->input->post('qty'); 

        $detail_data = [
            'po_header_id'      => $po_header_id,
            'item_id'           => $this->input->post('item_id'), // Akan dipecah di model
            'item_type'         => $item_info->item_type, 
            'qty'               => $qty,
            'base_price'        => $base_price,
            'local_price'       => $local_price,
            'total_local'       => $qty * $local_price, 
            'sales_contract_id' => $this->input->post('sales_contract_id') ?: NULL,
            'description'       => trim($this->input->post('description'))
        ];

        $insert_id = $this->Po_model->add_item($detail_data); 
        
        // Siapkan output JSON
        $output = [];
        if ($insert_id) {
            $output = ['status' => 'success', 'message' => 'Item berhasil ditambahkan. Halaman akan dimuat ulang.', 'detail_id' => $insert_id];
        } else {
             $error_msg = $this->db->error()['message'] ?? 'Gagal menyimpan item ke database.'; 
             $output = ['status' => 'error', 'message' => $error_msg];
        }
        // Tambahkan CSRF hash baru ke output
        $output[$this->security->get_csrf_token_name()] = $this->security->get_csrf_hash();
        echo json_encode($output); exit; // Exit setelah echo JSON
    }

    /**
     * Menghapus item (hanya Draft)
     */
    public function delete_item($po_id, $detail_id) {
        if (!check_access('purchase_order', 'edit')) { show_error('Izin Ditolak.', 403); }
        $po_id = (int)$po_id; $detail_id = (int)$detail_id; // Cast to int
        if ($po_id <= 0 || $detail_id <= 0) show_404();

        $header = $this->Po_model->get_po_header_by_id($po_id); 
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Item tidak bisa dihapus karena status PO bukan Draft!');
            redirect('purchase_order/detail/' . $po_id);
            return;
        }

        if ($this->Po_model->delete_item($detail_id)) { 
            $this->session->set_flashdata('success', 'Item berhasil dihapus dari PO.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus item!');
        }
        redirect('purchase_order/detail/' . $po_id);
    }

    // --- ALUR KERJA STATUS ---
    // Pastikan semua memanggil $this->Po_model->update_status(...)
    public function submit_po($id) { /* ... kode sama ... */ 
        if (!check_access('purchase_order', 'edit')) { show_error('Izin Ditolak.', 403); return; }
        if (empty($id) || !is_numeric($id)) { show_404(); return; }
        $header = $this->Po_model->get_po_header_by_id($id);
        $allowed_initial_statuses = ['Draft', NULL];
        if (!$header || !in_array($header->status, $allowed_initial_statuses, TRUE)) {
             $this->session->set_flashdata('warning', 'Hanya PO status Draft yang bisa disubmit!');
             redirect('purchase_order/detail/' . $id); return;
        }
        $details = $this->Po_model->get_po_details_by_id($id);
        if (empty($details)) {
             $this->session->set_flashdata('error', 'PO tidak bisa disubmit karena belum memiliki item detail!');
             redirect('purchase_order/detail/' . $id); return;
        }
        if ($this->Po_model->update_status($id, 'Submitted')) {
            $this->session->set_flashdata('success', 'Purchase Order berhasil disubmit.');
        } else {
            $this->session->set_flashdata('error', 'Gagal submit Purchase Order!');
        }
        redirect('purchase_order/detail/' . $id);
    }
    public function confirm_po($id) { /* ... kode sama ... */ 
        if (!check_access('purchase_order', 'edit')) { show_error('Izin Ditolak.', 403); return; }
        if (empty($id) || !is_numeric($id)) { show_404(); return; }
        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->status != 'Submitted') {
             $this->session->set_flashdata('warning', 'Hanya PO status Submitted yang bisa dikonfirmasi!');
             redirect('purchase_order/detail/' . $id); return;
        }
        $userId = $this->session->userdata('user_id') ?? null;
        if ($this->Po_model->update_status($id, 'Confirmed', $userId)) {
             $this->session->set_flashdata('success', 'Purchase Order berhasil dikonfirmasi (Approved).');
        } else {
             $this->session->set_flashdata('error', 'Gagal mengkonfirmasi Purchase Order!');
        }
        redirect('purchase_order/detail/' . $id);
    }
    public function unconfirm_po($id) { /* ... kode sama ... */ 
        if (!check_access('purchase_order', 'edit')) { show_error('Izin Ditolak.', 403); return; }
        if (empty($id) || !is_numeric($id)) { show_404(); return; }
        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->status != 'Confirmed') {
             $this->session->set_flashdata('warning', 'Hanya PO status Confirmed yang bisa dibatalkan konfirmasinya!');
             redirect('purchase_order/detail/' . $id); return;
        }
        $is_received = $this->Po_model->check_if_po_has_receipts($id);
        if ($is_received) {
             $this->session->set_flashdata('error', 'PO tidak bisa dibatalkan konfirmasinya karena sudah ada penerimaan barang!');
             redirect('purchase_order/detail/' . $id); return;
        }
        if ($this->Po_model->update_status($id, 'Submitted', NULL, TRUE)) { 
             $this->session->set_flashdata('success', 'Konfirmasi Purchase Order berhasil dibatalkan (Status: Submitted).');
        } else {
             $this->session->set_flashdata('error', 'Gagal membatalkan konfirmasi Purchase Order!');
        }
        redirect('purchase_order/detail/' . $id);
    }
    public function reject_po($id) { /* ... kode sama ... */ 
        if (!check_access('purchase_order', 'edit')) { show_error('Izin Ditolak.', 403); return; }
        if (empty($id) || !is_numeric($id)) { show_404(); return; }
        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->status != 'Submitted') {
             $this->session->set_flashdata('warning', 'Hanya PO status Submitted yang bisa dikembalikan ke Draft!');
             redirect('purchase_order/detail/' . $id); return;
        }
        if ($this->Po_model->update_status($id, 'Draft')) {
             $this->session->set_flashdata('success', 'Purchase Order berhasil dikembalikan ke Draft.');
        } else {
             $this->session->set_flashdata('error', 'Gagal mengembalikan PO ke Draft!');
        }
        redirect('purchase_order/detail/' . $id);
    }
     public function cancel_po($id) { /* ... kode sama ... */ 
        if (!check_access('purchase_order', 'delete')) { show_error('Izin Ditolak.', 403); return; }
        if (empty($id) || !is_numeric($id)) { show_404(); return; }
        $header = $this->Po_model->get_po_header_by_id($id);
        $allowed_statuses_to_cancel = ['Draft', 'Submitted', 'Confirmed'];
        if (!$header || !in_array($header->status, $allowed_statuses_to_cancel, TRUE)) {
             $this->session->set_flashdata('warning', 'Hanya PO status Draft, Submitted, atau Confirmed yang bisa dibatalkan!');
             redirect($this->agent->referrer() ?? 'purchase_order/detail/' . $id); return;
        }
        if ($header->status == 'Confirmed') {
             $is_received = $this->Po_model->check_if_po_has_receipts($id);
             if ($is_received) {
                 $this->session->set_flashdata('error', 'PO tidak bisa dibatalkan karena sudah ada penerimaan barang!');
                 redirect('purchase_order/detail/' . $id); return;
             }
        }
        if ($this->Po_model->update_status($id, 'Cancelled')) {
             $this->session->set_flashdata('success', 'Purchase Order berhasil dibatalkan (Cancelled).');
        } else {
             $this->session->set_flashdata('error', 'Gagal membatalkan Purchase Order!');
        }
        redirect('purchase_order/detail/' . $id);
    }

    // --- FUNGSI EXPORT (Asumsikan sudah benar) ---
    public function export_pdf($id) {
        if (!check_access('purchase_order', 'view')) { show_error('Izin Ditolak.', 403); }
        if (empty($id) || !is_numeric($id)) show_404();
        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Po_model->get_po_details_by_id($id);
        $data = ['header' => $header, 'details' => $details];

        try {
            // Create temp directory if it doesn't exist
            $temp_dir = APPPATH . 'cache/pdf_temp/';
            if (!is_dir($temp_dir)) {
                @mkdir($temp_dir, 0777, true);
                @chmod($temp_dir, 0777);
            }

            // Use mPDF with CJK support
            $mpdf = new Mpdf([
                'tempDir' => $temp_dir,
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'default_font_size' => 0,
                'default_font' => 'sun-extA',  // Use Sun font which supports Chinese characters
            ]);
            
            $html = $this->load->view('purchase_order/pdf_template', $data, true);
            $mpdf->WriteHTML($html);
            
            $filename = "PO_".str_replace(['/','\\'], '-', $header->po_number).".pdf";
            $mpdf->Output($filename, 'D'); // D = download
            exit;
        } catch (Exception $e) {
            log_message('error', 'PDF Export Error: ' . $e->getMessage());
            show_error('Gagal membuat PDF: ' . $e->getMessage(), 500);
        }
    }
    public function export_excel($id) { /* ... kode sama ... */ 
        if (!check_access('purchase_order', 'view')) { show_error('Izin Ditolak.', 403); }
        if (empty($id) || !is_numeric($id)) show_404();
        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Po_model->get_po_details_by_id($id);

        $spreadsheet = new Spreadsheet(); $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'PURCHASE ORDER'); $sheet->mergeCells('A1:G1'); 
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16); $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('A3', 'Nomor PO:'); $sheet->setCellValue('B3', $header->po_number);
        $sheet->setCellValue('E3', 'Tanggal:'); $sheet->setCellValue('F3', date('d-m-Y', strtotime($header->po_date)));
        $sheet->setCellValue('A4', 'Supplier:'); $sheet->setCellValue('B4', $header->nama_supplier);
        $sheet->setCellValue('E4', 'Currency:'); $sheet->setCellValue('F4', $header->currency_code . ($header->currency_code != 'IDR' ? ' (Kurs: '.number_format($header->exchange_rate, 4).')' : ''));
        $startRow = 7;
        $sheet->setCellValue('A'.$startRow, 'No'); $sheet->setCellValue('B'.$startRow, 'Kode Barang'); $sheet->setCellValue('C'.$startRow, 'Nama Barang'); $sheet->setCellValue('D'.$startRow, 'Satuan'); $sheet->setCellValue('E'.$startRow, 'Qty'); $sheet->setCellValue('F'.$startRow, 'Harga (' . $header->currency_code . ')'); $sheet->setCellValue('G'.$startRow, 'Total (' . $header->currency_code . ')');
        $sheet->getStyle('A'.$startRow.':G'.$startRow)->getFont()->setBold(true); $sheet->getStyle('A'.$startRow.':G'.$startRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); $sheet->getStyle('A'.$startRow.':G'.$startRow)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $rowNum = $startRow + 1; $no = 1; $grand_total_base = 0;
        foreach ($details as $d) {
             $total_base = $d->qty * $d->base_price;
             $sheet->setCellValue('A'.$rowNum, $no++); $sheet->setCellValue('B'.$rowNum, $d->item_code); $sheet->setCellValue('C'.$rowNum, $d->item_name); $sheet->setCellValue('D'.$rowNum, $d->item_unit); $sheet->setCellValue('E'.$rowNum, $d->qty); $sheet->setCellValue('F'.$rowNum, $d->base_price); $sheet->setCellValue('G'.$rowNum, $total_base);
             $sheet->getStyle('E'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00'); $sheet->getStyle('F'.$rowNum.':G'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00'); $grand_total_base += $total_base; $rowNum++;
        }
         $sheet->getStyle('A'.$startRow.':G'.($rowNum-1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
         $sheet->setCellValue('F'.$rowNum, 'Subtotal:'); $sheet->setCellValue('G'.$rowNum, $grand_total_base); $sheet->getStyle('F'.$rowNum.':G'.$rowNum)->getFont()->setBold(true); $sheet->getStyle('G'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00'); $rowNum++;
        if($header->has_ppn == 1){ 
             $ppn_amount = $grand_total_base * 0.11; 
             $sheet->setCellValue('F'.$rowNum, 'PPN 11%:'); $sheet->setCellValue('G'.$rowNum, $ppn_amount); $sheet->getStyle('F'.$rowNum.':G'.$rowNum)->getFont()->setBold(true); $sheet->getStyle('G'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00'); $rowNum++;
             $sheet->setCellValue('F'.$rowNum, 'Grand Total:'); $sheet->setCellValue('G'.$rowNum, $grand_total_base + $ppn_amount); $sheet->getStyle('F'.$rowNum.':G'.$rowNum)->getFont()->setBold(true)->setSize(12); $sheet->getStyle('G'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
        } else {
              $sheet->setCellValue('F'.$rowNum, 'Grand Total:'); $sheet->setCellValue('G'.$rowNum, $grand_total_base); $sheet->getStyle('F'.$rowNum.':G'.$rowNum)->getFont()->setBold(true)->setSize(12); $sheet->getStyle('G'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
        }
         $sheet->getStyle('A'.$rowNum.':G'.$rowNum)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        foreach (range('A', 'G') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }
        $writer = new Xlsx($spreadsheet); $filename = 'PO_'.str_replace(['/','\\'], '-', $header->po_number).'.xlsx'; 
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment;filename="'. $filename.'"'); header('Cache-Control: max-age=0');
        $writer->save('php://output'); exit;
    }

    /**
     * Export List PO dengan Filter
     */
    public function export_list_excel()
    {
        if (!check_access('purchase_order', 'view')) {
            show_error('Izin Ditolak.', 403);
        }

        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $status = $this->input->get('status');
        
        // Build query dengan filter
        $this->db->select('po_header.*, suppliers.nama_supplier');
        $this->db->from('po_header');
        $this->db->join('suppliers', 'po_header.supplier_id = suppliers.id', 'left');
        
        if (!empty($start_date)) {
            $this->db->where('po_header.po_date >=', $start_date);
        }
        if (!empty($end_date)) {
            $this->db->where('po_header.po_date <=', $end_date);
        }
        if (!empty($status)) {
            $this->db->where('po_header.status', $status);
        }
        
        $this->db->order_by('po_header.po_date', 'DESC');
        $pos = $this->db->get()->result();
        
        // Create Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Header
        $sheet->setCellValue('A1', 'DAFTAR PURCHASE ORDER');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Filter info
        $filterInfo = 'Periode: ';
        if ($start_date && $end_date) {
            $filterInfo .= date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date));
        } elseif ($start_date) {
            $filterInfo .= 'Dari ' . date('d/m/Y', strtotime($start_date));
        } elseif ($end_date) {
            $filterInfo .= 'Sampai ' . date('d/m/Y', strtotime($end_date));
        } else {
            $filterInfo .= 'Semua';
        }
        if ($status) {
            $filterInfo .= ' | Status: ' . $status;
        }
        
        $sheet->setCellValue('A2', $filterInfo);
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setItalic(true);
        
        // Column Headers
        $sheet->setCellValue('A4', 'No.');
        $sheet->setCellValue('B4', 'Nomor PO');
        $sheet->setCellValue('C4', 'Tanggal');
        $sheet->setCellValue('D4', 'Supplier');
        $sheet->setCellValue('E4', 'Currency');
        $sheet->setCellValue('F4', 'Total Amount');
        $sheet->setCellValue('G4', 'Status');
        
        $sheet->getStyle('A4:G4')->getFont()->setBold(true);
        $sheet->getStyle('A4:G4')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A4:G4')->getFont()->getColor()->setARGB('FFFFFFFF');
        
        // Data
        $row = 5;
        $no = 1;
        
        foreach ($pos as $po) {
            // Calculate total amount
            $this->db->select('SUM(qty * base_price) as total');
            $this->db->from('po_details');
            $this->db->where('po_header_id', $po->id);
            $total_query = $this->db->get()->row();
            $total_amount = $total_query ? $total_query->total : 0;
            
            if ($po->has_ppn == 1) {
                $total_amount = $total_amount * 1.11;
            }
            
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $po->po_number);
            $sheet->setCellValue('C' . $row, date('d/m/Y', strtotime($po->po_date)));
            $sheet->setCellValue('D' . $row, $po->nama_supplier);
            $sheet->setCellValue('E' . $row, $po->currency_code ?? 'IDR');
            $sheet->setCellValue('F' . $row, (float)$total_amount);
            $sheet->setCellValue('G' . $row, $po->status);
            
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
            
            $row++;
        }
        
        // Column widths
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(12);
        
        $filename = 'Daftar_PO_' . date('YmdHis') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * DIAGNOSTIK: Debug remarks field
     */
    public function debug_remarks() {
        // Hanya untuk development/debugging
        if (!check_access('purchase_order', 'view')) { show_error('Izin Ditolak.', 403); }
        
        $output = [
            'php_version'           => phpversion(),
            'max_input_vars'        => ini_get('max_input_vars'),
            'post_max_size'         => ini_get('post_max_size'),
            'upload_max_filesize'   => ini_get('upload_max_filesize'),
            'memory_limit'          => ini_get('memory_limit'),
            'php_sapi_name'         => php_sapi_name(),
            'os'                    => php_uname(),
            'post_keys'             => array_keys($_POST),
            'post_count'            => count($_POST),
            'remarks_exists'        => isset($_POST['remarks']),
            'remarks_value'         => $_POST['remarks'] ?? 'NOT SET',
            'remarks_length'        => strlen($_POST['remarks'] ?? ''),
            'db_last_error'         => $this->db->error(),
        ];
        
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    // --- END DIAGNOSTIK ---

} // End Class