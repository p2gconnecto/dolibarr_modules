/**
 * Modulo inizializzazione DrawflowManager
 */
const DrawflowInitializer = (function() {
    // Variabili condivise
    let editor;
    let nodesToConfigure = {};
    let currentMapping = {};
    let nodeVisibility = {
        file: true, ai: true, tag: true, aitag: true, docx: true
    };

    // Inizializza l'editor e processa le configurazioni
    function init(pendingConfigs) {
        return new Promise((resolve, reject) => {
            try {
                // Inizializza l'editor DrawFlow
                const container = document.getElementById("drawflow");
                if (!container) {
                    console.warn("Container #drawflow non trovato nel DOM");
                    // Creiamo un contenitore temporaneo per evitare errori
                    const tempContainer = document.createElement('div');
                    tempContainer.id = 'drawflow';
                    tempContainer.style.display = 'none';
                    document.body.appendChild(tempContainer);
                }

                // Crea l'editor DrawFlow
                editor = EditorManager.createEditor(container || document.getElementById("drawflow"));

                // Condividi l'editor con gli altri moduli
                window.DrawflowEditor = editor;

                // Segnala che DrawflowManager è pronto
                window.DrawflowManager.isReady = true;

                // Esponi l'API pubblica
                window.DrawflowManager = {
                    init: function(config) {
                        console.log("DrawflowManager.init() chiamato con configurazione:", config ? Object.keys(config) : null);
                        if (!config) {
                            console.error("Configurazione non valida fornita a DrawflowManager.init()");
                            return false;
                        }
                        return initializeDrawflow(config);
                    },
                    isReady: true,
                    getQueueLength: function() {
                        return 0;
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
                        return DebugNodesManager.updateDebugNodeHTML(nodeId);
                    },
                    clear: function() {
                        editor.clear();
                        currentMapping = {};
                    }
                };

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

                resolve();
            } catch (err) {
                console.error("Errore nell'inizializzazione: ", err);
                reject(err);
            }
        });
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
            FileNodesManager.createFileNodes(config.files_in_dir, config.ai_tags, config.ai_descriptions,
                                          config.socid, config.periziaid_sanitized, config.baseUrl);

            const aiTagCounts = AINodesManager.createAINodes(config.ai_descriptions, config.ai_tags,
                                                       config.socid, config.periziaid_sanitized, config.baseUrl);

            TagNodesManager.createTagNodes(config.available_tags);
            TagNodesManager.createAITagNodes(aiTagCounts);
            DocxNodesManager.createDocxNodes(config.docx_fields);
            DebugNodesManager.createDebugNodes(1, 10); // 1 nodo debug con 10 input

            // Configura gli eventi di Drawflow
            ConnectionEventsManager.setupDrawflowEvents(editor);

            // Configura i pulsanti dell'interfaccia utente
            UIEventsManager.setupUIButtons(config, editor, currentMapping);

            // Rendi visibili i nodi all'inizio
            DOMUtils.updateNodeVisibility();

            // Se c'è un loadUrl e autoLoad è true, carica automaticamente il mapping salvato
            if (config.loadUrl && config.autoLoad) {
                DataServices.loadMapping(config.loadUrl, config.socid, config.periziaid_sanitized)
                    .then(data => {
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

            return true;
        } catch (e) {
            console.error("Errore durante l'inizializzazione di DrawflowManager:", e);
            return false;
        }
    }

    // Esponi API pubblica
    return {
        init: init,
        getEditor: function() { return editor; },
        getNodesToConfig: function() { return nodesToConfigure; },
        getCurrentMapping: function() { return currentMapping; },
        getNodeVisibility: function() { return nodeVisibility; },
        setCurrentMapping: function(mapping) { currentMapping = mapping; }
    };
})();