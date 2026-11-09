<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

class Production_receive extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Mr_model', 'Stock_ledger_model']);
        $this->load->helper('url');
    }

    // Halaman daftar Issuance (Status Issued / Received)
    public function index() {
        if (!check_access('production_receive', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $filters = [
            'search' => trim((string) $this->input->get('search', TRUE)),
            'status' => trim((string) $this->input->get('status', TRUE)),
        ];

        // Query issuances with MR info
        $this->db->reset_query();
        $this->db->select('mri.id as issuance_id, mri.issuance_number, mri.status, mri.issued_at, mri.received_at, mrh.mr_number, mrh.request_date, u.fullname as requester_name')
            ->from('mr_issuances mri')
            ->join('mr_header mrh', 'mri.mr_header_id = mrh.id', 'left')
            ->join('users u', 'mrh.requester_id = u.id', 'left');

        // Filter status (redesign 2026-11-08): Issued = menunggu diterima,
        // need_qc = sudah diterima tapi belum QC, qc_done = selesai.
        if ($filters['status'] === 'need_qc') {
            $this->db->where('mri.status', 'Received')->where('mri.qc_status !=', 'QC Done');
        } elseif ($filters['status'] === 'qc_done') {
            $this->db->where('mri.qc_status', 'QC Done');
        } elseif (!empty($filters['status'])) {
            $this->db->where('mri.status', $filters['status']);
        } else {
            $this->db->where_in('mri.status', ['Issued', 'Received']);
        }

        // qc_status + jumlah item/qty yang dikeluarkan + total NG + departemen MR
        $this->db->select("mri.qc_status, mri.qc_done_at, mrh.department_code,
            (SELECT COUNT(DISTINCT CONCAT(sl.item_id, '|', sl.item_type)) FROM stock_ledger sl WHERE sl.issuance_id = mri.id AND sl.transaction_type = 'OUT_ISSUE') AS item_count,
            (SELECT COALESCE(SUM(sl.qty_out), 0) FROM stock_ledger sl WHERE sl.issuance_id = mri.id AND sl.transaction_type = 'OUT_ISSUE') AS issued_qty,
            (SELECT COALESCE(SUM(q.reject_qty), 0) FROM issuance_qc_results q WHERE q.issuance_id = mri.id) AS ng_qty", FALSE);

        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->where('(mrh.mr_number LIKE "%' . $term . '%" OR mri.issuance_number LIKE "%' . $term . '%" OR u.fullname LIKE "%' . $term . '%")');
        }

        // Yang perlu ditindaklanjuti tampil paling atas.
        $this->db->order_by("CASE WHEN mri.status = 'Issued' THEN 0 WHEN mri.qc_status <> 'QC Done' THEN 1 ELSE 2 END", '', FALSE);
        $this->db->order_by('mri.issued_at', 'DESC');
        $issuances = $this->db->get()->result();

        $cnt = $this->db->query("SELECT
                SUM(status = 'Issued') AS waiting,
                SUM(status = 'Received' AND qc_status <> 'QC Done') AS need_qc,
                SUM(qc_status = 'QC Done') AS qc_done,
                COUNT(*) AS total
            FROM mr_issuances WHERE status IN ('Issued', 'Received')")->row();

        $data = [
            'title'      => lang_line('title_pr_index'),
            'issuances'  => $issuances,
            'filters'    => $filters,
            'counts'     => $cnt,
            'dept_names' => department_names(),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('production_receive/index_issuance', $data);
        $this->load->view('templates/footer');
    }

    // Halaman detail untuk konfirmasi
    public function detail($mr_id) {
        if (!check_access('production_receive', 'edit')) { show_error('Tidak ada izin.', 403); }
        if (empty($mr_id)) show_404();

        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || !in_array($header->status, ['Issued', 'Received'])) {
             $this->session->set_flashdata('error', 'MR ini belum dikeluarkan oleh Gudang.');
             redirect('production_receive');
        }

        $data = [
            'title'   => lang_line('title_pr_confirm'),
            'header'  => $header,
            'details' => $this->Mr_model->get_details_by_id($mr_id) // Ambil detail MR
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('production_receive/detail', $data); // View baru
        // Footer dipanggil di view
    }

    // Detail untuk issuance tertentu (per-issuance model)
    public function detail_issuance($issuance_id) {
        if (!check_access('production_receive', 'edit')) { show_error('Tidak ada izin.', 403); }
        if (empty($issuance_id) || !is_numeric($issuance_id)) show_404();

        $issuance = $this->db->get_where('mr_issuances', ['id' => $issuance_id])->row();
        if (!$issuance) {
            $this->session->set_flashdata('error', 'Issuance tidak ditemukan.');
            redirect('production_receive');
            return;
        }

        $mr_header = $this->Mr_model->get_header_by_id($issuance->mr_header_id);
        if (!$mr_header) {
            $this->session->set_flashdata('error', 'MR tidak ditemukan.');
            redirect('production_receive');
            return;
        }

        // Get items issued in this specific issuance
        $issued_items = $this->db
            ->select('sl.item_id, sl.item_type, SUM(sl.qty_out) as issued_qty, sl.stock_on_hand_id')
            ->from('stock_ledger sl')
            ->where('sl.issuance_id', $issuance_id)
            ->where('sl.transaction_type', 'OUT_ISSUE')
            ->group_by('sl.item_id, sl.item_type')
            ->get()->result();

        // Enrich with item names
        foreach ($issued_items as &$item) {
            if ($item->item_type == 'material') {
                $mat = $this->db->get_where('materials', ['id' => $item->item_id])->row();
                $item->item_code = $mat ? $mat->kode_material : '';
                $item->item_name = $mat ? $mat->nama_material : '';
                $item->item_unit = $mat ? $mat->satuan : '';
            } else {
                $sf = $this->db->get_where('semifinished_goods', ['id' => $item->item_id])->row();
                $item->item_code = $sf ? $sf->kode_barang : '';
                $item->item_name = $sf ? $sf->nama_barang : '';
                $item->item_unit = $sf ? $sf->satuan : '';
            }
        }

        // Ambil hasil QC jika sudah ada
        $qc_results     = $this->Mr_model->get_qc_results($issuance_id);
        $qc_results_map = [];
        foreach ($qc_results as $qr) {
            $qc_results_map[$qr->item_id . '_' . $qr->item_type] = $qr;
        }

        // Nama pengeluar / penerima / pemeriksa QC untuk header & alur.
        $people = $this->db->query("SELECT
                (SELECT fullname FROM users WHERE id = ?) AS issuer_name,
                (SELECT fullname FROM users WHERE id = ?) AS receiver_name,
                (SELECT fullname FROM users WHERE id = ?) AS qc_by_name",
            [(int) $issuance->issuer_id, (int) $issuance->receiver_id, (int) $issuance->qc_done_by])->row();

        $data = [
            'title'          => lang_line('title_pr_detail_issuance'),
            'issuance'       => $issuance,
            'people'         => $people,
            'dept_name'      => department_names()[$mr_header->department_code ?? ''] ?? ($mr_header->department_code ?: '-'),
            'header'         => $mr_header,
            'items'          => $issued_items,
            'qc_results'     => $qc_results,
            'qc_results_map' => $qc_results_map,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('production_receive/detail_issuance', $data);
        $this->load->view('templates/footer');
    }

    // Proses Konfirmasi Terima (oleh Produksi) - per issuance
    public function confirm_receive($issuance_id) {
        if (!check_access('production_receive', 'confirm')) { show_error('Tidak ada izin.', 403); }

        $issuance = $this->db->get_where('mr_issuances', ['id' => $issuance_id])->row();
        if (!$issuance || $issuance->status != 'Issued') {
             $this->session->set_flashdata('error', 'Issuance ini tidak dalam status Issued.');
             redirect('production_receive/detail_issuance/' . $issuance_id);
             return;
        }

        $user_id = $this->session->userdata('user_id') ?? null;
        if ($this->Mr_model->process_reception_by_issuance($issuance_id, $user_id)) {
            $this->session->set_flashdata('success', 'Barang berhasil dikonfirmasi diterima oleh Produksi.');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengkonfirmasi penerimaan.');
        }
        redirect('production_receive/detail_issuance/' . $issuance_id);
    }
    
    // Proses Batal Terima (oleh Produksi) - per issuance
    // Membatalkan: wip_in + IN_RETURN_WIP (jika QC sudah done) + QC results → kembali ke Issued
    public function unconfirm_receive($issuance_id) {
        if (!check_access('production_receive', 'edit')) { show_error('Tidak ada izin.', 403); }

        $issuance = $this->db->get_where('mr_issuances', ['id' => $issuance_id])->row();
        if (!$issuance || $issuance->status !== 'Received') {
             $this->session->set_flashdata('error', 'Issuance ini tidak dalam status Received.');
             redirect('production_receive/detail_issuance/' . $issuance_id);
             return;
        }

        if ($this->Mr_model->reverse_reception_by_issuance($issuance_id)) {
            $msg = 'Penerimaan berhasil dibatalkan. Status kembali ke Issued.';
            if (($issuance->qc_status ?? '') === 'QC Done') {
                $msg .= ' Hasil QC dan pengembalian stok reject juga sudah dibatalkan.';
            }
            $this->session->set_flashdata('success', $msg);
        } else {
             $this->session->set_flashdata('error', 'Gagal membatalkan penerimaan.');
        }
        redirect('production_receive/detail_issuance/' . $issuance_id);
    }
    
    // =====================================================
    // PRODUCTION QC — Quality Check setelah Production Receive
    // =====================================================

    /**
     * POST: Proses QC untuk satu issuance.
     * Form: items[item_id_type][accept_qty], items[...][reject_qty], items[...][qc_notes]
     */
    public function process_qc($issuance_id) {
        if (!check_access('production_receive', 'edit')) {
            show_error('Tidak ada izin.', 403);
        }
        if (empty($issuance_id) || !is_numeric($issuance_id)) show_404();

        $issuance = $this->db->get_where('mr_issuances', ['id' => $issuance_id])->row();
        if (!$issuance || $issuance->status !== 'Received') {
            $this->session->set_flashdata('error', 'QC hanya bisa dilakukan setelah issuance berstatus Received.');
            redirect('production_receive/detail_issuance/' . $issuance_id);
            return;
        }

        $raw_items = $this->input->post('items') ?? [];
        if (empty($raw_items)) {
            $this->session->set_flashdata('error', 'Tidak ada data QC yang dikirim.');
            redirect('production_receive/detail_issuance/' . $issuance_id);
            return;
        }

        // Susun array qc_items dari form input
        $qc_items = [];
        foreach ($raw_items as $key => $val) {
            // key format: "{item_id}_{item_type}"
            $parts = explode('_', $key, 2);
            if (count($parts) < 2) continue;
            [$item_id, $item_type] = $parts;
            $qc_items[] = [
                'item_id'     => (int)$item_id,
                'item_type'   => $item_type,
                'issued_qty'  => (float)($val['issued_qty'] ?? 0),
                'accept_qty'  => (float)($val['accept_qty'] ?? 0),
                'reject_qty'  => (float)($val['reject_qty'] ?? 0),
                // Keterangan NG (wajib kalau reject_qty > 0 -- dicek di model).
                // Rincian 4 kategori + Kode NG warisan NSI dihapus 2026-11-08.
                'qc_notes'    => trim(strip_tags((string) ($val['qc_notes'] ?? ''))),
            ];
        }

        $user_id = $this->session->userdata('user_id');
        $result  = $this->Mr_model->process_qc_issuance($issuance_id, $qc_items, $user_id);

        if ($result['success']) {
            $this->session->set_flashdata('success', 'QC berhasil diproses. Qty OK masuk WIP; Qty NG dapat diretur ke supplier via Warehouse Out → Production Return.');
        } else {
            $this->session->set_flashdata('error', 'Gagal proses QC: ' . $result['message']);
        }
        redirect('production_receive/detail_issuance/' . $issuance_id);
    }

    // =====================================================

    // Export PDF Production Receive
    public function export_pdf($id) {
        if (!check_access('production_receive', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        $header = $this->Mr_model->get_header_by_id($id);
        if (!$header || !in_array($header->status, ['Issued', 'Received'])) {
            $this->session->set_flashdata('error', 'Data penerimaan tidak ditemukan atau belum dikeluarkan.');
            redirect('production_receive');
            return;
        }

        $details = $this->Mr_model->get_details_by_id($id);

        $data = [
            'header' => $header,
            'details' => $details
        ];

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $html = $this->load->view('production_receive/pdf_template', $data, true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = 'Production_Receive_' . str_replace('/', '-', $header->mr_number) . '.pdf';
        $dompdf->stream($filename, ["Attachment" => 0]);
    }
}