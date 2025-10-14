<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMaterialsTable extends Migration
{
    /**
     * Method up() akan dijalankan saat kita menjalankan "php spark migrate".
     * Fungsinya adalah untuk membangun skema tabel di database.
     */
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'item_code' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'unique'     => true, // Kode barang harus unik
            ],
            'item_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'hs_code' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true, // Boleh kosong
            ],
            'item_type' => [
                'type'       => "ENUM('import', 'lokal')", // Hanya bisa diisi 'import' atau 'lokal'
                'default'    => 'import',
            ],
            'unit' => [
                'type'       => 'VARCHAR',
                'constraint' => '50', // Misal: KG, PCS, METER
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        // Menentukan Primary Key
        $this->forge->addKey('id', true);
        
        // Membuat tabel dengan nama 'materials'
        $this->forge->createTable('materials');
    }

    /**
     * Method down() akan dijalankan saat kita melakukan rollback migrasi.
     * Fungsinya adalah untuk menghapus tabel yang dibuat oleh method up().
     */
    public function down()
    {
        $this->forge->dropTable('materials');
    }
}