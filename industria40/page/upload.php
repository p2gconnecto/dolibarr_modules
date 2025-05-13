<?php

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../class/industria40element.class.php';
require_once '../class/industria40project.class.php';

$langs->load('admin');
$langs->load('industria40@industria40');

$id = GETPOST('id', 'int'); // Perizia ID
$element_id = GETPOST('element_id', 'int'); // Single Machine/PLC Element ID
$action = GETPOST('action', 'alpha');

$element = new Industria40Element($db);
$element->fetch($element_id);

// Fetch related project (to know fk_societe and ref)
$project = new Industria40Project($db);
$project->fetch($id);

if ($action == 'upload') {
    // Modifica $upload_dir per essere coerente con file_manager.php e la nuova struttura
    $upload_dir = DOL_DATA_ROOT.'/industria40/documents/'.$project->fk_societe.'/'.$id.'/';

    if (!file_exists($upload_dir)) {
        // Assicurati che la directory base esista prima di creare quella specifica
        $base_module_dir = DOL_DATA_ROOT.'/industria40/';
        $base_documents_dir = DOL_DATA_ROOT.'/industria40/documents/';
        $base_soc_dir = DOL_DATA_ROOT.'/industria40/documents/'.$project->fk_societe.'/';

        if (!is_dir($base_module_dir)) dol_mkdir($base_module_dir);
        if (!is_dir($base_documents_dir)) dol_mkdir($base_documents_dir);
        if (!is_dir($base_soc_dir)) dol_mkdir($base_soc_dir);

        dol_mkdir($upload_dir);
    }

    foreach ($_FILES as $key => $fileinfo) {
        if (!empty($fileinfo['tmp_name'])) {
            $originalname = dol_sanitizeFileName($fileinfo['name']);
            $ext = pathinfo($originalname, PATHINFO_EXTENSION);

            $newname = strtolower(str_replace(' ', '_', $element->modello)).'_'.strtolower(str_replace(' ', '_', $element->matricola)).'_'.$key.'.'.$ext;

            $fullpath = $upload_dir.$newname;

            move_uploaded_file($fileinfo['tmp_name'], $fullpath);

            // Update corresponding field
            if ($key == 'invoice') $element->invoice_file = $newname;
            if ($key == 'contract') $element->contract_file = $newname;
            if ($key == 'ce_declaration') $element->ce_declaration_file = $newname;
            if ($key == 'datasheet') $element->datasheet_file = $newname;
            if ($key == 'manual') $element->manual_file = $newname;
            if ($key == 'image') $element->image_file = $newname;
        }
    }
    $element->update($user);

    setEventMessages('Evidence files uploaded successfully.', null, 'mesgs');

    header("Location: card.php?id=".$id);
    exit;
}

llxHeader('', 'Upload Evidence for Element');

print load_fiche_titre('Upload Evidence for '.$element->modello.' / '.$element->matricola, '', 'title_generic');

print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'&element_id='.$element_id.'" method="POST" enctype="multipart/form-data">';
print '<input type="hidden" name="action" value="upload">';
print '<table class="border centpercent">';

print '<tr><td>Invoice (PDF)</td><td><input type="file" name="invoice" accept=".pdf"></td></tr>';
print '<tr><td>Leasing Contract (PDF)</td><td><input type="file" name="contract" accept=".pdf"></td></tr>';
print '<tr><td>CE Declaration (PDF)</td><td><input type="file" name="ce_declaration" accept=".pdf"></td></tr>';
print '<tr><td>Datasheet (PDF)</td><td><input type="file" name="datasheet" accept=".pdf"></td></tr>';
print '<tr><td>Manual (PDF)</td><td><input type="file" name="manual" accept=".pdf"></td></tr>';
print '<tr><td>Image/Label (JPG/PNG)</td><td><input type="file" name="image" accept=".jpg,.jpeg,.png"></td></tr>';

print '</table>';
print '<br><div class="center">';
print '<input type="submit" class="button" value="Upload">';
print '</div>';
print '</form>';

llxFooter();
$db->close();
?>
