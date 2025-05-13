<?php
/**
 * Inizializzazione del modulo Industria 4.0
 * Configura le variabili necessarie per il modulo
 */

dol_syslog("init.inc.php: Initializing Industria40 module", LOG_DEBUG);

global $conf;

// Definizione delle directory
$conf->industria40 = new stdClass();
$conf->industria40->dir_output = DOL_DATA_ROOT . '/industria40';
dol_syslog("init.inc.php: Set conf->industria40->dir_output = " . $conf->industria40->dir_output, LOG_INFO);

// Supporto multi-entity
$conf->industria40->multidir_output = array();
$conf->industria40->multidir_output[$conf->entity] = $conf->industria40->dir_output;
dol_syslog("init.inc.php: Set conf->industria40->multidir_output for entity " . $conf->entity, LOG_INFO);

// Registrazione del modulo con il gestore di hook
if (is_object($hookmanager)) {
    $hookmanager->initHooks(array('industria40'));
    dol_syslog("init.inc.php: Registered industria40 with hookmanager", LOG_INFO);
}

// Aggiungi il tipo di file al gestore documenti di Dolibarr
global $filetype;
if (empty($filetype)) $filetype = array();
$filetype['industria40'] = array(
    'modulepart' => 'industria40',
    'name' => 'Industria 4.0',
    'enabled' => 1,
    'dir' => 'industria40/documents',
    'rel_dir' => 'industria40/documents'
);
dol_syslog("init.inc.php: Registered industria40 in filetype array", LOG_INFO);

// Fine dell'inizializzazione
dol_syslog("init.inc.php: Configuration complete for Industria40 module", LOG_DEBUG);
