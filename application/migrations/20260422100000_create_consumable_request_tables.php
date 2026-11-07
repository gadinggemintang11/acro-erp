<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_consumable_request_tables extends CI_Migration {

    public function up()
    {
        // ========================================
        // consumable_request_header
        // ========================================
        $this->dbforge->add_field([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => TRUE,
                'auto_increment' => TRUE,
            ],
            'request_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => FALSE,
            ],
            'request_date' => [
                'type' => 'DATE',
                'null' => FALSE,
            ],
            'department' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => TRUE,
            ],
            'requestor_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => TRUE,
            ],
            'purpose' => [
                'type' => 'TEXT',
                'null' => TRUE,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => TRUE,
            ],
            'status' => [
                'type'    => "ENUM('Draft','Submitted','Approved','Rejected','Cancelled')",
                'default' => 'Draft',
                'null'    => FALSE,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => TRUE,
                'null'       => TRUE,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => TRUE,
            ],
            'approved_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => TRUE,
                'null'       => TRUE,
            ],
            'approved_at' => [
                'type' => 'DATETIME',
                'null' => TRUE,
            ],
            'rejection_notes' => [
                'type' => 'TEXT',
                'null' => TRUE,
            ],
        ]);

        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('request_number');
        $this->dbforge->add_key('status');
        $this->dbforge->add_foreign_key('created_by',   'users', 'id', 'SET NULL');
        $this->dbforge->add_foreign_key('approved_by',  'users', 'id', 'SET NULL');
        $this->dbforge->create_table('consumable_request_header', TRUE);

        $this->db->query('ALTER TABLE `consumable_request_header` ADD UNIQUE KEY `uq_request_number` (`request_number`)');

        // ========================================
        // consumable_request_details
        // ========================================
        $this->dbforge->add_field([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => TRUE,
                'auto_increment' => TRUE,
            ],
            'header_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => TRUE,
                'null'       => FALSE,
            ],
            'item_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => TRUE,
            ],
            'item_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => TRUE,
            ],
            'item_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => TRUE,
            ],
            'item_unit' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => TRUE,
            ],
            'qty_requested' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,4',
                'default'    => '0.0000',
                'null'       => FALSE,
            ],
            'notes' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => TRUE,
            ],
        ]);

        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('header_id');
        $this->dbforge->add_foreign_key('header_id', 'consumable_request_header', 'id', 'CASCADE');
        $this->dbforge->create_table('consumable_request_details', TRUE);

        // ========================================
        // Update consumable_out_header - add request_id
        // ========================================
        $this->dbforge->add_column('consumable_out_header', [
            'request_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => TRUE,
                'null'       => TRUE,
                'after'      => 'doc_number',
            ],
        ]);

        $this->db->query('ALTER TABLE `consumable_out_header` ADD FOREIGN KEY (`request_id`) REFERENCES `consumable_request_header` (`id`) ON DELETE SET NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `consumable_out_header` DROP FOREIGN KEY consumable_out_header_ibfk_4');
        $this->dbforge->drop_column('consumable_out_header', 'request_id');
        $this->dbforge->drop_table('consumable_request_details', TRUE);
        $this->dbforge->drop_table('consumable_request_header',  TRUE);
    }
}
