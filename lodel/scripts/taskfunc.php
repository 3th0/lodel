<?php
/**
 * Fichier utilitaire de gestion des t�ches (table task)
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
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */

// $context est soit un tableau qui sera serialise soit une chaine deja serialise
/**
 * Ajoute une t�che dans la table
 *
 * @param string $name le nom de la t�che
 * @param string $etape l'�tape courante
 * @param array (ou string) $context le context courant pour l'�tape
 * @param integer $id par d�faut 0. l'id de la t�che
 * @return integer l'identifiant ins�r� (si c'est le cas)
 */
function maketask($name, $etape, $context, $id = 0)
{
	global $lodeluser, $db;
	if (is_array($context))
		$context = addslashes(serialize($context));
	$db->execute(lq("REPLACE INTO #_TP_tasks (id,name,step,user,context) VALUES ('$id','$name','$etape','".$lodeluser['id']."','$context')")) or dberror();
	return $db->insert_ID();
}

/**
 * Retrouve une t�che suivant son identifiant
 *
 * @param integer &$id l'identifiant de la t�che
 * @param array retourne les informations concernant la t�che
 */
function gettask(& $id)
{
	global $db;
	$id = intval($id);
	$row = $db->getRow(lq("SELECT * FROM #_TP_tasks WHERE id='$id' AND status>0"));
	if ($row === false)
		dberror();
	if (!$row) {
		require_once 'view.php';
		$view = &View::getView();
		$view->back();
		return;
	}
	$row = array_merge($row, unserialize($row['context']));
	return $row;
}

/**
 * Suivant que le document est import� ou r�import�, les informations dans la t�che sont
 * diff�rentes. Cette fonction uniformise ces informations dans le context ($context).
 *
 * Depending the document is imported or re-imported, the information in task are different.
 * This function uniformize the information in the context
 *
 * @param array $task un tableau contenant les infos d'une t�che
 * @param array &$context le context dans lequel seront mis � jour les informations
 */
function gettypeandclassfromtask($task, & $context)
{
	global $db;
	if ($task['identity']) {
		$row = $db->getRow(lq("SELECT class,idtype,idparent FROM #_entitiestypesjoin_ WHERE #_TP_entities.id='".$task['identity']."'"));
		if ($db->errorno())
			dberror();
		$context['class'] = $row['class'];
		$context['idtype'] = $row['idtype'];
		$context['idparent'] = $row['idparent'];
		if (!$context['class'])
			die("ERROR: can't find entity ".$task['identity']." in gettypeandclassfromtask");
	} else {
		$idtype = $task['idtype'];
		if (!$idtype)
			die("ERROR: idtype must be given by task in gettypeandclassfromtask");
		// get the type 
		$dao = & getDAO("types");
		$votype = $dao->getById($idtype, "class");
		$context['class'] = $votype->class;
	}
}
?>