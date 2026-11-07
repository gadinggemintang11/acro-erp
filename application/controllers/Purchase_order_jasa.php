<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Purchase_order.php';

/**
 * PO Jasa — SUBCLASS TIPIS dari `Purchase_order` (lihat docblock lengkap di
 * sana, user 2026-09-05). Beda dari Produksi/General: tidak pakai BOM/Sales
 * Contract (Sc_model/Bom_model tidak di-load), item picker dibatasi ke
 * Master Jasa saja, dan punya 1 aksi UNIK `complete_service()` ("Selesaikan
 * Jasa") yang tidak ada padanannya di tipe lain.
 */
class Purchase_order_jasa extends Purchase_order {

    protected $po_type = 'jasa';
    protected $menu_key = 'purchase_order_jasa';
    protected $approval_menu_key = 'purchase_order_approval_jasa';
    protected $module_label = 'Purchase Order Jasa';
    protected $search_item_type = 'jasa';
    protected $completion_label = 'penyelesaian jasa';

    // Item WAJIB ditarik dari PR Jasa (user 2026-09-09) -- lihat
    // Purchase_order::$allow_manual_items.
    protected $allow_manual_items = false;

    protected function _extra_construct_models() {
        return []; // Jasa tidak pakai BOM/Sales Contract sama sekali.
    }

    protected function _extra_detail_data($header, $id) {
        return [
            'service_items' => ($header->status === 'Confirmed') ? $this->Po_model->get_service_completion_items($id) : [],
        ];
    }

    protected function _title_index() {
        return lang_line('title_po_index_jasa', 'Purchase Order Jasa');
    }

    protected function _title_detail() {
        return lang_line('title_po_detail_jasa', 'Detail Purchase Order Jasa');
    }

    /**
     * "Selesaikan Jasa" — tombol di halaman detail PO Jasa (status Confirmed).
     * Menulis penyelesaian ke grn_header/grn_details lewat Po_model, supaya
     * otomatis kebaca AP Accounting lewat jalur GRN_BASED yang sudah ada.
     * UNIK milik Jasa — padanannya di Asset adalah receive_asset().
     */
    public function complete_service($id) {
        if (!check_access($this->menu_key, 'confirm')) { show_error('Izin Ditolak.', 403); return; }
        $id = (int)$id;
        if ($id <= 0) show_404();
        if ($this->input->method() !== 'post') { show_error('Metode Ilegal.', 405); return; }

        $detail_ids = $this->input->post('detail_id') ?? [];
        $qtys       = $this->input->post('qty') ?? [];
        $item_ids   = $this->input->post('item_id') ?? [];
        $prices     = $this->input->post('unit_price') ?? [];

        $items = [];
        foreach ($detail_ids as $i => $detail_id) {
            $qty = (float)($qtys[$i] ?? 0);
            if ($qty <= 0) continue;
            $items[] = [
                'detail_id'  => (int)$detail_id,
                'item_id'    => (int)($item_ids[$i] ?? 0),
                'qty'        => $qty,
                'unit_price' => (float)($prices[$i] ?? 0),
            ];
        }

        $user_id = $this->session->userdata('user_id') ?? null;
        $result = $this->Po_model->complete_service_items($id, $items, $user_id);

        if (!empty($result['success'])) {
            $this->_log('CONFIRM', 'Menyelesaikan jasa untuk PO ID ' . $id);
            $this->session->set_flashdata('success', $result['message']);
        } else {
            $this->session->set_flashdata('error', $result['message'] ?? 'Gagal menyelesaikan jasa.');
        }
        redirect($this->menu_key . '/detail/' . $id);
    }
}
