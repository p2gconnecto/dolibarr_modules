<?php

require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php';

class OCRProcessor
{
    public function generateReadablePDF($inputFile, $outputFile)
    {
        if (!file_exists($inputFile)) {
            throw new Exception("File di input non trovato: $inputFile");
        }

        // Leggi il contenuto OCR dal file di testo
        if (!file_exists($outputFile . '.txt')) {
            throw new Exception("File OCR non trovato: " . $outputFile . '.txt');
        }

        $ocrText = file_get_contents($outputFile . '.txt');

        // Crea un PDF leggibile
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Write(0, $ocrText);

        $pdfOutputFile = $outputFile . '_readable.pdf';
        $pdf->Output($pdfOutputFile, 'F');

        return $pdfOutputFile;
    }
}