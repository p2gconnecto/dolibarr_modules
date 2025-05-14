<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Assuming a class for Perizia exists or will be created
// require_once __DIR__.'/class/perizia.class.php';

// Aggiungi l'inizializzazione del modulo
require_once DOL_DOCUMENT_ROOT . '/custom/industria40/core/init.inc.php';
// Include the new functions file
//require_once DOL_DOCUMENT_ROOT . '/custom/industria40/file_manager.php'; // CORRETTO: Percorso assoluto completo
require_once DOL_DOCUMENT_ROOT . '/custom/industria40/file_manager_functions.php'; // CORRETTO: Percorso assoluto completo


// Ensure $langs is loaded for the main page
$langs->loadLangs(array("companies", "users", "industria40@industria40", "file_manager@industria40"));

// --- Debugging GET and POST data ---

$socid = GETPOSTINT('socid');
$periziaid = GETPOSTINT('periziaid');
$periziaid_sanitized = $periziaid;
$action = GETPOST('action', 'alpha');
$mode = GETPOST('mode', 'alpha');
$view_mode = GETPOST('view_mode', 'alpha'); // New parameter for view switching
$file_name_param = GETPOST('file_name', 'alpha'); // For AI view

// Call load_dotenv() early if it's in file_manager_functions.php and needed globally
if (function_exists('load_dotenv')) {
    load_dotenv();
}
// Define $modulepart globally for document.php URLs
$modulepart = 'industria40';


// --- Action Handling (Perizia Add/Edit - Keep existing logic if any) ---
if ($action == 'addperizia' && !empty(GETPOST('ref', 'alpha')) && GETPOSTINT('socid_new') > 0) { // Remove the permission check
    $db->begin();
    $new_ref = GETPOST('ref', 'alpha');
    $new_socid = GETPOSTINT('socid_new');

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."industria40_perizia (ref, fk_soc, entity, datec, fk_user_creat)";
    $sql.= " VALUES ('".$db->escape($new_ref)."', ".$new_socid.", ".$conf->entity.", '".$db->idate(dol_now())."', ".$user->id.")";

    dol_syslog("industria40index.php: Add Perizia SQL: ".$sql, LOG_DEBUG);
    $resql = $db->query($sql);
    if ($resql) {
        $new_perizia_id = $db->last_insert_id(MAIN_DB_PREFIX."industria40_perizia");
        $db->commit();
        setEventMessages($langs->trans("PeriziaAdded", $new_ref), null, 'mesgs');
        // Redirect to view the newly added perizia, perhaps in manage mode
        header("Location: ".$_SERVER['PHP_SELF']."?socid=".$new_socid."&periziaid=".$new_perizia_id."&view_mode=manage");
        exit;
    } else {
        $db->rollback();
        setEventMessages($langs->trans("ErrorFailedToAddPerizia").': '.$db->lasterror(), null, 'errors');
        dol_syslog("industria40index.php: Failed to add Perizia: ".$db->lasterror(), LOG_ERR);
        $mode = 'add_new'; // Stay in add mode on error
        $socid = $new_socid; // Keep selected company
    }
}

// Aggiungi controlli aggiuntivi per il debugging
/*
dol_syslog("industria40index.php: Script execution started", LOG_DEBUG);
dol_syslog("industria40index.php: PHP version: " . PHP_VERSION, LOG_DEBUG);
dol_syslog("industria40index.php: Server software: " . (isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown'), LOG_DEBUG);
*/
try {
    llxHeader('', $langs->trans('Industria40FileManager'));
    dol_syslog("industria40index.php: After llxHeader call", LOG_DEBUG);
    print load_fiche_titre($langs->trans('Industria40FileManager'), '', 'title_generic.png@industria40');

    // --- Basic error handling for the script ---
    // Check access permissions (generic approach)
    if (!$user->rights->societe->lire) {
        dol_syslog("industria40index.php: Access denied - User doesn't have societe->lire permission", LOG_WARNING);
        accessforbidden('You need permission to read companies');
        exit;
    }

    $formcompany = new FormCompany($db);

    // --- Company Selection ---
    // Check if any companies exist using SQL
    $sql_check_company = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE entity IN (".getEntity('societe').") LIMIT 1";
    $resql_check_company = $db->query($sql_check_company);
    $num_companies = $db->num_rows($resql_check_company);

    // Modifica la parte di selezione azienda per usare un approccio più semplice
    if ($num_companies == 0) {
         print '<div class="warning">'.$langs->trans("NoThirdPartyDefined").' '.$langs->trans("PleaseCreateOneFirst").'</div>';
    } else {
        // Versione semplificata del form di selezione
        print '<form id="selectCompanyForm" name="select_company_form" action="'.$_SERVER["PHP_SELF"].'" method="GET">'; // Cambiato da POST a GET
        print '<input type="hidden" name="periziaid" value="0">';
        print '<input type="hidden" name="view_mode" value="">';
        print $langs->trans("SelectCompany").': ';

        // Usa il select standard senza personalizzazioni
        print $formcompany->select_company($socid, 'socid', '', 1, 0, 0, array(), 0, 'minwidth300', '');

        print ' <button type="submit" class="button">'.$langs->trans("Select").'</button>';
        print '</form>';

        // Script JS più semplice
        print '<script>
            jQuery(document).ready(function() {
                console.log("Document ready in industria40index.php");
            });
        </script>';

        print '<br>';
    }



    // --- Mode Handling ---
    if ($socid > 0) {
        dol_syslog("industria40index.php: Entered if (\$socid > 0) block. socid = " . $socid, LOG_DEBUG);

        // --- Add New Perizia Mode ---
        if ($mode == 'add_new') {
            // REMOVED PERMISSION CHECK - Allow anyone to add new
            print '<h2>'.$langs->trans("AddNewPerizia").'</h2>';
            print '<form name="add_perizia_form" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
            print '<input type="hidden" name="action" value="addperizia">';
            print '<input type="hidden" name="mode" value="add_new">'; // Keep mode if validation fails

            print '<table class="border">';
            // Company selection for the new Perizia (pre-filled with current $socid)
            print '<tr><td>'.$langs->trans("SelectCompany").'</td><td>';
            print $formcompany->select_company($socid, 'socid_new', '', 1); // Use different name 'socid_new'
            print '</td></tr>';
            // Reference for the new Perizia
            print '<tr><td>'.$langs->trans("Reference").' / '.$langs->trans("Name").'</td><td>';
            print '<input type="text" name="ref" value="'.dol_escape_htmltag(GETPOST('ref', 'alpha')).'" size="30" required>';
            print '</td></tr>';
            print '</table>';

            print '<div class="center">';
            print '<button type="submit" class="button button-save">'.$langs->trans("Save").'</button>';
            print ' &nbsp; ';
            print '<a href="'.$_SERVER["PHP_SELF"].'?socid='.$socid.'" class="button button-cancel">'.$langs->trans("Cancel").'</a>';
            print '</div>';
            print '</form>';
        }
        // --- View/Select Existing Perizia Mode ---
        else {
            print '<div style="margin-bottom: 10px; display:flex; align-items: center; gap: 10px;">'; // Flex container for selects and buttons
            // --- Perizia Selection Dropdown ---
            $sql_perizie = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."industria40_perizia";
            $sql_perizie.= " WHERE fk_soc = ".$socid." AND entity IN (".getEntity('industria40_perizia', 1).")";
            $sql_perizie.= " ORDER BY ref ASC";

            $resql_perizie = $db->query($sql_perizie);
            $num_perizie = 0; // Initialize
            $query_successful = false; // Flag to track if the perizie query was successful

            if ($resql_perizie) {
                $query_successful = true;
                $num_perizie = $db->num_rows($resql_perizie);
                dol_syslog("industria40index.php: Found ".$num_perizie." perizie for socid ".$socid, LOG_DEBUG);

                print '<form name="select_perizia_form" action="'.$_SERVER["PHP_SELF"].'" method="POST" style="display: inline-block;">';
                print '<input type="hidden" name="socid" value="'.$socid.'">';
                print $langs->trans("SelectExistingPerizia").': ';
                print '<select name="periziaid" class="flat">'; // Removed onchange="this.form.submit()"
                print '<option value="0">'.$langs->trans("SelectOne").'</option>';
                if ($num_perizie > 0) {
                    while ($obj = $db->fetch_object($resql_perizie)) {
                        $selected = ($obj->rowid == $periziaid) ? ' selected' : '';
                        print '<option value="'.$obj->rowid.'"'.$selected.'>'.dol_escape_htmltag($obj->ref).'</option>';
                    }
                }
                print '</select>';
                print ' <button type="submit" class="button">'.$langs->trans("Select").'</button>'; // Added explicit select button
                print '</form>';
            } else {
                // Generic error handling is sufficient now
                $db_error = $db->lasterror();
                dol_syslog("industria40index.php: Failed to fetch perizie: ".$db_error, LOG_ERR);
                print '<div class="error">'.$langs->trans("ErrorFetchingPerizie").': '.$db_error.'</div>';
                // Do not show add button if there's a general DB error fetching existing ones
                // $query_successful remains false
            }

            // --- Add New Perizia Button ---
            // if ($user->rights->industria40->creer) { // Or appropriate permission
               print '<a href="'.$_SERVER["PHP_SELF"].'?socid='.$socid.'&mode=add_new" class="button button-add">'.$langs->trans("AddNewPerizia").'</a>';
            // }
            print '</div>';

            // --- File Manager Views (Upload, Manage, AI, Drawflow) OR Messages ---
            if ($periziaid > 0) {
                $sql_get_ref = "SELECT ref FROM ".MAIN_DB_PREFIX."industria40_perizia WHERE rowid = ".$periziaid;
                $resql_get_ref = $db->query($sql_get_ref);
                $perizia_ref = $langs->trans("Unknown");
                if ($resql_get_ref && $db->num_rows($resql_get_ref) > 0) {
                    $obj_ref = $db->fetch_object($resql_get_ref);
                    $perizia_ref = $obj_ref->ref;
                }

                print '<h2>'.$langs->trans("FileManagerForPerizia", dol_escape_htmltag($perizia_ref)).'</h2>';

                // Navigation for different views
                print '<div class="tabs">';
                print '<a href="'.$_SERVER['PHP_SELF'].'?socid='.$socid.'&periziaid='.$periziaid.'&view_mode=upload" class="tab'.($view_mode == 'upload' ? ' active' : '').'">'.$langs->trans("UploadFiles").'</a>';
                print '<a href="'.$_SERVER['PHP_SELF'].'?socid='.$socid.'&periziaid='.$periziaid.'&view_mode=manage" class="tab'.($view_mode == 'manage' || empty($view_mode) ? ' active' : '').'">'.$langs->trans("ManageFiles").'</a>';
                // AI view might be better accessed from a specific file in manage view, but a general link can be here too.
                // print '<a href="'.$_SERVER['PHP_SELF'].'?socid='.$socid.'&periziaid='.$periziaid.'&view_mode=ai" class="tab'.($view_mode == 'ai' ? ' active' : '').'">'.$langs->trans("AIInteractions").'</a>';
                print '<a href="'.$_SERVER['PHP_SELF'].'?socid='.$socid.'&periziaid='.$periziaid.'&view_mode=drawflow" class="tab'.($view_mode == 'drawflow' ? ' active' : '').'">'.$langs->trans("DrawflowMapping").'</a>';
                print '</div>';

                // Define $upload_dir_base and $upload_dir to be used by included views
                $upload_dir_base = DOL_DATA_ROOT . '/industria40/documents';
                $upload_dir = $upload_dir_base . '/' . $socid . '/' . $periziaid_sanitized;

                // Ensure base directories exist (idempotent checks)
                if (!is_dir($upload_dir_base)) dol_mkdir($upload_dir_base, 0775);
                if (!is_dir($upload_dir_base . '/' . $socid)) dol_mkdir($upload_dir_base . '/' . $socid, 0775);
                if (!is_dir($upload_dir)) dol_mkdir($upload_dir, 0775);

                // Define $form_action_url for views
                $form_action_url = $_SERVER['PHP_SELF'] . '?socid=' . $socid . '&periziaid=' . $periziaid_sanitized;

                // Include the specific view file
                // The included file will handle its specific $action values.
                // The $action variable from GETPOST is available to them.
                try {
                    if ($view_mode == 'upload') {
                        print '<h3>'.$langs->trans("UploadView").'</h3>';
                        $include_file = __DIR__ . '/file_manager_upload_view.php';
                        dol_syslog("industria40index.php: Attempting to include file: " . $include_file, LOG_DEBUG);

                        if (file_exists($include_file)) {
                            include $include_file;
                        } else {
                            dol_syslog("industria40index.php: Include file not found: " . $include_file, LOG_ERROR);
                            print '<div class="error">File not found: file_manager_upload_view.php</div>';
                        }
                    } elseif ($view_mode == 'ai' && !empty($file_name_param)) {
                        print '<h3>'.$langs->trans("AIInteractionViewFor", $file_name_param).'</h3>';
                        $file_name_for_ai_view = $file_name_param;
                        include __DIR__ . '/file_manager_ai_view.php';
                    } elseif ($view_mode == 'drawflow') {
                        print '<h3>'.$langs->trans("DrawflowView").'</h3>';
                        include __DIR__ . '/file_manager_drawflow_view.php';
                    } else { // Default to manage view
                        $view_mode = 'manage'; // Ensure $view_mode is set for manage
                        print '<h3>'.$langs->trans("ManageFilesView").'</h3>';
                        include __DIR__ . '/file_manager_manage_view.php';
                    }
                } catch (Exception $e) {
                    dol_syslog("industria40index.php: Exception during view inclusion (" . $view_mode . "): " . $e->getMessage(), LOG_ERR);
                    print '<div class="error">Error including view: ' . $e->getMessage() . '</div>';
                }
            } else { // No perizia is currently selected ($periziaid is 0 or not set)
                if ($query_successful) { // Only show these messages if the perizie query was successful
                    if ($num_perizie > 0) {
                        print '<div class="info">'.$langs->trans("PleaseSelectPeriziaToShowFiles").'</div>';
                    } else { // $num_perizie is 0
                        print '<div class="info">'.$langs->trans("NoPerizieFoundForCompany").' '.$langs->trans("UseAddButton").'</div>';
                    }
                }
                // If !$query_successful, the database error message was already printed above.
                // No need to print "NoPerizieFoundForCompany" in that case, as it might be misleading.
            }
        } // <-- questa parentesi chiude l'else della view/select perizia
    } else {
        dol_syslog("industria40index.php: Entered else block for (\$socid > 0). socid = " . $socid, LOG_DEBUG);
        if ($num_companies > 0) {
            print '<div class="info">'.$langs->trans("PleaseSelectCompany").'</div>';
        }
    } // <-- chiusura if ($socid > 0)

    llxFooter();
} catch (Exception $e) {
    dol_syslog("industria40index.php: Exception caught - " . $e->getMessage(), LOG_ERR);
    print '<div class="error">An error occurred: ' . $e->getMessage() . '</div>';
    if (!headers_sent()) {
        llxFooter();
    }
}

if (!empty($db) && $db->connected) {
    $db->close();
}
?>
<script>
// Sistema migliorato per garantire che l'inizializzazione avvenga solo quando DrawflowManager è pronto
(function() {
    // Inizializza la coda dei callback se non esiste
    if (!window.DrawflowManagerCallbacks) {
        window.DrawflowManagerCallbacks = [];
    }

    // Definisci la funzione di callback per quando DrawflowManager è pronto
    // Usiamo un flag per evitare chiamate multiple
    var callbackCalled = false;
    function readyCallback() {
        // Previeni chiamate multiple
        if (callbackCalled) return;
        callbackCalled = true;

        console.log("DrawflowManager è pronto! [" + new Date().toISOString() + "]");

        // Utilizziamo il drawflowConfig globale già definito nella pagina
        if (window.drawflowConfig) {
            console.log("Inizializzazione DrawflowManager con configurazione");
            DrawflowManager.init(window.drawflowConfig);
        } else {
            console.error("Configurazione DrawflowManager non trovata!");
        }
    }

    // Se DrawflowManager è già pronto, chiamiamo subito il callback
    if (window.DrawflowManager && window.DrawflowManager.isReady === true) {
        console.log("DrawflowManager già pronto, inizializzazione immediata");
        // Usa setTimeout per evitare ricorsione
        setTimeout(readyCallback, 0);
    }
    // Altrimenti, aggiungiamo il nostro callback alla coda
    else {
        console.log("DrawflowManager non ancora pronto, callback messo in coda");
        window.DrawflowManagerCallbacks.push(readyCallback);

        // Impostiamo onDrawflowManagerReady come backup, ma solo se non è già definito
        if (!window.onDrawflowManagerReady) {
            window.onDrawflowManagerReady = readyCallback;
        }
    }
})();
</script>
