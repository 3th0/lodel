<?php
/**
 * Fichier racine de lodel/admin
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�ou
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
 * @author Sophie Malafosse
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel/source/lodel/admin
 */
require_once 'siteconfig.php';
require_once 'lang.php';
require_once 'auth.php';

if ($_GET['insert'] == 'mets') {
	// authentification avec pour niveau minimum administrateur d'un site
	authenticate(LEVEL_ADMIN);

	 // insertion du METS dans la base
        require_once ('mets_insert.php');
        $mets = new mets_insert();
        if ($partners = $mets->partners) {
        	foreach($partners as $partner) {
                	$dir_revues = $mets->get_revues_dir($partner['import_directory']);
                	if(is_array && !empty($dir_revues)) {
                        	foreach ($dir_revues as $dir) {
                                	$revue = array();
                                	$revue['partner_Lodel_id'] = $partner['Lodel_id'];
                                	$revue['directory'] = $dir;
                                	$revue['mets'] = $partner['mets_directory'];
                                	$revue['dc'] = $partner['dc_directory'];
					$revue['nom'] = $partner['nom'];
                                	$mets->parse_mets($revue); // ins�re les donn�es de la revue dans la base, � partir du METS
                                	$mets->parse_dc(); // mets � jour les donn�es avec le dublin core
                        	}
                	}
        	}
	} else {
		die(utf8_encode('Rien � ins�rer : aucun partenaire � la racine du site.'));
	}
} else {
	authenticate(LEVEL_VISITOR);
	if ($_GET['page']) { // call a special page (and template)
		$page = $_GET['page'];
	  	if (strlen($page) > 64 || preg_match("/[^a-zA-Z0-9_\/-]/", $page)) {
			die('invalid page');
		}
  	require 'view.php';
  	$view = &View::getView();
  	$view->renderCached($context, $page);
  	exit;
	}


	require 'controler.php';
	$authorized_logics = array('entrytypes', 'persontypes',
					'entries', 'persons',
					'tablefieldgroups', 'tablefields', 'indextablefields',
					'translations', 'texts',
					'usergroups', 'users',
					'types', 'classes',
					'options', 'optiongroups', 'useroptiongroups', 'servooconf',
					'internalstyles', 'characterstyles', 'entities_index',
					'filebrowser', 'xml', 'data');
	$Controler = new controler($authorized_logics);
}

function loop_classtypes($context, $funcname)
{
	global $db;
	foreach(array('entities', 'entries', 'persons') as $classtype) {
		$localcontext = $context;
		$localcontext['classtype'] = $classtype;
		$localcontext['title']     = getlodeltextcontents("classtype_$classtype", 'admin');
    call_user_func("code_do_$funcname", $localcontext);
  }
}
?>