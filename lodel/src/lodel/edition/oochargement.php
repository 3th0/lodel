<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.*/




require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_REDACTOR);
include ($home."func.php");
require_once("utf8.php"); // conversion des caracteres

if ($_POST) {
  $therequest=&$_POST;
} else {
  $therequest=&$_GET;
}
$context['idparent']=intval($therequest['idparent']);
$context['identity']=$therequest['identity'] ? intval($therequest['identity']) : intval($therequest['iddocument']);
$context['idtask']=$idtask=intval($therequest['idtask']);
$context['idtype']=intval($therequest['idtype']);
$context['lodeltags']=intval($therequest['lodeltags']);

if (!$context['idtask'] && !$context['identity'] && !$context['idtype']) {
  header("location: index.php?id=".$context['idparent']);
  return;
}

if ($_POST['fileorigin']=="upload" && $_FILES['file1'] && $_FILES['file1']['tmp_name'] && $_FILES['file1']['tmp_name']!="none") {
  $file1=$_FILES['file1']['tmp_name'];
  if (!is_uploaded_file($file1)) die(utf8_encode("Le fichier n'est pas un fichier charg�"));
  $sourceoriginale=$_FILES['file1']['name'];
  $tmpdir=tmpdir(); // use here and later.
  $source=$tmpdir."/".basename($file1)."-source";
  move_uploaded_file($file1,$source); // move first because some provider does not allow operation in the upload dir
} elseif ($_POST['fileorigin']=="server" && $_POST['localfile']) {
  $sourceoriginale=basename($_POST['localfile']);
  $file1=SITEROOT."CACHE/upload/".$sourceoriginale;
  $tmpdir=tmpdir(); // use here and later.
  $source=$tmpdir."/".basename($file1)."-source";
  copy($file1,$source);
} else {
  $file1="";
  $sourceoriginale="";
  $source="";
}

if ($file1) {
  do {
    // verifie que la variable file1 n'a pas ete hackee
    $t=time();
    @chmod($source,0666 & octdec($GLOBALS['filemask'])); 

    require_once("servoofunc.php");

    $client=new ServOO;
    if ($client->error_message) {
      $context['error']="Aucun ServOO n'est configur&eacute; pour r&eacute;aliser la conversion. Vous pouvez faire la configuration dans les options du site (Administrer/Options)";
      break;
    }

    // get the extension...it's indicative only !
    preg_match("/\.(\w+)$/",$sourceoriginale,$result);
    $ext=$result[1];

    $options=array(
		   #"predefinedblocktranslations"=>"fr",
		   #"predefinedinlinetranslations"=>"fr",
		   "transparentclass"=>"stylestransparents",
		   "block"=>true,
		   "inline"=>true,
		   #"heading"=>true
		   );
    $outformat=$sortiexhtml ? "W2L-XHTML" : "W2L-XHTMLLodel";
    $xhtml=$client->convertToXHTML($source,$ext,$outformat,
				   $tmpdir,"",
				   $options,
				   array("allowextensions"=>"xhtml|jpg|png|gif"),
				   "imagesnaming", // callback
				   SITEROOT."docannexe/tmp".rand()); // base name for the images

    if ($xhtml===false) {

      if (strpos($client->error_message,"Not well-formed XML")!==FALSE) {
	$arr=preg_split("/\n/",$client->error_message);
	$l=-3;
	foreach ($arr as $t) {
	  echo $l++," ",$t,"\n";
	}
	return;
      }

      $context['error']=utf8_encode("Erreur renvoy�e par le ServOO: \"".$client->error_message."\"");
      break;
    }
    if ($sortieoo || $sortiexhtml) die(htmlentities($xhtml));

    $err=lodelprocessing($xhtml);

    if ($err) {
      $context['error']="error in the lodelprocessing function";
      break;
    }

    if ($sortiexmloo || $sortie) die(htmlentities($xhtml));

    require_once("balises.php");

    $fileconverted=$source.".converted";
    if (!writefile($fileconverted,$xhtml)) {
      $context['error']="unable to write converted file";
      break;
    }

    if ($idtask) { // reimportation of an existing document ?
      $row=get_task($idtask);
    } else {
      $row=array();
    }
      
    $row['fichier']=$fileconverted;
    $row['source']=$source;
    $row['sourceoriginale']=$sourceoriginale;
    // build the import
    $row['importversion']=addslashes($convertretvar['version'])."; oochargement $version;";

    if (!$idtask) {
      if ($context['identity']) {
	$row['identity']=$context['identity'];
      } else {
	$row['idparent']=$context['idparent'];
      }
      $row['idtype']=$context['idtype'];
    }
    $idtask=makeTask("Import $file1_name",3,$row,$idtask);

    if ($msg) {
      echo '<br><a href="checkimport.php?id='.$idtask.'"><font size="+1">Continuer</font></a>';
      return;
    }

    header("Location: checkimport.php?idtask=$idtask");
    return;
  } while (0); // exceptions
}

$context['url']="oochargement.php";


require("view.php");
$view=&getView();
$view->render($context,"oochargement",!(bool)$_POST);



function imagesnaming($filename,$index,$uservars)

{
  preg_match("/\.\w+$/",$filename,$result); // get extension
  return $uservars."_".$index.$result[0];
}


function lodelprocessing(&$xhtml)

{
/*
  $arr=preg_split("/(<\/?)soo:(\w+)\b([^>]*>)/",$xhtml,-1,PREG_SPLIT_DELIM_CAPTURE);
  $xhmtl=""; // save memory (not really in fact)
  $count=count($arr);
  $stack=array();

  for($i=1; $i<$count; $i+=4) {
    if ($arr[$i]=="</") { // closing tags
      $arr[$i]=array_pop($stack);
      $arr[$i+1]=$arr[$i+2]="";
    } else { // opening tags
      // document tag
      if ($arr[$i+1]=="document") { 
	$arr[$i+1]='r2r:document';
	$arr[$i+2]=' xmlns:r2r="http://www.lodel.org/xmlns/r2r" xmlns="http://www.w3.org/1999/xhtml">';
	array_push($stack,"</r2r:document>");
      } else {
	// others tags
	$tag=$arr[$i+1];
	$class="";
	$ns=$tag=="inline" ? "r2rc" : "r2r";
	if ($tag=="heading" && preg_match("/level=\"(\d+)\"/",$arr[$i+2],$result)) {
	  $class="section".$result[1];
	} elseif (preg_match("/class=\"([^\"]+)\"/",$arr[$i+2],$result)) {
	  $class=removeaccentsandspaces($result[1]);
	  $class=preg_replace("/\W/","_",$class);
	}
	if ($class) {
	  $arr[$i]="<$ns:$class>";
	  $arr[$i+1]=$arr[$i+2]="";
	  array_push($stack,"</$ns:$class>");
	}
      }
    }
  }
  $xhtml=join("",$arr);
*/
  $xhtml=str_replace(array("&#39;","&apos;"),array("'","'"),$xhtml);

  return false;
}




function cleanList($text)

{
  $arr=preg_split("/(<\/?(?:ul|ol)\b[^>]*>)/",$text,-1,PREG_SPLIT_DELIM_CAPTURE);
  $count=count($arr);
  $arr[0]=addList($arr[0]);
  $inlist=0; $start=0;
  for($i=1; $i<$count; $i+=2) {
    if ($arr[$i][1]=="/") { // closing
      $inlist--;
      if ($inlist==0) { $arr[$i].="</r2r:puces>"; } // end of a list
    } else { // opening
      if ($inlist==0) { $arr[$i]="<r2r:puces>".$arr[$i]; } // beginning of a list
      $inlist++;
    }
    if ($inlist>0) { // in a list
      //      $arr[$i+1]=preg_replace("/<\/?(?:p|div|r2r:puces?)\b[^>]*>/"," ",$arr[$i+1]);
      //$arr[$i+1]=preg_replace("/<\/?r2r:puces?\b[^>]*>/"," ",$arr[$i+1]);
      $arr[$i+1]=preg_replace("/<\/?r2r:[^>]+>/"," ",$arr[$i+1]);
    } else { // out of any list
      $arr[$i+1]=addList($arr[$i+1]);
    }
  }
  $text=join("",$arr);

  return preg_replace("/<\/r2r:(puces?)>((?:<\/?(p|br)(?:\s[^>]*)?\/?>|\s)*)<r2r:\\1(?:\s[^>]*)?>/s", // process couple 
		      "",$text);
}

function addList($text)

{ // especially for RTF file where there are some puces but no li
  return preg_replace(array(
			   "/<r2r:(puces?)>(.*?)<\/r2r:\\1>/", // put li
			   "/<\/r2r:(puces?)>((?:<\/?(p|br)(?:\s[^>]*)?\/?>|\s)*)<r2r:\\1(?:\s[^>]*)?>/s", // process couple 
			   "/(<r2r:puces?>)/",  // add ul
			   "/(<\/r2r:puces?>)/" // add /ul
			   ),
		     array("<r2r:\\1><li>\\2</li></r2r:\\1>",
			   "",
			   "\\1<ul>",
			   "</ul>\\1"
			   ),$text);

}


?>
