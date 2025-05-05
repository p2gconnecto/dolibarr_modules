<?php

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Controllo degli accessi
if (!$user->admin) accessforbidden();

// Visualizza intestazione della pagina
llxHeader('', 'Diagnosi Industria40');

print '<h1>Diagnosi del modulo Industria40</h1>';

print '<h2>Configurazione attuale</h2>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>Impostazione</td>';
print '<td>Valore</td>';
print '<td>Note</td>';
print '</tr>';

// Controlla se industria40 è nei moduli di Dolibarr
print '<tr>';
print '<td>Modulo registrato</td>';
print '<td>';
if (in_array('industria40', $conf->modules)) {
    print '<span class="statusok">Sì</span>';
} else {
    print '<span class="statuserror">No</span>';
}
print '</td>';
print '<td>Il modulo deve essere registrato nei moduli di Dolibarr</td>';
print '</tr>';

// Controlla DOL_DATA_ROOT
print '<tr>';
print '<td>DOL_DATA_ROOT</td>';
print '<td>' . DOL_DATA_ROOT . '</td>';
print '<td>Directory principale per i dati di Dolibarr</td>';
print '</tr>';

// Controlla MAIN_MODULE_INDUSTRIA40_DOCUMENT_ROOT
print '<tr>';
print '<td>MAIN_MODULE_INDUSTRIA40_DOCUMENT_ROOT</td>';
print '<td>' . (!empty($conf->global->MAIN_MODULE_INDUSTRIA40_DOCUMENT_ROOT) ? $conf->global->MAIN_MODULE_INDUSTRIA40_DOCUMENT_ROOT : '<span class="statuserror">Non definito</span>') . '</td>';
print '<td>Dovrebbe essere impostato su DOL_DATA_ROOT/industria40</td>';
print '</tr>';

// Controlla dir_output
print '<tr>';
print '<td>conf->industria40->dir_output</td>';
print '<td>' . (isset($conf->industria40->dir_output) ? $conf->industria40->dir_output : '<span class="statuserror">Non definito</span>') . '</td>';
print '<td>Dovrebbe puntare alla directory dei documenti per questo modulo</td>';
print '</tr>';

// Controlla multidir_output
print '<tr>';
print '<td>conf->industria40->multidir_output</td>';
print '<td>' . (isset($conf->industria40->multidir_output[$conf->entity]) ? $conf->industria40->multidir_output[$conf->entity] : '<span class="statuserror">Non definito</span>') . '</td>';
print '<td>Dovrebbe puntare alla directory dei documenti per questo modulo (per questa entità)</td>';
print '</tr>';

// Controlla permessi UMASK
print '<tr>';
print '<td>MAIN_UMASK</td>';
print '<td>' . (!empty($conf->global->MAIN_UMASK) ? $conf->global->MAIN_UMASK : '<span class="statuserror">Non definito</span>') . '</td>';
print '<td>Dovrebbe essere impostato su 0664 o 0666 per permettere la scrittura dei file</td>';
print '</tr>';

print '</table>';

print '<h2>Test delle Directory</h2>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>Directory</td>';
print '<td>Esiste</td>';
print '<td>Scrivibile</td>';
print '<td>Permessi</td>';
print '</tr>';

// Definisci le directory da testare
$directories = array(
    DOL_DATA_ROOT => 'Data Root',
    DOL_DATA_ROOT . '/industria40' => 'Industria40 Root',
    DOL_DATA_ROOT . '/industria40/documents' => 'Documents',
    DOL_DATA_ROOT . '/industria40/thumbnails' => 'Thumbnails'
);

foreach ($directories as $dir => $label) {
    print '<tr>';
    print '<td>' . $dir . ' (' . $label . ')</td>';

    // Verifica esistenza
    if (is_dir($dir)) {
        print '<td><span class="statusok">Sì</span></td>';

        // Verifica se è scrivibile
        if (is_writable($dir)) {
            print '<td><span class="statusok">Sì</span></td>';
        } else {
            print '<td><span class="statuserror">No</span></td>';
        }

        // Mostra permessi
        print '<td>' . substr(sprintf('%o', fileperms($dir)), -4) . '</td>';
    } else {
        print '<td><span class="statuserror">No</span></td>';
        print '<td>N/A</td>';
        print '<td>N/A</td>';
    }

    print '</tr>';
}

print '</table>';

print '<h2>Azioni di risoluzione</h2>';

// Form per creare o correggere le directory
print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="fix_directories">';

print '<input type="submit" class="button" value="Correggi struttura directory e permessi">';
print '</form>';

// Gestisci l'azione di correzione
if (GETPOST('action') == 'fix_directories') {
    print '<h3>Risultati dell\'operazione</h3>';
    print '<ul>';

    // Registra il modulo nei moduli Dolibarr
    if (!in_array('industria40', $conf->modules)) {
        $conf->modules[] = 'industria40';
        print '<li>Aggiunto industria40 all\'array dei moduli</li>';
    }

    // Imposta la costante del document root se non è già definita
    if (empty($conf->global->MAIN_MODULE_INDUSTRIA40_DOCUMENT_ROOT)) {
        dolibarr_set_const($db, 'MAIN_MODULE_INDUSTRIA40_DOCUMENT_ROOT', 'DOL_DATA_ROOT/industria40', 'chaine', 0, '', $conf->entity);
        print '<li>Impostata costante MAIN_MODULE_INDUSTRIA40_DOCUMENT_ROOT</li>';
    }

    // Configura conf->industria40 se non esiste
    if (!isset($conf->industria40)) {
        $conf->industria40 = new stdClass();
        print '<li>Creato oggetto conf->industria40</li>';
    }

    // Configura la directory di output
    if (!isset($conf->industria40->dir_output)) {
        $conf->industria40->dir_output = DOL_DATA_ROOT . '/industria40';
        print '<li>Impostato conf->industria40->dir_output</li>';
    }

    // Configura multidir_output
    if (!isset($conf->industria40->multidir_output)) {
        $conf->industria40->multidir_output = array();
    }
    $conf->industria40->multidir_output[$conf->entity] = $conf->industria40->dir_output;
    print '<li>Impostato conf->industria40->multidir_output</li>';

    // Imposta MAIN_UMASK a 0664 se non è già definito
    if (empty($conf->global->MAIN_UMASK)) {
        dolibarr_set_const($db, 'MAIN_UMASK', '0664', 'chaine', 0, '', $conf->entity);
        print '<li>Impostato MAIN_UMASK a 0664</li>';
    }

    // Crea le directory necessarie
    $dirs_to_create = array(
        DOL_DATA_ROOT . '/industria40',
        DOL_DATA_ROOT . '/industria40/documents',
        DOL_DATA_ROOT . '/industria40/thumbnails'
    );

    foreach ($dirs_to_create as $dir) {
        if (!is_dir($dir)) {
            if (dol_mkdir($dir) >= 0) {
                @chmod($dir, 0775);
                print '<li>Directory creata: ' . $dir . '</li>';
            } else {
                print '<li class="error">Impossibile creare la directory: ' . $dir . '</li>';
            }
        } else {
            @chmod($dir, 0775);
            print '<li>Corretti i permessi per: ' . $dir . '</li>';
        }
    }

    print '</ul>';

    print '<p>Ricarica la pagina per verificare le modifiche.</p>';
}

// Aggiungi un test di documento generico
print '<h2>Test di accesso ai documenti</h2>';
print '<p>Questa sezione crea un file di test e verifica l\'accesso tramite document.php</p>';

// Form per creare un file di test
print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="create_test_file">';
print '<input type="submit" class="button" value="Crea file di test e verifica l\'accesso">';
print '</form>';

// Gestisci l'azione di creazione del file di test
if (GETPOST('action') == 'create_test_file') {
    // Crea una directory e un file di test
    $test_dir = DOL_DATA_ROOT . '/industria40/documents/test';
    $test_file = $test_dir . '/test_file.txt';
    $test_content = 'Questo è un file di test creato per verificare l\'accesso ai documenti. Timestamp: ' . date('Y-m-d H:i:s');

    print '<h3>Risultati del test</h3>';

    // Crea la directory di test se non esiste
    if (!is_dir($test_dir)) {
        if (dol_mkdir($test_dir) >= 0) {
            @chmod($test_dir, 0775);
            print '<p class="ok">Directory di test creata: ' . $test_dir . '</p>';
        } else {
            print '<p class="error">Impossibile creare la directory di test: ' . $test_dir . '</p>';
            // Ferma l'esecuzione se non è possibile creare la directory
            llxFooter();
            exit;
        }
    }

    // Crea il file di test
    if (file_put_contents($test_file, $test_content) !== false) {
        @chmod($test_file, 0664);
        print '<p class="ok">File di test creato: ' . $test_file . '</p>';

        // Genera l'URL per accedere al file
        $file_path_relative = 'documents/test/test_file.txt';
        $file_url = DOL_URL_ROOT . '/document.php?modulepart=industria40&file=' . urlencode($file_path_relative);

        // Aggiungi una sezione di debug più dettagliata
        print '<h4>Dettagli di Debug per l\'accesso ai file:</h4>';
        print '<table class="noborder">';
        print '<tr class="liste_titre"><td>Parametro</td><td>Valore</td><td>Note</td></tr>';

        // Controlla se il file esiste
        print '<tr><td>File esiste fisicamente</td><td>' . (file_exists($test_file) ? '<span class="statusok">Sì</span>' : '<span class="statuserror">No</span>') . '</td><td>Percorso completo: ' . $test_file . '</td></tr>';

        // URL completa
        print '<tr><td>URL di accesso</td><td>' . $file_url . '</td><td>URL completo per accedere al file</td></tr>';

        // Verifica DOL_URL_ROOT
        print '<tr><td>DOL_URL_ROOT</td><td>' . DOL_URL_ROOT . '</td><td>Base URL di Dolibarr</td></tr>';

        // Controlla modulepart e directory configurate
        print '<tr><td>modulepart</td><td>industria40</td><td>Nome del modulo usato in document.php</td></tr>';

        // Verifica la configurazione nel file conf.php
        $conf_file = DOL_DOCUMENT_ROOT . '/conf/conf.php';
        $has_modulepart_def = false;
        if (file_exists($conf_file)) {
            $conf_content = file_get_contents($conf_file);
            if (strpos($conf_content, 'dolibarr_main_document_root_alt') !== false) {
                print '<tr><td>dolibarr_main_document_root_alt</td><td>Definito in conf.php</td><td>Necessario per i moduli custom</td></tr>';
            } else {
                print '<tr><td>dolibarr_main_document_root_alt</td><td><span class="statuserror">Non definito in conf.php</span></td><td>Potrebbe essere necessario configurarlo</td></tr>';
            }
        }

        // Verifica manualmente il percorso del file in multidir_output
        if (isset($conf->industria40->multidir_output[$conf->entity])) {
            $expected_path = $conf->industria40->multidir_output[$conf->entity] . '/' . $file_path_relative;
            print '<tr><td>Percorso previsto</td><td>' . $expected_path . '</td><td>Basato su multidir_output</td></tr>';
            print '<tr><td>Il file esiste nel percorso previsto</td><td>' . (file_exists($expected_path) ? '<span class="statusok">Sì</span>' : '<span class="statuserror">No</span>') . '</td><td>Verifica basata su multidir_output</td></tr>';
        }

        // Controlla permessi SELinux
        if (function_exists('shell_exec')) {
            $selinux_check = shell_exec('ls -Z ' . $test_file . ' 2>&1');
            print '<tr><td>SELinux Context</td><td>' . htmlspecialchars($selinux_check) . '</td><td>Verificare che il context sia corretto</td></tr>';
        }

        print '</table>';

        // Test diretto di document.php
        print '<h4>Test diretto di document.php:</h4>';
        print '<p>Prova a caricare direttamente il file tramite document.php:</p>';
        print '<iframe src="' . $file_url . '" style="width:100%; height:100px; border:1px solid #ccc;"></iframe>';

        // Link diretto da testare manualmente
        print '<p>Link diretto da testare: <a href="' . $file_url . '" target="_blank">' . $file_url . '</a></p>';

        // Suggerimenti per il debug
        print '<h4>Suggerimenti per il debug:</h4>';
        print '<ol>';
        print '<li>Verifica che <code>document.php</code> stia cercando il file nel percorso corretto</li>';
        print '<li>Controlla eventuali errori 404 o 403 nella console del browser quando provi ad accedere al file</li>';
        print '<li>Verifica che il modulo sia correttamente registrato in <code>conf->modules</code></li>';
        print '<li>Controlla che <code>multidir_output</code> e <code>dir_output</code> puntino alle directory corrette</li>';
        print '</ol>';

        // Test alternativo per modulepart
        print '<h4>Test con modulepart alternativo:</h4>';
        print '<p>A volte "ecm" funziona come modulepart di fallback:</p>';
        $alt_url = DOL_URL_ROOT . '/document.php?modulepart=ecm&file=' . urlencode($file_path_relative);
        print '<p><a href="' . $alt_url . '" target="_blank">Prova con modulepart=ecm</a></p>';

        // Mostra il codice di esempio per accedere al file
        print '<h4>Esempio di codice per accedere al file:</h4>';
        print '<pre style="background-color: #f4f4f4; padding: 10px; border: 1px solid #ddd;">';
        print htmlspecialchars('$file_path_relative = \'documents/test/test_file.txt\';
$file_url = DOL_URL_ROOT . \'/document.php?modulepart=industria40&file=\' . urlencode($file_path_relative);
print \'<a href="\' . $file_url . \'" target="_blank">Apri file</a>\';');
        print '</pre>';

        // Aggiungi un test di document.php con tutti i parametri espliciti
        print '<h4>Test con parametri espliciti:</h4>';
        $explicit_url = DOL_URL_ROOT . '/document.php?modulepart=industria40&file=' . urlencode($file_path_relative) . '&entity=' . $conf->entity;
        print '<p><a href="' . $explicit_url . '" target="_blank">Test con tutti i parametri</a></p>';
    } else {
        print '<p class="error">Impossibile creare il file di test: ' . $test_file . '</p>';
    }
}

// Aggiungi un nuovo test per document.php
print '<h2>Test Diretto di document.php</h2>';
print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="test_document">';
print '<table class="noborder">';
print '<tr class="liste_titre"><td colspan="2">Test parametri document.php</td></tr>';
print '<tr><td>modulepart</td><td><input type="text" name="test_modulepart" value="industria40"></td></tr>';
print '<tr><td>file</td><td><input type="text" name="test_file" value="documents/test/test_file.txt"></td></tr>';
print '<tr><td>entity</td><td><input type="text" name="test_entity" value="' . $conf->entity . '"></td></tr>';
print '<tr><td>attachment</td><td><input type="checkbox" name="test_attachment" value="1"> (force download)</td></tr>';
print '<tr><td></td><td><input type="submit" class="button" value="Testa document.php"></td></tr>';
print '</table>';
print '</form>';

if (GETPOST('action') == 'test_document') {
    $test_modulepart = GETPOST('test_modulepart', 'alpha');
    $test_file = GETPOST('test_file', 'alpha');
    $test_entity = GETPOST('test_entity', 'int');
    $test_attachment = GETPOST('test_attachment', 'int');

    $test_url = DOL_URL_ROOT . '/document.php?modulepart=' . urlencode($test_modulepart) .
               '&file=' . urlencode($test_file) .
               '&entity=' . $test_entity;

    if ($test_attachment) {
        $test_url .= '&attachment=1';
    }

    print '<div class="info">';
    print '<p>URL generato: <a href="' . $test_url . '" target="_blank">' . $test_url . '</a></p>';
    print '<iframe src="' . $test_url . '" style="width:100%; height:100px; border:1px solid #ccc;"></iframe>';
    print '</div>';
}

// Aggiungi un nuovo pulsante per installare il modulo
print '<h2>Reinstallazione Modulo</h2>';
print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="reinstall_module">';
print '<input type="submit" class="button" value="Reinstalla e Riattiva il modulo" onclick="return confirm(\'Sei sicuro di voler reinstallare il modulo?\');">';
print '</form>';

if (GETPOST('action') == 'reinstall_module') {
    // Esegui la reinstallazione del modulo
    require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

    print '<h3>Risultati Reinstallazione</h3>';
    print '<ul>';

    // Disattiva il modulo
    $res = activateModule('modIndustria40');
    if ($res) {
        print '<li>Modulo riattivato con successo</li>';
    } else {
        print '<li class="error">Errore durante la riattivazione del modulo</li>';
    }

    // Ricrea la struttura di directory
    $dirs_to_create = array(
        DOL_DATA_ROOT . '/industria40',
        DOL_DATA_ROOT . '/industria40/documents',
        DOL_DATA_ROOT . '/industria40/thumbnails'
    );

    foreach ($dirs_to_create as $dir) {
        if (!is_dir($dir)) {
            if (dol_mkdir($dir) >= 0) {
                chmod($dir, 0775);
                print '<li>Directory creata: ' . $dir . '</li>';
            } else {
                print '<li class="error">Impossibile creare la directory: ' . $dir . '</li>';
            }
        } else {
            chmod($dir, 0775);
            print '<li>Aggiornati i permessi per: ' . $dir . '</li>';
        }
    }

    // Imposta esplicitamente le costanti del modulo
    dolibarr_set_const($db, 'MAIN_MODULE_INDUSTRIA40_DOCUMENT_ROOT', 'DOL_DATA_ROOT/industria40', 'chaine', 0, '', $conf->entity);
    print '<li>Impostata la costante MAIN_MODULE_INDUSTRIA40_DOCUMENT_ROOT</li>';

    // Imposta il flag per permettere l'accesso esterno
    dolibarr_set_const($db, 'INDUSTRIA40_ALLOW_EXTERNAL_DOWNLOAD', 1, 'chaine', 0, '', $conf->entity);
    print '<li>Impostata la costante INDUSTRIA40_ALLOW_EXTERNAL_DOWNLOAD</li>';

    // Configura l'oggetto conf
    if (!isset($conf->industria40)) {
        $conf->industria40 = new stdClass();
    }
    $conf->industria40->dir_output = DOL_DATA_ROOT . '/industria40';

    if (!isset($conf->industria40->multidir_output)) {
        $conf->industria40->multidir_output = array();
    }
    $conf->industria40->multidir_output[$conf->entity] = $conf->industria40->dir_output;

    print '<li>Oggetto conf->industria40 configurato</li>';
    print '</ul>';

    print '<p>Modulo reinstallato. <a href="' . $_SERVER["PHP_SELF"] . '">Ricarica la pagina</a> per verificare lo stato.</p>';
}

print '<br><br>';

llxFooter();
$db->close();
