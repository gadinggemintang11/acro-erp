<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_konversi_satuan_to_finished_goods extends CI_Migration {

    public function up() {
        // Add konversi_satuan column to finished_goods table
        $this->dbforge->add_column('finished_goods', array(
            'konversi_satuan' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'comment' => 'Konversi satuan (misal: 50 PCS per BOX)'
            )
        ));
    }

    public function down() {
        // Drop konversi_satuan column from finished_goods table
        $this->dbforge->drop_column('finished_goods', 'konversi_satuan');
    }
}
