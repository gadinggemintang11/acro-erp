<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Xlsx_report — pembuat file Excel laporan (beberapa sheet) dengan judul,
 * header dua tingkat (kolom ber-'group' digabung di baris atas), format angka,
 * baris subtotal/total, dan freeze pane.
 *
 *   $this->load->library('xlsx_report');
 *   $this->xlsx_report->add_sheet([
 *       'title'   => 'Rincian',                       // nama sheet (maks 31)
 *       'heading' => ['Laporan Aset Tetap', 'Periode Jan 2026 s/d Nov 2026'],
 *       'columns' => [
 *           ['label' => 'No. Aset', 'type' => 'text', 'width' => 16],
 *           ['label' => 'Saldo Awal', 'type' => 'money', 'group' => 'Harga Perolehan'],
 *       ],
 *       'rows'    => [ ['AST-1', 1000], ['_style' => 'subtotal', 'cells' => ['Subtotal', 1000]] ],
 *   ]);
 *   $this->xlsx_report->download('Laporan_Aset_2026-11');
 *
 * type: text | money | int | date (Y-m-d) | percent. Sel null dibiarkan kosong.
 * _style: subtotal | total | section (baris judul kelompok, digabung satu baris).
 */
class Xlsx_report {

    private $book;
    private $sheets = 0;

    const MONEY = '#,##0.00;-#,##0.00;"-"';

    public function __construct()
    {
        $this->book = new Spreadsheet();
        $this->book->getProperties()->setCreator('Acropoly ERP')->setTitle('Laporan');
    }

    private static function col($i)
    {
        return Coordinate::stringFromColumnIndex($i);
    }

    public function add_sheet(array $spec)
    {
        $ws = $this->sheets === 0 ? $this->book->getActiveSheet() : $this->book->createSheet();
        $this->sheets++;
        $title = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', (string) ($spec['title'] ?? ('Sheet' . $this->sheets)));
        $ws->setTitle(mb_substr($title, 0, 31));

        $cols = array_values($spec['columns'] ?? []);
        $n = max(1, count($cols));
        $last = self::col($n);
        $r = 1;

        foreach ((array) ($spec['heading'] ?? []) as $k => $h) {
            $ws->setCellValue('A' . $r, (string) $h);
            $ws->mergeCells('A' . $r . ':' . $last . $r);
            $ws->getStyle('A' . $r)->getFont()->setBold($k === 0)->setSize($k === 0 ? 13 : 10);
            $r++;
        }
        if (!empty($spec['heading'])) $r++;

        // Header (1 atau 2 baris)
        $two = false;
        foreach ($cols as $c) if (!empty($c['group'])) { $two = true; break; }
        $h1 = $r; $h2 = $two ? $r + 1 : $r;
        $i = 0;
        while ($i < $n) {
            $c = $cols[$i];
            $L = self::col($i + 1);
            if ($two && !empty($c['group'])) {
                $j = $i;
                while ($j + 1 < $n && ($cols[$j + 1]['group'] ?? null) === $c['group']) $j++;
                $ws->setCellValue($L . $h1, $c['group']);
                if ($j > $i) $ws->mergeCells($L . $h1 . ':' . self::col($j + 1) . $h1);
                for ($k = $i; $k <= $j; $k++) $ws->setCellValue(self::col($k + 1) . $h2, $cols[$k]['label']);
                $i = $j + 1;
                continue;
            }
            $ws->setCellValue($L . $h1, $c['label']);
            if ($two) $ws->mergeCells($L . $h1 . ':' . $L . $h2);
            $i++;
        }
        $hdr = 'A' . $h1 . ':' . $last . $h2;
        $ws->getStyle($hdr)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9BB7D4']]],
        ]);
        $r = $h2 + 1;
        $first_data = $r;

        foreach ((array) ($spec['rows'] ?? []) as $row) {
            $style = null; $cells = $row;
            if (is_array($row) && isset($row['_style'])) { $style = $row['_style']; $cells = $row['cells'] ?? []; }
            $cells = array_values((array) $cells);
            if ($style === 'section') {
                $ws->setCellValue('A' . $r, (string) ($cells[0] ?? ''));
                $ws->mergeCells('A' . $r . ':' . $last . $r);
                $ws->getStyle('A' . $r)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '1F4E78']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDEBF7']],
                ]);
                $r++;
                continue;
            }
            foreach ($cols as $ci => $c) {
                $v = $cells[$ci] ?? null;
                if ($v === null || $v === '') continue;
                $addr = self::col($ci + 1) . $r;
                $type = $c['type'] ?? 'text';
                if ($type === 'money' || $type === 'int' || $type === 'percent') {
                    if (is_numeric($v)) $ws->setCellValueExplicit($addr, (float) $v, DataType::TYPE_NUMERIC);
                    else $ws->setCellValueExplicit($addr, (string) $v, DataType::TYPE_STRING);
                } elseif ($type === 'date' && is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}/', $v)) {
                    $ws->setCellValue($addr, XlsDate::PHPToExcel(new DateTime(substr($v, 0, 10))));
                } else {
                    $ws->setCellValueExplicit($addr, (string) $v, DataType::TYPE_STRING);
                }
            }
            if ($style === 'subtotal' || $style === 'total') {
                $ws->getStyle('A' . $r . ':' . $last . $r)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $style === 'total' ? 'BDD7EE' : 'F2F2F2']],
                    'borders' => ['top' => ['borderStyle' => $style === 'total' ? Border::BORDER_DOUBLE : Border::BORDER_THIN]],
                ]);
            }
            $r++;
        }
        $last_data = $r - 1;

        // Format kolom & lebar
        foreach ($cols as $ci => $c) {
            $L = self::col($ci + 1);
            $type = $c['type'] ?? 'text';
            if ($last_data >= $first_data) {
                $rng = $L . $first_data . ':' . $L . $last_data;
                if ($type === 'money') $ws->getStyle($rng)->getNumberFormat()->setFormatCode(self::MONEY);
                elseif ($type === 'int') $ws->getStyle($rng)->getNumberFormat()->setFormatCode('#,##0');
                elseif ($type === 'percent') $ws->getStyle($rng)->getNumberFormat()->setFormatCode('0.00%');
                elseif ($type === 'date') $ws->getStyle($rng)->getNumberFormat()->setFormatCode('dd-mm-yyyy');
            }
            $w = $c['width'] ?? ($type === 'money' ? 16 : ($type === 'date' ? 12 : ($type === 'int' ? 8 : 14)));
            $ws->getColumnDimension($L)->setWidth($w);
        }
        if ($last_data >= $first_data) {
            $ws->getStyle('A' . $first_data . ':' . $last . $last_data)->getBorders()->getAllBorders()
               ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('BFBFBF');
        }
        // Freeze baris header (+ n kolom pertama bila freeze_cols diisi).
        $ws->freezePane(self::col((int) ($spec['freeze_cols'] ?? 0) + 1) . $first_data);

        foreach ((array) ($spec['notes'] ?? []) as $note) {
            $r++;
            $ws->setCellValue('A' . $r, (string) $note);
            $ws->getStyle('A' . $r)->getFont()->setItalic(true)->setSize(9);
        }
        $ws->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
           ->setFitToWidth(1)->setFitToHeight(0);
        return $ws;
    }

    /** Kirim ke browser (.xlsx) lalu exit. */
    public function download($filename)
    {
        $this->book->setActiveSheetIndex(0);
        $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename) . '.xlsx';
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        (new Xlsx($this->book))->save('php://output');
        exit;
    }

    /** Simpan ke file (dipakai pengujian). */
    public function save($path)
    {
        $this->book->setActiveSheetIndex(0);
        (new Xlsx($this->book))->save($path);
        return $path;
    }
}
