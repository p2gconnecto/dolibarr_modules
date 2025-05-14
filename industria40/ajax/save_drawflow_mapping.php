<?php
// Inizializza l'ambiente Dolibarr
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/industria40/core/init.inc.php';

// Verifica dell'accesso
if (!$user->rights->industria40->read) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Accesso negato']));
}

// Recupera i dati inviati
$postdata = json_decode(file_get_contents('php://input'), true);
if (empty($postdata) || empty($postdata['socid']) || empty($postdata['periziaid']) || empty($postdata['data'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Dati mancanti']));
}

$socid = intval($postdata['socid']);
$periziaid = dol_sanitizeFileName($postdata['periziaid']);
$data = $postdata['data'];

// Directory per salvare i mapping
$mapping_dir = DOL_DATA_ROOT . '/industria40/drawflow_mappings';
if (!is_dir($mapping_dir)) {
    mkdir($mapping_dir, 0755, true);
}

// Salva il mapping in un file JSON
$mapping_file = $mapping_dir . '/' . $socid . '_' . $periziaid . '_mapping.json';
if (file_put_contents($mapping_file, json_encode($data))) {
    dol_syslog("Drawflow mapping saved for socid=$socid, perizia=$periziaid", LOG_DEBUG);
    exit(json_encode(['success' => true]));
} else {
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => 'Errore durante il salvataggio']));
}
?>