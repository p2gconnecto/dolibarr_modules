<?php
// This view is included by industria40index.php
// It expects $socid, $periziaid, $periziaid_sanitized, $upload_dir, $form_action_url, $langs, $conf, $user, $db, $modulepart, $available_tags
// and $action (from GETPOST in index) to be available.

// Action Handlers for this view
if ($action == 'rename_files') {
    if (!$user->rights->industria40->write && !$user->admin) {
        setEventMessages($langs->trans("NoPermissionToWrite"), null, 'errors');
    } else {
        dol_syslog("file_manager_manage_view.php: Handling action 'rename_files'", LOG_DEBUG);
        $single_file_rename = GETPOST('rename_single_file', 'alpha');

        if (!empty($single_file_rename)) {
            $original_name = $single_file_rename;
            $key_post_new_name = 'new_name_' . $original_name; // Key used in POST from the text input
            $new_name_val = GETPOST($key_post_new_name, 'alpha');

            dol_syslog("file_manager_manage_view.php: Attempting to rename '$original_name' to '$new_name_val'", LOG_DEBUG);
            $sanitized_new_name = dol_sanitizeFileName($new_name_val);
            // Ensure $upload_dir has a trailing slash
            $upload_dir_path_rs = rtrim($upload_dir, '/') . '/';
            $original_path_rs = $upload_dir_path_rs . $original_name;
            $new_path_rs = $upload_dir_path_rs . $sanitized_new_name;

            if ($original_path_rs != $new_path_rs && !empty($sanitized_new_name)) {
                if (file_exists($original_path_rs)) {
                    if (rename($original_path_rs, $new_path_rs)) {
                        update_file_references($socid, $periziaid_sanitized, $original_name, $sanitized_new_name);
                        setEventMessages($langs->trans("FileRenamedFromTo", $original_name, $sanitized_new_name), null, 'mesgs');
                    } else {
                        setEventMessages($langs->trans("ErrorRenamingFile", $original_name), null, 'errors');
                    }
                } else {
                    setEventMessages($langs->trans("ErrorFileNotFound", $original_name), null, 'errors');
                }
            } elseif ($original_path_rs == $new_path_rs) {
                setEventMessages($langs->trans("NoChangeToApply"), null, 'warnings');
            } else {
                setEventMessages($langs->trans("ErrorNewNameEmpty"), null, 'warnings');
            }
        } else { // Batch rename (if still supported through a different mechanism, not the single input)
            // This part might be deprecated if only single rename via text input is used.
            // For now, assume it's for a potential future "save all changes" button.
            $changes_made = false;
            foreach ($_POST as $key => $new_name_val) {
                if (strpos($key, 'new_name_') === 0) {
                    $original_name = substr($key, 9); // Length of 'new_name_'
                    $original_name = rawurldecode($original_name); // Names in POST keys might be URL encoded by forms

                    $sanitized_new_name = dol_sanitizeFileName($new_name_val);
                    $upload_dir_path_rb = rtrim($upload_dir, '/') . '/';
                    $original_path_rb = $upload_dir_path_rb . $original_name;
                    $new_path_rb = $upload_dir_path_rb . $sanitized_new_name;

                    if ($original_path_rb != $new_path_rb && !empty($sanitized_new_name)) {
                        if (file_exists($original_path_rb)) {
                            if (rename($original_path_rb, $new_path_rb)) {
                                update_file_references($socid, $periziaid_sanitized, $original_name, $sanitized_new_name);
                                setEventMessages($langs->trans("FileRenamedFromTo", $original_name, $sanitized_new_name), null, 'mesgs');
                                $changes_made = true;
                            } else {
                                setEventMessages($langs->trans("ErrorRenamingFile", $original_name), null, 'errors');
                            }
                        }
                    }
                }
            }
            if (!$changes_made && empty($single_file_rename)) { // Avoid double message if single rename was attempted
                 setEventMessages($langs->trans("NoChangesToApply"), null, 'warnings');
            }
        }
        // Redirect to refresh the view after rename
        header('Location: ' . $form_action_url . '&view_mode=manage&message=renamedone');
        exit;
    }
} elseif ($action == 'remove_file') {
    $file_to_remove = GETPOST('file_to_remove', 'alpha');
    if (!$user->rights->industria40->delete && !$user->admin) { // Adjusted permission
        setEventMessages($langs->trans("NoPermissionToDelete"), null, 'errors');
    } elseif (!empty($file_to_remove)) {
        $sanitized_file_to_remove = dol_sanitizeFileName(basename($file_to_remove));
        $upload_dir_path_rf = rtrim($upload_dir, '/') . '/';
        $file_path_rf = $upload_dir_path_rf . $sanitized_file_to_remove;

        // Security check (already present in original code)
        $real_upload_dir_rf = realpath($upload_dir_base . '/' . $socid . '/' . $periziaid_sanitized);
        $real_file_path_rf = realpath($file_path_rf);

        if (!$real_upload_dir_rf || !$real_file_path_rf || strpos($real_file_path_rf, $real_upload_dir_rf) !== 0) {
            setEventMessages($langs->trans("PathInjectionAttempt"), null, 'errors');
        } elseif (file_exists($file_path_rf) && is_file($file_path_rf)) {
            if (unlink($file_path_rf)) {
                remove_associated_file_data($socid, $periziaid_sanitized, $sanitized_file_to_remove, $upload_dir_path_rf);
                setEventMessages($langs->trans("FileRemoved", $sanitized_file_to_remove), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("ErrorRemovingFile", $sanitized_file_to_remove), null, 'errors');
            }
        } else {
            setEventMessages($langs->trans("ErrorFileNotFound", $sanitized_file_to_remove), null, 'warnings');
        }
        header('Location: ' . $form_action_url . '&view_mode=manage&message=fileremoved');
        exit;
    }
} elseif ($action == 'remove_all_files') {
     if (!$user->rights->industria40->delete && !$user->admin) {
        setEventMessages($langs->trans("NoPermissionToDelete"), null, 'errors');
    } else {
        dol_syslog("file_manager_manage_view.php: Handling action 'remove_all_files'", LOG_DEBUG);
        $files_removed_count = 0; $files_failed_count = 0;
        $upload_dir_path_ra = rtrim($upload_dir, '/') . '/';
        if (is_dir($upload_dir_path_ra)) {
            $files_ra = scandir($upload_dir_path_ra);
            foreach ($files_ra as $file_ra) {
                if ($file_ra != '.' && $file_ra != '..') {
                    $file_path_ra = $upload_dir_path_ra . $file_ra;
                    if (is_file($file_path_ra)) {
                        if (unlink($file_path_ra)) {
                            remove_associated_file_data($socid, $periziaid_sanitized, $file_ra, $upload_dir_path_ra);
                            $files_removed_count++;
                        } else {
                            $files_failed_count++;
                        }
                    }
                }
            }
            if ($files_failed_count > 0) setEventMessages($langs->trans("ErrorRemovingMultipleFiles", $files_failed_count), null, 'errors');
            if ($files_removed_count > 0) setEventMessages($langs->trans("FilesRemovedCount", $files_removed_count), null, 'mesgs');
            else if ($files_failed_count == 0) setEventMessages($langs->trans("NoFilesToRemove"), null, 'warnings');
        } else {
            setEventMessages($langs->trans("UploadDirectoryNotFound"), null, 'warnings');
        }
        header('Location: ' . $form_action_url . '&view_mode=manage&message=allfilesremoved');
        exit;
    }
} elseif ($action == 'set_tag' && !empty($_POST['file_name_for_tag']) && !empty($_POST['file_tag'])) {
    if (!$user->rights->industria40->write && !$user->admin) {
         setEventMessages($langs->trans("NoPermissionToWrite"), null, 'errors');
    } else {
        $file_name_tag = GETPOST('file_name_for_tag', 'alpha');
        $file_tag_val = GETPOST('file_tag', 'alpha');
        $file_key_tag = $socid . '_' . $periziaid_sanitized . '_' . $file_name_tag;

        $tags_dir_st = DOL_DATA_ROOT . '/industria40/tags';
        if (!is_dir($tags_dir_st)) dol_mkdir($tags_dir_st);
        $tags_file_st = $tags_dir_st . '/file_tags.json';
        $tags_data_st = [];
        if (file_exists($tags_file_st)) {
            $content = @file_get_contents($tags_file_st);
            if ($content) $tags_data_st = json_decode($content, true);
            if (!is_array($tags_data_st)) $tags_data_st = [];
        }
        $tags_data_st[$file_key_tag] = $file_tag_val;
        if (@file_put_contents($tags_file_st, json_encode($tags_data_st))) {
            setEventMessages($langs->trans("TagSetFor", $file_name_tag), null, 'mesgs');
        } else {
            setEventMessages($langs->trans("ErrorSettingTag"), null, 'errors');
        }
        header('Location: ' . $form_action_url . '&view_mode=manage&message=tagset');
        exit;
    }
}

// Require the PDF thumbnail generator
require_once __DIR__ . '/lib/pdf_thumbnail_generator.php';

// Display uploaded files
print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/custom/industria40/css/file_manager.css">';
print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/custom/industria40/css/ai_indicators.css">'; // Nuovo file CSS per indicatori AI
print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/custom/industria40/js/file_manager.js"></script>';
print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/custom/industria40/js/ai_indicators.js"></script>'; // Nuovo file JS per indicatori AI

print '<div id="filePreview" style="margin-top: 20px;">';

$tags_dir_m = DOL_DATA_ROOT . '/industria40/tags';
$tags_file_m = $tags_dir_m . '/file_tags.json';
$file_tags_m = [];
if (file_exists($tags_file_m)) {
    $content = @file_get_contents($tags_file_m);
    if ($content) $file_tags_m = json_decode($content, true);
    if (!is_array($file_tags_m)) $file_tags_m = [];
}

$desc_dir_m = DOL_DATA_ROOT . '/industria40/descriptions';
$desc_file_m = $desc_dir_m . '/file_descriptions.json';
$file_descriptions_m = [];
if (file_exists($desc_file_m)) {
    $content = @file_get_contents($desc_file_m);
    if ($content) $file_descriptions_m = json_decode($content, true);
    if (!is_array($file_descriptions_m)) $file_descriptions_m = [];
}

// Form for renaming files (individual confirmation is handled by JS calling this form's submit)
// The action URL for this form should include view_mode=manage
print '<form id="renameForm" action="' . $form_action_url . '&view_mode=manage" method="POST">';
print '<input type="hidden" name="action" value="rename_files">';
// socid and periziaid are in $form_action_url

print '<table class="border" id="fileTable">';
print '<thead><tr>';
print '<th>' . $langs->trans('FileName') . ' / ' . $langs->trans('Preview') . '</th>';
print '<th>' . $langs->trans('Description') . ' / ' . $langs->trans('Tag') . '</th>';
print '<th>' . $langs->trans('NewName') . '</th>';
print '<th class="right">' . $langs->trans('Actions') . '</th>';
print '</tr></thead>';
print '<tbody>';

$files_exist_m = false;
$upload_dir_path_m = rtrim($upload_dir, '/') . '/'; // Ensure trailing slash

if (is_dir($upload_dir_path_m)) {
    $files_m = scandir($upload_dir_path_m);
    foreach ($files_m as $file_m) {
        if ($file_m != '.' && $file_m != '..' && !preg_match('/\.(txt)$/i', $file_m)) {
            $files_exist_m = true;
            $file_path_relative_m = 'documents/' . $socid . '/' . $periziaid_sanitized . '/' . $file_m;
            $file_full_path_m = $upload_dir_path_m . $file_m;
            $file_url_m = DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&file=' . urlencode($file_path_relative_m) . '&entity=' . $conf->entity;
            $file_extension_m = strtolower(pathinfo($file_m, PATHINFO_EXTENSION));
            $file_key_m = $socid . '_' . $periziaid_sanitized . '_' . $file_m;

            // Verifica se esiste una descrizione AI per questo file
            $ai_description_summary_m = get_stored_ai_response($file_key_m . '_summary');
            $has_ai_desc = ($ai_description_summary_m !== false && !is_array($ai_description_summary_m));

            print '<tr>';
            // Preview Column
            print '<td>';
            print '<div class="file-preview-container" data-filename="'.dol_escape_htmltag($file_m).'" data-socid="'.$socid.'" data-perizia="'.$periziaid_sanitized.'" data-has-ai-desc="'.($has_ai_desc ? '1' : '0').'">';
            $ocr_text_m = load_ocr_text($file_full_path_m); // Load OCR text for status icon

            // Thumbnail/Icon
            $thumbnail_rel_path_m = 'thumbnails/' . $socid . '/' . $periziaid_sanitized . '/thumb_' . pathinfo($file_m, PATHINFO_FILENAME) . '.jpg';
            $thumbnail_full_path_m = DOL_DATA_ROOT . '/industria40/' . $thumbnail_rel_path_m;
            if ($file_extension_m == 'pdf' && file_exists($thumbnail_full_path_m)) {
                $thumb_url_m = DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&file=' . urlencode($thumbnail_rel_path_m) . '&entity=' . $conf->entity;
                print '<a href="' . $file_url_m . '" target="_blank" class="zoom-container"><img src="' . $thumb_url_m . '" class="preview-image" alt="PDF Thumbnail">';
            } elseif (in_array($file_extension_m, array('jpg', 'jpeg', 'png', 'gif'))) {
                print '<a href="' . $file_url_m . '" target="_blank" class="zoom-container"><img src="' . $file_url_m . '" class="preview-image" alt="Image Preview">';
            } else {
                print '<a href="' . $file_url_m . '" target="_blank" class="zoom-container"><div class="icon-container"><i class="fa fa-file fa-2x"></i></div>';
            }
            if (!empty($ocr_text_m)) {
                print '<div class="file-status-tooltip"><div class="file-processed" title="' . $langs->trans("FileAlreadyAnalyzed") . '">✓</div><span class="tooltiptext">' . $langs->trans("TextAlreadyExtracted") . '</span></div>';
            }
            print '</a>'; // Close zoom-container

            // Aggiungi indicatore di descrizione AI se disponibile
            if ($has_ai_desc) {
                print '<div class="ai-description-indicator">';
                print '<i class="fa fa-robot" title="'.$langs->trans("AIDescriptionAvailable").'"></i>';

                // Tooltip che mostra un'anteprima della descrizione
                print '<div class="ai-description-tooltip">';
                print '<div class="ai-tooltip-content">';

                // Mostra le prime righe del riepilogo
                $summary_lines = explode("\n", $ai_description_summary_m);
                $preview_lines = array_slice($summary_lines, 0, 3);
                foreach ($preview_lines as $line) {
                    if (!empty(trim($line))) {
                        print dol_escape_htmltag($line) . '<br>';
                    }
                }
                if (count($summary_lines) > 3) {
                    print '...';
                }

                print '</div>';
                print '<a href="' . $form_action_url . '&view_mode=ai&file_name=' . urlencode($file_m) . '" class="button buttonsmall">' . $langs->trans("ViewAIDetails") . '</a>';
                print '</div>';
                print '</div>';
            }

            print '<div class="filename-container">' . dol_escape_htmltag($file_m) . '</div>';
            print '</div>'; // Close file-preview-container
            print '</td>';

            // Description/Tag Column
            print '<td class="description-column">';
            if (!empty($file_descriptions_m[$file_key_m])) {
                print '<div class="description-text">' . dol_escape_htmltag($file_descriptions_m[$file_key_m]) . '</div>';
            } elseif ($has_ai_desc) {
                // Se non c'è una descrizione manuale ma esiste una descrizione AI, mostriamo un estratto
                print '<div class="ai-summary-compact">';

                // Formatta il contenuto del sommario AI in un modo più leggibile
                $summary_lines = explode("\n", $ai_description_summary_m);
                foreach ($summary_lines as $line) {
                    if (!empty(trim($line))) {
                        // Separa la chiave dal valore (formato "Chiave: Valore")
                        $parts = explode(':', $line, 2);
                        if (count($parts) == 2) {
                            $key = trim($parts[0]);
                            $value = trim($parts[1]);
                            print '<div class="ai-summary-row">';
                            print '<span class="ai-summary-key">' . dol_escape_htmltag($key) . ':</span> ';
                            print '<span class="ai-summary-value">' . dol_escape_htmltag($value) . '</span>';
                            print '</div>';
                        } else {
                            // Nel caso in cui il formato non sia "Chiave: Valore"
                            print '<div>' . dol_escape_htmltag($line) . '</div>';
                        }
                    }
                }

                print '</div>';
            } else {
                print '<span class="opacitymedium">' . $langs->trans("NoDescription") . '</span>';
            }

            // Tag selection
            print '<form id="tagForm_'.$file_key_m.'" action="' . $form_action_url . '&view_mode=manage" method="POST" style="margin-top: 5px;">';
            print '<input type="hidden" name="action" value="set_tag">';
            print '<input type="hidden" name="file_name_for_tag" value="' . dol_escape_htmltag($file_m) . '">';
            print '<select name="file_tag" class="flat" onchange="document.getElementById(\'tagForm_'.$file_key_m.'\').submit();">';
            print '<option value="">'.$langs->trans("SelectTag").'</option>';
            foreach ($available_tags as $tag_key => $tag_label) {
                $selected_tag = (isset($file_tags_m[$file_key_m]) && $file_tags_m[$file_key_m] == $tag_key) ? ' selected' : '';
                print '<option value="' . $tag_key . '"' . $selected_tag . '>' . $langs->trans($tag_label) . '</option>';
            }
            print '</select>';
            // print '<button type="submit" class="buttonextrasmall">'.$langs->trans("Set").'</button>'; // Auto-submit on change
            print '</form>';

            // AI Interaction Link
            print '<div style="margin-top:5px;"><a href="'.$form_action_url.'&view_mode=ai&file_name='.urlencode($file_m).'" class="button button-ai">'.$langs->trans("AIInteraction").'</a></div>';
            print '</td>';

            // New Name Column
            print '<td class="rename-container">';
            // The input name must be unique for each file, e.g., new_name_FILENAME
            // The value of rename_single_file in JS will be the original filename.
            print '<input type="text" name="new_name_' . dol_escape_htmltag(rawurlencode($file_m)) . '" value="' . dol_escape_htmltag($file_m) . '" class="flat" size="30">';
            print '<div class="confirm-rename" title="' . $langs->trans("ConfirmRename") . '" onclick="confirmRename(\'' . dol_escape_js(rawurlencode($file_m)) . '\')"><i class="fa fa-check"></i></div>';
            print '</td>';

            // Actions Column
            print '<td class="right">';
            print '<a class="delete-icon" href="' . $form_action_url . '&view_mode=manage&action=remove_file&file_to_remove=' . urlencode($file_m) . '" onclick="return confirm(\'' . $langs->trans("ConfirmRemoveFile", dol_escape_js($file_m)) . '\');">';
            print '<i class="fa fa-trash" title="' . $langs->trans("Remove") . '"></i>';
            print '</a>';
            print '</td>';
            print '</tr>';
        }
    }
} else {
    if (is_dir($upload_dir_path_m)) {
        print '<tr><td colspan="4">' . $langs->trans("NoFilesUploadedForPerizia") . '</td></tr>';
    } else {
        print '<tr><td colspan="4">' . $langs->trans("UploadDirectoryWillBeCreated") . '</td></tr>';
    }
}
print '</tbody>';
print '</table>'; // End renameForm

if ($files_exist_m) {
    print '<form id="deleteAllForm" action="' . $form_action_url . '&view_mode=manage" method="POST" style="margin-top:10px;">';
    print '<input type="hidden" name="action" value="remove_all_files">';
    print '<button type="submit" class="button-delete-all" onclick="return confirm(\''.$langs->trans("ConfirmRemoveAllFiles").'\');"><i class="fa fa-trash"></i> ' . $langs->trans("RemoveAllFiles") . '</button>';
    print '</form>';
}

print '</div>'; // End filePreview div

// Rimozione degli stili CSS inline (spostati nel file ai_indicators.css)

// Rimozione del JavaScript inline (spostato nel file ai_indicators.js)

/**
 * Update references to a file when it's renamed
 *
 * @param int $socid The society ID
 * @param string $perizia_sanitized The sanitized perizia ID
 * @param string $old_name The original file name
 * @param string $new_name The new file name
 * @return void
 */
function update_file_references($socid, $perizia_sanitized, $old_name, $new_name) {
    dol_syslog("update_file_references: Updating references from '$old_name' to '$new_name'", LOG_DEBUG);

    // Update tags
    $old_file_key = $socid . '_' . $periziaid_sanitized . '_' . $old_name;
    $new_file_key = $socid . '_' . $periziaid_sanitized . '_' . $new_name;

    $tags_dir = DOL_DATA_ROOT . '/industria40/tags';
    $tags_file = $tags_dir . '/file_tags.json';
    if (file_exists($tags_file)) {
        $tags_data = json_decode(file_get_contents($tags_file), true);
        if (is_array($tags_data) && isset($tags_data[$old_file_key])) {
            $tags_data[$new_file_key] = $tags_data[$old_file_key];
            unset($tags_data[$old_file_key]);
            file_put_contents($tags_file, json_encode($tags_data));
            dol_syslog("update_file_references: Updated tag reference", LOG_DEBUG);
        }
    }

    // Update descriptions
    $desc_dir = DOL_DATA_ROOT . '/industria40/descriptions';
    $desc_file = $desc_dir . '/file_descriptions.json';
    if (file_exists($desc_file)) {
        $desc_data = json_decode(file_get_contents($desc_file), true);
        if (is_array($desc_data) && isset($desc_data[$old_file_key])) {
            $desc_data[$new_file_key] = $desc_data[$old_file_key];
            unset($desc_data[$old_file_key]);
            file_put_contents($desc_file, json_encode($desc_data));
            dol_syslog("update_file_references: Updated description reference", LOG_DEBUG);
        }
    }

    // Update AI responses (move files)
    $ai_response_dir = DOL_DATA_ROOT . '/industria40/ai_responses';
    $old_response_file = $ai_response_dir . '/' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $old_file_key) . '.txt';
    $new_response_file = $ai_response_dir . '/' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $new_file_key) . '.txt';

    if (file_exists($old_response_file)) {
        rename($old_response_file, $new_response_file);
        dol_syslog("update_file_references: Renamed AI response file", LOG_DEBUG);
    }

    // Update summary files too
    $old_summary_file = $ai_response_dir . '/' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $old_file_key) . '_summary.txt';
    $new_summary_file = $ai_response_dir . '/' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $new_file_key) . '_summary.txt';

    if (file_exists($old_summary_file)) {
        rename($old_summary_file, $new_summary_file);
        dol_syslog("update_file_references: Renamed AI summary file", LOG_DEBUG);
    }

    // Update OCR data (which uses MD5 of file path)
    $ocr_dir = DOL_DATA_ROOT . '/industria40/ocr';
    $upload_dir_path = DOL_DATA_ROOT . '/industria40/documents/' . $socid . '/' . $perizia_sanitized . '/';

    $old_ocr_path = $upload_dir_path . $old_name;
    $new_ocr_path = $upload_dir_path . $new_name;

    $old_ocr_file = $ocr_dir . '/' . md5($old_ocr_path) . '.txt';
    $new_ocr_file = $ocr_dir . '/' . md5($new_ocr_path) . '.txt';

    if (file_exists($old_ocr_file)) {
        rename($old_ocr_file, $new_ocr_file);
        dol_syslog("update_file_references: Renamed OCR file", LOG_DEBUG);
    }
}