<?php
/* Copyright (C) 2025		SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    industria40/lib/industria40.lib.php
 * \ingroup industria40
 * \brief   Library files with common functions for Industria40
 */

/**
 * Prepare array of tabs for Industria40
 *
 * @param   Object  $object         Object related to tabs
 * @return  array                   Array of tabs to show
 */
function industria40AdminPrepareHead($object = null)
{
    global $langs, $conf;

    $langs->load("industria40@industria40");

    $h = 0;
    $head = array();

    // Scheda configurazione principale
    $head[$h][0] = dol_buildpath("/industria40/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Settings");
    $head[$h][2] = 'settings';
    $h++;

    // Aggiungi qui altre schede di amministrazione se necessario

    // Completato: restituisci le schede
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'industria40admin');

    return $head;
}

/**
 * Process a newly uploaded file
 *
 * @param string $file_path Full path to the file
 * @param int $socid Society ID
 * @param string $periziaid_sanitized Sanitized perizia ID
 * @return void
 */
function process_uploaded_file($file_path, $socid, $periziaid_sanitized) {
    global $conf, $db, $user;

    // Get file extension
    $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $file_name = basename($file_path);

    // Create file key for storing metadata
    $file_key = $socid . '_' . $periziaid_sanitized . '_' . $file_name;

    // Log the file upload
    writeToLog("Processing uploaded file: " . $file_name, $file_key);

    // Generate thumbnail for PDF files
    if ($file_extension == 'pdf') {
        require_once __DIR__ . '/pdf_thumbnail_generator.php';

        $thumbnail_dir = DOL_DATA_ROOT . '/industria40/thumbnails/' . $socid . '/' . $periziaid_sanitized;
        $thumbnail_path = $thumbnail_dir . '/thumb_' . pathinfo($file_name, PATHINFO_FILENAME) . '.jpg';

        if (generate_pdf_thumbnail($file_path, $thumbnail_path)) {
            writeToLog("Generated thumbnail for PDF: " . $file_name, $file_key);
        } else {
            writeToLog("Failed to generate thumbnail for PDF: " . $file_name, $file_key);
        }
    }

    // Add other processing steps as needed (OCR, AI analysis, etc.)
}
