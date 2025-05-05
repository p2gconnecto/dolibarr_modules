<?php
/**
 * Initialization file for the Industria40 module
 */

// Initialize module document directories
dol_syslog("init.inc.php: Initializing Industria40 module", LOG_DEBUG);

// Check if file is included from another script or direct call
if (!defined('NOREQUIREMENU')) {
    // Ensure correct file includes
    if (!defined('NOREQUIREUSER')) define('NOREQUIREUSER', '1');
    if (!defined('NOREQUIRETRAN')) define('NOREQUIRETRAN', '1');
}

// Ensure module is registered
if (!in_array('industria40', $conf->modules)) {
    $conf->modules[] = 'industria40';
    dol_syslog("init.inc.php: Added industria40 to conf->modules", LOG_INFO);
}

// Configure directory structure
if (!isset($conf->industria40)) {
    $conf->industria40 = new stdClass();
    dol_syslog("init.inc.php: Created conf->industria40 object", LOG_INFO);
}

// Set dir_output and ensure it's a full absolute path
$conf->industria40->dir_output = DOL_DATA_ROOT . '/industria40';
dol_syslog("init.inc.php: Set conf->industria40->dir_output = " . $conf->industria40->dir_output, LOG_INFO);

if (!isset($conf->industria40->multidir_output)) {
    $conf->industria40->multidir_output = array();
}

// Salva entity in una variabile prima
$entity = (isset($conf->entity)) ? $conf->entity : 1;  // Default to 1 if not set
$conf->industria40->multidir_output[$entity] = $conf->industria40->dir_output;
dol_syslog("init.inc.php: Set conf->industria40->multidir_output for entity " . $entity, LOG_INFO);

// Rimosso il blocco di codice commentato che causa problemi

// Create required directories if they don't exist
$dirs = array(
    $conf->industria40->dir_output,
    $conf->industria40->dir_output . '/documents',
    $conf->industria40->dir_output . '/thumbnails',
);

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (dol_mkdir($dir) >= 0) {
            chmod($dir, 0775);
            dol_syslog("init.inc.php: Created directory: " . $dir, LOG_INFO);
        } else {
            dol_syslog("init.inc.php: Failed to create directory: " . $dir, LOG_ERR);
        }
    } else if (!is_writable($dir)) {
        chmod($dir, 0775);
        dol_syslog("init.inc.php: Set permissions for existing directory: " . $dir, LOG_INFO);
    }
}

// Register in the file access hook system (if needed)
if (function_exists('dol_include_once')) {
    // Try to register the module in the file access system
    dol_include_once('/core/class/hookmanager.class.php');
    if (class_exists('HookManager')) {
        global $hookmanager;
        if (!isset($hookmanager)) {
            $hookmanager = new HookManager($db);
        }
        $hookmanager->initHooks(['filedoc']);
        dol_syslog("init.inc.php: Registered industria40 with hookmanager", LOG_INFO);
    }
}

// Add document handler for Industria40 module
global $filetype;
if (!isset($filetype)) {
    $filetype = array();
}
if (!isset($filetype['industria40'])) {
    $filetype['industria40'] = array(
        'name' => 'Industria40',
        'dir' => 'industria40', // Rimosso /documents
        'icon' => 'generic',
    );
    dol_syslog("init.inc.php: Registered industria40 in filetype array", LOG_INFO);
}

// Debug
dol_syslog("init.inc.php: Configuration complete for Industria40 module", LOG_DEBUG);
