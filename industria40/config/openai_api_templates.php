<?php
/**
 * Configuration file for OpenAI API request templates
 * Contains request templates and configurations for different file types
 */

/**
 * Returns the OpenAI API request configuration for image analysis
 *
 * @param string $file_extension File extension (jpg, png, etc.)
 * @param string $base64_image Base64 encoded image data
 * @return array Request data configuration
 */
function get_image_analysis_request($file_extension, $base64_image) {
    return [
        'model' => 'gpt-4o',
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Analizza questa immagine e estrai tutte le informazioni testuali e visive rilevanti. '
                            . 'Restituisci la risposta in formato JSON strutturato seguendo esattamente uno dei modelli forniti, '
                            . 'riempiendo i campi appropriati. Seleziona il modello più adatto basandoti sul tipo di contenuto '
                            . 'dell\'immagine (fattura, preventivo, scheda tecnica, schermata, targhetta identificativa o foto generica).'
                            . "\n\nModello JSON da utilizzare (scegli il più appropriato):\n"
                            . '```json
{
  "fattura": {
    "numero": "",
    "data": "",
    "emettitore": "",
    "piva_emettitore": "",
    "destinatario": "",
    "piva_destinatario": "",
    "prodotti": [
      {
        "descrizione": "",
        "quantita": 0,
        "prezzo_unitario": 0.00,
        "totale": 0.00
      }
    ],
    "totale_documento": 0.00
  },
  "preventivo": {
    "numero": "",
    "data": "",
    "emettitore": "",
    "piva_emettitore": "",
    "destinatario": "",
    "piva_destinatario": "",
    "prodotti": [
      {
        "descrizione": "",
        "quantita": 0,
        "prezzo_unitario": 0.00,
        "totale": 0.00
      }
    ],
    "totale_documento": 0.00
  },
  "scheda": {
    "marca": "",
    "modello": "",
    "descrizione": "",
    "funzionalita_principali": [],
    "dati_tecnici": {
      "dimensioni": "",
      "peso": "",
      "alimentazione": "",
      "connettivita": "",
      "sensori": []
    }
  },
  "schermata": {
    "sorgente": "",
    "url_o_indirizzo_ip": "",
    "timestamp": "",
    "tipo_dato": ""
  },
  "targhetta": {
    "marca": "",
    "modello_o_tipo": "",
    "matricola": "",
    "anno_costruzione": "",
    "omologazione": ""
  },
  "foto": {
    "tipo": "",
    "contesto": "",
    "annotazioni_visive": ""
  }
}```
'
                            . 'Fornisci solo il JSON appropriato compilato con i dati che riesci a identificare dall\'immagine, senza commenti o testo aggiuntivo.'
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => 'data:image/'.$file_extension.';base64,'.$base64_image
                        ]
                    ]
                ]
            ]
        ],
        'max_tokens' => 1000,
        'response_format' => ['type' => 'json_object']
    ];
}

/**
 * Returns the OpenAI API request configuration for PDF analysis
 *
 * @param string $pdf_text Extracted text from PDF
 * @return array Request data configuration
 */
function get_pdf_analysis_request($pdf_text) {
    return [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Sei un assistente specializzato nell\'analisi di documenti PDF.'
            ],
            [
                'role' => 'user',
                'content' => 'Analizza il seguente testo estratto da un documento PDF e fornisci una descrizione dettagliata del contenuto. '
                    . 'Identifica il tipo di documento, i dettagli principali, e le informazioni più rilevanti. '
                    . 'Ecco il testo estratto: ' . $pdf_text
            ]
        ],
        'max_tokens' => 500
    ];
}

/**
 * Returns a summary structure based on document type
 *
 * @param string $detected_type Type of document detected
 * @param array $doc_data Document data extracted
 * @return string Formatted summary
 */
function get_document_summary($detected_type, $doc_data) {
    $summary = "Tipo documento: " . ucfirst($detected_type) . "\n";

    // Add information based on document type
    switch ($detected_type) {
        case 'fattura':
        case 'preventivo':
            $summary .= "Numero: " . ($doc_data['numero'] ?? 'N/D') . "\n";
            $summary .= "Data: " . ($doc_data['data'] ?? 'N/D') . "\n";
            $summary .= "Emesso da: " . ($doc_data['emettitore'] ?? 'N/D') . "\n";
            $summary .= "A favore di: " . ($doc_data['destinatario'] ?? 'N/D') . "\n";
            $summary .= "Totale: " . ($doc_data['totale_documento'] ?? 'N/D') . "€\n";
            break;
        case 'scheda':
            $summary .= "Marca: " . ($doc_data['marca'] ?? 'N/D') . "\n";
            $summary .= "Modello: " . ($doc_data['modello'] ?? 'N/D') . "\n";
            $summary .= "Descrizione: " . ($doc_data['descrizione'] ?? 'N/D') . "\n";
            break;
        case 'targhetta':
            $summary .= "Marca: " . ($doc_data['marca'] ?? 'N/D') . "\n";
            $summary .= "Modello: " . ($doc_data['modello_o_tipo'] ?? 'N/D') . "\n";
            $summary .= "Matricola: " . ($doc_data['matricola'] ?? 'N/D') . "\n";
            $summary .= "Anno: " . ($doc_data['anno_costruzione'] ?? 'N/D') . "\n";
            break;
        case 'schermata':
            $summary .= "Sorgente: " . ($doc_data['sorgente'] ?? 'N/D') . "\n";
            $summary .= "URL/IP: " . ($doc_data['url_o_indirizzo_ip'] ?? 'N/D') . "\n";
            break;
        case 'foto':
            $summary .= "Tipo: " . ($doc_data['tipo'] ?? 'N/D') . "\n";
            $summary .= "Contesto: " . ($doc_data['contesto'] ?? 'N/D') . "\n";
            $summary .= "Note: " . ($doc_data['annotazioni_visive'] ?? 'N/D') . "\n";
            break;
        default:
            $summary .= "Dati non strutturati disponibili.\n";
    }

    return $summary;
}