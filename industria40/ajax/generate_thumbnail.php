<?php
/**
 * Endpoint API per generare miniature PDF in background
 */

// Carica l'ambiente Dolibarr
if (!defined('NOREQUIREUSER')) define('NOREQUIREUSER', '1');
if (!defined('NOREQUIREDB')) define('NOREQUIREDB', '0');
if (!defined('NOREQUIRESOC')) define('NOREQUIRESOC', '1');
if (!defined('NOREQUIRETRAN')) define('NOREQUIRETRAN', '0');
if (!defined('NOLOGIN')) define('NOLOGIN', '0');
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', '0');
if (!defined('NOIPCHECK')) define('NOIPCHECK', '0');

// Carica main.inc.php
$res = 0;
$res = @include_once __DIR__ . '/../main.inc.php';
if (!$res) {
    $res = @include_once __DIR__ . '/../../main.inc.php';
}
if (!$res) die('Error: Failed to include Dolibarr main.inc.php file');

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once __DIR__ . '/../lib/pdf_thumbnail_generator.php';

// Controlla se l'utente è autorizzato
if (!$user->rights->industria40->read && !$user->admin) {
    http_response_code(403);
    echo json_encode(['error' => 'Autorizzazione negata']);
    exit;
}

// Ottieni i parametri dalla richiesta
$socid = GETPOST('socid', 'int');
$periziaid = GETPOST('periziaid', 'alpha');
$filename = GETPOST('filename', 'alpha');

if (empty($socid) || empty($periziaid) || empty($filename)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parametri mancanti']);
    exit;
}

// Costruisci i percorsi
$upload_dir = DOL_DATA_ROOT . '/industria40/' . $socid . '/' . $periziaid;
$file_path = $upload_dir . '/' . $filename;
$thumbnail_dir = DOL_DATA_ROOT . '/industria40/thumbnails/' . $socid . '/' . $periziaid;
$thumbnail_path = $thumbnail_dir . '/thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';

// Verifica che il file esista
if (!file_exists($file_path)) {
    http_response_code(404);
    echo json_encode(['error' => 'File non trovato']);
    exit;
}

// Crea la directory delle miniature se non esiste
if (!is_dir($thumbnail_dir)) {
    if (!dol_mkdir($thumbnail_dir)) {
        http_response_code(500);
        echo json_encode(['error' => 'Impossibile creare la directory delle miniature']);
        exit;
    }
}

// Genera la miniatura
$result = generate_pdf_thumbnail($file_path, $thumbnail_path);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Miniatura generata con successo']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Errore durante la generazione della miniatura']);
}
?>
