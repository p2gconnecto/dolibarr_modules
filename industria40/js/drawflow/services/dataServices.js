/**
 * Servizi per l'interazione con le API
 */
const DataServices = (function() {
    /**
     * Invia una richiesta POST generica
     * @param {string} url - URL della richiesta
     * @param {Object} data - Dati da inviare
     * @return {Promise} Promise con la risposta
     */
    async function postRequest(url, data) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data),
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`Errore HTTP: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error("Errore nella chiamata POST:", error);
            throw error;
        }
    }

    /**
     * Invia una richiesta GET generica
     * @param {string} url - URL della richiesta
     * @param {Object} params - Parametri della query string
     * @return {Promise} Promise con la risposta
     */
    async function getRequest(url, params = {}) {
        try {
            // Costruisci la query string dai parametri
            const queryString = Object.keys(params)
                .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`)
                .join('&');

            const fullUrl = queryString ? `${url}?${queryString}` : url;

            const response = await fetch(fullUrl, {
                method: 'GET',
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`Errore HTTP: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error("Errore nella chiamata GET:", error);
            throw error;
        }
    }

    /**
     * Salva il mapping del diagramma
     * @param {string} url - URL endpoint di salvataggio
     * @param {Object} data - Dati da salvare
     * @return {Promise} Promise con la risposta
     */
    async function saveMapping(url, data) {
        try {
            return await postRequest(url, data);
        } catch (error) {
            console.error("Errore nel salvataggio del mapping:", error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Carica un mapping esistente
     * @param {string} url - URL endpoint di caricamento
     * @param {number} socid - ID della società
     * @param {string} periziaid - ID della perizia
     * @return {Promise} Promise con la risposta
     */
    async function loadMapping(url, socid, periziaid) {
        try {
            return await getRequest(url, { socid, periziaid });
        } catch (error) {
            console.error("Errore nel caricamento del mapping:", error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Esporta in DOCX
     * @param {string} url - URL endpoint di esportazione
     * @param {Object} data - Dati da esportare
     */
    function exportToDocx(url, data) {
        try {
            // Crea un form nascosto per fare il submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.target = '_blank'; // Apre in una nuova tab

            // Crea un input nascosto per i dati
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'data';
            input.value = JSON.stringify(data);
            form.appendChild(input);

            // Aggiungi socid e periziaid
            const socidInput = document.createElement('input');
            socidInput.type = 'hidden';
            socidInput.name = 'socid';
            socidInput.value = data.socid;
            form.appendChild(socidInput);

            const periziaInput = document.createElement('input');
            periziaInput.type = 'hidden';
            periziaInput.name = 'periziaid';
            periziaInput.value = data.periziaid;
            form.appendChild(periziaInput);

            // Aggiungi il form al body, fai il submit e poi rimuovilo
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);

            return { success: true };
        } catch (error) {
            console.error("Errore nell'esportazione in DOCX:", error);
            return { success: false, error: error.message };
        }
    }

    // Esponi API pubblica
    return {
        saveMapping,
        loadMapping,
        exportToDocx,
        postRequest,
        getRequest
    };
})();