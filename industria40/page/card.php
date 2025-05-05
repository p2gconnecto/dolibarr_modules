<?php

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../class/industria40project.class.php';
require_once '../class/industria40element.class.php';

$langs->load('admin');
$langs->load('industria40@industria40');

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$error = 0;

$project = new Industria40Project($db);
if ($id > 0) $project->fetch($id);

// Handle form submissions
if ($action == 'add_element') {
    $element = new Industria40Element($db);
    $element->fk_industria40project = $id;
    $element->produttore = GETPOST('produttore', 'alphanohtml');
    $element->piva = GETPOST('piva', 'alphanohtml');
    $element->modello = GETPOST('modello', 'alphanohtml');
    $element->matricola = GETPOST('matricola', 'alphanohtml');
    $element->anno_costruzione = GETPOST('anno_costruzione', 'int');
    $element->descrizione = GETPOST('descrizione', 'restricthtml');

    $element->create($user);

    header("Location: ".$_SERVER['PHP_SELF']."?id=".$id."&upload_for=".$element->id);
    exit;
}

llxHeader('', 'Industria 4.0 - Project');

print load_fiche_titre("Industria 4.0 - ".dol_escape_htmltag($project->ref), '', 'title_generic');

// Project Info
print '<div class="fichecenter">';
print '<table class="border centpercent">';
print '<tr><td>Reference</td><td>'.$project->ref.'</td></tr>';
print '<tr><td>Company</td><td>'.$project->fk_societe.'</td></tr>'; // You could enhance to show real company name
print '<tr><td>Description</td><td>'.$project->description.'</td></tr>';
print '<tr><td>Status</td><td>'.$project->status.'</td></tr>';
print '</table>';
print '</div>';

print '<br><br>';

// Form to add a new Element
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'">';
print '<input type="hidden" name="action" value="add_element">';
print load_fiche_titre('➕ Add New Machine/PLC/Sensor', '', '');
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>Type</td><td>Produttore</td><td>P.IVA</td><td>Modello</td><td>Matricola</td><td>Anno</td><td>Description</td><td>Action</td>';
print '</tr>';
print '<tr>';
print '<td><select name="type">
<option value="MACHINE">Machine</option>
<option value="PLC">PLC</option>
<option value="CENTRALINA">Centralina</option>
<option value="SENSOR">Sensor</option>
<option value="ACCESSORY">Accessory</option>
</select></td>';
print '<td><input name="produttore" type="text" size="12"></td>';
print '<td><input name="piva" type="text" size="12"></td>';
print '<td><input name="modello" type="text" size="12"></td>';
print '<td><input name="matricola" type="text" size="12"></td>';
print '<td><input name="anno_costruzione" type="text" size="6" maxlength="4"></td>';
print '<td><input name="descrizione" type="text" size="20"></td>';
print '<td><input class="button" type="submit" value="Save"></td>';
print '</tr>';
print '</table>';
print '</div>';
print '</form>';

print '<br><br>';

// List existing Elements
print load_fiche_titre('📦 Elements of this Perizia', '', '');

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."industria40element WHERE fk_industria40project = ".((int) $id);
$resql = $db->query($sql);

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>Type</td><td>Produttore</td><td>Modello</td><td>Matricola</td><td>Anno</td><td>Actions</td>';
print '</tr>';

while ($obj = $db->fetch_object($resql)) {
    print '<tr>';
    print '<td>'.$obj->type.'</td>';
    print '<td>'.$obj->produttore.'</td>';
    print '<td>'.$obj->modello.'</td>';
    print '<td>'.$obj->matricola.'</td>';
    print '<td>'.$obj->anno_costruzione.'</td>';
    print '<td>';
    print '<a href="upload.php?element_id='.$obj->rowid.'&id='.$id.'" class="button">Upload Evidence</a>';
    print '</td>';
    print '</tr>';
}

print '</table>';
print '</div>';

llxFooter();
$db->close();
?>
