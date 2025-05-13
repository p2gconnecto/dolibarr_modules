<?php
/* Copyright (C) 2025 SuperAdmin
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
 * \file    industria40/class/actions_industria40.class.php
 * \ingroup industria40
 * \brief   Hook for document access integration
 */

class ActionsIndustria40
{
    /**
     * @var DoliDB Database handler
     */
    public $db;

    /**
     * @var string[] Module parts
     */
    public $modulepart = array('industria40');

    /**
     * @var string[] Permissions
     */
    public $permission = array();

    /**
     * @var string Error message
     */
    public $error = '';

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * File/dir access hook
     *
     * @param array $parameters Parameters
     * @return int 0=Keep standard behavior, 1=Replace standard behavior with this class
     */
    public function fileAccess($parameters)
    {
        global $conf, $user, $langs;

        $modulepart = $parameters['modulepart'];

        // Only handle industria40 modulepart
        if ($modulepart != 'industria40') {
            return 0;
        }

        // Log the file access attempt
        dol_syslog("actions_industria40.class.php: FileAccess hook called for modulepart=" . $modulepart, LOG_DEBUG);
        dol_syslog("actions_industria40.class.php: Parameters: " . print_r($parameters, true), LOG_DEBUG);

        // Ensure proper configuration
        if (!isset($conf->industria40)) {
            $conf->industria40 = new stdClass();
        }

        // $conf->industria40->dir_output should now be DOL_DATA_ROOT . '/industria40/documents'
        // due to changes in init.inc.php / modIndustria40.class.php
        if (!isset($conf->industria40->dir_output) || empty($conf->industria40->dir_output)) {
            // Fallback, though init.inc.php should handle this
            $conf->industria40->dir_output = DOL_DATA_ROOT . '/industria40/documents';
        }

        if (!isset($conf->industria40->multidir_output) || empty($conf->industria40->multidir_output)) {
            $conf->industria40->multidir_output = array();
            // Ensure the entity specific path also points to the correct base
            $current_entity = isset($conf->entity) ? $conf->entity : 1;
            $conf->industria40->multidir_output[$current_entity] = $conf->industria40->dir_output;
        }

        // Ensure directory exists (points to .../industria40/documents)
        if (!is_dir($conf->industria40->dir_output)) {
            // Attempt to create .../industria40 first, then .../industria40/documents
            $base_module_dir = dirname($conf->industria40->dir_output); // Should be DOL_DATA_ROOT . '/industria40'
            if (!is_dir($base_module_dir)) {
                dol_mkdir($base_module_dir, 0775); // Create with 0775
            }
            if (dol_mkdir($conf->industria40->dir_output, 0775) >= 0) { // Create with 0775
                chmod($conf->industria40->dir_output, 0775);
            }
        }

        // Set result parameters
        // $parameters['original_file'] is now SOCID/PERIZIAID/FILENAME
        // $conf->industria40->dir_output is DOL_DATA_ROOT/industria40/documents
        // So, fullpath_original becomes DOL_DATA_ROOT/industria40/documents/SOCID/PERIZIAID/FILENAME
        $parameters['accessallowed'] = 1;  // Allow access to file
        $parameters['fullpath_original'] = $conf->industria40->dir_output . '/' . $parameters['original_file'];

        dol_syslog("actions_industria40.class.php: access allowed, fullpath_original = " . $parameters['fullpath_original'], LOG_DEBUG);

        return 1; // Replace standard behavior
    }
}
