<?php
/**
 * Fichier de la classe DAO
 *
 * PHP versions 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
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
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * @copyright 2009, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajout� depuis la version 0.8
 * @version CVS:$Id:
 */

/**
 * Classe g�rant la DAO (Database Abstraction Object)
 * 
 * <p>Cette classe d�finit un ensemble de m�thodes permettant d'effectuer les op�rations
 * courantes sur la base de donn�es : s�lection, insertion, mise � jour, suppression. Au lieu
 * d'effectuer soit m�me les requ�tes SQL et de traiter les r�sultats SQL sous forme de tableau,
 * les m�thodes de cette classe retourne leurs r�sultat sous forme d'objet : les Virtual Objet
 *  (VO).</p>
 * <p>Exemple d'utilisation (factice)
 * <code>
 * $dao = new DAO('personnes',true); //instantiation
 * $vos = $DAO->find("nom LIKE('robert')", "nom", "nom,prenom,mail");
 * print_r($vos); // affiche toutes les personnes dont le nom contient robert
 * 
 * $dao->deleteObject($vo[0]); //suppression du premier objet
 * </code>
 *
 * @package lodel
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Pierre-Alain Mignot
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
 * @since Classe ajout�e depuis la version 0.8
 * @see controler.php
 * @see view.php
 */
class DAO
{
	/**#@+
	 * @access private
	 */
	/**
	 * Nom et classe de la table SQL
	 * @var string
	 */
	public $table;

	/**
	 * Nom de la table avec et pr�fixe et �ventuellement la jointure pour le SELECT
	 * Table name with the prefix, and potential join for views.
	 * @var string
	 */
	public $sqltable;

	/**
	 * Uniqueid. Vrai si la table utilise une cl� primaire (cl� unique).
	 * @var integer
	 */
	public $uniqueid;

	/**
	 * Tableau associatif avec les droits requis pour lire, �crire et prot�ger
	 * Assoc array with the right level required to read, write, protect
	 * @var array
	 */
	public $rights;

	/**
	 * Champ identifiant
	 * @var string
	 */
	public $idfield;

	/**
	 * Tableau de cache stockant les crit�res SQL correspondants aux droit d'acc�s sur les objets
	 * @see rightsCriteria()
	 * @access private
	 */
	protected $cache_rightscriteria;

	/**
	 * Internal cache for DAO objects
	 * @var array
	 */
	static protected $_daos = array();

	/**
	 * Internal cache for GenericDAO objects
	 * @var array
	 */
	static protected $_gdaos = array();
	/**#@-*/

	/**
	 * Constructeur de classe
	 *
	 * Positionne les variables priv�es de la classe.
	 *
	 * @param string $table le nom de la table et de la classe.
	 * @param boolean $uniqueid Par d�faut � 'false'. Indique si la table utilise une cl� primaire.
	 * @param string $idfield Par d�faut � 'id'. Indique le nom du champ identifiant
	 */
	public function __construct($table, $uniqueid = false, $idfield = "id")
	{
		$this->table = $table;
		$this->sqltable = lq("#_TP_"). $table;
		$this->uniqueid = $uniqueid;
		$this->idfield = $idfield;
	}

	/**
	* DAO factory
	*
	* @param string $table the dao name
	*/
	static public function getDAO($table)
	{
		if (isset(self::$_daos[$table])) {
			return self::$_daos[$table]; // cache
		}
		$daoclass = $table. 'DAO';
	
		if(!class_exists($daoclass))
		{
			$file = C::get('sharedir', 'cfg').'/plugins/custom/'.$table.'/dao.php';
			if(!file_exists($file))
				trigger_error('ERROR: unknown dao', E_USER_ERROR);
			
			include $file;
			if(!class_exists($daoclass, false) || !is_subclass_of($daoclass, 'DAO'))
				trigger_error('ERROR: the DAO plugin file MUST extends the DAO OR GenericDAO class', E_USER_ERROR);
		}
		
		self::$_daos[$table] = new $daoclass;
		return self::$_daos[$table];
	}

	/**
	* generic DAO factory
	*
	* @param string $table the dao name
	* @param int $idfield the identifier field
	*/
	static public function getGenericDAO($table, $idfield)
	{
		if (isset(self::$_gdaos[$table])) {
			return self::$_gdaos[$table]; // cache
		}
		self::$_gdaos[$table] = new genericDAO ($table,$idfield);
		return self::$_gdaos[$table];
	}

	/**
	 * Ajout/Modification d'enregistrement
	 * Main function to add/modify records
	 *
	 * @param object &$vo l'objet virtuel � sauvegarder.
	 * @param boolean $forcecreate Par d�faut � false. Indique si on doit forcer la cr�ation.
	 * @return $idfield l'identifiant de l'enregistrement cr�� ou modifi�.
	 */
	public function save(&$vo, $forcecreate = false) // $set,$context=array())
	{
		global $db;
		$idfield = $this->idfield;
		#print_r($vo);
		// check the user has the basic right for modifying/creating an object
		if (isset($this->rights['write']) && C::get('rights', 'lodeluser') < $this->rights['write']) {
			trigger_error('ERROR: you don\'t have the right to modify objects from the table '. $this->table, E_USER_ERROR);
		}
        
		// check the user has the right to protect the object
		if (((isset ($vo->status) && ($vo->status >= 32 || $vo->status <= -32)) || (isset($vo->protect) && $vo->protect)) && 
					C::get('rights', 'lodeluser') < $this->rights['protect']) {
			trigger_error('ERROR: you don\'t have the right to protect objects from the table '. $this->table, E_USER_ERROR);
		}

		if (isset ($vo->rank) && $vo->rank == 0) { // initialize the rank
			$rank = $db->getOne('SELECT MAX(rank) FROM '.$this->sqltable.' WHERE status>-64');
			if ($db->errorno()) {
				trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
			$vo->rank = $rank +1;
		}
		$this->quote($vo);
		if (isset($vo->$idfield) && $vo->$idfield > 0 && !$forcecreate) { // Update - Mise � jour
			$update = ''; //crit�re de mise � jour
			if (isset ($vo->protect))	{ // special processing for the protection
				$update = 'status=(2*(status>0)-1)'. ($vo->protect ? '*32' : ''); //reglage du status
				unset ($vo->status);
				unset ($vo->protect);
			}
			foreach ($vo as $k => $v)	{ // ajout de chaque champ � la requete update
				if (!isset ($v) || $k == $idfield) {
					continue;
				}
				if ($update) {
					$update .= ',';
				}
				$update .= "$k='". $v. "'";
			}
			if ($update) {
				$update = 
				$db->execute('UPDATE '. $this->sqltable. " SET  $update WHERE ". $idfield. "='". $vo->$idfield. "' ". $this->rightscriteria('write')) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
		}	else	{ // new  - Ajout
			if (isset ($vo->protect))	{ // special processing for the protection
				$vo->status = ($vo->status > 0 ? 1 : -1) * ($vo->protect ? 32 : 1);
				unset ($vo->protect);
			}
			$insert = ''; //condition SQL pour INSERT
			$values = ''; // valeur des champs pour la requete SQL INSERT
			if ($this->uniqueid && !$vo->$idfield) {
				$vo->$idfield = uniqueid($this->table);
			}
			foreach ($vo as $k => $v)	{
				if (!isset ($v)) {
					continue;
				}
				if ($insert) {
					$insert .= ',';
					$values .= ',';
				}
				$insert .= $k;
				$values .= "'". $v. "'";
			}
			if ($insert) {
				$db->execute('REPLACE INTO '.$this->sqltable.' ('. $insert. ') VALUES ('. $values. ')') or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				if (!isset($vo->$idfield)) {
					$vo->$idfield = $db->insert_id();
				}
			}
		}
		return $vo->$idfield;
	}

	/**
	 * Ajout de slashes dans champ pour la protection des donn�es dans la requ�te SQL
	 *
	 * Quote the field in the object
	 *
	 * @param object &$vo Objet virtuel pass� par r�f�rence
	 */
	public function quote(&$vo)
	{
		foreach ($vo as $k => $v) {
			if (isset ($v)){
				$vo->$k = addslashes($v);
			}
		}
	}

	/**
	 * R�cuperer un objet par son identifiant
	 *
	 * Function to get a value object
	 *
	 * @param integer $id l'identifiant de l'objet
	 * @param string $select les champs � r�cuperer
	 * @return object un objet virtuel contenant les champs de l'objet
	 * @see fonction find()
	 */
	public function getById($id, $select = "*")
	{
		return $this->find($this->idfield. "='$id'", $select);
	}

	/**
	 * R�cuperer des objects gr�ce aux identifiants
	 *
	 * Function to get many value object
	 * @param array $ids le tableau des identifiant
	 * @param string $select les champs � r�cuperer
	 * @return array un tableau d'objet virtuels
	 * @see fonction find(), getById()
	 */
	public function getByIds($ids, $select = "*")
	{
		return $this->findMany($this->idfield. (is_array($ids) ? " IN ('". join("','", $ids). "')" : "='".$ids."'"), '', $select);
	}

	/**
	 * Trouver un objet suivant certains crit�res et en s�lectionnant certains champs
	 *
	 * Function to get a value object
	 *
	 * @param string $criteria les crit�res SQL de recherche
	 * @param string $select les crit�res SQL de s�lection (par d�faut : SELECT *)
	 * @return l'objet virtuel trouv� sinon null
	 */
	public function find($criteria, $select = "*")
	{
		global $db;

		//execute select statement
		$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;
		$row = $db->getRow("SELECT ".$select." FROM ".$this->sqltable." WHERE ($criteria) ".$this->rightscriteria("read"));
		$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_DEFAULT;
		if ($row === false) {
			trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}
		if (!$row) {
			return null;
		}

		// create new vo and call getFromResult
		$this->instantiateObject($vo);
		$this->_getFromResult($vo, $row);
		return $vo;
	}

	/**
	 * Trouver un ensemble d'objet correspondant � des crit�res
	 *
	 * Function to get many value object
	 *
	 * @param string $criteria les crit�res SQL de recherches
	 * @param string $order le crit�re SQL de tri des r�sultats. (par d�faut vide)
	 * @param string $select les champs � s�lectionner. (par d�faut *).
	 * @return array Un tableau de VO correspondant aux r�sultats de la requ�te
	 */
	public function findMany($criteria, $order = '', $select = '*')
	{
		global $db;

		//execute select statement
		$morecriteria = $this->rightscriteria("read");
		if ($order) {
			$order = "ORDER BY ".$order;
		}
		$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;
		# echo "SELECT ".$select." FROM ".$this->sqltable." WHERE ($criteria) ".$morecriteria." ".$order;
		$result = $db->execute("SELECT ".$select." FROM ".$this->sqltable." WHERE ($criteria) ".$morecriteria." ".$order) 
			or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_DEFAULT;

		$i = 0;
		$vos = array ();
		while (!$result->EOF) {
			//create new vo and
			$this->instantiateObject($vos[$i]);
			// call getFromResult
			$this->_getFromResult($vos[$i], $result->fields);
			++$i;
			$result->MoveNext();
		}
		$result->Close();
		// return vo's
		return $vos;
	}

	/**
	 * Compter le nombre d'�l�ments correspondant � tel crit�re
	 *
	 * Return the number of element matching a criteria
	 *
	 * @param string $criteria Les crit�res SQL de la requ�te.
	 */
	public function count($criteria)
	{
		global $db;
		$ret = $db->getOne('SELECT COUNT(*) FROM '.$this->sqltable.' WHERE '.$criteria);
		if ($db->errorno()) {
			trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}
		return $ret;
	}

	/**
	 * Cr�e un nouvel objet virtuel (VO)
	 * Create a new Value Object
	 *
	 * @return object Le VO instanci�
	 */
	public function createObject()
	{
		$vo = null;
		$this->instantiateObject($vo);
		if (array_key_exists("status", $vo)) {
			$vo->status = 1;
		}
		if (array_key_exists("rank", $vo)) {
			$vo->rank = 0; // auto
		}
		return $vo;
	}

	/**
	 * Instanciation d'un nouvel objet virtuel (VO)
	 *
	 * Instantiate a new object
	 */
	public function instantiateObject(& $vo)
	{
		$classname = $this->table. 'VO';
		$vo = new $classname; // the same name as the table. We don't use factory...
	}

	/**
	 * Suppression d'un objet - fonction qui ne fait qu'appeller deleteObject
	 * Function to delete an object value.
	 * @param mixed object or numeric id or an array of ids or criteria
	 * @return boolean un booleen indiquant l'�tat de la suppression de l'objet
	 */
	public function delete($mixed)
	{
		return $this->deleteObject($mixed);
	}
	/**
	 * Suppression d'un objet ou d'un tableau d'objet (tableau d'identifiant)
	 * @param mixed object or numeric id or an array of ids or criteria
	 * @return boolean un booleen indiquant l'�tat de la suppression de l'objet
	 */
	public function deleteObject(&$mixed)
	{
		global $db;

		if (isset($this->rights['write']) && C::get('rights', 'lodeluser') < $this->rights['write']) {
			trigger_error('ERROR: you don\'t have the right to delete object from the table '. $this->table, E_USER_ERROR);
		}
		
		$idfield = $this->idfield;
		if (is_object($mixed)) {
			$vo = &$mixed;
			$id = $vo->$idfield;
			$criteria = $idfield. "='$id'";
			//set id on vo to 0
			$vo->$idfield = 0;
			$nbid = 1;
		}	elseif (is_numeric($mixed) && $mixed > 0)	{
			$id = $mixed;
			$criteria = $idfield. "='$id'";
			$nbid = 1;
		}	elseif (is_array($mixed))	{
			$id = $mixed;
			$criteria = $idfield. " IN ('". join("','", $id). "')";
			$nbid = count($id);
		}	elseif (is_string($mixed) && trim($mixed)) {
			$criteria = lq($mixed);
			if ($this->uniqueid) {
				// select before deleting
				$result = $db->execute('SELECT id FROM '.$this->sqltable." WHERE ($criteria) ". $this->rightscriteria('write')) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				// collect the ids
				$id = array ();
				foreach ($result as $row) {
					$id[] = $row['id'];
				}
				$nbid = count($id);
			}	else {
				$nbid = 0; // check we have delete at least one
			}
		}	else {
			trigger_error('ERROR: DAO::deleteObject does not support the type of mixed variable', E_USER_ERROR);
		}

		//execute delete statement
		$db->execute('DELETE FROM '. $this->sqltable. " WHERE ($criteria) ". $this->rightscriteria("write")) 
			or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		if ($db->affected_Rows() < $nbid) {
			trigger_error("ERROR: you don't have the right to delete some objects in table ". $this->table, E_USER_ERROR);
		}
		// in theory, this is bad in the $mixed is an array because 
		// some but not all of the object may have been deleted
		// in practice, it is an error in the interface. The database may be corrupted (object in fact).

		//delete the uniqueid entry if required
		if ($this->uniqueid) {
			if ($nbid != count($id)) {
				trigger_error("ERROR: internal error in DAO::deleteObject. Please report the bug", E_USER_ERROR);
			}
			deleteuniqueid($id);
		}
		return true;
	}

	/**
	 * Suppression de plusieurs objets suivant un crit�re particulier
	 * Function to delete many object value given a criteria
	 *
	 * @param string crit�res SQL pour la suppression
	 * @return boolean un booleen indiquant l'�tat de la suppression de l'objet
	 */
	public function deleteObjects($criteria)
	{
		global $db;

		// check the rights
		if (isset($this->rights['write']) && C::get('rights', 'lodeluser') < $this->rights['write']) {
			trigger_error("ERROR: you don't have the right to delete object from the table ".$this->table, E_USER_ERROR);
		}
		$where = " WHERE (".$criteria.") ".$this->rightscriteria("write");

		// delete the uniqueid entry if required
		if ($this->uniqueid) {
			// select before deleting
			$result = $db->execute("SELECT id FROM ".$this->sqltable.$where) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			// collect the ids
			$ids = array ();
			foreach ($result as $row) {
				$ids[] = $row['id'];
			}
			// delete the uniqueid
			deleteuniqueid($ids);
		}
	
		//execute delete statement
		$db->execute("DELETE FROM ". $this->sqltable. $where) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		if ($db->Affected_Rows() <= 0) {
			return false; // not the rights
		}
		return true;
	}

	/**
	 * R�cup�re le crit�re SQL correspondant aux droits d'acc�s en lecture et en �criture
	 *
	 * Return the criteria depending on the write/read access
	 *
	 * @param string $access le niveau d'acc�s pour lequel on souhaite avoir le crit�re SQL
	 * @return string Le crit�re SQL correspond au droit d'acc�s
	 */
	public function rightscriteria($access)
	{
		if (!isset($this->cache_rightscriteria[$access])) {
			$classvars = get_class_vars($this->table. "VO");
			if ($classvars && array_key_exists("status", $classvars)) {
				$status = $this->sqltable. '.status';
				$this->cache_rightscriteria[$access] = C::get('visitor', 'lodeluser') ? '' : " AND $status > 0";

				if ($access == "write" && isset($this->rights['protect']) && C::get('rights', 'lodeluser') < $this->rights['protect']) {
					$this->cache_rightscriteria[$access] .= " AND $status<32 AND $status>-32 ";
				}
			}
		}	else	{
			$this->cache_rightscriteria[$access] = "";
		}
		return $this->cache_rightscriteria[$access];
	}

	/**
	 * Remplit un VO depuis une ligne d'un ResultSet SQL
	 *
	 * @param objet $vo Le VO � remplir pass� par r�f�rence
	 * @param array $row La ligne du ResultSet SQL
	 * @access private
	 */
	protected function _getFromResult(&$vo, $row)
	{
		foreach ($row as $k => $v) {//fill vo from the database result set
			$vo->$k = $v;
		}
	}
}
?>