<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_minimum_stock_to_materials_and_inventories extends CI_Migration {

    public function up() {
        // Add minimum_stock column to materials table
        $this->dbforge->add_column('materials', array(
            'minimum_stock' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0,
                'null' => false,
                'comment' => 'Reorder Point (ROP) - Stock Minimal untuk Alert'
            )
        ));

        // Add minimum_stock column to inventories table
        $this->dbforge->add_column('inventories', array(
            'minimum_stock' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0,
                'null' => false,
                'comment' => 'Reorder Point (ROP) - Stock Minimal untuk Alert'
            )
        ));
    }

    public function down() {
        // Drop minimum_stock column from materials table
        $this->dbforge->drop_column('materials', 'minimum_stock');
        
        // Drop minimum_stock column from inventories table
        $this->dbforge->drop_column('inventories', 'minimum_stock');
    }
}
