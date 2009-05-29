<?php
/**
 * Fichier de gestion des types : tables li�es (pas trop compris ce fichier l� ... :()
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
 * @package lodel
 */

//
//
// Function to be rewritten in an Object framework: NN-relationship.
//
/**
 * Suppression des entr�es dans les tables types li�es
 *
 * @param string $typetable le nom du type
 * @param string $critere crit�res SQL
 */
function typetype_delete($typetable, $critere)
{
	global $db;
	$db->execute(lq("DELETE FROM #_TP_entitytypes_".$typetable."s WHERE $critere")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
}

/**
 *
 * Ajout d'entr�es dans les tables types li�es
 *
 * @param array ou integer id du type de l'entit�
 * @param array ou integer id du type
 * @param string nom de la table de type
 */
function typetype_insert($identitytype, $idtypetable, $typetable)
{
	global $db;
	// l'un ou l'autre des idtype doit etre un array, l'autre est un id fixe.
	//
	if (!$identitytype || !$idtypetable) {
		return;
	}

	$values = array ();
	if (is_array($idtypetable))	{
		foreach ($idtypetable as $idtype => $cond) {
			array_push($values, "('$identitytype','$idtype','*')");
		}
	} else {
		foreach ($identitytype as $idtype => $cond) {
			array_push($values, "('$idtype','$idtypetable','*')");
		}
	}
	$table = $typetable != 'entitytype2' ? $typetable : 'entitytype';

	$db->execute(lq("INSERT INTO #_TP_entitytypes_".$table."s (identitytype,id$typetable,cond) VALUES ".join(",", $values))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
}


function loop_typetable($listtype, $criteretype, $context, $funcname, $checked = -1)
{
	global $db;
	if ($listtype == 'entitytype' || $listtype == 'entitytype2') {
		$maintable = 'types';
		$rank = 'class, type';
		$relationtable = $criteretype;
	} else {
		$maintable = $listtype."s";
		$relationtable = $listtype;
		$rank = 'type';
	}

	$result = $db->execute(lq("SELECT * FROM #_TP_$maintable LEFT JOIN #_TP_entitytypes_".$relationtable."s ON id$listtype=#_TP_$maintable.id AND id$criteretype='$context[id]' WHERE status>0 ORDER BY $rank")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

	while (!$result->EOF) {
		$localcontext = array_merge($context, $result->fields);
		if (is_array($checked)) {
			$localcontext['value'] = isset($checked[$result->fields['id']]) ? 'checked="checked"' : '';
		} else {
			$localcontext['value'] = $result->fields['cond'] ? 'checked="checked"' : '';
		}

		call_user_func("code_do_$funcname", $localcontext);
		$result->MoveNext();
	}
}
?>