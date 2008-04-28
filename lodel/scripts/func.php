<?php
/**
 * Fichier utilitaire proposant des fonctions souvent utilis�es dans Lodel
 *
 * PHP versions 4 et 5
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
 * @version CVS:$Id:
 * @package lodel
 */


function writefile ($filename,$text)
{
# echo "name de fichier : $filename";
   if (file_exists($filename)) { 
     if (! (unlink($filename)) ) die ("Ne peut pas supprimer $filename. probleme de right contacter Luc ou Ghislain");
   }
  $ret=($f=fopen($filename,"w")) && (fputs($f,$text)!==false) && fclose($f);
   
  @chmod ($filename,0666 & octdec($GLOBALS['filemask']));
  return  $ret;
}


function postprocessing(&$context)

{
  if ($context) {
    foreach($context as $key=>$val) {
      if (is_array($val)) {
	postprocessing($context[$key]);
      } else {
	$context[$key]=str_replace(array("\n","�\240"),array(" ","&nbsp;"),$val);
      }
    }
  }
}



/**
 *   Extrait toutes les variables pass�es par la m�thode post puis les stocke dans 
 *   le tableau $context
 */
function extract_post($arr=-1)
{
  if (!is_array($arr)) $arr=&$_POST;
  foreach ($arr as $key=>$val) {

    if (!isset($GLOBALS['context'][$key])) // protege
      $GLOBALS['context'][$key] = $val;
  }
  array_walk($GLOBALS['context'],"clean_request_variable");

}


function clean_request_variable(&$var, $personalsTags=array(), $personalsAttr=array())
{
	static $filter;
	if (!$filter) {
		require_once 'class.inputfilter.php';
		$filter = new InputFilter($personalsTags, $personalsAttr);
  	}

	if (is_array($var)) {
	#print_r($var);
	foreach(array_keys($var) as $k) {
		clean_request_variable($var[$k], $personalsTags, $personalsAttr);
	}
	} else {
		$var = magic_stripslashes($var);
		//ici on regle un bug : lors qu'on insere un espace ins�cable, l'appel � la fonction PHP 'chr' plante sur le &#160; dans la fonction $filter->decode
		if(preg_match("`&#160;`me", $var))
			$var = str_replace("&#160;", "&nbsp;", $var);
		$var = $filter->process(trim($var));
		// le process nettoie un peu trop : remplace les br ferm�s par des br ouverts : document plus valide..
		$var = str_replace("<br>", "<br />", $var);
		$var = str_replace(array("\n", "&nbsp;"), array("", "�\240"), $var);
  	}
}

function magic_addslashes($var) 
{
	/*if (!get_magic_quotes_gpc()) {
		$var = addslashes($var);
	}*/
	$var = stripslashes($var);
	$var = addslashes($var);
	return $var;
}

function magic_stripslashes($var) 
{
	if (get_magic_quotes_gpc()) {
		$var = stripslashes($var);
	}
	return $var;
}


function get_max_rank ($table,$where="") 
{
  if ($where) $where="WHERE ".$where;

  #require_once ($GLOBALS[home]."connect.php");
  $rank=$db->getone("SELECT MAX(rank) FROM #_TP_$table $where");
  if ($db->errorno()) dberror();

  return $rank+1;
}

function chrank($table,$id,$critere,$dir,$inverse="",$jointables="")
{
  global $db;

  $table="#_TP_$table";
  $dir=$dir=="up" ? -1 : 1;  if ($inverse) $dir=-$dir;
  $desc=$dir>0 ? "" : "DESC";
  if ($jointables) {
    $jointables=",#_TP_".
      trim(join(",#_TP_",preg_split("/,\s*/",$jointables)));
  }
  $result=$db->execute(lq("SELECT $table.id,$table.rank FROM $table $jointables WHERE $critere ORDER BY $table.rank $desc")) or dberror();

  $rank=$dir>0 ? 1 : mysql_num_rows($result);

  while ($row=$result->fetchrow($result)) {
    if ($row['id']==$id) {
      # intervertit avec le suivant s il existe
      if (!($row2=$result->fetchrow($result))) break;
      $db->execute(lq("UPDATE $table SET rank='$rank' WHERE id='$row2[id]'")) or dberror();
      $rank+=$dir;
    }
    if ($row['rank']!=$rank) {
      $db->execute(lq("UPDATE $table SET rank='$rank' WHERE id='$row[id]'")) or dberror();
    }
    $rank+=$dir;
  }
} 


/**
 * function returning the closing tag corresponding to the opening tag in the sequence
 * this function could be smarter.
 */
function closetags($text)
{
	preg_match_all("/<(\w+)\b[^>]*>/",$text,$results,PREG_PATTERN_ORDER);
	$n=count($results[1]);
	for($i=$n-1; $i>=0; $i--) $ret.="</".$results[1][$i].">";
	return $ret;
}


function myaddslashes (&$var)
{
  if (is_array($var)) {
    array_walk($var,"myaddslashes");
    return $var;
  } else {
    return $var=addslashes($var);
  }
}



function myfilemtime($filename)
{
  return file_exists($filename) ? filemtime($filename) : 0;
}


function update()
{
	if (defined("SITEROOT")) {
		@touch(SITEROOT."CACHE/maj");
	} else {
		@touch("CACHE/maj");
	}
}

function addmeta(&$arr,$meta="")
{
	foreach ($arr as $k=>$v) {
		if (strpos($k,"meta_")===0) {
			if (!isset($metaarr)) { // cree le hash des meta
		$metaarr=$meta ? unserialize($meta) : array();
			}
			if ($v) {
	$metaarr[$k]=$v;
			} else {
	unset($metaarr[$k]);
			}
		}
	}
	return $metaarr ? serialize($metaarr) : $meta;
}



function translate_xmldata($data) 
{
	return strtr($data,array("&"=>"&amp;","<" => "&lt;", ">" => "&gt;"));
}


### use the transaction now.
function unlock()
{
	global $db;
	// D�verrouille toutes les tables verrouill�es
	// fonction lock_write()
	if (!defined("DONTUSELOCKTABLES") || !DONTUSELOCKTABLES) {
		$db->execute(lq("UNLOCK TABLES")) or dberror();
	}
}


function lock_write()
{
	global $db;
  // Verrouille toutes les tables MySQL en �criture
  $list = func_get_args();
	if (!defined("DONTUSELOCKTABLES") || !DONTUSELOCKTABLES)
		$db->execute(lq("LOCK TABLES #_MTP_". join (" WRITE ,"."#_MTP_", $list)." WRITE")) or dberror();
}

function prefix_keys($prefix,$arr)
{
	if (!$arr) {
		return $arr;
	}
	foreach ($arr as $k=>$v) {
		$outarr[$prefix.$k]=$v;
	}
	return $outarr;
}

function array_merge_withprefix($arr1,$prefix,$arr2)
{
	if (!$arr2) {
		return $arr1;
	}
	foreach ($arr2 as $k=>$v) {
		$arr1[$prefix.$k]=$v;
	}
	return $arr1;
}

function getoption($name)
{
	global $db;
	static $options_cache;
	if (!$name) return;
	if (!isset($options_cache)) {
		$optionsfile=SITEROOT."CACHE/options_cache.php";
	
		if (file_exists($optionsfile)) {
			require($optionsfile);
		} else {
			require_once('optionfunc.php');
			$options_cache = cacheOptionsInFile($optionsfile);
		}
	}
	if (is_array($name)) {
		foreach ($name as $n) {
			if ($options_cache[$n]) $ret[$n]=stripslashes($options_cache[$n]);
		}    
		return  ($ret);
	} else {
		if ($options_cache[$name]) // cached ?
			return  stripslashes ($options_cache[$name]);
		$critere="name='$name'";
	}
}

function getlodeltext($name,$group,&$id,&$contents,&$status,$lang=-1)
{
	
	if ($group=="") {
		if ($name[0]!='[' && $name[1]!='@') return array(0,$name);
		$dotpos=strpos($name,".");
		if ($dotpos) {
			$group=substr($name,1,$dotpos); 
			$name=substr($name,$dotpos+1,-1);
		} else {
			die("ERROR: unknow group for getlodeltext");
		}
	}
	if ($lang==-1) $lang=$GLOBALS['lang'] ? $GLOBALS['lang'] : $GLOBALS['lodeluser']['lang'];
	if (!$lang) $lang = $GLOBALS['installlang']; // if no lang is specified choose the default installation language
	require_once("connect.php");
	global $db;
	
	if ($group!="site") {
		usemaindb();
		$prefix="#_MTP_";
	} else {
		$prefix="#_TP_";
	}
	
	$critere=$GLOBALS['lodeluser']['visitor'] ? "" : "AND status>0";
	$logic=false;
	do {
		$arr=$db->getRow("SELECT id,contents,status FROM ".lq($prefix)."texts WHERE name='".$name."' AND textgroup='".$group."' AND (lang='$lang' OR lang='') $critere ORDER BY lang DESC");
		if ($arr===false) dberror();
		if (!$GLOBALS['lodeluser']['admin'] || $logic) break;
		
		if (!$arr) {
			
			// create the textfield
			require_once("logic.php");
			$logic=getLogic("texts");
			$logic->createTexts($name,$group);
		}
	} while(!$arr);
	
	if ($group!="site") usecurrentdb();
	
	$id=$arr['id'];
	$contents=$arr['contents'];
	$status=$arr['status'];
	if (!$contents && $GLOBALS['lodeluser']['visitor']) $contents="@".$name;
}

function getlodeltextcontents($name,$group="",$lang=-1)
{
	if ($lang==-1) $lang=$GLOBALS['lang'] ? $GLOBALS['lang'] : $GLOBALS['lodeluser']['lang'];
	if ($GLOBALS['langcache'][$lang][$group.".".$name]) {
		return $GLOBALS['langcache'][$lang][$group.".".$name];
	} else {
		#echo "name=$name,group=$group,id=$id,contents=$contents,status=$status,lang=$lang<br />";
		getlodeltext($name,$group,$id,$contents,$status,$lang);
		return $contents;
	}
}

function makeurlwithid ($id, $base = 'index')
{
	
	if (is_numeric($base)) {
		$t    = $id;
		$id   = $base;
		$base = $t;
	} // exchange
	if (defined('URI')) {
		$uri = URI;
	} else {
		// compat 0.7
		if ($GLOBALS['idagauche']) {
			$uri = 'leftid';
		}
	}
	
	/*$class = $GLOBALS['db']->getOne(lq("SELECT class FROM #_TP_objects WHERE id='$id'"));
		if ($GLOBALS['db']->errorno()) {
			dberror();
		}
	if($class != 'entities')
		$uri = '';*/
	switch($uri) {
	case 'leftid':
		return $base. $id. '.'. $GLOBALS['extensionscripts'];
	//fabrique des urls type index.php?/rubrique/mon-titre
	case 'path':
		$id = intval($id);
		$path = getPath($id,'path');
		return $path;
	case 'querystring':
		$id = intval($id);
		$path = getPath($id,'querystring');
		return $path;
	default:
		return $base. '.'. $GLOBALS['extensionscripts']. '?id='. $id;
	}
}

if (!function_exists("file_get_contents")) {
  function file_get_contents($file) 
  {
    $fp=fopen($file,"r") or die("Impossible to read the file $file");
    while(!feof($fp)) $res.=fread($fp,2048);
    fclose($fp);
    return $res;
  }
}
/**
 * retourne le chemin complet vers une entit�e *
 * @param integer $id identifiant num�rique de l'entit�e * 
 * @param string $urltype le type d'url utilis�e(path,querystring)
 * @return string le chemin
 * @since fonction ajout�e en 0.8
 */
function getPath($id, $urltype,$base='index')
{
	$urltype = 'querystring'; //la version actuelle de lodel ne g�re que le type path
	if($urltype!='path' && $urltype!='querystring') {
		return;
	}
	$id = intval($id);
		$result = $GLOBALS['db']->execute(lq("SELECT identifier FROM #_TP_entities INNER JOIN #_TP_relations ON id1=id WHERE id2='$id' ORDER BY degree DESC")) or dberror();
		while(!$result->EOF) {
			$path.= '/'. $result->fields['identifier'];
			$result->MoveNext();
		}
		$row = $GLOBALS['db']->getRow(lq("SELECT identifier FROM #_TP_entities WHERE id='$id'"));
		if ($GLOBALS['db']->errorno()) {
			dberror();
		}
		$path.= "/$id-". $row['identifier'];
		if($urltype == 'path') {
			return $base. '.'. $GLOBALS['extensionscripts']. $path;
		}
		return "$base.". $GLOBALS['extensionscripts']. "?$path";
}


/**
 * sent the header and the file for downloading
 * 
 * @param     string   name of the real file.
 * @param     string   name to send to the browser.
 * 
 */
function download($filename,$originalname="",$contents="")
{
  $mimetype = array(
		    'doc'=>'application/msword',
		    'htm'=>'text/html',
		    'html'=>'text/html',
		    'jpg'=>'image/jpeg',
		    'gif'=>'image/gif',
		    'png'=>'image/png',
		    'pdf'=>'application/pdf',
		    'txt'=>'text/plain',
		    'xls'=>'application/vnd.ms-excel'
		    );

  if (!$originalname) $originalname=$filename;
  $originalname=preg_replace("/.*\//","",$originalname);
  $ext=substr($originalname,strrpos($originalname,".")+1);
  $size = $filename ? filesize($filename) : strlen($contents);
  get_PMA_define(); 
  if($mimetype[$ext] && !(PMA_USR_BROWSER_AGENT == 'IE' && $ext == "pdf" && PMA_USR_OS != "Mac")){
    $mime = $mimetype[$ext];
    $disposition = "inline";
  } else {
    $mime = "application/force-download";
    $disposition = "attachment";
  }
  if ($filename) {
    $fp=fopen($filename,"rb");
    if (!$fp) die ("ERROR: The file \"$filename\" is not readable");
  }
  // fix for IE catching or PHP bug issue
  header("Pragma: public");
  header("Expires: 0"); // set expiration time
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

#  header("Cache-Control: ");// leave blank to avoid IE errors (from on uk.php.net)
#  header("Pragma: ");// leave blank to avoid IE errors (from on uk.php.net)

  header("Content-type: $mime\n");
  header("Content-transfer-encoding: binary\n");
  header("Content-length: ".$size."\n");
  header("Content-disposition: $disposition; filename=\"$originalname\"\n");
  sleep(1); // don't know why... (from on uk.php.net)
  if ($filename) {
    fpassthru($fp); 
  } else { 
    echo $contents; 
  }
}


// taken from phpMyAdmin 2.5.4

function get_PMA_define()
{

// Determines platform (OS), browser and version of the user
// Based on a phpBuilder article:
//   see http://www.phpbuilder.net/columns/tim20000821.php

    // loic1 - 2001/25/11: use the new globals arrays defined with
    // php 4.1+
    if (!empty($_SERVER['HTTP_USER_AGENT'])) {
        $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
    }

    #} else if (!empty($HTTP_SERVER_VARS['HTTP_USER_AGENT'])) {
    #    $HTTP_USER_AGENT = $HTTP_SERVER_VARS['HTTP_USER_AGENT'];
    #} else if (!isset($HTTP_USER_AGENT)) {
    #    $HTTP_USER_AGENT = '';
    #}

    // 1. Platform
    if (strstr($HTTP_USER_AGENT, 'Win')) {
        define('PMA_USR_OS', 'Win');
    } else if (strstr($HTTP_USER_AGENT, 'Mac')) {
        define('PMA_USR_OS', 'Mac');
    } else if (strstr($HTTP_USER_AGENT, 'Linux')) {
        define('PMA_USR_OS', 'Linux');
    } else if (strstr($HTTP_USER_AGENT, 'Unix')) {
        define('PMA_USR_OS', 'Unix');
    } else if (strstr($HTTP_USER_AGENT, 'OS/2')) {
        define('PMA_USR_OS', 'OS/2');
    } else {
        define('PMA_USR_OS', 'Other');
    }

    // 2. browser and version
    // (must check everything else before Mozilla)

    if (ereg('Opera(/| )([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
        define('PMA_USR_BROWSER_VER', $log_version[2]);
        define('PMA_USR_BROWSER_AGENT', 'OPERA');
    } else if (ereg('MSIE ([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
        define('PMA_USR_BROWSER_VER', $log_version[1]);
        define('PMA_USR_BROWSER_AGENT', 'IE');
    } else if (ereg('OmniWeb/([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
        define('PMA_USR_BROWSER_VER', $log_version[1]);
        define('PMA_USR_BROWSER_AGENT', 'OMNIWEB');
    //} else if (ereg('Konqueror/([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
    // Konqueror 2.2.2 says Konqueror/2.2.2
    // Konqueror 3.0.3 says Konqueror/3
    } else if (ereg('(Konqueror/)(.*)(;)', $HTTP_USER_AGENT, $log_version)) {
        define('PMA_USR_BROWSER_VER', $log_version[2]);
        define('PMA_USR_BROWSER_AGENT', 'KONQUEROR');
    } else if (ereg('Mozilla/([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)
               && ereg('Safari/([0-9]*)', $HTTP_USER_AGENT, $log_version2)) {
        define('PMA_USR_BROWSER_VER', $log_version[1] . '.' . $log_version2[1]);
        define('PMA_USR_BROWSER_AGENT', 'SAFARI');
    } else if (ereg('Mozilla/([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
        define('PMA_USR_BROWSER_VER', $log_version[1]);
        define('PMA_USR_BROWSER_AGENT', 'MOZILLA');
    } else {
        define('PMA_USR_BROWSER_VER', 0);
        define('PMA_USR_BROWSER_AGENT', 'OTHER');
    }
}

/**
 * Save the file or image files associated with a entites (annex file). Check it is a valid image.
 *
 * @param    dir    If $dir is numeric it is the id of the entites. In the other case, $dir should be a temporary directory.
 * @param docAnnexe boolean = true if the file is saved in the directory "docannexe", else false
 *
 */
function save_file($type, $dir, $file, $filename, $uploaded, $move, &$error, $docAnnexe=true) 
{
	if ($type != 'file' && $type != 'image') {
		die("ERROR: type is not a valid file type");
	}
	if (!$dir) {
		die("Internal error in saveuploadedfile dir=$dir");
	}
	if (is_numeric($dir)) {
		$dir = "docannexe/$type/$dir";
	}
	if (!$file) {
		die("ERROR: save_file file is not set");
	}
	if ($type == 'image') { // check this is really an image
		if ($uploaded) { // it must be first moved if not it cause problem on some provider where some directories are forbidden
			$tmpdir = tmpdir();
			$newfile=$tmpdir."/".basename($file);
			if ($file != $newfile && !move_uploaded_file($file, $newfile)) {
				die("ERROR: a problem occurs while moving the uploaded file from $file to $newfile.");
			}
			$file = $newfile;
    		}
		if (!filesize($file)) {
			$error = 'readerror'; return;
		}
		$info = getimagesize($file);
		if (!is_array($info)) {
			$error = 'imageformat'; return;
		}
		$exts = array("gif", "jpg", "png", "swf", "psd", "bmp", "tiff", "tiff", "jpc", "jp2", "jpx", "jb2", "swc", "iff");
    $ext = $exts[$info[2]-1];
		if (!$ext) { // si l'extension n'est pas bonne
			$error = 'imageformat'; return;
		}
	}

	if ($docAnnexe === true) {
		checkdocannexedir($dir);
		}

	if ($type == 'image') {
		$filename = preg_replace("/\.\w+$/", "", basename($filename)); // take only the name, remove the extensio
		$dest = $dir. '/'. $filename. '.'. $ext;
	} else {
		$filename = rewriteFilename($filename);
		$dest = $dir. '/'. basename($filename);
	}
	if(defined("SITEROOT"))
	{
		$dest = SITEROOT . $dest;	
	}
	if (!copy($file, $dest)) {
		die("ERROR: a problem occurs while moving the file.");
	}
	// and try to delete
	if ($move) {
		@unlink($file);
	}
	@chmod($dest, 0666 & octdec($GLOBALS['filemask']));
	return $dest;
}

/**
 * V�rifie que le r�pertoire $dir, un r�pertoire de docannexe existe. Dans le cas
 * contraire le cr�e
 *
 * @param string $dir le nom du r�pertoire
 */
function checkdocannexedir($dir)
{
	if(defined("SITEROOT"))
	{//si le siteroot est d�fini
		$rep = SITEROOT . $dir;
		if(!file_exists(SITEROOT . "docannexe/image"))
		{//il n'y a pas de r�pertoire docannexe/image dans le siteroot, on essaye de le cr�er
			if (!@mkdir(SITEROOT . "docannexe/image",0777 & octdec($GLOBALS['filemask']))) {
				//on arrive pas a le cr�er, peut etre que docannexe n'existe pas, on tente de le cr�er
				if (!@mkdir(SITEROOT . "docannexe",0777 & octdec($GLOBALS['filemask']))) {
					die("ERROR: impossible to create the directory \"docannexe\"");//peut rien faire
				}
				else
				{//on a cr�� le repertoire docannexe, on tente de cr�er image
					if (!@mkdir(SITEROOT . "docannexe/image",0777 & octdec($GLOBALS['filemask']))) {
						die("ERROR: impossible to create the directory \"docannexe/image\"");//peut rien faire
					}
				}
			}
		}
	}
	else
	{
		$rep = $dir;
		if(!file_exists("docannexe/image"))
		{
			if (!@mkdir("docannexe/image",0777 & octdec($GLOBALS['filemask']))) {
				if (!@mkdir("docannexe",0777 & octdec($GLOBALS['filemask']))) {
					die("ERROR: impossible to create the directory \"docannexe\"");
				}
				else
				{
					if (!@mkdir("docannexe/image",0777 & octdec($GLOBALS['filemask']))) {
						die("ERROR: impossible to create the directory \"docannexe/image\"");
					}
				}
			}
		}
	}
	if(defined("SITEROOT"))
	{//si le siteroot est d�fini
		if(!file_exists(SITEROOT . "docannexe/file"))
		{//il n'y a pas de r�pertoire docannexe/image dans le siteroot, on essaye de le cr�er
			if (!@mkdir(SITEROOT . "docannexe/file",0777 & octdec($GLOBALS['filemask']))) {
				//on arrive pas a le cr�er, peut etre que docannexe n'existe pas, on tente de le cr�er
				if (!@mkdir(SITEROOT . "docannexe",0777 & octdec($GLOBALS['filemask']))) {
					die("ERROR: impossible to create the directory \"docannexe\"");//peut rien faire
				}
				else
				{//on a cr�� le repertoire docannexe, on tente de cr�er image
					if (!@mkdir(SITEROOT . "docannexe/file",0777 & octdec($GLOBALS['filemask']))) {
						die("ERROR: impossible to create the directory \"docannexe/file\"");//peut rien faire
					}
				}
			}
		}
	}
	else
	{
		if(!file_exists("docannexe/file"))
		{
			if (!@mkdir("docannexe/file",0777 & octdec($GLOBALS['filemask']))) {
				if (!@mkdir("docannexe",0777 & octdec($GLOBALS['filemask']))) {
					die("ERROR: impossible to create the directory \"docannexe\"");
				}
				else
				{
					if (!@mkdir("docannexe/file",0777 & octdec($GLOBALS['filemask']))) {
						die("ERROR: impossible to create the directory \"docannexe/file\"");
					}
				}
			}
		}
	}

	if (!file_exists($rep)) {
		if (!@mkdir($rep,0777 & octdec($GLOBALS['filemask']))) {
			die("ERROR: impossible to create the directory \"$rep\"");
		}
		@chmod($rep,0777 & octdec($GLOBALS['filemask']));
		writefile($rep. '/index.html', '');
	}
}


function tmpdir()
{
	$tmpdir=defined("TMPDIR") && (TMPDIR) ? TMPDIR : "CACHE/tmp";
	if (!file_exists($tmpdir)) { 
		mkdir($tmpdir,0777  & octdec($GLOBALS['filemask']));
		chmod($tmpdir,0777 & octdec($GLOBALS['filemask'])); 
	}
	return $tmpdir;
}

function myhtmlentities($text)
{
	return str_replace(array("&","<",">","\""),array("&amp;","&lt;","&gt;","&quot;"),$text);
}


//
// Main function to add/modify records 
//
function setrecord($table,$id,$set,$context=array())
{
	global $db;
	
	$table=lq("#_TP_").$table;
	
	if ($id>0) { // update
		foreach($set as $k=>$v) {
			if (is_numeric($k)) { // get it from context
	$k=$v;
	$v=$context[$k];
			}
			if ($update) $update.=",";
			$update.="$k=".$db->qstr($v);
		}
		if ($update)
			$db->execute("UPDATE $table SET  $update WHERE id='$id'") or dberror();
	} else {
		$insert="";$values="";
		if (is_string($id) && $id=="unique") {
			$id=uniqueid($table);
			$insert="id";$values="'".$id."'";
		}
		foreach($set as $k=>$v) {
			if (is_numeric($k)) { // get it from context
	$k=$v;
	$v=$context[$k];
			}
			if ($insert) { $insert.=","; $values.=","; }
			$insert.=$k;
			$values.=$db->qstr($v);
		}
	
		if ($insert) {
	
			$db->execute("REPLACE INTO $table (".$insert.") VALUES (".$values.")") or dberror();
			if (!$id) $id=$db->insert_id();
		}
	}
	return $id;
}

/**
 *
 * Function to solve the UTF8 poor support in MySQL
 * This function should be i18n in the futur to support more language
 */
/**
 * Fonction qui indique si une chaine est en utf-8 ou non
 *
 * Cette fonction est inspir�e de Dotclear et de
 * http://w3.org/International/questions/qa-forms-utf-8.html.
 *
 * @param string $string la cha�ne � tester
 * @return le r�sultat de la fonction preg_match c'est-a-dire false si la chaine n'est pas en
 * UTF8
 */
function isUTF8($string)
	{
		// From http://w3.org/International/questions/qa-forms-utf-8.html
		return preg_match('%^(?:
			  [\x09\x0A\x0D\x20-\x7E]			# ASCII
			| [\xC2-\xDF][\x80-\xBF]				# non-overlong 2-byte
			| \xE0[\xA0-\xBF][\x80-\xBF]			# excluding overlongs
			| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}	# straight 3-byte
			| \xED[\x80-\x9F][\x80-\xBF]			# excluding surrogates
			| \xF0[\x90-\xBF][\x80-\xBF]{2}		# planes 1-3
			| [\xF1-\xF3][\x80-\xBF]{3}			# planes 4-15
			| \xF4[\x80-\x8F][\x80-\xBF]{2}		# plane 16
		)*$%xs', $string);
	}

/**
 * Transforme une chaine de caract�re UTF8 en minuscules d�saccentu�es
 *
 * Cette fonction prends en entr�e une cha�ne en UTF8 et donne en sortie une cha�ne
 * o les accents ont �t� remplac�s par leur �quivalent d�saccentu�. De plus les caract�res
 * sont mis en minuscules et les espaces en d�but et fin de chaine sont enlev�s.
 *
 * Cette fonction est utilis�e pour les entrees d'index ainsi que dans le moteur de recherche
 * et pour le calcul des identifiants litt�raux.
 *
 * @param string $text le texte � passer en entr�e
 * @return le texte transform� en minuscule
 */
function makeSortKey($text)
{
	$text = strip_tags($text);
	//remplacement des caract�res accentues en UTF8
	$replacement = array(chr(197).chr(146) => 'OE', chr(197).chr(147) => 'oe',
											chr(197).chr(160) => 'S',chr(197).chr(189) => 'Z', 
											chr(197).chr(161) => 's',	chr(197).chr(190) => 'z', 
											chr(197).chr(184) => 'Y',	chr(194).chr(165) => 'Y', 
											chr(194).chr(181) => 'u',	chr(195).chr(134) => 'AE',
											chr(195).chr(133) => 'A', chr(195).chr(132) => 'A',
											chr(195).chr(131) => 'A', chr(195).chr(130) => 'A',
											chr(195).chr(129) => 'A', chr(195).chr(128) => 'A',
											chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
											chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
											chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
											chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
											chr(195).chr(143) => 'I', chr(195).chr(144) => 'D',
											chr(195).chr(145) => 'N', chr(195).chr(146) => 'O',
											chr(195).chr(147) => 'O', chr(195).chr(148) => 'O',
											chr(195).chr(149) => 'O', chr(195).chr(150) => 'O',
											chr(195).chr(152) => 'O', chr(195).chr(153) => 'U',
											chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
											chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
											chr(195).chr(159) => 'SS', chr(195).chr(160) => 'a',
											chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
											chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
											chr(195).chr(165) => 'a', chr(195).chr(166) => 'ae',
											chr(195).chr(167) => 'c', chr(195).chr(168) => 'e',
											chr(195).chr(169) => 'e', chr(195).chr(170) => 'e',
											chr(195).chr(171) => 'e', chr(195).chr(172) => 'i',
											chr(195).chr(173) => 'i', chr(195).chr(174) => 'i',
											chr(195).chr(175) => 'i', chr(195).chr(176) => 'o',
											chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
											chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
											chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
											chr(195).chr(184) => 'o', chr(195).chr(185) => 'u',
											chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
											chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
											chr(195).chr(191) => 'y',
									);
	$text = strtr($text,$replacement);
	return trim(strtolower($text));
}

/**
 * rightonentity check if a user has the rights to perform an action on an entity
 * @param string $action the action to be performed : create, edit, delete,...
 * @param array $context the current context
 * @return boolean true if the user has the right, false ifnot
 */
function rightonentity ($action, $context)
{
	if ($GLOBALS['lodeluser']['admin']) return true;

	if ($context['id'] && (!$context['usergroup'] || !$context['status'])) {
		// get the group, the status, and the parent
		$row = $GLOBALS['db']->getRow (lq ("SELECT idparent,status,usergroup, iduser FROM #_TP_entities WHERE id='".$context['id']."'"));
	if (!$row) die ("ERROR: internal error in rightonentity");
	$context = array_merge ($context, $row);
	}
  // groupright ?
	if ($context['usergroup']) {
  	$groupright = in_array ($context['usergroup'], explode (',', $GLOBALS['lodeluser']['groups']));
  	if (!$groupright)return false;
	}

	// only admin can work at the base.
	$editorok= $GLOBALS['lodeluser']['editor'] && $context['idparent'];
	// redactor are ok, only if they own the document and it is not protected.
	$redactorok = ($context['iduser']==$GLOBALS['lodeluser']['id'] && $GLOBALS['lodeluser']['redactor']) && $context['status']<8 && $context['idparent'];

	switch($action) {
	case 'create' :
		return ($GLOBALS['lodeluser']['editor'] ||  ($GLOBALS['lodeluser']['redactor'] && $context['status']<8));// &&  $context['id'];
		break;
	case 'delete' :
		return (abs($context['status'])<8 && $editorok) || ($context['status']<0 && $redactorok);
		break;
	case 'edit':
	case 'advanced' :
		return $editorok || $redactorok;
		break;
	case 'move' :
	case 'changerank' :
	case 'publish' :
	case 'unpublish' :
	case 'protect' :
		return $editorok;
		break;
	case 'changestatus' :
		if ($context['status']<0) {
			return $editorok || $redactorok;
		} else {
			return $editorok;
		} 
	default:
		if ($GLOBALS['lodeluser']['visitor'])
			die("ERROR: unknown action \"$action\" in the loop \"rightonentity\"");
		return;
	}
}//end of rightonentity function


/**
 * generate the SQL criteria depending whether ids is an array or a number
 */

function sql_in_array($ids) 
{
	return is_array($ids) ? "IN ('".join("','",$ids)."')" : "='".$ids."'";
}

/**
 * DAO factory
 *
 */

function &getDAO($table)
{
	static $factory; // cache
	if ($factory[$table]) {
		return $factory[$table]; // cache
	}
  require_once 'dao.php' ;
  require_once 'dao/class.'.$table.'.php';
  $daoclass = $table. 'DAO';
  $factory[$table] = new $daoclass;
  return $factory[$table];
}

/**
 * generic DAO factory
 *
 */
function &getGenericDAO($table, $idfield)
{
	static $factory; // cache
	if ($factory[$table]) {
		return $factory[$table]; // cache
	}
	require_once 'dao.php';
	require_once 'genericdao.php';
	$factory[$table] = new genericDAO ($table,$idfield);
	return $factory[$table];
}

/**
 * Return true if a type can contains other types.
 * (this function is used in edition, to make entities clicable or not)
 * @param idtype id of the type
 */
function canContainTypes ($idtype)
{
	global $db;
	//select types in entitytypes_entitytypes which can be contains in idtype (identitytypes2) 
	//but select only those who can be contains directly (not in advanced function)
	$sql = "SELECT COUNT(*) as count FROM #_TP_entitytypes_entitytypes , #_TP_types as t WHERE identitytype = t.id AND identitytype2='$idtype' AND t.display!='advanced'";
	$count = $db->getOne (lq($sql));
	if ($count === false) return false;
	if ($count > 0) return true;
	return false;
}

function mystripslashes (&$var)
{
	if (is_array($var)) {
		array_walk($var,"mystripslashes");
		return $var;
	} else {
		return $var=stripslashes($var);
	}
}

/**
 * Indentation de code HTML, XML
 *
 * @param string $source le code a indenter
 * @param string $indenter les caract�res � utiliser pour l'indentation. Par d�faut deux espaces.
 * @return le code indent� proprement
 */
function _indent($source, $indenter = '  ')
{
	if(preg_match('/<\?xml[^>]*\s* version\s*=\s*[\'"]([^"\']*)[\'"]\s*encoding\s*=\s*[\'"]([^"\']*)[\'"]\s*\?>/i', $source)) {
			$source = preg_replace('/<\?xml[^>]*\s* version\s*=\s*[\'"]([^"\']*)[\'"]\s*encoding\s*=\s*[\'"]([^"\']*)[\'"]\s*\?>/i', '', $source);
			require_once 'xmlfunc.php';
			$source = indentXML($source, false, $indenter);
			return $source;
		}
	$source = _indent_xhtml($source,$indenter);
	return $source;
}



// Function to seperate multiple tags one line (used by function _indent_xhtml)
function fix_newlines_for_clean_html($fixthistext)
{
	$fixthistext_array = explode("\n", $fixthistext);

	foreach ($fixthistext_array as $unfixedtextkey => $unfixedtextvalue) {

 		// Exception for fckeditor
		if (preg_match("/fck_.+editor/", $unfixedtextvalue))
		{
			$fixedtext_array[$unfixedtextkey] = $unfixedtextvalue;
		}
		
		//Makes sure empty lines are ignores
		else if (!preg_match("/^(\s)*$/", $unfixedtextvalue))
		{
			$fixedtextvalue = preg_replace("/[^[em|sup|sub|span]]>(\s|\t)*</U", ">\n<", $unfixedtextvalue);
			$fixedtext_array[$unfixedtextkey] = $fixedtextvalue;
		}
		
	}
	
	if (is_array($fixedtext_array)) {
		return implode("\n", $fixedtext_array);
	} else {
		return false;
	}
}

/**
 * Indentation de code XHTML
 *
 * @param string $uncleanhtml le code a indenter
 * @param string $indent les caract�res � utiliser pour l'indentation. Par d�ffaut deux espaces.
 * @return le code indent� proprement
 */


function _indent_xhtml ($uncleanhtml, $indent = "  ")
{
	//Set wanted indentation
	//$indent = "    ";
	//Uses previous function to seperate tags
	if ($fixed_uncleanhtml = fix_newlines_for_clean_html($uncleanhtml)) {

		$uncleanhtml_array = explode("\n", $fixed_uncleanhtml);
	
		//Sets no indentation
		$indentlevel = 0;
		foreach ($uncleanhtml_array as $uncleanhtml_key => $currentuncleanhtml)
		{
			//Removes all indentation
			$currentuncleanhtml = preg_replace("/\t+/", "", $currentuncleanhtml);
			$currentuncleanhtml = preg_replace("/^\s+/", "", $currentuncleanhtml);
		
			$replaceindent = "";
		
			//Sets the indentation from current indentlevel
			for ($o = 0; $o < $indentlevel; $o++)
			{
				$replaceindent .= $indent;
			}
		
			//If self-closing tag, simply apply indent
			if (preg_match("/<(.+)\/>/", $currentuncleanhtml))
			{ 
				$cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
			}
			//If doctype declaration, simply apply indent
			else if (preg_match("/<!(.*)>/", $currentuncleanhtml))
			{ 
				$cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
			}
			//If opening AND closing tag on same line, simply apply indent
			else if (preg_match("/<[^\/](.*)>/", $currentuncleanhtml) && preg_match("/<\/(.*)>/", $currentuncleanhtml))
			{ 
				$cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
			}
			//If closing HTML tag or closing JavaScript clams, decrease indentation and then apply the new level
			else if (preg_match("/<\/(.*)>/", $currentuncleanhtml) || preg_match("/^(\s|\t)*\}{1}(\s|\t)*$/", $currentuncleanhtml))
			{
				$indentlevel--;
				$replaceindent = "";
				for ($o = 0; $o < $indentlevel; $o++)
				{
					$replaceindent .= $indent;
				}
			
				$cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
			}
			//If opening HTML tag AND not a stand-alone tag, or opening JavaScript clams, increase indentation and then apply new level
			else if ((preg_match("/<[^\/](.*)>/", $currentuncleanhtml) && !preg_match("/<(link|meta|base|br|img|hr)(.*)>/", $currentuncleanhtml)) || preg_match("/^(\s|\t)*\{{1}(\s|\t)*$/", $currentuncleanhtml))
			{
				$cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
			
				$indentlevel++;
				$replaceindent = "";
				for ($o = 0; $o < $indentlevel; $o++)
				{
					$replaceindent .= $indent;
				}
			}
			else
			//Else, only apply indentation
			{$cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;}
		}
		//Return single string seperated by newline

		return implode("\n", $cleanhtml_array);	
	} else {
			return '';
		}
}



/**
 * R�cup�ration des champs g�n�riques dc.* associ�s aux entit�s
 *
 * @param integer $id identifiant num�ique de l'entit� dont on veut r�cup�rer un champ dc
 * @param string $dcfield le nom du champ � r�cup�rer (sans le dc.devant). Ex : .'description' pour 'dc.description'
 * @return le contenu du champ pass� dans le param�tre $dcfield
 */
function get_dc_fields($id, $dcfield)
{
	$dcfield = 'dc.' . $dcfield;
	global $db;
	if ($result = $db->execute(lq("SELECT #_TP_entities.id, #_TP_types.class, #_TP_tablefields.name, #_TP_tablefields.g_name
	FROM #_TP_entities, #_TP_types, #_TP_tablefields
  	WHERE (#_TP_tablefields.g_name = '$dcfield')
  	AND #_TP_tablefields.class = #_TP_types.class
  	AND #_TP_entities.idtype = #_TP_types.id
  	AND #_TP_entities.id = $id")
	))

	{
		if ($row = $result->fields)
			{
			$id  = $row['id'];
			$id_class_fields[$id]['class'] = $row['class'];
			$id_class_fields[$id][$row['g_name']] = $row['name'];
	
			if ($id_class_fields[$id][$dcfield])
				{
				$class_table = "#_TP_".$id_class_fields[$id]['class'];
				$field = $id_class_fields[$id][$dcfield];
				$result =$db->getOne(lq("SELECT $field FROM $class_table WHERE identity = '$id'"));
				if ($result===false) {
					dberror();
					}
  				}
  			return $result;
			} 
	else return false;
	}
else return false;
}

// Tente de recup�rer la liste des locales du syst�me dans un tableau
function list_system_locales()
{
	ob_start();
	if(system('locale -a')) {
		$str = ob_get_contents();
		ob_end_clean();
		return split("\n", trim($str));
	}else{
		return FALSE;
	}
}

/**
 * R�cup�re les champs g�n�riques d�finis pour une entit� *
 * Stocke les champs g�n�riques d�finis pour une entit� dans un sous tableau de $context : generic
 *
 * @param array $context le contexte pass�  par r�f�rence
 */
function getgenericfields(&$context)
{
	global $db;
	#print_r($context);
	$sql = lq("SELECT name,g_name, defaultvalue FROM #_TP_tablefields WHERE class='". $context['class'])."' AND g_name!=''";
	$row = $db->getArray($sql);
	#print_r($row);
	foreach ($row as $elem) {
		$fields[] = $elem['name'];
		$generic[$elem['name']] = $elem['g_name'];
	}
	//Retrouve les valeurs de $fields
	$sql = lq("SELECT ".join(',', $fields). ' FROM #_TP_'.$context['class']. " WHERE identity='".$context['id']."'");
	#echo "sql=$sql";
	$row = $db->getRow($sql);
	foreach ($row as $key => $value) {
		$values[$key] = $value;
	}
	//Contruit le tableau des champs g�n�riques avec leur valeur
	foreach($generic as $name => $g_name) {
		$g_name = str_replace('.','_',$g_name);
		$context['generic'][$g_name] = $values[$name];
	}
	unset($fields);
	unset($values);
	unset($generic);
	#print_r($context['generic']);exit;

	// -- Traitement des indexs -- 
	//R�cup�re maintenant les valeurs des champs g�n�riques des entr�es d'index associ�s et des personnes associ�es
	$sql = lq("SELECT e.type,e.g_type, e.class FROM #_TP_entrytypes as e, #_TP_tablefields as t WHERE t.class='".$context['class']."' AND t.name = e.type AND e.g_type!=''");
	#echo "sql=$sql";exit;
	$row = $db->getArray($sql);
	foreach ($row as $elem) {
		$fields[] = $elem['type'];
		$generic[$elem['type']] = $elem['g_type'];
	}
	//Retrouve les valeurs des entr�es en utilisant le g_name de la table entries
	if(count($fields) > 0) {
		$sql = lq("SELECT e.g_name, et.type FROM #_TP_entries as e, #_TP_relations as r, #_TP_entrytypes as et WHERE et.id=e.idtype AND e.id=r.id2 AND r.id1='".$context['id']."' AND et.type IN('".join("','",$fields)."')");
		#echo "sql=$sql";
		$array = $db->getArray($sql);
		foreach($array as $row) {
			if($cle = $generic[$row['type']]) {
				$cle = str_replace('.','_',$cle);
				$context['generic'][$cle][] = $row['g_name'];
			}
		}
	}
	
	// -- Traitement des personnes --
	unset($fields);
	unset($generic);
	//R�cup�re maintenant les valeurs des champs g�n�riques des entr�es d'index associ�es et des personnes associ�es
	$sql = lq("SELECT e.type,e.g_type, e.class FROM #_TP_persontypes as e, #_TP_tablefields as t WHERE t.class='".$context['class']."' AND t.name = e.type AND e.g_type!=''");
	#echo "sql=$sql";
	$row = $db->getArray($sql);
	foreach ($row as $elem) {
		$fields[] = $elem['type'];
		$generic[$elem['type']] = $elem['g_type'];
	}
	if(count($fields) > 0) {
		//Retrouve les valeurs des entr�es en utilisant le g_name de la table entries
		$sql = lq("SELECT e.g_firstname, e.g_familyname, et.type FROM #_TP_persons as e, #_TP_relations as r, #_TP_persontypes as et WHERE et.id=e.idtype AND e.id=r.id2 AND r.id1='".$context['id']."' AND et.type IN('".join("','",$fields)."')");
		#echo "sql=$sql";
		$array = $db->getArray($sql);
		foreach($array as $row) {
			if($cle = $generic[$row['type']]) {
				$cle = str_replace('.','_',$cle);
				$context['generic'][$cle][] = $row['g_firstname']. ' '. $row['g_familyname'];
			}
		}
	}


	return $context; // pas n�cessaire le context est pass� par r�f�rence
}


/**
 * Analyse une url et retourne le chemin en local qu'elle contient �ventuellement
 * Cf. parse_url : �l�ment 'path' du tableau retourn�
 *
 * @param string $url 
 * @return le chemin contenu dans l'URL
 */
function url_path($url)
{
	$url_parts = parse_url($url);
	return $url_parts['path'];
}

function rewriteFilename($string) {
     if(isUTF8($string)) {
	$string = preg_replace('/[^\w.-\/]+/', '_', makeSortKey($string));
     } else {
	$string = strip_tags($string);
     	$string = strtolower(htmlentities($string));
     	$string = preg_replace("/&(.)(uml);/", "$1e", $string);
     	$string = preg_replace("/&(.)(acute|cedil|circ|ring|tilde|uml);/", "$1", $string);
     	$string = preg_replace("([^\w.-]+)/", "_", html_entity_decode($string));
     	$string = trim($string, "-");
     	
     }
     return $string;
}

/**
 * Fonction permettant d'envoyer correctement un mail en html (utf8)
 *
 * @author Pierre-Alain Mignot
 * @param string $to destinataire
 * @param string $body corps du message
 * @param string $subject sujet du mail
 * @param string $fromaddress adresse de l'exp�diteur
 * @param string $fromname nom de l'expediteur
 * @return boolean
 */
function send_mail($to, $body, $subject, $fromaddress, $fromname)
{
	require_once 'Mail/Mail.php';
	require_once 'Mail/mime.php';
	$message = new Mail_mime();
	$message->setHTMLBody($body);
	$aParam = array(
		"text_charset" => "UTF-8",
		"html_charset" => "UTF-8",
		"head_charset" => "UTF-8"
	);
	$body = $message->get($aParam);
	if(mb_detect_encoding($subject, "auto", TRUE) != "UTF-8") {	
		$subject = mb_convert_encoding($subject, "UTF-8");
	}
	$extraheaders = array("From"=>$fromname."<".$fromaddress.">", "Subject"=>$subject);
	$headers = $message->headers($extraheaders);
	$mail = Mail::factory('mail');
	return $mail->send($to, $headers, $body);
}

// valeur de retour identifier ce script
return 568;
?>