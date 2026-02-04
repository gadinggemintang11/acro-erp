<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Simple Chinese Translation Helper
 * Uses Google Translate API
 */

if (!function_exists('translate_to_chinese')) {
    /**
     * Translate text to Chinese (Simplified)
     * @param string $text Text to translate
     * @return string Chinese translation or original text if translation fails
     */
    function translate_to_chinese($text) {
        if (empty($text)) {
            return '';
        }

        // Use Google Translate API
        try {
            $translation = google_translate($text, 'en', 'zh-CN');
            return $translation;
        } catch (Exception $e) {
            log_message('warning', 'Translation failed for "' . $text . '": ' . $e->getMessage());
            // Return original text if translation fails
            return $text;
        }
    }
}

if (!function_exists('google_translate')) {
    /**
     * Translate using Google Translate (requires cURL)
     * @param string $text Text to translate
     * @param string $source Source language (en, id, etc)
     * @param string $target Target language (zh-CN for simplified Chinese)
     * @return string Translated text
     */
    function google_translate($text, $source = 'en', $target = 'zh-CN') {
        if (empty($text)) {
            return '';
        }

        $url = "https://translate.googleapis.com/translate_a/single";
        $params = [
            'client' => 'gtx',
            'sl' => $source,
            'tl' => $target,
            'dt' => 't',
            'q' => urlencode($text)
        ];

        $full_url = $url . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Translation API Error: " . $error);
        }

        if (empty($response)) {
            throw new Exception("Translation API returned empty response");
        }

        // Parse JSON response
        $json = json_decode($response, true);
        if (isset($json[0][0][0])) {
            return $json[0][0][0];
        }

        return $text; // Return original if parsing fails
    }
}
