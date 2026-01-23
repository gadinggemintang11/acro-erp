<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_supplier extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Supplier_model');
        // ✅ Load Commoncode_model untuk data dinamis
        $this->load->model('Commoncode_model');
        $this->load->helper(['form', 'url']);
        $this->load->library('pagination');
    }

    public function index() {
        // ✅ KEAMANAN: Check akses view
        if (!check_access('master_supplier', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        // ✅ GET: search, jenis, status, page dari query string
        $search = $this->input->get('search', true);
        $jenis = $this->input->get('jenis', true);
        $status = $this->input->get('status', true);
        $page = (int) $this->input->get('page', 0);

        // ✅ PAGINATION CONFIG
        $limit = 10;
        $offset = $page > 0 ? ($page - 1) * $limit : 0;

        // Count total records
        $total_rows = $this->Supplier_model->count_all($search, $jenis, $status);

        // Get paginated data
        $suppliers = $this->Supplier_model->get_paginated($search, $jenis, $status, $limit, $offset);

        // Pagination config - Sama seperti Master Material
        $config['base_url'] = site_url('master_supplier');
        $config['total_rows'] = $total_rows;
        $config['per_page'] = $limit;
        $config['page_query_string'] = TRUE;
        $config['query_string_segment'] = 'page';
        $config['reuse_query_string'] = TRUE;
        $config['use_page_numbers'] = TRUE;

        // Bootstrap styling - SAMA SEPERTI MASTER MATERIAL
        $config['full_tag_open'] = '<ul class="pagination justify-content-end">';
        $config['full_tag_close'] = '</ul>';
        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="page-item active"><a class="page-link" href="#">';
        $config['cur_tag_close'] = '</a></li>';
        $config['first_tag_open'] = '<li class="page-item">';
        $config['first_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li class="page-item">';
        $config['last_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li class="page-item">';
        $config['next_tag_close'] = '</li>';
        $config['prev_tag_open'] = '<li class="page-item">';
        $config['prev_tag_close'] = '</li>';
        $config['attributes'] = array('class' => 'page-link');

        $this->pagination->initialize($config);

        $data = [
            'title'     => 'Master Supplier',
            'suppliers' => $suppliers,
            'jenis_list' => $this->Commoncode_model->get_by_group('SUPPLIER_TYPE'),
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $total_rows,
            'search'     => $search,
            'jenis'      => $jenis,
            'status'     => $status,
            'limit'      => $limit,
            'offset'     => $offset
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('supplier/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        // ✅ KEAMANAN: Check akses add
        if (!check_access('master_supplier', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        $data = [
            'kode_supplier' => $this->Supplier_model->generate_kode($this->input->post('jenis')),
            'nama_supplier' => $this->input->post('nama_supplier'),
            'jenis'         => $this->input->post('jenis'),
            'npwp'          => $this->input->post('npwp'),
            'alamat'        => $this->input->post('alamat'),
            'telepon'       => $this->input->post('telepon'),
            'status'        => 'active',
        ];

        if ($this->Supplier_model->insert($data)) {
            $this->session->set_flashdata('success', 'Data supplier berhasil ditambahkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan data!');
        }
        redirect('master_supplier');
    }

    public function update($id) {
        // ✅ KEAMANAN: Check akses edit
        if (!check_access('master_supplier', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }

        $data = [
            'nama_supplier' => $this->input->post('nama_supplier'),
            'jenis'         => $this->input->post('jenis'),
            'npwp'          => $this->input->post('npwp'),
            'alamat'        => $this->input->post('alamat'),
            'telepon'       => $this->input->post('telepon'),
            'status'        => $this->input->post('status'),
        ];

        if ($this->Supplier_model->update($id, $data)) {
            $this->session->set_flashdata('success', 'Data supplier berhasil diperbarui!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui data!');
        }
        redirect('master_supplier');
    }

    public function delete($id) {
        // ✅ KEAMANAN: Check akses delete
        if (!check_access('master_supplier', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }

        if ($this->Supplier_model->delete($id)) {
            // ✅ DIUBAH: Pesan disesuaikan dengan soft delete
            $this->session->set_flashdata('success', 'Data supplier berhasil dinonaktifkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan data!');
        }
        redirect('master_supplier');
    }

    // ✅ BARU: Export Excel (TRUE .XLSX Format - Binary Excel File)
    public function export_excel() {
        if (!check_access('master_supplier', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        // Get filter dari query string
        $search = $this->input->get('search', true);
        $jenis = $this->input->get('jenis', true);
        $status = $this->input->get('status', true);

        // Get data untuk export
        $suppliers = $this->Supplier_model->get_for_export($search, $jenis, $status);

        // Filename
        $filename = 'Supplier_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        // Use application/cache folder (writable)
        $temp_dir = APPPATH . 'cache/xlsx_' . uniqid();
        
        // Create directories
        @mkdir($temp_dir, 0777, true);
        @mkdir($temp_dir . '/_rels', 0777, true);
        @mkdir($temp_dir . '/xl', 0777, true);
        @mkdir($temp_dir . '/xl/_rels', 0777, true);
        @mkdir($temp_dir . '/xl/worksheets', 0777, true);
        @mkdir($temp_dir . '/docProps', 0777, true);

        try {
            // 1. Generate worksheet data (Sheet1.xml)
            $this->_generate_xlsx_sheet($temp_dir, $suppliers);

            // 2. Generate workbook.xml
            $this->_generate_xlsx_workbook($temp_dir);

            // 3. Generate workbook.xml.rels
            $this->_generate_xlsx_workbook_rels($temp_dir);

            // 4. Generate .rels
            $this->_generate_xlsx_rels($temp_dir);

            // 5. Generate [Content_Types].xml
            $this->_generate_xlsx_content_types($temp_dir);

            // 6. Generate app.xml
            $this->_generate_xlsx_app_xml($temp_dir, count($suppliers));

            // 7. Generate core.xml
            $this->_generate_xlsx_core_xml($temp_dir);

            // 8. Create ZIP file
            $zip_file = $temp_dir . '/output.zip';
            $this->_create_xlsx_zip($temp_dir, $zip_file);

            // 9. Send file to browser
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($zip_file));
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: no-cache');
            readfile($zip_file);

            // 10. Clean up
            $this->_remove_directory($temp_dir);
            exit;
        } catch (Exception $e) {
            $this->_remove_directory($temp_dir);
            show_error('Gagal generate Excel: ' . $e->getMessage(), 500);
        }
    }

    // Helper: Generate worksheet XML
    private function _generate_xlsx_sheet($temp_dir, $suppliers) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n";
        $xml .= '<sheetData>' . "\n";

        // Header row
        $xml .= '<row r="1">' . "\n";
        $headers = ['Kode Supplier', 'Nama Supplier', 'Jenis', 'NPWP', 'Alamat', 'Telepon', 'Status'];
        foreach ($headers as $col => $header) {
            $col_letter = $this->_get_excel_column_letter($col + 1);
            $xml .= '<c r="' . $col_letter . '1" t="inlineStr"><is><t>' . htmlspecialchars($header) . '</t></is></c>' . "\n";
        }
        $xml .= '</row>' . "\n";

        // Data rows
        $row_num = 2;
        foreach ($suppliers as $supplier) {
            $xml .= '<row r="' . $row_num . '">' . "\n";
            $data = [
                $supplier->kode_supplier,
                $supplier->nama_supplier,
                $supplier->jenis,
                $supplier->npwp,
                $supplier->alamat,
                $supplier->telepon,
                strtoupper($supplier->status)
            ];
            foreach ($data as $col => $value) {
                $col_letter = $this->_get_excel_column_letter($col + 1);
                $xml .= '<c r="' . $col_letter . $row_num . '" t="inlineStr"><is><t>' . htmlspecialchars($value) . '</t></is></c>' . "\n";
            }
            $xml .= '</row>' . "\n";
            $row_num++;
        }

        $xml .= '</sheetData>' . "\n";
        $xml .= '</worksheet>' . "\n";

        file_put_contents($temp_dir . '/xl/worksheets/sheet1.xml', $xml);
    }

    // Helper: Generate workbook.xml
    private function _generate_xlsx_workbook($temp_dir) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n";
        $xml .= '<sheets>' . "\n";
        $xml .= '<sheet name="Supplier" sheetId="1" r:id="rId1"/>' . "\n";
        $xml .= '</sheets>' . "\n";
        $xml .= '</workbook>' . "\n";

        file_put_contents($temp_dir . '/xl/workbook.xml', $xml);
    }

    // Helper: Generate workbook.xml.rels
    private function _generate_xlsx_workbook_rels($temp_dir) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
        $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' . "\n";
        $xml .= '</Relationships>' . "\n";

        file_put_contents($temp_dir . '/xl/_rels/workbook.xml.rels', $xml);
    }

    // Helper: Generate .rels
    private function _generate_xlsx_rels($temp_dir) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
        $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' . "\n";
        $xml .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>' . "\n";
        $xml .= '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>' . "\n";
        $xml .= '</Relationships>' . "\n";

        file_put_contents($temp_dir . '/_rels/.rels', $xml);
    }

    // Helper: Generate [Content_Types].xml
    private function _generate_xlsx_content_types($temp_dir) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' . "\n";
        $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' . "\n";
        $xml .= '<Default Extension="xml" ContentType="application/xml"/>' . "\n";
        $xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' . "\n";
        $xml .= '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' . "\n";
        $xml .= '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>' . "\n";
        $xml .= '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.custom-properties+xml"/>' . "\n";
        $xml .= '</Types>' . "\n";

        file_put_contents($temp_dir . '/[Content_Types].xml', $xml);
    }

    // Helper: Generate app.xml
    private function _generate_xlsx_app_xml($temp_dir, $sheet_count) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/custom-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">' . "\n";
        $xml .= '<AppVersion>12.0000</AppVersion>' . "\n";
        $xml .= '<Application>Mingbao ERP</Application>' . "\n";
        $xml .= '<DocSecurity>0</DocSecurity>' . "\n";
        $xml .= '<ScaleCrop>false</ScaleCrop>' . "\n";
        $xml .= '<HeadingPairs>' . "\n";
        $xml .= '<vt:vector baseType="variant" size="2">' . "\n";
        $xml .= '<vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant>' . "\n";
        $xml .= '<vt:variant><vt:i4>' . $sheet_count . '</vt:i4></vt:variant>' . "\n";
        $xml .= '</vt:vector>' . "\n";
        $xml .= '</HeadingPairs>' . "\n";
        $xml .= '<TitlesOfParts>' . "\n";
        $xml .= '<vt:vector baseType="lpstr" size="' . $sheet_count . '">' . "\n";
        $xml .= '<vt:lpstr>Supplier</vt:lpstr>' . "\n";
        $xml .= '</vt:vector>' . "\n";
        $xml .= '</TitlesOfParts>' . "\n";
        $xml .= '</Properties>' . "\n";

        file_put_contents($temp_dir . '/docProps/app.xml', $xml);
    }

    // Helper: Generate core.xml
    private function _generate_xlsx_core_xml($temp_dir) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' . "\n";
        $xml .= '<dc:creator>Mingbao ERP</dc:creator>' . "\n";
        $xml .= '<cp:lastModifiedBy>Mingbao ERP</cp:lastModifiedBy>' . "\n";
        $xml .= '<dcterms:created xsi:type="dcterms:W3CDTF">' . date('Y-m-d\TH:i:s\Z') . '</dcterms:created>' . "\n";
        $xml .= '<dcterms:modified xsi:type="dcterms:W3CDTF">' . date('Y-m-d\TH:i:s\Z') . '</dcterms:modified>' . "\n";
        $xml .= '</cp:coreProperties>' . "\n";

        file_put_contents($temp_dir . '/docProps/core.xml', $xml);
    }

    // Helper: Create XLSX ZIP file
    private function _create_xlsx_zip($source_dir, $output_file) {
        $zip = new ZipArchive();
        if ($zip->open($output_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            // Add [Content_Types].xml
            $zip->addFile($source_dir . '/[Content_Types].xml', '[Content_Types].xml');

            // Add _rels/.rels
            $zip->addFile($source_dir . '/_rels/.rels', '_rels/.rels');

            // Add xl/workbook.xml
            $zip->addFile($source_dir . '/xl/workbook.xml', 'xl/workbook.xml');

            // Add xl/worksheets/sheet1.xml
            $zip->addFile($source_dir . '/xl/worksheets/sheet1.xml', 'xl/worksheets/sheet1.xml');

            // Add xl/_rels/workbook.xml.rels
            $zip->addFile($source_dir . '/xl/_rels/workbook.xml.rels', 'xl/_rels/workbook.xml.rels');

            // Add docProps/app.xml
            $zip->addFile($source_dir . '/docProps/app.xml', 'docProps/app.xml');

            // Add docProps/core.xml
            $zip->addFile($source_dir . '/docProps/core.xml', 'docProps/core.xml');

            $zip->close();
            return true;
        }
        return false;
    }

    // Helper: Convert number to Excel column letter (1=A, 26=Z, 27=AA, etc)
    private function _get_excel_column_letter($column_number) {
        $dividend = $column_number;
        $column_letter = '';
        while ($dividend > 0) {
            $modulo = ($dividend - 1) % 26;
            $column_letter = chr(65 + $modulo) . $column_letter;
            $dividend = (int)(($dividend - $modulo) / 26);
        }
        return $column_letter;
    }

    // Helper: Hapus directory dan isinya
    private function _remove_directory($dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    $path = $dir . "/" . $file;
                    if (is_dir($path)) {
                        $this->_remove_directory($path);
                    } else {
                        @unlink($path);
                    }
                }
            }
            @rmdir($dir);
        }
    }

    // ✅ BARU: Export PDF (Simple HTML Printable)
    public function export_pdf() {
        if (!check_access('master_supplier', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        // Get filter dari query string
        $search = $this->input->get('search', true);
        $jenis = $this->input->get('jenis', true);
        $status = $this->input->get('status', true);

        // Get data untuk export
        $suppliers = $this->Supplier_model->get_for_export($search, $jenis, $status);

        // Generate PDF filename
        $filename = 'Supplier_' . date('Y-m-d_H-i-s') . '.pdf';
        
        // HTML untuk printing/PDF
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Supplier - ' . date('d-m-Y') . '</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 5px;
            font-size: 24px;
        }
        .meta-info {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #4472C4;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            font-size: 13px;
            border: 1px solid #333;
        }
        td {
            padding: 10px 12px;
            border: 1px solid #ddd;
            font-size: 12px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f0f0f0;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .button-area {
            text-align: center;
            margin: 20px 0;
        }
        button {
            background-color: #4472C4;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        button:hover {
            background-color: #2e5090;
        }
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .container {
                box-shadow: none;
                padding: 0;
            }
            .button-area {
                display: none;
            }
            .footer {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 DAFTAR SUPPLIER</h1>
        <div class="meta-info">
            Tanggal: ' . date('d-m-Y H:i:s') . ' | Total: ' . count($suppliers) . ' Supplier
        </div>
        
        <div class="button-area">
            <button onclick="window.print()">🖨️ Print / Save as PDF</button>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 12%;">Kode</th>
                    <th style="width: 20%;">Nama Supplier</th>
                    <th style="width: 10%;">Jenis</th>
                    <th style="width: 15%;">NPWP</th>
                    <th style="width: 20%;">Alamat</th>
                    <th style="width: 10%;">Telepon</th>
                    <th style="width: 8%;">Status</th>
                </tr>
            </thead>
            <tbody>';
        
        $no = 1;
        foreach ($suppliers as $supplier) {
            $html .= '<tr>
                <td>' . $no . '</td>
                <td><strong>' . htmlspecialchars($supplier->kode_supplier) . '</strong></td>
                <td>' . htmlspecialchars($supplier->nama_supplier) . '</td>
                <td>' . htmlspecialchars($supplier->jenis) . '</td>
                <td>' . htmlspecialchars($supplier->npwp) . '</td>
                <td>' . htmlspecialchars($supplier->alamat) . '</td>
                <td>' . htmlspecialchars($supplier->telepon) . '</td>
                <td><span style="background-color: ' . (strtoupper($supplier->status) == 'ACTIVE' ? '#28a745' : '#dc3545') . '; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">' . strtoupper($supplier->status) . '</span></td>
            </tr>';
            $no++;
        }
        
        $html .= '</tbody>
        </table>
        
        <div class="footer">
            <p>Dokumen ini dihasilkan oleh Sistem ERP Mingbao pada ' . date('d-m-Y H:i:s') . '</p>
            <p>Tekan Ctrl+P (atau Cmd+P) untuk print dan save sebagai PDF</p>
        </div>
    </div>
</body>
</html>';

        // Output
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
}