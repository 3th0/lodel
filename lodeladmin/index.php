<?php
require("lodelconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMINLODEL);

if (file_exists("CACHE/unlockedinstall")) die("L'installation de LODEL n'est pas termin�. Veuillez la terminer ou �ffacer le fichier lodel/admin/CACHE/unlockedinstall.<br><a href=\"install.php\">install.php");



include ($home."calcul-page.php");
calcul_page($context,"index");

?>
