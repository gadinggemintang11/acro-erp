<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Material_outstanding extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Material_outstanding_model', 'Purchase_request_model']);
        $this->load->helper(['url', 'form']);
        $this->load->library('session');
    }

    public function index() {
        if (!check_access('material_outstanding', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        $filters = [
            'search'     => $this->input->get('search'),
            'department' => $this->input->get('department'),
            'date_from'  => $this->input->get('date_from'),
            'date_to'    => $this->input->get('date_to'),
        ];

        $data = [
            'title'       => lang_line('title_mo_index'),
            'records'     => $this->Material_outstanding_model->get_grouped_by_item($filters),
            'departments' => $this->Material_outstanding_model->get_departments(),
            'filters'     => $filters,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_outstanding/index', $data);
        $this->load->view('templates/footer');
    }

    public function detail($item_id, $item_type = 'material') {
        if (!check_access('material_outstanding', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        if (empty($item_id) || !is_numeric($item_id)) { show_404(); }
        if (!in_array($item_type, ['material', 'semifinished'])) { show_404(); }

        $rows = $this->Material_outstanding_model->get_breakdown_by_item($item_id, $item_type);

        if (empty($rows)) {
            $this->session->set_flashdata('error', 'Tidak ada outstanding untuk item ini.');
            redirect('material_outstanding');
            return;
        }

        $first = $rows[0];
        $total_outstanding = 0;
        foreach ($rows as $r) { $total_outstanding += (float)$r->qty_outstanding; }

        $data = [
            'title'             => sprintf(lang_line('title_mo_detail'), $first->item_name),
            'item_id'           => (int)$item_id,
            'item_type'         => $item_type,
            'item_code'         => $first->item_code,
            'item_name'         => $first->item_name,
            'item_unit'         => $first->item_unit,
            'rows'              => $rows,
            'total_outstanding' => $total_outstanding,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_outstanding/detail', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Create a Draft PR from selected outstanding MR detail rows.
     * POST: mr_detail_ids[] - array of mr_details.id
     */
    public function create_pr() {
        if (!check_access('material_outstanding', 'view') || !check_access('purchase_request', 'add')) {
            show_error('Anda tidak memiliki izin untuk membuat Purchase Request.', 403);
            return;
        }

        $mr_detail_ids = $this->input->post('mr_detail_ids');
        if (empty($mr_detail_ids) || !is_array($mr_detail_ids)) {
            $this->session->set_flashdata('error', 'Pilih minimal satu baris outstanding untuk dibuatkan PR.');
            redirect($this->input->post('redirect_to') ?: 'material_outstanding');
            return;
        }

        // Qty PR = outstanding - yang sudah ada di PR aktif - yang bisa
        // dipenuhi stok gudang (user 2026-09-21: "logikanya harusnya sama
        // dengan general"). Lihat Material_outstanding_model::get_pr_need_map().
        $map = $this->Material_outstanding_model->get_pr_need_map($mr_detail_ids);
        if (empty($map)) {
            $this->session->set_flashdata('error', 'Baris outstanding tidak ditemukan / sudah tidak valid.');
            redirect('material_outstanding');
            return;
        }

        // Aggregate by (item_id, item_type).
        $by_item   = [];
        $mr_numbers = [];
        $first_dept = null;

        $covered  = []; // sisanya sudah tertutup stok / PR sebelumnya
        $deducted = []; // qty PR-nya dikurangi stok / PR sebelumnya
        foreach ($map as $row) {
            $qty = (float)$row->qty_pr;
            if ($qty <= 0) {
                if ((float) $row->qty_outstanding > 0) {
                    $covered[] = $row->item_name . ' (' . $row->request_number . ')';
                }
                continue;
            }
            if ((float) $row->qty_from_stock > 0 || (float) $row->qty_on_pr > 0) {
                $deducted[] = $row->item_name . ': diminta sisa ' . (float) $row->qty_outstanding
                    . ((float) $row->qty_from_stock > 0 ? ', dari stok ' . (float) $row->qty_from_stock : '')
                    . ((float) $row->qty_on_pr > 0 ? ', sudah di PR lain ' . (float) $row->qty_on_pr : '')
                    . ', jadi PR ' . $qty;
            }

            $key = $row->item_type . ':' . (int)$row->item_id;
            if (!isset($by_item[$key])) {
                $by_item[$key] = [
                    'item_id'   => (int)$row->item_id,
                    'item_type' => $row->item_type,
                    'item_code' => $row->item_code,
                    'item_name' => $row->item_name,
                    'item_unit' => $row->item_unit,
                    'qty'       => 0,
                    'mr_refs'   => [],
                    'mr_links'  => [],
                ];
            }
            $by_item[$key]['qty'] += $qty;
            $by_item[$key]['mr_refs'][] = $row->request_number;
            // Dicatat per mr_detail_id + qty supaya jejaknya struktural di
            // pr_detail_mr_links (dipakai anti-PR-ganda & keterangan nomor PR).
            $by_item[$key]['mr_links'][] = ['mr_detail_id' => (int) $row->mr_detail_id, 'qty' => $qty];
            $mr_numbers[$row->request_number] = true;
            if ($first_dept === null && !empty($row->department)) {
                $first_dept = $row->department;
            }
        }

        if (empty($by_item)) {
            $this->session->set_flashdata('error', !empty($covered)
                ? 'Tidak perlu PR: sisa ' . implode(', ', $covered) . ' sudah bisa dipenuhi dari stok gudang atau sudah ada di PR sebelumnya.'
                : 'Tidak ada qty outstanding > 0 pada baris yang dipilih.');
            redirect($this->input->post('redirect_to') ?: 'material_outstanding');
            return;
        }

        $mr_list = implode(', ', array_keys($mr_numbers));
        $user_id = $this->session->userdata('user_id');
        $now     = date('Y-m-d H:i:s');

        $this->db->trans_start();

        // [TIDAK BERLAKU sejak 2026-11-05 -- PR kini dibuat Draft & wajib approval 1 tahap]
        // Catatan lama: PR ini langsung Approved (skip Submit/HOD/Endorsement/Final) — sama
        // seperti Consumable_outstanding::create_pr() utk IRF. Prinsip yang
        // sudah dikonfirmasi user (ERP Revision Notes 2026-09-02, lihat
        // [[erp_revision_access_and_forms_reference]]): "PR TIDAK PERLU
        // approval sama sekali kalau sumbernya request form (di sini: MRF)
        // yang sudah lolos approval sendiri" — berlaku UMUM utk semua PR,
        // bukan cuma yang dari IRF Consumable. Sebelum fix ini PR dari sini
        // masih dibuat Draft & butuh submit+approve manual lagi — tidak
        // konsisten dgn prinsip di atas. Diperbaiki 2026-09-03, lihat
        // [[revision_irf_auto_pr_at_issuance]].
        $header_data = [
            // Nomor PR memakai departemen MRF sumbernya, bukan departemen user
            // yang menekan tombol -- lihat generate_pr_number().
            'pr_number'          => $this->Purchase_request_model->generate_pr_number('produksi', $first_dept),
            'pr_date'            => date('Y-m-d'),
            'department'         => $first_dept ?: '',
            'type'               => 'produksi',
            'purpose'            => 'Pengadaan untuk outstanding material request',
            'remarks'            => 'Dibuat dari outstanding Material Request. Sumber: ' . $mr_list,
            'requester_id'       => $user_id,
            'created_by'         => $user_id,
            // Sejak 2026-11-05 PR WAJIB di-approve (1 tahap, spt PO) -- dibuat Draft,
            // pembuat memeriksa/menambah item lalu Submit. Dulu langsung 'Approved'.
            'status'             => 'Draft',

        ];

        $pr_id = $this->Purchase_request_model->insert_header($header_data);
        if (!$pr_id) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Gagal membuat header Purchase Request.');
            redirect('material_outstanding');
            return;
        }

        foreach ($by_item as $row) {
            $remarks = 'Outstanding dari: ' . implode(', ', array_unique($row['mr_refs']));
            $pr_detail_id = $this->Purchase_request_model->insert_item([
                'pr_header_id' => $pr_id,
                'item_id'      => $row['item_id'],
                'item_type'    => $row['item_type'],
                'qty'          => $row['qty'],
                'description'  => '',
                'remarks'      => $remarks,
            ]);
            if ($pr_detail_id) {
                foreach ($row['mr_links'] as $link) {
                    $this->db->insert('pr_detail_mr_links', [
                        'pr_detail_id' => (int) $pr_detail_id,
                        'mr_detail_id' => $link['mr_detail_id'],
                        'qty'          => $link['qty'],
                    ]);
                }
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Terjadi kesalahan database saat membuat PR.');
            redirect('material_outstanding');
            return;
        }

        $msg = 'Purchase Request ' . $header_data['pr_number'] . ' dibuat sebagai Draft. Periksa / tambah item, '
             . 'lalu Submit untuk approval sebelum bisa dibuatkan PO.';
        if ($deducted) $msg .= ' Qty disesuaikan — ' . implode('; ', $deducted) . '.';
        if ($covered)  $msg .= ' Tidak di-PR-kan (sudah tertutup stok/PR lain): ' . implode(', ', $covered) . '.';
        $this->session->set_flashdata('success', $msg);
        redirect('purchase_request/detail/' . $pr_id);
    }
}
