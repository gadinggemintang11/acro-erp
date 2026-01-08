<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Dompdf\Dompdf;
use Dompdf\Options;

class JurnalUmum extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        $this->load->model('JurnalUmum_model', 'model');
        $this->load->model('Coa_model'); 
        $this->load->library('form_validation');
        $this->load->helper('form');
    }

    public function index()
    {
        $data['title'] = lang_line('jurnal_umum_title', 'Jurnal Umum (Jurnal Entry)');
        $search = $this->input->get('search');
        $data['search'] = $search ? $search : '';
        
        if ($search) {
            $data['journals'] = $this->model->search_journals($search);
        } else {
            $data['journals'] = $this->model->get_all_headers();
        }
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/jurnal_umum/index', $data);
        $this->load->view('templates/footer');
    }

    public function create()
    {
        $data['title'] = lang_line('jurnal_umum_create', 'Buat Jurnal Umum Baru');
        $data['journal_no'] = $this->model->get_new_journal_number();
        $data['accounts'] = $this->model->get_postable_accounts();
        $data['currencies'] = $this->model->get_currencies(); 
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/jurnal_umum/create', $data);
        $this->load->view('templates/footer');
    }

    public function store()
    {
        $this->_validate_journal();
        $this->form_validation->set_rules('currency', 'Mata Uang', 'required');
        $this->form_validation->set_rules('exchange_rate', 'Kurs', 'required|numeric|greater_than[0]');

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $details = $this->input->post('details');
            $rate = (float) $this->input->post('exchange_rate');

            $totals = $this->_calculate_totals($details, $rate);
            
            if ($totals['debit'] != $totals['kredit']) {
                $this->session->set_flashdata('error', 'Gagal: Total Debit ('.$totals['debit'].') dan Kredit ('.$totals['kredit'].') tidak seimbang.');
                $this->create();
                return;
            }

            $header = [
                'journal_no' => $this->input->post('journal_no'),
                'journal_date' => $this->input->post('journal_date'),
                'description' => $this->input->post('description'),
                'currency' => $this->input->post('currency'),
                'exchange_rate' => $rate,
                'total_debit' => $totals['debit'],
                'total_kredit' => $totals['kredit'],
                'total_debit_local' => $totals['debit_local'],
                'total_kredit_local' => $totals['kredit_local'],
                'created_by' => $this->session->userdata('user_id'),
                'status' => 'Draft'
            ];

            $result = $this->model->insert_journal($header, $details);

            if ($result) {
                $this->session->set_flashdata('success', 'Jurnal berhasil disimpan sebagai Draft.');
                redirect('jurnalumum/view/' . $result);
            } else {
                $this->session->set_flashdata('error', 'Gagal menyimpan jurnal.');
                redirect('jurnalumum/create');
            }
        }
    }

    public function view($id)
    {
        $data['header'] = $this->model->get_header_by_id($id);
        if (!$data['header']) show_404();
        
        $data['title'] = 'View Jurnal: ' . $data['header']->journal_no;
        $data['details'] = $this->model->get_details_by_header_id($id);
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/jurnal_umum/view', $data);
        $this->load->view('templates/footer');
    }

    public function post($id)
    {
        $header = $this->model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya jurnal status Draft yang bisa di-posting.');
            redirect('jurnalumum/view/' . $id);
        }

        if ($header->total_debit != $header->total_kredit) {
             $this->session->set_flashdata('error', 'Jurnal tidak seimbang (Unbalanced). Posting dibatalkan.');
             redirect('jurnalumum/view/' . $id);
        }

        $result = $this->model->post_journal($id, $this->session->userdata('user_id'));
        if ($result) {
            $this->session->set_flashdata('success', 'Jurnal berhasil di-posting.');
        } else {
            $this->session->set_flashdata('error', 'Gagal mem-posting jurnal.');
        }
        redirect('jurnalumum/view/' . $id);
    }
    
    public function delete($id)
    {
        $result = $this->model->delete_draft_journal($id);
        if ($result) {
            $this->session->set_flashdata('success', 'Jurnal Draft berhasil dihapus.');
            redirect('jurnalumum');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus. Hanya status Draft yang bisa dihapus.');
            redirect('jurnalumum/view/' . $id);
        }
    }

    public function import()
    {
        $data['title'] = 'Impor Detail Jurnal';
        $data['journal_no'] = $this->input->get('journal_no');
        $data['journal_date'] = $this->input->get('journal_date');
        $data['description'] = $this->input->get('description');

        if(empty($data['journal_no'])) {
            $this->session->set_flashdata('error', 'Sesi impor tidak valid. Harap mulai dari form Jurnal Umum.');
            redirect('jurnalumum/create');
        }

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/jurnal_umum/import', $data);
        $this->load->view('templates/footer');
    }
    
    public function process_import()
    {
        $config['upload_path']   = './uploads/temp/';
        $config['allowed_types'] = 'xls|xlsx|csv';
        $config['max_size']      = 5120; // 5MB
        $config['encrypt_name']  = TRUE;

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0777, TRUE);
        }

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file_import')) {
            $this->session->set_flashdata('error', $this->upload->display_errors());
            redirect('jurnalumum/import?' . http_build_query($this->input->post()));
        } else {
            $file_data = $this->upload->data();
            $file_path = $config['upload_path'] . $file_data['file_name'];

            $extension = pathinfo($file_path, PATHINFO_EXTENSION);
            if ('csv' == $extension) {
                $reader = new Csv();
            } else if ('xls' == $extension) {
                $reader = new Xls();
            } else {
                $reader = new Xlsx();
            }

            try {
                $spreadsheet = $reader->load($file_path);
                $sheet_data = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
                
                $details = $this->model->process_import_data($sheet_data);
                
                unlink($file_path);

                if (empty($details)) {
                     $this->session->set_flashdata('error', 'File berhasil dibaca tetapi tidak ada data valid yang ditemukan. Pastikan format kolom benar.');
                     redirect('jurnalumum/import?' . http_build_query($this->input->post()));
                }

                $this->session->set_flashdata('imported_details', $details);
                $this->session->set_flashdata('success', 'File berhasil diimpor. Silakan periksa detail di bawah.');
                redirect('jurnalumum/create?' . http_build_query($this->input->post()));

            } catch (Exception $e) {
                unlink($file_path);
                $this->session->set_flashdata('error', 'Gagal membaca file: ' . $e->getMessage());
                redirect('jurnalumum/import?' . http_build_query($this->input->post()));
            }
        }
    }

    private function _validate_journal()
    {
        $this->form_validation->set_rules('journal_date', 'Tanggal Jurnal', 'required');
        $this->form_validation->set_rules('description', 'Keterangan', 'required');
        
        $details = $this->input->post('details');
        if (empty($details)) {
            $this->form_validation->set_rules('details_dummy', 'Detail Jurnal', 'required', ['required' => 'Detail Jurnal tidak boleh kosong.']);
        } else {
            foreach ($details as $i => $detail) {
                $this->form_validation->set_rules("details[{$i}][account_id]", "Baris " . ($i+1) . " Akun", 'required');
                $this->form_validation->set_rules("details[{$i}][debit]", "Baris " . ($i+1) . " Debit", 'numeric');
                $this->form_validation->set_rules("details[{$i}][kredit]", "Baris " . ($i+1) . " Kredit", 'numeric');
            }
        }
    }

    private function _calculate_totals($details, $rate = 1.0)
    {
        $total_debit = 0;
        $total_kredit = 0;
        $total_debit_local = 0;
        $total_kredit_local = 0;

        if (!empty($details)) {
            foreach ($details as $d) {
                $debit = (float) $d['debit'];
                $kredit = (float) $d['kredit'];
                
                $total_debit += $debit;
                $total_kredit += $kredit;
                $total_debit_local += $debit * $rate;
                $total_kredit_local += $kredit * $rate;
            }
        }
        return [
            'debit' => $total_debit, 
            'kredit' => $total_kredit,
            'debit_local' => $total_debit_local,
            'kredit_local' => $total_kredit_local
        ];
    }

    /**
     * ✅ Export ke Excel dengan validasi pembuat
     */
    public function export_excel($id)
    {
        $header = $this->model->get_header_by_id($id);
        if (!$header) show_404();
        
        $details = $this->model->get_details_by_header_id($id);
        $creator = $this->_get_user_by_id($header->created_by);
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Header
        $sheet->setCellValue('A1', 'JURNAL UMUM (JURNAL ENTRY)');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        
        $sheet->setCellValue('A2', 'No. Jurnal: ' . $header->journal_no);
        $sheet->setCellValue('A3', 'Tanggal: ' . date('d/m/Y', strtotime($header->journal_date)));
        $sheet->setCellValue('A4', 'Keterangan: ' . $header->description);
        $sheet->setCellValue('A5', 'Mata Uang: ' . $header->currency);
        $sheet->setCellValue('A6', 'Kurs: ' . number_format($header->exchange_rate, 4));
        $sheet->setCellValue('A7', 'Status: ' . $header->status);
        
        // Detail Header
        $sheet->setCellValue('A9', 'No.');
        $sheet->setCellValue('B9', 'Kode Akun');
        $sheet->setCellValue('C9', 'Nama Akun');
        $sheet->setCellValue('D9', 'Debit');
        $sheet->setCellValue('E9', 'Kredit');
        $sheet->setCellValue('F9', 'Keterangan');
        
        $sheet->getStyle('A9:F9')->getFont()->setBold(true);
        $sheet->getStyle('A9:F9')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFD3D3D3');
        
        // Detail Data
        $row = 10;
        $no = 1;
        foreach ($details as $detail) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $detail->account_code ?? '-');
            $sheet->setCellValue('C' . $row, $detail->account_name ?? '-');
            $sheet->setCellValue('D' . $row, (float)$detail->debit);
            $sheet->setCellValue('E' . $row, (float)$detail->kredit);
            $sheet->setCellValue('F' . $row, $detail->description ?? '');
            
            $sheet->getStyle('D' . $row . ':E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }
        
        // Total
        $sheet->setCellValue('C' . $row, 'TOTAL');
        $sheet->setCellValue('D' . $row, (float)$header->total_debit);
        $sheet->setCellValue('E' . $row, (float)$header->total_kredit);
        $sheet->getStyle('C' . $row . ':E' . $row)->getFont()->setBold(true);
        $sheet->getStyle('D' . $row . ':E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        
        // Validasi Pembuat
        $row += 2;
        $sheet->setCellValue('A' . $row, 'Dibuat oleh:');
        $sheet->setCellValue('A' . ($row + 1), $creator->fullname ?? 'Unknown');
        $sheet->setCellValue('A' . ($row + 2), $creator->username ?? '');
        $sheet->setCellValue('A' . ($row + 3), date('d/m/Y H:i:s', strtotime($header->created_at ?? now())));
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(20);
        
        $filename = 'Jurnal_' . str_replace('/', '-', $header->journal_no) . '_' . date('YmdHis') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        
        $writer = new XlsxWriter($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * ✅ Export ke PDF dengan validasi pembuat
     */
    public function export_pdf($id)
    {
        $header = $this->model->get_header_by_id($id);
        if (!$header) show_404();
        
        $details = $this->model->get_details_by_header_id($id);
        $creator = $this->_get_user_by_id($header->created_by);
        
        $data = [
            'header' => $header,
            'details' => $details,
            'creator' => $creator
        ];
        
        $html = $this->load->view('accounting/jurnal_umum/export_pdf', $data, true);
        
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = 'Jurnal_' . str_replace('/', '-', $header->journal_no) . '_' . date('YmdHis') . '.pdf';
        $dompdf->stream($filename, ['Attachment' => 0]);
    }

    /**
     * Helper: Get user by ID
     */
    private function _get_user_by_id($user_id)
    {
        if (empty($user_id)) {
            return (object) ['fullname' => 'Unknown', 'username' => ''];
        }
        return $this->db->select('id, fullname, username')->where('id', $user_id)->get('users')->row();
 
    }

    /**
     * ✅ Export List Jurnal ke Excel dengan Filter
     */
    public function export_list_excel()
    {
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $status = $this->input->get('status');
        
        // Build query dengan filter
        $this->db->select('jh.*, u.fullname as creator_name');
        $this->db->from('journal_header jh');
        $this->db->join('users u', 'jh.created_by = u.id', 'left');
        
        if (!empty($start_date)) {
            $this->db->where('jh.journal_date >=', $start_date);
        }
        if (!empty($end_date)) {
            $this->db->where('jh.journal_date <=', $end_date);
        }
        if (!empty($status)) {
            $this->db->where('jh.status', $status);
        }
        
        $this->db->order_by('jh.journal_date', 'DESC');
        $this->db->order_by('jh.id', 'DESC');
        $journals = $this->db->get()->result();
        
        // Create Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Header
        $sheet->setCellValue('A1', 'DAFTAR JURNAL UMUM');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        
        // Filter info
        $filterInfo = 'Periode: ';
        if ($start_date && $end_date) {
            $filterInfo .= date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date));
        } elseif ($start_date) {
            $filterInfo .= 'Dari ' . date('d/m/Y', strtotime($start_date));
        } elseif ($end_date) {
            $filterInfo .= 'Sampai ' . date('d/m/Y', strtotime($end_date));
        } else {
            $filterInfo .= 'Semua';
        }
        if ($status) {
            $filterInfo .= ' | Status: ' . $status;
        }
        
        $sheet->setCellValue('A2', $filterInfo);
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getFont()->setItalic(true);
        
        // Column Headers
        $sheet->setCellValue('A4', 'No.');
        $sheet->setCellValue('B4', 'No. Jurnal');
        $sheet->setCellValue('C4', 'Tanggal');
        $sheet->setCellValue('D4', 'Keterangan');
        $sheet->setCellValue('E4', 'Mata Uang');
        $sheet->setCellValue('F4', 'Debit');
        $sheet->setCellValue('G4', 'Kredit');
        $sheet->setCellValue('H4', 'Status');
        
        $sheet->getStyle('A4:H4')->getFont()->setBold(true);
        $sheet->getStyle('A4:H4')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A4:H4')->getFont()->getColor()->setARGB('FFFFFFFF');
        
        // Data
        $row = 5;
        $no = 1;
        $total_debit = 0;
        $total_kredit = 0;
        
        foreach ($journals as $j) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $j->journal_no);
            $sheet->setCellValue('C' . $row, date('d/m/Y', strtotime($j->journal_date)));
            $sheet->setCellValue('D' . $row, $j->description);
            $sheet->setCellValue('E' . $row, $j->currency ?? 'IDR');
            $sheet->setCellValue('F' . $row, (float)$j->total_debit);
            $sheet->setCellValue('G' . $row, (float)$j->total_kredit);
            $sheet->setCellValue('H' . $row, $j->status);
            
            $sheet->getStyle('F' . $row . ':G' . $row)->getNumberFormat()->setFormatCode('#,##0');
            
            $total_debit += (float)$j->total_debit;
            $total_kredit += (float)$j->total_kredit;
            
            $row++;
        }
        
        // Total
        $sheet->setCellValue('E' . $row, 'TOTAL:');
        $sheet->setCellValue('F' . $row, $total_debit);
        $sheet->setCellValue('G' . $row, $total_kredit);
        $sheet->getStyle('E' . $row . ':G' . $row)->getFont()->setBold(true);
        $sheet->getStyle('F' . $row . ':G' . $row)->getNumberFormat()->setFormatCode('#,##0');
        
        // Column widths
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(35);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(12);
        
        $filename = 'Daftar_Jurnal_' . date('YmdHis') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        
        $writer = new XlsxWriter($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * ✅ BATCH IMPORT - Upload Excel dengan banyak transaksi
     * Pemisah transaksi menggunakan kolom Reference
     */
    public function batch_import()
    {
        $data['title'] = lang_line('jurnal_umum_batch_import', 'Batch Import Jurnal Umum');
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/jurnal_umum/batch_import', $data);
        $this->load->view('templates/footer');
    }

    /**
     * ✅ Process Batch Import dari Excel
     */
    public function process_batch_import()
    {
        // Gunakan absolute path untuk upload directory
        $upload_path = FCPATH . 'uploads/temp/';
        
        // Buat direktori jika belum ada
        if (!is_dir($upload_path)) {
            if (!mkdir($upload_path, 0777, TRUE)) {
                $this->session->set_flashdata('error', 'Gagal membuat folder upload. Periksa permission folder.');
                redirect('jurnalumum/batch_import');
                return;
            }
        }
        
        // Cek apakah folder writable
        if (!is_writable($upload_path)) {
            $this->session->set_flashdata('error', 'Folder upload tidak dapat ditulis. Path: ' . $upload_path);
            redirect('jurnalumum/batch_import');
            return;
        }
        
        $config['upload_path']   = $upload_path;
        $config['allowed_types'] = 'xls|xlsx|csv';
        $config['max_size']      = 10240; // 10MB
        $config['encrypt_name']  = TRUE;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file_batch')) {
            $error = $this->upload->display_errors('', '');
            $this->session->set_flashdata('error', 'Upload gagal: ' . $error);
            redirect('jurnalumum/batch_import');
        } else {
            $file_data = $this->upload->data();
            $file_path = $file_data['full_path'];

            $extension = pathinfo($file_path, PATHINFO_EXTENSION);
            if ('csv' == $extension) {
                $reader = new Csv();
            } else if ('xls' == $extension) {
                $reader = new Xls();
            } else {
                $reader = new Xlsx();
            }

            try {
                $spreadsheet = $reader->load($file_path);
                $worksheet = $spreadsheet->getActiveSheet();
                
                // ✅ Baca data dengan cara yang benar untuk preserve numeric values
                $sheet_data = [];
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                
                for ($row = 1; $row <= $highestRow; $row++) {
                    $rowData = [];
                    foreach (range('A', $highestColumn) as $col) {
                        $cell = $worksheet->getCell($col . $row);
                        // Get calculated value (untuk formula) atau raw value
                        $rowData[$col] = $cell->getCalculatedValue();
                    }
                    $sheet_data[$row] = $rowData;
                }
                
                // Process batch data
                $result = $this->model->process_batch_import($sheet_data, $this->session->userdata('user_id'));
                
                unlink($file_path);

                if ($result['success']) {
                    $message = "Berhasil mengimpor {$result['total_transactions']} transaksi jurnal:<br>";
                    foreach ($result['journal_numbers'] as $jno) {
                        $message .= "✓ {$jno}<br>";
                    }
                    $this->session->set_flashdata('success', $message);
                    redirect('jurnalumum');
                } else {
                    $this->session->set_flashdata('error', 'Gagal mengimpor: ' . $result['message']);
                    redirect('jurnalumum/batch_import');
                }

            } catch (Exception $e) {
                if (file_exists($file_path)) unlink($file_path);
                $this->session->set_flashdata('error', 'Gagal membaca file: ' . $e->getMessage());
                redirect('jurnalumum/batch_import');
            }
        }
    }

    /**
     * ✅ Download Template Excel untuk Batch Import
     */
    public function download_batch_template()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Header
        $sheet->setCellValue('A1', 'TEMPLATE BATCH IMPORT JURNAL UMUM');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        
        $sheet->setCellValue('A2', 'Petunjuk: Isi data mulai dari baris 5. Setiap transaksi baru ditandai dengan Reference baru di kolom pertama.');
        $sheet->mergeCells('A2:H2');
        
        $sheet->setCellValue('A3', 'PENTING: Untuk kolom Debit/Kredit, isi angka TANPA titik atau koma. Contoh: 50000000 (bukan 50.000.000)');
        $sheet->mergeCells('A3:H3');
        $sheet->getStyle('A3')->getFont()->setBold(true)->getColor()->setARGB('FFFF0000');
        
        // Column Headers
        $sheet->setCellValue('A4', 'Reference');
        $sheet->setCellValue('B4', 'Tanggal (YYYY-MM-DD)');
        $sheet->setCellValue('C4', 'Keterangan Jurnal');
        $sheet->setCellValue('D4', 'Kode Akun');
        $sheet->setCellValue('E4', 'Nama Akun');
        $sheet->setCellValue('F4', 'Debit');
        $sheet->setCellValue('G4', 'Kredit');
        $sheet->setCellValue('H4', 'Keterangan Detail');
        
        $sheet->getStyle('A4:H4')->getFont()->setBold(true);
        $sheet->getStyle('A4:H4')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A4:H4')->getFont()->getColor()->setARGB('FFFFFFFF');
        
        // Contoh Data - Transaksi 1: Gaji (Debit dulu, lalu Kredit)
        $sheet->setCellValue('A5', 'GAJI-JAN-2026');
        $sheet->setCellValue('B5', '2026-01-31');
        $sheet->setCellValue('C5', 'Pembayaran Gaji Karyawan Januari 2026');
        $sheet->setCellValueExplicit('D5', '5.200.001.00', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('E5', 'Beban Gaji');
        $sheet->setCellValue('F5', 50000000);
        $sheet->setCellValue('G5', 0);
        $sheet->setCellValue('H5', 'Gaji pokok karyawan');
        
        $sheet->setCellValue('A6', '');
        $sheet->setCellValue('B6', '2026-01-31');
        $sheet->setCellValue('C6', '');
        $sheet->setCellValueExplicit('D6', '1.101.001.00', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('E6', 'Kas');
        $sheet->setCellValue('F6', 0);
        $sheet->setCellValue('G6', 50000000);
        $sheet->setCellValue('H6', 'Pembayaran tunai');
        
        // Transaksi 2: Penjualan (Kredit dulu, lalu Debit - Reference di kredit)
        $sheet->setCellValue('A7', 'PENJUALAN-001');
        $sheet->setCellValue('B7', '2026-01-15');
        $sheet->setCellValue('C7', 'Penjualan Tunai');
        $sheet->setCellValueExplicit('D7', '4.110.001.00', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('E7', 'Pendapatan Penjualan');
        $sheet->setCellValue('F7', 0);
        $sheet->setCellValue('G7', 10000000);
        $sheet->setCellValue('H7', 'Pendapatan hari ini');
        
        $sheet->setCellValue('A8', '');
        $sheet->setCellValue('B8', '2026-01-15');
        $sheet->setCellValue('C8', '');
        $sheet->setCellValueExplicit('D8', '1.101.001.00', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('E8', 'Kas');
        $sheet->setCellValue('F8', 10000000);
        $sheet->setCellValue('G8', 0);
        $sheet->setCellValue('H8', 'Terima tunai');
        
        // Transaksi 3: Listrik dengan banyak detail
        $sheet->setCellValue('A9', 'LISTRIK-JAN-2026');
        $sheet->setCellValue('B9', '2026-01-20');
        $sheet->setCellValue('C9', 'Biaya Listrik Multi Lokasi');
        $sheet->setCellValueExplicit('D9', '6.220.001.00', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('E9', 'Beban Listrik');
        $sheet->setCellValue('F9', 3000000);
        $sheet->setCellValue('G9', 0);
        $sheet->setCellValue('H9', 'Kantor Pusat');
        
        $sheet->setCellValue('A10', '');
        $sheet->setCellValue('B10', '2026-01-20');
        $sheet->setCellValue('C10', '');
        $sheet->setCellValueExplicit('D10', '6.220.001.00', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('E10', 'Beban Listrik');
        $sheet->setCellValue('F10', 1500000);
        $sheet->setCellValue('G10', 0);
        $sheet->setCellValue('H10', 'Kantor Cabang');
        
        $sheet->setCellValue('A11', '');
        $sheet->setCellValue('B11', '2026-01-20');
        $sheet->setCellValue('C11', '');
        $sheet->setCellValueExplicit('D11', '1.101.001.00', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('E11', 'Kas');
        $sheet->setCellValue('F11', 0);
        $sheet->setCellValue('G11', 4500000);
        $sheet->setCellValue('H11', 'Pembayaran tunai');
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(25);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(25);
        
        // Format angka
        $sheet->getStyle('F5:G11')->getNumberFormat()->setFormatCode('#,##0');
        
        $filename = 'Template_Batch_Jurnal_' . date('YmdHis') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        
        $writer = new XlsxWriter($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}