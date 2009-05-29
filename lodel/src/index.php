<?php
/**
 * Fichier racine - porte d'entr�e principale du site
 *
 * Ce fichier permet de faire appel aux diff�rentes entit�s (documents), via leur id, leur
 * identifier (lien permanent). Il permet aussi d'appeler un template particulier (via l'argument
 * page=)
 * Voici des exemples d'utilisations
 * <code>
 * index.php?/histoire/france/charlemagne-le-pieux
 * index.php?id=48
 * index.php?page=rss20
 * index.php?do=view&idtype=2
 * </code>
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 *
 * Home page: http://www.lodel.org
 *
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Pierre-Alain Mignot
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel/source
 */

require 'siteconfig.php';

try
{
	//gestion de l'authentification
	include 'auth.php';
	authenticate();
    
	// record the url if logged
	if (C::get('visitor', 'lodeluser')) {
		recordurl();
	}

	if(!C::get('debugMode', 'cfg'))
	{
		if(View::getView()->renderIfCacheIsValid()) exit();
	}

    	$accepted_logic = array();
	$called_logic = null;

	if (!C::get('editor', 'lodeluser') && ($do = C::get('do'))) 
	{
		if ($do === 'edit' || $do === 'view') 
		{
			// check for the right to change this document
			if (!($idtype = C::get('idtype'))) {
				trigger_error('ERROR: idtype must be given', E_USER_ERROR);
			}
			if(!defined('INC_CONNECT')) include 'connect.php'; // init DB if not already done
			include 'dao.php';
			$vo = getDAO('types')->find("id='{$idtype}' and public>0 and status>0");
			if (!$vo) {
				trigger_error("ERROR: you are not allowed to add this kind of document", E_USER_ERROR);
			}
			unset($vo);
			$lodeluser['rights']  = LEVEL_EDITOR; // grant temporary
			$lodeluser['editor']  = 1;
			C::setUser($lodeluser);
            		$_REQUEST['clearcache'] = false;
            		C::set('nocache', false);
            		unset($lodeluser);
			$accepted_logic = array('entities_edition');
            		C::set('lo', 'entities_edition');
			$called_logic = 'entities_edition';
		} else {
			trigger_error('ERROR: unknown action', E_USER_ERROR);
		}
	}

	Controller::getController()->execute($accepted_logic, $called_logic);
	exit();
}
catch(Exception $e)
{
	echo $e->getContent();
	exit();
}
?>