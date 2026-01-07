<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class Subkon_out extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();

        $this->load->model([
            'Subkon_out_model', 
            'Subkon_order_model',
            'Stock_ledger_model',
            'Commoncode_model'
        ]);

        $this->load->library(['pagination', 'form_validation']);
        $this->load->helper('url');
    }

    /* ==========================================================
       INDEX — LIST DO SUBKON
       ========================================================== */
    public function index() {
        if (!check_access('subkon_out', 'view')) show_error('Akses ditolak', 403);

        $filters = [
            'start_date' => $this->input->get('start_date') ?: date('Y-m-01'),
            'end_date'   => $this->input->get('end_date') ?: date('Y-m-t'),
            'status'     => $this->input->get('status'),
            'search'     => $this->input->get('search')
        ];

        $config['base_url'] = base_url('subkon_out/index');
        $config['total_rows'] = $this->Subkon_out_model->count_all($filters);
        $config['per_page'] = 25;
        $config['reuse_query_string'] = TRUE;
        $config['use_page_numbers'] = TRUE;

        $this->pagination->initialize($config);
        $page   = ($this->uri->segment(3)) ? (int)$this->uri->segment(3) : 1;
        $offset = ($page > 0) ? ($page - 1) * $config['per_page'] : 0;

        $data = [
            'title'      => 'Daftar Pengeluaran Subkon (DO)',
            'filters'    => $filters,
            'dos'        => $this->Subkon_out_model->get_all($filters, $config['per_page'], $offset),
            'pagination' => $this->pagination->create_links(),
            'start_no'   => $offset + 1,
            'total_rows' => $config['total_rows']
        ];

        $this->load_views('subkon_out/index', $data);
    }

    /* ==========================================================
       CREATE — HEADER DO BARU
       ========================================================== */
    public function create() {
        if (!check_access('subkon_out', 'add')) { show_error('Akses ditolak', 403); }
        $data = [
            'title'    => 'Buat Pengeluaran Subkon Baru',
            // KIRIM DATA BC TYPES UNTUK DROPDOWN
            'bc_types' => $this->Commoncode_model->get_by_group('BC_TYPE')
        ];
        $this->load_views('subkon_out/create', $data);
    }

    public function save_header() {
        if (!check_access('subkon_out', 'add')) { show_error('Akses ditolak', 403); }

        $this->form_validation->set_rules('subkon_order_id', 'Kontrak Subkon', 'required|numeric');
        $this->form_validation->set_rules('shipping_date', 'Tanggal Kirim', 'required');
        
        // Tambah validasi BC
        $this->form_validation->set_rules('bc_type_code', 'Jenis BC', 'required');
        $this->form_validation->set_rules('bc_number', 'Nomor BC', 'required');
        $this->form_validation->set_rules('bc_date', 'Tanggal BC', 'required');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', 'Validasi gagal! ' . validation_errors());
            // Kita harus kirim ulang data BC Types jika validasi gagal
            $data = [
                'title'    => 'Buat Pengeluaran Subkon Baru',
                'bc_types' => $this->Commoncode_model->get_by_group('BC_TYPE')
            ];
            $this->load_views('subkon_out/create', $data);
            return;
        }
        
        $order = $this->Subkon_order_model->get_header_by_id($this->input->post('subkon_order_id'));
        if (!$order || $order->status != 'Confirmed') {
            $this->session->set_flashdata('error', 'Kontrak Subkon tidak valid atau belum dikonfirmasi.');
            redirect('subkon_out/create');
            return;
        }

        $data = [
            'subkon_out_no'   => $this->Subkon_out_model->generate_do_no(),
            'subkon_order_id' => $order->id,
            'supplier_id'     => $order->supplier_id,
            'shipping_date'   => $this->input->post('shipping_date'),
            'notes'           => $this->input->post('notes'),
            'status'          => 'Draft',
            'created_by'      => $this->session->userdata('user_id'),
            
            // TAMBAHAN DATA BC
            'bc_type_code'    => $this->input->post('bc_type_code'),
            'bc_number'       => $this->input->post('bc_number'),
            'bc_date'         => $this->input->post('bc_date')
        ];

        $new_id = $this->Subkon_out_model->insert_header($data);
        if ($new_id) {
            $this->session->set_flashdata('success', 'Surat Jalan Subkon berhasil dibuat. Silakan tambahkan item yang akan dikirim.');
            redirect('subkon_out/detail/' . $new_id);
        } else {
            $this->session->set_flashdata('error', 'Gagal menyimpan header DO Subkon.');
            redirect('subkon_out/create');
        }
    }

    /* ==========================================================
       DETAIL — ISI ITEM
       ========================================================== */
    public function detail($id) {
        if (!check_access('subkon_out', 'view')) show_error('Akses ditolak', 403);

        $header = $this->Subkon_out_model->get_header_by_id($id);
        if (!$header) show_404();

        $data = [
            'title'        => 'Detail Pengeluaran Subkon: ' . $header->subkon_out_no,
            'header'       => $header,
            'detail_items' => $this->Subkon_out_model->get_details_by_id($id),
            'bom_needed'   => $this->Subkon_out_model->get_needed_bom_items($header->subkon_order_id)
        ];

        $this->load_views('subkon_out/detail', $data);
    }

    /* ==========================================================
       ADD ITEM
       ========================================================== */
    public function add_item($do_id) {
        if (!check_access('subkon_out', 'edit')) show_error('Akses ditolak', 403);

        $header = $this->Subkon_out_model->get_header_by_id($do_id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya status DRAFT yang bisa diubah.');
            redirect('subkon_out/detail/' . $do_id);
            return;
        }

        $item_id_type = $this->input->post('item_id_type', true);
        $qty_kirim    = (float) $this->input->post('qty_kirim', true);
        $stock_on_hand_id = $this->input->post('stock_on_hand_id', true);

        if (empty($item_id_type) || strpos($item_id_type, '|') === false) {
            $this->session->set_flashdata('error', 'Item tidak valid. Pastikan memilih item dari daftar.');
            redirect('subkon_out/detail/' . $do_id);
            return;
        }

        list($item_id, $item_type) = explode('|', $item_id_type, 2);
        if ($qty_kirim <= 0) {
            $this->session->set_flashdata('error', 'Qty kirim harus lebih besar dari 0.');
            redirect('subkon_out/detail/' . $do_id);
            return;
        }

        $unit_value = 0;
        $stock_on_hand_id = $stock_on_hand_id ?: NULL;

        try {
            if ($item_type === 'material') {
                if (empty($stock_on_hand_id)) {
                    $this->session->set_flashdata('error', 'Material harus memilih BATCH (Stock on Hand).');
                    redirect('subkon_out/detail/' . $do_id); return;
                }
                $batch_info = $this->Subkon_out_model->get_material_batch_info($stock_on_hand_id);
                if (!$batch_info) {
                    $this->session->set_flashdata('error', 'Batch tidak ditemukan.');
                    redirect('subkon_out/detail/' . $do_id); return;
                }
                if ((float)$batch_info->available_qty < $qty_kirim) {
                    $this->session->set_flashdata('error', 'Stok batch tidak mencukupi. Sisa: ' . number_format($batch_info->available_qty, 2));
                    redirect('subkon_out/detail/' . $do_id); return;
                }
                $unit_value = (float)$batch_info->unit_value;
            } else {
                $sfg_balance = $this->Subkon_out_model->get_sfg_wip_balance($item_id);
                if ($sfg_balance < $qty_kirim) {
                    $this->session->set_flashdata('error', 'Stok WIP (SFG) tidak mencukupi. Sisa: ' . number_format($sfg_balance, 2));
                    redirect('subkon_out/detail/' . $do_id); return;
                }
                $unit_value = (float)$this->Stock_ledger_model->get_last_unit_value($item_id, $item_type);
            }

            $item_master = $this->Stock_ledger_model->get_item_master_detail($item_id_type);
            if (!$item_master) {
                $this->session->set_flashdata('error', 'Item master tidak ditemukan.');
                redirect('subkon_out/detail/' . $do_id);
                return;
            }

            $data = [
                'subkon_out_id'    => $do_id,
                'item_id'          => $item_id,
                'item_type'        => $item_type,
                'qty_kirim'        => $qty_kirim,
                'satuan'           => $item_master->item_unit,
                'unit_value'       => $unit_value,
                'total_value'      => $qty_kirim * $unit_value,
                'stock_on_hand_id' => $stock_on_hand_id
            ];

            if ($this->Subkon_out_model->insert_item_to_do($data)) {
                $this->session->set_flashdata('success', 'Item berhasil ditambahkan ke Surat Jalan.');
            } else {
                $err = $this->db->error();
                log_message('error', 'Gagal insert item DO: ' . $err['message']);
                $this->session->set_flashdata('error', 'Gagal menambah item. Cek log aplikasi.');
            }
        } catch (Exception $ex) {
            log_message('error', 'Exception add_item: ' . $ex->getMessage());
            $this->session->set_flashdata('error', 'Terjadi kesalahan saat menambahkan item.');
        }

        redirect('subkon_out/detail/' . $do_id);
    }

    /* ==========================================================
       DELETE ITEM
       ========================================================== */
    public function delete_item($do_id, $detail_id) {
        if (!check_access('subkon_out', 'delete')) show_error('Akses ditolak', 403);

        $this->Subkon_out_model->delete_item_from_do($detail_id);
        $this->session->set_flashdata('success', 'Item berhasil dihapus dari Surat Jalan.');
        redirect('subkon_out/detail/' . $do_id);
    }

    /* ==========================================================
       KONFIRMASI & UNCONFIRM
       ========================================================== */
    public function confirm($id) {
        if (!check_access('subkon_out', 'edit')) show_error('Akses ditolak', 403);

        $header = $this->Subkon_out_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya status DRAFT yang bisa dikonfirmasi.');
            redirect('subkon_out/detail/' . $id);
            return;
        }

        $result = $this->Subkon_out_model->confirm_do($id, $this->session->userdata('user_id'));
        if ($result) {
            $this->session->set_flashdata('success', 'Surat Jalan Subkon berhasil dikonfirmasi. Stok telah dipotong.');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengonfirmasi. Cek log atau stok.');
        }
        redirect('subkon_out/detail/' . $id);
    }

    public function unconfirm($id) {
        if (!check_access('subkon_out', 'edit')) show_error('Akses ditolak', 403);

        $result = $this->Subkon_out_model->unconfirm_do($id);
        if ($result) {
            $this->session->set_flashdata('success', 'Konfirmasi Surat Jalan berhasil dibatalkan. Stok dikembalikan.');
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan konfirmasi.');
        }
        redirect('subkon_out/detail/' . $id);
    }

    /* ==========================================================
       AJAX ENDPOINTS
       ========================================================== */
    public function ajax_get_bom_items($order_id) {
        if (!$this->input->is_ajax_request()) show_error('No direct access', 403);

        $term = $this->input->get('q', true);
        $data = $this->Subkon_out_model->get_needed_bom_items($order_id);

        $results = [];
        foreach ($data as $row) {
            if (!empty($term) && stripos($row->item_name, $term) === false) continue;
            $results[] = [
                'id' => $row->item_id . '|' . $row->item_type,
                'text' => $row->item_name . ' (' . $row->satuan . ') — Sisa: ' . number_format($row->sisa_butuh, 2)
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['results' => $results]);
        exit;
    }

    public function ajax_get_material_batches() {
        if (!$this->input->is_ajax_request()) show_error('No direct access', 403);

        $item_id = $this->input->get('item_id', true);
        $data = $this->Subkon_out_model->get_available_stock_material($item_id);

        $results = [];
        foreach ($data as $r) {
            $results[] = [
                'stock_on_hand_id' => $r->stock_on_hand_id,
                'grn_number'       => $r->grn_number,
                'available_qty'    => (float)$r->available_qty,
                'unit_value'       => (float)$r->unit_value
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['results' => $results]);
        exit;
    }

    public function ajax_search_confirmed_orders() {
        if (!$this->input->is_ajax_request()) show_error('No direct access', 403);

        $term = $this->input->get('q', true);
        $data = $this->Subkon_out_model->search_confirmed_orders($term);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['results' => $data]);
        exit;
    }

    /* ==========================================================
       UTIL
       ========================================================== */
    private function load_views($main_view, $data = []) {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($main_view, $data);
        $this->load->view('templates/footer');
    }
}
