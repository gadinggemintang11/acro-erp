<?php
/*
 * Template Konfigurasi Config untuk PRODUCTION (cPanel)
 * 
 * INSTRUKSI:
 * 1. Edit application/config/config.php di cPanel
 * 2. Ubah base_url sesuai domain Anda
 * 3. Pastikan index_page = '' untuk clean URL
 */

// File: application/config/config.php
// Ubah baris berikut:

// Base URL - WAJIB diubah!
$config['base_url'] = 'https://yourdomain.com/';
// Atau jika di subfolder:
// $config['base_url'] = 'https://yourdomain.com/erp/';

// Index page - kosongkan untuk clean URL
$config['index_page'] = '';

// Encryption key - Generate random untuk security
$config['encryption_key'] = 'YOUR_RANDOM_32_CHARACTER_KEY_HERE';

/*
 * Generate encryption key:
 * - Buka terminal/SSH
 * - Jalankan: php -r "echo bin2hex(random_bytes(16));"
 * - Copy hasilnya ke encryption_key
 */
