<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_user_enhancement extends CI_Migration {

    public function up() {
        // Add departemen, signature_path, and session_token columns to users table
        $fields = array(
            'departemen' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => TRUE,
                'after' => 'role_id'
            ),
            'signature_path' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => TRUE,
                'after' => 'departemen'
            ),
            'session_token' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => TRUE,
                'after' => 'signature_path'
            )
        );

        $this->dbforge->add_column('users', $fields);
    }

    public function down() {
        // Remove the three columns in reverse order
        $this->dbforge->drop_column('users', 'session_token');
        $this->dbforge->drop_column('users', 'signature_path');
        $this->dbforge->drop_column('users', 'departemen');
    }
}
?>
