<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_consumable_out_tables extends CI_Migration {

    public function up()
    {
        // --------------------------------------------------------
        // Tabel: consumable_out_header
        // --------------------------------------------------------
        $this->dbforge->add_field([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => TRUE,
                'auto_increment' => TRUE,
            ],
            'doc_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => FALSE,
            ],
            'issuance_date' => [
                'type' => 'DATE',
                'null' => FALSE,
            ],
            'department' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => TRUE,
            ],
            'recipient' => [
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
                'type'    => "ENUM('Created','Confirmed','Cancelled')",
                'default' => 'Created',
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
            'confirmed_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => TRUE,
                'null'       => TRUE,
            ],
            'confirmed_at' => [
                'type' => 'DATETIME',
                'null' => TRUE,
            ],
            'cancelled_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => TRUE,
                'null'       => TRUE,
            ],
            'cancelled_at' => [
                'type' => 'DATETIME',
                'null' => TRUE,
            ],
        ]);

        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('doc_number');
        $this->dbforge->add_key('status');
        $this->dbforge->add_foreign_key('created_by',   'users', 'id', 'SET NULL');
        $this->dbforge->add_foreign_key('confirmed_by', 'users', 'id', 'SET NULL');
        $this->dbforge->add_foreign_key('cancelled_by', 'users', 'id', 'SET NULL');
        $this->dbforge->create_table('consumable_out_header', TRUE);

        // Unique constraint on doc_number
        $this->db->query('ALTER TABLE `consumable_out_header` ADD UNIQUE KEY `uq_doc_number` (`doc_number`)');

        // --------------------------------------------------------
        // Tabel: consumable_out_details
        // --------------------------------------------------------
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
            'qty_issued' => [
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
        $this->dbforge->add_foreign_key('header_id', 'consumable_out_header', 'id', 'CASCADE');
        $this->dbforge->create_table('consumable_out_details', TRUE);
    }

    public function down()
    {
        $this->dbforge->drop_table('consumable_out_details', TRUE);
        $this->dbforge->drop_table('consumable_out_header',  TRUE);
    }
}
