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

// change le statut d'un document ou d'une publication


require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

include_once ($home."connect.php");

if ($statut=="protege") {
  $newstatut=+32;
} elseif ($statut=="deprotege") {
  $newstatut=+1;
} elseif ($statut=="brouillon") {
  $newstatut=-32;
} elseif ($statut=="pret") {
  $newstatut=-1;
} else {
  die ("statut invalide");
}

// ce script permet de changer le statut, mais pas lui faire changer de signe.
// pour publie ou depublie (changer le signe de statut) il faut utiliser publi.php

// le statut ne doit pas changer de signe...
$critere=$newstatut>0 ? "statut>0" : "statut<0";

if ($publication) {
  $id=intval($publication);
} elseif ($id) {
  $id=intval($id);
} else {
  die("specifier une publication");
}
 
mysql_query("UPDATE $GLOBALS[tp]entites SET statut=$newstatut WHERE id='$id' AND $critere") or die(mysql_error());


back();
return;

?>
