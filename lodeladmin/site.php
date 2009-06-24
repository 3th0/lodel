<?php
/**
 * Fichier site - G�re un site
 *
 * PHP version 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * Copyright (c) 2008, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * Copyright (c) 2009, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
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
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * @copyright 2009, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodeladmin
 */

define('backoffice-lodeladmin', true);
// gere un site. L'acces est reserve au niveau lodeladmin.
require 'lodelconfig.php';

try
{
	include 'auth.php';
	authenticate(LEVEL_ADMINLODEL, NORECORDURL);
	
	include 'class.siteManage.php';
	$website = new siteManage();
	C::set('installoption', C::get('installoption', 'cfg'));
	if(C::get('maintenance') > 0)
	{
		$website->maintenance();
	} 
	elseif (C::get('id') > 0) 
	{ // suppression et restauration
		if(C::get('delete'))
			$website->remove();
		elseif(C::get('restore'))
			$website->restore();
        
        	$result = $db->GetRow("
            SELECT * 
                FROM `$GLOBALS[tp]sites` 
                WHERE ".$website->get('critere')." AND (status>0 || status=-32)") 
                or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
        
        	C::mergeC($result); // preserve possible post datas
        	unset($result);
	}
	
    	// reinstall all the sites
	if (C::get('reinstall') == 'all') {
		$website->reinstall();
	}
	
    	$task = C::get('task');

	// ajoute ou edit
	if (C::get('edit') || C::get('maindefault')) {
		if($website->manageSite())
		{
			if (!preg_match($website->get('lodelhomere'),$website->get('versiondir'))) {
				trigger_error("ERROR: versiondir", E_USER_ERROR);
			}
			$task = 'createdb';
		}
	}

	// creation de la DataBase si besoin
	if (defined('DATABASE')) {
		$database = DATABASE;
	}
	$website->set('database', $database);
	
	if(C::get('name') && !C::get('dbname'))
        	C::set('dbname', (C::get('singledatabase', 'cfg') == 'on') ? $database : $database. '_'. C::get('name'));

	if ($task === 'createdb') 
	{
		$website->createDB();
		$task = 'createtables';
	}
	
	// creation des tables des sites
	if ($task === 'createtables') 
	{
		$website->createTables();
		$task = 'createdir';
	}
	
	// Creer le repertoire principale du site
	if ($task === 'createdir'){
		$website->createDir();
		$task = 'file';
	}

	// verifie la presence ou copie les fichiers necessaires
	// cherche dans le fichier install-file.dat les fichiers a copier
	if ($task === 'file') {
		$website->manageFiles();
	}
	
	// post-traitement
    	defined('INC_FUNC') || include 'func.php';
	postprocessing(C::getC());
	
	View::getView()->render('site');
}
catch(Exception $e)
{
	echo $e->getContent();
	exit();
}
?>