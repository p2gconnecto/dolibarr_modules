/**
 * JavaScript per la gestione degli indicatori AI
 * Gestisce l'interazione con tooltip e descrizioni
 */

document.addEventListener("DOMContentLoaded", function() {
    // Gestisce i tooltip degli indicatori AI
    var indicators = document.querySelectorAll(".ai-description-indicator");
    indicators.forEach(function(indicator) {
        indicator.addEventListener("click", function(e) {
            e.stopPropagation();
            var tooltip = this.querySelector(".ai-description-tooltip");
            if (tooltip.style.display === "block") {
                tooltip.style.display = "none";
            } else {
                tooltip.style.display = "block";
            }
        });
    });

    // Chiude i tooltip quando si fa click altrove nella pagina
    document.addEventListener("click", function() {
        var tooltips = document.querySelectorAll(".ai-description-tooltip");
        tooltips.forEach(function(tooltip) {
            tooltip.style.display = "none";
        });
    });
});
