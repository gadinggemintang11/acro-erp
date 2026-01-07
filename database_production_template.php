<?php
/*
 * Template Konfigurasi Database untuk PRODUCTION (cPanel)
 * 
 * INSTRUKSI:
 * 1. Copy file ini ke application/config/database.php di server production
 * 2. Atau edit application/config/database.php langsung di cPanel
 * 3. Sesuaikan nilai hostname, username, password, database
 * 
 * JANGAN commit file ini ke Git!
 */

defined('BASEPATH') OR exit('No direct script access allowed');

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
    'dsn'   => '',
    'hostname' => 'localhost',              // Biasanya 'localhost' di cPanel
    'username' => 'cpanel_db_user',         // Ganti dengan username database cPanel
    'password' => 'cpanel_db_password',     // Ganti dengan password database cPanel
    'database' => 'cpanel_db_name',         // Ganti dengan nama database cPanel
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => FALSE,                    // HARUS FALSE untuk production!
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => FALSE                 // FALSE untuk performance
);

/* 
 * Contoh kredensial cPanel (format umum):
 * 
 * Database Name:  username_dbname
 * Database User:  username_dbuser
 * 
 * Cara mendapatkan info:
 * 1. Login cPanel
 * 2. MySQL Databases
 * 3. Lihat di "Current Databases" dan "Current Users"
 */
