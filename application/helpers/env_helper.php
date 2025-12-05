<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('load_env')) {
    function load_env($file_path = null) {
        static $loaded = false;
        
        if ($loaded) return;
        
        $env_file = $file_path ?: FCPATH . '.env';
        
        if (file_exists($env_file)) {
            $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, '"\'');
                    putenv($key . '=' . $value);
                    $_ENV[$key] = $value;
                }
            }
        }
        
        $loaded = true;
    }
}

// Auto-load environment variables
load_env();