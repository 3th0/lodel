<?

// assure le traitement du fichier lors de l'arrive dans extrainfo
function ei_pretraitement($filename,$row,&$context,&$text)

{
  global $langresume,$home;

  $text=join("",file ($filename.".html"));
  auteurs2auteur($text);
  if (!$context[option_pasdeperiode]) tags2tag("periode",$text);
  if (!$context[option_pasdemotcle]) tags2tag("motcle",$text);
  if (!$context[option_pasdegeographie]) tags2tag("geographie",$text);

  // extrait les balises et met les dans le context
  $lbalises=array("titre","soustitre","surtitre","typedoc");

  foreach ($lbalises as $b) {
    if (preg_match ("/<r2r:$b>\s*(.*?)\s*<\/r2r:$b>/si",$text,$result)) {
      $context[$b]=strip_tags($result[1],"<I><B><U>");
      $text=str_replace($result[0],"",$text);
    }
  }
  // extrait les langues
  if (preg_match("/<r2r:texte\b[^>]+\blang\s*=\s*\"([^\"]+)\"/i",$text,$result)) {
    list($context[lang1],$context[lang2],$context[lang3])=explode(" ",$result[1]);
  }
  // transforme les balises resume
  include_once("$home/langues.php");
  $srch=array(); $rpl=array();
  foreach ($langresume as $bal=>$lang) {
    array_push($srch,"/<r2r:$bal>/i","/<\/r2r:$bal>/i");
    array_push($rpl,"<r2r:resume lang=\"$lang\">","</r2r:resume>");
  }
  $text='<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>
<!DOCTYPE article SYSTEM "r2r-xhtml-1.dtd">
'.preg_replace($srch,$rpl,$text);
      
  if (!writefile ($filename.".balise",$text)) die ("erreur d'ecriture du fichier $filename.balise");
  if ($row[iddocument]) { # le document existe
# on recupere la date de publication du texte
    $result=mysql_query("SELECT datepubli from documents WHERE id='$row[iddocument]'") or die (mysql_error());
    list($context[datepubli])=mysql_fetch_row($result);
  }
}


//
// verifie les entrees et enregistre dans la base sauf s'il y a erreur
//  ou si on souhaite ajouter un auteur
//

function ei_edition($filename,$row,&$context,&$text,&$motcles,&$periodes,&$geographies)

{
  global $home;

  $balisefilename=$filename.".balise";
  if (file_exists($balisefilename) && filemtime($balisefilename)>filemtime($filename.".html")) {
    $text=join("",file($balisefilename));
  } else {
    $text=join("",file($filename.".html"));
  }

  extract_post();
  // suppression des slashes
  foreach($context as $key=>$val) {
    $context[$key]=stripslashes($val);
  }

  // verifie que le titre est present
  if (!$context[titre]) $err=$context[erreur_titre]=1;
  if ($context[datepubli]) {
    include ("$home/date.php");
    $row[datepubli]=mysqldate($context[datepubli]);
    if (!$row[datepubli]) { $context[erreur_datepubli]=$err=1; }
    // fin de la validation
  }
  // efface les groupes
  $text=preg_replace ("/<r2r:(grmotcle|grperiode|grgeographie|grtitre|meta|grauteur)\b[^>]*>(.*?)<\/r2r:\\1>/si", // efface les champs auteurs et auteur
		      "",$text);

  // ajoute les groupes a la fin
  $text=preg_replace("/<\/r2r:article>/i",
		     gr_auteur($context,$context[plusauteurs]).
		     gr_motcle($context,$motcles).
		     gr_indexh($context,$periodes,"periode").
		     gr_indexh($context,$geographies,"geographie").
		     gr_titre($context).
		     gr_meta($context)."\\0",
		     $text);
  // change la langue du texte
  $lang=$context[lang1];
  if ($context[lang2]) $lang.=" ".$context[lang2];
  if ($context[lang3]) $lang.=" ".$context[lang3];

  $text=preg_replace(array("/(<r2r:texte\b[^>]+)\blang\s*=\s*\"[^\"]*\"/i","/(<r2r:texte\b[^>]*?)\s*>/i"),
		     array("\\1",$lang ? "\\1 lang=\"$lang\">" : "\\1>"),$text);

  if ($err || $context[plusauteurs]) {
    writefile ($balisefilename,$text);
    return 0;
  }
  //
  // enregistre
  //
  if ($row[iddocument]) { # efface d'abord
    include_once("$home/managedb.php");
    // recupere les metas et le status
    $result=mysql_query("SELECT meta,status from documents WHERE id='$row[iddocument]'") or die (mysql_error());
    list($row[meta],$status)=mysql_fetch_row($result);
    if (!$row[status]) $status=$row[status]; // recupere le status si necessaire
    supprime_document($row[iddocument]);
  } else { # Il n'existe pas, alors on calcule la date
    $context[duree]=intval($context[duree]);
    $time=localtime();
    if ($context[dateselect]=="jours") $time[3]+=$context[duree];
    if ($context[dateselect]=="mois") $time[4]+=$context[duree];
    if ($context[dateselect]=="ann�e") $time[5]+=$context[duree];
    $row[datepubli]=date("Y-m-d",mktime(0,0,0,$time[4]+1,$time[3],$time[5]));
  }
  // enregistre dans la base
  include_once ("$home/dbxml.php");
  $iddocument=enregistre($row,$text);

  // change le nom des images
  if (!function_exists("img_rename")) {
    function img_rename($imgfile,$ext,$count) {
      global $iddocument;
      
      $newimgfile="docannexe/r2r-img-$iddocument-$count.$ext";
      if ($imgfile!=$newimgfile) {
	rename ($imgfile,"../../$newimgfile") or die ("impossible de renomer l'image $imgfile en $newimgfile");
	chmod ("../../$newimgfile",0644) or die ("impossible de chmod'er le ../../$newimagefile");
      }
      return $newimgfile;
    }
  }
  copy_images($text,"img_rename");

  // copie le fichier balise en lieu sur !
  if (!writefile("../txt/r2r-$iddocument.xml",$text)) die ("Erreur lors de l' ecriture du fichier. Contactez l'administrateur: ../txt/r2r-$iddocument.xml");
  // et le rtf s'il existe
  $rtfname="$filename.rtf";
  if (file_exists($rtfname)) { 
    $dest="../rtf/r2r-$iddocument.rtf";
    copy ($rtfname,$dest);
    chmod($dest,0644) or die ("impossible de chmod'er $dest");
  }
  // efface le fichier balise
  if (file_exists($balisefilename)) unlink($balisefilename);

  return $iddocument; // ok on a finit correctement
}


  ///////////////////////////////////////////////////////////
//
// fonctions de traitement
// specifiques a extrainfo
//


function gr_auteur(&$context,$plusauteurs)

{
    $i=1;
    $rpl="<r2r:grauteur>";
    while ($context["nomfamille$i"] || $context["prenom$i"] || $context["prefix$i"] 
	   // supprimer le 6/4/3 || $context["affiliation$i"] || $context["courriel$i"]
	   ) {
      $rpl.="<r2r:auteur ordre=\"$i\">";
      // nompersonne
      $rpl.="<r2r:nompersonne>\n".
	writetag("prefix",$context["prefix$i"]).
	writetag("nomfamille",$context["nomfamille$i"]).
	writetag("prenom",$context["prenom$i"]).
	"</r2r:nompersonne>\n";

//      // affiliation
//      $rpl.=writetag("affiliation",$context["affiliation$i"]);
//      // courriel
//      $rpl.=writetag("courriel",$context["courriel$i"]);

      $rpl.=writetag("description",$context["description$i"]);

      $rpl.="</r2r:auteur>\n";
      $i++;
    }
    if ($plusauteurs) $rpl.="<r2r:auteur></r2r:auteur>";

    $rpl.="</r2r:grauteur>";

    return $rpl;
}

function gr_motcle(&$context,&$motcles)

{
  // traite les motcles
  if (!$context[option_motclefige]) $motcles=array_merge($motcles,preg_split ("/\s*[,;]\s*/",$context[autresmotcles]));
  $rpl="<r2r:grmotcle>";
  if ($motcles) {
    foreach ($motcles as $p) {
      $rpl.=writetag("motcle",strip_tags(rmscript(trim($p))));
    }
  }
  $rpl.="</r2r:grmotcle>";
  return $rpl;
}


//function gr_periode(&$periodes)
//
//{
//    // traite les periodes
//    $rpl="<r2r:grperiode>";
//
//    if ($periodes) {
//	foreach ($periodes as $p) {
//	  $rpl.=writetag("periode",strip_tags(rmscript(trim($p))));
//	}
//    }
//    $rpl.="</r2r:grperiode>\n";
//    return $rpl;
//}

function gr_indexh(&$context,&$indexhs,$bal)

{
  $bal=strtolower($bal);
  // traite les indexhs
  $rpl="<r2r:gr$bal>";

  if ($indexhs) {
    foreach ($indexhs as $p) {
      $rpl.=writetag($bal,strip_tags(rmscript(trim($p))));
    }
  }
  $rpl.="</r2r:gr$bal>\n";
  return $rpl;
}


function gr_titre(&$context)

{
    return "<r2r:grtitre>".
      writetag("titre",strip_tags(rmscript(trim($context[titre])),"<I>")).
      writetag("soustitre",strip_tags(rmscript(trim($context[soustitre])),"<I>")).
      "</r2r:grtitre>\n";
}

function gr_meta(&$context)

{
  return "<r2r:meta><r2r:infoarticle>".
    writetag("typedoc",strip_tags(rmscript(trim($context[typedoc])))).
    "</r2r:infoarticle></r2r:meta>\n";
}

function writetag($name,$content,$attr="")

{
  if (!$content) return "";
  if ($attr) return "<r2r:$name $attr>$content</r2r:$name>\n";
  return "<r2r:$name>$content</r2r:$name>\n";
}

###################### functions ##################

function makeselecttypedoc()

{
  global $context;

  $result=mysql_query("SELECT nom FROM typedocs WHERE status>0") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=$context[typedoc]==$row[nom] ? " selected" : "";
    echo "<option value=\"$row[nom]\"$selected>$row[nom]</option>\n";
  }
}

function makeselectperiodes()
{ makeselectindexhs("periode",TYPE_PERIODE); }
function makeselectgeographies()
{ makeselectindexhs("geographie",TYPE_GEOGRAPHIE); }


function makeselectindexhs ($bal,$type)
{
  global $context,$text;
  # extrait les periodes du texte
  preg_match_all("/<r2r:$bal\b[^>]*>(.*?)<\/r2r:$bal>/is",$text,$indexhs,PREG_PATTERN_ORDER);
#  print_r($indexhs[1]);

  makeselectindexhs_rec(0,"",$indexhs,$type);
}

function makeselectindexhs_rec($parent,$rep,$indexhs,$type)

{
  $result=mysql_query("SELECT id, abrev, nom FROM indexhs WHERE status>=-1 AND parent='$parent' AND type='$type' ORDER BY ordre") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=in_array($row[abrev],$indexhs[1]) ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[abrev]\"$selected>$rep$row[nom]</OPTION>\n";
    makeselectindexhs_rec($row[id],$rep.$row[nom]."/",$indexhs,$type);
  }
}


function makeselectmotcles()

{
  global $context,$text;

  if (!$context[option_motclefige]) $critere="type=".TYPE_MOTCLE." OR";
  $result=mysql_query("SELECT mot FROM indexls WHERE status>=-1 AND ($critere type=".TYPE_MOTCLE_PERMANENT.") GROUP BY mot ORDER BY mot") or die (mysql_error());

  # extrait les motcles du texte
  preg_match_all("/<r2r:motcle\b[^>]*>(.*?)<\/r2r:motcle\s*>/is",$text,$motcles,PREG_PATTERN_ORDER);

  $motclestrouves=array();

  while ($row=mysql_fetch_assoc($result)) {
    $selected=in_array($row[mot],$motcles[1]) ? " selected" : "";
    if ($selected) array_push($motclestrouves,$row[mot]);
    echo "<option value=\"$row[mot]\"$selected>$row[mot]</option>\n";
  }
#  print_r($motcles[1]);
#  print_r($motclestrouves);
#  print_r(array_diff($motcles[1],$motclestrouves));
  if (!$context[option_motclefige]) $GLOBALS[context][autresmotcles]=join(", ",array_diff($motcles[1],$motclestrouves));
}



function makeselectdate() {
  global $context;

  foreach (array("maintenant",
		 "jours",
		 "mois",
		 "ann�es") as $date) {
    $selected=$context[dateselect]==$date ? "selected" : "";
    echo "<option value=\"$date\"$selected>$date</option>\n";
  }
}


function boucle_auteurs(&$context,$funcname)

{
  global $text;
#  $balises="(prefix|nomfamille|prenom|courriel|affiliation)";
  $balises="(prefix|nomfamille|prenom|description)";

  preg_match_all("/<r2r:auteur\b[^>]*>(.*?)<\/r2r:auteur\s*>/is",$text,$results,PREG_SET_ORDER);
  foreach ($results as $auteur) {
    preg_match_all("/<r2r:$balises\b[^>]*>(.*?)<\/r2r:\\1\s*>/is",$auteur[1],$result,PREG_SET_ORDER);

    $ind++;
    $localcontext=$context;
    $localcontext[ind]=$ind;
    if ($result) {
      foreach ($result as $champ) { 
#	$localcontext[strtolower($champ[1])]=htmlspecialchars(stripslashes(strip_tags($champ[2]))); }
	$localcontext[strtolower($champ[1])]=htmlspecialchars(stripslashes($champ[2])); }
    }
	call_user_func("code_boucle_$funcname",$localcontext);
  }
}


//////////////////////// function de transformation des balises 's ///////////

function auteurs2auteur (&$text)

{
  // traitements speciaux:

  // accouple les balises auteurs et descriptionauteur
  $text=preg_replace ("/(<\/r2r:auteurs>)[\s\n\r]*(<r2r:descriptionauteur>.*?<\/r2r:descriptionauteur>)/is","\\2\\1",$text);

  // cherche toutes les balises auteurs
  preg_match_all ("/<r2r:auteurs>\s*(.*?)\s*<\/r2r:auteurs>/si",$text,$results,PREG_SET_ORDER);

  $grauteur="<r2r:grauteur>";
  $i=1;

  while ($result=array_shift($results)) { // parcours les resultats.
    // cherche s'il y a un bloc description
    if (preg_match("/^(.*?)(<r2r:descriptionauteur>.*?<\/r2r:descriptionauteur>)/si",$result[1],$result2)) { // il y a un bloc description, donc on a une description pour le dernier auteur.
      $val=$result2[1];
      // remplace descriptionauteur en description.
      $descrauteur=preg_replace("/(<\/?r2r:description)auteur>/i","\\1>",$result2[2]);
    } else { // pas description des auteurs
      $val=$result[1];
      $descrauteur="";
    }
    echo htmlentities($descrauteur)."<br><br>\n\n";
    $auteurs=preg_split ("/\s*[,;]\s*/",strip_tags($val));

    while (($auteur=array_shift($auteurs))) {
      // ok, on cherche maintenant a separer le nom et le prenom
      $nom=$auteur;
      while ($nom && strtoupper($nom)!=$nom) { $nom=substr(strstr($nom," "),1);}
      if ($nom) {
	$prenom=str_replace($nom,"",$auteur);
      } else { // sinon coupe apres le premiere espace
	preg_match("/^\s*(.*)\s+([^\s]+)\s*$/i",$auteur,$result2);
	$prenom=$result2[1]; $nom=$result2[2];
      }
      // on a maintenant le nom et le prenom, on ecrit le bloc
      $grauteur.="<r2r:auteur ordre=\"$i\"><r2r:nompersonne><r2r:nomfamille>$nom</r2r:nomfamille><r2r:prenom>$prenom</r2r:prenom></r2r:nompersonne>";
      if ($descrauteur && !$auteurs)  $grauteur.=$descrauteur; // c'est le dernier auteur de cette liste, s'il y a un bloc description, alors c'est pour lui !
      $grauteur.="</r2r:auteur>";
      $i++;
    }
    $text=str_replace($result[0],"",$text); // efface ce bloc
  } // fin du traitement speciale des auteurs
  $grauteur.="</r2r:grauteur>\n";

  // ajoute ce bloc a la fin
  $bal="</r2r:article>";
  //   die("$grauteur");
  $text=str_replace($bal,$grauteur.$bal,$text);
}


function tags2tag ($bal,&$text)

{
  $bals=$bal."s";
  $bal=strtolower($bal);

  if (preg_match ("/<r2r:$bals>\s*(.*?)\s*<\/r2r:$bals>/si",$text,$result)) {
    $val=$result[1];
    $tags=preg_split ("/[,;]/",preg_replace(
					    array("/^\s*<(p|div)\b[^>]*>/si","/<\/(p|div)\b[^>]*>$/si","/\s+/"),
					    array("",""," "),$val));
    $val="<r2r:gr$bal>\n";
    foreach($tags as $tag) {
      # enlever le strip_tages
      $val.="<r2r:$bal>".trim(strip_tags($tag))."</r2r:$bal>";
    }
    $val.="</r2r:gr$bal>\n";
    $text=str_replace($result[0],$val,$text);
  }
}

?>
