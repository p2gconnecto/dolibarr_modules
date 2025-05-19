/**
 * Gestione dei nodi AI
 */
const AINodesManager = (function() {
    // Funzione per creare i nodi AI
    function createAINodes(ai_descriptions, ai_tags, socid, periziaid_sanitized, baseUrl) {
        var posX = 350;
        var posY = 50;
        var aiTagCounts = {};

        Object.keys(ai_descriptions).forEach(function(filename, index) {
            const content = ai_descriptions[filename];
            const isJsonContent = JsonUtils.isValidJSON(content);

            // Usa la funzione getPeriziaFileThumbnail tramite il modulo DOMUtils
            const thumbnailHtml = DOMUtils.getPeriziaFileThumbnail(filename, socid, periziaid_sanitized, baseUrl);

            let shortContent = "";
            let jsonData = null;
            let outputKeys = [];

            if (ai_tags[filename]) {
                const tag = ai_tags[filename];
                aiTagCounts[tag] = (aiTagCounts[tag] || 0) + 1;
            }

            if (isJsonContent) {
                try {
                    jsonData = JSON.parse(content);
                    // Estrai tutte le chiavi, incluse quelle annidate
                    outputKeys = JsonUtils.extractKeys(jsonData);

                    // Crea contenuto abbreviato per visualizzazione
                    for (let key in jsonData) {
                        if (jsonData.hasOwnProperty(key)) {
                            const value = jsonData[key];
                            const displayValue = typeof value === 'object'
                                ? JSON.stringify(value).substring(0, 20) + '...'
                                : String(value).substring(0, 20) + (String(value).length > 20 ? '...' : '');

                            shortContent += `<strong>${key}</strong>: ${displayValue}<br>`;
                        }
                    }

                    // Genera HTML per JSON
                    shortContent += JsonUtils.generateJsonHTML(jsonData, {
                        showClipboardScript: true
                    });
                } catch(e) {
                    console.error("Errore nel parsing JSON:", e);
                    shortContent = content.substring(0, 100) + "...";
                }
            } else {
                shortContent = content.substring(0, 100) + "...";
            }

            const numOutputs = isJsonContent && outputKeys.length > 0 ? outputKeys.length : 1;
            console.log("Contenuto AI per il file:", filename, content);
            console.log("Numero di output:", numOutputs);
            console.log("Chiavi di output:", outputKeys);
            console.log("Contenuto JSON:", jsonData);

            const nodeId = "ai-" + index;
            // Use first available key in json as the node title
            let nodeTitle = isJsonContent && outputKeys.length > 0 ? outputKeys[0] : filename;
            nodeTitle = nodeTitle.length > 20 ? nodeTitle.substring(0, 17) + '...' : nodeTitle;

            let nodeHTML = `<div class="node-title" style="text-align: left;">
                <strong>${nodeTitle}</strong>
            </div>
            ${thumbnailHtml}`;

            const addedNodeId = window.DrawflowEditor.addNode(
                nodeId,
                0,
                numOutputs,
                posX,
                posY,
                thumbnailHtml ? "ai-source node-with-thumbnail" : "ai-source",
                {
                    filename: filename,
                    type: "ai",
                    jsonData: isJsonContent ? jsonData : null,
                    outputKeys: outputKeys
                },
                nodeHTML
            );

            console.log(`Nodo AI aggiunto con ID richiesto '${nodeId}', ID effettivo:`, addedNodeId);

            // Usa l'ID reale per il resto del codice
            const actualNodeId = addedNodeId || nodeId;
            if (isJsonContent && outputKeys.length > 0) {
                DrawflowInitializer.getNodesToConfig()[actualNodeId] = {
                    outputKeys: outputKeys
                };

                // Passa anche outputKeys come secondo parametro
                DOMUtils.observeNode(actualNodeId, outputKeys);
            } else {
                // Anche se non ci sono chiavi JSON, potresti comunque voler aggiungere etichette numeriche
                DOMUtils.observeNode(actualNodeId, []);
            }

            // Calcola la nuova posizione
            const newPos = EditorManager.calculateNodePosition(posX, posY, thumbnailHtml ? 220 : 150, 500, 250);
            posX = newPos.posX;
            posY = newPos.posY;
        });

        return aiTagCounts;
    }

    // Esponi API pubblica
    return {
        createAINodes
    };
})();