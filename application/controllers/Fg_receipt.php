<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Dompdf\Dompdf;
use Dompdf\Options;

class Fg_receipt extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->library('user_agent');
        $this->load->model([
            'Fg_receipt_model',
            'Production_model',
            'FinishedGoods_model',
            'Stock_ledger_model'
        ]);
        $this->load->helper(['url', 'form']);
        $this->load->library('form_validation');
    }

    // ======================================================
    // INDEX
    // ======================================================
    public function index() {
        if (!check_access('fg_receipt', 'view')) { show_error('Tidak ada izin.', 403); }

        $filters = ['search' => $this->input->get('search')];
        $data = [
            'title'    => lang_line('title_fgr_index'),
            'receipts' => $this->Fg_receipt_model->get_all($filters),
            'filters'  => $filters
        ];

        $this->_render('fg_receipt/index', $data);
    }

    // ======================================================
    // RENDER VIEW WRAPPER
    // ======================================================
    private function _render($view, $data = []) {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer');
    }

    // ======================================================
    // AJAX: SEARCH FG (MODEL) — entry point pencarian receipt baru.
    // ======================================================
    public function ajax_search_fg() {
        $term = $this->input->get('q');
        $fgs  = $this->Fg_receipt_model->search_fg_for_receipt($term);
        $results = [];
        foreach ($fgs as $fg) {
            $results[] = [
                'id'      => $fg->fg_id,
                'text'    => htmlspecialchars($fg->fg_code . ' - ' . $fg->fg_name),
                'fg_code' => htmlspecialchars($fg->fg_code),
                'fg_name' => htmlspecialchars($fg->fg_name),
                'uom'     => htmlspecialchars($fg->uom),
            ];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }

    // ======================================================
    // AJAX: GET WO LIST (+ outputs) untuk FG yang dipilih.
    // ======================================================
    public function ajax_get_receivable_wos_by_fg($fg_id) {
        if (empty($fg_id) || !is_numeric($fg_id)) {
            echo json_encode(['success' => false, 'wos' => []]);
            return;
        }
        $wos = $this->Fg_receipt_model->get_receivable_wos_by_fg_id($fg_id);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'wos' => $wos]);
    }

    // ======================================================
    // CREATE FORM (base on FG/model, multi-WO)
    // ======================================================
    public function create() {
        if (!check_access('fg_receipt', 'add')) { show_error('Tidak ada izin.', 403); }

        $data['title'] = 'Buat Penerimaan Barang Jadi Baru (by Model)';
        $this->_render('fg_receipt/create_by_wo', $data);
    }

    // ======================================================
    // STORE NEW RECEIPT (multi-WO per FG)
    // ======================================================
    public function store() {
        if (!check_access('fg_receipt', 'add')) { show_error('Tidak ada izin.', 403); }

        $this->form_validation->set_rules('receipt_date', 'Tanggal Terima', 'required');
        $this->form_validation->set_rules('fg_id',        'FG / Model',     'required|numeric');

        $fg_id   = (int) $this->input->post('fg_id');
        $details = $this->input->post('details') ?: [];

        // Validasi: minimal 1 detail dengan qty > 0
        $valid_details = [];
        foreach ($details as $row) {
            if (!isset($row['qty_received'])) continue;
            $qty = (float) $row['qty_received'];
            if ($qty <= 0) continue;
            // Wajib ada prod_output_id, fg_id, wo, ph
            if (empty($row['prod_output_id']) || empty($row['fg_id']) || empty($row['wo_header_id']) || empty($row['production_header_id'])) {
                continue;
            }
            $valid_details[] = $row;
        }

        if (empty($valid_details)) {
            $this->session->set_flashdata('error', 'Minimal 1 item harus memiliki Qty Diterima > 0.');
            $this->create();
            return;
        }

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            $this->create();
            return;
        }

        // Validasi: semua detail wajib pakai fg_id yang sama dengan header
        foreach ($valid_details as $row) {
            if ((int)$row['fg_id'] !== $fg_id) {
                $this->session->set_flashdata('error', 'Semua item harus FG yang sama dengan model yang dipilih.');
                $this->create();
                return;
            }
        }

        $headerData = [
            'receipt_number' => $this->Fg_receipt_model->generate_receipt_number(),
            'receipt_date'   => $this->input->post('receipt_date'),
            'fg_id'          => $fg_id,
            'received_by'    => $this->session->userdata('user_id') ?? null,
            'remarks'        => $this->input->post('remarks'),
            'status'         => 'Draft',
        ];

        $detailsData = [];
        foreach ($valid_details as $row) {
            $detailsData[] = [
                'prod_output_id'       => (int) $row['prod_output_id'],
                'production_header_id' => (int) $row['production_header_id'],
                'wo_header_id'         => (int) $row['wo_header_id'],
                'sales_contract_id'    => !empty($row['sales_contract_id']) ? (int) $row['sales_contract_id'] : null,
                'fg_id'                => (int) $row['fg_id'],
                'qty_received'         => (float) $row['qty_received'],
                'uom'                  => $row['uom'] ?? null,
                'notes'                => $row['notes'] ?? null,
            ];
        }

        $insertedId = $this->Fg_receipt_model->insert_receipt($headerData, $detailsData);
        if ($insertedId) {
            $this->session->set_flashdata('success', 'Penerimaan FG berhasil disimpan (Status: Draft).');
            redirect('fg_receipt/show/' . $insertedId);
        } else {
            $this->session->set_flashdata('error', 'Gagal menyimpan penerimaan.');
            $this->create();
        }
    }

    // ======================================================
    // SHOW DETAIL
    // ======================================================
    public function show($id) {
        if (!check_access('fg_receipt', 'view')) { show_error('Tidak ada izin.', 403); }
        if (empty($id)) show_404();

        $header  = $this->Fg_receipt_model->get_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Fg_receipt_model->get_details_by_header_id($id);

        $data = [
            'title'           => lang_line('title_fgr_detail'),
            'receipt_header'  => $header,
            'receipt_details' => $details
        ];

        $this->_render('fg_receipt/show', $data);
    }

    // ======================================================
    // EDIT FORM
    // ======================================================
    public function edit($id) {
        if (!check_access('fg_receipt', 'edit')) { show_error('Tidak ada izin.', 403); }
        if (empty($id)) show_404();

        $header = $this->Fg_receipt_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya dokumen Draft yang bisa diedit.');
            redirect('fg_receipt/show/' . $id);
        }

        $details = $this->Fg_receipt_model->get_details_by_header_id($id);
        $data = [
            'title'           => lang_line('title_fgr_edit'),
            'receipt_header'  => $header,
            'receipt_details' => $details
        ];

        $this->_render('fg_receipt/edit', $data);
    }

    // ======================================================
    // UPDATE
    // ======================================================
    public function update($id) {
        if (!check_access('fg_receipt', 'edit')) { show_error('Tidak ada izin.', 403); }
        if (empty($id)) show_404();

        $header = $this->Fg_receipt_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya Draft yang bisa diupdate.');
            redirect('fg_receipt/show/' . $id);
        }

        $this->form_validation->set_rules('receipt_date', 'Tanggal Terima', 'required');
        $details = $this->input->post('details') ?: [];

        $detailsData = [];
        foreach ($details as $detail) {
            if (empty($detail['fg_id']) || !isset($detail['qty_received']) || (float)$detail['qty_received'] <= 0) {
                continue;
            }
            $detailsData[] = [
                'prod_output_id'       => !empty($detail['prod_output_id']) ? (int)$detail['prod_output_id'] : null,
                'production_header_id' => !empty($detail['production_header_id']) ? (int)$detail['production_header_id'] : null,
                'wo_header_id'         => !empty($detail['wo_header_id']) ? (int)$detail['wo_header_id'] : null,
                'sales_contract_id'    => !empty($detail['sales_contract_id']) ? (int)$detail['sales_contract_id'] : null,
                'fg_id'                => (int) $detail['fg_id'],
                'qty_received'         => (float) $detail['qty_received'],
                'uom'                  => $detail['uom'] ?? null,
                'notes'                => $detail['notes'] ?? null,
            ];
        }

        if (empty($detailsData)) {
            $this->session->set_flashdata('error', 'Item detail tidak boleh kosong.');
            redirect('fg_receipt/edit/' . $id);
            return;
        }

        $headerData = [
            'receipt_date' => $this->input->post('receipt_date'),
            'remarks'      => $this->input->post('remarks'),
            'received_by'  => $this->session->userdata('user_id') ?? null
        ];

        if ($this->Fg_receipt_model->update_receipt($id, $headerData, $detailsData)) {
            $this->session->set_flashdata('success', 'Penerimaan berhasil diupdate.');
        } else {
            $this->session->set_flashdata('error', 'Gagal update penerimaan.');
        }

        redirect('fg_receipt/show/' . $id);
    }

    // ======================================================
    // DESTROY / CANCEL
    // ======================================================
    public function destroy($id) {
        if (empty($id)) show_404();
        $header = $this->Fg_receipt_model->get_header_by_id($id);
        if (!$header) show_404();

        if ($header->status == 'Draft') {
            if (!check_access('fg_receipt', 'delete')) { show_error('Tidak ada izin.', 403); }
            $success = $this->Fg_receipt_model->delete_receipt($id);
            $msg = $success ? 'Draft berhasil dihapus.' : 'Gagal menghapus draft.';
            $this->session->set_flashdata($success ? 'success' : 'error', $msg);
        } elseif ($header->status == 'Confirmed') {
            if (!check_access('fg_receipt', 'delete')) { show_error('Tidak ada izin.', 403); }
            if ($this->Fg_receipt_model->cancel_receipt($id)) {
                $this->session->set_flashdata('success', 'Dokumen Confirmed dibatalkan (stok dikembalikan).');
            } else {
                $this->session->set_flashdata('error', 'Gagal membatalkan dokumen.');
            }
        } else {
            $this->session->set_flashdata('warning', 'Hanya status Draft/Confirmed yang dapat dihapus.');
        }

        redirect('fg_receipt');
    }

    // ======================================================
    // APPROVAL WORKFLOW (3-stage)
    // Draft → Submitted → Approved Head Dept → Endorsed → Approved (stock fires)
    // Permission check pakai menu `fg_receipt_approval`.
    // ======================================================

    /**
     * FG Receipt Approval list — filtered by user's stage permission.
     */
    public function approval() {
        if (!check_access('fg_receipt_approval', 'view')) {
            show_error('Anda tidak memiliki akses ke modul FG Receipt Approval.', 403);
        }

        $can_head_dept   = check_access('fg_receipt_approval', 'head_dept');
        $can_endorsement = check_access('fg_receipt_approval', 'endorsement');
        $can_approval    = check_access('fg_receipt_approval', 'approval');

        $data = [
            'title'           => lang_line('title_fgr_approval'),
            'receipts'        => $this->Fg_receipt_model->get_pending_approvals($can_head_dept, $can_endorsement, $can_approval),
            'can_head_dept'   => $can_head_dept,
            'can_endorsement' => $can_endorsement,
            'can_approval'    => $can_approval,
            // Riwayat approval milik user yg sedang login (user 2026-09-06:
            // "di bagian bawah ada riwayat yang user itu approve") — kartu
            // kedua di bawah antrian, partial templates/my_approval_history.php.
            'my_approval_history' => my_approval_history([
                'table'  => 'fg_receipt_header',
                'number' => 'receipt_number',
                'date'   => 'receipt_date',
            ]),
            'my_history_route'    => 'fg_receipt/show',
        ];

        $this->_render('fg_receipt/approval', $data);
    }

    private function _approval_redirect($id) {
        $back = $this->input->post('redirect') ?: $this->input->get('redirect');
        if ($back === 'approval') {
            redirect('fg_receipt/approval');
        }
        redirect('fg_receipt/show/' . $id);
    }

    /**
     * Submit Draft → Pending approval (creator).
     */
    public function submit_for_approval($id) {
        if (!check_access('fg_receipt', 'edit')) {
            show_error('Anda tidak memiliki izin untuk submit.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();
        $user_id = $this->session->userdata('user_id');
        $res = $this->Fg_receipt_model->submit_for_approval($id, $user_id);
        $this->session->set_flashdata($res['success'] ? 'success' : 'error',
            $res['success'] ? 'FG Receipt di-submit untuk approval.' : $res['message']);
        $this->_approval_redirect($id);
    }

    public function approve_hod($id) {
        if (!check_access('fg_receipt_approval', 'head_dept')) {
            show_error('Anda tidak memiliki izin approve Head of Dept.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();
        $user_id = $this->session->userdata('user_id');
        $res = $this->Fg_receipt_model->approve_hod($id, $user_id);
        $this->session->set_flashdata($res['success'] ? 'success' : 'error',
            $res['success'] ? 'FG Receipt disetujui (approval 1 tahap). Stok barang jadi sudah masuk.' : $res['message']);
        $this->_approval_redirect($id);
    }

    public function approve_endorsement($id) {
        if (!check_access('fg_receipt_approval', 'endorsement')) {
            show_error('Anda tidak memiliki izin endorsement.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();
        $user_id = $this->session->userdata('user_id');
        $res = $this->Fg_receipt_model->approve_endorsement($id, $user_id);
        $this->session->set_flashdata($res['success'] ? 'success' : 'error',
            $res['success'] ? 'FG Receipt di-endorse.' : $res['message']);
        $this->_approval_redirect($id);
    }

    /**
     * Final approve — INI yang fire stock_ledger fg_in.
     */
    public function approve_final($id) {
        if (!check_access('fg_receipt_approval', 'approval')) {
            show_error('Anda tidak memiliki izin final approval.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();
        $user_id = $this->session->userdata('user_id');
        $res = $this->Fg_receipt_model->approve_final($id, $user_id);
        $this->session->set_flashdata($res['success'] ? 'success' : 'error',
            $res['success'] ? 'FG Receipt final approved. Stock sudah masuk ledger.' : $res['message']);
        $this->_approval_redirect($id);
    }

    public function return_to_draft($id) {
        $can = check_access('fg_receipt_approval', 'head_dept')
            || check_access('fg_receipt_approval', 'endorsement')
            || check_access('fg_receipt_approval', 'approval');
        if (!$can) show_error('Anda tidak memiliki izin return.', 403);
        if (empty($id) || !is_numeric($id)) show_404();

        $reason = trim($this->input->post('return_reason'));
        if (empty($reason)) {
            $this->session->set_flashdata('error', 'Alasan return wajib diisi.');
            $this->_approval_redirect($id); return;
        }
        $user_id = $this->session->userdata('user_id');
        $res = $this->Fg_receipt_model->return_to_draft($id, $user_id, $reason);
        $this->session->set_flashdata($res['success'] ? 'success' : 'error',
            $res['success'] ? 'FG Receipt dikembalikan ke Draft.' : $res['message']);
        $this->_approval_redirect($id);
    }

    public function reject($id) {
        $can = check_access('fg_receipt_approval', 'head_dept')
            || check_access('fg_receipt_approval', 'endorsement')
            || check_access('fg_receipt_approval', 'approval');
        if (!$can) show_error('Anda tidak memiliki izin reject.', 403);
        if (empty($id) || !is_numeric($id)) show_404();

        $reason = trim($this->input->post('reject_reason'));
        if (empty($reason)) {
            $this->session->set_flashdata('error', 'Alasan reject wajib diisi.');
            $this->_approval_redirect($id); return;
        }
        $user_id = $this->session->userdata('user_id');
        $res = $this->Fg_receipt_model->reject($id, $user_id, $reason);
        $this->session->set_flashdata($res['success'] ? 'success' : 'error',
            $res['success'] ? 'FG Receipt berhasil di-reject.' : $res['message']);
        $this->_approval_redirect($id);
    }

    // ======================================================
    // EXPORT PDF
    // ======================================================
    public function export_pdf($id) {
        if (!check_access('fg_receipt', 'view')) { show_error('Tidak ada izin.', 403); }

        $header = $this->Fg_receipt_model->get_header_by_id($id);
        if (!$header) show_404();

        $details = $this->Fg_receipt_model->get_details_by_header_id($id);

        // Tanda tangan PDF (2026-11-06): Dibuat (pembuat dokumen) & Disetujui (Head of Dept,
        // approval 1 tahap; FG Receipt lama: approver final / konfirmasi).
        $sig_slot = function ($user_id, $date, $show_sig) {
            $slot = ['name' => '-', 'html' => '', 'date' => $date];
            if (empty($user_id)) return $slot;
            $u = $this->db->select('fullname, signature_path')->where('id', $user_id)->get('users')->row();
            if ($u) {
                $slot['name'] = $u->fullname;
                if ($show_sig) $slot['html'] = render_signature_stamp_html($u->signature_path, 70, false);
            }
            return $slot;
        };
        $maker_id    = $header->received_by ?: ($header->submitted_by ?? null);
        $approver_id = $header->final_approved_by ?: ($header->confirmed_by ?? null);
        $approved_at = $header->final_approved_at ?: ($header->confirmed_at ?? null);

        $data = [
            'receipt_header'  => $header,
            'receipt_details' => $details,
            'sig_made'        => $sig_slot($maker_id, $header->submitted_at ?: $header->created_at, $header->status !== 'Draft'),
            'sig_approved'    => $sig_slot($approver_id, $approved_at, true),
        ];

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        // Gambar ttd/stempel ada di folder proyek; chroot bawaan Dompdf (folder vendor) memblokirnya.
        $options->set('chroot', [FCPATH]);

        $dompdf = new Dompdf($options);
        $html = $this->load->view('fg_receipt/pdf_template', $data, true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = "FGReceipt_" . str_replace('/', '-', $header->receipt_number) . ".pdf";
        $dompdf->stream($filename, ["Attachment" => 0]);
        exit;
    }
}
