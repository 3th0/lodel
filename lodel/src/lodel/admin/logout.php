<?php
/**
 * Fichier de Logout
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
 * @package lodel/source/lodel/admin
 */

require 'siteconfig.php';
require 'class.errors.php';
set_error_handler(array('LodelException', 'exception_error_handler'));

// les niveaux d'erreur � afficher
error_reporting(E_ALL);

try
{
require 'auth.php';
authenticate(LEVEL_VISITOR | LEVEL_RESTRICTEDUSER);

$name = addslashes($_COOKIE[$sessionname]);
$url_retour = strip_tags($_GET['url_retour']);
$time = time()-1;
usemaindb();
$db->execute(lq("UPDATE #_MTP_session SET expire2='$time' WHERE name='$name'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
$db->execute(lq("DELETE FROM #_MTP_urlstack WHERE idsession='$idsession'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
setcookie($sessionname, "", $time,$urlroot);

if($url_retour)
	header('Location: '.$url_retour);
else
	header('Location: '.SITEROOT); // la norme ne supporte pas les chemins relatifs !!
}
catch(Exception $e)
{
	if(!headers_sent())
	{
		header("HTTP/1.0 403 Internal Error");
		header("Status: 403 Internal Error");
		header("Connection: Close");
	}
	echo $e->getContent();
	exit();
}
?>