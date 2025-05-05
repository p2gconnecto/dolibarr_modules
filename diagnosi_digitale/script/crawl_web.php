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

// Verifica che l'URL sia stato fornito
if (empty($_GET['url'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No URL provided']);
    exit;
}

$url = filter_var($_GET['url'], FILTER_SANITIZE_URL);

// Verifica che l'URL sia valido
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid URL']);
    exit;
}

// Esegui il crawling del sito web
$content = @file_get_contents($url);
if ($content === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch content from the URL']);
    exit;
}

// Restituisci il contenuto come JSON
echo json_encode(['success' => true, 'content' => $content]);