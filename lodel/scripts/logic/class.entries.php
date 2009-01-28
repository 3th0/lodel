<?php
/**	
 * Logique des entr�es et des personnes
 *
 * PHP version 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Home page: http://www.lodel.org
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
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Sophie Malafosse
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajout� depuis la version 0.8
 * @version CVS:$Id$
 */

if(!class_exists('GenericLogic', false))
	require 'genericlogic.php';

/**
 * Classe de logique des entr�es
 * 
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajout� depuis la version 0.8
 * @see logic.php
 */
class EntriesLogic extends GenericLogic
{

	/**
	 * Constructeur
	 */
	public function __construct($logicname = 'entries')
	{
		parent::__construct($logicname);
		$this->daoname = 'entrytypes';
		$this->idtype = 'identry';
	}

	/**
	 * Affichage d'un objet (index ET persons)
	 *
	 * @param array &$context le contexte pass� par r�f�rence
	 * @param array &$error le tableau des erreurs �ventuelles pass� par r�f�rence
	 */
	public function viewAction (&$context, &$error) 
	{
		if (!$context['id']) $context['status']=32; //why ?
		$context['classtype']=$this->maintable;
		return parent::viewAction ($context, $error); //call the parent method
	}


	/**
	 * Publication d'une entr�e (index ET persons)
	 *
	 * @param array &$context le contexte pass� par r�f�rence
	 * @param array &$error le tableau des erreurs �ventuelles pass� par r�f�rence
	 */
	public function publishAction(&$context, &$error)
	{
		global $db;
		$dao = $this->_getMainTableDAO();
		$vo  = $dao->find('id=' . $context['id'], 'status,id');
		if (!$vo) {
			trigger_error("ERROR: interface error in EntriesLogic::publishAction ", E_USER_ERROR);
		}
		
		if ($vo->status <= 0) {
			$vo->status = abs($vo->status);
		} else {
			$vo->status = -abs($vo->status);
		}
		
		$dao->save($vo);		
		update();
		return '_back';
	}


	/**
	*  Indique si un objet est prot�g� en suppression (index ET persons)
	*
	* Cette m�thode indique si un objet, identifi� par son identifiant num�rique et
	* �ventuellement son status, ne peut pas �tre supprim�. Dans le cas o� un objet ne serait
	* pas supprimable un message est retourn� indiquant la cause. Sinon la m�thode renvoit le
	* booleen false.
	*
	* @param integer $id identifiant de l'objet
	* @param integer $status status de l'objet
	* @return false si l'objet n'est pas prot�g� en suppression, un message sinon
	*/
	public function isdeletelocked ($id, $status = 0)
	{
		global $db;

		// if this entry has child
		// OR is published AND permanent (status=32)
		$count = $db->getOne(lq("SELECT count(*) FROM #_TP_" . $this->maintable . " WHERE idparent ".sql_in_array($id)." AND status >-64"));
		$count += $db->getOne(lq("SELECT count(*) FROM #_TP_" . $this->maintable . " WHERE id ".sql_in_array($id)." AND status=32"));
		if ($db->errorno())  trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		if ($count==0) {
			return false;
		} else {
			return sprintf(getlodeltextcontents("cannot_delete_hasentrieschild","admin"),$count);
		}
	}

	/**
	 * Appel la liste des objet de cette logic : ici les entr�es (index ET persons)
	 *
	 * @param array &$context le contexte pass� par r�f�rence
	 * @param array &$error le tableau des erreurs �ventuelles pass� par r�f�rence
	 */
	public function listAction (&$context, &$error, $clean = false)
	{
		$daotype = &getDAO ($this->daoname);
		$votype = $daotype->getById($context['idtype']);
		if (!$votype) {
			trigger_error("ERROR: idtype must me known in GenericLogic::viewAction", E_USER_ERROR);
		}
		$this->_populateContext ($votype, $context['type']);
		return '_ok';
	}

	/**
	 * Ajout d'un nouvel objet ou Edition d'un objet existant (index seulement)
	 *
	 * Ajout d'une nouvelle entr�e
	 *
	 * @param array &$context le contexte pass� par r�f�rence
	 * @param array &$error le tableau des erreurs �ventuelles pass� par r�f�rence
	 */
	public function editAction (&$context, &$error, $clean=false) 
	{
		global $home;
		$id = $context['id'];
		$idtype=$context['idtype'];
		if (!$idtype) {
			trigger_error("ERROR: internal error in EntriesLogic::editAction", E_USER_ERROR);
		}
		$status = $context['status'];
		// get the class 
		$daotype = &getDAO ("entrytypes");
		$votype = $daotype->getById ($idtype, "class,newbyimportallowed,flat");
		$class = $context['class']=$votype->class;
		if ($clean!=CLEAN) {
			if (!$this->validateFields($context,$error)) {
				// error.
				// if the entity is imported and will be checked
				// that's fine, let's continue, if not return an error
				if ($status>-64) {
					return "_error";
				}
			}
		}
		$g_index_key = $this->getGenericEquivalent($class,'index key');
		if (!$g_index_key) {
			trigger_error("ERROR: The generic field 'index key' is required. Please edit your editorial model.", E_USER_ERROR);
		}
		// get the dao for working with the object
		$dao = $this->_getMainTableDAO ();
		if (isset ($context['g_name'])) {
			if (!$context['g_name']) return '_error'; // empty entry!
			// search if the entries exists
			$tmpgname = $context['g_name'];
			myaddslashes($tmpgname);
			$vo = $dao->find ("BINARY g_name='". $tmpgname. "' AND idtype='". $idtype."' AND status>-64","id,status");
			//$vo = $dao->find ("g_name='". $context['g_name']. "' AND idtype='". $idtype."' AND status>-64","id,status");
			if ($vo->id) {
				$context['id']=$vo->id;
				return; // nothing to do.
			} else {
				$context['data'][$g_index_key]=$context['g_name'];
			}
		}

		$index_key = &$context['data'][$g_index_key];
		$index_key = str_replace(',',' ',$index_key); // remove the , because it is a separator
		if ($context['lo'] == 'entries') {  // check it does not exist
			$tmpindex_key = $index_key;
			myaddslashes($tmpindex_key);
			$vo=$dao->find("BINARY g_name='". $tmpindex_key. "' AND idtype='". $idtype. "' AND status>-64 AND id!='".$id."'", 'id');
			//$vo=$dao->find("g_name='". $index_key. "' AND idtype='". $idtype. "' AND status>-64 AND id!='".$id."'", 'id');
			if ($vo->id) {
				$error[$g_index_key] = "1";
				return '_error';
			}
		}

		if (!$vo) {
			if ($id) { // create or edit the entity
				$new=false;
				$dao->instantiateObject ($vo);
				$vo->id=$id;
			} else {
				if (!$votype->newbyimportallowed && $context['lo']!="entries") { return "_error"; }
				$new=true;
				$vo=&$dao->createObject();
				$vo->status=$status ? $status : -1;
			}
		}
		if ($dao->rights['protect']) $vo->protect=$context['protected'] ? 1 : 0;
		if ($votype->flat) {
			$vo->idparent=0; // force the entry to be at root
		} else {
			$vo->idparent=(int)$context['idparent'];
		}
		// populate the entry table
		if ($idtype) $vo->idtype=$idtype;
		$vo->g_name=$index_key;
		$vo->sortkey=makeSortKey($vo->g_name);
		$id=$context['id']=$dao->save($vo);
		// save the class table
		$gdao=&getGenericDAO($class,"identry");
		$gdao->instantiateObject($gvo);
		$context['data']['id']=$context['id'];
		$this->_populateObject($gvo,$context['data']);
		$gvo->identry=$id;

		$this->_moveFiles($id,$this->files_to_move,$gvo);
		$gdao->save($gvo,$new);  // save the related table

		update();
		return "_back";
	}


	/**
	 * Changement du rang d'un objet (index seulement)
	 *
	 * @param array &$context le contexte pass� par r�f�rence
	 * @param array &$error le tableau des erreurs �ventuelles pass� par r�f�rence
	 */
	public function changeRankAction (&$context, &$error) 
	{
		return parent::changeRankAction(&$context, &$error, 'idparent', '');
	}

	/**
	 * Construction des balises select HTML pour cet objet (index seulement)
	 *
	 * @param array &$context le contexte, tableau pass� par r�f�rence
	 * @param string $var le nom de la variable du select
	 * @param string $edittype le type d'�dition
	 */
	public function makeSelect (&$context, $var) 
	{
		global $db;
		switch($var) {
		case 'idparent':
			$arr=array ();
			$rank=array ();
			$parent=array ();
			$ids=array (0);
			$l=1;
			do {
				$result=$db->execute (lq ("SELECT * FROM #_TP_entries WHERE idtype='".$context['idtype']."' AND id!='".$context['id']."' AND idparent ".sql_in_array ($ids). " AND ABS(status) = 32 ORDER BY ". $context['type']['sort'])) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				$ids=array();
				$i=0;
				while (!$result->EOF) {
					$id=$result->fields['id'];
					$ids[]=$id;	 
					$fullname=$result->fields['g_name'];
					$idparent=$result->fields['idparent'];
					if ($idparent) $fullname=$parent[$idparent]." / ".$fullname;
					do {
						$i++;
						$d=$rank[$id]=$rank[$idparent]+($i*1.0)/$l;
					} while(isset($arr["p$d"]));
					$arr["p$d"]=array($id,$fullname);
					$parent[$id]=$fullname;
					$i++;
					$result->MoveNext();
				} //end while
				$l*=100;
			} while ($ids); // end do while
			ksort ($arr);
			$arr2=array ("0"=>"--"); // reorganize the array $arr
			foreach ($arr as $row) {
				$arr2[$row[0]]=$row[1];
			}
			renderOptions ($arr2, $context[$var]);
			break;
		}
	} //end of function

	/**
	 * Appel� avant l'action delete (index ET persons)
	 *
	 * Cette m�thode est appel�e avant l'action delete pour effectuer des v�rifications
	 * pr�liminaires � une suppression.
	 *
	 * @param object $dao la DAO utilis�e
	 * @param array &$context le contexte pass� par r�f�r�nce
	 * @access private
	 */
	protected function _prepareDelete($dao, &$context) {
		global $db;
		// get the classes
		$this->classes = array ();

		// $this->daoname = persontypes OU entrytypes
		// $this->maintable = persons OU entries
		$result = $db->execute (lq ("SELECT DISTINCT class FROM #_TP_". $this->daoname . " INNER JOIN #_TP_" . $this->maintable . " ON idtype=#_TP_" . $this->daoname . ".id WHERE #_TP_" .$this->maintable. ".id ".sql_in_array ($context['id']))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		while (!$result->EOF) {
			$this->classes[] = $result->fields['class'];
			$result->MoveNext();
		}

		if (isset($context['idrelation'])) {
			$this->idrelation=$context['idrelation'];
		} else {
			$dao=&getDAO ('relations');
			$vos=$dao->findMany ("id2 ".sql_in_array ($context['id']));
			$this->idrelation=array ();
			foreach ($vos as $vo) {
				$this->idrelation[]=$vo->idrelation;
			}
		}
	}


	/**
	 * Used in deleteAction to do extra operation after the object has been deleted (index ET persons)
	 */
	protected function _deleteRelatedTables($id) 
	{
		global $db;
		foreach ($this->classes as $class) {
			$gdao=&getGenericDAO ($class, $this->idtype);
			$gdao->deleteObject ($id);
		
			if($this->maintable == 'persons') {
				if ($this->idrelation) {
					$gdao=&getGenericDAO("entities_".$class,"idrelation");
					$gdao->deleteObject($this->idrelation);
				}
			}
		}

		if ($this->idrelation) {
			$dao=&getDAO ('relations');
			$dao->delete ('idrelation '. sql_in_array ($this->idrelation));
		}
	}
} // class 
?>