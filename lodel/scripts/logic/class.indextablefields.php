<?php
/**	
 * Logique des champs d'index
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

if(!class_exists('TableFieldsLogic', false))
	require("logic/class.tablefields.php");

/**
 * Classe de logique des champs d'indexs
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
class IndexTableFieldsLogic extends TableFieldsLogic {

	/** Constructor
	*/
	public function __construct() {
		parent::__construct();
	}

	/**
		* edit/add an object Action
		*/
	public function editAction(&$context,&$error)
	{
		$context['condition']="*";
		return parent::editAction($context,$error);
	}

	/**
		*
		*/
	public function makeSelect(&$context,$var)
	{
		global $home;

		switch($var) {
		case "name" :       
			$dao=&getDAO($context['type']=='entries' ? "entrytypes" : "persontypes");
			$vos=$dao->findMany("status>0","rank,title","type,title");
			foreach($vos as $vo) {
				$arr[$vo->type]=$vo->title;
			}
			renderOptions($arr,$context['name']);
			break;
		case "edition" :
			$arr=array(
			"editable"=>getlodeltextcontents("edit_in_the_interface","admin"),
			"importable"=>getlodeltextcontents("no_edit_but_import","admin"),
			"none"=>getlodeltextcontents("no_change","admin"),
			"display"=>getlodeltextcontents("display_no_edit","admin"),
			);
			renderOptions($arr,$context['edition']);
			break;
		default:
			parent::makeSelect($context,$var);
			break;
		}
	}

	/*---------------------------------------------------------------*/
	//! Private or protected from this point
	/**
		* @private
		*/

	/**
	* Pr�paration de l'action Edit
	*
	* @access private
	* @param object $dao la DAO utilis�e
	* @param array &$context le context pass� par r�f�rence
	*/
	protected function _prepareEdit($dao,&$context)
	{
	}
	/**
	* Sauve des donn�es dans des tables li�es �ventuellement
	*
	* Appel� par editAction pour effectuer des op�rations suppl�mentaires de sauvegarde.
	*
	* @param object $vo l'objet qui a �t� cr��
	* @param array $context le contexte
	*/
	protected function _saveRelatedTables($vo,$context) 
	{
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
	protected function _prepareDelete($dao,&$context)
	{     
	}

	protected function _deleteRelatedTables($id)
	{
	}


	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array('name' => array('select', '+'),
									'class' => array('class', '+'),
									'title' => array('text', '+'),
									'altertitle' => array('mltext', ''),
									'edition' => array('select', ''),
									'idgroup' => array('select', '+'),
									'type' => array('class', '+'),
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
		return array(array('name', 'class'), );
	}
	// end{uniquefields} automatic generation  //


} // class 
?>