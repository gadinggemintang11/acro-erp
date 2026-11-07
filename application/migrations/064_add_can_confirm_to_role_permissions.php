<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_can_confirm_to_role_permissions extends CI_Migration {

    public function up()
    {
        if (!$this->db->field_exists('can_confirm', 'role_permissions')) {
            $this->dbforge->add_column('role_permissions', [
                'can_confirm' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'after'      => 'can_edit',
                ]
            ]);

            // Backfill: existing roles that could edit also keep confirm/approve ability
            // so no user loses access right after the migration.
            $this->db->query("UPDATE role_permissions SET can_confirm = can_edit WHERE can_edit = 1");
        }
    }

    public function down()
    {
        if ($this->db->field_exists('can_confirm', 'role_permissions')) {
            $this->dbforge->drop_column('role_permissions', 'can_confirm');
        }
    }
}
