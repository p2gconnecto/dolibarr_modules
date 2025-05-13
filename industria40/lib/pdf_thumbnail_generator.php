<?php
/**
 * PDF thumbnail generation utility
 */

/**
 * Generate thumbnail for a PDF file
 *
 * @param string $pdf_file_path Full path to the PDF file
 * @param string $thumbnail_path Full path where the thumbnail should be saved
 * @param int $width Desired width of the thumbnail (height will be proportional)
 * @param int $quality JPEG quality (0-100)
 * @return bool Success of thumbnail generation
 */
function generate_pdf_thumbnail($pdf_file_path, $thumbnail_path, $width = 800, $quality = 90) {
    // Create directory structure if it doesn't exist
    $thumbnail_dir = dirname($thumbnail_path);
    if (!is_dir($thumbnail_dir)) {
        if (!dol_mkdir($thumbnail_dir)) {
            writeToLog("Failed to create thumbnail directory: " . $thumbnail_dir);
            return false;
        }
    }

    // If thumbnail already exists, we're done
    if (file_exists($thumbnail_path)) {
        return true;
    }

    $thumbnail_created = false;

    // Method 1: Try using ImageMagick's convert command
    if (function_exists('exec')) {
        // Check if ImageMagick is available
        $convert_path = '';
        exec('which convert 2>/dev/null', $output, $return_var);
        if ($return_var === 0 && !empty($output[0])) {
            $convert_path = $output[0];
        }

        if (!empty($convert_path)) {
            // Use ImageMagick to convert first page of PDF to JPG
            $cmd = escapeshellcmd($convert_path) . ' -density 150 ' . escapeshellarg($pdf_file_path . '[0]') .
                  ' -quality ' . intval($quality) . ' -resize ' . intval($width) . 'x ' .
                  escapeshellarg($thumbnail_path) . ' 2>/dev/null';
            exec($cmd, $output, $return_var);

            if ($return_var === 0 && file_exists($thumbnail_path)) {
                writeToLog("PDF thumbnail created successfully using ImageMagick");
                return true;
            }
        }

        // Method 2: Try GhostScript
        $gs_path = '';
        exec('which gs 2>/dev/null', $output, $return_var);
        if ($return_var === 0 && !empty($output[0])) {
            $gs_path = $output[0];
        }

        if (!empty($gs_path)) {
            // Use GhostScript to convert first page of PDF to JPG
            $cmd = escapeshellcmd($gs_path) . ' -sDEVICE=jpeg -dTextAlphaBits=4 -dGraphicsAlphaBits=4 ' .
                  '-dFirstPage=1 -dLastPage=1 -r150 -dJPEGQ=' . intval($quality) . ' ' .
                  '-dBATCH -dNOPAUSE -dSAFER -sOutputFile=' . escapeshellarg($thumbnail_path) . ' ' .
                  escapeshellarg($pdf_file_path) . ' 2>/dev/null';
            exec($cmd, $output, $return_var);

            if ($return_var === 0 && file_exists($thumbnail_path)) {
                writeToLog("PDF thumbnail created successfully using GhostScript");
                return true;
            }
        }
    }

    // Method 3: Try PHP's Imagick extension
    if (extension_loaded('imagick')) {
        try {
            $imagick = new \Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($pdf_file_path . '[0]');
            $imagick->setImageFormat('jpg');
            $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality($quality);
            $imagick->thumbnailImage($width, 0); // Width fixed, height proportional
            $imagick->writeImage($thumbnail_path);
            $imagick->clear();
            $imagick->destroy();

            if (file_exists($thumbnail_path)) {
                writeToLog("PDF thumbnail created successfully using PHP Imagick extension");
                return true;
            }
        } catch (Exception $e) {
            writeToLog("Error creating thumbnail with PHP Imagick: " . $e->getMessage());
        }
    }

    // Method 4: Try Poppler's pdftoppm (commonly available on Linux systems)
    if (function_exists('exec')) {
        exec('which pdftoppm 2>/dev/null', $output, $return_var);
        if ($return_var === 0 && !empty($output[0])) {
            $pdftoppm_path = $output[0];

            // Create a temporary file
            $temp_file = tempnam(sys_get_temp_dir(), 'pdf_thumb') . '.jpg';

            $cmd = escapeshellcmd($pdftoppm_path) . ' -jpeg -f 1 -l 1 -scale-to ' . intval($width) . ' ' .
                  escapeshellarg($pdf_file_path) . ' ' . escapeshellarg(substr($temp_file, 0, -4)) . ' 2>/dev/null';
            exec($cmd, $output, $return_var);

            // pdftoppm adds -1.jpg to the output file
            $actual_temp_file = substr($temp_file, 0, -4) . '-1.jpg';

            if ($return_var === 0 && file_exists($actual_temp_file)) {
                // Copy the temp file to the desired location
                if (copy($actual_temp_file, $thumbnail_path)) {
                    unlink($actual_temp_file);
                    writeToLog("PDF thumbnail created successfully using pdftoppm");
                    return true;
                }
                unlink($actual_temp_file);
            }
        }
    }

    writeToLog("Failed to create thumbnail for PDF: no suitable conversion tool found");
    return false;
}

/**
 * Delete thumbnail for a PDF file
 *
 * @param string $pdf_file_path Full path to the PDF file or its base information
 * @param int $socid Society ID
 * @param string $periziaid_sanitized Sanitized perizia ID
 * @return bool Success of thumbnail deletion
 */
function delete_pdf_thumbnail($pdf_file_path, $socid = null, $periziaid_sanitized = null) {
    // If we have a full path, extract the filename
    $file_name = basename($pdf_file_path);

    // Handle two different usage patterns:
    // 1. Full path provided directly
    // 2. File name with separate socid and periziaid parameters
    if ($socid !== null && $periziaid_sanitized !== null) {
        // Construct the thumbnail path manually
        $thumbnail_dir = DOL_DATA_ROOT . '/industria40/thumbnails/' . $socid . '/' . $periziaid_sanitized;
        $thumbnail_path = $thumbnail_dir . '/thumb_' . pathinfo($file_name, PATHINFO_FILENAME) . '.jpg';
    } else {
        // Try to determine thumbnail path from the PDF path
        $pdf_dir = dirname($pdf_file_path);
        // Replace 'documents' with 'thumbnails' in the path
        $thumbnail_dir = str_replace('/documents/', '/thumbnails/', $pdf_dir);
        $thumbnail_path = $thumbnail_dir . '/thumb_' . pathinfo($file_name, PATHINFO_FILENAME) . '.jpg';
    }

    // Log the attempt
    writeToLog("Attempting to delete thumbnail for: " . $file_name);

    // Delete the thumbnail if it exists
    if (file_exists($thumbnail_path)) {
        if (@unlink($thumbnail_path)) {
            writeToLog("Successfully deleted thumbnail: " . $thumbnail_path);
            return true;
        } else {
            writeToLog("Failed to delete thumbnail (permission issue): " . $thumbnail_path);
            return false;
        }
    } else {
        writeToLog("No thumbnail found to delete at: " . $thumbnail_path);
        return false; // No thumbnail existed
    }
}
