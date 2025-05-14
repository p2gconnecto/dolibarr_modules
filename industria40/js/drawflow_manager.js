/**
 * Drawflow Manager - Gestione dei diagrammi per le perizie Industria 4.0
 * Versione refactorizzata
 */
document.addEventListener("DOMContentLoaded", function() {
    // Previeni inizializzazioni multiple
    if (window.DrawflowManagerInitialized) {
        console.log("DrawflowManager è già stato inizializzato, saltando...");
        return;
    }
    window.DrawflowManagerInitialized = true;

    // Lista di configurazioni in attesa
    let pendingConfigs = [];

    // Cache busting
    const CACHE_VERSION = new Date().getTime();

    // Funzione globale per copia negli appunti (evita duplicazione inline)
    window.copyJsonValueToClipboard = function(element) {
        const value = decodeURIComponent(element.getAttribute('data-value'));
        navigator.clipboard.writeText(value).then(() => {
            element.classList.add('copied');
            setTimeout(() => {
                element.classList.remove('copied');
            }, 1000);
        });
    };

    // Step 1: Crea subito un oggetto DrawflowManager vuoto ma funzionante
    window.DrawflowManager = {
        // Inizializza con funzione temporanea che salva la configurazione
        init: function(config) {
            console.log("DrawflowManager: memorizzazione configurazione per inizializzazione posticipata");
            if (!config) {
                console.error("Configurazione non valida fornita a DrawflowManager.init()");
                return false;
            }
            pendingConfigs.push(config);
            return true;
        },
        isReady: false,
        getQueueLength: function() {
            return pendingConfigs.length;
        }
    };

    // Funzioni per il caricamento risorse con cache busting
    function addCacheBuster(url) {
        const separator = url.includes('?') ? '&' : '?';
        return `${url}${separator}v=${CACHE_VERSION}`;
    }

    function loadResource(type, url, id = null) {
        return new Promise((resolve, reject) => {
            // Verifica se la risorsa è già caricata
            if (id) {
                const existingElement = document.getElementById(id);
                if (existingElement) {
                    console.log(`Risorsa ${url} già caricata con ID ${id}`);
                    return resolve();
                }
            } else if (type === 'css') {
                // Se non è specificato un ID, controlla comunque per i CSS
                const links = document.querySelectorAll('link[rel="stylesheet"]');
                for (let i = 0; i < links.length; i++) {
                    if (links[i].href.includes(url)) {
                        console.log(`CSS ${url} già caricato`);
                        return resolve();
                    }
                }
            }

            const bustUrl = addCacheBuster(url);
            let element;

            if (type === 'script') {
                element = document.createElement('script');
                element.src = bustUrl;
                element.async = true;
            } else if (type === 'css') {
                element = document.createElement('link');
                element.rel = 'stylesheet';
                element.href = bustUrl;
            }

            if (id) {
                element.id = id;
            }

            element.onload = () => resolve();
            element.onerror = () => reject(new Error(`Fallimento nel caricamento della risorsa: ${url}`));

            document.head.appendChild(element);
        });
    }

    // Carica risorse necessarie
    Promise.all([
        loadResource('css', '/custom/industria40/js/drawflow/drawflow.min.css', 'drawflow-lib-css'),
        loadResource('css', '/custom/industria40/css/drawflow_style.css', 'drawflow-main-styles'),
        loadResource('css', '/custom/industria40/css/drawflow_debug.css', 'drawflow-debug-styles')
    ])
    .then(() => {
        console.log('Risorse CSS caricate con successo (v=' + CACHE_VERSION + ')');
        return initDrawflow();
    })
    .then(() => {
        // Dopo che DrawflowManager è stato completamente inizializzato, processa le configurazioni in coda
        console.log("DrawflowManager completamente inizializzato, " +
                    pendingConfigs.length + " configurazioni in attesa");

        // Segnala che DrawflowManager è pronto
        window.DrawflowManager.isReady = true;

        // Processa tutte le configurazioni in attesa
        while (pendingConfigs.length > 0) {
            const config = pendingConfigs.shift();
            console.log("Processamento configurazione DrawflowManager in coda:",
                       config ? Object.keys(config) : "config non valida");
            initializeDrawflow(config);
        }

        // Richiama il callback di pronto
        if (typeof window.onDrawflowManagerReady === 'function') {
            console.log("Invocazione onDrawflowManagerReady");
            setTimeout(function() {
                try {
                    window.onDrawflowManagerReady();
                } catch (e) {
                    console.error("Errore in onDrawflowManagerReady:", e);
                }
            }, 0);
        }

        // Richiama tutti i callback in attesa
        if (window.DrawflowManagerCallbacks && window.DrawflowManagerCallbacks.length > 0) {
            console.log("Richiamo di " + window.DrawflowManagerCallbacks.length + " callback in attesa");
            const callbacks = [...window.DrawflowManagerCallbacks];
            window.DrawflowManagerCallbacks = [];

            setTimeout(function() {
                callbacks.forEach(callback => {
                    try {
                        callback();
                    } catch (e) {
                        console.error("Errore durante esecuzione callback:", e);
                    }
                });
            }, 0);
        }
    })
    .catch(error => {
        console.error('Errore nel caricamento delle risorse:', error);
    });

    // La funzione principale di inizializzazione
    function initDrawflow() {
        return new Promise((resolve, reject) => {
            try {
                // Inizializza l'editor
                var container = document.getElementById("drawflow");
                if (!container) {
                    console.warn("Container #drawflow non trovato nel DOM");
                    // Creiamo un contenitore temporaneo per evitare errori
                    container = document.createElement('div');
                    container.id = 'drawflow';
                    container.style.display = 'none';
                    document.body.appendChild(container);
                }

                var editor = new Drawflow(container);
                editor.reroute = true;
                editor.start();

                // Aggiungi questa mappa per tracciare i nodi con output multipli
                var nodesToConfigure = {};
                var currentMapping = {};
                var nodeVisibility = {
                    file: true, ai: true, tag: true, aitag: true, docx: true
                };

                // Funzione utile per evitare iniezioni HTML - SPOSTATA QUI
                function escapeHtml(str) {
                    if (typeof str !== 'string') {
                        return String(str);
                    }
                    return str
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                // Correzione della funzione updateDebugNodeHTML
                function updateDebugNodeHTML(nodeId) {
                    try {
                        console.log(`Aggiornamento HTML del nodo debug ${nodeId} iniziato`);

                        const node = editor.getNodeFromId(nodeId);
                        if (!node || !node.data || node.data.type !== 'debug') {
                            console.warn(`Nodo ${nodeId} non trovato o non è un nodo debug`);
                            return;
                        }

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

                        // CORREZIONE 3: Assicurati che i valori vengano visualizzati correttamente
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
                                <pre class="debug-value-content ${isJson ? 'json-content' : ''}">${escapeHtml(formattedValue)}</pre>
                            `;

                            fragment.appendChild(itemDiv);
                        });

                        // Aggiorna il DOM una sola volta
                        valuesContainer.innerHTML = '';
                        valuesContainer.appendChild(fragment);

                        // Debug finale
                        console.log(`HTML del nodo debug ${nodeId} aggiornato con ${Object.keys(values).length} valori`);
                        console.log("HTML finale:", valuesContainer.innerHTML);

                        // Aggiungi l'ispezione debug DENTRO la funzione dove nodeId è definito
                        setTimeout(() => inspectDebugNode(nodeId, editor), 100);
                    } catch (e) {
                        console.error(`Errore durante l'aggiornamento dell'HTML del nodo debug ${nodeId}:`, e);
                    }
                }

                // --------- FUNZIONI DI UTILITÀ ---------

                // Funzione unificata per calcolare il posizionamento dei nodi
                function calculateNodePosition(posX, posY, height, resetAt = 500, offsetX = 200) {
                    posY += height;

                    if (posY > resetAt) {
                        posY = 50;
                        posX += offsetX;
                    }

                    return { posX, posY };
                }

                // Funzione utility per creare nodi
                function createNode(id, inputs, outputs, posX, posY, className, data, htmlContent) {
                    editor.addNode(
                        id, inputs, outputs, posX, posY, className, data, htmlContent
                    );
                    return { id, posX, posY };
                }

                // Helper per verificare se una stringa è JSON valido
                function isValidJSON(str) {
                    try {
                        JSON.parse(str); // Assicurati che questa riga sia presente
                        return true;
                    } catch (e) {
                        return false;
                    }
                }

                // Funzione unificata per formattare valori JSON
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

                // Funzione per caricare un'immagine come Data URL
                function loadImageAsDataURL(url, callback) {
                    fetch(url)
                        .then(response => response.blob())
                        .then(blob => {
                            const reader = new FileReader();
                            reader.onloadend = () => callback(reader.result);
                            reader.readAsDataURL(blob);
                        })
                        .catch(error => {
                            console.error('Errore caricamento immagine:', error);
                            callback(null);
                        });
                }

                // Funzione unificata per generare HTML per visualizzare dati JSON
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

                // Funzione per ottenere thumbnail dai file delle perizie
                function getPeriziaFileThumbnail(filename, socid, periziaid_sanitized, baseUrl) {
                    const extension = filename.split(".").pop().toLowerCase();

                    if (["jpg", "jpeg", "png", "gif"].includes(extension)) {
                        const id = 'img-' + Math.random().toString(36).substr(2, 9);
                        const fileDir = "documents/" + socid + "/" + periziaid_sanitized + "/";
                        const encodedFilename = filename.replace(/_/g, "+");
                        const origUrl = `/document.php?modulepart=industria40&file=${fileDir}${encodedFilename}&entity=1`;

                        setTimeout(() => {
                            loadImageAsDataURL(origUrl, dataUrl => {
                                const img = document.getElementById(id);
                                if (img) {
                                    if (dataUrl) {
                                        img.src = dataUrl;
                                    } else {
                                        img.style.display = 'none';
                                    }
                                }
                            });
                        }, 100);

                        return `<div style="text-align: left;">
                                <img id="${id}" class="file-thumbnail original-image" alt="${filename}"
                                src="data:image/gif;base64,R0lGODlhEAAQAPIAAP///wAAAMLCwkJCQgAAAGJiYoKCgpKSkiH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAADMwi63P4wyklrE2MIOggZnAdOmGYJRbExwroUmcG2LmDEwnHQLVsYOd2mBzkYDAdKa+dIAAAh+QQJCgAAACwAAAAAEAAQAAADNAi63P5OjCEgG4QMu7DmikRxQlFUYDEZIGBMRVsaqHwctXXf7WEYB4Ag1xjihkMZsiUkKhIAIfkECQoAAAAsAAAAABAAEAAAAzYIujIjK8pByJDMlFYvBoVjHA70GU7xSUJhmKtwHPAKzLO9HMaoKwJZ7Rf8AYPDDzKpZBqfvwQAIfkECQoAAAAsAAAAABAAEAAAAzMIumIlK8oyhpHsnFZfhYumCYUhDAQxRIdhHBGqRoKw0R8DYlJd8z0fMDgsGo/IpHI5TAAAIfkECQoAAAAsAAAAABAAEAAAAzIIunInK0rnZBTwGPNMgQwmdsNgXGJUlIWEuR5oWUIpz8pAEAMe6TwfwyYsGo/IpFKSAAAh+QQJCgAAACwAAAAAEAAQAAADMwi6IMKQORfjdOe82p4wGccc4CEuQradylesojEMBgsUc2G7sDX3lQGBMLAJibufbSlKAAAh+QQJCgAAACwAAAAAEAAQAAADMgi63P7wCRHZnFVdmgHu2nFwlWCI3WGc3TSWhUFGxTAUkGCbtgENBMJAEJsxgMLWzpEAACH5BAkKAAAALAAAAAAQABAAAAMyCLrc/jDKSatlQtScKdceCAjDII7HcQ4EMTCpyrCuUBjCYRgHVtqlAiB1YhiCnlsRkAAAOwAAAAAAAAAAAA=="
                                style="float: left; margin-right: 10px;" />
                                </div>`;
                    } else if (extension === "pdf") {
                        return `<div class="pdf-thumbnail">
                                    <i class="fa fa-file-pdf"></i>
                                    <div style="font-size:10px;text-align:center;margin-top:5px;overflow:hidden;text-overflow:ellipsis;">
                                        ${filename.length > 20 ? filename.substring(0, 18) + '...' : filename}
                                    </div>
                                </div>`;
                    }

                    return "";
                }

                // Funzione per aggiornare la visibilità dei nodi
                function updateNodeVisibility(search) {
                    try {
                        const searchStr = search || "";
                        const searchLower = searchStr.toLowerCase();

                        // Verifica che editor e la sua struttura dati siano definiti
                        if (!editor || !editor.drawflow || !editor.drawflow.drawflow || !editor.drawflow.drawflow.Home || !editor.drawflow.drawflow.Home.data) {
                            console.warn("Dati di Drawflow non disponibili per l'aggiornamento della visibilità");
                            return;
                        }

                        const nodes = editor.drawflow.drawflow.Home.data;

                        Object.keys(nodes).forEach(nodeId => {
                            try {
                                const node = editor.getNodeFromId(nodeId);
                                if (!node) return;

                                // Cerca prima l'elemento nel DOM usando l'ID
                                let nodeElement = document.getElementById('node-' + nodeId);

                                // Se non lo troviamo, prova con node.html
                                if (!nodeElement && node.html) {
                                    nodeElement = node.html;
                                }

                                // Se ancora non troviamo l'elemento, saltiamo questo nodo
                                if (!nodeElement) {
                                    console.warn(`Elemento DOM non trovato per il nodo ${nodeId}`);
                                    return;
                                }

                                let shouldShow = false;

                                if (node.data) {
                                    const nodeType = node.data.type;

                                    // Mappa di funzioni di ricerca per tipo di nodo
                                    const searchFunctions = {
                                        'file': () => {
                                            const filename = node.data.filename || "";
                                            return filename.toLowerCase().includes(searchLower);
                                        },
                                        'ai': () => {
                                            const filename = node.data.filename || "";
                                            return filename.toLowerCase().includes(searchLower);
                                        },
                                        'tag': () => {
                                            const tagText = node.data.label || "";
                                            return tagText.toLowerCase().includes(searchLower);
                                        },
                                        'aitag': () => {
                                            const tagText = node.data.tagName || "";
                                            return tagText.toLowerCase().includes(searchLower);
                                        },
                                        'docx': () => {
                                            const field = node.data.field || "";
                                            return field.toLowerCase().includes(searchLower);
                                        },
                                        'json-property': () => {
                                            const key = node.data.key || "";
                                            return key.toLowerCase().includes(searchLower);
                                        },
                                        'json-parent': () => {
                                            const sourceFile = node.data.sourceFile || "";
                                            return sourceFile.toLowerCase().includes(searchLower);
                                        },
                                        'debug': () => {
                                            // I nodi debug sono sempre visibili
                                            return true;
                                        }
                                    };

                                    // Usa la funzione di ricerca appropriata per il tipo di nodo
                                    if (searchFunctions[nodeType]) {
                                        shouldShow = searchFunctions[nodeType]();
                                    }
                                }

                                // Se la stringa di ricerca è vuota, mostra tutti i nodi
                                if (searchLower === '') {
                                    shouldShow = true;
                                }

                                // Imposta la visibilità appropriata
                                if (shouldShow) {
                                    nodeElement.classList.remove('filtered-out');
                                } else {
                                    nodeElement.classList.add('filtered-out');
                                }
                            } catch (e) {
                                console.error(`Errore durante l'elaborazione del nodo ${nodeId}:`, e);
                            }
                        });
                    } catch (e) {
                        console.error("Errore durante l'aggiornamento della visibilità dei nodi:", e);
                    }
                }

                // --------- FUNZIONI PER CREARE NODI ---------

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

                        var thumbnailHtml = getPeriziaFileThumbnail(filename, socid, periziaid_sanitized, baseUrl);
                        //var aiTag = ai_tags[filename] ? `<div><span class="tag-badge">${ai_tags[filename]}</span></div>` : "";

                        // Crea nodo per questo file
                        createNode(
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
                        const newPos = calculateNodePosition(posX, posY, thumbnailHtml ? 170 : 120, 500, 250);
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

                                    createNode(
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

                                        createNode(
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
                                            <div class="json-value-display">${formatValueForDisplay(pair.value)}</div>
                                            <div class="json-source">Da: ${filename}</div>`
                                        );

                                        // Connetti automaticamente il nodo principale ai nodi delle proprietà
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

                // Funzione per creare i nodi AI (versione migliorata per supportare oggetti annidati)
                function createAINodes(ai_descriptions, ai_tags, socid, periziaid_sanitized, baseUrl) {
                    var posX = 350;
                    var posY = 50;
                    var aiTagCounts = {};

                    Object.keys(ai_descriptions).forEach(function(filename, index) {
                        const content = ai_descriptions[filename];
                        const isJsonContent = isValidJSON(content);
                        const thumbnailHtml = getPeriziaFileThumbnail(filename, socid, periziaid_sanitized, baseUrl);

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

                                // Funzione ricorsiva per estrarre le chiavi di output
                                function extractKeys(obj, basePath = '') {
                                    for (let key in obj) {
                                        if (obj.hasOwnProperty(key)) {
                                            const currentPath = basePath ? `${basePath}.${key}` : key;
                                            const value = obj[key];

                                            // Se è un oggetto, estraiamo tutte le sue proprietà come chiavi separate
                                            if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
                                                // Aggiungi l'oggetto stesso come chiave
                                                outputKeys.push(currentPath);

                                                // Quindi estrai ricorsivamente le sue proprietà
                                                extractKeys(value, currentPath);
                                            } else {
                                                outputKeys.push(currentPath);
                                            }

                                            // Genera una visualizzazione breve del valore
                                            const displayValue = typeof value === 'object'
                                                ? JSON.stringify(value).substring(0, 20) + '...'
                                                : String(value).substring(0, 20) + (String(value).length > 20 ? '...' : '');

                                            shortContent += `<strong>${currentPath}</strong>: ${displayValue}<br>`;
                                        }
                                    }
                                }

                                // Estrai tutte le chiavi, incluse quelle annidate
                                extractKeys(jsonData);

                                // Usa la funzione unificata per generare l'HTML JSON
                                shortContent += generateJsonHTML(jsonData, {
                                    showClipboardScript: true // Per mantenere compatibilità con codice esistente
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
                        // yurij
                        //<div class="ai-content" style="text-align: left;">${shortContent}</div>`;

                        const addedNodeId = editor.addNode(
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
                            nodesToConfigure[actualNodeId] = {
                                outputKeys: outputKeys
                            };

                            // CORREZIONE: Passa anche outputKeys come secondo parametro
                            observeNode(actualNodeId, outputKeys);
                        } else {
                            // Anche se non ci sono chiavi JSON, potresti comunque voler aggiungere etichette numeriche
                            observeNode(actualNodeId, []); // Passa un array vuoto anziché undefined
                        }

                        // Calcola la nuova posizione
                        const newPos = calculateNodePosition(posX, posY, thumbnailHtml ? 220 : 150, 500, 250);
                        posX = newPos.posX;
                        posY = newPos.posY;
                    });

                    return aiTagCounts;
                }

                // Funzione per creare i nodi tag
                function createTagNodes(available_tags) {
                    var posX = 700;
                    var posY = 50;

                    Object.keys(available_tags).forEach(function(tagId, index) {
                        var tag = available_tags[tagId];

                        createNode(
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
                        const newPos = calculateNodePosition(posX, posY, 100, 500, 200);
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
                        createNode(
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
                        const newPos = calculateNodePosition(posX, posY, 100, 500, 200);
                        posX = newPos.posX;
                        posY = newPos.posY;
                    });
                }

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
                        createNode(
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
                        const newPos = calculateNodePosition(posX, posY, 80, 500, 250);
                        posX = newPos.posX;
                        posY = newPos.posY;
                    });
                }

                // Funzione per creare nodi di debug/test
                function createDebugNodes(count = 1, inputsPerNode = 10) {
                    var posX = 800;
                    var posY = 50;

                    for (let i = 0; i < count; i++) {
                        // CORREZIONE: Assicurati che l'ID sia coerente
                        const nodeId = `debug-${i}`;
                        const nodeTitle = "Debug Node";

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

                        // HTML interno del nodo - CORREZIONE: usa classi invece di ID specifici
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
                        const newPos = calculateNodePosition(posX, posY, i === 0 ? 450 : 150, 500, 250);
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
                            if (typeof inspectDebugNode === 'function') {
                                // Ispeziona questo nodo
                                inspectDebugNode(anyDebugNode.id.replace('node-', ''), editor);
                            } else {
                                console.warn("Funzione inspectDebugNode non definita");
                            }
                        }
                    }, 500);
                }

                // --------- GESTIONE EVENTI ---------

                // Gestisci i filtri per categoria di nodo
                document.querySelectorAll(".node-category").forEach(function(element) {
                    element.addEventListener("click", function() {
                        var type = this.getAttribute("data-type");
                        this.classList.toggle("active");
                        nodeVisibility[type] = !nodeVisibility[type];
                        updateNodeVisibility();
                    });
                });

                // Funzione per aggiornare il mapping quando viene creata una connessione
                function updateMappingOnConnection(fromNode, toNode, connection) {
                    // Verifica che sia fromNode che toNode siano definiti
                    if (!fromNode || !toNode) {
                        console.warn("Nodi non definiti in updateMappingOnConnection:", fromNode, toNode, connection);
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

                    // Gestiamo i nodi di debug in modo più sicuro
                    if (toNode.data && toNode.data.type === 'debug') {
                        try {
                            console.log("Debug node connection - input_id:", connection.input_id,
                                      "input_index:", inputIndex,
                                      "output_id:", connection.output_id,
                                      "output_index:", outputIndex);

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
                                            try {
                                                let value = fromNode.data.jsonData;
                                                const parts = keyPath.split('.');

                                                // Log per debug del percorso
                                                console.log(`Estrazione valore da percorso: ${keyPath}`, parts);

                                                for (const part of parts) {
                                                    if (value && typeof value === 'object') {
                                                        value = value[part];
                                                        console.log(`  Sottoparte ${part} => `, typeof value, value);
                                                    } else {
                                                        throw new Error(`Percorso ${keyPath} interrotto a ${part}`);
                                                    }
                                                }

                                                valueToDisplay = typeof value === 'object' ?
                                                    JSON.stringify(value, null, 2) :
                                                    String(value);

                                                console.log(`Valore estratto per ${keyPath}:`, valueToDisplay);
                                            } catch (e) {
                                                valueToDisplay = `Errore nell'accesso al percorso ${keyPath}: ${e.message}`;
                                                console.error(valueToDisplay);
                                            }
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

                            // Assicurati che .values esista
                            if (!toNode.data.values) {
                                toNode.data.values = {};
                            }

                            // CORREZIONE: salva il valore usando inputIndex come chiave
                            toNode.data.values[inputIndex] = {
                                sourceNodeId: fromNode.id,
                                sourceNodeType: nodeType,
                                keyPath: keyPath,
                                value: valueToDisplay,
                                connectionId: connection.connection_id
                            };

                            console.log(`Debug node ${toNode.id} valori aggiornati:`, toNode.data.values);
                            console.log(`Debug node ${toNode.id} ricevuto valore per input ${inputIndex}:`, valueToDisplay);

                            // Aggiorna la visualizzazione HTML
                            updateDebugNodeHTML(toNode.id);
                        } catch (e) {
                            console.error("Errore durante l'aggiornamento del nodo debug:", e);
                        }
                    }
                    // Gestione mappatura normale per i nodi DOCX
                    else if (toNode.data && toNode.data.field) {
                        // Salviamo il mapping nel formato: { campo_docx: { sourceId, sourceType, value } }
                        if (fromNode.data) {
                            let mappedValue = null;
                            let keyPath = null;

                            // A seconda del tipo di nodo determiniamo quale valore mappare
                            if (fromNode.data.type === "ai" && fromNode.data.jsonData) {
                                const outputIndex = parseInt(connection.output_index) || 0;
                                if (fromNode.data.outputKeys && outputIndex < fromNode.data.outputKeys.length) {
                                    keyPath = fromNode.data.outputKeys[outputIndex];

                                    // Estrai il valore dall'oggetto JSON seguendo il percorso
                                    try {
                                        let value = fromNode.data.jsonData;
                                        const parts = keyPath.split('.');

                                        for (const part of parts) {
                                            if (value && typeof value === 'object') {
                                                value = value[part];
                                            } else {
                                                throw new Error(`Percorso ${keyPath} interrotto a ${part}`);
                                            }
                                        }

                                        mappedValue = value;
                                    } catch (e) {
                                        console.error(`Errore nell'estrazione del valore da ${keyPath}:`, e);
                                        mappedValue = null;
                                    }
                                }
                            } else if (fromNode.data.type === "tag") {
                                mappedValue = fromNode.data.label;
                            } else if (fromNode.data.type === "aitag") {
                                mappedValue = fromNode.data.tagName;
                            } else if (fromNode.data.type === "file") {
                                mappedValue = fromNode.data.filename;
                            } else if (fromNode.data.type === "json-property") {
                                mappedValue = fromNode.data.value;
                                keyPath = fromNode.data.key;
                            }

                            // Aggiorna il mapping
                            currentMapping[toNode.data.field] = {
                                sourceId: fromNode.id,
                                sourceType: fromNode.data.type,
                                keyPath: keyPath,
                                value: mappedValue
                            };

                            console.log(`Mapping aggiornato per ${toNode.data.field}:`, currentMapping[toNode.data.field]);
                        }
                    }
                }

                // Configura gli eventi di Drawflow per gestire le connessioni
                function setupDrawflowEvents(editor) {
                    // Gestisci creazione connessione
                    editor.on("connectionCreated", function(connection) {
                        var fromNode = editor.getNodeFromId(connection.output_id);
                        var toNode = editor.getNodeFromId(connection.input_id);
                        updateMappingOnConnection(fromNode, toNode, connection);
                    });

                    // Gestisci rimozione connessione
                    editor.on("connectionRemoved", function(connection) {
                        const toNode = editor.getNodeFromId(connection.input_id);

                        // Gestisce la rimozione della connessione per i nodi di debug
                        if (toNode && toNode.data && toNode.data.type === 'debug') {
                            // Trova quale input è stato disconnesso
                            const inputIndex = parseInt(connection.input_index) || 0;

                            // Rimuovi il valore memorizzato
                            if (toNode.data.values && toNode.data.values[inputIndex]) {
                                delete toNode.data.values[inputIndex];
                            }

                            // Aggiorna la visualizzazione HTML
                            updateDebugNodeHTML(toNode.id);
                        }
                        // Gestione normale per i nodi DOCX
                        else if (toNode && toNode.data && toNode.data.field) {
                            delete currentMapping[toNode.data.field];
                        }
                    });

                    // Altre gestioni di eventi
                    editor.on("nodeSelected", function(id) {
                        console.log("Nodo selezionato:", id);
                    });

                    editor.on("nodeUnselected", function() {
                        console.log("Nodo deselezionato");
                    });
                }

                // Implementazione corretta di setupUIButtons
                function setupUIButtons(config, editor, currentMapping) {
                    // Verifica che i pulsanti esistano prima di aggiungere event listener
                    const saveButton = document.getElementById("save-mapping");
                    if (saveButton) {
                        saveButton.addEventListener("click", function() {
                            var exportData = {
                                drawflow: editor.export(),
                                mapping: currentMapping
                            };

                            fetch(config.saveUrl, {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    socid: config.socid,
                                    periziaid: config.periziaid_sanitized,
                                    data: exportData
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert("Mapping salvato con successo!");
                                } else {
                                    alert("Errore durante il salvataggio: " + data.error);
                                }
                            })
                            .catch(error => {
                                console.error("Error:", error);
                                alert("Si è verificato un errore durante il salvataggio.");
                            });
                        });
                    }

                    // Altri pulsanti UI possono essere configurati qui
                    const loadButton = document.getElementById("load-mapping");
                    if (loadButton && config.loadUrl) {
                        loadButton.addEventListener("click", function() {
                            fetch(config.loadUrl + "?socid=" + config.socid + "&periziaid=" + config.periziaid_sanitized)
                                .then(response => response.json())
                                .then(data => { // <- Aggiunta la freccia qui
                                    if (data.success && data.data) {
                                        // Rimuovi tutti i nodi esistenti
                                        editor.clear();

                                        // Importa il diagramma salvato
                                        editor.import(data.data.drawflow);

                                        // Ripristina il mapping
                                        currentMapping = data.data.mapping || {};

                                        alert("Mapping caricato con successo!");
                                    } else {
                                        alert("Nessun mapping trovato o errore durante il caricamento.");
                                    }
                                })
                                .catch(error => {
                                    console.error("Error:", error);
                                    alert("Si è verificato un errore durante il caricamento.");
                                });
                        });
                    }

                    // Configura il campo di ricerca
                    const searchInput = document.getElementById("node-search");
                    if (searchInput) {
                        searchInput.addEventListener("input", function() {
                            updateNodeVisibility(this.value);
                        });
                    }
                }

                // Funzione principale per inizializzare Drawflow con configurazione
                function initializeDrawflow(config) {
                    if (!config) {
                        console.error("Configurazione mancante per initializeDrawflow");
                        return false;
                    }

                    // Normalizza la configurazione per garantire che tutti i campi siano presenti con valori predefiniti
                    config = {
                        files_in_dir: config.files_in_dir || {},
                        ai_tags: config.ai_tags || {},
                        ai_descriptions: config.ai_descriptions || {},
                        available_tags: config.available_tags || {},
                        docx_fields: Array.isArray(config.docx_fields) ? config.docx_fields : [],
                        socid: config.socid || 0,
                        periziaid_sanitized: config.periziaid_sanitized || "",
                        baseUrl: config.baseUrl || "",
                        saveUrl: config.saveUrl || "",
                        loadUrl: config.loadUrl || "",
                        exportUrl: config.exportUrl || "",
                        autoLoad: !!config.autoLoad
                    };

                    try {
                        // Crea i vari tipi di nodi
                        createFileNodes(config.files_in_dir, config.ai_tags, config.ai_descriptions, config.socid, config.periziaid_sanitized, config.baseUrl);
                        var aiTagCounts = createAINodes(config.ai_descriptions, config.ai_tags, config.socid, config.periziaid_sanitized, config.baseUrl);
                        createTagNodes(config.available_tags);
                        createAITagNodes(aiTagCounts);
                        createDocxNodes(config.docx_fields);

                        // Crea esplicitamente il nodo di debug - assicurati che questa riga sia presente
                        createDebugNodes(1, 10); // 1 nodo debug con 10 input

                        // Configura gli eventi di Drawflow
                        setupDrawflowEvents(editor);

                        // Configura i pulsanti dell'interfaccia utente
                        setupUIButtons(config, editor, currentMapping);

                        // Rendi visibili i nodi all'inizio
                        updateNodeVisibility();

                        // Se c'è un loadUrl e autoLoad è true, carica automaticamente il mapping salvato
                        if (config.loadUrl && config.autoLoad) {
                            fetch(config.loadUrl + "?socid=" + config.socid + "&periziaid=" + config.periziaid_sanitized)
                                .then(response => response.json())
                                .then (data => {
                                    if (data.success && data.data) {
                                        editor.import(data.data.drawflow);
                                        currentMapping = data.data.mapping || {};
                                        console.log("Mapping caricato automaticamente");
                                    }
                                })
                                .catch(error => {
                                    console.error("Errore caricamento automatico:", error);
                                });
                        }

                        // Debug - crea una connessione di test al nodo debug (rimuovi in produzione)
                        setTimeout(() => {
                            try {
                                console.log("Verifica nodi debug:");
                                // Verifica che il nodo debug esista
                                const nodes = editor.drawflow.drawflow.Home.data;
                                let debugNodeFound = false;

                                Object.keys(nodes).forEach(nodeId => {
                                    const node = editor.getNodeFromId(nodeId);
                                    if (node && node.data && node.data.type === 'debug') {
                                        debugNodeFound = true;
                                        console.log("Nodo debug trovato:", nodeId);

                                        // Verifica che l'elemento HTML esista
                                        const nodeElement = document.getElementById(`node-${nodeId}`);
                                        if (!nodeElement) {
                                            console.warn(`Elemento DOM per nodo debug ${nodeId} non trovato!`);
                                        } else {
                                            console.log(`Elemento DOM per nodo debug ${nodeId} trovato correttamente.`);
                                        }
                                    }
                                });

                                if (!debugNodeFound) {
                                    console.error("NESSUN NODO DEBUG TROVATO!");
                                }
                            } catch (e) {
                                console.error("Errore durante la verifica dei nodi debug:", e);
                            }
                        }, 1000);

                        // Verifica avanzata di debug
                        setTimeout(() => {
                            console.log("VERIFICA AVANZATA DEL NODO DEBUG:");

                            // Stampa tutti gli ID nel DOM per identificazione
                            const allIdsInDom = Array.from(document.querySelectorAll('[id]')).map(el => el.id);
                            console.log("Tutti gli ID nel DOM:", allIdsInDom);

                            // Stampa tutti i nodi Drawflow
                            const drawflowNodes = document.querySelectorAll('.drawflow-node');
                            console.log(`Trovati ${drawflowNodes.length} nodi Drawflow:`,
                                        Array.from(drawflowNodes).map(n => ({ id: n.id, class: n.className })));

                            // Ispeziona specificamente i nodi di tipo debug
                            const debugNodes = Array.from(drawflowNodes).filter(n => n.className.includes('debug-node'));
                            console.log(`Trovati ${debugNodes.length} nodi debug:`,
                                        debugNodes.map(n => ({ id: n.id, html: n.innerHTML.substring(0, 100) + '...' })));

                            // Cerca specificamente i container dei valori debug
                            const debugValueContainers = document.querySelectorAll('.debug-values');
                            console.log(`Trovati ${debugValueContainers.length} container dei valori debug:`,
                                        Array.from(debugValueContainers).map(c => ({
                                            id: c.id,
                                            parent: c.parentElement ? c.parentElement.id : 'nessun genitore',
                                            html: c.innerHTML.substring(0, 50) + '...'
                                        })));

                        }, 1000);

                        return true;
                    } catch (e) {
                        console.error("Errore durante l'inizializzazione di DrawflowManager:", e);
                        return false;
                    }
                }

                // Sostituzione della funzione init di DrawflowManager
                window.DrawflowManager = {
                    init: function(config) {
                        console.log("DrawflowManager.init() chiamato con configurazione:", config ? Object.keys(config) : null);

                        if (!config) {
                            console.error("Configurazione non valida fornita a DrawflowManager.init()");
                            return false;
                        }

                        // Se DrawflowManager non è ancora pronto, mettiamo in coda la configurazione
                        if (!window.DrawflowManager.isReady) {
                            console.log("DrawflowManager non è ancora pronto, configurazione messa in coda");
                            pendingConfigs.push(config);
                            return true;
                        }

                        return initializeDrawflow(config);
                    },
                    isReady: true,
                    getQueueLength: function() {
                        return pendingConfigs.length;
                    },
                    editor: editor,
                    exportData: function() {
                        return {
                            drawflow: editor.export(),
                            mapping: currentMapping
                        };
                    },
                    importData: function(data) {
                        if (data && data.drawflow) {
                            editor.import(data.drawflow);
                            currentMapping = data.mapping || {};
                            return true;
                        }
                        return false;
                    },
                    updateDebugNode: function(nodeId) {
                        return updateDebugNodeHTML(nodeId);
                    },
                    clear: function() {
                        editor.clear();
                        currentMapping = {};
                    }
                };

                // Risolvi la Promise dopo che tutto è stato configurato
                resolve();
            } catch (e) {
                console.error("Errore durante l'inizializzazione di Drawflow:", e);
                reject(e);
            }
        });
    }

    // All'inizio del documento, dopo DOMContentLoaded, pulisci la gestione degli stili CSS

    // Assicurati che lo stile CSS per i nodi debug sia presente - versione più precisa e completa
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

    const additionalStyles = `
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

    // CORREZIONE: usa additionalStyles che è già definito
    const outputKeyStyleTag = document.getElementById('drawflow-output-key-styles');
    if (outputKeyStyleTag) {
        outputKeyStyleTag.textContent = additionalStyles;
    } else {
        const style = document.createElement('style');
        style.id = 'drawflow-output-key-styles';
        style.textContent = additionalStyles;
        document.head.appendChild(style);
    }

    // Aggiungi questa funzione per verificare cosa sta succedendo
    function inspectDebugNode(nodeId, editorInstance) {
        console.log("------------------- DEBUG NODE INSPECTION -------------------");
        // Usa l'editor passato come parametro o cerca di ottenerlo dall'oggetto globale
        const ed = editorInstance || (window.DrawflowManager && window.DrawflowManager.editor);

        if (!ed) {
            console.error("Editor Drawflow non disponibile per l'ispezione");
            return;
        }

        const node = ed.getNodeFromId(nodeId);
        if (!node) {
            console.log(`Nodo ${nodeId} non esiste`);
            return;
        }

        console.log("Dati del nodo:", node.data);
        console.log("Valori memorizzati:", node.data.values);

        const nodeElement = document.getElementById('node-' + nodeId);
        if (!nodeElement) {
            console.log(`Elemento DOM per nodo ${nodeId} non trovato`);
            return;
        }

        console.log("Elemento DOM:", nodeElement);

        const valuesContainer = nodeElement.querySelector('.debug-values');
        if (!valuesContainer) {
            console.log("Container valori non trovato");
            return;
        }

        console.log("Container HTML:", valuesContainer.innerHTML);
        console.log("Contiene figli:", valuesContainer.childNodes.length);

        // Ispeziona le connessioni in arrivo
        const connections = ed.drawflow.drawflow.Home.data[nodeId].inputs || {};
        console.log("Connessioni in ingresso:", connections);

        // Verifica altre proprietà rilevanti
        console.log("Classi del contenitore:", valuesContainer.className);
        console.log("Visibilità del contenitore:", window.getComputedStyle(valuesContainer).display);
        console.log("------------------------------------------------------------");
    }

    // Definisci la funzione observeNode PRIMA del suo utilizzo in createAINodes
    function observeNode(nodeId, outputKeys) {
        console.log(`Impostazione observer per nodo ${nodeId}`);

        // Assicurati che outputKeys sia un array, anche se è undefined
        const keys = Array.isArray(outputKeys) ? outputKeys : [];

        if (!Array.isArray(outputKeys)) {
            console.warn(`outputKeys per il nodo ${nodeId} non è un array valido:`, outputKeys);
        }

        // Funzione per applicare le etichette una volta che il nodo è nel DOM
        const applyLabels = (nodeElement, outputKeys) => {
            if (!nodeElement) return;

            // Assicurati nuovamente che outputKeys sia un array
            const safeOutputKeys = Array.isArray(outputKeys) ? outputKeys : [];

            console.log(`Applicazione etichette al nodo ${nodeId} con ${safeOutputKeys.length} chiavi`);

            // Seleziona i contenitori di output - prova diverse strategie
            let outputElements = nodeElement.querySelectorAll('.output');

            if (!outputElements || outputElements.length === 0) {
                outputElements = nodeElement.querySelectorAll('[class*="output"]');
            }

            console.log(`Nodo AI ${nodeId}: trovati ${outputElements?.length || 0} elementi output`);

            if (!outputElements || outputElements.length === 0) {
                console.warn(`Nessun elemento output trovato per il nodo ${nodeId}`);
                return;
            }

            // Usa safeOutputKeys che garantisce di essere un array
            safeOutputKeys.forEach((key, idx) => {
                if (idx >= outputElements.length) {
                    console.warn(`Output ${idx} non trovato per il nodo ${nodeId}`);
                    return;
                }

                // Rimuovi etichette esistenti
                const existingLabels = outputElements[idx].querySelectorAll('.output-key-label');
                existingLabels.forEach(el => el.remove());

                // Crea il container dell'etichetta
                const labelContainer = document.createElement('div');
                labelContainer.className = 'output-key-label';
                labelContainer.title = key; // Aggiungi tooltip
                labelContainer.dataset.outputKey = key;
                labelContainer.dataset.outputIndex = idx;

                // Usa colori diversi per root vs proprietà annidate
                const isNestedProperty = key.includes('.');

                // Mostra in modo più leggibile le proprietà annidate
                if (isNestedProperty) {
                    const parts = key.split('.');
                    const lastPart = parts.pop();

                    labelContainer.innerHTML = `
                        <span class="output-path-parent">${parts.join('.')}</span>
                        <span class="output-path-property">.${lastPart}</span>
                    `;
                } else {
                    labelContainer.textContent = key;
                }

                // Stile migliorato direttamente nell'elemento
                labelContainer.style.cssText = `
                    position: absolute;
                    font-size: 10px;
                    padding: 2px 5px;
                    border: 1px solid ${isNestedProperty ? '#b8d0ff' : '#c3e6cb'};
                    right: 35px;
                    top: 50%;
                    transform: translateY(-50%);
                    z-index: 100;
                    white-space: nowrap;
                    max-width: 180px;
                    overflow: visible;
                    text-overflow: ellipsis;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
                    pointer-events: auto;
                `;

                // Assicurati che il contenitore dell'output sia posizionato relativamente
                outputElements[idx].style.position = 'relative';

                // Aggiungi l'etichetta all'output
                outputElements[idx].appendChild(labelContainer);

                console.log(`Etichetta aggiunta all'output ${idx} per nodo ${nodeId}: ${key}`);
            });

            // Aggiungi numerazione agli output per riferimento facile
            /*outputElements.forEach((el, idx) => {
                const numLabel = document.createElement('div');
                numLabel.className = 'output-number';
                // yurij001
                // parse jsons and use keys as numLabel.textContent
                numLabel.textContent = outputKeys[idx] ? outputKeys[idx] : ("Pippo-" + (idx + 1));
                el.appendChild(numLabel);
            });*/
        };

        // Prima prova con un timeout normale
        setTimeout(() => {
            try {
                const nodeElement = document.getElementById(`node-${nodeId}`);
                if (nodeElement) {
                    applyLabels(nodeElement, keys); // Usa keys invece di outputKeys
                } else {
                    // Se non lo trova, configura un observer
                    console.log(`Nodo ${nodeId} non trovato, configurazione observer...`);

                    const observer = new MutationObserver((mutations) => {
                        const nodeElement = document.getElementById(`node-${nodeId}`);
                        if (nodeElement) {
                            applyLabels(nodeElement, keys); // Usa keys invece di outputKeys
                            observer.disconnect();
                        }
                    });

                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });

                    // Termina l'observer dopo un timeout ragionevole se il nodo non viene mai trovato
                    setTimeout(() => {
                        observer.disconnect();
                        console.warn(`Timeout observer per nodo ${nodeId}`);
                    }, 5000);
                }
            } catch (e) {
                console.error(`Errore nell'aggiunta delle etichette agli output per nodo ${nodeId}:`, e);
            }
        }, 500);
    }

    // Debugger per rilevare elementi mancanti
    const debugStyleTag = document.getElementById('drawflow-debug-inline-styles');
    if (!debugStyleTag) {
        const style = document.createElement('style');
        style.id = 'drawflow-debug-inline-styles';
        style.textContent = inlineDebugStyles;
        document.head.appendChild(style);
    }

}); // <- Chiusura di document.addEventListener("DOMContentLoaded", function()
