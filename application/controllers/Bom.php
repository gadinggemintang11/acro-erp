<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

class Bom extends MY_Controller {

    /**
     * Tentukan nama modul untuk logging
     */
    protected $module_name = 'Bill of Materials';


    public function __construct() {
        parent::__construct(); 
        check_login();
        $this->load->model(['Bom_model', 'Fg_model']);
        $this->load->helper('url');
        $this->load->library('session');
        $this->load->library('form_validation'); 
    }

    // Halaman utama daftar BOM
    public function index() {
        if (!check_access('bom', 'view')) { show_error('Anda tidak memiliki izin.', 403); return;}

        $filters = ['search' => $this->input->get('search')];
        $data = [
            'title' => lang_line('title_bom_index'),
            'boms'  => $this->Bom_model->get_all($filters),
            'available_fgs' => $this->Fg_model->get_fgs_without_bom(),
            'filters' => $filters
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('bom/index', $data);
        $this->load->view('templates/footer');
    }

    // Proses buat header BOM baru
    public function create_header() {
        if (!check_access('bom', 'add')) { show_error('Tidak ada izin.', 403); return;}

        $this->form_validation->set_rules('fg_id', 'Style (Finished Good)', 'required|numeric');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('bom');
            return;
        }

        $fg_id = $this->input->post('fg_id');

        if ($this->Bom_model->check_bom_exists_for_fg($fg_id)) {
            $this->session->set_flashdata('error', 'Style ini sudah memiliki BOM!');
            redirect('bom');
            return;
        }

        $data = [
            'fg_id'      => $fg_id,
            'status'     => 'Draft', // Selalu 'Draft' saat dibuat
            'revision'   => 1, // Revisi awal
            'created_by' => $this->session->userdata('user_id') ?? null
        ];

        $new_bom_id = $this->Bom_model->insert_header($data);

        if ($new_bom_id) {
            // --- LOGGING (CREATE) ---
            $fg_data = $this->db->get_where('finished_goods', ['id' => $fg_id])->row();
            $deskripsi = "Membuat BOM header (ID: $new_bom_id) untuk FG: " . ($fg_data->nama_barang ?? $fg_id);
            $this->_log('CREATE', $deskripsi);
            // --- END LOG ---

            $this->session->set_flashdata('success', 'Header BOM berhasil dibuat. Silakan tambahkan komponen.');
            redirect('bom/detail/' . $new_bom_id);
        } else {
            $this->session->set_flashdata('error', 'Gagal membuat header BOM!');
            redirect('bom');
        }
    }

    // Halaman detail BOM untuk kelola komponen
    public function detail($id) {
        if (!check_access('bom', 'view')) { show_error('Tidak ada izin.', 403); return;}
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Bom_model->get_header_by_id($id);
        if (!$header) show_404();

        $data = [
            'title'   => lang_line('title_bom_detail'),
            'header'  => $header,
            'details' => $this->Bom_model->get_details_by_header_id($id)
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('bom/detail', $data); 
        $this->load->view('templates/footer'); 
    }

    // AJAX: Cari komponen (material & semifinished)
    public function search_components() {
        if (!$this->input->is_ajax_request()) { exit('No direct script access allowed'); }
        $term = $this->input->get('q');
        $items = $this->Bom_model->search_components($term);
        $results = [];
        foreach ($items as $item) {
            $results[] = ['id' => $item->id . '|' . $item->type, 'text' => htmlspecialchars($item->name . ' (' . $item->code . ')')];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }

    // Proses tambah item komponen ke BOM (Hanya Draft)
    public function add_item($bom_header_id) {
        if (!check_access('bom', 'edit')) { show_error('Tidak ada izin.', 403); return;}
        if (empty($bom_header_id) || !is_numeric($bom_header_id)) show_404();

        $is_ajax = $this->input->is_ajax_request();
        $header = $this->Bom_model->get_header_by_id($bom_header_id);
        if (!$header || strtoupper(trim($header->status)) != 'DRAFT') {
             $msg = 'Komponen hanya bisa ditambahkan saat status BOM Draft!';
             if ($is_ajax) {
                 return $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => $msg]));
             }
             $this->session->set_flashdata('error', $msg);
             redirect('bom/detail/' . $bom_header_id);
             return;
        }

        // Validasi Form
        $this->form_validation->set_rules('component_id', 'Komponen', 'required');
        $this->form_validation->set_rules('quantity_required', 'Kuantitas', 'required|numeric|greater_than[0]');

        if ($this->form_validation->run() == FALSE) {
             $msg = validation_errors();
             if ($is_ajax) {
                 return $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => $msg]));
             }
             $this->session->set_flashdata('error', $msg);
             redirect('bom/detail/' . $bom_header_id);
             return;
        }

        $item_data = explode('|', $this->input->post('component_id'));
        if (count($item_data) < 2) {
             $msg = 'Komponen yang dipilih tidak valid!';
             if ($is_ajax) {
                 return $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => $msg]));
             }
             $this->session->set_flashdata('error', $msg);
             redirect('bom/detail/' . $bom_header_id);
             return;
        }
        $item_id = $item_data[0];
        $item_type = $item_data[1];
        $quantity = (float) $this->input->post('quantity_required');


        $data = [
            'bom_header_id'       => $bom_header_id,
            'component_item_id'   => $item_id,
            'component_item_type' => $item_type,
            'quantity_required'   => $quantity,
            'specification'       => $this->input->post('specification'),
        ];

        if ($this->Bom_model->insert_item($data)) {
            // --- LOGGING (UPDATE) ---
            $deskripsi = "Menambah item (ID: $item_id, Tipe: $item_type, Qty: $quantity) ke BOM ID: $bom_header_id";
            $this->_log('UPDATE', $deskripsi);
            // --- END LOG ---

            $msg = 'Komponen berhasil ditambahkan.';
            if ($is_ajax) {
                return $this->output->set_content_type('application/json')->set_output(json_encode(['success' => true, 'message' => $msg]));
            }
            $this->session->set_flashdata('success', $msg);
        } else {
            $msg = 'Gagal menambah komponen (mungkin sudah ada atau item tidak valid?).';
            if ($is_ajax) {
                return $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => $msg]));
            }
            $this->session->set_flashdata('error', $msg);
        }
        redirect('bom/detail/' . $bom_header_id);
    }

    /**
     * Update kuantitas item komponen (Hanya Draft)
     */
    public function update_item($bom_header_id, $detail_id) {
        if (!check_access('bom', 'edit')) { show_error('Tidak ada izin.', 403); return;}
        if (empty($bom_header_id) || empty($detail_id) || !is_numeric($bom_header_id) || !is_numeric($detail_id)) show_404();

        $is_ajax = $this->input->is_ajax_request();
        $input_name = 'quantity_required_' . $detail_id;
        $new_quantity_str = $this->input->post($input_name);

        if ($new_quantity_str === null || !is_numeric($new_quantity_str) || (float)$new_quantity_str <= 0) {
             $msg = 'Kuantitas harus berupa angka positif!';
             if ($is_ajax) {
                 return $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => $msg]));
             }
             $this->session->set_flashdata('error', $msg);
             redirect('bom/detail/' . $bom_header_id);
             return;
        }
        $new_quantity = (float)$new_quantity_str;

        $data_update = ['quantity_required' => $new_quantity];

        if ($this->Bom_model->update_item($detail_id, $data_update)) {
            // --- LOGGING (UPDATE) ---
            $deskripsi = "Update kuantitas item (Detail ID: $detail_id) menjadi $new_quantity pada BOM ID: $bom_header_id";
            $this->_log('UPDATE', $deskripsi);
            // --- END LOG ---

            $msg = 'Kuantitas komponen berhasil diperbarui.';
            if ($is_ajax) {
                return $this->output->set_content_type('application/json')->set_output(json_encode(['success' => true, 'message' => $msg]));
            }
            $this->session->set_flashdata('success', $msg);
        } else {
            $msg = 'Gagal memperbarui kuantitas (BOM mungkin tidak lagi Draft).';
            if ($is_ajax) {
                return $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => $msg]));
            }
            $this->session->set_flashdata('error', $msg);
        }
        redirect('bom/detail/' . $bom_header_id);
    }


    // Proses hapus item komponen dari BOM (Hanya Draft)
    public function delete_item($bom_header_id, $detail_id) {
        if (!check_access('bom', 'edit')) { show_error('Tidak ada izin.', 403); return;}
        if (empty($bom_header_id) || empty($detail_id) || !is_numeric($bom_header_id) || !is_numeric($detail_id)) show_404();

        $is_ajax = $this->input->is_ajax_request();
        $header = $this->Bom_model->get_header_by_id($bom_header_id);
        if (!$header || strtoupper(trim($header->status)) != 'DRAFT') {
             $msg = 'Komponen hanya bisa dihapus saat status BOM Draft!';
             if ($is_ajax) {
                 return $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => $msg]));
             }
             $this->session->set_flashdata('error', $msg);
             redirect('bom/detail/' . $bom_header_id);
             return;
        }

        $item_info = $this->Bom_model->get_detail_item_by_id($detail_id);

        if ($this->Bom_model->delete_item($detail_id)) {
            // --- LOGGING (UPDATE) ---
            $deskripsi = "Menghapus item (Detail ID: $detail_id, Tipe: " . ($item_info->component_item_type ?? 'N/A') . ") dari BOM ID: $bom_header_id";
            $this->_log('UPDATE', $deskripsi);
            // --- END LOG ---

            $msg = 'Komponen berhasil dihapus.';
            if ($is_ajax) {
                return $this->output->set_content_type('application/json')->set_output(json_encode(['success' => true, 'message' => $msg]));
            }
            $this->session->set_flashdata('success', $msg);
        } else {
            $msg = 'Gagal menghapus komponen.';
            if ($is_ajax) {
                return $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => $msg]));
            }
            $this->session->set_flashdata('error', $msg);
        }
        redirect('bom/detail/' . $bom_header_id);
    }

    // Proses hapus BOM header (dan detailnya) (Hanya Draft)
    public function delete_bom($id) {
        if (!check_access('bom', 'delete')) { show_error('Tidak ada izin.', 403); return;}
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Bom_model->get_header_by_id($id);
        if (!$header || strtoupper(trim($header->status)) != 'DRAFT') {
            $this->session->set_flashdata('error', 'Hanya BOM status Draft yang bisa dihapus!');
            redirect('bom');
            return;
        }

        if ($this->Bom_model->delete_header($id)) {
            // --- LOGGING (DELETE) ---
            $deskripsi = "Menghapus BOM (ID: $id) untuk FG: $header->fg_name (Status: $header->status)";
            $this->_log('DELETE', $deskripsi);
            // --- END LOG ---
            
            $this->session->set_flashdata('success', 'BOM berhasil dihapus (termasuk semua komponennya).');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus BOM.');
        }
        redirect('bom'); 
    }

    // Proses Konfirmasi/Aktivasi BOM (Draft -> Active)
    public function confirm_bom($id) {
        if (!check_access('bom', 'confirm')) { show_error('Anda tidak memiliki izin.', 403); return; }
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Bom_model->get_header_by_id($id);
        if (!$header || strtoupper(trim($header->status)) != 'DRAFT') {
            $this->session->set_flashdata('warning', 'Hanya BOM status Draft yang bisa diaktifkan.');
            redirect('bom/detail/' . $id);
            return;
        }
        $details = $this->Bom_model->get_details_by_header_id($id);
        if(empty($details)){
            $this->session->set_flashdata('error', 'BOM tidak bisa diaktifkan karena belum ada komponen.');
            
            // =========================================================
            // PERBAIKAN ERROR DI SINI (Line 283)
            // Hilangkan tanda kutip ganda dan spasi yang tidak perlu
            // =========================================================
            redirect('bom/detail/' . $id); 
            // =========================================================
            // AKHIR PERBAIKAN
            // =========================================================

            return;
        }

        if ($this->Bom_model->update_status($id, 'Active')) {
            // --- LOGGING (APPROVE) ---
            $deskripsi = "Approve BOM (ID: $id) untuk FG: $header->fg_name. Status: Draft -> Active";
            $this->_log('APPROVE', $deskripsi);
            // --- END LOG ---
            
            $this->session->set_flashdata('success', 'BOM berhasil diaktifkan.');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengaktifkan BOM!');
        }
        redirect('bom/detail/' . $id);
    }

    /**
     * FUNGSI BARU: Deaktivasi BOM (Active -> Draft)
     */
    public function deactivate_bom($id) {
        if (!check_access('bom', 'edit')) { show_error('Anda tidak memiliki izin.', 403); return; }
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Bom_model->get_header_by_id($id);
        if (!$header || strtoupper(trim($header->status)) != 'ACTIVE') {
            $this->session->set_flashdata('warning', 'Hanya BOM status Active yang bisa dikembalikan ke Draft.');
            redirect('bom/detail/' . $id);
            return;
        }
        
        // Cek apakah BOM ini sedang dipakai di Work Order Aktif (PENTING!)
        // $is_used = $this->db->where('bom_header_id', $id)->where('status', 'WIP')->count_all_results('wo_header');
        // if ($is_used > 0) {
        //     $this->session->set_flashdata('error', 'BOM tidak bisa dikembalikan ke Draft karena sedang dipakai di Work Order (WIP).');
        //     redirect('bom/detail/' . $id);
        //     return;
        // }

        if ($this->Bom_model->update_status($id, 'Draft')) {
            // --- LOGGING (UPDATE) ---
            $deskripsi = "Deaktivasi BOM (ID: $id) untuk FG: $header->fg_name. Status: Active -> Draft";
            $this->_log('UPDATE', $deskripsi);
            // --- END LOG ---
            
            $this->session->set_flashdata('success', 'BOM berhasil dikembalikan ke status Draft.');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengembalikan BOM ke Draft!');
        }
        redirect('bom/detail/' . $id);
    }


    // Fungsi Export PDF (Tidak berubah signifikan)
    public function export_pdf($id) {
        if (!check_access('bom', 'view')) { show_error('Tidak ada izin.', 403); return;}
        if (empty($id) || !is_numeric($id)) show_404();
        $header = $this->Bom_model->get_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Bom_model->get_details_by_header_id($id);

        $data = ['header' => $header, 'details' => $details];
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $html = $this->load->view('bom/pdf_template', $data, true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = "BOM_".str_replace(['/','\\'], '-', ($header->fg_code ?? $header->fg_id)).".pdf";
        $dompdf->stream($filename, ["Attachment" => 0]);
        exit;
    }

    // === DOCUMENTS ===

    public function upload_document($bom_id) {
        if (!$this->input->is_ajax_request()) show_404();
        if (!check_access('bom', 'edit')) return $this->_json(['success' => false, 'message' => 'Akses ditolak']);

        $header = $this->Bom_model->get_header_by_id($bom_id);
        if (!$header || strtoupper(trim($header->status)) != 'DRAFT') {
            return $this->_json(['success' => false, 'message' => 'BOM harus dalam status Draft']);
        }

        $this->load->library('upload');
        $this->lang->load('upload', 'english');

        $config = [
            'upload_path' => FCPATH . 'uploads/bom_documents/',
            'allowed_types' => 'pdf|jpg|jpeg|png|gif',
            'max_size' => 10240,
            'encrypt_name' => TRUE
        ];

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('document')) {
            return $this->_json(['success' => false, 'message' => 'Upload gagal: ' . $this->upload->display_errors()]);
        }

        $upload_data = $this->upload->data();

        $doc_data = [
            'bom_header_id' => $bom_id,
            'file_name' => $upload_data['orig_name'],
            'file_path' => 'uploads/bom_documents/' . $upload_data['file_name'],
            'file_type' => $upload_data['file_type'],
            'file_size' => $upload_data['file_size'],
            'description' => $this->input->post('description'),
            'uploaded_by' => $this->session->userdata('user_id')
        ];

        $insert_result = $this->Bom_model->insert_document($doc_data);

        if ($insert_result) {
            error_log('[BOM Upload] File uploaded: ' . $upload_data['file_name'] . ' for BOM ID: ' . $bom_id);
            return $this->_json(['success' => true, 'message' => 'Dokumen berhasil diupload']);
        } else {
            error_log('[BOM Upload] Failed to insert document record for BOM ID: ' . $bom_id);
            return $this->_json(['success' => false, 'message' => 'Gagal menyimpan data dokumen']);
        }
    }

    public function delete_document($doc_id) {
        if (!$this->input->is_ajax_request()) show_404();
        if (!check_access('bom', 'edit')) return $this->_json(['success' => false, 'message' => 'Akses ditolak']);

        $doc = $this->Bom_model->get_document_by_id($doc_id);
        if (!$doc) return $this->_json(['success' => false, 'message' => 'Dokumen tidak ditemukan']);

        if ($this->Bom_model->delete_document($doc_id)) {
            @unlink(FCPATH . $doc->file_path);
            return $this->_json(['success' => true, 'message' => 'Dokumen berhasil dihapus']);
        }
        return $this->_json(['success' => false, 'message' => 'Gagal menghapus dokumen']);
    }

    public function get_documents($bom_id) {
        if (!$this->input->is_ajax_request()) show_404();
        if (!check_access('bom', 'view')) return $this->_json(['success' => false, 'message' => 'Akses ditolak']);

        $documents = $this->Bom_model->get_documents_by_bom($bom_id);
        return $this->_json(['success' => true, 'documents' => $documents]);
    }

    // Helper: Return JSON response
    private function _json($data) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

} // End Class

