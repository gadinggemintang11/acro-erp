<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_stock_separated_menus extends CI_Migration {

    public function up() {
        // Get the highest order_no from menus table
        $last_menu = $this->db->select('order_no')->order_by('order_no', 'DESC')->limit(1)->get('menus')->row();
        $next_order = ($last_menu) ? $last_menu->order_no + 1 : 1;

        // Define the three new stock menus
        $new_menus = [
            [
                'menu_name' => 'Stock Material & Semifinished',
                'menu_key' => 'stock_material',
                'order_no' => $next_order
            ],
            [
                'menu_name' => 'Stock Mesin',
                'menu_key' => 'stock_machine',
                'order_no' => $next_order + 1
            ],
            [
                'menu_name' => 'Stock Inventaris',
                'menu_key' => 'stock_inventory',
                'order_no' => $next_order + 2
            ]
        ];

        // Insert menus and their permissions
        foreach ($new_menus as $menu_data) {
            // Check if menu already exists
            $existing = $this->db->get_where('menus', ['menu_key' => $menu_data['menu_key']])->num_rows();

            if ($existing == 0) {
                // Insert menu
                $this->db->insert('menus', $menu_data);
                $menu_id = $this->db->insert_id();

                // Add permissions for all roles
                $all_roles = $this->db->get('roles')->result();
                foreach ($all_roles as $role) {
                    // For admin role, grant all permissions
                    $can_view = 1;
                    $can_add = ($role->role_name === 'Admin') ? 1 : 0;
                    $can_edit = ($role->role_name === 'Admin') ? 1 : 0;
                    $can_delete = ($role->role_name === 'Admin') ? 1 : 0;

                    // For stock_ledger related modules, default view permission
                    $perm_data = array(
                        'role_id' => $role->id,
                        'menu_id' => $menu_id,
                        'can_view' => $can_view,
                        'can_add' => $can_add,
                        'can_edit' => $can_edit,
                        'can_delete' => $can_delete
                    );
                    $this->db->insert('role_permissions', $perm_data);
                }
            }
        }
    }

    public function down() {
        // Remove the three new menus and their permissions
        $menu_keys = ['stock_material', 'stock_machine', 'stock_inventory'];

        foreach ($menu_keys as $key) {
            $menu = $this->db->get_where('menus', ['menu_key' => $key])->row();

            if ($menu) {
                // Delete permissions associated with this menu
                $this->db->where('menu_id', $menu->id)->delete('role_permissions');

                // Delete the menu itself
                $this->db->where('id', $menu->id)->delete('menus');
            }
        }
    }
}
?>
