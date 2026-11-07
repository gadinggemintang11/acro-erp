<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class Initial_stock extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Initial_stock_model');
        $this->load->library('form_validation');
        $this->load->library('session');
        $this->load->helper(['form', 'url']);
    }

    public function index() {
        if (!check_access('initial_stock', 'view')) {
            show_error('Anda tidak memiliki izin mengakses menu ini.', 403);
        }

        $data = [
            'title' => lang_line('title_is_index'),
            'inventories' => $this->Initial_stock_model->get_all_inventories(),
            'drafts' => $this->Initial_stock_model->get_draft_list(),
            'confirmed' => $this->Initial_stock_model->get_confirmed_list()
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('initial_stock/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Save ke Draft via AJAX
     */
    public function save() {
        if (!$this->input->is_ajax_request()) {
            show_error('Invalid Request', 400);
        }

        if (!check_access('initial_stock', 'add')) {
            echo json_encode(['status' => false, 'message' => 'Tidak ada izin']);
            return;
        }

        $item_id = $this->input->post('item_id');
        $quantity = $this->input->post('quantity');
        $transaction_date = $this->input->post('transaction_date');
        $notes = $this->input->post('notes');
        $category = $this->input->post('category');
        if (!in_array($category, ['General', 'Produksi'], true)) { $category = 'General'; }

        if (empty($item_id) || !is_numeric($item_id)) {
            echo json_encode(['status' => false, 'message' => 'Item tidak valid']);
            return;
        }

        if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
            echo json_encode(['status' => false, 'message' => 'Quantity harus > 0']);
            return;
        }

        if (empty($transaction_date)) {
            echo json_encode(['status' => false, 'message' => 'Tanggal periode harus dipilih']);
            return;
        }

        $result = $this->Initial_stock_model->save_draft($item_id, $quantity, $transaction_date, $notes, $category);
        echo json_encode($result);
    }

    /**
     * Confirm draft (AJAX)
     */
    public function confirm() {
        if (!$this->input->is_ajax_request()) {
            show_error('Invalid Request', 400);
        }

        if (!check_access('initial_stock', 'add')) {
            echo json_encode(['status' => false, 'message' => 'Tidak ada izin']);
            return;
        }

        $draft_id = $this->input->post('draft_id');
        if (empty($draft_id) || !is_numeric($draft_id)) {
            echo json_encode(['status' => false, 'message' => 'Draft ID tidak valid']);
            return;
        }

        $result = $this->Initial_stock_model->confirm_draft($draft_id);
        echo json_encode($result);
    }

    /**
     * Unconfirm draft (AJAX)
     */
    public function unconfirm() {
        if (!$this->input->is_ajax_request()) {
            show_error('Invalid Request', 400);
        }

        if (!check_access('initial_stock', 'add')) {
            echo json_encode(['status' => false, 'message' => 'Tidak ada izin']);
            return;
        }

        $draft_id = $this->input->post('draft_id');
        if (empty($draft_id) || !is_numeric($draft_id)) {
            echo json_encode(['status' => false, 'message' => 'Draft ID tidak valid']);
            return;
        }

        $result = $this->Initial_stock_model->unconfirm_draft($draft_id);
        echo json_encode($result);
    }

    /**
     * Confirm banyak draft sekaligus (AJAX)
     */
    public function confirm_bulk() {
        if (!$this->input->is_ajax_request()) {
            show_error('Invalid Request', 400);
        }

        if (!check_access('initial_stock', 'add')) {
            echo json_encode(['status' => false, 'message' => 'Tidak ada izin']);
            return;
        }

        $ids = $this->input->post('draft_ids');
        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['status' => false, 'message' => 'Tidak ada draft yang dipilih']);
            return;
        }

        $ids = array_filter(array_map('intval', $ids));
        $result = $this->Initial_stock_model->confirm_bulk($ids);

        $msg = "{$result['success']} draft dikonfirmasi.";
        if ($result['failed'] > 0) {
            $msg .= " {$result['failed']} gagal.<br>" . implode('<br>', array_slice($result['errors'], 0, 20));
        }
        $result['message'] = $msg;
        echo json_encode($result);
    }

    /**
     * Hapus banyak draft sekaligus (AJAX)
     */
    public function delete_bulk() {
        if (!$this->input->is_ajax_request()) {
            show_error('Invalid Request', 400);
        }

        if (!check_access('initial_stock', 'delete')) {
            echo json_encode(['status' => false, 'message' => 'Tidak ada izin']);
            return;
        }

        $ids = $this->input->post('draft_ids');
        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['status' => false, 'message' => 'Tidak ada draft yang dipilih']);
            return;
        }

        $ids = array_filter(array_map('intval', $ids));
        $result = $this->Initial_stock_model->delete_bulk($ids);

        $msg = "{$result['success']} draft dihapus.";
        if ($result['failed'] > 0) {
            $msg .= " {$result['failed']} gagal.<br>" . implode('<br>', array_slice($result['errors'], 0, 20));
        }
        $result['message'] = $msg;
        echo json_encode($result);
    }

    /**
     * Export hasil stock awal (data Confirmed) ke Excel.
     */
    public function export_excel() {
        if (!check_access('initial_stock', 'view')) {
            show_error('Anda tidak memiliki izin untuk mengekspor data.', 403);
        }

        $rows = $this->Initial_stock_model->get_confirmed_list();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Awal');

        // ===== Judul =====
        $sheet->setCellValue('A1', 'HASIL STOCK AWAL (CONFIRMED)');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Tanggal cetak: ' . date('d/m/Y H:i'));
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ===== Header tabel =====
        $headers = [
            'A' => 'No',
            'B' => 'Kode Barang',
            'C' => 'Nama Barang',
            'D' => 'Kategori',   // General / Produksi (ditambah 2026-09-10)
            'E' => 'Tgl Periode',
            'F' => 'Qty',
            'G' => 'Satuan',
            'H' => 'Tgl Confirm',
            'I' => 'Catatan',
        ];
        $headerRow = 4;
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $headerRow, $label);
        }
        $sheet->getStyle('A' . $headerRow . ':I' . $headerRow)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A' . $headerRow . ':I' . $headerRow)->getFill()
              ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2E7D32');

        // ===== Isi data =====
        $r = $headerRow + 1;
        $no = 1;
        $total_qty = 0;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $r, $no++);
            $sheet->setCellValueExplicit('B' . $r, $row->kode_barang, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $r, $row->nama_barang);
            $sheet->setCellValue('D' . $r, $row->category ?? 'General');
            $sheet->setCellValue('E' . $r, $row->transaction_date ? date('d/m/Y', strtotime($row->transaction_date)) : '');
            $sheet->setCellValueExplicit('F' . $r, (float)$row->quantity, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValue('G' . $r, $row->satuan_desc);
            $sheet->setCellValue('H' . $r, $row->confirmed_at ? date('d/m/Y', strtotime($row->confirmed_at)) : '');
            $sheet->setCellValue('I' . $r, $row->notes);
            $total_qty += (float)$row->quantity;
            $r++;
        }

        // ===== Border seluruh tabel =====
        if (!empty($rows)) {
            $sheet->getStyle('A' . $headerRow . ':I' . ($r - 1))
                  ->getBorders()->getAllBorders()
                  ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        } else {
            $sheet->setCellValue('A' . $r, 'Belum ada data yang dikonfirmasi');
            $sheet->mergeCells('A' . $r . ':H' . $r);
            $r++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'stock_awal_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Download template Excel untuk import stock awal.
     * Sheet di-prefill dengan semua barang aktif; user tinggal isi Qty & Tanggal.
     */
    public function download_template() {
        if (!check_access('initial_stock', 'add')) {
            show_error('Anda tidak memiliki izin untuk mengunduh template.', 403);
        }

        $items = $this->Initial_stock_model->get_active_inventories_for_template();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Awal');

        // Kolom `kategori` DITAMBAHKAN 2026-09-10 (user: "initial stock ini kan
        // otomatis ke general, bisa ga buatin untuk yang kebutuhan produksi").
        // Sebelumnya template tidak punya kolom ini sama sekali, jadi APA PUN
        // yang diimport selalu mendarat di gudang General — itulah kenapa 154
        // baris stock awal yang ada sekarang semuanya General.
        //
        // Kolom disisipkan di tengah (D), jadi tanggal/qty/catatan bergeser.
        // Supaya file template LAMA yang sudah terlanjur diisi user tidak jadi
        // salah baca, import_excel() sekarang memetakan kolom lewat NAMA di
        // baris header, bukan huruf kolom yang dipatok. Lihat _column_map().
        $headers = [
            'A' => 'kode_barang *',
            'B' => 'nama_barang (info)',
            'C' => 'satuan (info)',
            'D' => 'kategori (General/Produksi) *',
            'E' => 'tanggal_periode (YYYY-MM-DD) *',
            'F' => 'qty_awal *',
            'G' => 'catatan',
        ];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . '1', $label);
        }

        $sheet->getStyle('A1:G1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A1:G1')->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB('C62828');

        $today = date('Y-m-d');
        $row = 2;
        foreach ($items as $item) {
            $sheet->setCellValueExplicit('A' . $row, $item->kode_barang, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $row, $item->nama_barang);
            $sheet->setCellValue('C' . $row, $item->satuan_desc);
            $sheet->setCellValue('D' . $row, 'General');   // default, user tinggal ganti ke Produksi
            $sheet->setCellValue('E' . $row, $today);
            // Qty (F) & catatan (G) sengaja dikosongkan untuk diisi user
            $row++;
        }

        // Jika belum ada barang, beri 1 baris contoh
        if (empty($items)) {
            $sheet->setCellValue('A2', 'KODE-001');
            $sheet->setCellValue('B2', 'Contoh Barang');
            $sheet->setCellValue('C2', 'PCS');
            $sheet->setCellValue('D2', 'General');
            $sheet->setCellValue('E2', $today);
            $sheet->setCellValue('F2', 0);
        }

        // Dropdown General/Produksi di kolom kategori supaya user tidak salah ketik.
        $last_row = max(2, $row - 1);
        $validation = $sheet->getCell('D2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                   ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP)
                   ->setAllowBlank(false)->setShowInputMessage(true)
                   ->setShowErrorMessage(true)->setShowDropDown(true)
                   ->setErrorTitle('Kategori tidak valid')
                   ->setError('Pilih General atau Produksi.')
                   ->setFormula1('"General,Produksi"');
        for ($r = 3; $r <= $last_row; $r++) {
            $sheet->getCell('D' . $r)->setDataValidation(clone $validation);
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'template_initial_stock.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Import Excel → buat Draft stock awal (belum posting ledger).
     *
     * Kolom dipetakan lewat NAMA di baris header (bukan huruf kolom yang
     * dipatok) sejak 2026-09-10 — waktu kolom `kategori` disisipkan, posisi
     * tanggal/qty/catatan bergeser, dan file template LAMA yang sudah
     * terlanjur diisi user akan salah baca kalau hurufnya dihardcode.
     * Lihat _column_map().
     */
    public function import_excel() {
        if (!check_access('initial_stock', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        if (empty($_FILES['excel_file']['name'])) {
            $this->session->set_flashdata('error', 'Harap pilih file Excel (.xlsx/.xls).');
            redirect('initial_stock');
            return;
        }

        $ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'])) {
            $this->session->set_flashdata('error', 'Format file tidak didukung. Gunakan .xlsx atau .xls');
            redirect('initial_stock');
            return;
        }

        try {
            $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (\Exception $e) {
            $this->session->set_flashdata('error', 'Gagal membaca file: ' . $e->getMessage());
            redirect('initial_stock');
            return;
        }

        $success = 0;
        $failed = 0;
        $skipped = 0;
        $errors = [];

        $map = $this->_column_map($rows[1] ?? []);

        foreach ($rows as $idx => $row) {
            if ($idx == 1) continue; // skip header

            $get = function ($key) use ($row, $map) {
                if (empty($map[$key])) return '';
                return trim((string) ($row[$map[$key]] ?? ''));
            };

            $kode_barang = $get('kode');
            $tgl_raw     = $get('tanggal');
            $qty_raw     = $get('qty');
            $notes       = $get('catatan');
            $kategori    = $get('kategori');

            // Baris tanpa qty diabaikan (user tidak mengisi barang ini)
            if ($qty_raw === '') {
                continue;
            }

            if ($kode_barang === '') {
                $errors[] = "Baris $idx: kode_barang kosong";
                $failed++;
                continue;
            }

            if (!is_numeric($qty_raw) || (float)$qty_raw <= 0) {
                $errors[] = "Baris $idx: qty_awal harus angka > 0";
                $failed++;
                continue;
            }

            $item = $this->Initial_stock_model->get_inventory_by_kode($kode_barang);
            if (!$item) {
                $errors[] = "Baris $idx: kode_barang '$kode_barang' tidak ditemukan";
                $failed++;
                continue;
            }

            // Normalisasi tanggal (terima YYYY-MM-DD, DD/MM/YYYY, atau serial Excel)
            $transaction_date = $this->_parse_date($tgl_raw);
            if ($transaction_date === '') {
                $transaction_date = date('Y-m-d');
            }

            // Kategori gudang. Kosong = General, supaya file template LAMA
            // (yang memang belum punya kolom ini) tetap terbaca seperti dulu.
            if ($kategori === '') {
                $kategori = 'General';
            } else {
                $kategori = ucfirst(strtolower($kategori));
                if (!in_array($kategori, ['General', 'Produksi'], true)) {
                    $errors[] = "Baris $idx: kategori '" . $get('kategori') . "' tidak dikenal (isi General atau Produksi)";
                    $failed++;
                    continue;
                }
            }

            $result = $this->Initial_stock_model->save_draft($item->id, (float)$qty_raw, $transaction_date, $notes, $kategori);
            if (!empty($result['status'])) {
                $success++;
            } else {
                // item Confirmed di-skip, bukan dihitung gagal
                $skipped++;
                $errors[] = "Baris $idx ($kode_barang): " . ($result['message'] ?? 'gagal');
            }
        }

        $msg = "$success draft berhasil dibuat.";
        if ($skipped > 0) $msg .= " $skipped dilewati.";
        if ($failed > 0)  $msg .= " $failed gagal.";

        if ($failed > 0 || $skipped > 0) {
            $msg .= '<br>' . implode('<br>', array_slice($errors, 0, 30));
            if (count($errors) > 30) {
                $msg .= '<br>... dan ' . (count($errors) - 30) . ' lainnya.';
            }
        }

        $flash = ($success > 0) ? ($failed > 0 ? 'warning' : 'success') : 'error';
        $this->session->set_flashdata($flash, $msg);
        redirect('initial_stock');
    }

    /**
     * Parse tanggal dari sel Excel ke format Y-m-d.
     */
    /**
     * Petakan nama kolom di baris header Excel -> huruf kolomnya.
     *
     * Dibuat waktu kolom `kategori` disisipkan di tengah template
     * (2026-09-10): kalau huruf kolom dipatok, file template LAMA yang sudah
     * diisi user akan terbaca melenceng satu kolom (tanggal kebaca sbg
     * kategori, dst). Dengan pemetaan berbasis nama, template lama MAUPUN baru
     * dua-duanya terbaca benar.
     *
     * Kalau baris header tidak dikenali sama sekali (mis. file dibuat sendiri
     * tanpa header), jatuh ke tata letak template LAMA — persis perilaku
     * sebelum perubahan ini, jadi tidak ada import yang mendadak gagal.
     */
    private function _column_map($header_row) {
        $needles = [
            'kode'     => ['kode_barang', 'kode barang', 'kode'],
            'kategori' => ['kategori', 'category', 'gudang'],
            'tanggal'  => ['tanggal_periode', 'tanggal periode', 'tanggal', 'tgl'],
            'qty'      => ['qty_awal', 'qty awal', 'qty', 'jumlah'],
            'catatan'  => ['catatan', 'notes', 'keterangan'],
        ];

        $map = [];
        foreach ((array) $header_row as $col => $label) {
            $label = strtolower(trim((string) $label));
            if ($label === '') continue;
            foreach ($needles as $key => $variants) {
                if (isset($map[$key])) continue;
                foreach ($variants as $v) {
                    if (strpos($label, $v) !== false) { $map[$key] = $col; break 2; }
                }
            }
        }

        // Header tidak terbaca -> pakai tata letak template LAMA (tanpa kategori).
        if (empty($map['kode']) || empty($map['qty'])) {
            return ['kode' => 'A', 'tanggal' => 'D', 'qty' => 'E', 'catatan' => 'F'];
        }
        return $map;
    }

    private function _parse_date($value) {
        $value = trim((string)$value);
        if ($value === '') return '';

        // Serial date Excel (angka)
        if (is_numeric($value)) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
                return $dt->format('Y-m-d');
            } catch (\Exception $e) {
                return '';
            }
        }

        $ts = strtotime(str_replace('/', '-', $value));
        return $ts ? date('Y-m-d', $ts) : '';
    }

    /**
     * Delete draft
     */
    public function delete($draft_id) {
        if (!check_access('initial_stock', 'delete')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        if (empty($draft_id) || !is_numeric($draft_id)) {
            show_404();
        }

        $result = $this->Initial_stock_model->delete_draft($draft_id);

        if ($result['status']) {
            $this->session->set_flashdata('success', $result['message']);
        } else {
            $this->session->set_flashdata('error', $result['message']);
        }

        redirect('initial_stock');
    }
}
