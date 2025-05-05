/**
 * JavaScript functions for Industria40 File Manager
 */

document.addEventListener('DOMContentLoaded', function() {
    // File selection indicator
    function setupFileSelectionIndicator() {
        const fileInputs = document.querySelectorAll("#fileInput, #folderInput");

        fileInputs.forEach(function(input) {
            input.addEventListener("change", function() {
                // Remove previous indicators
                document.querySelectorAll(".selectedfileinfo").forEach(function(info) {
                    info.remove();
                });

                const numFiles = this.files.length;
                if (numFiles > 0) {
                    const parent = this.parentNode;
                    const indicator = document.createElement('div');
                    indicator.className = 'selectedfileinfo';
                    indicator.textContent = numFiles + " " + (numFiles > 1 ? "files" : "file") + " selezionati";
                    parent.appendChild(indicator);
                }
            });
        });
    }

    // Improve zoom effect for images and icons
    function setupZoomEffects() {
        document.querySelectorAll(".zoom-container").forEach(function(container) {
            const element = container.querySelector("img, .pdf-thumbnail, .file-icon");

            if (!element) return;

            container.addEventListener("mouseenter", function() {
                const containerRect = container.getBoundingClientRect();
                const viewportWidth = window.innerWidth;
                const viewportHeight = window.innerHeight;

                // Adjust transform origin based on position
                if (containerRect.right + 200 > viewportWidth) {
                    element.style.transformOrigin = "center right";
                } else if (containerRect.left < 200) {
                    element.style.transformOrigin = "center left";
                }

                if (containerRect.bottom + 200 > viewportHeight) {
                    element.style.transformOrigin = "bottom center";
                } else if (containerRect.top < 200) {
                    element.style.transformOrigin = "top center";
                }
            });
        });
    }

    // Apply suggestion to rename field
    function applySuggestedName(fileName, suggestedName) {
        const inputField = document.querySelector('input[name="new_name_' + fileName + '"]');
        if (inputField) {
            inputField.value = suggestedName;
        }
    }

    // Apply suggested tag
    function applySuggestedTag(formId, tagValue) {
        const form = document.getElementById(formId);
        const select = form.querySelector('select[name="file_tag"]');

        if (select) {
            select.value = tagValue;
            form.submit();
        }
    }

    // Confirm file deletion
    function confirmDelete(message) {
        return confirm(message);
    }

    // Initial setup
    function init() {
        setupFileSelectionIndicator();
        setupZoomEffects();

        // Expose functions to global scope for inline event handlers
        window.applySuggestedName = applySuggestedName;
        window.applySuggestedTag = applySuggestedTag;
        window.confirmDelete = confirmDelete;
    }

    // Run initialization
    init();
});
