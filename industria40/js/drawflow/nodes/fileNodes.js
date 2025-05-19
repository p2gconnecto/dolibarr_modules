/**
 * Gestione dei nodi file
 */
const FileNodesManager = (function() {
    // Funzione per creare i nodi dei file
    function createFileNodes(files_in_dir, ai_tags, ai_descriptions, socid, periziaid_sanitized, baseUrl) {
        if (!files_in_dir) {
            console.warn("files_in_dir è null o undefined");
            files_in_dir = {};
        }

        var posX = 50;
        var posY = 50;
        var jsonNodesCount = 0;

        Object.keys(files_in_dir).forEach(function(key, index) {
            var file = files_in_dir[key];
            var filename = file.name;
            var extension = file.extension;
            var iconClass = file.icon;

            var thumbnailHtml = DOMUtils.getPeriziaFileThumbnail(filename, socid, periziaid_sanitized, baseUrl);

            // Crea nodo per questo file
            EditorManager.createNode(
                "file-" + index,
                0, // inputs
                1, // outputs
                posX,
                posY,
                thumbnailHtml ? "file-node node-with-thumbnail" : "file-node",
                { filename: filename, extension: extension, type: "file" },
                `<div class="node-title">
                    <i class="${iconClass} file-icon"></i>
                    <strong>${filename}</strong>
                </div>
                ${thumbnailHtml}`
            );

            // Calcola la nuova posizione
            const newPos = EditorManager.calculateNodePosition(posX, posY, thumbnailHtml ? 170 : 120, 500, 250);
            posX = newPos.posX;
            posY = newPos.posY;

            // Verifica se questo file ha una descrizione AI in formato JSON
            if (ai_descriptions && ai_descriptions[filename]) {
                try {
                    const jsonData = JSON.parse(ai_descriptions[filename]);
                    var jsonX = posX + 200;
                    var jsonY = posY - 100;

                    // Estrai le coppie chiave-valore di primo livello
                    const jsonPairs = [];
                    for (let key in jsonData) {
                        if (jsonData.hasOwnProperty(key)) {
                            jsonPairs.push({
                                key: key,
                                value: jsonData[key]
                            });
                        }
                    }

                    if (jsonPairs.length > 0) {
                        // Crea un nodo principale per il JSON
                        const jsonParentNodeId = `jsonparent-${jsonNodesCount}`;

                        EditorManager.createNode(
                            jsonParentNodeId,
                            1, // inputs
                            1, // outputs
                            jsonX,
                            jsonY,
                            "json-parent-node",
                            {
                                sourceFile: filename,
                                type: "json-parent"
                            },
                            `<div class="node-title">
                                <i class="fa fa-code json-icon"></i>
                                <strong>JSON: ${filename}</strong>
                            </div>
                            <div class="json-source">Contiene ${jsonPairs.length} proprietà</div>`
                        );

                        // Incrementa Y per i nodi figli
                        jsonY += 80;

                        // Crea un nodo separato per ogni proprietà JSON
                        jsonPairs.forEach((pair, idx) => {
                            const jsonChildNodeId = `jsonchild-${jsonNodesCount}-${idx}`;

                            EditorManager.createNode(
                                jsonChildNodeId,
                                1, // inputs
                                1, // outputs
                                jsonX + 200,
                                jsonY + (idx * 60),
                                "json-property-node",
                                {
                                    key: pair.key,
                                    value: pair.value,
                                    sourceFile: filename,
                                    type: "json-property"
                                },
                                `<div class="node-title">
                                    <i class="fa fa-key json-key-icon"></i>
                                    <strong>${pair.key}</strong>
                                </div>
                                <div class="json-value-display">${JsonUtils.formatValueForDisplay(pair.value)}</div>
                                <div class="json-source">Da: ${filename}</div>`
                            );

                            // Connetti automaticamente il nodo principale ai nodi delle proprietà
                            const editor = window.DrawflowEditor;
                            editor.addConnection(jsonParentNodeId, jsonChildNodeId, 1, 1);
                        });

                        jsonNodesCount++;
                    }
                } catch (e) {
                    console.log(`Il file ${filename} non contiene JSON valido:`, e);
                }
            }
        });
    }

    // Esponi API pubblica
    return {
        createFileNodes
    };
})();