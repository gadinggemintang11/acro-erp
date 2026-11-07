<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Document Sharing — menu baru (permintaan user 2026-09-04): upload
 * dokumen dgn folder per departemen + akses per-file (private/roles).
 * Detail desain lengkap: memory document_sharing_feature.md.
 *
 * PENTING (beda dari pola attachment lain di app ini, mis. Erf::attachment()
 * yang TIDAK cek permission per-dokumen sama sekali): view()/download() DI
 * SINI wajib lewat Document_sharing_model::can_view_document() setiap kali
 * — itu justru INTI fitur ini (private harus BENERAN private).
 */
class Document_sharing extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Document_sharing_model');
    }

    private function _ctx() {
        return [
            'user_id'  => $this->session->userdata('user_id'),
            'role_id'  => current_role_ids(), // semua role user (Role Level 2026-09-15)
            'is_admin' => is_admin(),
        ];
    }

    public function index() {
        if (!check_access('document_sharing', 'view')) {
            show_error('Anda tidak memiliki akses ke modul ini.', 403);
        }

        $departments = $this->Document_sharing_model->get_departments();
        $counts      = $this->Document_sharing_model->count_documents_by_department();

        $data = [
            'title'       => lang_line('title_doc_sharing_index', 'Document Sharing'),
            'departments' => $departments,
            'counts'      => $counts,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('document_sharing/index', $data);
        $this->load->view('templates/footer');
    }

    public function folder($department_code) {
        if (!check_access('document_sharing', 'view')) {
            show_error('Anda tidak memiliki akses ke modul ini.', 403);
        }

        // Kode departemen bisa mengandung spasi (mis. "FIN ACT", "Q A") —
        // CI3 TIDAK auto-decode segmen URI (dibuktikan: $_SERVER['REQUEST_URI']
        // tetap mentah/ter-%-encode apa adanya, lihat memory
        // document_sharing_feature.md bagian bugfix). Link-nya SELALU dibikin
        // via rawurlencode() (view index.php/folder.php), jadi di sini WAJIB
        // rawurldecode() balik supaya cocok dgn nilai asli di common_code.
        $department_code = rawurldecode($department_code);

        $dept = $this->Document_sharing_model->get_department_by_code($department_code);
        if (!$dept) show_404();

        $ctx    = $this->_ctx();
        $search = $this->input->get('search');

        $data = [
            'title'       => lang_line('title_doc_sharing_folder', 'Document Sharing'),
            'dept'        => $dept,
            'documents'   => $this->Document_sharing_model->get_documents_for_folder($department_code, $ctx['user_id'], $ctx['role_id'], $ctx['is_admin'], $search),
            'roles'       => $this->Document_sharing_model->get_all_roles(),
            'search'      => $search,
            'can_upload'  => check_access('document_sharing', 'add'),
            'ctx'         => $ctx,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('document_sharing/folder', $data);
        $this->load->view('templates/footer');
    }

    public function upload($department_code) {
        if (!check_access('document_sharing', 'add')) {
            show_error('Anda tidak memiliki akses untuk upload dokumen.', 403);
        }
        $department_code = rawurldecode($department_code); // lihat catatan di folder()

        $dept = $this->Document_sharing_model->get_department_by_code($department_code);
        if (!$dept) show_404();

        $upload_path = FCPATH . 'uploads/documents/';
        if (!is_dir($upload_path)) { @mkdir($upload_path, 0755, true); }

        $config = [
            'upload_path'   => $upload_path,
            'allowed_types' => 'pdf|jpg|jpeg|png|doc|docx|xls|xlsx|ppt|pptx|zip|rar|csv|txt',
            'max_size'      => 10240, // 10 MB
            'encrypt_name'  => TRUE,
        ];
        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('document_file')) {
            $this->session->set_flashdata('error', 'Upload gagal: ' . $this->upload->display_errors('', ''));
            redirect('document_sharing/folder/' . rawurlencode($department_code));
            return;
        }

        $f = $this->upload->data();

        $visibility = $this->input->post('visibility') === 'roles' ? 'roles' : 'private';
        $role_ids   = $visibility === 'roles' ? (array) $this->input->post('role_ids') : [];

        if ($visibility === 'roles' && empty($role_ids)) {
            @unlink($f['full_path']);
            $this->session->set_flashdata('error', 'Pilih minimal satu role yang boleh melihat dokumen ini, atau pilih "Privat".');
            redirect('document_sharing/folder/' . rawurlencode($department_code));
            return;
        }

        $title = trim((string) $this->input->post('title'));
        if ($title === '') $title = $f['orig_name'];

        $doc_id = $this->Document_sharing_model->insert_document([
            'department_code' => $department_code,
            'title'           => $title,
            'file_path'       => 'uploads/documents/' . $f['file_name'],
            'original_name'   => $f['orig_name'],
            'mime_type'       => $f['file_type'],
            'file_size'       => (int) round($f['file_size'] * 1024), // CI raw dalam KB
            'visibility'      => $visibility,
            'uploaded_by'     => $this->session->userdata('user_id'),
            'uploaded_at'     => date('Y-m-d H:i:s'),
        ], $role_ids);

        if ($doc_id) {
            $this->session->set_flashdata('success', 'Dokumen berhasil diupload.');
        } else {
            @unlink($f['full_path']);
            $this->session->set_flashdata('error', 'Gagal menyimpan data dokumen.');
        }
        redirect('document_sharing/folder/' . rawurlencode($department_code));
    }

    /** Tampilkan file inline (preview PDF/gambar dsb) — WAJIB lolos cek akses. */
    public function view($id) {
        $this->_stream($id, 'inline');
    }

    /** Paksa download — WAJIB lolos cek akses. */
    public function download($id) {
        $this->_stream($id, 'attachment');
    }

    private function _stream($id, $disposition) {
        if (!check_access('document_sharing', 'view')) show_error('Anda tidak memiliki akses.', 403);

        $doc = $this->Document_sharing_model->get_document_by_id((int) $id);
        if (!$doc) show_404();

        $ctx = $this->_ctx();
        if (!$this->Document_sharing_model->can_view_document($doc, $ctx['user_id'], $ctx['role_id'], $ctx['is_admin'])) {
            show_error('Anda tidak memiliki akses untuk melihat dokumen ini.', 403);
        }

        $full = FCPATH . $doc->file_path;
        if (!is_file($full)) show_404();

        header('Content-Type: ' . ($doc->mime_type ?: 'application/octet-stream'));
        header('Content-Disposition: ' . $disposition . '; filename="' . $doc->original_name . '"');
        header('Content-Length: ' . filesize($full));
        readfile($full);
        exit;
    }

    public function delete($id) {
        $doc = $this->Document_sharing_model->get_document_by_id((int) $id);
        if (!$doc) show_404();

        $ctx = $this->_ctx();
        // Boleh hapus kalau: pemilik dokumen ATAU Admin (can_manage_document),
        // ATAU role-nya sudah diberi hak can_delete lewat Role Access (default
        // migration cuma Admin, tapi bisa diperluas admin lain via UI Role
        // Access tanpa perlu jadi pemilik dokumennya).
        $allowed = $this->Document_sharing_model->can_manage_document($doc, $ctx['user_id'], $ctx['is_admin'])
            || check_access('document_sharing', 'delete');

        if (!$allowed) show_error('Anda tidak memiliki akses untuk menghapus dokumen ini.', 403);

        $full = FCPATH . $doc->file_path;
        if (is_file($full)) @unlink($full);
        $this->Document_sharing_model->delete_document($doc->id);

        $this->session->set_flashdata('success', 'Dokumen berhasil dihapus.');
        redirect('document_sharing/folder/' . rawurlencode($doc->department_code));
    }
}
