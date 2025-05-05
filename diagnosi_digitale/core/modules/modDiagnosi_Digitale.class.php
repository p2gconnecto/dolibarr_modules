<?php
//require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/diagnosi_digitale/includes/common_includes.php'; // Include il file centralizzato

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modDiagnosi_Digitale extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs;
        $this->db = $db;

        $this->numero = 500001; // Unique ID for the module
        $this->rights_class = 'diagnosi_digitale';
        $this->family = 'other';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Modulo per la Diagnosi Digitale (Allegato C)";
        $this->version = '1.0';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->special = 0;
        $this->picto = 'fa-file';

        $this->module_parts = array(
            'triggers' => array(),
            'login' => array(),
            'substitutions' => array(),
            'menus' => array(),
            'theme' => array(),
            'tpl' => array(),
            'barcode' => array(),
            'models' => 1, // Enable PDF model support
            'hooks' => array('projectcard')
        );

        $this->dirs = array('/documents/diagnosi_digitale'); // Directory for documents

        $this->config_page_url = array("setup.php@diagnosi_digitale");

        $this->depends = array('modProjet', 'modSociete');
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->langfiles = array("diagnosi_digitale@diagnosi_digitale");

        $this->const = array();

        $this->tabs = array(
            'entity:+diagnosi_digitale:Diagnosi Digitale:custom/diagnosi_digitale/card.php?id=__ID__',
            'admin:-diagnosi_digitale:PDF Parameters:custom/diagnosi_digitale/admin/admin_pdf_parameters.php'
        );

        $this->dictionaries = array();

        $this->boxes = array();

        $this->rights = array();
        $this->rights_class = 'diagnosi_digitale';
        $this->rights[0][0] = 500001;
        $this->rights[0][1] = 'Leggere le diagnosi digitali';
        $this->rights[0][2] = 'r';
        $this->rights[0][3] = 1;
        $this->rights[0][4] = 'read';
        $this->rights[1][0] = 500002;
        $this->rights[1][1] = 'Creare/modificare le diagnosi digitali';
        $this->rights[1][2] = 'w';
        $this->rights[1][3] = 1;
        $this->rights[1][4] = 'write';
        $this->rights[2][0] = 500003;
        $this->rights[2][1] = 'Eliminare le diagnosi digitali';
        $this->rights[2][2] = 'd';
        $this->rights[2][3] = 1;
        $this->rights[2][4] = 'delete';
    }

    public function generateDocument($model, $outputlangs)
    {
        global $conf, $langs;

        dol_syslog(__METHOD__ . " Debug: Generating document for object Ref = " . $this->ref, LOG_DEBUG);

        // Define file path
        $filedir = DOL_DATA_ROOT . '/societe/' . $this->fk_societe;

        // Assign a default file name if $this->ref is empty
        $filename = dol_sanitizeFileName($this->ref ?: 'diagnosi_digitale_' . dol_print_date(dol_now(), '%Y%m%d%H%M%S')) . ".pdf";
        $file = $filedir . '/' . $filename;

        dol_syslog(__METHOD__ . " Debug: File path for document: $file", LOG_DEBUG);

        // Load PDF model
        $pdf_model = new ModelePDFDiagnosi_digitale();
        $result = $pdf_model->write_file($this, $outputlangs, $file);

        if ($result > 0) {
            dol_syslog(__METHOD__ . " Debug: Document successfully generated at $file", LOG_DEBUG);
            return 1;
        } else {
            $this->error = $langs->trans("ErrorFailedToGenerateDocument");
            dol_syslog(__METHOD__ . " Error: Failed to generate document. Error: " . $this->error, LOG_ERR);
            return -1;
        }
    }

    public function generateODFDocument($outputlangs)
    {
        global $conf, $langs;

        dol_syslog(__METHOD__ . " Debug: Generating ODF document for object Ref = " . $this->ref, LOG_DEBUG);

        // Define file path
        $filedir = DOL_DATA_ROOT . '/societe/' . $this->fk_societe;

        // Assign a default file name if $this->ref is empty
        $filename = dol_sanitizeFileName($this->ref ?: 'diagnosi_digitale_' . dol_print_date(dol_now(), '%Y%m%d%H%M%S')) . ".odt";
        $file = $filedir . '/' . $filename;

        dol_syslog(__METHOD__ . " Debug: File path for ODF document: $file", LOG_DEBUG);

        // Ensure the directory exists
        if (!is_dir($filedir)) {
            if (!mkdir($filedir, 0755, true)) {
                $this->error = $langs->trans("ErrorFailedToCreateDirectory", $filedir);
                dol_syslog(__METHOD__ . " Error: Failed to create directory $filedir", LOG_ERR);
                return -1;
            }
        }

        // Load ODF template
       /* $templatePath = DOL_DOCUMENT_ROOT . '/custom/diagnosi_digitale/templates/model.odt';
        if (!file_exists($templatePath)) {
            $this->error = $langs->trans("ErrorTemplateNotFound", $templatePath);
            dol_syslog(__METHOD__ . " Error: Template not found at $templatePath", LOG_ERR);
            return -1;
        }*/

        // Use a library like TBS (TinyButStrong) to generate the ODF document
        require_once DOL_DOCUMENT_ROOT . '/includes/tbs/tbs_class.php';
        require_once DOL_DOCUMENT_ROOT . '/includes/tbs/plugins/opentbs/tbs_plugin_opentbs.php';

        $TBS = new clsTinyButStrong();
        $TBS->Plugin(TBS_INSTALL, OPENTBS_PLUGIN);

        // Load the ODF template
        //$TBS->LoadTemplate($templatePath, OPENTBS_ALREADY_UTF8);

        // Merge data into the template
        $TBS->MergeField('object', $this);

        // Save the ODF document
        $TBS->Show(OPENTBS_FILE, $file);

        if (file_exists($file)) {
            dol_syslog(__METHOD__ . " Debug: ODF document successfully generated at $file", LOG_DEBUG);
            return 1;
        } else {
            $this->error = $langs->trans("ErrorFailedToGenerateDocument");
            dol_syslog(__METHOD__ . " Error: Failed to generate ODF document. Error: " . $this->error, LOG_ERR);
            return -1;
        }
    }
}

// Ensure the object is properly instantiated
require_once DOL_DOCUMENT_ROOT . '/custom/diagnosi_digitale/core/modules/modDiagnosi_Digitale.class.php';

/*$object = new DiagnosiDigitale($db); // Crea un'istanza della classe DiagnosiDigitale
$object->fetch($id); // Recupera i dati dell'oggetto (usa l'ID corretto)
$result = $object->generateDOCXDocument($outputlangs); // Chiama il metodo sull'oggetto
if ($result > 0) {
    print "DOCX document generated successfully.";
} else {
    print "Error: " . $object->error;
}*/
?>
