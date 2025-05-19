/**
 * Utility per la gestione dei dati JSON
 */
const JsonUtils = (function() {
    // Verifica se una stringa è JSON valido
    function isValidJSON(str) {
        try {
            JSON.parse(str);
            return true;
        } catch (e) {
            return false;
        }
    }

    // Formatta un valore per la visualizzazione
    function formatValueForDisplay(value, maxLength = 25) {
        if (value === null) return '<span class="json-null">null</span>';
        if (value === undefined) return '<span class="json-undefined">undefined</span>';

        if (Array.isArray(value)) {
            return `<span class="json-array">[${value.slice(0, 3).join(', ')}${value.length > 3 ? '...' : ''}]</span>`;
        }

        switch (typeof value) {
            case 'string':
                return `<span class="json-string">"${value.length > maxLength ? value.substring(0, maxLength) + '...' : value}"</span>`;
            case 'number':
                return `<span class="json-number">${value}</span>`;
            case 'boolean':
                return `<span class="json-boolean">${value}</span>`;
            case 'object':
                return `<span class="json-object">{...}</span>`;
            default:
                return `<span class="json-object">${JSON.stringify(value).substring(0, maxLength)}...</span>`;
        }
    }

    // Genera HTML per visualizzare dati JSON
    function generateJsonHTML(jsonData, options = {}) {
        const { prefix = '', maxDisplayLength = 25, showClipboardScript = false } = options;
        let html = '<hr style="margin:5px 0;"><div class="json-values">';

        function processObject(obj, currentPrefix) {
            for (let key in obj) {
                if (obj.hasOwnProperty(key)) {
                    const fullKey = currentPrefix ? `${currentPrefix}.${key}` : key;
                    const value = obj[key];
                    const valueId = `value-${Math.random().toString(36).substr(2, 9)}`;

                    // Crea una rappresentazione del valore per la visualizzazione
                    const displayValue = formatValueForDisplay(value, maxDisplayLength);

                    html += `
                        <div class="json-value-item" title="Clicca per copiare">
                            <span class="json-key">${fullKey}:</span>
                            <span id="${valueId}" class="json-value"
                                  data-value="${encodeURIComponent(typeof value === 'object' ? JSON.stringify(value) : value)}"
                                  onclick="window.copyJsonValueToClipboard(this)">
                                ${displayValue}
                            </span>
                            <span class="copy-icon">📋</span>
                        </div>
                    `;

                    // Se è un oggetto, processa ricorsivamente
                    if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
                        processObject(value, fullKey);
                    }
                }
            }
        }

        processObject(jsonData, prefix);
        html += '</div>';

        // Aggiungi script solo se richiesto (per retrocompatibilità)
        if (showClipboardScript) {
            html += `
                <script>
                function copyToClipboard(element) {
                    window.copyJsonValueToClipboard(element);
                }
                </script>
            `;
        }

        return html;
    }

    // Estrai le chiavi da un oggetto JSON, inclusi percorsi annidate
    function extractKeys(obj, basePath = '', result = []) {
        for (let key in obj) {
            if (obj.hasOwnProperty(key)) {
                const currentPath = basePath ? `${basePath}.${key}` : key;
                const value = obj[key];

                // Aggiungi la chiave corrente
                result.push(currentPath);

                // Se è un oggetto, estrai ricorsivamente le sue chiavi
                if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
                    extractKeys(value, currentPath, result);
                }
            }
        }
        return result;
    }

    // Recupera un valore da un oggetto usando un percorso separato da punti
    function getValueByPath(obj, path) {
        try {
            if (!path) return obj;

            const parts = path.split('.');
            let value = obj;

            for (const part of parts) {
                if (value && typeof value === 'object') {
                    value = value[part];
                } else {
                    return undefined;
                }
            }

            return value;
        } catch (e) {
            console.error(`Errore nell'accesso al percorso ${path}:`, e);
            return undefined;
        }
    }

    // Esponi API pubblica
    return {
        isValidJSON,
        formatValueForDisplay,
        generateJsonHTML,
        extractKeys,
        getValueByPath
    };
})();