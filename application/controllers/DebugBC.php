<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DebugBC extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('BeaCukai_model');
        $this->load->model('User_model');
        $this->load->helper('url');
    }

    public function test($identifier = '099812')
    {
        // Suppress deprecation warnings
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        
        echo '<pre style="background: #f5f5f5; padding: 20px; border-radius: 5px; font-family: monospace;">';
        echo "=== DEBUG BC DETAIL ===\n";
        echo "Identifier: $identifier\n\n";

        // Query GRN
        $this->db->select('
            h.id,
            h.grn_number,
            h.grn_date,
            h.bc_type_code,
            h.bc_number,
            h.bc_date,
            h.created_by,
            h.created_at,
            h.supplier_id
        ');
        $this->db->from('grn_header h');
        $this->db->where('h.bc_number', $identifier);
        $doc = $this->db->get()->row_array();

        echo "Step 1: GRN Query\n";
        echo "Query Result:\n";
        if ($doc) {
            echo "  ID: " . $doc['id'] . "\n";
            echo "  GRN Number: " . $doc['grn_number'] . "\n";
            echo "  BC Number: " . $doc['bc_number'] . "\n";
            echo "  Supplier ID: " . ($doc['supplier_id'] === null ? 'NULL' : $doc['supplier_id']) . "\n";
            echo "  Is NULL: " . (is_null($doc['supplier_id']) ? 'YES' : 'NO') . "\n";
            echo "  Empty check: " . (empty($doc['supplier_id']) ? 'YES' : 'NO') . "\n";
        } else {
            echo "  NO RESULT\n";
            exit;
        }

        echo "\n\nStep 2: Check if should fetch from detail\n";
        if (is_null($doc['supplier_id']) || $doc['supplier_id'] == 0) {
            echo "  Condition TRUE - Will fetch from detail\n\n";
            
            // Get first detail
            $first_detail = $this->db->select('source_doc_header_id, source_doc_type')
                ->from('grn_details')
                ->where('grn_header_id', $doc['id'])
                ->limit(1)
                ->get()
                ->row();

            if ($first_detail) {
                echo "Step 3: First Detail Found\n";
                echo "  Source Doc Type: " . $first_detail->source_doc_type . "\n";
                echo "  Source Doc Header ID: " . $first_detail->source_doc_header_id . "\n\n";

                if ($first_detail->source_doc_type == 'MIR') {
                    echo "Step 4: Looking up MIR\n";
                    
                    // Method 1: Direct query like in BeaCukai
                    echo "\n  Method 1: Query using DB\n";
                    $supplier = $this->db->select('s.nama_supplier')
                        ->from('mir_header mh')
                        ->join('suppliers s', 's.id = mh.supplier_id', 'left')
                        ->where('mh.id', $first_detail->source_doc_header_id)
                        ->get()
                        ->row();
                    
                    echo "  Result: " . ($supplier ? 'FOUND' : 'NOT FOUND') . "\n";
                    if ($supplier) {
                        echo "  Supplier Name: " . ($supplier->nama_supplier ?? 'NULL') . "\n";
                    }
                    
                    // Method 2: Check MIR separately
                    echo "\n  Method 2: Check MIR data first\n";
                    $mir = $this->db->select('id, mir_number, supplier_id')
                        ->from('mir_header')
                        ->where('id', $first_detail->source_doc_header_id)
                        ->get()
                        ->row();

                    if ($mir) {
                        echo "  MIR Found!\n";
                        echo "    MIR ID: " . $mir->id . "\n";
                        echo "    MIR Number: " . $mir->mir_number . "\n";
                        echo "    Supplier ID: " . ($mir->supplier_id === null ? 'NULL' : $mir->supplier_id) . "\n";

                        if (!is_null($mir->supplier_id)) {
                            // Check supplier
                            $supp = $this->db->select('id, nama_supplier')
                                ->from('suppliers')
                                ->where('id', $mir->supplier_id)
                                ->get()
                                ->row();

                            if ($supp) {
                                echo "  Supplier:\n";
                                echo "    ID: " . $supp->id . "\n";
                                echo "    Name: " . $supp->nama_supplier . "\n";
                                echo "\n✅ FINAL: nama_supplier = " . $supp->nama_supplier . "\n";
                            } else {
                                echo "  ❌ Supplier NOT FOUND\n";
                            }
                        } else {
                            echo "  ⚠️ MIR has no supplier_id\n";
                        }
                    } else {
                        echo "  ❌ MIR NOT FOUND\n";
                    }
                }
            } else {
                echo "  ❌ First detail NOT FOUND\n";
            }
        } else {
            echo "  Condition FALSE - GRN has supplier_id: " . $doc['supplier_id'] . "\n";
        }

        echo "\n\n=== END DEBUG ===\n";
        echo '</pre>';
    }
}
?>
