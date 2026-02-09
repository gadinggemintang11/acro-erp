<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class Production_process extends MY_Controller {

    protected $module_name = 'Production Process';

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->library('user_agent');

        // Pastikan semua model di-load
        $this->load->model([
            'Production_model',
            'Mr_model',
            'Wo_model',
            'Scrap_model',
            'FinishedGoods_model' // Pastikan model ini ada dan punya fungsi get_finished_goods_list()
        ]);

        $this->load->helper('url');
        $this->load->library('session'); // Load session untuk flashdata
    }

    // Halaman index (daftar proses produksi)
    public function index() {
        if (!check_access('production_process', 'view')) { show_error('Tidak ada izin.', 403); return; }

        $filters = ['search' => $this->input->get('search')];
        $data = [
            'title'     => 'Proses Produksi',
            'processes' => $this->Production_model->get_all($filters),
            'filters'   => $filters
        ];

        $this->_load_views('production_process/index', $data);
    }

    // Fungsi AJAX
    public function ajax_get_wo_details() {
        header('Content-Type: application/json'); // Set header JSON
        $wo_id = $this->input->get('wo_id');
        if (!$wo_id || !is_numeric($wo_id)) { echo json_encode(['success' => false, 'message' => 'Invalid WO ID']); return; }
        $wo_details = $this->Production_model->get_wo_details_for_prod($wo_id);
        echo json_encode($wo_details ? ['success' => true, 'details' => $wo_details] : ['success' => false, 'message' => 'WO Details not found']);
    }

    public function search_open_wos() {
        header('Content-Type: application/json');
        $term = $this->input->get('q');
        $wos = $this->Production_model->search_open_wos($term);
        $results = [];
        foreach ($wos as $wo) {
            $results[] = ['id' => $wo->id, 'text' => htmlspecialchars($wo->wo_number . ' - ' . $wo->fg_name)];
        }
        echo json_encode(['results' => $results]);
    }

    // Fungsi search terpisah
    public function search_semifinished() {
        header('Content-Type: application/json');
        $term = $this->input->get('q');
        $items = $this->Production_model->search_semifinished_goods($term);
        $results = [];
        foreach ($items as $item) {
            $results[] = ['id' => $item->id . '|' . $item->type, 'text' => htmlspecialchars($item->name . ' (' . $item->code . ')')];
        }
        echo json_encode(['results' => $results]);
    }

    public function search_scrap() {
        header('Content-Type: application/json');
        $term = $this->input->get('q');
        $items = $this->Production_model->search_scrap_items($term);
        $results = [];
        foreach ($items as $item) {
            $results[] = ['id' => $item->id . '|' . $item->type, 'text' => htmlspecialchars($item->name . ' (' . $item->code . ')')];
        }
        echo json_encode(['results' => $results]);
    }

    public function search_all_outputs() {
        header('Content-Type: application/json');
        $term = $this->input->get('q');
        $semifinished = $this->Production_model->search_semifinished_goods($term);
        $scraps = $this->Production_model->search_scrap_items($term);
        $results = [
            ['text' => '--- Semi-Finished Goods ---', 'children' => []],
            ['text' => '--- Scrap Items ---', 'children' => []]
        ];
        foreach ($semifinished as $item) {
            $results[0]['children'][] = ['id' => $item->id . '|' . $item->type, 'text' => htmlspecialchars($item->name . ' (' . $item->code . ')')];
        }
        foreach ($scraps as $item) {
            $results[1]['children'][] = ['id' => $item->id . '|' . $item->type, 'text' => htmlspecialchars($item->name . ' (' . $item->code . ')')];
        }
        echo json_encode(['results' => $results]);
    }


    // Membuat Header Produksi Baru
    public function create_header() {
        if (!check_access('production_process', 'add')) { show_error('Tidak ada izin.', 403); return; }

        $wo_id = $this->input->post('wo_header_id');
        $production_date = $this->input->post('production_date');

        // Validasi dasar
        if (empty($wo_id) || empty($production_date)) {
            $this->session->set_flashdata('error', 'Work Order dan Tanggal Produksi wajib diisi!');
            redirect('production_process');
            return;
        }

        $wo_details = $this->Production_model->get_wo_details_for_prod($wo_id);
        if (!$wo_details) {
            $this->session->set_flashdata('error', 'Work Order tidak valid atau tidak ditemukan!');
            redirect('production_process');
            return;
        }

        // Generate nomor produksi (ini harus cepat setelah index dibuat)
        $prod_number = $this->Production_model->generate_prod_number();

        $data = [
            'production_number' => $prod_number,
            'wo_header_id'      => $wo_id,
            'fg_id'             => $wo_details['fg_id'],
            'planned_qty'       => $wo_details['planned_qty'],
            'production_date'   => $production_date,
            'remarks'           => $this->input->post('remarks'),
            'status'            => 'Draft',
            'created_by'        => $this->session->userdata('user_id') ?? null
        ];

        // Insert header (model insert_header sekarang return false jika gagal)
        $new_prod_id = $this->Production_model->insert_header($data);

        if ($new_prod_id) {
            // --- LOGGING (CREATE) ---
            $wo_number = $wo_details['wo_number'] ?? 'N/A';
            $deskripsi = "Membuat Proses Produksi baru: {$prod_number} untuk WO: {$wo_number}";
            $this->_log('CREATE', $deskripsi);
            // --- END LOG ---
            log_message('info', 'Production Header created successfully. ID: ' . $new_prod_id . ' Number: ' . $prod_number);
            redirect('production_process/preparation/' . $new_prod_id);
        } else {
            log_message('error', 'Gagal membuat Production Header. DB Error: ' . print_r($this->db->error(), true) . ' Data: ' . json_encode($data));
            $this->session->set_flashdata('error', 'Gagal membuat Proses Produksi! Terjadi kesalahan database.');
            redirect('production_process');
        }
    }

    private function _show_stage_view($id, $current_stage, $previous_stage = null) {
        if (!check_access('production_process', 'view')) { show_error('Tidak ada izin.', 403); return; }
        if (empty($id) || !is_numeric($id)) { show_404(); return; }

        $header = $this->Production_model->get_header_by_id($id);
        if (!$header) { show_404(); return; }

        $data = [
            'title'           => 'Proses Produksi - ' . ucfirst($current_stage),
            'header'          => $header,
            'current_stage'   => $current_stage,
        ];

        // Data spesifik per stage
        if ($current_stage == 'preparation') {
            $data['available_batches'] = $this->Production_model->get_wip_materials_with_remaining($header->wo_header_id);
            $data['preparation_inputs'] = $this->Production_model->get_production_inputs($id, 'preparation');
            $data['preparation_outputs'] = $this->Production_model->get_production_outputs($id, 'preparation');
        } else {
            $data['available_previous_outputs'] = $this->Production_model->get_available_previous_stage_outputs($id, $current_stage, $previous_stage);
            $data['available_batches'] = $this->Production_model->get_wip_materials_with_remaining($header->wo_header_id); // Material tambahan
            $data['current_inputs'] = $this->Production_model->get_production_inputs($id, $current_stage);
            $data['current_outputs'] = $this->Production_model->get_production_outputs($id, $current_stage);
            if ($previous_stage) {
                $data['previous_stage_name'] = ucfirst($previous_stage);
            }
        }

        if ($current_stage == 'finishing') {
            $data['finished_goods_options'] = $this->FinishedGoods_model->get_finished_goods_list();
        }

        $this->_load_views('production_process/' . $current_stage . '_view', $data);
    }

    public function preparation($id) { $this->_show_stage_view($id, 'preparation'); }
    public function assembling($id) { $this->_show_stage_view($id, 'assembling', 'preparation'); }
    public function finishing($id)
    {
        // ================= VALIDASI AKSES =================
        if (!check_access('production_process', 'view')) {
            show_error('Tidak ada izin.', 403);
            return;
        }

        if (empty($id) || !is_numeric($id)) {
            show_404();
            return;
        }

        // ================= AMBIL DATA HEADER =================
        $header = $this->Production_model->get_header_by_id($id);
        if (!$header) {
            show_404();
            return;
        }

        // ================= AMBIL DATA FINISHING =================
        $data = [
            'title'                 => 'Proses Produksi - Finishing',
            'header'                => $header,
            'current_stage'         => 'finishing',
            'available_previous_outputs' => $this->Production_model->get_available_previous_stage_outputs($id, 'finishing', 'assembling'),
            'available_batches'     => $this->Production_model->get_wip_materials_with_remaining($header->wo_header_id),
            'current_inputs'        => $this->Production_model->get_production_inputs($id, 'finishing'),
            'current_outputs'       => $this->Production_model->get_production_outputs($id, 'finishing'),
            'finished_goods_options'=> $this->FinishedGoods_model->get_finished_goods_list()
        ];

        // ================= SINKRONISASI SIZE BREAKDOWN =================
        foreach ($data['current_outputs'] as &$out) {
            // Ambil dari tabel production_output_sizes
            $sizes = $this->db->get_where('production_output_sizes', ['output_id' => $out->id])->result();

            if (!empty($sizes)) {
                $breakdown = [];
                foreach ($sizes as $s) {
                    $breakdown[] = [
                        'size' => trim($s->size_label),
                        'qty'  => (float)$s->qty
                    ];
                }

                // Simpan sementara ke variabel untuk view
                $out->sizes = $breakdown;

                // Jika size_breakdown di DB kosong, isi otomatis (sinkron permanen)
                if (empty($out->size_breakdown)) {
                    $json = json_encode($breakdown);
                    $this->db->where('id', $out->id)->update('production_outputs', [
                        'size_breakdown' => $json
                    ]);
                    $out->size_breakdown = $json;
                }
            } else {
                $out->sizes = [];
            }
        }

        // ================= RENDER VIEW =================
        $this->_load_views('production_process/finishing_view', $data);
    }




    // ======================================================
    // FUNGSI SIMPAN GENERIC (INTERNAL)
    // ======================================================

    /**
     * Simpan Input dari Stage Sebelumnya (TIDAK buat ledger)
     */
    private function _save_stage_input_from_output($prod_id, $current_stage, $previous_stage) {
        $response = ['success' => false, 'message' => 'Terjadi kesalahan tidak diketahui.']; // Default response
        $is_ajax = $this->input->is_ajax_request(); // Cek apakah AJAX

        if (!check_access('production_process', 'edit')) {
            $response['message'] = 'Tidak ada izin.';
            $this->_send_response($response, $is_ajax, $current_stage, $prod_id, 403); return;
        }
        $header = $this->Production_model->get_header_by_id($prod_id);
        if (!$header || $header->status != 'Draft') {
            $response['message'] = 'Hanya status Draft yang bisa diubah.';
            $this->_send_response($response, $is_ajax, $current_stage, $prod_id); return;
        }

        // Validasi Input
        $source_output_id = $this->input->post('source_output_id');
        $item_id          = $this->input->post('item_id');
        $item_type        = $this->input->post('item_type');
        $qty_used         = filter_var($this->input->post('qty_used'), FILTER_VALIDATE_FLOAT);

        if (empty($source_output_id) || empty($item_id) || empty($item_type) || $qty_used === false || $qty_used <= 0) {
            $response['message'] = 'Data input dari '.ucfirst($previous_stage).' tidak lengkap atau Qty tidak valid.';
            $this->_send_response($response, $is_ajax, $current_stage, $prod_id); return;
        }

        // Validasi Stok Output Stage Sebelumnya
        $available_outputs = $this->Production_model->get_available_previous_stage_outputs($prod_id, $current_stage, $previous_stage);
        $true_available_qty = -1; $selected_output = null;
        foreach ($available_outputs as $out) { if ($out->output_id == $source_output_id) { $true_available_qty = (float)$out->remaining_qty; $selected_output = $out; break; } }

        if (!$selected_output || $selected_output->item_id != $item_id || $selected_output->item_type != $item_type) {
            $response['message'] = 'Data output '.ucfirst($previous_stage).' tidak cocok/ditemukan.';
            $this->_send_response($response, $is_ajax, $current_stage, $prod_id); return;
        }
        if ($qty_used > $true_available_qty + 0.00001) { // Toleransi float
            $response['message'] = 'Gagal, Qty ('.number_format($qty_used,4).') > Sisa Stok '.ucfirst($previous_stage).' ('.number_format($true_available_qty,4).').';
            $this->_send_response($response, $is_ajax, $current_stage, $prod_id); return;
        }

        // Data Siap Disimpan
        $data = [
           'production_header_id' => $prod_id, 'stage_name' => $current_stage, 'item_id' => $item_id, 'item_type' => $item_type,
           'source_output_id' => $source_output_id,
           'stock_on_hand_id' => NULL, // Bukan dari SOH
           'mr_detail_id' => NULL,
           'qty_used' => $qty_used,
           'notes' => strip_tags($this->input->post('notes')) // Sanitasi
        ];

        // Panggil Model (TIDAK buat ledger)
        if ($this->Production_model->save_material_usage($data)) {
            // --- LOGGING (UPDATE) ---
            $item_name = $selected_output->item_name ?? 'Item';
            $deskripsi = "Input {$item_name} (Qty: {$qty_used}) dari tahap {$previous_stage} ke {$current_stage} pada Produksi ID: {$prod_id}";
            $this->_log('UPDATE', $deskripsi);
            // --- END LOG ---
            $response = ['success' => true, 'message' => 'Pemakaian hasil '.ucfirst($previous_stage).' dicatat.'];
        } else {
            $response['message'] = 'Gagal menyimpan pemakaian hasil '.ucfirst($previous_stage).' (Database Error).';
             // Set status 500 jika error server
             if ($is_ajax) $this->output->set_status_header(500);
        }
        $this->_send_response($response, $is_ajax, $current_stage, $prod_id);
    }

     /**
      * Simpan Input Material Tambahan (MEMBUAT ledger 'wip_out')
      */
     private function _save_stage_additional_input($prod_id, $current_stage) {
         $response = ['success' => false, 'message' => 'Terjadi kesalahan tidak diketahui.'];
         $is_ajax = $this->input->is_ajax_request();

         if (!check_access('production_process', 'edit')) {
            $response['message'] = 'Tidak ada izin.';
            $this->_send_response($response, $is_ajax, $current_stage, $prod_id, 403); return;
         }
         $header = $this->Production_model->get_header_by_id($prod_id);
         if (!$header || $header->status != 'Draft') {
            $response['message'] = 'Hanya status Draft yang bisa diubah.';
            $this->_send_response($response, $is_ajax, $current_stage, $prod_id); return;
         }

         // Validasi Input
         $batch_id         = $this->input->post('batch_id'); // Format: item_id_item_type
         $item_id          = $this->input->post('item_id');
         $item_type        = $this->input->post('item_type');
         // Standardisasi koma menjadi titik untuk float validation
         $qty_used_raw     = str_replace(',', '.', $this->input->post('qty_used'));
         $qty_used         = filter_var($qty_used_raw, FILTER_VALIDATE_FLOAT);

         if (empty($batch_id)) {
             $response['message'] = 'Batch ID tidak boleh kosong.';
             $this->_send_response($response, $is_ajax, $current_stage, $prod_id); return;
         }
         if (empty($item_id)) {
             $response['message'] = 'ID Item tidak boleh kosong.';
             $this->_send_response($response, $is_ajax, $current_stage, $prod_id); return;
         }
         if (empty($item_type)) {
             $response['message'] = 'Tipe Item tidak boleh kosong.';
             $this->_send_response($response, $is_ajax, $current_stage, $prod_id); return;
         }
         if ($qty_used === false || $qty_used <= 0) {
             $response['message'] = 'Kuantitas tidak valid atau kurang dari sama dengan nol.';
             $this->_send_response($response, $is_ajax, $current_stage, $prod_id); return;
         }

         // Validasi Stok Batch
         $additional_batches = $this->Production_model->get_wip_materials_with_remaining($header->wo_header_id);
         $true_available_qty = -1; $selected_batch = null;
         foreach ($additional_batches as $batch) { 
             if ($batch->batch_id == $batch_id && $batch->item_id == $item_id && $batch->item_type == $item_type) { 
                 $true_available_qty = (float)$batch->remaining_qty; 
                 $selected_batch = $batch; 
                 break; 
             } 
         }

         if (!$selected_batch) {
             $response['message'] = 'Data material tambahan tidak ditemukan untuk batch ini.';
             $this->_send_response($response, $is_ajax, $current_stage, $prod_id); return;
         }
         if ($qty_used > $true_available_qty + 0.00001) { // Toleransi float
             $response['message'] = 'Gagal, Qty ('.$qty_used.') > Sisa Stok Material ('.$true_available_qty.').';
             $this->_send_response($response, $is_ajax, $current_stage, $prod_id); return;
         }

         // Data Siap Disimpan
         $data = [
             'production_header_id' => $prod_id, 
             'stage_name' => $current_stage,
             'item_id' => $item_id, 
             'item_type' => $item_type,
             'stock_on_hand_id' => NULL, // WIP material tidak punya stock_on_hand_id
             'source_output_id' => NULL,
             'mr_detail_id' => NULL,
             'qty_used' => $qty_used,
             'notes' => strip_tags($this->input->post('notes')),
             'wo_header_id' => $header->wo_header_id // Pass WO ID for ledger (will be extracted before insert)
         ];
         
         // BC info untuk ledger (TIDAK disimpan di production_inputs, hanya di stock_ledger)
         $data['_bc_type_code'] = !empty($selected_batch->bc_type_code) ? $selected_batch->bc_type_code : NULL;
         $data['_bc_number'] = !empty($selected_batch->bc_number) ? $selected_batch->bc_number : NULL;
         $data['_bc_date'] = !empty($selected_batch->bc_date) ? $selected_batch->bc_date : NULL;

         // Panggil Model (AKAN buat ledger 'wip_out')
         log_message('error', 'Controller calling save_material_usage with data: ' . json_encode($data));
         if ($this->Production_model->save_material_usage($data)) {
             // --- LOGGING (UPDATE) ---
             $item_name = $selected_batch->item_name ?? 'Material';
             $deskripsi = "Input material tambahan: {$item_name} (Qty: {$qty_used}) ke tahap {$current_stage} pada Produksi ID: {$prod_id}";
             $this->_log('UPDATE', $deskripsi);
             // --- END LOG ---
             $response = ['success' => true, 'message' => 'Pemakaian material tambahan dicatat.'];
         } else {
             // Ambil error dari log
             $db_error = $this->db->error();
             $error_msg = 'Database Error';
             if (!empty($db_error['message'])) {
                 $error_msg .= ': ' . $db_error['message'];
             }
             log_message('error', 'Controller: save_material_usage returned FALSE. DB Error: ' . json_encode($db_error));
             $response['message'] = 'Gagal menyimpan pemakaian material tambahan (' . $error_msg . ')';
             if ($is_ajax) $this->output->set_status_header(500);
         }
         $this->_send_response($response, $is_ajax, $current_stage, $prod_id);
     }

    /**
     * Simpan Output Stage (MEMBUAT ledger 'wip_in' HANYA jika semifinished)
     */
    private function _save_stage_output($prod_id, $current_stage, $allowed_output_types = ['semifinished', 'scrap']) {
        $response = ['success' => false, 'message' => 'Terjadi kesalahan tidak diketahui.'];
        $is_ajax = $this->input->is_ajax_request();

        if (!check_access('production_process', 'edit')) {
            $response['message'] = 'Tidak ada izin.';
            $this->_send_response($response, $is_ajax, $current_stage, $prod_id, 403); return;
        }
        $header = $this->Production_model->get_header_by_id($prod_id);
        if (!$header || $header->status != 'Draft') {
            $response['message'] = 'Hanya status Draft yang bisa diubah.';
            $this->_send_response($response, $is_ajax, $current_stage, $prod_id); return;
        }

        // Validasi Input
        $item_value = $this->input->post('item_id');
        $qty        = filter_var($this->input->post('qty'), FILTER_VALIDATE_FLOAT);
        $item_id = null; $item_type = null;

        if (empty($item_value) || $qty === false || $qty <= 0) {
            $response['message'] = 'Item atau Qty output tidak valid.';
            $this->_send_response($response, $is_ajax, $current_stage, $prod_id); return;
        }

        // Parse item_id dan item_type
        if (strpos($item_value, '|') !== false) {
            $item_data = explode('|', $item_value);
            if (count($item_data) == 2) { $item_id = $item_data[0]; $item_type = $item_data[1]; }
        } elseif (in_array('finished_goods', $allowed_output_types)) {
             $item_id = $item_value; $item_type = 'finished_goods';
        }

        // Validasi tipe output
        if ($item_id === null || $item_type === null || !in_array($item_type, $allowed_output_types)) {
            $response['message'] = 'Format item ID atau tipe output tidak valid/diizinkan.';
            $this->_send_response($response, $is_ajax, $current_stage, $prod_id); return;
        }

        // Data Siap Disimpan
        $data = [
            'production_header_id' => $prod_id, 'stage_name' => $current_stage,
            'item_id' => $item_id, 'item_type' => $item_type,
            'qty_produced' => $qty,  // Semua output disimpan di qty_produced
            'qty_scrap' => 0,        // qty_scrap hanya untuk referensi
            'received_by' => $this->session->userdata('user_id') ?? null,
            'received_at' => date('Y-m-d H:i:s')
        ];

        // Panggil Model (buat ledger HANYA jika semifinished)
        if ($this->Production_model->save_production_output($data)) {
            // --- LOGGING (UPDATE) ---
            $deskripsi = "Output tahap {$current_stage}: item tipe {$item_type} (Qty: {$qty}) pada Produksi ID: {$prod_id}";
            $this->_log('UPDATE', $deskripsi);
            // --- END LOG ---

            $response = ['success' => true, 'message' => 'Hasil output '.ucfirst($current_stage).' dicatat.'];
        } else {
            $response['message'] = 'Gagal mencatat hasil '.ucfirst($current_stage).' (Database Error).';
             if ($is_ajax) $this->output->set_status_header(500);
        }
        $this->_send_response($response, $is_ajax, $current_stage, $prod_id);
    }

    /**
     * Helper untuk mengirim respons (JSON atau Redirect+Flashdata)
     */
    private function _send_response($response, $is_ajax, $redirect_stage, $redirect_id, $http_code = 200) {
        if ($is_ajax) {
            $this->output
                 ->set_status_header($response['success'] ? $http_code : ($http_code == 200 ? 400 : $http_code)) // Set error code jika gagal
                 ->set_content_type('application/json')
                 ->set_output(json_encode($response));
        } else {
            $message_type = $response['success'] ? 'success' : 'error';
            $this->session->set_flashdata($message_type, $response['message']);
            redirect('production_process/'.$redirect_stage.'/' . $redirect_id);
        }
        // exit; // Exit tidak diperlukan karena redirect() atau set_output() sudah menghentikan script
    }

    // ======================================================
    // FUNGSI KHUSUS PER STAGE (Panggil fungsi generic)
    // ======================================================

    // --- Preparation (formerly Cutting) ---
    public function save_preparation_material_usage($prod_id) { $this->_save_stage_additional_input($prod_id, 'preparation'); }
    public function save_preparation_output($prod_id) { $this->_save_stage_output($prod_id, 'preparation', ['semifinished', 'scrap']); }

    // --- Assembling ---
    public function save_assembling_preparation_input($prod_id) { $this->_save_stage_input_from_output($prod_id, 'assembling', 'preparation'); }
    public function save_assembling_additional_input($prod_id) { $this->_save_stage_additional_input($prod_id, 'assembling'); }
    public function save_assembling_output($prod_id) { $this->_save_stage_output($prod_id, 'assembling', ['semifinished', 'scrap']); }

    // --- Finishing ---
    public function save_finishing_assembling_input($prod_id) { $this->_save_stage_input_from_output($prod_id, 'finishing', 'assembling'); }
    public function save_finishing_additional_input($prod_id) { $this->_save_stage_additional_input($prod_id, 'finishing'); }
    public function save_finishing_output($production_id)
    {
        if (!check_access('production_process', 'edit')) {
            show_error('Tidak ada izin.', 403);
            return;
        }

        $item_id_raw = $this->input->post('item_id');
        $qty_total   = (float)$this->input->post('qty');
        $sizes       = $this->input->post('size');
        $size_qtys   = $this->input->post('size_qty');

        if (empty($item_id_raw) || $qty_total <= 0) {
            $this->session->set_flashdata('error', 'Item atau jumlah output tidak valid.');
            redirect('production_process/finishing/' . $production_id);
            return;
        }

        // Parse ID dan tipe item
        $item_id = null;
        $item_type = null;
        if (strpos($item_id_raw, '|') !== false) {
            list($item_id, $item_type) = explode('|', $item_id_raw);
        } else {
            $item_id = $item_id_raw;
            $item_type = 'finished_goods';
        }

        // Susun JSON breakdown size
        $size_breakdown = [];
        if (!empty($sizes) && !empty($size_qtys)) {
            foreach ($sizes as $i => $label) {
                $qty_size = isset($size_qtys[$i]) ? (float)$size_qtys[$i] : 0;
                if (!empty($label) && $qty_size > 0) {
                    $size_breakdown[] = [
                        'size' => trim($label),
                        'qty'  => $qty_size
                    ];
                }
            }
        }

        // Simpan ke tabel utama (production_outputs)
        $data_output = [
            'production_header_id' => $production_id,
            'stage_name'           => 'finishing',
            'item_id'              => $item_id,
            'item_type'            => $item_type,
            'qty_produced'         => $qty_total,
            'qty_scrap'            => 0,
            'size_breakdown'       => !empty($size_breakdown) ? json_encode($size_breakdown) : null, // ✅ penting
            'created_at'           => date('Y-m-d H:i:s'),
            'received_by'          => $this->session->userdata('user_id') ?? null
        ];

        $this->db->insert('production_outputs', $data_output);
        $output_id = $this->db->insert_id();

        // Simpan juga ke tabel detail size
        if ($output_id && !empty($size_breakdown)) {
            foreach ($size_breakdown as $s) {
                $this->db->insert('production_output_sizes', [
                    'output_id'  => $output_id,
                    'size_label' => $s['size'],
                    'qty'        => $s['qty']
                ]);
            }
        }

        if ($output_id) {
            // --- LOGGING (UPDATE) ---
            $deskripsi = "Output Finished Goods (Qty: {$qty_total}) pada Produksi ID: {$production_id}";
            $this->_log('UPDATE', $deskripsi);
            // --- END LOG ---

            $this->session->set_flashdata('success', 'Output Finished Goods berhasil disimpan beserta detail size.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menyimpan output Finished Goods.');
        }

        redirect('production_process/finishing/' . $production_id);
    }





    // ======================================================
    // FUNGSI QC & DELETE GENERIC (INTERNAL)
    // ======================================================

    private function _perform_action_and_respond($action_callback, $prod_id, $current_stage, $success_msg, $error_msg, $required_status = null, $permission_key = 'edit') {
        $response = ['success' => false, 'message' => 'Terjadi kesalahan.'];
        $is_ajax = $this->input->is_ajax_request();

        if (!check_access('production_process', $permission_key)) {
            $response['message'] = 'Tidak ada izin.';
            $this->_send_response($response, $is_ajax, $current_stage, $prod_id, 403); return;
        }

        $header = $this->Production_model->get_header_by_id($prod_id);
        if (!$header) { show_404(); return;} // Jika header tidak ada, 404 saja

        if ($required_status && $header->status != $required_status) {
             $response['message'] = 'Aksi hanya bisa dilakukan saat status ' . $required_status . '.';
             $this->_send_response($response, $is_ajax, $current_stage, $prod_id); return;
        }

        // Panggil fungsi callback (misal: $this->Production_model->confirm_qc_output(...))
        if ($action_callback()) {
            $response = ['success' => true, 'message' => $success_msg];
        } else {
            $response['message'] = $error_msg;
            if ($is_ajax) $this->output->set_status_header(500);
        }
        $this->_send_response($response, $is_ajax, $current_stage, $prod_id);
    }

    // --- QC Actions ---
    private function _confirm_qc($prod_id, $output_id, $stage) {
        // 🔒 Gunakan izin 'add' untuk QC (bukan 'edit')
        if (!check_access('production_process', 'add')) {
            $this->session->set_flashdata('error', 'Tidak ada izin.');
            redirect('production_process/' . $stage . '/' . $prod_id);
            return;
        }

        $user_id = $this->session->userdata('user_id');
        if (!$user_id) {
            $this->session->set_flashdata('error', 'Session tidak valid.');
            redirect('production_process/' . $stage . '/' . $prod_id);
            return;
        }

        $success = $this->Production_model->confirm_qc_output($output_id, $user_id);
        if ($success) {
            // --- LOGGING (QC) ---
            $output_data = $this->db->get_where('production_outputs', ['id' => $output_id])->row();
            $item_info = $output_data ? "Item ID {$output_data->item_id}" : "Output ID {$output_id}";
            $deskripsi = "QC Check untuk {$item_info} pada tahap {$stage} (Produksi ID: {$prod_id})";
            $this->_log('QC_APPROVE', $deskripsi);
            // --- END LOG ---
            $this->session->set_flashdata('success', 'QC berhasil dikonfirmasi untuk tahap ' . ucfirst($stage) . '.');
        } else {
            $this->session->set_flashdata('error', 'Gagal konfirmasi QC.');
        }

        redirect('production_process/' . $stage . '/' . $prod_id);
    }
    public function search_finished_goods()
    {
        $q = trim($this->input->get('q', true));
        $this->load->database();

        try {
            // --- QUERY SESUAI DENGAN TABEL KAMU ---
            $this->db->select('id, kode_barang AS code, nama_barang AS name, satuan');
            $this->db->from('finished_goods'); // tabel kamu
            $this->db->where('status', 'active');

            if (!empty($q)) {
                $this->db->group_start()
                        ->like('kode_barang', $q)
                        ->or_like('nama_barang', $q)
                        ->group_end();
            }

            $this->db->limit(20);
            $query = $this->db->get();

            // cek jika query error
            if (!$query) {
                $error = $this->db->error();
                log_message('error', 'DB ERROR search_finished_goods: ' . print_r($error, true));
                show_error('Database Error: ' . $error['message'], 500);
                return;
            }

            $results = [];
            foreach ($query->result() as $row) {
                $results[] = [
                    'id'   => $row->id,
                    'text' => "{$row->name} ({$row->code}) - {$row->satuan}"
                ];
            }

            echo json_encode(['results' => $results]);

        } catch (Throwable $e) {
            log_message('error', 'Exception in search_finished_goods: ' . $e->getMessage());
            show_error('Internal Error: ' . $e->getMessage(), 500);
        }
    }



    // ===============================================================
    // BATAL QC
    // ===============================================================
    private function _unconfirm_qc($prod_id, $output_id, $stage) {
        // 🔒 Gunakan izin 'add' juga untuk pembatalan QC
        if (!check_access('production_process', 'add')) {
            $this->session->set_flashdata('error', 'Tidak ada izin.');
            redirect('production_process/' . $stage . '/' . $prod_id);
            return;
        }

        $success = $this->Production_model->unconfirm_qc_output($output_id);
        if ($success) {
            // --- LOGGING (QC) ---
            $output_data = $this->db->get_where('production_outputs', ['id' => $output_id])->row();
            $item_info = $output_data ? "Item ID {$output_data->item_id}" : "Output ID {$output_id}";
            $deskripsi = "Membatalkan QC Check untuk {$item_info} pada tahap {$stage} (Produksi ID: {$prod_id})";
            $this->_log('QC_REJECT', $deskripsi);
            // --- END LOG ---
            $this->session->set_flashdata('success', 'QC berhasil dibatalkan untuk tahap ' . ucfirst($stage) . '.');
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan QC.');
        }

        redirect('production_process/' . $stage . '/' . $prod_id);
    }

    // --- Delete Actions ---
    private function _delete_input($prod_id, $input_id, $current_stage) {
        $callback = function() use ($input_id, $current_stage) {
            // --- LOGGING (DELETE) ---
            $input_data = $this->db->get_where('production_inputs', ['id' => $input_id])->row();
            $deskripsi = "Menghapus input ID: {$input_id} dari tahap {$current_stage} pada Produksi ID: " . ($input_data->production_header_id ?? 'N/A');
            $this->_log('DELETE', $deskripsi);
            // --- END LOG ---
            return $this->Production_model->delete_input_by_id($input_id);
        };
        $this->_perform_action_and_respond($callback, $prod_id, $current_stage, 'Data input dihapus (stok dikembalikan).', 'Gagal menghapus data input.', 'Draft', 'delete'); // Hanya Draft, izin 'delete'
    }
    private function _delete_output($prod_id, $output_id, $current_stage) {
        $callback = function() use ($output_id) {
            // --- LOGGING (DELETE) ---
            $output_data = $this->db->get_where('production_outputs', ['id' => $output_id])->row();
            $deskripsi = "Menghapus output ID: {$output_id} dari tahap {$current_stage} pada Produksi ID: " . ($output_data->production_header_id ?? 'N/A');
            $this->_log('DELETE', $deskripsi);
            // --- END LOG ---
            return $this->Production_model->delete_output_by_id($output_id);
        };
        $this->_perform_action_and_respond($callback, $prod_id, $current_stage, 'Data output dihapus (stok dikembalikan).', 'Gagal menghapus data output.', 'Draft', 'delete'); // Hanya Draft, izin 'delete'
    }
    

    // ======================================================
    // FUNGSI KHUSUS PER STAGE QC/DELETE (Panggil generic)
    // ======================================================
    public function confirm_qc_preparation($prod_id, $output_id) { $this->_confirm_qc($prod_id, $output_id, 'preparation'); }
    public function unconfirm_qc_preparation($prod_id, $output_id) { $this->_unconfirm_qc($prod_id, $output_id, 'preparation'); }
    public function delete_material_usage($prod_id, $input_id) { $this->_delete_input($prod_id, $input_id, $this->_get_stage_from_referer()); } // Cek referer
    public function delete_preparation_output($prod_id, $output_id) { $this->_delete_output($prod_id, $output_id, 'preparation'); }

    public function confirm_qc_assembling($prod_id, $output_id) { $this->_confirm_qc($prod_id, $output_id, 'assembling'); }
    public function unconfirm_qc_assembling($prod_id, $output_id) { $this->_unconfirm_qc($prod_id, $output_id, 'assembling'); }
    public function delete_assembling_input($prod_id, $input_id) { $this->_delete_input($prod_id, $input_id, 'assembling'); }
    public function delete_assembling_output($prod_id, $output_id) { $this->_delete_output($prod_id, $output_id, 'assembling'); }

    public function confirm_qc_finishing($prod_id, $output_id) { $this->_confirm_qc($prod_id, $output_id, 'finishing'); }
    public function unconfirm_qc_finishing($prod_id, $output_id) { $this->_unconfirm_qc($prod_id, $output_id, 'finishing'); }
    public function delete_finishing_input($prod_id, $input_id) { $this->_delete_input($prod_id, $input_id, 'finishing'); }
    public function delete_finishing_output($prod_id, $output_id) { $this->_delete_output($prod_id, $output_id, 'finishing'); }

    // Helper untuk menentukan stage saat delete material usage
    private function _get_stage_from_referer($default = 'preparation') {
        $referer = $this->agent->is_referral() ? $this->agent->referrer() : '';
        if(strpos($referer, '/assembling/') !== false) return 'assembling';
        if(strpos($referer, '/finishing/') !== false) return 'finishing';
        return $default;
    }

    // ======================================================
    // FUNGSI STATUS HEADER
    // ======================================================

    // Konfirmasi Seluruh Proses Produksi
    public function confirm($id) {
        $current_stage = $this->_get_stage_from_referer();
        $user_id = $this->session->userdata('user_id') ?? null;
        $callback = function() use ($id, $user_id) {
            // --- LOGGING (APPROVE) ---
            $header = $this->Production_model->get_header_by_id($id);
            $deskripsi = "Konfirmasi Proses Produksi: {$header->production_number}. Status: Draft -> In Progress";
            $this->_log('APPROVE', $deskripsi);
            // --- END LOG ---
            return $this->Production_model->confirm_production($id, $user_id);
        };
        $this->_perform_action_and_respond($callback, $id, $current_stage, 'Proses Produksi dikonfirmasi (Status: In Progress).', 'Gagal mengonfirmasi status.', 'Draft', 'edit');
    }

    // Batal Konfirmasi Seluruh Proses Produksi
    public function unconfirm($id) {
        $current_stage = $this->_get_stage_from_referer();
        $callback = function() use ($id) {
            // --- LOGGING (UPDATE) ---
            $header = $this->Production_model->get_header_by_id($id);
            $deskripsi = "Membatalkan konfirmasi Proses Produksi: {$header->production_number}. Status: In Progress -> Draft";
            $this->_log('UPDATE', $deskripsi);
            // --- END LOG ---
            return $this->Production_model->unconfirm_production($id);
        };
        $this->_perform_action_and_respond($callback, $id, $current_stage, 'Konfirmasi dibatalkan (Status: Draft).', 'Gagal membatalkan konfirmasi.', 'In Progress', 'edit');
    }

    // Hapus Header Proses Produksi (Ini redirect ke index, bukan stage)
    public function delete_header($id) {
        $response = ['success' => false, 'message' => 'Terjadi kesalahan.'];
        // Note: Delete header biasanya bukan AJAX, jadi pakai flashdata + redirect biasa

        if (!check_access('production_process', 'delete')) { show_error('Tidak ada izin.', 403); return; }
        $header = $this->Production_model->get_header_by_id($id);
        if (!$header) { show_404(); return; }

        if ($header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya status Draft yang bisa dihapus!');
        } else {
            // --- LOGGING (DELETE) ---
            $deskripsi = "Menghapus Proses Produksi: {$header->production_number} (ID: $id)";
            $this->_log('DELETE', $deskripsi);
            // --- END LOG ---
            if ($this->Production_model->delete_header($id)) {
                $this->session->set_flashdata('success', 'Proses produksi (dan semua entri stok terkait) telah dihapus/dibatalkan.');
            } else {
                $this->session->set_flashdata('error', 'Gagal menghapus (Database Error). Periksa log.');
            }
        }
        redirect('production_process'); // Redirect ke daftar index
    }

     // Fungsi Update Header
     public function update_header($id) {
         $current_stage = $this->_get_stage_from_referer();
         $data_to_update = [
             'production_date' => $this->input->post('production_date'),
             'remarks'         => strip_tags($this->input->post('remarks')), // Sanitasi
         ];
         // Validasi tanggal jika perlu
         if (empty($data_to_update['production_date'])) {
             $this->_send_response(['success'=>false, 'message'=>'Tanggal produksi tidak boleh kosong.'], $this->input->is_ajax_request(), $current_stage, $id); return;
         }

         $callback = function() use ($id, $data_to_update) {
             // --- LOGGING (UPDATE) ---
             $header = $this->Production_model->get_header_by_id($id);
             $deskripsi = "Memperbarui header Proses Produksi: {$header->production_number} (ID: $id)";
             $this->_log('UPDATE', $deskripsi);
             // --- END LOG ---
             return $this->Production_model->update_header($id, $data_to_update);
         };
         $this->_perform_action_and_respond($callback, $id, $current_stage, 'Header diperbarui.', 'Gagal update header.', 'Draft', 'edit');
     }

    // ======================================================
    // FUNGSI COMPLETE PROCESS
    // ======================================================
    public function complete_production($id) {
        $current_stage = 'finishing'; // Complete hanya bisa dari stage finishing
        $callback = function() use ($id) {
            // Validasi tambahan sebelum complete
             $finishing_outputs = $this->Production_model->get_production_outputs($id, 'finishing');
             $has_fg_output = false;
             foreach($finishing_outputs as $out) { if($out->item_type == 'finished_goods' && $out->qty_produced > 0) { $has_fg_output = true; break; } }
             if(!$has_fg_output) {
                 // Set error message di sini atau return false dari callback?
                 // Agar konsisten, kita return false dan biarkan _perform_action handle message
                 return false;
             }
            // --- LOGGING (COMPLETE) ---
            $header = $this->Production_model->get_header_by_id($id);
            $deskripsi = "Menyelesaikan Proses Produksi: {$header->production_number}. Status: In Progress -> Completed";
            $this->_log('COMPLETE', $deskripsi);
            // --- END LOG ---
            return $this->Production_model->complete_production($id);
        };
        // Menyesuaikan pesan error jika validasi FG gagal
        $error_msg = 'Gagal menyelesaikan proses produksi.';
        // Cek lagi apakah ada output FG, jika tidak, ubah pesan error
        $finishing_outputs = $this->Production_model->get_production_outputs($id, 'finishing');
        $has_fg_output = false; foreach($finishing_outputs as $out) { if($out->item_type == 'finished_goods' && $out->qty_produced > 0) { $has_fg_output = true; break; } }
        if(!$has_fg_output){
            $error_msg = 'Gagal! Belum ada output Finished Goods (> 0) yang dicatat di tahap Finishing.';
        }

        $this->_perform_action_and_respond($callback, $id, $current_stage, 'Proses Produksi diselesaikan (Status: Completed).', $error_msg, 'In Progress', 'edit');
    }

    // ======================================================
    // FUNGSI EKSPOR PDF
    // ======================================================
    
    // Helper function for PDF generation (using mPDF)
    private function _generate_pdf($view_path, $data, $filename_prefix) {
        try {
            // Suppress warnings during PDF generation
            $old_error = error_reporting();
            error_reporting(E_ERROR | E_PARSE);
            
            // Gunakan writable directory yang aman
            $temp_dir = FCPATH . 'uploads/temp';
            if (!is_dir($temp_dir)) {
                @mkdir($temp_dir, 0777, true);
            }
            
            // Font directory - include mPDF defaults + custom fonts + CJK fonts
            $font_dirs = [
                FCPATH . 'vendor/mpdf/mpdf/ttfonts',  // Default mPDF fonts
                FCPATH . 'assets/fonts'               // Custom fonts
            ];
            
            // mPDF configuration dengan support untuk Chinese characters
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 12,
                'margin_right' => 12,
                'margin_top' => 12,
                'margin_bottom' => 12,
                'tempDir' => $temp_dir,
                'fontDir' => $font_dirs,
                'default_font' => 'dejavusans',
                'useOnlyCore' => false
            ]);
            
            // Disable drawing for complex CSS to avoid border errors
            $mpdf->simpleTables = true;
            
            $html = $this->load->view($view_path, $data, true);
            $mpdf->WriteHTML($html);
            
            // Restore error reporting
            error_reporting($old_error);
            
            $filename = $filename_prefix . ".pdf";
            $mpdf->Output($filename, 'I'); // 'I' for inline preview
            exit;
        } catch (\Mpdf\MpdfException $e) {
            error_reporting($old_error ?? E_ALL);
            log_message('error', 'mPDF Exception: ' . $e->getMessage());
            show_error('Gagal membuat file PDF: ' . $e->getMessage());
        } catch (Exception $e) {
            error_reporting($old_error ?? E_ALL);
            log_message('error', 'PDF Exception: ' . $e->getMessage());
            show_error('Gagal membuat file PDF: ' . $e->getMessage());
        }
    }

    public function export_preparation_pdf($id) {
        if (!check_access('production_process', 'view')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Production_model->get_header_by_id($id);
        if (!$header) show_404();
        $data = [
            'header'              => $header,
            'preparation_inputs'  => $this->Production_model->get_production_inputs_for_trace($id, 'preparation'), // Pakai trace agar ada info GRN
            'preparation_outputs' => $this->Production_model->get_production_outputs($id, 'preparation'),
        ];
        $this->_generate_pdf('production_process/preparation_pdf_template', $data, "ProdPreparation_".str_replace('/', '-', $header->production_number));
    }

     public function export_assembling_pdf($id) {
         if (!check_access('production_process', 'view')) { show_error('Tidak ada izin.', 403); }
         $header = $this->Production_model->get_header_by_id($id);
         if (!$header) show_404();
         $data = [
             'header' => $header,
             'assembling_inputs' => $this->Production_model->get_production_inputs_for_trace($id, 'assembling'), // Pakai trace
             'assembling_outputs' => $this->Production_model->get_production_outputs($id, 'assembling'),
         ];
         $this->_generate_pdf('production_process/assembling_pdf_template', $data, "ProdAssembling_".str_replace('/', '-', $header->production_number));
     }

     public function export_finishing_pdf($id) {
         if (!check_access('production_process', 'view')) { show_error('Tidak ada izin.', 403); }
         $header = $this->Production_model->get_header_by_id($id);
         if (!$header) show_404();
         $data = [
             'header' => $header,
             'finishing_inputs' => $this->Production_model->get_production_inputs_for_trace($id, 'finishing'), // Pakai trace
             'finishing_outputs' => $this->Production_model->get_production_outputs($id, 'finishing'),
         ];
         $this->_generate_pdf('production_process/finishing_pdf_template', $data, "ProdFinishing_".str_replace('/', '-', $header->production_number));
     }
     private function _load_views($main_view, $data = []) {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($main_view, $data);
        $this->load->view('templates/footer');
    }

} // End Class