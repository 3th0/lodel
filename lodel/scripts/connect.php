<?php
/**
 * Fichier pour g�rer la connection � la base de donn�e - initialise les connexions
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
 * @package lodel
 */
if (!(INC_LODELCONFIG)) {
	die("inc lodelconfig please"); // security
}
// compatibility 0.7
if (!defined("DATABASE")) {
	define("DATABASE", $GLOBALS['database']);
	define("DBUSERNAME", $GLOBALS['dbusername']);
	define("DBPASSWD", $GLOBALS['dbpasswd']);
	define("DBHOST", $GLOBALS['dbhost']);
	define("DBDRIVER", "mysql");
}

// connect to the database server
require_once "adodb/adodb.inc.php";
$GLOBALS['db'] = ADONewConnection(DBDRIVER);
$GLOBALS['db']->debug = false; // mettre � true pour activer le mode debug

if ($GLOBALS['site'] && $GLOBALS['singledatabase'] != "on") {
	$GLOBALS['currentdb'] = DATABASE. "_".$GLOBALS['site'];
} else {
	$GLOBALS['currentdb'] = DATABASE;
}

if (!defined("SINGLESITE")) {
	define("SINGLESITE", $GLOBALS['singledatabase'] == "on"); // synonyme currently but may change in the future
}

$GLOBALS['db']->connect(DBHOST, DBUSERNAME, DBPASSWD, $GLOBALS['currentdb']) or dberror();
if (DBDRIVER == 'mysql') {
	$info_mysql = $GLOBALS['db']->ServerInfo();
	$vs_mysql = explode(".", substr($info_mysql['version'], 0, 3));
	$GLOBALS['version_mysql'] = $vs_mysql[0] . $vs_mysql[1];

	if ($GLOBALS['version_mysql'] > 40) {
		$GLOBALS['db_charset'] = mysql_find_db_variable($GLOBALS['currentdb'], 'character_set_database');
		if ($GLOBALS['db_charset'] === false) {
			$GLOBALS['db_charset'] = 'utf8';
		}
		$GLOBALS['db']->execute('SET NAMES ' . $GLOBALS['db_charset']);
	}
}

$GLOBALS['db']->SetFetchMode(ADODB_FETCH_ASSOC);
$GLOBALS['tp'] = $GLOBALS['tableprefix'];


/**
 * D�clenche une erreur lors d'une erreur concernant la base de donn�es
 */
function dberror()
{
	global $db;
	$ret = trigger_error($db->errormsg(), E_USER_ERROR);
}

$GLOBALS['maindb'] = '';
$GLOBALS['savedb'] = '';

/**
 * Positionne la connexion de la base de donn�es sur la table principale (en cas d'installation 
 * multisite.
 */
function usemaindb()
{
	global $db, $maindb, $savedb;
	if (DATABASE == $GLOBALS['currentdb']) {
		return false; // nothing to do
	}
	if ($db->selectDB(DATABASE)) {
		return true; // try to selectdb
	}

	if (!$maindb)	{ // not connected
		$maindb = ADONewConnection(DBDRIVER);
		if (!$maindb->nconnect(DBHOST, DBUSERNAME, DBPASSWD, DATABASE)) {
			die("ERROR: reconnection is not allow with the driver: ".DBDRIVER);
		}
	}

	// set $db as $maindb
	$savedb = &$db;
	$db = &$maindb;
	return true;
}

/**
 * Positionne la connexion de la base de donn�es sur la base de donn�es du site (si Lodel est
 * install� en multisite, l'unique base sinon
 */
function usecurrentdb()
{
	if (DATABASE == $GLOBALS['currentdb']) {
		return; // nothing to do
	}
	global $db, $savedb;
	if ($db->selectDB($GLOBALS['currentdb'])) {
		return; // try to selectdb
	}
	$db = &$savedb;
}

/**
 * Lodel Query : 
 *
 * Transforme les requ�tes en r�solvant les jointures et en cherchant les bonnes
 * tables dans les bases de donn�es (suivant notamment le pr�fix utilis� pour le nommage des
 * tables).
 *
 * @param string $query la requ�te � traduire
 * @return string la requ�te traduite
 */
function lq($query)
{
	static $cmd;
	// the easiest, fats replace
	$query = str_replace('#_TP_', $GLOBALS['tableprefix'], $query);
	// any other ?
	if (strpos($query, '#_') !== false)	{
		if (!$cmd)
			$cmd = array ('#_MTP_' => '`'.DATABASE.'`.'.$GLOBALS['tableprefix'],
	'#_entitiestypesjoin_' => "$GLOBALS[tableprefix]types INNER JOIN $GLOBALS[tableprefix]entities ON $GLOBALS[tableprefix]types.id=$GLOBALS[tableprefix]entities.idtype",

	'#_tablefieldsandgroupsjoin_' => "$GLOBALS[tableprefix]tablefieldgroups INNER JOIN $GLOBALS[tableprefix]tablefields ON $GLOBALS[tableprefix]tablefields.idgroup=$GLOBALS[tableprefix]tablefieldgroups.id",

	'#_tablefieldgroupsandclassesjoin_' => "$GLOBALS[tableprefix]tablefieldgroups INNER JOIN $GLOBALS[tableprefix]classes ON $GLOBALS[tableprefix]classes.class=$GLOBALS[tableprefix]tablefieldgroups.class");

		$query = strtr($query, $cmd);
	}
	return $query;
}

/**
 * Fonction n�cessaire pour la gestion des id num�riques uniques (dans la table object)
 *
 * get a unique id
 * fonction for handling unique id
 *
 * @param string $table le nom de la table dans laquelle on veut ins�rer un objet
 * @return integer Un entier correspondant � l'id ins�r�.
 */
function uniqueid($table)
{
	global $db;
	$db->execute(lq("INSERT INTO #_TP_objects (class) VALUES ('$table')")) or dberror();
	return $db->insert_id();
}

/**
 * Suppression d'un identifiant uniques (table objets)
 *
 * erase a unique id.
 * Cette fonction accepte en entr�e un id ou un tableau d'id
 *
 * @param integer or array un id ou un tableau d'ids.
 */
function deleteuniqueid($id)
{
	global $db;
	if (is_array($id) && $id)	{
		$db->execute(lq("DELETE FROM $GLOBALS[tableprefix]objects WHERE id IN (". join(",", $id). ")"));
	}	else {
		$db->execute(lq("DELETE FROM $GLOBALS[tableprefix]objects WHERE id='$id'"));
	}
}

/**
 * Recherche d'une variable MySQL
 *
 * @param string $database_name nom de la base de donn�e
 * @param string $var nom de la variable recherch�e
 * @return valeur de la variable
 */
function mysql_find_db_variable ($database_name, $var = 'character_set_database') {
	mysql_select_db($database_name) or die ("ERROR select database");
	$result = mysql_query("SHOW VARIABLES LIKE '$var'");
	if ($db_charset = mysql_fetch_row($result)) {
		return $db_charset[1]; }
	else return false;
}

?>