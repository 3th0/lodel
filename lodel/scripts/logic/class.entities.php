<?php
/**	
 * Logique des entit�s
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
 * @version CVS:$Id$
 */



/**
 * Classe de logique des entit�s
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
 * @copyright 2008, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * @copyright 2009, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajout� depuis la version 0.8
 * @see logic.php
 */
class EntitiesLogic extends Logic
{

	/**
	* generic equivalent assoc array
	*/
	public $g_name;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct("entities");
	}


	/**
	 * Affichage d'un objet
	 *
	 * @param array &$context le contexte pass� par r�f�rence
	 * @param array &$error le tableau des erreurs �ventuelles pass� par r�f�rence
	 */
	public function viewAction(&$context, &$error)
	{
		trigger_error("EntitiesLogic::viewAction", E_USER_ERROR);
	}


	/**
	 * Changement du rang d'un objet
	 *
	 * @param array &$context le contexte pass� par r�f�rence
	 * @param array &$error le tableau des erreurs �ventuelles pass� par r�f�rence
	 */
	public function changeRankAction(&$context, &$error, $groupfields = "", $status = "status>0")
	{
		global $db;
		$id  = $context['id'];
		$vo  = $this->_getMainTableDAO()->getById($id,"idparent");
		$this->_changeRank($id,$context['dir'], "status<64 AND idparent='". $vo->idparent. "'");
		update();
		return '_back';
	}

	/**
	 * Ajout d'un nouvel objet ou Edition d'un objet existant
	 *
	 * Cette m�thode est abstraite ici. On utilise die() pour simuler le fonctionnement
	 * d'une m�thode abstraite.
	 *
	 * @param array &$context le contexte pass� par r�f�rence
	 * @param array &$error le tableau des erreurs �ventuelles pass� par r�f�rence
	 */
	public function editAction(&$context,&$error, $clean = false)
	{
		trigger_error("EntitiesLogic::editAction", E_USER_ERROR);
	}


	/**
	 * Op�rations de masse : suppression massive, publication ou d�publication massive
	 *
	 * @param array &$context le contexte pass� par r�f�rence
	 * @param array &$error le tableau des erreurs �ventuelles pass� par r�f�rence
	 */
	public function massAction(&$context,&$error)
	{
		if (empty($context['entity'])) {
			return "_back";
		}
		$context['id'] = array();
        	$entities = array_keys($context['entity']);
		foreach($entities as $id) {
			$context['id'][] = (int)$id;
		}

		if (isset($context['delete'])) {
			return $this->deleteAction($context,$error);
		} elseif (isset($context['publish'])) {
			$context['status'] = 1;
			return $this->publishAction($context,$error);
		} elseif (isset($context['unpublish'])) {
			$context['status'] = -1;
			return $this->publishAction($context,$error);
		}
		trigger_error("unknow mass operation",E_USER_ERROR);
	}


	/**
	 * Suppression d'un objet
	 *
	 * @param array &$context le contexte pass� par r�f�rence
	 * @param array &$error le tableau des erreurs �ventuelles pass� par r�f�rence
	 */
	public function deleteAction(&$context, &$error)
	{
		global $db;
		// get the entities to modify and ancillary information
		if (!rightonentity("delete",$context)) trigger_error("ERROR: you don't have the right to perform this operation", E_USER_ERROR);
		$ids = $classes = $softprotectedids = $lockedids = null;
		$this->_getEntityHierarchy($context['id'],"write","",$ids,$classes,$softprotectedids,$lockids);
		if (!$ids) {
			return '_back';
		}
		if ($lockedids)  {
			trigger_error("ERROR: some entities are locked in the family. No operation is allowed", E_USER_ERROR);
		}

		// needs confirmation ?
		if (!isset($context['confirm']) && $softprotectedids) {
			$context['softprotectedentities'] = $softprotectedids;
			$this->define_loop_protectedentities();
			return 'delete_confirm';
		}

		// delete all the entities
		$this->_getMainTableDAO()->deleteObject($ids);

		// delete in the joint table
		if(!empty($classes))
		{
			$classes = array_keys($classes);
		}

		foreach($classes as $class) {
			$db->execute(lq("DELETE FROM #_TP_$class WHERE identity ".sql_in_array($ids))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}

		// delete the relations
		$this->_deleteSoftRelation($ids);

		// delete other relations
		$db->execute(lq("DELETE FROM #_TP_relations WHERE id1 ".sql_in_array($ids)." OR id2 ".sql_in_array($ids))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		// delete the entity from the search_engine table
		$db->execute(lq("DELETE FROM #_TP_search_engine WHERE identity ".sql_in_array($ids))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		update();
		return '_back';
	}

	/**
	 * Publication ou d�publication d'une entit�
	 *
	 * Change le status de l'entit� � 1 (publication) ou -1 (d�publication).
	 * Fonction r�cursive
	 * Ne modifie pas les entit�s dont le status est inf�rieur ou �gal � -8
	 *
	 * @param array &$context le contexte pass� par r�f�rence
	 * @param array &$error le tableau des erreurs �ventuelles pass� par r�f�rence
	 */

	public function publishAction (&$context, &$error) 
	{
		global $db;
		$status = (int)$context['status'];
		$this->_isAuthorizedStatus($status);
		if ($status == 0) {
			trigger_error("error in publishAction", E_USER_ERROR);
		}
		if (!rightonentity ($status > 0 ? 'publish' : 'unpublish', $context)) {
			trigger_error("ERROR: you don't have the right to perform this operation", E_USER_ERROR);
		}

		// get the entities to modify and ancillary information
		$access = abs ($status) >= 32 ? 'protect' : 'write';
		$this->_getEntityHierarchy($context['id'], $access,"#_TP_entities.status>-8", $ids, $classes, $softprotectedids, $lockedids);

		if (!$ids) {
			return '_back';
		}

		if ($lockedids && $status < 0) {
			trigger_error("ERROR: some entities are locked in the family. No operation is allowed", E_USER_ERROR);
		}

		// depublish protected entity ? need confirmation.
		if ((!isset($context['confirm']) || !$context['confirm']) && $status < 0 && $softprotectedids) {
			$context['softprotectedentities'] = $softprotectedids;
			$this->define_loop_protectedentities();
			return 'unpublish_confirm';
		}
		$criteria=" id IN (". join(",", $ids). ')';

		// mais attention, il ne faut pas reduire le status quand on publie
		if ($status > 0) {
			$criteria.= " AND status < '$status'";
		}
		//mise � jour des entit�s
		$db->execute(lq("UPDATE #_TP_entities SET status=$status WHERE ". $criteria)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		// check if the entities have an history field defined
		$this->_processSpecialFields('history', $context, $status);

		//mise � jour des personnes et entr�es li�es � ces entit�s
		$this->_publishSoftRelation($ids, $status);
		update();
		return '_back';
		}

	
	

	/**
	 * Suppressions des relations entre une entit� et des persons et des entries
	 *
	 * Dans la table relations, le champ nature = G ou E (G = gens, E=entr�es)
	 * @access private
	 * @param array $ids les identifiants num�riques des entit�s
	 */
	protected function _deleteSoftRelation($ids) 
	{
		// most of this should be transfered in the entries and persons logic
		global $db;
		$criteria = 'id1 '. sql_in_array($ids);
		$result = $db->execute(lq("SELECT idrelation,nature FROM #_TP_relations WHERE $criteria AND nature IN ('G','E')")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		$idrelation = array();
		while(!$result->EOF) {
			$nature = $result->fields['nature'];
			$idrelation[$nature][] = $result->fields['idrelation'];
			$result->MoveNext();
		}

		// select all the items not in entities_$table
		// those with status<=1 must be deleted
		// thise with status> must be depublished
		foreach(array_keys($idrelation) as $nature) {
			$idlist=join(",",$idrelation[$nature]);
			$table=$nature=='G' ? "persons" : "entries";
			$db->execute(lq("DELETE FROM #_TP_relations WHERE idrelation IN (".$idlist.")")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

			$result=$db->execute(lq("SELECT id,status FROM #_TP_$table LEFT JOIN #_TP_relations ON id2=id WHERE id1 is NULL")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	
			$idstodelete=array();
			$idstounpublish=array();
			while (!$result->EOF) {
				if (abs($result->fields['status'])==1) {
					$idstodelete[]=$result->fields['id']; 
				} else {
					$idstounpublish[]=$result->fields['id']; 
				}
				$result->MoveNext();
			}

			if ($idstodelete) {
				$logic=getLogic($table);
				$localcontext=array("id"=>$idstodelete,"idrelation"=>$idrelation[$nature]);
				$localerror=array();
				$logic->deleteAction($localcontext,$localerror);
			}

			if ($idstounpublish) {
			// should be in $table dao or logic
			$db->execute(lq("UPDATE #_TP_$table SET status=-abs(status) WHERE id IN (". join(",", $idstounpublish). ") AND status>=32")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR); 
			}
		} // tables
	}

	/**
	 * Mise � jour du status des objets li�es (liaisons 'soft', c'est � dire des personnes 
	 * ou des entr�es d'index.
	 *
	 * Lors d'une publication c'est simple, le status des entr�es ou personnes li�es � l'entit�
	 * est mis � +32 ou +1 suivant si l'entr�e ou la personne est permanente.
	 *
	 * Lors d'une d�publication, c'est plus compliqu�, il ne faut pas toucher aux entr�es qui ont
	 * publi�es par d'autres entit�s. Ensuite de la m�me mani�re le status est mis � -32 ou -1
	 *
	 * @param array les identifiants
	 * @param integer le status de l'entit� concern�e ou des entit�s concern�es
	 * @access private
	 */
	public function _publishSoftRelation($ids, $status)
	{
		global $db;
		$criteria = "id1 IN (". join(",", $ids). ")";
		$status = $status > 0 ? 1 : -1; // dans les tables le status est seulement a +1 ou -1
		$result = $db->execute(lq("SELECT id2,nature FROM #_TP_relations WHERE nature IN ('E','G') AND ". $criteria)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		$ids = array();
		while (!$result->EOF) {
			$ids[$result->fields['nature']][$result->fields['id2']] = true;
			$result->MoveNext();
		}
		if (!$ids) {
			return; // get back, nothing to do
		}
		foreach(array_keys($ids) as $nature) {
			$idlist = join(',', array_keys($ids[$nature]));
			$table = $nature == 'G' ? 'persons' : 'entries';

			//------- PUBLISH ---------
			if ($status > 0) {
				// simple : on doit mettre le status � positif : +32 ou +1 si l'entree ou la personne
				// est permanente ou non
				
				$db->execute(lq("UPDATE #_TP_$table SET status=abs(status) WHERE id IN ($idlist)")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	
			//------- UNPUBLISH ---------
			} else { // status < 0
				// plus difficile. On v�rifie si les entries ou persons sont attach�s � des entit�s publi�es.
				$result =  $db->execute(lq("SELECT id1,id2 FROM #_TP_relations INNER JOIN #_TP_entities ON id1=id WHERE #_TP_entities.status>0 AND id2 IN (". $idlist. ") AND nature='".$nature."' GROUP BY id2")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				while (!$result->EOF) {
					unset($ids[$nature][$result->fields['id2']]); // remove the id from the list to unpublish
					$result->MoveNext();
				}
				if ($ids[$nature]) {
					$idlist = join(',', array_keys($ids[$nature]));
					// d�publie les entr�es ou personnes qui n'ont pas �t� publi�s par d'autres entit�s :
					$db->execute(lq("UPDATE #_TP_$table SET status=-abs(status) WHERE id IN ($idlist)")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				}
			} // status < 0
		} // foreach
	}

	/**
	 * R�cup�re une entit� et tous ses fils
	 *
	 * R�cup�re une entit� et tous ses fils pour une op�ration donn�e et par acc�s.
	 * On obtiens une liste d'identifiant, d'entit� prot�g�s et les classes auxquelles elles
	 * appartiennent.
	 *
	 * @param integer $id Identifiant de l'entit�
	 * @param string $access l'acc�s
	 * @param string $criteria les crit�res de s�lections
	 * @param array &$ids les identifiants des fils et de l'entit�, tableau pass� par r�f�rence
	 * @param array &$classes les classes des differentes entit�s de $ids, tableau pass� par
	 * r�f�rence
	 * @param array &$softprotectedids les entit�s prot�g�s de $ids, tableau pass� par r�f�rence
	 * @param array &$lockedids les entit�s verrouill�es de $ids, tableau pass� par r�f�rence
	 */
	protected function _getEntityHierarchy($id, $access, $criteria, &$ids, &$classes, &$softprotectedids, &$lockedids)
	{
		global $db;

		// check the rights to $access the current entity
		$hasrights="(1 ".$this->_getMainTableDAO()->rightsCriteria($access).") as hasrights";

		// get the central object
		if ($criteria) {
			$criteria=" AND ".$criteria;
		}
		$result = $db->execute(lq("SELECT #_TP_entities.id,#_TP_entities.status,$hasrights,class FROM #_entitiestypesjoin_ WHERE #_TP_entities.id ".sql_in_array($id).$criteria));

		// list the entities
		$ids              = array();
		$classes          = array();
		$softprotectedids = array();
		$lockedids        = array();
		while (!$result->EOF) {
			if (!$result->fields['hasrights']) trigger_error("This object is locked. Please report the bug",E_USER_ERROR);
			if ($result->fields['id']>0) $ids[]=$result->fields['id'];
			$classes[$result->fields['class']]=true;
			if ($result->fields['status']>=8) $softprotectedids[]=$result->fields['id'];      
			if ($result->fields['status']>=16) $lockedids[]=$result->fields['id'];      
			$result->MoveNext();
		}
		
		// check the rights to delete the sons and get their ids
		// criteria to determin if one of the sons is locked
		$result = $db->execute(lq("SELECT #_TP_entities.id,#_TP_entities.status,$hasrights,class FROM #_entitiestypesjoin_ INNER JOIN #_TP_relations ON id2=#_TP_entities.id WHERE id1 ". sql_in_array($id). " AND nature='P' ". $criteria)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		while (!$result->EOF) {
			if (!$result->fields['hasrights']) trigger_error("This object is locked. Please report the bug",E_USER_ERROR);
			if ($result->fields['id']>0) $ids[]=$result->fields['id'];
			$classes[$result->fields['class']]=true;
			if ($result->fields['status']>=8) $softprotectedids[]=$result->fields['id'];
			if ($result->fields['status']>=16) $lockedids[]=$result->fields['id'];
			$result->MoveNext();
		}
	}
	
	protected function define_loop_protectedentities()
	{
		function loop_protectedentities($context,$funcname) {
			global $db;
			$result=$db->execute(lq("SELECT * FROM #_TP_entities WHERE id ".sql_in_array($context['softprotectedentities']))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			while(!$result->EOF) {
				$localcontext=array_merge($context,$result->fields);
				call_user_func("code_do_$funcname",$localcontext);
				$result->MoveNext();
			}
		} // loop
	}

	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array();
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	// end{uniquefields} automatic generation  //


} // class 

?>