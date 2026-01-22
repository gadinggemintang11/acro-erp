<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Mpdf\Mpdf;

class Pdf {
    
    protected $mpdf;
    
    public function __construct($config = [])
    {
        // Set temp directory ke folder cache aplikasi
        $tempDir = APPPATH . 'cache/mpdf';
        
        // Buat direktori jika belum ada
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        // Default config untuk mPDF
        $defaultConfig = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 5,
            'margin_footer' => 5,
            'tempDir' => $tempDir,
        ];
        
        $config = array_merge($defaultConfig, $config);
        
        // Inisialisasi mPDF
        $this->mpdf = new Mpdf($config);
    }
    
    /**
     * Load HTML content
     */
    public function loadHtml($html)
    {
        $this->mpdf->WriteHTML($html);
    }
    
    /**
     * Set paper size and orientation
     */
    public function setPaper($size = 'A4', $orientation = 'portrait')
    {
        $this->mpdf->_setPageSize($size, $orientation);
    }
    
    /**
     * Render (tidak perlu untuk mPDF, method ini untuk kompatibilitas)
     */
    public function render()
    {
        // mPDF tidak memerlukan render eksplisit
        return true;
    }
    
    /**
     * Stream/output PDF
     */
    public function stream($filename = 'document.pdf', $options = [])
    {
        $attach = isset($options['Attachment']) ? $options['Attachment'] : 0;
        $dest = $attach ? 'D' : 'I'; // D = download, I = inline
        
        $this->mpdf->Output($filename, $dest);
    }
    
    /**
     * Save PDF to file
     */
    public function save($filename)
    {
        $this->mpdf->Output($filename, 'F');
    }
    
    /**
     * Get raw mPDF instance
     */
    public function getMpdf()
    {
        return $this->mpdf;
    }
}
