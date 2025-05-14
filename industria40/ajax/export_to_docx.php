<?php
// Inizializza l'ambiente Dolibarr
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/industria40/core/init.inc.php';

// Verifica che PhpWord sia disponibile
if (!file_exists(DOL_DOCUMENT_ROOT . '/includes/phpoffice/phpword/vendor/autoload.php')) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => 'PhpWord library not found']));
}
require_once DOL_DOCUMENT_ROOT . '/includes/phpoffice/phpword/vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;

// Verifica dell'accesso
if (!$user->rights->industria40->read) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Accesso negato']));
}

// Recupera i dati inviati
$postdata = json_decode(file_get_contents('php://input'), true);
if (empty($postdata) || empty($postdata['socid']) || empty($postdata['periziaid']) || empty($postdata['data'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Dati mancanti']));
}

$socid = intval($postdata['socid']);
$periziaid = dol_sanitizeFileName($postdata['periziaid']);
$mapping = $postdata['data']['mapping'] ?? [];

if (empty($mapping)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Nessun mapping definito']));
}

// Directory per salvare le risposte AI
$ai_response_dir = DOL_DATA_ROOT . '/industria40/ai_responses';
$field_values = [];

// Recupera i tag AI analizzando tutti i file delle descrizioni AI
$ai_tags = [];
$files = scandir($ai_response_dir);
foreach ($files as $file) {
    if ($file == '.' || $file == '..' || strpos($file, '_summary') !== false) {
        continue;
    }

    if (preg_match('/^' . $socid . '_' . $periziaid . '_(.+)\.txt$/', $file, $matches)) {
        $filename = $matches[1];
        $file_path = $ai_response_dir . '/' . $file;

        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);

            // Estrai il tipo di documento dalla risposta JSON
            $json_data = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
                foreach ($json_data as $key => $value) {
                    $ai_tags[$key][] = $filename;
                    break;  // Prendi solo la prima chiave
                }
            }
        }
    }
}

// Elabora ogni campo del mapping
foreach ($mapping as $field => $source) {
    // Verifica se il source è un tag dal database
    if (strpos($source, 'tag_') === 0) {
        // È un tag dal DB - recupera le informazioni dell'etichetta
        $tag_id = substr($source, 4); // Rimuovi il prefisso 'tag_'

        // Recupera i dettagli del tag dal database
        $sql = "SELECT label, description FROM " . MAIN_DB_PREFIX . "categorie WHERE rowid = " . ((int) $tag_id);
        $resql = $db->query($sql);

        if ($resql && $obj = $db->fetch_object($resql)) {
            $field_values[$field] = $obj->label . (!empty($obj->description) ? " - " . $obj->description : "");
        } else {
            $field_values[$field] = "Tag non trovato (ID: " . $tag_id . ")";
        }
    }
    // Verifica se il source è un tag estratto dall'AI
    else if (strpos($source, 'aitag_') === 0) {
        // È un tag AI - recupera le informazioni associate
        $tag_name = substr($source, 6); // Rimuovi il prefisso 'aitag_'

        if (isset($ai_tags[$tag_name])) {
            // Componi una descrizione dei file che hanno questo tag
            $files_with_tag = $ai_tags[$tag_name];
            $field_values[$field] = "Tipo documento: " . ucfirst($tag_name) . "\n";
            $field_values[$field] .= "Presente in " . count($files_with_tag) . " file:\n";

            foreach ($files_with_tag as $tagged_file) {
                $field_values[$field] .= "- " . $tagged_file . "\n";
            }
        } else {
            $field_values[$field] = "Tag AI non trovato: " . $tag_name;
        }
    }
    // Verifica se il source è una descrizione AI di file
    else if (strpos($source, 'file_') === 0) {
        // È un file - recupera la descrizione AI
        $filename = substr($source, 5); // Rimuovi il prefisso 'file_'
        $response_file = $ai_response_dir . '/' . $socid . '_' . $periziaid . '_' . $filename . '.txt';

        if (file_exists($response_file)) {
            $content = file_get_contents($response_file);

            // Prova a formattare il JSON per una migliore leggibilità
            $json_data = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $formatted_content = "";
                foreach ($json_data as $type => $data) {
                    $formatted_content .= "Tipo documento: " . ucfirst($type) . "\n\n";
                    foreach ($data as $key => $value) {
                        if (is_array($value)) {
                            $formatted_content .= ucfirst($key) . ":\n";
                            foreach ($value as $sub_item) {
                                if (is_array($sub_item)) {
                                    foreach ($sub_item as $sub_key => $sub_value) {
                                        $formatted_content .= "  - " . ucfirst($sub_key) . ": " . $sub_value . "\n";
                                    }
                                    $formatted_content .= "\n";
                                } else {
                                    $formatted_content .= "  - " . $sub_item . "\n";
                                }
                            }
                        } else {
                            $formatted_content .= ucfirst($key) . ": " . $value . "\n";
                        }
                    }
                }
                $field_values[$field] = $formatted_content;
            } else {
                // Se non è JSON, usa il testo grezzo
                $field_values[$field] = $content;
            }
        } else {
            $field_values[$field] = "Descrizione AI non trovata per: " . $filename;
        }
    }
    else {
        // Source non riconosciuto
        $field_values[$field] = "Fonte dati non valida: " . $source;
    }
}

// Percorso al template DOCX
$template_dir = DOL_DOCUMENT_ROOT . '/custom/industria40/templates';
$template_file = $template_dir . '/perizia_template.docx';

// Se il template non esiste, crea un documento base
if (!file_exists($template_file)) {
    // Crea un nuovo documento
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();

    // Aggiungi titolo
    $section->addText('Report Perizia', ['bold' => true, 'size' => 18], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

    // Aggiungi i campi dal mapping
    foreach ($field_values as $field => $content) {
        $section->addText($field, ['bold' => true, 'size' => 14]);
        $section->addText($content, ['size' => 12]);
        $section->addTextBreak(1);
    }

    // Salva come file temporaneo
    $temp_file = sys_get_temp_dir() . '/perizia_' . $socid . '_' . $periziaid . '_' . time() . '.docx';
    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save($temp_file);

    // Invia il file
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="report_perizia_' . $periziaid . '.docx"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($temp_file));
    readfile($temp_file);
    unlink($temp_file);
    exit;
} else {
    // Usa il template esistente
    try {
        $templateProcessor = new TemplateProcessor($template_file);

        // Sostituisci i valori nel template
        foreach ($field_values as $field => $content) {
            $templateProcessor->setValue($field, $content);
        }

        // Aggiungi info dell'azienda
        $soc = new Societe($db);
        if ($soc->fetch($socid) > 0) {
            $templateProcessor->setValue('NomeAzienda', $soc->name);
            $templateProcessor->setValue('IndirizzoAzienda', $soc->address);
            $templateProcessor->setValue('CittaAzienda', $soc->zip . ' ' . $soc->town);
            $templateProcessor->setValue('PIVA', $soc->idprof1);
        }

        // Salva il documento in file temporaneo
        $temp_file = sys_get_temp_dir() . '/perizia_' . $socid . '_' . $periziaid . '_' . time() . '.docx';
        $templateProcessor->saveAs($temp_file);

        // Invia il file
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="report_perizia_' . $periziaid . '.docx"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($temp_file));
        readfile($temp_file);
        unlink($temp_file);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        exit(json_encode(['success' => false, 'error' => 'Errore elaborazione template: ' . $e->getMessage()]));
    }
}
?>