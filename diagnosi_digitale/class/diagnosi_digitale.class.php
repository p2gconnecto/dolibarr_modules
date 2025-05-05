<?php

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class DiagnosiDigitale extends CommonObject
{
    public $element = 'diagnosi_digitale';
    public $table_element = 'diagnosi_digitale';
    public $ismultientitymanaged = 1;
    public $picto = 'fa-file';

    public function __construct($db)
    {
        $this->db = $db;
        $this->fields = include DOL_DOCUMENT_ROOT.'/custom/diagnosi_digitale/diagnosi_digitale_fields.php';

        // Aggiungi il campo oggetto_valutazione
        $this->fields['oggetto_valutazione'] = array(
            'type' => 'text',
            'label' => 'OggettoValutazione',
            'enabled' => 1,
            'visible' => 1,
            'notnull' => 0,
            'position' => 100
        );

        // Aggiungi il campo oggetto_valutazione_ai
        $this->fields['oggetto_valutazione_ai'] = [
            'type' => 'text',
            'label' => 'Oggetto Valutazione AI',
            'enabled' => 1,
            'visible' => 1,
            'notnull' => 0,
            'position' => 101
        ];
    }

    /**
     * Create a new record in database
     *
     * @param  User $user User that creates
     * @return int >0 if OK, <0 if KO
     */
    public function create($user)
    {
        global $langs, $conf; // Ensure $langs and $conf are available

        // Generate 'ref' if empty
        if (empty($this->ref)) {
            // Option 1: Use Dolibarr numbering module (if configured for 'diagnosi_digitale')
            $num_ref = dol_get_next_value($this->db, $conf->global->DIAGNOSI_DIGITALE_ADDON_NUM, $this->element, '', $user->entity);
            if ($num_ref) {
                $this->ref = $num_ref;
                dol_syslog(__METHOD__ . " Generated ref using numbering module: " . $this->ref, LOG_DEBUG);
            } else {
                // Option 2: Fallback or custom logic (e.g., based on project/societe)
                // Example: $this->ref = 'DD-' . $this->fk_projet . '-' . $this->fk_societe;
                // For now, let's report an error if numbering module fails or is not set
                $this->errors[] = $langs->trans("ErrorFailedToGenerateRef");
                dol_syslog(__METHOD__ . " Error: Failed to generate ref. Numbering module not configured or failed.", LOG_ERR);
                return -1;
            }
        }

        // The include file handles DB interaction, transaction, and returns ID or -1
        $result = include DOL_DOCUMENT_ROOT.'/custom/diagnosi_digitale/includes/create.inc.php';

        if ($result <= 0) {
            // Error message should have been set within the include or by DB layer
            dol_syslog(__METHOD__ . " Error during create include. Result: " . $result . " Errors: " . implode(', ', $this->errors), LOG_ERR);
            // Ensure a generic error is set if none exists
            if (empty($this->errors)) {
                $this->errors[] = $langs->trans("ErrorCreateFailed");
            }
            return -1;
        }

        // Success, $result contains the new rowid ($this->id was set in the include)
        dol_syslog(__METHOD__ . " Create successful via include. New rowid: " . $this->id, LOG_DEBUG);
        return $this->id; // Return the new ID
    }


    public function fetch($id)
    {
        // Ensure the include returns correctly
        $result = include DOL_DOCUMENT_ROOT.'/custom/diagnosi_digitale/includes/fetch.inc.php';
        return $result; // Return 1 on success, -1 on failure
    }

    public function update()
    {
        global $db, $user, $langs; // Assicurati che le globali necessarie siano disponibili per l'include

        // Controlla se l'ID è valido prima di procedere
        if (empty($this->id) || !is_numeric($this->id)) {
            $this->errors[] = "Invalid rowid for update";
            dol_syslog(__METHOD__ . " Invalid rowid for update: " . var_export($this->id, true), LOG_ERR);
            return -1;
        }

        // Includi il file che contiene la logica di aggiornamento SQL
        try {
            // **** AGGIUNGI QUESTO LOG ****
            dol_syslog(__METHOD__ . " Before including update.inc.php. DB Connected: " . ($this->db->connected ? 'Yes' : 'No'), LOG_DEBUG);
            // ****************************

            // L'include eseguirà la query e restituirà 1, 0, o -1, oppure lancerà un'eccezione
            $result = include DOL_DOCUMENT_ROOT.'/custom/diagnosi_digitale/includes/update.inc.php';

            if ($result < 0) {
                 dol_syslog(__METHOD__ . " Error during update include. Result: " . $result . " Errors: " . implode(', ', $this->errors), LOG_ERR);
                 if (empty($this->errors)) { // Ensure error message exists
                     $this->errors[] = $langs->trans("ErrorUpdateFailed");
                 }
            } elseif ($result === 0) {
                 dol_syslog(__METHOD__ . " Update include executed but no rows were affected.", LOG_WARNING);
                 // Optionally set a specific message for no changes, or treat as success
                 // setEventMessages($langs->trans("NoChange"), null, 'warnings');
                 return 1; // Treat "no change" as a successful operation completion
            } else {
                 dol_syslog(__METHOD__ . " Update successful via include for rowid: " . $this->id, LOG_DEBUG);
            }
            return $result; // 1 for success/no change, -1 for error

        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            dol_syslog(__METHOD__ . " Exception during update include: " . $e->getMessage(), LOG_ERR);
            return -1; // Errore grave
        }
    }

    public function setDocModel($user, $model)
    {
        $this->model_pdf = $model;

        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . "
                SET model_pdf = '" . $this->db->escape($model) . "'
                WHERE rowid = " . ((int) $this->id);

        dol_syslog(__METHOD__ . " SQL: " . $sql, LOG_DEBUG);

        $res = $this->db->query($sql);
        if (!$res) {
            dol_syslog(__METHOD__ . " SQL Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        dol_syslog(__METHOD__ . " Model PDF updated successfully for rowid: " . $this->id, LOG_DEBUG);
        return 1;
    }

    // generateDocument method remains the same
    public function generateDocument($model, $outputlangs)
    {
        global $conf, $langs;

        dol_syslog(__METHOD__ . " Debug: Generating document for object Ref = " . (isset($this->ref) ? $this->ref : 'N/A'), LOG_DEBUG);

        // Check if fk_societe is set
        if (empty($this->fk_societe)) {
             $this->error = $langs->trans("ErrorFkSocieteMissing");
             dol_syslog(__METHOD__ . " Error: fk_societe is not set for rowid: " . $this->id, LOG_ERR);
             return -1;
        }


        // Define file path - Ensure fk_societe is loaded
        $filedir = $conf->societe->dir_output . '/' . dol_sanitizeFileName($this->fk_societe); // Use conf dir
        $filename = dol_sanitizeFileName((!empty($this->ref) ? $this->ref : $this->nom . '_DD_' . $this->id . '_' . dol_print_date(dol_now(), '%Y%m%d%H%M%S'))) . ".pdf";
        $filepath = $filedir . '/' . $filename;

        // Ensure directory exists
        if (!is_dir($filedir)) {
            dol_mkdir($filedir);
        }


        dol_syslog(__METHOD__ . " Debug: File path for document: $filepath", LOG_DEBUG);

        // Load PDF model class dynamically if possible or use a known one
        $modelclass = 'ModelePDFDiagnosi_digitale'; // Adjust if model name varies
        $modelpath = DOL_DOCUMENT_ROOT.'/custom/diagnosi_digitale/core/modules/pdf_diagnosi_digitale.modules.php'; // Adjust path

        if (file_exists($modelpath)) {
            require_once $modelpath;
            if (class_exists($modelclass)) {
                $pdf_model = new $modelclass($this->db);

                // Call the generation method (assuming write_file exists)
                $result = $pdf_model->write_file($this, $outputlangs, $filepath);

                if ($result > 0) {
                    dol_syslog(__METHOD__ . " Debug: Document successfully generated at $filepath", LOG_DEBUG);
                    // Optional: Link file to element
                    // require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
                    // dol_add_file_process($filedir.'/', $filename, $langs->trans("DiagnosiDigitale"), $this->element, $this->id, 'diagnosi_digitale');
                    return 1;
                } else {
                    $this->error = $pdf_model->error ? $pdf_model->error : $langs->trans("ErrorFailedToGenerateDocument");
                    dol_syslog(__METHOD__ . " Error: Failed to generate document using model $modelclass. Error: " . $this->error, LOG_ERR);
                    return -1;
                }
            } else {
                 $this->error = $langs->trans("ErrorPDFModelClassNotFound", $modelclass);
                 dol_syslog(__METHOD__ . " Error: PDF Model class '$modelclass' not found in $modelpath", LOG_ERR);
                 return -1;
            }
        } else {
             $this->error = $langs->trans("ErrorPDFModelFileNotFound", $modelpath);
             dol_syslog(__METHOD__ . " Error: PDF Model file not found at $modelpath", LOG_ERR);
             return -1;
        }
    }

    public function generateDOCXDocument($outputlangs)
    {
        global $conf, $langs;

        dol_syslog(__METHOD__ . " Debug: Generating DOCX document for object Ref = " . $this->ref, LOG_DEBUG);

        // Define file path
        $filedir = DOL_DATA_ROOT . '/societe/' . $this->fk_societe;

        // Assign a default file name if $this->ref is empty
        $filename = dol_sanitizeFileName($this->nom . '_DD_' . $this->id . '_' . dol_print_date(dol_now(), '%Y%m%d%H%M%S')) . ".docx";
        $file = $filedir . '/' . $filename;

        dol_syslog(__METHOD__ . " Debug: File path for DOCX document: $file", LOG_DEBUG);

        // Ensure the directory exists
        if (!is_dir($filedir)) {
            if (!dol_mkdir($filedir)) {
                $this->error = $langs->trans("ErrorFailedToCreateDirectory", $filedir);
                dol_syslog(__METHOD__ . " Error: Failed to create directory $filedir", LOG_ERR);
                return -1;
            }
        }

        // Define DOCX template path
        $templatePath = DOL_DOCUMENT_ROOT . '/custom/diagnosi_digitale/templates/model.docx';
        if (!file_exists($templatePath)) {
            $this->error = $langs->trans("ErrorTemplateNotFound", $templatePath);
            dol_syslog(__METHOD__ . " Error: DOCX Template not found at $templatePath", LOG_ERR);
            return -1;
        }

        // Include PHPWord using Composer autoload
        $composerAutoload = DOL_DOCUMENT_ROOT . '/custom/diagnosi_digitale/vendor/autoload.php'; // Assicurati che questo percorso sia corretto
        if (!file_exists($composerAutoload)) {
            $this->error = $langs->trans("ErrorLibraryNotFound", "PHPWord");
            dol_syslog(__METHOD__ . " Error: Composer autoload not found at $composerAutoload", LOG_ERR);
            return -1;
        }
        require_once $composerAutoload;
        dol_syslog(__METHOD__ . " Debug: Using Composer autoload for PHPWord.", LOG_DEBUG);

        // --- INIZIO MODIFICA: Logica indirizzo allineata al PDF ---
        // get the company object
        $address_to_use = ''; // Inizializza l'indirizzo a una stringa vuota
        // Se l'oggetto società è vuoto, non possiamo procedere
        $address_to_use = '007 '. $this->company_address . ' 007'; // Inizializza con un valore di default

        // 1. Priorità al campo specifico 'sede_operativa_interessata' (come nel PDF)
        if (!empty($this->sede_operativa_interessata)) {
            $address_to_use = $this->sede_operativa_interessata;
            dol_syslog(__METHOD__ . " Debug: Using address from 'sede_operativa_interessata': " . $address_to_use, LOG_DEBUG);
        }
        // 2. Fallback: Ricostruisci dai dati della società (se il campo sopra è vuoto)

                $temp_indirizzo = '';
                if (!empty($societe->address)) $temp_indirizzo .= $societe->address;
                if (!empty($societe->zip)) $temp_indirizzo .= ($temp_indirizzo ? ', ' : '') . $societe->zip;
                if (!empty($societe->town)) $temp_indirizzo .= ($temp_indirizzo && !empty($societe->zip) ? ' ' : ($temp_indirizzo ? ', ' : '')) . $societe->town;
                // Aggiungi paese se necessario
                // if (!empty($societe->country_code)) $temp_indirizzo .= ($temp_indirizzo ? ' (' . $societe->country_code . ')' : '');

                if (!empty($temp_indirizzo)) {
                    $address_to_use = $temp_indirizzo;
                    dol_syslog(__METHOD__ . " Debug: Reconstructed address from Societe (fallback): " . $address_to_use, LOG_DEBUG);
                } else {
                     dol_syslog(__METHOD__ . " Debug: Fallback failed, Societe fields are empty.", LOG_DEBUG);
                }

        // --- FINE MODIFICA ---


        try {
            // Load the template
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

            // Iterate through object properties and set values in the template
            foreach (get_object_vars($this) as $key => $value) {
                if (is_scalar($value) || is_null($value)) {
                    // --- MODIFICA: Usa l'indirizzo determinato sopra ---
                    if ($key === 'indirizzo_completo') { // Continua a usare il placeholder ${indirizzo_completo} nel DOCX
                        $templateProcessor->setValue($key, htmlspecialchars($address_to_use ?? '', ENT_COMPAT, 'UTF-8'));
                    } else {
                        // Gestisci altri campi come prima
                        $formattedValue = $value;
                        // ... (eventuale formattazione date/numeri) ...
                        $templateProcessor->setValue($key, htmlspecialchars($formattedValue ?? '', ENT_COMPAT, 'UTF-8'));
                    }
                    // --- FINE MODIFICA ---
                }
            }

            // Save the processed document
            $templateProcessor->saveAs($file);

        } catch (\PhpOffice\PhpWord\Exception\Exception $e) {
            $this->error = $langs->trans("ErrorGeneratingDocument") . ': ' . $e->getMessage();
            dol_syslog(__METHOD__ . " Error: PHPWord Exception: " . $e->getMessage(), LOG_ERR);
            return -1;
        } catch (Exception $e) {
            $this->error = $langs->trans("ErrorGeneratingDocument") . ': ' . $e->getMessage();
            dol_syslog(__METHOD__ . " Error: General Exception: " . $e->getMessage(), LOG_ERR);
            return -1;
        }

        // Check if file was created
        if (file_exists($file)) {
            dol_syslog(__METHOD__ . " Debug: DOCX document successfully generated at $file", LOG_DEBUG);
            return 1;
        } else {
            $this->error = $langs->trans("ErrorFailedToGenerateDocument");
            dol_syslog(__METHOD__ . " Error: Failed to generate DOCX document", LOG_ERR);
            return -1;
        }
    }

    public function create_or_update($user)
    {
        if (!empty($this->id)) {
            // Se l'ID esiste, aggiorna il record
            return $this->update($user);
        } else {
            // Altrimenti, crea un nuovo record
            return $this->create($user);
        }
    }

    public function fetchBySociete($fk_societe)
    {
        if (empty($fk_societe) || !is_numeric($fk_societe)) {
            $this->errors[] = "Invalid fk_societe";
            dol_syslog(__METHOD__ . " Invalid fk_societe: " . var_export($fk_societe, true), LOG_ERR);
            return -1;
        }

        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . $this->table_element . " WHERE fk_societe = " . ((int) $fk_societe) . " LIMIT 1";
        dol_syslog(__METHOD__ . " SQL: " . $sql, LOG_DEBUG);

        $res = $this->db->query($sql);
        if ($res) {
            if ($this->db->num_rows($res)) {
                $obj = $this->db->fetch_object($res);
                foreach ($this->fields as $key => $field) {
                    if (property_exists($obj, $key)) {
                        $this->$key = $obj->$key;
                    }
                }
                $this->id = $obj->rowid;
                $this->rowid = $obj->rowid;
                return 1; // Success
            } else {
                dol_syslog(__METHOD__ . " No record found for fk_societe: " . $fk_societe, LOG_WARNING);
                return 0; // No record found
            }
        } else {
            $this->errors[] = $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL Error: " . $this->db->lasterror(), LOG_ERR);
            return -1; // Error
        }
    }

}