/**
 * Modulo per la gestione dell'editor DrawFlow
 */
const EditorManager = (function() {
    // Crea e configura l'editor DrawFlow
    function createEditor(container) {
        const editor = new Drawflow(container);
        editor.reroute = true;
        editor.start();
        return editor;
    }

    // Funzione utility per creare nodi
    function createNode(id, inputs, outputs, posX, posY, className, data, htmlContent) {
        const editor = window.DrawflowEditor;
        if (!editor) {
            console.error("Editor DrawFlow non disponibile");
            return null;
        }

        const nodeId = editor.addNode(
            id, inputs, outputs, posX, posY, className, data, htmlContent
        );
        return { id: nodeId, posX, posY };
    }

    // Calcola la posizione di un nuovo nodo
    function calculateNodePosition(posX, posY, height, resetAt = 500, offsetX = 200) {
        posY += height;

        if (posY > resetAt) {
            posY = 50;
            posX += offsetX;
        }

        return { posX, posY };
    }

    // Esponi API pubblica
    return {
        createEditor,
        createNode,
        calculateNodePosition
    };
})();