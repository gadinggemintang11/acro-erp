<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2019, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Database language strings - Bahasa Indonesia
 *
 * @package	CodeIgniter
 * @subpackage	Language
 * @category	Language
 * @author	EllisLab Dev Team
 * @link	https://codeigniter.com
 */

$lang['db_invalid_connection_str'] = 'Tidak dapat menentukan pengaturan database berdasarkan string koneksi yang Anda berikan.';
$lang['db_unable_to_select'] = 'Tidak dapat memilih database yang ditentukan: %s';
$lang['db_unable_to_create'] = 'Tidak dapat membuat database yang ditentukan: %s';
$lang['db_invalid_query'] = 'Query SQL yang Anda berikan tidak valid.';
$lang['db_halt_executed'] = 'Query yang fatal dieksekusi.';
$lang['db_unable_to_load_utility'] = 'Tidak dapat memuat utility database yang ditentukan.';
$lang['db_unable_to_load_driver'] = 'Tidak dapat memuat driver database yang ditentukan: %s';
$lang['db_connection_failed'] = 'Gagal terhubung ke database dengan pengaturan yang diberikan.';
$lang['db_unable_to_send_charset'] = 'Tidak dapat mengirim character set request: %s';
$lang['db_invalid_savepoint'] = 'Savepoint tidak valid untuk driver database yang digunakan.';
$lang['db_error_heading'] = 'Telah Terjadi Kesalahan Database';
$lang['db_unknown_error'] = 'Kesalahan Database Tidak Diketahui';
$lang['db_must_set_table'] = 'Anda harus mengatur tabel database yang akan digunakan query Anda.';
$lang['db_must_use_set'] = 'Anda harus menggunakan metode SET untuk memperbarui entri.';
$lang['db_must_use_where'] = 'Update tidak diizinkan kecuali jika berisi klausa WHERE.';
$lang['db_must_use_like'] = 'DELETE query tidak diizinkan kecuali jika berisi klausa LIKE atau WHERE.';
$lang['db_delete_limit'] = 'DELETE queries tidak didukung karena masalah keamanan.';
$lang['db_call_to_unconfigured_model'] = 'Pemanggilan metode model yang tidak dikonfigurasi dengan benar atau tidak dapat diakses.';
$lang['db_invalid_call_default_table'] = 'Pemanggilan tidak valid pada model: tabel default tidak valid.';
$lang['db_invalid_calls'] = 'Pemanggilan model tidak valid.';
$lang['db_query_notallowed'] = 'Query tipe ini tidak diizinkan.';
