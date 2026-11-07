<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class QC_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get all QC records with details
     * @param array $filters - search, stage filter, date range
     * @return array
     */
    public function get_all_qc($filters = []) {
        $this->db->select('
            po.id,
            po.production_header_id,
            po.stage_name,
            po.item_id,
            po.item_type,
            po.qty_produced as qty_qc,
            po.qc_checked_by,
            po.qc_checked_at,
            po.qc_description,
            po.qc_photo_url,
            COALESCE(NULLIF(m.nama_material, ""), COALESCE(NULLIF(sf.nama_barang, ""), COALESCE(NULLIF(ms.scrap_name, ""), COALESCE(NULLIF(fg.nama_barang, ""), "Unknown")))) as item_name,
            COALESCE(NULLIF(m.kode_material, ""), COALESCE(NULLIF(sf.kode_barang, ""), COALESCE(NULLIF(ms.scrap_code, ""), COALESCE(NULLIF(fg.kode_barang, ""), "")))) as item_code,
            ph.production_number,
            ph.wo_header_id,
            u.fullname as checked_by_name,
            u.email as checked_by_email
        ');
        
        $this->db->from('production_outputs po');
        $this->db->join('production_header ph', 'po.production_header_id = ph.id', 'left');
        $this->db->join('users u', 'po.qc_checked_by = u.id', 'left');
        
        // Join dengan berbagai tabel material
        $this->db->join('materials m', 'po.item_id = m.id AND po.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'po.item_id = sf.id AND po.item_type = "semifinished"', 'left');
        $this->db->join('master_scrap ms', 'po.item_id = ms.id AND po.item_type = "scrap"', 'left');
        $this->db->join('finished_goods fg', 'po.item_id = fg.id AND po.item_type = "finished_goods"', 'left');
        
        // Filter: hanya QC yang sudah dicek (qc_checked_by NOT NULL)
        $this->db->where('po.qc_checked_by IS NOT NULL', NULL, FALSE);
        
        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $this->db->group_start()
                ->like('ph.production_number', $search)
                ->or_like('m.nama_material', $search)
                ->or_like('sf.nama_barang', $search)
                ->or_like('ms.scrap_name', $search)
                ->or_like('fg.nama_barang', $search)
                ->group_end();
        }
        
        // Stage filter
        if (!empty($filters['stage'])) {
            $this->db->where('po.stage_name', $filters['stage']);
        }
        
        // Date range filter
        if (!empty($filters['date_from'])) {
            $this->db->where('DATE(po.qc_checked_at) >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('DATE(po.qc_checked_at) <=', $filters['date_to']);
        }
        
        $this->db->order_by('po.qc_checked_at', 'DESC');
        $query = $this->db->get();
        
        return $query->result();
    }

    /**
     * Get production outputs PENDING QC (qc_checked_by IS NULL).
     * Untuk action page Production QC — list semua output yang belum di-QC.
     * @param array $filters - search, stage, date_from, date_to
     * @return array
     */
    public function get_pending_qc($filters = []) {
        $this->db->select('
            po.id,
            po.production_header_id,
            po.stage_name,
            po.item_id,
            po.item_type,
            po.qty_produced as qty_qc,
            po.qty_scrap,
            po.received_at,
            po.received_by,
            po.qc_description,
            po.qc_photo_url,
            COALESCE(NULLIF(m.nama_material, ""), COALESCE(NULLIF(sf.nama_barang, ""), COALESCE(NULLIF(ms.scrap_name, ""), COALESCE(NULLIF(fg.nama_barang, ""), "Unknown")))) as item_name,
            COALESCE(NULLIF(m.kode_material, ""), COALESCE(NULLIF(sf.kode_barang, ""), COALESCE(NULLIF(ms.scrap_code, ""), COALESCE(NULLIF(fg.kode_barang, ""), "")))) as item_code,
            COALESCE(NULLIF(m.satuan, ""), COALESCE(NULLIF(sf.satuan, ""), "")) as satuan,
            ph.production_number,
            ph.wo_header_id,
            ph.production_date,
            wo.wo_number,
            u_rec.fullname as received_by_name
        ');

        $this->db->from('production_outputs po');
        $this->db->join('production_header ph', 'po.production_header_id = ph.id', 'left');
        $this->db->join('wo_header wo', 'ph.wo_header_id = wo.id', 'left');
        $this->db->join('users u_rec', 'po.received_by = u_rec.id', 'left');
        $this->db->join('materials m', 'po.item_id = m.id AND po.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'po.item_id = sf.id AND po.item_type = "semifinished"', 'left');
        $this->db->join('master_scrap ms', 'po.item_id = ms.id AND po.item_type = "scrap"', 'left');
        $this->db->join('finished_goods fg', 'po.item_id = fg.id AND po.item_type = "finished_goods"', 'left');

        // Hanya yang BELUM di-QC
        $this->db->where('po.qc_checked_by IS NULL', NULL, FALSE);

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $this->db->group_start()
                ->like('ph.production_number', $term)
                ->or_like('wo.wo_number', $term)
                ->or_like('m.nama_material', $term)
                ->or_like('sf.nama_barang', $term)
                ->or_like('ms.scrap_name', $term)
                ->or_like('fg.nama_barang', $term)
                ->group_end();
        }
        if (!empty($filters['stage'])) {
            $this->db->where('po.stage_name', $filters['stage']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('DATE(po.received_at) >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('DATE(po.received_at) <=', $filters['date_to']);
        }

        $this->db->order_by('po.received_at', 'ASC'); // FIFO — yang paling lama belum di-QC duluan
        $this->db->order_by('po.id', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Approve QC: set qc_checked_by + qc_checked_at.
     */
    public function approve_qc($output_id, $user_id, $description = null, $photo_url = null) {
        $data = [
            'qc_checked_by' => $user_id,
            'qc_checked_at' => date('Y-m-d H:i:s'),
        ];
        if ($description !== null) $data['qc_description'] = $description;
        if ($photo_url !== null)   $data['qc_photo_url']   = $photo_url;
        return $this->db->where('id', $output_id)->update('production_outputs', $data);
    }

    /**
     * Undo approve QC.
     */
    public function unapprove_qc($output_id) {
        return $this->db->where('id', $output_id)->update('production_outputs', [
            'qc_checked_by' => NULL,
            'qc_checked_at' => NULL,
        ]);
    }

    /**
     * Count pending QC per stage (untuk badge di sidebar/dashboard).
     */
    public function count_pending_qc_by_stage() {
        $this->db->select('stage_name, COUNT(*) as total');
        $this->db->from('production_outputs');
        $this->db->where('qc_checked_by IS NULL', NULL, FALSE);
        $this->db->group_by('stage_name');
        return $this->db->get()->result();
    }

    /**
     * Get single QC detail
     * @param int $output_id
     * @return object|null
     */
    public function get_qc_detail($output_id) {
        $this->db->select('
            po.id,
            po.production_header_id,
            po.stage_name,
            po.item_id,
            po.item_type,
            po.qty_produced as qty_qc,
            po.qc_checked_by,
            po.qc_checked_at,
            po.qc_description,
            po.qc_photo_url,
            po.size_breakdown,
            COALESCE(NULLIF(m.nama_material, ""), COALESCE(NULLIF(sf.nama_barang, ""), COALESCE(NULLIF(ms.scrap_name, ""), COALESCE(NULLIF(fg.nama_barang, ""), "Unknown")))) as item_name,
            COALESCE(NULLIF(m.kode_material, ""), COALESCE(NULLIF(sf.kode_barang, ""), COALESCE(NULLIF(ms.scrap_code, ""), COALESCE(NULLIF(fg.kode_barang, ""), "")))) as item_code,
            COALESCE(NULLIF(m.satuan, ""), COALESCE(NULLIF(sf.satuan, ""), "")) as satuan,
            ph.production_number,
            ph.wo_header_id,
            ph.production_date,
            ph.remarks as production_remarks,
            u.fullname as checked_by_name,
            u.email as checked_by_email,
            wo.wo_number,
            fg_data.nama_barang as fg_name
        ');
        
        $this->db->from('production_outputs po');
        $this->db->join('production_header ph', 'po.production_header_id = ph.id', 'left');
        $this->db->join('wo_header wo', 'ph.wo_header_id = wo.id', 'left');
        $this->db->join('users u', 'po.qc_checked_by = u.id', 'left');
        $this->db->join('finished_goods fg_data', 'ph.fg_id = fg_data.id', 'left');
        
        // Join dengan berbagai tabel material
        $this->db->join('materials m', 'po.item_id = m.id AND po.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'po.item_id = sf.id AND po.item_type = "semifinished"', 'left');
        $this->db->join('master_scrap ms', 'po.item_id = ms.id AND po.item_type = "scrap"', 'left');
        $this->db->join('finished_goods fg', 'po.item_id = fg.id AND po.item_type = "finished_goods"', 'left');
        
        $this->db->where('po.id', $output_id);
        $query = $this->db->get();
        
        return $query->row();
    }

    /**
     * Get QC input details (tracing back to source materials)
     * @param int $production_header_id
     * @param string $stage_name
     * @return array
     */
    public function get_qc_stage_inputs($production_header_id, $stage_name) {
        $this->db->select('
            pi.id,
            pi.item_id,
            pi.item_type,
            pi.qty_used,
            pi.notes,
            COALESCE(NULLIF(m.nama_material, ""), COALESCE(NULLIF(sf.nama_barang, ""), "Unknown")) as item_name,
            COALESCE(NULLIF(m.kode_material, ""), COALESCE(NULLIF(sf.kode_barang, ""), "")) as item_code
        ');
        
        $this->db->from('production_inputs pi');
        $this->db->join('materials m', 'pi.item_id = m.id AND pi.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'pi.item_id = sf.id AND pi.item_type = "semifinished"', 'left');
        
        $this->db->where('pi.production_header_id', $production_header_id);
        $this->db->where('pi.stage_name', $stage_name);
        $this->db->order_by('pi.id', 'ASC');
        
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Get QC statistics
     * @return object
     */
    public function get_qc_statistics() {
        $this->db->select('
            COUNT(DISTINCT po.id) as total_qc_records,
            COUNT(DISTINCT po.production_header_id) as total_production_checked,
            SUM(po.qty_produced) as total_qty_checked,
            po.stage_name
        ');
        
        $this->db->from('production_outputs po');
        $this->db->where('po.qc_checked_by IS NOT NULL', NULL, FALSE);
        $this->db->group_by('po.stage_name');
        
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Get QC by Production Header ID
     * @param int $production_header_id
     * @return array
     */
    public function get_qc_by_production($production_header_id) {
        $this->db->select('
            po.id,
            po.stage_name,
            po.item_id,
            po.item_type,
            po.qty_produced as qty_qc,
            po.qc_checked_by,
            po.qc_checked_at,
            po.qc_description,
            po.qc_photo_url,
            COALESCE(NULLIF(m.nama_material, ""), COALESCE(NULLIF(sf.nama_barang, ""), COALESCE(NULLIF(ms.scrap_name, ""), COALESCE(NULLIF(fg.nama_barang, ""), "Unknown")))) as item_name,
            u.fullname as checked_by_name
        ');
        
        $this->db->from('production_outputs po');
        $this->db->join('users u', 'po.qc_checked_by = u.id', 'left');
        $this->db->join('materials m', 'po.item_id = m.id AND po.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'po.item_id = sf.id AND po.item_type = "semifinished"', 'left');
        $this->db->join('master_scrap ms', 'po.item_id = ms.id AND po.item_type = "scrap"', 'left');
        $this->db->join('finished_goods fg', 'po.item_id = fg.id AND po.item_type = "finished_goods"', 'left');
        
        $this->db->where('po.production_header_id', $production_header_id);
        $this->db->where('po.qc_checked_by IS NOT NULL', NULL, FALSE);
        $this->db->order_by('po.stage_name', 'ASC');
        
        $query = $this->db->get();
        return $query->result();
    }
}
