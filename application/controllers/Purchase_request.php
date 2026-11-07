<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class Purchase_request extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Purchase_request_model', 'Sc_model', 'Bom_model', 'Material_model', 'Inventory_model', 'Semifinished_model', 'Machine_model']);
        $this->load->helper('url');
    }

    // ===================== LIST =====================

    public function index() {
        if (!check_access('purchase_request', 'view')) show_error(lang_line('purchase_request_msg_no_permission', 'Anda tidak memiliki izin.'), 403);

        $filters = [
            'status' => $this->input->get('status'),
            'search' => $this->input->get('search'),
            'type'   => 'produksi',
            'requester_id' => $this->session->userdata('user_id'),
        ];

        $data = [
            'title'    => 'PR Material',
            'prs'      => $this->Purchase_request_model->get_all($filters),
            'filters'  => $filters,
            'statuses' => ['Draft', 'Submitted', 'Approved', 'Rejected'],
            'suppliers'=> $this->db->order_by('nama_supplier','ASC')->get('suppliers')->result(),
            'pr_type'  => 'produksi',
        ];

        $this->load->view('templates/header',  $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar',  $data);
        $this->load->view('purchase_request/index', $data);
        $this->load->view('templates/footer');
    }

    // ===================== DETAIL =====================

    public function detail($id) {
        if (!check_access('purchase_request', 'view')) show_error(lang_line('purchase_request_msg_no_permission', 'Anda tidak memiliki izin.'), 403);
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header) show_404();

        $data = [
            'title'          => 'Detail PR Material',
            'header'         => $header,
            'details'        => $this->Purchase_request_model->get_details_by_id($id),
            // Progres PO per baris: sudah masuk PO berapa, PO mana saja.
            'po_progress'    => $this->Purchase_request_model->get_po_progress_by_pr($id),
            'open_contracts' => $this->Sc_model->get_open_contracts(),
            'fg_model_list'  => $this->Sc_model->get_fg_with_open_sc(),
            'suppliers'      => $this->db->order_by('nama_supplier','ASC')->get('suppliers')->result(),
            'can_edit'       => $header->status === 'Draft' && check_access('purchase_request', 'edit'),
            'can_submit'     => $header->status === 'Draft' && check_access('purchase_request', 'edit'),
            'can_approve'    => $header->status === 'Submitted' && check_access('purchase_request_approval', 'approve'),
            'can_unsubmit'   => $header->status === 'Submitted' && check_access('purchase_request', 'edit'),
        ];

        $this->load->view('templates/header',  $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar',  $data);
        $this->load->view('purchase_request/detail', $data);
        $this->load->view('templates/footer');
    }

    // ===================== CREATE FROM STOCK ALERT =====================

    public function create() {
        if (!check_access('purchase_request', 'add')) show_error(lang_line('purchase_request_msg_no_permission_add', 'Anda tidak memiliki izin.'), 403);

        $item_type = $this->input->get('item_type');
        $item_id = $this->input->get('item_id');

        if (!$item_type || !$item_id) {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_item_type_id_required', 'Item type dan ID harus disediakan.'));
            redirect('purchase_request');
        }

        // Fetch item details based on type
        $item = null;
        $item_name = lang_line('text_unknown', 'Unknown Item');
        $table_map = [
            'material' => 'materials',
            'inventory' => 'inventories',
            'semifinished' => 'semifinished_goods',
            'machine' => 'machines'
        ];

        if (isset($table_map[$item_type])) {
            $item = $this->db->where('id', $item_id)->get($table_map[$item_type])->row();
            if ($item) {
                $name_field = [
                    'material' => 'nama_material',
                    'inventory' => 'nama_barang',
                    'semifinished' => 'nama_barang',
                    'machine' => 'nama_mesin'
                ][$item_type];
                $item_name = $item->{$name_field};
            }
        }

        if (!$item) {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_item_not_found', 'Item tidak ditemukan.'));
            redirect('purchase_request');
        }

        // Create new PR from stock alert (type follows item type — inventory => non_produksi)
        $pr_type = ($item_type === 'inventory') ? 'non_produksi' : 'produksi';
        $header_data = [
            'pr_number'   => $this->Purchase_request_model->generate_pr_number($pr_type),
            'pr_date'     => date('Y-m-d'),
            'department'  => 'Stock Alert',
            'type'        => $pr_type,
            'purpose'     => 'Pengadaan untuk stock alert: ' . $item_name,
            'remarks'     => 'Dibuat otomatis dari stock alert untuk item dengan stok minimum',
            'requester_id'=> $this->session->userdata('user_id'),
            'created_by'  => $this->session->userdata('user_id'),
        ];

        $pr_id = $this->Purchase_request_model->insert_header($header_data);

        if ($pr_id) {
            // Add the item to PR
            $insert = [
                'pr_header_id' => $pr_id,
                'item_id'      => $item_id,
                'item_type'    => $item_type,
                'qty'          => 1,
                'description'  => '',
                'remarks'      => 'Dari stock alert',
            ];

            $this->Purchase_request_model->insert_item($insert);

            $this->session->set_flashdata('success', lang_line('purchase_request_msg_create_from_stock_success', 'Purchase Request berhasil dibuat dari stock alert') . ' (' . $header_data['pr_number'] . ')');
            redirect('purchase_request/detail/' . $pr_id);
        } else {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_create_failed', 'Gagal membuat Purchase Request.'));
            redirect('purchase_request');
        }
    }

    // ===================== CRUD HEADER =====================

    public function add_header() {
        if (!check_access('purchase_request', 'add')) show_error(lang_line('purchase_request_msg_no_permission_add', 'Anda tidak memiliki izin.'), 403);

        $data = [
            'pr_number'   => $this->Purchase_request_model->generate_pr_number('produksi'),
            'pr_date'     => $this->input->post('pr_date') ?: date('Y-m-d'),
            'department'  => $this->input->post('department'),
            'type'        => 'produksi',
            'purpose'     => $this->input->post('purpose'),
            'remarks'     => $this->input->post('remarks'),
            'requester_id'=> $this->session->userdata('user_id'),
            'created_by'  => $this->session->userdata('user_id'),
        ];

        $id = $this->Purchase_request_model->insert_header($data);
        if ($id) {
            $this->session->set_flashdata('success', lang_line('purchase_request_msg_create_success', 'Purchase Request berhasil dibuat') . ' (' . $data['pr_number'] . ')');
            redirect('purchase_request/detail/' . $id);
        } else {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_create_failed', 'Gagal membuat Purchase Request.'));
            redirect('purchase_request');
        }
    }

    public function update_header($id) {
        if (!check_access('purchase_request', 'edit')) show_error('Anda tidak memiliki izin.', 403);

        $data = [
            'pr_date'     => $this->input->post('pr_date'),
            'department'  => $this->input->post('department'),
            'purpose'     => $this->input->post('purpose'),
            'remarks'     => $this->input->post('remarks'),
        ];

        if ($this->Purchase_request_model->update_header($id, $data)) {
            $this->session->set_flashdata('success', lang_line('purchase_request_msg_update_header_success', 'Header PR berhasil diperbarui.'));
        } else {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_update_header_failed', 'Gagal memperbarui header PR.'));
        }
        redirect('purchase_request/detail/' . $id);
    }

    public function delete_header($id) {
        if (!check_access('purchase_request', 'delete')) show_error(lang_line('purchase_request_msg_no_permission_delete', 'Anda tidak memiliki izin.'), 403);

        if ($this->Purchase_request_model->delete_header($id)) {
            $this->session->set_flashdata('success', lang_line('purchase_request_msg_delete_success', 'Purchase Request berhasil dihapus.'));
        } else {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_delete_failed', 'Gagal menghapus PR. Pastikan status Draft dan tidak ada item.'));
        }
        redirect('purchase_request');
    }

    // ===================== ITEM CRUD (AJAX) =====================

    public function add_single_item($pr_id) {
        if (!check_access('purchase_request', 'edit')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        }

        $item_str = $this->input->post('item_id');
        if (!$item_str || strpos($item_str, '-') === false) {
            return $this->_json(['success' => false, 'message' => 'Item tidak valid']);
        }

        list($item_type, $item_id) = explode('-', $item_str, 2);
        $item_id = (int)$item_id;
        $qty     = (float)($this->input->post('qty') ?: 1);

        error_log('[PR Add Item] Called - PR ID: ' . $pr_id . ', Item: ' . $item_type . '-' . $item_id . ', Qty: ' . $qty);

        $existing = $this->Purchase_request_model->check_existing_item($pr_id, $item_id, $item_type);
        if ($existing) {
            $new_qty = (float)$existing->qty + $qty;
            $this->Purchase_request_model->update_item_qty($existing->id, $new_qty);
            error_log('[PR Add Item] Updated existing item - Detail ID: ' . $existing->id . ', New Qty: ' . $new_qty);
            return $this->_json(['success' => true, 'message' => 'Qty item diperbarui.', 'detail_id' => $existing->id, 'updated' => true, 'new_qty' => $new_qty]);
        }

        $insert = [
            'pr_header_id' => $pr_id,
            'item_id'      => $item_id,
            'item_type'    => $item_type,
            'qty'          => $qty,
            'description'  => $this->input->post('description'),
            'remarks'      => $this->input->post('remarks'),
        ];

        $new_id = $this->Purchase_request_model->insert_item($insert);
        if ($new_id) {
            error_log('[PR Add Item] New item added - Detail ID: ' . $new_id);
            return $this->_json(['success' => true, 'message' => 'Item berhasil ditambahkan.', 'detail_id' => $new_id, 'updated' => false]);
        }
        error_log('[PR Add Item] Failed to insert item');
        return $this->_json(['success' => false, 'message' => 'Gagal menambahkan item.']);
    }

    public function update_item() {
        if (!check_access('purchase_request', 'edit')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        }

        $detail_id = (int)$this->input->post('detail_id');
        $qty       = (float)$this->input->post('qty');

        if ($qty <= 0) return $this->_json(['success' => false, 'message' => 'Qty harus > 0']);

        error_log('[PR Update Item] Detail ID: ' . $detail_id . ', Qty: ' . $qty);

        $this->Purchase_request_model->update_item($detail_id, [
            'qty'         => $qty,
            'description' => $this->input->post('description'),
            'remarks'     => $this->input->post('remarks'),
        ]);
        error_log('[PR Update Item] Item updated successfully');
        return $this->_json(['success' => true, 'message' => 'Item diperbarui.']);
    }

    public function delete_item($detail_id) {
        if (!check_access('purchase_request', 'edit')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        }
        if ($this->Purchase_request_model->delete_item($detail_id)) {
            return $this->_json(['success' => true, 'message' => 'Item dihapus.']);
        }
        return $this->_json(['success' => false, 'message' => 'Gagal menghapus item.']);
    }

    public function get_ipo_breakdown($detail_id) {
        $detail_id = (int)$detail_id;
        if (!$detail_id) return $this->_json(['success' => false, 'message' => 'ID tidak valid']);

        $refs = $this->Purchase_request_model->get_ipo_refs_by_detail_id($detail_id);

        // Lazy backfill: data lama yang belum punya refs di tabel pr_detail_ipo_refs
        if (empty($refs)) {
            $detail = $this->Purchase_request_model->get_detail_by_id($detail_id);
            if ($detail && !empty($detail->fg_id) && !empty($detail->ipo_references)) {
                $refs = $this->_backfill_ipo_refs($detail);
            }
        }

        $data = [];
        foreach ($refs as $r) {
            $data[] = [
                'sc_number' => $r->sc_number,
                'qty'       => (float)$r->qty_contribution,
            ];
        }
        return $this->_json(['success' => true, 'refs' => $data]);
    }

    private function _backfill_ipo_refs($detail) {
        $sc_numbers = array_filter(array_map('trim', explode(',', $detail->ipo_references)));
        if (empty($sc_numbers)) return [];

        // Ambil semua BOM FG ini, cari qty untuk material ini
        $bom_items = $this->Bom_model->get_bom_by_fg($detail->fg_id);
        $bom_qty = 0;
        foreach ($bom_items as $b) {
            if ((int)$b->material_id === (int)$detail->item_id && $b->item_type === $detail->item_type) {
                $bom_qty = (float)$b->qty;
                break;
            }
        }

        foreach ($sc_numbers as $sc_number) {
            $sc = $this->db->where('sc_number', trim($sc_number))->get('sales_contracts')->row();
            if (!$sc) continue;
            $qty_contribution = $bom_qty > 0 ? $bom_qty * (float)$sc->po_qty : (float)$detail->qty / max(count($sc_numbers), 1);
            $this->Purchase_request_model->upsert_ipo_ref($detail->id, $sc->id, $sc->sc_number, $qty_contribution);
        }

        return $this->Purchase_request_model->get_ipo_refs_by_detail_id($detail->id);
    }

    public function bulk_delete_items($pr_id) {
        if (!check_access('purchase_request', 'edit')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        }

        $header = $this->Purchase_request_model->get_header_by_id($pr_id);
        if (!$header || $header->status !== 'Draft') {
            return $this->_json(['success' => false, 'message' => 'PR tidak dapat diedit dalam status ini']);
        }

        $ids = $this->input->post('ids');
        if (empty($ids) || !is_array($ids)) {
            return $this->_json(['success' => false, 'message' => 'Tidak ada item yang dipilih']);
        }

        $ids = array_filter(array_map('intval', $ids));
        if (empty($ids)) {
            return $this->_json(['success' => false, 'message' => 'ID item tidak valid']);
        }

        $deleted = 0;
        foreach ($ids as $id) {
            if ($this->Purchase_request_model->delete_item($id)) {
                $deleted++;
            }
        }

        return $this->_json(['success' => true, 'message' => $deleted . ' item berhasil dihapus.', 'deleted' => $deleted]);
    }

    public function search_items() {
        $term = $this->input->get('term') ?: $this->input->post('term');
        $results = $this->Purchase_request_model->search_items($term);
        $out = [];
        foreach ($results as $item) {
            $out[] = [
                'id'   => $item->type . '-' . $item->id,
                'text' => '[' . strtoupper($item->type) . '] ' . $item->code . ' - ' . $item->name,
                'code' => $item->code,
                'name' => $item->name,
                'type' => $item->type,
                'unit' => $item->unit,
            ];
        }
        $this->output->set_content_type('application/json')->set_output(json_encode(['results' => $out]));
    }

    // ===================== GET PR ITEMS (FOR AJAX RENDERING) =====================

    public function get_pr_items($pr_id) {
        if (!check_access('purchase_request', 'view')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        }

        $details = $this->Purchase_request_model->get_details_by_id($pr_id);
        if (empty($details)) {
            return $this->_json(['success' => true, 'items' => []]);
        }

        $items = [];
        foreach ($details as $d) {
            $items[] = [
                'id'              => $d->id,
                'item_id'         => $d->item_id,
                'item_type'       => $d->item_type,
                'item_code'       => $d->item_code,
                'item_name'       => $d->item_name,
                'item_unit'       => $d->item_unit,
                'qty'             => $d->qty,
                'description'     => $d->description,
                'remarks'         => $d->remarks,
                'sc_number'       => $d->sc_number,
            ];
        }

        return $this->_json(['success' => true, 'items' => $items]);
    }

    // ===================== BOM LOADING =====================

    public function load_bom_from_sc($pr_id) {
        if (!check_access('purchase_request', 'edit')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        }

        $sc_id = $this->input->post('sc_id');
        if (!$sc_id) return $this->_json(['success' => false, 'message' => 'Sales Contract tidak valid']);

        error_log('[Load BOM] Starting - PR ID: ' . $pr_id . ', SC ID: ' . $sc_id);

        $sc_details = $this->Sc_model->get_items_by_contract($sc_id);
        if (empty($sc_details)) {
            error_log('[Load BOM] No SC details found');
            return $this->_json(['success' => false, 'message' => 'Sales Contract tidak memiliki item']);
        }

        $inserted = 0;
        $updated  = 0;
        $errors   = [];

        foreach ($sc_details as $sc_item) {
            if ($sc_item->item_type !== 'finished') continue;

            $fg_id    = $sc_item->fg_id;
            $sc_qty   = (float)$sc_item->qty;
            $bom_items = $this->Bom_model->get_bom_by_fg($fg_id);

            if (empty($bom_items)) {
                $errors[] = 'FG ' . $sc_item->nama_barang . ' tidak memiliki BOM';
                continue;
            }

            foreach ($bom_items as $bom) {
                $calc_qty = (float)$bom->qty * $sc_qty;
                $existing = $this->Purchase_request_model->check_existing_item($pr_id, $bom->material_id, $bom->item_type);

                if ($existing) {
                    $this->Purchase_request_model->update_item_qty($existing->id, $existing->qty + $calc_qty);
                    $updated++;
                } else {
                    $this->Purchase_request_model->insert_item([
                        'pr_header_id'     => $pr_id,
                        'item_id'          => $bom->material_id,
                        'item_type'        => $bom->item_type,
                        'sales_contract_id'=> $sc_id,
                        'qty'              => $calc_qty,
                        'remarks'          => 'Auto dari BOM FG: ' . ($sc_item->nama_barang ?? ''),
                    ]);
                    $inserted++;
                }
            }
        }

        error_log('[Load BOM] Complete - Inserted: ' . $inserted . ', Updated: ' . $updated . ', Errors: ' . count($errors));

        $msg = $inserted > 0 ? "$inserted item baru ditambahkan. " : '';
        $msg .= $updated > 0  ? "$updated item qty diperbarui. " : '';
        $msg .= !empty($errors) ? implode(', ', $errors) : '';

        if ($inserted + $updated > 0) {
            error_log('[Load BOM] Success - returning true with items');
            
            // Fetch updated items to return in response
            $updated_details = $this->Purchase_request_model->get_details_by_id($pr_id);
            $items = [];
            foreach ($updated_details as $d) {
                $items[] = [
                    'id'          => $d->id,
                    'item_id'     => $d->item_id,
                    'item_type'   => $d->item_type,
                    'item_code'   => $d->item_code,
                    'item_name'   => $d->item_name,
                    'item_unit'   => $d->item_unit,
                    'qty'         => $d->qty,
                    'description' => $d->description,
                    'remarks'     => $d->remarks,
                    'sc_number'   => $d->sc_number,
                    'fg_id'       => $d->fg_id ?? null,
                    'fg_code'     => $d->fg_code ?? null,
                    'fg_name'     => $d->fg_name ?? null,
                ];
            }

            return $this->_json(['success' => true, 'message' => trim($msg), 'items' => $items]);
        }
        return $this->_json(['success' => false, 'message' => trim($msg) ?: 'Tidak ada material yang ditambahkan.']);
    }

    public function load_bom_from_model($pr_id) {
        // Anti-timeout: operasi ini bisa berat (per SC × per BOM item × beberapa query).
        // Server tetap selesai meski koneksi user putus.
        @set_time_limit(0);
        @ignore_user_abort(true);

        if (!check_access('purchase_request', 'edit')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        }

        $fg_id = (int)$this->input->post('fg_id');
        if (!$fg_id) return $this->_json(['success' => false, 'message' => 'Model tidak valid']);

        $header = $this->Purchase_request_model->get_header_by_id($pr_id);
        if (!$header || $header->status !== 'Draft') {
            return $this->_json(['success' => false, 'message' => 'PR tidak dapat diedit dalam status ini']);
        }

        $open_scs = $this->Sc_model->get_open_scs_by_fg($fg_id);
        if (empty($open_scs)) {
            return $this->_json(['success' => false, 'message' => 'Tidak ada Sales Contract dengan status Open untuk model ini']);
        }

        $fg = $this->db->where('id', $fg_id)->get('finished_goods')->row();
        if (!$fg) return $this->_json(['success' => false, 'message' => 'Model tidak ditemukan']);
        $fg_name = $fg->nama_barang;

        $bom_items = $this->Bom_model->get_bom_by_fg($fg_id);
        if (empty($bom_items)) {
            return $this->_json(['success' => false, 'message' => 'Model ' . $fg_name . ' tidak memiliki BOM aktif']);
        }

        $inserted   = 0;
        $updated    = 0;
        $sc_names   = implode(', ', array_map(fn($s) => $s->sc_number, $open_scs));
        $total_sc_qty = array_sum(array_map(fn($s) => (float)$s->qty, $open_scs));

        // Bungkus dalam transaksi: kalau timeout / error di tengah, di-rollback supaya
        // tidak meninggalkan data setengah jadi.
        $this->db->trans_start();

        foreach ($open_scs as $sc) {
            $sc_qty = (float)$sc->qty;
            foreach ($bom_items as $bom) {
                $calc_qty = (float)$bom->qty * $sc_qty;

                // Cek global per material (bukan per SC) — 1 row per material, qty akumulasi
                $existing = $this->Purchase_request_model->check_existing_item($pr_id, $bom->material_id, $bom->item_type);

                if ($existing) {
                    $this->Purchase_request_model->update_item_qty($existing->id, $existing->qty + $calc_qty);
                    $this->Purchase_request_model->append_ipo_reference($existing->id, $sc->sc_number);
                    $this->Purchase_request_model->upsert_ipo_ref($existing->id, $sc->id, $sc->sc_number, $calc_qty);
                    $updated++;
                } else {
                    $new_id = $this->Purchase_request_model->insert_item([
                        'pr_header_id'   => $pr_id,
                        'item_id'        => $bom->material_id,
                        'item_type'      => $bom->item_type,
                        'fg_id'          => $fg_id,
                        'ipo_references' => $sc->sc_number,
                        'qty'            => $calc_qty,
                        'remarks'        => 'Auto dari Model: ' . $fg_name,
                    ]);
                    if ($new_id) {
                        $this->Purchase_request_model->upsert_ipo_ref($new_id, $sc->id, $sc->sc_number, $calc_qty);
                    }
                    $inserted++;
                }
            }
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return $this->_json(['success' => false, 'message' => 'Gagal menyimpan BOM ke database.']);
        }

        $msg = $inserted > 0 ? "$inserted item baru ditambahkan. " : '';
        $msg .= $updated > 0  ? "$updated item qty diperbarui. " : '';

        if ($inserted + $updated > 0) {
            $updated_details = $this->Purchase_request_model->get_details_by_id($pr_id);
            $items = [];
            foreach ($updated_details as $d) {
                $items[] = [
                    'id'          => $d->id,
                    'item_id'     => $d->item_id,
                    'item_type'   => $d->item_type,
                    'item_code'   => $d->item_code ?? '',
                    'item_name'   => $d->item_name ?? '',
                    'item_unit'   => $d->item_unit ?? '',
                    'qty'         => $d->qty,
                    'description' => $d->description ?? '',
                    'remarks'     => $d->remarks ?? '',
                    'sc_number'   => $d->sc_number ?? '',
                    'fg_id'       => $d->fg_id ?? null,
                    'fg_code'     => $d->fg_code ?? null,
                    'fg_name'     => $d->fg_name ?? null,
                ];
            }
            return $this->_json(['success' => true, 'message' => trim($msg) . ' (SC: ' . $sc_names . ', total ' . number_format($total_sc_qty) . ' pcs)', 'items' => $items]);
        }
        return $this->_json(['success' => false, 'message' => 'Tidak ada material yang ditambahkan.']);
    }

    // ===================== APPROVAL FLOW =====================

    public function submit_pr($id) {
        if (!check_access('purchase_request', 'edit')) show_error(lang_line('purchase_request_msg_permission_denied', 'Izin ditolak.'), 403);

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->status !== 'Draft') {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_invalid_status_submit', 'PR tidak bisa disubmit dalam status ini.'));
            return redirect('purchase_request/detail/' . $id);
        }

        $details = $this->Purchase_request_model->get_details_by_id($id);
        if (empty($details)) {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_requires_items', 'PR harus memiliki minimal 1 item sebelum disubmit.'));
            return redirect('purchase_request/detail/' . $id);
        }

        if ($this->Purchase_request_model->update_status($id, 'Submitted')) {
            $this->session->set_flashdata('success', lang_line('purchase_request_msg_submit_success', 'PR berhasil disubmit ke atasan untuk disetujui.'));
        } else {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_submit_failed', 'Gagal submit PR.'));
        }
        redirect('purchase_request/detail/' . $id);
    }

    public function unsubmit_pr($id) {
        if (!check_access('purchase_request', 'edit')) show_error(lang_line('purchase_request_msg_permission_denied', 'Izin ditolak.'), 403);

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->status !== 'Submitted') {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_invalid_status_unsubmit', 'PR tidak bisa dibatalkan submitnya.'));
            return redirect('purchase_request/detail/' . $id);
        }

        if ($this->Purchase_request_model->update_status($id, 'Draft')) {
            $this->session->set_flashdata('success', lang_line('purchase_request_msg_unsubmit_success', 'PR dikembalikan ke Draft.'));
        } else {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_unsubmit_failed', 'Gagal membatalkan submit PR.'));
        }
        redirect('purchase_request/detail/' . $id);
    }

    public function approve_pr($id) {
        if (!check_access('purchase_request', 'approve')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        }

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->status !== 'Submitted') {
            return $this->_json(['success' => false, 'message' => 'PR tidak bisa disetujui dalam status ini']);
        }

        $user_id = $this->session->userdata('user_id');
        if ($this->Purchase_request_model->update_status($id, 'Approved', $user_id)) {
            return $this->_json(['success' => true, 'message' => 'PR berhasil disetujui.']);
        }
        return $this->_json(['success' => false, 'message' => 'Gagal menyetujui PR.']);
    }

    public function reject_pr($id) {
        if (!check_access('purchase_request', 'approve')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        }

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->status !== 'Submitted') {
            return $this->_json(['success' => false, 'message' => 'PR tidak bisa ditolak dalam status ini']);
        }

        $notes   = $this->input->post('rejection_notes');
        $user_id = $this->session->userdata('user_id');

        if ($this->Purchase_request_model->update_status($id, 'Rejected', $user_id, $notes)) {
            return $this->_json(['success' => true, 'message' => 'PR ditolak.']);
        }
        return $this->_json(['success' => false, 'message' => 'Gagal menolak PR.']);
    }

    // ===================== CREATE PO FROM PR =====================

    public function create_po_from_pr($id) {
        if (!check_access('purchase_order', 'add')) show_error(lang_line('purchase_request_msg_no_permission_po', 'Anda tidak memiliki izin membuat PO.'), 403);

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->status !== 'Approved') {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_only_approved_po', 'Hanya PR yang sudah Approved yang bisa dikonversi ke PO.'));
            return redirect('purchase_request/detail/' . $id);
        }

        $supplier_id = $this->input->post('supplier_id');
        if (!$supplier_id) {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_supplier_required', 'Supplier harus dipilih sebelum membuat PO.'));
            return redirect('purchase_request/detail/' . $id);
        }

        $this->load->model('Po_model');

        // Item yang dicentang di modal. Kalau form tidak mengirim pilihan sama
        // sekali, ambil semua baris PR — qty tetap diklamp ke sisa yang belum
        // masuk PO lain, jadi tidak bisa dobel pesan.
        $selected = $this->input->post('pr_detail_ids');
        if (empty($selected)) {
            $selected = array_column($this->Purchase_request_model->get_details_by_id($id), 'id');
        }
        $rows = Po_model::build_pull_rows($selected, $this->input->post('pull_qty'));

        // Get PPN flag and rate from form
        $include_ppn = $this->input->post('include_ppn') ? 1 : 0;
        $ppn_rate = $include_ppn ? (float)($this->input->post('ppn_rate') ?? 10) : 0;

        $result = $this->Po_model->create_po_from_pr($id, [
            'supplier_id'  => $supplier_id,
            'po_date'      => date('Y-m-d'),
            'type'         => $header->type ?? 'produksi',
            'currency_code'=> 'IDR',
            'exchange_rate'=> 1,
            'has_ppn'      => $include_ppn,
            'ppn_rate'     => $ppn_rate,
            'remarks'      => 'Dibuat dari PR: ' . $header->pr_number,
            'created_by'   => $this->session->userdata('user_id'),
        ], $rows);

        if (empty($result['success'])) {
            $this->session->set_flashdata('error', $result['message']);
            return redirect('purchase_request/detail/' . $id);
        }

        $message = 'PO dibuat dari PR ' . $header->pr_number . '. ' . $result['message'];
        if ($include_ppn) {
            $message .= ' (PPN ' . number_format($ppn_rate, 2) . '% akan ditambahkan)';
        }
        $this->session->set_flashdata('success', $message);
        redirect('purchase_order/detail/' . $result['po_id']);
    }

    // ===================== EXPORT =====================

    public function export_list_excel()
    {
        if (!check_access('purchase_request', 'view')) show_error(lang_line('purchase_request_msg_permission_denied', 'Izin Ditolak.'), 403);

        $filters = [
            'status' => $this->input->get('status'),
            'search' => $this->input->get('search'),
            'type'   => 'produksi',
            'requester_id' => $this->session->userdata('user_id'),
        ];
        $prs = $this->Purchase_request_model->get_all($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'DAFTAR PURCHASE REQUEST');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'PR Number');
        $sheet->setCellValue('C3', 'Tanggal');
        $sheet->setCellValue('D3', 'Requester');
        $sheet->setCellValue('E3', 'Supplier');
        $sheet->setCellValue('F3', 'Keperluan / Purpose');
        $sheet->setCellValue('G3', 'Status');

        $sheet->getStyle('A3:G3')->getFont()->setBold(true);
        $sheet->getStyle('A3:G3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A3:G3')->getFont()->getColor()->setARGB('FFFFFFFF');

        $row = 4;
        $no  = 1;
        foreach ($prs as $pr) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $pr->pr_number);
            $sheet->setCellValue('C'.$row, date('d/m/Y', strtotime($pr->pr_date)));
            $sheet->setCellValue('D'.$row, $pr->requester_name ?? '-');
            $sheet->setCellValue('E'.$row, $pr->nama_supplier ?? '-');
            $sheet->setCellValue('F'.$row, $pr->purpose ?? '-');
            $sheet->setCellValue('G'.$row, $pr->status);
            $row++;
        }

        foreach (range('A','G') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $filename = 'PR_List_' . date('Ymd') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function export_list_pdf()
    {
        if (!check_access('purchase_request', 'view')) show_error(lang_line('purchase_request_msg_permission_denied', 'Izin Ditolak.'), 403);

        $filters = [
            'status' => $this->input->get('status'),
            'search' => $this->input->get('search'),
            'type'   => 'produksi',
            'requester_id' => $this->session->userdata('user_id'),
        ];
        $prs = $this->Purchase_request_model->get_all($filters);

        $status_colors = [
            'Draft'     => 'orange',
            'Submitted' => 'blue',
            'Approved'  => 'green',
            'Rejected'  => 'red',
        ];

        $rows = '';
        $no   = 1;
        foreach ($prs as $pr) {
            $color = $status_colors[$pr->status] ?? '#333';
            $rows .= '<tr>
                <td style="text-align:center">' . $no++ . '</td>
                <td>' . htmlspecialchars($pr->pr_number) . '</td>
                <td>' . date('d/m/Y', strtotime($pr->pr_date)) . '</td>
                <td>' . htmlspecialchars($pr->requester_name ?? '-') . '</td>
                <td>' . htmlspecialchars($pr->nama_supplier ?? '-') . '</td>
                <td>' . htmlspecialchars($pr->purpose ?? '-') . '</td>
                <td style="text-align:center;color:' . $color . ';font-weight:bold">' . htmlspecialchars($pr->status) . '</td>
            </tr>';
        }

        $html = '<style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            h2 { text-align: center; margin-bottom: 6px; font-size: 14px; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #4472C4; color: #fff; padding: 5px 4px; text-align: left; }
            td { padding: 4px; border-bottom: 1px solid #ddd; }
            tr:nth-child(even) td { background: #f5f5f5; }
        </style>
        <h2>DAFTAR PURCHASE REQUEST</h2>
        <p style="text-align:center;font-size:9px">Dicetak: ' . date('d/m/Y H:i') . '</p>
        <table>
            <thead><tr>
                <th width="4%">No</th>
                <th width="14%">PR Number</th>
                <th width="10%">Tanggal</th>
                <th width="14%">Requester</th>
                <th width="16%">Supplier</th>
                <th width="30%">Keperluan</th>
                <th width="12%">Status</th>
            </tr></thead>
            <tbody>' . $rows . '</tbody>
        </table>';

        $temp_dir = APPPATH . 'cache/pdf_temp/';
        if (!is_dir($temp_dir)) { @mkdir($temp_dir, 0777, true); }
        $mpdf = new Mpdf(['tempDir' => $temp_dir, 'mode' => 'utf-8', 'format' => 'A4-L']);
        $mpdf->WriteHTML($html);
        $mpdf->Output('PR_List_' . date('Ymd') . '.pdf', 'D');
        exit;
    }

    public function export_detail_excel($id)
    {
        if (!check_access('purchase_request', 'view')) show_error(lang_line('purchase_request_msg_permission_denied', 'Izin Ditolak.'), 403);
        if (empty($id) || !is_numeric($id)) show_404();

        $header  = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Purchase_request_model->get_details_by_id($id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'PURCHASE REQUEST');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'PR Number:');   $sheet->setCellValue('B3', $header->pr_number);
        $sheet->setCellValue('E3', 'Tanggal:');      $sheet->setCellValue('F3', date('d-m-Y', strtotime($header->pr_date)));
        $sheet->setCellValue('A4', 'Requester:');    $sheet->setCellValue('B4', $header->requester_name ?? '-');
        $sheet->setCellValue('E4', 'Status:');       $sheet->setCellValue('F4', $header->status);
        $sheet->setCellValue('A5', 'Supplier:');     $sheet->setCellValue('B5', $header->nama_supplier ?? '-');
        $sheet->setCellValue('A6', 'Keperluan:');    $sheet->setCellValue('B6', $header->purpose ?? '-');
        $sheet->mergeCells('B6:G6');
        $sheet->setCellValue('A7', 'Dibuat Oleh:');  $sheet->setCellValue('B7', $header->requester_name ?? '-');
        $sheet->setCellValue('E7', 'Disetujui Oleh:'); $sheet->setCellValue('F7', $header->approver_name ?? '-');

        $startRow = 9;
        $sheet->setCellValue('A'.$startRow, 'No');
        $sheet->setCellValue('B'.$startRow, 'Kode Item');
        $sheet->setCellValue('C'.$startRow, 'Nama Item');
        $sheet->setCellValue('D'.$startRow, 'Satuan');
        $sheet->setCellValue('E'.$startRow, 'Qty');
        $sheet->setCellValue('F'.$startRow, 'Deskripsi');
        $sheet->setCellValue('G'.$startRow, 'Sales Contract');

        $sheet->getStyle('A'.$startRow.':G'.$startRow)->getFont()->setBold(true);
        $sheet->getStyle('A'.$startRow.':G'.$startRow)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A'.$startRow.':G'.$startRow)->getFont()->getColor()->setARGB('FFFFFFFF');

        $rowNum = $startRow + 1;
        $no = 1;
        foreach ($details as $d) {
            $sheet->setCellValue('A'.$rowNum, $no++);
            $sheet->setCellValue('B'.$rowNum, $d->item_code ?? '-');
            $sheet->setCellValue('C'.$rowNum, $d->item_name ?? '-');
            $sheet->setCellValue('D'.$rowNum, $d->item_unit ?? '-');
            $sheet->setCellValue('E'.$rowNum, $d->qty);
            $sheet->setCellValue('F'.$rowNum, $d->description ?? '-');
            $sheet->setCellValue('G'.$rowNum, $d->sc_number ?? '-');
            $rowNum++;
        }

        $sheet->getStyle('A'.$startRow.':G'.($rowNum-1))->getBorders()
            ->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        foreach (range('A','G') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $filename = str_replace(['/', '\\'], '-', $header->pr_number) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function export_detail_pdf($id)
    {
        if (!check_access('purchase_request', 'view')) show_error(lang_line('purchase_request_msg_permission_denied', 'Izin Ditolak.'), 403);
        if (empty($id) || !is_numeric($id)) show_404();

        $header  = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Purchase_request_model->get_details_by_id($id);

        $items = [];
        $no = 1;
        foreach ($details as $d) {
            $items[] = [
                'no'         => $no++,
                'item_code'  => $d->item_code,
                'item_name'  => $d->item_name,
                'qty'        => (float)$d->qty,
                'unit'       => $d->item_unit ?? '',
                'unit_price' => 0,
                'amount'     => 0,
                'notes'      => $d->description ?? '',
            ];
        }

        $is_non_produksi = (($header->type ?? 'produksi') === 'non_produksi');
        $form_label = $is_non_produksi ? 'PURCHASE REQUEST - NON PRODUKSI' : 'PURCHASE REQUEST - PRODUKSI';

        // Signatures ikut pola 3-stage IRF: Applicant (creator), Head of Dept,
        // Endorsement, Approve by (final). Receiver dihilangkan.
        $data = [
            'irf_number'    => $header->pr_number,
            'request_date'  => $header->pr_date,
            'department'    => resolve_department_name($header->department ?? ''),
            'purpose'       => $header->purpose ?? $header->remarks ?? '',
            'items'         => $items,
            'form_title'    => $form_label,
            'form_subtitle' => '采购申请单',
            'number_label'  => 'PR',
            // Approval PR 1 tahap (2026-11-05): cuma Pemohon + Penyetuju.
            'signatures'    => [
                build_signature_slot(
                    $header->requester_id ?? ($header->created_by ?? null),
                    $header->submitted_at ?? ($header->created_at ?? null)
                ),
                build_signature_slot(
                    $header->final_approved_by ?? ($header->approved_by ?? null),
                    $header->final_approved_at ?? ($header->approved_at ?? null)
                ),
            ],
            'signature_labels' => [
                ['Applicant',     '申請人'],
                ['Approve by',    '核准人'],
            ],
        ];

        $filename = 'PR_' . str_replace([':', ' ', '/', '\\'], ['', '_', '-', '-'], $header->pr_number) . '.pdf';
        render_document_pdf($data, $filename);
    }

    // ===================== HELPER =====================

    // ---------- Tarik dari Material Request (2026-11-05) ----------
    // Lihat Purchase_request_model::get_pullable_rows() / pull_rows_into_pr().

    /** JSON: baris Material Request Approved yang masih butuh PR. */
    public function pull_list($pr_id) {
        if (!check_access('purchase_request', 'edit')) return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        $header = $this->Purchase_request_model->get_header_by_id($pr_id);
        if (!$header || $header->type !== 'produksi') return $this->_json(['success' => false, 'message' => 'PR tidak ditemukan']);
        if ($header->status !== 'Draft') {
            return $this->_json(['success' => false, 'message' => 'Item hanya bisa ditarik saat PR berstatus Draft.']);
        }
        $rows = [];
        foreach ($this->Purchase_request_model->get_pullable_rows('mr') as $r) {
            $rows[] = [
                'id'              => (int) $r->mr_detail_id,
                'request_number'  => $r->request_number,
                'department'      => $r->department,
                'item_code'       => $r->item_code,
                'item_name'       => $r->item_name,
                'item_unit'       => $r->item_unit,
                'category'        => '',
                'qty_outstanding' => (float) $r->qty_outstanding,
                'qty_from_stock'  => (float) ($r->qty_from_stock ?? 0),
                'qty_on_pr'       => (float) $r->qty_on_pr,
                'qty_pr'          => (float) $r->qty_pr,
            ];
        }
        return $this->_json(['success' => true, 'rows' => $rows]);
    }

    /** POST rows[<mr_detail_id>] = qty -> masukkan ke PR Material (Draft). */
    public function pull_items($pr_id) {
        if (!check_access('purchase_request', 'edit')) return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        $header = $this->Purchase_request_model->get_header_by_id($pr_id);
        if (!$header || $header->type !== 'produksi') return $this->_json(['success' => false, 'message' => 'PR tidak ditemukan']);
        $rows = $this->input->post('rows');
        $res  = $this->Purchase_request_model->pull_rows_into_pr($pr_id, 'mr', is_array($rows) ? $rows : []);
        if ($res['success']) {
            add_log('UPDATE', 'Purchase Request', 'Tarik dari Material Request ke ' . $header->pr_number . ': ' . implode('; ', $res['added']));
        }
        return $this->_json($res);
    }

    private function _json($data) {
        // Bersihkan semua output buffer agar tidak ada notice/warning yang nyelip & korup JSON
        while (ob_get_level()) { ob_end_clean(); }

        // Cegah PHP nge-display error di output (notice / deprecated dll) yang bisa bikin
        // jQuery dataType=json gagal parse → masuk ke error callback ("Gagal menghubungi server")
        @ini_set('display_errors', '0');

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('X-Content-Type-Options: nosniff');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}
