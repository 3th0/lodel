<?php
/**
 * Fichier de la classe view.
 *
 * PHP 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajout� depuis la version 0.8
 */
include_once 'func.php';
include_once 'cachefunc.php';

/**
 * Classe g�rant la partie 'vue' du mod�le MVC. Cette classe est un singleton.
 * 
 * Exemple d'utilisation de ce singleton :
 * <code>
 * $view = &getView();
 * $view->render($context,$tpl);
 * </code>
 *
 * @package lodel
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Pierre-Alain Mignot
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajout�e depuis la version 0.8
 * @see logic.php
 * @see controler.php
 */

class View
{
	/** 
	 * Le nom du fichier de cache
	 * @var string 
	 */
	private $_cachedfile;

	/** 
	 * Les options du cache
	 * @var array
	 */
	private $cacheOptions;

	/**
	 * Instance du singleton
	 * @var object
	 */
	private static $_instance;


	/** 
	 * Constructeur priv�
	 * @access private
	 */
	private function View() {
		global $cacheOptions;
		$this->_cacheOptions = $cacheOptions;
	}


	/**
	 * 'Getter' de ce singleton.
	 * Cette fonction �vite l'initialisation inutile de la classe si une instance de celle-ci existe
	 * d�j�.
	 *
	 * @return object l'instance de la classe view
	 */
	public static function &getView()
	{
		if (!isset(self::$_instance)) {
			self::$_instance = new View;
		}
		return self::$_instance;
	}

	/**
	 * Fonction qui redirige l'utilisateur vers la page pr�c�dente
	 * 
	 * <p>Cette fonction selectionne l'URL pr�c�dente dans la pile des URL (table urlstack). Ceci est
	 * fait suivant le niveau de profondeur choisi (par d�faut 1).<br />
	 * Si une URL est trouv�e, toutes les autres URLS de l'historique (pour la session en cours) sont
	 * supprim�es et une redirection est faite sur cette page.<br />
	 * Si aucune URL n'est trouv�e alors la redirection est faite sur l'accueil (index.php).</p>
	 * @param integer $back le nombre de retour en arri�re qu'il faut faire. Par d�faut est �gal � 1.
	 */
	public function back($back = 1)
	{
		global $db, $idsession;

		$offset = $back-1;
		usemaindb();
		// selectionne les urls dans la pile gr�ce � l'idsession et suivant la
		// la profondeur indiqu�e (offset)
		$result = $db->selectLimit(lq("SELECT id, url FROM #_MTP_urlstack WHERE url!='' AND idsession='$idsession' ORDER BY id DESC"), 1, $offset) or dberror();
		$row = $result->fetchRow();

		$id = $row['id'];	$newurl = $row['url'];
		
		if ($id) {
			$db->execute(lq("DELETE FROM #_TP_urlstack WHERE id>='$id' AND idsession='$idsession'")) or dberror();
			$newurl = 'http://'. $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] != 80 ? ":". $_SERVER['SERVER_PORT'] : ''). $newurl;
		} else {
				$newurl = "index.". ($GLOBALS['extensionscripts'] ? $GLOBALS['extensionscripts'] : 'php');
		}

		if (!headers_sent()) {
			header("location: ".$newurl);
			exit;
		} else { // si probleme
			echo "<h2>Warnings seem to appear on this page. You may go on anyway by following <a href=\"$go\">this link</a>. Please report the problem to help us to improve Lodel.</h2>";
			exit;
		}
	}

	/**
	 * Fonction Render
	 *
	 * Affiche une page particuli�re en utilisant le contexte (tableau $context) et le nom du template
	 * pass� en argument.
	 * Cette fonction g�re la mise en cache et le recalcule si n�cessaire. C'est-�-dire si celui-ci
	 * n'existe pas, si celui-ci n'est plus � jour, n'est plus valide,...
	 * 
	 * @param array $context Le tableau de toutes les variables du contexte
	 * @param string $tpl Le nom du template utilis� pour l'affichage
	 * @param boolean $cache Si on doit utiliser le cache ou non (par d�faut � false)
	 *
	 */
	public function render(&$context, $tpl, $caching = false)
	{
		global $site;

		$this->_makeCachedFileName();

		include_once 'Cache/Lite.php';
		$cache = new Cache_Lite($this->_cacheOptions);

		if (!$caching || $_REQUEST['clearcache']) { // calcul la page si le cache n'existe pas
			clearcache();
			$content = $this->_calcul_page($context, $tpl);
			$cache->save($content, $this->_cachedfile, $site);
			$content = $this->_eval($content, $context);
			echo $content;
			flush();
			return;
		}

		if($content = $cache->get($this->_cachedfile, $site)) {
			if(FALSE !== ($content = $this->_iscachevalid($content, $context))) {
				echo $content;
				flush();
				return;
			}
		}
		// pas de fichier dispo dans le cache, on le calcule, l'enregistre, l'execute et affiche le r�sultat
		$content = $this->_calcul_page($context, $tpl);
		$cache->save($content, $this->_cachedfile, $site);
		$content = $this->_eval($content, $context);
		echo $content;
		flush();
		return;	
	}

	/**
	 * Fonction qui affiche le r�sultat si le cache est valide
	 * 
	 * Alternative � la fonction render.
	 *
	 * @return boolean true ou false si le cache est valide ou non
	 */
	public function renderIfCacheIsValid()
	{
		global $site, $context;

		if ($_REQUEST['clearcache'])	{
			clearcache();
			return false;
		}

		$this->_makeCachedFileName();
		include_once 'Cache/Lite.php';
		$cache = new Cache_Lite($this->_cacheOptions);
		if($content = $cache->get($this->_cachedfile, $site)) {
			// on v�rifie que le tpl n'est pas � recompiler
			if(FALSE !== ($content = $this->_iscachevalid($content, $context))) {
				if(FALSE !== strpos($content, '<?')) {
					$content = $this->_eval($content, $context);
				}
				echo $content;
				flush();
				return true;
			}
		}
			
		return false;
	}

	/**
	 * Fonction qui affiche une page d�j� en cache
	 * 
	 * Alternative � la fonction render.
	 *
	 * @param array $context Le tableau de toutes les variables du contexte
	 * @param string $tpl Le nom du template utilis� pour l'affichage
	 * @return retourne la m�me chose que la fonction render
	 * @see render
	 */
	public function renderCached(&$context, $tpl)
	{
		return $this->render($context, $tpl, true);
	}


	/**
	* Fonction qui affiche un template inclus en LodelScript
	*
	* @param array $context le context
	* @param string $base le nom du fichier template
	* @param string $cache_rep chemin vers r�pertoire cache si diff�rent de ./CACHE/
	* @param string $base_rep chemin vers r�pertoire tpl
	* @param bool $escRefresh appel de la fonction par le refresh manager
	*/
	public function renderTemplateFile(&$context, $tpl, $cache_rep='', $base_rep='tpl/', $escRefresh) {

		global $site;
		include_once 'Cache/Lite.php';

		$cache = new Cache_Lite($this->_cacheOptions);
		$content = $cache->get($tpl, 'TemplateFile');
		if(!$content) {
			if(!$base_rep)
				$base_rep = 'tpl/';
			$content = $this->_calcul_page($context, $tpl, $cache_rep, $base_rep, true);
			$cache->save($content, $tpl, 'TemplateFile');
		}
 		if(!$escRefresh)
			$content = $this->_eval($content, $context, true);
		$GLOBALS['TemplateFile'][$tpl] = true;
		return $content;	
	}

	/**
	 * Modifie le nom du fichier � utiliser pour mettre en cache
	 */
	private function _makeCachedFileName() {
		global $lodeluser, $site;
		// Calcul du nom du fichier en cache
		$this->_cachedfile = str_replace('?id=0', '',
					preg_replace(array("/#[^#]*$/", "/[\?&]clearcache=[^&]*/"), "", $_SERVER['REQUEST_URI'])
					). "//". $lodeluser['name']. "//". $lodeluser['rights'];
		$GLOBALS['cachedfile'] = getCachedFileName($this->_cachedfile, $site, $this->_cacheOptions);
	}

	/**
	* Fonction qui execute le code PHP (si pr�sent)
	*
	* @param string $content contenu � �valuer
	* @param array $context le context
	* @return le contenu du code �valu�
	*/
	private function _eval($content, $context) {
		if(FALSE !== strpos($content, '<?')) { // on a du PHP, on l'execute
			ob_start();
			$ret = @eval('?'.'>'.$content);
			if(FALSE === $ret) {
				// on peut d�commenter ici pour afficher l'erreur (mode devel)
				//echo ob_get_clean();
				ob_end_clean();
				$this->_error("Syntax error when evaluating", __FUNCTION__);
			} elseif('refresh' == $ret) {
				ob_end_clean();
				return $ret;
			}
			$content = ob_get_clean();
		}
		return $content;
	}

	/**
	* Fonction qui v�rifie qu'il ne faut pas rafraichir le template
	*
	* @param string $content contenu � �valuer
	* @param array $context le context
	*/
	private function _iscachevalid($content, $context) {
		if(FALSE !== strpos($content, '<?')) {
			$content = $this->_eval($content, $context);
			if('refresh' == $content) {
				return false;
			}
		}
		return $content;	
	}

	/**
	* Fonction de calcul d'un template
	*
	* @param array $context le context
	* @param string $base le nom du fichier template
	* @param string $cache_rep chemin vers r�pertoire cache si diff�rent de ./CACHE/
	* @param string $base_rep chemin vers r�pertoire tpl
	* @param bool $include appel de la fonction par une inclusion de template (d�faut � false)
	*/
	private function _calcul_template(&$context, $base, $cache_rep = '', $base_rep = 'tpl/', $include=false) {

		global $home, $format;
		if(!empty($cache_rep))
			$this->_cacheOptions['cacheDir'] = $cache_rep . $this->_cacheOptions['cacheDir'];

		$group = $include ? 'TemplateFile' : 'tpl';

		if ($_REQUEST['clearcache'])	{
			clearcache();
			$_REQUEST['clearcache'] = false; // to avoid to erase the CACHE again
		}
	
		if ($format && !preg_match("/\W/", $format)) {
			$base .= "_$format";
		}
		$format = ''; // en cas de nouvel appel a calcul_page
		$template_cache = "tpl_$base";
		$tpl = $base_rep. $base. '.html';
		if (!file_exists($tpl)) {
			$this->_error("<code><strong>Error!</strong>  The <span style=\"border-bottom : 1px dotted black\">$base</span> template does not exist</code>", __FUNCTION__);
		}

		include_once 'Cache/Lite.php';
		$cache = new Cache_Lite($this->_cacheOptions);

		if(myfilemtime(getCachedFileName($template_cache, $group, $this->_cacheOptions)) <= myfilemtime($tpl) || !$cache->get($template_cache, $group)) {
			// le tpl cach� n'existe pas ou n'est pas � jour compar� au fichier de maquette
			if (!defined("TOINCLUDE")) {
				define("TOINCLUDE", $home);
			}

			require_once 'lodelparser.php';
			$parser = new LodelParser;
			$contents = $parser->parse($tpl, $include);
			$cache->save($contents, $template_cache, $group);
		}  else {
			// on �tend la dur�e de vie du tpl mis en cache
			$cache->extendLife();
		}
		// si jamais le path a �t� modifi� on remet par d�faut
		$this->_cacheOptions['cacheDir'] = "./CACHE/";
	}

	/**
	* Fonction de calcul d'une page
	*
	* Cette fonction sort de l'utf-8 par d�faut. Sinon c'est de l'iso-latin1 (m�thode un peu
	* dictatoriale)
	* @param array $context le context
	* @param string $base le nom du fichier template
	* @param string $cache_rep chemin vers r�pertoire cache si diff�rent de ./CACHE/
	* @param string $base_rep chemin vers r�pertoire tpl
	* @param bool $include appel de la fonction par une inclusion de template (d�faut � false)
	*/
	private function _calcul_page(&$context, $base, $cache_rep = '', $base_rep = 'tpl/', $include=false)
	{
		global $format;

		if(!empty($cache_rep))
			$this->_cacheOptions['cacheDir'] = $cache_rep . $this->_cacheOptions['cacheDir'];	

		$template_cache = "tpl_$base";
		$group = $include ? 'TemplateFile' : 'tpl';
		include_once 'Cache/Lite.php';
		$cache = new Cache_Lite($this->_cacheOptions);

		$i=0; // on va essayer 5 fois de r�cup�rer le fichier mis en cache
		do {
			$this->_calcul_template($context, $base, $cache_rep, $base_rep, $include);
			$content = $cache->get($template_cache, $group);
			if($content)
				break;
			$i++;
		} while (5>$i);

		if(!$content) {	
			include_once 'PEAR.php';
			if(PEAR::isError($content))
				echo $content->getMessage()."<br>";
			$this->_error('Impossible to get cached TPL. Is the cache directory accessible ? (read/write)', __FUNCTION__);
		} else {
			// si jamais le path a �t� modifi� on remet par d�faut
			$this->_cacheOptions['cacheDir'] = "./CACHE/";

			// execute le template php
			include_once 'textfunc.php';
			if ($GLOBALS['showhtml'] && $GLOBALS['lodeluser']['visitor']) {
				require_once 'showhtml.php';
				// on affiche la source
				$content = $this->_eval(_indent(show_html($content)), $context);
				return $content;
			}
			include_once 'loops.php';
			
			if ($context['charset'] == 'utf-8') {
				// utf-8 c'est le charset natif, donc on sort directement la chaine.
				$content = $this->_eval(_indent($content), $context);
				return $content;
			} else {
				// isolatin est l'autre charset par defaut
				$content = $this->_eval(_indent(utf8_decode($content)), $context);
				return $content;
			}
			$this->_error('Calculating page failed', __FUNCTION__);
		}
	}

	/**
	 * Fonction g�rant les erreurs
	 * Renvoit une 403 et affiche le message
	 */
	private function _error($msg, $func) {
		header("HTTP/1.0 403 Internal Error");
		header("Status: 403 Internal Error");
		header("Connection: Close");
		die("Error: [\" " . $msg . " \"] in function '".$func."'");	
	}

} // end class


/**
 *  Insertion d'un template dans le context
 *
 * @param array $context le context
 * @param string $tpl le nom du fichier template
 * @param string $cache_rep chemin vers r�pertoire cache si diff�rent de ./CACHE/
 * @param string $base_rep chemin vers r�pertoire tpl
 * @param bool $escRefresh appel de la fonction par le refresh manager (d�faut � false)
 */
function insert_template(&$context, $tpl, $cache_rep = '', $base_rep='tpl/', $escRefresh=false) {
	$view =& View::getView();
	$content = $view->renderTemplateFile($context, $tpl, $cache_rep, $base_rep, $escRefresh);
	echo $content;
}


// REMARQUE : Les fonctions suivantes n'ont rien � faire ici il me semble
/**
 * Fonction qui permet d'envoyer les erreurs lors du calcul des templates
 *
 * @param string $query la requete SQL
 * @param string $tablename le nom de la table SQL (par d�faut vide)
 */
function mymysql_error($query, $tablename = '')
{
	if ($GLOBALS['lodeluser']['editor']) {
		if ($tablename) {
			$tablename = "LOOP: $tablename ";
		}
		die("</body>".$tablename."QUERY: ". htmlentities($query)."<br /><br />".mysql_error());
	}	else {
		if ($GLOBALS['contactbug']) {
			$sujet = "[BUG] LODEL - ".$GLOBALS['version']." - ".$GLOBALS['currentdb'];
			$contenu = "Erreur de requete sur la page http://".$_SERVER['HTTP_HOST'].($_SERVER['SERVER_PORT'] != 80 ? ":". $_SERVER['SERVER_PORT'] : '').$_SERVER['REQUEST_URI']." \n\nQuery : ". $query . "\n\nErreur : ".mysql_error()."\n\nBacktrace :\n\n".print_r(debug_backtrace(), true);
			@mail($GLOBALS['contactbug'], $sujet, $contenu);
		}
		die("<code><strong>Error!</strong> An error has occured during the calcul of this page. We are sorry and we are going to check the problem</code>");
	}
}

/**
 * Appelle la bonne fonction makeSelect suivant la logique appel�e
 * Cette fonction est utilis�e dans le calcul de la page
 *
 * @param array $context Le tableau de toutes les variables du contexte
 * @param string $varname Le nom de la variable du select
 * @param string $lo Le nom de la logique appel�e
 * @param string $edittype Le type d'�dition (par d�faut vide)
 */
function makeSelect(&$context, $varname, $lo, $edittype = '')
{
	$logic = &getLogic($lo);
	$logic->makeSelect($context, $varname, $edittype);
}


/**
 * Affiche le tag HTML <option> pour les select normaux et multiples
 * Cette fonction positionne l'attribut selected="selected" das tags options d'un select suivant
 * les �l�ments qui sont effectivements s�lectionn�s.
 *
 * @param array $arr la liste des options
 * @param array $selected la liste des �l�ments s�lectionn�s.
 */
function renderOptions($arr, $selected)
{
	$multipleselect = is_array($selected);
	foreach ($arr as $k=>$v) {
		if ($multipleselect) {
			$s = in_array($k, $selected) ? "selected=\"selected\"" : "";
		} else {
			$s = $k == $selected ? "selected=\"selected\"" : "";
		}
		$k = htmlentities($k);
		
		// si la cl� commence par optgroup, on g�n�re une balise <optgroup>
		// Cf. la fonction makeSelectEdition($value), in commonselect.php
		if(substr($k, 0, 8) == "OPTGROUP") { echo "<optgroup label=\"$v\">";}
		elseif (substr($k, 0, 11) == "ENDOPTGROUP") { echo '</optgroup>';}
		//sinon on g�n�re une balise <option>
		else { echo '<option value="'. $k. '" '. $s. '>'. $v. '</option>'; }
	}
}

/**
 * Gen�re le fichier de CACHE d'une page dans une autre langue.
 *
 * @param string $lang la langue dans laquelle on veut g�n�rer le cache
 * @param string $file le fichier de cache
 * @param array $tags la liste des tags � internationaliser.
 *
 */
function generateLangCache($lang, $file, $tags)
{
	foreach($tags as $tag) {
		$dotpos = strpos($tag, '.');
		$group  = substr($tag, 0, $dotpos);
		$name   = substr($tag, $dotpos+1);

		$txt.= "'". $tag. "'=>'". str_replace("'", "\'",(getlodeltextcontents($name, $group, $lang))). "',";
	}
	$dir = dirname($file);
	if (!is_dir($dir)) {
		@mkdir($dir, 0777 & octdec($GLOBALS['filemask']));
		@chmod($dir, 0777 & octdec($GLOBALS['filemask']));
	}

	writefile($file, '<'.'?php if (!$GLOBALS[\'langcache\'][\''. $lang. '\']) $GLOBALS[\'langcache\'][\''. $lang. '\']=array(); $GLOBALS[\'langcache\'][\''. $lang. '\']+=array('. $txt. '); ?'. '>');
}

?>