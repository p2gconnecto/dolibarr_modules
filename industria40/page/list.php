<?php

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../class/industria40project.class.php';
require_once '../class/industria40element.class.php';

$langs->load('admin');
$langs->load('industria40@industria40');

$title = "Industria 4.0 - Projects";
llxHeader('', $title);

$sql = "SELECT p.rowid, p.ref, p.fk_societe, s.nom as company_name, p.status
        FROM ".MAIN_DB_PREFIX."industria40project p
        LEFT JOIN ".MAIN_DB_PREFIX."societe s ON p.fk_societe = s.rowid
        ORDER BY p.date_creation DESC";

$resql = $db->query($sql);

print load_fiche_titre($title, '', 'title_generic');

print '<div class="div-table-responsive">';
print '<table class="liste centpercent">';
print '<thead><tr class="liste_titre">';
print '<td>Ref.</td><td>Company</td><td>Status</td><td>Evidence Completion</td><td>Actions</td>';
print '</tr></thead><tbody>';

while ($obj = $db->fetch_object($resql)) {
    print '<tr>';
    print '<td><a href="card.php?id='.$obj->rowid.'">'.dol_escape_htmltag($obj->ref).'</a></td>';
    print '<td>'.dol_escape_htmltag($obj->company_name).'</td>';

    // Calculate % evidence completion
    $sql2 = "SELECT * FROM ".MAIN_DB_PREFIX."industria40element WHERE fk_industria40project = ".((int) $obj->rowid);
    $resql2 = $db->query($sql2);

    $total_required = 0;
    $total_present = 0;
    $missing_details = [];

    while ($elem = $db->fetch_object($resql2)) {
        $total_required += 5; // invoice + ce_declaration + datasheet + manual + image

        if (!empty($elem->invoice_file)) $total_present++;
        else $missing_details[] = 'Invoice ('.$elem->modello.')';

        if (!empty($elem->ce_declaration_file)) $total_present++;
        else $missing_details[] = 'CE ('.$elem->modello.')';

        if (!empty($elem->datasheet_file)) $total_present++;
        else $missing_details[] = 'Datasheet ('.$elem->modello.')';

        if (!empty($elem->manual_file)) $total_present++;
        else $missing_details[] = 'Manual ('.$elem->modello.')';

        if (!empty($elem->image_file)) $total_present++;
        else $missing_details[] = 'Image ('.$elem->modello.')';
    }

    $percent = 0;
    if ($total_required > 0) {
        $percent = round(($total_present / $total_required) * 100);
    }

    // Status Badge
    if ($percent == 100) {
        print '<td><span class="badge badge-success">✅ Completed</span></td>';
    } elseif ($percent >= 50) {
        print '<td><span class="badge badge-warning">🛠 In Progress</span></td>';
    } else {
        print '<td><span class="badge badge-danger">🔴 Missing Documents</span></td>';
    }

    // Evidence Completion %
    print '<td>';
    print $percent.'%';
    if ($percent < 100 && count($missing_details) > 0) {
        print '<br><small><b>Missing:</b> '.implode(', ', $missing_details).'</small>';
    }
    print '</td>';

    // Actions
    print '<td>';
    print '<a class="butAction" href="card.php?id='.$obj->rowid.'">👁 View</a>';
    print '</td>';

    print '</tr>';
}

print '</tbody></table>';
print '</div>';

llxFooter();
$db->close();
?>
