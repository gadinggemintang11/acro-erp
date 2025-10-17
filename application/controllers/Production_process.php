<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class Production_process extends CI_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        // Pastikan Scrap_model di-load jika belum (sebelumnya ada di detail())
        $this->load->model(['Production_model', 'Mr_model', 'Wo_model', 'Scrap_model']);
        $this->load->helper('url');
    }

    public function index() {
        if (!check_access('production_process', 'view')) { show_error('Tidak ada izin.', 403); }

        $filters = ['search' => $this->input->get('search')];
        $data = [
            'title'     => 'Proses Produksi',
            'processes' => $this->Production_model->get_all($filters),
            'filters'   => $filters
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('production_process/index', $data);
        $this->load->view('templates/footer');
    }

    public function ajax_get_wo_details() {
        $wo_id = $this->input->get('wo_id');
        if (!$wo_id) { echo json_encode(['success' => false]); return; }

        $wo_details = $this->Production_model->get_wo_details_for_prod($wo_id);
        if ($wo_details) {
            echo json_encode(['success' => true, 'details' => $wo_details]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    public function search_open_wos() {
        $term = $this->input->get('q');
        $wos = $this->Production_model->search_open_wos($term);
        $results = [];
        foreach ($wos as $wo) {
            $results[] = [
                'id' => $wo->id,
                'text' => htmlspecialchars($wo->wo_number . ' - ' . $wo->fg_name)
            ];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }

    public function create_header() {
        if (!check_access('production_process', 'add')) { show_error('Tidak ada izin.', 403); }

        $wo_id = $this->input->post('wo_header_id');
        $wo_details = $this->Production_model->get_wo_details_for_prod($wo_id);
        if (!$wo_details) {
            $this->session->set_flashdata('error', 'Work Order tidak valid!');
            redirect('production_process');
        }

        $data = [
            'production_number' => $this->Production_model->generate_prod_number(),
            'wo_header_id'      => $wo_id,
            'fg_id'             => $wo_details['fg_id'],
            'planned_qty'       => $wo_details['planned_qty'],
            'production_date'   => $this->input->post('production_date'),
            'remarks'           => $this->input->post('remarks'),
            'status'            => 'Draft',
            'created_by'        => $this->session->userdata('user_id') ?? null
        ];

        $new_prod_id = $this->Production_model->insert_header($data);
        if ($new_prod_id) {
            redirect('production_process/detail/' . $new_prod_id);
        } else {
            $this->session->set_flashdata('error', 'Gagal membuat Proses Produksi!');
            redirect('production_process');
        }
    }

    public function detail($id) {
        if (!check_access('production_process', 'view')) { show_error('Tidak ada izin.', 403); }
        if (empty($id)) show_404();

        $header = $this->Production_model->get_header_by_id($id);
        if (!$header) show_404();

        // PERBAIKAN: Memanggil fungsi baru yang menghitung sisa stok
        $wip_materials = $this->Production_model->get_wip_materials_with_remaining(
            $header->wo_header_id, 
            $id, 
            'cutting'
        );

        $data = [
            'title'           => 'Detail Proses Produksi',
            'header'          => $header,
            'wip_materials'   => $wip_materials, // Mengirim data WIP yg sudah dihitung
            'cutting_inputs'  => $this->Production_model->get_production_inputs($id, 'cutting'),
            'cutting_outputs' => $this->Production_model->get_production_outputs($id, 'cutting'),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('production_process/detail', $data);
        $this->load->view('templates/footer');
    }

    /**
     * PERBAIKAN KRITIS:
     * Validasi stok sekarang menggunakan sisa stok yang sebenarnya.
     * Mengirim 'item_id' dan 'item_type' ke model.
     */
    public function save_material_usage($prod_id) {
        if (!check_access('production_process', 'edit')) { show_error('Tidak ada izin.', 403); }

        $header = $this->Production_model->get_header_by_id($prod_id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya status Draft yang bisa diubah.');
            redirect('production_process/detail/' . $prod_id);
        }

        // Ambil data BATCH dari form
        $mr_detail_id = $this->input->post('mr_detail_id'); // <-- KUNCI
        $item_id      = $this->input->post('item_id');
        $item_type    = $this->input->post('item_type');
        $qty_used     = (float) $this->input->post('qty_used');

        if (empty($mr_detail_id) || empty($item_id) || empty($item_type) || $qty_used <= 0) {
            $this->session->set_flashdata('error', 'Data tidak lengkap atau Qty tidak valid.');
            redirect('production_process/detail/' . $prod_id);
        }

        // 1. Ambil data sisa stok yang SEBENARNYA (per batch)
        $wip_materials = $this->Production_model->get_wip_materials_with_remaining($header->wo_header_id, $prod_id, 'cutting');
        
        $true_available_qty = 0;
        foreach ($wip_materials as $batch) {
            if ($batch->mr_detail_id == $mr_detail_id) { // <-- Validasi per batch
                $true_available_qty = $batch->remaining_qty;
                break;
            }
        }

        // 2. Validasi dengan sisa stok yang benar
        if ($qty_used > $true_available_qty) {
            $this->session->set_flashdata('error', 'Gagal, Qty terpakai (' . number_format($qty_used, 4) . ') melebihi Sisa Stok Batch ini (' . number_format($true_available_qty, 4) . ').');
            redirect('production_process/detail/' . $prod_id);
            return;
        }

        // 3. Siapkan data, TERMASUK mr_detail_id
        $data = [
            'production_header_id' => $prod_id,
            'stage_name'           => 'cutting',
            'item_id'              => $item_id,       
            'item_type'            => $item_type,   
            'mr_detail_id'         => $mr_detail_id,  // <-- PENTING (FIX 1048)
            'qty_used'             => $qty_used,
            'notes'                => $this->input->post('notes')
        ];

        if ($this->Production_model->save_material_usage($data)) {
            $this->session->set_flashdata('success', 'Pemakaian material dicatat.');
        } else {
            $this->session->set_flashdata('error', 'Gagal mencatat pemakaian (Database Error).');
        }
        redirect('production_process/detail/' . $prod_id);
    }

    /**
     * PERBAIKAN (LOGIKA):
     * Menyederhanakan logika, tidak perlu $output_type.
     * Cukup andalkan 'item_type' dari value dropdown.
     */
    public function save_cutting_output($prod_id) {
        if (!check_access('production_process', 'edit')) { show_error('Tidak ada izin.', 403); }

        $header = $this->Production_model->get_header_by_id($prod_id);
        if (!$header || $header->status != 'Draft') { redirect('production_process/detail/' . $prod_id); }

        $item_value = $this->input->post('item_id'); // Format: "id|type"
        $qty = (float) $this->input->post('qty');

        if (empty($item_value) || $qty <= 0) {
             $this->session->set_flashdata('error', 'Item atau Qty tidak valid.');
             redirect('production_process/detail/' . $prod_id);
        }

        $item_data = explode('|', $item_value);
        $item_id   = $item_data[0];
        $item_type = $item_data[1]; // 'semifinished' atau 'scrap'

        $data = [
            'production_header_id' => $prod_id,
            'stage_name'           => 'cutting',
            'item_id'              => $item_id,
            'item_type'            => $item_type,
            'qty_produced'         => ($item_type == 'semifinished') ? $qty : 0,
            'qty_scrap'            => ($item_type == 'scrap') ? $qty : 0,
            'received_by'          => $this->session->userdata('user_id') ?? null,
            'received_at'          => date('Y-m-d H:i:s')
        ];

        if ($this->Production_model->save_production_output($data)) {
            $this->session->set_flashdata('success', 'Hasil output cutting dicatat.');
        } else {
            $this->session->set_flashdata('error', 'Gagal mencatat hasil cutting.');
        }
        redirect('production_process/detail/' . $prod_id);
    }

    public function confirm_qc_cutting($prod_id, $output_id) {
        if (!check_access('production_process', 'edit')) { show_error('Tidak ada izin.', 403); }
        $user_id = $this->session->userdata('user_id') ?? null;
        if ($this->Production_model->confirm_qc_output($output_id, $user_id)) {
            $this->session->set_flashdata('success', 'Hasil cutting telah di-QC.');
        } else {
            $this->session->set_flashdata('error', 'Gagal konfirmasi QC.');
        }
        redirect('production_process/detail/' . $prod_id);
    }

    // Fungsi search terpisah untuk Semi-Finished
    public function search_semifinished() {
        $term = $this->input->get('q');
        $items = $this->Production_model->search_semifinished_goods($term);
        $results = [];
        foreach ($items as $item) {
            $results[] = ['id' => $item->id . '|' . $item->type, 'text' => htmlspecialchars($item->name . ' (' . $item->code . ')')];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }

    // Fungsi search terpisah untuk Scrap
    public function search_scrap() {
        $term = $this->input->get('q');
        $items = $this->Production_model->search_scrap_items($term);
        $results = [];
        foreach ($items as $item) {
            $results[] = ['id' => $item->id . '|' . $item->type, 'text' => htmlspecialchars($item->name . ' (' . $item->code . ')')];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }

    /**
     * FUNGSI BARU (Opsional tapi Direkomendasikan):
     * Menggabungkan search_semifinished dan search_scrap untuk satu dropdown Select2.
     * Jika Anda ingin 2 dropdown terpisah, hapus fungsi ini dan sesuaikan view.
     */
    public function search_all_outputs() {
        $term = $this->input->get('q');
        
        $semifinished = $this->Production_model->search_semifinished_goods($term);
        $scraps = $this->Production_model->search_scrap_items($term);

        $results = [];
        $results[] = ['text' => '--- Semi-Finished Goods ---', 'children' => []];
        foreach ($semifinished as $item) {
            $results[0]['children'][] = [
                'id' => $item->id . '|' . $item->type, 
                'text' => htmlspecialchars($item->name . ' (' . $item->code . ')')
            ];
        }
        
        $results[] = ['text' => '--- Scrap Items ---', 'children' => []];
        foreach ($scraps as $item) {
             $results[1]['children'][] = [
                'id' => $item->id . '|' . $item->type, 
                'text' => htmlspecialchars($item->name . ' (' . $item->code . ')')
            ];
        }

        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }


    public function update_header($id) {
        if (!check_access('production_process', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Production_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Header tidak bisa diedit!');
            redirect('production_process');
        }
        $data = [
            'production_date' => $this->input->post('production_date'),
            'remarks'         => $this->input->post('remarks'),
        ];
        if ($this->Production_model->update_header($id, $data)) {
            $this->session->set_flashdata('success', 'Header diperbarui.');
        } else {
            $this->session->set_flashdata('error', 'Gagal update header.');
        }
        redirect('production_process');
    }

    public function delete_header($id) {
        if (!check_access('production_process', 'delete')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Production_model->get_header_by_id($id);
        if (!$header) { show_404(); }

        if ($header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya status Draft yang bisa dihapus!');
            redirect('production_process');
        }

        // Memanggil fungsi delete_header baru yang sudah aman (dengan transaksi)
        if ($this->Production_model->delete_header($id)) {
            $this->session->set_flashdata('success', 'Proses produksi (dan semua entri stok terkait) telah dihapus/dibatalkan.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus (Database Error).');
        }
        redirect('production_process');
    }

    public function export_pdf($id) { echo "Fungsi Export PDF untuk ID: $id (Segera Hadir)"; }
    public function export_excel($id) { echo "Fungsi Export Excel untuk ID: $id (Segera Hadir)"; }
    public function unconfirm_qc_cutting($prod_id, $output_id) {
        if (!check_access('production_process', 'edit')) { show_error('Tidak ada izin.', 403); }
        if ($this->Production_model->unconfirm_qc_output($output_id)) {
            $this->session->set_flashdata('success', 'Status QC dibatalkan.');
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan QC.');
        }
        redirect('production_process/detail/' . $prod_id);
    }

    /**
     * FUNGSI BARU UNTUK HAPUS MATERIAL USAGE
     */
    public function delete_material_usage($prod_id, $input_id) {
        if (!check_access('production_process', 'delete')) { show_error('Tidak ada izin.', 403); }
        
        $header = $this->Production_model->get_header_by_id($prod_id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya status Draft yang bisa dihapus!');
            redirect('production_process/detail/' . $prod_id);
        }

        if ($this->Production_model->delete_input_by_id($input_id)) {
            $this->session->set_flashdata('success', 'Data pemakaian material dihapus (stok dikembalikan).');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus data.');
        }
        redirect('production_process/detail/' . $prod_id);
    }

    /**
     * FUNGSI BARU UNTUK HAPUS CUTTING OUTPUT
     */
    public function delete_cutting_output($prod_id, $output_id) {
        if (!check_access('production_process', 'delete')) { show_error('Tidak ada izin.', 403); }

        $header = $this->Production_model->get_header_by_id($prod_id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya status Draft yang bisa dihapus!');
            redirect('production_process/detail/' . $prod_id);
        }

        if ($this->Production_model->delete_output_by_id($output_id)) {
            $this->session->set_flashdata('success', 'Data output cutting dihapus (stok dikembalikan).');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus data.');
        }
        redirect('production_process/detail/' . $prod_id);
    }
}