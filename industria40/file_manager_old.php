<?php
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Ensure $langs is available (it should be from main.inc.php)
global $langs, $db, $conf, $user; // Added $user for permission checks

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
            $last_error = error_get_last();
            $error_message = $last_error ? $last_error['message'] : 'Unknown error scanning directory.';
            dol_syslog("file_manager.php: Failed to scan directory '$doc_root'. Error: " . $error_message, LOG_WARNING);
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
            $error_message = $last_error ? $last_error['message'] : 'Unknown error reading file.';
            dol_syslog("file_manager.php: Failed to read .env file content at $dotenv_path. Error: " . $error_message, LOG_WARNING);
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
            $line = trim($line); // Trim whitespace from each line
            // Skip empty lines and comments
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            // Parse KEY=VALUE, allowing for quoted values
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                // Remove surrounding quotes (single or double)
                if (strlen($value) > 1 && (($value[0] == '"' && substr($value, -1) == '"') || ($value[0] == "'" && substr($value, -1) == "'"))) {
                    $value = substr($value, 1, -1);
                }
                // Set environment variable if not already set
                if (getenv($key) === false && function_exists('putenv')) {
                    putenv("$key=$value");
                    // Also set $_ENV and $_SERVER for broader compatibility
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                    if ($key === 'OPENAI_API_KEY') {
                        $key_found_in_dotenv = true;
                        // Log that the specific key was found and set (avoid logging the key itself)
                        dol_syslog("file_manager.php: Found and set OPENAI_API_KEY from .env file.", LOG_DEBUG);
                    } else {
                        dol_syslog("file_manager.php: Set environment variable '$key' from .env file.", LOG_DEBUG);
                    }
                } elseif (getenv($key) !== false) {
                     // Log if the variable was already set (e.g., by web server config)
                     if ($key === 'OPENAI_API_KEY') {
                         dol_syslog("file_manager.php: OPENAI_API_KEY was already set in environment, not overwriting from .env.", LOG_INFO);
                         $key_found_in_dotenv = true; // Consider it found if already set
                     }
                }
            }
        }
        if (!$key_found_in_dotenv) {
            dol_syslog("file_manager.php: OPENAI_API_KEY was not found within the parsed content of the .env file.", LOG_WARNING);
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

// Load language files
$langs->load("industria40@industria40");
$langs->load("file_manager@industria40"); // Load dedicated language file

// Get the selected company ID and Perizia ID from the parent script
$socid = GETPOSTINT('socid');
$periziaid = GETPOSTINT('periziaid'); // Get Perizia ID

if (!$socid || empty($periziaid)) {
    print '<div class="error">'."Errore: ID Società o ID Perizia non selezionati.".'</div>';
    return; // Stop execution of this included file
}

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
            (isset($conf->industria40->multidir_output[$conf->entity]) ?
            $conf->industria40->multidir_output[$conf->entity] : 'NOT SET'), LOG_DEBUG);
    } else {
        dol_syslog("file_manager.php: WARNING - conf->industria40 or multidir_output not defined", LOG_WARNING);

        // Configurazione esplicita se mancante
        if (!isset($conf->industria40)) {
            $conf->industria40 = new stdClass();
            dol_syslog("file_manager.php: Created conf->industria40 object", LOG_DEBUG);
        }

        if (!isset($conf->industria40->multidir_output)) {
            $conf->industria40->multidir_output = array();
            dol_syslog("file_manager.php: Created conf->industria40->multidir_output array", LOG_DEBUG);
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
        }
        // È importante che $conf->industria40->dir_output sia definito per il resto dello script.
        // Se non è ancora definito, potrebbe indicare un problema più a monte.
        dol_syslog("file_manager.php: WARNING - conf->industria40->dir_output was not set, potential issue.", LOG_WARNING);
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
            dol_syslog("file_manager.php: Perizia directory created successfully", LOG_DEBUG);
            dol_syslog("file_manager.php: Directory permissions: " .
                substr(sprintf('%o', fileperms($upload_dir)), -4), LOG_DEBUG);
        } else {
            dol_syslog("file_manager.php: ERROR - Failed to create perizia directory", LOG_ERR);
            // Aggiungi un messaggio visibile all'utente
            setEventMessages(sprintf("Errore durante la creazione della directory: %s", $upload_dir), null, 'errors');
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
        if (!$user->admin && !$user->rights->industria40->write) {
            setEventMessages("Accesso negato", null, 'errors');
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
                if (!empty($name)) {
                    $tmp_name = $_FILES['files']['tmp_name'][$key];

                    // Check for upload errors
                    if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) {
                        $error_message = "Error code: " . $_FILES['files']['error'][$key];
                        dol_syslog("file_manager.php: File upload error: " . $error_message, LOG_ERR);
                        setEventMessages("Errore durante il caricamento del file" . " (" . $error_message . ")", null, 'errors');
                        continue;
                    }

                    // Sanitize filename
                    $sanitized_name = dol_sanitizeFileName(basename($name));
                    $destination = $upload_dir . $sanitized_name;

                    // Ensure directory has proper permissions before moving file
                    dol_syslog("file_manager.php: Uploading file '$tmp_name' to '$destination'", LOG_DEBUG);

                    // Modifica alla sezione di creazione thumbnail durante l'upload
                    if (move_uploaded_file($tmp_name, $destination)) {
                        dol_syslog("file_manager.php: Successfully moved '$tmp_name' to '$destination'", LOG_DEBUG);
                        setEventMessages(sprintf("File caricato: %s", $sanitized_name), null, 'mesgs');

                        // Miglioriamo il supporto per la generazione di thumbnail dei PDF
                        $file_extension = strtolower(pathinfo($sanitized_name, PATHINFO_EXTENSION));
                        if ($file_extension == 'pdf') {
                            try {
                                // Create thumbnails directory structure
                                $thumb_dir_base = DOL_DATA_ROOT . '/industria40/thumbnails';
                                if (!is_dir($thumb_dir_base)) dol_mkdir($thumb_dir_base);

                                $thumb_dir = $thumb_dir_base . '/' . $socid;
                                if (!is_dir($thumb_dir)) dol_mkdir($thumb_dir);

                                $thumb_dir .= '/' . $periziaid_sanitized;
                                if (!is_dir($thumb_dir)) dol_mkdir($thumb_dir);

                                $thumbnail_path = $thumb_dir . '/thumb_' . pathinfo($sanitized_name, PATHINFO_FILENAME) . '.jpg';

                                // Verifichiamo se la thumbnail esiste già prima di generarla
                                if (!file_exists($thumbnail_path)) {
                                    // Tenta la conversione con ImageMagick (dopo aver modificato il policy.xml)
                                    dol_syslog("file_manager.php: Thumbnail not found, creating at $thumbnail_path", LOG_DEBUG);
                                    if (function_exists('exec')) {
                                        // Aumentiamo densità e qualità per migliori thumbnail
                                        $density = "300"; // Densità maggiore per qualità più alta
                                        $quality = "95"; // Qualità maggiore
                                        $size = "600x600"; // Dimensione maggiore per più dettagli

                                        // Per uniformità con le immagini, impostiamo una dimensione più consistente
                                        $width = "600";
                                        $height = "600";
                                        $command = "convert -density $density \"$destination\"[0] -quality $quality -resize ${width}x${height} \"$thumbnail_path\"";
                                        exec($command, $output, $return_var);

                                        // Dopo aver generato la thumbnail, verifichiamo se esiste un tag associato
                                        $tags_dir = DOL_DATA_ROOT . '/industria40/tags';
                                        if (!is_dir($tags_dir)) dol_mkdir($tags_dir);

                                        $tags_file = $tags_dir . '/file_tags.json';
                                        if (!file_exists($tags_file)) {
                                            file_put_contents($tags_file, json_encode([]));
                                        }

                                        if ($return_var === 0) {
                                            dol_syslog("file_manager.php: Successfully created PDF thumbnail", LOG_DEBUG);
                                        } else {
                                            dol_syslog("file_manager.php: Failed to create PDF thumbnail: " . implode(" ", $output), LOG_WARNING);
                                        }
                                    }
                                } else {
                                    dol_syslog("file_manager.php: Thumbnail already exists, skipping creation for: $thumbnail_path", LOG_INFO);
                                }
                            } catch (Exception $e) {
                                dol_syslog("file_manager.php: Error in thumbnail creation: " . $e->getMessage(), LOG_ERR);
                            }
                        }

                        // Esegui OCR sul file caricato (per PDF e immagini)
                        if (in_array($file_extension, array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'tiff', 'tif'))) {
                            // Verifichiamo se esiste già un file di testo estratto
                            $text_file_path = pathinfo($destination, PATHINFO_DIRNAME) . '/' .
                                              pathinfo($destination, PATHINFO_FILENAME) . '.txt';

                            if (!file_exists($text_file_path)) {
                                dol_syslog("file_manager.php: No extracted text found, performing OCR on: $destination", LOG_INFO);
                                perform_ocr_on_file($destination, $file_extension);
                            } else {
                                dol_syslog("file_manager.php: Extracted text already exists, skipping OCR for: $text_file_path", LOG_INFO);
                                // Carichiamo comunque il testo per l'analisi AI
                                $ocr_text = file_get_contents($text_file_path);
                                if (!empty($ocr_text)) {
                                    $ocr_analysis = analyze_ocr_content($ocr_text, $sanitized_name, $file_extension);
                                    // Auto-tag se l'analisi è andata a buon fine
                                    if (!empty($ocr_analysis['suggested_tag'])) {
                                        // Crea una chiave univoca che include socid e periziaid
                                        $file_key = $socid . '_' . $periziaid_sanitized . '_' . $sanitized_name;

                                        // Salva tag suggerito
                                        $tags_dir = DOL_DATA_ROOT . '/industria40/tags';
                                        if (!is_dir($tags_dir)) dol_mkdir($tags_dir);

                                        $tags_file = $tags_dir . '/file_tags.json';
                                        if (!file_exists($tags_file)) {
                                            file_put_contents($tags_file, json_encode([]));
                                        }

                                        $tags_data = json_decode(file_get_contents($tags_file), true);
                                        if (!is_array($tags_data)) $tags_data = [];

                                        $tags_data[$file_key] = $ocr_analysis['suggested_tag'];
                                        file_put_contents($tags_file, json_encode($tags_data));
                                    }
                                }
                            }
                        }
                    } else {
                        dol_syslog("file_manager.php: Failed to move '$tmp_name' to '$destination'. Error code: " . $_FILES['files']['error'][$key], LOG_ERR);
                        setEventMessages(sprintf("Errore durante il caricamento del file: %s", $sanitized_name) . " (Error code: " . $_FILES['files']['error'][$key] . ")", null, 'errors');
                    }
                }
            }
        }
    }

    // Handle file renaming
    if ($action == 'rename_files') {
        // Basic permission check (Example: only admin can rename)
        if (!$user->admin) {
            setEventMessages("Accesso negato", null, 'errors');
            $action = ''; // Prevent further processing
        } else {
            dol_syslog("file_manager.php: Handling action 'rename_files'", LOG_DEBUG);

            // Controlla se stiamo rinominando un singolo file
            $single_file_rename = GETPOST('rename_single_file', 'alpha');
            dol_syslog("file_manager.php: single_file_rename = " . $single_file_rename, LOG_DEBUG);

            if (!empty($single_file_rename)) {
                // Rinomina solo il file specificato
                $original_name = $single_file_rename;
                $key = 'new_name_' . $original_name;
                $new_name = GETPOST($key, 'alpha');

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

                            // Aggiorna anche eventuali file txt associati per OCR
                            $txt_original = pathinfo($original_path, PATHINFO_DIRNAME) . '/' . pathinfo($original_path, PATHINFO_FILENAME) . '.txt';
                            $txt_new = pathinfo($new_path, PATHINFO_DIRNAME) . '/' . pathinfo($new_path, PATHINFO_FILENAME) . '.txt';

                            if (file_exists($txt_original)) {
                                rename($txt_original, $txt_new);
                                dol_syslog("file_manager.php: Also renamed txt file from '$txt_original' to '$txt_new'", LOG_DEBUG);
                            }

                            // Aggiorna tag e descrizioni con la nuova chiave
                            update_file_references($socid, $periziaid_sanitized, $original_name, $sanitized_new_name);

                            setEventMessages(sprintf("File rinominato da %s a %s", $original_name, $sanitized_new_name), null, 'mesgs');
                        } else {
                            dol_syslog("file_manager.php: Failed to rename '$original_path' to '$new_path'. Check permissions.", LOG_ERR);
                            setEventMessages(sprintf("Errore durante la rinomina del file %s", $original_name), null, 'errors');
                        }
                    } else {
                        dol_syslog("file_manager.php: Original file '$original_path' not found for renaming.", LOG_ERR);
                        setEventMessages(sprintf("Errore: File non trovato: %s", $original_name), null, 'errors');
                    }
                } elseif ($original_path == $new_path) {
                    dol_syslog("file_manager.php: Skipped renaming '$original_name' as new name is the same.", LOG_DEBUG);
                    setEventMessages("Nessuna modifica da applicare", null, 'warnings');
                } else {
                    dol_syslog("file_manager.php: Skipped renaming '$original_name' as new name is empty.", LOG_WARNING);
                    setEventMessages("Errore: Il nuovo nome non può essere vuoto.", null, 'warnings');
                }

                // Dopo il rename, reindirizza alla stessa pagina per aggiornare la visualizzazione
                header('Location: ' . $form_action_url);
                exit;
            } else {
                // Rinomina multipla (codice esistente)
                foreach ($_POST as $key => $new_name) {
                    if (strpos($key, 'new_name_') === 0) {
                        // Decode the original name which might have been urlencoded or html escaped in the form
                        $original_name_encoded = substr($key, 9);
                        $original_name = dol_unescape_htmltag($original_name_encoded); // Use appropriate unescaping if needed

                        $sanitized_new_name = dol_sanitizeFileName($new_name);
                        $original_path = $upload_dir . $original_name; // Use new $upload_dir
                        $new_path = $upload_dir . $sanitized_new_name; // Use new $upload_dir

                        // Prevent renaming to the same name or empty name
                        if ($original_path != $new_path && !empty($sanitized_new_name)) {
                            if (file_exists($original_path)) {
                                if (rename($original_path, $new_path)) {
                                    dol_syslog("file_manager.php: Successfully renamed '$original_path' to '$new_path'", LOG_DEBUG);
                                    setEventMessages(sprintf("File rinominato da %s a %s", $original_name, $sanitized_new_name), null, 'mesgs');
                                } else {
                                    dol_syslog("file_manager.php: Failed to rename '$original_path' to '$new_path'. Check permissions.", LOG_ERR);
                                    setEventMessages(sprintf("Errore durante la rinomina del file %s", $original_name), null, 'errors');
                                }
                            } else {
                                dol_syslog("file_manager.php: Original file '$original_path' not found for renaming.", LOG_ERR);
                                setEventMessages(sprintf("Errore: File non trovato: %s", $original_name), null, 'errors');
                            }
                        } elseif ($original_path == $new_path) {
                            dol_syslog("file_manager.php: Skipped renaming '$original_name' as new name is the same.", LOG_DEBUG);
                        } else {
                            dol_syslog("file_manager.php: Skipped renaming '$original_name' as new name is empty.", LOG_WARNING);
                            setEventMessages("Errore: Il nuovo nome non può essere vuoto.", null, 'warnings');
                        }
                    }
                }
            }
        }
    }

    // Handle single file removal
    if ($action == 'remove_file' && !empty($file_to_remove)) {
        // Basic permission check (Example: only admin can remove)
        if (!$user->admin) {
            setEventMessages("Accesso negato", null, 'errors');
            $action = ''; // Prevent further processing
        } else {
            dol_syslog("file_manager.php: Handling action 'remove_file' for file: " . $file_to_remove, LOG_DEBUG);
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
                    dol_syslog("file_manager.php: Successfully removed file: " . $file_path, LOG_INFO);
                    setEventMessages(sprintf("File rimosso: %s", $sanitized_file_to_remove), null, 'mesgs');

                    // Remove associated AI data and OCR text file
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
        if (!$user->admin) {
            setEventMessages("Accesso negato", null, 'errors');
            $action = ''; // Prevent further processing
        } else {
            dol_syslog("file_manager.php: Handling action 'remove_all_files' for directory: " . $upload_dir, LOG_DEBUG);
            $files_removed = 0;
            $files_failed = 0;
            if (is_dir($upload_dir)) {
                $files = scandir($upload_dir);
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..') {
                        $file_path = $upload_dir . $file;
                        if (is_file($file_path)) { // Ensure it's a file
                            if (unlink($file_path)) {
                                $files_removed++;
                                // Remove associated AI data and OCR text file
                                remove_associated_file_data($socid, $periziaid_sanitized, $file, $upload_dir);
                            } else {
                                $files_failed++;
                                dol_syslog("file_manager.php: Failed to remove file during 'remove_all_files': " . $file_path, LOG_ERR);
                            }
                        }
                    }
                }
                if ($files_failed > 0) {
                    setEventMessages(sprintf("Errore: Impossibile rimuovere %s file.", $files_failed), null, 'errors');
                }
                if ($files_removed > 0) {
                    setEventMessages(sprintf("%s file rimossi.", $files_removed), null, 'mesgs');
                } else {
                    setEventMessages("Nessun file da rimuovere.", null, 'warnings');
                }
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
        if (!is_dir($tags_dir)) dol_mkdir($tags_dir);

        $tags_file = $tags_dir . '/file_tags.json';
        if (!file_exists($tags_file)) {
            file_put_contents($tags_file, json_encode([]));
        }

        $tags_data = json_decode(file_get_contents($tags_file), true);
        if (!is_array($tags_data)) $tags_data = [];

        // Creiamo una chiave univoca che include socid e periziaid
        $file_key = $socid . '_' . $periziaid_sanitized . '_' . $file_name;
        $tags_data[$file_key] = $file_tag;

        file_put_contents($tags_file, json_encode($tags_data));
        setEventMessages(sprintf("Etichetta impostata per %s", $file_name), null, 'mesgs');
    }

    // Aggiungiamo l'azione per ottenere descrizioni via ChatGPT
    if ($action == 'get_description') {
        // Ottieni il nome del file sia da GET che da POST per assicurarsi di catturarlo comunque
        $file_name = GETPOST('file_name', 'alpha');

        if (!empty($file_name)) {
            $file_path_relative = 'documents/' . $socid . '/' . $periziaid_sanitized . '/' . $file_name;
            $file_path = $upload_dir . $file_name;
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Directory per salvare le descrizioni
            $desc_dir = DOL_DATA_ROOT . '/industria40/descriptions';
            if (!is_dir($desc_dir)) dol_mkdir($desc_dir);

            $desc_file = $desc_dir . '/file_descriptions.json';
            if (!file_exists($desc_file)) {
                file_put_contents($desc_file, json_encode([]));
            }

            $desc_data = json_decode(file_get_contents($desc_file), true);
            if (!is_array($desc_data)) $desc_data = [];

            $file_key = $socid . '_' . $periziaid_sanitized . '_' . $file_name;

            // Log informativo per debug
            dol_syslog("file_manager.php: Generating description for file: " . $file_name . ", extension: " . $file_extension, LOG_DEBUG);

            // Verifica se abbiamo già una risposta AI memorizzata per questo file
            $ai_response = get_stored_ai_response($file_key);
            if ($ai_response !== false) {
                dol_syslog("file_manager.php: Found stored AI response for file: " . $file_name, LOG_INFO);
                $desc_data[$file_key] = $ai_response;
                file_put_contents($desc_file, json_encode($desc_data));
                setEventMessages(sprintf("Descrizione recuperata per %s", $file_name), null, 'mesgs');

                // Redirect back to the same page to display the retrieved description
                header('Location: ' . $form_action_url);
                exit;
            }

            // Chiave API di OpenAI: Prioritize .env, then Dolibarr config
            $openai_api_key = getenv('OPENAI_API_KEY'); // Check .env first
            if (empty($openai_api_key)) {
                $openai_api_key = !empty($conf->global->INDUSTRIA40_OPENAI_API_KEY) ? $conf->global->INDUSTRIA40_OPENAI_API_KEY : '';
                if (!empty($openai_api_key)) {
                     dol_syslog("file_manager.php: get_description: Using OpenAI key from Dolibarr config.", LOG_DEBUG);
                }
            } else {
                 dol_syslog("file_manager.php: get_description: Using OpenAI key from .env file.", LOG_DEBUG);
            }


            if (empty($openai_api_key)) {
                setEventMessages("Chiave API OpenAI non configurata", null, 'warnings');
                // Log the warning and prevent further API calls in this block
                dol_syslog("file_manager.php: get_description: OpenAI API key not configured in .env or Dolibarr settings. Skipping description generation for '$file_name'.", LOG_WARNING);
            } else {
                try {
                    $content_to_analyze = '';

                    // Per le immagini, invia direttamente il file
                    if (in_array($file_extension, array('jpg', 'jpeg', 'png', 'gif'))) {
                        $image_data = file_get_contents($file_path);
                        if ($image_data !== false) {
                            $base64_image = base64_encode($image_data);

                            // Chiama l'API di OpenAI per la descrizione dell'immagine
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                'Content-Type: application/json',
                                'Authorization: Bearer ' . $openai_api_key
                            ]);

                            // Utilizziamo la versione corretta del modello
                            $request_data = [
                                'model' => 'gpt-4o', // Aggiornato da gpt-4-vision-preview a gpt-4o
                                'messages' => [
                                    [
                                        'role' => 'user',
                                        'content' => [
                                            [
                                                'type' => 'text',
                                                // usa i tag predefiniti (assigned_tags) per iniziare la descrizione
                                                'text' => 'Estrai le informazioni testuali dall\'immagine.' // Testo semplificato per evitare warning se $assigned_tags non è disponibile
                                                . (isset($assigned_tags) && is_array($assigned_tags) && !empty($assigned_tags) ? ' Utilizzando i seguenti tag: ' . implode(', ', $assigned_tags) . '.' : '')
                                                . ' Riporta le informazioni testuali in maniera organizzata (massimo 150 caratteri, in italiano)'
                                                . ' Privilegia le informazioni come: indirizzo IP, Marca, Modello, Matricola, Anno, Omologazione, S/N, Targa'
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
                                'max_tokens' => 150
                            ];

                            dol_syslog("file_manager.php: Calling OpenAI API for image description with model gpt-4o", LOG_DEBUG);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
                            $response = curl_exec($ch);
                            $curl_info = curl_getinfo($ch);
                            $curl_error = curl_error($ch);
                            curl_close($ch);

                            // Log più dettagliati per il debug
                            dol_syslog("file_manager.php: API Response HTTP Status: " . $curl_info['http_code'], LOG_DEBUG);
                            if (!empty($curl_error)) {
                                dol_syslog("file_manager.php: cURL Error: " . $curl_error, LOG_ERR);
                            }

                            if ($response) {
                                $decoded_response = json_decode($response, true);
                                if (isset($decoded_response['choices'][0]['message']['content'])) {
                                    $description = $decoded_response['choices'][0]['message']['content'];
                                    $desc_data[$file_key] = $description;

                                    // Memorizza la risposta AI per future richieste
                                    store_ai_response($file_key, $description);

                                    dol_syslog("file_manager.php: Got image description from OpenAI: " . substr($description, 0, 100), LOG_DEBUG);
                                } else {
                                    // Log più dettagliato della risposta
                                    dol_syslog("file_manager.php: Invalid response from OpenAI: " . print_r($decoded_response, true), LOG_WARNING);
                                    if (isset($decoded_response['error'])) {
                                        $desc_data[$file_key] = "Errore API: " . $decoded_response['error']['message'];
                                    } else {
                                        $desc_data[$file_key] = "Descrizione non disponibile";
                                    }
                                }
                            } else {
                                dol_syslog("file_manager.php: No response from OpenAI API", LOG_WARNING);
                                $desc_data[$file_key] = "Errore nella generazione della descrizione";
                            }
                        }
                    }
                    // Per i PDF, aggiorniamo anche questo modello
                    elseif ($file_extension == 'pdf') {
                        // Chiediamo a ChatGPT di generare una descrizione generica
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Content-Type: application/json',
                            'Authorization: Bearer ' . $openai_api_key
                        ]);

                        // Aggiorniamo anche qui il modello
                        $request_data = [
                            'model' => 'gpt-4o', // Aggiornato
                            'messages' => [
                                [
                                    'role' => 'system',
                                    'content' => 'Sei un assistente che genera descrizioni brevi di documenti PDF.'
                                ],
                                [
                                    'role' => 'user',
                                    'content' => "Genera una breve descrizione generica per un documento PDF chiamato '" . $file_name . "' (massimo 100 caratteri, in italiano)"
                                ]
                            ],
                            'max_tokens' => 150
                        ];

                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
                        $response = curl_exec($ch);
                        $curl_info = curl_getinfo($ch);
                        curl_close($ch);

                        dol_syslog("file_manager.php: API Response HTTP Status for PDF: " . $curl_info['http_code'], LOG_DEBUG);

                        if ($response) {
                            $decoded_response = json_decode($response, true);
                            if (isset($decoded_response['choices'][0]['message']['content'])) {
                                $description = $decoded_response['choices'][0]['message']['content'];
                                $desc_data[$file_key] = $description;

                                // Memorizza la risposta AI per future richieste
                                store_ai_response($file_key, $description);
                            } else {
                                if (isset($decoded_response['error'])) {
                                    $desc_data[$file_key] = "Errore API: " . $decoded_response['error']['message'];
                                } else {
                                    $desc_data[$file_key] = "Descrizione non disponibile per PDF";
                                }
                            }
                        }
                    }

                    // Salvare le descrizioni nel file JSON
                    file_put_contents($desc_file, json_encode($desc_data));
                    setEventMessages(sprintf("Descrizione generata per %s", $file_name), null, 'mesgs');

                } catch (Exception $e) {
                    dol_syslog("file_manager.php: Error generating description: " . $e->getMessage(), LOG_ERR);
                    setEventMessages("Errore nella generazione della descrizione" . ": " . $e->getMessage(), null, 'errors');
                }
            }

            // Redirect back to the same page to display the new description
            header('Location: ' . $form_action_url);
            exit;
        } else {
            dol_syslog("file_manager.php: No file name provided for description generation", LOG_WARNING);
            setEventMessages("Nome file non specificato", null, 'errors');
        }
    }

    // Handle forcing OCR on existing files
    if ($action == 'force_ocr' && !empty($_POST['file_name'])) {
        $file_name = GETPOST('file_name', 'alpha');

        if (!empty($file_name)) {
            $file_path = $upload_dir . $file_name;
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Verifichiamo se il file di testo esiste già
            $text_file_path = pathinfo($file_path, PATHINFO_DIRNAME) . '/' .
                              pathinfo($file_path, PATH_INFO_FILENAME) . '.txt';

            $skip_ocr = false;
            if (file_exists($text_file_path) && filesize($text_file_path) > 0) {
                // Il file di testo esiste già, carica il contenuto esistente
                $ocr_text = file_get_contents($text_file_path);
                if (!empty($ocr_text)) {
                    $skip_ocr = true;
                    dol_syslog("file_manager.php: OCR text already exists, using existing file: $text_file_path", LOG_INFO);
                    setEventMessages("Analisi saltata, file già elaborato", null, 'mesgs');
                }
            }

            if (in_array($file_extension, array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'tiff', 'tif'))) {
                $extracted_text = '';

                if (!$skip_ocr) {
                    // Esegui OCR solo se necessario
                    $extracted_text = perform_ocr_on_file($file_path, $file_extension);
                    if (!empty($extracted_text)) {
                        setEventMessages(sprintf("OCR completato per %s", $file_name), null, 'mesgs');
                    } else {
                        setEventMessages("Tipo di file non supportato per OCR", null, 'warnings');
                    }
                } else {
                    // Usa il testo già estratto
                    $extracted_text = $ocr_text;
                }

                // In entrambi i casi, esegui l'analisi del contenuto
                if (!empty($extracted_text)) {
                    $ocr_analysis = analyze_ocr_content($extracted_text, $file_name, $file_extension);

                    // Applica automaticamente il tag suggerito
                    if (!empty($ocr_analysis['suggested_tag'])) {
                        $tags_dir = DOL_DATA_ROOT . '/industria40/tags';
                        if (!is_dir($tags_dir)) dol_mkdir($tags_dir);

                        $tags_file = $tags_dir . '/file_tags.json';
                        if (!file_exists($tags_file)) {
                            file_put_contents($tags_file, json_encode([]));
                        }

                        $tags_data = json_decode(file_get_contents($tags_file), true);
                        if (!is_array($tags_data)) $tags_data = [];

                        $file_key = $socid . '_' . $periziaid_sanitized . '_' . $file_name;
                        $tags_data[$file_key] = $ocr_analysis['suggested_tag'];
                        file_put_contents($tags_file, json_encode($tags_data));
                    }

                    // Mostra messaggio di analisi completata
                    if ($skip_ocr) {
                        setEventMessages("Analisi del file completata." . " (" . "Riduzione carico di sistema" . ")", null, 'mesgs');
                    } else {
                        setEventMessages("Analisi del file completata.", null, 'mesgs');
                    }
                } else {
                    setEventMessages("L'analisi del file non ha prodotto risultati.", null, 'warnings');
                }
            } else {
                setEventMessages("Tipo di file non supportato per OCR", null, 'warnings');
            }
        }

        // Redirect to prevent resubmission
        header('Location: ' . $form_action_url);
        exit;
    }

    // Azione per gestire il cambio nome del file
    if ($action == 'rename_file') {
        $file_name = GETPOST('file_name', 'alpha');
        $new_name = GETPOST('new_name', 'alpha');

        dol_syslog("file_manager.php: Rinomina file richiesta per file: " . $file_name . " in: " . $new_name, LOG_DEBUG);

        if (!empty($file_name) && !empty($new_name)) {
            // Sanificare i nomi file
            $file_name = dol_sanitizeFileName($file_name);
            $new_name = dol_sanitizeFileName($new_name);

            // Verifica se i file esistono e sono accessibili
            $old_path = $upload_dir . $file_name;
            $new_path = $upload_dir . $new_name;

            dol_syslog("file_manager.php: Percorso vecchio: " . $old_path, LOG_DEBUG);
            dol_syslog("file_manager.php: Percorso nuovo: " . $new_path, LOG_DEBUG);

            // Verifica permessi di scrittura sulla directory
            if (!is_writable($upload_dir)) {
                dol_syslog("file_manager.php: ERRORE - Directory non scrivibile: " . $upload_dir, LOG_ERR);
                setEventMessages("La directory non è scrivibile.", null, 'errors');
            }
            // Verifica esistenza del file originale
            elseif (!file_exists($old_path)) {
                dol_syslog("file_manager.php: ERRORE - File non trovato: " . $old_path, LOG_ERR);
                setEventMessages(sprintf("Errore: File non trovato: %s", $file_name), null, 'errors');
            }
            // Verifica che il nuovo nome non esista già
            elseif (file_exists($new_path) && $file_name != $new_name) {
                dol_syslog("file_manager.php: ERRORE - Nuovo nome file già esistente: " . $new_path, LOG_ERR);
                setEventMessages(sprintf("File già esistente: %s", $new_name), null, 'errors');
            }
            else {
                try {
                    // Usa rename() con gestione errori migliorata
                    dol_syslog("file_manager.php: Tentativo di rinominare il file da " . $old_path . " a " . $new_path, LOG_DEBUG);

                    // Tenta l'operazione di rinomina
                    $rename_result = @rename($old_path, $new_path);

                    if ($rename_result) {
                        dol_syslog("file_manager.php: File rinominato con successo", LOG_INFO);
                        // Aggiorna anche le miniature se esistono
                        $thumb_dir = $conf->industria40->dir_output . '/thumbnails/' . $socid . '/' . $periziaid_sanitized;
                        $old_thumb = $thumb_dir . '/thumb_' . pathinfo($file_name, PATHINFO_FILENAME) . '.jpg';
                        $new_thumb = $thumb_dir . '/thumb_' . pathinfo($new_name, PATHINFO_FILENAME) . '.jpg';

                        if (file_exists($old_thumb)) {
                            @rename($old_thumb, $new_thumb);
                        }

                        // Aggiorna anche la cache delle descrizioni
                        $desc_dir = DOL_DATA_ROOT . '/industria40/descriptions';
                        $desc_file = $desc_dir . '/file_descriptions.json';
                        if (file_exists($desc_file)) {
                            $desc_data = json_decode(file_get_contents($desc_file), true);
                            $old_key = $socid . '_' . $periziaid_sanitized . '_' . $file_name;
                            $new_key = $socid . '_' . $periziaid_sanitized . '_' . $new_name;

                            if (isset($desc_data[$old_key])) {
                                $desc_data[$new_key] = $desc_data[$old_key];
                                unset($desc_data[$old_key]);
                                file_put_contents($desc_file, json_encode($desc_data));
                            }
                        }

                        // Aggiorna anche la cache delle risposte AI
                        $ai_dir = DOL_DATA_ROOT . '/industria40/ai_responses';
                        $ai_file = $ai_dir . '/stored_responses.json';
                        if (file_exists($ai_file)) {
                            $ai_data = json_decode(file_get_contents($ai_file), true);
                            if (is_array($ai_data)) {
                                $old_keys = [
                                    $socid . '_' . $periziaid_sanitized . '_' . $file_name,
                                    $socid . '_' . $periziaid_sanitized . '_' . $file_name . '_ocr_analysis'
                                ];
                                $new_keys = [
                                    $socid . '_' . $periziaid_sanitized . '_' . $new_name,
                                    $socid . '_' . $periziaid_sanitized . '_' . $new_name . '_ocr_analysis'
                                ];

                                foreach ($old_keys as $index => $old_key) {
                                    if (isset($ai_data[$old_key])) {
                                        $ai_data[$new_keys[$index]] = $ai_data[$old_key];
                                        unset($ai_data[$old_key]);
                                    }
                                }
                                file_put_contents($ai_file, json_encode($ai_data));
                            }
                        }

                        setEventMessages(sprintf("File rinominato da %s a %s", $file_name, $new_name), null, 'mesgs');
                    } else {
                        // Ottieni dettagli errore
                        $error_msg = error_get_last();
                        dol_syslog("file_manager.php: ERRORE rinomina - " . ($error_msg ? $error_msg['message'] : 'Errore sconosciuto'), LOG_ERR);

                        // Verifica specifici problemi di permessi
                        $perms_issue = false;
                        if (function_exists('posix_getuid') && function_exists('fileowner')) {
                            $file_owner = fileowner($old_path);
                            $process_owner = posix_getuid();
                            dol_syslog("file_manager.php: File owner: " . $file_owner . ", Process owner: " . $process_owner, LOG_DEBUG);
                            if ($file_owner !== $process_owner) {
                                $perms_issue = true;
                            }
                        }

                        if ($perms_issue) {
                            setEventMessages(sprintf("Errore nei permessi del file %s", $file_name), null, 'errors');
                        } else {
                            setEventMessages(sprintf("Errore durante il salvataggio del nome del file: %s", $error_msg ? $error_msg['message'] : "Errore sconosciuto"), null, 'errors');
                        }
                    }
                } catch (Exception $e) {
                    dol_syslog("file_manager.php: Eccezione durante la rinomina del file: " . $e->getMessage(), LOG_ERR);
                    setEventMessages(sprintf("Errore durante la rinomina del file %s", $file_name) . ": " . $e->getMessage(), null, 'errors');
                }
            }
        } elseif (empty($new_name)) {
            dol_syslog("file_manager.php: ERRORE - Nuovo nome file vuoto", LOG_ERR);
            setEventMessages("Errore: Il nuovo nome non può essere vuoto.", null, 'errors');
        } else {
            dol_syslog("file_manager.php: ERRORE - Nome file originale non specificato", LOG_ERR);
            setEventMessages("Nome file non specificato", null, 'errors');
        }

        // Redirect back to file manager
        header('Location: ' . $form_action_url);
        exit;
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
    print "Trascina file e cartelle qui, o clicca per selezionare"; // E.g., "Trascina file e cartelle qui, o clicca per selezionare"
    print '<div id="fileSelectionInfo" style="margin-top:10px; color:#2c5987; font-size:0.9em;"></div>';
    print '</div>';
    // Modifica lo stile dell'input file qui sotto
    print '<input type="file" id="actualFileInput" name="files[]" multiple webkitdirectory style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0;">';
    print '<div style="margin-top:10px;">';
    // Add the new "+" button here
    print '<button type="button" id="addFilesButton" class="button button-add" title="' . "Aggiungi File/Cartelle" . '"><i class="fa fa-plus"></i></button> '; // Using Font Awesome icon
    print '<button type="submit" class="button">' . "Carica File" . '</button>';
    print '</div>'; // Closing the div that contains the buttons
    print '</div>';
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
        'foto' => 'Foto'];
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

print '<form id="renameForm" action="' . $form_action_url . '" method="POST">';
print '<input type="hidden" name="action" value="rename_files">';
print '<input type="hidden" name="socid" value="' . $socid . '">';
print '<input type="hidden" name="periziaid" value="' . dol_escape_htmltag($periziaid_sanitized) . '">'; // Pass periziaid
print '<table class="border" id="fileTable">';
print '<thead><tr>';
print '<th>' . "Nome File" . ' / ' . "Anteprima" . '</th>';
print '<th>' . "Descrizione" . '</th>'; // Nuova colonna per le descrizioni
print '<th>' . "Nuovo Nome" . '</th>';
print '<th class="right">' . "Azione" . '</th>'; // Added Action column header
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
            //print '<div class="preview-container">';
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
                        print '<div class="file-status-tooltip"><div class="file-processed" title="' . "File già analizzato" . '">✓</div>';
                        print '<span class="tooltiptext">' . "Testo già estratto" . '</span></div>';
                    }

                    print '</a>';

                    // Visualizza il tag se presente
                    $file_key = $socid . '_' . $periziaid_sanitized . '_' . $file;
                    if (isset($file_tags[$file_key])) {
                        print '<div class="tag-container">';
                        print '<span class="file-tag">' . $available_tags[$file_tags[$file_key]] . '</span>';
                        print '</div>';
                    }

                    print '<div class="filename-container">' . dol_escape_htmltag($file) . '</div>';
                    print '</div>'; // Fine file-preview-container
                    break;

                case 'pdf':
                    // Verifica se esiste un thumbnail per questo PDF
                    $thumbnail_path = DOL_DATA_ROOT . '/industria40/thumbnails/' . $socid . '/' . $periziaid_sanitized . '/thumb_' . pathinfo($file, PATHINFO_FILENAME) . '.jpg';
                    $has_thumbnail = file_exists($thumbnail_path);

                    print '<div class="file-preview-container">';

                    if ($has_thumbnail) {
                        // Mostra thumbnail per PDF
                        $thumb_rel_path = 'thumbnails/' . $socid . '/' . $periziaid_sanitized . '/thumb_' . pathinfo($file, PATHINFO_FILENAME) . '.jpg';
                        $thumb_url = DOL_URL_ROOT . '/document.php?modulepart=industria40&file=' . urlencode($thumb_rel_path) . '&entity=' . $conf->entity;

                        print '<a href="' . $file_url . '" target="_blank" class="zoom-container">';
                        print '<img src="' . $thumb_url . '" class="preview-image" alt="PDF Thumbnail">';

                        // Aggiungi indicatore se il file è stato processato
                        $text_file_path = $upload_dir . pathinfo($file, PATHINFO_FILENAME) . '.txt';
                        if (file_exists($text_file_path) && filesize($text_file_path) > 0) {
                            print '<div class="file-status-tooltip"><div class="file-processed" title="' . "File già analizzato" . '">✓</div>';
                            print '<span class="tooltiptext">' . "Testo già estratto" . '</span></div>';
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
                        print '<div class="tag-container">';
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
                        print '<div class="tag-container">';
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
                        print '<div class="tag-container">';
                        print '<span class="file-tag">' . $available_tags[$file_tags[$file_key]] . '</span>';
                        print '</div>';
                    }
                    print '</div>'; // Fine file-preview-container
                    break;
            }
            print '</td>';
            dol_syslog("file_manager.php: Finished preview column for '$file'", LOG_DEBUG); // Log preview end

            // Colonna descrizione
            print '<td class="description-column">';
            dol_syslog("file_manager.php: Generating description column for '$file'", LOG_DEBUG); // Log description start
            $file_key_desc = $socid . '_' . $periziaid_sanitized . '_' . $file; // Use a different variable name to avoid conflict if $file_key is used differently above
            dol_syslog("file_manager.php: File key for description: '$file_key_desc'", LOG_DEBUG); // Log file key

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
                    print '<button type="submit" class="generate-desc-btn">' . "Genera descrizione" . '</button>';
                    print '</form>';
                } else {
                    dol_syslog("file_manager.php: Not displaying 'Generate Description' button for '$file' (extension: $file_extension)", LOG_DEBUG); // Log button skip
                    print '<span class="opacitymedium">' . "Nessuna descrizione" . '</span>';
                }
            }
            dol_syslog("file_manager.php: After description display/button logic for '$file'", LOG_DEBUG); // Log after description/button

            // NON è più necessario caricare $ocr_text e $ocr_analysis qui, sono già stati caricati prima della colonna di anteprima
            // $ocr_text è già disponibile
            // $ocr_analysis è già disponibile

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
                    print '<div class="suggestion"><span class="label">' . "Nome suggerito" . ':</span> ' . dol_escape_htmltag($ocr_analysis['suggested_name']) . '</div>';
                    print '<button type="submit" class="rename-suggested-btn">' . "Rinomina con Suggerimento" . '</button>';
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
                print '<button type="submit" class="button">' . "Analizza File" . '</button>';
                print '</form>';
                print '</div>';
            } else {
                 dol_syslog("file_manager.php: Not displaying 'Analyze File' button for '$file' (extension: $file_extension, ocr_text empty: " . (empty($ocr_text) ? 'true' : 'false') . ")", LOG_DEBUG); // Log analyze button skip
            }
            dol_syslog("file_manager.php: After 'Analyze File' button logic for '$file'", LOG_DEBUG); // Log after analyze button logic
            print '</td>';
            dol_syslog("file_manager.php: Finished description column for '$file'", LOG_DEBUG); // Log description end

            // Colonna per il nuovo nome file
            print '<td class="rename-container">';
            dol_syslog("file_manager.php: Generating rename column for '$file'", LOG_DEBUG); // Log rename start
            print '<input type="text" name="new_name_' . dol_escape_htmltag($file) . '" value="' . dol_escape_htmltag($file) . '" class="flat">';
            // Aggiungi la spunta per confermare il nuovo nome
            print '<div class="confirm-rename" title="' . "Conferma Rinomina" . '" onclick="confirmRename(\'' . dol_escape_js($file) . '\')"><i class="fa fa-check"></i></div>';
            print '</td>';
            dol_syslog("file_manager.php: Finished rename column for '$file'", LOG_DEBUG); // Log rename end

            // Colonna azioni - sostituiamo il bottone testuale con un'icona
            print '<td class="right">';
            dol_syslog("file_manager.php: Generating action column for '$file'", LOG_DEBUG); // Log action start
            print '<a class="delete-icon" href="' . $form_action_url . '&action=remove_file&file_to_remove=' . urlencode($file) . '" onclick="return confirm(\'' . sprintf("Confermi la rimozione del file %s?", dol_escape_js($file)) . '\');">';
            print '<i class="fa fa-trash" title="' . "Rimuovi" . '"></i>';
            print '</a>';
            print '</td>';
            dol_syslog("file_manager.php: Finished action column for '$file'", LOG_DEBUG); // Log action end
            print '</tr>';
            dol_syslog("file_manager.php: Finished table row for '$file'", LOG_DEBUG); // Log end of row generation
        } else {
            dol_syslog("file_manager.php: Skipping entry: " . $file, LOG_DEBUG); // Log skipped entry
        }
    } // End foreach
    dol_syslog("file_manager.php: Finished iterating through directory entries.", LOG_DEBUG); // Log end of loop
} else {
    // Se la directory non esiste, mostro un messaggio appropriato
    if (!file_exists($upload_dir)) {
        dol_syslog("file_manager.php: Directory does not exist yet: " . $upload_dir, LOG_INFO);
        print '<tr><td colspan="4">' . "La directory di upload verrà creata al primo caricamento." . '</td></tr>';
    }
}

// Se non ci sono file da mostrare ma la directory esiste
if (!$files_exist && is_dir($upload_dir)) {
    print '<tr><td colspan="4">' . "Nessun file caricato per questa perizia." . '</td></tr>';
}

print '</tbody>';
print '</table>';

// Rename Button
if ($files_exist) {
    print '<div class="tabsAction">';
    print '<button type="submit" class="butAction" style="margin-top: 10px;">' . "Salva i nuovi nomi" . '</button>';
    print '</div>';
    print '</form>';

    // Delete All Files Form
    print '<form id="deleteAllForm" action="' . $form_action_url . '" method="POST">';
    print '<input type="hidden" name="action" value="remove_all_files">';
    print '<input type="hidden" name="socid" value="' . $socid . '">';
    print '<input type="hidden" name="periziaid" value="' . dol_escape_htmltag($periziaid_sanitized) . '">'; // Pass periziaid
    print '<button type="submit" class="button-delete-all" onclick="return confirm(\''."Confermi la rimozione di TUTTI i file per questa perizia?".'\');"><i class="fa fa-trash"></i> ' . "Rimuovi Tutti i File" . '</button>';
    print '</form>';
}

print '</div>'; // End filePreview div
// Aggiungi un messaggio di debug per confermare che lo script è stato eseguito completamente
dol_syslog("file_manager.php: Script execution completed successfully", LOG_DEBUG);

} catch (Exception $e) {
    // Gestione degli errori generici
    dol_syslog("file_manager.php: Uncaught exception: " . $e->getMessage(), LOG_ERR);

    // Utilizziamo setEventMessages solo se la funzione esiste ed è inizializzata
    if (function_exists('setEventMessages')) {
        setEventMessages("Si è verificato un errore: " . $e->getMessage(), null, 'errors');
    }

    // Mostra un messaggio di errore anche nell'interfaccia
    print '<div class="error">Si è verificato un errore durante l\'elaborazione. Dettagli: ' . $e->getMessage() . '</div>';
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

    dol_syslog("file_manager.php: Iniziando OCR sul file: $file_path", LOG_INFO);

    // Define output text file path
    $text_file_path = pathinfo($file_path, PATHINFO_DIRNAME) . '/' .
                      pathinfo($file_path, PATHINFO_FILENAME) . '.txt';

    // Verifichiamo se il file di testo esiste già e contiene dati
    if (file_exists($text_file_path) && filesize($text_file_path) > 0) {
        dol_syslog("file_manager.php: OCR text file already exists, skipping OCR for: $text_file_path", LOG_INFO);
        return file_get_contents($text_file_path);
    }

    $extracted_text = '';

    try {
        // Check if tesseract is available
        $tesseract_available = false;
        if (function_exists('exec')) {
            exec('which tesseract', $tesseract_output, $return_var);
            $tesseract_available = ($return_var === 0);

            if ($tesseract_available) {
                dol_syslog("file_manager.php: Tesseract OCR trovato nel sistema: " . implode(" ", $tesseract_output), LOG_INFO);
            } else {
                dol_syslog("file_manager.php: Tesseract OCR non disponibile sul server", LOG_WARNING);
                return $extracted_text;
            }
        } else {
            dol_syslog("file_manager.php: La funzione exec() non è disponibile, OCR non può essere eseguito", LOG_WARNING);
            return $extracted_text;
        }

        // Check if file exists
        if (!file_exists($file_path)) {
            dol_syslog("file_manager.php: File non trovato: $file_path", LOG_WARNING);
            return $extracted_text;
        } else {
            dol_syslog("file_manager.php: File trovato, dimensione: " . filesize($file_path) . " bytes", LOG_INFO);
        }

        // Check if convert command from ImageMagick is available
        exec('which convert', $convert_output, $convert_return);
        if ($convert_return !== 0) {
            dol_syslog("file_manager.php: ImageMagick 'convert' non disponibile sul server, necessario per PDF", LOG_WARNING);
            if ($file_extension == 'pdf') {
                return $extracted_text;
            }
        } else {
            dol_syslog("file_manager.php: ImageMagick 'convert' trovato nel sistema: " . implode(" ", $convert_output), LOG_INFO);
        }

        // Process PDF files
        if ($file_extension == 'pdf') {
            // Convert PDF to images first, then OCR
            $temp_dir = sys_get_temp_dir() . '/' . uniqid('ocr_pdf_');
            if (!mkdir($temp_dir, 0755, true)) {
                dol_syslog("file_manager.php: Impossibile creare la directory temporanea: $temp_dir", LOG_ERR);
                return $extracted_text;
            }
            dol_syslog("file_manager.php: Directory temporanea creata: $temp_dir", LOG_DEBUG);

            // Use imagemagick to convert PDF pages to images
            $cmd = "convert -density 300 \"$file_path\" -quality 100 \"$temp_dir/page_%04d.png\"";
            dol_syslog("file_manager.php: Esecuzione comando di conversione PDF: $cmd", LOG_INFO);

            exec($cmd, $output, $return_var);
            dol_syslog("file_manager.php: Output del comando convert: " . implode("\n", $output), LOG_DEBUG);
            dol_syslog("file_manager.php: Codice di ritorno convert: $return_var", LOG_INFO);

            if ($return_var === 0) {
                $page_files = glob("$temp_dir/page_*.png");
                dol_syslog("file_manager.php: Conversione PDF completata con successo, trovate " . count($page_files) . " pagine", LOG_INFO);

                // Process each page image
                $page_count = 0;
                foreach ($page_files as $page_image) {
                    $page_count++;

                    // Run tesseract on each page
                    $out_base = pathinfo($page_image, PATHINFO_DIRNAME) . '/' .
                                pathinfo($page_image, PATHINFO_FILENAME);
                    $cmd = "tesseract \"$page_image\" \"$out_base\" -l ita+eng";
                    dol_syslog("file_manager.php: Elaborazione OCR pagina $page_count con comando: $cmd", LOG_INFO);

                    exec($cmd, $tesseract_output, $tesseract_return);
                    dol_syslog("file_manager.php: Output tesseract: " . implode("\n", $tesseract_output), LOG_DEBUG);
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
                dol_syslog("file_manager.php: Pulizia dei file temporanei in: $temp_dir", LOG_DEBUG);
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
        }
        // Process image files directly
        else {
            // Use tesseract directly on image
            $out_base = pathinfo($file_path, PATH_INFO_DIRNAME) . '/' .
                        pathinfo($file_path, PATH_INFO_FILENAME) . '_ocr';
            $cmd = "tesseract \"$file_path\" \"$out_base\" -l ita+eng";
            dol_syslog("file_manager.php: Elaborazione OCR immagine con comando: $cmd", LOG_INFO);

            exec($cmd, $output, $return_var);
            dol_syslog("file_manager.php: Output tesseract: " . implode("\n", $output), LOG_DEBUG);
            dol_syslog("file_manager.php: Codice ritorno tesseract: $return_var", LOG_INFO);

            // Read extracted text
            if (file_exists("$out_base.txt")) {
                $extracted_text = file_get_contents("$out_base.txt");

                // Log un estratto del testo estratto (primi 150 caratteri)
                $truncated_text = (strlen($extracted_text) > 150) ? substr($extracted_text, 0, 150) . '...' : $extracted_text;
                dol_syslog("file_manager.php: Testo OCR estratto dall'immagine: " . str_replace(["\r", "\n"], [" ", " "], $truncated_text), LOG_INFO);
                unlink("$out_base.txt");  // Remove temporary OCR file
                dol_syslog("file_manager.php: File temporaneo eliminato: $out_base.txt", LOG_DEBUG);
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
 * Carica il testo OCR da un file esistente
 * @param string $file_path Percorso completo del file
 * @return string Il testo OCR estratto o vuoto se non disponibile
 */
function load_ocr_text($file_path) {
    $text_file_path = pathinfo($file_path, PATH_INFO_DIRNAME) . '/' .
                      pathinfo($file_path, PATH_INFO_FILENAME) . '.txt';

    if (file_exists($text_file_path)) {
        return file_get_contents($text_file_path);
    }

    return '';
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
             // Optionally, remove the invalid stored data here
             // remove_stored_ai_response($file_key);
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
    dol_syslog("file_manager.php: analyze_ocr_content: OpenAI API key found.", LOG_DEBUG); // Log API key found

    // Limita il testo OCR a 1000 caratteri per l'analisi
    $truncated_text = substr($ocr_text, 0, 1000);
    dol_syslog("file_manager.php: analyze_ocr_content: Truncated OCR text length: " . strlen($truncated_text), LOG_DEBUG); // Log truncated text length

    try {
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

        // Ensure $available_tags is an array before implode
        $available_tag_keys = is_array($available_tags) ? array_keys($available_tags) : [];
        $tag_list_string = !empty($available_tag_keys) ? implode(", ", $available_tag_keys) : 'documento, fattura, contratto, perizia, altro, ecc.';

        // Crea una richiesta per analizzare il testo e suggerire un nome file e un tag/categoria appropriati
        // Updated prompt structure
        $request_data = [
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "Sei un assistente che analizza testo estratto da OCR per documenti relativi a perizie tecniche (ID: $periziaid_sanitized). Devi proporre un nome di file e un tag/categoria appropriati. Rispondi *esclusivamente* con un oggetto JSON contenente 'suggested_name' e 'suggested_tag'."
                ],
                [
                    'role' => 'user',
                    'content' => "Analizza il seguente testo estratto da OCR per la perizia ID $periziaid_sanitized. Rispondi esclusivamente in formato JSON con due proprietà:\n"
                               . "1. 'suggested_tag': Scegli la *singola* categoria (tag) più appropriata *esclusivamente* da questa lista: [$tag_list_string].\n"
                               . "2. 'suggested_name': Crea un nome file conciso (max 30 caratteri, senza estensione, usa underscore per spazi) combinando l'ID perizia ($periziaid_sanitized), il tag scelto e una brevissima descrizione (1-3 parole) derivata dal testo. Formato: {$periziaid_sanitized}_{tag_scelto}_descrizione_breve\n"
                               . "Non includere altro testo, spiegazioni o markdown. Solo l'oggetto JSON.\n\n"
                               . "Testo OCR:\n\"$truncated_text\"\n\n"
                               . "Nome file originale: $original_filename\n"
                               . "Estensione: $file_extension"
                ]
            ],
            'max_tokens' => 150,
            'temperature' => 0.3 // Lower temperature for more deterministic tag/name generation
        ];

        $json_request_data = json_encode($request_data);
        if (json_last_error() !== JSON_ERROR_NONE) {
             dol_syslog("file_manager.php: analyze_ocr_content: Failed to encode JSON request data: " . json_last_error_msg(), LOG_ERR);
             curl_close($ch);
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
            $decoded_response = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                dol_syslog("file_manager.php: analyze_ocr_content: Failed to decode JSON response from OpenAI: " . json_last_error_msg(), LOG_WARNING);
                // Attempt to extract JSON even if decoding failed initially (e.g., if wrapped in text)
                preg_match('/{.*}/s', $response, $json_matches);
                if (!empty($json_matches[0])) {
                    $decoded_response = json_decode($json_matches[0], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                         dol_syslog("file_manager.php: analyze_ocr_content: Failed to decode extracted JSON response: " . json_last_error_msg(), LOG_WARNING);
                         return $result; // Give up if extraction also fails
                    }
                    dol_syslog("file_manager.php: analyze_ocr_content: Successfully decoded extracted JSON response.", LOG_DEBUG);
                } else {
                    dol_syslog("file_manager.php: analyze_ocr_content: No JSON found in the response.", LOG_WARNING);
                    return $result; // Give up if no JSON found
                }
            }


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
                         dol_syslog("file_manager.php: analyze_ocr_content: Successfully decoded suggestion JSON.", LOG_DEBUG);
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
    } catch (Exception $e) {
        dol_syslog("file_manager.php: analyze_ocr_content: Exception during OpenAI API call for '$original_filename': " . $e->getMessage(), LOG_ERR); // Log exception
        $result['suggested_name'] = $original_filename; // Fallback on exception
    }

    dol_syslog("file_manager.php: analyze_ocr_content: Finished analysis for '$original_filename'. Returning: " . json_encode($result), LOG_DEBUG); // Log function end
    return $result;
}

/**
 * Aggiorna i riferimenti ai file (tag, descrizioni, ecc) dopo una rinomina
 *
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
        $tags_data = json_decode(file_get_contents($tags_file), true);
        if (is_array($tags_data)) {
            // Se esiste un tag per il vecchio nome, spostalo al nuovo nome
            if (isset($tags_data[$old_key_base])) {
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
        $desc_data = json_decode(file_get_contents($desc_file), true);
        if (is_array($desc_data)) {
            // Se esiste una descrizione per il vecchio nome, spostala al nuovo nome
            if (isset($desc_data[$old_key_base])) {
                $desc_data[$new_key_base] = $desc_data[$old_key_base];
                unset($desc_data[$old_key_base]);
                file_put_contents($desc_file, json_encode($desc_data));
                dol_syslog("file_manager.php: Updated description reference from '$old_key_base' to '$new_key_base'", LOG_DEBUG);
            }
        }
    }

    // Aggiorna la cache delle risposte AI (descrizioni e analisi OCR)
    $ai_dir = DOL_DATA_ROOT . '/industria40/ai_responses';
    $ai_file = $ai_dir . '/stored_responses.json';
    if (file_exists($ai_file)) {
        $ai_data = json_decode(file_get_contents($ai_file), true);
        if (is_array($ai_data)) {
            $updated_ai_data = false;
            // Check for description key
            if (isset($ai_data[$old_key_base])) {
                $ai_data[$new_key_base] = $ai_data[$old_key_base];
                unset($ai_data[$old_key_base]);
                dol_syslog("file_manager.php: Updated AI description cache for key '$ai_desc_key' to '$new_key_base'", LOG_DEBUG);
                $updated_ai_data = true;
            }
            // Check for OCR analysis key
            $old_ocr_key = $old_key_base . '_ocr_analysis';
            $new_ocr_key = $new_key_base . '_ocr_analysis';
            if (isset($ai_data[$old_ocr_key])) {
                $ai_data[$new_ocr_key] = $ai_data[$old_ocr_key];
                unset($ai_data[$old_ocr_key]);
                dol_syslog("file_manager.php: Updated AI OCR analysis cache key from '$old_ocr_key' to '$new_ocr_key'", LOG_DEBUG);
                $updated_ai_data = true;
            }

            if ($updated_ai_data) {
                file_put_contents($ai_file, json_encode($ai_data));
            }
        }
    }


    // Aggiorna anche eventuali thumbnail per i PDF
    // Verifica se esiste la thumbnail del PDF
    $thumbnail_path = DOL_DATA_ROOT . '/industria40/thumbnails/' . $socid . '/' . $periziaid_sanitized . '/thumb_' . pathinfo($old_filename, PATHINFO_FILENAME) . '.jpg';
    $new_thumbnail_path = DOL_DATA_ROOT . '/industria40/thumbnails/' . $socid . '/' . $periziaid_sanitized . '/thumb_' . pathinfo($new_filename, PATHINFO_FILENAME) . '.jpg';

    if (file_exists($thumbnail_path)) {
        // Se la thumbnail esiste già, prova a rinominarla
        if (rename($thumbnail_path, $new_thumbnail_path)) {
            dol_syslog("file_manager.php: Thumbnail rinominata con successo da '$thumbnail_path' a '$new_thumbnail_path'", LOG_DEBUG);
        } else {
            dol_syslog("file_manager.php: ERRORE rinomina thumbnail da '$thumbnail_path' a '$new_thumbnail_path'", LOG_ERR);
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
         dol_syslog("file_manager.php: get_stored_ai_response: File not found: $ai_responses_file", LOG_DEBUG);
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
    if (empty($key) || empty($response)) {
        return false;
    }

    $ai_responses_dir = DOL_DATA_ROOT . '/industria40/ai_responses';
    if (!is_dir($ai_responses_dir)) {
        if (!dol_mkdir($ai_responses_dir)) {
            dol_syslog("file_manager.php: Failed to create AI responses directory", LOG_ERR);
            return false;
        }
    }

    $ai_responses_file = $ai_responses_dir . '/stored_responses.json';
    $responses_data = []; // Initialize
    if (file_exists($ai_responses_file)) {
        $responses_data_content = file_get_contents($ai_responses_file);
        if ($responses_data_content !== false) {
            $decoded_data = json_decode($responses_data_content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_data)) {
                $responses_data = $decoded_data;
            } else {
                 dol_syslog("file_manager.php: store_ai_response: Failed to decode JSON from $ai_responses_file or not an array. Initializing.", LOG_WARNING);
            }
        }
    } else {
        // If file doesn't exist, it will be created with an empty array initially.
        // No need to explicitly put json_encode([]) here, as it's handled by the assignment below.
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
 *
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
        if ($desc_content !== false) {
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
        if ($ai_content !== false) {
            $ai_data = json_decode($ai_content, true);
            if (is_array($ai_data)) {
                $ai_desc_key = $file_key_base;
                $ai_ocr_key = $file_key_base . '_ocr_analysis';
                $updated_ai_data = false;

                if (isset($ai_data[$ai_desc_key])) {
                    unset($ai_data[$ai_desc_key]);
                    dol_syslog("file_manager.php: Removed AI description cache for key '$ai_desc_key'", LOG_DEBUG);
                    $updated_ai_data = true;
                }
                if (isset($ai_data[$ai_ocr_key])) {
                    unset($ai_data[$ai_ocr_key]);
                    dol_syslog("file_manager.php: Removed AI OCR analysis cache for key '$ai_ocr_key'", LOG_DEBUG);
                    $updated_ai_data = true;
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
