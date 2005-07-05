<?php

/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�ou
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

/**
 * Calcul the XML file for an entity
 * @return the indented XML
 */
function calculateXML($context) {
	require_once ("calcul-page.php");
	ob_start();
	calcul_page($context, "xml-classe", "", SITEROOT."lodel/edition/tpl/");
	$contents = ob_get_contents();
	ob_end_clean();
	return indentXML($contents);
}
/**
 * Calcul the XSD scheme for a class of entity
 * @return the indented XSD
 */
function calculateXMLSchema($context) {
	require_once ("calcul-page.php");
	ob_start();
	calcul_page($context, "schema-xsd", "", SITEROOT."lodel/admin/tpl/");
	$contents = ob_get_contents();
	ob_end_clean();
	return indentXML($contents);
}

/**
 * Indent an XML content
 * 
 */
function indentXML($contents, $output = false) {
	$arr = preg_split("/\s*(<(\/?)(?:\w+:)?\w+(?:\s[^>]*)?>)\s*/", $contents, -1, PREG_SPLIT_DELIM_CAPTURE);
	$ret = '<?xml version="1.0" encoding="utf-8" ?>
	';
	if ($output)
		echo $ret;
	$tab = "";
	for ($i = 1; $i < count($arr); $i += 3) {
		if ($arr[$i +1])
			$tab = substr($tab, 2); // closing tag
		if (substr($arr[$i], -2) == "/>") { // opening closing tag
			$out = $tab.$arr[$i].$arr[$i +2]."\n";
		} else
			if (!$arr[$i +1] && $arr[$i +4]) { // opening follow by a closing tags
				$out = $tab.$arr[$i].$arr[$i +2].$arr[$i +3].$arr[$i +5]."\n";
				$i += 3;
			} else {
				$out = $tab.$arr[$i]."\n";
				if (!$arr[$i +1])
					$tab .= "  ";
				if (trim($arr[$i +2])) {
					$out .= $tab.$arr[$i +2]."\n";
				}
			}
		if ($output) {
			echo $out;
		} else {
			$ret .= $out;
		};
	}
	if (!$output)
		return $ret;
}

/**
 * Decode Balise field
 */
function loop_xsdtypes(& $context, $funcname) {
	$balises = preg_split("/;/", $context['allowedtags'], -1, PREG_SPLIT_NO_EMPTY);
	if ($balises)
		call_user_func("code_before_$funcname", $context);
	foreach ($balises as $name) {
		if (is_numeric($name))
			continue;
		$localcontext = $context;
		$localcontext['count'] = $count;
		$count ++;
		$localcontext['name'] = preg_replace("/\s/", "_", $name);
		call_user_func("code_do_$funcname", $localcontext);
	}
	if ($balises)
		call_user_func("code_after_$funcname", $context);
}

/**
 * Loop that select each field with its value for an entity
 */
function loop_fields_values(& $context, $funcname) {
  global $error;
  global $db;
  $result = $db->execute(lq("SELECT name,type FROM #_TP_tablefields WHERE idgroup='$context[id]' AND status>0 ORDER BY rank")) or dberror();
  $haveresult = $result->NumRows() > 0;
  if ($haveresult && function_exists("code_before_$funcname"))
    call_user_func("code_before_$funcname", $context);
  
  while (!$result->EOF) {
    $row = $result->fields;
    if ($row['type'] != 'persons' && $row['type'] != 'entries' && $row['type'] != 'entities')
      $fieldvalued[] = $row['name'];
    $fields[] = $row;
    $result->moveNext();
  }
#print_r($fields);
  if (is_array($fieldvalued) && count($fieldvalued) > 0) {
    $sql = lq("SELECT ".implode(',', $fieldvalued)." FROM #_TP_".$context['class']." WHERE identity='".$context['identity']."'");
  #echo "sql=$sql<br />";
  $rowsvalued = $db->getRow($sql);
  }

  foreach ($fields as $row) {
    $localcontext = array();
    $localcontext['name'] = $row['name'];
    $localcontext['type'] = $row['type'];
    $localcontext['identity'] = $context['identity'];
    if ($rowsvalued[$row['name']])
      $localcontext['value'] = $rowsvalued[$row['name']];
    //else
      //unset($localcontext['value']);
    call_user_func("code_do_$funcname", $localcontext);
  }

  if ($haveresult && function_exists("code_after_$funcname"))
    call_user_func("code_after_$funcname", $context);
}

function loop_entry_or_persons_fields_values(& $context, $funcname) {
  global $error;
  global $db;

  if ($context['nature'] == 'G') {
    $table = '#_TP_persontypes';
    $id = 'idperson';
  }
  elseif ($context['nature'] == 'E') {
    $table = '#_TP_entrytypes';
    $id = 'identry';
  }
  $sql = "SELECT t.name, t.class, t.type,t.condition FROM #_TP_tablefields as t, $table as et";
  $sql .= " WHERE et.type='".$context['name']."' AND et.class=t.class";
  $result = $db->execute(lq($sql));
  $haveresult = $result->NumRows() > 0;
  if ($haveresult && function_exists("code_before_$funcname"))
    call_user_func("code_before_$funcname", $context);

  while (!$result->EOF) {
    $row = $result->fields;
    if (!$class)
      $class = $row['class'];
    $fields[$row['name']] = $row;
    $result->moveNext();
  }
  $fieldnames = array_keys($fields);
  if (is_array($fieldnames) && count($fieldnames) > 0) {
    $sql = lq("SELECT ".implode(',', $fieldnames)." FROM #_TP_".$class." WHERE $id='".$context['id2']."'");
    $values = $db->getRow($sql);
    foreach ($fields as $key => $row) {
      $localcontext = array();
      $localcontext['name'] = $row['name'];
      if ($values[$row['name']])
        $localcontext['value'] = $values[$row['name']];
      else
        $localcontext['value'] = '';
      call_user_func("code_do_$funcname", $localcontext);
    }
  }

if ($haveresult && function_exists("code_after_$funcname"))
  call_user_func("code_after_$funcname", $context);

}
/**
 * Loop that select each field of a relation between an entity and a person for an entity
 */
function loop_person_relations_fields(& $context, $funcname) {
  global $error;
  global $db;
  $sql = "SELECT t.name, t.class, t.type,t.condition FROM #_TP_tablefields as t";
  $sql .= " WHERE t.class='entities_".$context['class']."'";
  $result = $db->execute(lq($sql));
  $haveresult = $result->NumRows() > 0;
  if ($haveresult && function_exists("code_before_$funcname"))
    call_user_func("code_before_$funcname", $context);

  while (!$result->EOF) {
    $row = $result->fields;
    if (!$class)
      $class = $row['class'];
    $fields[$row['name']] = $row;
    $result->moveNext();
  }
  $fieldnames = array_keys($fields);
  if (is_array($fieldnames) && count($fieldnames) > 0) {
    $sql = lq("SELECT ".implode(',', $fieldnames)." FROM #_TP_".$row['class']." WHERE idrelation='".$context['idrelation']."'");
    $values = $db->getRow($sql);
    foreach ($fields as $key => $row) {
      $localcontext = array();
      $localcontext['name'] = $row['name'];
      if ($values[$row['name']])
        $localcontext['value'] = $values[$row['name']];
     call_user_func("code_do_$funcname", $localcontext);
    }
  }
  if ($haveresult && function_exists("code_after_$funcname"))
    call_user_func("code_after_$funcname", $context);

}


/**
 * Put the XHTML namespace in each tag with no namespace and delete r2r namespace
 * Met le namespace xhtml pour toutes balises qui n'ont pas de namespace et supprime le namespace r2r.
 */
function namespace($text) {
  $ns = "xhtml";
  // put namespace on each html tag
  $text = preg_replace(array ("/<(\/?)(\w+(\s+[^>]*)?>)/", // add xhtml
    "/(<\/?)r2r:/"), // remove r2r
    array ("<\\1$ns:\\2", "\\1"), $text);
  // then put namespace on each attribute
  return $text;
}
?>