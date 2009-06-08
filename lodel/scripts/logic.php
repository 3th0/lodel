<?php
/**
 * Fichier de la classe Logic
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
 * @author Sophie Malafosse
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
	protected $maintable;

	/**
	 * crit�re SQL du rang
	 * Give the SQL criteria which make a group from the ranking point of view.
	 * @var string
	 */
	protected $rankcriteria;
	/**#@-*/


	/** 
	 * Constructeur de la classe.
	 * 
	 * Positionne simplement le nom de la table principale.
	 * @param string $maintable le nom de la table principale.
	 */
	public function __construct($maintable) 
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
	public function viewAction(&$context, &$error)
	{
		if ($error) return; // nothing to do if it is an error.
		$id = $context['id'];
		if (!$id) return "_ok"; // just add a new Object
		$vo  = $this->_getMainTableDAO()->getById($id);
		if (!$vo) //erreur critique
			trigger_error("ERROR: can't find object $id in the table ". $this->maintable, E_USER_ERROR);
		if(isset($vo->passwd)) $vo->passwd = null; // clean the passwd !
		$this->_populateContext($vo, $context); //rempli le context

		if('tablefields' == $this->maintable && !empty($context['mask'])) {
			$context['mask'] = unserialize(html_entity_decode(stripslashes($context['mask'])));
		}
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
	public function copyAction(&$context, &$error)
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
	public function editAction(&$context, &$error, $clean = false)
	{
		if ($clean != 'CLEAN') {      // validate the forms data
			if (!$this->validateFields($context, $error)) {
				return '_error';
			}
		}
		
		// get the dao for working with the object
		$dao = $this->_getMainTableDAO();
		$id = (int)$context['id'];
		$this->_prepareEdit($dao, $context);
		// create or edit
		if ($id) {
			$dao->instantiateObject($vo);
			$vo->id = $id;
		} else {
			$create = true;
			$vo = $dao->createObject();
		}
		if (isset($dao->rights['protect'])) {
			$vo->protect = isset($context['protect']) && $context['protect'] ? 1 : 0;
		}
		// put the context into 
		$this->_populateObject($vo, $context);
		if (!$dao->save($vo)) trigger_error("You don't have the rights to modify or create this object", E_USER_ERROR);
		$ret = $this->_saveRelatedTables($vo, $context);
		if(isset($create) && ('users' == $context['lo'] || 'restricted_users' == $context['lo'])) {
			$this->_sendPrivateInformation($context);
		}
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
	public function changeRankAction(&$context, &$error, $groupfields = "", $status = "status>0")
	{
		$criterias = array();
		$id = $context['id'];
		if ($groupfields) {
			$vo  = $this->_getMainTableDAO()->getById($id, $groupfields);
			foreach (explode(",", $groupfields) as $field) {
				$criterias[] = $field. "='". $vo->$field. "'";
			}
		}
		if ($status) $criterias[] = $status;

		$criteria = join(" AND ", $criterias);
		$this->_changeRank($id, $context['dir'], $criteria);

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
	public function deleteAction(&$context, &$error)
	{
		global $db;
		$id = $context['id'];

		if ($this->isdeletelocked($id)) {
			trigger_error("This object is locked for deletion. Please report the bug", E_USER_ERROR);
		}
		$dao = $this->_getMainTableDAO();
		$this->_prepareDelete($dao, $context);
		$dao->deleteObject($id);

		$ret=$this->_deleteRelatedTables($id);

		update();
		return $ret ? $ret : '_back';
	}

	public function removeAction(&$context, &$error)
	{
		// we can reach here ONLY if we are at least admin for table plugins
		// or adminlodel for table mainplugins
		if( (C::get('site', 'cfg') && !C::get('admin', 'lodeluser')) ||
			!C::get('adminlodel', 'lodeluser'))
		trigger_error('ERROR: You don\'t have the rights to do that', E_USER_ERROR);
		
		C::set('remove', true); // @see Dao::delete()
		
		return $this->deleteAction($context, $error);
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
	public function rights($access) 
	{
		return @$this->_getMainTableDAO()->rights[$access];
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
	public function isdeletelocked($id, $status=0)
	{
		// basic
		if (is_numeric($id)) {
			$criteria = "id='". $id. "'";
			$nbexpected = 1;
		} else {
			$criteria = "id IN ('". join("','", $id). "')";
			$nbexpected = count($id);
		}
		$dao = $this->_getMainTableDAO();
		$nbreal = $dao->count($criteria. " ". $dao->rightsCriteria("write"));
		return $nbexpected != $nbreal;
	}

	//! Private or protected from this point
	/**#@+
	 * @access private
	 */
	
	protected function _getMainTableDAO() 
	{
		if(!defined('INC_FUNC')) include 'func.php';
		return getDAO($this->maintable);
	}
   

	/**
	 * Change the rank of an Object
	 */
	protected function _changeRank($id, $dir, $criteria)
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
				if (!isset($vos[$i+1])) {
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
				++$i;
			}
			$newrank+= $dir;
		}
	}

	/**
	 * Validated the public fields and the unicity.
	 * @return return an array containing the error and warning, null otherwise.
	 */
	public function validateFields(&$context, &$error) 
	{
		global $db;
		
		// Noms des logics qui sont trait�es par des formulaires dans lesquels il y a des champs de type file ou image, et qui ont besoin d'un traitement particulier pour ces champs (i.e. pas des docs annexes)
		// Ne concerne que la partie admin de l'interface, ajout� pour les ic�nes li�es aux classes et aux types
		// Cf. par ex. les formulaires edit_types.html ou edit_classes.html
		$adminFormLogics = array ('classes', 'entrytypes', 'persontypes', 'types');
		if(!function_exists('validfield'))
			include "validfunc.php";
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
				if (($type == "image" || $type == "file") && in_array($this->maintable, $adminFormLogics)){
					// traitement particulier des champs de type file et images dans les formulaires de la partie admin
					
					// r�pertoire de destination pour les fichiers et les images : array ($field, $r�pertoire)
					$directory =array ('icon' => 'lodel/icons');
					$valid = validfield($context[$field], $type, "",$field, "", $directory[$field]);
				} else {
					$valid = validfield($context[$field], $type, "",$field, '', '', $context);
				}
				if ($valid === false) {
					trigger_error("ERROR: \"$type\" can not be validated in logic.php", E_USER_ERROR);
				}
				if (is_string($valid)) {
					$error[$field]=$valid;
				}
			}
			if('tablefields' == $this->maintable && 'mask' == $field) {
				if(!empty($context['mask']['user']))
				{
					$this->_makeMask($context, $error);
					if(!$error['mask']) $context['mask'] = addslashes(serialize($context['mask']));
				}
				else
				{
					$context['mask'] = null;
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
				trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
			if ($ret) {
				$error[$fields[0]] = "1"; // report the error on the first field
			}
		}

		return empty($error);
	}

	/**
	 * Cr�e le masque (regexp) en fonction du masque rentr� dans l'interface
	 *
	 * @param array $context le tableau des donn�es pass� par r�f�rence.
	 * @param array $error le tableau des erreurs rencontr�es pass� par r�f�rence.
	 */
	protected function _makeMask(&$context, &$error)
	{
		if($context['mask']['user'] == '') return;
		if(!defined('PONCTUATION')) include 'utf8_file.php';
		$mask = $context['mask']['user'];
		if(isset($context['mask_regexp'])) {
			// disable eval options for more security
			$mask = $context['mask']['user'] = preg_replace('/^(.)(.*)(\\1)([msieDASuUXxJ]*)?$/e', "'\\1'.\\2.'\\1'.str_replace('e', '', \"\\4\")", $mask);
			if(FALSE === @preg_match($mask, 'just a test for user regexp')) {
				$error['mask'] = 'mask: '.getlodeltextcontents('mask_bad_regexp', 'common');
				return;
			} elseif(PREG_NO_ERROR !== preg_last_error()) {
				$error['mask'] = 'mask: '.getlodeltextcontents('mask_bad_regexp', 'common').' : PCRE : '.preg_last_error();
				return;
			}
			$context['mask']['lodel'] = $mask;
			$context['mask']['model'] = 'regexp';
		} elseif(isset($context['mask_reasonable'])) {
			$regexp = null;
			$ponct = false;
			$chars = false;
			$num = false;
			$unknown = false;
			$spaces = false;
			while($strlen = mb_strlen($mask, 'UTF-8')) {
				$c = mb_substr($mask,0,1,"UTF-8");
				$mask = mb_substr($mask,1,$strlen,"UTF-8");
				if($c >= '0' && $c <= '9') {
					$regexp .= (false === $num ? '[0-9]+' : '');
					$spaces = false;
					$num = true;
					$chars = false;
					$unknown = false;
					$ponct = false;
				} elseif( ($c >= 'a' && $c <= 'z') || 
					($c >= 'A' && $c <= 'Z')  ||
					(preg_match("/^[".ACCENTS."]$/u", $c)>0) ) {
					$regexp .= (false === $chars ? '[a-zA-Z'.ACCENTS.']+' : '');
					$spaces = false;
					$num = false;
					$chars = true;
					$unknown = false;
					$ponct = false;
				} elseif(preg_match("/^[".PONCTUATION."]$/u", $c)>0) {
					$regexp .= (false === $ponct ? '['.PONCTUATION.']+' : '');
					$spaces = false;
					$num = false;
					$chars = false;
					$unknown = false;
					$ponct = true;
				} elseif(preg_match("/^\s$/u", $c)>0) {
					$regexp .= (false === $spaces ? '\s+' : '');
					$spaces = true;
					$num = false;
					$chars = false;
					$unknown = false;
					$ponct = false;
				}  else {
					// unknown character ?
					$regexp .= (false === $unknown ? '.+?' : '');
					$spaces = false;
					$num = false;
					$chars = false;
					$unknown = true;
					$ponct = false;
				}
			}
			if(!is_null($regexp)) {
				$regexp = '/^'.$regexp.'$/';
				$context['mask']['lodel'] = $regexp;
				$context['mask']['model'] = 'reasonable';
			} else {
				$error['mask'] = 'mask: unknown error';
			}
		} else {
			// let's find masks like %something% and decode them
			preg_match_all("/(?<!\\\\)(?:%)(?!\s)(.*?)((?<!\\\\)(?:[\?\+\*]|\{[\d,]+\}))?(?<!(?:\\\\|\s))(?:%)/u", $mask, $m);
			$masks = array();
			foreach($m[0] as $k=>$v) {
				$content = $m[1][$k];
				$chars = 0;
				$num = 0;
				$spaces = 0;
				$pmask = '';
				while($strlen = mb_strlen($content, 'UTF-8')) {
					$c = mb_substr($content,0,1,"UTF-8");
					$content = mb_substr($content,1,$strlen,"UTF-8");
					if($c >= '0' && $c <= '9') {
						if($chars === 0 && $num === 0) {
							$pmask .= '[';
						}
						if(0 === $num) {
							$pmask .= '0-9';
						}
						$spaces = 0;
						$num++;
					} elseif(($c >= 'a' && $c <= 'z') || 
						($c >= 'A' && $c <= 'Z')  ||
						(preg_match("/^[".ACCENTS."]$/u", $c)>0) ) {
						if($num === 0 && $chars === 0) {
							$pmask .= '[';
						}
						if(0 === $chars) {
							$pmask .= 'a-zA-Z'.ACCENTS.'';
						}
						$spaces = 0;
						$chars++;
					} elseif(preg_match("/^\s$/u", $c)>0) {
						if($chars > 0 || $num > 0) {
							$pmask .= ($chars > 1 || $num > 1 ? ']+' : ']');
						}
						if(0 === $spaces) {
							$pmask .= '\s';
						} elseif(1 === $spaces) {
							$pmask .= '+';
						}
						$spaces++;
						$num = 0;
						$chars = 0;
					} else {
						if($chars > 0 || $num > 0) {
							$pmask .= ($chars > 1 || $num > 1 ? ']+' : ']');
						}
						$pmask .= preg_quote($c, '/');
						$spaces = 0;
						$num = 0;
						$chars = 0;
					}
					
				}
				if($chars > 0 || $num > 0) {
					$pmask .= ($chars > 1 || $num > 1 ? ']+' : ']');
				}
				if($m[2][$k]) $pmask = '('.$pmask.')'.$m[2][$k];
				$masks[$v] = $pmask;
			}
			$mask = preg_quote($mask, '/');
			foreach($masks as $k=>$m)
				$mask = str_replace(preg_quote($k, '/'), $m, $mask);
			$context['mask']['lodel'] = '/^'.$mask.'$/';
			$context['mask']['model'] = 'traditional';
		}
	}

	/**
	 * Return the public fields
	 * @access public
	 */
	public function getPublicFields()
	{
		return $this->_publicfields();
	}

	/**
	 * Return the unique fields
	 * @access protected
	 */
	protected function _publicfields() 
	{
		trigger_error("call to abstract publicfields", E_USER_ERROR);
		return array();
	}

	/**
	 * Return the unique fields
	 * @access public
	 */
	public function getUniqueFields()
	{
		return $this->_uniqueFields();
	}

	/**
	 * Return the unique fields
	 * @access protected
	 */
	protected function _uniqueFields() 
	{
		trigger_error("call to abstract uniquefields", E_USER_ERROR);
		return array();
	}

	/**
	 * Populate the object from the context. Only the public fields are inputted.
	 * @private
	 */
	protected function _populateObject($vo, &$context) 
	{
		$publicfields = $this->_publicfields();
		foreach ($publicfields as $field => $fielddescr) {
			$vo->$field = isset($context[$field]) ? $context[$field] : null;
		}
	}

	/**
	 * Populate the context from the object. All fields are outputted.
	 * @protected
	 */
	protected function _populateContext($vo, &$context) 
	{
		$view = (isset($context['do']) && $context['do'] == 'view');
		foreach ($vo as $k=>$v) {
			//Added by Jean - Be carefull using it
			//if value is a string and we want to view it (or edit it in a form, 
			//open a form is a view action) then we htmlize it
			if (is_string($v) && $view) {
				$v = htmlspecialchars($v);
			}
			$context[$k] = $v;
		}
	}

	/**
	 * Used in editAction to do extra operation before the object is saved.
	 * Usually it gather information used after in _saveRelatedTables
	 */
	protected function _prepareEdit($dao, &$context) {}

	/**
	 * Used in deleteAction to do extra operation before the object is saved.
	 * Usually it gather information used after in _deleteRelatedTables
	 */
	protected function _prepareDelete($dao, &$context) {}

	/**
	 * Used in editAction to do extra operation after the object has been saved
	 */
	protected function _saveRelatedTables($vo, &$context) {}

	/**
	 * Used in deleteAction to do extra operation after the object has been deleted
	 */
	protected function _deleteRelatedTables($id) {}

	/**
	 * Used in viewAction to do extra populate in the context 
	 */
	protected function _populateContextRelatedTables($vo, &$context) {}
	
	/**
	 * process of particular type of fields
	 * @param string $type the type of the field
	 * @param array $context the context
	 * @param int $status the status; by default 0 if no status changed
	 */
	protected function _processSpecialFields($type, &$context, $status = 0) 
	{
		global $db;
		$vo = getDAO('entities')->getById($context['id'], 'id, idtype');
		$votype = getDAO ("types")->getById ($vo->idtype, 'class');
		$class = $votype->class;
		unset($vo,$votype);

		$fields = getDAO("tablefields")->findMany ("(class='". $class. "' OR class='entities_". $class. "') AND type='". $type. "' AND status>0 ", "",    "name, type, class");
		if($fields && $type == 'history') {
			$updatecrit = "";
			foreach ($fields as $field) {
				$value = "";
				$this->_calculateHistoryField ($value, $context, $status);
				if (isset ($context['data'][$field->name])) { //if a value for this field is in the context, use it (to allow user to modify the field
					$updatecrit = ($updatecrit ? "," : ""). $field->name. "=CONCAT('". $value. "','\n".$context['data'][$field->name] . "')";
				}
				else {
					$updatecrit = ($updatecrit ? "," : ""). $field->name. "=CONCAT(".$value. ",'\n".$field->name . "')";
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
	protected function _calculateHistoryField(&$value, &$context, $status = 0) 
	{
		if($context['id']) {
            	$dao = getDAO('users');
		if(C::get('lodeladmin', 'lodeluser')) {
			usemaindb();
			$vo = $dao->getById (C::get('id', 'lodeluser'));
			usecurrentdb();
		}
		else {
			$vo = $dao->getById (C::get('id', 'lodeluser'));
		}
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
			$line .= " ". ($vo->name ? $vo->name : ($vo->username ? $vo->username : $context['lodeluser']['lastname']));
			#print_r($context['lodeluser']);
			#$line .= ' '.$context['lodeluser']['username'];
			$line .= " ".getlodeltextcontents('on', 'common'). " ". date('d/m/Y H:i');
			$value = $line;
			#echo "value=$value";
			unset($line);
		}
	}

	/**
	 * V�rification de la valeur du statut (champ status dans les tables)
	 * @param int $status la valeur du statut � ins�rer dans la base
	 * @return bool true si le param�tre $status correspond � une valeur autoris�e, sinon d�clenche une erreur php
	 */
	protected function _isAuthorizedStatus($status)
	{
	//echo $this->maintable . '<p>' . $status . '<p>';
		switch ($this->maintable) {
			case 'entities' :
				$this->_authorizedStatus = array(-64, -8, -1, 1, 8, 17, 24);
				break;
			case 'persons' :
			case 'entries' :
				$this->_authorizedStatus = array(-64, -32, -1, 1, 32);
				break;
			case 'texts' :
				$this->_authorizedStatus = array(-1, 1, 2);
				break;
			default : trigger_error("ERROR: Cannot find authorized status", E_USER_ERROR);
				
		}
		if (in_array($status, $this->_authorizedStatus) || $status == 0) {
			return true;
		} else {
			trigger_error("ERROR: Invalid status ! ", E_USER_ERROR);
		}
	}
	/**#@-*/

} // class Logic }}}


/*------------------------------------------------*/

/**
 * Logic factory
 *
 */
function getLogic($table) 
{
	static $factory; // cache
	if (isset($factory[$table])) {
		return $factory[$table]; // cache
	}
	$logicclass = $table. 'Logic';
	if(!class_exists($logicclass))
	{
		$file = C::get('sharedir', 'cfg').'/plugins/custom/'.$table.'/logic.php';
		if(!file_exists($file))
			trigger_error('ERROR: unknown logic', E_USER_ERROR);
		include $file;
		if(!class_exists($logicclass,false) || !is_subclass_of($logicclass, 'Logic'))
			trigger_error('ERROR: cannot find the class, or the logic plugin file does not extend the Logic OR GenericLogic class', E_USER_ERROR);
	}
	$factory[$table] = new $logicclass;

	return $factory[$table];
}


/**
 * function returning the right for $access in the table $table
 */
function rights($table, $access)
{
	static $cache;
	if (!isset($cache[$table][$access])) {
		$cache[$table][$access] = getLogic($table)->rights($access);
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
		$cache[$table][$id] = getLogic($table)->isdeletelocked($id, $status);
	}
	return $cache[$table][$id];
}
?>