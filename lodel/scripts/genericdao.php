<?php
/**
 * Fichier de la classe GenericDAO
 *
 * PHP versions 5
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
 * @since Fichier ajout� depuis la version 0.8
 * @version CVS:$Id:
 */

/**
 * Classe GenericDAO
 *
 * <p>Cette classe permet de g�rer les �l�ments g�n�riques de l'interface</p>
 * @package lodel
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @since Classe ajout�e depuis la version 0.8
 * @see genericlogic.php
 */

class genericDAO extends DAO
{
	/**
	 * Constructeur
	 *
	 * @param string $table la table SQL de la DAO
	 * @param string $idfield Indique le nom du champ identifiant
	 */
	public function __construct($table, $idfield)
	{
		parent::__construct($table, false, $idfield); // create the class
	}

	/**
	 * Instantie un nouvel objet virtuel (VO)
	 *
	 * Instantiate a new object
	 *
	 * @param object &$vo l'objet virtuel qui sera instanci�
	 */
	public function instantiateObject(&$vo)
	{
		static $def;
		$classname = $this->table."VO";
		if (!$def[$classname]) {
			eval ("class ". $classname. " { var $". $this->idfield. "; } ");
			$def[$classname] = true;
		}
		$vo = new $classname; // the same name as the table. We don't use factory...
	}
	/**
	 * Retourne les droits correspondant � un access particulier
	 *
	 * @access private
	 * @param string $access l'acc�s pour lequel on veut les droits
	 * @return Retourne une cha�ne vide.
	 */
	protected function _rightscriteria($access)
	{
		return '';
	}
}
?>