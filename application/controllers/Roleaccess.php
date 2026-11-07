<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Roleaccess extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        check_login(); // middleware login
        $this->load->model('Role_model');
        $this->load->model('Commoncode_model');
    }

    /**
     * Tolak perubahan selagi Role Access dikunci.
     *
     * Tombolnya memang sudah disembunyikan di halaman utama, tapi URL-nya
     * tetap bisa diketik langsung — jadi gerbangnya diulang di sini.
     */
    private function _deny_if_locked()
    {
        if ($this->Role_model->is_locked()) {
            $this->session->set_flashdata('error', lang_line('ra_locked_denied',
                'Role Access sedang dikunci. Minta Admin membuka kuncinya dulu untuk menambah, mengubah, atau menghapus role.'));
            redirect('roleaccess'); // redirect() CI3 sudah exit
        }
    }

    /**
     * Kunci / buka kunci SELURUH Role Access — khusus Admin.
     * Satu tombol untuk semua role (user 2026-09-17: "saya mau button lock
     * ini 1 aja dan meng lock semua").
     */
    public function toggle_lock()
    {
        if (!is_admin()) {
            show_error('Hanya Admin yang bisa mengunci atau membuka kunci Role Access.', 403);
        }
        if ($this->Role_model->lock_info() === null) {
            $this->session->set_flashdata('error',
                'Fitur kunci belum aktif: jalankan migrasi migration_role_lock.sql dulu.');
            redirect('roleaccess');
            return;
        }
        $lock = !$this->Role_model->is_locked();
        $this->Role_model->set_lock($lock, $this->session->userdata('user_id'));
        add_log('UPDATE', 'Role Access', $lock ? 'Mengunci semua role' : 'Membuka kunci semua role');
        $this->session->set_flashdata('success', $lock ? 'Semua role dikunci.' : 'Kunci Role Access dibuka.');
        redirect('roleaccess');
    }

    public function index()
    {
        if (!check_access('roleaccess', 'view')) {
            show_error('Anda tidak memiliki izin untuk mengakses modul ini.', 403);
        }
        $data['title'] = lang_line('title_ra_index');
        $data['roles'] = $this->Role_model->get_all_roles();
        $data['departments'] = $this->Commoncode_model->get_by_group('Department');
        // Status kunci: menentukan tombol Tambah/Kelola/Edit/Hapus tampil atau tidak.
        $data['lock_info'] = $this->Role_model->lock_info();
        $data['is_locked'] = $this->Role_model->is_locked();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('roleaccess/index', $data);
        $this->load->view('templates/footer');
    }

    public function manage_permission($role_id)
    {
        $this->_deny_if_locked();
        $data['title'] = lang_line('title_ra_manage');
        $data['role'] = $this->Role_model->get_role_by_id($role_id);
        $data['permissions'] = $this->Role_model->get_permissions_by_role($role_id);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('roleaccess/manage', $data);
        $this->load->view('templates/footer');
    }

    public function save_permission($role_id)
    {
        $this->_deny_if_locked();
        $permissions = $this->input->post('perm');
        $this->Role_model->save_permissions($role_id, $permissions);

        $this->session->set_flashdata('success', 'Hak akses role berhasil diperbarui.');
        redirect('roleaccess/manage_permission/'.$role_id);
    }

    public function add()
    {
        $this->_deny_if_locked();
        if (!check_access('roleaccess', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah role.', 403);
        }
        $data = [
            'role_name' => $this->input->post('role_name'),
            'description' => $this->input->post('description'),
            'department' => $this->input->post('department'),
            'status' => 'active'
        ];
        $this->Role_model->insert_role($data);
        $this->session->set_flashdata('success', 'Role berhasil ditambahkan.');
        redirect('roleaccess');
    }

    public function update($id)
    {
        $this->_deny_if_locked();
        if (!check_access('roleaccess', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit role.', 403);
        }
        $data = [
            'role_name' => $this->input->post('role_name'),
            'description' => $this->input->post('description'),
            'department' => $this->input->post('department'),
            'status' => $this->input->post('status')
        ];
        $this->Role_model->update_role($id, $data);
        $this->session->set_flashdata('success', 'Role berhasil diperbarui.');
        redirect('roleaccess');
    }

    public function delete($id)
    {
        $this->_deny_if_locked();
        if (!check_access('roleaccess', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus role.', 403);
        }
        $this->Role_model->delete_role($id);
        $this->session->set_flashdata('success', 'Role berhasil dihapus.');
        redirect('roleaccess');
    }

    public function export_excel()
    {
        if (!check_access('roleaccess', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        require_once FCPATH . 'vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $spreadsheet->getProperties()
            ->setCreator(company_info('app_name'))
            ->setTitle("Data Role Access")
            ->setSubject("Export Role Access Management")
            ->setDescription("Data role dan akses pengguna sistem");

        // Header styling
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1d3557']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ];

        // Data styling
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
            'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
        ];

        // Set headers
        $headers = ['No', 'Role Name', 'Description', 'Department', 'Status'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->applyFromArray($headerStyle);
            $col++;
        }

        // Get data
        $roles = $this->Role_model->get_all_roles();
        $row = 2;
        $no = 1;

        foreach ($roles as $role) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $role->role_name);
            $sheet->setCellValue('C' . $row, $role->description ?: '-');
            $sheet->setCellValue('D' . $row, $role->department ?: '-');
            $sheet->setCellValue('E' . $row, ucfirst($role->status));

            $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($dataStyle);
            $row++;
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(12);

        // Freeze first row
        $sheet->freezePane('A2');

        // Generate file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Role_Access_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Struktur sidebar — sumber kebenaran urutan & grouping
     * di halaman manage_permission dan export_permission.
     * Mirror persis dari application/views/templates/sidebar.php.
     */
    private function _get_sidebar_structure()
    {
        return [
            // ============ CORE OPERATIONS ============
            ['section' => 'Core Operations', 'group' => 'Sales Order Number', 'subs' => [
                [null, ['sales_contract', 'sales_contract_approval', 'sales_contract_list']],
            ]],
            ['section' => 'Core Operations', 'group' => 'Permintaan Barang', 'subs' => [
                ['PRODUCTION MATERIAL', ['material_request', 'material_request_approval', 'material_outstanding']],
                ['GENERAL MATERIAL',    ['consumable_request', 'consumable_request_approval', 'irf_status', 'consumable_outstanding']],
            ]],
            ['section' => 'Core Operations', 'group' => 'Production', 'subs' => [
                [null,     ['work_order', 'production_process', 'production_qc', 'bom', 'production_receive']],
                ['REPORT', ['qc_production']],
            ]],
            ['section' => 'Core Operations', 'group' => 'Warehouse', 'subs' => [
                ['PRO-WAREHOUSE',     ['warehouse_in', 'warehouse_out', 'material_issuance', 'prodreturn', 'purchase_request', 'purchase_request_approval', 'stock_material']],
                ['GENERAL WAREHOUSE', ['supplier_general_item_receipt', 'consumable_out', 'purchase_request_general', 'purchase_request_approval_general', 'purchase_request_jasa', 'purchase_request_approval_jasa', 'stock_inventory', 'stock_machine']],
                ['OTHER',             ['initial_stock', 'stock_ledger', 'material_traceability', 'inventory_traceability', 'stock_alert']],
            ]],
            ['section' => 'Core Operations', 'group' => 'Purchasing', 'subs' => [
                ['PRODUCTION MATERIAL', ['purchase_order', 'purchase_order_approval', 'pmpo_status']],
                ['GENERAL MATERIAL',    ['purchase_order_general', 'purchase_order_approval_general', 'gmpo_status']],
                ['JASA',                ['purchase_order_jasa', 'purchase_order_approval_jasa']],
                ['IMPORT',              ['mir']],
                ['REPORT',              ['pmpo_list', 'gmpo_list', 'po_report']],
            ]],
            ['section' => 'Core Operations', 'group' => 'Quality Control & Assurances', 'subs' => [
                [null,     ['quality_assurance', 'qa_sampling']],
                ['REPORT', ['qa_report', 'qa_sampling_report']],
            ]],
            ['section' => 'Core Operations', 'group' => 'Finished Goods', 'subs' => [
                [null, ['fg_receipt', 'fg_receipt_approval', 'fg_stock', 'fg_delivery']],
            ]],
            ['section' => 'Core Operations', 'group' => 'Shipping & Transportation', 'subs' => [
                [null, ['exim_verification']],
            ]],
            ['section' => 'Core Operations', 'group' => 'Scrap', 'subs' => [
                [null,     ['scrap_in', 'scrap_out']],
                ['REPORT', ['scrap_stock']],
            ]],
            ['section' => 'Core Operations', 'group' => 'Subkon', 'subs' => [
                [null, ['subkon_order', 'subkon_out', 'subkon_in']],
            ]],
            ['section' => 'Core Operations', 'group' => 'IT Inventory [BC]', 'subs' => [
                [null,              ['lap_pemasukan_bb', 'lap_pengeluaran_barang']],
                ['MUTATION REPORT', ['lap_mutasi_scrap', 'lapmutasimaterial', 'lapmutasifinished', 'lapmutasiwip', 'lapmutasimesin']],
            ]],

            // ============ ACCOUNTING & FINANCE ============
            ['section' => 'Accounting & Finance', 'group' => 'Finance & Accounting', 'subs' => [
                ['FINANCE',           ['prf', 'prf_approval', 'erf', 'erf_approval']],
                ['FINANCE REPORT',    ['prf_list', 'erf_list']],
                ['ACCOUNTING',        ['jurnalumum', 'summary_ledger', 'voucher_disbursement_list', 'apinvoice', 'appayment', 'hutang', 'arinvoice', 'arreceipt', 'piutang', 'cashbank']],
                ['STOCK OPNAME',      ['stockopname', 'adjustment']],
                ['ACCOUNTING REPORT', ['laba_rugi', 'neraca', 'trial_balance', 'cash_flow']],
                ['SETTING',           ['coa', 'settingakun', 'kurs_pajak']],
            ]],
            
            // ============ CONFIGURATION ============
            ['section' => 'Configuration', 'group' => 'Master Database', 'subs' => [
                [null, ['master_supplier', 'master_customer', 'master_material', 'master_semifinished', 'master_fg', 'master_scrap', 'master_inventory', 'master_machine', 'master_karyawan', 'master_jasa']],
            ]],
            ['section' => 'Configuration', 'group' => 'System Setup', 'subs' => [
                [null, ['commoncode', 'roleaccess', 'user', 'log_activity']],
            ]],
        ];
    }

    public function export_permission($role_id)
    {
        if (!check_access('roleaccess', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        require_once FCPATH . 'vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Get role and permissions
        $role = $this->Role_model->get_role_by_id($role_id);
        $permissions = $this->Role_model->get_permissions_by_role($role_id);

        $spreadsheet->getProperties()
            ->setCreator(company_info('app_name'))
            ->setTitle("Role Permission - " . $role->role_name)
            ->setSubject("Export Role Permissions")
            ->setDescription("Data hak akses untuk role " . $role->role_name);

        // Title styling
        $titleStyle = [
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1d3557']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
        ];

        // Column header (View/Add/Edit/...)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1d3557']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ];

        // Section heading (Core Operations / Accounting & Finance / Configuration) — merah
        $sectionStyle = [
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C62828']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ];

        // Group header (top-level sidebar menu) — biru tua
        $groupStyle = [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1d3557']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ];

        // Sub-header (PRO-WAREHOUSE, FINANCE, dst) — abu-abu
        $subStyle = [
            'font' => ['bold' => true, 'italic' => true, 'size' => 10, 'color' => ['rgb' => '444444']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBEDF0']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'indent' => 1],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ];

        // Data row
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
            'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
        ];

        // Set title
        $sheet->setCellValue('A1', 'Role Permissions - ' . $role->role_name);
        $sheet->getStyle('A1')->applyFromArray($titleStyle);
        $sheet->getStyle('A1')->getFont()->setSize(14);

        // Column headers
        $headers = ['No', 'Nama Modul', 'View', 'Add', 'Edit', 'Confirm', 'Head of Dept', 'Endorsement', 'Approval', 'Delete'];
        $col = 'A';
        $row = 3;
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray($headerStyle);
            $col++;
        }

        // Build lookup map: menu_key (lowercase) => permission object
        $by_key = [];
        $used   = [];
        foreach ($permissions as $p) {
            $by_key[strtolower($p->menu_key)] = $p;
        }

        $row = 4;
        $prev_section = null;

        foreach ($this->_get_sidebar_structure() as $group_def) {
            // Skip group jika semua menu_key-nya tidak ada di DB
            $group_has_rows = false;
            foreach ($group_def['subs'] as $sub) {
                foreach ($sub[1] as $mk) {
                    if (isset($by_key[strtolower($mk)])) { $group_has_rows = true; break 2; }
                }
            }
            if (!$group_has_rows) continue;

            // Section heading
            if ($group_def['section'] !== $prev_section) {
                $prev_section = $group_def['section'];
                $sheet->setCellValue('A' . $row, strtoupper($group_def['section']));
                $sheet->mergeCells('A' . $row . ':J' . $row);
                $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($sectionStyle);
                $sheet->getRowDimension($row)->setRowHeight(22);
                $row++;
            }

            // Group header (top-level)
            $sheet->setCellValue('A' . $row, $group_def['group']);
            $sheet->mergeCells('A' . $row . ':J' . $row);
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($groupStyle);
            $row++;

            $no = 1;
            foreach ($group_def['subs'] as $sub) {
                list($sub_header, $menu_keys) = $sub;

                // Kumpulkan row yg benar-benar ada di DB
                $rows = [];
                foreach ($menu_keys as $mk) {
                    $lk = strtolower($mk);
                    if (isset($by_key[$lk])) {
                        $rows[] = $by_key[$lk];
                        $used[$lk] = true;
                    }
                }
                if (empty($rows)) continue;

                if ($sub_header) {
                    $sheet->setCellValue('A' . $row, $sub_header);
                    $sheet->mergeCells('A' . $row . ':J' . $row);
                    $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($subStyle);
                    $row++;
                }

                foreach ($rows as $perm) {
                    $sheet->setCellValue('A' . $row, $no++);
                    $sheet->setCellValue('B' . $row, $perm->menu_name);
                    $sheet->setCellValue('C' . $row, $perm->can_view ? '✓' : '');
                    $sheet->setCellValue('D' . $row, $perm->can_add ? '✓' : '');
                    $sheet->setCellValue('E' . $row, $perm->can_edit ? '✓' : '');
                    $sheet->setCellValue('F' . $row, $perm->can_confirm ? '✓' : '');
                    $sheet->setCellValue('G' . $row, !empty($perm->can_head_dept) ? '✓' : '');
                    $sheet->setCellValue('H' . $row, !empty($perm->can_endorsement) ? '✓' : '');
                    $sheet->setCellValue('I' . $row, !empty($perm->can_approval) ? '✓' : '');
                    $sheet->setCellValue('J' . $row, $perm->can_delete ? '✓' : '');

                    for ($c = 'C'; $c <= 'J'; $c++) {
                        $sheet->getStyle($c . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    }
                    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($dataStyle);
                    $row++;
                }
            }
        }

        // Fallback: menu DB yang tidak tercakup struktur sidebar → "OTHER / Uncategorized"
        $leftover = [];
        foreach ($by_key as $lk => $perm) {
            if (!isset($used[$lk])) $leftover[] = $perm;
        }
        if (!empty($leftover)) {
            $sheet->setCellValue('A' . $row, 'OTHER');
            $sheet->mergeCells('A' . $row . ':J' . $row);
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($sectionStyle);
            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;

            $sheet->setCellValue('A' . $row, 'Uncategorized');
            $sheet->mergeCells('A' . $row . ':J' . $row);
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($groupStyle);
            $row++;

            $no = 1;
            foreach ($leftover as $perm) {
                $sheet->setCellValue('A' . $row, $no++);
                $sheet->setCellValue('B' . $row, $perm->menu_name . ' (' . $perm->menu_key . ')');
                $sheet->setCellValue('C' . $row, $perm->can_view ? '✓' : '');
                $sheet->setCellValue('D' . $row, $perm->can_add ? '✓' : '');
                $sheet->setCellValue('E' . $row, $perm->can_edit ? '✓' : '');
                $sheet->setCellValue('F' . $row, $perm->can_confirm ? '✓' : '');
                $sheet->setCellValue('G' . $row, !empty($perm->can_head_dept) ? '✓' : '');
                $sheet->setCellValue('H' . $row, !empty($perm->can_endorsement) ? '✓' : '');
                $sheet->setCellValue('I' . $row, !empty($perm->can_approval) ? '✓' : '');
                $sheet->setCellValue('J' . $row, $perm->can_delete ? '✓' : '');

                for ($c = 'C'; $c <= 'J'; $c++) {
                    $sheet->getStyle($c . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($dataStyle);
                $row++;
            }
        }

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(45);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(14);
        $sheet->getColumnDimension('I')->setWidth(12);
        $sheet->getColumnDimension('J')->setWidth(10);

        // Freeze header row
        $sheet->freezePane('A4');

        // Generate file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Role_Permission_' . str_replace(' ', '_', $role->role_name) . '_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
