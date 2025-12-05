<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Language Helper
 * Helper function untuk multi-language support
 */

if (!function_exists('lang_line')) {
    /**
     * Get translated text
     * 
     * @param string $key Language key
     * @param string $default Default text if key not found
     * @return string Translated text
     */
    function lang_line($key, $default = '') {
        $CI =& get_instance();
        $text = $CI->lang->line($key);
        return $text ? $text : ($default ? $default : $key);
    }
}

if (!function_exists('get_current_language')) {
    /**
     * Get current active language
     * 
     * @return string Language code
     */
    function get_current_language() {
        $CI =& get_instance();
        $lang = $CI->session->userdata('site_lang');
        if (!$lang) {
            $lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : 'indonesian';
        }
        return $lang;
    }
}

if (!function_exists('get_language_name')) {
    /**
     * Get language display name
     * 
     * @param string $code Language code
     * @return string Language display name
     */
    function get_language_name($code) {
        $languages = [
            'indonesian' => 'Bahasa Indonesia',
            'english' => 'English',
            'chinese' => '中文'
        ];
        return isset($languages[$code]) ? $languages[$code] : $code;
    }
}

if (!function_exists('get_available_languages')) {
    /**
     * Get all available languages
     * 
     * @return array Available languages
     */
    function get_available_languages() {
        return [
            'indonesian' => [
                'code' => 'indonesian',
                'name' => 'Bahasa Indonesia',
                'flag' => '🇮🇩'
            ],
            'english' => [
                'code' => 'english',
                'name' => 'English',
                'flag' => '🇺🇸'
            ],
            'chinese' => [
                'code' => 'chinese',
                'name' => '中文',
                'flag' => '🇨🇳'
            ]
        ];
    }
}

if (!function_exists('translate_module_name')) {
    /**
     * Translate module name from database
     * 
     * @param string $module_name Module name from database
     * @return string Translated module name
     */
    function translate_module_name($module_name) {
        if (empty($module_name)) return $module_name;
        
        // Convert to lowercase and replace spaces with underscore for key
        $module_key = 'module_' . strtolower(str_replace(' ', '_', $module_name));
        
        // Get translation
        return lang_line($module_key, $module_name);
    }
}

if (!function_exists('translate_action')) {
    /**
     * Translate action from database
     * 
     * @param string $action Action from database
     * @return string Translated action
     */
    function translate_action($action) {
        if (empty($action)) return $action;
        
        // Convert to lowercase for key
        $action_key = 'action_' . strtolower($action);
        
        // Get translation
        return lang_line($action_key, $action);
    }
}
