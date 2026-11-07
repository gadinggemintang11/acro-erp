<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Purchase_order.php';

/**
 * PO Asset — SUBCLASS TIPIS dari `Purchase_order` (lihat docblock lengkap di
 * sana, user 2026-09-05). Beda dari tipe lain: "penerimaan" tidak lewat GRN
 * sama sekali (Asset_register_model::check_if_po_has_asset_register()), dan
 * punya 1 aksi UNIK `receive_asset()` ("Terima Aset", nulis 1 baris per unit
 * fisik ke `asset_register` — padanan `complete_service()` milik PO Jasa).
 */
class Purchase_order_asset extends Purchase_order {

    protected $po_type = 'asset';
    protected $menu_key = 'purchase_order_asset';
    protected $approval_menu_key = 'purchase_order_approval_asset';
    protected $module_label = 'Purchase Order Asset';
    protected $search_item_type = 'asset';
    protected $completion_label = 'pencatatan di Asset Register';

    // Item WAJIB ditarik dari PR Asset (user 2026-09-09) -- lihat
    // Purchase_order::$allow_manual_items.
    protected $allow_manual_items = false;

    protected function _extra_construct_models() {
        return ['Asset_register_model'];
    }

    protected function _has_receipts($id) {
        // Aset tidak lewat GRN, jadi penanda "sudah diterima" adalah baris di
        // asset_register (lihat Po_model::check_if_po_has_asset_register).
        return $this->Po_model->check_if_po_has_asset_register($id);
    }

    protected function _extra_detail_data($header, $id) {
        return [
            'departments'       => $this->Commoncode_model->get_by_group('Department'),
            'receivable_items'  => ($header->status === 'Confirmed') ? $this->Asset_register_model->get_receivable_items($id) : [],
            'registered_assets' => $this->Asset_register_model->get_by_po($id),
        ];
    }

    protected function _title_index() {
        return lang_line('title_po_index_asset', 'Purchase Order Asset');
    }

    protected function _title_detail() {
        return lang_line('title_po_detail_asset', 'Detail Purchase Order Asset');
    }

    /**
     * "Terima Aset" — tombol di detail PO Asset (status Confirmed).
     *
     * Padanan complete_service() milik PO Jasa, tapi menulis ke `asset_register`
     * (1 baris per unit fisik) alih-alih ke grn_header/grn_details. Modul GRN
     * tidak dipakai di instalasi ini, dan satu baris GRN tidak bisa mewakili
     * aset: beli 5 laptop = 5 nomor aset yang disusutkan dan dilepas sendiri.
     */
    public function receive_asset($id) {
        if (!check_access($this->menu_key, 'confirm')) { show_error('Izin Ditolak.', 403); return; }
        $id = (int)$id;
        if ($id <= 0) show_404();
        if ($this->input->method() !== 'post') { show_error('Metode Ilegal.', 405); return; }

        $detail_ids = $this->input->post('detail_id') ?? [];
        $qtys       = $this->input->post('qty') ?? [];
        $tanggals   = $this->input->post('tanggal_perolehan') ?? [];
        $depts      = $this->input->post('department') ?? [];
        $lokasis    = $this->input->post('lokasi') ?? [];
        $pics       = $this->input->post('pic') ?? [];

        $items = [];
        foreach ($detail_ids as $i => $detail_id) {
            $qty = (int)($qtys[$i] ?? 0);
            if ($qty <= 0) continue;
            $items[] = [
                'detail_id'         => (int)$detail_id,
                'qty'               => $qty,
                'tanggal_perolehan' => $tanggals[$i] ?? null,
                'department'        => $depts[$i] ?? null,
                'lokasi'            => $lokasis[$i] ?? null,
                'pic'               => $pics[$i] ?? null,
            ];
        }

        $user_id = $this->session->userdata('user_id') ?? null;
        $result = $this->Asset_register_model->receive_from_po($id, $items, $user_id);

        if (!empty($result['success'])) {
            $this->_log('CONFIRM', 'Menerima ' . $result['created'] . ' unit aset dari PO ID ' . $id);
            $this->session->set_flashdata('success', $result['message']);
        } else {
            $this->session->set_flashdata('error', $result['message'] ?? 'Gagal mencatat penerimaan aset.');
        }
        redirect($this->menu_key . '/detail/' . $id);
    }
}
