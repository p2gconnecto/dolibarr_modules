<?php
// Imposta l'header Content-Type su application/json
header('Content-Type: application/manifest+json');

// --- Inizio costruzione URL Assoluti ---
$scheme = 'http'; // Schema predefinito
// Verifica HTTPS standard
if (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == 1)) {
    $scheme = 'https';
}
// Verifica header X-Forwarded-Proto per proxy/load balancer
elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') {
    $scheme = 'https';
}
// Verifica Server Port
elseif (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
    $scheme = 'https';
}

$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'; // Host predefinito
$base_url = $scheme . '://' . $host; // Es. https://dolibarr.ai8472.com
// --- Fine costruzione URL Assoluti ---

$module_path_segment = '/custom/industria40/'; // Segmento del percorso per il modulo
$start_url_path_segment = $module_path_segment . 'industria40index.php'; // Pagina di avvio del modulo

$absolute_start_url = $base_url . $start_url_path_segment;
// L'ID dovrebbe essere un identificatore univoco per l'applicazione su questa origine.
// Spesso è uguale a start_url o a un percorso più specifico.
$absolute_id = $base_url . $module_path_segment . 'manifest_id'; // O usa $absolute_start_url se preferisci
$absolute_scope = $base_url . $module_path_segment; // Scope assoluto

// Costruisci l'array del manifest
$manifest = [
    'name' => 'Industria 4.0 Manager',
    'short_name' => 'Industria40',
    'description' => 'Gestione Documenti e Perizie per Industria 4.0',
    'start_url' => $absolute_start_url . '?utm_source=pwa', // URL assoluto
    'id' => $absolute_id, // URL assoluto o ID univoco per l'origine
    'scope' => $absolute_scope, // URL assoluto
    'display' => 'standalone', // o 'fullscreen', 'minimal-ui'
    'background_color' => '#ffffff',
    'theme_color' => '#2c5987', // Colore principale del tema Dolibarr o del modulo
    'orientation' => 'any',
    'icons' => [
        [
            // I percorsi delle icone possono essere root-relative (iniziano con /)
            // o relativi al percorso del manifest.
            // Usare percorsi root-relative è spesso più robusto.
            'src' => $module_path_segment . 'img/icons/icon-72x72.png',
            'sizes' => '72x72',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $module_path_segment . 'img/icons/icon-96x96.png',
            'sizes' => '96x96',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $module_path_segment . 'img/icons/icon-128x128.png',
            'sizes' => '128x128',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $module_path_segment . 'img/icons/icon-144x144.png',
            'sizes' => '144x144',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $module_path_segment . 'img/icons/icon-152x152.png',
            'sizes' => '152x152',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $module_path_segment . 'img/icons/icon-192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $module_path_segment . 'img/icons/icon-384x384.png',
            'sizes' => '384x384',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $module_path_segment . 'img/icons/icon-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ]
    ],
    // Opzionale: definisci scorciatoie per azioni comuni
    // "shortcuts" => [
    //     [
    //         "name" => "Nuova Perizia",
    //         "short_name" => "Nuova",
    //         "description" => "Crea una nuova perizia",
    //         "url" => $module_path . "new_perizia.php?utm_source=pwa_shortcut",
    //         "icons" => [["src" => $module_path . "img/icons/shortcut-new-96x96.png", "sizes" => "96x96"]]
    //     ]
    // ],
    // Opzionale: definisci gestori di protocollo
    // "protocol_handlers" => [
    //     [
    //         "protocol" => "web+industria40",
    //         "url" => $module_path . "handle_protocol.php?data=%s"
    //     ]
    // ]
];

// Stampa il manifest come JSON
echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

?>
