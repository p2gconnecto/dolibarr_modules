<?php
/**
 * AI Functions Library for Industria40 Module
 * Contiene funzioni per gestire le risposte dell'AI, il salvataggio e il recupero dei dati OCR
 */

/**
 * Recupera una risposta AI salvata
 *
 * @param string $file_key Chiave del file
 * @return mixed Contenuto della risposta o false se non trovata
 */
function get_stored_ai_response($file_key) {
    global $conf;

    // Aggiungiamo debug approfondito
    error_log("get_stored_ai_response chiamata con file_key originale: " . $file_key);

    // Salviamo la chiave originale prima della sanitizzazione
    $original_file_key = $file_key;

    // Assicuriamoci che la chiave del file sia sicura per il filesystem
    $safe_file_key = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file_key);

    // Reportiamo la differenza se c'è stata una conversione
    if ($original_file_key !== $safe_file_key) {
        error_log("Chiave file sanitizzata da: '$original_file_key' a: '$safe_file_key'");
    }

    $ai_response_dir = DOL_DATA_ROOT . '/industria40/ai_responses';
    $ai_response_file = $ai_response_dir . '/' . $safe_file_key . '.txt';

    // Verifichiamo anche il percorso originale in caso di migrazione
    $original_response_file = $ai_response_dir . '/' . $original_file_key . '.txt';

    // Log per debug
    error_log("Ricerca risposta AI in file sanitizzato: " . $ai_response_file);
    if ($original_file_key !== $safe_file_key) {
        error_log("Ricerca anche nel percorso originale: " . $original_response_file);
    }

    // Prima controlliamo il file sanitizzato
    if (file_exists($ai_response_file)) {
        $content = @file_get_contents($ai_response_file);
        if ($content !== false) {
            error_log("Risposta AI trovata nel file sanitizzato");
            return $content;
        }
    }

    // Se il file sanitizzato non esiste, proviamo con il percorso originale
    if ($original_file_key !== $safe_file_key && file_exists($original_response_file)) {
        $content = @file_get_contents($original_response_file);
        if ($content !== false) {
            error_log("Risposta AI trovata nel percorso originale, migrando al formato sanitizzato");
            // Migriamo il contenuto al nuovo formato
            if (store_ai_response($file_key, $content)) {
                // Eliminiamo il vecchio file dopo la migrazione
                @unlink($original_response_file);
            }
            return $content;
        }
    }

    // Log per debug
    error_log("File risposta AI non trovato o non leggibile: " . $ai_response_file);

    return false;
}

/**
 * Salva una risposta AI
 *
 * @param string $file_key Chiave del file
 * @param string $content Contenuto da salvare
 * @return bool Esito dell'operazione
 */
function store_ai_response($file_key, $content) {
    global $conf;

    // Aggiungiamo debug approfondito
    error_log("store_ai_response chiamata con file_key originale: " . $file_key);

    // Log della risposta AI
    log_ai_transaction('response_store', $file_key, substr($content, 0, 1000) . '... [truncated]');

    // Salviamo la chiave originale prima della sanitizzazione
    $original_file_key = $file_key;

    // Assicuriamoci che la chiave del file sia sicura per il filesystem
    $safe_file_key = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file_key);

    // Reportiamo la differenza se c'è stata una conversione
    if ($original_file_key !== $safe_file_key) {
        error_log("Chiave file sanitizzata da: '$original_file_key' a: '$safe_file_key'");
    }

    $ai_response_dir = DOL_DATA_ROOT . '/industria40/ai_responses';

    // Assicurati che la directory esista
    if (!file_exists($ai_response_dir)) {
        if (!dol_mkdir($ai_response_dir)) {
            error_log("Impossibile creare la directory per le risposte AI: " . $ai_response_dir);
            return false;
        }
    }

    $ai_response_file = $ai_response_dir . '/' . $safe_file_key . '.txt';

    // Log per debug
    error_log("Salvataggio risposta AI in: " . $ai_response_file);

    $result = @file_put_contents($ai_response_file, $content);

    if ($result === false) {
        error_log("Errore nel salvare la risposta AI: " . $ai_response_file);
        return false;
    }

    error_log("Risposta AI salvata con successo in: " . $ai_response_file);
    return true;
}

/**
 * Elimina una risposta AI
 *
 * @param string $file_key Chiave del file
 * @return bool Esito dell'operazione
 */
function delete_stored_ai_response($file_key) {
    global $conf;

    // Aggiungiamo debug approfondito
    error_log("delete_stored_ai_response chiamata con file_key originale: " . $file_key);

    // Salviamo la chiave originale prima della sanitizzazione
    $original_file_key = $file_key;

    // Assicuriamoci che la chiave del file sia sicura per il filesystem
    $safe_file_key = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file_key);

    // Reportiamo la differenza se c'è stata una conversione
    if ($original_file_key !== $safe_file_key) {
        error_log("Chiave file sanitizzata da: '$original_file_key' a: '$safe_file_key'");
    }

    $ai_response_dir = DOL_DATA_ROOT . '/industria40/ai_responses';
    $ai_response_file = $ai_response_dir . '/' . $safe_file_key . '.txt';

    // Verifichiamo anche il percorso originale in caso di file legacy
    $original_response_file = $ai_response_dir . '/' . $original_file_key . '.txt';

    // Log per debug
    error_log("Tentativo di eliminazione della risposta AI dal file sanitizzato: " . $ai_response_file);
    if ($original_file_key !== $safe_file_key) {
        error_log("Verifica anche nel percorso originale: " . $original_response_file);
    }

    $success = false;

    // Prima controlliamo il file sanitizzato
    if (file_exists($ai_response_file)) {
        if (@unlink($ai_response_file)) {
            error_log("Risposta AI eliminata con successo: " . $ai_response_file);
            $success = true;
        } else {
            error_log("Errore nell'eliminare la risposta AI: " . $ai_response_file);
        }
    }

    // Controlliamo anche il file originale se è diverso
    if ($original_file_key !== $safe_file_key && file_exists($original_response_file)) {
        if (@unlink($original_response_file)) {
            error_log("Risposta AI (formato originale) eliminata con successo: " . $original_response_file);
            $success = true;
        } else {
            error_log("Errore nell'eliminare la risposta AI (formato originale): " . $original_response_file);
        }
    }

    // I file non esistevano o sono stati eliminati con successo
    return $success || !file_exists($ai_response_file) && !file_exists($original_response_file);
}

/**
 * Scrive un messaggio di log per le operazioni AI
 *
 * @param string $message Il messaggio da registrare
 * @param string $file_key Chiave del file associato (opzionale)
 * @return void
 */
function writeToLog($message, $file_key = '') {
    global $conf, $user;

    $log_message = date('Y-m-d H:i:s') . " - ";
    if ($user && isset($user->login)) {
        $log_message .= "User: " . $user->login . " - ";
    }
    if (!empty($file_key)) {
        $log_message .= "File: " . $file_key . " - ";
    }
    $log_message .= $message;

    // Utilizziamo il sistema di logging di Dolibarr
    dol_syslog($log_message, LOG_INFO);

    // Scrivi anche su un file di log specifico per il modulo
    $log_dir = DOL_DATA_ROOT . '/industria40/logs';
    if (!file_exists($log_dir)) {
        dol_mkdir($log_dir);
    }

    $log_file = $log_dir . '/' . date('Y-m-d') . '_ai_operations.log';
    @file_put_contents($log_file, $log_message . "\n", FILE_APPEND);
}

/**
 * Carica il testo OCR di un file
 *
 * @param string $file_path Percorso completo del file
 * @return string Testo OCR o stringa vuota se non disponibile
 */
function load_ocr_text($file_path) {
    global $conf;

    // Calcola il nome del file OCR basato sul percorso del file originale
    $ocr_dir = DOL_DATA_ROOT . '/industria40/ocr';
    $ocr_file = $ocr_dir . '/' . md5($file_path) . '.txt';

    if (file_exists($ocr_file)) {
        $content = @file_get_contents($ocr_file);
        if ($content !== false) {
            return $content;
        }
    }

    return '';
}

/**
 * Esegue OCR su un file
 *
 * @param string $file_path Percorso completo del file
 * @param string $extension Estensione del file
 * @return string Testo estratto dall'OCR o stringa vuota in caso di errore
 */
function perform_ocr_on_file($file_path, $extension) {
    global $conf;

    // Directory per i risultati OCR
    $ocr_dir = DOL_DATA_ROOT . '/industria40/ocr';
    if (!file_exists($ocr_dir)) {
        if (!dol_mkdir($ocr_dir)) {
            error_log("Impossibile creare la directory OCR: " . $ocr_dir);
            return '';
        }
    }

    // File di output OCR
    $ocr_file = $ocr_dir . '/' . md5($file_path) . '.txt';

    // Implementazione di base - nella realtà useremmo un servizio OCR
    // Questo è un semplice placeholder
    $ocr_text = "OCR eseguito su " . basename($file_path) . " il " . date('Y-m-d H:i:s');

    if (@file_put_contents($ocr_file, $ocr_text)) {
        return $ocr_text;
    }

    return '';
}

/**
 * Analizza il contenuto OCR per suggerire tag o nomi di file
 *
 * @param string $ocr_text Testo OCR da analizzare
 * @param string $current_filename Nome attuale del file
 * @param string $extension Estensione del file
 * @return array Array con suggerimenti
 */
function analyze_ocr_content($ocr_text, $current_filename, $extension) {
    $suggestions = [];

    // Implementazione semplificata per scopi dimostrativi
    if (!empty($ocr_text)) {
        // Suggerisci un nome di file basato sul contenuto OCR
        $suggested_name = sanitize_filename(substr($ocr_text, 0, 30)) . '.' . $extension;
        $suggestions['suggested_name'] = $suggested_name;

        // Suggerisci un tag basato sul contenuto OCR
        if (stripos($ocr_text, 'fattura') !== false) {
            $suggestions['suggested_tag'] = 'invoice';
        } elseif (stripos($ocr_text, 'preventivo') !== false) {
            $suggestions['suggested_tag'] = 'estimate';
        } elseif (stripos($ocr_text, 'scheda tecnica') !== false) {
            $suggestions['suggested_tag'] = 'datasheet';
        } else {
            $suggestions['suggested_tag'] = 'document';
        }
    }

    return $suggestions;
}

/**
 * Sanifica un nome di file
 *
 * @param string $filename Nome del file da sanificare
 * @return string Nome del file sanificato
 */
function sanitize_filename($filename) {
    // Rimuovi caratteri non sicuri
    $filename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $filename);

    // Limita la lunghezza
    if (strlen($filename) > 50) {
        $filename = substr($filename, 0, 47) . '...';
    }

    return $filename;
}

/**
 * Aggiorna un valore in un array JSON in base a un percorso
 *
 * @param array &$json_data Array JSON da modificare
 * @param string $path Percorso del valore da aggiornare
 * @param mixed $value Nuovo valore
 * @return void
 */
function update_json_value(&$json_data, $path, $value) {
    $path_parts = explode('.', $path);
    $current = &$json_data;

    foreach ($path_parts as $key) {
        if (is_array($current) && isset($current[$key])) {
            $current = &$current[$key];
        } else {
            return; // Percorso non valido
        }
    }

    $current = $value;
}

/**
 * Aggiorna il riepilogo da un array JSON
 *
 * @param string $file_key Chiave del file
 * @param array $json_data Dati JSON
 * @return bool Esito dell'operazione
 */
function update_summary_from_json($file_key, $json_data) {
    // Determiniamo quale tipo di documento è stato identificato
    $detected_type = '';
    foreach (['fattura', 'preventivo', 'scheda', 'schermata', 'targhetta', 'foto'] as $type) {
        if (isset($json_data[$type]) && !empty($json_data[$type])) {
            $detected_type = $type;
            break;
        }
    }

    if (empty($detected_type)) {
        return false;
    }

    // Creiamo un testo di riepilogo per la visualizzazione
    $summary = "Tipo documento: " . ucfirst($detected_type) . "\n";

    // Aggiungiamo informazioni in base al tipo di documento
    switch ($detected_type) {
        case 'fattura':
        case 'preventivo':
            $doc = $json_data[$detected_type];
            $summary .= "Numero: " . ($doc['numero'] ?? 'N/D') . "\n";
            $summary .= "Data: " . ($doc['data'] ?? 'N/D') . "\n";
            $summary .= "Emesso da: " . ($doc['emettitore'] ?? 'N/D') . "\n";
            $summary .= "A favore di: " . ($doc['destinatario'] ?? 'N/D') . "\n";
            $summary .= "Totale: " . ($doc['totale_documento'] ?? 'N/D') . "€\n";
            break;
        case 'scheda':
            $doc = $json_data[$detected_type];
            $summary .= "Marca: " . ($doc['marca'] ?? 'N/D') . "\n";
            $summary .= "Modello: " . ($doc['modello'] ?? 'N/D') . "\n";
            $summary .= "Descrizione: " . ($doc['descrizione'] ?? 'N/D') . "\n";
            break;
        case 'targhetta':
            $doc = $json_data[$detected_type];
            $summary .= "Marca: " . ($doc['marca'] ?? 'N/D') . "\n";
            $summary .= "Modello: " . ($doc['modello_o_tipo'] ?? 'N/D') . "\n";
            $summary .= "Matricola: " . ($doc['matricola'] ?? 'N/D') . "\n";
            $summary .= "Anno: " . ($doc['anno_costruzione'] ?? 'N/D') . "\n";
            break;
        case 'schermata':
            $doc = $json_data[$detected_type];
            $summary .= "Sorgente: " . ($doc['sorgente'] ?? 'N/D') . "\n";
            $summary .= "Tipo dato: " . ($doc['tipo_dato'] ?? 'N/D') . "\n";
            $summary .= "Timestamp: " . ($doc['timestamp'] ?? 'N/D') . "\n";
            $summary .= "Indirizzo IP: " . ($doc['indirizzo_ip'] ?? 'N/D') . "\n";
            $summary .= "URL: " . ($doc['url'] ?? 'N/D') . "\n";
            break;
        case 'foto':
            $doc = $json_data[$detected_type];
            $summary .= "Tipo: " . ($doc['tipo'] ?? 'N/D') . "\n";
            $summary .= "Contesto: " . ($doc['contesto'] ?? 'N/D') . "\n";
            $summary .= "Note: " . ($doc['annotazioni_visive'] ?? 'N/D') . "\n";
            break;
    }




}    return store_ai_response($file_key . '_summary', $summary);    // Salviamo anche la versione di riepilogo per visualizzazione
/**
 * Log dettagliato per richieste e risposte AI
 *
 * @param string $type Tipo di log (request/response)
 * @param string $file_key Chiave del file
 * @param mixed $data Dati da loggare
 * @return void
 */
function log_ai_transaction($type, $file_key, $data) {
    $log_dir = DOL_DATA_ROOT . '/industria40/logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }

    $log_file = $log_dir . '/ai_transactions_' . date('Y-m-d') . '.log';

    $log_entry = date('Y-m-d H:i:s') . " | " . $type . " | " . $file_key . " | ";

    if (is_array($data) || is_object($data)) {
        $log_entry .= json_encode($data, JSON_UNESCAPED_SLASHES);
    } else {
        $log_entry .= $data;
    }

    $log_entry .= "\n";

    @file_put_contents($log_file, $log_entry, FILE_APPEND);

    // Log anche in error_log per centralizzare
    error_log("AI Transaction [$type] for file_key: $file_key");
}

/**
 * AI Functions Library for Industria40 Module
 * Contains helper functions for AI operations
 */

if (!defined('DOL_DOCUMENT_ROOT')) die('Dolibarr environment not defined');

/**
 * Formats AI outputs to be displayed in a user-friendly way
 *
 * @param string $content The AI-generated content
 * @param string $format Output format ('html', 'text', 'json')
 * @return string Formatted content
 */
function format_ai_output($content, $format = 'html') {
    if (empty($content)) return '';

    // Detect if content is likely to be JSON
    $json_data = json_decode($content, true);
    $is_json = (json_last_error() === JSON_ERROR_NONE);

    switch ($format) {
        case 'html':
            if ($is_json) {
                // Format JSON as nice HTML
                $output = '<div class="ai-json-data">';
                $output .= '<table class="border" width="100%">';

                // Iterate through the top-level JSON entries
                foreach ($json_data as $key => $value) {
                    $output .= '<tr class="liste_titre"><th colspan="2">' . ucfirst($key) . '</th></tr>';

                    if (is_array($value)) {
                        foreach ($value as $subkey => $subvalue) {
                            if (is_array($subvalue)) {
                                // Handle nested arrays (like products in a fattura)
                                $output .= '<tr><td class="subtitle">' . ucfirst($subkey) . '</td><td>';
                                $output .= '<table width="100%">';
                                foreach ($subvalue as $item) {
                                    if (is_array($item)) {
                                        foreach ($item as $itemkey => $itemvalue) {
                                            $output .= '<tr><td class="subitem">' . ucfirst($itemkey) . '</td><td>' .
                                                    htmlspecialchars($itemvalue) . '</td></tr>';
                                        }
                                    } else {
                                        $output .= '<tr><td colspan="2">' . htmlspecialchars($item) . '</td></tr>';
                                    }
                                }
                                $output .= '</table>';
                                $output .= '</td></tr>';
                            } else {
                                // Simple key-value pair
                                $output .= '<tr><td class="titlefield">' . ucfirst($subkey) . '</td><td>' .
                                        htmlspecialchars($subvalue) . '</td></tr>';
                            }
                        }
                    } else {
                        $output .= '<tr><td colspan="2">' . htmlspecialchars($value) . '</td></tr>';
                    }
                }

                $output .= '</table>';
                $output .= '</div>';
            } else {
                // Format text as HTML
                $output = '<div class="ai-text-content">';
                $output .= nl2br(htmlspecialchars($content));
                $output .= '</div>';
            }
            break;

        case 'json':
            if ($is_json) {
                // Already JSON, pretty-print it
                $output = json_encode($json_data, JSON_PRETTY_PRINT);
            } else {
                // Not JSON, create a simple JSON wrapper
                $output = json_encode(['text' => $content]);
            }
            break;

        case 'text':
        default:
            if ($is_json) {
                // Convert JSON to a simple text representation
                $output = '';
                foreach ($json_data as $key => $value) {
                    $output .= strtoupper($key) . ":\n";
                    if (is_array($value)) {
                        foreach ($value as $subkey => $subvalue) {
                            if (is_array($subvalue)) {
                                $output .= "  " . ucfirst($subkey) . ":\n";
                                foreach ($subvalue as $idx => $item) {
                                    if (is_array($item)) {
                                        $output .= "    Item " . ($idx+1) . ":\n";
                                        foreach ($item as $itemkey => $itemvalue) {
                                            $output .= "      " . ucfirst($itemkey) . ": " . $itemvalue . "\n";
                                        }
                                    } else {
                                        $output .= "    " . $item . "\n";
                                    }
                                }
                            } else {
                                $output .= "  " . ucfirst($subkey) . ": " . $subvalue . "\n";
                            }
                        }
                    } else {
                        $output .= "  " . $value . "\n";
                    }
                    $output .= "\n";
                }
            } else {
                // Already text
                $output = $content;
            }
    }

    return $output;
}

/**
 * Log AI operations to central location
 *
 * @param string $message Log message
 * @param string $level Log level (debug, info, warning, error)
 * @return void
 */
function ai_log($message, $level = 'info') {
    $log_dir = DOL_DATA_ROOT . '/industria40/logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0775, true);
    }

    $log_file = $log_dir . '/ai_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');

    // Format level for log
    $level_str = strtoupper($level);

    // Create log entry
    $log_entry = "[$timestamp] [$level_str] $message\n";

    // Append to log file
    @file_put_contents($log_file, $log_entry, FILE_APPEND);

    // Also log to Dolibarr's system if possible
    if (function_exists('dol_syslog')) {
        $log_level = LOG_INFO; // Default

        // Map our levels to Dolibarr's constants
        switch (strtolower($level)) {
            case 'debug':
                $log_level = LOG_DEBUG;
                break;
            case 'info':
                $log_level = LOG_INFO;
                break;
            case 'warning':
                $log_level = LOG_WARNING;
                break;
            case 'error':
                $log_level = LOG_ERR;
                break;
        }

        dol_syslog("AI: $message", $log_level);
    }
}