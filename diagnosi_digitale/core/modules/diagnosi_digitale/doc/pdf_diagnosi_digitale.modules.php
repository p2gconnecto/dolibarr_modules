<?php

//require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/tcpdf.php';


class ModelePDFDiagnosi_digitale extends ModelePDFSociete
{
    public $name = 'DiagnosiDigitale';
    public $description = 'PDF model for Diagnosi Digitale';

    /**
     * Write the PDF file
     *
     * @param object $object      Object to generate the PDF for
     * @param object $outputlangs Language object
     * @param string $file        Path to save the PDF
     * @return int                1 if OK, -1 if error
     */
    public function write_file(
        $object,
        $outputlangs,
        $file,
        $societa_nome = null,      // Aggiunto parametro con valore predefinito
        $societa_indirizzo = null, // Aggiunto parametro con valore predefinito
        $societa_cap = null,       // Aggiunto parametro con valore predefinito
        $societa_citta = null,     // Aggiunto parametro con valore predefinito
        $data_pdf = []             // Aggiunto parametro con valore predefinito
    )
    {
        global $langs, $conf;

        $langs->load("diagnosi_digitale@diagnosi_digitale");

        // Ensure the directory exists
        $dir = dirname($file);
        dol_syslog(__METHOD__ . " Debug: Directory for PDF: $dir", LOG_DEBUG);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                dol_syslog(__METHOD__ . " Error: Failed to create directory $dir", LOG_ERR);
                return -1;
            }
        }

        // Sanitize the file name
        $file = $dir . '/' . dol_sanitizeFileName(basename($file));

        // Create PDF instance
        $pdf = pdf_getInstance();
        if (!$pdf) {
            dol_syslog(__METHOD__ . " Error: Failed to initialize PDF instance", LOG_ERR);
            return -1;
        }

        $pdf->SetAutoPageBreak(1, 0);
        $pdf->SetFont(pdf_getPDFFont($outputlangs));

        // Add a page
        $pdf->AddPage();

        // Write content
        $pdf->SetXY(10, 10);
        $pdf->MultiCell(0, 5, $langs->trans("DiagnosiDigitalePDFContent"));

        // Output PDF
        dol_syslog(__METHOD__ . " Debug: Writing PDF to file: $file", LOG_DEBUG);
        try {
            $pdf->Output($file, 'F');
        } catch (Exception $e) {
            dol_syslog(__METHOD__ . " Error: Exception during PDF generation: " . $e->getMessage(), LOG_ERR);
            return -1;
        }

        // Verify if the file was created
        if (file_exists($file)) {
            dol_syslog(__METHOD__ . " Debug: PDF successfully created at $file", LOG_DEBUG);
            return 1;
        } else {
            dol_syslog(__METHOD__ . " Error: PDF file not found after generation: $file", LOG_ERR);
            return -1;
        }
    }

    /**
     * List available PDF models
     *
     * @param DoliDB $db Database handler
     * @return array List of available models
     */
    public static function liste_modeles($db)
    {
        global $conf, $langs;

        $langs->load("diagnosi_digitale@diagnosi_digitale");

        $list = array();

        // Add this model to the list
        $list['DiagnosiDigitale'] = array(
            'name' => "DiagnosiDigitale",
            'label' => $langs->trans("DiagnosiDigitale"),
            'description' => $langs->trans("PDFModelDescription"),
            'file' => __FILE__
        );

        return $list;
    }
}