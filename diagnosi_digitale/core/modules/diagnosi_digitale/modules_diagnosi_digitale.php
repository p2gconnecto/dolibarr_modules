<?php
/* Copyright (C) 2010-2014	Regis Houssin	<regis.houssin@inodbox.com>
 * Copyright (C) 2014       Marcos García   <marcosgdf@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * or see https://www.gnu.org/
 */

/**
 *		\file       custom/diagnosi_digitale/core/modules/diagnosi_digitale/modules_diagnosi_digitale.php
 *      \ingroup    diagnosi_digitale
 *      \brief      File that contains custom classes for Diagnosi Digitale module
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonnumrefgenerator.class.php';

/**
 *  Custom class for Diagnosi Digitale numbering
 */
class ModeleNumRefDiagnosiDigitale extends CommonNumRefGenerator
{
    /**
     *  Return next value
     *
     *  @param   ?Societe		$objsoc		Object third party
     *  @param   Project		$project	Object project
     *  @return  string|int<-1,0>			Value if OK, 0 if KO
     */
    public function getNextValue($objsoc, $project)
    {
        // Custom logic for generating the next value
        return 'DD' . sprintf('%04d', $project->id);
    }

    /**
     *  Return an example of numbering
     *
     *  @return     string      Example
     */
    public function getExample()
    {
        return 'DD0001';
    }
}
