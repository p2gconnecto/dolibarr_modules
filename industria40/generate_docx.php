<?php
// filepath: /home/dolibarr/.volumes/dolibarr/custom/industria40/generate_docx.php

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once __DIR__.'/../../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

$langs->load("industria40@industria40");

$title = $langs->trans("GenerateDocx");
llxHeader('', $title);

$templatePath = __DIR__ . '/../templates/analisi_tecnica_template.docx';
$outputPath = DOL_DATA_ROOT . '/industria40/generated/analisi_tecnica_' . time() . '.docx';

$templateProcessor = new TemplateProcessor($templatePath);

// Sostituisci i segnaposto nel template
$templateProcessor->setValue('company_name', 'Nome Azienda');
$templateProcessor->setValue('project_name', 'Nome Progetto');
$templateProcessor->setValue('date', dol_print_date(dol_now(), 'day'));

// Salva il documento generato
$templateProcessor->saveAs($outputPath);

print '<div class="success">' . $langs->trans("DocumentGenerated") . ': <a href="' . DOL_URL_ROOT . '/document.php?modulepart=industria40&file=' . urlencode(basename($outputPath)) . '">' . $langs->trans("DownloadHere") . '</a></div>';

llxFooter();
$db->close();