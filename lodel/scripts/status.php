<?

// ne pas changer les valeurs numeriques

$listestatus=array(
		   -32 => "Brouillon",
		   -1  => "Non publi�",
		   1   => "Publi�",
		   32  => "Publi� (prot�g�)");


function makeselectstatus(&$context) {
  global $listestatus;
  foreach ($listestatus as $status =>$statusstr) {
    $selected=$status==$context[status] ? "SELECTED" : "";
    echo "<OPTION $selected VALUE=\"$status\">$statusstr</OPTION>";
  }
}



?>
