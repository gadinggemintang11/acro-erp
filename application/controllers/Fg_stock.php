<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Dompdf\Dompdf;
use Dompdf\Options;

class Fg_stock extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->library('user_agent');
        $this->load->model(['Fg_stock_ledger_model', 'FinishedGoods_model']);
        $this->load->helper('url');
        $this->load->library('form_validation');
    }

    /**
     * FUNGSI INDEX (BARU) - Menampilkan Ringkasan Stok
     */
    public function index() {
        if (!check_access('fg_stock', 'view')) { show_error('Tidak ada izin.', 403); }

        $filters = [
            'search_summary' => $this->input->get('search_summary') ?: ''
        ];

        // Panggil fungsi model baru untuk ringkasan
        $stock_summary = $this->Fg_stock_ledger_model->get_current_stock_summary($filters);

        $data = [
            'title'         => 'Ringkasan Stok Barang Jadi (FG)',
            'filters'       => $filters,
            'stock_summary' => $stock_summary
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('fg_stock/index', $data); // Load view index (Ringkasan)
        $this->load->view('templates/footer');
    }

    /**
     * FUNGSI DETAIL (BARU) - Menampilkan Kartu Stok (Ledger)
     */
    public function detail($fg_id = 0) {
        if (!check_access('fg_stock', 'view')) { show_error('Tidak ada izin.', 403); }
        if (empty($fg_id) || !is_numeric($fg_id)) {
            $this->session->set_flashdata('error', 'Item Barang Jadi tidak valid.');
            redirect('fg_stock');
        }

        // Ambil info FG untuk judul, dll.
        $fg_info = $this->FinishedGoods_model->get_by_id($fg_id);
        if (!$fg_info) {
            $this->session->set_flashdata('error', 'Item Barang Jadi tidak ditemukan.');
            redirect('fg_stock');
        }

        // Filter untuk kartu stok
        $filters = [
            'fg_id'      => $fg_id, // FG ID sudah pasti
            'start_date' => $this->input->get('start_date') ?: date('Y-m-01'), // Default awal bulan
            'end_date'   => $this->input->get('end_date') ?: date('Y-m-d'), // Default hari ini
            'search'     => $this->input->get('search') ?: '',
        ];

        // Ambil data untuk kartu stok
        $opening_balance = $this->Fg_stock_ledger_model->get_opening_balance($filters['fg_id'], $filters['start_date']);
        $ledger_data = $this->Fg_stock_ledger_model->get_ledger($filters);

        $data = [
            'title'              => 'Kartu Stok - ' . htmlspecialchars($fg_info->nama_barang),
            'filters'            => $filters,
            'fg_info'            => $fg_info,
            'opening_balance'    => $opening_balance,
            'ledger_data'        => $ledger_data,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('fg_stock/detail_view', $data); // Load view detail baru
        $this->load->view('templates/footer');
    }

    /**
     * Export PDF Kartu Stok (Sekarang butuh FG ID)
     */
    public function export_pdf() {
        if (!check_access('fg_stock', 'view')) { show_error('Tidak ada izin.', 403); }

        $filters = [
            'fg_id'      => $this->input->get('fg_id'), // Ambil fg_id dari GET
            'start_date' => $this->input->get('start_date') ?: null,
            'end_date'   => $this->input->get('end_date') ?: null,
            'search'     => $this->input->get('search') ?: '',
        ];

        // PDF Kartu Stok WAJIB filter per FG
        if (empty($filters['fg_id']) || $filters['fg_id'] == 'all') {
             show_error('Harap pilih satu Barang Jadi spesifik untuk diekspor PDF.', 400);
        }

        $fg_info = $this->FinishedGoods_model->get_by_id($filters['fg_id']);
        if (!$fg_info) show_404();

        // Set default tanggal jika kosong (untuk saldo awal & query)
        $filters['start_date'] = $filters['start_date'] ?: date('Y-m-01');
        $filters['end_date'] = $filters['end_date'] ?: date('Y-m-d');

        $opening_balance = $this->Fg_stock_ledger_model->get_opening_balance($filters['fg_id'], $filters['start_date']);
        $ledger_data = $this->Fg_stock_ledger_model->get_ledger($filters);

        $data = [
            'title'           => 'Kartu Stok Barang Jadi',
            'filters'         => $filters,
            'fg_info'         => $fg_info,
            'opening_balance' => $opening_balance,
            'ledger_data'     => $ledger_data,
        ];

        // Generate PDF (sisanya sama)
        $options = new Options(); $options->set('isHtml5ParserEnabled', true); $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $html = $this->load->view('fg_stock/pdf_template', $data, true); // View PDF tetap sama
        $dompdf->loadHtml($html); $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        $filename = "KartuStokFG_".preg_replace('/[^A-Za-z0-9_\-]/', '_', $fg_info->kode_barang);
        $filename .= "_".($filters['start_date'] ?: 'ALL');
        $filename .= "_".($filters['end_date'] ?: 'ALL');
        $filename .= ".pdf";

        $dompdf->stream($filename, ["Attachment" => 0]);
        exit;
    }

    /**
     * AJAX Traceability (Tetap sama, akan dipanggil dari halaman detail_view)
     */
    public function ajax_get_traceability_info($source_doc_type, $source_doc_id, $transaction_id = null)
    {
        // Normalisasi tipe dokumen
        $source_doc_type = strtoupper(trim($source_doc_type));
        if (in_array($source_doc_type, ['FG_DELIVERY', 'FG_DELIVER'])) {
            $source_doc_type = 'FG_DELIVER';
        }

        // Ambil header dan detail
        $header = $this->Fg_stock_ledger_model->get_trace_header($source_doc_type, $source_doc_id);
        $rows   = $this->Fg_stock_ledger_model->get_trace_rows_by_doc($source_doc_type, $source_doc_id, $transaction_id);

        // Kalau tidak ada detail data
        if (empty($rows)) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Detail tidak ditemukan.'
            ]);
            return;
        }

        // Jika header kosong, isi dari fallback (data rows)
        if (empty($header)) {
            $first = $rows[0];

            $source_number = '-';
            $source_date   = '-';

            if (!empty($first['receipt_number'])) {
                $source_number = $first['receipt_number'];
                $source_date   = $first['receipt_date'] ?? '-';
            } elseif (!empty($first['delivery_number'])) {
                $source_number = $first['delivery_number'];
                $source_date   = $first['delivery_date'] ?? '-';
            }

            $header = [
                'source_type'   => $first['source_doc_type'] ?? '-',
                'source_id'     => $first['source_doc_id'] ?? '-',
                'source_number' => $source_number,
                'source_date'   => $source_date,
                'remarks'       => ''
            ];
        }

        // Kirim ke view modal
        $html = $this->load->view('fg_stock/trace_modal', [
            'header' => $header,
            'rows'   => $rows
        ], TRUE);

        echo json_encode([
            'status' => 'success',
            'html'   => $html
        ]);
    }





} // End Class