<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Refactor_machines_table extends CI_Migration {

    public function up() {
        // Add tipe column if doesn't exist
        if (!$this->db->field_exists('tipe', 'machines')) {
            $this->dbforge->add_column('machines', array(
                'tipe' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '100',
                    'null' => true,
                    'after' => 'merk',
                    'comment' => 'Tipe mesin/peralatan'
                )
            ));
        }

        // Rename tahun to tahun_pengadaan or add it
        if ($this->db->field_exists('tahun', 'machines')) {
            // If tahun exists, copy to tahun_pengadaan
            $this->db->query("ALTER TABLE machines CHANGE COLUMN tahun tahun_pengadaan INT(4) NULL COMMENT 'Tahun pengadaan'");
        } else {
            $this->dbforge->add_column('machines', array(
                'tahun_pengadaan' => array(
                    'type' => 'INT',
                    'constraint' => 4,
                    'null' => true,
                    'comment' => 'Tahun pengadaan'
                )
            ));
        }

        // Add bulan_pengadaan column
        if (!$this->db->field_exists('bulan_pengadaan', 'machines')) {
            $this->dbforge->add_column('machines', array(
                'bulan_pengadaan' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '2',
                    'null' => true,
                    'comment' => 'Bulan pengadaan (01-12)'
                )
            ));
        }

        // Rename lokasi to pic_departemen or add it
        if ($this->db->field_exists('lokasi', 'machines')) {
            // If lokasi exists, rename it
            $this->db->query("ALTER TABLE machines CHANGE COLUMN lokasi pic_departemen VARCHAR(100) NULL COMMENT 'PIC Departemen'");
        } else {
            $this->dbforge->add_column('machines', array(
                'pic_departemen' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '100',
                    'null' => true,
                    'comment' => 'PIC Departemen'
                )
            ));
        }
    }

    public function down() {
        // Drop columns in reverse order
        if ($this->db->field_exists('tipe', 'machines')) {
            $this->dbforge->drop_column('machines', 'tipe');
        }
        if ($this->db->field_exists('bulan_pengadaan', 'machines')) {
            $this->dbforge->drop_column('machines', 'bulan_pengadaan');
        }

        // Revert tahun_pengadaan back to tahun
        if ($this->db->field_exists('tahun_pengadaan', 'machines')) {
            $this->db->query("ALTER TABLE machines CHANGE COLUMN tahun_pengadaan tahun INT(4) NULL");
        }

        // Revert pic_departemen back to lokasi
        if ($this->db->field_exists('pic_departemen', 'machines')) {
            $this->db->query("ALTER TABLE machines CHANGE COLUMN pic_departemen lokasi VARCHAR(100) NULL");
        }
    }
}
