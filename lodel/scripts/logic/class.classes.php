<?php
/**	
 * Logique des classes d'objets du syst�me
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

/**
 * Classe de logique des classes du syst�me - Fille de la classe Logic
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
class ClassesLogic extends Logic 
{

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		parent::__construct('classes');
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
	public function isdeletelocked($id, $status = 0)
	{
		global $db;
		$dao = $this->_getMainTableDAO ();
		$vo  = $dao->getById ($id, 'classtype');
		$types = $this->typestable($vo->classtype);
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
			trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
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
	public function typestable ($classtype) 
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
	protected function _prepareEdit ($dao, &$context)
	{
		// gather information for the following
		if ($context['id']) {
			$this->oldvo = $dao->getById ($context['id']);
			if (!$this->oldvo) {
				trigger_error("ERROR: internal error in Classes::deleteAction", E_USER_ERROR);
			}
		}
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
	public function editAction(&$context, &$error, $clean = false)
	{
		if ($clean != 'CLEAN') {      // validate the forms data
			if (!$this->validateFields($context, $error)) {
				return '_error';
			}
		}
		if(!function_exists('reservedByLodel'))
			include 'fieldfunc.php';
		if(reservedByLodel($context['class'])) {
			$error['class'] = 'reservedsql';
			return '_error';
		}
		// get the dao for working with the object
		$dao = $this->_getMainTableDAO();
		$id = $context['id'];
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
		
		update();
		return $ret ? $ret : "_back";
	}
	/**
	 * Sauve des donn�es dans des tables li�es �ventuellement
	 *
	 * Appel� par editAction pour effectuer des op�rations suppl�mentaires de sauvegarde.
	 *
	 * @param object $vo l'objet qui a �t� cr��
	 * @param array $context le contexte
	 */
	protected function _saveRelatedTables ($vo, $context) 
	{
		global $db;

		$alter = false;
		//----------------new, create the table
		if (!isset($this->oldvo->class)) {
			switch($vo->classtype) {
			case 'entities' :
				$create = "identity	INTEGER UNSIGNED  UNIQUE, KEY index_identity (identity)";
				break;
			case 'entries' :
				$create = "identry	INTEGER UNSIGNED  UNIQUE, KEY index_identry (identry)";
				break;
			case 'persons' :
				$create = "idperson	INTEGER UNSIGNED  UNIQUE, KEY index_idperson (idperson)";
				$db->execute (lq ("CREATE TABLE IF NOT EXISTS #_TP_entities_". $vo->class." ( idrelation INTEGER UNSIGNED UNIQUE, KEY index_idrelation (idrelation) )")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				break;
			}
			$db->execute(lq("CREATE TABLE IF NOT EXISTS #_TP_". $vo->class." ( ". $create." )")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			$alter=true;
			//---------------- change class name ?
		} elseif ($this->oldvo->class!=$vo->class) {
			// change table name 
			$db->execute (lq ("RENAME TABLE #_TP_". $this->oldvo->class. " TO #_TP_". $vo->class)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			if ($vo->classtype=="persons") {
				$db->execute (lq ("RENAME TABLE #_TP_entities_". $this->oldvo->class. " TO #_TP_entities_". $vo->class)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				$db->execute (lq ("UPDATE #_TP_tablefields SET class='entities_". $vo->class. "' WHERE class='entities_". $this->oldvo->class."'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
			// update tablefields, objects and types
			foreach (array ('objects',
					$this->typestable ($vo->classtype),
					'tablefields',
					'tablefieldgroups')
				as $table) {
				$db->execute (lq ("UPDATE #_TP_". $table. " SET class='". $vo->class. "' WHERE class='". $this->oldvo->class."'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
			$alter = true;
		}
		if ($alter) {        // update the CACHE ?
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
	protected function _prepareDelete ($dao, &$context) 
	{
		// gather information for the following
		$this->vo = $dao->getById ($context['id']);
		if (!$this->vo) {
			trigger_error("ERROR: internal error in Classes::deleteAction", E_USER_ERROR);
		}
	}
	/**
	 * Suppression �ventuelle dans des tables li�es
	 *
	 * @param integer $id identifiant num�rique de l'objet supprim�
	 */
	protected function _deleteRelatedTables ($id) {
		global $db;
		if (!$this->vo) trigger_error("ERROR: internal error in Classes::deleteAction", E_USER_ERROR);
		$db->execute (lq ("DROP TABLE #_TP_".$this->vo->class)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		if ($this->vo->classtype=="persons") {
			$db->execute(lq("DROP TABLE #_TP_entities_".$this->vo->class)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}
		// delete associated types
		// collect the type to delete
		$types=getDAO ($this->typestable ($this->vo->classtype))->findMany ("class='". $this->vo->class. "'", "id");
		$logic=getLogic ($this->typestable ($this->vo->classtype));
		foreach ($types as $type) {
			$localcontext['id']=$type->id;
			$logic->deleteAction ($localcontext, $err);
		}
		// delete tablefields and tablefieldgroups
		$criteria="class='".$this->vo->class."'";
		if ($this->vo->classtype=="persons") {
			$criteria.=" OR class='entities_".$this->vo->class."'";
		}
		getDAO ("tablefields")->deleteObjects ($criteria);

		// delete tablefields
		getDAO ("tablefieldgroups")->deleteObjects ($criteria);
		unset ($this->vo);

		clearcache();
		return "_back";
	}


	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array('class' => array('class', '+'),
									'classtype' => array('text', '+'),
									'title' => array('text', '+'),
									'altertitle' => array('mltext', ''),
									'icon' => array('image', ''),
									'comment' => array('longtext', ''));
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	/**
	 * Retourne la liste des champs uniques
	 * @access private
	 */
	protected function _uniqueFields() 
	{ 
		return array(array('class'), );
	}
	// end{uniquefields} automatic generation  //

} // class 
?>