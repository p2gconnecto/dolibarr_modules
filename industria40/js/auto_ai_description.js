/**
 * Script per la generazione automatica delle descrizioni AI
 */

document.addEventListener('DOMContentLoaded', function() {
    // Controlla se siamo nella vista di gestione file
    if (window.location.href.indexOf('view_mode=manage') > -1) {
        console.log("Auto AI: Detected file manager view");

        // Cerca tutti i file che non hanno ancora descrizioni AI
        const fileItems = document.querySelectorAll('.file-item[data-has-ai-desc="0"]');
        console.log(`Auto AI: Found ${fileItems.length} files without AI description`);

        if (fileItems.length > 0) {
            processNextFile(fileItems, 0);
        }
    }
});

/**
 * Processa i file uno alla volta per evitare troppe richieste contemporanee
 */
function processNextFile(fileItems, index) {
    if (index >= fileItems.length) {
        console.log("Auto AI: Finished processing all files");
        return;
    }

    const fileItem = fileItems[index];
    const fileName = fileItem.getAttribute('data-filename');
    const socid = fileItem.getAttribute('data-socid');
    const periziaId = fileItem.getAttribute('data-perizia');

    console.log(`Auto AI: Processing file ${index+1}/${fileItems.length}: ${fileName}`);

    // Verifica se il file è un'immagine o PDF
    const fileExt = fileName.split('.').pop().toLowerCase();
    const supportedExt = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

    if (supportedExt.includes(fileExt)) {
        // Effettua la richiesta asincrona per generare la descrizione AI
        fetch(`${window.location.origin}/custom/industria40/async_ai_processor.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `aiai_action=generate&socid=${socid}&perizia_id=${periziaId}&file_name=${encodeURIComponent(fileName)}`
        })
        .then(response => response.json())
        .then(data => {
            console.log(`Auto AI: Result for ${fileName}:`, data);

            // Aggiorna lo stato del file nell'interfaccia
            if (data.status === 'success' || data.status === 'partial_success') {
                fileItem.setAttribute('data-has-ai-desc', '1');

                // Aggiungi un indicatore visivo che la descrizione è stata generata
                const aiIcon = document.createElement('span');
                aiIcon.className = 'ai-icon-success';
                aiIcon.innerHTML = '🤖';
                aiIcon.title = 'Descrizione AI generata';
                fileItem.querySelector('.file-name').appendChild(aiIcon);
            }

            // Passa al file successivo con un ritardo per evitare di sovraccaricare il server
            setTimeout(() => {
                processNextFile(fileItems, index + 1);
            }, 3000);
        })
        .catch(error => {
            console.error(`Auto AI: Error processing ${fileName}:`, error);
            // Passa al file successivo nonostante l'errore
            setTimeout(() => {
                processNextFile(fileItems, index + 1);
            }, 3000);
        });
    } else {
        // Non è un tipo di file supportato, passa al successivo
        console.log(`Auto AI: Skipping unsupported file type ${fileExt}`);
        processNextFile(fileItems, index + 1);
    }
}
