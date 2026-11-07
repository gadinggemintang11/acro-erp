<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Export Helper for Excel & PDF
 * Helps generate Excel files and PDF from data arrays
 */

function export_to_excel($filename, $sheet_name, $headers, $data) {
    try {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle(substr($sheet_name, 0, 31)); // Max 31 chars for sheet name

        // Set header row
        $col = 'A';
        foreach ($headers as $header) {
            $worksheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Style header - simplified version
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        
        $worksheet->getStyle('A1:' . chr(64 + count($headers)) . '1')->applyFromArray($headerStyle);
        
        // Set background color for header row
        $headerRange = 'A1:' . chr(64 + count($headers)) . '1';
        $worksheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $worksheet->getStyle($headerRange)->getFill()->getStartColor()->setRGB('4472C4');

        // Set data rows
        $row = 2;
        foreach ($data as $item) {
            $col = 'A';
            if (is_array($item)) {
                foreach ($item as $value) {
                    $worksheet->setCellValue($col . $row, $value);
                    $col++;
                }
            } else {
                foreach (get_object_vars($item) as $value) {
                    $worksheet->setCellValue($col . $row, $value);
                    $col++;
                }
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', chr(64 + count($headers))) as $col) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output to browser
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } catch (Exception $e) {
        log_message('error', 'Export Excel Error: ' . $e->getMessage());
        return false;
    }
}

function export_to_pdf($filename, $title, $headers, $data) {
    try {
        $html = '
        <style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            h2 { text-align: center; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; }
            th { background-color: #4472C4; color: white; padding: 8px; text-align: left; border: 1px solid #333; }
            td { padding: 6px; border: 1px solid #ddd; }
            tr:nth-child(even) { background-color: #f9f9f9; }
        </style>
        <h2>' . htmlspecialchars($title) . '</h2>
        <table>
            <thead>
                <tr>';
        
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        
        $html .= '</tr>
            </thead>
            <tbody>';
        
        foreach ($data as $item) {
            $html .= '<tr>';
            if (is_array($item)) {
                foreach ($item as $value) {
                    $html .= '<td>' . htmlspecialchars($value) . '</td>';
                }
            } else {
                foreach (get_object_vars($item) as $value) {
                    $html .= '<td>' . htmlspecialchars($value) . '</td>';
                }
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody>
        </table>';

        $pdf = new \Dompdf\Dompdf();
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        header('Cache-Control: max-age=0');

        echo $pdf->output();
        exit;
    } catch (Exception $e) {
        log_message('error', 'Export PDF Error: ' . $e->getMessage());
        return false;
    }
}
