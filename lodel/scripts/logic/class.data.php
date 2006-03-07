<?php
/**
 * Fichier de classe pour les backups
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
 * Classe de logique permettant de g�rer les backup et import de donn�es et de ME
 * 
 * @package lodel/logic
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajout� depuis la version 0.8
 * @see logic.php
 */
class DataLogic
{
	/**
	 * Constructeur
	 *
	 * Interdit l'acc�s aux utilisateurs qui ne sont pas ADMIN
	 */
	function DataLogic()
	{
		if ($GLOBALS['lodeluser']['rights'] < LEVEL_ADMIN) {
			die("ERROR: you don't have the right to access this feature");
		}
	}

	/**
	 * Importation des donn�es
	 *
	 * Cette fonction importe les donn�es issus d'un backup de lodel : le dump SQL, les fichiers associ�s (si ils ont �t� sauvegard�s).
	 *
	 * @param array $context le contexte pass� par r�f�rence
	 * @param array $error les �ventuelles erreur, pass�es par r�f�rence
	 */
	function importAction(&$context, &$error)
	{
		global $db;
		require_once 'func.php';
		$context['importdir'] = $GLOBALS['$importdir'];
		$context['fileregexp'] = '(site|revue)-\w+-\d+.zip';

		// les r�pertoires d'import
		$context['importdirs'] = array('CACHE');
		if ($context['importdir']) {
  		$context['importdirs'][] = $context['importdir'];
		}
		//Si un fichier a �t� upload�
		if($_FILES) {
		$archive                 = $_FILES['archive']['tmp_name'];
		$context['error_upload'] = $_FILES['archive']['error'];
		}
		// Upload du fichier
		if (!$context['error_upload'] && $archive && $archive != 'none' && is_uploaded_file($context['archive'])) {
			$prefixre   = '(site|revue)';
			$prefixunix = '{site,revue}';
			$file       = $archive;
			unset($_FILES);
		// Ficher d�j� sur le disque
		} elseif ($context['file'] && preg_match("/^(?:". str_replace('/', '\/', join('|', $context['importdirs'])). ")\/".$context['fileregexp']."$/", $context['file'], $result) && file_exists($context['file'])) {
			$prefixre = $prefixunix = $result[1];
			$file = $context['file'];
		} else { // rien
			$file = '';
		}

		if ($file) { // Si on a bien sp�cifi� un fichier
			do { // control block

				set_time_limit(120); //pas d'effet si safe_mode on ; on met le temps � unlimited
				//nom du fichier SQL
				$sqlfile = tempnam(tmpdir(), 'lodelimport_');
				//noms des r�pertoires accept�s
				$accepteddirs = array('lodel/txt', 'lodel/rtf', 'lodel/sources', 'docannexe/file', 'docannexe/image');
		
				require_once 'backupfunc.php';
				if (!importFromZip($file, $accepteddirs, array(), $sqlfile)) {
					
					$err = $error['error_extract'] = 'extract';
					return 'import';
				}
				#require_once 'connect.php';
				// drop les tables existantes
				$db->execute(lq('DROP TABLE IF EXISTS '. join(',', $GLOBALS['lodelsitetables']))) or dberror();
				//execution du dump SQL
				if (!$this->_execute_dump($sqlfile)) {
					$error['error_execute_dump'] = $err = $db->errormsg();
				}
				@unlink($sqlfile);
		
				require_once 'cachefunc.php';
				removefilesincache(SITEROOT, SITEROOT. 'lodel/edition', SITEROOT. 'lodel/admin');
		
				// verifie les .htaccess dans le CACHE
				$this->_checkFiles($context);
			} while(0);
		} else {
			$error['file'] = 'unknown_file';
			return 'import';
		}
		if(!$error) {
				$context['success'] = 1;
		}
		return 'import';
	}

	/**
	 * Sauvegarde des donn�es
	 *
	 * Fait un dump de la base de donn�es du site et si indiqu� sauve aussi les fichiers annexes et source.
	 *
	 * @param array $context le contexte pass� par r�f�rence
	 * @param array $error les �ventuelles erreur, pass�es par r�f�rence
	 */
	function backupAction(&$context, &$error)
	{
		$context['importdir'] = $GLOBALS['importdir'];
		#print_r($context);
		if ($context['backup']) { // si on a demand� le backup
			require_once 'func.php';
			require_once 'backupfunc.php';
			$site = $context['site'];
			$outfile = "site-$site.sql";
			//$uselodelprefix = true; // ? NON UTILISE
			$GLOBALS['tmpdir'] = $tmpdir = tmpdir();
			$errors = array();
			$this->_dump($site, $tmpdir. '/'. $outfile, $errors);
			if($errors) {
				$error = $errors;
				return 'backup';
			}
			// verifie que le fichier SQL n'est pas vide
			if (filesize($tmpdir. '/'. $outfile) <= 0) {
				$error['mysql'] = 'dump_failed';
				return 'backup';
			}
		
			// zip le site et ajoute la base
			$archivetmp      = tempnam($tmpdir, 'lodeldump_'). '.zip';
			$archivefilename = "site-$site-". date("dmy"). '.zip';
			$GLOBALS['excludes'] = $excludes        = array('lodel/sources/.htaccess',
															'docannexe/fichier/.htaccess',
															'docannexe/image/index.html',
															'docannexe/index.html',
															'docannexe/image/tmpdir-\*',
															'docannexe/tmp\*'
															);
			$dirs            = $context['sqlonly'] ? '' : 'lodel/sources docannexe';
		
			if ($zipcmd && $zipcmd != 'pclzip') { //Commande ZIP

				if (!$context['sqlonly']) {
					if (!chdir(SITEROOT)) {
						die ("ERROR: can't chdir in SITEROOT");
					}
					$prefixdir    = $tmpdir[0] == "/" ? '' : 'lodel/admin/';
					$excludefiles = $excludes ? " -x ". join(" -x ", $excludes) : "";
					system($zipcmd. " -q $prefixdir$archivetmp -r $dirs $excludefiles");
					if (!chdir("lodel/admin")) {
						die ("ERROR: can't chdir in lodel/admin");
					}
					system($zipcmd. " -q -g $archivetmp -j $tmpdir/$outfile");
				} else {
					system($zipcmd. " -q $archivetmp -j $tmpdir/$outfile");
				}
			} else { // Comande PCLZIP

				require_once 'pclzip/pclzip.lib.php';
				$archive = new PclZip($archivetmp);
				if (!$context['sqlonly']) {
					// function to exclude files and rename directories
					function preadd($p_event, &$p_header) 
					{
						global $excludes, $tmpdir; // that's bad to depend on globals like that
						$p_header['stored_filename'] = preg_replace("/^". preg_quote($tmpdir, "/"). "\//", "", $p_header['stored_filename']);
						foreach ($excludes as $exclude) {
							if (preg_match ("/^". str_replace('\\\\\*', '.*', preg_quote($exclude, "/")). "$/", $p_header['stored_filename'])) {
								return 0;
							}
						}
						return 1;
					}
					// end of function to exclude files
					$archive->create(array(SITEROOT. 'lodel/sources',
							SITEROOT. 'docannexe',
							$tmpdir. '/'. $outfile
							),
							PCLZIP_OPT_REMOVE_PATH,SITEROOT,
							PCLZIP_CB_PRE_ADD, 'preadd'
							);
				} else {
					$archive->create($tmpdir. "/". $outfile, PCLZIP_OPT_REMOVE_ALL_PATH);
				}
			} // end of pclzip option
		
			if (!file_exists($archivetmp)) {
				die ("ERROR: the zip command or library does not produce any output");
			}
			@unlink($tmpdir. '/'. $outfile); // delete the sql file
			#echo "toto";exit;
			if (operation($context['operation'], $archivetmp, $archivefilename, $context)) {
				$context['success'] = 1;
				return 'backup';
			}
			else {
				$context['success'] = 1;
				return 'backup';
			}
			return 'backup';
		}
		else {
			return 'backup';
		}
	}

	/**
	 * Importation du mod�le �ditorial
	 *
	 */
	function importmodelAction(&$context, &$error)
	{

	}


	/**
	 * Sauvegarde du mod�le �ditorial
	 *
	 *
	 */
	function backupmodelAction(&$context, &$error)
	{
		$context['importdir'] = $importdir;
		if ($context['backup']) {
			if(!$context['title']) {
				$error['title'] = 'title_required';
			}
			if(!$context['description']) {
				$error['description'] = 'description_required';
			}
			if(!$context['author']) {
				$error['author'] = 'author_required';
			}
			if(!$context['modelversion']) {
				$error['modelversion'] = 'modelversion_required';
			}
			if($error) { // Si on detecte des erreurs
				$context['error'] = $error;
				return 'backupmodel';
			}
			require 'backupfunc.php';
			$tmpfile        = tmpdir(). '/model.sql';
			$fh             = fopen($tmpfile, 'w');
			$description    = '<model>
			<lodelversion>'. $version. '</lodelversion>
			<date>'. date("Y-m-d"). '</date>
			<title>
			'. myhtmlentities(stripslashes($context['title'])). '
			</title>
			<description>
			'. myhtmlentities(stripslashes($context['description'])). '
			</description>
			<author>
			'. myhtmlentities(stripslashes($context['author'])). '
			</author>
			<modelversion>
			'. myhtmlentities(stripslashes($context['modelversion'])). '
			</modelversion>
			</model>
			';
		
			fputs($fh, '# '. str_replace("\n", "\n# ", $description). "\n#------------\n\n");
			
			$tables = array('#_TP_classes',
				'#_TP_tablefields',
				'#_TP_tablefieldgroups',
				'#_TP_types',
				'#_TP_persontypes',
				'#_TP_entrytypes',
				'#_TP_entitytypes_entitytypes',
				'#_TP_characterstyles',
				'#_TP_internalstyles'); //liste des tables de lodel � sauver.
			foreach ($tables as $table) {
				fputs($fh, 'DELETE FROM '. $table. ";\n");
			}
			$GLOBALS['currentprefix'] = $currentprefix = '#_TP_';
			$GLOBALS['showcolumns'] = true; // use by PMA to print the fields.
			//fait un DUMP de ces tables
			mysql_dump($currentdb, $tables, '', $fh, false, false, true); // get the content
			
			// select the optiongroups to export
			$dao = &getDAO('optiongroups');
			$vos = $dao->findMany('exportpolicy > 0 AND status > 0', '', 'name, id');
			$ids = array();
			foreach($vos as $vo) {
				$ids[] = $vo->id;
			}
			fputs($fh, "DELETE FROM #_TP_optiongroups;\n");
			mysql_dump($currentdb, array('#_TP_optiongroups'), '', $fh, false, false, true, '*', 'id '. sql_in_array($ids));
			fputs($fh, "DELETE FROM #_TP_options;\n");
			mysql_dump($currentdb,array('#_TP_options'), '', $fh, false, false, true, 'id, idgroup, name, title, type, defaultvalue, comment, userrights, rank, status, upd, edition, editionparams', 'idgroup '. sql_in_array($ids)); // select everything but not the value
		
			// R�cup�re la liste des tables de classe � sauver.
			$dao = &getDAO('classes');
			$vos = $dao->findMany('status > 0', '', 'class,classtype');
			$tables = array();
			foreach ($vos as $vo) {
				$tables[] = lq('#_TP_'. $vo->class);
				if ($vo->classtype == 'persons') {
					$tables[] = lq('#_TP_entities_'. $vo->class);
				}
			}
			if ($tables) {
				mysql_dump($currentdb, $tables, '', $fh, true, true, false); // get the table create
			}
			// it may be better to recreate the field at the import rather 
			// than using the created field. It may be more robust. Status quo at the moment.
			fclose($fh);
			
			if (filesize($tmpfile) <= 0) {
				die ('ERROR: mysql_dump failed');
			}
		
			$dirs = array();
			$dirstest = array('tpl', 'css', 'images', 'js');
			foreach($dirstest as $dir) {
				if ($context[$dir]) {
					$dirs[] = $dir;
				}
			}
			$zipfile = $this->_backupME($tmpfile, $dirs);
			$site = $context['site'];
			$filename  = "model-$site-". date("dmy"). ".zip";
			$operation = 'download';
			if (operation($operation, $zipfile, $filename, $context)) {
				$context['success'] = 1;
				return 'backupmodel';
			}
			@unlink($tmpfile);
			@unlink($zipfile);
			return 'backupmodel';
		}
		return 'backupmodel';
	}

	/**
	 * Dump SQL d'un site donn�
	 *
	 * @access private
	 * @param string $site le nom du site
	 * @param string $outfile le fichier dans lequel �crire le dump SQL
	 * @param resource $fh le descripteur de fichier (par d�faut 0)
	 * @param array $error tableau des erreurs
	 */
	function _dump($site, $outfile, &$error, $fh = 0)
	{
		global $db;
		if ($site && $GLOBALS['singledatabase'] != 'on') {
			$dbname = DATABASE."_".$site;
			if (!$fh)	{
				$fh = fopen($outfile, "w");
				$closefh = true;
			}
			if (!$fh)
				die("ERROR: unable to open file $outfile for writing");
		}	else	{
			$dbname = DATABASE;
		}
	
		if (!$db->selectDB($dbname)) {
			$error['database'] = 'error : '.$db->ErrorMsg().'<br />';
			return ;
		}
		$GLOBALS['currentprefix'] = "#_TP_";
		$tables = $GLOBALS['lodelsitetables'];
		$dao = &getDAO('classes');
		$vos = $dao->findMany('status > 0', '', 'class, classtype');
		foreach ($vos as $vo)	{
			$tables[] = lq("#_TP_". $vo->class);
			if ($vo->classtype == 'persons')
				$tables[] = lq('#_TP_entities_'. $vo->class);
		}
	
		mysql_dump($dbname, $tables, $outfile, $fh);
	
		if ($closefh)
			fclose($fh);
	}

	/**
	 * Execute un dump (fichier SQL) point� par $url
	 *
	 * @todo v�rifier que cette fonction ne prends pas trop de place en m�moire.
	 * @access private
	 * @param string $url le fichier SQL
	 * @param boolean $ignoreerrors. false par d�faut
	 * @return true si le dump a bien �t� execut�
	 */
	function _execute_dump($url, $ignoreerrors = false) 
	{
		$file_content = file($url);
		$query = '';
		foreach($file_content as $sql_line) {
			$tsl = trim($sql_line);
			if (($sql_line != "") && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != "#")) {
				$query .= $sql_line;
				if(preg_match("/;\s*$/", $sql_line)) {
					#echo "query:".lq($query)."<br />";
					$result = mysql_query(lq($query));
					if (!$result && !$ignoreerrors) die(mysql_error());
					$query = '';
				}
			}
		}
		return true;
	}

	/**
	 * V�rifie les fichiers CACHE et .htaccess et recr� les .htaccess.
	 *
	 * @param array $context le contexte pass� par r�f�rence.
	 */
	function _checkFiles(&$context)
	{
		$dirs = array('CACHE', 'lodel/admin/CACHE', 'lodel/edition/CACHE', 'lodel/txt', 'lodel/rtf', 'lodel/sources');
		foreach ($dirs as $dir) {
			if (!file_exists(SITEROOT. $dir)) {
				continue;
			}
			$file = SITEROOT. $dir. '/.htaccess';
			if (file_exists($file)) {
				@unlink($file);
			}
			$f = @fopen ($file, 'w');
			if (!$f) {
				$context['error_htaccess'].= $dir. ' ';
				$err = 1;
			} else {
				fputs($f, "deny from all\n");
				fclose ($f);
			}
		}
	}

	/**
	 * Cr�� un fichier ZIP du ME contenant le fichier SQL et �ventuellement les r�pertoires
	 * images, css, js et tpl.
	 *
	 * @access private
	 * @param string $sqlfile le fichier dump SQL
	 * @param array $dirs la liste des r�pertoires � inclure.
	 * @return le nom du fichier ZIP
	 */
	function _backupME($sqlfile, $dirs = array())
	{
		global $zipcmd;
	
		$acceptedexts = array ('html', 'js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'tiff');
		$tmpdir = tmpdir();
		$archivetmp = tempnam($tmpdir, 'lodeldump_'). '.zip';
	
		// Cherche si les r�pertoire � zipper contiennent bien des fichiers
		$zipdirs = array ();
		foreach ($dirs as $dir)	{
			if (!file_exists(SITEROOT. $dir))
				continue;
			$dh = opendir(SITEROOT. $dir);
			while (($file = readdir($dh)) && !preg_match("/\.(".join("|", $acceptedexts).")$/", $file))	{
			}
			if ($file)
				$zipdirs[] = $dir;
			closedir($dh);
		}
		//
	
		if ($zipcmd && $zipcmd != 'pclzip')	{ //commande ZIP
			if ($zipdirs)	{
				foreach ($zipdirs as $dir) {
					foreach ($acceptedexts as $ext)	{
						$files .= " $dir/*.$ext";
					}
				}
				if (!chdir(SITEROOT))
					die("ERROR: can't chdir in SITEROOT");
				$prefixdir = $tmpdir[0] == '/' ? '' : 'lodel/admin/';
				system($zipcmd." -q $prefixdir$archivetmp $files");
				if (!chdir("lodel/admin"))
					die("ERROR: can't chdir in lodel/admin");
				system($zipcmd." -q -g $archivetmp -j $sqlfile");
			}	else {
				system($zipcmd." -q $archivetmp -j $sqlfile");
			}
		}	else	{ // commande PCLZIP
			//require_once "pclzip.lib.php";
			require_once 'pclzip/pclzip.lib.php';
			$archive = new PclZip($archivetmp);
			if ($zipdirs)	{
				// function to exclude files and rename directories
				function preadd($p_event, & $p_header, $user_vars)
				{
					$p_header['stored_filename'] = preg_replace("/^".preg_quote($user_vars['tmpdir'], "/")."\//", "", $p_header['stored_filename']);
	
					#echo $p_header['stored_filename'],"<br>";
					return preg_match("/\.(".join("|", $user_vars['acceptedexts'])."|sql)$/", $p_header['stored_filename']);
				}
				// end of function to exclude files
				foreach ($zipdirs as $dir) {
					$files[] = SITEROOT.$dir;
				}
				$files[] = $sqlfile;
				$archive->user_vars = array ('tmpdir' => $tmpdir, 'acceptedexts' => $acceptedexts);
				$res = $archive->create($files, PCLZIP_OPT_REMOVE_PATH, SITEROOT, PCLZIP_CB_PRE_ADD, 'preadd');
				if (!$res)
					die("ERROR: Error while creating zip archive: ".$archive->error_string);
			}	else {
				$archive->create($sqlfile, PCLZIP_OPT_REMOVE_ALL_PATH);
			}
		} // end of pclzip option
	
		return $archivetmp;
	}

}// end of DataLogic class


//D�finition de la LOOP sur les fichiers d'import d�tect�s
function loop_files(&$context, $funcname)
{
	#global $importdirs,$fileregexp;
	$context['importdirs'][] = $GLOBALS['importdir'];
	foreach ($context['importdirs'] as $dir) {
		if ( $dh = @opendir($dir)) {
			while (($file = readdir($dh)) !== FALSE) {
				if (!preg_match("/^".$context['fileregexp']."$/i", $file)) {
					continue;
				}
				$localcontext = $context;
				$localcontext['filename']     = $file;
				$localcontext['fullfilename'] = "$dir/$file";
				call_user_func("code_do_$funcname", $localcontext);
			}
			closedir ($dh);
		}
	}
}

?>