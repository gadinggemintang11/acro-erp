<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Piutang_model
 * Sumber data laporan "Daftar Piutang" (Account Receivable monitoring).
 *
 * Read-only. Menggabungkan:
 *  - Faktur AR yang sudah diposting (ar_invoice_header) -> outstanding & aging
 *  - FG Delivery / Scrap Out yang sudah Confirmed tapi BELUM difaktur (delivered not billed)
 *  - Rekap per customer
 *
 * Catatan currency: ar_invoice_header.total_paid disimpan dalam mata uang faktur
 * (sama seperti total_invoice). Nilai lokal (IDR) didekati dengan
 * outstanding * exchange_rate untuk keperluan rekap.
 */
class Piutang_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_customers()
    {
        $sql = "
            SELECT c.id, c.kode_customer, c.nama_customer
            FROM customers c
            WHERE EXISTS (
                    SELECT 1 FROM ar_invoice_header ar
                    WHERE ar.customer_id = c.id AND ar.status = 'Posted'
                  )
               OR EXISTS (
                    SELECT 1 FROM fg_delivery_header fdh
                    WHERE fdh.customer_id = c.id AND fdh.status = 'Confirmed'
                  )
               OR EXISTS (
                    SELECT 1 FROM scrap_out_header soh
                    WHERE soh.destination = c.id AND soh.status = 'Confirmed'
                  )
            ORDER BY c.nama_customer ASC
        ";
        return $this->db->query($sql)->result();
    }

    /**
     * Daftar faktur piutang (AR) posted beserta sisa & umur.
     *
     * @param array $filters customer_id, payment_status, only_outstanding (bool)
     */
    public function get_invoice_list($filters = [])
    {
        $where  = "ar.status = 'Posted'";
        $params = [];

        if (!empty($filters['customer_id'])) {
            $where .= " AND ar.customer_id = ?";
            $params[] = (int) $filters['customer_id'];
        }
        if (!empty($filters['payment_status'])) {
            $where .= " AND ar.payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        if (!empty($filters['only_outstanding'])) {
            $where .= " AND (ar.total_invoice - ar.total_paid) > 0.01";
        }

        $sql = "
            SELECT
                ar.id,
                ar.invoice_no,
                ar.invoice_date,
                ar.due_date,
                ar.currency,
                ar.exchange_rate,
                ar.total_invoice,
                ar.total_paid,
                (ar.total_invoice - ar.total_paid)                       AS outstanding,
                ((ar.total_invoice - ar.total_paid) * ar.exchange_rate)  AS outstanding_local,
                ar.total_invoice_local,
                ar.payment_status,
                ar.source_type,
                c.kode_customer,
                c.nama_customer,
                DATEDIFF(CURDATE(), ar.due_date) AS days_overdue
            FROM ar_invoice_header ar
            JOIN customers c ON c.id = ar.customer_id
            WHERE {$where}
            ORDER BY ((ar.total_invoice - ar.total_paid) * ar.exchange_rate) DESC, ar.due_date ASC, ar.id DESC
        ";
        return $this->db->query($sql, $params)->result();
    }

    /**
     * Pengiriman (FG Delivery + Scrap Out) yang sudah Confirmed tapi belum difaktur.
     * "Barang sudah dikirim tapi belum ditagih" (accrued receivable).
     *
     * @param array $filters customer_id
     */
    public function get_uninvoiced_delivery($filters = [])
    {
        $fg_where     = "fdh.status = 'Confirmed'";
        $scrap_where  = "soh.status = 'Confirmed'";
        $fg_params    = [];
        $scrap_params = [];

        if (!empty($filters['customer_id'])) {
            $fg_where .= " AND fdh.customer_id = ?";
            $fg_params[] = (int) $filters['customer_id'];
            $scrap_where .= " AND soh.destination = ?";
            $scrap_params[] = (int) $filters['customer_id'];
        }

        // --- FG Delivery (Surat Jalan barang jadi) ---
        $fg = $this->db->query("
            SELECT
                'FG_DELIVERY'         AS source_type,
                fdh.id                AS source_id,
                fdh.delivery_number   AS doc_no,
                fdh.delivery_date     AS doc_date,
                c.kode_customer,
                c.nama_customer,
                MAX(sc.currency_code) AS currency,
                COALESCE(SUM(fdd.qty_shipped * sc.unit_price), 0) AS est_value,
                GROUP_CONCAT(DISTINCT sc.sc_number ORDER BY sc.sc_number SEPARATOR ', ') AS ref_no,
                DATEDIFF(CURDATE(), fdh.delivery_date) AS age_days
            FROM fg_delivery_header fdh
            JOIN customers c ON c.id = fdh.customer_id
            LEFT JOIN fg_delivery_details fdd ON fdd.fg_delivery_header_id = fdh.id
            LEFT JOIN sales_contracts sc ON fdd.sales_contract_id = sc.id
            WHERE {$fg_where}
              AND NOT EXISTS (
                    SELECT 1 FROM ar_invoice_header ar
                    WHERE ar.source_type = 'FG_DELIVERY'
                      AND JSON_SEARCH(ar.source_header_ids, 'one', CAST(fdh.id AS CHAR)) IS NOT NULL
                      AND ar.status != 'Cancelled'
                  )
            GROUP BY fdh.id
        ", $fg_params)->result();

        // --- Scrap Out (penjualan scrap) ---
        $scrap = $this->db->query("
            SELECT
                'SCRAP_OUT'   AS source_type,
                soh.id        AS source_id,
                soh.doc_no    AS doc_no,
                soh.doc_date  AS doc_date,
                c.kode_customer,
                c.nama_customer,
                'IDR'         AS currency,
                COALESCE(SUM(sod.qty * sod.sales_value_per_unit), 0) AS est_value,
                soh.bc_number AS ref_no,
                DATEDIFF(CURDATE(), soh.doc_date) AS age_days
            FROM scrap_out_header soh
            JOIN customers c ON c.id = soh.destination
            LEFT JOIN scrap_out_detail sod ON sod.header_id = soh.id
            WHERE {$scrap_where}
              AND NOT EXISTS (
                    SELECT 1 FROM ar_invoice_header ar
                    WHERE ar.source_type = 'SCRAP_OUT'
                      AND JSON_SEARCH(ar.source_header_ids, 'one', CAST(soh.id AS CHAR)) IS NOT NULL
                      AND ar.status != 'Cancelled'
                  )
            GROUP BY soh.id
        ", $scrap_params)->result();

        return array_merge($fg, $scrap);
    }

    /**
     * Rekap per customer: faktur, diterima, sisa piutang (IDR/local).
     *
     * @param array $filters customer_id
     */
    public function get_summary_by_customer($filters = [])
    {
        $where  = "(inv.inv_count > 0)";
        $params = [];

        if (!empty($filters['customer_id'])) {
            $where .= " AND c.id = ?";
            $params[] = (int) $filters['customer_id'];
        }

        $sql = "
            SELECT
                c.id,
                c.kode_customer,
                c.nama_customer,
                COALESCE(inv.inv_count, 0)             AS inv_count,
                COALESCE(inv.total_invoice_local, 0)   AS total_invoice_local,
                COALESCE(inv.outstanding_local, 0)     AS outstanding_local
            FROM customers c
            LEFT JOIN (
                SELECT customer_id,
                       COUNT(*)                                            AS inv_count,
                       SUM(total_invoice_local)                            AS total_invoice_local,
                       SUM((total_invoice - total_paid) * exchange_rate)   AS outstanding_local
                FROM ar_invoice_header
                WHERE status = 'Posted'
                GROUP BY customer_id
            ) inv ON inv.customer_id = c.id
            WHERE {$where}
            ORDER BY COALESCE(inv.outstanding_local, 0) DESC, c.nama_customer ASC
        ";
        return $this->db->query($sql, $params)->result();
    }

    /**
     * Total keseluruhan untuk kartu ringkasan (nilai lokal/IDR).
     *
     * @param array $filters customer_id
     */
    public function get_totals($filters = [])
    {
        $inv_where = "status = 'Posted'";
        $params = [];

        if (!empty($filters['customer_id'])) {
            $inv_where .= " AND customer_id = ?";
            $params[] = (int) $filters['customer_id'];
        }

        $inv = $this->db->query("
            SELECT
                COALESCE(SUM(total_invoice_local), 0)                          AS total_invoice_local,
                COALESCE(SUM(total_paid * exchange_rate), 0)                    AS total_paid_local,
                COALESCE(SUM((total_invoice - total_paid) * exchange_rate), 0)  AS outstanding_local,
                COUNT(*)                                                       AS inv_count
            FROM ar_invoice_header WHERE {$inv_where}
        ", $params)->row();

        $uninvoiced = $this->get_uninvoiced_delivery($filters);
        $uninvoiced_value = 0;
        foreach ($uninvoiced as $u) {
            $uninvoiced_value += (float) $u->est_value;
        }

        return (object) [
            'total_invoice_local' => (float) $inv->total_invoice_local,
            'total_paid_local'    => (float) $inv->total_paid_local,
            'outstanding_local'   => (float) $inv->outstanding_local,
            'inv_count'           => (int)   $inv->inv_count,
            'uninvoiced_value'    => (float) $uninvoiced_value,
            'uninvoiced_count'    => count($uninvoiced),
        ];
    }
}
