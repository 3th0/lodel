<?php
/**	
 * Logique des utilisateurs
 *
 * PHP versions 4 et 5
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


/**
 * Classe de logique des utilisateurs
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
class UsersLogic extends Logic 
{

	/** Constructor
	*/
	function UsersLogic() {
		$this->Logic('users');
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
		global $lodeluser;
		if ($lodeluser['id']==$id && 
	( ($GLOBALS['site'] && $lodeluser['rights']<LEVEL_ADMINLODEL) ||
		(!$GLOBALS['site'] && $lodeluser['rights']==LEVEL_ADMINLODEL))) {
			return getlodeltextcontents("cannot_delete_current_user","common");
		} else {
			return false;
		}
		//) { $error["error_has_entities"]=$count; return "_back"; }
	}


	/**
	 * Suppression du log des sessions d'un utilisateur
	 *
	 * Cette action permet de supprimer soit :
	 * - toutes les sessions d'un utilisateur, do=deletesession&lo=users&id=xx
	 * - une session particuli�re : do=deletesession&lo=users&session=xx
	 *
	 * @param array $context le contexte pass� par r�f�rence
	 * @param array $error les erreur �ventuelles par r�f�rence
	 */
	function deletesessionAction(&$context, &$error)
	{
		global $db;
		require_once 'func.php';
		$id = intval($context['id']);
		$session     = intval($context['session']);
		usemaindb(); // les sessions sont stock�s dans la base principale
		$ids = array();
		if ($id) { //suppression de toutes les sessions
			$result = $db->execute(lq("SELECT id FROM #_MTP_session WHERE iduser='".$id."'")) or dberror();
			while(!$result->EOF) {
				$ids[] = $result->fields['id'];
				$result->MoveNext();
			}
		} elseif ($session) { //suppression d'une session
			$ids[] = $session;
		} else {
			die ('ERROR: unknown operation');
		}
		
		if ($ids) {
			$idstr = join(',', $ids);
			// remove the session
			$db->execute(lq("DELETE FROM #_MTP_session WHERE id IN ($idstr)")) or dberror();
			// remove the url related to the session
			$db->execute(lq("DELETE FROM #_MTP_urlstack WHERE idsession IN ($idstr)")) or dberror();
		}

		usecurrentdb();
		update();
		return '_back';
	}

	/**
	 * Permet de r�gler la langue ou le mode traduction d'un utilisateur
	 *
	 * Pour changer la langue d'un utilisateur : lo=users&do=set&lang=fr
	 * Pour changer le mode traduction : lo=users&do=set&translationmode=off
	 * @param array $context le contexte pass� par r�f�rence
	 * @param array $error les erreur �ventuelles par r�f�rence
	 */
	function setAction(&$context, &$error)
	{
		global $db;
		$lang = $context['lang'];
		$translationmode = $context['translationmode'];
		if ($lang) {
			if (!preg_match("/^\w\w(-\w\w)?$/",$lang)) {
				die("ERROR: invalid lang");
			}
			$db->execute(lq("UPDATE #_TP_users SET lang='$lang'")) or dberror();
			$this->_setcontext('lang', 'setvalue', $lang);
		}

		if ($translationmode) {
			switch($translationmode) {
			case 'off':
				$this->_setcontext('translationmode', 'clear');
				break;
			case 'site':
			case 'interface':
				$this->_setcontext('translationmode', 'setvalue', $translationmode);
				break;
			}
		}

	update();
	return '_back';
	}



	/**
		* make the select for this logic
		*/
	function makeSelect(&$context,$var)

	{
		switch($var) {
		case 'usergroups' :
			$dao=&getDAO("usergroups");
			$list=$dao->findMany("status>0","rank,name","id,name");
			$arr=array();
			foreach($list as $group) {
	$arr[$group->id]=$group->name;
			}
			if (!$arr) $arr[1] = '--';
			renderOptions($arr,$context['usergroups']);
			break;
		case 'gui_user_complexity' :
			require_once 'commonselect.php';
			makeSelectGuiUserComplexity($context['gui_user_complexity']);
			break;
		case 'userrights':
			require_once 'commonselect.php';
			makeSelectUserRights($context['userrights'],!$GLOBALS['site'] || SINGLESITE);
			break;
		case "lang" :
			// get the language available in the interface
			
			$dao=&getDAO("translations");
			$list=$dao->findMany("status>0 AND textgroups='interface'","rank,lang","lang,title");
			$arr=array();
			foreach($list as $lang) {
	$arr[$lang->lang]=$lang->title;
			}
			if (!$arr) $arr['fr']="Francais";
			renderOptions($arr,$context['lang']);
		}
	}

	/*---------------------------------------------------------------*/
	//! Private or protected from this point
	/**
		* @private
		*/
	/**
	 *
	 *
	 */
	function _setcontext($var, $operation, $value = '')
	{
		global $db;
		usemaindb();
		$where = "name='". addslashes($_COOKIE[$GLOBALS['sessionname']]). "' AND iduser='". $GLOBALS['lodeluser']['id']. "'";
		$context = $db->getOne(lq("SELECT context FROM $GLOBALS[tp]session WHERE ".$where));
		if ($db->errorno()) {
			dberror();
		}
		$arr = unserialize($context);
		switch ($operation) {
		case 'toggle' :
			$arr[$var] = $arr[$var] ? 0 : 1; // toggle
			break;
		case 'setvalue' :
			$arr[$var] = $value;  // set
			break;
		case 'clear' :
			unset($arr[$var]);  // clear
			break;
		}
	
		$db->execute(lq("UPDATE #_MTP_session SET context='". addslashes(serialize($arr)). "' WHERE ".$where)) or dberror();
		usecurrentdb();
	}

	/**
	* Pr�paration de l'action Edit
	*
	* @access private
	* @param object $dao la DAO utilis�e
	* @param array &$context le context pass� par r�f�rence
	*/
	function _prepareEdit($dao,&$context) 
	{
		// encode the password
		if ($context['passwd']) {
			$context['tmppasswd'] = $context['passwd'];
			$context['passwd']=md5($context['passwd'].$context['username']);
		}
	}



	function _populateContextRelatedTables(&$vo,&$context)

	{
		if ($vo->userrights<=LEVEL_EDITOR) {
			$dao=&getDAO("users_usergroups");
			$list=$dao->findMany("iduser='". $vo->id."'", "", "idgroup");
			$context['usergroups']=array();
			foreach($list as $relationobj) {
				$context['usergroups'][] = $relationobj->idgroup;
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
	function _saveRelatedTables($vo,$context) 

	{
		global $db;
		if ($vo->userrights<=LEVEL_EDITOR) {
			if (!$context['usergroups']) $context['usergroups']=array(1);

			// change the usergroups     
			// first delete the group
			$this->_deleteRelatedTables($vo->id);
			// now add the usergroups
			foreach ($context['usergroups'] as $usergroup) {
				$usergroup=intval($usergroup);
				$db->execute(lq("INSERT INTO #_TP_users_usergroups (idgroup, iduser) VALUES  ('$usergroup','$id')")) or dberror();
			}
		}
	}

	function _deleteRelatedTables($id) {
		global $db;
		if ($GLOBALS['site']) { // only in the site table
			$db->execute(lq("DELETE FROM #_TP_users_usergroups WHERE iduser='$id'")) or dberror();
		}
	}


	function validateFields(&$context,&$error) {
		global $db,$lodeluser;

		if (!Logic::validateFields($context,$error)) return false;

		// check the user has the right equal or higher to the new user
		if ($lodeluser['rights']<$context['userrights']) die("ERROR: You don't have the right to create a user with rights higher than yours");

		// Check the user is not duplicated in the main table...
		if (!usemaindb()) return true; // use the main db, return if it is the same as the current one.

		$ret=$db->getOne("SELECT 1 FROM ".lq("#_TP_".$this->maintable)." WHERE status>-64 AND id!='".$context['id']."' AND username='".$context['username']."'");
		if ($db->errorno()) die($this->errormsg());
		usecurrentdb();

		// check the passwd is given for new user.
		if (!$context['id'] && !trim($context['passwd'])) {
			$error['passwd']=1;
			return false;
		}

		if ($ret) {
			$error['username']="1"; // report the error on the first field
			return false;
		} else {
			return true;
		}
	}


	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	function _publicfields() 
	{
		return array('username' => array('username', '+'),
									'passwd' => array('passwd', ''),
									'lastname' => array('text', ''),
									'firstname' => array('text', ''),
									'email' => array('email', '+'),
									'lang' => array('lang', '+'),
									'userrights' => array('select', '+'),
									'gui_user_complexity' => array('select', '+'),
									'nickname' => array('text', ''),
									'biography' => array('longtext', ''),
									'photo' => array('image', ''),
									'professional_website' => array('text', ''),
									'url_professional_website' => array('url', ''),
									'rss_professional_website' => array('url', ''),
									'personal_website' => array('text', ''),
									'url_personal_website' => array('url', ''),
									'rss_personal_website' => array('url', ''),
									'pgp_key' => array('longtext', ''),
									'alternate_email' => array('email', ''),
									'phonenumber' => array('text', ''),
									'im_identifier' => array('text', ''),
									'im_name' => array('text', ''));
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	/**
	 * Retourne la liste des champs uniques
	 * @access private
	 */
	function _uniqueFields() 
	{ 
		return array(array('username'), );
	}
	// end{uniquefields} automatic generation  //

	/**
	 * Bloque un compte utilisateur tant que celui-ci n'a pas modifi� son mot de passe
	 */

	function suspendAction()
	{
 		global $db, $id, $site, $lodeluser;

		//on v�rifie qu'on est bien administrateur
		if($lodeluser['rights'] >= 40) {
			$prefixe = ($site != '' && $site != "tous les sites") ? "#_TP_" : "#_MTP_";
	
			$status = $db->getOne(lq("SELECT status FROM ".$prefixe."users WHERE id = '".$id."'"));
	
			if($status != -40 && $status != 32)
				$stat = 10;
			else
				$stat = 11;
			
			$db->execute(lq("UPDATE ".$prefixe."users SET status = ".$stat." WHERE id = '".$id."'"));
		} else
			die("ERROR : You don't have permissions to suspend this user. Contact your administrator.");
		
		return "_back";
	}

	/**
	 * Envoi un mail au nouvel utilisateur cr�� avec son login/mdp et diverses informations
	 */
	function _sendPrivateInformation(&$context) {
		global $db;
		if(!$context['tmppasswd']) return;
		$row = $db->getRow(lq("SELECT url, title FROM #_MTP_sites WHERE name = '{$context['site']}'"));
		if(!$row) die('Error while getting url and title of site for new user mailing');
		$context['siteurl'] = str_replace(":80", "", $row['url']);
		$context['sitetitle'] = $row['title'];
		$prefix = $context['lodeluser']['adminlodel'] ? lq("#_MTP_") : lq("#_TP_");
		$email = $db->getOne("SELECT email FROM {$prefix}users WHERE id = '{$context['lodeluser']['id']}'");
		if(!$email) die('Error while getting your email for new user mailing');
		require_once 'view.php';
		$GLOBALS['nodesk'] = true;
		ob_start();
		insert_template($context, 'users_mail', "", SITEROOT."lodel/admin/tpl/", true);
		$body = ob_get_contents();
		ob_end_clean();
		unset($context['tmppasswd']);
		require_once 'func.php';
		return send_mail($context['email'], $body, "Votre compte Lodel sur le site '{$context['sitetitle']}' ({$context['siteurl']})", $email, '');
	}

} // class 


/*-----------------------------------*/
/* loops                             */

?>
