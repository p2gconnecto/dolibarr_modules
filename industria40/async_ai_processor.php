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

        // OpenAI API call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $openai_api_key]);
        $request_data = [
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Analizza questa immagine e estrai tutte le informazioni testuali e visive rilevanti. '
                                . 'Restituisci la risposta in formato JSON strutturato seguendo esattamente uno dei modelli forniti, '
                                . 'riempiendo i campi appropriati. Seleziona il modello più adatto basandoti sul tipo di contenuto '
                                . 'dell\'immagine (fattura, preventivo, scheda tecnica, schermata, targhetta identificativa o foto generica).'
                                . "\n\nModello JSON da utilizzare (scegli il più appropriato):\n"
                                . '```json
{
  "fattura": {
    "numero": "",
    "data": "",
    "emettitore": "",
    "piva_emettitore": "",
    "destinatario": "",
    "piva_destinatario": "",
    "prodotti": [
      {
        "descrizione": "",
        "quantita": 0,
        "prezzo_unitario": 0.00,
        "totale": 0.00
      }
    ],
    "totale_documento": 0.00
  },
  "preventivo": {
    "numero": "",
    "data": "",
    "emettitore": "",
    "piva_emettitore": "",
    "destinatario": "",
    "piva_destinatario": "",
    "prodotti": [
      {
        "descrizione": "",
        "quantita": 0,
        "prezzo_unitario": 0.00,
        "totale": 0.00
      }
    ],
    "totale_documento": 0.00
  },
  "scheda": {
    "marca": "",
    "modello": "",
    "descrizione": "",
    "funzionalita_principali": [],
    "dati_tecnici": {
      "dimensioni": "",
      "peso": "",
      "alimentazione": "",
      "connettivita": "",
      "sensori": []
    }
  },
  "schermata": {
    "sorgente": "",
    "url_o_indirizzo_ip": "",
    "timestamp": "",
    "tipo_dato": ""
  },
  "targhetta": {
    "marca": "",
    "modello_o_tipo": "",
    "matricola": "",
    "anno_costruzione": "",
    "omologazione": ""
  },
  "foto": {
    "tipo": "",
    "contesto": "",
    "annotazioni_visive": ""
  }
}```
'
                                . 'Fornisci solo il JSON appropriato compilato con i dati che riesci a identificare dall\'immagine, senza commenti o testo aggiuntivo.'
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:image/'.$file_extension.';base64,'.$base64_image
                            ]
                        ]
                    ]
                ]
            ],
            'max_tokens' => 1000,
            'response_format' => ['type' => 'json_object']
        ];
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

                        // Creiamo un testo di riepilogo per la visualizzazione
                        $summary = "Tipo documento: " . ucfirst($detected_type) . "\n";

                        // Aggiungiamo informazioni in base al tipo di documento
                        switch ($detected_type) {
                            case 'fattura':
                            case 'preventivo':
                                $doc = $json_response[$detected_type];
                                $summary .= "Numero: " . ($doc['numero'] ?? 'N/D') . "\n";
                                $summary .= "Data: " . ($doc['data'] ?? 'N/D') . "\n";
                                $summary .= "Emesso da: " . ($doc['emettitore'] ?? 'N/D') . "\n";
                                $summary .= "A favore di: " . ($doc['destinatario'] ?? 'N/D') . "\n";
                                $summary .= "Totale: " . ($doc['totale_documento'] ?? 'N/D') . "€\n";
                                break;
                            case 'scheda':
                                $doc = $json_response[$detected_type];
                                $summary .= "Marca: " . ($doc['marca'] ?? 'N/D') . "\n";
                                $summary .= "Modello: " . ($doc['modello'] ?? 'N/D') . "\n";
                                $summary .= "Descrizione: " . ($doc['descrizione'] ?? 'N/D') . "\n";
                                break;
                            case 'targhetta':
                                $doc = $json_response[$detected_type];
                                $summary .= "Marca: " . ($doc['marca'] ?? 'N/D') . "\n";
                                $summary .= "Modello: " . ($doc['modello_o_tipo'] ?? 'N/D') . "\n";
                                $summary .= "Matricola: " . ($doc['matricola'] ?? 'N/D') . "\n";
                                $summary .= "Anno: " . ($doc['anno_costruzione'] ?? 'N/D') . "\n";
                                break;
                            case 'schermata':
                                $doc = $json_response[$detected_type];
                                $summary .= "Sorgente: " . ($doc['sorgente'] ?? 'N/D') . "\n";
                                $summary .= "URL/IP: " . ($doc['url_o_indirizzo_ip'] ?? 'N/D') . "\n";
                                break;
                            case 'foto':
                                $doc = $json_response[$detected_type];
                                $summary .= "Tipo: " . ($doc['tipo'] ?? 'N/D') . "\n";
                                $summary .= "Contesto: " . ($doc['contesto'] ?? 'N/D') . "\n";
                                $summary .= "Note: " . ($doc['annotazioni_visive'] ?? 'N/D') . "\n";
                                break;
                        }

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
