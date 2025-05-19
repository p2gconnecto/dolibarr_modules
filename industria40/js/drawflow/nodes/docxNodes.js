/**
 * Gestione dei nodi DOCX
 */
const DocxNodesManager = (function() {
    // Funzione per creare i nodi DOCX
    function createDocxNodes(docx_fields) {
        var posX = 1200;
        var posY = 50;

        // Verifica che docx_fields sia un array valido prima di procedere
        if (!docx_fields || !Array.isArray(docx_fields)) {
            console.warn("docx_fields è null, undefined o non è un array. Viene utilizzato un array vuoto.");
            docx_fields = [];
        }

        docx_fields.forEach(function(field, index) {
            EditorManager.createNode(
                "docx-" + index,
                1,
                0,
                posX,
                posY,
                "docx-target",
                { field: field, type: "docx" },
                `<div class="node-title"><strong>${field}</strong></div>`
            );

            // Calcola la nuova posizione
            const newPos = EditorManager.calculateNodePosition(posX, posY, 80, 500, 250);
            posX = newPos.posX;
            posY = newPos.posY;
        });
    }

    // Esponi API pubblica
    return {
        createDocxNodes
    };
})();