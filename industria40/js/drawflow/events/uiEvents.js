/**
 * Gestione degli eventi UI
 */
const UIEventsManager = (function() {
    /**
     * Configura i pulsanti e gli eventi dell'interfaccia utente
     * @param {Object} config - Configurazione globale
     * @param {Drawflow} editor - Istanza dell'editor Drawflow
     * @param {Object} currentMapping - Mapping corrente
     */
    function setupUIButtons(config, editor, currentMapping) {
        if (!editor) {
            console.error("Editor non fornito per la configurazione degli eventi UI");
            return;
        }

        console.log("Configurazione eventi UI");

        // Evento di salvataggio del mapping
        const saveButton = document.getElementById('save-mapping');
        if (saveButton) {
            saveButton.addEventListener('click', function(e) {
                e.preventDefault();
                saveMapping(config, editor, currentMapping);
            });
        }

        // Evento di caricamento del mapping
        const loadButton = document.getElementById('load-mapping');
        if (loadButton) {
            loadButton.addEventListener('click', function(e) {
                e.preventDefault();
                loadMapping(config, editor);
            });
        }

        // Evento di esportazione in DOCX
        const exportButton = document.getElementById('export-docx');
        if (exportButton) {
            exportButton.addEventListener('click', function(e) {
                e.preventDefault();
                exportToDocx(config, currentMapping);
            });
        }

        // Configurazione della casella di ricerca per filtrare i nodi
        const searchInput = document.getElementById('node-search');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                DOMUtils.updateNodeVisibility(this.value);
            });
        }

        // Gestione dei filtri per categoria di nodo
        document.querySelectorAll(".node-category").forEach(function(element) {
            // Assicurati che sia definito l'attributo data-type
            const type = element.getAttribute("data-type");
            if (!type) {
                console.warn("Elemento con classe node-category senza attributo data-type:", element);
                return;
            }

            // CORREZIONE: Controllo di sicurezza per window.DrawflowManager
            let nodeVisibility = {};
            try {
                if (window.DrawflowManager && typeof window.DrawflowManager.getNodeVisibility === 'function') {
                    nodeVisibility = window.DrawflowManager.getNodeVisibility();
                } else {
                    console.warn("DrawflowManager.getNodeVisibility non disponibile, uso valori predefiniti");
                    nodeVisibility = {
                        "file": true,
                        "ai": true,
                        "tag": true,
                        "aitag": true,
                        "docx": true,
                        "json-property": true,
                        "json-parent": true,
                        "debug": true
                    };
                }
            } catch (e) {
                console.error("Errore nell'accesso a DrawflowManager.getNodeVisibility:", e);
                nodeVisibility = { file: true, ai: true, tag: true, debug: true };
            }

            // Imposta lo stato iniziale
            if (nodeVisibility[type]) {
                element.classList.add("active");
            } else {
                element.classList.remove("active");
            }

            // Aggiungi gestore dell'evento click
            element.addEventListener("click", function() {
                const type = this.getAttribute("data-type");
                this.classList.toggle("active");

                // CORREZIONE: Controllo di sicurezza per setNodeVisibility
                try {
                    if (window.DrawflowManager && typeof window.DrawflowManager.setNodeVisibility === 'function') {
                        window.DrawflowManager.setNodeVisibility(type, this.classList.contains("active"));
                    } else {
                        console.warn("DrawflowManager.setNodeVisibility non disponibile");
                    }
                } catch (e) {
                    console.error("Errore nell'impostazione della visibilità:", e);
                }

                console.log(`Visibilità del tipo ${type} ora è: ${this.classList.contains("active")}`);
                DOMUtils.updateNodeVisibility(document.getElementById("node-search")?.value || "");
            });
        });
    }

    /**
     * Salva il mapping corrente
     * @param {Object} config - Configurazione globale
     * @param {Drawflow} editor - Istanza dell'editor Drawflow
     * @param {Object} currentMapping - Mapping corrente
     */
    function saveMapping(config, editor, currentMapping) {
        if (!config.saveUrl) {
            console.error("URL di salvataggio non configurato");
            alert("URL di salvataggio non configurato");
            return;
        }

        try {
            // Prepara i dati da inviare
            const data = {
                socid: config.socid,
                periziaid: config.periziaid_sanitized,
                data: {
                    drawflow: editor.export(),
                    mapping: currentMapping || {}
                }
            };

            console.log("Salvataggio mapping:", data);

            // Chiamata AJAX per salvare
            DataServices.saveMapping(config.saveUrl, data)
                .then(response => {
                    if (response.success) {
                        console.log("Mapping salvato con successo");
                        alert("Mapping salvato con successo");
                    } else {
                        console.error("Errore durante il salvataggio:", response.error);
                        alert("Errore durante il salvataggio: " + response.error);
                    }
                })
                .catch(error => {
                    console.error("Errore nella chiamata di salvataggio:", error);
                    alert("Errore nella chiamata di salvataggio");
                });
        } catch (e) {
            console.error("Errore nel salvataggio del mapping:", e);
            alert("Errore nel salvataggio del mapping: " + e.message);
        }
    }

    /**
     * Carica un mapping esistente
     * @param {Object} config - Configurazione globale
     * @param {Drawflow} editor - Istanza dell'editor Drawflow
     */
    function loadMapping(config, editor) {
        if (!config.loadUrl) {
            console.error("URL di caricamento non configurato");
            alert("URL di caricamento non configurato");
            return;
        }

        if (!confirm("Questa azione sovrascriverà il mapping corrente. Continuare?")) {
            return;
        }

        try {
            DataServices.loadMapping(config.loadUrl, config.socid, config.periziaid_sanitized)
                .then(response => {
                    if (response.success && response.mapping) {
                        console.log("Mapping caricato con successo:", response.mapping);

                        // Pulisci l'editor
                        editor.clear();

                        // Importa il drawflow salvato
                        if (response.mapping.drawflow) {
                            editor.import(response.mapping.drawflow);
                        }

                        // Aggiorna il mapping corrente
                        if (window.DrawflowManager.setCurrentMapping && response.mapping.mapping) {
                            window.DrawflowManager.setCurrentMapping(response.mapping.mapping);
                        }

                        alert("Mapping caricato con successo");
                    } else {
                        console.error("Errore durante il caricamento:", response.error);
                        alert("Errore durante il caricamento: " + (response.error || "Nessun mapping trovato"));
                    }
                })
                .catch(error => {
                    console.error("Errore nella chiamata di caricamento:", error);
                    alert("Errore nella chiamata di caricamento");
                });
        } catch (e) {
            console.error("Errore nel caricamento del mapping:", e);
            alert("Errore nel caricamento del mapping: " + e.message);
        }
    }

    /**
     * Esporta il mapping in un documento DOCX
     * @param {Object} config - Configurazione globale
     * @param {Object} currentMapping - Mapping corrente
     */
    function exportToDocx(config, currentMapping) {
        if (!config.exportUrl) {
            console.error("URL di esportazione non configurato");
            alert("URL di esportazione non configurato");
            return;
        }

        try {
            // Prepara i dati da inviare
            const data = {
                socid: config.socid,
                periziaid: config.periziaid_sanitized,
                data: {
                    mapping: currentMapping || {}
                }
            };

            console.log("Esportazione in DOCX:", data);

            // Verifica se ci sono mapping da esportare
            if (!currentMapping || Object.keys(currentMapping).length === 0) {
                alert("Nessun mapping definito da esportare");
                return;
            }

            // Esegui la richiesta per l'esportazione
            DataServices.exportToDocx(config.exportUrl, data);

        } catch (e) {
            console.error("Errore nell'esportazione in DOCX:", e);
            alert("Errore nell'esportazione in DOCX: " + e.message);
        }
    }

    // Esponi API pubblica
    return {
        setupUIButtons
    };
})();