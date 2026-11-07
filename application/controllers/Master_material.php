<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class Master_material extends MY_Controller {

    protected $module_name = 'Master Material';

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Material_model');
        // ✅ Load Commoncode_model untuk data dinamis
        $this->load->model('Commoncode_model');
        // ✅ Load Export Helper
        $this->load->helper('export');
    }

    public function index() {
        // ✅ KEAMANAN: Check akses view
        if (!check_access('master_material', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $keyword = $this->input->get('q');
        $per_page = 20;
        $page = (int) $this->input->get('page');
        $offset = $page > 0 ? ($page - 1) * $per_page : 0;

        $total_rows = $this->Material_model->count_filtered($keyword);
        $materials = $this->Material_model->get_paginated($per_page, $offset, $keyword);

        // Pagination config
        $this->load->library('pagination');
        $config['base_url'] = site_url('master_material');
        $config['total_rows'] = $total_rows;
        $config['per_page'] = $per_page;
        $config['page_query_string'] = TRUE;
        $config['query_string_segment'] = 'page';
        $config['reuse_query_string'] = TRUE;
        $config['use_page_numbers'] = TRUE;

        // Styling bootstrap
        $config['full_tag_open'] = '<ul class="pagination justify-content-end">';
        $config['full_tag_close'] = '</ul>';
        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="page-item active"><a class="page-link" href="#">';
        $config['cur_tag_close'] = '</a></li>';
        $config['prev_tag_open'] = '<li class="page-item">';
        $config['prev_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li class="page-item">';
        $config['next_tag_close'] = '</li>';
        $config['attributes'] = array('class' => 'page-link');

        $this->pagination->initialize($config);

        $data['title'] = lang_line('material_title');
        $data['materials'] = $materials;
        $data['pagination'] = $this->pagination->create_links();
        $data['keyword'] = $keyword;
        $data['offset'] = $offset;
        $data['per_page'] = $per_page;

        // ✅ DINAMIS: Menggunakan Commoncode_model yang lebih umum
        $data['satuan_list'] = $this->Commoncode_model->get_by_group('SATUAN');
        $data['kategori_list'] = $this->Commoncode_model->get_by_group('MATERIAL_CATEGORY');
        $data['kategorirm_list'] = $this->Commoncode_model->get_by_group('RM_CATEGORY');
        $data['asal_list'] = $this->Commoncode_model->get_by_group('ITEM_ORIGIN'); // Asumsi 'Lokal'/'Impor'
        // Kode master barang v2 (ERP Revision Notes 2026-09-02): sub kategori
        // produksi (RAW/SUP/CON/WIP) dipakai form Tambah utk generate kode_material baru.
        $data['sub_class_list'] = $this->Commoncode_model->get_by_group('MATERIAL_SUB_CLASS');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        // ✅ KEAMANAN: Check akses add
        if (!check_access('master_material', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        $kode_material = trim((string) $this->input->post('kode_material'));
        $nama_material = $this->input->post('nama_material');
        $kategori = $this->input->post('kategori');
        $kategorirm = $this->input->post('kategorirm');
        $sub_class = $this->input->post('sub_class');
        $asal = $this->input->post('asal');
        $satuan = $this->input->post('satuan');
        $minimum_stock = $this->input->post('minimum_stock');

        if (empty($nama_material)) {
            $this->session->set_flashdata('error', 'Nama material wajib diisi.');
            return redirect('master_material');
        }
        $valid_sub_class = array_map(function($s) { return $s->code; }, $this->Commoncode_model->get_by_group('MATERIAL_SUB_CLASS'));
        if (empty($sub_class) || !in_array($sub_class, $valid_sub_class, true)) {
            $this->session->set_flashdata('error', 'Sub Kategori Produksi wajib dipilih (Raw Material/Support Material/Consumable/Semifinished).');
            return redirect('master_material');
        }

        // Kode master barang v2 (MAIN-SUB-ORIGIN-NUMBER): auto-generate kalau
        // tidak diisi manual. Lihat Material_model::generate_kode().
        if (empty($kode_material)) {
            $kode_material = $this->Material_model->generate_kode($sub_class, $asal);
        }

        $data = [
            'kode_material' => $kode_material,
            'nama_material' => $nama_material,
            'kategori'      => $kategori,
            'kategorirm'    => $kategorirm,
            'sub_class'     => $sub_class,
            'asal'          => $asal,
            'satuan'        => $satuan,
            'minimum_stock' => !empty($minimum_stock) ? (float)$minimum_stock : 0,
            'status'        => 'active'
        ];

        // Add spesifikasi only if column exists
        if ($this->db->field_exists('spesifikasi', 'materials')) {
            $data['spesifikasi'] = $this->input->post('spesifikasi');
        }

        if ($this->Material_model->insert($data)) {
            // --- LOGGING (CREATE) ---
            $deskripsi = "Membuat material baru: {$data['nama_material']} (Kode: {$data['kode_material']})";
            $this->_log('CREATE', $deskripsi);
            $this->session->set_flashdata('success', 'Material berhasil ditambahkan!');
            // --- END LOG ---
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan material!');
        }
        redirect('master_material');
    }

    public function update($id) {
        // ✅ KEAMANAN: Check akses edit
        if (!check_access('master_material', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }

        // Ambil data lama untuk log
        $old_data = $this->Material_model->get_by_id($id);
        if (!$old_data) show_404();

        $data = [
            'kode_material' => $this->input->post('kode_material'),
            'nama_material' => $this->input->post('nama_material'),
            'kategori'      => $this->input->post('kategori'),
            'kategorirm'    => $this->input->post('kategorirm'),
            'asal'          => $this->input->post('asal'),
            'satuan'        => $this->input->post('satuan'),
            'minimum_stock' => !empty($this->input->post('minimum_stock')) ? (float)$this->input->post('minimum_stock') : 0,
            'status'        => $this->input->post('status')
        ];

        // Add spesifikasi only if column exists
        if ($this->db->field_exists('spesifikasi', 'materials')) {
            $data['spesifikasi'] = $this->input->post('spesifikasi');
        }

        if ($this->Material_model->update($id, $data)) {
            // --- LOGGING (UPDATE) ---
            $deskripsi = "Memperbarui material: {$old_data->nama_material} (ID: $id) menjadi {$data['nama_material']}";
            $this->_log('UPDATE', $deskripsi);
            $this->session->set_flashdata('success', 'Data material berhasil diperbarui!');
            // --- END LOG ---
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui data!');
        }
        redirect('master_material');
    }

    public function delete($id) {
        // ✅ KEAMANAN: Check akses delete
        if (!check_access('master_material', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }

        // Ambil data untuk log sebelum dinonaktifkan
        $material_data = $this->Material_model->get_by_id($id);

        if ($this->Material_model->delete($id)) {
            // --- LOGGING (DELETE/SOFT DELETE) ---
            $deskripsi = "Menonaktifkan material: " . ($material_data->nama_material ?? "ID: $id");
            $this->_log('UPDATE', $deskripsi);
            // ✅ DIUBAH: Pesan disesuaikan dengan soft delete
            $this->session->set_flashdata('success', 'Data material berhasil dinonaktifkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan data!');
        }
        redirect('master_material');
    }

    // ✅ EXPORT EXCEL
    public function export_excel() {
        if (!check_access('master_material', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        $materials = $this->Material_model->get_all();
        
        $headers = ['Kode', 'Nama Material', 'Kategori', 'Material Kategori', 'Asal', 'Satuan', 'Spesifikasi', 'Status'];
        $data = [];

        foreach ($materials as $m) {
            $data[] = [
                $m->kode_material,
                $m->nama_material,
                $m->kategori,
                $m->kategorirm,
                $m->asal,
                $m->satuan,
                $m->spesifikasi ?? '',
                ucfirst($m->status)
            ];
        }

        $filename = 'Master_Material_' . date('Ymd_His');
        export_to_excel($filename, 'Materials', $headers, $data);
    }

    // ✅ EXPORT PDF
    public function export_pdf() {
        if (!check_access('master_material', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        $materials = $this->Material_model->get_all();
        
        $headers = ['Kode', 'Nama Material', 'Kategori', 'Material Kategori', 'Asal', 'Satuan', 'Spesifikasi', 'Status'];
        $data = [];

        foreach ($materials as $m) {
            $data[] = [
                $m->kode_material,
                $m->nama_material,
                $m->kategori,
                $m->kategorirm,
                $m->asal,
                $m->satuan,
                $m->spesifikasi ?? '',
                ucfirst($m->status)
            ];
        }

        $filename = 'Master_Material_' . date('Ymd_His');
        $title = 'Master Material - ' . date('d-m-Y H:i:s');
        export_to_pdf($filename, $title, $headers, $data);
    }

    /**
     * Download Excel template untuk import Master Material
     * Sheet 1 = data, Sheet 2 = referensi kode (kategori, kategorirm, asal, satuan)
     */
    public function download_template() {
        if (!check_access('master_material', 'add')) {
            show_error('Anda tidak memiliki izin untuk mengunduh template.', 403);
        }

        $kategori_list   = $this->Commoncode_model->get_by_group('MATERIAL_CATEGORY');
        $kategorirm_list = $this->Commoncode_model->get_by_group('RM_CATEGORY');
        $asal_list       = $this->Commoncode_model->get_by_group('ITEM_ORIGIN');
        $satuan_list     = $this->Commoncode_model->get_by_group('SATUAN');
        $sub_class_list  = $this->Commoncode_model->get_by_group('MATERIAL_SUB_CLASS');

        $spreadsheet = new Spreadsheet();

        // ===== Sheet 1: Template data =====
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Materials');

        $headers = [
            'A' => 'kode_material (kosongkan untuk auto)',
            'B' => 'nama_material *',
            'C' => 'kategori (code) *',
            'D' => 'kategorirm (code) *',
            'E' => 'sub_class (code) *',
            'F' => 'asal (code) *',
            'G' => 'satuan (code) *',
            'H' => 'spesifikasi',
            'I' => 'minimum_stock',
        ];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . '1', $label);
        }

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
        $sheet->getStyle('A1:I1')->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB('4472C4');

        // Sample row (ambil code pertama dari masing-masing list jika ada)
        $sampleKategori   = !empty($kategori_list)   ? $kategori_list[0]->code   : '';
        $sampleKategoriRm = !empty($kategorirm_list) ? $kategorirm_list[0]->code : '';
        $sampleSubClass   = !empty($sub_class_list)  ? $sub_class_list[0]->code  : '';
        $sampleAsal       = !empty($asal_list)       ? $asal_list[0]->code       : '';
        $sampleSatuan     = !empty($satuan_list)     ? $satuan_list[0]->code     : '';

        $sheet->setCellValue('A2', '');
        $sheet->setCellValue('B2', 'Contoh Material');
        $sheet->setCellValue('C2', $sampleKategori);
        $sheet->setCellValue('D2', $sampleKategoriRm);
        $sheet->setCellValue('E2', $sampleSubClass);
        $sheet->setCellValue('F2', $sampleAsal);
        $sheet->setCellValue('G2', $sampleSatuan);
        $sheet->setCellValue('H2', 'Spesifikasi contoh');
        $sheet->setCellValue('I2', 0);

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ===== Sheet 2: Referensi =====
        $ref = $spreadsheet->createSheet();
        $ref->setTitle('Referensi Kode');

        $ref->setCellValue('A1', 'KATEGORI (MATERIAL_CATEGORY)');
        $ref->setCellValue('C1', 'KATEGORI RM (RM_CATEGORY)');
        $ref->setCellValue('E1', 'SUB_CLASS (MATERIAL_SUB_CLASS)');
        $ref->setCellValue('G1', 'ASAL (ITEM_ORIGIN)');
        $ref->setCellValue('I1', 'SATUAN');
        $ref->getStyle('A1:J1')->getFont()->setBold(true);

        $ref->setCellValue('A2', 'code'); $ref->setCellValue('B2', 'description');
        $ref->setCellValue('C2', 'code'); $ref->setCellValue('D2', 'description');
        $ref->setCellValue('E2', 'code'); $ref->setCellValue('F2', 'description');
        $ref->setCellValue('G2', 'code'); $ref->setCellValue('H2', 'description');
        $ref->setCellValue('I2', 'code'); $ref->setCellValue('J2', 'description');
        $ref->getStyle('A2:J2')->getFont()->setItalic(true);

        $writeRef = function($list, $colCode, $colDesc) use ($ref) {
            $r = 3;
            foreach ($list as $item) {
                $ref->setCellValue($colCode . $r, $item->code);
                $ref->setCellValue($colDesc . $r, $item->description);
                $r++;
            }
        };
        $writeRef($kategori_list,   'A', 'B');
        $writeRef($kategorirm_list, 'C', 'D');
        $writeRef($sub_class_list,  'E', 'F');
        $writeRef($asal_list,       'G', 'H');
        $writeRef($satuan_list,     'I', 'J');

        foreach (range('A', 'J') as $col) {
            $ref->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'template_master_material.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Upload Excel untuk bulk insert Master Material
     * Kolom: A kode_material (opsional), B nama_material, C kategori, D kategorirm,
     *        E sub_class (RAW/SUP/CON/WIP, wajib utk kode baru), F asal, G satuan,
     *        H spesifikasi, I minimum_stock
     */
    public function upload_excel() {
        if (!check_access('master_material', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        if (empty($_FILES['excel_file']['name'])) {
            $this->session->set_flashdata('error', 'Harap pilih file Excel (.xlsx/.xls).');
            redirect('master_material');
            return;
        }

        $ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'])) {
            $this->session->set_flashdata('error', 'Format file tidak didukung. Gunakan .xlsx atau .xls');
            redirect('master_material');
            return;
        }

        try {
            $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (\Exception $e) {
            $this->session->set_flashdata('error', 'Gagal membaca file: ' . $e->getMessage());
            redirect('master_material');
            return;
        }

        $has_spesifikasi = $this->db->field_exists('spesifikasi', 'materials');
        $valid_sub_class = array_map(function($s) { return strtoupper($s->code); }, $this->Commoncode_model->get_by_group('MATERIAL_SUB_CLASS'));

        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $idx => $row) {
            if ($idx == 1) continue; // skip header

            $kode_material = trim((string)($row['A'] ?? ''));
            $nama_material = trim((string)($row['B'] ?? ''));
            $kategori      = trim((string)($row['C'] ?? ''));
            $kategorirm    = trim((string)($row['D'] ?? ''));
            $sub_class     = strtoupper(trim((string)($row['E'] ?? '')));
            $asal          = trim((string)($row['F'] ?? ''));
            $satuan        = trim((string)($row['G'] ?? ''));
            $spesifikasi   = trim((string)($row['H'] ?? ''));
            $minimum_stock = $row['I'] ?? 0;

            // Skip baris kosong total
            if ($nama_material === '' && $kategori === '' && $kategorirm === '' && $kode_material === '') {
                continue;
            }

            if ($nama_material === '') {
                $errors[] = "Baris $idx: nama_material kosong";
                $failed++;
                continue;
            }
            if ($kategori === '' || $kategorirm === '' || $asal === '' || $satuan === '') {
                $errors[] = "Baris $idx: kategori/kategorirm/asal/satuan wajib diisi";
                $failed++;
                continue;
            }
            if ($sub_class === '' || !in_array($sub_class, $valid_sub_class, true)) {
                $errors[] = "Baris $idx: sub_class wajib diisi salah satu dari " . implode('/', $valid_sub_class);
                $failed++;
                continue;
            }

            // Cek duplikat kode jika diisi manual
            if ($kode_material !== '') {
                $exists = $this->db->get_where('materials', ['kode_material' => $kode_material])->row();
                if ($exists) {
                    $errors[] = "Baris $idx: kode_material '$kode_material' sudah ada";
                    $failed++;
                    continue;
                }
            } else {
                $kode_material = $this->Material_model->generate_kode($sub_class, $asal);
            }

            $data = [
                'kode_material' => $kode_material,
                'nama_material' => $nama_material,
                'kategori'      => $kategori,
                'kategorirm'    => $kategorirm,
                'sub_class'     => $sub_class,
                'asal'          => $asal,
                'satuan'        => $satuan,
                'minimum_stock' => is_numeric($minimum_stock) ? (float)$minimum_stock : 0,
                'status'        => 'active',
            ];
            if ($has_spesifikasi) {
                $data['spesifikasi'] = $spesifikasi;
            }

            if ($this->Material_model->insert($data)) {
                $success++;
            } else {
                $errors[] = "Baris $idx: gagal menyimpan ke database";
                $failed++;
            }
        }

        if ($success > 0) {
            $this->_log('CREATE', "Import Excel master material: $success baris berhasil, $failed gagal");
        }

        $msg = "$success baris berhasil di-import.";
        if ($failed > 0) {
            $msg .= " $failed baris gagal.<br>" . implode('<br>', $errors);
            $flash = $success > 0 ? 'warning' : 'error';
            $this->session->set_flashdata($flash, $msg);
        } else {
            $this->session->set_flashdata('success', $msg);
        }

        redirect('master_material');
    }

    /**
     * AJAX endpoint generate kode_material (format baru MTR-SUB-ORIGIN-NUMBER).
     * Lihat Material_model::generate_kode() & pola yang sama di Master_inventory.
     */
    public function generate_kode() {
        if (!$this->input->is_ajax_request()) {
            show_error('Invalid Request', 400);
        }

        $sub_class = $this->input->post('sub_class');
        $asal      = $this->input->post('asal') ?: 'Lokal';
        if (empty($sub_class)) {
            echo json_encode(['success' => false, 'message' => 'Sub Kategori Produksi tidak ditemukan']);
            return;
        }

        $kode = $this->Material_model->generate_kode($sub_class, $asal);
        echo json_encode(['success' => true, 'kode' => $kode]);
    }
}