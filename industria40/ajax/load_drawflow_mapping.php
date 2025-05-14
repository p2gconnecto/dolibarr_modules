<?php
// Inizializza l'ambiente Dolibarr
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/industria40/core/init.inc.php';

// Verifica dell'accesso
if (!$user->rights->industria40->read) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Accesso negato']));
}

// Recupera i parametri
$socid = GETPOST('socid', 'int');
$periziaid = GETPOST('periziaid', 'alpha');

if (empty($socid) || empty($periziaid)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Parametri mancanti']));
}

// Directory per i mapping
$mapping_dir = DOL_DATA_ROOT . '/industria40/drawflow_mappings';
$mapping_file = $mapping_dir . '/' . $socid . '_' . $periziaid . '_mapping.json';

if (file_exists($mapping_file)) {
    $mapping_data = file_get_contents($mapping_file);
    exit(json_encode(['success' => true, 'mapping' => json_decode($mapping_data, true)]));
} else {
    exit(json_encode(['success' => false, 'error' => 'Nessun mapping trovato']));
}
?>