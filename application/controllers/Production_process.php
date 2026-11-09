<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class Production_process extends MY_Controller {

    protected $module_name = 'Production Process';

    /**
     * Tahap produksi Acropoly (2026-11-08), urut: tahap => tahap sebelumnya.
     * Kode tahap tersimpan di stage_name (production_inputs/production_outputs)
     * dan dipakai juga oleh QC Produksi. Cutting mengambil material dari
     * MR/WIP; tahap berikutnya mengambil hasil tahap sebelumnya (+ material
     * tambahan); Finishing menghasilkan Barang Jadi.
     */
    const STAGES = [
        'cutting'    => null,
        'sewing'     => 'cutting',
        'assembling' => 'sewing',
        'finishing'  => 'assembling',
    ];

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

        $filters = [
            'search' => trim((string) $this->input->get('search', TRUE)),
            'status' => trim((string) $this->input->get('status', TRUE)),
        ];
        $processes = $this->Production_model->get_all($filters);
        $data = [
            'title'          => lang_line('title_pp_main'),
            'processes'      => $processes,
            'filters'        => $filters,
            'status_counts'  => $this->Production_model->count_by_status(),
            // Progres tahap per baris (stepper mini di daftar)
            'stage_overview' => $this->Production_model->get_stage_overview_bulk(
                array_map(function ($p) { return $p->id; }, $processes), array_keys(self::STAGES)),
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

    // Get scrap sources and available inputs
    public function ajax_get_scrap_sources() {
        header('Content-Type: application/json');
        $output_id = $this->input->get('output_id');
        $stage = $this->input->get('stage');
        if (!$output_id) {
            echo json_encode(['error' => 'Invalid output ID']);
            exit;
        }

        // Get scrap output info
        $output = $this->db->get_where('production_outputs', ['id' => $output_id])->row();
        if (!$output) {
            echo json_encode(['error' => 'Scrap not found']);
            exit;
        }

        try {
            // Get scrap item name
            $item_name = 'Scrap Item';
            if ($output->item_type == 'scrap') {
                $scrap_item = $this->db->get_where('master_scrap', ['id' => $output->item_id])->row();
                if ($scrap_item) {
                    $item_name = $scrap_item->scrap_name ?? 'Scrap Item';
                }
            }

            // Get scrap sources + info MR yang terkait (untuk block create MR duplikat)
            $sources = $this->db->query("
                SELECT pss.*, pi.item_id, pi.item_type,
                       COALESCE(NULLIF(m.nama_material, ''), COALESCE(NULLIF(sf.nama_barang, ''), 'Unknown')) as source_item_name,
                       COALESCE(NULLIF(m.kode_material, ''), COALESCE(NULLIF(sf.kode_barang, ''), '')) as source_item_code,
                       pi.qty_used as source_qty_input,
                       mh.id        as mr_header_id,
                       mh.mr_number as mr_number,
                       mh.status    as mr_status
                FROM production_scrap_sources pss
                JOIN production_inputs pi ON pss.source_input_id = pi.id
                LEFT JOIN materials m ON pi.item_id = m.id AND pi.item_type = 'material'
                LEFT JOIN semifinished_goods sf ON pi.item_id = sf.id AND pi.item_type = 'semifinished'
                LEFT JOIN mr_details md ON pss.mr_detail_id = md.id
                LEFT JOIN mr_header mh ON md.mr_header_id = mh.id
                WHERE pss.production_output_id = ?
                ORDER BY pss.id
            ", [$output_id])->result();

            // Get available inputs - ONLY from current stage for traceability
            $available = $this->db->query("
                SELECT pi.id as input_id, pi.item_id, pi.item_type, pi.qty_used,
                       COALESCE(NULLIF(m.nama_material, ''), COALESCE(NULLIF(sf.nama_barang, ''), 'Unknown')) as item_name,
                       COALESCE(NULLIF(m.kode_material, ''), COALESCE(NULLIF(sf.kode_barang, ''), '')) as item_code,
                       (pi.qty_used - COALESCE((SELECT SUM(source_qty) FROM production_scrap_sources
                                                WHERE source_input_id = pi.id AND production_output_id = ?), 0)) as remaining_qty
                FROM production_inputs pi
                LEFT JOIN materials m ON pi.item_id = m.id AND pi.item_type = 'material'
                LEFT JOIN semifinished_goods sf ON pi.item_id = sf.id AND pi.item_type = 'semifinished'
                WHERE pi.production_header_id = ? AND pi.stage_name = ?
                HAVING remaining_qty > 0
                ORDER BY pi.id
            ", [$output_id, $output->production_header_id, $stage])->result();

            // Total qty MATERIAL yang ditunjuk sebagai asal scrap.
            $total_scrap = 0;
            if ($sources) {
                foreach ($sources as $src) {
                    $total_scrap += (float)$src->source_qty;
                }
            }

            // Qty scrap = qty output scrap itu sendiri (barang scrap yang
            // dihasilkan). Dulu dipakai max(qty_produced, total_scrap) — angka
            // qty MATERIAL ikut terpakai sebagai qty barang scrap padahal beda
            // hal & beda satuan (user 2026-09-21: "padahal qty material yang
            // jadi scrap 10", sementara scrap yang dihasilkan 1 Housing).
            $qty_scrap = (float) $output->qty_produced;

            echo json_encode([
                'success' => true,
                'item_name' => $item_name,
                'qty_scrap' => $qty_scrap,
                'total_source_qty' => $total_scrap,
                'sources' => $sources,
                'available_inputs' => $available
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Isi modal "Pilih dari Daftar" Work Order pada form Buat Proses Produksi.
     * Mengembalikan SELURUH WO yang relevan (tidak dipotong limit seperti
     * select2), termasuk yang belum memenuhi syarat beserta alasannya.
     */
    public function ajax_selectable_wo_list() {
        header('Content-Type: application/json');

        if (!check_access('production_process', 'add')) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada izin.']);
            return;
        }

        $rows = $this->Production_model->get_selectable_wos([
            'search' => trim((string) $this->input->get('search', TRUE)),
        ]);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'                 => (int) $r->id,
                'wo_number'          => $r->wo_number,
                'sc_number'          => $r->sc_number,
                'fg_code'            => $r->fg_code,
                'fg_name'            => $r->fg_name,
                'planned_qty'        => (float) $r->planned_qty,
                'planned_start_date' => $r->planned_start_date,
                'wo_status'          => $r->wo_status,
                'material_count'     => (int) $r->material_count,
                'eligible'           => (bool) $r->eligible,
                'reason'             => $r->reason,
            ];
        }

        echo json_encode(['success' => true, 'rows' => $out]);
    }

    /**
     * TIDAK DIPAKAI LAGI sejak modal "Pilih dari Daftar" (2026-09-30):
     * form Buat Proses Produksi dulu memakai select2 yang menembak endpoint
     * ini sambil user mengetik. Digantikan ajax_selectable_wo_list().
     * Dibiarkan ada kalau-kalau masih ada pemanggil di luar repo ini.
     */
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
            redirect('production_process/cutting/' . $new_prod_id);
        } else {
            log_message('error', 'Gagal membuat Production Header. DB Error: ' . print_r($this->db->error(), true) . ' Data: ' . json_encode($data));
            $this->session->set_flashdata('error', 'Gagal membuat Proses Produksi! Terjadi kesalahan database.');
            redirect('production_process');
        }
    }

    /**
     * Halaman tahap (satu tampilan untuk semua tahap: production_process/stage).
     * Cutting  : input material dari MR/WIP.
     * Sewing / Assembling / Finishing : input hasil tahap sebelumnya + material
     *            tambahan. Finishing menghasilkan Barang Jadi (+ scrap).
     */
    private function _show_stage_view($id, $current_stage) {
        if (!check_access('production_process', 'view')) { show_error('Tidak ada izin.', 403); return; }
        if (!array_key_exists($current_stage, self::STAGES)) { show_404(); return; }
        if (empty($id) || !is_numeric($id)) { show_404(); return; }

        $header = $this->Production_model->get_header_by_id($id);
        if (!$header) { show_404(); return; }

        $stages         = array_keys(self::STAGES);
        $previous_stage = self::STAGES[$current_stage];
        $stage_times    = $this->Production_model->get_stage_times($id);

        $data = [
            'title'             => sprintf(lang_line('title_pp_stage'), lang_line('pp_stage_' . $current_stage)),
            'header'            => $header,
            'current_stage'     => $current_stage,
            'previous_stage'    => $previous_stage,
            'stage_index'       => array_search($current_stage, $stages, true),
            'stage_overview'    => $this->Production_model->get_stage_overview($id, $stages),
            'stage_time'        => $stage_times[$current_stage] ?? null,
            'stage_times_ready' => $this->Production_model->stage_times_ready(),
            // Material dari MR/WIP (Cutting: bahan utama; tahap lain: tambahan)
            'available_batches' => $this->Production_model->get_wip_materials_with_remaining($header->wo_header_id),
            'available_previous_outputs' => $previous_stage
                ? $this->Production_model->get_available_previous_stage_outputs($id, $current_stage, $previous_stage)
                : [],
            'current_inputs'    => $this->Production_model->get_production_inputs($id, $current_stage),
            'current_outputs'   => $this->Production_model->get_production_outputs($id, $current_stage),
        ];

        if ($current_stage == 'finishing') {
            // Sinkronisasi size breakdown dari production_output_sizes (perilaku lama).
            foreach ($data['current_outputs'] as &$out) {
                $sizes = $this->db->get_where('production_output_sizes', ['output_id' => $out->id])->result();
                if (!empty($sizes)) {
                    $breakdown = [];
                    foreach ($sizes as $sz) {
                        $breakdown[] = ['size' => trim($sz->size_label), 'qty' => (float) $sz->qty];
                    }
                    $out->sizes = $breakdown;
                    if (empty($out->size_breakdown)) {
                        $json = json_encode($breakdown);
                        $this->db->where('id', $out->id)->update('production_outputs', ['size_breakdown' => $json]);
                        $out->size_breakdown = $json;
                    }
                } else {
                    $out->sizes = [];
                }
            }
            unset($out);
        }

        $this->_load_views('production_process/stage', $data);
    }

    // Halaman per tahap -- urutannya lihat self::STAGES.
    public function cutting($id)    { $this->_show_stage_view($id, 'cutting'); }
    public function sewing($id)     { $this->_show_stage_view($id, 'sewing'); }
    public function assembling($id) { $this->_show_stage_view($id, 'assembling'); }
    public function finishing($id)  { $this->_show_stage_view($id, 'finishing'); }

    // URL lama era NSI (tahap 1 = Grinding) -- diarahkan ke Cutting supaya
    // bookmark / link lama tidak 404.
    public function grinding($id = null) { redirect('production_process/cutting/' . (int) $id); }

    // ======================================================
    // WAKTU PROSES PER TAHAP (2026-11-08)
    // ======================================================

    /**
     * Simpan Mulai/Selesai tahap. Waktu mulai menjadi tanggal transaksi stok
     * WIP semua input/output tahap itu (lihat Production_model::save_stage_time).
     * POST started_at (wajib), finished_at (opsional) -- format datetime-local.
     */
    public function save_stage_time($prod_id = null, $stage = null) {
        if (!array_key_exists((string) $stage, self::STAGES) || empty($prod_id) || !is_numeric($prod_id)) { show_404(); return; }
        $back = 'production_process/' . $stage . '/' . (int) $prod_id;

        if (!check_access('production_process', 'edit')) {
            $this->session->set_flashdata('error', 'Tidak ada izin.');
            redirect($back); return;
        }
        $header = $this->Production_model->get_header_by_id($prod_id);
        if (!$header) { show_404(); return; }
        if ($header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Waktu proses hanya bisa diubah saat status Draft.');
            redirect($back); return;
        }
        if (!$this->Production_model->stage_times_ready()) {
            $this->session->set_flashdata('error', 'Tabel waktu proses belum ada. Jalankan db/migration_tahap_produksi_acropoly.sql.');
            redirect($back); return;
        }

        $stage_label = lang_line('pp_stage_' . $stage);
        $start_raw   = trim((string) $this->input->post('started_at'));
        $finish_raw  = trim((string) $this->input->post('finished_at'));
        $start       = $this->_parse_datetime($start_raw);
        $finish      = $finish_raw === '' ? null : $this->_parse_datetime($finish_raw);
        $limit       = date('Y-m-d H:i:s', time() + 600); // toleransi jam perangkat 10 menit

        $error = null;
        if (!$start) {
            $error = 'Waktu mulai tahap ' . $stage_label . ' wajib diisi (tanggal & jam).';
        } elseif ($finish_raw !== '' && !$finish) {
            $error = 'Format waktu selesai tidak valid.';
        } elseif ($start > $limit) {
            $error = 'Waktu mulai tidak boleh di masa depan.';
        } elseif ($finish && $finish < $start) {
            $error = 'Waktu selesai tidak boleh sebelum waktu mulai.';
        } elseif ($finish && $finish > $limit) {
            $error = 'Waktu selesai tidak boleh di masa depan.';
        }
        if ($error) {
            $this->session->set_flashdata('error', $error);
            redirect($back); return;
        }

        $user_id = $this->session->userdata('user_id') ?? null;
        if ($this->Production_model->save_stage_time($prod_id, $stage, $start, $finish, $user_id)) {
            $this->_log('UPDATE', "Waktu proses {$stage_label} {$header->production_number}: mulai {$start}" . ($finish ? ", selesai {$finish}" : ''));
            $this->session->set_flashdata('success', 'Waktu proses ' . $stage_label . ' disimpan. Input & output tahap ini tercatat per ' . date('d M Y H:i', strtotime($start)) . '.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menyimpan waktu proses (Database Error).');
        }
        redirect($back);
    }

    /** 'Y-m-d\TH:i' / 'Y-m-d H:i' / ...:s -> 'Y-m-d H:i:s', atau null kalau tidak valid. */
    private function _parse_datetime($raw) {
        $raw = trim((string) $raw);
        if ($raw === '') return null;
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Y-m-d H:i:s'] as $fmt) {
            $dt = DateTime::createFromFormat('!' . $fmt, $raw);
            $err = DateTime::getLastErrors();
            if ($dt && (!$err || ($err['warning_count'] == 0 && $err['error_count'] == 0))) {
                return $dt->format('Y-m-d H:i:s');
            }
        }
        return null;
    }

    /**
     * Pesan penolakan kalau waktu mulai tahap belum diisi (null = boleh).
     * Input/output tidak boleh dicatat tanpa tanggal proses -- kalau tidak,
     * stok WIP kembali tercatat pada jam user mengetik.
     */
    private function _stage_start_missing_msg($prod_id, $stage) {
        if (!$this->Production_model->stage_times_ready()) return null;
        if ($this->Production_model->get_stage_start($prod_id, $stage)) return null;
        return sprintf(lang_line('pp_time_required_msg'), lang_line('pp_stage_' . $stage));
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
        if ($msg = $this->_stage_start_missing_msg($prod_id, $current_stage)) {
            $response['message'] = $msg;
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
        if ($msg = $this->_stage_start_missing_msg($prod_id, $current_stage)) {
            $response['message'] = $msg;
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
        if ($msg = $this->_stage_start_missing_msg($prod_id, $current_stage)) {
            $response['message'] = $msg;
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

        // Kalau output-nya Barang Jadi, harus sama dgn FG pada Work Order —
        // aturannya satu tempat di Production_model::check_output_fg().
        $fg_check = $this->Production_model->check_output_fg($prod_id, $item_id, $item_type);
        if (!$fg_check['ok']) {
            $response['message'] = $fg_check['message'];
            $this->_send_response($response, $is_ajax, $current_stage, $prod_id); return;
        }

        // Data Siap Disimpan
        $data = [
            'production_header_id' => $prod_id, 'stage_name' => $current_stage,
            'item_id' => $item_id, 'item_type' => $item_type,
            'qty_produced' => $qty,  // Semua output disimpan di qty_produced
            'qty_scrap' => 0,        // qty_scrap hanya untuk referensi
            'received_by' => $this->session->userdata('user_id') ?? null,
            'received_at' => $this->Production_model->stage_tx_date($prod_id, $current_stage) // waktu proses tahap
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

    // --- 1. Cutting ---
    public function save_cutting_material_usage($prod_id) { $this->_save_stage_additional_input($prod_id, 'cutting'); }
    public function save_cutting_output($prod_id) { $this->_save_stage_output($prod_id, 'cutting', ['semifinished', 'scrap']); }

    // --- 2. Sewing (input dari hasil Cutting) ---
    public function save_sewing_cutting_input($prod_id) { $this->_save_stage_input_from_output($prod_id, 'sewing', 'cutting'); }
    public function save_sewing_additional_input($prod_id) { $this->_save_stage_additional_input($prod_id, 'sewing'); }
    public function save_sewing_output($prod_id) { $this->_save_stage_output($prod_id, 'sewing', ['semifinished', 'scrap']); }

    // --- 3. Assembling (input dari hasil Sewing) ---
    public function save_assembling_sewing_input($prod_id) { $this->_save_stage_input_from_output($prod_id, 'assembling', 'sewing'); }
    public function save_assembling_additional_input($prod_id) { $this->_save_stage_additional_input($prod_id, 'assembling'); }
    public function save_assembling_output($prod_id) { $this->_save_stage_output($prod_id, 'assembling', ['semifinished', 'scrap']); }

    // --- 4. Finishing (input dari hasil Assembling) ---
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

        // Dokumen harus ada & masih Draft. Sebelumnya TIDAK dicek sama sekali di
        // sini -- output bisa ditambahkan ke produksi yang sudah dikonfirmasi.
        $header = $this->Production_model->get_header_by_id($production_id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya produksi berstatus Draft yang bisa ditambah output.');
            redirect('production_process/finishing/' . $production_id);
            return;
        }
        if ($msg = $this->_stage_start_missing_msg($production_id, 'finishing')) {
            $this->session->set_flashdata('error', $msg);
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

        // Barang Jadi WAJIB sama dengan FG pada Work Order.
        // Sebelum ini FG dipilih bebas dari master: kalau WO-nya FG A tapi
        // operator memilih FG B, hasil produksi tidak pernah ketemu dengan
        // kebutuhan di WO/IPO dan tidak ada apa pun yang menahannya
        // (user 2026-09-30). Tampilan sudah dikunci, tapi form ini tetap bisa
        // di-POST langsung -- jadi dijaga juga di sini.
        $fg_check = $this->Production_model->check_output_fg($production_id, $item_id, $item_type);
        if (!$fg_check['ok']) {
            $this->session->set_flashdata('error', $fg_check['message']);
            redirect('production_process/finishing/' . $production_id);
            return;
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
            'received_at'          => $this->Production_model->stage_tx_date($production_id, 'finishing'),
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

    // Save QC with description and photo
    private function _save_qc($prod_id, $output_id, $stage) {
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

        $qc_description = $this->input->post('qc_description');
        $qc_photo_url = $this->input->post('qc_photo_url');

        $update_data = [
            'qc_checked_by' => $user_id,
            'qc_checked_at' => date('Y-m-d H:i:s'),
            'qc_description' => $qc_description,
            'qc_photo_url' => $qc_photo_url
        ];

        $success = $this->db->where('id', $output_id)->update('production_outputs', $update_data);
        if ($success) {
            $this->session->set_flashdata('success', 'QC berhasil disimpan untuk tahap ' . ucfirst($stage) . '.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menyimpan QC.');
        }

        redirect('production_process/' . $stage . '/' . $prod_id);
    }

    public function save_qc_cutting($prod_id, $output_id) { $this->_save_qc($prod_id, $output_id, 'cutting'); }
    public function save_qc_sewing($prod_id, $output_id) { $this->_save_qc($prod_id, $output_id, 'sewing'); }
    public function save_qc_assembling($prod_id, $output_id) { $this->_save_qc($prod_id, $output_id, 'assembling'); }
    public function save_qc_finishing($prod_id, $output_id) { $this->_save_qc($prod_id, $output_id, 'finishing'); }

    // Save scrap source (trace scrap asal dari material input)
    public function save_scrap_source($prod_id, $output_id, $stage = null) {
        $is_ajax = $this->input->is_ajax_request();

        if (!check_access('production_process', 'edit')) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Tidak ada izin']);
                return;
            }
            $this->session->set_flashdata('error', 'Tidak ada izin.');
            redirect('production_process/' . ($stage ?? 'cutting') . '/' . $prod_id);
            return;
        }

        $source_input_id = $this->input->post('source_input_id');
        $source_qty = (float)$this->input->post('source_qty');
        if (!$stage) {
            $stage = $this->input->post('stage', true);
        }

        if (!$source_input_id || $source_qty <= 0) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
                return;
            }
            $this->session->set_flashdata('error', 'Data tidak valid.');
            redirect('production_process/' . $stage . '/' . $prod_id);
            return;
        }

        $data = [
            'production_output_id' => $output_id,
            'source_input_id' => $source_input_id,
            'source_qty' => $source_qty
        ];

        // NG cukup qty + keterangan (2026-11-08; rincian 4 kategori + Kode NG
        // warisan NSI dihapus). Keterangan masuk ke Daily Material NG Report.
        if (db_has_column('production_scrap_sources', 'notes')) {
            $remark = trim((string) ($this->input->post('notes', true) ?? $this->input->post('ng_remark', true)));
            if ($remark === '') {
                $msg = lang_line('pp2_scrap_notes_required', 'Keterangan NG wajib diisi.');
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'error', 'message' => $msg]);
                    return;
                }
                $this->session->set_flashdata('error', $msg);
                redirect('production_process/' . $stage . '/' . $prod_id);
                return;
            }
            $data['notes'] = mb_substr($remark, 0, 255);
        }

        $ok = (bool) $this->db->insert('production_scrap_sources', $data);

        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => $ok ? 'success' : 'error',
                'message' => $ok ? 'Asal scrap berhasil ditambahkan.' : 'Gagal menyimpan asal scrap.'
            ]);
            return;
        }

        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Asal scrap berhasil ditambahkan.' : 'Gagal menyimpan asal scrap.');
        redirect('production_process/' . $stage . '/' . $prod_id);
    }

    /**
     * Create MR otomatis dari asal scrap yang dipilih (untuk menutup kekurangan material akibat scrap).
     * POST: scrap_source_ids[] = array id production_scrap_sources
     *
     * Hasil: 1 MR header dengan status 'Submitted' (pending HoD approval), details = 1 per scrap source yang dipilih.
     * Qty di-request = source_qty (qty material yang menjadi scrap).
     */
    public function create_mr_from_scrap($prod_id) {
        header('Content-Type: application/json');

        if (!check_access('material_request', 'create') && !check_access('material_request', 'edit')) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada izin create Material Request']);
            return;
        }

        if (empty($prod_id) || !is_numeric($prod_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid production ID']);
            return;
        }

        $scrap_source_ids = $this->input->post('scrap_source_ids');
        if (!is_array($scrap_source_ids) || empty($scrap_source_ids)) {
            echo json_encode(['status' => 'error', 'message' => 'Pilih minimal satu asal scrap']);
            return;
        }

        // Get production header → wo_header_id
        $prod_header = $this->db->get_where('production_header', ['id' => $prod_id])->row();
        if (!$prod_header || empty($prod_header->wo_header_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Production header tidak ditemukan / belum link ke WO']);
            return;
        }

        // Gabung scrap_sources + production_inputs + cek apakah sudah punya MR aktif (status != Draft/Rejected)
        $ids_placeholder = implode(',', array_map('intval', $scrap_source_ids));
        $rows = $this->db->query("
            SELECT pss.id as scrap_source_id, pss.source_qty, pss.mr_detail_id,
                   pi.item_id, pi.item_type, pi.production_header_id,
                   mh.mr_number AS existing_mr_number,
                   mh.status    AS existing_mr_status
            FROM production_scrap_sources pss
            JOIN production_inputs pi ON pss.source_input_id = pi.id
            LEFT JOIN mr_details md ON pss.mr_detail_id = md.id
            LEFT JOIN mr_header mh ON md.mr_header_id = mh.id
            WHERE pss.id IN ($ids_placeholder)
              AND pi.production_header_id = ?
        ", [$prod_id])->result();

        if (empty($rows)) {
            echo json_encode(['status' => 'error', 'message' => 'Data asal scrap tidak valid untuk produksi ini']);
            return;
        }

        // Block scrap source yang sudah punya MR aktif (bukan Draft/Rejected)
        $blocked = [];
        foreach ($rows as $r) {
            if (!empty($r->existing_mr_status) && !in_array($r->existing_mr_status, ['Draft', 'Rejected'], true)) {
                $blocked[] = $r->existing_mr_number . ' (' . $r->existing_mr_status . ')';
            }
        }
        if (!empty($blocked)) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Beberapa asal scrap sudah punya MR aktif: ' . implode(', ', array_unique($blocked)) . '. Refresh modal lalu pilih ulang.'
            ]);
            return;
        }

        $this->db->trans_start();

        try {
            // Generate MR number + insert header sebagai Draft (insert_item Mr_model hanya allow saat Draft)
            $mr_number = $this->Mr_model->generate_mr_number();
            $user_id   = $this->session->userdata('user_id');

            $header_data = [
                'mr_number'    => $mr_number,
                'request_date' => date('Y-m-d'),
                'status'       => 'Draft',
                'remarks'      => 'Auto-generated dari asal scrap Production #' . $prod_id
                                  . ' untuk menutup kekurangan material',
            ];

            $mr_header_id = $this->Mr_model->insert_header($header_data);
            if (!$mr_header_id) {
                throw new Exception('Gagal insert MR header');
            }

            // Insert details (allowed karena header status masih Draft) + link FK ke scrap_source
            $inserted = 0;
            foreach ($rows as $r) {
                $detail_data = [
                    'mr_header_id'        => $mr_header_id,
                    'wo_header_id'        => $prod_header->wo_header_id,
                    'component_item_id'   => $r->item_id,
                    'component_item_type' => $r->item_type,
                    'requested_qty'       => $r->source_qty,
                    'notes'               => 'Kekurangan akibat scrap (scrap source #' . $r->scrap_source_id . ')',
                ];
                $mr_detail_id = $this->Mr_model->insert_item($detail_data);
                if ($mr_detail_id) {
                    $inserted++;
                    // Link production_scrap_sources → mr_details untuk mencegah create MR duplikat
                    $this->db->where('id', $r->scrap_source_id)
                             ->update('production_scrap_sources', ['mr_detail_id' => $mr_detail_id]);
                }
            }

            if ($inserted === 0) {
                throw new Exception('Tidak ada detail MR yang berhasil diinsert');
            }

            // Promote ke Submitted (set requester_id + requested_at via update_status)
            if (!$this->Mr_model->update_status($mr_header_id, 'Submitted', $user_id)) {
                throw new Exception('Gagal submit MR ke approval');
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed saat create MR');
            }

            echo json_encode([
                'status'    => 'success',
                'message'   => 'MR ' . $mr_number . ' berhasil dibuat (' . $inserted . ' item) dan submitted untuk approval HoD.',
                'mr_number' => $mr_number,
                'mr_id'     => $mr_header_id,
            ]);
            return;

        } catch (Exception $e) {
            $this->db->trans_complete();
            log_message('error', 'create_mr_from_scrap: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            return;
        }
    }

    // Delete scrap source
    public function delete_scrap_source($source_id, $prod_id) {
        $is_ajax = $this->input->is_ajax_request();

        if (!check_access('production_process', 'delete')) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Tidak ada izin']);
                return;
            }
            $this->session->set_flashdata('error', 'Tidak ada izin.');
            redirect('production_process');
            return;
        }

        $source = $this->db->get_where('production_scrap_sources', ['id' => $source_id])->row();
        if (!$source) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Asal scrap tidak ditemukan']);
                return;
            }
            $this->session->set_flashdata('error', 'Asal scrap tidak ditemukan.');
            redirect('production_process');
            return;
        }

        $ok = (bool) $this->db->delete('production_scrap_sources', ['id' => $source_id]);

        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => $ok ? 'success' : 'error',
                'message' => $ok ? 'Asal scrap berhasil dihapus.' : 'Gagal menghapus asal scrap.'
            ]);
            return;
        }

        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Asal scrap berhasil dihapus.' : 'Gagal menghapus asal scrap.');
        redirect('production_process');
    }

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
    /**
     * TIDAK DIPAKAI LAGI sejak Barang Jadi dikunci ke FG milik Work Order
     * (2026-09-30): form Finishing dulu memakai select2 yang menembak endpoint
     * ini dan menampilkan SELURUH FG aktif di master. Dibiarkan ada (tidak
     * dihapus) kalau-kalau masih ada pemanggil di luar repo ini.
     */
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
    // --- 1. Cutting ---
    public function confirm_qc_cutting($prod_id, $output_id) { $this->_confirm_qc($prod_id, $output_id, 'cutting'); }
    public function unconfirm_qc_cutting($prod_id, $output_id) { $this->_unconfirm_qc($prod_id, $output_id, 'cutting'); }
    public function delete_cutting_input($prod_id, $input_id) { $this->_delete_input($prod_id, $input_id, 'cutting'); }
    public function delete_cutting_output($prod_id, $output_id) { $this->_delete_output($prod_id, $output_id, 'cutting'); }
    public function delete_material_usage($prod_id, $input_id) { $this->_delete_input($prod_id, $input_id, $this->_get_stage_from_referer()); } // Cek referer

    // --- 2. Sewing ---
    public function confirm_qc_sewing($prod_id, $output_id) { $this->_confirm_qc($prod_id, $output_id, 'sewing'); }
    public function unconfirm_qc_sewing($prod_id, $output_id) { $this->_unconfirm_qc($prod_id, $output_id, 'sewing'); }
    public function delete_sewing_input($prod_id, $input_id) { $this->_delete_input($prod_id, $input_id, 'sewing'); }
    public function delete_sewing_output($prod_id, $output_id) { $this->_delete_output($prod_id, $output_id, 'sewing'); }

    // --- 3. Assembling ---
    public function confirm_qc_assembling($prod_id, $output_id) { $this->_confirm_qc($prod_id, $output_id, 'assembling'); }
    public function unconfirm_qc_assembling($prod_id, $output_id) { $this->_unconfirm_qc($prod_id, $output_id, 'assembling'); }
    public function delete_assembling_input($prod_id, $input_id) { $this->_delete_input($prod_id, $input_id, 'assembling'); }
    public function delete_assembling_output($prod_id, $output_id) { $this->_delete_output($prod_id, $output_id, 'assembling'); }

    // --- 4. Finishing ---

    public function confirm_qc_finishing($prod_id, $output_id) { $this->_confirm_qc($prod_id, $output_id, 'finishing'); }
    public function unconfirm_qc_finishing($prod_id, $output_id) { $this->_unconfirm_qc($prod_id, $output_id, 'finishing'); }
    public function delete_finishing_input($prod_id, $input_id) { $this->_delete_input($prod_id, $input_id, 'finishing'); }
    public function delete_finishing_output($prod_id, $output_id) { $this->_delete_output($prod_id, $output_id, 'finishing'); }

    // Helper untuk menentukan stage saat delete material usage
    private function _get_stage_from_referer($default = 'cutting') {
        $referer = $this->agent->is_referral() ? $this->agent->referrer() : '';
        if(strpos($referer, '/sewing/') !== false) return 'sewing';
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
        $this->_perform_action_and_respond($callback, $id, $current_stage, 'Proses Produksi dikonfirmasi (Status: In Progress).', 'Gagal mengonfirmasi status.', 'Draft', 'confirm');
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
        $this->_perform_action_and_respond($callback, $id, $current_stage, 'Konfirmasi dibatalkan (Status: Draft).', 'Gagal membatalkan konfirmasi.', 'In Progress', 'confirm');
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

    public function export_cutting_pdf($id) {
        if (!check_access('production_process', 'view')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Production_model->get_header_by_id($id);
        if (!$header) show_404();
        $data = [
            'header'          => $header,
            'cutting_inputs'  => $this->Production_model->get_production_inputs_for_trace($id, 'cutting'), // Pakai trace agar ada info GRN
            'cutting_outputs' => $this->Production_model->get_production_outputs($id, 'cutting'),
        ];
        $this->_generate_pdf('production_process/cutting_pdf_template', $data, "ProdCutting_".str_replace('/', '-', $header->production_number));
    }

     public function export_sewing_pdf($id) { $this->_export_stage_pdf($id, 'sewing'); }
     public function export_assembling_pdf($id) { $this->_export_stage_pdf($id, 'assembling'); }

     // PDF tahap tengah (Sewing, Assembling) -- satu template bersama.
     private function _export_stage_pdf($id, $stage) {
         if (!check_access('production_process', 'view')) { show_error('Tidak ada izin.', 403); }
         $header = $this->Production_model->get_header_by_id($id);
         if (!$header) show_404();
         $data = [
             'header'        => $header,
             'stage_title'   => ucfirst($stage),
             'stage_inputs'  => $this->Production_model->get_production_inputs_for_trace($id, $stage), // Pakai trace
             'stage_outputs' => $this->Production_model->get_production_outputs($id, $stage),
         ];
         $this->_generate_pdf('production_process/stage_pdf_template', $data, "Prod".ucfirst($stage)."_".str_replace('/', '-', $header->production_number));
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
        $data['pp_stages'] = array_keys(self::STAGES); // untuk tab tahap (_stage_tabs.php)
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($main_view, $data);
        $this->load->view('templates/footer');
    }

} // End Class