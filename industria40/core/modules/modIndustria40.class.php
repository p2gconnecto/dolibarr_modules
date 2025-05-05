<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2023       Your Name               <your.email@example.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \defgroup   industria40     Module Industria40
 * \brief      Industria40 module descriptor.
 *
 * \file       htdocs/custom/industria40/core/modules/modIndustria40.class.php
 * \ingroup    industria40
 * \brief      Description and activation file for module Industria40
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module Industria40
 */
class modIndustria40 extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;
        $this->db = $db;

        // Id for module (must be unique).
        $this->numero = 436996;

        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'industria40';

        // Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
        $this->family = "other";

        // Module position in the family on 2 digits ('01', '10', '20', ...)
        $this->module_position = '90';

        // Gives the possibility for the module, to provide his own family info and position of this family
        $this->familyinfo = array('Your customized groups' => array('position' => '01', 'label' => $langs->trans("MyModuleFamily")));

        // Module label (no space allowed), used if translation string 'ModuleIndustria40Name' not found (Industria40 is name of module).
        $this->name = preg_replace('/^mod/i', '', get_class($this));

        // Module description, used if translation string 'ModuleIndustria40Desc' not found
        $this->description = "Industria 4.0 Management";
        // Used only if file README.md and README-LL.md not found.
        $this->descriptionlong = "Industria 4.0 management module for technical analysis and appraisal";

        // Author
        $this->editor_name = 'Your Company';
        $this->editor_url = 'https://www.example.com';

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
        $this->version = '1.0.0';

        // Keyword of module visible in Setup->Modules, Help and URLs
        $this->keywords = array('industria40', 'technical analysis', 'perizia');

        // Define module parts (triggers, login, substitutions, menus, css, etc...)
        $this->module_parts = array(
            'triggers' => 0,                // Set this to 1 if module has its own trigger directory
            'login' => 0,                   // Set this to 1 if module has its own login page
            'substitutions' => 0,           // Set this to 1 if module has its own substitution function file
            'models' => 0,                  // Set this to 1 if module has its own models directory for pdf documents
            'menus' => 0,                   // Set this to 1 if module has its own menus handler directory
            'theme' => 0,                   // Set this to 1 if module has its own theme directory
            'tpl' => 0,                     // Set this to 1 if module overrides template dir
            'barcode' => 0,                 // Set this to 1 if module wants to scan barcode
            'dir' => array('output' => 'custom/industria40/documents'), // Set this to relative path if module has its own document directory
            'moduleforexternal' => 0
        );

        // Data directories to create when module is enabled.
        $this->dirs = array(
            '/industria40/documents',
            '/industria40/generated'
        );

        // Config pages. Put here list of php page, stored into your module's directory.
        $this->config_page_url = array("setup.php@industria40");

        // Dependencies
        $this->hidden = false;                      // A condition to hide module
        $this->depends = array();                   // List of module class names as string that must be enabled if this module is enabled
        $this->requiredby = array();                // List of module ids to disable if this one is disabled
        $this->conflictwith = array();              // List of module class names as string this module is in conflict with
        $this->langfiles = array("industria40@industria40");

        // Constants
        $this->const = array();

        // Arrays to init labels of major actions
        $this->tabs = array();
        $this->tabs[] = array('data'=>'thirdparty:+industria40:Industria40:industria40@industria40:$user->rights->industria40->read:/custom/industria40/industria40index.php?socid=__ID__');

        // Boxes/Widgets
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $r = 0;

        // Permission ID (must be unique and starting from 1 for each module)
        $this->rights[$r][0] = $this->numero + $r + 1;
        // Permission label
        $this->rights[$r][1] = 'Read Industria 4.0 data';
        // Permission by default for new user (0/1)
        $this->rights[$r][3] = 1;
        // Permission for what (in Dolibarr code)
        $this->rights[$r][4] = 'read';
        // Permission for what level (in Dolibarr code)
        $this->rights[$r][5] = '';
        $r++;

        // Permission ID (must be unique and incrementing)
        $this->rights[$r][0] = $this->numero + $r + 1;
        // Permission label
        $this->rights[$r][1] = 'Create/modify Industria 4.0 data';
        // Permission by default for new user (0/1) - CHANGE THIS TO 1
        $this->rights[$r][3] = 1; // Changed from 0 to 1 to enable by default
        // Permission for what (in Dolibarr code)
        $this->rights[$r][4] = 'write';
        // Permission for what level (in Dolibarr code)
        $this->rights[$r][5] = '';
        $r++;

        // Permission ID (must be unique and incrementing)
        $this->rights[$r][0] = $this->numero + $r + 1;
        // Permission label
        $this->rights[$r][1] = 'Delete Industria 4.0 data';
        // Permission by default for new user (0/1)
        $this->rights[$r][3] = 0;
        // Permission for what (in Dolibarr code)
        $this->rights[$r][4] = 'delete';
        // Permission for what level (in Dolibarr code)
        $this->rights[$r][5] = '';
        $r++;

        // Main menu entries
        $this->menu = array();
        $r = 0;

        // Add main menu entry
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=tools',
            'type' => 'left',
            'titre' => 'Industria 4.0',
            'prefix' => img_picto('', 'title_generic.png@industria40', 'class="pictofixedwidth"'),
            'mainmenu' => 'tools',
            'leftmenu' => 'industria40',
            'url' => '/custom/industria40/page/list.php',
            'langs' => 'industria40@industria40',
            'position' => 100+$r,
            'enabled' => '$conf->industria40->enabled',
            'perms' => '$user->rights->industria40->read',
            'target' => '',
            'user' => 0,
        );

        // Add secondary menu entries
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=industria40',
            'type' => 'left',
            'titre' => 'List',
            'url' => '/custom/industria40/page/list.php',
            'langs' => 'industria40@industria40',
            'position' => 100+$r,
            'enabled' => '$conf->industria40->enabled',
            'perms' => '$user->rights->industria40->read',
            'target' => '',
            'user' => 0,
        );

        // Add secondary menu entry for creating new project
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=industria40',
            'type' => 'left',
            'titre' => 'New',
            'url' => '/custom/industria40/page/card.php?action=create',
            'langs' => 'industria40@industria40',
            'position' => 100+$r,
            'enabled' => '$conf->industria40->enabled',
            'perms' => '$user->rights->industria40->write',
            'target' => '',
            'user' => 0,
        );
    }

    /**
     * Function called when module is enabled.
     * The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * It also creates data directories
     *
     * @param string $options   Options when enabling module ('', 'noboxes')
     * @return int              1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        $result = $this->_load_tables('/industria40/sql/');
        if ($result < 0) {
            return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
        }

        // Permissions
        $this->remove($options);

        $sql = array();

        // Register the module for document management
        $this->const[] = array(
            'MAIN_MODULE_INDUSTRIA40_DOCUMENT_ROOT',
            'chaine',
            'DOL_DATA_ROOT/industria40',
            'Document root for Industria40 module',
            0,
            'current',
            0
        );

        // Aggiungi costanti necessarie per l'accesso ai documenti
        $this->const[] = array(
            'MAIN_UMASK',
            'chaine',
            '0666',
            'Permessi predefiniti per i file',
            0,
            'current',
            0
        );

        $this->const[] = array(
            'INDUSTRIA40_ALLOW_EXTERNAL_DOWNLOAD',
            'chaine',
            '1',
            'Permetti accesso ai documenti tramite document.php',
            0,
            'current',
            0
        );

        // Aggiorna la configurazione module_parts
        $this->module_parts = array(
            'triggers' => 0,                // Set this to 1 if module has its own trigger directory
            'login' => 0,                   // Set this to 1 if module has its own login page
            'substitutions' => 0,           // Set this to 1 if module has its own substitution function file
            'models' => 0,                  // Set this to 1 if module has its own models directory for pdf documents
            'menus' => 0,                   // Set this to 1 if module has its own menus handler directory
            'theme' => 0,                   // Set this to 1 if module has its own theme directory
            'tpl' => 0,                     // Set this to 1 if module overrides template dir
            'barcode' => 0,                 // Set this to 1 if module wants to scan barcode
            // Definizione corretta per il supporto documenti
            'dir' => array('output' => 'industria40'),
            'moduleforexternal' => 0,
            'document' => 1                 // Set to 1 to enable document management
        );

        $result = $this->_init($sql, $options);

        if ($result > 0) {
            // Crea le directory necessarie con i permessi corretti
            $dirs = array(
                DOL_DATA_ROOT.'/industria40',
                DOL_DATA_ROOT.'/industria40/documents',
                DOL_DATA_ROOT.'/industria40/thumbnails'
            );

            foreach ($dirs as $dir) {
                if (!is_dir($dir)) {
                    if (dol_mkdir($dir, 0775) > 0) {
                        // Cambia esplicitamente i permessi per assicurarsi che siano corretti
                        chmod($dir, 0775);
                        dol_syslog("modIndustria40: Created directory ".$dir, LOG_DEBUG);
                    }
                } else {
                    // Aggiorna i permessi delle directory esistenti
                    chmod($dir, 0775);
                }
            }
        }

        return $result;
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted
     *
     * @param string $options   Options when enabling module ('', 'noboxes')
     * @return int              1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
