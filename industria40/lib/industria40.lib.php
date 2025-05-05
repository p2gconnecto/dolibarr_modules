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
 * Prepare admin pages header
 *
 * @return array<array{string,string,string}>
 */
function industria40AdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("industria40@industria40");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/industria40/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/industria40/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'industria40@industria40');

	return $head;
}

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
