/**
 * JavaScript functions for Industria40 File Manager
 */

// Functions that need to be globally available for inline HTML event handlers
function confirmRename(originalNameEncoded) { // Parameter might be URL encoded from PHP
    console.log("confirmRename called for (encoded):", originalNameEncoded);
    // The input name attribute is 'new_name_' + originalNameEncoded (as set in manage_view.php)
    var inputElem = document.getElementsByName("new_name_" + originalNameEncoded)[0];
    if (!inputElem) {
        console.error("Input element for new name not found for:", originalNameEncoded);
        alert("Errore: campo per il nuovo nome non trovato.");
        return;
    }
    var newName = inputElem.value.trim();

    if (newName === originalNameEncoded) {
        alert("Nessuna modifica da applicare");
        return;
    }

    if (newName === "") {
        alert("Errore: Il nuovo nome non può essere vuoto.");
        return;
    }

    if (/[\\/:*?"<>|]/.test(newName)) {
        alert("Errore: Il nome contiene caratteri non validi.");
        return;
    }

    var formElement = document.getElementById("renameForm");
    if (!formElement) {
        console.error("Rename form (renameForm) not found!");
        alert("Errore: modulo di rinomina non trovato.");
        return;
    }

    // Remove any existing hidden input for rename_single_file to avoid duplicates if function is called multiple times without page reload
    var existingHiddenInput = formElement.querySelector('input[name="rename_single_file"]');
    if (existingHiddenInput) {
        existingHiddenInput.remove();
    }

    // Ensure originalName for the hidden input is the decoded version if it was encoded for the input field name
    // However, the PHP side expects the original name as it is on the filesystem for rename_single_file.
    // If originalNameEncoded was rawurlencode'd, PHP's GETPOST will urldecode it.
    // So, rename_single_file should receive the actual original name.
    // The key 'new_name_' + originalNameEncoded is what PHP uses to get the new value.
    // Let's assume originalNameEncoded is the actual filename (possibly with special chars)
    // and it's correctly handled by dol_escape_js and form submission.

    var hiddenInputOriginal = document.createElement("input");
    hiddenInputOriginal.type = "hidden";
    hiddenInputOriginal.name = "rename_single_file"; // This POST var tells PHP which file's new_name_... to use
    hiddenInputOriginal.value = decodeURIComponent(originalNameEncoded); // Ensure this is the plain filename
    formElement.appendChild(hiddenInputOriginal);

    console.log("Submitting renameForm to rename: " + decodeURIComponent(originalNameEncoded) + " to " + newName);
    formElement.submit();
}

// Apply suggestion to rename field
// Exposed to window if called by inline HTML, e.g., onclick="applySuggestedName(...)"
window.applySuggestedName = function(fileName, suggestedName) {
    const inputField = document.querySelector('input[name="new_name_' + fileName + '"]');
    if (inputField) {
        inputField.value = suggestedName;
    }
};

// Apply suggested tag
// Exposed to window if called by inline HTML, e.g., onclick="applySuggestedTag(...)"
window.applySuggestedTag = function(formId, tagValue) {
    const form = document.getElementById(formId); // formId will be like 'tagForm_FILE_KEY_AI'
    if (form) { // Check if form exists
        const select = form.querySelector('select[name="file_tag"]');
        if (select) {
            select.value = tagValue;
            // Instead of direct submit, let user confirm or have a general save button for the AI view if needed.
            // For now, direct submit is fine if that's the UX from manage_view.
            form.submit();
        } else {
            console.error("Tag select not found in form:", formId);
        }
    } else {
        console.error("Form not found for applySuggestedTag:", formId);
    }
};

// Handle file rename confirmation
function confirmFileRename(button, filename) {
    var form = button.closest('form');
    var inputElement = form.querySelector('input[name="new_name_' + filename + '"]');

    if (!inputElement || !inputElement.value.trim()) {
        alert('Il nuovo nome del file non può essere vuoto');
        return false;
    }

    return confirm('Confermi il cambio di nome da "' + filename + '" a "' + inputElement.value + '"?');
}

// Handle file deletion confirmation
function confirmFileDelete(filename) {
    return confirm('Sei sicuro di voler eliminare il file "' + filename + '"?');
}

// Initialize file manager components when document is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log("File Manager JS: Document ready.");

    // Get elements
    var actualFileInput = document.getElementById('actualFileInput');
    var customFileButton = document.getElementById('customFileButton');

    // Setup file input trigger only if the elements exist
    if (actualFileInput && customFileButton) {
        customFileButton.addEventListener('click', function() {
            actualFileInput.click();
        });

        actualFileInput.addEventListener('change', function() {
            // Handle file selection
            if (actualFileInput.files.length > 0) {
                document.getElementById('uploadFilesForm').submit();
            }
        });
        console.log("File Manager JS: File input setup complete.");
    } else {
        console.log("File Manager JS: Some file upload elements not found. This is normal in view modes that don't support uploads.");
    }
});

// Function to format file sizes
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

jQuery(document).ready(function ($) { // Pass $ to avoid conflicts
    console.log("File Manager JS: Document ready.");

    // These elements are primarily for the upload view.
    // They might not exist on other views. Add null checks.
    const dropZone = document.getElementById("dropZone");
    const actualFileInput = document.getElementById("actualFileInput");
    const fileSelectionInfo = document.getElementById("fileSelectionInfo");
    const addFilesButton = document.getElementById("addFilesButton");

    // console.log("DropZone element:", dropZone); // These logs can be noisy if elements are not on current page
    // console.log("ActualFileInput element:", actualFileInput);
    // console.log("FileSelectionInfo element:", fileSelectionInfo);
    // console.log("AddFilesButton element:", addFilesButton);

    // Helper function to trigger file input click
    function triggerFileInputClick() {
        if (actualFileInput) {
            console.log("Triggering file input click.");
            try {
                actualFileInput.value = ""; // Clear previous selection
                actualFileInput.click();
                console.log("actualFileInput.click() called.");
            } catch (e) {
                console.error("Error in triggerFileInputClick handler:", e);
            }
        } else {
            console.error("actualFileInput not found when trying to trigger click.");
        }
    }

    // Helper function to update selected files information
    function updateDisplayWithSelectedFiles(fileList) {
        if (fileSelectionInfo) { // Check if element exists
            console.log("updateDisplayWithSelectedFiles called with:", fileList);
            if (fileList && fileList.length > 0) {
                let message = "";
                if (fileList[0] && fileList[0].webkitRelativePath) {
                    // Folder selection
                    const topFolderName = fileList[0].webkitRelativePath.split('/')[0];
                    message = "Cartella selezionata: " + topFolderName + " (" + fileList.length + " " + (fileList.length > 1 ? "files" : "file") + ")";
                } else {
                    // File selection
                    message = fileList.length + " " + (fileList.length > 1 ? "files" : "file") + " selezionati";
                }
                console.log("Updating fileSelectionInfo text to:", message);
                fileSelectionInfo.textContent = message;
            } else {
                console.log("Clearing fileSelectionInfo text.");
                fileSelectionInfo.textContent = "";
            }
        }
        // else: console.warn("fileSelectionInfo element not found, cannot update display."); // Optional: uncomment if fileSelectionInfo is critical
    }

    // Setup for DropZone
    if (dropZone && actualFileInput) { // Check if elements exist
        console.log("File Manager JS: Setting up DropZone.");
        dropZone.addEventListener("click", triggerFileInputClick);

        dropZone.addEventListener("dragenter", function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.style.borderColor = "#2c5987";
            dropZone.style.backgroundColor = "#f0f8ff";
            console.log("DragEnter event");
        });

        dropZone.addEventListener("dragover", function (e) {
            e.preventDefault();
            e.stopPropagation();
        });

        dropZone.addEventListener("dragleave", function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.style.borderColor = "#ccc";
            dropZone.style.backgroundColor = "transparent";
            console.log("DragLeave event");
        });

        dropZone.addEventListener("drop", function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.style.borderColor = "#ccc";
            dropZone.style.backgroundColor = "transparent";
            console.log("Drop event triggered");

            const dataTransferFiles = e.dataTransfer.files;
            console.log("e.dataTransfer.files:", dataTransferFiles);

            if (dataTransferFiles && dataTransferFiles.length > 0) {
                try {
                    actualFileInput.value = "";
                    actualFileInput.files = dataTransferFiles;
                    console.log("actualFileInput.files after assignment:", actualFileInput.files);
                    updateDisplayWithSelectedFiles(actualFileInput.files); // Update display after drop
                } catch (assignError) {
                    console.error("Error assigning files to input:", assignError);
                    updateDisplayWithSelectedFiles(null);
                }
            } else {
                console.log("No files found in dataTransfer.files or dataTransferFiles is null/empty.");
                updateDisplayWithSelectedFiles(null);
            }
        });
        console.log("File Manager JS: Dropzone event listeners attached.");
    } else {
        if (!dropZone) console.warn("File Manager JS: DropZone element not found.");
        // actualFileInput warning is handled below if it's missing for other crucial parts
    }

    // Setup for AddFilesButton
    if (addFilesButton && actualFileInput) { // Check if elements exist
        console.log("File Manager JS: Setting up AddFilesButton.");
        addFilesButton.addEventListener("click", triggerFileInputClick);
    } else {
        if (!addFilesButton) console.warn("File Manager JS: AddFilesButton not found.");
        // actualFileInput warning is handled below
    }

    // Setup for actualFileInput 'change' event
    if (actualFileInput) { // Check if element exists
        console.log("File Manager JS: Setting up actualFileInput change listener.");
        actualFileInput.addEventListener("change", function () {
            console.log("ActualFileInput 'change' event triggered. Files:", this.files);
            updateDisplayWithSelectedFiles(this.files);
        });
    } else {
        // This is a critical issue if actualFileInput is missing for core functionality
        console.error("File Manager JS: actualFileInput not found. File selection and upload will not work.");
    }

    // Zoom effect logic (consolidated)
    // This should still work if .zoom-container is used consistently in manage_view.php
    $(".zoom-container").each(function() { // Use $ passed to ready()
        var $container = $(this); // Use $
        var $element = $container.find("img, .pdf-thumbnail, .file-icon");

        if (!$element.length) return; // Skip if no element found

        $container.on("mouseenter", function() {
            var containerRect = $container[0].getBoundingClientRect();
            // ElementRect should be fetched here, as its size might change or not be ready before
            var elementRect = $element[0].getBoundingClientRect();
            var viewportWidth = window.innerWidth;
            var viewportHeight = window.innerHeight;

            var zoomFactor = 3; // Assuming zoom scale is 3 (as per CSS transform: scale(3))
            var extraWidth = elementRect.width * (zoomFactor - 1);
            var extraHeight = elementRect.height * (zoomFactor - 1);

            var transformOriginX = "center";
            var transformOriginY = "center";

            // Adjust horizontal origin
            if (containerRect.right + extraWidth / 2 > viewportWidth) {
                transformOriginX = "right";
            } else if (containerRect.left - extraWidth / 2 < 0) {
                transformOriginX = "left";
            }

            // Adjust vertical origin
            if (containerRect.bottom + extraHeight / 2 > viewportHeight) {
                transformOriginY = "bottom";
            } else if (containerRect.top - extraHeight / 2 < 0) {
                transformOriginY = "top";
            }

            $element.css("transform-origin", transformOriginY + " " + transformOriginX);
        });
    });
    console.log("File Manager JS: Zoom logic attached.");

}); // End of jQuery(document).ready()

console.log("File Manager JS: Script loaded and parsed completely.");
