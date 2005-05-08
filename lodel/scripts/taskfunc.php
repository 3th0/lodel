<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 *  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
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


//
// $context est soit un tableau qui sera serialise soit une chaine deja serialise
//

function maketask($name,$etape,$context,$id=0)

{
  global $lodeluser,$db;
  if (is_array($context)) $context=serialize($context);
  $db->execute(lq("REPLACE INTO #_TP_tasks (id,name,step,user,context) VALUES ('$id','$name','$etape','".$lodeluser['id']."','$context')")) or dberror();
  return $db->insert_ID();
}

function updatetask_step($id,$step)

{
  global $db;
  $db->exxecute(lq("UPDATE #_TP_tasks SET step='$step' WHERE id='$id'")) or dberror();
}

//
// previouscontext est la chaine serialisee
// newcontext est un array

function updatetask_context($id,$newcontext,$previouscontext="")

{
  global $db;
  if ($previouscontext) { // on merge les deux contextes
    $contextstr=serialize(array_merge(unserialize($previouscontext),$newcontext));
  } else {
    $contextstr=serialize($newcontext);
  }

  $db->execute(lq("UPDATE #_TP_tasks SET context='$contextstr' WHERE id='$id'")) or dberror();

}


function gettask (&$id)

{
  global $db;

  $id=intval($id);
  $row=$db->getRow(lq("SELECT * FROM #_TP_tasks WHERE id='$id' AND status>0"));
  if ($row===false) dberror();
  if (!$row) { require_once("view.php"); $view=&getView(); $view->back(); return; }
  $row=array_merge($row,unserialize($row['context']));
  return $row;
}


/*
 * Depending the document is imported or re-imported, the information in task are different.
 * This function uniformize the information
 */


function gettypeandclassfromtask($task,&$context)

{
  global $db;

  if ($task['identity']) {
    $row=$db->getRow(lq("SELECT class,idtype FROM #_entitiestypesjoin_ WHERE #_TP_entities.id='".$task['identity']."'"));        
    if ($db->errorno()) dberror();
    $context['class']=$row['class'];
    $context['idtype']=$row['idtype'];
    
    if (!$context['class']) die("ERROR: can't find entity ".$task['identity']." in gettypeandclassfromtask");
    
  } else {
    $idtype=$task['idtype'];
    if (!$idtype) die("ERROR: idtype must be given by task in importAction");
    // get the type 
    $dao=&getDAO("types");
    $votype=$dao->getById($idtype,"class");
    $context['class']=$votype->class;
  }
}

?>