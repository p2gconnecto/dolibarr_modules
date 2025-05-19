/**
 * Gestione degli eventi di connessione
 */
const ConnectionEventsManager = (function() {

    /**
     * Configura gli eventi di connessione per l'editor
     * @param {Drawflow} editor - Istanza dell'editor Drawflow
     */
    function setupDrawflowEvents(editor) {
        if (!editor) {
            console.error("Editor non fornito per la configurazione degli eventi");
            return;
        }

        console.log("Configurazione eventi Drawflow");

        // Evento click sul canvas per chiudere menù contestuali
        editor.on('click', function(event) {
            // Qualsiasi operazione da eseguire al clic sull'editor
            console.log("Click sull'editor");
        });

        // Evento quando viene creato un nuovo nodo
        editor.on('nodeCreated', function(nodeId) {
            console.log("Nodo creato:", nodeId);
            // Qualsiasi operazione per i nuovi nodi
        });

        // Evento quando viene creata una nuova connessione
        editor.on('connectionCreated', function(connection) {
            console.log("Connessione creata:", connection);
            updateConnectionMapping(connection);
        });

        // Evento quando viene rimossa una connessione
        editor.on('connectionRemoved', function(connection) {
            console.log("Connessione rimossa:", connection);
            // Rimuovi il mapping quando la connessione viene eliminata
            removeConnectionMapping(connection);
        });

        // Evento quando viene eliminato un nodo
        editor.on('nodeRemoved', function(nodeId) {
            console.log("Nodo rimosso:", nodeId);
            // Rimuovi tutti i mapping associati a questo nodo
            removeNodeMapping(nodeId);
        });
    }

    /**
     * Aggiorna il mapping quando viene creata una connessione
     * @param {Object} connection - Oggetto connessione
     */
    function updateConnectionMapping(connection) {
        try {
            const editor = window.DrawflowEditor;
            if (!editor) {
                console.error("Editor non disponibile per updateConnectionMapping");
                return;
            }

            // Ottieni i nodi dalla connessione
            const fromNodeId = connection.output_id;
            const toNodeId = connection.input_id;
            const fromNode = editor.getNodeFromId(fromNodeId);
            const toNode = editor.getNodeFromId(toNodeId);

            // Se uno dei due nodi non esiste, esci
            if (!fromNode || !toNode) {
                console.warn("Nodi non definiti in updateConnectionMapping:", fromNode, toNode, connection);
                return;
            }

            // Estrai gli indici correttamente
            let inputIndex = 0;
            let outputIndex = 0;

            // Cerca di estrarre l'indice dall'input_class
            if (connection.input_class && connection.input_class.includes('_')) {
                inputIndex = parseInt(connection.input_class.split('_').pop()) - 1; // input_6 diventa 5
            }

            // Cerca di estrarre l'indice dall'output_class
            if (connection.output_class && connection.output_class.includes('_')) {
                outputIndex = parseInt(connection.output_class.split('_').pop()) - 1; // output_2 diventa 1
            }

            // Debug avanzato
            console.log("Connessione creata - Dettagli completi:", connection);
            console.log("Connessione creata tra:", fromNode?.id, "e", toNode?.id,
                        "Input index:", inputIndex, "Output index:", outputIndex);

            // Gestione speciale per i nodi di debug
            if (toNode.data && toNode.data.type === 'debug') {
                updateMappingForDebugNode(fromNode, toNode, connection, inputIndex, outputIndex);
            }
            // Gestione per le connessioni ai campi DOCX
            else if (toNode.data && toNode.data.type === 'docx') {
                updateMappingForDocxNode(fromNode, toNode, connection, inputIndex, outputIndex);
            }
        } catch (e) {
            console.error("Errore nell'aggiornamento del mapping per la connessione:", e);
        }
    }

    /**
     * Aggiorna il mapping quando un nodo di debug riceve una connessione
     */
    function updateMappingForDebugNode(fromNode, toNode, connection, inputIndex, outputIndex) {
        try {
            console.log("Debug node connection - input_id:", connection.input_id,
                      "input_index:", inputIndex,
                      "output_id:", connection.output_id,
                      "output_index:", outputIndex);

            // CORREZIONE: Ottieni il nodo direttamente dall'editor per essere sicuri di avere l'istanza più aggiornata
            const editor = window.DrawflowEditor;
            const updatedToNode = editor.getNodeFromId(connection.input_id);

            // Inizializza le variabili necessarie
            let keyPath = null;
            let valueToDisplay = '';
            const nodeType = fromNode.data ? fromNode.data.type : 'sconosciuto';

            // Estrazione del valore a seconda del tipo di nodo
            if (nodeType === 'ai' && fromNode.data.jsonData) {
                // Controlla se c'è una chiave specifica associata a questo output
                console.log("AI node output - outputIndex:", outputIndex, "available keys:",
                           fromNode.data.outputKeys ? fromNode.data.outputKeys : "nessuna chiave");

                if (fromNode.data.outputKeys && fromNode.data.outputKeys.length > 0) {
                    // Usa l'outputIndex estratto correttamente prima
                    if (outputIndex >= 0 && outputIndex < fromNode.data.outputKeys.length) {
                        keyPath = fromNode.data.outputKeys[outputIndex];

                        if (keyPath) {
                            // Estrai il valore usando il percorso della chiave
                            valueToDisplay = JsonUtils.getValueByPath(fromNode.data.jsonData, keyPath);
                            valueToDisplay = typeof valueToDisplay === 'object' ?
                                JSON.stringify(valueToDisplay, null, 2) :
                                String(valueToDisplay || '');

                            console.log(`Valore estratto per ${keyPath}:`, valueToDisplay);
                        }
                    }
                }
            } else if (nodeType === 'tag') {
                valueToDisplay = fromNode.data.label || '';
            } else if (nodeType === 'aitag') {
                valueToDisplay = fromNode.data.tagName || '';
            } else if (nodeType === 'file') {
                valueToDisplay = fromNode.data.filename || '';
            } else if (nodeType === 'json-property') {
                valueToDisplay = fromNode.data.value || '';
                keyPath = fromNode.data.key || '';
            }

            // CORREZIONE: Assicurati che updatedToNode.data.values esista
            if (!updatedToNode.data) updatedToNode.data = {};
            if (!updatedToNode.data.values) updatedToNode.data.values = {};

            // Salva il valore usando inputIndex come chiave
            updatedToNode.data.values[inputIndex] = {
                sourceNodeId: fromNode.id,
                sourceNodeType: nodeType,
                keyPath: keyPath,
                value: valueToDisplay,
                connectionId: connection.connection_id
            };

            console.log(`Debug node ${updatedToNode.id} valori aggiornati:`, updatedToNode.data.values);
            console.log(`Debug node ${updatedToNode.id} ricevuto valore per input ${inputIndex}:`, valueToDisplay);

            // CORREZIONE: Forza un ritardo per permettere la propagazione dei dati
            setTimeout(() => {
                // CORREZIONE: Passa l'editor come secondo parametro per assicurare che usi lo stesso editor
                DebugNodesManager.updateDebugNodeHTML(updatedToNode.id, editor);
            }, 100);
        } catch (e) {
            console.error("Errore durante l'aggiornamento del nodo debug:", e);
        }
    }

    /**
     * Aggiorna il mapping quando un nodo DOCX riceve una connessione
     */
    function updateMappingForDocxNode(fromNode, toNode, connection, inputIndex, outputIndex) {
        try {
            console.log("DOCX node connection - input_id:", connection.input_id,
                      "input_index:", inputIndex,
                      "output_id:", connection.output_id,
                      "output_index:", outputIndex);

            const currentMapping = window.DrawflowManager.getCurrentMapping ?
                window.DrawflowManager.getCurrentMapping() : {};

            // Ottieni il nome del campo DOCX
            const fieldName = toNode.data.field;

            // Determina la sorgente del valore in base al tipo di nodo
            let sourceValue = '';
            const nodeType = fromNode.data ? fromNode.data.type : 'unknown';

            if (nodeType === 'ai') {
                let keyPath = '';
                if (fromNode.data.outputKeys && outputIndex < fromNode.data.outputKeys.length) {
                    keyPath = fromNode.data.outputKeys[outputIndex];
                }
                sourceValue = 'file_' + fromNode.data.filename + (keyPath ? '.' + keyPath : '');
            } else if (nodeType === 'tag') {
                sourceValue = 'tag_' + fromNode.data.tagId;
            } else if (nodeType === 'aitag') {
                sourceValue = 'aitag_' + fromNode.data.tagName;
            } else if (nodeType === 'file') {
                sourceValue = 'file_' + fromNode.data.filename;
            } else if (nodeType === 'json-property') {
                sourceValue = 'json_' + fromNode.data.key;
            }

            // Aggiorna il mapping corrente
            if (fieldName && sourceValue) {
                const newMapping = { ...currentMapping, [fieldName]: sourceValue };

                if (window.DrawflowManager.setCurrentMapping) {
                    window.DrawflowManager.setCurrentMapping(newMapping);
                }

                console.log(`Mapping aggiornato: ${fieldName} -> ${sourceValue}`);
            }
        } catch (e) {
            console.error("Errore durante l'aggiornamento del mapping DOCX:", e);
        }
    }

    /**
     * Rimuovi il mapping quando una connessione viene eliminata
     * @param {Object} connection - Oggetto connessione
     */
    function removeConnectionMapping(connection) {
        try {
            const editor = window.DrawflowEditor;
            if (!editor) return;

            // Per i nodi debug, rimuovi il valore visualizzato
            const toNodeId = connection.input_id;
            const toNode = editor.getNodeFromId(toNodeId);

            if (toNode && toNode.data && toNode.data.type === 'debug' && toNode.data.values) {
                // Cerca di estrarre l'indice dall'input_class
                let inputIndex = 0;
                if (connection.input_class && connection.input_class.includes('_')) {
                    inputIndex = parseInt(connection.input_class.split('_').pop()) - 1;
                }

                // Rimuovi il valore specifico
                if (toNode.data.values[inputIndex]) {
                    delete toNode.data.values[inputIndex];
                    console.log(`Rimosso valore dall'input ${inputIndex} del nodo debug ${toNodeId}`);

                    // Aggiorna la visualizzazione HTML
                    DebugNodesManager.updateDebugNodeHTML(toNodeId);
                }
            }
            // Per i nodi DOCX, rimuovi il mapping
            else if (toNode && toNode.data && toNode.data.type === 'docx') {
                const currentMapping = window.DrawflowManager.getCurrentMapping ?
                    window.DrawflowManager.getCurrentMapping() : {};

                const fieldName = toNode.data.field;
                if (fieldName && currentMapping[fieldName]) {
                    const newMapping = { ...currentMapping };
                    delete newMapping[fieldName];

                    if (window.DrawflowManager.setCurrentMapping) {
                        window.DrawflowManager.setCurrentMapping(newMapping);
                    }

                    console.log(`Mapping rimosso per il campo ${fieldName}`);
                }
            }
        } catch (e) {
            console.error("Errore nella rimozione del mapping per la connessione:", e);
        }
    }

    /**
     * Rimuovi tutti i mapping associati a un nodo eliminato
     * @param {string} nodeId - ID del nodo eliminato
     */
    function removeNodeMapping(nodeId) {
        try {
            const editor = window.DrawflowEditor;
            if (!editor) return;

            // Se il nodo eliminato era un nodo DOCX, rimuovi il suo mapping
            const currentMapping = window.DrawflowManager.getCurrentMapping ?
                window.DrawflowManager.getCurrentMapping() : {};

            // Controlla tra tutti i nodi se manca il nodo DOCX corrispondente al nodeId
            let removedField = null;
            for (const field in currentMapping) {
                const fieldNodeId = `docx-${field.toLowerCase().replace(/[^a-z0-9]/g, '')}`;
                if (fieldNodeId === nodeId) {
                    removedField = field;
                    break;
                }
            }

            if (removedField) {
                const newMapping = { ...currentMapping };
                delete newMapping[removedField];

                if (window.DrawflowManager.setCurrentMapping) {
                    window.DrawflowManager.setCurrentMapping(newMapping);
                }

                console.log(`Mapping rimosso per il campo ${removedField} (nodo ${nodeId} eliminato)`);
            }
        } catch (e) {
            console.error("Errore nella rimozione del mapping per il nodo eliminato:", e);
        }
    }

    // Esponi API pubblica
    return {
        setupDrawflowEvents,
        updateConnectionMapping,
        removeConnectionMapping,
        removeNodeMapping
    };
})();