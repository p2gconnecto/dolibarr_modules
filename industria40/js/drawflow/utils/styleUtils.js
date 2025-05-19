/**
 * Utility per la gestione degli stili CSS
 */
const StyleUtils = (function() {
    // Definizione degli stili CSS
    const inlineDebugStyles = `
        .debug-node {
            min-width: 400px !important;
            width: 400px !important;
            background-color: #f8f9fa !important;
            border: 1px solid #dee2e6 !important;
            padding: 10px !important;
        }
        .debug-node-title {
            background-color: #e2f0ff !important;
            padding: 5px !important;
            margin-bottom: 10px !important;
            border-radius: 3px !important;
        }
        .debug-content {
            padding: 5px !important;
            max-height: 400px !important;
            overflow: auto !important;
        }
        .debug-description {
            font-size: 0.85em !important;
            margin-bottom: 10px !important;
            color: #6c757d !important;
        }
        .debug-value-item {
            margin-bottom: 10px !important;
            border-bottom: 1px solid #eee !important;
            padding-bottom: 5px !important;
        }
        .debug-value-key {
            font-weight: bold !important;
            color: #0066cc !important;
        }
        .debug-value-path {
            font-style: italic !important;
            color: #666 !important;
            margin-left: 5px !important;
        }
        .debug-value-source {
            font-size: 0.85em !important;
            color: #888 !important;
            float: right !important;
        }
        .debug-value-content {
            display: block !important;
            margin: 5px 0 !important;
            padding: 5px !important;
            background: #f5f5f5 !important;
            border-radius: 3px !important;
            white-space: pre-wrap !important;
            word-break: break-word !important;
            max-height: 200px !important;
            overflow: auto !important;
        }
        .debug-value-content.json-content {
            background: #f0f8ff !important;
        }
    `;

    const outputKeyStyles = `
        .output-key-label {
            font-size: 10px !important;
            position: absolute !important;
            right: 35px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            background-color: #e2f0ff !important;
            padding: 2px 5px !important;
            border-radius: 3px !important;
            color: #333 !important;
            white-space: nowrap !important;
            overflow: visible !important;
            text-overflow: ellipsis !important;
            max-width: 150px !important;
            z-index: 100 !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            border: 1px solid #c5d9f0 !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1) !important;
        }

        /* Stile al passaggio del mouse */
        .output-key-label:hover {
            max-width: none !important;
            z-index: 1000 !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
            background-color: #fff !important;
            opacity: 1 !important;
        }

        /* Assicurati che gli output abbiano position relative */
        .drawflow .output {
            position: relative !important;
        }

        /* Impedisci che gli output nascondano le etichette */
        .drawflow .output * {
            z-index: auto !important;
        }

        /* Stile per i numeri degli output */
        .output-number {
            position: absolute !important;
            right: 12px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            background-color: #6c757d !important;
            color: white !important;
            border-radius: 50% !important;
            width: 32px !important;
            height: 16px !important;
            font-size: 9px !important;
            line-height: 16px !important;
            text-align: center !important;
            z-index: 101 !important;
            pointer-events: none !important;
        }
    `;

    // Inizializza gli stili CSS
    function initStyles() {
        // Debug styles
        const debugStyleTag = document.getElementById('drawflow-debug-inline-styles');
        if (!debugStyleTag) {
            const style = document.createElement('style');
            style.id = 'drawflow-debug-inline-styles';
            style.textContent = inlineDebugStyles;
            document.head.appendChild(style);
        }

        // Output key styles
        const outputKeyStyleTag = document.getElementById('drawflow-output-key-styles');
        if (!outputKeyStyleTag) {
            const style = document.createElement('style');
            style.id = 'drawflow-output-key-styles';
            style.textContent = outputKeyStyles;
            document.head.appendChild(style);
        }
    }

    // Chiamata all'inizializzazione
    initStyles();

    // Esponi API pubblica
    return {
        inlineDebugStyles,
        outputKeyStyles
    };
})();