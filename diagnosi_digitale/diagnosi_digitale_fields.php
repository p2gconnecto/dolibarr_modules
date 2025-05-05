<?php
// filepath: /home/dolibarr/.volumes/dolibarr/custom/diagnosi_digitale/diagnosi_digitale_fields.php

// Funzione helper per generare label leggibili
function generate_label_from_key($key) {
    return ucfirst(str_replace('_', ' ', $key));
}

// Campi principali e di base - Definisci qui i campi con configurazioni specifiche o ordine prioritario
$fields = [
    'rowid' => ['type' => 'integer', 'label' => 'ID', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'position' => 1],
    'entity' => ['type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => 0, 'position' => 2],
    'ref' => ['type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'visible' => 1, 'position' => 5],
    'fk_projet' => ['type' => 'integer', 'label' => 'Project', 'enabled' => 1, 'visible' => 1, 'position' => 10],
    'fk_societe' => ['type' => 'integer', 'label' => 'ThirdParty', 'enabled' => 1, 'visible' => 1, 'position' => 15],
    'sede_operativa_interessata' => ['type' => 'varchar(255)', 'label' => 'Sede Operativa Interessata', 'enabled' => 1, 'visible' => 1, 'position' => 20],
    'azienda_nome' => ['type' => 'varchar(255)', 'label' => 'Azienda Nome', 'enabled' => 1, 'visible' => 1, 'position' => 25],
    'azienda_ragione_sociale' => ['type' => 'varchar(255)', 'label' => 'Azienda Ragione Sociale', 'enabled' => 1, 'visible' => 1, 'position' => 30],
    'dimensione_pmi' => ['type' => 'varchar(255)', 'label' => 'Dimensione PMI', 'enabled' => 1, 'visible' => 1, 'position' => 30],
    'referente_nome' => ['type' => 'varchar(255)', 'label' => 'Referente Nome', 'enabled' => 1, 'visible' => 1, 'position' => 31], // Aggiunto dal DESCRIBE
    'referente_ruolo' => ['type' => 'varchar(255)', 'label' => 'Referente Ruolo', 'enabled' => 1, 'visible' => 1, 'position' => 32], // Aggiunto dal DESCRIBE
    'n_dipendenti' => ['type' => 'integer', 'label' => 'Numero Dipendenti', 'enabled' => 1, 'visible' => 1, 'position' => 35],
    'fatturato_annuo' => ['type' => 'double(24,8)', 'label' => 'Fatturato Annuo', 'enabled' => 1, 'visible' => 1, 'position' => 40],
    'ex_ante_or_ex_post' => ['type' => 'tinyint(1)', 'label' => 'Ex Ante/Post', 'enabled' => 1, 'visible' => 1, 'position' => 45],
    'data_inizio_intervento' => ['type' => 'date', 'label' => 'Data Inizio Intervento', 'enabled' => 1, 'visible' => 1, 'position' => 50],
    'data_fine_intervento' => ['type' => 'date', 'label' => 'Data Fine Intervento', 'enabled' => 1, 'visible' => 1, 'position' => 55],
    'data_compilazione' => ['type' => 'date', 'label' => 'Data Compilazione', 'enabled' => 1, 'visible' => 1, 'position' => 60],
    'oggetto_valutazione' => ['type' => 'text', 'label' => 'Oggetto Valutazione', 'enabled' => 1, 'visible' => 1, 'position' => 110],
    'oggetto_valutazione_ai' => ['type' => 'text', 'label' => 'Oggetto Valutazione AI', 'enabled' => 1, 'visible' => 1, 'position' => 111],
    'digital_workplace_numero' => ['type' => 'integer', 'label' => 'Digital Workplace Numero', 'enabled' => 1, 'visible' => 1, 'position' => 150],
    'digital_workplace' => ['type' => 'decimal(10,2)', 'label' => 'Digital Workplace Contributo', 'enabled' => 1, 'visible' => 1, 'position' => 151],
    'digital_comm_engag' => ['type' => 'decimal(10,2)', 'label' => 'Digital Commerce Contributo', 'enabled' => 1, 'visible' => 1, 'position' => 152],
    'cloud_comp_app_server' => ['type' => 'decimal(10,2)', 'label' => 'Cloud App Server Contributo', 'enabled' => 1, 'visible' => 1, 'position' => 153],
    'cloud_comp_db_server' => ['type' => 'decimal(10,2)', 'label' => 'Cloud DB Server Contributo', 'enabled' => 1, 'visible' => 1, 'position' => 154],
    'cloud_comp_web_server' => ['type' => 'decimal(10,2)', 'label' => 'Cloud Web Server Contributo', 'enabled' => 1, 'visible' => 1, 'position' => 155],
    'cloud_comp_db_bkup_server' => ['type' => 'decimal(10,2)', 'label' => 'Cloud DB Backup Contributo', 'enabled' => 1, 'visible' => 1, 'position' => 156],
    'cyber_security' => ['type' => 'decimal(10,2)', 'label' => 'Cyber Security Contributo', 'enabled' => 1, 'visible' => 1, 'position' => 157],
    'totale' => ['type' => 'decimal(10,2)', 'label' => 'Totale Contributo', 'enabled' => 1, 'visible' => 1, 'position' => 160],
    'model_pdf' => ['type' => 'varchar(255)', 'label' => 'Modello PDF', 'enabled' => 1, 'visible' => 0, 'position' => 1300],
    'datec' => ['type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 0, 'visible' => 0, 'position' => 1400],
    'tms' => ['type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 0, 'visible' => 0, 'position' => 1410],
];

// Lista completa dei campi TEXT dalla tabella (per aggiungerli automaticamente se non già definiti)
$allTextFields = [
    'societa_nome',
    'indirizzo_completo',
    'approccio_metodologico',
    'settore_industriale',
    'dimensioni_ambizioni',
    'caratteristiche_prodotti',
    'maturita_digitale',
    'obiettivi_azienda',
    'capacita_investimento',
    'capacita_gestionale',
    'software_produttivita',
    'software_altri',
    'software_comunicazione',
    'software_archiviazione',
    'software_automazione',
    'piattaforme_condivisione',
    'software_firma',
    'piattaforma_dig_commerce',
    'piattaforma_campagne',
    'piattaforma_dig_exper',
    'piattaforma_analytics',
    'piattaforma_mobile',
    'piattaforma_integrazione',
    'piattaforma_logistica',
    'presenza_cloud',
    'presenza_app_client',
    'applicazioni_client',
    'servizi_calcolo',
    'servizi_db_e_archiv',
    'servizi_network',
    'servizi_identita_sec',
    'servizi_devel_test',
    'sistemi_accessi',
    'sistemi_network_secur',
    'sistemi_endpoint_secur',
    'sistemi_data_secur',
    'sistemi_vulnerab_admin',
    'sistemi_secur_analytics',
    'sistemi_applic_security',
    'sistemi_risk_compl_admin',
    'sintesi_d_workplace',
    'sintesi_d_comm_engagem',
    'sintesi_cc_app_server',
    'sintesi_cc_db_server',
    'sintesi_cc_web_server',
    'sintesi_cc_db_backup',
    'sintesi_cyber_security',
    'fabbisogno_sintesi_d_workplace',
    'fabbisogno_sintesi_d_comm_engagem',
    'fabbisogno_sintesi_cc_app_server',
    'fabbisogno_sintesi_cc_db_server',
    'fabbisogno_sintesi_cc_web_server',
    'fabbisogno_sintesi_cc_db_backup',
    'fabbisogno_sintesi_cyber_security',
    'impatto_stimato',
    'rischi_iniziative',
    'criteri_chiave',
    'priorita',
    'tempo_completamento',
    'data_completamento',
    'cronoprogramma',
    'ex_p_sintesi_d_workplace',
    'ex_p_sintesi_d_comm_engagem',
    'ex_p_sintesi_cc_app_server',
    'ex_p_sintesi_cc_db_server',
    'ex_p_sintesi_cc_web_server',
    'ex_p_sintesi_cc_db_backup',
    'ex_p_sintesi_cyber_security',
    'ex_p_giudizio_finale',
    'context_ai',
    'context_web',
    'context_ocr',
    // Aggiungi qui altri campi TEXT se presenti nel DB e non elencati
];

// Lista completa dei campi TINYINT(1) (boolean) dalla tabella
$allBooleanFields = [
    'ex_ante_or_ex_post', // Già definito sopra
    'ex_p_v_f_d_workplace',
    'ex_p_v_f_d_comm_engagem',
    'ex_p_v_f_cloud_computing',
    'ex_p_v_f_cyber_security',
    // Aggiungi qui altri campi TINYINT(1) se presenti nel DB e non elencati
];


$positionCounter = 200; // Start position for automatically added fields

// Aggiungi i campi TEXT mancanti
foreach ($allTextFields as $fieldKey) {
    if (!isset($fields[$fieldKey])) { // Aggiungi solo se non già definito manualmente sopra
        $fields[$fieldKey] = [
            'type' => 'text',
            'label' => generate_label_from_key($fieldKey),
            'enabled' => 1,
            'visible' => 1, // Visibile nel form di default
            'position' => $positionCounter++
        ];
    }
}

// Aggiungi i campi TINYINT(1) mancanti
foreach ($allBooleanFields as $fieldKey) {
    if (!isset($fields[$fieldKey])) { // Aggiungi solo se non già definito manualmente sopra
        $fields[$fieldKey] = [
            'type' => 'tinyint(1)',
            'label' => generate_label_from_key($fieldKey),
            'enabled' => 1,
            'visible' => 1, // Visibile nel form di default
            'position' => $positionCounter++
        ];
    }
}

// Rendi invisibili i campi context_* se non devono apparire nel form standard
if (isset($fields['context_ai'])) $fields['context_ai']['visible'] = 0;
if (isset($fields['context_web'])) $fields['context_web']['visible'] = 0;
if (isset($fields['context_ocr'])) $fields['context_ocr']['visible'] = 0;


// Ordina l'array finale per 'position' per mantenere l'ordine logico
uasort($fields, function ($a, $b) {
    return ($a['position'] ?? 9999) <=> ($b['position'] ?? 9999);
});

return $fields;
?>
