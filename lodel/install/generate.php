<?php
/**
 * Fichier utilitaire pour la g�n�ration des fichiers de DAO et de Logic
 *
 *
 * Ce fichier permet de reg�n�rer les fichier du r�pertoire lodel/scripts/dao et ceux du
 * r�pertoire lodel/scripts/logic. Les modifications ne sont pas perdus car seul les parties
 * entre les blocs "// begin{publicfields} automatic generation  //" et "// end{publicfields}
 * automatic generation  //" sont r�g�n�r�s (pour les logiques).
 *
 * Ce script est � lancer en ligne de commande
 *
 * PHP version 4
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Micka�l Cixous, Sophie Malafosse
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
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Micka�l Cixous, Sophie Malafosse
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel/install
 */

require_once 'generatefunc.php';
## to be launch from lodel/scripts

$files = array("init-site.xml", "init.xml");
$table = '';
$uniqueid = false;
$rights = array();
$uniquefields = array();


/**
 * Cette m�thode est app�l�e quand le parser XML rencontre le d�but d'un �l�ment.
 *
 * R�cup�re la liste des champs, leurs propri�t�s pour tous les �l�ments table
 *
 * @param object $parser le parser XML
 * @param string $name le nom de l'�lement qui d�bute
 * @param array $attrs les attributs de l'�l�ment
 */
function startElement($parser, $name, $attrs)
{
	global $table, $tables, $fp, $varlist, $publicfields, $rights, $uniqueid;
	global $currentunique, $uniquefields;
	switch($name) {
	case "table" :
	case "vtable" :
		$table = $attrs['name'];
		if ($tables[$table]) {
			break;
		}
		if (!$table) {
			die('nom de table introuvable');
		}
		$uniqueid = isset($attrs['uniqueid']);
		$rights=array();
		if ($attrs['writeright']) $rights[] = "'write'=>LEVEL_". strtoupper($attrs['writeright']);
		if ($attrs['protectright']) $rights[] = "'protect'=>LEVEL_". strtoupper($attrs['protectright']);
		$varlist      = array();
		$publicfields = array();
		$uniquefields = array();
		break;
	case "column" :
		$varlist[] = $attrs['name'];
		if (!isset($attrs['edittype'])) {
			break;
		}
		if (isset($attrs['label']) || (isset($attrs['visibility']) && $attrs['visibility'] == 'hidden')) {
			$condition = $attrs['required'] == 'true' ? '+' : '';
			$publicfields[] = '\''. $attrs['name']. '\' => array(\''. $attrs['edittype']. '\', \''.$condition. '\')';
		}
		break;
	case "unique" :
		$currentunique = $attrs['name'];
		$uniquefields[$currentunique] = array();
		break;
	case "unique-column" :
		$uniquefields[$currentunique][]=$attrs['name'];
		break;
	}
}

/**
 * Cette m�thode est app�l�e quand le parser XML rencontre la fin de l'�l�ment.
 *
 * lorsque on d�tecte la fin d'un �l�ment table alors on g�n�re la DAO et la logic associ�e
 *
 * @param object $parser le parser XML
 * @param string $name le nom de l'�lement qui d�bute
 */
function endElement($parser, $name)
{
	global $table, $tables;
	switch($name) {
	case "table" :
		if ($tables[$table]) break;
		$tables[$table] = true;
		buildDAO();
		buildLogic();
		$table="";
		break;
	case "vtable" :
		if ($tables[$table]) break;
		$tables[$table] = true;
		//buildDAO(); // don't build DAO for virtual table
		buildLogic();
		$table="";
		break;
	}
}

//Lancement du parser
foreach($files as $file) {
	$xml_parser = xml_parser_create();
	xml_set_element_handler($xml_parser, 'startElement', 'endElement');
	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
	if (!($fp = fopen($file, "r"))) {
		die("could not open XML input");
	}

	while ($data = fread($fp, 4096)) {
		if (!xml_parse($xml_parser, $data, feof($fp))) {
			die(sprintf("XML error: %s at line %d",
			xml_error_string(xml_get_error_code($xml_parser)),
			xml_get_current_line_number($xml_parser)));
		}
	}
	xml_parser_free($xml_parser);
}

/**
 * Construction des fichiers de DAO.
 *
 * Pour chaque table du fichier XML init-site.xml, un fichier contenant la classe VO et la
 * classe DAO de la table est cr��.
 */
function buildDAO() 
{
	global $table, $uniqueid, $varlist, $rights;
	echo "table=$table\n";
	$text.='

/**
 * Classe d\'objet virtuel de la table SQL '.$table.'
 *
 * @package lodel/dao
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Micka�l Cixous, Sophie Malafosse
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajout�e depuis la version 0.8
 * @see dao.php
 */
class '.$table.'VO 
{
	/**#@+
	 * @access public
	 */
';
      foreach ($varlist as $var) {
	$text.= "\tvar $".$var.";\n";
      }
      $text.= '	/**#@-*/
}

/**
 * Classe d\'abstraction de la base de donn�es de la table '.$table.'
 *
 * Fille de la classe DAO
 *
 * @package lodel/dao
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Micka�l Cixous, Sophie Malafosse
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajout�e depuis la version 0.8
 * @see dao.php
 */
class '. $table. 'DAO extends DAO 
{
	/**
	 * Constructeur
	 *
	 * <p>Appelle le constructeur de la classe m�re DAO en lui passant le nom de la classe.
	 * Renseigne aussi le tableau rights des droits.
	 * </p>
	 */
	function '. $table. 'DAO()
	{
		$this->DAO("'. $table. '", '. ($uniqueid ? "true" : "false"). ');
		$this->rights = array('. join(", ", $rights).');
	}
';
	$daofile = "../scripts/dao/class.".$table.".php";
	if (file_exists($daofile)) {
		// unique fields
		$beginre = '\/\/\s*begin\{definitions\}[^\n]+?\/\/';
		$endre   = '\n\s*\/\/\s*end\{definitions\}[^\n]+?\/\/';
		$file = file_get_contents($daofile);
		if (preg_match("/$beginre/", $file)) {
			replaceInFile($daofile, $beginre, $endre, $text);
			return;
		}
	}

	// create the file
	$fp = fopen($daofile, "w");
	fwrite($fp, "<". "?php". getnotice($table). $text.'
}

?'.'>');
	fclose($fp);
}


/**
 * Construction des logics des classes
 *
 * Pour chaque table du fichier XML init-site.xml, les fichiers de logic sont modifi�s
 * pour mettre � jour les fonctions _publicfields et _uniquefields
 */
function buildLogic()
{
	global $table,$publicfields,$uniquefields;

	$filename = '../scripts/logic/class.'.$table.'.php';

	if (!file_exists($filename)) return;
	$file = file_get_contents($filename);

	// public fields
	$beginre = '\/\/\s*begin\{publicfields\}[^\n]+?\/\/';
	$endre   = '\n\s*\/\/\s*end\{publicfields\}[^\n]+?\/\/';
	if (preg_match("/$beginre/", $file)) {
		$newpublicfields='
	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	function _publicfields() 
	{
		return array('.join(",\n\t\t\t\t\t\t\t\t\t", $publicfields).");
	}";
	replaceInFile($filename, $beginre, $endre, $newpublicfields);
	}

	// unique fields
	$beginre = '\/\/\s*begin\{uniquefields\}[^\n]+?\/\/';
	$endre   = '\n\s*\/\/\s*end\{uniquefields\}[^\n]+?\/\/';
	if (preg_match("/$beginre/", $file)) {
		if ($uniquefields) {
			$newunique='
	/**
	 * Retourne la liste des champs uniques
	 * @access private
	 */
	function _uniqueFields() 
	{ 
		return array(';
			foreach ($uniquefields as $unique) {
				$newunique.='array(\''.join('\', \'',$unique).'\'), ';
			}
			$newunique.=");
	}";
  	}
		replaceInFile($filename,$beginre,$endre,$newunique);
  }
}

/**
 * Texte de la notice pour les fichiers DAO
 *
 * @param string $table le nom de la table
 */
function getnotice($table) 
{
	return '
/**
 * Fichier DAO de la table SQL '.$table.'.
 *
 * PHP version 4
 *
 * LODEL - Logiciel d\'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Micka�l Cixous, Sophie Malafosse
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
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Micka�l Cixous, Sophie Malafosse
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajout� depuis la version 0.8
 * @version CVS:$Id$
 * @package lodel/dao
 */

//
// Fichier g�n�r� automatiquement le '.date('d-m-Y').'.
//
';
}
?>