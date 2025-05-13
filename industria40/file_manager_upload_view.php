<?php
// This view is included by industria40index.php for file upload handling
// It expects $socid, $periziaid, $periziaid_sanitized, $upload_dir, $form_action_url, $langs, $modulepart to be available

// Aggiungi un messaggio di debug
dol_syslog("file_manager_upload_view.php: Script started", LOG_DEBUG);

// Action handler for file uploads
if ($action == 'upload_folder' && isset($_FILES['files'])) {
    dol_syslog("file_manager_upload_view.php: Processing file upload", LOG_DEBUG);
    dol_syslog("file_manager_upload_view.php: FILES data: " . print_r($_FILES, true), LOG_DEBUG);

    $total_files = count($_FILES['files']['name']);
    $files_uploaded = 0;
    $files_skipped = 0;
    $files_failed = 0;

    // Basic permission check
    if (!$user->rights->industria40->write && !$user->admin) {
        setEventMessages($langs->trans("NoPermissionToUpload"), null, 'errors');
    } else {
        // Ensure directories exist
        if (!is_dir($upload_dir)) {
            dol_syslog("file_manager_upload_view.php: Creating upload directory: " . $upload_dir, LOG_DEBUG);
            dol_mkdir($upload_dir);
        }

        // Process each file
        for ($i = 0; $i < $total_files; $i++) {
            if (!empty($_FILES['files']['name'][$i])) {
                $originalname = dol_sanitizeFileName($_FILES['files']['name'][$i]);
                $tmp_name = $_FILES['files']['tmp_name'][$i];
                $error_code = $_FILES['files']['error'][$i];

                if ($error_code == UPLOAD_ERR_OK && is_uploaded_file($tmp_name)) {
                    $destination = $upload_dir . '/' . $originalname;

                    // Check if file already exists
                    if (file_exists($destination)) {
                        $files_skipped++;
                        dol_syslog("file_manager_upload_view.php: File already exists: " . $destination, LOG_WARNING);
                        setEventMessages(sprintf($langs->trans("FileAlreadyExists"), $originalname), null, 'warnings');
                        continue;
                    }

                    // Move the file
                    if (move_uploaded_file($tmp_name, $destination)) {
                        dol_syslog("file_manager_upload_view.php: File uploaded successfully: " . $destination, LOG_INFO);
                        $files_uploaded++;
                    } else {
                        $files_failed++;
                        dol_syslog("file_manager_upload_view.php: Failed to move file: " . $tmp_name . " to " . $destination, LOG_ERR);
                        setEventMessages(sprintf($langs->trans("ErrorUploadingFile"), $originalname), null, 'errors');
                    }
                } else {
                    $files_failed++;
                    dol_syslog("file_manager_upload_view.php: Upload error for file: " . $originalname . " - Code: " . $error_code, LOG_ERR);
                    setEventMessages(sprintf($langs->trans("ErrorUploadingFile"), $originalname), null, 'errors');
                }
            }
        }

        // Display summary message
        if ($files_uploaded > 0) {
            setEventMessages(sprintf($langs->trans("FilesUploaded"), $files_uploaded), null, 'mesgs');
        }
        if ($files_skipped > 0) {
            setEventMessages(sprintf($langs->trans("FilesSkipped"), $files_skipped), null, 'warnings');
        }
        if ($files_failed > 0) {
            setEventMessages(sprintf($langs->trans("FilesFailed"), $files_failed), null, 'errors');
        }
    }
}

// Display the upload form
print '<div class="upload-container">';
print '<form id="uploadFilesForm" action="' . $form_action_url . '&view_mode=upload" method="POST" enctype="multipart/form-data">';
print '<input type="hidden" name="action" value="upload_folder">';
print '<input type="hidden" name="socid" value="' . $socid . '">';
print '<input type="hidden" name="periziaid" value="' . dol_escape_htmltag($periziaid_sanitized) . '">';

print '<div class="upload-box">';
print '<div id="uploadZone" class="upload-dropzone">';
print '<i class="fa fa-cloud-upload fa-3x"></i>';
print '<div class="upload-text">' . $langs->trans("DropFilesHere") . '</div>';
print '<div id="selectedFilesInfo" class="selected-files-info"></div>';
print '</div>';

print '<input type="file" name="files[]" id="uploadFilesInput" multiple style="display:none;">';

print '<div class="upload-actions">';
print '<button type="button" id="selectFilesBtn" class="button buttonplus">' . $langs->trans("SelectFiles") . '</button> ';
print '<button type="submit" class="button">' . $langs->trans("UploadFiles") . '</button>';
print '</div>';
print '</div>';
print '</form>';
print '</div>';

// Add JavaScript to handle the upload experience
print '<script>
jQuery(document).ready(function($) {
    var uploadZone = document.getElementById("uploadZone");
    var fileInput = document.getElementById("uploadFilesInput");
    var selectedFilesInfo = document.getElementById("selectedFilesInfo");

    // Handle click on upload zone
    uploadZone.addEventListener("click", function() {
        fileInput.click();
    });

    // Handle click on select files button
    document.getElementById("selectFilesBtn").addEventListener("click", function() {
        fileInput.click();
    });

    // Handle file selection
    fileInput.addEventListener("change", function() {
        updateSelectedFilesInfo(this.files);
    });

    // Handle drag and drop
    uploadZone.addEventListener("dragover", function(e) {
        e.preventDefault();
        uploadZone.classList.add("dragover");
    });

    uploadZone.addEventListener("dragleave", function() {
        uploadZone.classList.remove("dragover");
    });

    uploadZone.addEventListener("drop", function(e) {
        e.preventDefault();
        uploadZone.classList.remove("dragover");

        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            updateSelectedFilesInfo(e.dataTransfer.files);
        }
    });

    // Update selected files info
    function updateSelectedFilesInfo(files) {
        if (files.length === 0) {
            selectedFilesInfo.textContent = "";
            return;
        }

        var fileNames = [];
        var totalSize = 0;

        for (var i = 0; i < files.length; i++) {
            fileNames.push(files[i].name);
            totalSize += files[i].size;
        }

        var sizeStr = formatFileSize(totalSize);
        selectedFilesInfo.textContent = files.length + " ' . $langs->trans("FilesSelected") . ' (" + sizeStr + ")";

        // Add file list if not too many
        if (files.length <= 10) {
            var fileList = document.createElement("ul");
            fileList.className = "file-list";

            for (var i = 0; i < files.length; i++) {
                var li = document.createElement("li");
                li.textContent = files[i].name + " (' . $langs->trans("Size") . ': " + formatFileSize(files[i].size) + ")";
                fileList.appendChild(li);
            }

            selectedFilesInfo.appendChild(fileList);
        }
    }

    // Format file size
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + " B";
        else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + " KB";
        else if (bytes < 1073741824) return (bytes / 1048576).toFixed(1) + " MB";
        else return (bytes / 1073741824).toFixed(2) + " GB";
    }
});
</script>';

// Add CSS styles for the upload view
print '<style>
.upload-container {
    margin: 20px 0;
}
.upload-box {
    max-width: 800px;
    margin: 0 auto;
}
.upload-dropzone {
    border: 3px dashed #ccc;
    padding: 30px;
    text-align: center;
    background-color: #f9f9f9;
    margin-bottom: 20px;
    cursor: pointer;
    transition: all 0.3s;
}
.upload-dropzone.dragover {
    background-color: #e8f5ff;
    border-color: #2c5987;
}
.upload-text {
    margin: 10px 0;
    font-size: 1.2em;
    color: #666;
}
.selected-files-info {
    margin-top: 15px;
    font-weight: bold;
    color: #2c5987;
}
.file-list {
    text-align: left;
    margin: 10px auto;
    max-width: 80%;
    padding-left: 0;
    list-style-position: inside;
}
.file-list li {
    margin: 5px 0;
    font-size: 0.9em;
    color: #444;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.upload-actions {
    text-align: center;
}
</style>';
?>
