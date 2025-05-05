<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
// Assuming a class for Perizia exists or will be created
// require_once __DIR__.'/class/perizia.class.php';

// Aggiungi l'inizializzazione del modulo
require_once DOL_DOCUMENT_ROOT . '/custom/industria40/core/init.inc.php';

// Ensure $langs is loaded for the main page
$langs->loadLangs(array("companies", "users", "industria40@industria40"));

$socid = GETPOSTINT('socid');
$periziaid = GETPOSTINT('periziaid'); // Assuming Perizia ID is integer rowid now
$periziaid_sanitized = $periziaid; // Variabile esplicita per garantire la coerenza
$action = GETPOST('action', 'alpha');
$mode = GETPOST('mode', 'alpha'); // 'view' or 'add_new'

// --- Action Handling ---
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
        // Redirect to view the newly added perizia
        header("Location: ".$_SERVER['PHP_SELF']."?socid=".$new_socid."&periziaid=".$new_perizia_id);
        exit;
    } else {
        $db->rollback();
        setEventMessages($langs->trans("ErrorFailedToAddPerizia").': '.$db->lasterror(), null, 'errors');
        dol_syslog("industria40index.php: Failed to add Perizia: ".$db->lasterror(), LOG_ERR);
        $mode = 'add_new'; // Stay in add mode on error
        $socid = $new_socid; // Keep selected company
    }
}

try {
    llxHeader('', $langs->trans('Industria40'));
    print load_fiche_titre($langs->trans('Industria40'), '', 'title_generic.png@industria40');

    $formcompany = new FormCompany($db);

    // --- Company Selection ---
    // Check if any companies exist using SQL
    $sql_check_company = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE entity IN (".getEntity('societe').") LIMIT 1";
    $resql_check_company = $db->query($sql_check_company);
    $num_companies = $db->num_rows($resql_check_company);

    if ($num_companies == 0) {
         print '<div class="warning">'.$langs->trans("NoThirdPartyDefined").' '.$langs->trans("PleaseCreateOneFirst").'</div>';
    } else {
        // Always show company selection unless adding new? Or keep it simple? Keep it simple for now.
        print '<form name="select_company_form" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
        print '<input type="hidden" name="action" value="set_company">'; // Action for company change
        print $langs->trans("SelectCompany").': ';
        print $formcompany->select_company($socid, 'socid', '', 1);
        print ' <button type="submit" class="button">'.$langs->trans("Select").'</button>';
        print '</form><br>';
    }

    // --- Mode Handling ---
    if ($socid > 0) { // Only proceed if a company is selected

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
        else { // Default mode is 'view'
            print '<div style="margin-bottom: 10px;">';
            // --- Perizia Selection Dropdown ---
            $sql_perizie = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."industria40_perizia";
            $sql_perizie.= " WHERE fk_soc = ".$socid." AND entity IN (".getEntity('industria40_perizia', 1).")"; // Assuming perizia has entity
            $sql_perizie.= " ORDER BY ref ASC";

            $resql_perizie = $db->query($sql_perizie);
            $show_add_button = false; // Flag to control Add button display

            if ($resql_perizie) {
                $num_perizie = $db->num_rows($resql_perizie);
                dol_syslog("industria40index.php: Found ".$num_perizie." perizie for socid ".$socid, LOG_DEBUG);

                print '<form name="select_perizia_form" action="'.$_SERVER["PHP_SELF"].'" method="POST" style="display: inline-block;">';
                print '<input type="hidden" name="socid" value="'.$socid.'">'; // Keep socid selected
                print $langs->trans("SelectExistingPerizia").': ';
                print '<select name="periziaid" class="flat">';
                print '<option value="0">'.$langs->trans("SelectOne").'</option>';
                if ($num_perizie > 0) {
                    while ($obj = $db->fetch_object($resql_perizie)) {
                        $selected = ($obj->rowid == $periziaid) ? ' selected' : '';
                        print '<option value="'.$obj->rowid.'"'.$selected.'>'.dol_escape_htmltag($obj->ref).'</option>';
                    }
                }
                print '</select>';
                print ' <button type="submit" class="button">'.$langs->trans("View").'</button>';
                print '</form>';

                $show_add_button = true; // Show add button if query succeeded

            } else {
                // Generic error handling is sufficient now
                $db_error = $db->lasterror();
                dol_syslog("industria40index.php: Failed to fetch perizie: ".$db_error, LOG_ERR);
                print '<div class="error">'.$langs->trans("ErrorFetchingPerizie").': '.$db_error.'</div>';
                // Do not show add button if there's a general DB error fetching existing ones
            }

            // --- Add New Button ---
            if ($show_add_button) { // Remove the permission check for now
               print ' &nbsp; <a href="'.$_SERVER["PHP_SELF"].'?socid='.$socid.'&mode=add_new" class="button button-add">'.$langs->trans("AddNewPerizia").'</a>';
            }

            print '</div>'; // End div for selection/add button

            // --- File Manager Display ---
            if ($periziaid > 0) {
                // Fetch Perizia ref to display
                $sql_get_ref = "SELECT ref FROM ".MAIN_DB_PREFIX."industria40_perizia WHERE rowid = ".$periziaid;
                $resql_get_ref = $db->query($sql_get_ref);
                $perizia_ref = $langs->trans("Unknown");
                if ($resql_get_ref && $db->num_rows($resql_get_ref) > 0) {
                    $obj_ref = $db->fetch_object($resql_get_ref);
                    $perizia_ref = $obj_ref->ref;
                }

                print '<h2>'.$langs->trans("FileManager").'</h2>';
                print '<div>'.$langs->trans("ManagingFilesFor").': <strong>'.dol_escape_htmltag($perizia_ref).'</strong></div>';

                // Setta il flag del file manager per sapere se i file esistono
                $files_exist = false;

                // Assicurati che tutte le directory necessarie esistano
                $upload_dir = DOL_DATA_ROOT . '/industria40/documents/' . $socid . '/' . $periziaid;
                if (!is_dir($upload_dir)) {
                    if (dol_mkdir($upload_dir) >= 0) {
                        @chmod($upload_dir, 0775);
                        dol_syslog("industria40index.php: Created upload directory: " . $upload_dir, LOG_DEBUG);
                    } else {
                        dol_syslog("industria40index.php: Failed to create upload directory: " . $upload_dir, LOG_ERR);
                        print '<div class="error">' . $langs->trans("ErrorCannotCreateDir", $upload_dir) . '</div>';
                    }
                }

                // Include il file_manager.php con gestione errori
                $file_manager_path = __DIR__ . '/file_manager.php';
                if (!file_exists($file_manager_path)) {
                    dol_syslog("industria40index.php: file_manager.php not found at path: " . $file_manager_path, LOG_ERR);
                    print '<div class="error">Error: file_manager.php not found.</div>';
                } else {
                    dol_syslog("industria40index.php: Including file_manager.php for socid: " . $socid . ", periziaid: " . $periziaid, LOG_DEBUG);
                    try {
                        include $file_manager_path;
                    } catch (Exception $e) {
                        dol_syslog("industria40index.php: Exception during file_manager.php inclusion: " . $e->getMessage(), LOG_ERR);
                        print '<div class="error">Error including file manager: ' . $e->getMessage() . '</div>';
                    }
                }

            } elseif ($socid > 0 && isset($num_perizie) && $num_perizie > 0) { // Check if $num_perizie is set
                 print '<div class="info">'.$langs->trans("PleaseSelectPeriziaToShowFiles").'</div>';
            } elseif ($socid > 0 && isset($num_perizie) && $num_perizie == 0) { // Check if $num_perizie is set
                 print '<div class="info">'.$langs->trans("NoPerizieFoundForCompany").' '.$langs->trans("UseAddButton").'</div>';
            }
            // No specific message needed here if the table didn't exist, as the warning is shown above.
        }
    } else {
        // Message if no company is selected (and companies exist)
        if ($num_companies > 0) {
            print '<div class="info">'.$langs->trans("PleaseSelectCompany").'</div>';
        }
    }

    llxFooter();
} catch (Exception $e) {
    dol_syslog("industria40index.php: Exception caught - " . $e->getMessage(), LOG_ERR);
    print '<div class="error">An error occurred: ' . $e->getMessage() . '</div>';
    // Ensure footer is called even on error if header was called
    // Check if headers already sent before calling footer again
    if (!headers_sent()) {
         llxFooter();
    }
}

// Make sure DB connection is closed only once
if (!empty($db) && $db->connected) {
    $db->close();
}