<?php
/**
 * Script per elaborare richieste asincrone di analisi AI
 * Questo file viene chiamato via AJAX o cron per processare file in background
 */

if (!defined('NOREQUIREUSER')) define('NOREQUIREUSER', '1');
if (!defined('NOREQUIREDB')) define('NOREQUIREDB', '0');
if (!defined('NOREQUIRESOC')) define('NOREQUIRESOC', '1');
if (!defined('NOREQUIRETRAN')) define('NOREQUIRETRAN', '0');
if (!defined('NOLOGIN')) define('NOLOGIN', '0'); // Login required
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', '0');
if (!defined('NOIPCHECK')) define('NOIPCHECK', '0');
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');

// Load Dolibarr environment
$res = 0;
$res = @include_once __DIR__ . '/../main.inc.php';
if (!$res) {
    $res = @include_once __DIR__ . '/../../main.inc.php';
}
if (!$res) die('Error: Failed to include Dolibarr main.inc.php file');

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once __DIR__ . '/lib/industria40.lib.php';
require_once __DIR__ . '/config/openai_api_templates.php'; // Include the new configuration file

// Controllo sicurezza - solo utenti autenticati
if (!$user->rights->industria40->read && !$user->admin) {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorizzato']);
    exit;
}

// Parametri richiesti
$action = GETPOST('aiai_action', 'alpha');
$socid = GETPOST('socid', 'int');
$perizia_id = GETPOST('perizia_id', 'int');
$file_name = GETPOST('file_name', 'alpha');

// Controllo parametri
if (empty($action) || empty($socid) || empty($perizia_id) || empty($file_name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parametri mancanti']);
    exit;
}

$periziaid_sanitized = dol_sanitizeFileName($perizia_id);
$file_key = $socid . '_' . $periziaid_sanitized . '_' . $file_name;
$upload_dir = DOL_DATA_ROOT . '/industria40/' . $socid . '/' . $periziaid_sanitized;
$file_full_path = rtrim($upload_dir, '/') . '/' . $file_name;
$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Scrivi informazioni nel log
writeToLog("Starting async AI processing for file: " . $file_name, $file_key);

// Controllo se è già presente una descrizione AI
$ai_description = get_stored_ai_response($file_key);
if ($ai_description !== false) {
    echo json_encode(['status' => 'already_exists', 'message' => 'La descrizione AI esiste già']);
    writeToLog("AI description already exists for: " . $file_name, $file_key);
    exit;
}

// Verifica presenza API Key
$openai_api_key = getenv('OPENAI_API_KEY');
if (empty($openai_api_key)) $openai_api_key = !empty($conf->global->INDUSTRIA40_OPENAI_API_KEY) ? $conf->global->INDUSTRIA40_OPENAI_API_KEY : '';

// Aggiungi questo blocco per verificare l'esistenza del riepilogo compatto
$ai_description_summary_compact = get_stored_ai_response($file_key . '_summary_compact');
if ($ai_description_summary_compact === false && $ai_description !== false) {
    // Se esiste la descrizione AI ma non il riepilogo compatto, crealo
    writeToLog("Creating compact summary from existing AI description", $file_key);
    $compact_summary = create_compact_summary_from_description($file_key);
    if (!empty($compact_summary)) {
        store_ai_response($file_key . '_summary_compact', $compact_summary);
        writeToLog("Compact summary created successfully", $file_key);
    }
}

if (empty($openai_api_key)) {
    echo json_encode(['status' => 'error', 'message' => 'OpenAI API key non configurata']);
    writeToLog("AI description generation failed: OpenAI API key not configured", $file_key);
    exit;
}

// Elaborazione in base all'estensione del file
if (in_array($file_extension, array('jpg', 'jpeg', 'png', 'gif')) && file_exists($file_full_path)) {
    $image_data = @file_get_contents($file_full_path);
    if ($image_data) {
        $base64_image = base64_encode($image_data);
        writeToLog("Image converted to base64 for OpenAI API", $file_key);

        // OpenAI API call - using the external configuration
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $openai_api_key]);

        // Get request data from external configuration file
        $request_data = get_image_analysis_request($file_extension, $base64_image);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
        writeToLog("Sending async request to OpenAI API", $file_key);
        $response_api = curl_exec($ch);
        curl_close($ch);

        if ($response_api) {
            writeToLog("Received async response from OpenAI API", $file_key);
            $decoded_api = json_decode($response_api, true);

            if (isset($decoded_api['choices'][0]['message']['content'])) {
                $description_text = $decoded_api['choices'][0]['message']['content'];

                // Verifichiamo che la risposta sia effettivamente in JSON valido
                $json_response = json_decode($description_text, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    writeToLog("AI response received in valid JSON format", $file_key);

                    // Determiniamo quale tipo di documento è stato identificato
                    $detected_type = '';
                    foreach (['fattura', 'preventivo', 'scheda', 'schermata', 'targhetta', 'foto'] as $type) {
                        if (isset($json_response[$type]) && !empty($json_response[$type])) {
                            $detected_type = $type;
                            break;
                        }
                    }

                    if (!empty($detected_type)) {
                        writeToLog("Documento identificato come: " . $detected_type, $file_key);

                        // Salviamo sia il JSON completo che una versione formattata per la visualizzazione
                        store_ai_response($file_key, $description_text);

                        // Use the external function to generate the summary
                        $summary = get_document_summary($detected_type, $json_response[$detected_type]);

                        // Salviamo anche la versione di riepilogo per visualizzazione
                        store_ai_response($file_key . '_summary', $summary);

                        echo json_encode(['status' => 'success', 'message' => 'Descrizione AI generata con successo', 'type' => $detected_type]);
                    } else {
                        writeToLog("JSON ricevuto ma nessun tipo di documento identificato", $file_key);
                        store_ai_response($file_key, $description_text);
                        echo json_encode(['status' => 'partial_success', 'message' => 'Descrizione AI generata ma tipo non riconosciuto']);
                    }
                } else {
                    // Se non è in formato JSON, salviamo come testo semplice
                    writeToLog("AI response not in JSON format, saving as plain text", $file_key);
                    store_ai_response($file_key, $description_text);
                    echo json_encode(['status' => 'partial_success', 'message' => 'Descrizione AI generata ma non in formato JSON']);
                }
            } else {
                writeToLog("Error parsing OpenAI API response: " . json_encode($decoded_api), $file_key);
                echo json_encode(['status' => 'error', 'message' => 'Errore nella risposta di OpenAI API']);
            }
        } else {
            writeToLog("No response from OpenAI API", $file_key);
            echo json_encode(['status' => 'error', 'message' => 'Nessuna risposta da OpenAI API']);
        }
    } else {
        writeToLog("Failed to read image file: " . $file_full_path, $file_key);
        echo json_encode(['status' => 'error', 'message' => 'Impossibile leggere il file immagine']);
    }
} elseif ($file_extension == 'pdf') {
    // Generic description for PDF
    writeToLog("Generating generic description for PDF file", $file_key);
    $generic_pdf_desc = "File PDF: " . $file_name . "\n(Descrizione generica per file PDF)";
    store_ai_response($file_key, $generic_pdf_desc);

    // Generate thumbnail for the PDF
    $thumbnail_dir = DOL_DATA_ROOT . '/industria40/thumbnails/' . $socid . '/' . $periziaid_sanitized;
    $thumbnail_filename = 'thumb_' . pathinfo($file_name, PATHINFO_FILENAME) . '.jpg';
    $thumbnail_path = $thumbnail_dir . '/' . $thumbnail_filename;

    // Create directory if it doesn't exist
    if (!is_dir($thumbnail_dir)) {
        if (!dol_mkdir($thumbnail_dir)) {
            writeToLog("Failed to create thumbnail directory: " . $thumbnail_dir, $file_key);
        }
    }

    // Generate thumbnail from PDF using ImageMagick or GhostScript if available
    if (!file_exists($thumbnail_path)) {
        writeToLog("Attempting to create thumbnail for PDF: " . $file_name, $file_key);

        $thumbnail_created = false;

        // Try using ImageMagick's convert
        if (function_exists('exec')) {
            // Check if ImageMagick is available
            $convert_path = '';
            exec('which convert', $output, $return_var);
            if ($return_var === 0 && !empty($output[0])) {
                $convert_path = $output[0];
            }

            if (!empty($convert_path)) {
                // Use ImageMagick to convert first page of PDF to JPG
                $cmd = escapeshellcmd($convert_path) . ' -density 150 ' . escapeshellarg($file_full_path . '[0]') . ' -quality 90 -resize 800x600 ' . escapeshellarg($thumbnail_path);
                exec($cmd, $output, $return_var);

                if ($return_var === 0 && file_exists($thumbnail_path)) {
                    $thumbnail_created = true;
                    writeToLog("PDF thumbnail created successfully using ImageMagick", $file_key);
                }
            }

            // If ImageMagick failed, try GhostScript
            if (!$thumbnail_created) {
                $gs_path = '';
                exec('which gs', $output, $return_var);
                if ($return_var === 0 && !empty($output[0])) {
                    $gs_path = $output[0];
                }

                if (!empty($gs_path)) {
                    // Use GhostScript to convert first page of PDF to JPG
                    $cmd = escapeshellcmd($gs_path) . ' -sDEVICE=jpeg -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dFirstPage=1 -dLastPage=1 ' .
                           '-dBATCH -dNOPAUSE -dSAFER -sOutputFile=' . escapeshellarg($thumbnail_path) . ' ' . escapeshellarg($file_full_path);
                    exec($cmd, $output, $return_var);

                    if ($return_var === 0 && file_exists($thumbnail_path)) {
                        $thumbnail_created = true;
                        writeToLog("PDF thumbnail created successfully using GhostScript", $file_key);
                    }
                }
            }
        }

        // Try using PHP's GD/Imagick extension as fallback
        if (!$thumbnail_created && extension_loaded('imagick')) {
            try {
                $imagick = new \Imagick();
                $imagick->setResolution(150, 150);
                $imagick->readImage($file_full_path . '[0]');
                $imagick->setImageFormat('jpg');
                $imagick->thumbnailImage(800, 0); // Width of 800, height auto
                $imagick->writeImage($thumbnail_path);
                $imagick->clear();
                $imagick->destroy();

                if (file_exists($thumbnail_path)) {
                    $thumbnail_created = true;
                    writeToLog("PDF thumbnail created successfully using PHP Imagick extension", $file_key);
                }
            } catch (Exception $e) {
                writeToLog("Error creating thumbnail with PHP Imagick: " . $e->getMessage(), $file_key);
            }
        }

        if (!$thumbnail_created) {
            writeToLog("Failed to create thumbnail for PDF: no suitable conversion tool found", $file_key);
        }
    } else {
        writeToLog("PDF thumbnail already exists: " . $thumbnail_path, $file_key);
    }

    echo json_encode(['status' => 'success', 'message' => 'Descrizione generica generata per il file PDF', 'thumbnail' => file_exists($thumbnail_path)]);
} else {
    writeToLog("File type not supported for AI description: " . $file_extension, $file_key);
    echo json_encode(['status' => 'error', 'message' => 'Tipo di file non supportato per analisi AI']);
}
?>
