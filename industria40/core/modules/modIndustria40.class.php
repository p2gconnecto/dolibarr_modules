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
	public $module_parts = array(
		'triggers' => 0,
		'login' => 0,
		'substitutions' => 0,
		'models' => 0,
		'menus' => 0,
		'theme' => 0,
		'tpl' => 0,
		'barcode' => 0,
		'dir' => array('output' => 'industria40/documents'), // Percorso relativo per la directory dei documenti del modulo
		'moduleforexternal' => 0,
		'document' => 1 // Abilita la gestione documentale per il modulo
	);

	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;

		// Module configuration
		$this->numero = 100000; // Numero univoco del modulo (da cambiare)
		$this->rights_class = 'industria40'; // Classe per i permessi (nome del modulo)
		$this->family = "projects"; // Famiglia del modulo
		$this->module_position = 50; // Posizione del modulo nel menu

		// Module name (no space, no special chars, same as module directory name)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = $langs->trans("Industria40ModuleDescription"); // Descrizione del modulo
		$this->version = '1.0.0'; // Versione del modulo
		$this->editor_name = 'Your Name/Company'; // Nome dello sviluppatore
		$this->editor_url = 'https://yourwebsite.com'; // URL dello sviluppatore

		// Module parts (triggers, login, substitutions, models, menus, theme, tpl, barcode, dir)
		// Questa è già definita come proprietà della classe, assicurati che sia completa lì.
		// Se necessario, può essere modificata qui prima di parent::__construct().

		// Data directories to create when module is enabled.
		// These paths are relative to DOL_DATA_ROOT.
		$this->dirs = array(
			// La directory principale dei documenti (DOL_DATA_ROOT/industria40/documents)
			// dovrebbe essere gestita dal framework basandosi su $this->module_parts['dir']['output'].
			// Sottodirectory aggiuntive possono essere elencate qui se necessario.
			'/industria40/thumbnails', // Esempio per le miniature
			'/industria40/temp'        // Esempio per file temporanei
		);

		// Module constants
		$this->const = array();
		$this->const[] = array(
			'MAIN_MODULE_INDUSTRIA40_DOCUMENT_ROOT',
			'chaine',
			'DOL_DATA_ROOT/industria40/documents', // Valore letterale, DOL_DATA_ROOT sarà sostituito
			'Document root for Industria40 module',
			0,
			'current',
			0
		);
		$this->const[] = array(
			'INDUSTRIA40_ALLOW_EXTERNAL_DOWNLOAD',
			'chaine',
			'1',
			'Allow external download of documents',
			0,
			'current',
			0
		);


		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights[0][0] = $this->numero + 1;
		$this->rights[0][1] = $langs->trans('ReadIndustria40'); // Permesso di lettura
		$this->rights[0][3] = 1; // Attivo per impostazione predefinita
		$this->rights[0][4] = 'lire'; // Tipo di permesso (lettura). Usare $user->rights->industria40->lire per controllare questo permesso.

		$this->rights[1][0] = $this->numero + 2;
		$this->rights[1][1] = $langs->trans('WriteIndustria40'); // Permesso di scrittura
		$this->rights[1][3] = 0; // Non attivo per impostazione predefinita
		$this->rights[1][4] = 'ecrire'; // Tipo di permesso (scrittura). Usare $user->rights->industria40->ecrire per controllare questo permesso.

		// Menus
		$this->menus = array(); // Vedi hooks/admin.php per la gestione dei menu

		parent::__construct($db);
	}

	/**
	 * Init function. Called when module is enabled.
	 * The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return int 1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs, $db; // $db è disponibile qui

		// Chiama l'init del genitore per caricare costanti, ecc.
		$result = parent::init($options);

		if ($result > 0) {
			// Crea le directory necessarie con i permessi corretti.
			// La directory principale dei documenti del modulo (DOL_DATA_ROOT/industria40/documents)
			// dovrebbe essere creata dal framework basandosi su $this->module_parts['dir']['output']
			// e $this->module_parts['document'] = 1.
			// Tuttavia, una creazione esplicita qui può fungere da fallback.

			// Assicurati che la directory base del modulo esista
			if (!is_dir(DOL_DATA_ROOT.'/industria40')) {
				dol_mkdir(DOL_DATA_ROOT.'/industria40', dolibarr_get_default_PERM_DIRS());
			}
			// Assicurati che la directory principale dei documenti per il modulo esista
			if (!is_dir(DOL_DATA_ROOT.'/industria40/documents')) {
				dol_mkdir(DOL_DATA_ROOT.'/industria40/documents', dolibarr_get_default_PERM_DIRS());
			}

			// Crea altre directory definite nella proprietà $this->dirs
			// La logica di creazione delle directory in $this->dirs è gestita da parent::init()
			// ma possiamo aggiungere qui ulteriori directory specifiche se necessario.
			$additional_dirs_to_ensure = array(
				DOL_DATA_ROOT.'/industria40/thumbnails',
				DOL_DATA_ROOT.'/industria40/temp'
				// Aggiungi altre directory qui se non sono coperte da $this->dirs
				// o se richiedono una logica di creazione speciale.
			);

			foreach ($additional_dirs_to_ensure as $dir_path) {
				if (!is_dir($dir_path)) {
					dol_mkdir($dir_path, dolibarr_get_default_PERM_DIRS());
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
