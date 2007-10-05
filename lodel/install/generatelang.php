<?php
/**
 * Fichier utilitaire pour la g�n�ration des fichiers de traductions
 *
 * Ce script est � lancer en ligne de commande
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel/install
 */


require 'lodelconfig.php';
require 'connect.php';
require 'func.php';

$files = array("install.php",
	     "tpl/install-admin.html",
	     "tpl/install-bienvenue.html",
	     "tpl/install-closehtml.html",
	     "tpl/install-database.html",
	     "tpl/install-fin.html",
	     "tpl/install-home.html",
	     "tpl/install-htaccess.html",
	     "tpl/install-lodelconfig.html",
	     "tpl/install-mysql.html",
	     "tpl/install-openhtml.html",
	     "tpl/install-options.html",
	     "tpl/install-php.html",
	     "tpl/install-plateform.html",
	     "tpl/install-servoo.html",
	     "tpl/install-showlodelconfig.html");


// look for the files and create the tags
$GLOBALS['lodeluser']['admin']   = true;
$GLOBALS['lodeluser']['visitor'] = true;
$GLOBALS['lodeluser']['rights']  = 128;

foreach($files as $file) {
	$text = file_get_contents($file);
	preg_match_all("/\[@(\w+\.\w+)(|sprintf\(([^\]]+)\))?\]/", $text, $results, PREG_PATTERN_ORDER);
	foreach($results[1] as $tag) {
		list($group,$name)=explode(".", strtolower($tag));
		getlodeltext($name, $group, &$id, &$contents, &$status, $lang = '--');
	}
}

$dao = &getDAO('translations');
$daotexts = &getDAO('texts');

require 'view.php';

$vos = $dao->findMany("textgroups='interface'");
print_R($vos);
foreach($vos as $vo) {
	$texts = $daotexts->findMany("textgroup='install' AND lang='".$vo->lang."'");
	if (!$texts) continue;
	echo $vo->lang,"\n";
	$tags = array();
	foreach($texts as $text) {
		$tags[]=$text->textgroup.".".$text->name;
	}
	generateLangCache($vo->lang,"tpl/install-lang-".$vo->lang.".html",$tags);
}

?>
