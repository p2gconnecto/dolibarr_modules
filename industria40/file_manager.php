<?php
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php'; // Needed for FormCompany
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php'; // Needed for FormCompany

// Ensure $langs is available (it should be from main.inc.php)
global $db, $conf, $user; // Removed $langs

// --- Function to load .env file ---
/**
 * Loads environment variables from a .env file located in DOL_DOCUMENT_ROOT.
 * Basic implementation, handles simple KEY=VALUE pairs and comments.
 */
function load_dotenv() {
    // Clear PHP's file status cache before checking
    clearstatcache();
    dol_syslog("file_manager.php: Cleared stat cache.", LOG_DEBUG);

    // Modified path to look inside the 'custom' directory
    $dotenv_path = DOL_DOCUMENT_ROOT . '/custom/env';
    dol_syslog("file_manager.php: Attempting to load env file from: " . $dotenv_path, LOG_DEBUG); // Log the path being checked

    // --- Add more debugging ---
    $doc_root = DOL_DOCUMENT_ROOT;
    dol_syslog("file_manager.php: Checking base directory: '$doc_root'", LOG_DEBUG);
    if (is_dir($doc_root)) {
        dol_syslog("file_manager.php: PHP is_dir() confirms '$doc_root' is a directory.", LOG_DEBUG);
        // List directory contents if possible (might fail due to permissions/open_basedir)
        $dir_contents = @scandir($doc_root);
        if ($dir_contents !== false) {
            dol_syslog("file_manager.php: Contents of '$doc_root': " . implode(', ', $dir_contents), LOG_DEBUG);
        } else {
            dol_syslog("file_manager.php: Could not scandir '$doc_root'.", LOG_WARNING);
        }
    } else {
        dol_syslog("file_manager.php: PHP is_dir() reports '$doc_root' is NOT a directory.", LOG_WARNING);
    }
    // Check realpath
    $real_path = realpath($dotenv_path);
    if ($real_path !== false) {
        dol_syslog("file_manager.php: PHP realpath() resolves '$dotenv_path' to '$real_path'.", LOG_DEBUG);
    } else {
        dol_syslog("file_manager.php: PHP realpath() failed to resolve '$dotenv_path'. It might not exist or be accessible.", LOG_WARNING);
    }
    // Check current working directory
    $cwd = getcwd();
    dol_syslog("file_manager.php: PHP current working directory (getcwd()): '$cwd'", LOG_DEBUG);
    // --- End added debugging ---


    // Explicitly check if PHP thinks the file exists at this path
    if (file_exists($dotenv_path)) {
        dol_syslog("file_manager.php: PHP file_exists() confirms '$dotenv_path' exists.", LOG_DEBUG);
    } else {
        dol_syslog("file_manager.php: PHP file_exists() reports '$dotenv_path' does NOT exist.", LOG_WARNING);
        // If file_exists is false, no point trying file_get_contents
        dol_syslog("file_manager.php: Skipping file_get_contents because file_exists returned false.", LOG_WARNING);
        return; // Exit the function early
    }

    // Try reading the file directly and catch potential errors/warnings
    $file_content = false;
    $error_message = '';
    try {
        // Use error suppression (@) temporarily as file_get_contents can raise warnings
        // that we want to catch and log more gracefully.
        $file_content = @file_get_contents($dotenv_path);
        if ($file_content === false) {
            $last_error = error_get_last();
            $error_message = $last_error ? $last_error['message'] : 'Unknown error';
            dol_syslog("file_manager.php: file_get_contents failed for $dotenv_path: " . $error_message, LOG_WARNING);
            // No return here, let the outer if handle it.
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        dol_syslog("file_manager.php: Exception caught while trying to read .env file at $dotenv_path: " . $error_message, LOG_ERR);
    }

    if ($file_content !== false) {
        dol_syslog("file_manager.php: .env file content read successfully from $dotenv_path", LOG_DEBUG);
        $lines = explode("\n", $file_content); // Split content into lines
        $key_found_in_dotenv = false; // Flag to check if the specific key is found
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) { // Skip empty lines and comments
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                // Remove surrounding quotes from value if present
                if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match("/^'(.*)'$/", $value, $matches)) {
                    $value = $matches[1];
                }
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value; // Also make it available in $_SERVER for wider compatibility
                if ($key === 'OPENAI_API_KEY') { // Example specific key check
                    $key_found_in_dotenv = true;
                }
            }
        }
        if (!$key_found_in_dotenv) {
            dol_syslog("file_manager.php: Specific key 'OPENAI_API_KEY' not found in .env file at $dotenv_path (or file was empty/misformatted).", LOG_INFO);
        }
    } else {
         // Log already happened inside the try-catch block if reading failed
         // Added a note about the file_exists check result
         dol_syslog("file_manager.php: Could not get content from .env file at $dotenv_path even though file_exists() might have returned true. Check previous log messages for specific file_get_contents error (permissions, open_basedir, SELinux?).", LOG_WARNING);
    }
}
// --- End function to load .env file ---

// Log DOL_DOCUMENT_ROOT before loading .env
dol_syslog("file_manager.php: DOL_DOCUMENT_ROOT is defined as: " . DOL_DOCUMENT_ROOT, LOG_DEBUG);

// Load .env variables
load_dotenv();

// Get the selected company ID and Perizia ID from the parent script
$socid = GETPOSTINT('socid');
$periziaid = GETPOSTINT('periziaid'); // Get Perizia ID

// Determine the base URL for links (should be industria40index.php)
// $_SERVER['PHP_SELF'] in an included file refers to the parent script.
$base_link_url = $_SERVER['PHP_SELF'];
$formcompany = new FormCompany($db); // Initialize FormCompany

if (!$socid) {
    print '<h3>Seleziona un\'Azienda</h3>';
    print '<form name="select_company_form_fm" action="'.$base_link_url.'" method="POST">';
    // When company changes, periziaid and view_mode should be reset
    print '<input type="hidden" name="periziaid" value="0">';
    print '<input type="hidden" name="view_mode" value="">'; // Reset view mode
    print 'Azienda: ';
    // Parameters for select_company: $selected, $htmlname, $filter = '', $showempty = 0, $showtype = 0, $forcecombo = 0, $moreattr = array(), $exclude = 0, $outtpl = '', $outtplkey = '', $onchange = ''
    print $formcompany->select_company($socid, 'socid', '', 1, 0, 0, array(), 0, '', '', 'this.form.submit()');
    // The submit button is not strictly necessary if onchange submits the form, but can be a fallback.
    // print ' <button type="submit" class="button">Seleziona</button>';
    print '</form>';
    return; // Stop execution of this included file
}

if (empty($periziaid)) {
    // Company is selected, but Perizia is not. Display Perizia selection.
    print '<h3>Seleziona una Perizia per l\'Azienda o Aggiungine una Nuova</h3>';

    $sql_perizie = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."industria40_perizia";
    $sql_perizie.= " WHERE fk_soc = ".$db->escape($socid);
    // Assuming 'industria40_perizia' is a custom object type registered with Dolibarr's entity system.
    // If not, the entity check might need adjustment or removal if not multi-entity.
    // For safety, using getEntity if available, otherwise default to $conf->entity.
    if (function_exists('getEntity')) {
        $sql_perizie.= " AND entity IN (".getEntity('industria40_perizia', 1).")";
    } else {
         $sql_perizie.= " AND entity = ".$conf->entity;
    }
    $sql_perizie.= " ORDER BY ref ASC";

    dol_syslog("file_manager.php: Querying perizie for socid $socid: $sql_perizie", LOG_DEBUG);
    $resql_perizie = $db->query($sql_perizie);

    if ($resql_perizie) {
        $num_perizie = $db->num_rows($resql_perizie);
        if ($num_perizie > 0) {
            print '<div class="info">Seleziona una Perizia Esistente:</div>';
            print '<ul class="nobullet" style="margin-bottom: 20px; list-style-type: none; padding-left: 0;">';
            while ($obj_perizia = $db->fetch_object($resql_perizie)) {
                $select_perizia_link = $base_link_url . '?socid=' . $socid . '&periziaid=' . $obj_perizia->rowid;
                // Preserve view_mode if it was set, default to 'manage'
                $current_view_mode = GETPOST('view_mode', 'alpha');
                if (!empty($current_view_mode)) {
                    $select_perizia_link .= '&view_mode=' . $current_view_mode;
                } else {
                    $select_perizia_link .= '&view_mode=manage'; // Default to manage view
                }
                print '<li style="margin-bottom: 5px;"><a href="' . $select_perizia_link . '" class="button">' . dol_escape_htmltag($obj_perizia->ref) . '</a></li>';
            }
            print '</ul>';
        } else {
            print '<div class="warning">Nessuna Perizia trovata per questa Azienda.</div>';
        }
        $db->free($resql_perizie);
    } else {
        dol_print_error($db);
    }

    // Link to add a new perizia for the current company
    // This link directs to industria40index.php, which should handle the 'add_new' mode.
    $add_new_perizia_link = $base_link_url . '?socid=' . $socid . '&mode=add_new';
    print '<div style="margin-top: 20px;">';
    print 'Oppure: <a href="' . $add_new_perizia_link . '" class="button button-add">Aggiungi Nuova Perizia</a>';
    print '</div>';

    return; // Stop further execution of file_manager.php
}


// If we reach here, both socid and periziaid are set.
// Use the periziaid as-is since it's already an integer from GETPOSTINT
$periziaid_sanitized = $periziaid;

// Aggiungi try-catch intorno al codice principale per gestire gli errori
try {
    // Load language file if not already loaded by index
    // if (empty($langs->trans("FileManager"))) { // Check if translation is loaded
    //     $langs->load("industria40@industria40");
    // }

    $action = GETPOST('action', 'alpha');
    $file_to_remove = GETPOST('file_to_remove', 'alpha'); // Get specific file to remove

    // Define the upload directory using socid and periziaid
    $upload_dir_base = DOL_DATA_ROOT . '/industria40/documents'; // Base directory for the module
    $upload_dir = $upload_dir_base . '/' . $socid . '/' . $periziaid_sanitized; // Specific path (nota: rimosso trailing slash)

    // Aggiungi log dettagliati all'inizio per verificare variabili e percorsi
    dol_syslog("file_manager.php: *** DEBUG DETAILS START ***", LOG_DEBUG);
    dol_syslog("file_manager.php: DOL_DATA_ROOT = " . DOL_DATA_ROOT, LOG_DEBUG);
    dol_syslog("file_manager.php: socid = " . $socid, LOG_DEBUG);
    dol_syslog("file_manager.php: periziaid = " . $periziaid, LOG_DEBUG);
    dol_syslog("file_manager.php: periziaid_sanitized = " . $periziaid_sanitized, LOG_DEBUG);
    dol_syslog("file_manager.php: upload_dir_base = " . $upload_dir_base, LOG_DEBUG);
    dol_syslog("file_manager.php: upload_dir = " . $upload_dir, LOG_DEBUG);

    // Verifica se il modulo è nella lista dei moduli abilitati
    if (isset($conf->modules) && is_array($conf->modules)) {
        dol_syslog("file_manager.php: conf->modules = " . implode(', ', $conf->modules), LOG_DEBUG);
        if (in_array('industria40', $conf->modules)) {
            dol_syslog("file_manager.php: Module 'industria40' is registered in conf->modules", LOG_DEBUG);
        } else {
            dol_syslog("file_manager.php: WARNING - Module 'industria40' is NOT registered in conf->modules", LOG_WARNING);
        }
    } else {
        dol_syslog("file_manager.php: WARNING - conf->modules not properly defined", LOG_WARNING);
    }

    // Verifica le directory multidir_output
    if (isset($conf->industria40) && isset($conf->industria40->multidir_output)) {
        dol_syslog("file_manager.php: conf->industria40->multidir_output[entity] = " .
            (isset($conf->industria40->multidir_output[$conf->entity]) ? $conf->industria40->multidir_output[$conf->entity] : 'NOT SET'), LOG_DEBUG);
    } else {
        dol_syslog("file_manager.php: WARNING - conf->industria40 or multidir_output not defined", LOG_WARNING);

        // Configurazione esplicita se mancante
        if (!isset($conf->industria40)) {
            $conf->industria40 = new stdClass();
        }

        if (!isset($conf->industria40->multidir_output)) {
            $conf->industria40->multidir_output = array();
        }

        $conf->industria40->multidir_output[$conf->entity] = DOL_DATA_ROOT . '/industria40';
        $conf->industria40->dir_output = $conf->industria40->multidir_output[$conf->entity];

        dol_syslog("file_manager.php: Set conf->industria40->multidir_output[$conf->entity] = " .
            $conf->industria40->multidir_output[$conf->entity], LOG_DEBUG);
        dol_syslog("file_manager.php: Set conf->industria40->dir_output = " .
            $conf->industria40->dir_output, LOG_DEBUG);
    }

    // Verifica i permessi della directory
    if (is_dir($upload_dir)) {
        if (!is_writable($upload_dir)) {
            dol_syslog("file_manager.php: WARNING - Directory not writable: " . $upload_dir, LOG_WARNING);
            @chmod($upload_dir, 0775); // Tentativo di correzione dei permessi

            // Verifica dopo il tentativo di correzione
            if (!is_writable($upload_dir)) {
                dol_syslog("file_manager.php: ERROR - Directory still not writable after chmod: " . $upload_dir, LOG_ERR);
                print '<div class="error">La directory di upload non è scrivibile: ' . $upload_dir . '</div>';
            }
        }
    }

    // Verifica l'esistenza e i permessi della directory base del modulo industria40
    // Questa verifica è corretta e dovrebbe rimanere.
    // Assicurati che $conf->industria40->dir_output sia definito correttamente prima di questo punto
    // (lo è, grazie al blocco DEBUG DETAILS o a init.inc.php).
    if (isset($conf->industria40->dir_output) && !is_dir($conf->industria40->dir_output)) {
        dol_mkdir($conf->industria40->dir_output);
        dol_syslog("file_manager.php: Created base module directory: " . $conf->industria40->dir_output, LOG_INFO);
    } else if (!isset($conf->industria40->dir_output)) {
        // Questo caso dovrebbe essere coperto dalla logica precedente, ma per sicurezza:
        $fallback_dir_output = DOL_DATA_ROOT . '/industria40';
        if (!is_dir($fallback_dir_output)) {
            dol_mkdir($fallback_dir_output);
            dol_syslog("file_manager.php: Created fallback base module directory: " . $fallback_dir_output, LOG_INFO);
        }
        // È importante che $conf->industria40->dir_output sia definito per il resto dello script.
        $conf->industria40->dir_output = $fallback_dir_output; // Define it if it was missing
        dol_syslog("file_manager.php: WARNING - conf->industria40->dir_output was not set, potential issue. Defined to fallback: ".$fallback_dir_output, LOG_WARNING);
    }


    // Verifica per document.php
    $document_test_path = DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&file=test.txt';
    dol_syslog("file_manager.php: Document access URL would be: " . $document_test_path, LOG_DEBUG);

    dol_syslog("file_manager.php: *** DEBUG DETAILS END ***", LOG_DEBUG);

    // Verifica che il modulo industria40 sia registrato nel sistema di documenti di Dolibarr
    if (!dol_include_once('/core/lib/files.lib.php')) {
        dol_syslog("file_manager.php: Cannot include files.lib.php", LOG_ERR);
    }

    // Assicuriamoci che le directory abbiano i permessi corretti
    if (!is_dir($upload_dir_base)) {
        dol_mkdir($upload_dir_base);
        dol_syslog("file_manager.php: Created base directory: " . $upload_dir_base, LOG_INFO);
    }

    // Verifica che la directory base abbia i permessi corretti
    if (is_dir($upload_dir_base)) {
        if (!is_writable($upload_dir_base)) {
            dol_syslog("file_manager.php: Base directory not writable: " . $upload_dir_base, LOG_WARNING);
            // Prova a correggere i permessi
            @chmod($upload_dir_base, 0775);
        }
    }

    // Ensure company directory exists
    if (!is_dir($upload_dir_base . '/' . $socid)) {
        dol_mkdir($upload_dir_base . '/' . $socid);
        dol_syslog("file_manager.php: Created company directory: " . $upload_dir_base . '/' . $socid, LOG_INFO);
        @chmod($upload_dir_base . '/' . $socid, 0775);
    }

    // Ensure perizia directory exists
    if (!is_dir($upload_dir)) {
        dol_mkdir($upload_dir);
        dol_syslog("file_manager.php: Created perizia directory: " . $upload_dir, LOG_INFO);
        @chmod($upload_dir, 0775);

        // Verifica lo stato dopo la creazione
        if (is_dir($upload_dir)) {
            dol_syslog("file_manager.php: Perizia directory successfully created: " . $upload_dir, LOG_INFO);
        } else {
            setEventMessages($langs->trans("ErrorCreatingPeriziaDirectory", $upload_dir), null, 'errors');
            dol_syslog("file_manager.php: Failed to create perizia directory: " . $upload_dir, LOG_ERR);
            // Interrompere l'esecuzione se la directory non può essere creata
            return;
        }
    }

    dol_syslog("file_manager.php: SocID = " . $socid, LOG_DEBUG);
    dol_syslog("file_manager.php: PeriziaID = " . $periziaid_sanitized, LOG_DEBUG);
    dol_syslog("file_manager.php: Action = " . $action, LOG_DEBUG);
    dol_syslog("file_manager.php: Upload directory = " . $upload_dir, LOG_DEBUG);

    // Aggiungi una verifica aggiuntiva delle impostazioni di Dolibarr per i documenti
    dol_syslog("file_manager.php: MAIN_UPLOAD_DOC = " .
        (isset($conf->global->MAIN_UPLOAD_DOC) ? $conf->global->MAIN_UPLOAD_DOC : 'NOT SET'), LOG_DEBUG);
    dol_syslog("file_manager.php: MAIN_UMASK = " .
        (isset($conf->global->MAIN_UMASK) ? $conf->global->MAIN_UMASK : 'NOT SET'), LOG_DEBUG);

    // Imposta esplicitamente un modulepart per questo modulo
    $modulepart = 'industria40';

    // Aggiungi trailing slash alla directory per la manipolazione dei file
    if (substr($upload_dir, -1) !== '/') {
        $upload_dir .= '/';
    }

    // --- Action Handlers ---

    // Handle file uploads
    if ($action == 'upload_folder' && !empty($_FILES['files']['name'])) {
        // Basic permission check (Example: only admin can upload)
        if (!$user->admin && !(isset($user->rights->industria40->write) && $user->rights->industria40->write)) { // Corrected permission check
            setEventMessages($langs->trans("ErrorForbidden"), null, 'errors');
            $action = ''; // Prevent further processing
        } else {
            dol_syslog("file_manager.php: Handling action 'upload_folder'", LOG_DEBUG);

            // Ensure base directories exist
            if (!is_dir(DOL_DATA_ROOT.'/industria40')) {
                dol_mkdir(DOL_DATA_ROOT.'/industria40');
            }

            if (!is_dir(DOL_DATA_ROOT.'/industria40/'.$socid)) {
                dol_mkdir(DOL_DATA_ROOT.'/industria40/'.$socid);
            }

            // Ensure upload directory exists
            if (!is_dir($upload_dir)) {
                // Create directory with proper permissions
                if (!dol_mkdir($upload_dir, 0775)) {
                    dol_syslog("file_manager.php: Failed to create directory: " . $upload_dir, LOG_ERR);
                    setEventMessages("Errore: Impossibile creare la directory di upload.", null, 'errors');
                } else {
                    dol_syslog("file_manager.php: Created directory: " . $upload_dir, LOG_INFO);
                }
            }

            // Process uploads
            foreach ($_FILES['files']['name'] as $key => $name) {
                if ($_FILES['files']['error'][$key] == UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['files']['tmp_name'][$key];
                    $sanitized_name = dol_sanitizeFileName($name);
                    $destination = $upload_dir . $sanitized_name; // $upload_dir already has trailing slash

                    if (dol_move_uploaded_file($tmp_name, $destination, 0, 0, $_FILES['files']['error'][$key])) {
                        setEventMessages(sprintf("File %s caricato con successo.", $sanitized_name), null, 'mesgs');
                        dol_syslog("file_manager.php: File moved to '$destination'", LOG_INFO);

                        // Perform OCR and analysis after successful upload
                        $file_extension = strtolower(pathinfo($sanitized_name, PATHINFO_EXTENSION));
                        $text_file_path = pathinfo($destination, PATHINFO_DIRNAME) . '/' . pathinfo($sanitized_name, PATHINFO_FILENAME) . '.txt';

                        if (!file_exists($text_file_path) || filesize($text_file_path) == 0) {
                            dol_syslog("file_manager.php: No extracted text found or empty, performing OCR on: $destination", LOG_INFO);
                            perform_ocr_on_file($destination, $file_extension);
                        } else {
                            dol_syslog("file_manager.php: Extracted text already exists, skipping OCR for: $text_file_path", LOG_INFO);
                        }

                        $ocr_text_content = load_ocr_text($destination);
                        if (!empty($ocr_text_content)) {
                            $analysis_results = analyze_ocr_content($ocr_text_content, $sanitized_name, $file_extension);
                            if (!empty($analysis_results['suggested_tag'])) {
                                $tags_dir = DOL_DATA_ROOT . '/industria40/tags';
                                if (!is_dir($tags_dir)) dol_mkdir($tags_dir, 0775, true); // Added recursive true

                                $tags_file = $tags_dir . '/file_tags.json';
                                if (!file_exists($tags_file)) {
                                    file_put_contents($tags_file, json_encode([])); // Initialize if not exists
                                }
                                $tags_data = [];
                                $tags_content = file_get_contents($tags_file);
                                if ($tags_content) {
                                    $tags_data = json_decode($tags_content, true);
                                    if (!is_array($tags_data)) $tags_data = [];
                                }
                                $file_key_tag = $socid . '_' . $periziaid_sanitized . '_' . $sanitized_name;
                                $tags_data[$file_key_tag] = $analysis_results['suggested_tag'];
                                file_put_contents($tags_file, json_encode($tags_data));
                                dol_syslog("file_manager.php: Tag '{$analysis_results['suggested_tag']}' applied to '$sanitized_name'", LOG_INFO);
                            }
                        }

                    } else {
                        dol_syslog("file_manager.php: Failed to move '$tmp_name' to '$destination'. Error code: " . $_FILES['files']['error'][$key], LOG_ERR);
                        setEventMessages(sprintf("Errore durante il caricamento del file: %s (Error code: %s)", $sanitized_name, $_FILES['files']['error'][$key]), null, 'errors');
                    }
                } else {
                     setEventMessages(sprintf("Errore nel caricamento del file %s (PHP Error Code: %s)", $name, $_FILES['files']['error'][$key]), null, 'errors');
                     dol_syslog("file_manager.php: Upload error for file '$name'. PHP Error Code: " . $_FILES['files']['error'][$key], LOG_ERR);
                }
            }
            // --- End of the garbled/problematic section ---
        }
    }

    // Handle file renaming
    if ($action == 'rename_files') {
        // Basic permission check (Example: only admin can rename)
        if (!$user->admin && !(isset($user->rights->industria40->write) && $user->rights->industria40->write)) { // Corrected permission check
            setEventMessages($langs->trans("ErrorForbidden"), null, 'errors');
            $action = ''; // Prevent further processing
        } else {
            dol_syslog("file_manager.php: Handling action 'rename_files'", LOG_DEBUG);
            // Controlla se stiamo rinominando un singolo file
            $single_file_rename = GETPOST('rename_single_file', 'alpha');
            dol_syslog("file_manager.php: single_file_rename = " . $single_file_rename, LOG_DEBUG);

            if (!empty($single_file_rename)) {
                // Rinomina solo il file specificato
                $original_name = GETPOST('original_name_' . $single_file_rename, 'alpha'); // Assuming original name is passed this way
                $new_name = GETPOST('new_name_' . $single_file_rename, 'alpha'); // Assuming new name is passed this way
                dol_syslog("file_manager.php: Attempting to rename '$original_name' to '$new_name'", LOG_DEBUG);

                $sanitized_new_name = dol_sanitizeFileName($new_name);
                $original_path = $upload_dir . $original_name;
                $new_path = $upload_dir . $sanitized_new_name;

                dol_syslog("file_manager.php: Sanitized paths - original: '$original_path', new: '$new_path'", LOG_DEBUG);
                // Prevent renaming to the same name or empty name
                if ($original_path != $new_path && !empty($sanitized_new_name)) {
                    if (file_exists($original_path)) {
                        if (rename($original_path, $new_path)) {
                            dol_syslog("file_manager.php: Successfully renamed '$original_path' to '$new_path'", LOG_DEBUG);
                        } else {
                            dol_syslog("file_manager.php: ERROR - Failed to rename '$original_path' to '$new_path'", LOG_ERR);
                            setEventMessages(sprintf("Errore durante la rinomina del file %s", $original_name), null, 'errors');
                        }
                    } else {
                        dol_syslog("file_manager.php: Original file '$original_path' not found for renaming.", LOG_WARNING);
                        setEventMessages(sprintf("Errore: File originale %s non trovato.", $original_name), null, 'warnings');
                    }
                } elseif (empty($sanitized_new_name)) {
                    dol_syslog("file_manager.php: Skipped renaming '$original_name' as new name is empty.", LOG_WARNING);
                    setEventMessages("Errore: Il nuovo nome non può essere vuoto.", null, 'warnings');
                }
            }
        }
    }

    // Handle single file removal
    if ($action == 'remove_file' && !empty($file_to_remove)) {
        // Basic permission check (Example: only admin can remove)
        if (!$user->admin && !(isset($user->rights->industria40->delete) && $user->rights->industria40->delete)) { // Corrected permission for delete
            setEventMessages($langs->trans("ErrorForbidden"), null, 'errors');
            $action = ''; // Prevent further processing
        } else {
            $sanitized_file_to_remove = dol_sanitizeFileName(basename($file_to_remove)); // Sanitize input + basename
            $file_path = $upload_dir . $sanitized_file_to_remove; // Use new $upload_dir

            // Security check: Ensure the file path is within the intended directory structure
            // realpath() resolves symlinks and '..' before comparison
            $real_upload_dir = realpath($upload_dir_base . '/' . $socid . '/' . $periziaid_sanitized);
            $real_file_path = realpath($file_path);
            if (!$real_upload_dir || !$real_file_path || strpos($real_file_path, $real_upload_dir) !== 0) {
                dol_syslog("file_manager.php: Attempt to remove file outside designated directory: " . $file_path . " (Resolved: " . $real_file_path . ", Expected Base: " . $real_upload_dir . ")", LOG_ERR);
                setEventMessages("Tentativo di file path injection rilevato.", null, 'errors');
            } elseif (file_exists($file_path) && is_file($file_path)) {
                if (unlink($file_path)) {
                    setEventMessages(sprintf("File %s rimosso con successo.", $sanitized_file_to_remove), null, 'mesgs');
                    dol_syslog("file_manager.php: Removed file: " . $file_path, LOG_INFO);
                    // Remove associated data
                    remove_associated_file_data($socid, $periziaid_sanitized, $sanitized_file_to_remove, $upload_dir);
                } else {
                    dol_syslog("file_manager.php: Failed to remove file: " . $file_path . ". Check permissions.", LOG_ERR);
                    setEventMessages(sprintf("Errore: Impossibile rimuovere il file %s", $sanitized_file_to_remove), null, 'errors');
                }
            } else {
                dol_syslog("file_manager.php: File not found for removal or is not a file: " . $file_path, LOG_WARNING);
                setEventMessages(sprintf("Errore: File non trovato: %s", $sanitized_file_to_remove), null, 'warnings');
            }
            // Reset action to prevent re-processing on refresh if needed, or redirect
            // header("Location: ".$_SERVER['PHP_SELF'].'?socid='.$socid); exit;
        }
    }

    // Handle removing all files in the folder
    if ($action == 'remove_all_files') {
        // Basic permission check (Example: only admin can remove all)
        if (!$user->admin && !(isset($user->rights->industria40->delete) && $user->rights->industria40->delete)) { // Corrected permission for delete
            setEventMessages($langs->trans("ErrorForbidden"), null, 'errors');
            $action = ''; // Prevent further processing
        } else {
            dol_syslog("file_manager.php: Handling action 'remove_all_files' for directory: " . $upload_dir, LOG_DEBUG);
            $files_removed = 0;
            $files_failed = 0;
            if (is_dir($upload_dir)) {
                $all_files = scandir($upload_dir);
                foreach ($all_files as $file_in_dir) {
                    if ($file_in_dir != '.' && $file_in_dir != '..') {
                        $path_to_unlink = $upload_dir . $file_in_dir;
                        if (is_file($path_to_unlink)) {
                            if (unlink($path_to_unlink)) {
                                dol_syslog("file_manager.php: Removed file during 'remove_all_files': " . $path_to_unlink, LOG_INFO);
                                // Also remove associated data for each file
                                if (!preg_match('/\.txt$/i', $file_in_dir)) { // Don't try to remove data for .txt files themselves if they are primary
                                     remove_associated_file_data($socid, $periziaid_sanitized, $file_in_dir, $upload_dir);
                                }
                            } else {
                                dol_syslog("file_manager.php: Failed to remove file during 'remove_all_files': " . $path_to_unlink, LOG_ERR);
                                $files_failed++;
                            }
                        }
                    }
                }
                setEventMessages(sprintf("%s file rimossi.", $files_removed), null, 'mesgs');
            } else {
                setEventMessages("Directory di upload non trovata.", null, 'warnings');
            }
        }
    }

    // Prima del form di upload, aggiungiamo la funzione per gestire i tag
    if ($action == 'set_tag' && !empty($_POST['file_name']) && !empty($_POST['file_tag'])) {
        $file_name = GETPOST('file_name', 'alpha');
        $file_tag = GETPOST('file_tag', 'alpha');
        $tags_dir = DOL_DATA_ROOT . '/industria40/tags';
        if (!is_dir($tags_dir)) dol_mkdir($tags_dir, 0775, true); // Added recursive true

        $tags_file = $tags_dir . '/file_tags.json';
        if (!file_exists($tags_file)) {
            file_put_contents($tags_file, json_encode([])); // Initialize if not exists
        }
        $tags_data = [];
        $tags_content = file_get_contents($tags_file);
        if ($tags_content) {
            $tags_data = json_decode($tags_content, true);
            if (!is_array($tags_data)) $tags_data = [];
        }
        $file_key = $socid . '_' . $periziaid_sanitized . '_' . $file_name;
        $tags_data[$file_key] = $file_tag;
        file_put_contents($tags_file, json_encode($tags_data));
        dol_syslog("file_manager.php: Tag '$file_tag' applied to '$file_name'", LOG_INFO);
    }

    // Aggiungiamo l'azione per ottenere descrizioni via ChatGPT
    if ($action == 'get_description') {
        $file_name = GETPOST('file_name', 'alpha');
        if (!empty($file_name)) {
            $file_path = $upload_dir . $file_name;
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $base64_image = '';
            $prompt_text = "Descrivi brevemente questo file.";

            if (in_array($file_extension, array('jpg', 'jpeg', 'png', 'gif')) && file_exists($file_path)) {
                $image_data = file_get_contents($file_path);
                if ($image_data) {
                    $base64_image = base64_encode($image_data);
                    $prompt_text = "Descrivi questa immagine.";
                }
            } elseif ($file_extension == 'pdf' && file_exists($file_path)) {
                // For PDFs, we might use OCR text if available, or a generic prompt
                $ocr_text_for_pdf = load_ocr_text($file_path);
                if (!empty($ocr_text_for_pdf)) {
                    $prompt_text = "Riassumi il contenuto di questo documento PDF basandoti sul seguente testo estratto:\n" . substr($ocr_text_for_pdf, 0, 2000); // Limit length
                } else {
                    $prompt_text = "Fornisci una descrizione generica per un file PDF con nome '$file_name'.";
                }
            } else {
                 $ocr_text_generic = load_ocr_text($file_path);
                 if(!empty($ocr_text_generic)){
                     $prompt_text = "Riassumi il contenuto di questo documento basandoti sul seguente testo estratto:\n" . substr($ocr_text_generic, 0, 2000);
                 } else {
                    $prompt_text = "Descrivi brevemente il file '$file_name'.";
                 }
            }
                    // ... existing code ...
                    $request_data = [
                        'model' => 'gpt-4o', // Aggiornato da gpt-4-vision-preview a gpt-4o
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => [
                                    ['type' => 'text', 'text' => $prompt_text]
                                ]
                            ]
                        ],
                        'max_tokens' => 150
                    ];
                    if (!empty($base64_image) && in_array($file_extension, array('jpg', 'jpeg', 'png', 'gif'))) {
                        $image_mime_type = mime_content_type($file_path);
                        $request_data['messages'][0]['content'][] = [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$image_mime_type};base64,{$base64_image}"
                            ]
                        ];
                    }
                    // ... rest of the cURL call ...
    }

    // Handle forcing OCR on existing files
    if ($action == 'force_ocr' && !empty($_POST['file_name'])) {
        $file_name = GETPOST('file_name', 'alpha');
        if (!empty($file_name)) {
            $file_path = $upload_dir . $file_name;
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $text_file_path = pathinfo($file_path, PATHINFO_DIRNAME) . '/' . pathinfo($file_name, PATHINFO_FILENAME) . '.txt'; // Corrected variable

            $skip_ocr = false;
            if (file_exists($text_file_path) && filesize($text_file_path) > 0) {
                dol_syslog("file_manager.php: OCR text already exists, using existing file: $text_file_path", LOG_INFO);
                // setEventMessages("Analisi saltata, file già elaborato", null, 'mesgs'); // This message might be confusing if user clicked "Force OCR"
                $skip_ocr = true; // Still set to true to avoid re-processing if not needed
            }
            if (in_array($file_extension, array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'tiff', 'tif'))) {
                $extracted_text = '';

                if (!$skip_ocr) {
                    $extracted_text = perform_ocr_on_file($file_path, $file_extension);
                } else {
                    $extracted_text = load_ocr_text($file_path); // Load existing text if skipping OCR
                }

                // Esegui analisi AI sul testo estratto
                if (!empty($extracted_text)) {
                    $analysis_results = analyze_ocr_content($extracted_text, $file_name, $file_extension);
                    if (!empty($analysis_results['suggested_tag'])) {
                        $tags_dir = DOL_DATA_ROOT . '/industria40/tags';
                        if (!is_dir($tags_dir)) dol_mkdir($tags_dir, 0775, true); // Added recursive true

                        $tags_file = $tags_dir . '/file_tags.json';
                        if (!file_exists($tags_file)) {
                            file_put_contents($tags_file, json_encode([])); // Initialize if not exists
                        }
                        $tags_data = [];
                        $tags_content = file_get_contents($tags_file);
                        if ($tags_content) {
                            $tags_data = json_decode($tags_content, true);
                            if (!is_array($tags_data)) $tags_data = [];
                        }
                        $file_key_tag = $socid . '_' . $periziaid_sanitized . '_' . $file_name;
                        $tags_data[$file_key_tag] = $analysis_results['suggested_tag'];
                        file_put_contents($tags_file, json_encode($tags_data));
                        dol_syslog("file_manager.php: Tag '{$analysis_results['suggested_tag']}' applied to '$file_name'", LOG_INFO);
                    }
                }
            }
        }
    }

    // --- Display Forms and Files ---

    // Build the base URL for form actions, including socid and periziaid
    $form_action_url = $_SERVER['PHP_SELF'] . '?socid=' . $socid . '&periziaid=' . urlencode($periziaid_sanitized);

    // Include CSS and JavaScript files
    print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/custom/industria40/css/file_manager.css">';
    print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/custom/industria40/js/file_manager.js"></script>';

    // Upload form - Pass socid and periziaid
    print '<form id="uploadForm" action="' . $form_action_url . '" method="POST" enctype="multipart/form-data">';
    print '<input type="hidden" name="action" value="upload_folder">';
    print '<input type="hidden" name="socid" value="' . $socid . '">';
    print '<input type="hidden" name="periziaid" value="' . dol_escape_htmltag($periziaid_sanitized) . '">'; // Pass periziaid
    // Drop zone multifunzionale
    print '<div class="tabsAction" style="margin-bottom: 15px;">';
    print '<div id="dropZone" style="border: 2px dashed #ccc; padding: 20px; text-align: center; margin-bottom: 10px; cursor: pointer;">';
    print "Trascina file e cartelle qui, o clicca per selezionare";
    print '<div id="fileSelectionInfo" style="margin-top:10px; color:#2c5987; font-size:0.9em;"></div>';
    print '</div>';
    // Modifica lo stile dell'input file qui sotto;
    print '<input type="file" id="actualFileInput" name="files[]" multiple webkitdirectory style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0;">';
    print '<div style="margin-top:10px;">';
    // Add the new "+" button here
    print '<button type="button" id="addFilesButton" class="button button-add" title="Aggiungi File/Cartelle"><i class="fa fa-plus"></i></button> '; // Using Font Awesome icon
    print '<button type="submit" class="button">Carica File</button>';
    print '</div>'; // Closing the div that contains the buttons
    print '</div>'; // End of drop zone div
    print '</form>';

    // Display uploaded files
    print '<div id="filePreview" style="margin-top: 20px;">';

    // Include CSS file instead of inline styles
    print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/custom/industria40/css/file_manager.css">';

    // Leggiamo i tag salvati prima di visualizzare i file
    $tags_dir = DOL_DATA_ROOT . '/industria40/tags';
    $tags_file = $tags_dir . '/file_tags.json';
    $file_tags = [];
    if (file_exists($tags_file)) {
        $tags_data = json_decode(file_get_contents($tags_file), true);
        if (is_array($tags_data)) {
            $file_tags = $tags_data;
        }
    }

    // Definiamo un array di possibili tag
    $available_tags = [
        'documento' => 'Documento',
        'fattura' => 'Fattura',
        'contratto' => 'Contratto',
        'preventivo' => 'Preventivo',
        'scheda' => 'Scheda Tecnica',
        'manuale' => 'Manuale',
        'certificato' => 'Certificato',
        'dichiarazione' => 'Dichiarazione',
        'dichiarazione_di_conformita_ce' => 'Conformità CE',
        'schermata' => 'Screenshot',
        'targhetta' => 'Targhetta',
        'manuale_uso' => 'Manuale d\'uso',
        'foto' => 'Foto'
    ];

    // Carica le descrizioni
    $desc_dir = DOL_DATA_ROOT . '/industria40/descriptions';
    $desc_file = $desc_dir . '/file_descriptions.json';
    $file_descriptions = [];
    if (file_exists($desc_file)) {
        $desc_data = json_decode(file_get_contents($desc_file), true);
        if (is_array($desc_data)) {
            $file_descriptions = $desc_data;
        }
    }

    // Rename Form
    print '<form id="renameForm" action="' . $form_action_url . '" method="POST">';
    print '<input type="hidden" name="action" value="rename_files">';
    print '<input type="hidden" name="socid" value="' . $socid . '">';
    print '<input type="hidden" name="periziaid" value="' . dol_escape_htmltag($periziaid_sanitized) . '">'; // Pass periziaid
    print '<table class="border" id="fileTable">';
    print '<thead><tr>';
    print '<th>Nome File / Anteprima</th>';
    print '<th>Descrizione</th>'; // Nuova colonna per le descrizioni
    print '<th>Nuovo Nome</th>';
    print '<th class="right">Azione</th>'; // Added Action column header
    print '</tr></thead>';
    print '<tbody>';
    $files_exist = false; // Flag to check if any files are listed
    if (is_dir($upload_dir)) {
        $files = scandir($upload_dir);
        dol_syslog("file_manager.php: Found " . count($files) . " entries in directory: " . $upload_dir, LOG_DEBUG); // Log total entries found
        foreach ($files as $file) {
            dol_syslog("file_manager.php: Processing entry: " . $file, LOG_DEBUG); // Log start of iteration
            // Skip .txt files and hidden files
            if ($file != '.' && $file != '..' && !preg_match('/\.(txt)$/i', $file)) {
                $files_exist = true; // Found at least one file
                dol_syslog("file_manager.php: Valid file found: " . $file, LOG_DEBUG); // Log valid file

                // Construct the relative path for document.php
                $file_path_relative = 'documents/' . $socid . '/' . $periziaid_sanitized . '/' . $file;
                $file_path = $upload_dir . $file;

                // Costruzione dell'URL per accesso ai file
                $file_url = DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&file=' . urlencode($file_path_relative) . '&entity=' . $conf->entity;
                dol_syslog("file_manager.php: Generated file URL for '$file': " . $file_url, LOG_DEBUG); // Log generated URL

                // Identificare l'estensione del file
                $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                dol_syslog("file_manager.php: File extension for '$file': " . $file_extension, LOG_DEBUG); // Log file extension

                // Carica l'eventuale analisi OCR esistente per questo file PRIMA della colonna di anteprima
                $ocr_text = load_ocr_text($file_path);
                $ocr_analysis = []; // Inizializza per evitare errori se ocr_text è vuoto
                if (!empty($ocr_text)) {
                    dol_syslog("file_manager.php: Found OCR text for '$file' (before preview column). Analyzing content.", LOG_DEBUG);
                    $ocr_analysis = analyze_ocr_content($ocr_text, $file, $file_extension);
                    dol_syslog("file_manager.php: OCR analysis result for '$file' (before preview column): " . json_encode($ocr_analysis), LOG_DEBUG);
                } else {
                    dol_syslog("file_manager.php: No OCR text found for '$file' (before preview column).", LOG_DEBUG);
                }


                print '<tr>';
                print '<td>'; // Colonna nome file e anteprima
                dol_syslog("file_manager.php: Generating preview column for '$file'", LOG_DEBUG); // Log preview start
                //print '<div class="filename-container" style="margin-bottom:5px;">' . dol_escape_htmltag($file) . '</div>';
                // Preview logic based on file type
                switch ($file_extension) {
                    case 'jpg':
                    case 'jpeg':
                    case 'png':
                    case 'gif':
                    case 'bmp':
                    case 'webp':
                        // Usa lo stesso layout per le immagini
                        print '<div class="file-preview-container">';
                        // Link all'immagine con zoom
                        print '<a href="' . $file_url . '" target="_blank" class="zoom-container">';
                        print '<img src="' . $file_url . '" class="preview-image" alt="Image Preview">';

                        // Verifica se esiste testo OCR per l'immagine
                        $text_file_path = $upload_dir . pathinfo($file, PATHINFO_FILENAME) . '.txt';
                        if (file_exists($text_file_path) && filesize($text_file_path) > 0) {
                            print '<div class="file-status-tooltip"><div class="file-processed" title="File già analizzato">✓</div>';
                            print '<span class="tooltiptext">Testo già estratto</span></div>';
                        }
                        print '</a>';

                        // Visualizza il tag se presente
                        $file_key = $socid . '_' . $periziaid_sanitized . '_' . $file;
                        if (isset($file_tags[$file_key])) {
                            print '<div class="tag-container">'; // il tag se presente
                            print '<span class="file-tag">' . $available_tags[$file_tags[$file_key]] . '</span>';
                            print '</div>';
                        }
                        print '<div class="filename-container">' . dol_escape_htmltag($file) . '</div>';
                        print '</div>'; // Fine file-preview-container
                        break;

                    case 'pdf':
                        print '<div class="file-preview-container">';
                        // Verifica se esiste un thumbnail per questo PDF
                        $thumbnail_path = DOL_DATA_ROOT . '/industria40/thumbnails/' . $socid . '/' . $periziaid_sanitized . '/thumb_' . pathinfo($file, PATHINFO_FILENAME) . '.jpg';
                        $has_thumbnail = file_exists($thumbnail_path);

                        if ($has_thumbnail) {
                            // Mostra thumbnail per PDF
                            $thumb_rel_path = 'thumbnails/' . $socid . '/' . $periziaid_sanitized . '/thumb_' . pathinfo($file, PATHINFO_FILENAME) . '.jpg';
                            $thumb_url = DOL_URL_ROOT . '/document.php?modulepart=industria40&file=' . urlencode($thumb_rel_path) . '&entity=' . $conf->entity;
                            // Mostra thumbnail per PDF
                            print '<a href="' . $file_url . '" target="_blank" class="zoom-container">';
                            print '<img src="' . $thumb_url . '" class="preview-image" alt="PDF Thumbnail">';

                            // Aggiungi indicatore se il file è stato processato
                            $text_file_path = $upload_dir . pathinfo($file, PATHINFO_FILENAME) . '.txt';
                            if (file_exists($text_file_path) && filesize($text_file_path) > 0) {
                                print '<div class="file-status-tooltip"><div class="file-processed" title="File già analizzato">✓</div>';
                                print '<span class="tooltiptext">Testo già estratto</span></div>';
                            }
                            print '</a>';
                        } else {
                            // Mostra icona PDF se non c'è thumbnail
                            print '<a href="' . $file_url . '" target="_blank" class="zoom-container">';
                            print '<div class="icon-container">';
                            print '<i class="fa fa-file-pdf fa-4x" style="color:#e74c3c;"></i>';
                            print '</div>';
                            print '</a>';
                        }

                        // Nome file (nascosto visivamente ma presente per accessibilità)
                        print '<div class="filename-container">' . dol_escape_htmltag($file) . '</div>';

                        // Visualizza il tag se presente
                        $file_key = $socid . '_' . $periziaid_sanitized . '_' . $file;
                        if (isset($file_tags[$file_key])) {
                            print '<div class="tag-container">';
                            print '<span class="file-tag">' . $available_tags[$file_tags[$file_key]] . '</span>';
                            print '</div>';
                        }
                        print '</div>'; // Fine file-preview-container
                        break;

                    case 'doc':
                    case 'docx':
                        print '<div class="file-preview-container">';
                        print '<a href="' . $file_url . '" target="_blank" class="zoom-container">';
                        print '<div class="icon-container">';
                        print '<i class="fa fa-file-word fa-4x" style="color:#2980b9;"></i>';
                        print '</div>';
                        print '</a>';
                        print '<div class="filename-container">' . dol_escape_htmltag($file) . '</div>';
                        // Visualizza il tag se presente
                        $file_key = $socid . '_' . $periziaid_sanitized . '_' . $file;
                        if (isset($file_tags[$file_key])) {
                            print '<div class="tag-container">'; // il tag se presente
                            print '<span class="file-tag">' . $available_tags[$file_tags[$file_key]] . '</span>';
                            print '</div>';
                        }
                        print '</div>'; // Fine file-preview-container
                        break;

                    case 'xls':
                    case 'xlsx':
                        print '<div class="file-preview-container">';
                        print '<a href="' . $file_url . '" target="_blank" class="zoom-container">';
                        print '<div class="icon-container">';
                        print '<i class="fa fa-file-excel fa-4x" style="color:#27ae60;"></i>';
                        print '</div>';
                        print '</a>';
                        print '<div class="filename-container">' . dol_escape_htmltag($file) . '</div>';
                        // Visualizza il tag se presente
                        $file_key = $socid . '_' . $periziaid_sanitized . '_' . $file;
                        if (isset($file_tags[$file_key])) {
                            print '<div class="tag-container">'; // il tag se presente
                            print '<span class="file-tag">' . $available_tags[$file_tags[$file_key]] . '</span>';
                            print '</div>';
                        }
                        print '</div>'; // Fine file-preview-container
                        break;

                    default:
                        // Generic file preview
                        print '<div class="file-preview-container">';
                        print '<a href="' . $file_url . '" target="_blank" class="zoom-container">';
                        print '<div class="icon-container">';
                        print '<i class="fa fa-file fa-4x" style="color:#7f8c8d;"></i>';
                        print '</div>';
                        print '</a>';
                        print '<div class="filename-container">' . dol_escape_htmltag($file) . '</div>';
                        // Visualizza il tag se presente
                        $file_key = $socid . '_' . $periziaid_sanitized . '_' . $file;
                        if (isset($file_tags[$file_key])) {
                            print '<div class="tag-container">'; // il tag se presente
                            print '<span class="file-tag">' . $available_tags[$file_tags[$file_key]] . '</span>';
                            print '</div>';
                        }
                        print '</div>'; // Fine file-preview-container
                        break;
                }
                print '</td>'; // End of file preview column

                // Colonna descrizione
                print '<td class="description-column">';
                dol_syslog("file_manager.php: Generating description column for '$file'", LOG_DEBUG); // Log description start
                $file_key_desc = $socid . '_' . $periziaid_sanitized . '_' . $file; // Use a different variable name to avoid conflict if $file_key is used differently above
                // Mostra descrizione se esiste
                if (!empty($file_descriptions[$file_key_desc])) {
                    dol_syslog("file_manager.php: Found existing description for '$file_key_desc'", LOG_DEBUG); // Log found description
                    print '<div class="description-text">' . dol_escape_htmltag($file_descriptions[$file_key_desc]) . '</div>';
                } else {
                    dol_syslog("file_manager.php: No existing description found for '$file_key_desc'", LOG_DEBUG); // Log no description found
                    // Per immagini e PDF mostra il pulsante per generare descrizione
                    if (in_array($file_extension, array('jpg', 'jpeg', 'png', 'gif', 'pdf'))) {
                        dol_syslog("file_manager.php: Displaying 'Generate Description' button for '$file'", LOG_DEBUG); // Log button display
                        print '<form action="' . $form_action_url . '" method="POST">';
                        print '<input type="hidden" name="action" value="get_description">';
                        print '<input type="hidden" name="file_name" value="' . dol_escape_htmltag($file) . '">';
                        print '<button type="submit" class="generate-desc-btn">Genera descrizione</button>';
                        print '</form>';
                    } else {
                        dol_syslog("file_manager.php: Not displaying 'Generate Description' button for '$file' (extension: $file_extension)", LOG_DEBUG); // Log button skip
                        print '<span class="opacitymedium">Nessuna descrizione</span>';
                    }
                }

                // NON è più necessario caricare $ocr_text e $ocr_analysis qui, sono già stati caricati prima della colonna di anteprima
                // $ocr_text è già disponibile
                // $ocr_analysis è già disponibile
                // $ocr_text è già disponibile
                if (!empty($ocr_text)) { // Verifica se $ocr_text è stato caricato
                    // Mostra suggerimenti dalle analisi OCR se presenti (SOLO NOME, il tag è già in anteprima)
                    if (!empty($ocr_analysis) && !empty($ocr_analysis['suggested_name']) && $ocr_analysis['suggested_name'] != $file) {
                        dol_syslog("file_manager.php: Displaying AI suggested name for '$file' in description column", LOG_DEBUG);
                        print '<div class="ai-suggestions">';
                        // Modifica il form di rinomina suggerita per includere l'azione rinomina_file
                        print '<form action="' . $form_action_url . '" method="POST">';
                        print '<input type="hidden" name="action" value="rename_file">';
                        print '<input type="hidden" name="file_name" value="' . dol_escape_htmltag($file) . '">';
                        print '<input type="hidden" name="new_name" value="' . dol_escape_htmltag($ocr_analysis['suggested_name']) . '">';
                        print '<div class="suggestion"><span class="label">Nome suggerito:</span> ' . dol_escape_htmltag($ocr_analysis['suggested_name']) . '</div>';
                        print '<button type="submit" class="rename-suggested-btn">Rinomina con Suggerimento</button>';
                        print '</form>';
                        print '</div>'; // Chiude ai-suggestions
                        dol_syslog("file_manager.php: Finished displaying AI suggestions for '$file'", LOG_DEBUG); // Log AI suggestions end
                    } else {
                         dol_syslog("file_manager.php: No AI suggestions to display for '$file'", LOG_DEBUG); // Log no AI suggestions
                    }
                } else {
                     dol_syslog("file_manager.php: No OCR text found for '$file'", LOG_DEBUG); // Log no OCR text
                }
                dol_syslog("file_manager.php: After OCR analysis/suggestion logic for '$file'", LOG_DEBUG); // Log after OCR logic

                // Pulsante per analizzare file se necessario
                if (in_array($file_extension, array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'tiff', 'tif')) && empty($ocr_text)) {
                    dol_syslog("file_manager.php: Displaying 'Analyze File' button for '$file'", LOG_DEBUG); // Log analyze button display
                    print '<div style="margin-top:10px;">';
                    print '<form action="' . $form_action_url . '" method="POST">';
                    print '<input type="hidden" name="action" value="force_ocr">';
                    print '<input type="hidden" name="file_name" value="' . dol_escape_htmltag($file) . '">';
                    print '<button type="submit" class="button">Analizza File</button>';
                    print '</form>';
                    print '</div>';
                } else {
                     dol_syslog("file_manager.php: Not displaying 'Analyze File' button for '$file' (extension: $file_extension, ocr_text empty: " . (empty($ocr_text) ? 'true' : 'false') . ")", LOG_DEBUG); // Log analyze button skip
                }
                dol_syslog("file_manager.php: After 'Analyze File' button logic for '$file'", LOG_DEBUG); // Log after analyze button logic
                print '</td>';
                dol_syslog("file_manager.php: Finished table row for '$file'", LOG_DEBUG); // Log end of row generation
            } else {
                print '<td class="rename-container">';
                dol_syslog("file_manager.php: Skipping entry: " . $file, LOG_DEBUG); // Log skipped entry
            }
        } // End foreach
        dol_syslog("file_manager.php: Finished iterating through directory entries.", LOG_DEBUG); // Log end of loop
    } else {
        print '<tr><td colspan="4">La directory di upload verrà creata al primo caricamento.</td></tr>';
    }
}

// Se non ci sono file da mostrare ma la directory esiste
if (!$files_exist && is_dir($upload_dir)) {
    print '</div>'; // End filePreview div
    // Aggiungi un messaggio di debug per confermare che lo script è stato eseguito completamente
    dol_syslog("file_manager.php: Script execution completed successfully", LOG_DEBUG);
    print '</tbody>';
    print '</table>';
    print '</form>';
}
} catch (Exception $e) {
    // Gestione degli errori generici
    dol_syslog("file_manager.php: Uncaught exception: " . $e->getMessage(), LOG_ERR);
    if (isset($files_exist) && $files_exist) {
        // Utilizziamo setEventMessages solo se la funzione esiste ed è inizializzata
        if (function_exists('setEventMessages')) {
            setEventMessages("Si è verificato un errore: " . $e->getMessage(), null, 'errors');
        }

        // Mostra un messaggio di errore anche nell'interfaccia
        print '<div class="error">Si è verificato un errore durante l\'elaborazione. Dettagli: ' . $e->getMessage() . '</div>';
    }
    // Redirect back to file manager
    header('Location: ' . (isset($form_action_url) ? $form_action_url : $_SERVER['PHP_SELF']));
    exit;
}

/**
 * Perform OCR on a document and save the text to a corresponding text file
 * @param string $file_path Path to the file to process
 * @param string $file_extension File extension to determine processing method
 * @return string The extracted text
 */
function perform_ocr_on_file($file_path, $file_extension) {
    global $conf;

    // Forza l'attivazione dell'OCR indipendentemente dalle impostazioni
    // if (empty($conf->global->INDUSTRIA40_ENABLE_OCR)) {
    //     dol_syslog("file_manager.php: OCR è disabilitato nelle impostazioni globali", LOG_INFO);
    //     return;
    // }
    if (function_exists('setEventMessages')) {
    dol_syslog("file_manager.php: Iniziando OCR sul file: $file_path", LOG_INFO);

    // Define output text file path
    $text_file_path = pathinfo($file_path, PATHINFO_DIRNAME) . '/' . pathinfo($file_path, PATHINFO_FILENAME) . '.txt';

    // Verifichiamo se il file di testo esiste già e contiene dati
    if (file_exists($text_file_path) && filesize($text_file_path) > 0) {
        dol_syslog("file_manager.php: OCR text file already exists, skipping OCR for: $text_file_path", LOG_INFO);
        // No return here, let the process continue to ensure all steps are logged
    }

    $extracted_text = '';
    try {
        // Check if tesseract is available
        $tesseract_available = false;
        if (function_exists('exec')) {
            // Try to determine tesseract path
            exec('which tesseract', $tesseract_output, $return_var);
            $tesseract_available = ($return_var === 0);

            if (!$tesseract_available) {
                 dol_syslog("file_manager.php: Tesseract command not found via 'which tesseract'. OCR might fail.", LOG_WARNING);
            }
        } else {
            dol_syslog("file_manager.php: La funzione exec() non è disponibile, OCR non può essere eseguito", LOG_WARNING);
            return $extracted_text; // $extracted_text is empty here
        }

        if ($file_extension == 'pdf') {
            // Convert PDF to images first, then OCR
            $temp_dir = sys_get_temp_dir() . '/' . uniqid('ocr_pdf_');
            if (!mkdir($temp_dir, 0755, true)) {
                dol_syslog("file_manager.php: Impossibile creare directory temporanea: $temp_dir", LOG_ERR);
                return ''; // Return empty if temp dir fails
            }
            dol_syslog("file_manager.php: Directory temporanea creata: $temp_dir", LOG_DEBUG);

            // Use imagemagick to convert PDF pages to images
            $cmd = "convert -density 300 \"$file_path\" -quality 100 \"$temp_dir/page_%04d.png\"";
            dol_syslog("file_manager.php: Esecuzione comando di conversione PDF: $cmd", LOG_INFO);

            exec($cmd, $output, $return_var);
            dol_syslog("file_manager.php: Codice di ritorno convert: $return_var", LOG_INFO);

            if ($return_var === 0) {
                $page_files = glob("$temp_dir/page_*.png");
                if (empty($page_files)) {
                    dol_syslog("file_manager.php: Nessuna pagina PNG generata da PDF. Controllare output ImageMagick.", LOG_WARNING);
                }
                foreach ($page_files as $page_count => $page_image) {
                    // Perform tesseract on each page
                    $out_base = pathinfo($page_image, PATHINFO_DIRNAME) . '/' .
                                pathinfo($page_image, PATHINFO_FILENAME);
                    $cmd = "tesseract \"$page_image\" \"$out_base\" -l ita+eng";
                    dol_syslog("file_manager.php: Elaborazione OCR pagina $page_count con comando: $cmd", LOG_INFO);

                    exec($cmd, $tesseract_output, $tesseract_return);
                    dol_syslog("file_manager.php: Codice ritorno tesseract: $tesseract_return", LOG_INFO);

                    // Append text from each page
                    if (file_exists("$out_base.txt")) {
                        $page_text = file_get_contents("$out_base.txt");
                        $extracted_text .= $page_text . "\n\n";
                        // Log un estratto del testo estratto (primi 100 caratteri)
                        $truncated_text = (strlen($page_text) > 100) ? substr($page_text, 0, 100) . '...' : $page_text;
                        dol_syslog("file_manager.php: Testo OCR estratto dalla pagina $page_count: " . str_replace(["\r", "\n"], [" ", " "], $truncated_text), LOG_INFO);
                    } else {
                        dol_syslog("file_manager.php: Nessun file di output tesseract creato per la pagina $page_count", LOG_WARNING);
                    }
                }
                // Clean up temp files
                foreach ($page_files as $temp_file) {
                    if (file_exists($temp_file)) unlink($temp_file);
                }
                foreach (glob("$temp_dir/*.txt") as $temp_txt) {
                    if (file_exists($temp_txt)) unlink($temp_txt);
                }
                if (is_dir($temp_dir)) rmdir($temp_dir);
            } else {
                dol_syslog("file_manager.php: Conversione PDF fallita con codice di errore $return_var. Output: " . implode("\n", $output), LOG_ERR);
            }
        } else {
            // Use tesseract directly on image
            $out_base = pathinfo($file_path, PATHINFO_DIRNAME) . '/' . pathinfo($file_path, PATHINFO_FILENAME) . '_ocr';
            $cmd = "tesseract \"$file_path\" \"$out_base\" -l ita+eng";
            dol_syslog("file_manager.php: Elaborazione OCR immagine con comando: $cmd", LOG_INFO);

            exec($cmd, $output, $return_var);
            dol_syslog("file_manager.php: Codice ritorno tesseract: $return_var", LOG_INFO);

            // Read extracted text
            if (file_exists("$out_base.txt")) {
                $extracted_text = file_get_contents("$out_base.txt");
                // Log un estratto del testo estratto (primi 150 caratteri)
                $truncated_text = (strlen($extracted_text) > 150) ? substr($extracted_text, 0, 150) . '...' : $extracted_text;
                dol_syslog("file_manager.php: Testo OCR estratto dall'immagine: " . str_replace(["\r", "\n"], [" ", " "], $truncated_text), LOG_INFO);
            } else {
                dol_syslog("file_manager.php: Elaborazione OCR completata ma nessun file di output è stato creato", LOG_WARNING);
            }
        }

        // Save extracted text to final text file
        if (!empty($extracted_text)) {
            file_put_contents($text_file_path, $extracted_text);

            // Log che salviamo il file di testo con la dimensione del contenuto
            $text_size = strlen($extracted_text);
            $words_count = str_word_count(preg_replace('/[0-9]+/', '', $extracted_text)); // Approssimato
            dol_syslog("file_manager.php: OCR completato con successo, testo salvato in: $text_file_path ($text_size bytes, circa $words_count parole)", LOG_INFO);
            dol_syslog("file_manager.php: CONTENUTO FILE OCR INIZIO -----", LOG_INFO);
            $log_text = (strlen($extracted_text) > 500) ? substr($extracted_text, 0, 500) . "..." : $extracted_text;
            dol_syslog("file_manager.php: " . str_replace(["\r", "\n"], [" ", " "], $log_text), LOG_INFO);
            dol_syslog("file_manager.php: CONTENUTO FILE OCR FINE -----", LOG_INFO);
        } else {
            dol_syslog("file_manager.php: OCR completato ma nessun testo è stato estratto", LOG_WARNING);
        }
    } catch (Exception $e) {
        dol_syslog("file_manager.php: Errore durante l'esecuzione dell'OCR: " . $e->getMessage(), LOG_ERR);
        dol_syslog("file_manager.php: " . $e->getTraceAsString(), LOG_DEBUG);
    }
    dol_syslog("file_manager.php: Processo OCR completato per il file: $file_path", LOG_INFO);
    return $extracted_text;
}

/**
 * Analizza il testo OCR con ChatGPT per ottenere suggerimenti per nome file e tag
 * @param string $ocr_text Testo OCR da analizzare
 * @param string $original_filename Nome originale del file
 * @param string $file_extension Estensione del file
 * @return array Array con chiavi 'suggested_name' e 'suggested_tag'
 */
function analyze_ocr_content($ocr_text, $original_filename, $file_extension) {
    global $conf, $socid, $periziaid_sanitized, $available_tags; // Added $available_tags
    dol_syslog("file_manager.php: analyze_ocr_content: Starting analysis for '$original_filename'", LOG_DEBUG); // Log function start

    $result = [
        'suggested_name' => $original_filename, // Default to original name
        'suggested_tag' => ''
    ];

    if (empty($ocr_text) || strlen($ocr_text) < 10) {
        dol_syslog("file_manager.php: analyze_ocr_content: OCR text is empty or too short for '$original_filename'. Skipping analysis.", LOG_DEBUG); // Log skip due to short text
        return $result;
    }

    // Crea una chiave univoca per il file
    $file_key = $socid . '_' . $periziaid_sanitized . '_' . $original_filename . '_ocr_analysis';
    dol_syslog("file_manager.php: analyze_ocr_content: File key for stored analysis: '$file_key'", LOG_DEBUG); // Log file key

    // Verifica se abbiamo già un'analisi memorizzata per questo file
    $stored_analysis = get_stored_ai_response($file_key);
    if ($stored_analysis !== false) {
        dol_syslog("file_manager.php: analyze_ocr_content: Using stored OCR analysis for file: '$original_filename'", LOG_DEBUG); // Log using stored analysis
        // Ensure the stored analysis is valid JSON before decoding
        $decoded_analysis = json_decode($stored_analysis, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_analysis)) {
             dol_syslog("file_manager.php: analyze_ocr_content: Successfully decoded stored analysis for '$original_filename'", LOG_DEBUG);
             return $decoded_analysis;
        } else {
             dol_syslog("file_manager.php: analyze_ocr_content: Stored analysis for '$original_filename' is invalid JSON. Proceeding with new analysis.", LOG_WARNING);
        }
    } else {
        dol_syslog("file_manager.php: analyze_ocr_content: No stored analysis found for '$file_key'. Performing new analysis.", LOG_DEBUG); // Log no stored analysis
    }

    // Utilizzo dell'API di OpenAI: Prioritize .env, then Dolibarr config
    $openai_api_key = getenv('OPENAI_API_KEY'); // Check .env first
    if (empty($openai_api_key)) {
        $openai_api_key = !empty($conf->global->INDUSTRIA40_OPENAI_API_KEY) ? $conf->global->INDUSTRIA40_OPENAI_API_KEY : '';
         if (!empty($openai_api_key)) {
             dol_syslog("file_manager.php: analyze_ocr_content: Using OpenAI key from Dolibarr config.", LOG_DEBUG);
        }
    } else {
         dol_syslog("file_manager.php: analyze_ocr_content: Using OpenAI key from .env file.", LOG_DEBUG);
    }

    if (empty($openai_api_key)) {
        dol_syslog("file_manager.php: analyze_ocr_content: OpenAI API key not configured in .env or Dolibarr settings, skipping analysis for '$original_filename'", LOG_WARNING); // Log missing API key
        return $result;
    }

    // Limita il testo OCR a 1000 caratteri per l'analisi
    $truncated_text = substr($ocr_text, 0, 1000);
    dol_syslog("file_manager.php: analyze_ocr_content: Truncated OCR text length: " . strlen($truncated_text), LOG_DEBUG); // Log truncated text length

    //try {
        dol_syslog("file_manager.php: analyze_ocr_content: Preparing cURL request for OpenAI API for '$original_filename'", LOG_DEBUG); // Log before cURL init
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openai_api_key
        ]);
        // Add timeout options
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Connection timeout: 10 seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Total timeout: 30 seconds

        // Crea una lista di tag disponibili come stringa per il prompt
        $tag_list_string = '';
        if (is_array($available_tags) && !empty($available_tags)) {
            $tag_keys = array_keys($available_tags);
            $tag_list_string = implode(", ", $tag_keys);
        } else {
            // Definizione di fallback dei tag se $available_tags non è disponibile
            $tag_list_string = "documento, fattura, contratto, preventivo, scheda, manuale, certificato, dichiarazione, dichiarazione_di_conformita_ce, schermata, targhetta, manuale_uso, foto";
        }
        dol_syslog("file_manager.php: analyze_ocr_content: Tag list string: " . $tag_list_string, LOG_DEBUG);

        // Crea una richiesta per analizzare il testo e suggerire un nome file e un tag/categoria appropriati
        // Updated prompt structure
        $request_data = [
            'model' => 'gpt-4o',
            'response_format' => ['type' => 'json_object'], // Request JSON output
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "Sei un assistente che analizza testo estratto da OCR per documenti relativi a perizie tecniche (ID: $periziaid_sanitized). Devi proporre un nome di file e un tag/categoria appropriati. Rispondi *esclusivamente* con un oggetto JSON contenente 'suggested_name' e 'suggested_tag'."
                ],
                [
                    'role' => 'user',
                    'content' => "Analizza il seguente testo estratto da OCR per la perizia ID $periziaid_sanitized. Rispondi esclusivamente in formato JSON con due proprietà:\n"
                               . "'suggested_name': Un nome file conciso e descrittivo basato sul contenuto del testo (massimo 40 caratteri, senza estensione, usa underscore per spazi). Il nome dovrebbe includere l'ID perizia ($periziaid_sanitized), il tag scelto e una brevissima descrizione (1-3 parole) derivata dal testo. Formato: {$periziaid_sanitized}_{tag_scelto}_descrizione_breve\n"
                               . "'suggested_tag': Scegli la *singola* categoria (tag) più appropriata *esclusivamente* da questa lista: [$tag_list_string].\n"
                               . "Testo OCR:\n" . $truncated_text
                ]
            ],
            'max_tokens' => 150,
            'temperature' => 0.3 // Lower temperature for more deterministic tag/name generation
        ];
        // Updated prompt structure
        $json_request_data = json_encode($request_data);
        if (json_last_error() !== JSON_ERROR_NONE) {
             dol_syslog("file_manager.php: analyze_ocr_content: Failed to encode JSON request data: " . json_last_error_msg(), LOG_ERR);
            return $result;
        }

        dol_syslog("file_manager.php: analyze_ocr_content: Sending request to OpenAI: " . $json_request_data, LOG_DEBUG); // Log request data

        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_request_data);
        $response = curl_exec($ch);
        $curl_info = curl_getinfo($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        dol_syslog("file_manager.php: analyze_ocr_content: OpenAI API response HTTP status: " . $curl_info['http_code'] . " for '$original_filename'", LOG_DEBUG); // Log HTTP status

        if ($curl_error) {
            dol_syslog("file_manager.php: analyze_ocr_content: cURL error for '$original_filename': " . $curl_error, LOG_ERR); // Log cURL error
            return $result; // Return default result on cURL error
        }

        if ($response && $curl_info['http_code'] == 200) {
            dol_syslog("file_manager.php: analyze_ocr_content: Received successful response from OpenAI for '$original_filename'. Raw response: " . $response, LOG_DEBUG); // Log raw response

            // Estrai il JSON dalla risposta (potrebbe esserci del testo aggiuntivo)
            preg_match('/{.*}/s', $response, $json_matches);
            if (!empty($json_matches[0])) {
                $decoded_response = json_decode($json_matches[0], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    dol_syslog("file_manager.php: analyze_ocr_content: Failed to decode extracted JSON response: " . json_last_error_msg(), LOG_WARNING);
                    return $result; // Give up if extraction also fails
                }
                dol_syslog("file_manager.php: analyze_ocr_content: Successfully decoded extracted JSON response.", LOG_DEBUG);

                if (isset($decoded_response['choices'][0]['message']['content'])) {
                    $content = $decoded_response['choices'][0]['message']['content'];
                    dol_syslog("file_manager.php: analyze_ocr_content: Extracted content from OpenAI response: " . $content, LOG_DEBUG); // Log extracted content

                    // Estrai il JSON dalla risposta (potrebbe esserci del testo aggiuntivo)
                    preg_match('/{.*}/s', $content, $json_matches);
                    if (!empty($json_matches[0])) {
                        $suggestion_json = $json_matches[0];
                        dol_syslog("file_manager.php: analyze_ocr_content: Found JSON in content: " . $suggestion_json, LOG_DEBUG); // Log found JSON
                        $suggestion = json_decode($suggestion_json, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($suggestion)) {
                            if (isset($suggestion['suggested_name'])) {
                                // Sanitize and ensure underscores, limit length
                                $clean_name = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $suggestion['suggested_name']);
                                $clean_name = preg_replace('/_+/', '_', $clean_name); // Replace multiple underscores
                                $clean_name = trim($clean_name, '_'); // Trim leading/trailing underscores
                                // Ensure perizia ID is at the start if AI didn't include it correctly
                                if (strpos($clean_name, $periziaid_sanitized . '_') !== 0) {
                                    $clean_name = $periziaid_sanitized . '_' . $clean_name;
                                }
                                $clean_name = substr($clean_name, 0, 40); // Slightly increased length limit for ID+Tag+Desc
                                $result['suggested_name'] = dol_sanitizeFileName($clean_name); // Sanitize again
                                dol_syslog("file_manager.php: analyze_ocr_content: Suggested name (cleaned): " . $result['suggested_name'], LOG_DEBUG);

                                // Add original extension if missing
                                if (!empty($file_extension) && strtolower(pathinfo($result['suggested_name'], PATHINFO_EXTENSION)) !== strtolower($file_extension)) {
                                    $result['suggested_name'] .= '.' . $file_extension;
                                    dol_syslog("file_manager.php: analyze_ocr_content: Added extension: " . $result['suggested_name'], LOG_DEBUG);
                                }
                            } else {
                                 dol_syslog("file_manager.php: analyze_ocr_content: 'suggested_name' not found in suggestion JSON.", LOG_DEBUG);
                                 $result['suggested_name'] = $original_filename; // Fallback to original if AI fails name suggestion
                            }

                            if (isset($suggestion['suggested_tag'])) {
                                // Validate tag strictly against available tags keys
                                if (array_key_exists($suggestion['suggested_tag'], $available_tags)) {
                                    $result['suggested_tag'] = $suggestion['suggested_tag'];
                                    dol_syslog("file_manager.php: analyze_ocr_content: Suggested tag (validated): " . $result['suggested_tag'], LOG_DEBUG);
                                } else {
                                    dol_syslog("file_manager.php: analyze_ocr_content: Suggested tag '" . $suggestion['suggested_tag'] . "' is NOT in the available tags list [$tag_list_string]. Ignoring.", LOG_WARNING);
                                    // Keep suggested_tag empty if invalid
                                }
                            } else {
                                 dol_syslog("file_manager.php: analyze_ocr_content: 'suggested_tag' not found in suggestion JSON.", LOG_DEBUG);
                                 $result['suggested_name'] = $original_filename; // Fallback on JSON decode errors
                            }

                            // Store the analysis result (even if parts failed, store what we got)
                            store_ai_response($file_key, json_encode($result));
                            dol_syslog("file_manager.php: analyze_ocr_content: Stored the new analysis result for '$file_key'", LOG_DEBUG);

                        } else {
                             dol_syslog("file_manager.php: analyze_ocr_content: Failed to decode suggestion JSON: " . json_last_error_msg(), LOG_WARNING);
                             $result['suggested_name'] = $original_filename; // Fallback on JSON decode error
                        }
                    } else {
                         dol_syslog("file_manager.php: analyze_ocr_content: No JSON object found in OpenAI content.", LOG_WARNING);
                         $result['suggested_name'] = $original_filename; // Fallback if no JSON found
                    }
                } else {
                     dol_syslog("file_manager.php: analyze_ocr_content: 'choices[0][message][content]' not found in OpenAI response structure.", LOG_WARNING);
                     $result['suggested_name'] = $original_filename; // Fallback on unexpected response structure
                }
            } else {
                 dol_syslog("file_manager.php: analyze_ocr_content: OpenAI API call failed with HTTP status " . $curl_info['http_code'], LOG_WARNING);
                 $result['suggested_name'] = $original_filename; // Fallback on API error
            }
        /*} catch (Exception $e) {
            dol_syslog("file_manager.php: analyze_ocr_content: Exception during OpenAI API call for '$original_filename': " . $e->getMessage(), LOG_ERR); // Log exception
            $result['suggested_name'] = $original_filename; // Fallback on exception
        }*/
    } else {
        dol_syslog("file_manager.php: analyze_ocr_content: No file name provided for analysis", LOG_WARNING);
    }
    dol_syslog("file_manager.php: analyze_ocr_content: Finished analysis for '$original_filename'. Returning: " . json_encode($result), LOG_DEBUG); // Log function end
    return $result;
}

/**
 * Aggiorna i riferimenti ai file (tag, descrizioni, ecc) dopo una rinomina
 * @param int $socid ID della società
 * @param int $periziaid ID della perizia
 * @param string $old_filename Nome originale del file
 * @param string $new_filename Nuovo nome del file
 * @return void
 */
function update_file_references($socid, $periziaid, $old_filename, $new_filename) {
    global $conf; // Removed global $periziaid_sanitized as $periziaid is passed directly
    dol_syslog("file_manager.php: Updating file references from '$old_filename' to '$new_filename' for perizia $periziaid", LOG_DEBUG);

    // Define keys
    $old_key_base = $socid . '_' . $periziaid . '_' . $old_filename;
    $new_key_base = $socid . '_' . $periziaid . '_' . $new_filename;

    // Aggiorna i tag
    $tags_dir = DOL_DATA_ROOT . '/industria40/tags';
    $tags_file = $tags_dir . '/file_tags.json';

    if (file_exists($tags_file)) {
        $tags_content = file_get_contents($tags_file);
        if ($tags_content) {
            $tags_data = json_decode($tags_content, true);
            if (is_array($tags_data) && isset($tags_data[$old_key_base])) {
                $tags_data[$new_key_base] = $tags_data[$old_key_base];
                unset($tags_data[$old_key_base]);
                file_put_contents($tags_file, json_encode($tags_data));
                dol_syslog("file_manager.php: Updated tag reference from '$old_key_base' to '$new_key_base'", LOG_DEBUG);
            }
        }
    }
    // Aggiorna le descrizioni
    $desc_dir = DOL_DATA_ROOT . '/industria40/descriptions';
    $desc_file = $desc_dir . '/file_descriptions.json';
    if (file_exists($desc_file)) {
        $desc_content = file_get_contents($desc_file);
        if ($desc_content) {
            $desc_data = json_decode($desc_content, true);
            if (is_array($desc_data) && isset($desc_data[$old_key_base])) {
                $desc_data[$new_key_base] = $desc_data[$old_key_base];
                unset($desc_data[$old_key_base]);
                file_put_contents($desc_file, json_encode($desc_data));
                dol_syslog("file_manager.php: Updated description reference from '$old_key_base' to '$new_key_base'", LOG_DEBUG);
            }
        } else {
            dol_syslog("file_manager.php: Failed to read descriptions file $desc_file", LOG_ERR);
        }
    }
    // Aggiorna la cache delle risposte AI (descrizioni e analisi OCR)
    $ai_dir = DOL_DATA_ROOT . '/industria40/ai_responses';
    $ai_file = $ai_dir . '/stored_responses.json';
    if (file_exists($ai_file)) {
        $ai_content = file_get_contents($ai_file);
        if ($ai_content) {
            $ai_data = json_decode($ai_content, true);
            $updated_ai_data = false;
            if (is_array($ai_data)) {
                // Update AI description key
                $ai_desc_key = $old_key_base; // Assuming direct key for description
                if (isset($ai_data[$ai_desc_key])) {
                    $ai_data[$new_key_base] = $ai_data[$ai_desc_key];
                    unset($ai_data[$ai_desc_key]);
                    $updated_ai_data = true;
                    dol_syslog("file_manager.php: Updated AI description cache for key '$ai_desc_key' to '$new_key_base'", LOG_DEBUG);
                }
                // Update AI OCR analysis key
                $old_ocr_key = $old_key_base . '_ocr_analysis';
                $new_ocr_key = $new_key_base . '_ocr_analysis';
                if (isset($ai_data[$old_ocr_key])) {
                    $ai_data[$new_ocr_key] = $ai_data[$old_ocr_key];
                    unset($ai_data[$old_ocr_key]);
                    $updated_ai_data = true;
                    dol_syslog("file_manager.php: Updated AI OCR analysis cache key from '$old_ocr_key' to '$new_ocr_key'", LOG_DEBUG);
                }
            }
            if ($updated_ai_data) {
                file_put_contents($ai_file, json_encode($ai_data));
            }
        }
    }
    // Aggiorna anche eventuali thumbnail per i PDF
    $thumbnail_dir = DOL_DATA_ROOT . '/industria40/thumbnails/' . $socid . '/' . $periziaid; // Corrected periziaid variable
    // Ensure the perizia-specific thumbnail directory exists
    if (!is_dir($thumbnail_dir)) {
        dol_mkdir($thumbnail_dir, 0775, true);
    }
    $old_thumbnail_filename = 'thumb_' . pathinfo($old_filename, PATHINFO_FILENAME) . '.jpg';
    $new_thumbnail_filename = 'thumb_' . pathinfo($new_filename, PATHINFO_FILENAME) . '.jpg';
    $thumbnail_path = $thumbnail_dir . '/' . $old_thumbnail_filename;
    $new_thumbnail_path = $thumbnail_dir . '/' . $new_thumbnail_filename;


    if (file_exists($thumbnail_path)) {
        if (rename($thumbnail_path, $new_thumbnail_path)) {
            dol_syslog("file_manager.php: Thumbnail rinominata con successo da '$thumbnail_path' a '$new_thumbnail_path'", LOG_DEBUG);
        } else {
            dol_syslog("file_manager.php: Impossibile rinominare thumbnail da '$thumbnail_path' a '$new_thumbnail_path'", LOG_ERR);
        }
    } else {
        dol_syslog("file_manager.php: Thumbnail non trovata per il file PDF, nessuna azione necessaria: " . $thumbnail_path, LOG_DEBUG);
    }
}

/**
 * Verifica se esiste una risposta dell'AI memorizzata per il file specificato
 * @param string $file_key Chiave univoca del file
 * @return mixed La risposta memorizzata o false se non esiste
 */
function get_stored_ai_response($key) {
    $ai_responses_dir = DOL_DATA_ROOT . '/industria40/ai_responses';
    if (!is_dir($ai_responses_dir)) {
        dol_syslog("file_manager.php: get_stored_ai_response: Directory not found: $ai_responses_dir", LOG_DEBUG);
        return false;
    }
    $ai_responses_file = $ai_responses_dir . '/stored_responses.json';
    if (!file_exists($ai_responses_file)) {
        dol_syslog("file_manager.php: get_stored_ai_response: Stored responses file not found: $ai_responses_file", LOG_DEBUG);
        return false;
    }

    $responses_data = json_decode(file_get_contents($ai_responses_file), true);
    // Check for JSON decoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        dol_syslog("file_manager.php: get_stored_ai_response: Failed to decode JSON from $ai_responses_file: " . json_last_error_msg(), LOG_WARNING);
        return false; // Treat invalid JSON as if the file doesn't exist or key isn't present
    }

    if (!is_array($responses_data) || !isset($responses_data[$key])) {
         dol_syslog("file_manager.php: get_stored_ai_response: Key '$key' not found in responses data.", LOG_DEBUG);
        return false;
    }
    dol_syslog("file_manager.php: get_stored_ai_response: Retrieved stored AI response for key: '$key'", LOG_DEBUG);
    return $responses_data[$key];
}

/**
 * Memorizza una risposta AI per uso futuro
 * @param string $key Chiave univoca per identificare la risposta
 * @param string $response La risposta da memorizzare
 * @return bool True se memorizzata con successo, false altrimenti
 */
function store_ai_response($key, $response) {
    if (empty($key) || $response === null) { // Allow empty string for response, but not null
        dol_syslog("file_manager.php: store_ai_response: Key or response is empty/null. Key: $key", LOG_WARNING);
        return false;
    }

    $ai_responses_dir = DOL_DATA_ROOT . '/industria40/ai_responses';
    if (!is_dir($ai_responses_dir)) {
        if (!dol_mkdir($ai_responses_dir, 0775, true)) { // Added recursive true
            dol_syslog("file_manager.php: store_ai_response: Failed to create AI responses directory: $ai_responses_dir", LOG_ERR);
            return false;
        }
    }
    $ai_responses_file = $ai_responses_dir . '/stored_responses.json';
    $responses_data = [];
    if (file_exists($ai_responses_file)) {
        $current_content = file_get_contents($ai_responses_file);
        if ($current_content !== false) {
            $decoded_data = json_decode($current_content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_data)) {
                $responses_data = $decoded_data;
            } else {
                 dol_syslog("file_manager.php: store_ai_response: Failed to decode JSON from $ai_responses_file or not an array. Initializing.", LOG_WARNING);
                 // If JSON is corrupt, we might overwrite or log and preserve. For now, re-initializing.
                 $responses_data = [];
            }
        } else {
            // Could not read file, initialize
            $responses_data = [];
        }
    } else {
        // File doesn't exist, will be created with an empty array initially.
         $responses_data = [];
    }
    // Memorizza la risposta
    $responses_data[$key] = $response;
    if (file_put_contents($ai_responses_file, json_encode($responses_data)) === false) {
        dol_syslog("file_manager.php: store_ai_response: Failed to write to $ai_responses_file", LOG_ERR);
        return false;
    }
    dol_syslog("file_manager.php: Stored AI response for key: " . $key, LOG_DEBUG);
    return true;
}

/**
 * Rimuove i dati associati a un file (descrizioni, risposte AI, file OCR .txt)
 * @param int $socid ID della società
 * @param int $periziaid ID della perizia
 * @param string $filename Nome del file
 * @param string $upload_dir_path Percorso della directory di upload del file (con trailing slash)
 * @return void
 */
function remove_associated_file_data($socid, $periziaid, $filename, $upload_dir_path) {
    dol_syslog("file_manager.php: Removing associated data for file '$filename' in perizia '$periziaid'", LOG_DEBUG);
    $file_key_base = $socid . '_' . $periziaid . '_' . $filename;

    // Rimuovi la descrizione dal file JSON
    $desc_dir = DOL_DATA_ROOT . '/industria40/descriptions';
    $desc_file_path = $desc_dir . '/file_descriptions.json';
    if (file_exists($desc_file_path)) {
        $desc_content = file_get_contents($desc_file_path);
        if ($desc_content) {
            $desc_data = json_decode($desc_content, true);
            if (is_array($desc_data) && isset($desc_data[$file_key_base])) {
                unset($desc_data[$file_key_base]);
                if (file_put_contents($desc_file_path, json_encode($desc_data)) === false) {
                     dol_syslog("file_manager.php: Failed to write updated descriptions to $desc_file_path", LOG_ERR);
                } else {
                    dol_syslog("file_manager.php: Removed description for key '$file_key_base'", LOG_DEBUG);
                }
            }
        } else {
            dol_syslog("file_manager.php: Failed to read descriptions file $desc_file_path", LOG_ERR);
        }
    }

    // Rimuovi le risposte AI (descrizione e analisi OCR) dal file JSON
    $ai_responses_dir = DOL_DATA_ROOT . '/industria40/ai_responses';
    $ai_responses_file_path = $ai_responses_dir . '/stored_responses.json';
    if (file_exists($ai_responses_file_path)) {
        $ai_content = file_get_contents($ai_responses_file_path);
        if ($ai_content) {
            $ai_data = json_decode($ai_content, true);
            if (is_array($ai_data)) {
                $updated_ai_data = false;
                // Remove AI description key
                $ai_desc_key = $file_key_base; // Assuming direct key for description
                if (isset($ai_data[$ai_desc_key])) {
                    unset($ai_data[$ai_desc_key]);
                    $updated_ai_data = true;
                    dol_syslog("file_manager.php: Removed AI description cache for key '$ai_desc_key'", LOG_DEBUG);
                }
                // Remove AI OCR analysis key
                $ai_ocr_key = $file_key_base . '_ocr_analysis';
                if (isset($ai_data[$ai_ocr_key])) {
                    unset($ai_data[$ai_ocr_key]);
                    $updated_ai_data = true;
                    dol_syslog("file_manager.php: Removed AI OCR analysis cache key from '$ai_ocr_key' to '$new_ocr_key'", LOG_DEBUG);
                }

                if ($updated_ai_data) {
                    if (file_put_contents($ai_responses_file_path, json_encode($ai_data)) === false) {
                        dol_syslog("file_manager.php: Failed to write updated AI responses to $ai_responses_file_path", LOG_ERR);
                    }
                }
            }
        } else {
            dol_syslog("file_manager.php: Failed to read AI responses file $ai_responses_file_path", LOG_ERR);
        }
    }

    // Rimuovi il file di testo OCR associato
    $ocr_text_file_path = $upload_dir_path . pathinfo($filename, PATHINFO_FILENAME) . '.txt';
    if (file_exists($ocr_text_file_path)) {
        if (unlink($ocr_text_file_path)) {
            dol_syslog("file_manager.php: Removed OCR text file: $ocr_text_file_path", LOG_DEBUG);
        } else {
            dol_syslog("file_manager.php: Failed to remove OCR text file: $ocr_text_file_path", LOG_ERR);
        }
    }
}
    // Rimuovi i file temporanei di tesseract
    $temp_dir = DOL_DATA_ROOT . '/industria40/temp';
    if (is_dir($temp_dir)) {
        foreach (glob("$temp_dir/*") as $temp_file) {
            if (file_exists($temp_file)) {
                if (unlink($temp_file)) {
                    dol_syslog("file_manager.php: Removed temporary file: $temp_file", LOG_DEBUG);
                } else {
                    dol_syslog("file_manager.php: Failed to remove temporary file: $temp_file", LOG_ERR);
                }
            }
        }
    }
}
    // Rimuovi le risposte AI memorizzate
    $ai_responses_dir = DOL_DATA_ROOT . '/industria40/ai_responses';
    if (is_dir($ai_responses_dir)) {
        foreach (glob("$ai_responses_dir/*") as $temp_file) {
            if (file_exists($temp_file)) {
                if (unlink($temp_file)) {
                    dol_syslog("file_manager.php: Removed AI response file: $temp_file", LOG_DEBUG);
                } else {
                    dol_syslog("file_manager.php: Failed to remove AI response file: $temp_file", LOG_ERR);
                }
            }
        }
    }
