<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

class Material_issuance extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        // Load model (Mr_model mungkin tidak perlu jika header diambil dari Material_issuance_model)
        // Tapi kita biarkan dulu jika Mr_model punya fungsi get_header_by_id
        $this->load->model(['Mr_model', 'Material_issuance_model', 'Stock_ledger_model']);
        $this->load->helper('url');
        $this->load->library('session'); // Pastikan session di-load
    }

    

    // Halaman daftar MR (Submitted, Issued, Received)
    public function index() {
        if (!check_access('material_issuance', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
            return; // Tambahkan return
        }
        
        $filters = [
            'search' => $this->input->get('search'),
            'status' => $this->input->get('status')
        ];

        $data = [
            'title'         => lang_line('title_mi_index'),
            'mrs'           => $this->Material_issuance_model->get_all_mrs($filters),
            'filters'       => $filters,
            'status_counts' => $this->Material_issuance_model->count_by_status(),
            'dept_names'    => department_names(),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_issuance/index', $data);
        $this->load->view('templates/footer');
    }

    // Halaman detail untuk input Qty Gudang
    public function detail($mr_id) {
        if (!check_access('material_issuance', 'edit')) { show_error('Tidak ada izin.', 403); return; }
        if (empty($mr_id) || !is_numeric($mr_id)) { show_404(); } // Validasi ID

        // Ambil header dari Mr_model (seperti kode Anda)
        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || !in_array($header->status, ['Approved', 'Partial Issued', 'Partial Received', 'Issued', 'Received'])) {
            $this->session->set_flashdata('error', 'Material Request ini tidak valid atau status tidak sesuai (belum lolos approval).');
            redirect('material_issuance');
            return;
        }

        // "Buat PR Sisanya" — padanan jalur IRF/general (user 2026-09-21:
        // "ketika stock tidak tercukupi kan juga dibuat PR, logikanya harusnya
        // sama dengan general kan?"). Qty PR per baris = sisa MRF dikurangi
        // stok gudang & qty yang sudah ada di PR aktif, jadi PR ganda tidak
        // terjadi. Lihat Material_outstanding_model::get_pr_need_map().
        $this->load->model('Material_outstanding_model');
        $mr_detail_ids = array_map(function ($d) { return (int) $d->id; },
            $this->db->select('id')->where('mr_header_id', $mr_id)->get('mr_details')->result());

        $remaining_outstanding = [];
        $remaining_pr_qty = 0.0;
        $pr_docs = [];
        if ($mr_detail_ids) {
            foreach ($this->Material_outstanding_model->get_pr_need_map($mr_detail_ids) as $row) {
                if ((float) $row->qty_outstanding > 0.0001) {
                    $remaining_outstanding[] = $row;
                    $remaining_pr_qty += (float) $row->qty_pr;
                }
            }
            $pr_docs = $this->Material_outstanding_model->get_pr_docs_map($mr_detail_ids);
        }

        $data = [
            'title'       => lang_line('title_mi_detail'),
            'header'      => $header,
            'details'     => $this->Material_issuance_model->get_mr_details_for_issuance($mr_id), // Ini sudah ada total_available_stock
            'attachments' => $this->Material_issuance_model->get_attachments($mr_id),
            'remaining_outstanding' => $remaining_outstanding,
            'remaining_pr_qty'      => $remaining_pr_qty,
            'pr_docs'               => $pr_docs,
            // PR material = tipe produksi -> menu `purchase_request`; endpoint
            // pembuatnya juga menuntut izin lihat IRF/MRF Outstanding.
            'can_create_pr'         => check_access('purchase_request', 'add') && check_access('material_outstanding', 'view'),
            // Riwayat pengeluaran (1 MR bisa dikeluarkan bertahap) & departemen.
            'issuances'             => $this->Mr_model->get_issuances_by_mr($mr_id),
            'dept_name'             => (department_names()[$header->department_code ?? ''] ?? ($header->department_code ?: '-')),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_issuance/detail', $data);
        $this->load->view('templates/footer');
    }
    
    // --- FUNGSI ajax_get_stock() DIHAPUS KARENA TIDAK DIPERLUKAN LAGI ---
    
    // Proses Konfirmasi Pengeluaran (Submit Form)
    public function process_issuance($mr_id) {
        if (!check_access('material_issuance', 'edit')) { show_error('Tidak ada izin.', 403); return; }
        if (empty($mr_id) || !is_numeric($mr_id)) { show_404(); } // Validasi ID

        $header = $this->Mr_model->get_header_by_id($mr_id);
        // Approved (sudah lolos 3 stage), Partial Issued, Issued, atau Received bisa diproses (untuk multiple issuances)
        if (!$header || !in_array($header->status, ['Approved', 'Partial Issued', 'Partial Received', 'Issued', 'Received'])) {
            $this->session->set_flashdata('error', 'MR ini tidak bisa diproses (Status: ' . ($header->status ?? 'N/A') . ').');
            redirect('material_issuance/detail/' . $mr_id);
            return;
        }

        $items = $this->input->post('items');
        if (empty($items)) {
            $this->session->set_flashdata('error', 'Tidak ada item yang diisi.');
            redirect('material_issuance/detail/' . $mr_id);
            return;
        }

        // Item Release: attachment "Bukti Pengeluaran" wajib diupload SETELAH
        // pengeluaran terjadi (bukan syarat sebelum proses) — pola sama
        // seperti Production_receive: aksi utama jalan dulu, baru ada
        // kewajiban tindak lanjut terpisah. Lihat gate di detail() view
        // (badge/reminder) — ERP Revision Notes 2026-09-02, Fase 2 poin 8.
        $user_id = $this->session->userdata('user_id') ?? null;
        if (!$user_id) {
             $this->session->set_flashdata('error', 'Sesi Anda berakhir. Silakan login ulang.');
             redirect('material_issuance/detail/' . $mr_id);
             return;
        }

        // Panggil model baru yang sudah FIFO
        $result = $this->Material_issuance_model->process_issuance($mr_id, $items, $user_id);
        
        if ($result['success']) {
            $this->session->set_flashdata('success', 'Barang berhasil dikeluarkan dan stok (FIFO) dipotong.');
        } else {
            // Tampilkan pesan error dari model
            $this->session->set_flashdata('error', 'Gagal: ' . ($result['message'] ?? 'Terjadi kesalahan tidak diketahui.'));
        }
        redirect('material_issuance/detail/' . $mr_id);
    }
    
    // Proses Batal Pengeluaran (Un-Issue)
    public function reverse_issuance($mr_id) {
        if (!check_access('material_issuance', 'edit')) { show_error('Tidak ada izin.', 403); return; }
        if (empty($mr_id) || !is_numeric($mr_id)) { show_404(); } // Validasi ID

        $header = $this->Mr_model->get_header_by_id($mr_id);
        // Hanya Issued dan Partial Issued yang bisa dibatalkan
        if (!$header || !in_array($header->status, ['Partial Issued', 'Issued'])) {
            $this->session->set_flashdata('error', 'MR tidak bisa dibatalkan (Status: ' . ($header->status ?? 'N/A') . ').');
            redirect('material_issuance/detail/' . $mr_id);
            return;
        }
        
        // Cek jika sudah 'Received' oleh produksi (logika Anda sudah benar)
        if ($header->status == 'Received') {
           $this->session->set_flashdata('error', 'Tidak bisa dibatalkan, barang sudah diterima Produksi!');
           redirect('material_issuance/detail/' . $mr_id);
           return;
        }
        
        $user_id = $this->session->userdata('user_id') ?? null;
        if ($this->Material_issuance_model->reverse_issuance($mr_id, $user_id)) {
            $this->session->set_flashdata('success', 'Pengeluaran berhasil dibatalkan, stok (FIFO) dikembalikan.');
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan pengeluaran (Database Error).');
        }
        redirect('material_issuance/detail/' . $mr_id);
    }
    
    // Export PDF Material Issuance
    public function export_pdf($id) {
        if (!check_access('material_issuance', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        $header = $this->Mr_model->get_header_by_id($id);
        if (!$header || !in_array($header->status, ['Issued', 'Received'])) {
            $this->session->set_flashdata('error', 'Material Release tidak ditemukan atau belum dikeluarkan.');
            redirect('material_issuance');
            return;
        }

        $details = $this->Material_issuance_model->get_mr_details_for_issuance($id);

        $data = [
            'header' => $header,
            'details' => $details
        ];

        // Lewat helper bersama supaya setelan Dompdf-nya sama dgn PDF dokumen
        // lain — terutama chroot+tempDir, tanpa itu logo kop (path absolut di
        // FCPATH) ditolak Dompdf dan kop terbit tanpa gambar.
        $filename = 'Material_Issuance_' . str_replace('/', '-', $header->issuance_number) . '.pdf';
        render_letterhead_pdf('material_issuance/pdf_template', $data, $filename);
    }

    // -------------------------------------------------------
    // Attachments — "Bukti Pengeluaran" (Item Release, Fase 2 poin 8)
    // Pola sama persis dengan Erf::upload_attachment()/delete_attachment()
    // dan Consumable_out::upload_attachment()/delete_attachment().
    // -------------------------------------------------------
    public function upload_attachment($mr_id) {
        if (!check_access('material_issuance', 'edit')) { show_error('Tidak ada izin.', 403); return; }
        $mr_id = (int) $mr_id;
        if ($mr_id <= 0) { show_404(); }

        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header) { show_404(); }

        $upload_path = FCPATH . 'uploads/material_issuance_attachments/';
        if (!is_dir($upload_path)) { @mkdir($upload_path, 0755, true); }

        $config = [
            'upload_path'   => $upload_path,
            'allowed_types' => 'pdf|jpg|jpeg|png|doc|docx|xls|xlsx',
            'max_size'      => 5120,
            'encrypt_name'  => TRUE,
        ];
        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('attachment')) {
            $this->session->set_flashdata('error', 'Upload gagal: ' . $this->upload->display_errors('', ''));
            redirect('material_issuance/detail/' . $mr_id);
            return;
        }

        $f = $this->upload->data();
        $this->Material_issuance_model->add_attachment([
            'mr_header_id'  => $mr_id,
            'file_name'     => $f['file_name'],
            'original_name' => $f['orig_name'],
            'file_path'     => 'uploads/material_issuance_attachments/' . $f['file_name'],
            'file_type'     => $f['file_type'],
            'file_size'     => $f['file_size'] * 1024, // KB to bytes (CI raw is in kb)
            'uploaded_by'   => $this->session->userdata('user_id'),
        ]);
        $this->session->set_flashdata('success', 'Attachment berhasil di-upload.');
        redirect('material_issuance/detail/' . $mr_id);
    }

    public function delete_attachment($attachment_id) {
        if (!check_access('material_issuance', 'edit')) { show_error('Tidak ada izin.', 403); return; }
        $att = $this->Material_issuance_model->get_attachment((int) $attachment_id);
        if (!$att) { show_404(); }

        $full = FCPATH . $att->file_path;
        if (is_file($full)) { @unlink($full); }

        $this->Material_issuance_model->delete_attachment((int) $attachment_id);
        $this->session->set_flashdata('success', 'Attachment dihapus.');
        redirect('material_issuance/detail/' . $att->mr_header_id);
    }

    public function attachment($attachment_id) {
        $att = $this->Material_issuance_model->get_attachment((int) $attachment_id);
        if (!$att) { show_404(); }

        $full = FCPATH . $att->file_path;
        if (!is_file($full)) { show_404(); }

        header('Content-Type: ' . $att->file_type);
        header('Content-Disposition: inline; filename="' . $att->original_name . '"');
        header('Content-Length: ' . filesize($full));
        readfile($full);
        exit;
    }
}