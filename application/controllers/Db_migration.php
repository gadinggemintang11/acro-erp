<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Db_migration — halaman "Migrasi Database" (satu klik) untuk server
 * production yang cuma bisa diakses lewat cPanel (tidak ada shell), sehingga
 * `db/deploy_migrations.sh` tidak bisa dijalankan di sana.
 * Dibuat 2026-09-06 atas permintaan user.
 *
 * PEMBATASAN AKSES SENGAJA KETAT — dan sengaja TIDAK memakai menu Role Access:
 * halaman ini adalah alat bootstrap (dipakai justru SEBELUM tabel/menu baru
 * ada), jadi menggantungkan aksesnya ke baris `menus` malah bikin
 * ayam-dan-telur. Gerbangnya `is_admin()` (role_id = 1) — paling sempit yang
 * ada, dan tidak bisa tidak sengaja di-grant ke role lain lewat Role Access.
 *
 * Yang bisa dieksekusi HANYA file yang terdaftar di `db/deploy_migrations.sh`
 * (lihat Migration_runner_model) — tidak ada SQL bebas dari input user.
 */
class Db_migration extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        if (!is_admin()) {
            show_error('Halaman migrasi database hanya untuk Admin.', 403);
        }
        $this->load->model('Migration_runner_model');
        $this->load->helper('url');
    }

    public function index() {
        $rows = $this->Migration_runner_model->list_migrations();

        $count = ['pending' => 0, 'applied' => 0, 'skipped' => 0, 'failed' => 0, 'missing' => 0];
        foreach ($rows as $r) {
            if (!$r->exists) { $count['missing']++; continue; }
            $count[$r->status] = ($count[$r->status] ?? 0) + 1;
        }

        $data = [
            'title'      => 'Migrasi Database',
            'migrations' => $rows,
            'count'      => $count,
            'db_name'    => $this->db->database,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('db_migration/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Jalankan migrasi. Tanpa parameter `file` = jalankan SEMUA yang masih
     * pending, berurutan, BERHENTI di kegagalan pertama (biar tidak menumpuk
     * kerusakan). Wajib POST + centang konfirmasi backup.
     */
    public function run() {
        if ($this->input->method() !== 'post') { show_error('Metode ilegal.', 405); return; }
        if (!$this->input->post('confirm_backup')) {
            $this->session->set_flashdata('error', 'Centang dulu konfirmasi bahwa database sudah di-backup.');
            return redirect('db_migration');
        }

        $uid    = $this->session->userdata('user_id');
        $single = (string) $this->input->post('file');
        $log    = [];

        if ($single !== '') {
            $targets = [$single];
        } else {
            $targets = [];
            foreach ($this->Migration_runner_model->list_migrations() as $m) {
                if ($m->exists && $m->status === 'pending') $targets[] = $m->filename;
            }
        }

        if (empty($targets)) {
            $this->session->set_flashdata('success', 'Tidak ada migrasi yang perlu dijalankan — semuanya sudah beres.');
            return redirect('db_migration');
        }

        $ok = 0; $skip = 0; $fail = 0;
        foreach ($targets as $file) {
            $res = $this->Migration_runner_model->run_one($file, $uid);
            $log[] = ['file' => $file] + $res;
            if ($res['status'] === 'failed') { $fail++; break; } // stop di kegagalan pertama
            if ($res['status'] === 'skipped') $skip++; else $ok++;
        }

        add_log('MIGRATION', 'Database', 'Menjalankan migrasi: ' . count($log) . ' file (berhasil ' . $ok . ', dilewati ' . $skip . ', gagal ' . $fail . ').');

        $this->session->set_flashdata('migration_log', $log);
        if ($fail) {
            $this->session->set_flashdata('error', 'Migrasi BERHENTI karena ada yang gagal. Perbaiki dulu sebelum melanjutkan — lihat detail di bawah.');
        } else {
            $this->session->set_flashdata('success', "Selesai: $ok migrasi dijalankan, $skip dilewati (sudah ada sebelumnya).");
        }
        redirect('db_migration');
    }

    /** Tandai satu migrasi sebagai sudah dijalankan TANPA mengeksekusinya. */
    public function mark() {
        if ($this->input->method() !== 'post') { show_error('Metode ilegal.', 405); return; }
        $file = (string) $this->input->post('file');
        if ($this->Migration_runner_model->mark_applied($file, $this->session->userdata('user_id'))) {
            add_log('MIGRATION', 'Database', 'Menandai migrasi sudah dijalankan (tanpa eksekusi): ' . $file);
            $this->session->set_flashdata('success', 'Ditandai sudah dijalankan: ' . $file);
        } else {
            $this->session->set_flashdata('error', 'File migrasi tidak dikenal.');
        }
        redirect('db_migration');
    }

    /** Hapus catatan 1 migrasi supaya bisa dicoba jalankan lagi. */
    public function forget() {
        if ($this->input->method() !== 'post') { show_error('Metode ilegal.', 405); return; }
        $file = (string) $this->input->post('file');
        if ($this->Migration_runner_model->forget($file)) {
            add_log('MIGRATION', 'Database', 'Menghapus catatan migrasi (bisa dijalankan ulang): ' . $file);
            $this->session->set_flashdata('success', 'Catatan dihapus, migrasi bisa dijalankan lagi: ' . $file);
        } else {
            $this->session->set_flashdata('error', 'File migrasi tidak dikenal.');
        }
        redirect('db_migration');
    }
}
