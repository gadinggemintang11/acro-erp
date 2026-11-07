<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Master Karyawan — CRUD karyawan untuk reimbursement (ERF).
 * Termasuk import / template Excel.
 */
class Masterkaryawan extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Masterkaryawan_model', 'model');
        $this->load->helper(['form', 'url']);
        $this->load->library('pagination');
    }

    public function index() {
        if (!check_access('master_karyawan', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $search = $this->input->get('search', true);
        $status = $this->input->get('status', true);
        $page   = (int) $this->input->get('page', 0);

        $limit  = 10;
        $offset = $page > 0 ? ($page - 1) * $limit : 0;

        $total_rows = $this->model->count_all($search, $status);

        $config['base_url']             = site_url('masterkaryawan');
        $config['total_rows']           = $total_rows;
        $config['per_page']             = $limit;
        $config['page_query_string']    = TRUE;
        $config['query_string_segment'] = 'page';
        $config['reuse_query_string']   = TRUE;
        $config['use_page_numbers']     = TRUE;
        $config['full_tag_open']  = '<ul class="pagination justify-content-end">';
        $config['full_tag_close'] = '</ul>';
        $config['num_tag_open']   = '<li class="page-item">';
        $config['num_tag_close']  = '</li>';
        $config['cur_tag_open']   = '<li class="page-item active"><a class="page-link" href="#">';
        $config['cur_tag_close']  = '</a></li>';
        $config['first_tag_open'] = '<li class="page-item">';
        $config['first_tag_close']= '</li>';
        $config['last_tag_open']  = '<li class="page-item">';
        $config['last_tag_close'] = '</li>';
        $config['next_tag_open']  = '<li class="page-item">';
        $config['next_tag_close'] = '</li>';
        $config['prev_tag_open']  = '<li class="page-item">';
        $config['prev_tag_close'] = '</li>';
        $config['attributes']     = ['class' => 'page-link'];
        $this->pagination->initialize($config);

        $data = [
            'title'      => lang_line('karyawan_title', 'Master Karyawan'),
            'karyawan'   => $this->model->get_paginated($search, $status, $limit, $offset),
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $total_rows,
            'search'     => $search,
            'status'     => $status,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('masterkaryawan/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        if (!check_access('master_karyawan', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        $nip = trim($this->input->post('nip'));
        if (!empty($nip) && $this->model->nip_exists($nip)) {
            $this->session->set_flashdata('error', 'NIP "' . $nip . '" sudah terdaftar.');
            redirect('masterkaryawan');
            return;
        }

        $data = [
            'nama_karyawan' => trim($this->input->post('nama_karyawan')),
            'nip'           => $nip ?: null,
            'bank_name'     => trim($this->input->post('bank_name')) ?: null,
            'bank_account'  => trim($this->input->post('bank_account')) ?: null,
            'status'        => 'active',
            'created_by'    => $this->session->userdata('user_id'),
        ];

        if (empty($data['nama_karyawan'])) {
            $this->session->set_flashdata('error', 'Nama karyawan wajib diisi.');
            redirect('masterkaryawan');
            return;
        }

        if ($this->model->insert($data)) {
            $this->session->set_flashdata('success', 'Data karyawan berhasil ditambahkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan data!');
        }
        redirect('masterkaryawan');
    }

    public function update($id) {
        if (!check_access('master_karyawan', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }

        $nip = trim($this->input->post('nip'));
        if (!empty($nip) && $this->model->nip_exists($nip, $id)) {
            $this->session->set_flashdata('error', 'NIP "' . $nip . '" sudah dipakai karyawan lain.');
            redirect('masterkaryawan');
            return;
        }

        $data = [
            'nama_karyawan' => trim($this->input->post('nama_karyawan')),
            'nip'           => $nip ?: null,
            'bank_name'     => trim($this->input->post('bank_name')) ?: null,
            'bank_account'  => trim($this->input->post('bank_account')) ?: null,
            'status'        => $this->input->post('status') ?: 'active',
            'updated_by'    => $this->session->userdata('user_id'),
        ];

        if ($this->model->update($id, $data)) {
            $this->session->set_flashdata('success', 'Data karyawan berhasil diperbarui!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui data!');
        }
        redirect('masterkaryawan');
    }

    public function delete($id) {
        if (!check_access('master_karyawan', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }

        if ($this->model->delete($id)) {
            $this->session->set_flashdata('success', 'Karyawan berhasil dinonaktifkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan data!');
        }
        redirect('masterkaryawan');
    }

    // ============================================================
    // IMPORT EXCEL
    // ============================================================

    /**
     * Download template .xlsx untuk import.
     * Kolom: Nama Karyawan | NIP | Nama Bank | No. Rekening
     */
    public function download_template() {
        if (!check_access('master_karyawan', 'add')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Karyawan');

        $headers = ['Nama Karyawan', 'NIP', 'Nama Bank', 'No. Rekening'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D1')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFDDEBF7');
        $sheet->getStyle('A1:D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Contoh baris
        $sheet->fromArray(['Budi Santoso', 'NIP001', 'BCA', '1234567890'], null, 'A2');

        foreach (['A' => 30, 'B' => 18, 'C' => 20, 'D' => 22] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $filename = 'Template_Master_Karyawan.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        (new Xlsx($ss))->save('php://output');
        exit;
    }

    /**
     * Import dari file .xlsx/.xls. Kolom diharapkan:
     * A: Nama Karyawan (wajib), B: NIP, C: Nama Bank, D: No. Rekening.
     * Baris 1 = header (dilewati). NIP duplikat dilewati.
     */
    public function import_excel() {
        if (!check_access('master_karyawan', 'add')) {
            show_error('Anda tidak memiliki izin untuk import data.', 403);
        }

        if (empty($_FILES['import_file']['name'])) {
            $this->session->set_flashdata('error', 'Pilih file Excel terlebih dahulu.');
            redirect('masterkaryawan');
            return;
        }

        $ext = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'])) {
            $this->session->set_flashdata('error', 'Format file harus .xlsx atau .xls.');
            redirect('masterkaryawan');
            return;
        }

        try {
            $spreadsheet = IOFactory::load($_FILES['import_file']['tmp_name']);
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
        } catch (\Exception $e) {
            $this->session->set_flashdata('error', 'Gagal membaca file: ' . $e->getMessage());
            redirect('masterkaryawan');
            return;
        }

        $uid       = $this->session->userdata('user_id');
        $to_insert = [];
        $seen_nip  = [];
        $skipped   = 0;
        $first     = true;

        foreach ($rows as $row) {
            // Lewati header (baris pertama).
            if ($first) { $first = false; continue; }

            $nama = isset($row[0]) ? trim((string)$row[0]) : '';
            $nip  = isset($row[1]) ? trim((string)$row[1]) : '';
            $bank = isset($row[2]) ? trim((string)$row[2]) : '';
            $rek  = isset($row[3]) ? trim((string)$row[3]) : '';

            // Lewati baris kosong.
            if ($nama === '' && $nip === '' && $bank === '' && $rek === '') continue;

            // Nama wajib; NIP duplikat (file / DB) dilewati.
            if ($nama === '') { $skipped++; continue; }
            if ($nip !== '' && (isset($seen_nip[$nip]) || $this->model->nip_exists($nip))) {
                $skipped++;
                continue;
            }
            if ($nip !== '') $seen_nip[$nip] = true;

            $to_insert[] = [
                'nama_karyawan' => $nama,
                'nip'           => $nip !== '' ? $nip : null,
                'bank_name'     => $bank !== '' ? $bank : null,
                'bank_account'  => $rek !== '' ? $rek : null,
                'status'        => 'active',
                'created_by'    => $uid,
            ];
        }

        $inserted = $this->model->insert_batch($to_insert);

        $msg = $inserted . ' karyawan berhasil diimport.';
        if ($skipped > 0) $msg .= ' ' . $skipped . ' baris dilewati (nama kosong / NIP duplikat).';

        if ($inserted > 0) {
            $this->session->set_flashdata('success', $msg);
        } else {
            $this->session->set_flashdata('error', 'Tidak ada data yang diimport. ' . ($skipped > 0 ? $skipped . ' baris dilewati.' : ''));
        }
        redirect('masterkaryawan');
    }

    // ============================================================
    // EXPORT EXCEL
    // ============================================================
    public function export_excel() {
        if (!check_access('master_karyawan', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        $search = $this->input->get('search', true);
        $status = $this->input->get('status', true);
        $list   = $this->model->get_for_export($search, $status);

        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Karyawan');

        $headers = ['No', 'Nama Karyawan', 'NIP', 'Nama Bank', 'No. Rekening', 'Status'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A1:F1')->getFont()->getColor()->setARGB('FFFFFFFF');

        $r = 2; $no = 1;
        foreach ($list as $k) {
            $sheet->fromArray([
                $no++,
                $k->nama_karyawan,
                $k->nip,
                $k->bank_name,
                $k->bank_account,
                strtoupper($k->status),
            ], null, 'A' . $r);
            // No. rekening sebagai teks supaya tidak jadi notasi ilmiah.
            $sheet->setCellValueExplicit('E' . $r, (string)$k->bank_account,
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $r++;
        }

        foreach (['A' => 5, 'B' => 30, 'C' => 18, 'D' => 20, 'E' => 22, 'F' => 12] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $filename = 'Master_Karyawan_' . date('Y-m-d_H-i-s') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        (new Xlsx($ss))->save('php://output');
        exit;
    }
}
