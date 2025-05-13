<?php
/**
 * Endpoint per la generazione asincrona delle miniature PDF
 */

// Inizializzazione Dolibarr
if (!defined('NOREQUIREUSER')) define('NOREQUIREUSER', '1');
if (!defined('NOREQUIREDB')) define('NOREQUIREDB', '0');
if (!defined('NOREQUIRESOC')) define('NOREQUIRESOC', '1');
if (!defined('NOREQUIRETRAN')) define('NOREQUIRETRAN', '0');
if (!defined('NOLOGIN')) define('NOLOGIN', '0'); // Login required
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', '0');
if (!defined('NOIPCHECK')) define('NOIPCHECK', '0');

// Load Dolibarr environment
$res = 0;
$res = @include_once __DIR__ . '/../main.inc.php';
if (!$res) {
    $res = @include_once __DIR__ . '/../../main.inc.php';
}
if (!$res) die('Error: Failed to include Dolibarr main.inc.php file');

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once __DIR__ . '/../lib/pdf_thumbnail_generator.php';

// Verifica autorizzazioni
if (!$user->rights->industria40->read && !$user->admin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Parametri
$socid = GETPOST('socid', 'int');
$periziaid = GETPOST('periziaid', 'alpha');
$filename = GETPOST('filename', 'alpha');

if (empty($socid) || empty($periziaid) || empty($filename)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parametri mancanti']);
    exit;
}

// Sanitizza i parametri
$periziaid_sanitized = dol_sanitizeFileName($periziaid);

// Costruisci i percorsi
$upload_dir = DOL_DATA_ROOT . '/industria40/documents/' . $socid . '/' . $periziaid_sanitized;
$pdf_path = $upload_dir . '/' . $filename;
$thumbnail_dir = DOL_DATA_ROOT . '/industria40/thumbnails/' . $socid . '/' . $periziaid_sanitized;
$thumbnail_path = $thumbnail_dir . '/thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';

// Controlla se il PDF esiste
if (!file_exists($pdf_path)) {
    echo json_encode(['success' => false, 'message' => 'PDF non trovato']);
    exit;
}

// Assicurati che la directory esista
if (!is_dir($thumbnail_dir)) {
    if (!dol_mkdir($thumbnail_dir)) {
        echo json_encode(['success' => false, 'message' => 'Impossibile creare la directory per le miniature']);
        exit;
    }
}

// Se la miniatura esiste già, restituisce l'URL
if (file_exists($thumbnail_path)) {
    $thumbnail_rel_path = 'thumbnails/' . $socid . '/' . $periziaid_sanitized . '/thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
    $thumbnail_url = DOL_URL_ROOT . '/document.php?modulepart=industria40&file=' . urlencode($thumbnail_rel_path) . '&entity=' . $conf->entity;
    echo json_encode([
        'success' => true,
        'message' => 'La miniatura esiste già',
        'thumbnail_url' => $thumbnail_url
    ]);
    exit;
}

// Genera la miniatura
$result = generate_pdf_thumbnail($pdf_path, $thumbnail_path);

if ($result) {
    // Costruisci l'URL della miniatura
    $thumbnail_rel_path = 'thumbnails/' . $socid . '/' . $periziaid_sanitized . '/thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
    $thumbnail_url = DOL_URL_ROOT . '/document.php?modulepart=industria40&file=' . urlencode($thumbnail_rel_path) . '&entity=' . $conf->entity;

    echo json_encode([
        'success' => true,
        'message' => 'Miniatura generata con successo',
        'thumbnail_url' => $thumbnail_url
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Impossibile generare la miniatura']);
}
?>
