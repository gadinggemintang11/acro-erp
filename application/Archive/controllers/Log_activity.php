<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Log_activity extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->database();
        $this->load->library('pagination');
    }

    public function index() {
        // Cek akses
        check_access('log_activity', 'view'); // Pastikan Anda menambahkan permission ini di 'roleaccess'

        $data['title'] = 'Log Aktivitas Pengguna';
        
        // Konfigurasi Pagination
        $config['base_url'] = base_url('log_activity/index');
        $config['total_rows'] = $this->db->count_all('user_logs');
        $config['per_page'] = 25; // Jumlah log per halaman
        $config['uri_segment'] = 3;

        // Styling pagination (opsional, sesuaikan dengan Bootstrap 4)
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
        $page = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;

        // Ambil data log dengan limit dan offset
        $data['logs'] = $this->db
            ->order_by('log_time', 'DESC')
            ->limit($config['per_page'], $page)
            ->get('user_logs')
            ->result();
        
        $data['pagination'] = $this->pagination->create_links();

        // Load views
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('log_activity/index', $data); // Buat file view ini
        $this->load->view('templates/footer');
    }

    public function clear() {
        // Cek akses
        check_access('log_activity', 'delete'); // Izin khusus untuk hapus

        // Kosongkan tabel
        $this->db->truncate('user_logs');
        
        // Catat aksi pengosongan log (sebelum redirect)
        add_log('DELETE', 'Log Activity', 'Semua data log telah dikosongkan.');

        $this->session->set_flashdata('success', 'Semua log aktivitas telah berhasil dihapus.');
        redirect('log_activity');
    }

    public function export_excel() {
        // Cek akses
        check_access('log_activity', 'view');

        // Load PHPSpreadsheet
        require_once FCPATH . 'vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("ERP System")
            ->setTitle("Log Aktivitas Pengguna")
            ->setSubject("Export Log Activity")
            ->setDescription("Data log aktivitas pengguna sistem");

        // Header styling
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '667eea']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];

        // Set header
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Waktu');
        $sheet->setCellValue('C1', 'Username');
        $sheet->setCellValue('D1', 'Modul');
        $sheet->setCellValue('E1', 'Aksi');
        $sheet->setCellValue('F1', 'Deskripsi');

        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(50);

        // Get data
        $logs = $this->db->order_by('log_time', 'DESC')->get('user_logs')->result();

        // Fill data
        $row = 2;
        $no = 1;
        foreach ($logs as $log) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, date('d M Y, H:i:s', strtotime($log->log_time)));
            $sheet->setCellValue('C' . $row, $log->username);
            $sheet->setCellValue('D' . $row, $log->module);
            $sheet->setCellValue('E' . $row, $log->action);
            $sheet->setCellValue('F' . $row, $log->description);

            // Action color coding
            $actionStyle = [
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'font' => ['bold' => true]
            ];

            if ($log->action == 'CREATE') {
                $actionStyle['font']['color'] = ['rgb' => '38a169'];
            } elseif ($log->action == 'UPDATE') {
                $actionStyle['font']['color'] = ['rgb' => 'dd6b20'];
            } elseif ($log->action == 'DELETE') {
                $actionStyle['font']['color'] = ['rgb' => 'e53e3e'];
            } else {
                $actionStyle['font']['color'] = ['rgb' => '3182ce'];
            }

            $sheet->getStyle('E' . $row)->applyFromArray($actionStyle);

            // Borders for data cells
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ]);

            // Alternate row colors
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8F9FF']
                    ]
                ]);
            }

            $row++;
        }

        // Auto-size rows
        for ($i = 2; $i < $row; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(-1);
        }

        // Set active sheet index to the first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Redirect output to a client's web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Log_Activity_' . date('YmdHis') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');

        // Log export action
        add_log('EXPORT', 'Log Activity', 'Export log activity ke Excel');
    }

    public function export_pdf() {
        // Cek akses
        check_access('log_activity', 'view');

        // Get data
        $logs = $this->db->order_by('log_time', 'DESC')->get('user_logs')->result();

        $data['logs'] = $logs;
        $data['export_date'] = date('d F Y H:i:s');

        // Load PDF library
        $this->load->library('pdf');

        // Load view
        $html = $this->load->view('log_activity/pdf_template', $data, true);

        // Setup PDF
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'landscape');
        $this->pdf->render();

        // Output PDF
        $this->pdf->stream("Log_Activity_" . date('YmdHis') . ".pdf", array("Attachment" => 1));

        // Log export action
        add_log('EXPORT', 'Log Activity', 'Export log activity ke PDF');
    }
}