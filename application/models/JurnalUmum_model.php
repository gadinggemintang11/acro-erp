<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class JurnalUmum_model extends CI_Model {

    public function get_all_headers()
    {
        $this->db->select('jh.*, u.fullname as creator_name');
        $this->db->from('journal_header jh');
        $this->db->join('users u', 'jh.created_by = u.id', 'left');
        $this->db->order_by('jh.journal_date', 'DESC');
        $this->db->order_by('jh.id', 'DESC');
        return $this->db->get()->result();
    }

    public function search_journals($search)
    {
        $this->db->select('jh.*, u.fullname as creator_name');
        $this->db->from('journal_header jh');
        $this->db->join('users u', 'jh.created_by = u.id', 'left');
        $this->db->group_start();
        $this->db->like('jh.journal_no', $search);
        $this->db->or_like('jh.description', $search);
        $this->db->or_like('jh.journal_date', $search);
        $this->db->group_end();
        $this->db->order_by('jh.journal_date', 'DESC');
        $this->db->order_by('jh.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id)
    {
        $this->db->select('jh.*, u.fullname as creator_name, p.fullname as poster_name');
        $this->db->from('journal_header jh');
        $this->db->join('users u', 'jh.created_by = u.id', 'left');
        $this->db->join('users p', 'jh.posted_by = p.id', 'left');
        $this->db->where('jh.id', $id);
        return $this->db->get()->row();
    }

    public function get_new_journal_number()
    {
        $year_month = date('Ym'); // Format: 202601
        $prefix = 'JU/' . $year_month . '/';
        
        // Cari nomor jurnal terakhir dengan prefix bulan ini
        $this->db->select('journal_no');
        $this->db->from('journal_header');
        $this->db->like('journal_no', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $result = $this->db->get()->row();
        
        if ($result) {
            // Ekstrak nomor urut dari journal_no terakhir
            // Contoh: JU/202601/0001 -> ambil 0001
            $last_number = (int) substr($result->journal_no, -4);
            $next_number = $last_number + 1;
        } else {
            // Jika belum ada jurnal bulan ini, mulai dari 0001
            $next_number = 1;
        }
        
        // Format dengan leading zeros (4 digit)
        $new_number = $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
        
        return $new_number;
    }

    public function post_journal($header_id, $user_id)
    {
        $this->db->where('id', $header_id);
        $this->db->where('status', 'Draft');
        return $this->db->update('journal_header', [
            'status' => 'Posted',
            'posted_by' => $user_id,
            'posted_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function delete_draft_journal($header_id)
    {
        $this->db->trans_start();
        $this->db->delete('journal_details', ['journal_header_id' => $header_id]);
        $this->db->delete('journal_header', ['id' => $header_id, 'status' => 'Draft']);
        $affected_rows = $this->db->affected_rows();
        $this->db->trans_complete();
        return ($this->db->trans_status() && $affected_rows > 0);
    }

    public function update_journal($header_id, $header_data, $details_data)
    {
        $this->db->trans_start();

        // Update header (hanya jika status masih Draft)
        $this->db->where('id', $header_id);
        $this->db->where('status', 'Draft');
        $this->db->update('journal_header', $header_data);
        
        if ($this->db->affected_rows() == 0) {
            $this->db->trans_rollback();
            return false; // Jurnal tidak ditemukan atau sudah dipost
        }

        // Hapus detail lama
        $this->db->delete('journal_details', ['journal_header_id' => $header_id]);
        
        // Insert detail baru
        $rate = (float) $header_data['exchange_rate'];
        foreach ($details_data as $detail) {
            if (!empty($detail['account_id']) && ($detail['debit'] > 0 || $detail['kredit'] > 0)) {
                $detail['journal_header_id'] = $header_id;
                
                $detail['debit_local'] = (float) $detail['debit'] * $rate;
                $detail['kredit_local'] = (float) $detail['kredit'] * $rate;
                
                $this->db->insert('journal_details', $detail);
            }
        }

        $this->db->trans_complete();
        return ($this->db->trans_status() !== FALSE);
    }

    public function can_edit_journal($header_id)
    {
        $this->db->select('status');
        $this->db->where('id', $header_id);
        $journal = $this->db->get('journal_header')->row();
        
        return ($journal && $journal->status === 'Draft');
    }
    
    public function process_import_data($sheet_data)
    {
        $details = [];
        $accounts = $this->get_postable_accounts();
        $account_map = [];
        foreach ($accounts as $acc) {
            $account_map[trim($acc->account_code)] = $acc->id;
        }

        foreach ($sheet_data as $row_num => $row) {
            if ($row_num == 1) continue; 
            $account_code = trim($row['A']);
            $debit = (float) $row['C'];
            $kredit = (float) $row['D'];
            $description = $row['E'];
            if (empty($account_code) || ($debit == 0 && $kredit == 0)) {
                continue; 
            }
            $account_id = $account_map[$account_code] ?? null;
            if ($account_id === null) {
                continue; 
            }
            $details[] = [
                'account_id' => $account_id,
                'debit' => $debit,
                'kredit' => $kredit,
                'description' => $description
            ];
        }
        return $details;
    }

    public function get_currencies()
    {
        $this->db->where('group_code', 'CURRENCY');
        $this->db->where('status', 'active');
        $this->db->order_by('code', 'ASC');
        return $this->db->get('common_code')->result();
    }

    public function get_postable_accounts()
    {
        $this->db->select('id, account_code, account_name, currency');
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }

    public function get_details_by_header_id($header_id)
    {
        $this->db->select('jd.*, coa.account_code, coa.account_name');
        $this->db->from('journal_details jd');
        $this->db->join('chart_of_accounts coa', 'jd.account_id = coa.id');
        $this->db->where('jd.journal_header_id', $header_id);
        $this->db->order_by('jd.id', 'ASC');
        return $this->db->get()->result();
    }

    public function insert_journal($header_data, $details_data)
    {
        $this->db->trans_start();

        $this->db->insert('journal_header', $header_data);
        $header_id = $this->db->insert_id();
        
        $rate = (float) $header_data['exchange_rate'];

        foreach ($details_data as $detail) {
            if (!empty($detail['account_id']) && ($detail['debit'] > 0 || $detail['kredit'] > 0)) {
                $detail['journal_header_id'] = $header_id;
                
                $detail['debit_local'] = (float) $detail['debit'] * $rate;
                $detail['kredit_local'] = (float) $detail['kredit'] * $rate;
                
                $this->db->insert('journal_details', $detail);
            }
        }

        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? FALSE : $header_id;
    }

    /**
     * ✅ Process Batch Import - Multiple Transactions from Excel
     * Pemisah transaksi menggunakan kolom Reference
     * 
     * Format Excel:
     * Column A: Reference (penanda transaksi baru jika tidak kosong)
     * Column B: Tanggal (YYYY-MM-DD)
     * Column C: Keterangan Jurnal
     * Column D: Kode Akun
     * Column E: Nama Akun (optional, untuk validasi)
     * Column F: Debit
     * Column G: Kredit
     * Column H: Keterangan Detail
     * 
     * PENTING: 
     * - Setiap kali kolom A (Reference) TIDAK KOSONG = TRANSAKSI BARU
     * - Tidak peduli apakah itu di debit atau kredit
     * - Reference kosong = lanjutan detail dari transaksi sebelumnya
     */
    public function process_batch_import($sheet_data, $user_id)
    {
        // Ambil semua akun yang bisa di-post
        $accounts = $this->get_postable_accounts();
        $account_map = [];
        foreach ($accounts as $acc) {
            $account_map[trim($acc->account_code)] = $acc;
        }

        $transactions = [];
        $current_transaction = null;
        $errors = [];

        // Parse data mulai dari baris ke-5 (setelah header)
        foreach ($sheet_data as $row_num => $row) {
            if ($row_num < 5) continue; // Skip header rows
            
            $reference = trim($row['A'] ?? '');
            $date = trim($row['B'] ?? '');
            $journal_desc = trim($row['C'] ?? '');
            
            // ✅ Kode akun bisa berupa string atau number dari Excel
            $account_code_raw = $row['D'] ?? '';
            $account_code = trim(strval($account_code_raw));
            
            // ✅ Ambil nilai ASLI dari Excel (bukan formatted value)
            // Excel menyimpan 50000000 sebagai number, bukan "50.000.000"
            $debit = abs((float) ($row['F'] ?? 0));
            $kredit = abs((float) ($row['G'] ?? 0));
            $detail_desc = trim($row['H'] ?? '');

            // Skip baris yang benar-benar kosong semua
            if (empty($reference) && empty($account_code) && $debit == 0 && $kredit == 0) {
                continue;
            }

            // ✅ LOGIKA PEMISAH: Setiap ada Reference TIDAK KOSONG = Transaksi Baru
            // Tidak peduli di baris kredit atau debit, yang penting Reference terisi
            if (!empty($reference)) {
                // Simpan transaksi sebelumnya jika ada
                if ($current_transaction !== null) {
                    $transactions[] = $current_transaction;
                }

                // Mulai transaksi baru
                $current_transaction = [
                    'reference' => $reference,
                    'date' => $date,
                    'description' => !empty($journal_desc) ? $journal_desc : $reference,
                    'details' => []
                ];
            }

            // Validasi: harus ada transaksi aktif
            if ($current_transaction === null) {
                $errors[] = "Baris {$row_num}: Tidak ada reference untuk memulai transaksi. Pastikan baris pertama memiliki Reference.";
                continue;
            }

            // Jika baris ini hanya berisi reference tanpa detail akun, skip (akan diproses sebagai header saja)
            if (empty($account_code)) {
                // Tapi update info jika ada tanggal/keterangan di baris reference
                if (!empty($date) && empty($current_transaction['date'])) {
                    $current_transaction['date'] = $date;
                }
                if (!empty($journal_desc) && $current_transaction['description'] == $current_transaction['reference']) {
                    $current_transaction['description'] = $journal_desc;
                }
                continue;
            }

            // Validasi akun
            if (!isset($account_map[$account_code])) {
                $errors[] = "Baris {$row_num}: Kode akun '{$account_code}' tidak ditemukan atau tidak aktif";
                continue;
            }

            // Validasi debit/kredit
            if ($debit == 0 && $kredit == 0) {
                $errors[] = "Baris {$row_num}: Debit dan Kredit tidak boleh kosong semua";
                continue;
            }

            if ($debit != 0 && $kredit != 0) {
                $errors[] = "Baris {$row_num}: Debit dan Kredit tidak boleh diisi bersamaan";
                continue;
            }

            // Update tanggal/keterangan jika baris detail punya info lebih lengkap
            if (!empty($date) && empty($current_transaction['date'])) {
                $current_transaction['date'] = $date;
            }
            if (!empty($journal_desc) && $current_transaction['description'] == $current_transaction['reference']) {
                $current_transaction['description'] = $journal_desc;
            }

            // Tambahkan detail ke transaksi saat ini
            $current_transaction['details'][] = [
                'account_id' => $account_map[$account_code]->id,
                'account_code' => $account_code,
                'debit' => $debit,
                'kredit' => $kredit,
                'description' => $detail_desc
            ];
        }

        // Simpan transaksi terakhir
        if ($current_transaction !== null) {
            $transactions[] = $current_transaction;
        }

        // Jika ada error, return
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => implode('; ', $errors)
            ];
        }

        // Jika tidak ada transaksi valid
        if (empty($transactions)) {
            return [
                'success' => false,
                'message' => 'Tidak ada transaksi valid yang ditemukan dalam file'
            ];
        }

        // Proses setiap transaksi
        $this->db->trans_start();
        $journal_numbers = [];
        
        foreach ($transactions as $trans) {
            // Validasi tanggal
            if (empty($trans['date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $trans['date'])) {
                $this->db->trans_rollback();
                return [
                    'success' => false,
                    'message' => "Reference '{$trans['reference']}': Format tanggal harus YYYY-MM-DD"
                ];
            }

            // Hitung total debit dan kredit
            $total_debit = 0;
            $total_kredit = 0;
            
            foreach ($trans['details'] as $detail) {
                $total_debit += $detail['debit'];
                $total_kredit += $detail['kredit'];
            }

            // Validasi balance
            if (abs($total_debit - $total_kredit) > 0.01) {
                $this->db->trans_rollback();
                return [
                    'success' => false,
                    'message' => "Reference '{$trans['reference']}': Tidak seimbang (Debit: {$total_debit}, Kredit: {$total_kredit})"
                ];
            }

            // Generate nomor jurnal baru
            $journal_no = $this->get_new_journal_number();

            // Insert header
            $header_data = [
                'journal_no' => $journal_no,
                'journal_date' => $trans['date'],
                'description' => !empty($trans['description']) ? $trans['description'] : $trans['reference'],
                'currency' => 'IDR',
                'exchange_rate' => 1,
                'total_debit' => $total_debit,
                'total_kredit' => $total_kredit,
                'total_debit_local' => $total_debit,
                'total_kredit_local' => $total_kredit,
                'created_by' => $user_id,
                'status' => 'Draft',
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert('journal_header', $header_data);
            $header_id = $this->db->insert_id();

            // Insert details
            foreach ($trans['details'] as $detail) {
                $detail_data = [
                    'journal_header_id' => $header_id,
                    'account_id' => $detail['account_id'],
                    'debit' => $detail['debit'],
                    'kredit' => $detail['kredit'],
                    'debit_local' => $detail['debit'],
                    'kredit_local' => $detail['kredit'],
                    'description' => $detail['description']
                ];
                
                $this->db->insert('journal_details', $detail_data);
            }

            $journal_numbers[] = $journal_no;
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan ke database'
            ];
        }

        return [
            'success' => true,
            'total_transactions' => count($transactions),
            'journal_numbers' => $journal_numbers
        ];
    }
}