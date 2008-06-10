<?php
/**
 * Fichier utilitaire pour la gestion du cache
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
 * @package lodel
 */


/**
 * Nettoyage du r�pertoire de CACHE
 *
 * Cette fonction appelle removefilesincache() si $allCache = true
 * @param bool $allCache
 * @see func.php -> function update()
 */
function clearcache($allCache=true)
{
	global $site;
	$_REQUEST['clearcache'] = false; // to avoid to erase the CACHE again
	if($allCache) {
		if (defined("SITEROOT")) {
			removefilesincache(SITEROOT, SITEROOT."lodel/edition", SITEROOT."lodel/admin");
		}	else {
			removefilesincache(".");
		}
	} else { // seules les donn�es ont �t� modifi�es : on supprime seulement les fichiers HTML mis en cache
		require_once 'Cache/Lite.php';
		$cache = new Cache_Lite($GLOBALS['cacheOptions']);
		if($site) {
			$cache->clean($site); // html
		} else {
			$cache->clean();
		}
	}
}

/**
 * Nettoyage des fichiers du r�pertoire de CACHE
 *
 * On ajoute le r�pertoire CACHE dans le code, ce qui emp�che de d�truire le contenu d'un autre
 * r�pertoire.
 */
function removefilesincache()
{
	global $site;
	$options = $GLOBALS['cacheOptions'];
	foreach (func_get_args() as $rep) {
		if (!$rep) {
			$rep = ".";
		} else {
			$rep = "./".$rep;
		}
		if(FALSE === strpos($rep, '/CACHE/'))
			$rep .= "/CACHE/";
		$rep = str_replace('//', '/', $rep);

		$options['cacheDir'] = $rep;
		require_once 'Cache/Lite.php';
		$cache = new Cache_Lite($options);
		$cache->clean();
		$cache = null;

		// fichiers/r�pertoires g�r�s ind�pendament de cache_lite
		$fd = opendir($rep) or die("Impossible d'ouvrir $rep");
		clearstatcache();
		while (($file = readdir($fd)) !== false) {
			if (($file[0] == ".") || ($file == "CVS") || ($file == "upload") || (FALSE !== strpos($file, 'cache_')))
				continue;
			$file = $rep. "/". $file;
			if (is_dir($file)) { //si c'est un r�pertoire on l'ouvre
				removefilesincache($file);
			} elseif (file_exists($file) && is_writeable($file)) {
				@unlink($file);
			}
		}
		closedir($fd);	
	}
}

/**
 * Fonction g�n�rant le nom du fichier cach� (prise de Cache_Lite)
 * @param string $id base du nom du fichier � g�n�rer
 * @param string $group groupe du fichier
 * @param array $options options du cache
*/
function getCachedFileName($id, $group, $options) {
        if ($options['fileNameProtection']) {
            $suffix = 'cache_'.md5($group).'_'.md5($id);
        } else {
            $suffix = 'cache_'.$group.'_'.$id;
        }
        $root = $options['cacheDir'] ? $options['cacheDir'] : './CACHE/';
        if ($options['hashedDirectoryLevel']>0) {
            $hash = md5($suffix);
            for ($i=0 ; $i<$options['hashedDirectoryLevel'] ; $i++) {
                $root = $root . 'cache_' . substr($hash, 0, $i + 1) . '/';
            }   
        }
        return $root.$suffix;
}
?>
