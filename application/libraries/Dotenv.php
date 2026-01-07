<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * DotEnv Library for CodeIgniter 3
 * 
 * Simple .env file parser untuk load environment variables
 * Inspired by vlucas/phpdotenv
 * 
 * @author  ERP Mingbao Team
 * @version 1.0
 */
class Dotenv
{
    /**
     * Load .env file
     * 
     * @param string $path Path ke folder yang berisi .env
     * @param string $file Nama file (default: .env)
     * @return bool
     */
    public function load($path, $file = '.env')
    {
        $filePath = rtrim($path, '/') . '/' . $file;
        
        if (!file_exists($filePath)) {
            // Jika .env tidak ada, tidak error (opsional)
            return false;
        }
        
        if (!is_readable($filePath)) {
            throw new Exception("Cannot read .env file at: {$filePath}");
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($name, $value) = $this->parseLine($line);
                
                if ($name) {
                    $this->setEnvironmentVariable($name, $value);
                }
            }
        }
        
        return true;
    }
    
    /**
     * Parse line dari .env file
     * 
     * @param string $line
     * @return array [name, value]
     */
    protected function parseLine($line)
    {
        // Split by first =
        list($name, $value) = explode('=', $line, 2);
        
        $name = trim($name);
        $value = trim($value);
        
        // Remove quotes
        $value = $this->removeQuotes($value);
        
        // Remove inline comments
        if (strpos($value, ' #') !== false) {
            $value = trim(substr($value, 0, strpos($value, ' #')));
        }
        
        return [$name, $value];
    }
    
    /**
     * Remove quotes dari value
     * 
     * @param string $value
     * @return string
     */
    protected function removeQuotes($value)
    {
        // Remove double quotes
        if (substr($value, 0, 1) === '"' && substr($value, -1) === '"') {
            return substr($value, 1, -1);
        }
        
        // Remove single quotes
        if (substr($value, 0, 1) === "'" && substr($value, -1) === "'") {
            return substr($value, 1, -1);
        }
        
        return $value;
    }
    
    /**
     * Set environment variable
     * 
     * @param string $name
     * @param string $value
     */
    protected function setEnvironmentVariable($name, $value)
    {
        // Set to $_ENV
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
        
        // Set to $_SERVER
        if (!array_key_exists($name, $_SERVER)) {
            $_SERVER[$name] = $value;
        }
        
        // Set to putenv
        if (function_exists('putenv')) {
            putenv("{$name}={$value}");
        }
    }
    
    /**
     * Get environment variable
     * 
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function get($name, $default = null)
    {
        // Check $_ENV
        if (isset($_ENV[$name])) {
            return $_ENV[$name];
        }
        
        // Check $_SERVER
        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }
        
        // Check getenv()
        $value = getenv($name);
        if ($value !== false) {
            return $value;
        }
        
        return $default;
    }
    
    /**
     * Get environment variable as boolean
     * 
     * @param string $name
     * @param bool $default
     * @return bool
     */
    public static function getBool($name, $default = false)
    {
        $value = self::get($name);
        
        if ($value === null) {
            return $default;
        }
        
        $value = strtolower($value);
        
        return in_array($value, ['true', '1', 'yes', 'on']);
    }
}
