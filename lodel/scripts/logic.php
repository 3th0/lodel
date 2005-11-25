<?php
/**
 * Fichier de la classe Logic
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
 * @since Fichier ajout� depuis la version 0.8
 * @version CVS:$Id$
 */


/**
 * Classe des logiques m�tiers.
 * 
 * <p>Cette classe d�finit les actions de base des diff�rentes logiques m�tiers utilis�es dans Lodel.
 * Elle est la classe 'm�re' des logiques m�tiers se trouvant dans le r�pertoire /logic.
 * Elles est aussi la liaison entre la couche d'abstraction de la base de donn�es (DAO/VO) et la
 * vue</p>.
 *
 * @package lodel
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajout� depuis la version 0.8
 * @see controler.php
 * @see view.php
 */
class Logic
{
	/**#@+
	 * @access private
	 */
	/**
	 * Nom de la table SQL centrale et de la classe.
	 *
	 * Table and class name of the central table
	 * @var string
	 */
	var $maintable;

	/**
	 * crit�re SQL du rang
	 * Give the SQL criteria which make a group from the ranking point of view.
	 * @var string
	 */
	var $rankcriteria;
	/**#@-*/


	/** 
	 * Constructeur de la classe.
	 * 
	 * Positionne simplement le nom de la table principale.
	 * @param string $maintable le nom de la table principale.
	 */
	function Logic($maintable) 
	{
		$this->maintable = $maintable;
	}


	/**
	 * Impl�mentation par d�faut de l'action permettant d'appeler l'affichage d'un objet.
	 *
	 * Cette fonction r�cup�re les donn�es de l'objet <em>via</em> la DAO de l'objet. Ensuite elle
	 * met ces donn�es dans le context (utilisation de la fonction priv�e _populateContext())
	 * 
	 * view an object Action
	 * @param array $context le tableau des donn�es pass� par r�f�rence.
	 * @param array $error le tableau des erreurs rencontr�es pass� par r�f�rence.
	 * @return string les diff�rentes valeurs possibles de retour d'une action (_ok, _back, _error ou xxxx).
	 */
	function viewAction(&$context, &$error)
	{
		if ($error) return; // nothing to do if it is an error.
		$id = intval($context['id']);
		if (!$id) return "_ok"; // just add a new Object
		$dao = $this->_getMainTableDAO();
		$vo  = $dao->getById($id);
		if (!$vo) //erreur critique
			die("ERROR: can't find object $id in the table ". $this->maintable);
		$this->_populateContext($vo, $context); //rempli le context

		//ajout d'informations suppl�mentaires dans le contexte (�ventuellement)
		$ret=$this->_populateContextRelatedTables($vo, $context); 

		return $ret ? $ret : "_ok";
	}

	/**
	 * Impl�mentation par d�faut de l'action de copie d'un objet.
	 * R�cup�re l'objet que l'on veut cr�er et le copie en ajoutant un prefixe devant.
	 *
	 * copy an object Action
	 *
	 * @param array $context le tableau des donn�es pass� par r�f�rence.
	 * @param array $error le tableau des erreurs rencontr�es pass� par r�f�rence.
	 * @return string les diff�rentes valeurs possibles de retour d'une action (_ok, _back, _error ou xxxx).
	 */
	function copyAction(&$context, &$error)
	{
		$ret = $this->viewAction($context, $error);
		$copyof = getlodeltextcontents("copyof", "common");
		if (isset($context['name'])) {
			$context['name'] = $copyof. "_". $context['name'];
		} elseif (isset($context['type']) && !is_array($context['type'])) {
			$context['type'] = $copyof. "_". $context['type'];
		}
		if (isset($context['title'])) {
			$context['title'] = $copyof. " ". $context['title'];
		} elseif (isset($context['username'])) {
			$context['username'] = $copyof. "_". $context['username'];
		}
		unset($context['id']);
		return $ret;
	}

	/**
	 * Impl�menation de l'action d'ajout ou d'�dition d'un objet.
	 *
	 * <p>Cette fonction cr�e un nouvel objet ou �dite un objet existant. Dans un premier temps les
	 * donn�es sont valid�es (suivant leur type) puis elles sont rentr�es dans la base de donn�es <em>via</em> la DAO associ�e � l'objet.
	 * Utilise _prepareEdit() pour effectuer des op�rations de pr�paration avant l'�dition de l'objet puis _populateContext() pour ajouter des informations suppl�mentaires au context. Et enfin _saveRelatedTables() pour sauver d'�ventuelles informations dans des tables li�es.
	 * </p>
	
	 * add/edit Action
	 * @param array $context le tableau des donn�es pass� par r�f�rence.
	 * @param array $error le tableau des erreurs rencontr�es pass� par r�f�rence.
	 * @param boolean $clean false si on ne doit pas nettoyer les donn�es (par d�faut � false).
	 * @return string les diff�rentes valeurs possibles de retour d'une action (_ok, _back, _error ou xxxx).
	 */
	function editAction(&$context, &$error, $clean = false)
	{
		if ($clean != CLEAN) {      // validate the forms data
			if (!$this->validateFields($context, $error)) {
				return '_error';
			}
		}
		// get the dao for working with the object
		$dao = $this->_getMainTableDAO();
		$id = $context['id'] = intval($context['id']);
		$this->_prepareEdit($dao, $context);
		// create or edit
		if ($id) {
			$dao->instantiateObject($vo);
			$vo->id = $id;
		} else {
			$vo = $dao->createObject();
		}
		if ($dao->rights['protect']) {
			$vo->protect = $context['protected'] ? 1 : 0;
		}
		// put the context into 
		$this->_populateObject($vo, $context);
		if (!$dao->save($vo)) trigger_error("You don't have the rights to modify or create this object", E_USER_ERROR);
		$ret = $this->_saveRelatedTables($vo, $context);
		
		require_once 'func.php';
		update();
		return $ret ? $ret : "_back";
	}

	/**
	 * Impl�mentation par d�faut de l'action qui permet de changer le rang d'un objet. 
	 * 
	 * Cette action modifie la rang (rank) d'un objet. Peut-�tre restreinte � un status particulier
	 * et � un �tage particulier (groupe).
	 *
	 * Change rank action
	 * Default implementation
	 *
	 * @param array $context le tableau des donn�es pass� par r�f�rence.
	 * @param array $error le tableau des erreurs rencontr�es pass� par r�f�rence.
	 * @param string $groupfields champ de groupe. Utilis� pour limit� le changement de rang � un �tage. Par d�faut vide.
	 * @param string $status utilis� pour changer le rang d'objets ayant un status particulier. il s'agit d'une condition. Par d�faut est : status>0
	 * @return string les diff�rentes valeurs possibles de retour d'une action (_ok, _back, _error ou xxxx).
	 */
	function changeRankAction(&$context, &$error, $groupfields = "", $status = "status>0")
	{
		$criterias = array();
		$id = $context['id'];
		if ($groupfields) {
			$dao = $this->_getMainTableDAO();
			$vo  = $dao->getById($id, $groupfields);
			foreach (explode(",", $groupfields) as $field) {
				$criterias[] = $field. "='". $vo->$field. "'";
			}
		}
		if ($status) $criterias[] = $status;

		$criteria = join(" AND ", $criterias);
		$this->_changeRank($id, $context['dir'], $criteria);

		require_once 'func.php';
		update();
		return '_back';
	}

	/**
	 * Impl�mentation par d�faut de l'action qui permet de supprimer un objet.
	 * <p>Cette action v�rifie tout d'abord que l'objet peut-�tre supprim� puis pr�pare
	 * la suppression (fonction _prepareDelete()) et enfin utilise la DAO pour supprimer l'objet</p>
	 * Delete
	 * Default implementation
	 * @param array $context le tableau des donn�es pass� par r�f�rence.
	 * @param array $error le tableau des erreurs rencontr�es pass� par r�f�rence.
	 * @return string les diff�rentes valeurs possibles de retour d'une action (_ok, _back, _error ou xxxx).
	 */
	function deleteAction(&$context, &$error)
	{
		global $db, $home;
		$id = $context['id'];

		if ($this->isdeletelocked($id)) {
			trigger_error("This object is locked for deletion. Please report the bug", E_USER_ERROR);
		}
		$dao = $this->_getMainTableDAO();
		$this->_prepareDelete($dao, $context);
		$dao->deleteObject($id);

		$ret=$this->_deleteRelatedTables($id);

		require_once 'func.php';
		update();
		return $ret ? $ret : '_back';
	}


	/**
	 * Impl�mentation par d�faut de la fonction right
	 * 
	 * Cette fonction permet de retourner les droits pour un niveau d'acc�s particulier
	 * 
	 * Return the right for a given kind of access
	 * @param string $access le niveau d'acc�s
	 * @return integer entier repr�sentant le droit pour l'acc�s demand�.
	 */
	function rights($access) 
	{
		$dao = $this->_getMainTableDAO();
		return $dao->rights[$access];
	}

	/**
	 * Impl�mentation par d�faut de isdeletelocked()
	 *
	 * Indique si un objet donn� est supprimable pour l'utilisateur courant.
	 *
	 * Say whether an object (given by its id and status if possible) is deletable by the current user or not
	 * @param integer $id l'identifiant num�rique de l'objet
	 * @param integer $status status de l'objet. Par d�faut vaut 0.
	 * @return boolean un bool�en indiquant si l'objet peut �tre supprim�.
	 */
	function isdeletelocked($id, $status=0)
	{
		global $lodeluser;
		// basic
		$dao = $this->_getMainTableDAO();
		if (is_numeric($id)) {
			$criteria = "id='". $id. "'";
			$nbexpected = 1;
		} else {
			$criteria = "id IN ('". join("','", $id). "')";
			$nbexpected = count($id);
		}
		$nbreal = $dao->count($criteria. " ". $dao->rightsCriteria("write"));
		return $nbexpected != $nbreal;
	}


	//! Private or protected from this point
	/**#@+
	 * @access private
	 */
	
	function &_getMainTableDAO() 
	{
		return getDAO($this->maintable);
	}
   

	/**
	 * Change the rank of an Object
	 */
	function _changeRank($id, $dir, $criteria)
	{
		global $db;
		if (is_numeric($dir)) {
			$dir = $dir>0 ? 1 : -1;
		} else {
			$dir = $dir == "up" ? -1 : 1;
 		}

		$desc = $dir>0 ? "" : "DESC";

		$dao = $this->_getMainTableDAO();
		$vos = $dao->findMany($criteria, "rank $desc, id $desc", "id, rank");

		$count = count($vos);
		$newrank = $dir>0 ? 1 : $count;
		for ($i = 0 ; $i < $count ; $i++) {
			if ($vos[$i]->id == $id) {
				// exchange with the next if it exists
				if (!$vos[$i+1]) {
					break;
				}
				$vos[$i+1]->rank = $newrank;
				$dao->save($vos[$i+1]);
				$newrank+= $dir;
			}
			if ($vos[$i]->rank != $newrank) { // rebuild the rank if necessary
				$vos[$i]->rank = $newrank;
				$dao->save($vos[$i]);
			}
			if ($vos[$i]->id == $id) {
				$i++;
			}
			$newrank+= $dir;
		}
	}

	/**
	 * Validated the public fields and the unicity.
	 * @return return an array containing the error and warning, null otherwise.
	 */
	function validateFields(&$context, &$error) 
	{
		global $db;

		require_once "validfunc.php";
		$publicfields = $this->_publicfields();
		foreach ($publicfields as $field => $fielddescr) {
			list($type, $condition) = $fielddescr;
			if ($condition == "+" && $type != "boolean" && // boolean false are not transfered by HTTP/POST
					(
						!isset($context[$field]) ||   // not set
						$context[$field] === ""  // or empty string
					)) {
				$error[$field]="+";
			} elseif ($type == "passwd" && !trim($context[$field]) && $context['id']>0) {
				// passwd can be empty only if $context[id] exists... it is a little hack but.
				unset($context[$field]); // remove it
			} else {
				require_once 'validfunc.php';
				$valid = validfield($context[$field], $type, "");
				if ($valid === false) {
					die("ERROR: \"$type\" can not be validated in logic.php");
				}
				if (is_string($valid)) {
					$error[$field]=$valid;
				}
			}
		}
		if ($error) {
			return false;
		}

		$conditions=array();
		foreach ($this->_uniqueFields() as $fields) { // all the unique set of fields
			foreach ($fields as $field) { // set of fields which has to be unique.
				$conditions[] = $field. "='". $context[$field]. "'";
			}
			// check
			$ret = $db->getOne("SELECT 1 FROM ". lq("#_TP_". $this->maintable). " WHERE status>-64 AND id!='". $context['id']. "' AND ". join(" AND ", $conditions));
			if ($db->errorno()) {
				dberror();
			}
			if ($ret) {
				$error[$fields[0]] = "1"; // report the error on the first field
			}
		}

		return empty($error);
	}

	function _publicfields() 
	{
		die("call to abstract publicfields");
		return array();
	}

	function _uniqueFields() 
	{
		return array();
	}

	/**
	 * Populate the object from the context. Only the public fields are inputted.
	 * @private
	 */
	function _populateObject(&$vo, &$context) 
	{
		$publicfields = $this->_publicfields();
		foreach ($publicfields as $field => $fielddescr) {
			$vo->$field = $context[$field];
		}
	}

	/**
	 * Populate the context from the object. All fields are outputted.
	 * @protected
	 */
	function _populateContext(&$vo, &$context) 
	{
		foreach ($vo as $k=>$v) {
			//Added by Jean - Be carefull using it
			//if value is a string and we want to view it (or edit it in a form, 
			//open a form is a view action) then we htmlize it
			if (is_string($v) && $context['do'] == 'view') {
				$v = htmlspecialchars($v);
			}
			$context[$k] = $v;
		}
	}

	/**
	 * Used in editAction to do extra operation before the object is saved.
	 * Usually it gather information used after in _saveRelatedTables
	 */
	function _prepareEdit($dao, &$context) {}

	/**
	 * Used in deleteAction to do extra operation before the object is saved.
	 * Usually it gather information used after in _deleteRelatedTables
	 */
	function _prepareDelete($dao, &$context) {}

	/**
	 * Used in editAction to do extra operation after the object has been saved
	 */
	function _saveRelatedTables($vo, &$context) {}

	/**
	 * Used in deleteAction to do extra operation after the object has been deleted
	 */
	function _deleteRelatedTables($id) {}

	/**
	 * Used in viewAction to do extra populate in the context 
	 */
	function _populateContextRelatedTables(&$vo, &$context) {}
	
	/**
	 * process of particular type of fields
	 * @param string $type the type of the field
	 * @param array $context the context
	 * @param int $status the status; by default 0 if no status changed
	 */
	function _processSpecialFields($type, $context, $status = 0) 
	{
		global $db;
		$daoentities = &getDAO('entities');
		$vo = $daoentities->getById($context['id'], 'id, idtype');
		$daotype = &getDAO ("types");
		$votype = $daotype->getById ($vo->idtype, 'class');
		$class = $votype->class;
		unset($vo);	unset($votype);

		$daotablefields = &getDAO("tablefields");
		$fields = $daotablefields->findMany ("(class='". $class. "' OR class='entities_". $class. "') AND type='". $type. "' AND status>0 ", "",    "name, type, class");
		if($fields && $type == 'history') {
			$updatecrit = "";
			foreach ($fields as $field) {
				$value = "";
				$this->_calculateHistoryField ($value, $context, $status);
				if (isset ($context['data'][$field->name])) { //if a value for this field is in the context, use it (to allow user to modify the field
					$updatecrit = ($updatecrit ? "," : ""). $field->name. "=CONCAT('". $context['data'][$field->name]. "','\n". $value. "')";
				}
				else {
					$updatecrit = ($updatecrit ? "," : ""). $field->name. "=CONCAT(".$field->name. ",'\n". $value. "')";
				}
			}
			$db->execute (lq ("UPDATE #_TP_$class SET $updatecrit WHERE identity='". $context['id'].  "'"));
		}
	}

	/**
	 * special processing for particular types of field
	 * @param string $value the current value of the field
	 * @param array $context the current context
	 */
	function _calculateHistoryField(&$value, &$context, $status = 0) 
	{
		$dao = &getDAO('users');
		if($context['lodeluser']['adminlodel'] == 1) {
			usemaindb();
			$vo = $dao->getById ($context['lodeluser']['id']);
			usecurrentdb();
		}
		else {
			$vo = $dao->getById ($context['lodeluser']['id']);
		}
		if($context['id']) {
			//edition or change of status
			switch($status) {
			case 0:
				$line .= getlodeltextcontents('editedby', 'common');
				break;
			case 1:
				$line .= getlodeltextcontents('publishedby', 'common');
				break;
			case -1:
				$line .= getlodeltextcontents('unpublishedby', 'common');
				break;
			case 8:
				$line .= getlodeltextcontents('protectedby', 'common');
				break;
			case -8:
				$line .= getlodeltextcontents('draftedby','common');
				break;
			default: //creation
				$line .= getlodeltextcontents('createdby', 'common');
			}
			$line .= " ". ($vo->name ? $vo->name : ($vo->username ? $vo->username : $context['lodeluser']['name']));
			$line .= " ".getlodeltextcontents('on', 'common'). " ". date('d/m/Y H:i');
			$value .= ($value ? "\n" : ""). $line;
			unset($line);
		}
	}
	/**#@-*/

} // class Logic }}}


/*------------------------------------------------*/

/**
 * Logic factory
 *
 */
function &getLogic($table) 
{
	static $factory; // cache
	if ($factory[$table]) {
		return $factory[$table]; // cache
	}
	require_once "logic/class.$table.php";
	$logicclass = $table. 'Logic';
	return $factory[$table]= new $logicclass;
}


/**
 * function returning the right for $access in the table $table
 */
	function rights($table, $access)
	{
		static $cache;
		if (!isset($cache[$table][$access])) {
			$logic = &getLogic($table);
			$cache[$table][$access] = $logic->rights($access);
		}
		return $cache[$table][$access];
	}

/**
 * Pipe function to test if an object can be deleted or not
 * (with cache)
 */
function isdeletelocked($table, $id, $status = 0)
{
	static $cache;
	if (!isset($cache[$table][$id])) {
		$logic = &getLogic($table);
		$cache[$table][$id] = $logic->isdeletelocked($id, $status);
	}
	return $cache[$table][$id];
}
?>