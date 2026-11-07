<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_spesifikasi_to_materials extends CI_Migration {

    public function up() {
        // Add spesifikasi column to materials table
        $this->dbforge->add_column('materials', array(
            'spesifikasi' => array(
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Spesifikasi, deskripsi, atau catatan material'
            )
        ));
    }

    public function down() {
        // Drop spesifikasi column from materials table
        $this->dbforge->drop_column('materials', 'spesifikasi');
    }
}
