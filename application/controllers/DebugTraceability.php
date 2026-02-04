<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DebugTraceability extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('LapMutasiFinished_model', 'model');
    }

    public function test_fg_in()
    {
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        
        echo '<pre style="background: #f5f5f5; padding: 20px; border-radius: 5px; font-family: monospace;">';
        echo "=== DEBUG FG TRACEABILITY MATERIALS ===\n\n";

        // Get FG receipt detail ID 15 (from screenshot)
        $detail = $this->model->get_detail_penerimaan(15);

        echo "Step 1: Get FG Receipt Detail\n";
        if ($detail) {
            echo "  WO Header ID: " . $detail->wo_header_id . "\n";
            echo "  Production Header ID: " . $detail->production_header_id . "\n\n";

            echo "Step 2: Get Materials for Production\n";
            $materials = $this->model->get_materials_for_production($detail->wo_header_id, $detail->production_header_id);

            if (!empty($materials)) {
                echo "  Materials found: " . count($materials) . "\n\n";
                
                foreach ($materials as $i => $mat) {
                    echo "Material " . ($i + 1) . ":\n";
                    echo "  Kode: " . ($mat['kode_material'] ?? '-') . "\n";
                    echo "  Nama: " . ($mat['nama_material'] ?? '-') . "\n";
                    echo "  BC Type Code: " . ($mat['bc_type_code'] ?? 'KOSONG') . "\n";
                    echo "  BC Number: " . ($mat['bc_number'] ?? 'KOSONG') . "\n";
                    echo "  BC Date: " . ($mat['bc_date'] ?? 'KOSONG') . "\n";
                    echo "  Arrival Date: " . ($mat['arrival_date'] ?? 'KOSONG') . "\n";
                    echo "  Supplier: " . ($mat['nama_supplier'] ?? 'KOSONG') . "\n";
                    echo "  GRN Number: " . ($mat['grn_number'] ?? 'KOSONG') . "\n";
                    echo "\n";
                }
            } else {
                echo "  ❌ No materials found\n";
            }
        } else {
            echo "  ❌ Detail penerimaan not found\n";
        }

        echo "=== END DEBUG ===\n";
        echo '</pre>';
    }
}
?>
