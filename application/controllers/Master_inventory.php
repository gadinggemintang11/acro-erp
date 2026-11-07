<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class Master_inventory extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Inventory_model');
        $this->load->model('Commoncode_model'); 
        $this->load->helper(['form', 'url', 'export']);
        $this->load->library('session');
        $this->load->library('form_validation');
    }

    public function index() {
        if (!check_access('master_inventory', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $search = $this->input->get('search') ?? '';
        // Filter kode kategori & status (user 2026-09-09). Keduanya ikut
        // dipakai di count_all_search() juga — kalau tidak, jumlah halaman
        // pagination-nya salah.
        $filters = [
            'kategori' => $this->input->get('kategori') ?? '',
            'status'   => $this->input->get('status') ?? '',
        ];
        $per_page = 20;
        $offset = (int)($this->input->get('page') ?? 0);

        $total_rows = $this->Inventory_model->count_all_search($search, $filters);
        $inventaris = $this->Inventory_model->get_with_pagination_and_search($per_page, $offset, $search, $filters);

        // Debug logging
        log_message('info', 'Master_inventory::index - Offset: ' . $offset . ', Total: ' . $total_rows . ', Results: ' . count($inventaris));

        // Setup pagination
        $this->load->library('pagination');
        // Semua filter aktif ikut dibawa di link pagination (kalau tidak,
        // pindah halaman = filter hilang). Diakhiri '?' atau '&' karena CI
        // menempelkan 'page=N' di belakangnya.
        $qs = array_filter([
            'search'   => $search,
            'kategori' => $filters['kategori'],
            'status'   => $filters['status'],
        ], function($v) { return $v !== '' && $v !== null; });
        $base_url = base_url('master_inventory') . '?' . (empty($qs) ? '' : http_build_query($qs) . '&');
        $config['base_url'] = $base_url;
        $config['total_rows'] = $total_rows;
        $config['per_page'] = $per_page;
        $config['page_query_string'] = true;
        $config['query_string_segment'] = 'page';
        $config['full_tag_open'] = '<nav><ul class="pagination">';
        $config['full_tag_close'] = '</ul></nav>';
        $config['first_tag_open'] = '<li class="page-item">';
        $config['first_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li class="page-item">';
        $config['last_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li class="page-item">';
        $config['next_tag_close'] = '</li>';
        $config['prev_tag_open'] = '<li class="page-item">';
        $config['prev_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="page-item active"><span class="page-link">';
        $config['cur_tag_close'] = '<span class="sr-only">(current)</span></span></li>';
        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';
        $config['attributes'] = ['class' => 'page-link'];

        $this->pagination->initialize($config);

        $data = [
            'title'         => lang_line('title_mst_inv'),
            'inventaris'    => $inventaris,
            'kategori_list' => $this->Inventory_model->get_kategori_list(),
            'sub_kategori_list' => $this->Inventory_model->get_sub_kategori_list(),
            'item_type_list' => $this->Inventory_model->get_item_type_list(),
            'kondisi_list'  => $this->Commoncode_model->get_by_group('INVENTORY_CONDITION'),
            'satuan_list'   => $this->Inventory_model->get_satuan_list(),
            'search'        => $search,
            'filter_kategori' => $filters['kategori'],
            'filter_status'   => $filters['status'],
            'kategori_used_list' => $this->Inventory_model->get_used_kategori_codes(),
            'pagination'    => $this->pagination->create_links(),
            // Kandidat item pengganti utk modal Edit (user 2026-09-09):
            // dipakai saat item lama dinonaktifkan karena sudah ada item
            // berkode baru. Hanya dikirim kalau kolomnya sudah ada
            // (migration_inventory_replacement.sql).
            'has_replacement' => db_has_column('inventories', 'replaced_by_id'),
            'replacement_candidates' => db_has_column('inventories', 'replaced_by_id')
                ? $this->Inventory_model->get_active_items_min() : [],
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('inventory/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        if (!check_access('master_inventory', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        // --- Validasi Input ---
        $this->form_validation->set_rules('nama_barang', 'Nama Barang', 'required|trim');
        $this->form_validation->set_rules('kategori', 'Kategori', 'required');
        $this->form_validation->set_rules('satuan', 'Satuan', 'required');
        $this->form_validation->set_rules('item_type', 'Jenis Item', 'required|in_list[Stock,Non-Stock]');
        $this->form_validation->set_rules('usage_category', 'Jenis Pemakaian', 'required|in_list[General,Produksi]');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('master_inventory');
            return;
        }
        // --- Akhir Validasi ---

        $kategori_code = $this->input->post('kategori');
        $item_type     = $this->input->post('item_type');
        $minimum_stock = $this->input->post('minimum_stock');
        $kode_barang = trim($this->input->post('kode_barang'));

        $usage_category = Inventory_model::clean_usage_category($this->input->post('usage_category'));

        // Gunakan kode manual jika ada, jika tidak auto-generate (format
        // MAIN-USG-SUB-NUMBER, lihat Inventory_model::generate_kode())
        if (empty($kode_barang)) {
            $kode_barang = $this->Inventory_model->generate_kode($item_type, $kategori_code, $usage_category);
        }

        $data = [
            'kode_barang' => $kode_barang,
            'nama_barang' => $this->input->post('nama_barang'),
            'kategori'    => $kategori_code,
            'item_type'   => $item_type,
            'satuan'      => $this->input->post('satuan'),
            'merk'        => $this->input->post('merk'),
            // 'lokasi' tidak lagi diisi dari form (user 2026-09-09: field
            // Lokasi diganti Jenis Item). Kolomnya nullable & TIDAK di-drop,
            // jadi data lama tetap utuh.
            'kondisi'     => $this->input->post('kondisi'),
            'minimum_stock' => !empty($minimum_stock) ? (float)$minimum_stock : 0,
            'status'      => 'Active',
            // Jenis pemakaian (user 2026-10-06): menentukan barang ini muncul
            // di pencarian item IRF kategori General atau Produksi.
            'usage_category' => $usage_category,
        ];

        if ($this->Inventory_model->insert($data)) {
            $this->session->set_flashdata('success', 'Data inventaris berhasil ditambahkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan data!');
        }
        redirect('master_inventory');
    }

    public function update($id) {
        if (!check_access('master_inventory', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();

        // --- Validasi Input ---
        $this->form_validation->set_rules('nama_barang', 'Nama Barang', 'required|trim');
        $this->form_validation->set_rules('kategori', 'Kategori', 'required');
        $this->form_validation->set_rules('satuan', 'Satuan', 'required');
        $this->form_validation->set_rules('status', 'Status', 'required');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('master_inventory'); 
            return;
        }
        // --- Akhir Validasi ---

        $minimum_stock = $this->input->post('minimum_stock');
        $status = $this->input->post('status');

        $data = [
            'nama_barang' => $this->input->post('nama_barang'),
            'kategori'    => $this->input->post('kategori'),
            'satuan'      => $this->input->post('satuan'),
            'merk'        => $this->input->post('merk'),
            // 'lokasi' sengaja TIDAK ikut di-update: field-nya sudah diganti
            // Jenis Item di form (user 2026-09-09). Kalau tetap diambil dari
            // POST, nilai lama malah tertimpa kosong.
            'kondisi'     => $this->input->post('kondisi'),
            'minimum_stock' => !empty($minimum_stock) ? (float)$minimum_stock : 0,
            'status'      => $status,
        ];

        // Jenis Item (Stock/Non-Stock) sekarang bisa diedit. Kode barang
        // TIDAK di-generate ulang — kode dibuat sekali saat item dibuat, dan
        // mengubahnya belakangan berisiko memutus referensi transaksi lama.
        $item_type = $this->input->post('item_type');
        if (in_array($item_type, ['Stock', 'Non-Stock'], true)) {
            $data['item_type'] = $item_type;
        }

        // Jenis Pemakaian hanya ditimpa kalau form memang mengirimnya -- form
        // yang belum punya field ini (DB belum dimigrasi) tidak boleh diam2
        // mereset nilai yang sudah ada jadi General.
        if ($this->input->post('usage_category') !== null) {
            $data['usage_category'] = Inventory_model::clean_usage_category($this->input->post('usage_category'));
        }

        // Item pengganti (user 2026-09-09): kasusnya barang dibuat ulang dgn
        // KODE baru tapi nama sama, lalu kode lama dinonaktifkan sambil
        // menunjuk penggantinya. Isian ini OPSIONAL — item yang memang
        // obsolete (tidak ada padanannya) tetap boleh dinonaktifkan kosong.
        if (db_has_column('inventories', 'replaced_by_id')) {
            $replaced_by = (int) $this->input->post('replaced_by_id');

            if ($status !== 'Nonaktif') {
                // Balik Active = tidak lagi "digantikan" siapa pun.
                $data['replaced_by_id'] = null;
                $data['replaced_at']    = null;
            } elseif ($replaced_by > 0) {
                if ($replaced_by === (int) $id) {
                    $this->session->set_flashdata('error', 'Item pengganti tidak boleh item itu sendiri.');
                    redirect('master_inventory');
                    return;
                }
                $target = $this->db->select('id, kode_barang, nama_barang, status')
                    ->where('id', $replaced_by)->get('inventories')->row();
                if (!$target) {
                    $this->session->set_flashdata('error', 'Item pengganti tidak ditemukan.');
                    redirect('master_inventory');
                    return;
                }
                if ($target->status !== 'Active') {
                    $this->session->set_flashdata('error',
                        'Item pengganti (' . $target->kode_barang . ') statusnya bukan Active — pilih item yang masih aktif.');
                    redirect('master_inventory');
                    return;
                }
                $data['replaced_by_id'] = $replaced_by;
                $data['replaced_at']    = date('Y-m-d H:i:s');
            } else {
                $data['replaced_by_id'] = null;
                $data['replaced_at']    = null;
            }
        }

        if ($this->Inventory_model->update($id, $data)) {
            $this->session->set_flashdata('success', 'Data inventaris berhasil diupdate!');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengupdate data!');
        }
        redirect('master_inventory');
    }

    public function delete($id) {
        if (!check_access('master_inventory', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();

        if ($this->Inventory_model->delete($id)) {
            $this->session->set_flashdata('success', 'Data inventaris berhasil dinonaktifkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan data!');
        }
        redirect('master_inventory');
    }

    // AJAX endpoint untuk generate kode barang
    public function generate_kode() {
        if (!$this->input->is_ajax_request()) {
            show_error('Invalid Request', 400);
        }

        $kategori  = $this->input->post('kategori');
        $item_type = $this->input->post('item_type') ?: 'Stock';
        $usage     = Inventory_model::clean_usage_category($this->input->post('usage_category'));
        if (empty($kategori)) {
            echo json_encode(['success' => false, 'message' => 'Kategori tidak ditemukan']);
            return;
        }

        $kode = $this->Inventory_model->generate_kode($item_type, $kategori, $usage);
        echo json_encode(['success' => true, 'kode' => $kode]);
    }

    public function export_excel() {
        if (!check_access('master_inventory', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        $data = $this->Inventory_model->get_all();
        $headers = ['Kode', 'Nama Barang', 'Kategori', 'Jenis Pemakaian', 'Satuan', 'Minimum Stock', 'Status'];
        
        $export_data = [];
        foreach ($data as $row) {
            $export_data[] = [
                $row->kode_barang,
                $row->nama_barang,
                $row->kategori,
                $row->usage_category ?? '-',
                $row->satuan,
                $row->minimum_stock,
                $row->status
            ];
        }

        $filename = 'Master_Inventory_' . date('Ymd_His');
        export_to_excel($filename, 'Inventory', $headers, $export_data);
    }

    public function export_pdf() {
        if (!check_access('master_inventory', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        $data = $this->Inventory_model->get_all();
        $headers = ['Kode', 'Nama Barang', 'Kategori', 'Jenis Pemakaian', 'Satuan', 'Minimum Stock', 'Status'];
        
        $export_data = [];
        foreach ($data as $row) {
            $export_data[] = [
                $row->kode_barang,
                $row->nama_barang,
                $row->kategori,
                $row->usage_category ?? '-',
                $row->satuan,
                $row->minimum_stock,
                $row->status
            ];
        }

        $filename = 'Master_Inventory_' . date('Ymd_His');
        $title = 'Master Inventory - ' . date('d-m-Y H:i:s');
        export_to_pdf($filename, $title, $headers, $export_data);
    }

    /**
     * Download Template Excel untuk import Master Inventory
     * Catatan: kolom kode_barang TIDAK disertakan — kode di-generate otomatis dari kategori.
     */
    public function download_template() {
        if (!check_access('master_inventory', 'add')) {
            show_error('Anda tidak memiliki izin untuk mengunduh template.', 403);
        }

        // Kode master barang v3 (MAIN-USG-SUB-NUMBER): sub kategori sekarang dari
        // grup baru INVENTORY_SUB_CATEGORY (bukan INVENTORY_CATEGORY lama).
        $kategori_list = $this->Inventory_model->get_sub_kategori_list();
        $satuan_list   = $this->Inventory_model->get_satuan_list();
        $kondisi_list  = $this->Commoncode_model->get_by_group('INVENTORY_CONDITION');

        $spreadsheet = new Spreadsheet();

        // ===== Sheet 1: Template Data =====
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventory');

        $headers = [
            'A' => 'nama_barang *',
            'B' => 'jenis_item (Stock/Non-Stock) *',
            'C' => 'kategori (code) *',
            'D' => 'satuan (code) *',
            'E' => 'merk',
            'F' => 'lokasi',
            'G' => 'kondisi (code)',
            'H' => 'minimum_stock',
            'I' => 'jenis_pemakaian (General/Produksi)',
        ];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . '1', $label);
        }

        $sheet->getStyle('A1:I1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A1:I1')->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB('4472C4');

        // Catatan di baris 2 (digabung)
        $sheet->setCellValue('A2', 'CATATAN: Kode barang TIDAK perlu diisi — akan di-generate otomatis (format MAIN-USG-SUB-NOMOR dari jenis_item + jenis_pemakaian + kategori, mis. STK-GNR-OFC-000001). jenis_pemakaian boleh dikosongkan (dianggap General). Isi mulai baris 3.');
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('B45309');
        $sheet->getStyle('A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF3C7');

        // Sample row di baris 3
        $sampleKat = !empty($kategori_list) ? $kategori_list[0]->code : '';
        $sampleSat = !empty($satuan_list)   ? $satuan_list[0]->code   : '';
        $sampleKon = !empty($kondisi_list)  ? $kondisi_list[0]->code  : '';

        $sheet->setCellValue('A3', 'Contoh Barang');
        $sheet->setCellValue('B3', 'Stock');
        $sheet->setCellValue('C3', $sampleKat);
        $sheet->setCellValue('D3', $sampleSat);
        $sheet->setCellValue('E3', 'Merk Contoh');
        $sheet->setCellValue('F3', 'Gudang A');
        $sheet->setCellValue('G3', $sampleKon);
        $sheet->setCellValue('H3', 0);
        $sheet->setCellValue('I3', 'General');

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ===== Sheet 2: Referensi Kode =====
        $ref = $spreadsheet->createSheet();
        $ref->setTitle('Referensi Kode');

        $ref->setCellValue('A1', 'KATEGORI (INVENTORY_SUB_CATEGORY)');
        $ref->setCellValue('C1', 'SATUAN');
        $ref->setCellValue('E1', 'KONDISI (INVENTORY_CONDITION)');
        $ref->getStyle('A1:F1')->getFont()->setBold(true);

        $ref->setCellValue('A2', 'code'); $ref->setCellValue('B2', 'description');
        $ref->setCellValue('C2', 'code'); $ref->setCellValue('D2', 'description');
        $ref->setCellValue('E2', 'code'); $ref->setCellValue('F2', 'description');
        $ref->getStyle('A2:F2')->getFont()->setItalic(true);

        $writeRef = function($list, $colCode, $colDesc) use ($ref) {
            $r = 3;
            foreach ($list as $item) {
                $ref->setCellValue($colCode . $r, $item->code);
                $ref->setCellValue($colDesc . $r, $item->description);
                $r++;
            }
        };
        $writeRef($kategori_list, 'A', 'B');
        $writeRef($satuan_list,   'C', 'D');
        $writeRef($kondisi_list,  'E', 'F');

        foreach (range('A', 'F') as $col) {
            $ref->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'template_master_inventory.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Upload Excel untuk bulk insert Master Inventory.
     * Kode barang AUTO-GENERATE dari kategori — user tidak perlu input.
     * Header di baris 1, catatan di baris 2, data mulai baris 3.
     * Tetap aman jika user menempatkan data mulai baris 2 (baris catatan akan di-skip).
     */
    public function upload_excel() {
        if (!check_access('master_inventory', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        if (empty($_FILES['excel_file']['name'])) {
            $this->session->set_flashdata('error', 'Harap pilih file Excel (.xlsx/.xls).');
            redirect('master_inventory');
            return;
        }

        $ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'])) {
            $this->session->set_flashdata('error', 'Format file tidak didukung. Gunakan .xlsx atau .xls');
            redirect('master_inventory');
            return;
        }

        try {
            $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (\Exception $e) {
            $this->session->set_flashdata('error', 'Gagal membaca file: ' . $e->getMessage());
            redirect('master_inventory');
            return;
        }

        // Validasi kategori & satuan: kumpulkan code yang valid.
        // Kategori pakai grup baru INVENTORY_SUB_CATEGORY (kode master barang v2).
        $valid_kategori = array_map(function($k) { return strtoupper($k->code); }, $this->Inventory_model->get_sub_kategori_list());
        $valid_satuan   = array_map(function($s) { return strtoupper($s->code); }, $this->Inventory_model->get_satuan_list());
        $valid_item_type = ['STOCK' => 'Stock', 'NON-STOCK' => 'Non-Stock', 'NONSTOCK' => 'Non-Stock'];

        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $idx => $row) {
            // Skip header
            if ($idx == 1) continue;

            $nama_barang     = trim((string)($row['A'] ?? ''));
            $item_type_raw   = strtoupper(trim((string)($row['B'] ?? '')));
            $kategori_code   = strtoupper(trim((string)($row['C'] ?? '')));
            $satuan_code     = strtoupper(trim((string)($row['D'] ?? '')));
            $merk            = trim((string)($row['E'] ?? ''));
            $lokasi          = trim((string)($row['F'] ?? ''));
            $kondisi         = trim((string)($row['G'] ?? ''));
            $minimum_stock   = $row['H'] ?? 0;
            // Kolom baru (2026-10-06). Template lama tidak punya kolom I --
            // sengaja TIDAK diwajibkan supaya file template lama tetap bisa
            // di-import; kosong/salah ketik dianggap General (sama dgn default
            // kolom DB-nya).
            $usage_category  = Inventory_model::clean_usage_category($row['I'] ?? '');

            // Skip baris catatan / baris kosong
            if ($nama_barang === '' && $kategori_code === '' && $satuan_code === '') {
                continue;
            }
            // Skip baris yang isinya teks catatan (mengandung kata "CATATAN")
            if (stripos($nama_barang, 'CATATAN') !== false && $kategori_code === '') {
                continue;
            }

            if ($nama_barang === '') {
                $errors[] = "Baris $idx: nama_barang kosong";
                $failed++;
                continue;
            }
            if (!isset($valid_item_type[$item_type_raw])) {
                $errors[] = "Baris $idx: jenis_item wajib diisi 'Stock' atau 'Non-Stock'";
                $failed++;
                continue;
            }
            $item_type = $valid_item_type[$item_type_raw];
            if ($kategori_code === '') {
                $errors[] = "Baris $idx: kategori wajib diisi (kode wajib agar kode_barang bisa di-generate otomatis)";
                $failed++;
                continue;
            }
            if (!in_array($kategori_code, $valid_kategori)) {
                $errors[] = "Baris $idx: kategori code '$kategori_code' tidak terdaftar di INVENTORY_SUB_CATEGORY";
                $failed++;
                continue;
            }
            if ($satuan_code === '') {
                $errors[] = "Baris $idx: satuan wajib diisi";
                $failed++;
                continue;
            }
            if (!in_array($satuan_code, $valid_satuan)) {
                $errors[] = "Baris $idx: satuan code '$satuan_code' tidak terdaftar";
                $failed++;
                continue;
            }

            // Auto-generate kode_barang (format MAIN-USG-SUB-NUMBER)
            $kode_barang = $this->Inventory_model->generate_kode($item_type, $kategori_code, $usage_category);

            $data = [
                'kode_barang'   => $kode_barang,
                'nama_barang'   => $nama_barang,
                'kategori'      => $kategori_code,
                'item_type'     => $item_type,
                'satuan'        => $satuan_code,
                'merk'          => $merk,
                'lokasi'        => $lokasi,
                'kondisi'       => $kondisi,
                'minimum_stock' => is_numeric($minimum_stock) ? (float)$minimum_stock : 0,
                'status'        => 'Active',
                'usage_category' => $usage_category,
            ];

            if ($this->Inventory_model->insert($data)) {
                $success++;
            } else {
                $errors[] = "Baris $idx: gagal menyimpan ke database";
                $failed++;
            }
        }

        $msg = "$success baris berhasil di-import.";
        if ($failed > 0) {
            $msg .= " $failed baris gagal.<br>" . implode('<br>', $errors);
            $flash = $success > 0 ? 'warning' : 'error';
            $this->session->set_flashdata($flash, $msg);
        } else {
            $this->session->set_flashdata('success', $msg);
        }

        redirect('master_inventory');
    }
}
