<?php
require '../../main.inc.php';

// Un file di test minimo per verificare l'accesso
llxHeader('', 'Test Page');
print '<h1>Test Page</h1>';
print '<p>If you can see this, basic access to the module is working.</p>';
print '<p>User ID: '.$user->id.'</p>';
print '<p>User Admin: '.($user->admin ? 'Yes' : 'No').'</p>';
print '<p>PHP Version: '.PHP_VERSION.'</p>';
print '<p>Server: '.(isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown').'</p>';

// Aggiungiamo un test per AIDescription
$socid = GETPOST('socid', 'int');
$periziaid = GETPOST('periziaid', 'alpha');

if ($socid && $periziaid) {
    $periziaid_sanitized = dol_sanitizeFileName($periziaid);

    require_once DOL_DOCUMENT_ROOT.'/custom/industria40/class/industria40.class.php';

    print '<h2>Industria 4.0 AIDescription Test</h2>';
    print '<p>Testing AIDescription for company ID: '.$socid.' and perizia: '.$periziaid.'</p>';

    // Verifichiamo se esistono descrizioni AI
    $upload_dir = DOL_DATA_ROOT . '/industria40/documents/' . $socid . '/' . $periziaid_sanitized;
    $files = dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'name', SORT_ASC, 1);

    if (count($files)) {
        print '<h3>File trovati: '.count($files).'</h3>';
        print '<table class="liste">';
        print '<tr class="liste_titre">';
        print '<th>File</th>';
        print '<th>AIDescription</th>';
        print '</tr>';

        foreach ($files as $file) {
            $file_key = $socid . '_' . $periziaid_sanitized . '_' . $file['name'];
            $ai_description_summary = get_stored_ai_response($file_key . '_summary');

            print '<tr class="oddeven">';
            print '<td>'.$file['name'].'</td>';
            print '<td>';
            if ($ai_description_summary !== false && !is_array($ai_description_summary)) {
                print '<div class="ai-summary-compact">';
                print nl2br(dol_escape_htmltag($ai_description_summary));
                print '</div>';
            } else {
                print '<span class="opacitymedium">Nessuna descrizione AI disponibile</span>';
            }
            print '</td>';
            print '</tr>';
        }

        print '</table>';
    } else {
        print '<p class="warning">Nessun file trovato nella directory: '.$upload_dir.'</p>';
    }
}

print '<h2>Module Permissions</h2>';
print '<pre>';
print_r($user->rights);
print '</pre>';

// Aggiungiamo funzione per recuperare le descrizioni AI - Solo per il test
if (!function_exists('get_stored_ai_response')) {
    function get_stored_ai_response($file_key) {
        $responses_dir = DOL_DATA_ROOT . '/industria40/ai_responses';
        if (!is_dir($responses_dir)) return false;

        $response_file = $responses_dir . '/' . dol_sanitizeFileName($file_key) . '.json';
        if (file_exists($response_file)) {
            $content = @file_get_contents($response_file);
            if ($content !== false) {
                return $content;
            }
        }
        return false;
    }
}

llxFooter();
