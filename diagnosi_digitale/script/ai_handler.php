<?php

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/diagnosi_digitale/class/ai.class.php';

header('Content-Type: application/json');

// Verifica i permessi dell'utente
if (!$user->rights->diagnosi_digitale->read) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

// Recupera i dati JSON inviati
$postData = json_decode(file_get_contents('php://input'), true);

// Verifica che il prompt sia presente
if (empty($postData['prompt'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No prompt provided']);
    exit;
}

$prompt = htmlspecialchars($postData['prompt'], ENT_QUOTES, 'UTF-8');

// Inizializza la classe AI
try {
    $ai = new DiagnosiDigitaleAI();
    $result = $ai->generateContent($prompt);

    // Restituisci la risposta
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error: ' . $e->getMessage()]);
}