<?php
die("script desuet");
// ne pas changer les valeurs numeriques

$listestatus=array(
		   -32 => "Brouillon",
		   -1  => "Non publi�",
		   1   => "Publi�",
		   32  => "Publi� (prot�g�)");


function makeselectstatus(&$context) {
  global $listestatus;
  foreach ($listestatus as $statut =>$statusstr) {
    $selected=$statut==$context[statut] ? "SELECTED" : "";
    echo "<OPTION $selected VALUE=\"$statut\">$statusstr</OPTION>";
  }
}



?>
