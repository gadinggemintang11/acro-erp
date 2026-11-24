<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SEMENTARA (dihapus setelah uji): uji fitur Redraft AP Invoice / AP Payment.
 * Hanya CLI. Semua dijalankan di dalam SATU transaksi lalu di-ROLLBACK.
 * Data uji sendiri (id >= 901 / nomor ZZTEST), tidak menyentuh dokumen user.
 */
class Zz_tmp_redraft_test extends CI_Controller {

    private $fails = 0;
    private $out = [];

    public function run()
    {
        if (!is_cli()) show_404();
        if (!isset($_SESSION)) $_SESSION = [];
        $this->load->model('ApInvoice_model', 'inv');
        $this->load->model('ApPayment_model', 'pay');
        $this->load->model('JurnalUmum_model', 'ju');
        $this->load->model('Hutang_model', 'hutang');

        $tables = ['journal_header', 'journal_details', 'ap_invoice_header', 'ap_invoice_details', 'ap_payment_header', 'ap_payment_details', 'po_header', 'po_details', 'sgr_header', 'sgr_details'];
        $before = $this->_counts($tables);

        $this->db->trans_begin();
        try {
            $this->_tests();
        } catch (Throwable $e) {
            $this->_check('EXCEPTION ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine(), false);
        }
        $this->db->conn_id->rollback();   // rollback langsung (aman walau depth transaksi CI bocor)
        $this->db->conn_id->autocommit(true);

        $after = $this->_counts($tables);
        $this->_check('ROLLBACK: jumlah baris semua tabel sama seperti sebelum uji', $before === $after, $before === $after ? null : ['sebelum' => $before, 'sesudah' => $after]);
        echo implode("\n", $this->out) . "\n\n" . ($this->fails ? $this->fails . ' FAIL' : 'SEMUA PASS') . "\n";
    }

    private function _counts($tables)
    {
        $c = [];
        foreach ($tables as $t) $c[$t] = (int) $this->db->count_all($t);
        return $c;
    }

    private function _check($label, $ok, $extra = null)
    {
        $this->out[] = ($ok ? 'PASS ' : 'FAIL ') . $label . ($extra !== null ? '  => ' . json_encode($extra) : '');
        if (!$ok) $this->fails++;
    }

    private function _throws($label, callable $fn, $needle)
    {
        try { $fn(); $this->_check($label . ' (harus ditolak)', false, 'tidak ada exception'); }
        catch (Exception $e) { $this->_check($label, stripos($e->getMessage(), $needle) !== false, $e->getMessage()); }
    }

    private function _ap($id) { return $this->db->where('id', $id)->get('ap_invoice_header')->row(); }
    private function _pay($id) { return $this->db->where('id', $id)->get('ap_payment_header')->row(); }
    private function _j($id) { return $id ? $this->db->where('id', $id)->get('journal_header')->row() : null; }
    private function _jlines($id)
    {
        $o = [];
        foreach ($this->db->where('journal_header_id', $id)->order_by('id')->get('journal_details')->result() as $l) {
            $o[] = (int) $l->account_id . ':' . (float) $l->debit_local . '/' . (float) $l->kredit_local;
        }
        return $o;
    }
    private function _view($v, $d)
    {
        $lvl = error_reporting(E_ALL);
        set_error_handler(function ($no, $str, $file, $line) use (&$errs) { $errs[] = "$str @ " . basename($file) . ":$line"; return true; });
        $errs = [];
        $html = $this->load->view($v, $d, TRUE);
        restore_error_handler();
        error_reporting($lvl);
        return [$html, $errs];
    }

    private function _tests()
    {
        $today = date('Y-m-d');
        $ACC_PERSEDIAAN = 52; $ACC_PPN = 61; $ACC_HUTANG = 76; $ACC_BANK = 41; $ACC_BIAYA = 144;

        // ---------- Fixture: PO + penerimaan SGR (qty 100 @ 1.000) ----------
        $this->db->insert('po_header', ['id' => 901, 'po_number' => 'ZZTEST/PO/901', 'type' => 'non_produksi', 'supplier_id' => 3, 'po_date' => $today,
            'currency_code' => 'IDR', 'exchange_rate' => 1, 'has_ppn' => 1, 'ppn_rate' => 11, 'ppn_inclusive' => 0, 'status' => 'Confirmed']);
        $this->db->insert('po_details', ['id' => 901, 'po_header_id' => 901, 'item_id' => 4, 'item_type' => 'inventory', 'description' => '',
            'qty' => 100, 'base_price' => 1000, 'local_price' => 1000, 'total_local' => 100000, 'total' => 100000]);
        $this->db->insert('sgr_header', ['id' => 901, 'sgr_number' => 'ZZTEST/SGR/901', 'sgr_date' => $today, 'base_on' => 'PO', 'warehouse_type' => 'inventory',
            'category' => 'General', 'supplier_id' => 3, 'status' => 'Completed']);
        $this->db->insert('sgr_details', ['id' => 901, 'sgr_header_id' => 901, 'source_doc_header_id' => 901, 'source_doc_detail_id' => 901, 'source_doc_type' => 'PO',
            'item_id' => 4, 'item_type' => 'inventory', 'category' => 'General', 'receive_qty' => 100, 'unit_value' => 1000, 'total_value' => 100000]);

        // ---------- A. Faktur PO_BASED qty 60 -> posting ----------
        $A = $this->inv->insert_invoice(['invoice_no' => 'ZZTEST/API/1', 'invoice_date' => $today, 'due_date' => $today, 'supplier_id' => 3,
            'invoice_type' => 'PO_BASED', 'ppn_rate' => 11, 'pph_rate' => 0,
            'details' => [['po_detail_id' => 901, 'qty' => 60, 'debet_account_id' => $ACC_PERSEDIAAN, 'description' => '']]]);
        $this->_check('A1 faktur PO dibuat, total 66.600', $A && (float) $this->_ap($A)->total_invoice == 66600, $A ? (float) $this->_ap($A)->total_invoice : null);
        $r = $this->inv->post_invoice_journal($A, null);
        $ap = $this->_ap($A); $J1 = (int) $ap->journal_header_id; $J1no = $this->_j($J1)->journal_no;
        $this->_check('A2 posting faktur -> Posted + jurnal Posted', $ap->status === 'Posted' && $this->_j($J1)->status === 'Posted', [$ap->status, $J1no]);
        $this->_check('A3 isi jurnal: Dr Persediaan 60rb, Dr PPN 6.600, Cr Hutang 66.600',
            $this->_jlines($J1) === ["$ACC_PERSEDIAAN:60000/0", "$ACC_PPN:6600/0", "$ACC_HUTANG:0/66600"], $this->_jlines($J1));
        $this->_check('A4 faktur Posted tanpa pembayaran boleh di-redraft', $this->inv->redraft_block_reason($this->inv->get_invoice_header_by_id($A)) === null);

        // ---------- B. Pembayaran 30rb ----------
        $P = $this->pay->insert_payment(['payment_no' => 'ZZTEST/APP/1', 'payment_date' => $today, 'supplier_id' => 3,
            'debet_account_id' => $ACC_HUTANG, 'kredit_account_id' => $ACC_BANK, 'pph_rate' => 0, 'details' => [$A => ['amount_paid' => 30000]]]);
        $why = $this->inv->redraft_block_reason($this->inv->get_invoice_header_by_id($A));
        $this->_check('B1 faktur yang ada di Pembayaran Draft tidak boleh di-redraft', $why !== null && strpos($why, 'ZZTEST/APP/1') !== false, $why);
        $this->pay->post_payment($P, null);
        $ap = $this->_ap($A); $pp = $this->_pay($P); $J2 = (int) $pp->journal_header_id; $J2no = $this->_j($J2)->journal_no;
        $this->_check('B2 posting bayar -> faktur Partial, dibayar 30rb', $ap->payment_status === 'Partial' && (float) $ap->total_paid == 30000, [$ap->payment_status, (float) $ap->total_paid]);
        $why = $this->inv->redraft_block_reason($this->inv->get_invoice_header_by_id($A));
        $this->_check('B3 faktur yang sudah dibayar tidak boleh di-redraft', $why !== null && strpos($why, 'Posted') !== false, $why);

        // ---------- C. Redraft pembayaran ----------
        $this->_check('C1 pembayaran Posted boleh di-redraft', $this->pay->redraft_block_reason($this->pay->get_header_by_id($P)) === null);
        $this->pay->redraft_payment($P);
        $ap = $this->_ap($A); $pp = $this->_pay($P); $j2 = $this->_j($J2);
        $this->_check('C2 redraft bayar -> pembayaran Draft, jurnal Draft (no. sama)', $pp->status === 'Draft' && $j2->status === 'Draft' && (int) $pp->journal_header_id === $J2 && $j2->journal_no === $J2no, [$pp->status, $j2->status]);
        $this->_check('C3 sisa hutang faktur dikembalikan: dibayar 0, Unpaid', (float) $ap->total_paid == 0 && $ap->payment_status === 'Unpaid', [(float) $ap->total_paid, $ap->payment_status]);
        $this->_check('C4 Jurnal Umum: jurnal terkunci (tidak bisa edit/post/hapus manual)', !$this->ju->can_edit_journal($J2) && $this->ju->module_lock_message($this->ju->get_header_by_id($J2)) !== null);
        $this->_check('C5 jurnal Draft keluar dari laporan (Neraca hanya Posted)', (int) $this->db->where('id', $J2)->where('status', 'Posted')->count_all_results('journal_header') === 0);

        // ---------- D. Edit pembayaran ----------
        $this->_throws('D1 edit bayar melebihi sisa ditolak', function () use ($P, $A, $ACC_HUTANG, $ACC_BANK, $today) {
            $this->pay->update_payment($P, ['payment_date' => $today, 'debet_account_id' => $ACC_HUTANG, 'kredit_account_id' => $ACC_BANK, 'pph_rate' => 0, 'details' => [$A => ['amount_paid' => 70000]]]);
        }, 'melebihi');
        $inv_edit = $this->pay->get_invoices_for_edit($this->pay->get_header_by_id($P));
        $row = null; foreach ($inv_edit as $x) if ((int) $x->id === $A) $row = $x;
        $this->_check('D2 form edit: sisa faktur dihitung tanpa pembayaran ini (66.600), nominal lama 30rb', $row && (float) $row->sisa_utang == 66600 && (float) $row->amount_paid == 30000, $row ? [(float) $row->sisa_utang, (float) $row->amount_paid] : null);
        $this->pay->update_payment($P, ['payment_date' => $today, 'debet_account_id' => $ACC_HUTANG, 'kredit_account_id' => $ACC_BANK, 'pph_rate' => 2, 'pph_account_id' => 79, 'notes' => 'edit', 'details' => [$A => ['amount_paid' => 66600]]]);
        $pp = $this->_pay($P);
        $this->_check('D3 edit bayar jadi lunas 66.600 + PPh 2%', (float) $pp->total_amount == 66600 && (float) $pp->pph_amount == 1332 && $pp->status === 'Draft', [(float) $pp->total_amount, (float) $pp->pph_amount]);

        // ---------- E. Posting ulang pembayaran: jurnal yang sama diperbarui ----------
        $this->pay->post_payment($P, null);
        $ap = $this->_ap($A); $pp = $this->_pay($P); $j2 = $this->_j($J2);
        $this->_check('E1 posting ulang -> jurnal SAMA (id & nomor), Posted', (int) $pp->journal_header_id === $J2 && $j2->journal_no === $J2no && $j2->status === 'Posted', [$j2->journal_no, $j2->status]);
        $this->_check('E2 isi jurnal diperbarui: Dr Hutang 66.600 / Cr Bank 65.268 / Cr PPh 1.332',
            $this->_jlines($J2) === ["$ACC_HUTANG:66600/0", "$ACC_BANK:0/65268", "79:0/1332"], $this->_jlines($J2));
        $this->_check('E3 faktur Paid', $ap->payment_status === 'Paid' && (float) $ap->total_paid == 66600, [$ap->payment_status, (float) $ap->total_paid]);
        $jcount = (int) $this->db->where('source_module', 'AP_PAYMENT')->where('source_doc_id', $P)->count_all_results('journal_header');
        $this->_check('E4 tidak ada jurnal ganda untuk pembayaran ini', $jcount === 1, $jcount);

        // ---------- F. Cek kelebihan bayar saat posting & faktur Draft ----------
        $this->pay->redraft_payment($P);
        $this->db->where('payment_header_id', $P)->update('ap_payment_details', ['amount_paid' => 99999]);
        $this->_throws('F1 posting bayar melebihi sisa ditolak', function () use ($P) { $this->pay->post_payment($P, null); }, 'melebihi sisa');
        $this->db->where('payment_header_id', $P)->update('ap_payment_details', ['amount_paid' => 66600]);

        // ---------- G. Hapus pembayaran Draft -> jurnal Draft-nya ikut terhapus ----------
        $ok = $this->pay->delete_draft_payment($P);
        $this->_check('G1 hapus pembayaran Draft hasil redraft -> jurnal ikut terhapus', $ok && !$this->_pay($P) && !$this->_j($J2));

        // ---------- H. Redraft faktur + edit + posting ulang ----------
        $this->inv->redraft_invoice($A);
        $ap = $this->_ap($A); $j1 = $this->_j($J1);
        $this->_check('H1 redraft faktur -> Draft, jurnal Draft (no. sama)', $ap->status === 'Draft' && $j1->status === 'Draft' && $j1->journal_no === $J1no, [$ap->status, $j1->status]);
        $hut = null; foreach ($this->hutang->get_invoice_list(['only_outstanding' => true]) as $x) if ((int) $x->id === $A) $hut = $x;
        $this->_check('H2 Daftar Hutang menampilkan faktur sebagai Draft', $hut && $hut->status === 'Draft');
        $detail_id = (int) $this->db->where('ap_invoice_header_id', $A)->get('ap_invoice_details')->row('id');
        $base = ['invoice_no' => 'ZZTEST/API/1', 'invoice_date' => $today, 'due_date' => $today, 'ppn_rate' => 11, 'pph_rate' => 0];
        $this->_throws('H3 qty melebihi sisa PO ditolak', function () use ($A, $base, $detail_id, $ACC_PERSEDIAAN) {
            $this->inv->update_invoice($A, $base + ['details' => [$detail_id => ['qty' => 101, 'debet_account_id' => $ACC_PERSEDIAAN, 'description' => 'x']]]);
        }, 'melebihi sisa');
        $this->_throws('H4 no. faktur bentrok ditolak', function () use ($A, $base, $detail_id, $ACC_PERSEDIAAN) {
            $other = $this->db->where('id !=', $A)->select('invoice_no')->limit(1)->get('ap_invoice_header')->row('invoice_no');
            $this->inv->update_invoice($A, array_merge($base, ['invoice_no' => $other ?: 'X']) + ['details' => [$detail_id => ['qty' => 1, 'debet_account_id' => $ACC_PERSEDIAAN]]]);
        }, 'sudah dipakai');
        $this->_throws('H5 semua baris dihapus ditolak', function () use ($A, $base, $detail_id) {
            $this->inv->update_invoice($A, $base + ['details' => [$detail_id => ['remove' => 1]]]);
        }, 'minimal satu baris');
        $this->inv->update_invoice($A, array_merge($base, ['invoice_no' => 'ZZTEST/API/1-REV', 'ppn_rate' => 11]) + ['details' => [$detail_id => ['qty' => 100, 'debet_account_id' => $ACC_PERSEDIAAN, 'description' => 'Revisi qty']]]);
        $ap = $this->_ap($A);
        $this->_check('H6 edit qty 60 -> 100: total 111.000, no. faktur berubah', (float) $ap->total_invoice == 111000 && $ap->invoice_no === 'ZZTEST/API/1-REV', [(float) $ap->total_invoice, $ap->invoice_no]);
        $this->inv->post_invoice_journal($A, null);
        $ap = $this->_ap($A); $j1 = $this->_j($J1);
        $this->_check('H7 posting ulang -> jurnal SAMA, Posted', (int) $ap->journal_header_id === $J1 && $j1->journal_no === $J1no && $j1->status === 'Posted' && $ap->status === 'Posted');
        $this->_check('H8 isi jurnal diperbarui: Dr 100rb / Dr PPN 11rb / Cr Hutang 111rb',
            $this->_jlines($J1) === ["$ACC_PERSEDIAAN:100000/0", "$ACC_PPN:11000/0", "$ACC_HUTANG:0/111000"], $this->_jlines($J1));
        $this->_check('H9 total jurnal header ikut diperbarui', (float) $j1->total_debit_local == 111000 && (float) $j1->total_kredit_local == 111000, [(float) $j1->total_debit_local]);

        // ---------- I. Faktur Jasa / Non-GRN: tambah baris saat edit ----------
        $B = $this->inv->insert_invoice(['invoice_no' => 'ZZTEST/API/2', 'invoice_date' => $today, 'due_date' => $today, 'supplier_id' => 3,
            'invoice_type' => 'NON_GRN', 'ppn_rate' => 0, 'pph_rate' => 2,
            'details' => [['account_id' => $ACC_BIAYA, 'description' => 'Jasa A', 'qty' => 2, 'unit_price' => 5000]]]);
        $this->inv->post_invoice_journal($B, null);
        $JB = (int) $this->_ap($B)->journal_header_id;
        $this->inv->redraft_invoice($B);
        $bd = (int) $this->db->where('ap_invoice_header_id', $B)->get('ap_invoice_details')->row('id');
        $this->inv->update_invoice($B, ['invoice_no' => 'ZZTEST/API/2', 'invoice_date' => $today, 'due_date' => $today, 'ppn_rate' => 0, 'pph_rate' => 2,
            'details' => [$bd => ['account_id' => $ACC_BIAYA, 'description' => 'Jasa A', 'qty' => 2, 'unit_price' => 6000],
                          'new0' => ['account_id' => $ACC_PERSEDIAAN, 'description' => 'Tambahan', 'qty' => 1, 'unit_price' => 3000]]]);
        $b = $this->_ap($B);
        $this->_check('I1 Non-GRN: ubah harga + tambah baris -> subtotal 15.000, PPh 300, total 14.700', (float) $b->subtotal == 15000 && (float) $b->pph_amount == 300 && (float) $b->total_invoice == 14700, [(float) $b->subtotal, (float) $b->total_invoice]);
        $this->inv->post_invoice_journal($B, null);
        $this->_check('I2 Non-GRN posting ulang: jurnal sama, baris baru ikut',
            (int) $this->_ap($B)->journal_header_id === $JB && $this->_jlines($JB) === ["$ACC_BIAYA:12000/0", "$ACC_PERSEDIAAN:3000/0", "79:0/300", "$ACC_HUTANG:0/14700"], $this->_jlines($JB));

        // ---------- J. Penolakan lain ----------
        $this->db->where('id', $B)->update('ap_invoice_header', ['prf_header_id' => 999999]);
        $why = $this->inv->redraft_block_reason($this->inv->get_invoice_header_by_id($B));
        $this->_check('J1 faktur dari Payment Request tidak boleh di-redraft', $why !== null && strpos($why, 'Payment Request') !== false, $why);
        $this->db->where('id', $B)->update('ap_invoice_header', ['prf_header_id' => null]);
        $this->inv->redraft_invoice($B);
        $P2 = $this->pay->insert_payment(['payment_no' => 'ZZTEST/APP/2', 'payment_date' => $today, 'supplier_id' => 3, 'debet_account_id' => $ACC_HUTANG, 'kredit_account_id' => $ACC_BANK, 'pph_rate' => 0, 'details' => [$A => ['amount_paid' => 1000]]]);
        $this->db->insert('ap_payment_details', ['payment_header_id' => $P2, 'ap_invoice_header_id' => $B, 'amount_paid' => 100]);
        $this->_throws('J2 posting bayar untuk faktur yang sedang Draft ditolak', function () use ($P2) { $this->pay->post_payment($P2, null); }, 'tidak berstatus Posted');
        $this->db->where('payment_header_id', $P2)->where('ap_invoice_header_id', $B)->delete('ap_payment_details');
        $this->_throws('J3 redraft faktur Draft ditolak', function () use ($B) { $this->inv->redraft_invoice($B); }, 'Hanya faktur berstatus Posted');
        $ok = $this->inv->delete_draft_invoice($B);
        $this->_check('J4 hapus faktur Draft hasil redraft -> jurnal ikut terhapus', $ok && !$this->_ap($B) && !$this->_j($JB));

        // ---------- K. Render view (cek error PHP) ----------
        $hdrA = $this->inv->get_invoice_header_by_id($A);
        $common = ['details' => $this->inv->get_invoice_details_by_header_id($A), 'journal_header' => $this->ju->get_header_by_id($J1), 'journal_details' => $this->ju->get_details_by_header_id($J1), 'title' => 't'];
        list($h, $e) = $this->_view('accounting/ap_invoice/view', $common + ['header' => $hdrA, 'can_edit' => true, 'redraft_block' => null]);
        $this->_check('K1 view faktur Posted: tombol Redraft tampil, tanpa error PHP', !$e && strpos($h, 'apinvoice/redraft/' . $A) !== false, $e);
        list($h, $e) = $this->_view('accounting/ap_invoice/view', $common + ['header' => $hdrA, 'can_edit' => true, 'redraft_block' => 'Faktur sudah dipakai di Pembayaran Hutang: X']);
        $this->_check('K2 view faktur terblokir: tombol Redraft disabled + alasan', !$e && strpos($h, 'disabled title="Faktur sudah dipakai') !== false, $e);
        list($h, $e) = $this->_view('accounting/ap_invoice/view', $common + ['header' => $hdrA, 'can_edit' => false, 'redraft_block' => null]);
        $this->_check('K3 tanpa izin edit: tombol Redraft tidak tampil', !$e && strpos($h, 'apinvoice/redraft/') === false, $e);
        $this->inv->redraft_invoice($A);
        $hdrA = $this->inv->get_invoice_header_by_id($A);
        list($h, $e) = $this->_view('accounting/ap_invoice/view', ['header' => $hdrA, 'details' => $common['details'], 'journal_header' => $this->ju->get_header_by_id($J1), 'journal_details' => $common['journal_details'], 'title' => 't', 'can_edit' => true, 'redraft_block' => null]);
        $this->_check('K4 view faktur hasil redraft: tombol Edit + info jurnal Draft', !$e && strpos($h, 'apinvoice/edit/' . $A) !== false && strpos($h, 'hasil <strong>redraft</strong>') !== false, $e);
        $edit_data = ['title' => 't', 'header' => $hdrA, 'details' => $this->inv->get_invoice_details_by_header_id($A), 'max_qty' => [], 'rates' => $this->inv->stored_tax_rates($hdrA),
            'debet_accounts' => $this->inv->get_debet_accounts(), 'kredit_accounts' => $this->inv->get_kredit_accounts(), 'accounts' => $this->inv->get_postable_accounts_for_expense(), 'journal_no' => $J1no];
        list($h, $e) = $this->_view('accounting/ap_invoice/edit', $edit_data);
        $this->_check('K5 form edit faktur PO tampil tanpa error, PPN 11%', !$e && strpos($h, 'name="ppn_rate" class="form-control" value="11"') !== false, $e);
        $P3 = $this->pay->insert_payment(['payment_no' => 'ZZTEST/APP/3', 'payment_date' => $today, 'supplier_id' => 3, 'debet_account_id' => $ACC_HUTANG, 'kredit_account_id' => $ACC_BANK, 'pph_rate' => 0, 'details' => [$A => ['amount_paid' => 0.01]]]);
        $ph = $this->pay->get_header_by_id($P3);
        list($h, $e) = $this->_view('accounting/ap_payment/edit', ['title' => 't', 'header' => $ph, 'invoices' => $this->pay->get_invoices_for_edit($ph), 'debet_accounts' => $this->pay->get_debet_accounts(),
            'kredit_accounts' => $this->pay->get_kredit_accounts(), 'pph_payable_accounts' => $this->pay->get_pph_payable_accounts(), 'journal_no' => null]);
        $this->_check('K6 form edit pembayaran tampil tanpa error', !$e && strpos($h, 'appayment/update/' . $P3) !== false, $e);
        list($h, $e) = $this->_view('accounting/ap_payment/view', ['title' => 't', 'header' => $ph, 'details' => $this->pay->get_details_by_header_id($P3), 'can_edit' => true, 'redraft_block' => null, 'account_names' => [$ACC_HUTANG => '2.101.001 - Hutang Dagang', $ACC_BANK => 'Bank']]);
        $this->_check('K7 view pembayaran Draft: tombol Edit + nama akun (bukan ID)', !$e && strpos($h, 'appayment/edit/' . $P3) !== false && strpos($h, 'Hutang Dagang') !== false, $e);
        $jh = $this->ju->get_header_by_id($J1);
        list($h, $e) = $this->_view('accounting/jurnal_umum/view', ['title' => 't', 'header' => $jh, 'details' => $this->ju->get_details_by_header_id($J1), 'can_edit' => $this->ju->can_edit_journal($J1),
            'module_lock' => $this->ju->module_lock_message($jh), 'module_doc_path' => $this->ju->module_doc_path($jh)]);
        $this->_check('K8 Jurnal Umum: jurnal AP Draft tampil terkunci (tanpa tombol Edit/Posting, ada link dokumen asal)',
            !$e && strpos($h, 'jurnalumum/edit/' . $J1) === false && strpos($h, 'jurnalumum/post/' . $J1) === false && strpos($h, 'apinvoice/view/' . $A) !== false, $e);
    }
}
