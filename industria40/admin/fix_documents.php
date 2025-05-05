<?php
/**
 * Script per riparare le directory dei documenti e le configurazioni del modulo Industria40
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

// Controllo accessi - solo admin
if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');

// Visualizza intestazione della pagina
llxHeader('', 'Riparazione documenti Industria40');

print '<h1>Riparazione documenti del modulo Industria40</h1>';

// Form per la riparazione
if (empty($action)) {
    print '<p class="warning">Questo script correggerà le directory dei documenti e le configurazioni del modulo Industria40.</p>';
    print '<p>Operazioni che verranno eseguite:</p>';
    print '<ol>';
    print '<li>Creazione delle directory mancanti con permessi corretti</li>';
    print '<li>Impostazione delle costanti di configurazione</li>';
    print '<li>Registrazione del modulo nel sistema dei documenti</li>';
    print '</ol>';

    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="fix">';
    print '<input type="submit" class="button" value="Esegui riparazione">';
    print '</form>';
}

// Esegui la riparazione
if ($action === 'fix') {
    print '<h2>Risultati della riparazione</h2>';
    print '<ul>';

    // Registra il modulo nei moduli Dolibarr
    if (!in_array('industria40', $conf->modules)) {
        $conf->modules[] = 'industria40';
        print '<li>Modulo industria40 aggiunto all\'elenco dei moduli</li>';
    } else {
        print '<li>Il modulo industria40 è già registrato</li>';
    }

    // Impostazione delle costanti
    print '<li>Impostazione delle costanti:</li><ul>';
    $constants = array(
        'MAIN_MODULE_INDUSTRIA40_DOCUMENT_ROOT' => 'DOL_DATA_ROOT/industria40',
        'INDUSTRIA40_ALLOW_EXTERNAL_DOWNLOAD' => '1',
    );

    foreach ($constants as $const => $value) {
        dolibarr_set_const($db, $const, $value, 'chaine', 0, '', $conf->entity);
        print '<li>Impostata ' . $const . ' = ' . $value . '</li>';
    }
    print '</ul>';

    // Configurazione dell'oggetto conf
    if (!isset($conf->industria40)) {
        $conf->industria40 = new stdClass();
        print '<li>Creato oggetto conf->industria40</li>';
    } else {
        print '<li>Oggetto conf->industria40 già esistente</li>';
    }

    // Imposta directory
    $conf->industria40->dir_output = DOL_DATA_ROOT . '/industria40';
    print '<li>Impostato conf->industria40->dir_output = ' . $conf->industria40->dir_output . '</li>';

    if (!isset($conf->industria40->multidir_output)) {
        $conf->industria40->multidir_output = array();
    }
    $conf->industria40->multidir_output[$conf->entity] = $conf->industria40->dir_output;
    print '<li>Impostato conf->industria40->multidir_output[' . $conf->entity . '] = ' . $conf->industria40->multidir_output[$conf->entity] . '</li>';

    // Creazione delle directory
    $directories = array(
        DOL_DATA_ROOT . '/industria40',
        DOL_DATA_ROOT . '/industria40/documents',
        DOL_DATA_ROOT . '/industria40/thumbnails',
        DOL_DATA_ROOT . '/industria40/temp',
    );

    print '<li>Creazione delle directory:</li><ul>';
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (dol_mkdir($dir) >= 0) {
                chmod($dir, 0775); // Permessi più permissivi per risolvere problemi di scrittura
                print '<li>Directory creata con successo: ' . $dir . '</li>';
            } else {
                print '<li class="error">Errore nella creazione della directory: ' . $dir . '</li>';
            }
        } else {
            chmod($dir, 0775); // Aggiorna i permessi anche se la directory esiste già
            print '<li>La directory esiste già, permessi aggiornati: ' . $dir . '</li>';
        }
    }
    print '</ul>';

    // Controllo del file document.php di Dolibarr
    $document_php = DOL_DOCUMENT_ROOT . '/document.php';
    print '<li>Controllo di document.php:</li>';
    if (file_exists($document_php)) {
        print '<ul><li>File document.php trovato</li>';
        print '<li>Permessi: ' . substr(sprintf('%o', fileperms($document_php)), -4) . '</li></ul>';
    } else {
        print '<ul><li class="error">File document.php non trovato!</li></ul>';
    }

    print '<li>Pulizia della cache:</li><ul>';
    // Pulisci la cache delle traduzioni
    $langs->clearCache();
    print '<li>Cache traduzioni pulita</li>';

    // Pulisci la cache dei template
    if (function_exists('clearTemplateCache')) {
        clearTemplateCache();
        print '<li>Cache template pulita</li>';
    }
    print '</ul>';

    // Carica nuovamente il modulo
    print '<li>Ricaricamento del modulo:</li>';
    try {
        // Ricarica i moduli
        $modulesinit = array();
        $modulesdir = dolGetModulesDirs();

        foreach ($modulesdir as $dir) {
            if (file_exists($dir . 'modIndustria40.class.php')) {
                print '<ul><li>Trovato modulo in: ' . $dir . '</li></ul>';
            }
        }

        print '<ul><li>Ricaricamento completato</li></ul>';
    } catch (Exception $e) {
        print '<ul><li class="error">Errore durante il ricaricamento: ' . $e->getMessage() . '</li></ul>';
    }

    print '</ul>';

    print '<div class="info">';
    print '<p><strong>Riparazione completata.</strong></p>';
    print '<p>Ora puoi tornare al modulo e verificare il corretto funzionamento:</p>';
    print '<a href="' . DOL_URL_ROOT . '/custom/industria40/industria40index.php" class="button">Torna al modulo</a>';
    print '</div>';
}

llxFooter();
$db->close();
