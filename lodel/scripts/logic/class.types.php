<?php
/**	
 * Logique des types
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


/*$GLOBALS['importdocument']=array(
				 0=>array("url"=>"document.php",
					  "title"=>"[@COMMON.FORM]"),

				 1=>array("url"=>"oochargement.php",
					  "title"=>"[@COMMON.IMPORT_FROM_SERVOO]")

				 //				 100=>array("url"=>"biblioimport.php",
				 //					    "titre"=>"BibImport")
				 );*/


/**
 * Classe de logique des types
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
class TypesLogic extends Logic {

	/** Constructor
	*/
	function TypesLogic() {
		$this->Logic("types");
	}


	/**
	 * Affichage d'un objet
	 *
	 * @param array &$context le contexte pass� par r�f�rence
	 * @param array &$error le tableau des erreurs �ventuelles pass� par r�f�rence
	 */
	function viewAction(&$context,&$error)

	{
		if ($error) return;
		if (!$context['id']) {
			// creation
			$context['creationstatus']=-1;
			$context['search']=1;
			$context['tpledition']="edition";
			$context['tplcreation']="entities";
			return "_ok";
		}

		return Logic::viewAction($context,$error);
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
	function isdeletelocked($id,$status=0) 

	{
		global $db;
		$count=$db->getOne(lq("SELECT count(*) FROM #_TP_entities WHERE idtype='$id' AND status>-64"));
		if ($db->errorno())  dberror();
		if ($count==0) {
			return false;
		} else {
			return sprintf(getlodeltextcontents("cannot_delete_hasentity","admin"),$count);
		}
		//) { $error["error_has_entities"]=$count; return "_back"; }
	}


	/**
	 * Changement du rang d'un objet
	 *
	 * @param array &$context le contexte pass� par r�f�rence
	 * @param array &$error le tableau des erreurs �ventuelles pass� par r�f�rence
	 */
	function changeRankAction(&$context, &$error)

	{
		return Logic::changeRankAction(&$context, &$error, 'class');
	}


	/**
		*
		*/

	function makeSelect(&$context,$var)

	{
		switch($var) {
		case "import" :
			/* foreach($GLOBALS['importdocument'] as $n=>$v) {
			#echo "bla :".strpos($v['title'],"[@"); 
	$arr[]=strpos($v['title'],"[@")!==false ? getlodeltextcontents($v['title']) : $v['title']; 
			}*/
			#	print_r($arr);
			$arr[] = getlodeltextcontents('form','common');
			$arr[] = getlodeltextcontents('import_from_servoo','common');
			renderOptions($arr,$context['import']);
			break;
		case "display" :
			$arr=array(""=>getlodeltextcontents("folded","admin"),
			"unfolded"=>getlodeltextcontents("unfolded","admin"),
			"advanced"=>getlodeltextcontents("advanced_functions","admin")
				);
			renderOptions($arr,$context['display']);
			break;
		case "creationstatus" :
			$arr=array("-8"=>getlodeltextcontents("draft","common"),
			"-1"=>getlodeltextcontents("ready_for_publication","common"),
			"1"=>getlodeltextcontents("published","common"),
			"8"=>getlodeltextcontents("protected","common"),
			"17"=>getlodeltextcontents("locked","common"));
			renderOptions($arr,$context['creationstatus']);
			break;
		case 'gui_user_complexity' :
			require_once 'commonselect.php';
			makeSelectGuiUserComplexity($context['gui_user_complexity']);
			break;
		}
	}
		

	/*---------------------------------------------------------------*/
	//! Private or protected from this point
	/**
		* @private
		*/

	/**
	* Sauve des donn�es dans des tables li�es �ventuellement
	*
	* Appel� par editAction pour effectuer des op�rations suppl�mentaires de sauvegarde.
	*
	* @param object $vo l'objet qui a �t� cr��
	* @param array $context le contexte
	*/
	function _saveRelatedTables($vo,$context) 

	{
		require_once("typetypefunc.php");

		if ($context['id']) {
			//typetype_delete("entrytype","identitytype='".$context['id']."'");
			//typetype_delete("persontype","identitytype='".$context['id']."'");
			typetype_delete("entitytype","identitytype='".$context['id']."'");
		}
		//typetype_insert($vo->id,$context['entrytype'],"entrytype");
		//typetype_insert($vo->id,$context['persontype'],"persontype");
		typetype_insert($vo->id,$context['entitytype'],"entitytype2");
	}



	function _deleteRelatedTables($id) {
		global $home;

		require_once("typetypefunc.php"); 
		$criteria="(identitytype ".sql_in_array($id)." OR identitytype2 ".sql_in_array($id).")";
		typetype_delete("entitytype",$criteria);
	}



	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	function _publicfields() 
	{
		return array('type' => array('type', '+'),
									'title' => array('text', '+'),
									'altertitle' => array('mltext', '+'),
									'class' => array('class', '+'),
									'tplcreation' => array('tplfile', ''),
									'import' => array('select', '+'),
									'creationstatus' => array('select', '+'),
									'public' => array('boolean', '+'),
									'gui_user_complexity' => array('select', '+'),
									'tpledition' => array('tplfile', ''),
									'display' => array('select', ''),
									'tpl' => array('tplfile', ''),
									'search' => array('boolean', '+'),
									'oaireferenced' => array('boolean', '+'));
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	/**
	 * Retourne la liste des champs uniques
	 * @access private
	 */
	function _uniqueFields() 
	{ 
		return array(array('type', 'class'), );
	}
	// end{uniquefields} automatic generation  //


} // class 


/*-----------------------------------*/
/* loops                             */


//function loop_persontypes($context,$funcname)
//{ require_once("typetypefunc.php"); 
//  loop_typetable ("persontype","entitytype",$context,$funcname,$_POST['edit'] ? $context['persontype'] : -1);}
//
//function loop_entrytypes($context,$funcname)
//{ require_once("typetypefunc.php"); 
//  loop_typetable ("entrytype","entitytype",$context,$funcname,$_POST['edit'] ? $context['entrytype'] : -1);}


function loop_entitytypes($context,$funcname)
{ require_once("typetypefunc.php"); 
	#loop_typetable ("entitytype2","entitytype",$context,$funcname,$_POST['edit'] ? $context['entitytype'] : -1);

loop_typetable ("entitytype2","entitytype",$context,$funcname,$context['entitytype']);
}






?>
