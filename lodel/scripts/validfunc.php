<?
/**
 * Fichier utilitaire pour g�rer la validation des champs
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
 * @author Sophie Malafosse
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno C�nou, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */


/**
 * Validation des champs
 *
 * <p>Validation des caract�res autoris�s dans les champs suivant leur type
 * leur nom, et le texte contenu. Par exemple si on a un champ de type email, il faut
 * v�rifier que l'adresse mail est bien form�e. Idem pour un champ de type url. Cela appliqu�  � tous les types de champs g�r�s par Lodel (cf. fichier fieldfunc.php)</p>
 *
 * @param string $&text le texte � valider. Pass� par r�f�rence.
 * @param string $type le type du champ � valider.
 * @param string $default la valeur par d�faut � valider (si le texte est vide). Est vide par d�faut
 * @param string $name le nom du champ
 * @param string $usedata indique si le context utilise le sous tableau data pour stocker les donn�es
 * @return boolean true si le champ est valide. false sinon
 */
function validfield(&$text, $type, $default = "", $name = "", $usedata = "", $directory="")
{
	static $tmpdir;
	require_once 'fieldfunc.php';
	if ($GLOBALS['lodelfieldtypes'][$type]['autostriptags'] && !is_array($text)) {
		$text = strip_tags($text);
	}
	switch ($type) { //pour chaque type de champ
	case 'history' :
	case 'text' :
	case 'tinytext' :
	case 'longtext' :
		if (!$text) {
			$text = $default;
		}
		return true; // always true
		break;
	case 'type' :
		if ($text && !preg_match("/^[a-zA-Z0-9_][a-zA-Z0-9_ -]*$/", $text)) {
			return $type;
		}
		break;
	case 'class' :
		if (!preg_match("/^[a-zA-Z][a-zA-Z0-9_]*$/", $text)) {
			return $type;
		}
		if (reservedword($text)) {
			return 'reservedsql'; // if the class is a reservedword -> error
		}
		break;
	case 'classtype' :
		$text = strtolower($text);
		if (!preg_match("/^[a-zA-Z][a-zA-Z0-9_]*$/", $text)) {
			return $type;
		}
		require_once 'fieldfunc.php';
		if (reservedword($text)) {
			return 'reservedsql';
		}
		break;
	case 'tablefield' :
		$text = strtolower($text);
		if (!preg_match("/^[a-z0-9]{2,}$/", $text)) {
			return $type;
		}
		require_once 'fieldfunc.php';
		if (reservedword($text))
			return 'reservedsql';
		break;
		if ($text && !preg_match("/^[a-zA-Z0-9]+$/", $text)) {
			return $type;
		}
		break;
	case 'mlstyle' :
		$text = strtolower($text);
		$stylesarr = preg_split("/[\n,;]/", $text);
		foreach ($stylesarr as $style) {
			$style = trim($style);
			if ($style && !preg_match("/^[a-zA-Z0-9]*(\.[a-zA-Z0-9]+)?\s*(:\s*([a-zA-Z]{2}|--))?$/", $style)) {
				return $type;
			}
		}
		break;
	case 'style' :
		if ($text)
		{
			$text = strtolower($text);
			$stylesarr = preg_split("/[\n,;]/", $text);
			foreach ($stylesarr as $style) {
				if (!preg_match("/^[a-zA-Z0-9]*(\.[a-zA-Z0-9]+)?$/", trim($style))) {
					return $type;
				}
			}
		}
		break;
	case 'passwd' :
		if(!$text) {
		return $type;
		break;
	}
	case 'username' :
		if ($text) {
			$len = strlen($text);
			if ($len < 3 || $len > 12 || !preg_match("/^[0-9A-Za-z_;.?!@:,&]+$/", $text)) {
				return $type;
			}
		}
		break;
	case 'lang' : //champ de type langue (i.e fr_FR, en_US)
		if ($text && !preg_match("/^[a-zA-Z]{2}(_[a-zA-Z]{2})?$/", $text)) {
			return $type;
		}
		break;
	case 'date' :
	case 'datetime' :
	case 'time' : //v�rification des champs date, time et datetime
		require_once 'date.php';
		if ($text) {
			$text = mysqldatetime($text, $type);
			if (!$text) {
				return $type;
			}
		}	elseif ($default) {
			$dt = mysqldatetime($default, $type);
			if ($dt) {
				$text = $dt;
			} else {
				die("ERROR: default value not a date or time: \"$default\"");
			}
		}
		break;
	case 'int' :
		if ((!isset ($text) || $text === "") && $default !== "") {
			$text = intval($default);
		}
		if (isset ($text) && (!is_numeric($text) || intval($text) != $text)) {
			return 'int';
		}
		break;
	case 'number' : //nombre
		if ((!isset ($text) || $text === "") && $default !== "") {
			$text = doubleval($default);
		}
		if (isset ($text) && !is_numeric($text)) {
			return 'numeric';
		}
		break;
	case 'email' :
		if (!$text && $default) {
			$text = $default;
		}
		if ($text && !preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $text)) {
			return 'email';
		}
		break;
	case 'url' :
		if (!$text && $default) {
			$text = $default;
		}
		if ($text) {
			$parsedurl = @parse_url($text);
			if (!$parsedurl['host'] || !preg_match("/^(http|ftp|https|file|gopher|telnet|nntp|news)$/i", $parsedurl['scheme'])) {
				return 'url';
			}
		}
		break;
	case 'boolean' :
		$text = $text ? 1 : 0;
		break;
	case 'tplfile' :
		$text = trim($text); // should be done elsewhere but to be sure...
		if (strpos($text, "/") !== false || $text[0] == ".") {
			return "tplfile";
		}
		break;
	case 'color' :
		if ($text && !preg_match("/^#[A-Fa-f0-9]{3,6}$/", $text)) {
			return 'color';
		}
		break;
	case 'entity' :
		$text = intval($text);
		// check it exists
		$dao = &getDAO('entities');
		$vo = $dao->getById($text, "1");
		if (!$vo) {
			return 'entity';
		}
		break;
	case 'textgroups' :
		return $text == 'site' || $text == 'interface';
		break;
	case 'select' :
	case 'multipleselect' :
		return true; // cannot validate
	case 'mltext' :
		if (is_array($text)) {
			$str = "";
			foreach ($text as $lang => $v) {
				if ($lang != "empty" && $v)
					$str .= "<r2r:ml lang=\"". $lang. "\">$v</r2r:ml>";
			}
			$text = $str;
		}
		return true;
	case 'list' :
		return true;
	case 'image' :
	case 'file' :
		if (!is_array($text)) {
			unset($text);
			return true;
		}
		if (!$name) {
			trigger_error("ERROR: \$name is not set in validfunc.php", E_USER_ERROR);
		}
		switch ($text['radio']) {
		case 'upload' :
			// let's upload
			if(!$usedata) {
				$files = &$_FILES;
			} else { //les informations sur le champ se trouve dans $_FILES['data']
				$files[$name]['error']['upload'] = $_FILES['data']['error'][$name]['upload'];
				$files[$name]['tmp_name']['upload'] = $_FILES['data']['tmp_name'][$name]['upload'];
				$files[$name]['type']['upload'] = $_FILES['data']['type'][$name]['upload'];
				$files[$name]['size']['upload'] = $_FILES['data']['size'][$name]['upload'];
				$files[$name]['name']['upload'] = $_FILES['data']['name'][$name]['upload'];
			}
			#print_r($files);
			// look for an error ?
			if (!$files || $files[$name]['error']['upload'] != 0 || !$files[$name]['tmp_name']['upload'] || $files[$name]['tmp_name']['upload'] == "none") {
				unset ($text);
				return 'upload';
			}
			
			require_once 'func.php';

			if (!empty($directory)) {
				// Champ de type file ou image qui n'est PAS un doc annexe : copi� dans le r�pertoire $directory
				$text = save_file($type, $directory, $files[$name]['tmp_name']['upload'], $files[$name]['name']['upload'], true, true, $err, false);
			} else {
				// check if the tmpdir is defined
				if (!$tmpdir[$type]) {
					// look for a unique dirname.
					do {
						$tmpdir[$type] = "docannexe/$type/tmpdir-". rand();
					}	while (file_exists(SITEROOT. $tmpdir[$type]));
				}
				// let's transfer
				$text = save_file($type, $tmpdir[$type], $files[$name]['tmp_name']['upload'], $files[$name]['name']['upload'], true, true, $err);
			}
			if ($err) {
				return $err;
			}
			return true;
		case 'serverfile' :
			// check if the tmpdir is defined
			require_once 'func.php';
			
				if (!empty($directory)) {
				// Champ de type file ou image qui n'est PAS un doc annexe : copi� dans le r�pertoire $directory
				$text = basename($text['localfilename']);
				$text = save_file($type, $directory, SITEROOT."upload/$text", $text, false, false, $err, false);
			} else {
				// check if the tmpdir is defined
				if (!$tmpdir[$type]) {
					// look for a unique dirname.
					do {
						$tmpdir[$type] = "docannexe/$type/tmpdir-". rand();
					} while (file_exists(SITEROOT. $tmpdir[$type]));
				}

				// let's move
				$text = basename($text['localfilename']);
				$text = save_file($type, $tmpdir[$type], SITEROOT."upload/$text", $text, false, false, $err);
			}
			if ($err) {
				return $err;
			}
			return true;
		case 'delete' :
			$filetodelete = true;
		case '' :
			// validate
			$text = $text['previousvalue'];
			if (!$text) {
				return true;
			}
			if (!empty($directory)) {//echo "text = $text <p>";
				$directory= str_replace('/', '\/', $directory);//echo $directory;
				
				if (!preg_match("/^$directory\/[^\/]+$/", $text)) {
					die("ERROR: invalid filename of type $type");
				}
			} else {
				if (!preg_match("/^docannexe\/(image|file)\/[^\.\/]+\/[^\/]+$/", $text)) {
					die("ERROR: invalid filename of type $type");
				}
			}
			if ($filetodelete) {
				unlink(SITEROOT.$text);
				$text = "";
				unset ($filetodelete);
			}
			return true;
		default :
			die("ERROR: unknow radio value for $name");
		} // switch
	default :
		return false; // pas de validation
	}

	return true; // validated
}
?>