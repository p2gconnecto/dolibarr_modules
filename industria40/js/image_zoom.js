/**
 * JavaScript for image zoom functionality in industria40 module
 */

document.addEventListener("DOMContentLoaded", function() {
    // Crea un elemento per l'immagine ingrandita
    const zoomedContainer = document.createElement("div");
    zoomedContainer.className = "zoomed-image";
    document.body.appendChild(zoomedContainer);

    // Crea il pulsante di chiusura (particolarmente utile per mobile)
    const closeButton = document.createElement("div");
    closeButton.className = "close-zoom";
    closeButton.textContent = "×";
    closeButton.setAttribute("aria-label", "Chiudi");
    document.body.appendChild(closeButton);

    // Gestione di tutte le immagini nei container di zoom
    const containers = document.querySelectorAll(".image-zoom-container");

    containers.forEach(function(container) {
        const img = container.querySelector("img");
        if (!img) return;

        // Copia l'immagine originale nel contenitore zoom
        const zoomedImg = document.createElement("img");
        zoomedImg.src = img.src;
        zoomedContainer.appendChild(zoomedImg);

        // Implementazione del toggle: primo click attiva zoom, secondo click disattiva
        let isZoomed = false;
        img.addEventListener("click", function() {
            if (!isZoomed) {
                zoomedContainer.style.display = "block";
                closeButton.style.display = "block";
                isZoomed = true;
            } else {
                zoomedContainer.style.display = "none";
                closeButton.style.display = "none";
                isZoomed = false;
            }
        });

        // Chiudi zoom al click sull'immagine ingrandita
        zoomedContainer.addEventListener("click", function() {
            zoomedContainer.style.display = "none";
            closeButton.style.display = "none";
            isZoomed = false;
        });

        // Chiudi zoom al click sul pulsante di chiusura
        closeButton.addEventListener("click", function() {
            zoomedContainer.style.display = "none";
            closeButton.style.display = "none";
            isZoomed = false;
        });

        // Previeni il bubbling degli eventi per permettere lo scroll
        zoomedImg.addEventListener("click", function(e) {
            e.stopPropagation();
        });

        // Consenti lo zoom con il movimento del mouse
        zoomedImg.addEventListener("mousemove", function(e) {
            const rect = this.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            // Calcola la posizione relativa del mouse sull'immagine
            const x = (mouseX / rect.width) * 100;
            const y = (mouseY / rect.height) * 100;

            // Applica il punto di origine della trasformazione
            this.style.transformOrigin = x + "% " + y + "%";
        });

        // Gestione dei tasti (ESC per chiudere)
        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape" && zoomedContainer.style.display === "block") {
                zoomedContainer.style.display = "none";
                closeButton.style.display = "none";
                isZoomed = false;
            }
        });
    });
});
