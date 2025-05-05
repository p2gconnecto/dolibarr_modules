<?php
// filepath: /home/dolibarr/.volumes/dolibarr/custom/diagnosi_digitale/admin/admin_pdf_parameters.php

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once '../class/diagnosi_digitale.class.php';

$langs->load("admin");
$langs->load("diagnosi_digitale@diagnosi_digitale");

// Access control
if (!$user->admin) {
    accessforbidden();
}

$action = GETPOST('action', 'alpha');

// Initialize conf object for Diagnosi Digitale
if (!isset($conf->diagnosi_digitale)) {
    $conf->diagnosi_digitale = new stdClass();
}

// Default values
if (!isset($conf->global->DIAGNOSI_DIGITALE_PDF_MODEL)) {
    dolibarr_set_const($db, 'DIAGNOSI_DIGITALE_PDF_MODEL', 'default', 'chaine', 0, '', $conf->entity);
}

if (!isset($conf->global->DIAGNOSI_DIGITALE_FIELD_MAPPING)) {
    dolibarr_set_const($db, 'DIAGNOSI_DIGITALE_FIELD_MAPPING', '{}', 'chaine', 0, '', $conf->entity);
}

$default_pdf_model = $conf->global->DIAGNOSI_DIGITALE_PDF_MODEL ?: 'default';
$default_field_mapping = $conf->global->DIAGNOSI_DIGITALE_FIELD_MAPPING ?: '{}';

// Actions
if ($action == 'save') {
    $default_pdf_model = GETPOST('pdf_model', 'alpha');
    $default_field_mapping = GETPOST('field_mapping', 'alpha');

    dolibarr_set_const($db, 'DIAGNOSI_DIGITALE_PDF_MODEL', $default_pdf_model, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DIAGNOSI_DIGITALE_FIELD_MAPPING', $default_field_mapping, 'chaine', 0, '', $conf->entity);

    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
}

// Load current values
$default_pdf_model = $conf->global->DIAGNOSI_DIGITALE_PDF_MODEL;
$default_field_mapping = $conf->global->DIAGNOSI_DIGITALE_FIELD_MAPPING;

// Page header
llxHeader('', $langs->trans("DiagnosiDigitalePDFSetup"));

// Title
print load_fiche_titre($langs->trans("DiagnosiDigitalePDFSetup"), '', 'title_setup');

// Form
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="action" value="save">';

print '<table class="noborder centpercent">';

// PDF Model
print '<tr class="oddeven">';
print '<td>'.$langs->trans("PDFModel").'</td>';
print '<td>';
print '<input type="text" name="pdf_model" value="'.dol_escape_htmltag($default_pdf_model).'" class="minwidth300">';
print '</td>';
print '</tr>';

// Field Mapping
print '<tr class="oddeven">';
print '<td>'.$langs->trans("FieldMapping").'</td>';
print '<td>';
print '<textarea name="field_mapping" rows="5" class="minwidth300">'.dol_escape_htmltag($default_field_mapping).'</textarea>';
print '</td>';
print '</tr>';

print '</table>';

print '<div class="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

// Footer
llxFooter();
$db->close();