<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Asset_model extends CI_Model {

    private $table = 'assets';

    public function get_all() {
        return $this->db->where('status', 'active')->order_by('nama_asset', 'ASC')->get($this->table)->result();
    }

    private function _apply_filter($keyword = null) {
        if ($keyword) {
            $this->db->group_start()
                ->like('kode_asset', $keyword)
                ->or_like('nama_asset', $keyword)
                ->or_like('kategori', $keyword)
                ->or_like('satuan', $keyword)
            ->group_end();
        }
    }

    public function get_paginated($limit, $offset, $keyword = null) {
        $this->_apply_filter($keyword);

        // Nama akun ikut ditarik supaya list bisa menampilkan mapping COA
        // tanpa query tambahan per baris.
        $this->db->select('a.*,
            ca.account_code AS coa_asset_code,  ca.account_name AS coa_asset_name,
            ck.account_code AS coa_akum_code,   ck.account_name AS coa_akum_name,
            cb.account_code AS coa_beban_code,  cb.account_name AS coa_beban_name');
        $this->db->from($this->table . ' a');
        $this->db->join('chart_of_accounts ca', 'ca.id = a.coa_asset_id', 'left');
        $this->db->join('chart_of_accounts ck', 'ck.id = a.coa_akum_id', 'left');
        $this->db->join('chart_of_accounts cb', 'cb.id = a.coa_beban_id', 'left');
        $this->db->order_by('a.id', 'DESC');
        $this->db->limit($limit, $offset);

        return $this->db->get()->result();
    }

    public function count_filtered($keyword = null) {
        $this->_apply_filter($keyword);
        return $this->db->count_all_results($this->table);
    }

    public function get_by_id($id) {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }

    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    // Soft delete, sama seperti Jasa_model::delete()
    public function delete($id) {
        $this->db->where('id', $id);
        return $this->db->update($this->table, ['status' => 'inactive']);
    }

    public function generate_kode() {
        $prefix = 'AST-';

        $this->db->like('kode_asset', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get($this->table)->row();

        if ($last) {
            $num = (int) substr($last->kode_asset, strlen($prefix));
            $num++;
        } else {
            $num = 1;
        }

        return sprintf("%s%04d", $prefix, $num);
    }

    /**
     * Daftar akun untuk dropdown mapping COA.
     *
     * is_header=0 wajib difilter: akun induk seperti '1.109 Aset Tetap'
     * tidak boleh dijurnal. normal_balance memisahkan akun aset biasa dari
     * akun akumulasi penyusutan, yang dua-duanya bertipe 'Aset'.
     *
     * @param string|array $type           Aset|Biaya|HPP, atau array beberapa tipe
     *                                     (beban penyusutan pabrik di COA Acropoly
     *                                     bertipe HPP: 5.300.020-022)
     * @param string|null  $normal_balance Debit|Kredit, atau NULL untuk semua
     */
    public function get_coa_options($type, $normal_balance = null) {
        $this->db->where_in('account_type', (array) $type);
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        if ($normal_balance !== null) {
            $this->db->where('normal_balance', $normal_balance);
        }
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }

    /**
     * Sama seperti get_coa_options(), tapi dipecah dua: akun yang berada di
     * rentang kode yang relevan ('disarankan') dan sisanya ('lainnya').
     *
     * Alasannya: filter account_type saja masih menyisakan 31 akun aset dan 65
     * akun biaya, sehingga mudah salah pilih -- mis. 'Accounts Receivable'
     * sebagai akun aktiva tetap, atau 'PPh 25' sebagai beban penyusutan.
     * Rentang di bawah cocok dipakai sebagai penuntun, bukan pembatas keras:
     * akun di luar rentang tetap bisa dipilih kalau COA nanti direstrukturisasi.
     *
     * @param string|array $prefix Awalan kode akun yang dianggap relevan, mis. '1.109'
     *                             atau ['5.300.02', '6.300']
     */
    public function get_coa_grouped($type, $normal_balance = null, $prefix = null) {
        $all = $this->get_coa_options($type, $normal_balance);
        $prefixes = $prefix === null ? [] : (array) $prefix;

        $grouped = ['disarankan' => [], 'lainnya' => []];
        foreach ($all as $acc) {
            $relevan = false;
            foreach ($prefixes as $px) {
                if ($px !== '' && strpos($acc->account_code, $px) === 0) { $relevan = true; break; }
            }
            $grouped[$relevan ? 'disarankan' : 'lainnya'][] = $acc;
        }
        return $grouped;
    }
}
