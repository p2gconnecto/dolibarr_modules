<?php
/**
 * File Manager Functions Library
 * Contiene funzioni comuni usate dalle diverse viste del gestore file
 */

// Includi la libreria di funzioni AI che contiene get_stored_ai_response e altre
require_once __DIR__ . '/lib/ai_functions.php';

/**
 * Carica le variabili d'ambiente dal file .env
 * @return void
 */
function load_dotenv() {
    clearstatcache();
    dol_syslog("file_manager_functions.php: Cleared stat cache.", LOG_DEBUG);

    $dotenv_path = DOL_DOCUMENT_ROOT . '/custom/industria40/.env';
    $fallback_path = DOL_DOCUMENT_ROOT . '/custom/.env';

    dol_syslog("file_manager_functions.php: Attempting to load env file from: " . $dotenv_path, LOG_DEBUG);

    if (file_exists($dotenv_path)) {
        parse_dotenv_file($dotenv_path);
    } elseif (file_exists($fallback_path)) {
        dol_syslog("file_manager_functions.php: Primary .env not found, trying fallback: " . $fallback_path, LOG_DEBUG);
        parse_dotenv_file($fallback_path);
    } else {
        dol_syslog("file_manager_functions.php: No .env file found at expected paths.", LOG_INFO);
    }
}

/**
 * Analizza un file .env e imposta le variabili d'ambiente
 * @param string $file_path Percorso del file .env
 * @return void
 */
function parse_dotenv_file($file_path) {
    try {
        $file_content = @file_get_contents($file_path);
        if ($file_content === false) {
            $last_error = error_get_last();
            $error_message = $last_error ? $last_error['message'] : 'Unknown error reading file.';
            dol_syslog("file_manager_functions.php: Failed to read .env file at $file_path. Error: " . $error_message, LOG_WARNING);
            return;
        }

        dol_syslog("file_manager_functions.php: Successfully read .env file from $file_path", LOG_DEBUG);
        $lines = explode("\n", $file_content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                if (strlen($value) > 1 && (($value[0] == '"' && substr($value, -1) == '"') ||
                                         ($value[0] == "'" && substr($value, -1) == "'"))) {
                    $value = substr($value, 1, -1);
                }

                if (getenv($key) === false && function_exists('putenv')) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                    dol_syslog("file_manager_functions.php: Set environment variable '$key'", LOG_DEBUG);
                }
            }
        }
    } catch (Exception $e) {
        dol_syslog("file_manager_functions.php: Exception while parsing .env file: " . $e->getMessage(), LOG_ERR);
    }
}

/**
 * Generate a unique file ID based on its path
 * @param string $file_path Full path to the file
 * @return string Unique file ID
 */
function generate_file_id($file_path) {
    return md5($file_path);
}

/**
 * Get file extension
 * @param string $filename Filename
 * @return string File extension in lowercase
 */
function get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if a file is an image
 * @param string $filename Filename
 * @return bool True if file is an image
 */
function is_image_file($filename) {
    $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    $extension = get_file_extension($filename);
    return in_array($extension, $image_extensions);
}

/**
 * Check if a file is a PDF
 * @param string $filename Filename
 * @return bool True if file is a PDF
 */
function is_pdf_file($filename) {
    return get_file_extension($filename) === 'pdf';
}

/**
 * Get thumbnail path for a file
 * @param int $socid Society ID
 * @param string $perizia_id Perizia ID
 * @param string $filename Filename
 * @return string Full path to thumbnail
 */
function get_thumbnail_path($socid, $perizia_id, $filename) {
    $base_path = DOL_DATA_ROOT . '/industria40/thumbnails/' . $socid . '/' . $perizia_id;
    $file_base = pathinfo($filename, PATHINFO_FILENAME);
    return $base_path . '/thumb_' . $file_base . '.jpg';
}

/**
 * Check if a thumbnail exists for a file
 * @param int $socid Society ID
 * @param string $perizia_id Perizia ID
 * @param string $filename Filename
 * @return bool True if thumbnail exists
 */
function has_thumbnail($socid, $perizia_id, $filename) {
    return file_exists(get_thumbnail_path($socid, $perizia_id, $filename));
}

/**
 * Clean up temporary files and directories created during processing
 * @param string $directory Directory to clean
 * @return void
 */
function cleanup_temp_files($directory) {
    if (is_dir($directory)) {
        $files = glob($directory . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($directory);
    }
}