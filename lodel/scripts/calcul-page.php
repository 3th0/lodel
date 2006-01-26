<?php
/**
 * Fichier utilitaire pour g�rer le calcul des pages templates
 *
 * PHP version 4
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno C�nou, Jean Lamy
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno C�nou, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */

require_once 'func.php';

/**
 * Fonction de calcul d'une page
 *
 * Cette fonction sort de l'utf-8 par d�faut. Sinon c'est de l'iso-latin1 (m�thode un peu
 * dictatoriale)
 *
 * NOTA : le $ret ne sert a rien, mais s'il n'est pas la, la version de php n'aime pas (4.3.x):
 * bug eratique.
 *
 */
function calcul_page(&$context, $base, $cache_rep = '', $base_rep = 'tpl/')
{
	global $home, $format;
	if ($_REQUEST['clearcache'])	{
		require_once 'cachefunc.php';
		clearcache();
		$_REQUEST['clearcache'] = false; // to avoid to erase the CACHE again
	}

	if ($format && !preg_match("/\W/", $format)) {
		$base .= "_$format";
	}
	$format = ''; // en cas de nouvel appel a calcul_page

	$template_cache = $cache_rep. "CACHE/tpl_$base.php";
	$base = $base_rep. $base. '.html';
	if (!file_exists($base)) {
		die("<code><strong>Error!</strong>  The <span style=\"border-bottom : 1px dotted black\">$base</span> template does not exist.</code>");
	}

	$template_time = myfilemtime($template_cache);
	if (($template_time <= myfilemtime($base)))	{
		if ($GLOBALS['lodeluser']['admin']) {
			$context['templatesrecompiles'] .= "$base | ";
		}
		if (!defined("TOINCLUDE")) {
			define("TOINCLUDE", $home);
		}

		require_once 'lodelparser.php';
		$parser = new LodelParser;
		$parser->parse($base, $template_cache);
	}

	require_once 'connect.php';
	// execute le template php
	require_once 'textfunc.php';
	if ($GLOBALS['showhtml'] && $GLOBALS['lodeluser']['visitor'])	{
		ob_start();
		require $template_cache;
		$content = ob_get_contents();
		ob_end_clean();
		require_once 'showhtml.php';
		echo _indent_xhtml(show_html($content));
		return;
	}
	require_once 'loops.php';

	if ($context['charset'] == 'utf-8')	{ // utf-8 c'est le charset natif, donc on sort directement la chaine.
		#$start = microtime();
		ob_start();
		require $template_cache;
		$contents = ob_get_contents();
		ob_end_clean();
		echo _indent_xhtml($contents);
		#$end = microtime();
		#echo "temps : ". ($end - $start);
	}
	else
	{
		// isolatin est l'autre charset par defaut
		ob_start();
		require $template_cache;
		$contents = ob_get_contents();
		ob_end_clean();
		echo _indent_xhtml(utf8_decode($contents));
	}
}

/**
 *  Insertion d'un template dans le context
 *
 * @param array $context le context
 * @param string $filename le nom du fichier template
 */
function insert_template($context, $filename)
{
	if (file_exists("tpl/$filename". ".html")) {
		calcul_page($context, $filename);
	}	elseif (file_exists($GLOBALS['home']. "../tpl/$filename". ".html")) {
		calcul_page($context, $filename, "", $GLOBALS['home']. '../tpl/');
	} else {
		die("<code><strong>Error!</strong> Unable to find the file <span style=\"border-bottom : 1px dotted black\">$filename.html</span></code>");
	}
}

/**
 * Fonction qui permet d'envoyer les erreurs lors du calcul des templates
 *
 * @param string $query la requete SQL
 * @param string $tablename le nom de la table SQL (par d�faut vide)
 */
function mymysql_error($query, $tablename = '')
{
	if ($GLOBALS['lodeluser']['editor']) {
		if ($tablename) {
			$tablename = "LOOP: $tablename ";
		}
		die("</body>".$tablename."QUERY: ". htmlentities($query)."<br><br>".mysql_error());
	}	else {
		if ($GLOBALS['contactbug']) {
			@mail($GLOBALS['contactbug'], "[BUG] LODEL - $GLOBALS[version] - $GLOBALS[database]", "Erreur de requete sur la page ".$_SERVER['REQUEST_URI']."<br>". htmlentities($query). "<br /><br />".mysql_error());
		}
		die("<code><strong>Error!</strong> An error has occured during the calcul of this page. We are sorry and we are going to check the problem</code>");
	}
}
?>