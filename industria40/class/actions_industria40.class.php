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

        if (!isset($conf->industria40->dir_output) || empty($conf->industria40->dir_output)) {
            $conf->industria40->dir_output = DOL_DATA_ROOT . '/industria40';
        }

        if (!isset($conf->industria40->multidir_output) || empty($conf->industria40->multidir_output)) {
            $conf->industria40->multidir_output = array();
            $conf->industria40->multidir_output[$conf->entity] = $conf->industria40->dir_output;
        }

        // Ensure directory exists
        if (!is_dir($conf->industria40->dir_output)) {
            if (dol_mkdir($conf->industria40->dir_output) >= 0) {
                chmod($conf->industria40->dir_output, 0775);
            }
        }

        // Set result parameters
        $parameters['accessallowed'] = 1;  // Allow access to file
        $parameters['fullpath_original'] = $conf->industria40->dir_output . '/' . $parameters['original_file'];

        dol_syslog("actions_industria40.class.php: access allowed, fullpath_original = " . $parameters['fullpath_original'], LOG_DEBUG);

        return 1; // Replace standard behavior
    }
}
