<?php
// This view is included by industria40index.php
// It expects $socid, $periziaid, $langs, etc. to be available.

// Recuperiamo le descrizioni AI disponibili per questa società e perizia
$ai_descriptions = array();
$ai_tags = array(); // Array per memorizzare i tag estratti dalle risposte AI
$ai_response_dir = DOL_DATA_ROOT . '/industria40/ai_responses';
$periziaid_sanitized = dol_sanitizeFileName($periziaid);

// Directory dei file caricati
$upload_dir = DOL_DATA_ROOT . '/industria40/' . $socid . '/' . $periziaid_sanitized;
$files_in_dir = array();

// Recupera tutti i file dalla directory
if (is_dir($upload_dir)) {
    $dir_files = scandir($upload_dir);
    foreach ($dir_files as $file) {
        if ($file != '.' && $file != '..' && !is_dir($upload_dir . '/' . $file)) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $files_in_dir[$file] = array(
                'name' => $file,
                'extension' => $extension,
                'icon' => get_file_icon($extension)
            );
        }
    }
}

// Recupera le descrizioni AI per i file
if (is_dir($ai_response_dir)) {
    // Ottiene tutti i file di descrizione AI
    $files = scandir($ai_response_dir);
    foreach ($files as $file) {
        // Salta directory e file summary
        if ($file == '.' || $file == '..' || strpos($file, '_summary') !== false) {
            continue;
        }

        // Verifica se è un file .txt o .json
        if (!preg_match('/\.(txt|json)$/', $file, $matches)) {
            continue;
        }

        $file_extension = $matches[1]; // txt o json

        // Schema di pattern per estrarre il nome del file dal nome del file di risposta AI
        $pattern = '/^' . $socid . '_' . $periziaid_sanitized . '_(.+)\.' . $file_extension . '$/';

        // Estrai l'identificativo del file
        if (preg_match($pattern, $file, $matches)) {
            $filename = $matches[1];
            $file_path = $ai_response_dir . '/' . $file;

            if (file_exists($file_path)) {
                $content = file_get_contents($file_path);
                $ai_descriptions[$filename] = $content;

                // Estrai il tipo di documento dalla risposta JSON
                $json_data = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
                    // Trova la prima chiave nell'oggetto JSON come tipo di documento
                    $doc_type = null;
                    foreach ($json_data as $key => $value) {
                        $doc_type = $key;
                        break;  // Prendi solo la prima chiave
                    }

                    if ($doc_type) {
                        $ai_tags[$filename] = $doc_type;
                        dol_syslog("Estratto tag '$doc_type' dal file $filename", LOG_DEBUG);
                    }
                } else {
                    dol_syslog("Impossibile estrarre JSON da $file", LOG_DEBUG);
                }
            }
        }
    }
}

// Recupera i tag/etichette disponibili per questa società/perizia
$available_tags = array();
$sql = "SELECT DISTINCT t.rowid, t.label, t.description
        FROM " . MAIN_DB_PREFIX . "categorie as t
        LEFT JOIN " . MAIN_DB_PREFIX . "categorie_file as cf ON t.rowid = cf.fk_categorie
        LEFT JOIN " . MAIN_DB_PREFIX . "ecm_files as f ON cf.fk_file = f.rowid
        WHERE f.src_object_type = 'industria40_perizia'
        AND f.src_object_id = " . ((int) $periziaid) . "
        ORDER BY t.label";

$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $available_tags[$obj->rowid] = array(
            'label' => $obj->label,
            'description' => $obj->description
        );
    }
}

// Funzione per cache busting
function getCacheBuster() {
    // Utilizza il timestamp dell'ultima modifica del file principale JS come versione
    $jsFile = DOL_DOCUMENT_ROOT . '/custom/industria40/js/drawflow_manager.js';
    $cssFile = DOL_DOCUMENT_ROOT . '/custom/industria40/css/drawflow_style.css';

    // Ottieni il timestamp del file più recente tra JS e CSS
    $jsTimestamp = file_exists($jsFile) ? filemtime($jsFile) : time();
    $cssTimestamp = file_exists($cssFile) ? filemtime($cssFile) : time();

    // Usa il più recente tra i due
    return '?v=' . max($jsTimestamp, $cssTimestamp);
}

$cacheBuster = getCacheBuster();

// Aggiungi file CSS e JS necessari per Drawflow con cache busting
print '<link rel="stylesheet" id="drawflow-lib-css" href="'.DOL_URL_ROOT.'/custom/industria40/js/drawflow/drawflow.min.css'.$cacheBuster.'" />';
print '<link rel="stylesheet" id="drawflow-main-styles" href="'.DOL_URL_ROOT.'/custom/industria40/css/drawflow_style.css'.$cacheBuster.'" />';
print '<script id="drawflow-lib-js" src="'.DOL_URL_ROOT.'/custom/industria40/js/drawflow/drawflow.min.js'.$cacheBuster.'"></script>';
print '<script id="drawflow-manager-js" src="'.DOL_URL_ROOT.'/custom/industria40/js/drawflow_manager.js'.$cacheBuster.'"></script>';

print '<div class="box">';
print '<h4>'.$langs->trans("DrawflowMappingSystem").'</h4>';
print '<p>'.$langs->trans("DrawflowDescription").'</p>';

// Filtri per categorie di nodi
print '<div class="drawflow-toolbar">';
print '<div class="node-filters">';
print '<span>'.$langs->trans("FilterNodes").': </span>';
print '<div class="node-category file-nodes active" data-type="file">'.$langs->trans("Files").'</div> ';
print '<div class="node-category ai-files active" data-type="ai">'.$langs->trans("AIDescriptions").'</div> ';
print '<div class="node-category tag-nodes active" data-type="tag">'.$langs->trans("Tags").'</div> ';
print '<div class="node-category ai-tags active" data-type="aitag">'.$langs->trans("AITags").'</div> ';
print '<div class="node-category docx-fields active" data-type="docx">'.$langs->trans("DocxFields").'</div> ';
print '</div>';
print '</div>';

// Toolbar per il controllo del diagramma
print '<div class="drawflow-toolbar">';
print '<button id="save-mapping" class="butAction">'.$langs->trans("SaveMapping").'</button> ';
print '<button id="load-mapping" class="butActionDelete">'.$langs->trans("LoadMapping").'</button> ';
print '<button id="export-docx" class="butAction">'.$langs->trans("ExportToDocx").'</button>';
print '</div>';

// Container per Drawflow
print '<div id="drawflow"></div>';

// Template placeholder per i nodi
print '<div style="display:none;">';
// Template per nodi file
print '<div id="node-file-template" class="drawflow-node file-node">';
print '<div class="node-title"><strong>{{filename}}</strong></div>';
print '<div class="file-info">{{fileinfo}}</div>';
print '<div class="node-output" draggable="true">Output</div>';
print '</div>';

// Template per nodi sorgente (descrizioni AI)
print '<div id="node-ai-template" class="drawflow-node ai-source">';
print '<div class="node-title"><strong>{{title}}</strong></div>';
print '<div class="ai-content">{{content}}</div>';
print '<div class="node-output" draggable="true">Output</div>';
print '</div>';

// Template per nodi tag (etichette)
print '<div id="node-tag-template" class="drawflow-node tag-node">';
print '<div class="node-title"><strong>Tag: {{label}}</strong></div>';
print '<div class="tag-content">{{description}}</div>';
print '<div class="node-output" draggable="true">Output</div>';
print '</div>';

// Template per nodi tag AI (estratti automaticamente)
print '<div id="node-aitag-template" class="drawflow-node ai-tag-node">';
print '<div class="node-title"><strong>AI Tag: {{label}}</strong></div>';
print '<div class="tag-content">Tipo documento estratto automaticamente dalla risposta AI</div>';
print '<div class="node-output" draggable="true">Output</div>';
print '</div>';

// Template per nodi target (campi DOCX)
print '<div id="node-docx-template" class="drawflow-node docx-target">';
print '<div class="node-title"><strong>{{field}}</strong></div>';
print '<div class="node-input">Input</div>';
print '</div>';
print '</div>'; // Fine div hidden

// Sezione dati JSON per inizializzazione JavaScript
print '<script type="text/javascript">';
print 'window.drawflowConfig = ' . json_encode([
    'ai_descriptions' => $ai_descriptions,
    'available_tags' => $available_tags,
    'ai_tags' => $ai_tags,
    'files_in_dir' => $files_in_dir,
    'docx_fields' => isset($docx_fields) && is_array($docx_fields) ? $docx_fields : [
        "RagioneSociale", "Indirizzo", "PartitaIVA", "DescrizioneProdotto",
        "CostoTotale", "DataAcquisto", "TipologiaInvestimento"
    ],
    'socid' => $socid,
    'periziaid_sanitized' => $periziaid_sanitized,
    'baseUrl' => DOL_URL_ROOT,
    'saveUrl' => DOL_URL_ROOT . '/custom/industria40/ajax/save_drawflow_mapping.php',
    'loadUrl' => DOL_URL_ROOT . '/custom/industria40/ajax/load_drawflow_mapping.php',
    'exportUrl' => DOL_URL_ROOT . '/custom/industria40/ajax/export_to_docx.php'
]) . ';';
print '</script>';

// Carica i file necessari per DrawFlow con cache busting
$timestamp = time();

// Controlla se gli script sono già stati caricati
$scriptsAlreadyLoaded = false;
?>

<script>
// Verifica se gli script sono già stati caricati
var scriptsAlreadyLoaded = (
    document.querySelector('script[src*="drawflow.min.js"]') !== null &&
    document.querySelector('script[src*="drawflow_manager.js"]') !== null
);
</script>



<!-- Container per l'editor DrawFlow -->


<script>
// Definisci la configurazione globale per DrawflowManager
window.drawflowConfig = {
    socid: <?php echo $socid; ?>,
    periziaid_sanitized: "<?php echo $periziaid_sanitized; ?>",
    baseUrl: "<?php echo DOL_URL_ROOT; ?>",
    files_in_dir: <?php echo json_encode($files_in_dir); ?>,
    docx_fields: <?php echo json_encode(isset($docx_fields) && is_array($docx_fields) ? $docx_fields : ["RagioneSociale", "Indirizzo", "PartitaIVA", "DescrizioneProdotto", "CostoTotale", "DataAcquisto", "TipologiaInvestimento"]); ?>,
    ai_tags: <?php echo json_encode($ai_tags); ?>,
    ai_descriptions: <?php echo json_encode($ai_descriptions); ?>,
    available_tags: <?php echo json_encode($available_tags); ?>,
    saveUrl: "<?php echo $saveUrl; ?>",
    loadUrl: "<?php echo $loadUrl; ?>",
    exportUrl: "<?php echo $exportUrl; ?>"
};

// Inizializza DrawflowManager quando è pronto
if (window.DrawflowManager && window.DrawflowManager.isReady) {
    console.log("DrawflowManager già pronto, inizializzazione immediata");
    window.DrawflowManager.init(window.drawflowConfig);
}
// In caso contrario, verrà inizializzato dalla funzione onDrawflowManagerReady definita in industria40index.php
</script>
<?php
/**
 * Funzione helper per ottenere l'icona del file in base all'estensione
 *
 * @param string $extension Estensione del file
 * @return string Classe CSS dell'icona
 */
function get_file_icon($extension) {
    $iconMap = array(
        "pdf" => "fa fa-file-pdf",
        "doc" => "fa fa-file-word",
        "docx" => "fa fa-file-word",
        "xls" => "fa fa-file-excel",
        "xlsx" => "fa fa-file-excel",
        "ppt" => "fa fa-file-powerpoint",
        "pptx" => "fa fa-file-powerpoint",
        "jpg" => "fa fa-file-image",
        "jpeg" => "fa fa-file-image",
        "png" => "fa fa-file-image",
        "gif" => "fa fa-file-image",
        "txt" => "fa fa-file-text",
        "zip" => "fa fa-file-archive",
        "rar" => "fa fa-file-archive"
    );

    return isset($iconMap[$extension]) ? $iconMap[$extension] : "fa fa-file";
}
?>
