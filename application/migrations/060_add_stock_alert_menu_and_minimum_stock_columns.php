<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_stock_alert_menu_and_minimum_stock_columns extends CI_Migration {

    public function up() {
        // ===== ADD MINIMUM STOCK COLUMNS TO SEMIFINISHED & MACHINES =====
        
        // Check if column exists before adding to semifinished_goods
        if (!$this->db->field_exists('minimum_stock', 'semifinished_goods')) {
            $this->dbforge->add_column('semifinished_goods', array(
                'minimum_stock' => array(
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => 0,
                    'null' => false,
                    'comment' => 'Reorder Point (ROP) - Stock Minimal untuk Alert'
                )
            ));
        }

        // Check if column exists before adding to machines
        if (!$this->db->field_exists('minimum_stock', 'machines')) {
            $this->dbforge->add_column('machines', array(
                'minimum_stock' => array(
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => 0,
                    'null' => false,
                    'comment' => 'Reorder Point (ROP) - Stock Minimal untuk Alert'
                )
            ));
        }

        // ===== ADD STOCK_ALERT MENU =====
        
        // Get the highest order_no from menus table
        $last_menu = $this->db->select('order_no')->order_by('order_no', 'DESC')->limit(1)->get('menus')->row();
        $next_order = ($last_menu) ? $last_menu->order_no + 1 : 1;

        // Check if stock_alert menu already exists
        $existing = $this->db->get_where('menus', ['menu_name' => 'Stock Alert'])->num_rows();
        
        if ($existing == 0) {
            $menu_data = array(
                'menu_name' => 'Stock Alert',
                'menu_link' => 'stock_alert',
                'menu_icon' => 'fas fa-exclamation-triangle',
                'parent_id' => null, // Top-level menu (you can change this if needed)
                'order_no' => $next_order,
                'status' => 'active'
            );
            $this->db->insert('menus', $menu_data);
            
            // Get the inserted menu ID
            $menu_id = $this->db->insert_id();
            
            // Add default permissions for all roles
            $all_roles = $this->db->get('roles')->result();
            foreach ($all_roles as $role) {
                $perm_data = array(
                    'role_id' => $role->id,
                    'menu_id' => $menu_id,
                    'can_view' => 1,  // Allow view by default
                    'can_add' => 0,
                    'can_edit' => 0,
                    'can_delete' => 0
                );
                $this->db->insert('role_permissions', $perm_data);
            }
        }
    }

    public function down() {
        // Remove stock_alert menu and its permissions
        $stock_alert_menu = $this->db->get_where('menus', ['menu_name' => 'Stock Alert'])->row();
        
        if ($stock_alert_menu) {
            // Delete permissions associated with this menu
            $this->db->where('menu_id', $stock_alert_menu->id)->delete('role_permissions');
            
            // Delete the menu itself
            $this->db->where('id', $stock_alert_menu->id)->delete('menus');
        }

        // Drop minimum_stock column from semifinished_goods if exists
        if ($this->db->field_exists('minimum_stock', 'semifinished_goods')) {
            $this->dbforge->drop_column('semifinished_goods', 'minimum_stock');
        }

        // Drop minimum_stock column from machines if exists
        if ($this->db->field_exists('minimum_stock', 'machines')) {
            $this->dbforge->drop_column('machines', 'minimum_stock');
        }
    }
}
