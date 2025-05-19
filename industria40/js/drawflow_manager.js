/**
 * Drawflow Manager - Gestione dei diagrammi per le perizie Industria 4.0
 * Versione modularizzata
 */
document.addEventListener("DOMContentLoaded", function() {
    // Previeni inizializzazioni multiple
    if (window.DrawflowManagerInitialized) {
        console.log("DrawflowManager è già stato inizializzato, saltando...");
        return;
    }
    window.DrawflowManagerInitialized = true;

    // Carica tutti i moduli necessari
    const modules = [
        'core/init.js',
        'core/editor.js',
        'utils/domUtils.js',
        'utils/styleUtils.js',
        'utils/jsonUtils.js',
        'nodes/fileNodes.js',
        'nodes/aiNodes.js',
        'nodes/tagNodes.js',
        'nodes/docxNodes.js',
        'nodes/debugNodes.js',
        'events/connectionEvents.js',
        'events/uiEvents.js',
        'services/dataServices.js'
    ];

    // Cache busting
    const CACHE_VERSION = new Date().getTime();

    // Lista di configurazioni in attesa
    let pendingConfigs = [];

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

    // Funzione per aggiungere cache busting agli URL
    function addCacheBuster(url) {
        const separator = url.includes('?') ? '&' : '?';
        return `${url}${separator}v=${CACHE_VERSION}`;
    }

    // Carica un modulo JavaScript
    function loadModule(modulePath) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            const fullPath = `/custom/industria40/js/drawflow/${modulePath}`;
            script.src = addCacheBuster(fullPath);
            script.onload = () => resolve();
            script.onerror = () => reject(new Error(`Fallimento nel caricamento del modulo: ${modulePath}`));
            document.head.appendChild(script);
        });
    }

    // Carica tutti i moduli in sequenza
    Promise.all([
        ...modules.map(module => loadModule(module)),
        loadResource('css', '/custom/industria40/js/drawflow/drawflow.min.css', 'drawflow-lib-css'),
        loadResource('css', '/custom/industria40/css/drawflow_style.css', 'drawflow-main-styles'),
        loadResource('css', '/custom/industria40/css/drawflow_debug.css', 'drawflow-debug-styles')
    ])
    .then(() => {
        console.log('Tutti i moduli e risorse CSS caricate con successo (v=' + CACHE_VERSION + ')');
        return DrawflowInitializer.init(pendingConfigs);
    })
    .catch(error => {
        console.error('Errore nel caricamento dei moduli:', error);
    });

    // Funzione generica per caricare risorse CSS
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

            if (type === 'css') {
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
});
