<?php
/**	
 * Logique des classes d'objets du syst�me
 *
 * PHP version 4
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
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajout� depuis la version 0.8
 * @version CVS:$Id$
 */

/**
 * Classe de logique des classes du syst�me - Fille de la classe Logic
 * 
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajout� depuis la version 0.8
 * @see logic.php
 */
class ClassesLogic extends Logic 
{

	/**
	 * Constructeur
	 */
	function ClassesLogic()
	{
		$this->Logic ('classes');
	}

	/**
	 *  Indique si un objet est prot�g� en suppression
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
	function isdeletelocked($id, $status = 0)
	{
		global $db;
		$dao = $this->_getMainTableDAO ();
		$vo  = $dao->getById ($id, 'classtype');
		$types = ClassesLogic::typestable($vo->classtype);
		switch ($vo->classtype) {
			case 'entities':
				$msg = 'cannot_delete_hasentities';
				break;
			case 'entries':
				$msg = 'cannot_delete_hasentries';
				break;
			case 'persons':
				$msg = 'cannot_delete_haspersons';
				break;
		}
		$count = $db->getOne (lq ("SELECT count(*) FROM #_TP_". $vo->classtype. " INNER JOIN #_TP_". $types. " ON idtype=#_TP_". $types. ".id INNER JOIN #_TP_classes ON #_TP_".$types. ".class=#_TP_classes.class WHERE #_TP_classes.id='$id' AND #_TP_". $vo->classtype. ".status>-64 AND #_TP_". $types. ".status>-64  AND #_TP_classes.status>-64"));
		if ($db->errorno ()){
			dberror ();
		}
		if ($count == 0) {
			return false;
		} else {
			return sprintf (getlodeltextcontents ($msg, 'admin'), $count);
		}
	}

	/**
	 * Indique le nom de la table type associ�e avec le type de classe
	 *
	 * Return the type table associated with the classtype
	 * @param string $classtype le type de la classe
	 * @return une valeur parmis : type, entrytypes et persontypes
	*/
	function typestable ($classtype) 
	{
		switch ($classtype) {
		case 'entities':
			return 'types';
		case 'entries':
			return 'entrytypes';
		case 'persons' :
			return 'persontypes';
		}
	}

	/**
	 * Pr�paration de l'action Edit
	 *
	 * @access private
	 * @param object $dao la DAO utilis�e
	 * @param array &$context le context pass� par r�f�rence
	 */
	function _prepareEdit ($dao, &$context)
	{
		// gather information for the following
		if ($context['id']) {
			$this->oldvo = $dao->getById ($context['id']);
			if (!$this->oldvo) {
				die ("ERROR: internal error in Classes::deleteAction");
			}
		}
	}
	/**
	 * Sauve des donn�es dans des tables li�es �ventuellement
	 *
	 * Appel� par editAction pour effectuer des op�rations suppl�mentaires de sauvegarde.
	 *
	 * @param object $vo l'objet qui a �t� cr��
	 * @param array $context le contexte
	 */
	function _saveRelatedTables ($vo, $context) 
	{
		global $db;
		//----------------new, create the table
		if (!$this->oldvo->class) {
			switch($vo->classtype) {
			case 'entities' :
				$create = "identity	INTEGER UNSIGNED  UNIQUE, KEY index_identity (identity)";
				break;
			case 'entries' :
				$create = "identry	INTEGER UNSIGNED  UNIQUE, KEY index_identry (identry)";
				break;
			case 'persons' :
				$create = "idperson	INTEGER UNSIGNED  UNIQUE, KEY index_idperson (idperson)";
				$db->execute (lq ("CREATE TABLE IF NOT EXISTS #_TP_entities_". $vo->class." ( idrelation INTEGER UNSIGNED UNIQUE, KEY index_idrelation (idrelation) )")) or dberror ();
				break;
			}
			$db->execute(lq("CREATE TABLE IF NOT EXISTS #_TP_". $vo->class." ( ". $create." )")) or dberror ();
			$alter=true;
			//---------------- change class name ?
		} elseif ($this->oldvo->class!=$vo->class) {
			// change table name 
			$db->execute (lq ("RENAME TABLE #_TP_". $this->oldvo->class. " TO #_TP_". $vo->class)) or dberror ();
			if ($vo->classtype=="persons") {
				$db->execute (lq ("RENAME TABLE #_TP_entities_". $this->oldvo->class. " TO #_TP_entities_". $vo->class)) or dberror ();
			}
			// update tablefields, objects and types
			foreach (array ('objects', $this->typestable ($vo->classtype), 'tablefields', 'tablefieldgroups') as $table) {
				$db->execute (lq ("UPDATE #_TP_". $table. " SET class='". $vo->class. "' WHERE class='". $this->oldvo->class."'")) or dberror ();
			}
			$alter = true;
		}
		if ($alter) {        // update the CACHE ?
			require_once 'cachefunc.php';
			clearcache();
		}
	}

	/**
	 * Appel� avant l'action delete
	 *
	 * Cette m�thode est appel�e avant l'action delete pour effectuer des v�rifications
	 * pr�liminaires � une suppression.
	 *
	 * @param object $dao la DAO utilis�e
	 * @param array &$context le contexte pass� par r�f�r�nce
	 */
	function _prepareDelete ($dao, &$context) 
	{
		// gather information for the following
		$this->vo = $dao->getById ($context['id']);
		if (!$this->vo) {
			die ("ERROR: internal error in Classes::deleteAction");
		}
	}
	/**
	 * Suppression �ventuelle dans des tables li�es
	 *
	 * @param integer $id identifiant num�rique de l'objet supprim�
	 */
	function _deleteRelatedTables ($id) {
		global $db,$home;
		if (!$this->vo) die ("ERROR: internal error in Classes::deleteAction");
		$db->execute (lq ("DROP TABLE #_TP_".$this->vo->class)) or dberror ();
		if ($this->vo->classtype=="persons") {
			$db->execute(lq("DROP TABLE #_TP_entities_".$this->vo->class)) or dberror();
		}
		// delete associated types
		// collect the type to delete
		$dao=&getDAO (ClassesLogic::typestable ($this->vo->classtype));
		$types=$dao->findMany ("class='". $this->vo->class. "'", "id");
		$logic=&getLogic (ClassesLogic::typestable ($this->vo->classtype));
		foreach ($types as $type) {
			$localcontext['id']=$type->id;
			$logic->deleteAction ($localcontext, $err);
		}
		// delete tablefields and tablefieldgroups
		$criteria="class='".$this->vo->class."'";
		if ($this->vo->classtype=="persons") {
			$criteria.=" OR class='entities_".$this->vo->class."'";
		}
		$dao=&getDAO ("tablefields");
		$dao->deleteObjects ($criteria);

		// delete tablefields
		$dao=&getDAO ("tablefieldgroups");
		$dao->deleteObjects ($criteria);
		unset ($this->vo);

		// should be in the view....
		require_once("cachefunc.php");
		clearcache();
		return "_back";
	}


	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	function _publicfields() 
	{
		return array('class' => array('class', '+'),
									'classtype' => array('text', '+'),
									'title' => array('text', '+'),
									'altertitle' => array('mltext', '+'),
									'comment' => array('longtext', ''));
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	/**
	 * Retourne la liste des champs uniques
	 * @access private
	 */
	function _uniqueFields() 
	{ 
		return array(array('class'), );
	}
	// end{uniquefields} automatic generation  //

} // class 

/*-----------------------------------*/
/* loops                             */
?>