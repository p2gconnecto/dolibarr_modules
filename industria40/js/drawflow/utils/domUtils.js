/**
 * Utility per la manipolazione DOM
 */
const DOMUtils = (function() {
    // Impedisci iniezioni XSS
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

    // Carica un'immagine come Data URL
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

    // Ottieni anteprima dei file peritzia
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

    // Osserva l'aggiunta di un nodo al DOM per applicare etichette agli output
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
        };

        // Prima prova con un timeout normale
        setTimeout(() => {
            try {
                const nodeElement = document.getElementById(`node-${nodeId}`);
                if (nodeElement) {
                    applyLabels(nodeElement, keys);
                } else {
                    // Se non lo trova, configura un observer
                    console.log(`Nodo ${nodeId} non trovato, configurazione observer...`);

                    const observer = new MutationObserver((mutations) => {
                        const nodeElement = document.getElementById(`node-${nodeId}`);
                        if (nodeElement) {
                            applyLabels(nodeElement, keys);
                            observer.disconnect();
                        }
                    });

                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });

                    // Termina l'observer dopo un timeout ragionevole
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

    // Funzione per aggiornare la visibilità dei nodi
    function updateNodeVisibility(search) {
        try {
            const editor = window.DrawflowEditor;
            if (!editor) return;

            const nodeVisibility = DrawflowInitializer.getNodeVisibility();
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

    // Funzione per ispezionare un nodo debug
    function inspectDebugNode(nodeId, editorInstance) {
        console.log("------------------- DEBUG NODE INSPECTION -------------------");
        // Usa l'editor passato come parametro o cerca di ottenerlo dall'oggetto globale
        const ed = editorInstance || window.DrawflowEditor;

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

    // Esponi API pubblica
    return {
        escapeHtml,
        loadImageAsDataURL,
        getPeriziaFileThumbnail,
        observeNode,
        updateNodeVisibility,
        inspectDebugNode
    };
})();