<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stock_alert extends MY_Controller {

    protected $module_name = 'Stock Alert';

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Material_model');
        $this->load->model('Inventory_model');
        $this->load->model('Semifinished_model');
        $this->load->model('Machine_model');
        $this->load->model('Stock_ledger_model');
        $this->load->helper('export'); // Load export helper
    }

    // ✅ INDEX - Tampilkan semua barang dengan status alert
    public function index() {
        if (!check_access('stock_alert', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $alerts = [];

        // === CEK MATERIAL ===
        $materials = $this->Material_model->get_all();
        foreach ($materials as $m) {
            $status = $this->Material_model->check_stock_status($m->id);
            if ($status['alert'] === true) {
                $alerts[] = [
                    'item_type' => 'material',
                    'item_id' => $m->id,
                    'kode_material' => $m->kode_material,
                    'nama_material' => $m->nama_material,
                    'satuan' => $m->satuan,
                    'current_stock' => $status['current_stock'],
                    'minimum_stock' => $status['minimum_stock'],
                    'status_message' => $status['message'],
                    'deficit' => $status['minimum_stock'] - $status['current_stock'],
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
        }

        // === CEK SEMIFINISHED ===
        $semifinished = $this->Semifinished_model->get_all();
        foreach ($semifinished as $s) {
            $status = $this->Semifinished_model->check_stock_status($s->id);
            if ($status['alert'] === true) {
                $alerts[] = [
                    'item_type' => 'semifinished',
                    'item_id' => $s->id,
                    'kode_barang' => $s->kode_barang,
                    'nama_barang' => $s->nama_barang,
                    'satuan' => $s->satuan,
                    'current_stock' => $status['current_stock'],
                    'minimum_stock' => $status['minimum_stock'],
                    'status_message' => $status['message'],
                    'deficit' => $status['minimum_stock'] - $status['current_stock'],
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
        }

        // === CEK INVENTORY ===
        $inventories = $this->Inventory_model->get_all();
        foreach ($inventories as $i) {
            $status = $this->Inventory_model->check_stock_status($i->id);
            if ($status['alert'] === true) {
                $alerts[] = [
                    'item_type' => 'inventory',
                    'item_id' => $i->id,
                    'kode_barang' => $i->kode_barang,
                    'nama_barang' => $i->nama_barang,
                    'satuan' => $i->satuan,
                    'current_stock' => $status['current_stock'],
                    'minimum_stock' => $status['minimum_stock'],
                    'status_message' => $status['message'],
                    'deficit' => $status['minimum_stock'] - $status['current_stock'],
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
        }

        // === CEK MACHINE ===
        $machines = $this->Machine_model->get_all();
        foreach ($machines as $m) {
            $status = $this->Machine_model->check_stock_status($m->id);
            if ($status['alert'] === true) {
                $alerts[] = [
                    'item_type' => 'machine',
                    'item_id' => $m->id,
                    'kode_mesin' => $m->kode_mesin,
                    'nama_mesin' => $m->nama_mesin,
                    'satuan' => $m->satuan,
                    'current_stock' => $status['current_stock'],
                    'minimum_stock' => $status['minimum_stock'],
                    'status_message' => $status['message'],
                    'deficit' => $status['minimum_stock'] - $status['current_stock'],
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
        }

        // Sort by deficit (paling kritis dulu)
        usort($alerts, function($a, $b) {
            return $b['deficit'] <=> $a['deficit'];
        });

        $data['title'] = 'Stock Alert - Barang Mencapai Minimum Stock';
        $data['alerts'] = $alerts;
        $data['total_alerts'] = count($alerts);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('stock_alert/index', $data);
        $this->load->view('templates/footer');
    }

    // ✅ AJAX - Refresh alert count (untuk widget di dashboard)
    public function get_alert_count() {
        $count = 0;

        // CEK MATERIAL
        $materials = $this->Material_model->get_all();
        foreach ($materials as $m) {
            $status = $this->Material_model->check_stock_status($m->id);
            if ($status['alert'] === true) $count++;
        }

        // CEK SEMIFINISHED
        $semifinished = $this->Semifinished_model->get_all();
        foreach ($semifinished as $s) {
            $status = $this->Semifinished_model->check_stock_status($s->id);
            if ($status['alert'] === true) $count++;
        }

        // CEK INVENTORY
        $inventories = $this->Inventory_model->get_all();
        foreach ($inventories as $i) {
            $status = $this->Inventory_model->check_stock_status($i->id);
            if ($status['alert'] === true) $count++;
        }

        // CEK MACHINE
        $machines = $this->Machine_model->get_all();
        foreach ($machines as $m) {
            $status = $this->Machine_model->check_stock_status($m->id);
            if ($status['alert'] === true) $count++;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['alert_count' => $count]));
    }

    // ✅ EXPORT EXCEL - Export semua alerts ke Excel
    public function export_excel() {
        if (!check_access('stock_alert', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        $alerts = $this->_get_all_alerts_formatted('excel');
        
        if (empty($alerts)) {
            $this->session->set_flashdata('message', '<div class="alert alert-info">Tidak ada data alert untuk di-export.</div>');
            redirect('stock_alert');
        }

        $headers = ['Tipe Item', 'Kode', 'Nama Barang', 'Satuan', 'Stok Saat Ini', 'Minimum Stok', 'Deficit'];
        $filename = 'Stock_Alert_' . date('Ymd_His');

        export_to_excel($filename, 'Stock Alert', $headers, $alerts);
    }

    // ✅ EXPORT PDF - Export semua alerts ke PDF
    public function export_pdf() {
        if (!check_access('stock_alert', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        $alerts = $this->_get_all_alerts_formatted('pdf');
        
        if (empty($alerts)) {
            $this->session->set_flashdata('message', '<div class="alert alert-info">Tidak ada data alert untuk di-export.</div>');
            redirect('stock_alert');
        }

        $headers = ['Tipe Item', 'Kode', 'Nama Barang', 'Satuan', 'Stok Saat Ini', 'Minimum Stok', 'Deficit'];
        $title = 'Laporan Stock Alert - Barang Mencapai Minimum Stock - ' . date('d-m-Y H:i:s');
        $filename = 'Stock_Alert_' . date('Ymd_His');

        export_to_pdf($filename, $title, $headers, $alerts);
    }

    // ✅ PRIVATE HELPER - Get all alerts dengan format standar
    private function _get_all_alerts_formatted($format = 'excel') {
        $alerts = [];
        $item_type_label = ($format === 'pdf') ? [
            'material' => 'Material',
            'semifinished' => 'Semifinished',
            'inventory' => 'Inventory',
            'machine' => 'Machine'
        ] : [
            'material' => 'material',
            'semifinished' => 'semifinished',
            'inventory' => 'inventory',
            'machine' => 'machine'
        ];

        // === CEK MATERIAL ===
        $materials = $this->Material_model->get_all();
        foreach ($materials as $m) {
            $status = $this->Material_model->check_stock_status($m->id);
            if ($status['alert'] === true) {
                $alerts[] = [
                    $item_type_label['material'],
                    $m->kode_material,
                    $m->nama_material,
                    $m->satuan,
                    $status['current_stock'],
                    $status['minimum_stock'],
                    $status['minimum_stock'] - $status['current_stock']
                ];
            }
        }

        // === CEK SEMIFINISHED ===
        $semifinished = $this->Semifinished_model->get_all();
        foreach ($semifinished as $s) {
            $status = $this->Semifinished_model->check_stock_status($s->id);
            if ($status['alert'] === true) {
                $alerts[] = [
                    $item_type_label['semifinished'],
                    $s->kode_barang,
                    $s->nama_barang,
                    $s->satuan,
                    $status['current_stock'],
                    $status['minimum_stock'],
                    $status['minimum_stock'] - $status['current_stock']
                ];
            }
        }

        // === CEK INVENTORY ===
        $inventories = $this->Inventory_model->get_all();
        foreach ($inventories as $i) {
            $status = $this->Inventory_model->check_stock_status($i->id);
            if ($status['alert'] === true) {
                $alerts[] = [
                    $item_type_label['inventory'],
                    $i->kode_barang,
                    $i->nama_barang,
                    $i->satuan,
                    $status['current_stock'],
                    $status['minimum_stock'],
                    $status['minimum_stock'] - $status['current_stock']
                ];
            }
        }

        // === CEK MACHINE ===
        $machines = $this->Machine_model->get_all();
        foreach ($machines as $m) {
            $status = $this->Machine_model->check_stock_status($m->id);
            if ($status['alert'] === true) {
                $alerts[] = [
                    $item_type_label['machine'],
                    $m->kode_mesin,
                    $m->nama_mesin,
                    $m->satuan,
                    $status['current_stock'],
                    $status['minimum_stock'],
                    $status['minimum_stock'] - $status['current_stock']
                ];
            }
        }

        // Sort by deficit (paling kritis dulu)
        usort($alerts, function($a, $b) {
            return $b[6] <=> $a[6];
        });

        return $alerts;
    }
}
