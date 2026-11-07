<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Header letterhead: ikon (assets/img/logo_full_biru.png, cuma logo bulat
 * "N", BUKAN gambar gabungan) + teks "PT. NEW SPECIALIFE INDUSTRIES" disusun
 * sendiri via HTML/CSS (bukan 1 gambar flat spt percobaan pertama
 * assets/img/header-pdf.png -- user 2026-09-06 minta dirombak krn median
 * gambar gabungan tidak bisa diatur proporsi ikon vs teks-nya sendiri2) +
 * garis pemisah -- dipakai sbg REPEATING page header di semua PDF dokumen
 * transaksi utama (IRF/MRF/PR/PO/ERF/PRF). HTML fragment yg sama dipakai
 * baik oleh Dompdf (dibungkus position:fixed manual di template) maupun
 * Mpdf (lewat <htmlpageheader>/<sethtmlpageheader> -- Mpdf urus repeat-nya
 * sendiri). Ikon+teks disusun pakai <table style="margin:0 auto"> (bukan
 * flex/inline-block) krn itu cara paling reliable utk center sebuah baris
 * dgn lebar tak-diketahui di KEDUA render engine ini.
 *
 * @param int $icon_h Tinggi ikon dalam px.
 * @param int $font_size Ukuran font nama perusahaan dalam px.
 */
if (!function_exists('render_pdf_letterhead_header_html')) {
    function render_pdf_letterhead_header_html($icon_h = 50, $font_size = 24) {
        // Logo & nama perusahaan bisa diganti user lewat Developer Mode
        // (user 2026-10-08). site_asset()/company_info() jatuh ke nilai bawaan
        // kalau belum pernah diganti atau tabelnya belum ada.
        $path = FCPATH . site_asset('logo_pdf', 'assets/img/logo_pdf.png');
        $company = htmlspecialchars(company_info('company_name'), ENT_QUOTES, 'UTF-8');
        if (!file_exists($path)) return '';
        $icon_h = max(20, (int) $icon_h);
        $font_size = max(10, (int) $font_size);
        // TINGGI EKSPLISIT di div pembungkus (bukan margin implisit) -- lihat
        // catatan lama di render_pdf_letterhead_header_margin(): kalau
        // tingginya bergantung ke line-height konten, posisinya bisa geser
        // di halaman ke-2+ (Dompdf) / kepotong (Mpdf). row_h = tinggi baris
        // ikon+teks (ambil yg lebih besar), box_h = row_h + padding atas-bawah.
        $row_h = max($icon_h, round($font_size * 1.3));
        $box_h = $row_h + 20;
        return '<div style="height:' . $box_h . 'px; padding-top:10px; text-align:center; border-bottom: 1.5px solid #3a3a3a;">'
             . '<table style="margin:0 auto; border-collapse:collapse;"><tr>'
             . '<td style="vertical-align:middle; padding-right:12px;"><img src="' . $path . '" style="width:' . $icon_h . 'px; height:' . $icon_h . 'px;"></td>'
             . '<td style="vertical-align:middle;"><span style="font-family: Arial Black, DejaVu Sans, sans-serif; font-weight:bold; font-size:' . $font_size . 'px; color:#1a1a1a; letter-spacing:0.5px; white-space:nowrap;">' . $company . '</span></td>'
             . '</tr></table>'
             . '</div>';
    }
}

/**
 * Tinggi total (px) yg HARUS disediakan sbg margin-top halaman supaya header
 * di atas (render_pdf_letterhead_header_html(), parameter yg SAMA) tidak
 * bertumpuk dgn baris pertama konten. box height + jarak ke konten.
 */
if (!function_exists('render_pdf_letterhead_header_margin')) {
    function render_pdf_letterhead_header_margin($icon_h = 50, $font_size = 24) {
        $icon_h = max(20, (int) $icon_h);
        $font_size = max(10, (int) $font_size);
        $row_h = max($icon_h, round($font_size * 1.3));
        $box_h = $row_h + 20;
        return $box_h + 10 /* jarak ke konten */;
    }
}

/**
 * Footer letterhead (garis + alamat perusahaan 3 baris, huruf besar semua
 * sesuai contoh yg dikasih user) -- pasangan dari
 * render_pdf_letterhead_header_html(), dipakai sbg REPEATING page footer.
 * border-top ditaruh LANGSUNG di div pembungkus (bukan div kosong terpisah)
 * dgn alasan sama spt header di atas -- hindari ketergantungan ke line-height
 * elemen kosong.
 */
if (!function_exists('render_pdf_letterhead_footer_html')) {
    function render_pdf_letterhead_footer_html() {
        // Alamat & telepon dari Developer Mode -- jumlah baris alamat mengikuti
        // yang diketik user, jadi kaki ini tidak lagi terkunci di 3 baris.
        $alamat = company_address_html();
        $telp   = htmlspecialchars(company_info('company_phone'), ENT_QUOTES, 'UTF-8');
        return '<div style="border-top: 1.5px solid #3a3a3a; padding-top: 5px; text-align:center; font-size:8px; color:#444; line-height:1.5;">'
             . $alamat
             . ($telp !== '' ? ($alamat !== '' ? '<br>' : '') . $telp : '')
             . '</div>';
    }
}

/**
 * Tinggi total (px) yg HARUS disediakan sbg margin-bottom halaman utk footer
 * di atas -- border-top(1.5) + padding-top(5) + N baris teks + buffer.
 *
 * Dulu dipatok 50 (pas utk alamat 3 baris yg di-hardcode). Sejak alamat bisa
 * diubah user lewat Developer Mode (2026-10-08) jumlah barisnya tidak tetap
 * lagi, dan margin yang kekecilan = kaki surat menabrak baris terakhir isi
 * dokumen. Dihitung dari jumlah baris SUNGGUHAN: 8px font x line-height 1.5
 * = 12px per baris.
 *
 * Sengaja TIDAK pernah lebih kecil dari 50: menyusutkan margin akan menggeser
 * pemenggalan halaman dokumen yang tata letaknya sudah disetel ke angka itu
 * (mis. $min_rows di cetak PRF), dan itu risiko tanpa imbalan.
 */
if (!function_exists('render_pdf_letterhead_footer_margin')) {
    function render_pdf_letterhead_footer_margin() {
        $lines = count(company_address_lines());
        if (trim((string) company_info('company_phone')) !== '') $lines++;
        return (int) max(50, ceil(14 + $lines * 12));
    }
}

/**
 * Tambah halaman LAMPIRAN di Mpdf tanpa letterhead header/footer.
 *
 * Halaman lampiran (scan invoice/billing, hasil importPage() FPDI) digambar
 * full-bleed dengan SetMargins(0,0,0), jadi kalau letterhead repeating dari
 * SetHTMLHeader()/SetHTMLFooter() ikut tercetak, isinya bertumpuk dengan
 * identitas perusahaan (dilaporkan user 2026-09-08: billing DJBC ketiban
 * tulisan "PT. NEW SPECIALIFE INDUSTRIES").
 *
 * PENTING -- kenapa pakai ohvalue/ofvalue = -1 di AddPageByArray dan BUKAN
 * sekadar SetHTMLHeader('')/SetHTMLFooter('') sebelum loop lampiran:
 * Mpdf menulis FOOTER sebuah halaman pada saat halaman itu DITUTUP (di dalam
 * AddPage berikutnya), sedangkan header ditulis saat halaman DIBUKA. Jadi
 * SetHTMLFooter('') yang dipanggil selagi halaman terakhir form masih aktif
 * ikut menghapus footer halaman form itu (dites: footer halaman 2 hilang).
 * Nilai negatif di sini diproses Mpdf::_beginpage() -- setelah halaman
 * sebelumnya selesai ditutup lengkap dengan footer-nya.
 *
 * @param Mpdf\Mpdf $mpdf
 * @param string $orientation 'P' atau 'L' (format kertas ikut dokumen induk).
 */
if (!function_exists('mpdf_add_attachment_page')) {
    function mpdf_add_attachment_page($mpdf, $orientation = 'P') {
        $mpdf->AddPageByArray([
            'orientation' => $orientation,
            'ohname' => '', 'ohvalue' => -1,
            'ehname' => '', 'ehvalue' => -1,
            'ofname' => '', 'ofvalue' => -1,
            'efname' => '', 'efvalue' => -1,
        ]);
    }
}

/**
 * Render IRF/PR/PO style document via Dompdf using the shared
 * application/views/irf/pdf_template.php template.
 *
 * @param array $data Variables passed to the template:
 *   irf_number, request_date, department, purpose, items, signatures,
 *   form_title, form_subtitle, number_label, form_type
 * @param string $filename PDF filename (preview, attachment=0)
 */
if (!function_exists('render_document_pdf')) {
    function render_document_pdf(array $data, $filename) {
        require_once FCPATH . 'vendor/autoload.php';

        $cache_dir = FCPATH . 'application/cache/dompdf';
        if (!is_dir($cache_dir)) { @mkdir($cache_dir, 0777, true); }

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', [FCPATH]);
        $options->set('tempDir', $cache_dir);

        $dompdf = new \Dompdf\Dompdf($options);

        // Register CJK font for Chinese characters in form labels.
        $cjk_dir = FCPATH . 'assets/fonts/noto-sans-sc/';
        if (file_exists($cjk_dir . 'NotoSansSC-Regular.ttf')) {
            try {
                $fm = $dompdf->getFontMetrics();
                $fm->registerFont(['family' => 'NotoSansSC', 'style' => 'normal', 'weight' => 'normal'], $cjk_dir . 'NotoSansSC-Regular.ttf');
                $fm->registerFont(['family' => 'NotoSansSC', 'style' => 'normal', 'weight' => 'bold'],   $cjk_dir . 'NotoSansSC-Bold.ttf');
            } catch (\Throwable $e) {
                log_message('error', 'Document PDF: failed to register CJK font - ' . $e->getMessage());
            }
        }

        $CI =& get_instance();
        $html = $CI->load->view('irf/pdf_template', $data, true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => 0]);
    }
}

/**
 * Render SEBUAH view jadi PDF ber-kop-surat, dengan opsi Dompdf yang sama
 * persis dengan render_po_letterhead_pdf().
 *
 * Dibuat 2026-09-14 waktu PDF Bukti Pengeluaran diminta ber-kop seperti PO.
 * Yang penting di sini bukan cuma HTML-nya: `chroot` + `isRemoteEnabled`
 * WAJIB diset, kalau tidak logo letterhead (path absolut di FCPATH) ditolak
 * Dompdf dan header-nya terbit tanpa gambar. Export lama modul pengeluaran
 * memakai Options polos, makanya dipindah ke sini supaya semua pemanggil
 * dapat setelan yang sama.
 *
 * Kop/footer-nya sendiri dipasang DI TEMPLATE (div position:fixed + @page
 * margin), pola yang sama dengan irf/pdf_template.php & pdf_po_letterhead.php.
 */
if (!function_exists('render_letterhead_pdf')) {
    function render_letterhead_pdf($view, array $data, $filename, $orientation = 'portrait') {
        require_once FCPATH . 'vendor/autoload.php';

        $cache_dir = FCPATH . 'application/cache/dompdf';
        if (!is_dir($cache_dir)) { @mkdir($cache_dir, 0777, true); }

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', [FCPATH]);
        $options->set('tempDir', $cache_dir);

        $dompdf = new \Dompdf\Dompdf($options);
        $CI =& get_instance();
        $dompdf->loadHtml($CI->load->view($view, $data, true));
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => 0]);
    }
}

/**
 * Render PO letterhead ("Purchasing Order" resmi ke supplier — user
 * 2026-09-05, harus mirip 100% dgn contoh dokumen asli PT New Specialife
 * Industries) via template terpisah application/views/purchase_order/
 * pdf_po_letterhead.php — BEDA dari render_document_pdf() (template
 * universal form approval internal irf/pdf_template.php). Dipakai SEMUA 4
 * varian PO (base/general/jasa/asset) via Purchase_order*::export_pdf().
 *
 * @param array $data lihat docblock di pdf_po_letterhead.php utk daftar key
 */
if (!function_exists('render_po_letterhead_pdf')) {
    function render_po_letterhead_pdf(array $data, $filename) {
        require_once FCPATH . 'vendor/autoload.php';

        $cache_dir = FCPATH . 'application/cache/dompdf';
        if (!is_dir($cache_dir)) { @mkdir($cache_dir, 0777, true); }

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', [FCPATH]);
        $options->set('tempDir', $cache_dir);

        $dompdf = new \Dompdf\Dompdf($options);

        $CI =& get_instance();
        $html = $CI->load->view('purchase_order/pdf_po_letterhead', $data, true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => 0]);
    }
}

/**
 * Susun $data siap-pakai utk render_po_letterhead_pdf() dari 1 baris
 * po_header (hasil Po_model::get_po_header_by_id(), yg SUDAH join
 * supplier.contact_person/nama_supplier/telepon, shipping/payment_mode_desc,
 * confirmer_name/signature, creator_name) + array po_details (hasil
 * Po_model::get_po_details_by_id(), SUDAH ada item_code/item_name/total_local).
 * Dipakai SEMUA 4 controller PO (base/general/jasa/asset) — strukturnya
 * identik, cuma beda cara nge-trace PR sumbernya (makanya $ref_text
 * dihitung terpisah di controller & dioper sbg parameter, bukan dihitung
 * ulang di sini).
 */
if (!function_exists('build_po_letterhead_data')) {
    function build_po_letterhead_data($header, $details, $ref_text = '') {
        $currency_code = $header->currency_code ?? 'IDR';
        $show_usd = ($currency_code !== 'IDR');

        $items = [];
        $no = 1;
        $grand_total = 0.0;
        foreach ($details as $d) {
            $qty = (float) ($d->qty ?? 0);
            $unit_price_idr = (float) ($d->local_price ?? 0);
            $amount = isset($d->total_local) && (float) $d->total_local > 0
                ? (float) $d->total_local
                : $unit_price_idr * $qty;
            $grand_total += $amount;
            $items[] = [
                'no'             => $no++,
                'item_code'      => $d->item_code ?? '',
                'item_name'      => $d->item_name ?? '',
                'qty'            => $qty,
                'unit_price_usd' => $show_usd ? (float) ($d->base_price ?? 0) : null,
                'unit_price_idr' => $unit_price_idr,
                'amount'         => $amount,
            ];
        }

        // AUTHORIZED SIGNATURE — cuma terisi kalau PO benar-benar SUDAH
        // di-approve (user 2026-09-09: "kenapa ttd otomatis terisi ...
        // harusnya baru bagian print pdf ttd nya terisi ketika sudah di
        // approval").
        //
        // BUG LAMA: kalau belum ada confirmer, kode ini jatuh ke tanda tangan
        // CREATOR; dan kalau creator pun tidak punya file tanda tangan,
        // render_signature_stamp_html(null) tetap mengembalikan cap perusahaan
        // ttd.png. Hasilnya PO yang masih Draft/Submitted ikut tercetak
        // "AUTHORIZED" — padahal belum ada satu pun yang menyetujui, dan
        // dokumen ini yang dikirim ke supplier.
        //
        // Aturan sekarang:
        //   belum approve  -> slot KOSONG (garis tanda tangan dibiarkan polos)
        //   sudah approve  -> tanda tangan personal si approver; kalau dia
        //                     belum pernah upload tanda tangan, pakai cap
        //                     perusahaan assets/img/ttd.png (perilaku bawaan
        //                     render_signature_stamp_html()).
        //
        // Penanda "sudah approve" = kolom yang HANYA diisi oleh aksi approve
        // sungguhan (approve_final()/confirm_po()) dan di-NULL-kan lagi oleh
        // update_status() saat balik ke Draft/Submitted maupun oleh
        // return_to_draft(). Status 'Confirmed' ikut dicek utk baris lama.
        $po_is_approved = !empty($header->confirmed_by)
            || !empty($header->final_approved_by)
            || (($header->status ?? '') === 'Confirmed');

        $authorized_name = $po_is_approved ? ($header->confirmer_name ?? '') : '';
        $authorized_signature_html = $po_is_approved
            ? render_signature_stamp_html($header->confirmer_signature ?? null)
            : '';

        // PPN (user 2026-09-06: "kenapa masih Without TAX" saat PO-nya
        // sebenarnya has_ppn=1) -- SEBELUMNYA field ini sama sekali tidak
        // dibaca di letterhead (selalu nampilin "Without TAX" apa pun
        // datanya), padahal has_ppn/ppn_rate SUDAH ada di po_header (dipakai
        // export Excel & dashboard PO, cuma PDF letterhead yg kelewatan).
        // $grand_total di atas adalah SUBTOTAL (belum kena PPN) -- kalau
        // has_ppn, PPN dihitung dari situ & ditambahkan ke Total Amount yg
        // tercetak, supaya nominal ke supplier BENAR (bukan cuma soal teks
        // caption doang).
        //
        // 2026-10-02: perhitungannya dipindah ke po_ppn_breakdown() (ppn_helper)
        // supaya SATU rumus dipakai halaman detail, cetak PDF, export Excel &
        // daftar PO. Sekaligus mendukung PPN INCLUDE: kalau harga barang sudah
        // termasuk PPN, subtotal baris ITU totalnya -- PPN diambil dari dalamnya,
        // bukan ditambahkan di atasnya, dan Subtotal yang dicetak adalah DPP.
        $ppn_calc   = po_ppn_from_header($header, $grand_total);
        $has_ppn    = $ppn_calc['applied'];
        $ppn_rate   = $ppn_calc['rate'];
        $ppn_amount = $ppn_calc['ppn'];
        $subtotal   = $ppn_calc['dpp'];
        $grand_total_with_ppn = $ppn_calc['total'];

        return [
            'po_number'                 => $header->po_number,
            'po_date'                   => $header->po_date,
            'supplier_name'             => $header->nama_supplier ?? '',
            'supplier_attn'             => $header->supplier_contact_person ?? '',
            'supplier_tel'              => $header->telepon ?? '',
            'ref_text'                  => $ref_text,
            'buyer_name'                => $header->creator_name ?? '',
            'items'                     => $items,
            'show_usd_column'           => $show_usd,
            'currency_code'             => $currency_code,
            'subtotal_idr'              => $subtotal,
            'has_ppn'                   => $has_ppn,
            'ppn_rate'                  => $ppn_rate,
            'ppn_inclusive'             => $ppn_calc['inclusive'],
            'ppn_label'                 => po_ppn_label($ppn_rate, $ppn_calc['inclusive']),
            'ppn_amount_idr'            => $ppn_amount,
            'grand_total_idr'           => $grand_total_with_ppn,
            'payment_term'              => $header->payment_mode_desc ?? '',
            'delivery_term'             => $header->shipping_mode_desc ?? '',
            'remarks'                   => $header->remarks ?? '',
            'authorized_name'           => $authorized_name,
            'authorized_signature_html' => $authorized_signature_html,
        ];
    }
}

/**
 * HTML utk 1 slot tanda tangan berdasarkan signature_path user (nullable) —
 * berlaku sama di SEMUA form (IRF/MRF/PRF/ERF/PO):
 * - ADA ttd personal ter-upload -> HANYA ttd itu, tanpa stempel/bingkai
 *   apa pun (user 2026-09-16: "jika ttd sudah diupload user maka stamp nya
 *   gausa ada, hanya ttd"). Dulu ttd ditempel di atas bingkai ttd2.png dan
 *   sempat dimatikan hanya utk ERF & PRF; sekarang seragam tanpa bingkai,
 *   jadi assets/img/ttd2.png tidak dipakai lagi.
 * - TIDAK ADA ttd personal -> pakai stempel generik `assets/img/ttd.png`
 *   ("ERP APPROVED - NEW SPECIALIFE INDUSTRIES", sudah lengkap teksnya).
 * Ini CUMA dipanggil kalau AKSI-nya beneran terjadi (ada $user_id yg
 * resolve ke user asli) -- kalau approval stage belum sampai giliran
 * siapa pun (slot masih kosong), tetap dibiarkan blank spt sebelumnya,
 * BUKAN dikasih stempel (nanti kesannya sudah ada yg approve padahal belum).
 *
 * @param string|null $signature_path  users.signature_path (relatif FCPATH)
 * @param int         $size            lebar/tinggi kotak stempel dlm px —
 *                                      default 80 (cocok utk cell ~110px tinggi
 *                                      spt IRF/ERF/PRF-A4/PO). Beberapa
 *                                      template py varian kertas lebih kecil
 *                                      (mis. PRF A5/A5-portrait) -- kirim
 *                                      $size lebih kecil (55/42) dari sana
 *                                      supaya tidak overflow cell.
 * @param bool        $with_frame      TIDAK DIPAKAI LAGI (2026-09-16). Dulu
 *                                      true = ttd personal ditempel di atas
 *                                      frame ttd2.png. User: "jika ttd sudah
 *                                      diupload user maka stamp nya gausa ada,
 *                                      hanya ttd" -- jadi ttd personal SELALU
 *                                      polos. Parameternya dipertahankan
 *                                      supaya pemanggil lama tidak perlu diubah.
 */
if (!function_exists('render_signature_stamp_html')) {
    function render_signature_stamp_html($signature_path, $size = 80, $with_frame = false) {
        $stamp_path = FCPATH . site_asset('stamp_approved', 'assets/img/ttd.png');
        $size = max(20, (int) $size);

        // Punya ttd personal -> tampilkan ttd-nya SAJA, tanpa stempel/frame
        // apa pun (user 2026-09-16, contoh kasus PDF IRF ADM00004: ttd asli
        // masih tertumpuk kotak "ERP"). Stempel hanya untuk yang belum
        // mengunggah ttd.
        //
        // Ukurannya DIPAS-kan ke dalam kotak (lebar & TINGGI), bukan cuma
        // lebarnya (user 2026-09-16: "ketika ukuran gambar ttd yang diupload
        // besar kenapa kolom ttd nya diperlebar, harusnya file ttd nya yang
        // dipaksa mengikuti kolomnya"). Dulu hanya lebar yang dikunci dan
        // tinggi dibiarkan ikut rasio -- foto ttd potret (mis. 739x1600)
        // jadi setinggi 147px dan meninggikan seluruh baris tanda tangan.
        // Tinggi maksimum disamakan dengan stempel supaya tinggi barisnya
        // konsisten, ada ttd personal maupun tidak.
        if (!empty($signature_path) && file_exists(FCPATH . $signature_path)) {
            $sig_abs = FCPATH . $signature_path;
            $box_w = round($size * 0.85);
            $box_h = round($size * 0.75);

            $dim = @getimagesize($sig_abs);
            if ($dim && $dim[0] > 0 && $dim[1] > 0) {
                // Perkecil/perbesar proporsional sampai muat kedua sisi kotak.
                $scale = min($box_w / $dim[0], $box_h / $dim[1]);
                $w = max(1, (int) round($dim[0] * $scale));
                $h = max(1, (int) round($dim[1] * $scale));
                return '<img src="' . $sig_abs . '" style="width: ' . $w . 'px; height: ' . $h . 'px;">';
            }
            // Dimensi tidak terbaca (format aneh / file rusak sebagian):
            // batasi lewat CSS saja supaya tetap tidak melebihi kotak.
            return '<img src="' . $sig_abs . '" style="max-width: ' . $box_w . 'px; max-height: ' . $box_h . 'px;">';
        }

        if (file_exists($stamp_path)) {
            return '<img src="' . $stamp_path . '" style="height: ' . round($size * 0.75) . 'px;">';
        }
        return '';
    }
}

/**
 * Cap "COMPLETED" (assets/img/stamp.png). Dipakai di ERF/PRF PDF ketika
 * dokumen sudah berstatus payment_status = 'Paid' -- kalau belum dibayar,
 * JANGAN dipanggil sama sekali (caller yang menentukan kondisinya, fungsi
 * ini sendiri tidak mengecek status apa pun).
 *
 * Garis "DATE:____" di gambar SENGAJA dibiarkan kosong (tidak diisi tanggal
 * payment via overlay teks) -- sudah dicoba pakai position:absolute nested
 * di dalam table cell (spt render_signature_stamp_html) tapi Mpdf tidak
 * resolve posisinya dgn benar di konteks itu (teks malah lompat ke pojok
 * halaman/dokumen, bukan ke atas garis). User mengonfirmasi kosong saja
 * tidak masalah, jadi tidak dipaksakan.
 *
 * @param int $width Lebar stempel dalam px (tinggi mengikuti rasio asli gambar 1870x841). Sesuaikan
 *                    per varian kertas (mis. PRF A4/A5/A5-portrait) supaya tidak overflow.
 */
if (!function_exists('render_payment_stamp_html')) {
    function render_payment_stamp_html($width = 170) {
        $stamp_path = FCPATH . site_asset('stamp_completed', 'assets/img/stamp.png');
        if (!file_exists($stamp_path)) return '';

        $width  = max(60, (int) $width);
        // Rasio dibaca dari berkas yang BENAR-BENAR dipakai. Dulu dipatok
        // 841/1870 (rasio stamp.png bawaan); sejak stempel bisa diganti user
        // (2026-10-08) angka mati itu akan membuat gambar unggahan tergencet
        // atau melar. Jatuh kembali ke rasio bawaan kalau ukurannya tidak
        // terbaca.
        $dim    = @getimagesize($stamp_path);
        $ratio  = ($dim && !empty($dim[0])) ? ($dim[1] / $dim[0]) : (841 / 1870);
        $height = round($width * $ratio);

        return '<img src="' . $stamp_path . '" style="width:' . $width . 'px; height:' . $height . 'px;">';
    }
}

/**
 * Build a single signature slot from a user_id.
 * Returns ['name' => ..., 'signature_html' => ..., 'date' => ...].
 */
if (!function_exists('build_signature_slot')) {
    function build_signature_slot($user_id, $date_raw) {
        $blank = ['name' => '', 'signature_html' => '', 'date' => ''];
        if (empty($user_id)) return $blank;

        $CI =& get_instance();
        $u = $CI->db->select('fullname, signature_path')
            ->from('users')->where('id', $user_id)->get()->row();
        if (!$u) return $blank;

        return [
            'name'           => $u->fullname,
            'signature_html' => render_signature_stamp_html($u->signature_path),
            'date'           => $date_raw ? date('d/m/Y', strtotime($date_raw)) : '',
        ];
    }
}

/**
 * Trace a PR back to its originating IRF (Material Request or Consumable Request)
 * by parsing pr_header.remarks (which contains "Outstanding dari: <mr_number>"
 * or "outstanding MR: <number>" / "outstanding CR: <number>" set by *_outstanding/create_pr).
 *
 * Returns ['user_id' => ..., 'date' => ...] of the IRF applicant, or blank slot if no trace.
 */
if (!function_exists('find_irf_applicant_from_remarks')) {
    function find_irf_applicant_from_remarks($remarks) {
        $blank = ['user_id' => null, 'date' => null];
        if (empty($remarks)) return $blank;

        $CI =& get_instance();

        // Find any MR/CR number that appears as a substring of $remarks.
        $mr = $CI->db->query(
            "SELECT requester_id, requested_at, created_at
             FROM mr_header
             WHERE ? LIKE CONCAT('%', mr_number, '%')
             ORDER BY id ASC LIMIT 1",
            [$remarks]
        )->row();
        if ($mr) {
            return [
                'user_id' => $mr->requester_id,
                'date'    => $mr->requested_at ?? $mr->created_at ?? null,
            ];
        }

        $cr = $CI->db->query(
            "SELECT created_by, created_at
             FROM consumable_request_header
             WHERE ? LIKE CONCAT('%', request_number, '%')
             ORDER BY id ASC LIMIT 1",
            [$remarks]
        )->row();
        if ($cr) {
            return [
                'user_id' => $cr->created_by,
                'date'    => $cr->created_at ?? null,
            ];
        }

        return $blank;
    }
}

/**
 * Resolve common_code department description from a possibly-code value.
 */
if (!function_exists('resolve_department_name')) {
    function resolve_department_name($value) {
        if (empty($value)) return '';
        $CI =& get_instance();
        $cc = $CI->db->select('description')->from('common_code')
            ->where('group_code', 'Department')
            ->group_start()
                ->where('code', $value)
                ->or_where('description', $value)
            ->group_end()
            ->limit(1)->get()->row();
        return $cc ? $cc->description : $value;
    }
}
