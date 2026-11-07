<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_department_to_menus extends CI_Migration {

    public function up() {
        // Add department column to menus table if not exists
        if (!$this->db->field_exists('department', 'menus')) {
            $fields = array(
                'department' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '50',
                    'null' => TRUE,
                    'after' => 'menu_name'
                )
            );
            $this->dbforge->add_column('menus', $fields);
        }

        // PPIC Department
        $this->db->where_in('menu_key', [
            'material_request', 'work_order', 'production_process', 'production_receive',
            'bom', 'master_semifinished', 'menu_master_semifinished', 'prodreturn',
            'lapmutasiwip', 'lapmutasimaterial'
        ]);
        $this->db->update('menus', ['department' => 'PPIC']);

        // WAREHOUSE Department
        $this->db->where_in('menu_key', [
            'warehouse_in', 'warehouse_out', 'stock_ledger', 'stock_material',
            'stock_machine', 'stock_inventory', 'adjustment', 'stockopname',
            'stock_alert', 'material_issuance', 'consumable_request', 'consumable_out',
            'quality_assurance'
        ]);
        $this->db->update('menus', ['department' => 'WAREHOUSE']);

        // PURCHASING Department
        $this->db->where_in('menu_key', [
            'purchase_request', 'purchase_request_approval', 'purchase_order',
            'purchase_order_approval', 'mir', 'master_supplier', 'po_report'
        ]);
        $this->db->update('menus', ['department' => 'PURCHASING']);

        // FINISHED GOODS Department
        $this->db->where_in('menu_key', [
            'fg_receipt', 'fg_stock', 'fg_delivery', 'master_fg', 'lapmutasifinished'
        ]);
        $this->db->update('menus', ['department' => 'FINISHED GOODS']);

        // SCRAP Department
        $this->db->where_in('menu_key', [
            'scrap_in', 'scrap_out', 'scrap_stock', 'master_scrap', 'lap_mutasi_scrap'
        ]);
        $this->db->update('menus', ['department' => 'SCRAP']);

        // SUBKON Department
        $this->db->where_in('menu_key', [
            'subkon_order', 'subkon_in', 'subkon_out'
        ]);
        $this->db->update('menus', ['department' => 'SUBKON']);

        // ACCOUNTING Department
        $this->db->where_in('menu_key', [
            'apinvoice', 'appayment', 'arinvoice', 'arreceipt', 'jurnalumum',
            'coa', 'settingakun', 'neraca', 'trial_balance', 'laba_rugi',
            'cash_flow', 'cashbank'
        ]);
        $this->db->update('menus', ['department' => 'ACCOUNTING']);

        // REPORTING Department
        $this->db->where_in('menu_key', [
            'exim_verification', 'lap_pemasukan_bb', 'lap_pengeluaran_barang', 'lapmutasimesin'
        ]);
        $this->db->update('menus', ['department' => 'REPORTING']);

        // MASTER DATA Department
        $this->db->where_in('menu_key', [
            'master_material', 'master_customer', 'master_machine', 'master_inventory', 'master_scrap'
        ]);
        $this->db->update('menus', ['department' => 'MASTER DATA']);

        // SYSTEM Department
        $this->db->where_in('menu_key', [
            'commoncode', 'roleaccess', 'user', 'log_activity', 'sales_contract'
        ]);
        $this->db->update('menus', ['department' => 'SYSTEM']);

        // Set remaining NULL values to 'LAINNYA'
        $this->db->where('department IS NULL', NULL, FALSE);
        $this->db->update('menus', ['department' => 'LAINNYA']);
    }

    public function down() {
        $this->dbforge->drop_column('menus', 'department');
    }
}
?>
