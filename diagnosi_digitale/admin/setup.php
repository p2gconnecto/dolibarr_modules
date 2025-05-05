<?php
// filepath: /home/dolibarr/.volumes/dolibarr/custom/diagnosi_digitale/admin/setup.php

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../lib/diagnosi_digitale.lib.php';

$langs->load("admin");
$langs->load("diagnosi_digitale@diagnosi_digitale");

// Access control
if (!$user->admin) {
    dol_syslog(__METHOD__ . " Access forbidden for user: " . $user->login, LOG_ERR);
    accessforbidden();
}

$action = GETPOST('action', 'alpha');

// Log the action
dol_syslog(__METHOD__ . " Action: " . $action, LOG_DEBUG);

// Actions
if ($action == 'set') {
    $value = GETPOST('value', 'alpha');
    $name = GETPOST('name', 'alpha');
    dol_syslog(__METHOD__ . " Setting constant: $name with value: $value", LOG_DEBUG);

    $result = dolibarr_set_const($db, $name, $value, 'chaine', 0, '', $conf->entity);
    if ($result > 0) {
        dol_syslog(__METHOD__ . " Constant $name set successfully", LOG_INFO);
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        dol_syslog(__METHOD__ . " Failed to set constant $name", LOG_ERR);
        setEventMessages($langs->trans("ErrorFailedToSaveSetup"), null, 'errors');
    }
} elseif ($action == 'del') {
    $name = GETPOST('name', 'alpha');
    dol_syslog(__METHOD__ . " Deleting constant: $name", LOG_DEBUG);

    $result = dolibarr_del_const($db, $name, $conf->entity);
    if ($result > 0) {
        dol_syslog(__METHOD__ . " Constant $name deleted successfully", LOG_INFO);
        setEventMessages($langs->trans("SetupDeleted"), null, 'mesgs');
    } else {
        dol_syslog(__METHOD__ . " Failed to delete constant $name", LOG_ERR);
        setEventMessages($langs->trans("ErrorFailedToDeleteSetup"), null, 'errors');
    }
}

// Page header
dol_syslog(__METHOD__ . " Loading setup page", LOG_DEBUG);
llxHeader('', $langs->trans("DiagnosiDigitaleSetup"));

// Title
print load_fiche_titre($langs->trans("DiagnosiDigitaleSetup"), '', 'title_setup');

// Setup form
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="action" value="set">';

print '<table class="noborder centpercent">';

// Example setting
print '<tr class="oddeven">';
print '<td>'.$langs->trans("PDFModel").'</td>';
print '<td>';
print '<input type="text" name="value" value="'.dol_escape_htmltag($conf->global->DIAGNOSI_DIGITALE_PDF_MODEL).'">';
print '<input type="hidden" name="name" value="DIAGNOSI_DIGITALE_PDF_MODEL">';
print '</td>';
print '<td class="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';

print '</table>';
print '</form>';

// Footer
dol_syslog(__METHOD__ . " Setup page loaded successfully", LOG_DEBUG);
llxFooter();
$db->close();
