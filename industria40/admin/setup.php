<?php
/* Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025		SuperAdmin
 * Copyright (C) 2023 SuperAdmin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    industria40/admin/setup.php
 * \ingroup industria40
 * \brief   Industria40 setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

global $langs, $user, $db, $conf;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/industria40.lib.php';

// Translations
$langs->loadLangs(array("admin", "industria40@industria40"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$error = 0;
$setupnotempty = 0;

// Azione per salvare le impostazioni
if ($action == 'update') {
    // Salva INDUSTRIA40_ENABLE_OCR
    $enable_ocr = GETPOST('INDUSTRIA40_ENABLE_OCR', 'int');
    dolibarr_set_const($db, 'INDUSTRIA40_ENABLE_OCR', $enable_ocr, 'chaine', 1, '', $conf->entity);

    // Salva OPENAI_API_KEY
    $openai_key = GETPOST('INDUSTRIA40_OPENAI_API_KEY', 'alpha');
    dolibarr_set_const($db, 'INDUSTRIA40_OPENAI_API_KEY', $openai_key, 'chaine', 0, '', $conf->entity);

    setEventMessages($langs->trans("SetupSaved"), null);

    // Verifica disponibilità strumenti OCR
    if ($enable_ocr) {
        $tesseract_available = false;
        if (function_exists('exec')) {
            exec('which tesseract', $output, $return_var);
            $tesseract_available = ($return_var === 0);
        }

        if (!$tesseract_available) {
            setEventMessages($langs->trans("TesseractNotAvailable"), null, 'warnings');
        }
    }

    // Assicura che OCR sia sempre abilitato per impostazione predefinita
    if (empty($conf->global->INDUSTRIA40_ENABLE_OCR)) {
        dolibarr_set_const($db, 'INDUSTRIA40_ENABLE_OCR', 1, 'chaine', 1, '', $conf->entity);
        dol_syslog("Enabled OCR by default", LOG_DEBUG);
    }

    // Redirect per evitare ritrasmissione del form
    header('Location: '.$_SERVER["PHP_SELF"]);
    exit();
}

// Titolo e intestazione
$title = $langs->trans("Industria40Setup");
llxHeader('', $title);

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

// Setup page content
$form = new Form($db);

// Sezione configurazione OCR
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '</tr>';

// Opzione per abilitare OCR
$setupnotempty++;
print '<tr class="oddeven">';
print '<td>'.$langs->trans("EnableOCR").'</td>';
print '<td>';
print $form->selectyesno("INDUSTRIA40_ENABLE_OCR", $conf->global->INDUSTRIA40_ENABLE_OCR, 1);
print '</td>';
print '<td>'.$langs->trans("EnableOCRDescription").'</td>';
print '</tr>';

// Configurazione API OpenAI per le descrizioni generate tramite AI
$setupnotempty++;
print '<tr class="oddeven">';
print '<td>'.$langs->trans("OpenAIAPIKey").'</td>';
print '<td><input type="password" class="flat minwidth300" name="INDUSTRIA40_OPENAI_API_KEY" value="' . $conf->global->INDUSTRIA40_OPENAI_API_KEY . '"></td>';
print '<td>'.$langs->trans("OpenAIAPIKeyDescription").'</td>';
print '</tr>';

print '</table>';

// Controllo della disponibilità degli strumenti OCR sul server
if (!empty($conf->global->INDUSTRIA40_ENABLE_OCR)) {
    print '<br><div class="opacitymedium">'.$langs->trans("CheckingOCRTools").'...</div>';

    // Verifica tesseract
    $tesseract_available = false;
    if (function_exists('exec')) {
        exec('which tesseract', $output, $return_var);
        $tesseract_available = ($return_var === 0);
    }

    // Verifica ImageMagick
    $imagemagick_available = false;
    if (function_exists('exec')) {
        exec('which convert', $output, $return_var);
        $imagemagick_available = ($return_var === 0);
    }

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Tool").'</td>';
    print '<td>'.$langs->trans("Status").'</td>';
    print '</tr>';

    // Status Tesseract
    print '<tr class="oddeven">';
    print '<td>Tesseract OCR</td>';
    if ($tesseract_available) {
        print '<td class="green">'.$langs->trans("Available").'</td>';
    } else {
        print '<td class="red">'.$langs->trans("NotAvailable").'</td>';
    }
    print '</tr>';

    // Status ImageMagick
    print '<tr class="oddeven">';
    print '<td>ImageMagick (convert)</td>';
    if ($imagemagick_available) {
        print '<td class="green">'.$langs->trans("Available").'</td>';
    } else {
        print '<td class="red">'.$langs->trans("NotAvailable").'</td>';
    }
    print '</tr>';

    print '</table>';

    if (!$tesseract_available || !$imagemagick_available) {
        print '<div class="warning">'.$langs->trans("OCRToolsMissing").'</div>';
    }
}

// Pulsanti
print '<br><div class="center">';
print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
print '</div>';
print '</form>';

// Page end
llxFooter();
$db->close();
