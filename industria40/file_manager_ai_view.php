<?php
// This view is included by industria40index.php for AI interactions with a single file.
// It expects $socid, $periziaid, $periziaid_sanitized, $upload_dir, $form_action_url, $langs, $conf, $user, $db, $modulepart, $available_tags
// and $file_name_for_ai_view (passed as $file_name_param from index), and $action.

$current_file_name = $file_name_for_ai_view; // Use a local variable
if (empty($current_file_name)) {
    print '<div class="error">'.$langs->trans("FileNameNotProvidedForAIView").'</div>';
    return; // Exit if no file name
}

$upload_dir_path_ai = rtrim($upload_dir, '/') . '/';
$file_full_path_ai = $upload_dir_path_ai . $current_file_name;
$file_extension_ai = strtolower(pathinfo($current_file_name, PATHINFO_EXTENSION));
$file_key_ai = $socid . '_' . $periziaid_sanitized . '_' . $current_file_name;

$assigned_tags = [

];

// Action Handlers for this view
if ($action == 'update_ai_values' && !empty($_POST['ai_values']) && !empty($_POST['file_key'])) {
    if (!$user->rights->industria40->write && !$user->admin) {
        setEventMessages($langs->trans("NoPermissionToWrite"), null, 'errors');
    } else {
        $file_key_update = GETPOST('file_key', 'alpha');
        $values_to_update = $_POST['ai_values'];

        // Carica il file di riepilogo esistente
        $ai_description_summary_update = get_stored_ai_response($file_key_update . '_summary');

        if ($ai_description_summary_update !== false && !is_array($ai_description_summary_update)) {
            $updated_summary = '';
            $summary_lines = explode("\n", $ai_description_summary_update);

            // Aggiorna ogni riga con i nuovi valori
            foreach ($summary_lines as $line) {
                if (empty(trim($line))) {
                    $updated_summary .= $line . "\n";
                    continue;
                }

                $parts = explode(':', $line, 2);
                if (count($parts) == 2) {
                    $key = trim($parts[0]);
                    if (isset($values_to_update[$key])) {
                        $updated_summary .= $key . ': ' . $values_to_update[$key] . "\n";
                    } else {
                        $updated_summary .= $line . "\n";
                    }
                } else {
                    $updated_summary .= $line . "\n";
                }
            }

            // Salva il riepilogo aggiornato
            if (store_ai_response($file_key_update . '_summary', $updated_summary)) {
                setEventMessages($langs->trans("AIDescriptionUpdated"), null, 'mesgs');
                writeToLog("AI description summary updated for file: " . GETPOST('file_name_for_ai', 'alpha'), $file_key_update);
            } else {
                setEventMessages($langs->trans("ErrorUpdatingAIDescription"), null, 'errors');
                writeToLog("Error updating AI description summary for file: " . GETPOST('file_name_for_ai', 'alpha'), $file_key_update);
            }
        } else {
            setEventMessages($langs->trans("NoSummaryFileFound"), null, 'warnings');
        }
    }
    header('Location: ' . $form_action_url . '&view_mode=ai&file_name=' . urlencode(GETPOST('file_name_for_ai', 'alpha')) . '&message=aidescupdated');
    exit;
} elseif ($action == 'update_ai_json' && !empty($_POST['json_values']) && !empty($_POST['file_key'])) {
    if (!$user->rights->industria40->write && !$user->admin) {
        setEventMessages($langs->trans("NoPermissionToWrite"), null, 'errors');
    } else {
        $file_key_update = GETPOST('file_key', 'alpha');
        $json_values = $_POST['json_values'];

        // Carica il file JSON esistente
        $ai_description_json = get_stored_ai_response($file_key_update);

        if ($ai_description_json !== false && !is_array($ai_description_json)) {
            $json_data = json_decode($ai_description_json, true);

            if (json_last_error() === JSON_ERROR_NONE && !empty($json_data)) {
                // Aggiorna i valori nel JSON
                foreach ($json_values as $type => $fields) {
                    foreach ($fields as $path => $value) {
                        // Analizza il percorso e imposta il valore
                        update_json_value($json_data[$type], $path, $value);
                    }
                }

                // Salva il JSON aggiornato
                $updated_json = json_encode($json_data);
                if (store_ai_response($file_key_update, $updated_json)) {
                    setEventMessages($langs->trans("AIDescriptionUpdated"), null, 'mesgs');
                    writeToLog("AI JSON data updated for file: " . GETPOST('file_name_for_ai', 'alpha'), $file_key_update);

                    // Aggiorna anche il riepilogo
                    update_summary_from_json($file_key_update, $json_data);
                } else {
                    setEventMessages($langs->trans("ErrorUpdatingAIDescription"), null, 'errors');
                    writeToLog("Error updating AI JSON data for file: " . GETPOST('file_name_for_ai', 'alpha'), $file_key_update);
                }
            } else {
                setEventMessages($langs->trans("InvalidJSONFormat"), null, 'errors');
            }
        } else {
            setEventMessages($langs->trans("NoJSONFileFound"), null, 'warnings');
        }
    }
    header('Location: ' . $form_action_url . '&view_mode=ai&file_name=' . urlencode(GETPOST('file_name_for_ai', 'alpha')) . '&message=aidescupdated');
    exit;
} elseif ($action == 'get_description' && GETPOST('file_to_describe', 'alpha') == $current_file_name) {
    $openai_api_key = getenv('OPENAI_API_KEY');
    if (empty($openai_api_key)) $openai_api_key = !empty($conf->global->INDUSTRIA40_OPENAI_API_KEY) ? $conf->global->INDUSTRIA40_OPENAI_API_KEY : '';

    if (empty($openai_api_key)) {
        setEventMessages($langs->trans("OpenAIKeyNotConfigured"), null, 'warnings');
        writeToLog("AI description generation failed: OpenAI API key not configured", $file_key_ai);
    } else {
        $ai_response_desc = get_stored_ai_response($file_key_ai); // Check if already have one

        // Se esiste già una descrizione, elimina quella precedente prima di rigenerare
        if ($ai_response_desc !== false) {
            writeToLog("Deleting previous AI description before regeneration", $file_key_ai);
            delete_stored_ai_response($file_key_ai);
            delete_stored_ai_response($file_key_ai . '_summary'); // Elimina anche il riepilogo
            setEventMessages($langs->trans("PreviousDescriptionDeleted"), null, 'mesgs');
        }

        // Ora genera una nuova descrizione
        writeToLog("Starting AI description generation for: " . $current_file_name, $file_key_ai);
        if (in_array($file_extension_ai, array('jpg', 'jpeg', 'png', 'gif')) && file_exists($file_full_path_ai)) {
            $image_data_desc = @file_get_contents($file_full_path_ai);
            if ($image_data_desc) {
                $base64_image_desc = base64_encode($image_data_desc);
                writeToLog("Image converted to base64 for OpenAI API", $file_key_ai);

                // Preparazione della richiesta OpenAI (corretta)
                $ch_desc = curl_init();
                curl_setopt($ch_desc, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
                curl_setopt($ch_desc, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_desc, CURLOPT_POST, true);
                curl_setopt($ch_desc, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $openai_api_key]);

                $prompt_text = 'Analizza questa immagine e estrai tutte le informazioni testuali e visive rilevanti. '
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
    "indirizzo_ip": "",
    "url": "",
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
}```';

                $request_data_desc = [
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $prompt_text
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => 'data:image/'.$file_extension_ai.';base64,'.$base64_image_desc
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'max_tokens' => 1000,
                    'response_format' => ['type' => 'json_object']
                ];

                curl_setopt($ch_desc, CURLOPT_POSTFIELDS, json_encode($request_data_desc));
                writeToLog("Sending request to OpenAI API", $file_key_ai);
                $response_api_desc = curl_exec($ch_desc);
                curl_close($ch_desc);

                if ($response_api_desc) {
                    writeToLog("Received response from OpenAI API", $file_key_ai);
                    $decoded_api_desc = json_decode($response_api_desc, true);
                    if (isset($decoded_api_desc['choices'][0]['message']['content'])) {
                        $description_text = $decoded_api_desc['choices'][0]['message']['content'];
                        // Verifichiamo che la risposta sia effettivamente in JSON valido
                        $json_response = json_decode($description_text, true);
                        if (json_last_error() === JSON_ERROR_NONE && !empty($json_response)) {
                            writeToLog("AI response received in valid JSON format", $file_key_ai);
                            // Determiniamo quale tipo di documento è stato identificato
                            $detected_type = '';
                            foreach (['fattura', 'preventivo', 'scheda', 'schermata', 'targhetta', 'foto'] as $type) {
                                if (isset($json_response[$type]) && !empty($json_response[$type])) {
                                    $detected_type = $type;
                                    break;
                                }
                            }
                            if (!empty($detected_type)) {
                                writeToLog("Documento identificato come: " . $detected_type, $file_key_ai);
                                // Salviamo sia il JSON completo che una versione formattata per la visualizzazione
                                store_ai_response($file_key_ai, $description_text);
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
                                        $summary .= "Tipo dato: " . ($doc['tipo_dato'] ?? 'N/D') . "\n";
                                        $summary .= "Timestamp: " . ($doc['timestamp'] ?? 'N/D') . "\n";
                                        $summary .= "Indirizzo IP: " . ($doc['indirizzo_ip'] ?? 'N/D') . "\n";
                                        $summary .= "URL: " . ($doc['url'] ?? 'N/D') . "\n";
                                        break;
                                    case 'foto':
                                        $doc = $json_response[$detected_type];
                                        $summary .= "Tipo: " . ($doc['tipo'] ?? 'N/D') . "\n";
                                        $summary .= "Contesto: " . ($doc['contesto'] ?? 'N/D') . "\n";
                                        $summary .= "Note: " . ($doc['annotazioni_visive'] ?? 'N/D') . "\n";
                                        break;
                                }
                                // Salviamo anche la versione di riepilogo per visualizzazione
                                store_ai_response($file_key_ai . '_summary', $summary);
                                setEventMessages($langs->trans("DescriptionGeneratedFor", $current_file_name), null, 'mesgs');
                            } else {
                                writeToLog("JSON ricevuto ma nessun tipo di documento identificato", $file_key_ai);
                                store_ai_response($file_key_ai, $description_text);
                                // Salviamo un riepilogo generico
                                $summary = "Tipo documento: PDF\n";
                                $summary .= "Nome file: " . $current_file_name . "\n";
                                $summary .= "Descrizione: Documento PDF caricato\n";
                                store_ai_response($file_key_ai . '_summary', $summary);
                                setEventMessages($langs->trans("DescriptionGeneratedFor", $current_file_name), null, 'mesgs');
                            }
                        } else {
                            // Se non è in formato JSON, salviamo come testo semplice
                            writeToLog("AI response not in JSON format, saving as plain text", $file_key_ai);
                            store_ai_response($file_key_ai, $description_text);
                            $summary = "Tipo documento: Sconosciuto\n";
                            $summary .= "Analisi testuale: Risposta ricevuta in formato non strutturato\n";
                            store_ai_response($file_key_ai . '_summary', $summary);
                            setEventMessages($langs->trans("DescriptionGeneratedFor", $current_file_name), null, 'mesgs');
                        }
                    } else {
                        writeToLog("Error parsing OpenAI API response: " . json_encode($decoded_api_desc), $file_key_ai);
                        setEventMessages($langs->trans("ErrorGeneratingDescription"), null, 'errors');
                    }
                } else {
                    writeToLog("No response from OpenAI API", $file_key_ai);
                    setEventMessages($langs->trans("ErrorAPINoResponse"), null, 'errors');
                }
            } else {
                writeToLog("Failed to read image file: " . $file_full_path_ai, $file_key_ai);
                setEventMessages($langs->trans("ErrorReadingFile", $current_file_name), null, 'errors');
            }
        } elseif ($file_extension_ai == 'pdf') {
            // Gestione particolare per i PDF (più semplice)
            writeToLog("Generating generic description for PDF file", $file_key_ai);
            $generic_desc = $langs->trans("GenericPDFDescription", $current_file_name);
            store_ai_response($file_key_ai, $generic_desc);

            // Crea un riepilogo semplice per il PDF
            $summary = "Tipo documento: PDF\n";
            $summary .= "Nome file: " . $current_file_name . "\n";
            $summary .= "Descrizione: Documento PDF caricato\n";
            store_ai_response($file_key_ai . '_summary', $summary);

            setEventMessages($langs->trans("DescriptionGeneratedFor", $current_file_name), null, 'mesgs');
        } else {
            writeToLog("File type not supported for AI description: " . $file_extension_ai, $file_key_ai);
            setEventMessages($langs->trans("FileTypeNotSupportedForDescription", $file_extension_ai), null, 'warnings');
        }
    }
    header('Location: ' . $form_action_url . '&view_mode=ai&file_name=' . urlencode($current_file_name) . '&message=descactiondone');
    exit;
} elseif ($action == 'force_ocr' && GETPOST('file_to_ocr', 'alpha') == $current_file_name) {
    if (!$user->rights->industria40->write && !$user->admin) {
        setEventMessages($langs->trans("NoPermissionToWrite"), null, 'errors');
    } elseif (in_array($file_extension_ai, array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'tiff', 'tif'))) {
        $ocr_text_force = perform_ocr_on_file($file_full_path_ai, $file_extension_ai);
        if (!empty($ocr_text_force)) {
            setEventMessages($langs->trans("OCROperationCompletedFor", $current_file_name), null, 'mesgs');
            // Optionally, trigger AI analysis right after OCR
            $ocr_analysis_force = analyze_ocr_content($ocr_text_force, $current_file_name, $file_extension_ai);
            if (!empty($ocr_analysis_force['suggested_tag'])) {
                // Auto-apply tag logic (simplified)
                $file_key_ocr_tag = $socid . '_' . $periziaid_sanitized . '_' . $current_file_name;
                $tags_dir_ocr = DOL_DATA_ROOT . '/industria40/tags';
                if (!is_dir($tags_dir_ocr)) dol_mkdir($tags_dir_ocr);
                $tags_file_ocr = $tags_dir_ocr . '/file_tags.json';
                $tags_data_ocr = [];
                if(file_exists($tags_file_ocr)) {
                    $content = @file_get_contents($tags_file_ocr);
                    if($content) $tags_data_ocr = json_decode($content, true);
                    if(!is_array($tags_data_ocr)) $tags_data_ocr = [];
                }
                $tags_data_ocr[$file_key_ocr_tag] = $ocr_analysis_force['suggested_tag'];
                @file_put_contents($tags_file_ocr, json_encode($tags_data_ocr));
                setEventMessages($langs->trans("SuggestedTagApplied", $ocr_analysis_force['suggested_tag']), null, 'mesgs');
            }
        } else {
            setEventMessages($langs->trans("ErrorPerformingOCR", $current_file_name), null, 'errors');
        }
    } else {
        setEventMessages($langs->trans("FileTypeNotSupportedForOCR", $file_extension_ai), null, 'warnings');
    }
    header('Location: ' . $form_action_url . '&view_mode=ai&file_name=' . urlencode($current_file_name) . '&message=ocractiondone');
    exit;
}

// Includi i file CSS/JS
print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/custom/industria40/css/file_manager.css">';
print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/custom/industria40/js/file_manager.js"></script>';
// Includi i nuovi file di CSS e JavaScript per la funzionalità di zoom
print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/custom/industria40/css/image_zoom.css">';
print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/custom/industria40/js/image_zoom.js"></script>';

// Aggiungi stili CSS specifici per la tabella AI
print '<style>
.ai-json-data table td.subtitle { font-weight: bold; padding-left: 10px; }
.ai-json-data table td.subitem { padding-left: 20px; }
.ai-json-data table td.titlefield,
.ai-summary table td.titlefield { font-weight: bold; width: 150px; }
/* ... other CSS rules ... */
</style>';

print '<div><a href="'.$form_action_url.'&view_mode=manage" class="button">&laquo; '.$langs->trans("BackToManageView").'</a></div>';
print '<h4>'.$langs->trans("AIInteractionForFile", dol_escape_htmltag($current_file_name)).'</h4>';

// Display file preview con la funzionalità di zoom
$file_path_relative_ai = 'documents/' . $socid . '/' . $periziaid_sanitized . '/' . $current_file_name;
$file_url_ai = DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&file=' . urlencode($file_path_relative_ai) . '&entity=' . $conf->entity;

if (in_array($file_extension_ai, array('jpg', 'jpeg', 'png', 'gif'))) {
    print '<div class="zoom-instructions">'.$langs->trans("ClickToZoom").'</div>';
    print '<div class="file-preview-ai zoomable-image" data-src="'.$file_url_ai.'"><img src="'.$file_url_ai.'" alt="'.dol_escape_htmltag($current_file_name).'" style="max-width:400px; max-height:300px; border:1px solid #ddd;"/></div>';
} elseif ($file_extension_ai == 'pdf') {
    // PDF preview (e.g., using an iframe or a thumbnail if available)
    print '<div class="file-preview-ai">';
    // Attempt to show PDF thumbnail if it exists (logic similar to manage_view)
    $thumbnail_rel_path = 'documents/' . $socid . '/' . $periziaid_sanitized . '/.thumbs/thumb_' . pathinfo($current_file_name, PATHINFO_FILENAME) . '.jpg';
    $thumbnail_abs_path = DOL_DATA_ROOT . '/industria40/' . $thumbnail_rel_path;
    $thumbnail_url = DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&file=' . urlencode($thumbnail_rel_path) . '&entity=' . $conf->entity;

    if (file_exists($thumbnail_abs_path)) {
        print '<img src="'.$thumbnail_url.'" alt="'.$langs->trans("PDFThumbnail").'" style="max-width:200px; border:1px solid #ddd;"/>';
    } else {
        print '<iframe src="'.$file_url_ai.'" style="width:100%; height:400px; border:1px solid #ddd;"></iframe>';
    }
    print '</div>';
} else {
    print '<div class="file-preview-ai"><a href="'.$file_url_ai.'" target="_blank">'.$langs->trans("ViewFile").': '.dol_escape_htmltag($current_file_name).'</a></div>';
}

// OCR Section
print '<div class="box">';
print '<p><strong>'.$langs->trans("OCRTextExtraction").'</strong></p>';
$ocr_text_content = load_ocr_text($file_full_path_ai);
if (!empty($ocr_text_content)) {
    print '<textarea readonly style="width:100%; height:150px; font-family:monospace; font-size:0.9em;">'.dol_escape_htmltag($ocr_text_content).'</textarea>';
} else {
    print '<p>'.$langs->trans("NoOCRTextFoundOrFileNotProcessable").'</p>';
    // Button to trigger OCR if not already done
    print '<form action="'.$form_action_url.'&view_mode=ai&file_name='.urlencode($current_file_name).'" method="POST">';
    print '<input type="hidden" name="action" value="force_ocr">';
    print '<input type="hidden" name="file_to_ocr" value="'.dol_escape_htmltag($current_file_name).'">';
    print '<button type="submit" class="button">'.$langs->trans("ForceOCRProcessing").'</button>';
    print '</form>';
}
print '</div>';

// AI Description Section
print '<div class="box">';
print '<p><strong>'.$langs->trans("AIDescription").'</strong></p>';

// DEBUG: Aggiungi console log e variabili visibili per debug
print '<script>console.log("Debug AIDescription: Starting AIDescription section");</script>';

$ai_description = get_stored_ai_response($file_key_ai); // Main key for description
$ai_description_summary = get_stored_ai_response($file_key_ai . '_summary');

// Verifica il percorso di archiviazione delle risposte AI
$ai_response_dir = DOL_DATA_ROOT . '/industria40/ai_responses';
$ai_response_file = $ai_response_dir . '/' . $file_key_ai . '.txt';
$ai_response_summary_file = $ai_response_dir . '/' . $file_key_ai . '_summary.txt';

// DEBUG: Dettagli avanzati sui file delle risposte AI
print '<div class="opacitymedium" style="font-size:0.8em;margin-bottom:10px;background:#f9f9f9;padding:5px;border-left:3px solid #666;">
    <strong>DEBUG INFO:</strong><br>
    file_key_ai: ' . dol_escape_htmltag($file_key_ai) . '<br>
    ai_description type: ' . gettype($ai_description) . '<br>
    ai_description empty: ' . (empty($ai_description) ? 'yes' : 'no') . '<br>
    ai_description_summary type: ' . gettype($ai_description_summary) . '<br>
    ai_description_summary empty: ' . (empty($ai_description_summary) ? 'yes' : 'no') . '<br>
    is_array(ai_description): ' . (is_array($ai_description) ? 'true' : 'false') . '<br>
    function_exists("get_stored_ai_response"): ' . (function_exists("get_stored_ai_response") ? 'yes' : 'no') . '<br>
    <hr>
    <strong>FILE INFO:</strong><br>
    ai_response_dir: ' . $ai_response_dir . '<br>
    ai_response_dir exists: ' . (file_exists($ai_response_dir) ? 'yes' : 'no') . '<br>
    ai_response_dir writable: ' . (is_writable($ai_response_dir) ? 'yes' : 'no') . '<br>
    ai_response_file: ' . $ai_response_file . '<br>
    ai_response_file exists: ' . (file_exists($ai_response_file) ? 'yes' : 'no') . '<br>
    ai_response_summary_file: ' . $ai_response_summary_file . '<br>
    ai_response_summary_file exists: ' . (file_exists($ai_response_summary_file) ? 'yes' : 'no') . '<br>
</div>';

// Aggiungi un test per verificare la funzionalità di base di salvataggio/recupero
if (!file_exists($ai_response_dir)) {
    @dol_mkdir($ai_response_dir);
    print '<div class="opacitymedium" style="font-size:0.8em;margin-bottom:10px;background:#eeffee;padding:5px;border-left:3px solid #5c5;">
        La directory delle risposte AI è stata creata: ' . $ai_response_dir . '
    </div>';
}

// Proviamo a risolvere il problema con la descrizione AI mancante
if ($ai_description === false && file_exists($ai_response_dir) && is_writable($ai_response_dir) && !file_exists($ai_response_file)) {
    // Aggiungi un form per forzare la generazione della descrizione AI quando mancante
    print '<div style="background:#ffe8e8; padding:10px; border-left:3px solid #d55; margin-bottom:15px;">
        <strong>Problema rilevato:</strong> La descrizione AI per questo file non è stata trovata nel percorso previsto.<br>
        Utilizza il pulsante "Genera descrizione AI" sotto per crearla.
    </div>';
}

// Controllo se esiste la descrizione, altrimenti suggerisco di generarla
if ($ai_description !== false && !is_array($ai_description)) { // Ensure it's not the analysis array
    // DEBUG: Console log del percorso
    print '<script>console.log("Debug AIDescription: Description exists path");</script>';

    // Se abbiamo un riepilogo, mostralo in una tabella editabile
    if ($ai_description_summary !== false && !is_array($ai_description_summary)) {
        // DEBUG: Console log del percorso
        print '<script>console.log("Debug AIDescription: Summary exists path");</script>';

        print '<div class="ai-summary">';
        print '<form action="' . $form_action_url . '&view_mode=ai&file_name=' . urlencode($current_file_name) . '" method="POST" id="summary_edit_form">';
        print '<input type="hidden" name="action" value="update_ai_values">';
        print '<input type="hidden" name="file_key" value="' . dol_escape_htmltag($file_key_ai) . '">';
        print '<input type="hidden" name="file_name_for_ai" value="' . dol_escape_htmltag($current_file_name) . '">';

        print '<table class="border" width="100%">';

        // Converti il riepilogo in un array di righe
        $summary_lines = explode("\n", $ai_description_summary);
        foreach ($summary_lines as $line) {
            if (empty(trim($line))) continue;

            // Separa la chiave dal valore (formato "Chiave: Valore")
            $parts = explode(':', $line, 2);
            if (count($parts) == 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                print '<tr>';
                print '<td class="titlefield width30">' . dol_escape_htmltag($key) . '</td>';
                print '<td><input type="text" class="flat width100" name="ai_values[' . dol_escape_htmltag($key) . ']" value="' . dol_escape_htmltag($value) . '"></td>';
                print '</tr>';
            } else {
                // Nel caso in cui il formato non sia "Chiave: Valore"
                print '<tr><td colspan="2">' . dol_escape_htmltag($line) . '</td></tr>';
            }
        }

        print '</table>';
        print '<div class="center" style="margin-top: 10px;"><button type="submit" class="button">'.$langs->trans("SaveChanges").'</button></div>';
        print '</form>';
        print '</div>';

        // Aggiungi JavaScript per abilitare il rilevamento delle modifiche
        print '<script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            var form = document.getElementById("summary_edit_form");
            var inputs = form.querySelectorAll("input[type=text]");
            var hasChanges = false;

            inputs.forEach(function(input) {
                input.addEventListener("change", function() {
                    hasChanges = true;
                });
            });

            window.addEventListener("beforeunload", function(e) {
                if (hasChanges) {
                    var message = "'.$langs->trans("UnsavedChangesWarning").'";
                    e.returnValue = message;
                    return message;
                }
            });
        });
        </script>';
    }

    // Verifico se è in formato JSON
    $json_data = json_decode($ai_description, true);
    $json_error = json_last_error();

    // DEBUG: Console log dello stato JSON
    print '<script>
        console.log("Debug AIDescription: JSON status", {
            json_error: ' . $json_error . ',
            json_error_message: "' . addslashes(json_last_error_msg()) . '",
            is_empty: ' . (empty($json_data) ? 'true' : 'false') . '
        });
    </script>';

    if ($json_error === JSON_ERROR_NONE && !empty($json_data)) {
        // DEBUG: Console log del percorso
        print '<script>console.log("Debug AIDescription: Valid JSON path");</script>';

        // È un JSON valido, visualizza i dati in modo strutturato
        print '<div class="ai-json-data">';
        print '<form action="' . $form_action_url . '&view_mode=ai&file_name=' . urlencode($current_file_name) . '" method="POST" id="json_edit_form">';
        print '<input type="hidden" name="action" value="update_ai_json">';
        print '<input type="hidden" name="file_key" value="' . dol_escape_htmltag($file_key_ai) . '">';
        print '<input type="hidden" name="file_name_for_ai" value="' . dol_escape_htmltag($current_file_name) . '">';

        print '<table class="border" width="100%">';

        // Determiniamo quale tipo di documento è stato identificato
        $found = false;
        foreach (['fattura', 'preventivo', 'scheda', 'schermata', 'targhetta', 'foto'] as $type) {
            if (isset($json_data[$type]) && !empty($json_data[$type])) {
                print '<tr class="liste_titre"><th colspan="2">' . ucfirst($type) . '</th></tr>';

                // Passa il tipo di documento alla funzione per la generazione della tabella
                print_json_data_as_editable_table($json_data[$type], $type);

                $found = true;
                break;
            }
        }

        if (!$found) {
            print '<tr><td colspan="2"><pre>' . dol_escape_htmltag(json_encode($json_data, JSON_PRETTY_PRINT)) . '</pre></td></tr>';
        }

        print '</table>';
        print '<div class="center" style="margin-top: 10px;"><button type="submit" class="button">'.$langs->trans("SaveChanges").'</button></div>';
        print '</form>';
        print '</div>';

        // Aggiungi JavaScript per abilitare il rilevamento delle modifiche
        print '<script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            var form = document.getElementById("json_edit_form");
            var inputs = form.querySelectorAll("input[type=text]");
            var hasChanges = false;

            inputs.forEach(function(input) {
                input.addEventListener("change", function() {
                    hasChanges = true;
                });
            });

            window.addEventListener("beforeunload", function(e) {
                if (hasChanges) {
                    var message = "'.$langs->trans("UnsavedChangesWarning").'";
                    e.returnValue = message;
                    return message;
                }
            });
        });
        </script>';
    } else {
        // È un testo normale, visualizzalo in una tabella a una riga
        if (!$ai_description_summary) { // Mostra solo se non è già presente il riepilogo
            print '<table class="border" width="100%">';
            print '<tr>';
            print '<td>' . nl2br(dol_escape_htmltag($ai_description)) . '</td>';
            print '</tr>';
            print '</table>';
        }
    }
} else {
    // DEBUG: Console log del percorso
    print '<script>console.log("Debug AIDescription: No description path");</script>';

    // DEBUG: Mostra dettagli su perché non trova la descrizione
    print '<div class="opacitymedium" style="font-size:0.8em;margin-bottom:10px;background:#ffeeee;padding:5px;border-left:3px solid #c55;">
        <strong>DEBUG: NoAIDescriptionGenerated Reason:</strong><br>
        ai_description === false: ' . ($ai_description === false ? 'true' : 'false') . '<br>
        is_array(ai_description): ' . (is_array($ai_description) ? 'true' : 'false') . '
    </div>';

    print '<p>'.$langs->trans("NoAIDescriptionGenerated").'</p>';
}

// Assicuriamoci che il pulsante per generare o rigenerare sia sempre mostrato
// a prescindere dal fatto che ci sia già una descrizione o meno
if (in_array($file_extension_ai, array('jpg', 'jpeg', 'png', 'gif', 'pdf'))) {
    // DEBUG: Console log del percorso
    print '<script>console.log("Debug AIDescription: Generate/Regenerate button section");</script>';

    print '<form action="' . $form_action_url . '&view_mode=ai&file_name=' . urlencode($current_file_name) . '" method="POST" style="margin-top:5px;">';
    print '<input type="hidden" name="action" value="get_description">';
    print '<input type="hidden" name="file_to_describe" value="' . dol_escape_htmltag($current_file_name) . '">';

    // Modifica il testo del pulsante in base alla presenza di una descrizione AI
    if ($ai_description === false || is_array($ai_description)) {
        print '<script>console.log("Debug AIDescription: Generate button shown");</script>';
        print '<button type="submit" class="button">'.$langs->trans("GenerateAIDescription").'</button>';
    } else {
        print '<script>console.log("Debug AIDescription: Regenerate button shown");</script>';
        print '<button type="submit" class="button">'.$langs->trans("ReGenerateAIDescription").'</button>';
    }

    print '</form>';
}
print '</div>';

// AI Analysis (Name/Tag Suggestions)
if (!empty($ocr_text_content)) {
    print '<div class="box">';
    print '<p><strong>'.$langs->trans("AIAnalysisSuggestions").'</strong></p>';
    $ocr_analysis_ai = analyze_ocr_content($ocr_text_content, $current_file_name, $file_extension_ai);
    if (!empty($ocr_analysis_ai)) {
        if (!empty($ocr_analysis_ai['suggested_name']) && $ocr_analysis_ai['suggested_name'] != $current_file_name) {
            print '<p>'.$langs->trans("SuggestedFileName").': <strong>'.dol_escape_htmltag($ocr_analysis_ai['suggested_name']).'</strong>';
            // Form to apply suggested name (redirects to manage view's rename action)
            print '<form action="' . $form_action_url . '&view_mode=manage" method="POST" style="display:inline; margin-left:10px;">';
            print '<input type="hidden" name="action" value="rename_files">';
            print '<input type="hidden" name="rename_single_file" value="' . dol_escape_htmltag(rawurlencode($current_file_name)) . '">'; // original name
            print '<input type="hidden" name="new_name_' . dol_escape_htmltag(rawurlencode($current_file_name)) . '" value="' . dol_escape_htmltag($ocr_analysis_ai['suggested_name']) . '">'; // new name
            print '<button type="submit" class="buttonextrasmall">'.$langs->trans("ApplySuggestedName").'</button>';
            print '</form>';
            print '</p>';
        }
        if (!empty($ocr_analysis_ai['suggested_tag'])) {
            print '<p>'.$langs->trans("SuggestedTag").': <strong>'.dol_escape_htmltag(isset($available_tags[$ocr_analysis_ai['suggested_tag']]) ? $langs->trans($available_tags[$ocr_analysis_ai['suggested_tag']]) : $ocr_analysis_ai['suggested_tag']).'</strong>';
            // Form to apply suggested tag (redirects to manage view's set_tag action)
            print '<form action="' . $form_action_url . '&view_mode=manage" method="POST" style="display:inline; margin-left:10px;">';
            print '<input type="hidden" name="action" value="set_tag">';
            print '<input type="hidden" name="file_name_for_tag" value="' . dol_escape_htmltag($current_file_name) . '">';
            print '<input type="hidden" name="file_tag" value="' . dol_escape_htmltag($ocr_analysis_ai['suggested_tag']) . '">';
            print '<button type="submit" class="buttonextrasmall">'.$langs->trans("ApplySuggestedTag").'</button>';
            print '</form>';
            print '</p>';
        }
        if (empty($ocr_analysis_ai['suggested_name']) && empty($ocr_analysis_ai['suggested_tag'])) {
            print '<p>'.$langs->trans("NoAISuggestionsAvailable").'</p>';
        }
    } else {
        print '<p>'.$langs->trans("AIAnalysisNotPerformedOrFailed").'</p>';
    }
    print '</div>';
}
