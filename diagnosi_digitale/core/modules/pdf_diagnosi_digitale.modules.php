<?php
ini_set('memory_limit', '512M');
//require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/diagnosi_digitale/includes/common_includes.php'; // Include il file centralizzato

require_once DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/tcpdf.php';

// 1. Carica il template HTML
//$template = file_get_contents('/custom/diagnosi_digitale/templates/model.html');

/**
 * Class ModelePDFDiagnosi_digitale
 * PDF generation model for Diagnosi Digitale module
 */
class ModelePDFSociete
{
    //public $name = 'DiagnosiDigitale';
     //public $description = 'PDF model for Diagnosi Digitale';

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
      $societa_nome,
      $societa_indirizzo,
      $societa_cap,
      $societa_citta,
      $data_pdf =[]
      )
     {
         global $langs, $conf;

         $langs->load("diagnosi_digitale@diagnosi_digitale");

// Prepara i dati per il PDF
$data_pdf = [
  'societa_nome' => $societa_nome ?? 'N/A',
  'indirizzo_completo' => $indirizzo_completo ?? 'N/A', // not from here in docx
  'societa_indirizzo' => $societa_indirizzo ?? 'N/A',
  'societa_cap' => $societa_cap ?? 'N/A',
  'societa_citta' => $societa_citta ?? 'N/A',
];




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
         $filename = dol_sanitizeFileName($this->ref ?: 'diagnosi_digitale_' . dol_print_date(dol_now(), '%Y%m%d%H%M%S')). ".pdf";
         $file = $filedir . '/' . $filename;


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
         $pdf->SetXY(10, 20);
         // fill in the content with the data from the object
         $pdf->MultiCell(0, 5, $object->getContentForPDF($outputlangs));
         // You can customize the content here based on the object data
         // For example, you can use $object->field_name to get specific fields
         // from the object and write them to the PDF.
         // You can also use HTML content if needed
         // $pdf->writeHTML($htmlContent, true, false, true, false, '');
         // You can also add images, tables, etc. using TCPDF methods

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
             'label' => $langs->trans("DiagnosiDigitale"), // Aggiungi la chiave 'label'
             'description' => $langs->trans("PDFModelDescription"),
             'file' => __FILE__
         );

         return $list;
     }
 }

 class MyPDF extends TCPDF {
  public function Header() {
      $pageWidth = $this->getPageWidth();
      $imageWidth = 164.90;
      $imageHeight = 17.41;
      $xCentered = ($pageWidth - $imageWidth) / 2;
      $y = 10;

      $this->Image(DOL_DOCUMENT_ROOT . '/custom/diagnosi_digitale/templates/images/header.png',
          $xCentered, $y, $imageWidth, $imageHeight, 'PNG');
      $this->SetY($y + $imageHeight + 5);
  }
}



class ModelePDFDiagnosi_digitale extends ModelePDFSociete
{
   //public $name = 'DiagnosiDigitale';
    //public $description = 'PDF model for Diagnosi Digitale';

    /**
     * Write the PDF file
     *
     * @param object $object      Object to generate the PDF for
     * @param object $outputlangs Language object
     * @param string $file        Path to save the PDF
     * @param string $societa_nome (Aggiunto per compatibilità)
     * @param string $societa_indirizzo (Aggiunto per compatibilità)
     * @param string $societa_cap (Aggiunto per compatibilità)
     * @param string $societa_citta (Aggiunto per compatibilità)
     * @param array  $data_pdf    (Aggiunto per compatibilità) Array con dati aggiuntivi
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
        global $langs, $conf, $db; // Aggiunto $db se necessario

        // Verifica che l'oggetto non sia null
        if (empty($object) || empty($object->id)) {
            dol_syslog(__METHOD__ . " Error: Object is null or ID is missing", LOG_ERR);
            return -1;
        }

        // Se i dati della società non sono passati direttamente, prova a recuperarli
        if ($societa_nome === null && !empty($object->fk_societe)) {
            $societe = new Societe($db);
            if ($societe->fetch($object->fk_societe) > 0) {
                $societa_nome = $societe->nom;
                $societa_indirizzo = $societe->address;
                $societa_cap = $societe->zip;
                $societa_citta = $societe->town;
                // Popola $data_pdf se necessario
                $data_pdf['societa_nome'] = $societa_nome;
                $data_pdf['societa_indirizzo'] = $societa_indirizzo;
                $data_pdf['societa_cap'] = $societa_cap;
                $data_pdf['societa_citta'] = $societa_citta;
            }
        }

        // Popola $data_pdf con i parametri ricevuti se non già presenti
        $data_pdf['societa_nome'] = $data_pdf['societa_nome'] ?? $societa_nome ?? $object->azienda_ragione_sociale ?? 'N/A';
        $data_pdf['societa_indirizzo'] = $data_pdf['societa_indirizzo'] ?? $societa_indirizzo ?? 'N/A';
        $data_pdf['societa_cap'] = $data_pdf['societa_cap'] ?? $societa_cap ?? 'N/A';
        $data_pdf['societa_citta'] = $data_pdf['societa_citta'] ?? $societa_citta ?? 'N/A';
        $data_pdf['indirizzo_completo'] = $data_pdf['indirizzo_completo'] ?? ($data_pdf['societa_indirizzo'] && $data_pdf['societa_cap'] && $data_pdf['societa_citta'] ? $data_pdf['societa_indirizzo'] . ', ' . $data_pdf['societa_cap'] . ', ' . $data_pdf['societa_citta'] : 'plutonio');


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
        /*$pdf = pdf_getInstance();
        if (!$pdf) {
            dol_syslog(__METHOD__ . " Error: Failed to initialize PDF instance", LOG_ERR);
            return -1;
        }*/

        $pdf = new MyPDF();
        $pageWidth = $pdf->getPageWidth();
        $imageWidth = 164.90;
        $imageHeight = 17.41;
        $xCentered = ($pageWidth - $imageWidth) / 2;
        $y = 10;

        // --- Inizio contenuto specifico di ModelePDFDiagnosi_digitale ---

        $pdf->SetMargins(20, 30, 20); // top 40 per non coprire l'immagine header
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->setPrintHeader(true); // Disabilita header di default
        $pdf->setPrintFooter(false); // Disabilita footer di default
        $pdf->AddPage();

        $mainTextFontSize = 9; // Font size for main text


        // Add title
        $pdf->SetFont('helvetica', 'B', 14); // Set font to bold
        $pdf->SetY(30); // Posizione sotto i loghi
        if ($object->dimensione_pmi == 'MICRO') {
          $pdf->Cell(0, 10, 'Allegato B', 0, 1, 'C');
        } else {
          $pdf->Cell(0, 10, 'Allegato C', 0, 1, 'C');        }
        //$pdf->Cell(0, 10, 'Allegato C', 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->Cell(0, 10, 'DIAGNOSI DIGITALE', 0, 1, 'C');

        // Add recipient information
        $pdf->SetFont('dejavusans', '', $mainTextFontSize);
        $pdf->Ln(5);
        $pdf->SetX($pdf->getPageWidth() - 80); // Posiziona a destra
        $pdf->MultiCell(60, 6, "Spettabile\nRegione Calabria\nDipartimento Transizione Digitale ed Attività Strategiche", 0, 'L');
        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', 'B', $mainTextFontSize);
        if ($object->dimensione_pmi == 'MICRO') {
          $pdf->MultiCell(0, 6, "Oggetto: Relazione Tecnica della Micro Impresa ……………. identificato con il n. (numero di protocollo). ", 0, 'L');
          $pdf->SetFont('dejavusans', '', $mainTextFontSize);
                  // Add user information (Esempio - da rendere dinamico)
          $pdf->Ln(5);
          $htmlUser = '
          <table cellpadding="2" cellspacing="2" border="0" style="font-size: 10 pt;">
            <tr><td>Il sottoscritto <b> </b></td></tr>

            <tr><td>nato in Italia a <b>.............................</b> il <b>......../....../........</b></td></tr>

            <tr><td>residente in .........................., <b>.........., ....., ......................., ............................./b></td></tr>

            <tr><td>codice fiscale: <b>.............................................................</b></td></tr>

            <tr><td>Partita IVA: <b>........................................................</b></td></tr>
          </table>';
        $pdf->writeHTML($htmlUser, true, false, false, false, '');
        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', 'B', $mainTextFontSize);
        $pdf->Cell(0, 6, "DICHIARA", 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', $mainTextFontSize);
        $pdf->MultiCell(0, 6, "ai sensi degli artt. 46 e 47 del D.P.R. 445 del 28/12/2000, consapevole delle sanzioni penali, nel caso di dichiarazioni non veritiere e falsità negli atti, richiamate dall’art. 76, consapevole altresì che, nel caso di dichiarazioni non veritiere e falsità negli atti, il dichiarante sopra indicato decadrà dai benefici per i quali la stessa dichiarazione è rilasciata ", 0, 'C');
        $pdf->Ln(5);
        } else {

        $pdf->MultiCell(0, 6, "Oggetto: Diagnosi Digitale a corredo della Domanda (solo ex ante) o della richiesta di erogazione (completa) relativa al", 0, 'L');
        $pdf->SetFont('dejavusans', '', $mainTextFontSize);
        $pdf->MultiCell(0, 6, "Progetto presentato dalla PMI e approvato a valere sull’Avviso in oggetto e identificato con il n. (numero di protocollo), CUP (codice CUP) e COR (codice COR).", 0, 'L');

        // Add user information (Esempio - da rendere dinamico)
        $pdf->Ln(5);
        $htmlUser = '
        <table cellpadding="2" cellspacing="0" border="0" style="font-size: '.$mainTextFontSize.'pt;">
          <tr><td>Il sottoscritto <b>Giuliano Beccaria</b></td></tr>
          <tr><td>nato in Italia a <b>Roma</b> il <b>28/11/1971</b></td></tr>
          <tr><td>residente in Italia, <b>89127, RC, Reggio Calabria, via Gaspare del Fosso 56</b></td></tr>
          <tr><td>codice fiscale: <b>BCCGLN71S28H501X</b></td></tr>
          <tr><td> </td></tr>
          <tr><td>in qualità di Innovation Manager incaricato di redigere la Diagnosi Digitale sia ex ante che ex post</td></tr>
        </table>';
        $pdf->writeHTML($htmlUser, true, false, false, false, '');


        // Add declaration
        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', 'B', $mainTextFontSize);
        $pdf->Cell(0, 6, "DICHIARA", 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', $mainTextFontSize);
        $pdf->MultiCell(0, 6, "ai sensi degli artt. 46 e 47 del D.P.R. 445 del 28/12/2000, consapevole delle sanzioni penali, nel caso di dichiarazioni non veritiere e falsità negli atti, richiamate dall’art. 76, consapevole altresì che, nel caso di dichiarazioni non veritiere e falsità negli atti, il dichiarante sopra indicato decadrà dai benefici per i quali la stessa dichiarazione è rilasciata.", 0, 'L');

        // Add checkboxes (Esempio - da rendere dinamico)
        $pdf->Ln(5);
        $pdf->MultiCell(0, 6, "a. di essere in possesso di uno dei requisiti specifici previsti per l’incarico ed in particolare di essere:", 0, 'L');
        $pdf->Ln(2);
        $pdf->CheckBox('innovation_manager', 5, true, array(), array(), 'X'); $pdf->Cell(0, 5, "  un Innovation Manager", 0, 1);
        $pdf->Ln(1);
        $pdf->CheckBox('specialista_innovazione', 5, false, array(), array(), 'X'); $pdf->Cell(0, 5, "  uno Specialista dell’Innovazione", 0, 1);
        $pdf->Ln(1);
        $pdf->CheckBox('tecnico_innovazione', 5, false, array(), array(), 'X'); $pdf->Cell(0, 5, "  un Tecnico dell’Innovazione", 0, 1);
        $pdf->Ln(1);
        $pdf->CheckBox('professionista_indipendente', 5, false, array(), array(), 'X'); $pdf->MultiCell(0, 5, "  professionista indipendente terzo rispetto all’impresa beneficiaria che eroga la propria attività nell’ambito del DIH - Digital Innovation Hub", 0, 'L');
        $pdf->Ln(1);
        $pdf->MultiCell(0, 6, "la cui figura professionale è certificata ai sensi della norma UNI 11814 dall’Organismo di certificazione del personale (Nome Organismo) accreditato da ACCREDIA in accordo alla norma ISO/IEC 17024 per la specifica norma UNI 11814", 0, 'L');
        $pdf->Ln(2);
        $pdf->CheckBox('riscontro_accredia', 5, true, array(), array(), 'X'); $pdf->MultiCell(0, 6, " come riscontrabile direttamente dal sito ACCREDIA (pagina Banche Dati ~ Accredia - Figure professionali certificate)", 0, 'L');
        $pdf->Ln(1);
        $pdf->CheckBox('riscontro_documentazione', 5, false, array(), array(), 'X'); $pdf->MultiCell(0, 6, " come riscontrabile da documentazione allegata e conforme all’originale", 0, 'L');
        $pdf->Ln(2);
        $pdf->MultiCell(0, 6, "b. che sussistono i requisiti di indipendenza ed obiettività di cui all’art. 10 del D. Lgs. n.39 del 2010 e ss.mm.ii, in particolare di:", 0, 'L');
        $pdf->MultiCell(0, 6, "- non essere coinvolto nella realizzazione delle attività oggetto della presente diagnosi;", 0, 'L');


        // --- Seconda Pagina ---
        $pdf->AddPage();
        // Aggiungi loghi se necessario anche qui
        $pdf->SetY(40); // Posizione sotto i loghi

        $pdf->MultiCell(0, 6, "- non trarre benefici diretti dall’accettazione dell’incarico diversi dal compenso pattuito per il rilascio della presente Diagnosi Digitale;\n- non avere un rapporto stretto con una persona che rappresenta il Beneficiario;\n- non essere un dirigente, un dipendente, un fiduciario o un partner del Beneficiario;\n- di essere indipendente e terzo rispetto al fornitore del servizio/prodotto reso, di cui alla Diagnosi ex post;\n- non trovarsi in un’altra situazione che compromette la sua indipendenza o la sua capacità di predisporre la Diagnosi Digitale in modo indipendente;\n- di non trovarsi in alcuna situazione di incompatibilità, sia di diritto che di fatto, nonché in situazioni di conflitto, anche potenziale, d’interessi che pregiudichino l’esercizio imparziale delle funzioni attribuite.\n\nc. che la Diagnosi Digitale non è oggetto di altro finanziamento pubblico anche indiretto", 0, 'L');

        // Add "E INOLTRE DICHIARA" section
        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', 'B', $mainTextFontSize);
        $pdf->Cell(0, 6, "E INOLTRE DICHIARA", 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', $mainTextFontSize);
        $pdf->MultiCell(0, 6, "- di essere consapevole che l’articolo 264 comma 2, lett. a) del D.L. 19 maggio 2020, n. 34 ha modificato, tra l’altro, gli articoli 75 e 76 del D.P.R. n. 445/2000, prevedendo in particolare che “La dichiarazione mendace comporta, altresì, la revoca degli eventuali benefici già erogati nonché il divieto di accesso a contributi, finanziamenti e agevolazioni per un periodo di 2 anni decorrenti da quando l’amministrazione ha adottato l’atto di decadenza” e che “la sanzione ordinariamente prevista dal codice penale è aumentata da un terzo alla metà”;\n- di impegnarsi a dare tempestiva comunicazione in caso intervengano eventi che rendano mendaci le dichiarazioni rese;\n- di aver preso visione della informativa ai sensi degli artt. 13 e 14 del Reg. (UE) 2016/679 in materia di protezione dei dati personali.", 0, 'L');
        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', 'B', $mainTextFontSize);
        $pdf->Cell(0, 6, "TUTTO CIÒ PREMESSO SI RENDE LA SEGUENTE DIAGNOSI DIGITALE", 0, 1, 'C');
        $pdf->Ln(5);

        }

        // Add subject

        // --- Sezione 1: Anagrafica del progetto ---
        $pdf->SetFont('dejavusans', '', $mainTextFontSize);
        $tableHTML_sec1 = '
        <table border="1" cellpadding="4" cellspacing="0" width="100%">
    <thead>
        <tr style="background-color: #c5c5c5;">
            <th colspan="3" align="center" style="font-size:10px; font-weight:bold; color: #003366;">Sezione 1: Anagrafica del progetto</th>
        </tr>
    </thead>
    <tbody nobr="true">
        <tr>
            <td width="70%" style="font-weight:bold; background-color:#c5c5c5;">Ragione sociale PMI oggetto della Diagnosi</td>
            <td width="30%" style="color: #003366; font-size: 9pt;">
            ' . dol_escape_htmltag($societa_nome ?? 'N/A') . '
            </td>
        </tr>
        <tr>
            <td style="font-weight:bold; background-color:#c5c5c5;">Sede o Sedi Operative interessate</td>
            <td style="color: #003366; font-size: 9pt;">' .

            (!empty($object->sede_operativa_interessata)
            ? dol_escape_htmltag($object->sede_operativa_interessata)
            : dol_escape_htmltag($societa_indirizzo ?? 'N/A') . ', ' .
              dol_escape_htmltag($societa_cap ?? 'N/A') . ', ' .
              dol_escape_htmltag($societa_citta ?? 'N/A')
        ). '
            </td>
        </tr>';
        if ($object->dimensione_pmi == 'MICRO') {
          $tableHTML_sec1 = $tableHTML_sec1;
        }
        else {
          $tableHTML_sec1 = $tableHTML_sec1 . '
        <tr>
            <td style="font-weight:bold; background-color:#c5c5c5;">Dimensione della PMI Beneficiaria</td>
            <td style="color: #003366; font-size: 9pt;">
            ' . dol_escape_htmltag($object->dimensione_pmi ?? 'N/A') . '
            </td>
        </tr>
        ';
        }

        $tableHTML_sec1 = $tableHTML_sec1 . '
        <tr>
            <td style="font-weight:bold; background-color:#c5c5c5; width:70%;">Interventi (rif. articolo 1 e appendice 4 dell’Avviso) previsti dal Progetto</td>
            <td style="background-color:#c5c5c5; width:30%; font-weight:bold;">Contributo</td>
            </tr>
        <tr>
            <td style="background-color:#c5c5c5; width:40%; font-weight:bold;">A. Digital Workplace</td>
            <td style="background-color:#c5c5c5; width:20%; font-style:italic;">Numero</td>
            <td style="width:10%; font-weight:bold;">
            ' . dol_escape_htmltag($object->digital_workplace_numero ?? 'N/A') . '
            </td>
            <td style="background-color:#c5c5c5; width:30%; text-align: right;">
            €' . dol_escape_htmltag($object->digital_workplace ?? 'N/A') . '
            </td>
        </tr>
        <tr>
            <td style="background-color:#c5c5c5; width:70%;">B. Digital Commerce & Engagement</td>
            <td style="width:30%; text-align: right;">
            €' . dol_escape_htmltag($object->digital_comm_engag ?? 'N/A') . '
            </td>
        </tr>
        <tr>
            <td style="background-color:#c5c5c5; width:70%;">C.1 Cloud Computing - Application Server</td>
            <td style="width:30%; text-align: right;">
            €' . dol_escape_htmltag($object->cloud_comp_app_server ?? 'N/A') . '
            </td>
        </tr>
        <tr>
            <td style="background-color:#c5c5c5; width:70%;">C.2 Cloud Computing - Database Server</td>
            <td style="width:30%; text-align: right;">
            €' . dol_escape_htmltag($object->cloud_comp_db_server ?? 'N/A') . '
            </td>
        </tr>
        <tr>
            <td style="background-color:#c5c5c5; width:70%;">C.3 Cloud Computing - Web Server</td>
            <td style="width:30%; text-align: right;">
            €' . dol_escape_htmltag($object->cloud_comp_web_server ?? 'N/A') . '
            </td>
        </tr>
        <tr>
            <td style="background-color:#c5c5c5; width:70%;">C.4 Cloud Computing - Database Back Up</td>
            <td style="width:30%; text-align: right;">
            €' . dol_escape_htmltag($object->cloud_comp_db_bkup_server ?? 'N/A') . '
            </td>
        </tr>';
if ($object->dimensione_pmi == 'PICCOLA' || $object->dimensione_pmi == 'MEDIA') {
  $tableHTML_sec1 = $tableHTML_sec1 . '
        <tr>
            <td style="background-color:#c5c5c5; width:70%;">D. Cyber Security</td>
            <td style="width:30%; text-align: right;">
            €' . dol_escape_htmltag($object->cyber_security ?? 'N/A') . '
            </td>
        </tr>';
}
        $tableHTML_sec1 = $tableHTML_sec1 . '
        <tr>
            <td style="font-weight:bold; background-color:#c5c5c5; width:70%;">Totale</td>
            <td style="width:30%; text-align: right; font-weight:bold;">
            €' . dol_escape_htmltag($object->totale ?? 'N/A') . '
            </td>
        </tr>
    </tbody>
</table>
        ';
        $pdf->writeHTML($tableHTML_sec1, true, false, true, false, '');
        $pdf->Ln(5);
        // terza pagina 3
        $pdf->addPage();
        $pdf->SetY(40);
        // --- Sezione 2: Approccio metodologico ---
        $tableHTML_sec2 = '
        <table border="1" cellpadding="5" cellspacing="0" width="100%">
            <thead>
                <tr style="background-color: #c5c5c5;">
                    <th align="center" style="font-size:10px; font-weight:bold; color: #003366;">Sezione 2: Approccio metodologico</th>
                </tr>
            </thead>
            <tbody nobr="true">
                <tr>
                    <td>'. nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->approccio_metodologico ?? 'N/A'))) . '</td>
                </tr>
            </tbody>
        </table>';
        $pdf->writeHTML($tableHTML_sec2, true, false, true, false, '');
        $pdf->Ln(5);
        // --- Sezione 3: Analisi del contesto aziendale ---
        $tableHTML_sec3 = '
        <table border="1" cellpadding="5" cellspacing="0" width="100%">
    <thead nobr="true">
        <tr style="background-color: #c5c5c5;">
            <th colspan="3" align="center" style= "color: #003366;"><strong>Sezione 3: Analisi del contesto aziendale</strong></th>
        </tr>
        <tr style="background-color: #c5c5c5;">
            <th width="30%"></th>
            <th width="35%" align="center"><b>ANALISI EX-ANTE</b></th>
            <th width="35%" align="center"><b>ANALISI EX-POST</b></th>
        </tr>
    </thead>
    <tbody nobr="true">
        <tr>
            <td width="30%" style="background-color: #c5c5c5;">Settore industriale di riferimento</td>
            <td width="70%" >
            '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->settore_industriale ?? 'N/A'))) . '
            </td>
        </tr>
        <tr>
            <td style="background-color: #c5c5c5;">Dimensioni attuali e ambizioni di crescita dell’azienda</td>
            <td width="70%" >
            '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->dimensioni_ambizioni ?? 'N/A'))) . '
            </td>
        </tr>
        <tr>
            <td style="background-color: #c5c5c5;">Caratteristiche dei prodotti / servizi forniti (es., turistici, artigianali, tecnici)</td>
            <td width="70%" >
            ' . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->caratteristiche_prodotti ?? 'N/A'))) . '
            </td>
        </tr>
        <tr>
            <td width="30%" style="background-color: #c5c5c5;">Maturità digitale dell’impresa</td>
            <td width="35%" >
            '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->maturita_digitale ?? 'N/A'))) . '
            </td>
            <td width="35%" >

            </td>
        </tr>
        <tr>
            <td width="30%" style="background-color: #c5c5c5;">Obiettivi dell’azienda (es., crescita presenza sul mercato, efficienza operativa, espansione geografica)</td>
            <td width="35%" >
            '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->obiettivi_azienda ?? 'N/A'))). '
            </td>
            <td width="35%" >

            </td>
        </tr>
        <tr>
            <td width="30%" style="background-color: #c5c5c5;">Capacità di investimento dell’azienda</td>
            <td width="35%" >
            '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->capacita_investimento ?? 'N/A'))). '
            </td>
            <td width="35%" >

            </td>
        </tr>
        <tr>
            <td width="30%" style="background-color: #c5c5c5;">Capacità di gestione della trasformazione digitale</td>
            <td width="35%" >
            '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->capacita_gestionale ?? 'N/A'))). '
            </td>
            <td width="35%" >

            </td>
        </tr>
    </tbody>
</table>
<br><br>';
        $pdf->writeHTML($tableHTML_sec3, true, false, true, false, '');
        $pdf->Ln(5);


        // --- Quarta Pagina ---
        if ($object->dimensione_pmi == 'PICCOLA' || $object->dimensione_pmi == 'MEDIA') {
            $pdf->AddPage();
        }
        // Aggiungi loghi se necessario
        $pdf->SetY(40); // Posizione sotto i loghi
if ($object->dimensione_pmi == 'PICCOLA' || $object->dimensione_pmi == 'MEDIA') {
        // --- Sezione 4: Analisi per Tipologia di Intervento (Digital Workplace) ---
        $tableHTML_sec4_dw = '
        <table border="1" cellpadding="4" cellspacing="0" style="width: 100%; border-collapse: collapse;">

          <thead>
            <tr  style="background-color: #c5c5c5;">
              <th colspan="3" style="text-align: center; color: #003366;">
                <srtong>Sezione 4: Analisi per Tipologia di Intervento</srtong>
              </th>
            </tr>
          </thead>

            <!-- Row 2: Three columns -->
            <tr style="background-color: #c5c5c5;">
              <td></td>
              <td style="text-align: center;">ANALISI EX-ANTE</td>
              <td style="text-align: center;">ANALISI EX-POST</td>
            </tr>
          <tr>
            <td colspan="3" style="text-align: center; color: #003366; background-color: #c5c5c5;">
              A Digital Workplace (postazioni di lavoro digitale)
            </td>
          </tr>
          <tr>
            <td colspan="3" style="text-align: center; background-color: #c5c5c5;">
              MAPPATURA DELLE DOTAZIONI<br>
              (descrizione e composizione del portafoglio applicativo)
            </td>
          </tr>
            <tr>
              <td style="background-color: #c5c5c5;">Software di produttività personale (es. suite Office, GSuite, ecc.) inclusivi di e-mail aziendali integrate nel flusso di lavoro</td>
              <td style="text-align: left;">
              '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->software_produttivita ?? 'N/A'))). '
              </td>
              <td style="text-align: left;">

              </td>
            </tr>
            <tr>
              <td style="background-color: #c5c5c5;">Altri software di utilizzo personale -
          quali antivirus personali o software di
          utilità di sistema
          </td>
              <td style="text-align: left;">
              '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->software_altri ?? 'N/A'))). '
              </td>
              <td style="text-align: left;">

              </td>
            </tr>
            <tr>
              <td style="background-color: #c5c5c5;">Software di comunicazione,
          collaborazione e video conferenze
          </td>
              <td style="text-align: left;">
              '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->software_comunicazione ?? 'N/A'))). '
              </td>
              <td style="text-align: left;">

              </td>
            </tr>
            <tr>
              <td style="background-color: #c5c5c5;">
          Software per l’archiviazione e la
          gestione documentale in cloud
              </td style="background-color: #c5c5c5;">
              <td style="text-align: left;">
              '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->software_archiviazione ?? 'N/A'))). '
              </td>
              <td style="text-align: left;">

              </td>
            </tr>
            <tr>
              <td style="background-color: #c5c5c5;">
          Software per l’automazione dei flussi
          di lavoro documentali (es. software
          per la creazione dei processi di
          approvazione/revisione/pubblicazione
          documentale automatizzato, anche di
          carattere specifico per il settore
          merceologico)
              </td>
             <td style="text-align: left;">
            '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->software_automazione ?? 'N/A'))). '
             </td>
              <td style="text-align: left;"></td>
            </tr>
            <tr>
              <td style="background-color: #c5c5c5;">
          Piattaforme per la condivisione e la
          distribuzione dei contenuti interni
              </td>
             <td style="text-align: left;">
             '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->piattaforme_condivisione ?? 'N/A'))). '
             </td>
              <td style="text-align: left;">

              </td>
            </tr>
            <tr>
              <td style="background-color: #c5c5c5;">
          Software di firma digitale e di
          archiviazione a norma di legge
              </td>
             <td style="text-align: left;">
             '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->software_firma ?? 'N/A'))). '
             </td>
              <td style="text-align: left;">

              </td>
            </tr>
        </table>

        ';
        $pdf->writeHTML($tableHTML_sec4_dw, true, false, true, false, '');
        $pdf->Ln(5);


        // --- Quinta Pagina ---
        $pdf->AddPage();
        // Aggiungi loghi se necessario
        $pdf->SetY(40); // Posizione sotto i loghi

        // --- Sezione 4: Analisi per Tipologia di Intervento (Digital Commerce & Engagement) ---
        $tableHTML_sec4_dce = '
        <table border="1" cellpadding="4" cellspacing="0" style="width: 100%; border-collapse: collapse;">

  <tr style="background-color:#c5c5c5;">
        <td colspan="3" style="text-align: center; color: #003366;">
            <b>B Digital Commerce & Engagement </b>
        </td>
    </tr>
  <tr>
    <td colspan="3" style="text-align: center; background-color: #c5c5c5;">
      MAPPATURA DELLE DOTAZIONI<br>
      (descrizione e composizione del portafoglio applicativo)
    </td>
  </tr>
  <tr>
    <td style="background-color: #c5c5c5;">
Piattaforme integrate di digital commerce comprensive di eventuali
Applicazioni addizionali. Tali
piattaforme possono includere anche
funzionalità di integrazione con i punti
vendita (es. Click-and-collect) e altre
simili modalità di integrazione tra
acquisti in presenza e acquisti online.
    </td>
   <td style="text-align: left;">
  '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->piattaforma_dig_commerce ?? 'N/A'))). '
   </td>
    <td style="text-align: left;">

    </td>
  </tr>
  <tr>
    <td style="background-color: #c5c5c5;">
Piattaforme per gestione di campagne
pubblicitarie/promozionali sui canali
digitali (Digital Marketing) che possano comprendere promozione e
ottimizzazione su motori di ricerca
generali o settoriali, campagne e-mail,
Social, Ads personalizzate;
    </td style="background-color: #c5c5c5;">
   <td style="text-align: left;">
   '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->piattaforma_campagne ?? 'N/A'))). '
   </td>
    <td style="text-align: left;">

    </td>
  </tr>
  <tr>
    <td style="background-color: #c5c5c5;">
Piattaforme di Digital Experience
(gestione contenuti web e
personalizzazione) ai fini della
condivisione di informazioni, servizi e
supporto ai clienti / partner;
    </td>
   <td style="text-align: left;">
   '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->piattaforma_dig_exper ?? 'N/A'))). '
   </td>
    <td style="text-align: left;">

    </td>
  </tr>
  <tr>
    <td style="background-color: #c5c5c5;">
Piattaforme di Analytics a supporto
dell’analisi del digital commerce ed
engagement;
    </td>
   <td style="text-align: left;">
   '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->piattaforma_analytics ?? 'N/A'))). '
   </td>
    <td style="text-align: left;">

    </td>
  </tr>
    <tr>
    <td style="background-color: #c5c5c5;">
Piattaforme di supporto e gestione
clienti personalizzate via Web, Mobile
App, Social;
    </td>
   <td style="text-align: left;">
   '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->piattaforma_mobile ?? 'N/A'))). '
   </td>
    <td style="text-align: left;">

    </td>
  </tr>
  <tr>
    <td style="background-color: #c5c5c5;">
Integrazione con piattaforme di terze
parti (portali eCommerce o verticali di
segmento);
    </td>
   <td style="text-align: left;">
   '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->piattaforma_integrazione ?? 'N/A'))). '
   </td>
    <td style="text-align: left;">

    </td>
  </tr>
  <tr>
    <td style="background-color: #c5c5c5;">
Integrazione con provider di logistica e
distribuzione per il miglioramento del
tracciamento ed efficacia della
distribuzione.
    </td>
   <td style="text-align: left;">
   '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->piattaforma_logistica ?? 'N/A'))). '
   </td>
    <td style="text-align: left;">
    </td>
  </tr>
</table>
<br><br>

        ';
        $pdf->writeHTML($tableHTML_sec4_dce, true, false, true, false, '');
        $pdf->Ln(5);


        // --- Sesta Pagina ---
        $pdf->AddPage();
        // Aggiungi loghi se necessario
        $pdf->SetY(40); // Posizione sotto i loghi

        // --- Sezione 4: Analisi per Tipologia di Intervento (Cloud Computing) ---
        $tableHTML_sec4_cc = '
<table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse;">
  <thead>
    <tr style="background-color: #c5c5c5;">
      <th colspan="3" style="text-align: center; color: #003366;">
        C Cloud Computing
      </th>
    </tr>
    <tr>
      <th colspan="3" style="text-align: center; background-color: #c5c5c5;">
        MAPPATURA DELLE DOTAZIONI<br>
        (descrizione e composizione del portafoglio applicativo)
      </th>
    </tr>
  </thead>
  <tbody nobr="true">
     <tr style="background-color: #c5c5c5;">
      <td></td>
      <td style="text-align: center;">ANALISI EX-ANTE</td>
      <td style="text-align: center;">ANALISI EX-POST</td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
      Presenza di applicazioni erogate già in cloud – ovvero basate su IaaS, PaaS, o SaaS</td>
      <td style="text-align: left;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->presenza_cloud ?? 'N/A'))). '
      </td>
      <td style="text-align: left;"></td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
      Presenza applicazioni “Client”, ovvero esclusivamente eseguite su dispositivi individuali e di produttività personale</td>
      <td style="text-align: left;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->presenza_app_client ?? 'N/A'))). '
      </td>
      <td style="text-align: left;"></td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
      Servizi di Calcolo</td>
      <td style="text-align: left;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->servizi_calcolo ?? 'N/A'))). '
      </td>
      <td style="text-align: left;"></td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
      Servizi di Archiviazione e Database</td>
      <td style="text-align: left;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->servizi_db_e_archiv ?? 'N/A'))). '
      </td>
      <td style="text-align: left;"></td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
      Servizi di Rete</td>
      <td style="text-align: left;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->servizi_network ?? 'N/A'))). '
      </td>
      <td style="text-align: left;"></td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
      Servizi di gestione identità e sicurezza</td>
      <td style="text-align: left;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->servizi_identita_sec ?? 'N/A'))). '
      </td>
      <td style="text-align: left;"></td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
      Servizi di strumenti di sviluppo e test</td>
      <td style="text-align: left;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->servizi_devel_test ?? 'N/A'))). '
      </td>
      <td style="text-align: left;"></td>
    </tr>
  </tbody>
</table>

        ';
        $pdf->writeHTML($tableHTML_sec4_cc, true, false, true, false, '');
        $pdf->Ln(5);


        // --- Settima Pagina ---
        $pdf->AddPage();
        // Aggiungi loghi se necessario
        $pdf->SetY(40); // Posizione sotto i loghi

        // --- Sezione 4: Analisi per Tipologia di Intervento (Cyber Security) ---
        $tableHTML_sec4_cs = '
        <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse;">
  <thead>
    <tr style="background-color: #c5c5c5;">
      <th colspan="3" style="text-align: center; color: #003366;">
        D. Cyber Security
      </th>
    </tr>
    <tr style="background-color: #e8e8e8;">
      <th></th>
      <th style="text-align: center;">ANALISI EX-ANTE</th>
      <th style="text-align: center;">ANALISI EX-POST</th>
    </tr>
  </thead>
  <tbody nobr="true">
    <tr>
      <td colspan="3" style="text-align: center;" style="background-color: #c5c5c5;">
        <strong>MAPPATURA DELLE DOTAZIONI</strong><br>
        (descrizione e composizione del portafoglio applicativo)
      </td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
      Sistemi e servizi per la gestione delle identità e degli accessi</td>
      <td style="text-align: left;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->sistemi_accessi ?? 'N/A'))). '
      </td>
      <td style="text-align: left;"></td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
      Sistemi e servizi per sicurezza della rete aziendale</td>
      <td style="text-align: left;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->sistemi_network_secur ?? 'N/A'))). '
      </td>
      <td style="text-align: left;"></td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
      Sistemi e servizi per la sicurezza degli endpoint</td>
      <td style="text-align: left;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->sistemi_endpoint_secur ?? 'N/A'))). '
      </td>
      <td style="text-align: left;"></td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
      Sistemi e servizi per la sicurezza dei dati</td>
      <td style="text-align: left;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->sistemi_data_secur ?? 'N/A'))). '
      </td>
      <td style="text-align: left;"></td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
      Sistemi e servizi per la gestione delle vulnerabilità</td>
      <td style="text-align: left;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->sistemi_vulnerab_admin ?? 'N/A'))). '
      </td>
      <td style="text-align: left;"></td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
      Sistemi e servizi di Security Analytics</td>
      <td style="text-align: left;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->sistemi_secur_analytics ?? 'N/A'))). '
      </td>
      <td style="text-align: left;"></td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
      Sistemi e servizi per application security</td>
      <td style="text-align: left;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->sistemi_applic_security ?? 'N/A'))). '
      </td>
      <td style="text-align: left;"></td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
      Sistemi e servizi per la gestione del rischio e della compliance</td>
      <td style="text-align: left;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->sistemi_risk_compl_admin ?? 'N/A'))). '
      </td>
      <td style="text-align: left;"></td>
    </tr>
  </tbody>
</table>
        ';
        $pdf->writeHTML($tableHTML_sec4_cs, true, false, true, false, '');
        $pdf->Ln(5);
}

        // --- Ottava Pagina ---
        $pdf->AddPage();
        // Aggiungi loghi se necessario
        //$pdf->SetY(40); // Posizione sotto i loghi

        // --- Sezione 5: Sintesi della valutazione ex-ante ---
        $tableHTML_sec5 = '
        <table border="1" cellpadding="4" cellspacing="0" style="width: 100%; border-collapse: collapse;">
<thead>
  <tr style="background-color: #c5c5c5; font-size: 12px;">
    <th colspan="4" style="text-align: center; color: #003366;">';
    if ($object->dimensione_pmi == 'PICCOLA' || $object->dimensione_pmi == 'MEDIA') {
        $tableHTML_sec5 .= 'Sezione 5: Sintesi dell’analisi ex-ante (azioni di miglioramento suggerite)';
    } else {
        $tableHTML_sec5 .= 'Sezione 4: Autovalutazione ex-ante (azioni di miglioramento suggerite) ';
    }
    $tableHTML_sec5 .= '

    </th>
  </tr>
  <tr style="background-color: #c5c5c5; font-size: 10px;">
    <th style="width: 5%;"></th>
    <th style="width: 25%;">AMBITI DI DIGITALIZZAZIONE VALUTATI</th>
    <th style="text-align: center; width: 35%;">SINTESI DELLA DIAGNOSI DIGITALE EX-ANTE</th>
    <th style="text-align: center; width: 35%;">DETERMINAZIONE DEL FABBISOGNO DIGITALE</th>
  </tr>
</thead>
  <tbody nobr="true">
<tr>
  <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
  <td style="width: 25%; background-color: #c5c5c5;">A. Digital Workplace</td>
  <td style="width: 35%;">
  '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->sintesi_d_workplace ?? 'N/A'))). '
  </td>
  <td style="width: 35%;">
  '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->fabbisogno_sintesi_d_workplace ?? 'N/A'))). '
  </td>
</tr>
<tr>
  <td style="width: 5%; text-align: vertical-align: middle;"><b>&#10004;</b></td>
  <td style="width: 25%; background-color: #c5c5c5;">B. Digital Commerce & Engagement</td>
  <td style="width: 35%;">
  '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->sintesi_d_comm_engagem ?? 'N/A'))). '
  </td>
  <td style="width: 35%;">
  '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->fabbisogno_sintesi_d_comm_engagem ?? 'N/A'))). '
  </td>
</tr>
<tr>
  <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
  <td style="width: 25%; background-color: #c5c5c5;">C.1. Cloud Computing - Application Server</td>
  <td style="width: 35%;">
  '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->sintesi_cc_app_server ?? 'N/A'))). '
  </td>
  <td style="width: 35%;">
  '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->fabbisogno_sintesi_cc_app_server ?? 'N/A'))). '
  </td>
</tr>
<tr>
  <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
  <td style="width: 25%; background-color: #c5c5c5;">C.2. Cloud Computing - Database Server</td>
  <td style="width: 35%;">
  '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->sintesi_cc_db_server ?? 'N/A'))). '
  </td>
  <td style="width: 35%;">
  '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->fabbisogno_sintesi_cc_db_server ?? 'N/A'))). '
  </td>
</tr>
<tr>
  <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
  <td style="width: 25%; background-color: #c5c5c5;">C.3. Cloud Computing - Web Server</td>
  <td style="width: 35%;">
  '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->sintesi_cc_web_server ?? 'N/A'))). '
  </td>
  <td style="width: 35%;">
  '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->fabbisogno_sintesi_cc_web_server ?? 'N/A'))). '
  </td>
</tr>
<tr>
  <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
  <td style="width: 25%; background-color: #c5c5c5;">C.4. Cloud Computing - Database Backup</td>
  <td style="width: 35%;">
  '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->sintesi_cc_db_backup ?? 'N/A'))). '
  </td>
  <td style="width: 35%;">
  '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->fabbisogno_sintesi_cc_db_backup ?? 'N/A'))). '
  </td>
</tr>';

if ($object->dimensione_pmi == 'PICCOLA' || $object->dimensione_pmi == 'MEDIA') {
    $tableHTML_sec5 .= '
<tr>
  <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
  <td style="width: 25%; background-color: #c5c5c5;">D. Cyber Security</td>
  <td style="width: 35%;">
  '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->sintesi_cyber_security ?? ''))). '
  </td>
  <td style="width: 35%;">
  '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->fabbisogno_sintesi_cyber_security ?? ''))). '
  </td>
</tr>';
}
$tableHTML_sec5 .= '
    <tr  style="background-color: #c5c5c5;">
      <td colspan="4" style="text-align: center;"><strong>IMPATTO PREVISTO DALLE SOLUZIONI SUGGERITE</strong></td>
    </tr>
    <tr>
      <td colspan="4">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->impatto_stimato ?? ''))). '
      </td>
    </tr>
    <tr style="background-color: #c5c5c5;">
      <td colspan="4" style="text-align: center;"><strong>PRINCIPALI RISCHI IDENTIFICATI PER LE INIZIATIVE PREDISPOSTE</strong></td>
    </tr>
    <tr>
      <td colspan="4" style="height: 50px;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->rischi_iniziative ?? ''))). '
      </td>
    </tr>
    <tr style="background-color: #c5c5c5;">
      <td colspan="4" style="text-align: center;"><strong>CRITERI CHIAVE DI SUCCESSO RICHIESTI PER IL COMPLETAMENTO DELLE INIZIATIVE</strong></td>
    </tr>
    <tr>
      <td colspan="4" style="height: 50px;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->criteri_chiave ?? 'N/A'))). '
      </td>
    </tr>
  </tbody>
</table>

        ';
        $pdf->SetFont('dejavusans');
        $pdf->writeHTML($tableHTML_sec5, true, false, true, false, '');
        $pdf->Ln(5);


        // --- Nona Pagina ---
        $pdf->AddPage();
        // Aggiungi loghi se necessario
        $pdf->SetY(40); // Posizione sotto i loghi

        // --- Sezione 6: Piano di intervento ---
        $tableHTML_sec6 = '
        <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
  <thead>
    <tr style="background-color: #c5c5c5;">
      <th colspan="2" style="text-align: center; color: #003366;">';
if ($object->dimensione_pmi == 'PICCOLA' || $object->dimensione_pmi == 'MEDIA') {
  $tableHTML_sec6 .= 'Sezione 6: Sintesi dell’analisi ex-ante (piano complessivo - master plan)';
} else {
  $tableHTML_sec6 .= 'Sezione 5: Sintesi dell’analisi ex-ante (piano complessivo – master plan)';
}
  $tableHTML_sec6 .= '
      </th>
    </tr>
  </thead>
  <tbody nobr="true">
    <tr>
      <td style="width: 50%;" style="background-color: #c5c5c5;">
        IDENTIFICAZIONE DELLE<br>
        PRIORITÀ DELLE AZIONI DI<br>
        SUGGERITE
      </td>
      <td>
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->priorita ?? 'N/A'))). '
      </td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
        NUMERO DI MESI NECESSARI PER IL<br>
        COMPLETAMENTO DELL’INTERVENTO<br>
        DALLA DATA DI AVVIO
      </td>
      <td>
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->tempo_completamento ?? 'N/A'))). '
      </td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
        DATA PREVISTA PER IL COMPLETAMENTO<br>
        DELL’INTERVENTO (12 mesi dalla data di sottoscrizione<br>
        dell’Atto di impegno)
      </td>
      <td>
          30/06/2026
      </td>
    </tr>
    <tr>
      <td style="background-color: #c5c5c5;">
        CRONOPROGRAMMA PREVISTO DELLE AZIONI SUGGERITE
      </td>
      <td>
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->cronoprogramma ?? 'N/A'))). '
      </td>
    </tr>
  </tbody>
</table>

        ';
        $pdf->writeHTML($tableHTML_sec6, true, false, true, false, '');
        $pdf->Ln(5);


        // --- Decima Pagina ---
        $pdf->AddPage();
        // Aggiungi loghi se necessario
        $pdf->SetY(40); // Posizione sotto i loghi

        // --- Sezione 7: Sintesi della valutazione ex-post ---
        $tableHTML_sec7 = '
        <table border="1" cellpadding="2" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 10pt;">
  <thead>
    <tr style="background-color: #c5c5c5;">
      <th colspan="4" style="text-align: center; color: #003366; font-size: 12pt;">';
      if ($object->dimensione_pmi == 'PICCOLA' || $object->dimensione_pmi == 'MEDIA') {
        $tableHTML_sec7 .= 'Sezione 7: Sintesi dell’analisi ex-post (valutazione dei risultati raggiunti rispetto a quelli previsti ex-ante)';
      } else {
        $tableHTML_sec7 .= 'Sezione 6: Sintesi dell’analisi ex-post (valutazione dei risultati raggiunti rispetto a quelli previsti ex-ante)';
      }
      $tableHTML_sec7 .= '
      </th>
    </tr>
    <tr style="background-color: #c5c5c5; font-size: 10pt;">
      <th style="width: 5%;"></th>
      <th style="width: 35%; text-align: center;">Ambiti di digitalizzazione valutati<br>(selezionare gli interventi di interesse del progetto)</th>
      <th style="width: 30%; text-align: center;">Sintesi della diagnosi digitale ex-post<br>per ciascun ambito di intervento</th>
      <th style="width: 30%; text-align: center;">Misura della realizzazione degli interventi</th>
    </tr>
  </thead>
  <tbody nobr="true">
    <!-- Riga tipo -->
    <tr>
      <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
      <td style="width: 35%; background-color: #c5c5c5;">A. Digital Workplace</td>
      <td style="width: 30%;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->ex_p_sintesi_d_workplace ?? ''))). '
      </td>
      <td style="width: 30%;"></td>
    </tr>
    <tr>
      <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
      <td style="width: 35%; background-color: #c5c5c5;">B. Digital Commerce & Engagement</td>
      <td style="width: 30%;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->ex_p_sintesi_d_comm_engagem ?? ''))). '
      </td>
      <td style="width: 30%;"></td>
    </tr>
    <tr>
      <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
      <td style="width: 35%; background-color: #c5c5c5;">C.1. Cloud Computing - Application Server</td>
      <td style="width: 30%;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->ex_p_sintesi_cc_app_server ?? ''))). '
      </td>
      <td style="width: 30%;"></td>
    </tr>
    <tr>
      <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
      <td style="width: 35%; background-color: #c5c5c5;">C.2. Cloud Computing - Database Server</td>
      <td style="width: 30%;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->ex_p_sintesi_cc_db_server ?? ''))). '
      </td>
      <td style="width: 30%;"></td>
    </tr>
    <tr>
      <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
      <td style="width: 35%; background-color: #c5c5c5;">C.3. Cloud Computing - Web Server</td>
      <td style="width: 30%;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->ex_p_sintesi_cc_web_server ?? ''))). '
      </td>
      <td style="width: 30%;"></td>
    </tr>
    <tr>
      <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
      <td style="width: 35%; background-color: #c5c5c5;">C.4. Cloud Computing - Database Back Up</td>
      <td style="width: 30%;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->ex_p_sintesi_cc_db_backup ?? ''))). '
      </td>
      <td style="width: 30%;"></td>
    </tr>';

if ($object->dimensione_pmi == 'PICCOLA' || $object->dimensione_pmi == 'MEDIA') {
    $tableHTML_sec7 .= '

    <tr>
      <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
      <td style="width: 35%; background-color: #c5c5c5;">D. Cyber Security</td>
      <td style="width: 30%;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->ex_p_sintesi_cyber_security ?? ''))). '
      </td>
      <td style="width: 30%;"></td>
    </tr>';
}
$tableHTML_sec7 .= '

    <!-- Blocco Servizi Software -->
    <tr style="background-color: #c5c5c5;">
      <th colspan="4" style="text-align: left;">
        Servizi software in licenza/canone di utilizzo associati all’intervento descritto<br>
        per la durata complessiva di 36 mesi dall’attivazione<br>
        (indicare "Sì" o "No" se dalla valutazione effettuata EX-POST sia rilevabile la presenza di servizi)
      </th>
    </tr>
    <tr>
      <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
      <td style="width: 35%; background-color: #c5c5c5;">A. Digital Workplace</td>
      <td style="width: 30%; text-align: center;">Sì &#9744;</td>
      <td style="width: 30%; text-align: center;">No &#9744;</td>
    </tr>
    <tr>
      <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
      <td style="width: 35%; background-color: #c5c5c5;">B. Digital Commerce & Engagement</td>
      <td style="width: 30%; text-align: center;">Sì &#9744;</td>
      <td style="width: 30%; text-align: center;">No &#9744;</td>
    </tr>
    <tr>
      <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
      <td style="width: 35%; background-color: #c5c5c5;">C. Cloud Computing</td>
      <td style="width: 30%; text-align: center;">Sì &#9744;</td>
      <td style="width: 30%; text-align: center;">No &#9744;</td>
    </tr>
    <tr>
      <td style="width: 5%; text-align: center; vertical-align: middle;"><b>&#10004;</b></td>
      <td style="width: 35%; background-color: #c5c5c5;">D. Cyber Security</td>
      <td style="width: 30%; text-align: center;">Sì &#9744;</td>
      <td style="width: 30%; text-align: center;">No &#9744;</td>
    </tr>

    <!-- Blocco Giudizio Finale -->
    <tr style="background-color: #c5c5c5;">
      <th colspan="4" style="text-align: center;">
        Giudizio finale sul raggiungimento degli obiettivi
      </th>
    </tr>
    <tr>
      <td style="width: 100%;">
      '  . nl2br(dol_escape_htmltag(str_replace('\n', ' ', $object->ex_p_giudizio_finale ?? ''))). '
      </td>
    </tr>
  </tbody>
</table>';
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->writeHTML($tableHTML_sec7, true, false, true, false, '');
        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', '', 9);
if ($object->dimensione_pmi == 'PICCOLA' || $object->dimensione_pmi == 'MEDIA') {
        $tableHTML = '
        <table style="width: 100%; border-collapse: collapse; text-align: center; margin-top: 2em;">
          <tr>
            <td><strong>EX-ANTE</strong></td>
            <td><strong>EX-POST</strong></td>
          </tr>
          <tr>
            <td>In fede</td>
            <td>In fede</td>
          </tr>
          <tr>
            <td>L’Innovation Manager o altro soggetto<br>previsto dall’Avviso</td>
            <td>L’Innovation Manager o altro soggetto<br>previsto dall’Avviso</td>
          </tr>
          <tr>
            <td>DATATO E SOTTOSCRITTO CON FIRMA DIGITALE</td>
            <td>DATATO E SOTTOSCRITTO CON FIRMA DIGITALE</td>
          </tr>
          <tr>
            <td>Per presa visione</td>
            <td>Per presa visione</td>
          </tr>
          <tr>
            <td>Il Legale Rappresentante</td>
            <td>Il Legale Rappresentante</td>
          </tr>
          <tr>
            <td>DATATO E SOTTOSCRITTO CON FIRMA DIGITALE</td>
            <td>DATATO E SOTTOSCRITTO CON FIRMA DIGITALE</td>
          </tr>
        </table>';
} else {
        $tableHTML = '
        <table style="width: 100%; border-collapse: collapse; text-align: center; margin-top: 2em;">
          <tr>
            <td><strong>EX-ANTE</strong></td>
            <td><strong>EX-POST</strong></td>
          </tr>
          <tr>
            <td>In fede</td>
            <td>Anche per piena accettazione dei prodotti, soluzioni e servizi configurati e istallati
            </td>
          </tr>
          <tr>
            <td>Il Legale Rappresentante del Richiedente </td>
            <td>Il Legale Rappresentante del Beneficiario</td>
          </tr>
          <tr>
            <td>DATATO E SOTTOSCRITTO CON FIRMA DIGITALE</td>
            <td>DATATO E SOTTOSCRITTO CON FIRMA DIGITALE</td>
          </tr>
          </table>';
}
        $pdf->writeHTML($tableHTML, true, false, false, false, '');
        $pdf->SetY(40);

        // Step 2: Get current Y after table to know approximate position
        $yStart = $pdf->GetY(); // Position below table
        $colWidth = 80; // Half page (for 2 columns), adjust if needed
        $col1X = 15;    // X coordinate for left column (EX-ANTE)
        $col2X = 110;   // X coordinate for right column (EX-POST)

        // Step 3: Add signature fields

        // Signature 1: EX-ANTE - Innovation Manager
        $pdf->addEmptySignatureAppearance($col1X, $yStart + 5, 80, 15);

        // Signature 2: EX-ANTE - CEO
        $pdf->addEmptySignatureAppearance($col1X, $yStart + 30, 80, 15);

        // Signature 3: EX-POST - Innovation Manager
        $pdf->addEmptySignatureAppearance($col2X, $yStart + 5, 80, 15);

        // Signature 4: EX-POST - CEO
        $pdf->addEmptySignatureAppearance($col2X, $yStart + 30, 80, 15);

        // Metadata
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Giuliano Beccaria');
        $pdf->SetTitle('Diagnosi Digitale');
        $pdf->SetSubject('Diagnosi Digitale');


        if ($object->dimensione_pmi == 'MICRO') {
            $pdf->addPage();
            $pdf->SetY(40); // Posizione sotto i loghi
            $htmlUser = <<<HTML
            <table cellpadding="2" cellspacing="0" border="0" style="font-size: 11pt;">
              <tr><td>Il sottoscritto</td><td> ........................</td></tr>
              <tr><td>nato in Italia a</td><td> ........................ il ..............</td></tr>
              <tr><td>residente in:</td><td> ........................................................</td></tr>
              <tr><td>codice fiscale:</td><td> .......................</td></tr>
              <tr><td>in qualità di Legale Rappresentante del fornitore </td><td>..........................</tr>
              <tr><td>con sede legale/fiscale in: </td><td>...............................</td></tr>
              <tr><td>codice fiscale:</td><td>.................................</td></tr>
            </table>
            <br>
            <h3 style="text-align: center;">CERTIFICA</h3>
            <p style="text-align: left;">
            l’avvenuta fornitura dei servizi, soluzioni e servizi e relativa configurazione e installazione, in linea con le caratteristiche
            descritte per ciascun Intervento e con le caratteristiche tecniche previste dall’Avviso.
            </p>
            HTML;

            $pdf->writeHTML($htmlUser, true, false, false, false, '');
        }





        // --- Fine contenuto specifico ---


        // Output PDF
        dol_syslog(__METHOD__ . " Debug: Writing PDF to file: $file", LOG_DEBUG);
        try {
            $pdf->Output($file, 'F');
        } catch (Exception $e) {
            dol_syslog(__METHOD__ . " Error: Exception during PDF generation: " . $e->getMessage(), LOG_ERR);
            $this->error = "Exception during PDF generation: " . $e->getMessage(); // Store error message
            return -1;
        }

        // Verify if the file was created
        if (file_exists($file)) {
            dol_syslog(__METHOD__ . " Debug: PDF successfully created at $file", LOG_DEBUG);
            return 1;
        } else {
            dol_syslog(__METHOD__ . " Error: PDF file not found after generation: $file", LOG_ERR);
            $this->error = "PDF file not found after generation"; // Store error message
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
            'label' => $langs->trans("DiagnosiDigitale"), // Aggiungi la chiave 'label'
            'description' => $langs->trans("PDFModelDescription"),
            'file' => __FILE__
        );

        return $list;
    }
}

$default_pdf_model = isset($conf->global->DIAGNOSI_DIGITALE_PDF_MODEL) ? $conf->global->DIAGNOSI_DIGITALE_PDF_MODEL : 'default';
$field_mapping = isset($conf->global->DIAGNOSI_DIGITALE_FIELD_MAPPING) ? json_decode($conf->global->DIAGNOSI_DIGITALE_FIELD_MAPPING, true) : [];

// Debugging
dol_syslog(__METHOD__ . " Debug: PDF Model = $default_pdf_model", LOG_DEBUG);
dol_syslog(__METHOD__ . " Debug: Field Mapping = " . var_export($field_mapping, true), LOG_DEBUG);