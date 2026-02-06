<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BeaCukai extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('BeaCukai_model');
        $this->load->model('User_model');
        $this->load->helper('url');
    }

    public function detail($identifier = null) {
        if (empty($identifier)) {
            show_error('Parameter dokumen BC tidak valid.', 400);
            return;
        }

        // Try to decode base64 first (untuk GRN number yang di-encode)
        $decoded = base64_decode($identifier, true);
        if ($decoded !== false && preg_match('/^GRN\/\d{6}\/\d{4}$/', $decoded)) {
            $identifier = $decoded;
        }

        $doc = null;
        $doc_type = null;

        // Deteksi apakah ini GRN number (format: GRN/YYYYMM/XXXX)
        if (preg_match('/^GRN\/\d{6}\/\d{4}$/', $identifier)) {
            // Ini adalah GRN number - cari berdasarkan grn_number
            $this->db->select('
                h.id,
                h.grn_number,
                h.grn_date,
                h.bc_type_code,
                h.bc_number,
                h.bc_date,
                h.created_by,
                h.created_at,
                h.supplier_id,
                s.nama_supplier
            ');
            $this->db->from('grn_header h');
            $this->db->join('suppliers s', 's.id = h.supplier_id', 'left');
            $this->db->where('h.grn_number', $identifier);
            $doc = $this->db->get()->row_array();
            
            if (!empty($doc)) {
                $doc_type = 'MATERIAL_IN';
                $doc['type'] = 'IN';
                
                // Ambil detail items dari GRN (support material & machine)
                $doc['details'] = $this->db->select('
                        gd.id,
                        gd.item_type,
                        COALESCE(m.kode_material, mc.kode_mesin) AS item_code,
                        COALESCE(m.nama_material, mc.nama_mesin) AS item_name,
                        gd.receive_qty AS qty,
                        COALESCE(m.satuan, mc.satuan) AS unit,
                        COALESCE(ph.po_number, mh.mir_number) as doc_number,
                        COALESCE(s_po.nama_supplier, s_mir.nama_supplier) as nama_supplier
                    ')
                    ->from('grn_details gd')
                    ->join('materials m', 'm.id = gd.item_id AND gd.item_type = "material"', 'left')
                    ->join('machines mc', 'mc.id = gd.item_id AND gd.item_type = "machine"', 'left')
                    ->join('po_header ph', 'ph.id = gd.source_doc_header_id AND gd.source_doc_type = "PO"', 'left')
                    ->join('mir_header mh', 'mh.id = gd.source_doc_header_id AND gd.source_doc_type = "MIR"', 'left')
                    ->join('suppliers s_po', 's_po.id = ph.supplier_id', 'left')
                    ->join('suppliers s_mir', 's_mir.id = mh.supplier_id', 'left')
                    ->where('gd.grn_header_id', $doc['id'])
                    ->get()
                    ->result_array();
                
                // Ensure nama_supplier is set
                if (empty($doc['nama_supplier']) || $doc['nama_supplier'] === null) {
                    foreach ($doc['details'] as $detail) {
                        if (!empty($detail['nama_supplier'])) {
                            $doc['nama_supplier'] = $detail['nama_supplier'];
                            break;
                        }
                    }
                }
                if (empty($doc['nama_supplier'])) {
                    $doc['nama_supplier'] = '-';
                }
            }
        } elseif (is_numeric($identifier) && strlen($identifier) < 6) {
            // ID numeric untuk SCRAP legacy
            $doc = $this->BeaCukai_model->get_by_id($identifier);
            $doc_type = 'SCRAP';
        }
        
        // Jika belum ketemu, cek berdasarkan BC number
        if (empty($doc)) {
            // Cek di GRN Header dulu (BC dari material masuk)
            $this->db->select('
                h.id,
                h.grn_number,
                h.grn_date,
                h.bc_type_code,
                h.bc_number,
                h.bc_date,
                h.created_by,
                h.created_at,
                h.supplier_id,
                s.nama_supplier
            ');
            $this->db->from('grn_header h');
            // Join with suppliers from GRN header
            $this->db->join('suppliers s', 's.id = h.supplier_id', 'left');
            $this->db->where('h.bc_number', $identifier);
            
            $doc = $this->db->get()->row_array();

            if (!empty($doc)) {
                $doc_type = 'MATERIAL_IN';
                $doc['type'] = 'IN';
                
                // Ambil detail items dari GRN (support material & machine)
                $doc['details'] = $this->db->select('
                        gd.id,
                        gd.item_type,
                        COALESCE(m.kode_material, mc.kode_mesin) AS item_code,
                        COALESCE(m.nama_material, mc.nama_mesin) AS item_name,
                        gd.receive_qty AS qty,
                        COALESCE(m.satuan, mc.satuan) AS unit,
                        COALESCE(ph.po_number, mh.mir_number) as doc_number,
                        COALESCE(s_po.nama_supplier, s_mir.nama_supplier) as nama_supplier
                    ')
                    ->from('grn_details gd')
                    ->join('materials m', 'm.id = gd.item_id AND gd.item_type = "material"', 'left')
                    ->join('machines mc', 'mc.id = gd.item_id AND gd.item_type = "machine"', 'left')
                    ->join('po_header ph', 'ph.id = gd.source_doc_header_id AND gd.source_doc_type = "PO"', 'left')
                    ->join('mir_header mh', 'mh.id = gd.source_doc_header_id AND gd.source_doc_type = "MIR"', 'left')
                    ->join('suppliers s_po', 's_po.id = ph.supplier_id', 'left')
                    ->join('suppliers s_mir', 's_mir.id = mh.supplier_id', 'left')
                    ->where('gd.grn_header_id', $doc['id'])
                    ->get()
                    ->result_array();
                
                // Ensure nama_supplier is set - if still null, fallback dari details
                if (empty($doc['nama_supplier']) || $doc['nama_supplier'] === null) {
                    // Try to get supplier from first item detail
                    foreach ($doc['details'] as $detail) {
                        if (!empty($detail['nama_supplier'])) {
                            $doc['nama_supplier'] = $detail['nama_supplier'];
                            break;
                        }
                    }
                }
                
                // If still empty, set default
                if (empty($doc['nama_supplier'])) {
                    $doc['nama_supplier'] = '-';
                }
            } else {
                // Cek di scrap_out_header (BC dari scrap keluar)
                $this->db->select('
                    h.id,
                    h.bc_type_code,
                    h.bc_number,
                    h.bc_date,
                    h.destination AS tujuan,
                    h.created_by,
                    h.created_at
                ');
                $this->db->from('scrap_out_header h');
                $this->db->where('h.bc_number', $identifier);
                $doc = $this->db->get()->row_array();

                if (!empty($doc)) {
                    $doc_type = 'SCRAP_OUT';
                    $doc['type'] = 'OUT';
                    $doc['details'] = $this->db->select('
                            d.id,
                            s.scrap_code AS item_code,
                            s.scrap_name AS item_name,
                            d.qty,
                            d.unit
                        ')
                        ->from('scrap_out_detail d')
                        ->join('master_scrap s', 's.id = d.scrap_id', 'left')
                        ->where('d.header_id', $doc['id'])
                        ->get()
                        ->result_array();
                }
            }
        }

        // Legacy: Jika masih belum ketemu dengan sistem lama (BeaCukai_model)
        if (empty($doc)) {
            $doc = $this->BeaCukai_model->get_by_number($identifier);
            $doc_type = 'SCRAP';
            if (!empty($doc)) {
                $doc['type'] = 'IN';
                $doc['details'] = $this->BeaCukai_model->get_scrap_details($doc['id'], 'grn');
            }
        }

        if (empty($doc)) {
            show_error('Dokumen BC "' . htmlspecialchars($identifier) . '" tidak ditemukan.', 404);
            return;
        }

        // Ambil nama user pembuat
        if (!empty($doc['created_by'])) {
            $user_name = $this->User_model->get_user_name($doc['created_by']);
            $doc['created_by'] = $user_name ?: 'Tidak diketahui';
        }

        // Tentukan content view berdasarkan tipe dokumen
        if ($doc_type == 'MATERIAL_IN') {
            $content_view = 'it_inventory/beacukai/view_bc_material_in';
        } else if ($doc_type == 'SCRAP_OUT') {
            $content_view = 'it_inventory/beacukai/view_bc_scrap_out';
        } else {
            $content_view = 'it_inventory/lap_mutasi_scrap/view_bc_doc'; // Legacy
        }

        // Siapkan data untuk view template utama
        $data = [
            'page_title' => 'Dokumen BC ' . ($doc['bc_number'] ?? '-'),
            'doc'        => $doc,
            'doc_type'   => $doc_type,
            'content_view' => $content_view,
        ];

        // Load tampilan lengkap menggunakan template Neokaber
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($data['content_view'], $data);
        $this->load->view('templates/footer', $data);
    }
}
