<?php
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

// Carica il template corretto
$template = new TemplateProcessor('templates/model.docx');




// Prepara i dati per il PDF
$idirizzo_full = $object->societa_nome . ' ' . $object->societa_indirizzo . ' ' . $object->societa_cap . ' ' . $object->societa_citta;
/*$data_pdf = [
    'societa_nome' => $object->societa_nome,
    'societa_indirizzo' => $object->societa_indirizzo,
    'societa_cap' => $object->societa_cap,
    'societa_citta' => $object->societa_citta,
];*/

if (empty($object) || empty($object->id)) {
    dol_syslog(__METHOD__ . " Error: Object is null or ID is missing", LOG_ERR);
    return -1;
}

// Se i dati della società non sono passati direttamente, prova a recuperarli
if ($societa_nome === null && !empty($object->fk_societe)) {
    $societe = new Societe($db);
    if ($societe->fetch($object->fk_societe) > 0) {
        $societa_nome = $societe->nom;
        $societa_indirizzo = $societe->address;
        $societa_cap = $societe->zip;
        $societa_citta = $societe->town;
        // Popola $data_pdf se necessario
        $data_pdf['societa_nome'] = $societa_nome;
        $data_pdf['societa_indirizzo'] = $societa_indirizzo;
        $data_pdf['societa_cap'] = $societa_cap;
        $data_pdf['societa_citta'] = $societa_citta;
    }
}

// Popola $data_pdf con i parametri ricevuti se non già presenti
$data_pdf['societa_nome'] = $data_pdf['societa_nome'] ?? $societa_nome ?? $object->azienda_ragione_sociale ?? 'N/A';
$data_pdf['societa_indirizzo'] = $data_pdf['societa_indirizzo'] ?? $societa_indirizzo ?? 'N/A';
$data_pdf['societa_cap'] = $data_pdf['societa_cap'] ?? $societa_cap ?? 'N/A';
$data_pdf['societa_citta'] = $data_pdf['societa_citta'] ?? $societa_citta ?? 'N/A';
$data_pdf['indirizzo_completo'] = $data_pdf['indirizzo_completo'] ?? ($data_pdf['societa_indirizzo'] && $data_pdf['societa_cap'] && $data_pdf['societa_citta'] ? $data_pdf['societa_indirizzo'] . ', ' . $data_pdf['societa_cap'] . ', ' . $data_pdf['societa_citta'] : 'N/A');



$dimensione_pmi = $object->dimensione_pmi ?? 'N/A'; // Aggiunto valore predefinito
$object->azienda_ragione_sociale = $object->societa_nome;
$object->indirizzo = $idirizzo_full;
$object->settore_industriale = $object->settore_industriale ?? 'N/A'; // Aggiunto valore predefinito
$object->fatturato_annuo = $object->fatturato_annuo ?? 'N/A'; // Aggiunto valore predefinito
$object->n_dipendenti = $object->n_dipendenti ?? 'N/A'; // Aggiunto valore predefinito
$object->ex_ante_or_ex_post = $object->ex_ante_or_ex_post ?? 'N/A'; // Aggiunto valore predefinito
$object->data_inizio_intervento = $object->data_inizio_intervento ?? 'N/A'; // Aggiunto valore predefinito

// Inserisci i dati
//$template->setValue('azienda_ragione_sociale', $object->azienda_ragione_sociale ?? 'N/A');
//$template->setValue('azienda_ragione_sociale', 'PAPPAFICO');
$template->setValue('indirizzo', $object->address ?? 'N/A');
$template->setValue('dimensione_pmi', $dimensione_pmi ?? 'N/A');
//$template->setValue('approccio_metodologico', $object->approccio_metodologico ?? 'N/A');
$template->setValue('cloud_comp_db_server', $object->cloud_comp_db_server ?? 'N/A');
$template->setValue('impatto_stimato', $object->impatto_stimato ?? 'N/A');
$template->setValue('digital_comm_engag', $object->digital_comm_engag ?? 'N/A');
$template->setValue('ex_p_giudizio_finale', $object->ex_p_giudizio_finale ?? 'N/A');
$template->setValue('software_produttivita', $object->software_produttivita ?? 'N/A');

// Salva su file
//$template->saveAs('diagnosi_digitale_compilata.docx');

