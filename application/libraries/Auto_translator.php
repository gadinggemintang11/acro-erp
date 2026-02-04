<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auto Translator Library
 * Supports Google Translate API and fallback dictionary
 */
class Auto_translator {
    
    private $CI;
    private $google_api_key = ''; // Set your Google API key here
    
    public function __construct() {
        $this->CI =& get_instance();
    }
    
    /**
     * Translate text to Chinese
     */
    public function translate_to_chinese($text) {
        if (empty(trim($text))) {
            return '';
        }
        
        // First try Google Translate API if available
        if (!empty($this->google_api_key)) {
            $google_result = $this->translate_with_google($text, 'zh');
            if ($google_result !== false) {
                return $google_result;
            }
        }
        
        // Try MyMemory Translation (Free API)
        $mymemory_result = $this->translate_with_mymemory($text);
        if ($mymemory_result !== false) {
            return $mymemory_result;
        }
        
        // Final fallback to dictionary
        return $this->translate_with_dictionary($text);
    }
    
    /**
     * Google Translate API (requires API key)
     */
    private function translate_with_google($text, $target_lang = 'zh') {
        if (empty($this->google_api_key)) {
            return false;
        }
        
        $url = 'https://translation.googleapis.com/language/translate/v2?key=' . $this->google_api_key;
        
        $data = [
            'q' => $text,
            'target' => $target_lang,
            'source' => 'en'
        ];
        
        $result = $this->make_curl_request($url, $data);
        
        if ($result && isset($result['data']['translations'][0]['translatedText'])) {
            return $result['data']['translations'][0]['translatedText'];
        }
        
        return false;
    }
    
    /**
     * Yandex Translate (Free with limitations)
     */
    private function translate_with_yandex($text) {
        // Simple free translation using web scraping
        $text_encoded = urlencode($text);
        $url = "https://translate.yandex.net/api/v1/tr.json/translate?id=&sr=1&text={$text_encoded}&lang=en-zh";
        
        $result = $this->make_curl_request($url, null, 'GET');
        
        if ($result && isset($result['text'][0])) {
            return $result['text'][0];
        }
        
        return false;
    }
    
    /**
     * MyMemory Translation API (Free)
     */
    private function translate_with_mymemory($text) {
        $text_encoded = urlencode($text);
        $url = "https://api.mymemory.translated.net/get?q={$text_encoded}&langpair=en|zh";
        
        $result = $this->make_curl_request($url, null, 'GET');
        
        if ($result && isset($result['responseData']['translatedText'])) {
            $translated = $result['responseData']['translatedText'];
            // Check if it's not "QUERY LENGTH LIMIT EXCEEDED"
            if (stripos($translated, 'QUERY LENGTH LIMIT') === false) {
                return $translated;
            }
        }
        
        return false;
    }
    
    /**
     * Dictionary fallback
     */
    private function translate_with_dictionary($text) {
        $dictionary = [
            // Basic items
            'MARKING STICKER' => '标记贴纸',
            'MARKING' => '标记',
            'STICKER' => '贴纸',
            'LABEL' => '标签',
            'TAG' => '吊牌',
            
            // Chemicals
            'CLEANER' => '清洁剂',
            'TREATMENT' => '处理剂',
            'STABILIZER' => '稳定剂',
            'ADDITIVE' => '添加剂',
            'CHEMICAL' => '化学品',
            'COMPOUND' => '复合物',
            'SOLUTION' => '溶液',
            'SOLVENT' => '溶剂',
            'ACID' => '酸',
            'BASE' => '碱',
            
            // Materials
            'PVC' => 'PVC',
            'PLASTIC' => '塑料',
            'RESIN' => '树脂',
            'POLYMER' => '聚合物',
            'RUBBER' => '橡胶',
            'METAL' => '金属',
            'STEEL' => '钢',
            'ALUMINUM' => '铝',
            'COPPER' => '铜',
            'ZINC' => '锌',
            'SILICON' => '硅',
            'FIBER' => '纤维',
            'GLASS' => '玻璃',
            
            // Textiles
            'FABRIC' => '织物',
            'CLOTH' => '布料',
            'COTTON' => '棉花',
            'POLYESTER' => '聚酯',
            'NYLON' => '尼龙',
            'SPANDEX' => '氨纶',
            'ELASTIC' => '弹性',
            'THREAD' => '线',
            'YARN' => '纱线',
            
            // Hardware
            'ZIPPER' => '拉链',
            'BUTTON' => '纽扣',
            'VELCRO' => '魔术贴',
            'TAPE' => '胶带',
            'ADHESIVE' => '胶粘剂',
            'GLUE' => '胶水',
            
            // Packaging
            'PACKAGING' => '包装',
            'BOX' => '盒子',
            'BAG' => '袋子',
            'CONTAINER' => '容器',
            'WRAPPER' => '包装纸',
            
            // Tools & Equipment
            'MACHINE' => '机器',
            'EQUIPMENT' => '设备',
            'TOOL' => '工具',
            'PART' => '零件',
            'COMPONENT' => '组件',
            'ASSEMBLY' => '组装件',
            
            // Common terms
            'MATERIAL' => '材料',
            'PRODUCT' => '产品',
            'ITEM' => '物品',
            'GOODS' => '货物',
            'SUPPLIES' => '供应品',
            'RAW' => '原料',
            'FINISHED' => '成品',
            'SEMI' => '半成品',
            
            // Sizes & Measurements
            'LARGE' => '大',
            'MEDIUM' => '中',
            'SMALL' => '小',
            'SIZE' => '尺寸',
            'KG' => '公斤',
            'GRAM' => '克',
            'LITER' => '升',
            'METER' => '米',
            'CM' => '厘米',
            'MM' => '毫米',
            
            // Colors
            'BLACK' => '黑色',
            'WHITE' => '白色',
            'RED' => '红色',
            'BLUE' => '蓝色',
            'GREEN' => '绿色',
            'YELLOW' => '黄色',
            'CLEAR' => '透明',
            'TRANSPARENT' => '透明',
            
            // Processing
            'PAINT' => '油漆',
            'COATING' => '涂层',
            'PRIMER' => '底漆',
            'FINISH' => '表面处理',
            'POLISH' => '抛光',
            'WASH' => '洗涤',
            'DRY' => '干燥',
            'CURE' => '固化'
        ];
        
        $text_upper = strtoupper(trim($text));
        
        // Check for exact matches first
        if (isset($dictionary[$text_upper])) {
            return $dictionary[$text_upper];
        }
        
        // Check for partial matches
        foreach ($dictionary as $en => $cn) {
            if (stripos($text_upper, $en) !== false) {
                return $cn;
            }
        }
        
        // If no match found, try to break down compound words
        $words = preg_split('/[\s\-_]+/', $text_upper);
        $translations = [];
        
        foreach ($words as $word) {
            if (isset($dictionary[$word])) {
                $translations[] = $dictionary[$word];
            }
        }
        
        if (!empty($translations)) {
            return implode(' ', $translations);
        }
        
        // Return original text if no translation found
        return $text;
    }
    
    /**
     * Make CURL request
     */
    private function make_curl_request($url, $data = null, $method = 'POST') {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200 && $response) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    /**
     * Set Google API key
     */
    public function set_google_api_key($key) {
        $this->google_api_key = $key;
    }
}