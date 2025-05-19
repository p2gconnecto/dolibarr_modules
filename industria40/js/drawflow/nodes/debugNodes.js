/**
 * Gestione dei nodi di debug
 */
const DebugNodesManager = (function() {
    // Funzione per creare nodi di debug/test
    function createDebugNodes(count = 1, inputsPerNode = 10) {
        var posX = 800;
        var posY = 50;

        for (let i = 0; i < count; i++) {
            // Assicurati che l'ID sia coerente
            const nodeId = `debug-${i}`;

            // Prepara le etichette per gli input
            const inputLabels = [];
            for (let j = 0; j < inputsPerNode; j++) {
                inputLabels.push(`Input ${j + 1}`);
            }

            // Configura dati del nodo
            const nodeData = {
                type: "debug",
                values: {},  // Memoria per salvare i valori ricevuti
                inputLabels: inputLabels.slice(0, inputsPerNode), // Crea etichette per tutti gli input
                isMaster: true
            };

            // HTML interno del nodo
            const nodeHtml = `
                <div class="node-title debug-node-title">
                    <i class="fa fa-bug"></i>
                    <strong>Debug Node</strong>
                </div>
                <div class="debug-content">
                    <div class="debug-description">
                        Nodo di debug. Connetti gli output dei nodi per visualizzare i valori.
                    </div>
                    <div class="debug-values" data-node-id="${nodeId}">
                        <p>Nessun valore ricevuto</p>
                    </div>
                </div>
            `;

            // Aggiungi il nodo all'editor
            const editor = window.DrawflowEditor;
            editor.addNode(nodeId, inputsPerNode, 0, posX, posY,
                          "debug-node master-debug-node", nodeData, nodeHtml);

            // Dopo l'aggiunta del nodo, salva una referenza all'ID effettivo
            console.log(`Nodo debug creato con ID interno: ${nodeId}, verifica l'ID DOM: node-${nodeId}`);

            // Aggiungi stile inline per dimensioni personalizzate
            setTimeout(() => {
                const nodeElement = document.getElementById(`node-${nodeId}`);
                if (nodeElement) {
                    nodeElement.style.minWidth = `400px`;
                    nodeElement.style.width = `400px`;
                    nodeElement.style.minHeight = `350px`;
                    nodeElement.classList.add('resized');
                }
            }, 100);

            // Calcola la nuova posizione
            const newPos = EditorManager.calculateNodePosition(posX, posY, i === 0 ? 450 : 150, 500, 250);
            posX = newPos.posX;
            posY = newPos.posY;
        }

        // Verifica dopo la creazione
        setTimeout(() => {
            // Cerca sia con l'ID specifico che con la classe
            const debugNode = document.querySelector('#node-debug-0');
            const anyDebugNode = document.querySelector('.debug-node');
            console.log("Debug node trovato con ID specifico:", !!debugNode);
            console.log("Qualsiasi debug node trovato:", !!anyDebugNode);
            if (anyDebugNode) {
                console.log("ID del nodo debug trovato:", anyDebugNode.id);
                // Verifica che inspectDebugNode sia definita prima di chiamarla
                if (DOMUtils.inspectDebugNode) {
                    // Ispeziona questo nodo
                    DOMUtils.inspectDebugNode(anyDebugNode.id.replace('node-', ''), window.DrawflowEditor);
                } else {
                    console.warn("Funzione inspectDebugNode non definita");
                }
            }
        }, 500);
    }

    // Funzione per aggiornare l'HTML del nodo debug
    function updateDebugNodeHTML(nodeId, editorInstance) {
        try {
            console.log(`Aggiornamento HTML del nodo debug ${nodeId} iniziato`);

            // CORREZIONE: Usa l'editor fornito o ottieni quello globale
            const editor = editorInstance || window.DrawflowEditor;
            if (!editor) {
                console.error("Editor non disponibile per l'aggiornamento del nodo debug");
                return;
            }

            // CORREZIONE: Ottieni il nodo usando una reference fresca
            const node = editor.getNodeFromId(nodeId);
            console.log("Nodo ottenuto dall'editor:", node);

            if (!node || !node.data || node.data.type !== 'debug') {
                console.warn(`Nodo ${nodeId} non trovato o non è un nodo debug`);
                return;
            }

            // CORREZIONE: Debug per verificare i valori
            console.log(`Nodo debug ${nodeId} dati completi:`, JSON.stringify(node.data));

            // Trova l'elemento del nodo in modo più affidabile
            let nodeElement = document.getElementById('node-' + nodeId);
            if (!nodeElement) {
                console.warn(`Elemento DOM per nodo debug ${nodeId} non trovato`);
                return;
            }

            console.log(`Elemento DOM trovato per il nodo ${nodeId}:`, nodeElement);

            // Cerca il contenitore dei valori direttamente nel nodo
            const valuesContainer = nodeElement.querySelector('.debug-values');
            if (!valuesContainer) {
                console.error(`Container valori .debug-values non trovato nel nodo ${nodeId}`);
                return;
            }

            console.log(`Container valori trovato per il nodo ${nodeId}:`, valuesContainer);

            // CORREZIONE: Assicurati che i valori esistano e siano aggiornati
            const values = node.data.values || {};
            console.log(`Valori da visualizzare per il nodo ${nodeId}:`, values);

            // Stampa tutti i valori per debug
            Object.keys(values).forEach(key => {
                console.log(`Valore in ${key}:`, values[key]);
            });

            if (Object.keys(values).length === 0) {
                valuesContainer.innerHTML = '<p>Nessun valore ricevuto</p>';
                return;
            }

            // Aggiornamento valori con DocumentFragment
            const fragment = document.createDocumentFragment();

            Object.keys(values).forEach(inputIndex => {
                const data = values[inputIndex];
                if (!data) {
                    console.warn(`Dati mancanti per input ${inputIndex}`);
                    return;
                }

                const inputLabel = node.data.inputLabels &&
                                 node.data.inputLabels[inputIndex] ?
                                 node.data.inputLabels[inputIndex] :
                                 `Input ${Number(inputIndex) + 1}`;

                // Formatta il valore in modo più leggibile
                let formattedValue = data.value;
                let isJson = false;

                try {
                    if (typeof data.value === 'object') {
                        formattedValue = JSON.stringify(data.value, null, 2);
                        isJson = true;
                    } else if (typeof data.value === 'string' && data.value.trim().startsWith('{')) {
                        const parsedJson = JSON.parse(data.value);
                        formattedValue = JSON.stringify(parsedJson, null, 2);
                        isJson = true;
                    }
                } catch (e) {
                    console.warn(`Errore nella formattazione del valore JSON: ${e.message}`);
                }

                const itemDiv = document.createElement('div');
                itemDiv.className = 'debug-value-item';
                itemDiv.innerHTML = `
                    <span class="debug-value-key">${inputLabel}</span>
                    ${data.keyPath ? `<span class="debug-value-path">(${data.keyPath})</span>` : ''}
                    <span class="debug-value-source">[${data.sourceNodeType}]</span>
                    <pre class="debug-value-content ${isJson ? 'json-content' : ''}">${DOMUtils.escapeHtml(formattedValue)}</pre>
                `;

                fragment.appendChild(itemDiv);
            });

            // Aggiorna il DOM una sola volta
            valuesContainer.innerHTML = '';
            valuesContainer.appendChild(fragment);

            // CORREZIONE: Forza il rendering DOM aggiungendo una classe
            nodeElement.classList.add('values-updated');
            setTimeout(() => nodeElement.classList.remove('values-updated'), 100);

            // Debug finale
            console.log(`HTML del nodo debug ${nodeId} aggiornato con ${Object.keys(values).length} valori`);
            console.log("HTML finale:", valuesContainer.innerHTML);

            // Ispeziona il nodo per debug avanzato
            setTimeout(() => DOMUtils.inspectDebugNode(nodeId, window.DrawflowEditor), 100);
        } catch (e) {
            console.error(`Errore durante l'aggiornamento dell'HTML del nodo debug ${nodeId}:`, e);
        }
    }

    // Esponi API pubblica
    return {
        createDebugNodes,
        updateDebugNodeHTML
    };
})();