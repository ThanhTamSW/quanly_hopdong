<?php
/**
 * Simple .env file loader
 * Load environment variables from .env file
 */

function loadEnv($file_path = __DIR__ . '/../.env') {
    if (!file_exists($file_path)) {
        return false;
    }
    
    $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            // Set environment variable
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
    
    return true;
}

/**
 * Get environment variable with fallback
 */
function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// Auto-load .env file when this file is included
loadEnv();
?>

