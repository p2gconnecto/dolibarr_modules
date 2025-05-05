<?php
define('NOSCANPOSTFORINJECTION', ['context_ai',
'context_web',
'context_ocr',
'azienda_nome',
'azienda_ragione_sociale',
'data_compilazione',
'oggetto_valutazione',
'oggetto_valutazione_ai',
'data_inizio_intervento',
'data_fine_intervento',
'fatturato_annuo',
'n_dipendenti',
'fk_projet',
'fk_societe',
'azienda_nome',
'ex_ante_or_ex_post',
'digital_workplace_numero',
'digital_workplace',
'digital_comm_engag',
'cloud_comp_app_server',
'cloud_comp_db_server',
'cloud_comp_web_server',
'cloud_comp_db_bkup_server',
'cyber_security',
'approccio_metodologico',
'prompt_approccio_metodologico',
'prompt_Settore_industriale',
'settore_industriale',
'dimensioni_ambizioni',
'prompt_Dimensioni_e_ambizioni',
'caratteristiche_prodotti',
'prompt_Caratteristiche_prodotti',
'maturita_digitale',
'prompt_Maturità_digitale',
'obiettivi_azienda',
'prompt_Obiettivi_azienda',
'prompt_Capacità_investimento',
'capacita_investimento',
'capacita_gestionale',
'prompt_Capacità_gestionale',
'software_produttivita',
'prompt_Software_produttività',
'software_altri',
'prompt_Software_altri',
'software_comunicazione',
'prompt_Software_comunicazione',
'software_archiviazione',
'prompt_Software_archiviazione',
'software_automazione',
'prompt_Software_automazione',
'piattaforme_condivisione',
'prompt_Piattaforme_condivisione',
'software_firma',
'prompt_Software_firma',
'piattaforma_dig_commerce',
'prompt_Piattaforma_dig_commerce',
'piattaforma_campagne',
'prompt_Piattaforma_campagne',
'piattaforma_dig_exper',
'prompt_Piattaforma_dig_exper',
'piattaforma_analytics',
'prompt_Piattaforma_analytics',
'piattaforma_mobile',
'prompt_Piattaforma_mobile',
'piattaforma_integrazione',
'prompt_Piattaforma_integrazione',
'piattaforma_logistica',
'prompt_Piattaforma_logistica',
'presenza_cloud',
'prompt_Presenza_cloud',
'applicazioni_client',
'prompt_Applicazioni_client',
'servizi_calcolo',
'prompt_Servizi_calcolo',
'servizi_db_e_archiv',
'prompt_Servizi_DB_e_archiv',
'servizi_network',
'prompt_Servizi_network',
'servizi_identita_sec',
'prompt_Servizi_identità_sec',
'servizi_devel_test',
'prompt_Servizi_devel_test',
'sistemi_accessi',
'prompt_Sistemi_accessi',
'sistemi_network_secur',
'prompt_Sistemi_network_secur',
'sistemi_endpoint_secur',
'prompt_Sistemi_endpoint_secur',
'sistemi_data_secur',
'prompt_Sistemi_data_secur',
'sistemi_vulnerab_admin',
'prompt_Sistemi_vulnerab_admin',
'sistemi_secur_analytics',
'prompt_Sistemi_secur_analytics',
'sistemi_applic_security',
'prompt_Sistemi_applic_security',
'sistemi_risk_compl_admin',
'prompt_Sistemi_risk_compl_admin',
'sintesi_d_workplace',
'prompt_Sintesi_d_workplace',
'sintesi_d_comm_engagem',
'prompt_Sintesi_d_comm_engagem',
'sintesi_cc_app_server',
'prompt_Sintesi_cc_app_server',
'sintesi_cc_db_server',
'prompt_Sintesi_cc_db_server',
'sintesi_cc_web_server',
'prompt_Sintesi_cc_web_server',
'sintesi_cc_db_backup',
'prompt_Sintesi_cc_db_backup',
'sintesi_cyber_security',
'prompt_Sintesi_cyber_security',
'impatto_stimato',
'prompt_Impatto_stimato',
'rischi_iniziative',
'prompt_Rischi_iniziative',
'criteri_chiave',
'prompt_Criteri_chiave',
'priorita',
'prompt_Priorità',
'tempo_completamento',
'prompt_Tempo_completamento',
'cronoprogramma',
'prompt_Cronoprogramma',
'ex_p_sintesi_d_workplace',
'ex_p_sintesi_d_comm_engagem',
'ex_p_sintesi_cc_app_server',
'ex_p_sintesi_cc_db_server',
'ex_p_sintesi_cc_web_server',
'ex_p_sintesi_cc_db_backup',
'ex_p_sintesi_cyber_security',
'ex_p_v_f_d_workplace',
'ex_p_v_f_d_comm_engagem',
'ex_p_v_f_cloud_computing',
'ex_p_v_f_cyber_security',
'ex_p_giudizio_finale',
'oggetto_valutazione']);




error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/diagnosi_digitale/class/diagnosi_digitale.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/diagnosi_digitale/core/modules/pdf_diagnosi_digitale.modules.php';
require_once DOL_DOCUMENT_ROOT.'/ai/class/ai.class.php'; // Include il modulo AI
require_once DOL_DOCUMENT_ROOT.'/custom/diagnosi_digitale/class/ai.class.php'; // Include la classe AI
require_once DOL_DOCUMENT_ROOT.'/custom/diagnosi_digitale/class/ocr.class.php';

$langs->load("diagnosi_digitale@diagnosi_digitale");

// Configurazione del modulo Diagnosi Digitale
if (!isset($conf->diagnosi_digitale)) {
    $conf->diagnosi_digitale = new stdClass();
}
if (empty($conf->diagnosi_digitale->dir_output)) {
    $conf->diagnosi_digitale->dir_output = DOL_DATA_ROOT . '/diagnosi_digitale';
}

// Verifica che la directory di output esista
if (!is_dir($conf->diagnosi_digitale->dir_output)) {
    if (!mkdir($conf->diagnosi_digitale->dir_output, 0755, true)) {
        dol_syslog("Error: Failed to create directory " . $conf->diagnosi_digitale->dir_output, LOG_ERR);
        print '<div class="error">Error: Failed to create output directory.</div>';
        exit;
    }
}

// Recupera i commenti delle colonne dalla tabella llx_diagnosi_digitale
$columnComments = [];
$sql = "SHOW FULL COLUMNS FROM " . MAIN_DB_PREFIX . "diagnosi_digitale";
$res = $db->query($sql);
if ($res) {
    while ($row = $db->fetch_object($res)) {
        if (!empty($row->Comment)) {
            $columnComments[$row->Field] = $row->Comment;
        }
    }
} else {
    dol_syslog("Error fetching column comments: " . $db->lasterror(), LOG_ERR);
}

$id = GETPOST('id', 'int');
//dol_syslog(__METHOD__ . " YDebug: ID received: " . $id, LOG_DEBUG);
$action = GETPOST('action', 'alpha');
//$action = in_array($action, ['add', 'edit', 'delete', 'validate', 'generate']) ? $action : '';
//dol_syslog(__METHOD__ . " YDebug: Action received: " . $action, LOG_DEBUG);
$project_id = GETPOST('project_id', 'int');
//dol_syslog(__METHOD__ . " YDebug: Project ID received: " . $project_id, LOG_DEBUG);
// print in log the full payload
dol_syslog(__METHOD__ . " YDebug: Full payload: " . var_export($_POST, true), LOG_DEBUG);

$object = new DiagnosiDigitale($db);

//dol_syslog(__METHOD__ . " Debug: Data received via POST: " . var_export($_POST, true), LOG_DEBUG);
dol_syslog(__METHOD__ . " Debug: Data received via GET: " . var_export($_GET, true), LOG_DEBUG);

if ($project_id > 0) {
    $project = new Project($db);
    if ($project->fetch($project_id) > 0) {
        $societe_id = $project->socid;
        $progetto_ref = $project->ref;
        $progetto_title = $project->title;
        $progetto_description = $project->description;

        // Populate project info
        $project_info = $progetto_ref . ' - ' . $progetto_title . ' - ' . $progetto_description;

        if ($societe_id > 0) {
            $societe = new Societe($db);
            if ($societe->fetch($societe_id) > 0) {
                $societa_nome = $societe->nom;
            }
        }
    }
}

$fk_projet = (int) GETPOST('fk_projet', 'int') ?: (int) $project_id;
$fk_societe = (int) GETPOST('fk_societe', 'int');

if (!$fk_societe && $fk_projet > 0) {
    $project = new Project($db);
    if ($project->fetch($fk_projet) > 0 && $project->socid > 0) {
        $fk_societe = $project->socid;
    }
}

if ($fk_societe > 0 && $fk_projet > 0) {
    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . $object->table_element . "
            WHERE fk_societe = $fk_societe AND fk_projet = $fk_projet LIMIT 1";
    $res = $db->query($sql);
    if ($res && $db->num_rows($res)) {
        $obj = $db->fetch_object($res);
        $object->fetch($obj->rowid);
        $object->id = $obj->rowid; // <- CRUCIALE!
        $object->rowid = $obj->rowid;
    } else {
        $object->fk_societe = $fk_societe;
        $object->fk_projet = $fk_projet;
    }
}


if ($id > 0) {
    $object->fetch($id);
}

if ($object->id > 0) {
    $object->fetch($object->id);
} elseif ($fk_societe > 0) {
    $object->fetchBySociete($fk_societe);
}

// Precompilazione se da progetto
if (empty($object->id) && $project_id > 0) {
    $project = new Project($db);
    if ($project->fetch($project_id) > 0 && $project->socid > 0) {
        $company = new Societe($db);
        if ($company->fetch($project->socid) > 0) {
            $object->fk_projet = $project_id;
            $object->fk_societe = $company->id;
            $object->azienda_nome = $company->nom;
            $object->azienda_ragione_sociale = $company->name_alias ?: $company->nom;
            $object->data_compilazione = dol_now();
            $object->address = $company->address;
            $object->zip = $company->zip;
            $object->town = $company->town;

        }
    }
}


if (empty($object->ref)) {
    //$object->ref = 'DD-' . dol_print_date(dol_now(), '%Y%m%d%H%M%S');
    //name after object->nom
    $object->ref =  dol_sanitizeFileName($object->azienda_nome) . '-' . dol_print_date(dol_now(), '%Y%m%d%H%M');
}

$title = $langs->trans("DiagnosiDigitale");
$form = new Form($db);
$formfile = new FormFile($db);

llxHeader('', $title);
print load_fiche_titre($title);

// Display errors and messages
displayErrorsAndMessages();

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
//print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?project_id=' . $object->fk_projet. '">';
print '<input type="hidden" name="token" value="'.newToken().'">';

// Add project_id as a hidden input field
if (!empty($project_id)) {
    print '<input type="hidden" name="project_id" value="'.$project_id.'">';
}

// Add other hidden fields if necessary
if ($object->id > 0) {
    print '<input type="hidden" name="id" value="'.$object->id.'">';
}
if ($object->fk_projet > 0) {
    print '<input type="hidden" name="fk_projet" value="'.$object->fk_projet.'">';
}
if ($object->fk_societe > 0) {
    print '<input type="hidden" name="fk_societe" value="'.$object->fk_societe.'">';
}
// Scan documents for "visura" and load the entire text as context
$contextText = '';
// 'visura' can be upper or lover case, match both
$visuraKeyword = 'VISURA'; // Keyword to search in file names


dol_syslog("Starting document scan for keyword: $visuraKeyword in societe ID: " . $object->fk_societe, LOG_DEBUG);

if ($object->fk_societe > 0) {
    $filedir = $conf->societe->dir_output . '/' . dol_sanitizeFileName($object->fk_societe); // Use conf dir
    dol_syslog("Document directory: " . $filedir, LOG_DEBUG);

    if (is_dir($filedir)) {
        // Use dol_dir_list to find files containing the keyword, excluding meta/preview files
        $documents = dol_dir_list($filedir, 'files', 0, $visuraKeyword, '\.meta$|_preview.*\.png$');
        dol_syslog("Found documents matching keyword '$visuraKeyword': " . count($documents), LOG_DEBUG);

        foreach ($documents as $doc) {
            $filepath = $filedir . '/' . $doc['name'];
            $fileExtension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
            dol_syslog("Processing document: " . $filepath . " (Extension: " . $fileExtension . ")", LOG_DEBUG);
            $content = extractTextFromPDF($filepath);
            if (!empty($content)) {
                $contextText .= trim($content) . "\n\n";
                // remove multiple spaces and new lines
                $contextText = preg_replace('/\s+/', ' ', $contextText);
                $contextText = preg_replace('/\n+/', "\n", $contextText);
                $contextText = preg_replace('/\r+/', "\n", $contextText);
                $contextText = preg_replace('/\s+/', ' ', $contextText);


                dol_syslog("Trimmed context text length: " . $contextText, LOG_DEBUG);
                dol_syslog("yurij Context text length: " . strlen($contextText), LOG_DEBUG);

                dol_syslog("Successfully extracted text from PDF: " . $doc['name'], LOG_DEBUG);
            } else {
                dol_syslog("Sorry No text found in PDF: " . $doc['name'], LOG_WARNING);
            }
        }
    } else {
        dol_syslog("Directory not found for document scan: " . $filedir, LOG_WARNING);
    }
} else {
     dol_syslog("Skipping document scan: fk_societe not set.", LOG_DEBUG);
}


// Update context_ai field
if (!empty($contextText)) {
    // Append to existing context_ai if it has content, otherwise replace
    // Add a clear separator
    $separator = "\n\n--- VISURA CONTEXT ---\n";
    // remove multiple spaces and new lines
    $contextText = preg_replace('/\s+/', ' ', $contextText);
    $contextText = preg_replace('/\n+/', "\n", $contextText);
    $contextText = preg_replace('/\r+/', "\n", $contextText);
    $contextText = preg_replace('/\s+/', ' ', $contextText);
    $contextText = preg_replace('/\n\s+/', "\n", $contextText);
    $contextText = preg_replace('/\s+\n/', "\n", $contextText);

    // Limita la lunghezza del testo a 65535 caratteri (per una colonna TEXT)
    $maxLength = 65535;
    if (strlen($contextText) > $maxLength) {
        $contextText = substr($contextText, 0, $maxLength);
        dol_syslog("Context text truncated to $maxLength characters.", LOG_WARNING);
    }

    $object->context_ai = !empty($object->context_ai) ? rtrim($object->context_ai) . $separator . $contextText : $contextText;
    dol_syslog("Context AI updated with visura text. Length: " . strlen($contextText), LOG_DEBUG);
} else {
    dol_syslog("No relevant text extracted from any document for keyword '$visuraKeyword'. Context AI not updated.", LOG_WARNING);
}

// Cerca i file PDF con "visura" nel nome e popola il campo context_ai
$contextText = '';
$visuraKeyword = 'VISURA'; // Parola chiave per cercare nei nomi dei file

dol_syslog("Starting document scan for keyword: $visuraKeyword in societe ID: " . $object->fk_societe, LOG_DEBUG);

if ($object->fk_societe > 0) {
    $filedir = $conf->societe->dir_output . '/' . dol_sanitizeFileName($object->fk_societe); // Directory dei documenti
    dol_syslog("Document directory: " . $filedir, LOG_DEBUG);

    if (is_dir($filedir)) {
        // Cerca i file PDF con "visura" nel nome
        $documents = dol_dir_list($filedir, 'files', 0, $visuraKeyword, '\.pdf$');
        dol_syslog("Found documents matching keyword '$visuraKeyword': " . count($documents), LOG_DEBUG);

        foreach ($documents as $doc) {
            $filepath = $filedir . '/' . $doc['name'];
            dol_syslog("Processing document: " . $filepath, LOG_DEBUG);

            // Usa pdftotext per estrarre il testo dal PDF
            $extractedText = extractTextFromPDF($filepath);


            if (!empty($extractedText)) {
                $contextText .= trim($extractedText) . "\n\n";
                dol_syslog("Successfully extracted text from PDF: " . $doc['name'], LOG_DEBUG);
            } else {
                dol_syslog("No text found in PDF: " . $doc['name'], LOG_WARNING);
            }
        }
    }
}

// Usa il testo estratto per aggiornare il contesto AI
if (!empty($contextText)) {
    dol_syslog("Updating context AI with extracted text", LOG_DEBUG);
    $object->context_ai = $contextText;
} else {
    dol_syslog("No relevant text extracted from any document for keyword '$visuraKeyword'. Context AI not updated.", LOG_WARNING);
}

// Recupera i dati della società
if (!empty($fk_societe)) {
    $societe = new Societe($db);
    if ($societe->fetch($fk_societe) > 0) {
        // Precompila i dati della società
        $societa_nome = $societe->nom;
        $societa_indirizzo = $societe->address;
        $societa_citta = $societe->town;
        $societa_cap = $societe->zip;
        $societa_paese = $societe->country_code;
        $societa_telefono = $societe->phone;
        $societa_email = $societe->email;

        // Componi l'indirizzo completo
        $indirizzo_completo = '';
        if (!empty($societa_indirizzo)) $indirizzo_completo .= $societa_indirizzo;
        if (!empty($societa_cap)) $indirizzo_completo .= ($indirizzo_completo ? ', ' : '') . $societa_cap;
        if (!empty($societa_citta)) $indirizzo_completo .= ($indirizzo_completo && !empty($societa_cap) ? ' ' : ($indirizzo_completo ? ', ' : '')) . $societa_citta;
        if (!empty($societa_paese)) $indirizzo_completo .= ($indirizzo_completo ? ' (' . $societa_paese . ')' : '');

        // Assegna l'indirizzo completo all'oggetto diagnosi_digitale
        // Questo assicura che venga salvato se l'azione è 'save'
        $object->indirizzo_completo = $indirizzo_completo;
        dol_syslog(__METHOD__ . " Debug: Setting indirizzo_completo to: " . $object->indirizzo_completo, LOG_DEBUG);
    }
}


// Mostra i campi precompilati nel modulo
print '<table class="border centpercent">';

// Progetto (visualizza in sola lettura)
// Società (visualizza in sola lettura) attivo solo prima del salvataggio
// https://dolibarr.ai8472.com/custom/diagnosi_digitale/card.php?project_id=7
/*if (!empty($project_info)) {
    print '<tr><td class="titlefield">'.$langs->trans("Progetto").'</td><td>';
    print dol_escape_htmltag($project_info);
    print '</td></tr>';
}

if (!empty($societa_nome)) {
    print '<tr><td>'.$langs->trans("Società").'</td><td>';
    print dol_escape_htmltag($societa_nome);
    print '</td></tr>';
    print '<tr><td>'.$langs->trans("Indirizzo").'</td><td>';
    print dol_escape_htmltag($societa_indirizzo);
    print '</td></tr>';
    print '<tr><td>'.$langs->trans("Città").'</td><td>';
    print dol_escape_htmltag($societa_citta);
    print '</td></tr>';
    print '<tr><td>'.$langs->trans("CAP").'</td><td>';
    print dol_escape_htmltag($societa_cap);
    print '</td></tr>';
    print '<tr><td>'.$langs->trans("Paese").'</td><td>';
    print dol_escape_htmltag($societa_paese);
    print '</td></tr>';
    print '<tr><td>'.$langs->trans("Telefono").'</td><td>';
    print dol_escape_htmltag($societa_telefono);
    print '</td></tr>';
    print '<tr><td>'.$langs->trans("Email").'</td><td>';
    print dol_escape_htmltag($societa_email);
    print '</td></tr>';
}*/

// Add ragione_sociale field take it from azienda_ragione_sociale
// https://dolibarr.ai8472.com/custom/diagnosi_digitale/card.php?id=4
print '<tr><td><table class="border centpercent">';
print '<tr><td style="width: 300px;"><td style="width: 90px;"></td></td><td><h3>Sezione 1: Anagrafica del progetto</h3></td></tr>';
print '<tr><td style="width: 300px;">'.$langs->trans("Società:").'</td><td></td><td style="text-align:left;">';
if (empty($object->nom) && !empty($object->fk_societe)) {
    $sql = "SELECT nom FROM " . MAIN_DB_PREFIX . "societe WHERE rowid = " . ((int) $object->fk_societe);
    $resql = $db->query($sql);

    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj) {
            $object->nom = $obj->nom;
        }
    } else {
        dol_syslog(__METHOD__ . " Error fetching nom from llx_societe: " . $db->lasterror(), LOG_ERR);
    }
}
print '<h2>'.dol_escape_htmltag($object->nom).'</h2>';
print '</td></tr>';

//place holder for "sede operativa interessata"
print '<tr><td>'.$langs->trans("Sede operativa interessata:").'';
print '<input type="text" class="minwidth300" name="sede_operativa_interessata" value="'.dol_escape_htmltag($object->sede_operativa_interessata).'">';
print '</td></tr>';

// Aggiungi campo Numero dipendenti
print '<tr><td>'.$langs->trans("Numero dipendenti:").'';
print '<input type="number" min="0" class="minwidth300" name="n_dipendenti" value="'.dol_escape_htmltag($object->n_dipendenti).'">';
print '</td></tr>';

// Aggiungi menu a tendina Dimensione PMI
print '<tr><td>'.$langs->trans("Dimensione PMI:").'';
print '<select name="dimensione_pmi" class="minwidth300">';
print '<option value=""'.($object->dimensione_pmi == '' ? ' selected' : '').'></option>';
print '<option value="MICRO"'.($object->dimensione_pmi == 'MICRO' ? ' selected' : '').'>MICRO</option>';
print '<option value="PICCOLA"'.($object->dimensione_pmi == 'PICCOLA' ? ' selected' : '').'>PICCOLA</option>';
print '<option value="MEDIA"'.($object->dimensione_pmi == 'MEDIA' ? ' selected' : '').'>MEDIA</option>';
print '</select>';
print '</td></tr></table></td></tr>';

// "A. Digital Workplace numero di postazioni" and "contibuto" as in 'digital_workplace_numero' and 'digital_workplace'
// Calculate the total on the fly
// Assuming the values are already set in the object
// and are numeric
// If any of the values are not set, default to 0.00
$object->digital_workplace_numero = isset($object->digital_workplace_numero) ? $object->digital_workplace_numero : 0;
$object->digital_workplace = isset($object->digital_workplace) ? $object->digital_workplace : '0.00';
$object->digital_comm_engag = isset($object->digital_comm_engag) ? $object->digital_comm_engag : '0.00';
$object->cloud_comp_app_server = isset($object->cloud_comp_app_server) ? $object->cloud_comp_app_server : '0.00';
$object->cloud_comp_db_server = isset($object->cloud_comp_db_server) ? $object->cloud_comp_db_server : '0.00';
$object->cloud_comp_web_server = isset($object->cloud_comp_web_server) ? $object->cloud_comp_web_server : '0.00';
$object->cloud_comp_db_bkup_server = isset($object->cloud_comp_db_bkup_server) ? $object->cloud_comp_db_bkup_server : '0.00';
$object->cyber_security = isset($object->cyber_security) ? $object->cyber_security : '0.00';

print '<tr><td>'.$langs->trans("A. Digital Workplace <br>numero di postazioni").'';
print '<input type="text" id="digital_workplace_numero" style="width:195px" name="digital_workplace_numero" value="'.dol_escape_htmltag($object->digital_workplace_numero).'" onchange="updateField(this)">';
print 'Contributo<input type="text" id="digital_workplace" class="minwidth300" name="digital_workplace" value="'.dol_escape_htmltag($object->digital_workplace).'" readonly>';
print '</td></tr>';
print '<table class="border centpercent">';
// add B. Digital Commerce & Engagement as in diagnosi_digitale_fields.php
print '<tr><td></td><td style="width:400px">'.$langs->trans("B. Digital Commerce & Engagement").'</td><td>';
print '<input type="text" id="digital_comm_engage" class="minwidth300" name="digital_comm_engage" value="'.dol_escape_htmltag($object->digital_comm_engag).'" onchange="updateField(this)">';
print '</td></tr>';
// add C.1 Cloud Computing - Application Server 'cloud_comp_app_server' as in diagnosi_digitale_fields.php
print '<tr><td></td><td>'.$langs->trans("C.1 Cloud Computing - Application Server").'</td><td>';
print '<input type="text" id="cloud_comp_app_server" class="minwidth300" name="cloud_comp_app_server" value="'.dol_escape_htmltag($object->cloud_comp_app_server).'" onchange="updateField(this)">';
print '</td></tr>';
// add C.2 Cloud Computing - Database Server 'cloud_comp_db_server' as in diagnosi_digitale_fields.php
print '<tr><td></td><td>'.$langs->trans("C.2 Cloud Computing - Database Server").'</td><td>';
print '<input type="text" id="cloud_comp_db_server" class="minwidth300" name="cloud_comp_db_server" value="'.dol_escape_htmltag($object->cloud_comp_db_server).'" onchange="updateField(this)">';
print '</td></tr>';
// add C.3 Cloud Computing - web Server 'cloud_comp_web_server' as in diagnosi_digitale_fields.php
print '<tr><td></td><td>'.$langs->trans("C.3 Cloud Computing - Web Server").'</td><td>';
print '<input type="text" id="cloud_comp_web_server" class="minwidth300" name="cloud_comp_web_server" value="'.dol_escape_htmltag($object->cloud_comp_web_server).'" onchange="updateField(this)">';
print '</td></tr>';
// add C.4 Cloud Computing - Database Back Up 'cloud_comp_db_bkup_server' as in diagnosi_digitale_fields.php
print '<tr><td></td><td>'.$langs->trans("C.4 Cloud Computing - Database Back Up").'</td><td>';
print '<input type="text" id="cloud_comp_db_bkup_server" class="minwidth300" name="cloud_comp_db_bkup_server" value="'.dol_escape_htmltag($object->cloud_comp_db_bkup_server).'" onchange="updateField(this)">';
print '</td></tr>';
// add C.5 Cyber Security 'cyber_security' as in diagnosi_digitale_fields.php
print '<tr><td></td><td>'.$langs->trans("D Cyber Security").'</td><td>';
print '<input type="text" id="cyber_security" class="minwidth300" name="cyber_security" value="'.dol_escape_htmltag($object->cyber_security).'" onchange="updateField(this)">';
print '</td></tr>';
// add totale as ((digital_workplace_numero * 2270 euro) + digital_comm_engage + cloud_comp_app_server + cloud_comp_db_server + cloud_comp_web_server + cloud_comp_db_back_up)
print '<tr><td></td><td>'.$langs->trans("Totale").'</td><td>';
// Calculate the total in euro when a field is changed
$total = ((int)$object->digital_workplace_numero * 2270)
    + (float)$object->digital_comm_engag
    + (float)$object->cloud_comp_app_server
    + (float)$object->cloud_comp_db_server
    + (float)$object->cloud_comp_web_server
    + (float)$object->cloud_comp_db_bkup_server
    + (float)$object->cyber_security;
// Display the total
print '<input type="text" id="totale" class="minwidth300" name="totale" value="'.dol_escape_htmltag($total).'" readonly>';
print '</td></tr>';


function renderDiagnosiDigitaleTextarea($object, $fieldKey, $langs, $columnComments) {
    global $conf, $db; // Rimosso $visuraKeyword se non usato qui

    // Sanitize and check field key
    $fieldKey = strtolower($fieldKey);
    $fieldKey = preg_replace('/[^a-z0-9_]/', '', $fieldKey);
    if (!isset($object->fields[$fieldKey])) {
        dol_syslog("renderDiagnosiDigitaleTextarea: Field key '$fieldKey' not found in object fields.", LOG_WARNING);
        return;
    }

    $label = $object->fields[$fieldKey]['label'];
    $value = $object->$fieldKey ?? ''; // Valore attuale del campo specifico
    $prompt = isset($columnComments[$fieldKey]) ? $columnComments[$fieldKey] : "Default prompt for $label";

    // Ottieni il testo dal campo context_ai
    $contextAI = $object->context_ai ?? '';
    $pdfTextShort = mb_substr($contextAI, 0, 4000); // Limita lunghezza

    // Ottieni il testo dal context_web
    $contextWeb = $object->context_web ?? '';

    // --- Inizio: Costruisci contesto da tutte le colonne ---
    $allFieldsContext = '';
    $fieldsToExclude = [ // Campi da non includere nel contesto generale
        'rowid', 'id', 'entity', 'fk_projet', 'fk_societe',
        'datec', 'tms', 'date_creation', 'fk_user_author', 'fk_user_modif',
        'model_pdf', 'ref',
        'context_ai', 'context_web', 'context_ocr',
        $fieldKey // Escludi il campo corrente stesso
    ];

    // Itera su tutti i campi definiti per l'oggetto
    foreach ($object->fields as $key => $fieldInfo) {
        // Salta i campi da escludere
        if (in_array($key, $fieldsToExclude)) {
            continue;
        }

        // Salta campi non visibili o non abilitati se necessario (opzionale)
        // if (empty($fieldInfo['enabled']) || empty($fieldInfo['visible'])) {
        //     continue;
        // }

        // Ottieni il valore del campo dall'oggetto
        $fieldValue = $object->$key ?? null;

        // Aggiungi al contesto solo se ha un valore significativo
        if ($fieldValue !== null && $fieldValue !== '') {
            $fieldLabel = $fieldInfo['label'] ?? ucfirst(str_replace('_', ' ', $key)); // Usa label se disponibile
            $allFieldsContext .= dol_escape_htmltag($langs->trans($fieldLabel)) . ": " . dol_escape_htmltag($fieldValue) . "\n";
        }
    }
    // --- Fine: Costruisci contesto da tutte le colonne ---


    $mainRole = "Sei un Innovation Manager, mantieni un tono professionale e distaccato, devi compilare il un questionario relativo a un Voucher della Regione Calabria, non scrivere il nome dell'azienda, limitati a locuzioni. Le risposte devono restare tassativamente entro le 40 parole. Non usare mai il termine 'AI' o 'Artificial Intelligence";

    // Costruisci il contenuto concatenato con separatori "***"
    $concatenatedPrompt =
    dol_escape_htmltag($prompt) .
                          "\n\n*** Contesto Amministrativo Generale ***\n\n" .
                          dol_escape_htmltag($pdfTextShort) . // Contenuto da context_ai
                          "\n\n*** Contesto Web (Spunti Narrativi) ***\n\n" .
                          dol_escape_htmltag($contextWeb) . // Contenuto da context_web
                          // --- Aggiungi il contesto da tutte le altre colonne ---
                          "\n\n*** Contesto Completo dagli Altri Campi della Diagnosi (cosa abbiamo già scritto) ***\n\n" .
                          $allFieldsContext . // Contenuto da tutte le altre colonne
                          "\n\n*** Contenuto Attuale del Campo '" . $langs->trans($label) . "' (se presente, usalo come base o riferimento):\n\n" .
                          dol_escape_htmltag($value) .
                          "\n\n*** Devi rispondere alla comanda che segue (relativa al campo '" . $langs->trans($label) . "'):\n\n" . // Contenuto attuale del campo specifico
                          dol_escape_htmltag($mainRole);
    // Stampa le textarea
    print '<tr><td></td><td class="titlefield" title="'.dol_escape_htmltag($prompt).'">'.$langs->trans($label).'</td><td>';
    print '<div style="display: flex; align-items: center;">';
    print '<textarea name="'.$fieldKey.'" rows="2" cols="80" class="content-field" data-target="'.$fieldKey.'" style="margin-right: 10px;">'.dol_escape_htmltag($value).'</textarea>';
    // Nota: Il prompt completo ora include tutti i campi, potrebbe diventare molto lungo.
    print '<textarea name="prompt_'.$fieldKey.'" rows="2" cols="80" style="display: none; margin-top: 10px;">'.$concatenatedPrompt.'</textarea>';
    print '<button type="button" class="button generate-button" data-target="'.$fieldKey.'">'.$langs->trans("Generate").'</button>';
    print '</div></td></tr>';
}

$campiDiagnosi = [
    // Sezione 2-3
    'approccio_metodologico', 'settore_industriale', 'dimensioni_ambizioni', 'caratteristiche_prodotti',
    'maturita_digitale', 'obiettivi_azienda', 'capacita_investimento', 'capacita_gestionale',

    // Sezione 4 - Digital Workplace
    'software_produttivita', 'software_altri', 'software_comunicazione', 'software_archiviazione', 'software_automazione',
    'piattaforme_condivisione', 'software_firma',

    // Digital Commerce
    'piattaforma_dig_commerce', 'piattaforma_campagne', 'piattaforma_dig_exper', 'piattaforma_analytics',
    'piattaforma_mobile', 'piattaforma_integrazione', 'piattaforma_logistica',

    // Cloud
    'presenza_cloud', 'presenza_app_client', 'applicazioni_client', 'servizi_calcolo', 'servizi_db_e_archiv', 'servizi_network',
    'servizi_identita_sec', 'servizi_devel_test',

    // Cyber Security
    'sistemi_accessi', 'sistemi_network_secur', 'sistemi_endpoint_secur', 'sistemi_data_secur',
    'sistemi_vulnerab_admin', 'sistemi_secur_analytics', 'sistemi_applic_security', 'sistemi_risk_compl_admin',

    // Sintesi (Sezione 5)
    'sintesi_d_workplace', 'fabbisogno_sintesi_d_workplace','sintesi_d_comm_engagem', 'fabbisogno_sintesi_d_comm_engagem', 'sintesi_cc_app_server', 'fabbisogno_sintesi_cc_app_server',
    'sintesi_cc_db_server', 'fabbisogno_sintesi_cc_db_server', 'sintesi_cc_web_server', 'fabbisogno_sintesi_cc_web_server',
    'sintesi_cc_db_backup', 'fabbisogno_sintesi_cc_db_backup', 'sintesi_cyber_security', 'fabbisogno_sintesi_cyber_security',
    'impatto_stimato', 'rischi_iniziative', 'criteri_chiave', 'priorita', 'tempo_completamento','data_completamento', 'cronoprogramma'
];

$sezioni = [
    'approccio_metodologico' => 'Sezione 2: Approccio metodologico',
    'settore_industriale' => 'Sezione 3: Analisi del contesto aziendale',
    'software_produttivita' => 'Sezione 4: Analisi per Tipologia di Intervento',
    'sintesi_d_workplace' => 'Sezione 5: Sintesi dell\’analisi ex-ante'
];

foreach ($campiDiagnosi as $fieldKey) {
    if (isset($sezioni[$fieldKey])) {
        print '<tr><td></td><td></td><td><h3>' . $sezioni[$fieldKey] . '</h3></td><td><div class="center"><input type="submit" class="button" name="action" value="save"></div></td></tr>';
    }
    renderDiagnosiDigitaleTextarea($object, $fieldKey, $langs, $columnComments);
}




print '</table>';
// Add the context textarea
// Aggiungi la checkbox per mostrare/nascondere i campi di contesto
print '<tr>';
print '<td class="titlefield">'.$langs->trans("Contesto").'</td>';
print '<td>';
print '<input type="checkbox" id="toggle_context" name="toggle_context">';
print '<label for="toggle_context">'.$langs->trans("Mostra/Nascondi Contesto").'</label>';
print '</td>';
print '</tr>';

// Aggiungi i campi di contesto (inizialmente nascosti)
print '<tr id="context_fields" style="display: none;">';
print '<td class="titlefield">'.$langs->trans("Context").'</td>';
print '<td>';
print '<div style="display: flex; flex-direction: column;">';
print '<textarea name="context_ai" id="context_ai" rows="10" cols="80" style="margin-bottom: 10px;">'.dol_escape_htmltag($object->context_ai).'</textarea>';
print '<button type="button" class="button" id="concat_context">'.$langs->trans("ConcatContextToPrompt").'</button>';
print '</div>';
print '</td>';
print '</tr>';

print '<tr id="context_web_fields" style="display: none;">';
print '<td class="titlefield">'.$langs->trans("ContextWeb").'</td>';
print '<td>';
print '<div style="display: flex; flex-direction: column;">';
print '<textarea name="context_web" id="context_web" rows="10" cols="80" style="margin-bottom: 10px;">'.dol_escape_htmltag($object->context_web).'</textarea>';
print '<button type="button" class="button" id="crawl_web">'.$langs->trans("CrawlWebsite").'</button>';
print '</div>';
print '</td>';
print '</tr>';

// Aggiungi lo script per gestire il comportamento di mostra/nascondi
print '<script>
document.addEventListener("DOMContentLoaded", function() {
    const toggleContext = document.getElementById("toggle_context");
    const contextFields = document.getElementById("context_fields");

    if (toggleContext && contextFields) {
        toggleContext.addEventListener("change", function() {
            if (this.checked) {
                contextFields.style.display = "block";
            } else {
                contextFields.style.display = "none";
            }
        });
    } else {
        console.error("Element not found: toggle_context or context_fields");
    }
});
</script>';


print '<script>
document.getElementById("crawl_web").addEventListener("click", function() {
    const url = prompt("Enter the URL to crawl:");
    if (!url) {
        alert("URL is required.");
        return;
    }

    const contextWebField = document.getElementById("context_web");
    this.disabled = true; // Disable the button during the request

    fetch("custom/diagnosi_digitale/script/crawl_web.php?url=" + encodeURIComponent(url))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                contextWebField.value = data.content;
                alert("Website content has been loaded into the context_web field.");
            } else {
                alert("Error: " + data.error);
            }
        })
        .catch(error => {
            console.error("Error crawling website:", error);
            alert("An error occurred while crawling the website.");
        })
        .finally(() => {
            this.disabled = false; // Re-enable the button
        });
});
</script>';


// Aggiungi un pulsante generale per mostrare o nascondere tutte le textarea dei prompt
print '<div class="center">';
print '<button type="button" class="button" id="toggle_all_prompts">'.$langs->trans("EditAllPrompts").'</button>';
print '</div>';

// Aggiungi uno script per gestire la visibilità di tutte le textarea dei prompt
print '<script>
document.getElementById("toggle_all_prompts").addEventListener("click", function() {
    const prompts = document.querySelectorAll("textarea[name^=\'prompt_\']");
    const isHidden = Array.from(prompts).every(prompt => prompt.style.display === "none" || prompt.style.display === "");
    prompts.forEach(prompt => {
        prompt.style.display = isHidden ? "block" : "none";
    });
    this.textContent = isHidden ? "'.$langs->trans("HideAllPrompts").'" : "'.$langs->trans("EditAllPrompts").'";
});
</script>';

// Aggiungi uno script per gestire la chiamata all'AI
print '<script src="'.DOL_URL_ROOT.'/custom/diagnosi_digitale/js/ai_handler.js"></script>';
print '<script>
document.getElementById("generate_ai").addEventListener("click", function() {
    const prompt = document.querySelector("textarea[name=\'oggetto_valutazione\']").value;
    generateAI(prompt);
});
</script>';

print '<script>
document.querySelectorAll(".generate-button").forEach(button => {
    button.addEventListener("click", function() {
        const field = this.getAttribute("data-field");
        const textarea = document.querySelector(`textarea[name="${field}"]`);
        const prompt = textarea.value;

        console.log("Generate AI: Sending prompt for field:", field, "Prompt:", prompt);

        fetch("'.DOL_URL_ROOT.'/custom/diagnosi_digitale/script/ai_handler.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ prompt: prompt })
        })
        .then(response => {
            console.log("Generate AI: Server response status:", response.status);
            if (!response.ok) {
                throw new Error("HTTP error! status: " + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log("Generate AI: Received response:", data);
            if (data.success) {
                textarea.value = data.content; // Aggiorna il contenuto del textarea
            } else {
                alert("Error generating AI content: " + data.error);
            }
        })
        .catch(error => {
            console.error("Generate AI: Fetch error:", error);
            alert("Error generating AI content. Please try again.");
        });
    });
});
</script>';

print '<script>
document.querySelectorAll(".toggle-prompt").forEach(button => {
    button.addEventListener("click", function() {
        const field = this.getAttribute("data-field");
        const promptTextarea = document.querySelector(`textarea[name="prompt_${field}"]`);
        if (promptTextarea.style.display === "none") {
            promptTextarea.style.display = "block";
            this.textContent = "'.$langs->trans("Hide Prompt").'";
        } else {
            promptTextarea.style.display = "none";
            this.textContent = "'.$langs->trans("Edit Prompt").'";
        }
    });
});
</script>';

print '<script>
document.querySelectorAll(".generate-button").forEach(button => {
    button.addEventListener("click", function() {
        const targetField = this.getAttribute("data-target");
        const promptTextarea = document.querySelector(`textarea[name="prompt_${targetField}"]`);
        const targetTextarea = document.querySelector(`textarea[name="${targetField}"]`);

        if (promptTextarea && targetTextarea) {
            // Usa il contenuto del prompt per riempire il campo
            targetTextarea.value = promptTextarea.value;
        }
    });
});
</script>';

print '<script>
document.getElementById("concat_context").addEventListener("click", function() {
    const contextField = document.querySelector("textarea[name=\'context_ai\']");
    const promptFields = document.querySelectorAll("textarea[name^=\'prompt_\']");

    if (!contextField) {
        alert("The context field is missing.");
        return;
    }

    const context = contextField.value.trim();
    if (!context) {
        alert("The context is empty. Please provide context before concatenating.");
        return;
    }

    if (promptFields.length === 0) {
        alert("No prompt fields found to concatenate.");
        return;
    }

    promptFields.forEach(promptField => {
        const currentPrompt = promptField.value.trim();
        promptField.value = currentPrompt ? `${currentPrompt}\n\n${context}` : context;
    });

    alert("Context has been concatenated to all prompts.");
});
</script>';

print '</table>';
print '<div class="center"><input type="submit" class="button" name="action" value="save"></div>';

// Rimuovi i listener 'submit' duplicati precedenti

// Aggiungi un unico listener 'submit'
print '<script>
document.addEventListener("DOMContentLoaded", function() {
    const mainForm = document.querySelector(\'form[action*="card.php"]\'); // Seleziona il form principale in modo più specifico se necessario

    if (mainForm) {
        mainForm.addEventListener("submit", function (e) {
            console.log("Form submission initiated...");

            // Seleziona tutti i textarea il cui nome inizia con "prompt_"
            const promptFields = mainForm.querySelectorAll("textarea[name^=\'prompt_\']");

            // Disabilita temporaneamente questi campi in modo che non vengano inclusi nei dati POST
            // Disabilitare è spesso preferibile a rimuovere, nel caso tu voglia riabilitarli se invio fallisce lato client.
            promptFields.forEach(field => {
                field.disabled = true;
                console.log(`Disabled field: ${field.name}`);
            });

            // Log opzionale dei dati che *verranno* inviati (FormData non mostrerà i campi disabilitati)
            const formData = new FormData(mainForm);
            const dataToSend = {};
            formData.forEach((value, key) => {
                dataToSend[key] = value;
            });
            console.log("Data being sent (excluding disabled prompt_ fields):", dataToSend);

            // Il modulo verrà inviato normalmente dopo la esecuzione di questo listener,
            // poiché non chiamiamo e preventDefault e non interrompiamo levento.

            // Opzionale: Riabilita i campi subito dopo. Utile se linvio viene annullato
            // o fallisce lato client prima che la pagina venga scaricata.
            // Per un invio standard del modulo, questo potrebbe non essere strettamente necessario.
            /*
            setTimeout(() => {
                promptFields.forEach(field => {
                    field.disabled = false;
                });
                console.log("Prompt fields re-enabled (in case submission fails).");
            }, 0);
            */
        });
    } else {
        console.warn("Main form for submission handling not found.");
    }




.

}); // Fine DOMContentLoaded
</script>';

print '<script>
document.querySelector("form").addEventListener("submit", function (e) {
    // Rimuovi i campi che iniziano con "prompt_"
    const promptFields = document.querySelectorAll("textarea[name^=\'prompt_\']");
    promptFields.forEach(field => field.remove());

    console.log("Form ready for submission:", new FormData(this));
});
</script>';
print '<script>
document.querySelector(\'form\').addEventListener(\'submit\', function (e) {
    e.preventDefault(); // Previeni l\'invio del modulo per il debug
    const formData = new FormData(this);
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });
    console.log(\'Dati inviati al backend:\', data);

    // Rimuovi il commento per inviare il modulo
     this.submit();
});
</script>';

print '<script>
document.querySelector("form").addEventListener("submit", function (e) {
    const contextAIField = document.querySelector("textarea[name=\'context_ai\']");
    if (contextAIField) {
        console.log("Removing context_ai field before submission.");
        contextAIField.remove(); // Rimuove il campo context_ai dal DOM
    }

    // Debug: Mostra i dati inviati al backend
    const formData = new FormData(this);
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });
    console.log("Dati inviati al backend:", data);
});
</script>';

if (GETPOST('modelselected')) {
    $action = 'presend';
}

// Document Management Section
if ($action != 'presend') {
    print '<div class="fichecenter"><div class="fichehalfleft">';
    print '<a name="builddoc"></a>'; // Anchor for document section

    if (getDolGlobalInt('PROJECT_ENABLE_SUB_PROJECT')) {
        /*
         * Sub-projects (children)
         */
        $children = $object->getChildren();
        if ($children) {
            print '<table class="centpercent notopnoleftnoright table-fiche-title">';
            print '<tr class="titre"><td class="nobordernopadding valignmiddle col-title">';
            print '<div class="titre inline-block">'.$langs->trans('Sub-projects').'</div>';
            print '</td></tr></table>';

            print '<div class="div-table-responsive-no-min">';
            print '<table class="centpercent noborder">';
            print '<tr class="liste_titre">';
            print getTitleFieldOfList('Ref', 0, $_SERVER["PHP_SELF"], '', '', '', '', '', '', '', 1);
            print getTitleFieldOfList('Title', 0, $_SERVER["PHP_SELF"], '', '', '', '', '', '', '', '', '', '', '', '', 1);
            print getTitleFieldOfList('Status', 0, $_SERVER["PHP_SELF"], '', '', '', '', '', '', '', '', '', 1);
            print '</tr>';
            print "\n";

            $subproject = new Project($db);
            foreach ($children as $child) {
                $subproject->fetch($child->rowid);
                print '<tr class="oddeven">';
                print '<td class="nowraponall">'.$subproject->getNomUrl(1, 'project').'</td>';
                print '<td class="nowraponall tdoverflowmax125">'.$child->title.'</td>';
                print '<td class="nowraponall">'.$subproject->getLibStatut(5).'</td>';
                print '</tr>';
            }

            print '</table>';
            print '</div>';
        }
    }

    /*
     * Generated documents
     */
    if ($fk_societe > 0) {
        $filedir = DOL_DATA_ROOT . '/societe/' . $object->fk_societe;
    } //else {
       // $filedir = $conf->diagnosi_digitale->dir_output . '/' . dol_sanitizeFileName($object->ref);
    //}

    // Ensure the directory exists
    if (!is_dir($filedir)) {
        $filedir = DOL_DATA_ROOT . '/societe/' . $object->fk_societe;
        //dol_syslog("Error: Directory $filedir does not exist.", LOG_ERR);
        //print '<div class="error">Error: Document directory does not exist.</div>';
        //exit;
    }

    //$modulepart = $fk_societe > 0 ? 'societe' : 'diagnosi_digitale';
    $modulepart = 'societe';
    $filename = $object->fk_societe. '/' .dol_sanitizeFileName($object->refMod);

    //$file = $fk_societe > 0 ? $fk_societe . '/' . dol_sanitizeFileName($filename) : dol_sanitizeFileName($filename);
    //$file = $object->fk_societe. '/' .dol_sanitizeFileName($filename);
    $urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
    //$filename = $urlsource;
    $genallowed = ($user->rights->diagnosi_digitale->read);
    $delallowed = ($user->rights->diagnosi_digitale->write);

    print $formfile->showdocuments(
        $modulepart,          // Module part
        $filename,                // Object reference
        $filedir,             // Directory for documents
        $urlsource,           // URL source
        $genallowed,          // Allow document generation
        $delallowed,          // Allow document deletion
        $object->model_pdf,   // PDF model
        1,                    // Show upload form
        0,                    // No max file size
        0,                    // No max width
        28,                   // Thumbnail size
        0,                    // No forced download
        '',                   // No specific file to highlight
        '',                   // No specific file to exclude
        '',                   // No specific file to include
        $langs->defaultlang   // Default language
    );

    // Upload form
    print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST" enctype="multipart/form-data">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="upload_file">';
    print '<table class="noborder">';
    print '<tr>';
    print '<td class="valignmiddle nowrap">';
    print '<input type="hidden" name="MAX_FILE_SIZE" value="4194304">'; // 4 MB limit
    print '<input class="flat minwidth400 maxwidth200onsmartphone" type="file" name="userfile[]" multiple="" accept="">';
    print '<input type="submit" class="button small reposition" name="sendit" value="' . $langs->trans("Upload") . '">';
    print '<span class="fas fa-info-circle em088 opacityhigh classfortooltip" style="" title="' . $langs->trans("FileSizeLimitInfo", "4096 Kb", "20480 Kb") . '"></span>';
    print '</td>';
    print '</tr>';
    print '</table>';
    print '</form>';

    print '</div><div class="fichehalfright">';

    $MAXEVENT = 10;

    $morehtmlcenter = '<div class="nowraponall">';
    $morehtmlcenter .= dolGetButtonTitle($langs->trans('FullConversation'), '', 'fa fa-comments imgforviewmode', DOL_URL_ROOT.'/diagnosi_digitale/messaging.php?id='.$object->id);
    $morehtmlcenter .= dolGetButtonTitle($langs->trans('FullList'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/diagnosi_digitale/agenda.php?id='.$object->id);
    $morehtmlcenter .= '</div>';

    // List of actions on the element
    include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
    $formactions = new FormActions($db);
    $somethingshown = $formactions->showactions(
        $object,
        $object->element,
        0,
        1,
        '',
        $MAXEVENT,
        '',
        $morehtmlcenter
    );

    print '</div></div>';
}

// Handle file upload
if ($action == 'upload_file' && $user->rights->diagnosi_digitale->write) {
    if (!empty($_FILES['userfile']['name'][0])) {
        // Always use the societe folder
        $upload_dir = DOL_DATA_ROOT . '/societe/' . $object->fk_societe;

        // Ensure the directory exists
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                dol_syslog("Error: Failed to create directory $upload_dir.", LOG_ERR);
                print '<div class="error">Error: Failed to create upload directory.</div>';
                exit;
            }
        }

        // Process each uploaded file
        foreach ($_FILES['userfile']['name'] as $key => $filename) {
            $tmp_name = $_FILES['userfile']['tmp_name'][$key];
            $dest_file = $upload_dir . '/' . dol_sanitizeFileName($filename);

            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "ecm_files
                    WHERE filepath = '" . $db->escape($filepath) . "'
                    AND filename = '" . $db->escape($filename) . "'
                    AND entity = " . ((int) $conf->entity);
            $res = $db->query($sql);

            if ($res && $db->num_rows($res) > 0) {
                dol_syslog("File already exists: $filepath/$filename", LOG_WARNING);
                setEventMessages($langs->trans("FileAlreadyExists", $filename), null, 'warnings');
                return 0; // Skip insertion
            }

            if (move_uploaded_file($tmp_name, $dest_file)) {
                dol_syslog("File uploaded successfully to $dest_file.", LOG_DEBUG);
                setEventMessages($langs->trans("FileUploadedSuccessfully", $filename), null, 'mesgs');
            } else {
                dol_syslog("Error: Failed to upload file $filename to $dest_file.", LOG_ERR);
                setEventMessages($langs->trans("ErrorFailToUploadFile", $filename), null, 'errors');
            }
        }
    } else {
        setEventMessages($langs->trans("ErrorNoFileUploaded"), null, 'errors');
    }
}

// Build doc
if ($action == 'builddoc' && $user->rights->diagnosi_digitale->write) {
    // Save last template used to generate document
    if (GETPOST('model')) {
        $object->setDocModel($user, GETPOST('model', 'alpha'));
    }

    $outputlangs = $langs;
    if (GETPOST('lang_id', 'aZ09')) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang(GETPOST('lang_id', 'aZ09'));
    }
    $result = $object->generateDocument($object->model_pdf, $outputlangs);
    if ($result <= 0) {
        setEventMessages($object->error, $object->errors, 'errors');
        $action = '';
    }
    $result = $object->generateDOCXDocument($object->model_docx);
    if ($result <= 0) {
        setEventMessages($object->error, $object->errors, 'errors');
        $action = '';
    }
    $result = $object->generateODTDocument($object->model_odt);
    if ($result <= 0) {
        setEventMessages($object->error, $object->errors, 'errors');
        $action = '';
    }
}

// Gestione della generazione dei documenti
if ($action == 'builddoc' && $user->rights->diagnosi_digitale->write) {
    if (empty($object->model_pdf)) {
        dol_syslog("Error: No PDF model configured for object.", LOG_ERR);
        print '<div class="error">Error: No PDF model configured.</div>';
        exit;
    }

    $outputlangs = $langs;
    if (GETPOST('lang_id', 'aZ09')) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang(GETPOST('lang_id', 'aZ09'));
    }

    $result = $object->generateDocument($object->model_pdf, $outputlangs);
    if ($result <= 0) {
        dol_syslog("Error: Failed to generate document. Error: " . $object->error, LOG_ERR);
        setEventMessages($object->error, $object->errors, 'errors');
        $action = '';
    } else {
        dol_syslog("Debug: Document generated successfully for object Ref = " . $object->ref, LOG_DEBUG);
    }

    $result = $object->generateDOCXDocument($object->model_docx); // DOCX - Line 1225 approx
    if ($result <= 0) { /* ... */ }
    $result = $object->generateODTDocument($object->model_odt);   // ODT
    if ($result <= 0) { /* ... */ }
}

// Delete file in doc form
if ($action == 'remove_file' && $user->rights->diagnosi_digitale->write) {
    if ($object->id > 0) {
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $langs->load("other");
        $upload_dir = DOL_DATA_ROOT . '/societe/'; //. $object->fk_societe;
        //$upload_dir = $conf->diagnosi_digitale->dir_output . '/' . dol_sanitizeFileName($object->ref);
        $file = $upload_dir.GETPOST('file');
        $ret = dol_delete_file($file, 0, 0, 0, $object);
        if ($ret) {
            setEventMessages($langs->trans("FileWasRemoved", GETPOST('file')), null, 'mesgs');
        } else {
            setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), null, 'errors');
        }
        $action = '';
    }
}

// Gestione della rimozione dei file
if ($action == 'remove_file' && $user->rights->diagnosi_digitale->write) {
    if ($object->id > 0) {
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $langs->load("other");
        $upload_dir = $conf->diagnosi_digitale->dir_output . '/' . dol_sanitizeFileName($object->ref);
        $file = $upload_dir . '/' . GETPOST('file');
        if (file_exists($file)) {
            $ret = dol_delete_file($file, 0, 0, 0, $object);
            if ($ret) {
                setEventMessages($langs->trans("FileWasRemoved", GETPOST('file')), null, 'mesgs');
            } else {
                dol_syslog("Error: Failed to delete file $file.", LOG_ERR);
                setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), null, 'errors');
            }
        } else {
            dol_syslog("Error: File $file does not exist.", LOG_ERR);
            setEventMessages($langs->trans("ErrorFileDoesNotExist", GETPOST('file')), null, 'errors');
        }
        $action = '';
    }
}

// OCR Processing
$ocrProcessor = new OCRProcessor();

// Percorso del file di input e di output
/*$inputFile = DOL_DATA_ROOT . '/societe/' . $object->fk_societe . '/example.pdf';
$outputFile = DOL_DATA_ROOT . '/societe/' . $object->fk_societe . '/example_ocr';

try {
    // Genera il PDF leggibile
    $pdfReadableFile = $ocrProcessor->generateReadablePDF($inputFile, $outputFile);
    print '<div class="ok">Copia OCR generata con successo: <a href="' . $pdfReadableFile . '">Scarica il PDF leggibile</a></div>';
} catch (Exception $e) {
    print '<div class="error">Errore durante la generazione della copia OCR: ' . $e->getMessage() . '</div>';
}
*/
/**
 * Estrae il testo da un file PDF utilizzando pdftotext.
 *
 * @param string $filePath Percorso del file PDF.
 * @param string|null $outputFile (Opzionale) Percorso del file di output per salvare il testo estratto.
 * @return string|false Testo estratto dal PDF o false in caso di errore.
 */
function extractTextFromPDF($filePath, $outputFile = null)
{
    $escapedFilePath = escapeshellarg($filePath);
    dol_syslog("Yurij Extracting text from PDF: $escapedFilePath", LOG_DEBUG);
    /*if (!file_exists($escapedFilePath)) {
        dol_syslog("Error: File $filePath does not exist.", LOG_ERR);
        return false;
    }*/
    //$command = "/usr/bin/pdftotext -layout $escapedFilePath";
    $command = "/usr/bin/pdftotext -layout $filePath";
    dol_syslog("PDF Command to execute: $command", LOG_DEBUG);

    if ($outputFile) {
        $escapedOutputFile = escapeshellarg($outputFile);
        $command .= " $escapedOutputFile";
    } else {
        $command .= " -"; // Output to stdout
    }

    dol_syslog("Executing command: $command", LOG_DEBUG);

    $output = [];
    $returnVar = 0;
    exec($command . " 2>&1", $output, $returnVar);

    //dol_syslog("Command output: " . implode("\n", $output), LOG_DEBUG);
    //dol_syslog("Command return code: $returnVar", LOG_DEBUG);

    if ($returnVar !== 0) {
        dol_syslog("Error: Failed to extract text using pdftotext for file: $filePath", LOG_ERR);
        return false;
    }

    // Se è stato specificato un file di output, restituisci il contenuto del file
    if ($outputFile && file_exists($outputFile)) {
        return file_get_contents($outputFile);
    }
    //shorten text to 5000 characters
    $output = array_slice($output, 0, 5000);



    // Altrimenti, restituisci l'output diretto
    return implode("\n", $output);
}

function displayErrorsAndMessages()
{
    global $langs;

    // Display errors
    if (!empty($GLOBALS['mesgs'])) {
        print '<div class="error">';
        foreach ($GLOBALS['mesgs'] as $msg) {
            print dol_escape_htmltag($msg) . '<br>';
        }
        print '</div>';
    }

    // Display success messages
    if (!empty($GLOBALS['mesgs_ok'])) {
        print '<div class="ok">';
        foreach ($GLOBALS['mesgs_ok'] as $msg) {
            print dol_escape_htmltag($msg) . '<br>';
        }
        print '</div>';
    }
}

function decryptPDF($inputFile, $outputFile)
{
    $command = "qpdf --decrypt " . escapeshellarg($inputFile) . " " . escapeshellarg($outputFile);
    exec($command, $output, $returnVar);

    if ($returnVar !== 0) {
        dol_syslog("Error: Failed to decrypt PDF file: $inputFile", LOG_ERR);
        return false;
    }

    dol_syslog("PDF file successfully decrypted: $outputFile", LOG_DEBUG);
    return true;
}

function sanitizePdfText($text)
{
    // Rimuovi caratteri non stampabili
    $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);

    // Converti entità HTML in caratteri normali
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

    // Rimuovi tag HTML o script
    $text = strip_tags($text);

    // Rimuovi spazi multipli e normalizza gli spazi
    $text = preg_replace('/\s+/', ' ', $text);

    // Limita la lunghezza del testo (opzionale)
    $text = mb_substr($text, 0, 10000, 'UTF-8'); // Limita a 10.000 caratteri

    // Escapa caratteri speciali per prevenire SQL injection
    global $db;
    $text = $db->escape($text);

    return trim($text);
}


// Salvataggio
if (empty($object->fk_societe) && $fk_societe > 0) $object->fk_societe = $fk_societe;
if (empty($object->fk_projet) && $fk_projet > 0) $object->fk_projet = $fk_projet;


//add debug to save action
if ($action == 'save') {
    // Mappa i nomi dei campi dal POST ai nomi dei campi dell'oggetto/DB se diversi
    $fieldMapping = [
        'cloud_comp_db_back_up' => 'cloud_comp_db_bkup_server',
        'digital_comm_engage' => 'digital_comm_engag'
        // Aggiungi altre mappature se necessario
    ];

    // Itera sui dati POST
    foreach ($_POST as $key => $value) {
        // Salta i campi prompt e altri campi non di dati (token, action, id, etc.)
        if (strpos($key, 'prompt_') === 0 || in_array($key, ['token', 'action', 'id', 'project_id', 'fk_projet', 'fk_societe'])) {
            continue;
        }

        // Usa il nome mappato se esiste, altrimenti usa la chiave originale
        $objectKey = $fieldMapping[$key] ?? $key;

        // Verifica se la chiave (originale o mappata) esiste nei campi dell'oggetto
        if (isset($object->fields[$objectKey])) {
            $field = $object->fields[$objectKey];
            dol_syslog(__METHOD__ . " Debug: Processing POST field '$key' as object field '$objectKey' with value: " . $value, LOG_DEBUG);

            // Assegna il valore in base al tipo di campo definito nell'oggetto
            switch ($field['type']) {
                case 'int':
                case 'integer':
                    // Assegna NULL se vuoto, altrimenti intval
                    $object->$objectKey = ($value === '' || $value === null) ? null : intval($value);
                    break;
                case 'double':
                case 'decimal(10,2)':
                case 'double(24,8)': // Aggiunto per fatturato_annuo
                    // Sostituisci la virgola, assegna NULL se vuoto, altrimenti floatval
                    $object->$objectKey = ($value === '' || $value === null) ? null : floatval(str_replace(',', '.', $value));
                    break;
                case 'date':
                    // Assegna NULL se vuoto, altrimenti formatta la data
                    $object->$objectKey = !empty($value) ? date('Y-m-d', strtotime($value)) : null;
                    break;
                case 'boolean': // Aggiunto per coerenza
                case 'tinyint(1)':
                    // Assegna 1 per '1' o true, 0 altrimenti
                    $object->$objectKey = ($value == '1' || $value === true || $value === 'on') ? 1 : 0; // Gestisce anche checkbox 'on'
                    break;
                case 'text':
                case 'varchar':
                default:
                    // Assegna il valore grezzo. L'escaping verrà fatto da create/update.inc.php
                    // Usa html_entity_decode per assicurarti che i caratteri speciali dal textarea siano corretti
                    $object->$objectKey = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
                    break;
            }
        } else {
            // Logga solo se il campo non è uno di quelli mappati (per evitare doppi warning)
            if (!isset($fieldMapping[$key])) {
                 dol_syslog(__METHOD__ . " Warning: Field '$key' from POST not defined in object fields or mapping.", LOG_WARNING);
            }
        }
    }

    // Logga lo stato finale dell'oggetto *prima* di chiamare create_or_update
    dol_syslog(__METHOD__ . " Debug: Final object state before save: " . var_export($object, true), LOG_DEBUG);

    // **** AGGIUNGI QUESTO LOG ****
    dol_syslog(__METHOD__ . " Before calling create_or_update. DB Connected: " . ($db->connected ? 'Yes' : 'No'), LOG_DEBUG);
    // ****************************

    // Salva l'oggetto nel database (questo chiama create_or_update.inc.php)
    $result = $object->create_or_update($user); // Riga ~1347

    if ($result > 0) {
        setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
        // Reindirizza all'ID corretto (assicurati che $object->id sia impostato correttamente da create/update)
        $redirectId = $object->id ?: $id; // Usa l'ID dall'oggetto se disponibile
        if ($redirectId) {
             header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $redirectId);
             exit;
        } else {
             // Gestisci il caso in cui l'ID non è disponibile dopo il salvataggio
             setEventMessages($langs->trans("ErrorRedirectFailed"), null, 'errors');
             dol_syslog(__METHOD__ . " Error: Redirect failed, ID not available after save.", LOG_ERR);
        }
    } else {
        setEventMessages($langs->trans("ErrorFailedToSave"), $object->errors, 'errors');
        // Non reindirizzare in caso di errore, così l'utente vede i messaggi e i dati inseriti
    }
}

// Rimuovi il secondo blocco if ($action == 'save') da qui in poi...
// // Azione di salvataggio // <-- RIMUOVI QUESTO BLOCCO
// if ($action == 'save') { // <-- RIMUOVI QUESTO BLOCCO
//     // ... codice del secondo blocco ... // <-- RIMUOVI QUESTO BLOCCO
// } // <-- RIMUOVI QUESTO BLOCCO


// ... (codice successivo, inclusi gli script JavaScript) ...

// Rimuovi anche il blocco "Caricamento iniziale della pagina" che contiene il fetch duplicato
// // Caricamento iniziale della pagina // <-- RIMUOVI QUESTO BLOCCO
// if ($action != 'save') { // <-- RIMUOVI QUESTO BLOCCO
//    // ... codice fetch ... // <-- RIMUOVI QUESTO BLOCCO
// } // <-- RIMUOVI QUESTO BLOCCO

// ... (resto del codice) ...
print '<script>
function calculateTotal() {
    // Recupera i valori dai campi
    const digitalWorkplaceNumero = parseFloat(document.getElementById("digital_workplace_numero").value) || 0;
    const digitalCommEngage = parseFloat(document.getElementById("digital_comm_engage").value) || 0;
    const cloudCompAppServer = parseFloat(document.getElementById("cloud_comp_app_server").value) || 0;
    const cloudCompDbServer = parseFloat(document.getElementById("cloud_comp_db_server").value) || 0;
    const cloudCompWebServer = parseFloat(document.getElementById("cloud_comp_web_server").value) || 0;
    const cloudCompDbBackUp = parseFloat(document.getElementById("cloud_comp_db_back_up").value) || 0;

    // Calcola il totale
    const total = (digitalWorkplaceNumero * 2270) + digitalCommEngage + cloudCompAppServer + cloudCompDbServer + cloudCompWebServer + cloudCompDbBackUp;

    // Aggiorna il campo totale
    document.getElementById("totale").value = total.toFixed(2);
}

// Aggiungi un listener per ogni campo
document.getElementById("digital_workplace_numero").addEventListener("input", calculateTotal);
document.getElementById("digital_comm_engage").addEventListener("input", calculateTotal);
document.getElementById("cloud_comp_app_server").addEventListener("input", calculateTotal);
document.getElementById("cloud_comp_db_server").addEventListener("input", calculateTotal);
document.getElementById("cloud_comp_web_server").addEventListener("input", calculateTotal);
document.getElementById("cloud_comp_db_back_up").addEventListener("input", calculateTotal);
</script>';

print '<script>
document.getElementById("digital_workplace_numero").addEventListener("input", function () {
    // Recupera il valore del campo "postazioni"
    const postazioni = parseFloat(this.value) || 0;

    // Calcola il contributo
    const contributo = postazioni * 2270;

    // Aggiorna il campo "contributo"
    document.getElementById("digital_workplace").value = contributo.toFixed(2);
});
</script>';

print '<script>
function updateField(field) {
    const formData = new FormData();
    formData.append(field.name, field.value);

    fetch(window.location.href, {
        method: "POST",
        body: formData
    }).then(response => {
        if (!response.ok) {
            console.error("Failed to update field:", field.name);
        }
    }).catch(error => {
        console.error("Error updating field:", error);
    });
}
</script>';

print '<script>
document.addEventListener("DOMContentLoaded", function() {
    const mainForm = document.querySelector(\'form[action*="card.php"]\');

    if (mainForm) {
        mainForm.addEventListener("submit", function (e) {
            // Prevent default submission to handle manually
            e.preventDefault();

            console.log("Form submission intercepted");

            // Process all form elements
            const allFormElements = this.querySelectorAll("input, textarea, select");
            const formData = new FormData();

            allFormElements.forEach(element => {
                // Skip disabled elements and those starting with "prompt_"
                if (element.disabled || element.name.toLowerCase().startsWith("prompt_")) {
                    console.log(`Skipping field: ${element.name}`);
                    return;
                }

                // Convert field name to lowercase
                const lowercaseName = element.name.toLowerCase();

                // Add to FormData with lowercase name
                if (element.type === "checkbox" || element.type === "radio") {
                    if (element.checked) {
                        formData.append(lowercaseName, element.value);
                    }
                } else {
                    formData.append(lowercaseName, element.value);
                }

                console.log(`Added field: ${lowercaseName} with value: ${element.value}`);
            });

            // Convert FormData to URLSearchParams for the fetch request
            const urlParams = new URLSearchParams();
            formData.forEach((value, key) => {
                urlParams.append(key, value);
            });

            // Send the form with fetch
            fetch(mainForm.getAttribute("action"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: urlParams.toString(),
                credentials: "same-origin"
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                window.location.reload();
                return response.text();
            })
            .then(html => {
                // Replace the current page with the response
                document.open();
                document.write(html);
                document.close();
                console.log("Form submitted successfully");
                window.location.reload();
            })
            .catch(error => {
                console.error("Error submitting form:", error);
                alert("Errore durante il salvataggio. Si prega di riprovare.");
                // Re-enable form submission to try again
                mainForm.submit();
            });
        });
    } else {
        console.error("Main form not found");
    }
});
</script>';

print '<script>
document.addEventListener("DOMContentLoaded", function() {
    // Seleziona il form principale
    const form = document.querySelector("form");

    if (form) {
        // Aggiungi un event listener per l\'evento submit
        form.addEventListener("submit", function(e) {
            // Previeni l\'invio predefinito del form
            e.preventDefault();

            console.log("Form submission intercepted");

            // Crea un nuovo FormData object
            const formData = new FormData();

            // Itera su tutti gli elementi del form
            Array.from(form.elements).forEach(function(element) {
                // Salta gli elementi disabled, i bottoni e i campi che iniziano con "prompt_"
                if (element.disabled || element.type === "button" || element.type === "submit" ||
                    element.type === "reset" || element.name.startsWith("prompt_")) {
                    return;
                }

                // Converti il nome del campo in minuscolo
                const lowerCaseName = element.name.toLowerCase();

                // Aggiungi il campo a FormData con il nome convertito in minuscolo
                if (element.type === "checkbox" || element.type === "radio") {
                    if (element.checked) {
                        formData.append(lowerCaseName, element.value);
                    }
                } else if (element.type === "file") {
                    // Gestione speciale per i file
                    if (element.files.length > 0) {
                        formData.append(lowerCaseName, element.files[0]);
                    }
                } else {
                    formData.append(lowerCaseName, element.value);
                }
            });

            // Aggiungi il token CSRF e altri campi nascosti necessari
            if (form.querySelector("input[name=\'token\']")) {
                formData.append("token", form.querySelector("input[name=\'token\']").value);
            }

            // Logging per debugging
            console.log("Form data prepared for submission:");
            formData.forEach((value, key) => {
                console.log(`${key}: ${value}`);
            });

            // Crea una nuova richiesta POST tramite fetch
            fetch(form.action, {
                method: "POST",
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error("Network response was not ok");
                }
                return response.text();
            })
            .then(html => {
                // Sostituisci l\'intera pagina con la risposta
                document.open();
                document.write(html);
                document.close();
            })
            .catch(error => {
                console.error("Error:", error);
                alert("Si è verificato un errore durante l\'invio del form. Riprova.");
            });
        });
    } else {
        console.error("Form not found");
    }
});
</script>';



print '<script type="text/javascript">
document.addEventListener("DOMContentLoaded", function() {
    // Aggiungi listener a tutti i campi textarea normali (senza "prompt_")
    document.querySelectorAll("textarea.content-field").forEach(function(textarea) {
        textarea.addEventListener("input", function() {
            // Ottieni l\'ID del campo target
            const fieldKey = this.getAttribute("data-target");

            // Trova il campo prompt corrispondente
            const promptField = document.querySelector(`textarea[name="prompt_${fieldKey}"]`);

            if (promptField) {
                // Aggiungi il testo corrente al prompt
                const currentPromptContent = promptField.value;

                // Aggiungi il contenuto del textarea alla fine del prompt, separandolo con "***"
                const updatedPrompt = currentPromptContent + "\n*** Contenuto compilato dall\'utente:\n\n" + this.value;

                // Aggiorna il campo prompt
                promptField.value = updatedPrompt;
                console.log(`Updated prompt for ${fieldKey} with user content`);
            }
        });
    });
});
</script>';

if (isset($_POST['textarea_field_name'])) {
    // Remove newlines from the textarea input
    $textareaValue = str_replace("\n", ' ', $_POST['textarea_field_name']);
    $textareaValue = str_replace("\r", '', $textareaValue); // Remove carriage returns if present

    // Save the cleaned value to the database or process it further
    $object->textarea_field_name = $textareaValue;
}

// --- Separate DOCX handler (Keep this) ---
/*if ($action == 'generate_docx' && $user->rights->diagnosi_digitale->write) {
    $outputlangs = $langs; // Definisci la variabile outputlangs
    if (GETPOST('lang_id', 'aZ09')) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang(GETPOST('lang_id', 'aZ09'));
    }

    if ($object->id > 0) {
        $result = $object->generateDOCXDocument($outputlangs); // Chiamata corretta con parametri
        if ($result > 0) {
            setEventMessages($langs->trans("DOCXDocumentGeneratedSuccessfully"), null, 'mesgs');
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
            exit;
        } else {
            setEventMessages($langs->trans("ErrorFailedToGenerateDOCX", $object->error), $object->errors, 'errors');
        }
    } else {
        setEventMessages($langs->trans("ErrorRecordNotFound"), null, 'errors');
    }
    $action = '';
}*/
// --- End Separate DOCX handler ---

// --- Separate ODT handler (Add/Keep this if you have generate_odf action) ---
/*if ($action == 'generate_odf' && $user->rights->diagnosi_digitale->write) {
    $outputlangs = $langs;
    if (GETPOST('lang_id', 'aZ09')) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang(GETPOST('lang_id', 'aZ09'));
    }

    if ($object->id > 0) {
        $result = $object->generateODTDocument($outputlangs); // Correct call
        if ($result > 0) {
            setEventMessages($langs->trans("ODTDocumentGeneratedSuccessfully"), null, 'mesgs'); // Add translation
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
            exit;
        } else {
            setEventMessages($langs->trans("ErrorFailedToGenerateODT", $object->error), $object->errors, 'errors'); // Add translation
            dol_syslog(__METHOD__ . " Error generating ODT: " . $object->error, LOG_ERR);
        }
    } else {
         setEventMessages($langs->trans("ErrorRecordNotFound"), null, 'errors');
    }
    $action = '';
}*/
// --- End Separate ODT handler ---


// ... (rest of the code) ...

// Nella sezione dei pulsanti d'azione (attorno alla riga 1100)
print '<div class="tabsAction">';

// ... altri pulsanti ...

// Pulsante per generare DOCX
if ($user->rights->diagnosi_digitale->write) {
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=generate_docx&id='.$object->id.'">'.$langs->trans("Generate DOCX").'</a>';
    //generate PDF
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=generate_pdf&id='.$object->id.'">'.$langs->trans("Generate PDF").'</a>';
}

print '</div>';

// All'interno della sezione dove viene elaborata l'azione 'generate_docx'
if ($action == 'generate_docx' && $user->rights->diagnosi_digitale->write) {
    // Definisci la variabile $outputlangs prima di usarla
    $outputlangs = $langs; // Inizializza con l'oggetto $langs globale

    // Gestisci l'opzione di lingua specifica se passata come parametro
    if (GETPOST('lang_id', 'aZ09')) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang(GETPOST('lang_id', 'aZ09'));
    }

    // Ora puoi utilizzare $outputlangs in modo sicuro
    if ($object->id > 0) {
        $result = $object->generateDOCXDocument($outputlangs);
        // resto del codice...
    }
}

if ($action == 'generate_pdf' && $user->rights->diagnosi_digitale->write) {
    // Definisci la variabile $outputlangs prima di usarla
    $outputlangs = $langs; // Inizializza con l'oggetto $langs globale

    // Gestisci l'opzione di lingua specifica se passata come parametro
    if (GETPOST('lang_id', 'aZ09')) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang(GETPOST('lang_id', 'aZ09'));
    }

    // Ora puoi utilizzare $outputlangs in modo sicuro
    if ($object->id > 0) {
        $result = $object->generateDocument($object->model_pdf, $outputlangs);
        // resto del codice...
    }
}

$db->close();