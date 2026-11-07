<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * KURS PAJAK KEMENKEU
 * --------------------------------------------------------------
 * Sumber resmi: https://fiskal.kemenkeu.go.id/informasi-publik/kurs-pajak
 * - Kurs Pajak update tiap Rabu (berlaku Rabu s/d Selasa minggu depan).
 * - Snapshot month-end disimpan tiap tanggal terakhir bulan untuk closing.
 *
 * Cron yang disarankan (lihat README di bawah file ini):
 *   # tiap Rabu 09:00 → sync kurs mingguan
 *   0 9 * * 3  php /path/to/index.php kurs_pajak cron_weekly
 *   # tiap hari 23:55 → cek bila hari ini tanggal terakhir bulan, snapshot
 *   55 23 * * * php /path/to/index.php kurs_pajak cron_month_end
 */
class Kurs_pajak extends MY_Controller {

    protected $module_name = 'Kurs Pajak';

    private $source_url = 'https://fiskal.kemenkeu.go.id/informasi-publik/kurs-pajak';
    private $is_cli;

    public function __construct() {
        parent::__construct();
        $this->is_cli = (php_sapi_name() === 'cli' || defined('STDIN'));
        if (!$this->is_cli) {
            check_login();
        }
        $this->load->model('Kurs_pajak_model');
        $this->load->helper(['form', 'url']);
    }

    // ============================================================
    // UI: LIST
    // ============================================================
    public function index() {
        if (!check_access('kurs_pajak', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $filter = [
            'currency_code'  => $this->input->get('currency_code'),
            'year'           => $this->input->get('year'),
            'month'          => $this->input->get('month'),
            'snapshot_only'  => $this->input->get('snapshot_only') ? 1 : 0,
        ];

        $data = [
            'title'         => lang_line('title_kp_index'),
            'rates'         => $this->Kurs_pajak_model->get_all($filter),
            'latest'        => $this->Kurs_pajak_model->get_latest_per_currency(),
            'sync_logs'     => $this->Kurs_pajak_model->get_sync_logs(10),
            'currencies'    => $this->Kurs_pajak_model->supported_currencies,
            'filter'        => $filter,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('kurs_pajak/index', $data);
        $this->load->view('templates/footer');
    }

    // ============================================================
    // UI: MANUAL INPUT
    // ============================================================
    public function add() {
        if (!check_access('kurs_pajak', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        $currency_code = strtoupper(trim($this->input->post('currency_code')));
        $rate          = (float) str_replace(',', '.', $this->input->post('rate'));
        $start         = $this->input->post('effective_date_start');
        $end           = $this->input->post('effective_date_end');
        $is_snapshot   = $this->input->post('is_month_end_snapshot') ? 1 : 0;

        if (!in_array($currency_code, $this->Kurs_pajak_model->supported_currencies, true)) {
            $this->session->set_flashdata('error', 'Mata uang tidak didukung.');
            redirect('kurs_pajak');
            return;
        }
        if ($rate <= 0 || empty($start) || empty($end)) {
            $this->session->set_flashdata('error', 'Data tidak lengkap atau kurs tidak valid.');
            redirect('kurs_pajak');
            return;
        }
        if (strtotime($end) < strtotime($start)) {
            $this->session->set_flashdata('error', 'Tanggal akhir tidak boleh sebelum tanggal mulai.');
            redirect('kurs_pajak');
            return;
        }

        $data = [
            'currency_code'         => $currency_code,
            'rate'                  => $rate,
            'effective_date_start'  => $start,
            'effective_date_end'    => $end,
            'is_month_end_snapshot' => $is_snapshot,
            'snapshot_month'        => $is_snapshot ? date('Y-m', strtotime($end)) : null,
            'source'                => 'manual',
            'source_url'            => null,
            'fetched_at'            => date('Y-m-d H:i:s'),
            'created_by'            => $this->session->userdata('user_id'),
        ];

        $res = $this->Kurs_pajak_model->upsert($data);
        $this->Kurs_pajak_model->log_sync([
            'sync_type'      => 'manual_input',
            'status'         => 'success',
            'total_inserted' => $res['action'] === 'inserted' ? 1 : 0,
            'total_updated'  => $res['action'] === 'updated' ? 1 : 0,
            'message'        => "Manual input {$currency_code} = {$rate} ({$start} s/d {$end})",
            'triggered_by'   => $this->session->userdata('user_id'),
        ]);

        $this->_log('CREATE', "Input manual kurs pajak {$currency_code} = {$rate} ({$start} s/d {$end})");
        $this->session->set_flashdata('success', 'Kurs pajak berhasil disimpan.');
        redirect('kurs_pajak');
    }

    public function update($id) {
        if (!check_access('kurs_pajak', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }
        $old = $this->Kurs_pajak_model->get_by_id($id);
        if (!$old) show_404();

        $data = [
            'rate'                  => (float) str_replace(',', '.', $this->input->post('rate')),
            'effective_date_start'  => $this->input->post('effective_date_start'),
            'effective_date_end'    => $this->input->post('effective_date_end'),
            'is_month_end_snapshot' => $this->input->post('is_month_end_snapshot') ? 1 : 0,
        ];
        $data['snapshot_month'] = $data['is_month_end_snapshot']
            ? date('Y-m', strtotime($data['effective_date_end']))
            : null;

        if ($data['rate'] <= 0) {
            $this->session->set_flashdata('error', 'Kurs tidak valid.');
            redirect('kurs_pajak');
            return;
        }

        $this->Kurs_pajak_model->update($id, $data);
        $this->_log('UPDATE', "Edit kurs pajak ID {$id} ({$old->currency_code})");
        $this->session->set_flashdata('success', 'Kurs pajak berhasil diperbarui.');
        redirect('kurs_pajak');
    }

    public function delete($id) {
        if (!check_access('kurs_pajak', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }
        $row = $this->Kurs_pajak_model->get_by_id($id);
        if (!$row) show_404();

        $this->Kurs_pajak_model->delete($id);
        $this->_log('DELETE', "Hapus kurs pajak {$row->currency_code} periode {$row->effective_date_start}");
        $this->session->set_flashdata('success', 'Data kurs pajak dihapus.');
        redirect('kurs_pajak');
    }

    // ============================================================
    // AJAX: Get kurs aktif untuk dipakai modul lain (PO, AP/AR Invoice, dll.)
    //   GET /kurs_pajak/ajax_get_rate?currency=USD&date=2026-05-19
    //
    // Response JSON:
    //   { ok: true,  rate: 15750.5000, currency_code: "USD",
    //     effective_date_start: "2026-05-13", effective_date_end: "2026-05-19",
    //     source: "auto" }
    //   { ok: false, message: "..." }
    // ============================================================
    public function ajax_get_rate() {
        if (!$this->session->userdata('logged_in')) {
            $this->output->set_status_header(401)
                         ->set_content_type('application/json')
                         ->set_output(json_encode(['ok' => false, 'message' => 'Unauthorized']));
            return;
        }

        $currency = strtoupper(trim($this->input->get('currency') ?: $this->input->get('currency_code')));
        $on_date  = $this->input->get('date') ?: date('Y-m-d');

        $this->output->set_content_type('application/json');

        if ($currency === '' || $currency === 'IDR') {
            $this->output->set_output(json_encode([
                'ok' => true, 'rate' => 1.0, 'currency_code' => 'IDR',
                'effective_date_start' => $on_date, 'effective_date_end' => $on_date,
                'source' => 'static',
            ]));
            return;
        }
        if (!in_array($currency, $this->Kurs_pajak_model->supported_currencies, true)) {
            $this->output->set_output(json_encode([
                'ok' => false, 'message' => "Mata uang {$currency} tidak didukung kurs pajak otomatis. Input manual."
            ]));
            return;
        }

        $row = $this->Kurs_pajak_model->get_active_rate($currency, $on_date);
        if (!$row) {
            $this->output->set_output(json_encode([
                'ok' => false,
                'message' => "Belum ada kurs pajak {$currency} untuk tanggal {$on_date}. Silakan input manual atau klik Sync Sekarang di menu Kurs Pajak."
            ]));
            return;
        }

        $this->output->set_output(json_encode([
            'ok' => true,
            'rate' => (float) $row->rate,
            'currency_code' => $row->currency_code,
            'effective_date_start' => $row->effective_date_start,
            'effective_date_end' => $row->effective_date_end,
            'source' => $row->source,
        ]));
    }

    // ============================================================
    // MANUAL SYNC (tombol "Sync Sekarang" di UI)
    // ============================================================
    public function sync_now() {
        if (!check_access('kurs_pajak', 'add')) {
            show_error('Anda tidak memiliki izin melakukan sync.', 403);
        }
        $result = $this->_fetch_and_store('manual_trigger', $this->session->userdata('user_id'));
        if ($result['status'] === 'success') {
            $this->session->set_flashdata('success',
                "Sync berhasil: {$result['inserted']} baru, {$result['updated']} diperbarui.");
        } else {
            $this->session->set_flashdata('error', 'Sync gagal: ' . $result['message']);
        }
        redirect('kurs_pajak');
    }

    // ============================================================
    // CLI: WEEKLY CRON (tiap Rabu)
    //   php index.php kurs_pajak cron_weekly
    // ============================================================
    public function cron_weekly() {
        if (!$this->is_cli) show_404();
        $result = $this->_fetch_and_store('weekly_auto', null);
        $line = sprintf("[%s] weekly_auto status=%s inserted=%d updated=%d msg=%s\n",
            date('Y-m-d H:i:s'), $result['status'], $result['inserted'], $result['updated'], $result['message']);
        fwrite(STDOUT, $line);
    }

    // ============================================================
    // CLI: MONTH-END SNAPSHOT (jalan tiap hari, cek tanggal terakhir bulan)
    //   php index.php kurs_pajak cron_month_end
    // ============================================================
    public function cron_month_end() {
        if (!$this->is_cli) show_404();

        $today = date('Y-m-d');
        $last_day_of_month = date('Y-m-t');
        if ($today !== $last_day_of_month) {
            fwrite(STDOUT, "[{$today}] bukan akhir bulan, skip snapshot.\n");
            return;
        }

        $year_month = date('Y-m');
        $inserted = 0; $updated = 0; $messages = [];

        foreach ($this->Kurs_pajak_model->supported_currencies as $cc) {
            $active = $this->Kurs_pajak_model->get_active_rate($cc, $today);
            if (!$active) {
                $messages[] = "{$cc}: tidak ada kurs aktif untuk {$today}";
                continue;
            }
            $data = [
                'currency_code'         => $cc,
                'rate'                  => $active->rate,
                'effective_date_start'  => $today,
                'effective_date_end'    => $today,
                'is_month_end_snapshot' => 1,
                'snapshot_month'        => $year_month,
                'source'                => 'auto',
                'source_url'            => $this->source_url,
                'fetched_at'            => date('Y-m-d H:i:s'),
                'created_by'            => null,
            ];
            $res = $this->Kurs_pajak_model->upsert($data);
            if ($res['action'] === 'inserted') $inserted++; else $updated++;
            $messages[] = "{$cc}: snapshot {$year_month} = {$active->rate}";
        }

        $this->Kurs_pajak_model->log_sync([
            'sync_type'      => 'month_end_auto',
            'status'         => 'success',
            'total_inserted' => $inserted,
            'total_updated'  => $updated,
            'message'        => implode('; ', $messages),
            'triggered_by'   => null,
        ]);

        fwrite(STDOUT, "[{$today}] month_end_auto inserted={$inserted} updated={$updated} : " . implode('; ', $messages) . "\n");
    }

    // ============================================================
    // PRIVATE: fetch & parse Kemenkeu page, store rows
    // ============================================================
    private function _fetch_and_store($sync_type, $triggered_by) {
        $html = $this->_http_get($this->source_url);
        if ($html === false) {
            $msg = 'Gagal mengambil halaman Kemenkeu (timeout / network).';
            $this->Kurs_pajak_model->log_sync([
                'sync_type' => $sync_type, 'status' => 'failed',
                'total_inserted' => 0, 'total_updated' => 0,
                'message' => $msg, 'triggered_by' => $triggered_by,
            ]);
            return ['status' => 'failed', 'inserted' => 0, 'updated' => 0, 'message' => $msg];
        }

        $parsed = $this->_parse_kemenkeu_html($html);
        if (empty($parsed['rows'])) {
            $msg = 'Tidak menemukan baris kurs USD/CNY pada halaman Kemenkeu. Format mungkin berubah.';
            $this->Kurs_pajak_model->log_sync([
                'sync_type' => $sync_type, 'status' => 'failed',
                'total_inserted' => 0, 'total_updated' => 0,
                'message' => $msg, 'triggered_by' => $triggered_by,
            ]);
            return ['status' => 'failed', 'inserted' => 0, 'updated' => 0, 'message' => $msg];
        }

        $inserted = 0; $updated = 0; $detail = [];
        foreach ($parsed['rows'] as $row) {
            $data = [
                'currency_code'         => $row['currency_code'],
                'rate'                  => $row['rate'],
                'effective_date_start'  => $parsed['period_start'],
                'effective_date_end'    => $parsed['period_end'],
                'is_month_end_snapshot' => 0,
                'snapshot_month'        => null,
                'source'                => 'auto',
                'source_url'            => $this->source_url,
                'fetched_at'            => date('Y-m-d H:i:s'),
                'created_by'            => $triggered_by,
            ];
            $res = $this->Kurs_pajak_model->upsert($data);
            if ($res['action'] === 'inserted') $inserted++; else $updated++;
            $detail[] = "{$row['currency_code']}={$row['rate']}";
        }

        $msg = "Periode {$parsed['period_start']} s/d {$parsed['period_end']}. " . implode(', ', $detail);
        $this->Kurs_pajak_model->log_sync([
            'sync_type' => $sync_type, 'status' => 'success',
            'total_inserted' => $inserted, 'total_updated' => $updated,
            'message' => $msg, 'triggered_by' => $triggered_by,
        ]);

        return ['status' => 'success', 'inserted' => $inserted, 'updated' => $updated, 'message' => $msg];
    }

    private function _http_get($url) {
        if (!function_exists('curl_init')) return false;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (ACROPOLY-ERP KursPajak Sync)',
            CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200 || empty($body)) return false;
        return $body;
    }

    /**
     * Parser HTML halaman Kemenkeu.
     * Halaman menampilkan tabel kurs pajak dengan kolom:
     *   Mata Uang | Nilai | Berlaku Tanggal s/d Tanggal
     *
     * Strategi parser (defensive):
     *   1. Cari rentang tanggal periode (regex tanggal-tanggal Indonesia).
     *   2. Cari baris yang mengandung kode mata uang (USD / CNY)
     *      dengan nilai numerik di sekitarnya.
     *
     * Bila struktur HTML berubah, fungsi ini akan return rows kosong
     * — log_sync akan mencatat failure dan admin bisa input manual.
     */
    private function _parse_kemenkeu_html($html) {
        $result = ['period_start' => null, 'period_end' => null, 'rows' => []];

        // Strip tags untuk memudahkan regex
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        // Bulan Indonesia → angka
        $bulan_map = [
            'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
            'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
            'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12',
        ];
        $bulan_regex = implode('|', array_keys($bulan_map));

        // Cari "Berlaku ... s/d ..." atau "tanggal X bulan Y sampai dengan X bulan Y"
        $pattern = '/(\d{1,2})\s+(' . $bulan_regex . ')\s+(\d{4})\s*(?:s\.?\/?d\.?|sampai dengan|-)\s*(\d{1,2})\s+(' . $bulan_regex . ')\s+(\d{4})/i';
        if (preg_match($pattern, strtolower($text), $m)) {
            $result['period_start'] = sprintf('%04d-%s-%02d', (int)$m[3], $bulan_map[strtolower($m[2])], (int)$m[1]);
            $result['period_end']   = sprintf('%04d-%s-%02d', (int)$m[6], $bulan_map[strtolower($m[5])], (int)$m[4]);
        } else {
            // Fallback: anggap periode = Rabu ini s/d Selasa depan
            $today = new DateTime();
            $dow = (int) $today->format('N'); // 1=Mon..7=Sun
            $wed_offset = (3 - $dow);          // ke Rabu minggu ini
            $start = clone $today;
            $start->modify(($wed_offset >= 0 ? '+' : '') . $wed_offset . ' days');
            $end = clone $start;
            $end->modify('+6 days');
            $result['period_start'] = $start->format('Y-m-d');
            $result['period_end']   = $end->format('Y-m-d');
        }

        // Cari nilai USD & CNY. Pola umum di halaman Kemenkeu:
        //   "1 USD = Rp 15.xxx,xx" atau tabel "USD ... 15.xxx,xx ... 1"
        // Kita coba beberapa pola.
        foreach (['USD', 'CNY'] as $cc) {
            $rate = $this->_extract_rate_for_currency($text, $cc);
            if ($rate !== null) {
                $result['rows'][] = ['currency_code' => $cc, 'rate' => $rate];
            }
        }

        return $result;
    }

    private function _extract_rate_for_currency($text, $cc) {
        // Pola 1: "USD 15.123,4567" atau "USD 15,123.4567"
        // Pola 2: "1 USD = ... 15.123,45"
        $patterns = [
            '/\b' . preg_quote($cc, '/') . '\b[^0-9]{0,40}([0-9][0-9\.\,]{2,20})/i',
            '/1\s*' . preg_quote($cc, '/') . '\s*=?\s*(?:Rp\.?\s*)?([0-9][0-9\.\,]{2,20})/i',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $text, $m)) {
                $val = $this->_normalize_number($m[1]);
                if ($val > 0 && $val < 1000000) return $val;
            }
        }
        return null;
    }

    private function _normalize_number($raw) {
        $raw = trim($raw);
        // Hapus spasi
        $raw = str_replace(' ', '', $raw);
        // Format Indonesia: titik ribuan, koma desimal → ubah jadi titik desimal
        if (preg_match('/,\d{1,4}$/', $raw)) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } else {
            // Format internasional: koma ribuan
            $raw = str_replace(',', '', $raw);
        }
        return is_numeric($raw) ? (float) $raw : 0.0;
    }
}
