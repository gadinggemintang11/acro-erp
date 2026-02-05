<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_adjustment_documents extends CI_Migration {

    public function up()
    {
        // Tabel untuk menyimpan dokumen upload yang terkait dengan adjustment
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ],
            'adjustment_header_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => FALSE,
                'comment' => 'Foreign key ke adjustment_headers'
            ],
            'file_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => FALSE,
                'comment' => 'Nama file asli'
            ],
            'file_path' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => FALSE,
                'comment' => 'Path file di server'
            ],
            'file_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => FALSE,
                'comment' => 'MIME type (e.g. image/jpeg, application/pdf)'
            ],
            'file_size' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => FALSE,
                'comment' => 'Ukuran file dalam bytes'
            ],
            'uploaded_at' => [
                'type' => 'DATETIME',
                'null' => FALSE,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Waktu upload'
            ],
            'uploaded_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => FALSE,
                'comment' => 'User ID yang upload'
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => TRUE,
                'comment' => 'Deskripsi dokumen'
            ]
        ]);

        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('adjustment_header_id');
        $this->dbforge->add_key('uploaded_by');
        $this->dbforge->add_foreign_key('adjustment_header_id', 'adjustment_headers', 'id', 'CASCADE');
        $this->dbforge->add_foreign_key('uploaded_by', 'users', 'id', 'SET NULL');

        $this->dbforge->create_table('adjustment_documents');
    }

    public function down()
    {
        $this->dbforge->drop_table('adjustment_documents', TRUE);
    }
}
