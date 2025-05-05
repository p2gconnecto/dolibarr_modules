<?php
/**
 * File per l'inizializzazione del modulo Industria40
 */

// Registra il modulo nei moduli attivi di Dolibarr se non è già registrato
if (!in_array('industria40', $conf->modules)) {
    $conf->modules[] = 'industria40';
}

// Inizializza l'oggetto conf->industria40 se non esiste
if (!isset($conf->industria40)) {
    $conf->industria40 = new stdClass();
}

// Imposta la directory di output del modulo
$conf->industria40->dir_output = DOL_DATA_ROOT . '/industria40';

// Inizializza l'array multidir_output se non esiste
if (!isset($conf->industria40->multidir_output)) {
    $conf->industria40->multidir_output = array();
}

// Imposta il percorso per l'entità corrente
$conf->industria40->multidir_output[$conf->entity] = $conf->industria40->dir_output;

// Crea le directory necessarie se non esistono
$dirs_to_create = array(
    DOL_DATA_ROOT . '/industria40',
    DOL_DATA_ROOT . '/industria40/documents',
    DOL_DATA_ROOT . '/industria40/thumbnails',
    DOL_DATA_ROOT . '/industria40/temp',
    DOL_DATA_ROOT . '/industria40/tags',
    DOL_DATA_ROOT . '/industria40/descriptions'
);

foreach ($dirs_to_create as $dir) {
    if (!is_dir($dir)) {
        if (dol_mkdir($dir) >= 0) {
            @chmod($dir, 0775);
        }
    }
}

// Imposta esplicitamente alcune costanti necessarie per il modulo
dolibarr_set_const($db, 'MAIN_MODULE_INDUSTRIA40', '1', 'chaine', 0, '', 0);
dolibarr_set_const($db, 'INDUSTRIA40_ALLOW_EXTERNAL_DOWNLOAD', '1', 'chaine', 0, '', 0);
