/**
 * Gestione dei nodi tag
 */
const TagNodesManager = (function() {
    // Funzione per creare i nodi tag
    function createTagNodes(available_tags) {
        var posX = 700;
        var posY = 50;

        Object.keys(available_tags).forEach(function(tagId, index) {
            var tag = available_tags[tagId];

            EditorManager.createNode(
                "tag-" + tagId,
                0,
                1,
                posX,
                posY,
                "tag-node",
                { tagId: tagId, label: tag.label, type: "tag" },
                `<div class="node-title tag-label">${tag.label}</div>
                <div class="tag-content">${tag.description || "Nessuna descrizione"}</div>`
            );

            // Calcola la nuova posizione
            const newPos = EditorManager.calculateNodePosition(posX, posY, 100, 500, 200);
            posX = newPos.posX;
            posY = newPos.posY;
        });
    }

    // Funzione per creare i nodi tag AI
    function createAITagNodes(aiTagCounts) {
        var posX = 950;
        var posY = 50;

        var uniqueAiTags = Object.keys(aiTagCounts);
        uniqueAiTags.forEach(function(tagName, index) {
            EditorManager.createNode(
                "aitag-" + index,
                0,
                1,
                posX,
                posY,
                "ai-tag-node",
                { tagName: tagName, count: aiTagCounts[tagName], type: "aitag" },
                `<div class="node-title ai-tag-label">${tagName}</div>
                <div class="tag-content">Trovato in ${aiTagCounts[tagName]} file</div>`
            );

            // Calcola la nuova posizione
            const newPos = EditorManager.calculateNodePosition(posX, posY, 100, 500, 200);
            posX = newPos.posX;
            posY = newPos.posY;
        });
    }

    // Esponi API pubblica
    return {
        createTagNodes,
        createAITagNodes
    };
})();