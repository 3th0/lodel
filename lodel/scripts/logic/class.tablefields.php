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



/**
 *  Logic TableField
 */

class TableFieldsLogic extends Logic {

  /** Constructor
   */
   function TableFieldsLogic() {
     $this->Logic("tablefields");
   }

   /**
    * view an object Action
    */
   function viewAction(&$context,&$error)

   {
     global $db;

     $ret=Logic::viewAction($context,$error);
     $this->_getClass($context);
     return $ret;
   }

   /**
    * edit/add an object Action
    */
   function editAction(&$context,&$error)

   {
     $this->_getClass($context);
     // must be done before the validation
     return Logic::editAction($context,$error);
   }


   /**
    * Change rank action
    * Default implementation
    */
   function changeRankAction(&$context,&$error)

   {
     return Logic::changeRankAction(&$context,&$error,"idgroup,class");
   }


   /**
    *
    */

   function makeSelect(&$context,$var)

   {
     switch($var) {
     case "type" :
       require_once("commonselect.php");
       makeSelectFieldTypes($context['type']);
       break;
     case "condition" :
       $arr=array(
		  "*"=>getlodeltextcontents("nocondition","admin"),
		  "+"=>getlodeltextcontents("fieldrequired","admin"),
		  "defaultnew"=>getlodeltextcontents("use_default_at_creation only","admin"),
		  "permanent"=>getlodeltextcontents("permanent","admin"),
		  "1"=>getlodeltextcontents("single","admin"),
		  );
     renderOptions($arr,$context['condition']);
       break;
     case "edition" :
       $arr=array(
		  "editable"=>getlodeltextcontents("edit_in_the_interface","admin"),
		  "importable"=>getlodeltextcontents("no_edit_but_import","admin"),
		  "none"=>getlodeltextcontents("no_change","admin"),
		  "display"=>getlodeltextcontents("display_no_edit","admin"),
		  "textarea"=>getlodeltextcontents("edit_textarea","admin"),
		  "fckeditor"=>getlodeltextcontents("edit_wysiwyg","admin")." (FCKEditor)",
		  "select"=>getlodeltextcontents("edit_select","admin"),
		  "multipleselect"=>getlodeltextcontents("edit_multiple_select","admin"),
		  "radio"=>getlodeltextcontents("edit_radio","admin"),
		  "checkbox"=>getlodeltextcontents("edit_checkbox","admin")
		  );
       renderOptions($arr,$context['edition']);
       break;
     case "allowedtags" :
       require_once("balises.php");
       $groups=array_merge(array_keys($GLOBALS['xhtmlgroups']),array_keys($GLOBALS['multiplelevel']));
       $arr2=array();
       foreach($groups as $k) {
	 if ($k && !is_numeric($k)) $arr2[$k]=$k;
       }
       renderOptions($arr2,$context['allowedtags']);
       break;
     case "idgroup" :
       $arr=array();
       // get the groups having of the same class as idgroup
       $result=$GLOBALS['db']->execute(lq("SELECT #_TP_tablefieldgroups.id,#_TP_tablefieldgroups.title FROM #_tablefieldgroupsandclassesjoin_ INNER JOIN #_TP_tablefieldgroups as tfg2 ON tfg2.class=#_TP_classes.class WHERE tfg2.id='".$context['idgroup']."'")) or die($GLOBALS['db']->errormsg());
       while(!$result->EOF) {
	 $arr[$result->fields['id']]=$result->fields['title'];
	 $result->MoveNext();
       }
       renderOptions($arr,$context['idgroup']);
       break;
   case "g_name" :
     if (!$context['classtype']) $this->_getClass($context);
     switch ($context['classtype']) {
     case 'entities':
       $g_namefields=array("DC.Title","DC.Description",
			    "DC.Publisher","DC.Date",
			    "DC.Format","DC.Identifier",
			    "DC.Source","DC.Language",
			    "DC.Relation","DC.Coverage",
			    "DC.Rights");
       break;
     case 'persons':
       $g_namefields=array("Firstname","Familyname","Title");
       break;
     case 'entities_persons':
       $g_namefields=array("Title");
       break;
     case 'entries':
       $g_namefields=array("Index key");
       break;
     default:
       trigger_error("class type ?",E_USER_ERROR);
     }
//"Creator",
//"Contributor",
//"Type"
     $dao=$this->_getMainTableDAO();
     $tablefields=$dao->findMany("class='".$context['class']."'","","g_name,title");     
     foreach($tablefields as $tablefield) { $arr[$tablefield->g_name]=$tablefield->title; }

     $arr2=array(""=>"--");
     foreach($g_namefields as $g_name) {
       $lg_name=strtolower($g_name);
       if ($arr[$lg_name]) {
	 $arr2[$lg_name]=$g_name." &rarr; ".$arr[$lg_name];
       } else {
	 $arr2[$lg_name]=$g_name;
       }
     }
     renderOptions($arr2,$context['g_name']);
     break;
     case 'weight':
       $arr=array("0"=>getlodeltextcontents("not_indexed","admin"),
		  "1"=>"1","2"=>"2","4"=>"4","8"=>"8");
       renderOptions($arr,$context['weight']);
       break;
   }
   }

   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */


   /*** In 0.7 we check the field is not moved in another class... it's not 100% required in fact
    function validateFields(&$context,&$error) {
     if (!Logic::validateFields($context,$error)) return false;
     // check the group does not change 
     if ($oldidgroup!=$idgroup) {
	$set['rank']=get_rank_max("fields","idgroup='$idgroup'");      
	// check the new group has the same class (extra security)
	$result=mysql_query("SELECT 1 FROM $GLOBALS[tp]tablefieldgroups WHERE id='$idgroup' AND class='".$context['class']."'") or dberror();
	if (mysql_num_rows($result)!=1) die("ERROR: the new and the old group of the field are not in the same class");
      }**/


   function _prepareEdit($dao,&$context)

   {
     // gather information for the following
     if ($context['id']) {
       $this->oldvo=$dao->getById($context['id']);
       if (!$this->oldvo) die("ERROR: internal error in TableFields::deleteAction");
     }
   }

   function _saveRelatedTables($vo,$context) 

   {
     global $home,$lodelfieldtypes,$db;
     require_once("fieldfunc.php");

     // remove the dc for all the other fields
     if ($vo->g_name) {
       $db->execute(lq("UPDATE #_TP_tablefields SET g_name='' WHERE g_name='".$vo->g_name."' AND id!='".$vo->id."' AND class='".$vo->class."'")) or dberror();
     }

     // manage the physical field 
     if ($vo->class && $this->oldvo->class && 
	 $this->oldvo->class!=$vo->class) die("ERROR: field change of class is not implemented yet");

     if ($vo->type!="entities") {
       if (!$this->oldvo) {
	 $alter="ADD";
       } elseif ($this->oldvo->name!=$vo->name) {
	 $alter="CHANGE ".$this->oldvo->name;
       } elseif ($lodelfieldtypes[$this->oldvo->type]['sql']=$lodelfieldtypes[$vo->type]['sql']) {
	 $alter="MODIFY";
       }

       if ($alter) { // modify or add or rename the field
	 if (!$lodelfieldtypes[$vo->type]['sql']) die("ERROR: internal error in TableFields:: _saveRelatedTables ".$vo->type);
	 $db->execute(lq("ALTER TABLE #_TP_".$context['class']." $alter ".$vo->name." ".$lodelfieldtypes[$vo->type]['sql'])) or dberror();
       }
       if ($alter || $vo->filtering!=$this->oldvo->filtering) {
	 // should be in view ??
	 require_once("cachefunc.php");
	 clearcache();
       }
     }
     unset($this->oldvo);
   }


   function _prepareDelete($dao,&$context)

   {     
     // gather information for the following
     $this->vo=$dao->getById($context['id']);
     if (!$this->vo) die("ERROR: internal error in TableFields::deleteAction");
   }

   function _deleteRelatedTables($id)

   {
     global $db,$home;
     print_r($this->vo);
     die();
     if (!$this->vo) die("ERROR: internal error in TableFields::deleteAction");
     $db->execute(lq("ALTER TABLE #_TP_".$this->vo->class." DROP ".$this->vo->name)) or dberror();
     unset($this->vo);

     // should be in the view....
     require_once("cachefunc.php");
     clearcache();
     //

     return "_back";
   }

   function _getClass(&$context)

   {
     global $db;
     if ($context['idgroup']) {
       $row=$db->getRow(lq("SELECT #_TP_classes.class,classtype FROM #_tablefieldgroupsandclassesjoin_ WHERE #_TP_tablefieldgroups.id='".$context['idgroup']."'")) or dberror();
       if ($context['class'] && $context['class']!=$row['class']) die("ERROR: idgroup and class are incompatible in TableFieldsLogic::editAction");
       $context['class']=$row['class'];
       $context['classtype']=$row['classtype'];
     } else {
       if (substr($context['class'],0,9)=="entities_") {
	 $class=substr($context['class'],9);
	 $classtype="entities_";
       } else {
	 $class=$context['class'];
       }
       $classtype.=$db->getOne(lq("SELECT classtype FROM #_TP_classes WHERE class='".$class."'"));
       $context['classtype']=$classtype;
     }
   }

   /**
    *
    * Special treatment for allowedtags, from/to the context
    */
   function _populateContext(&$vo,&$context) {
     Logic::_populateContext($vo,$context);
     $context['allowedtags']=explode(";",$vo->allowedtags);
   }
   function _populateObject(&$vo,&$context) {
     Logic::_populateObject($vo,$context);
     $vo->class=$context['class']; // it is safe, we now that !
     $vo->allowedtags=is_array($context['allowedtags']) ? join(";",$context['allowedtags']) : "";
   }


   // begin{publicfields} automatic generation  //   
    function _publicfields() {
     return array("name"=>array("tablefield","+"),
                  "class"=>array("class","+"),
                  "title"=>array("text","+"),
                  "style"=>array("mlstyle",""),
                  "type"=>array("select","+"),
                  "g_name"=>array("select",""),
                  "condition"=>array("select","+"),
                  "defaultvalue"=>array("text",""),
                  "processing"=>array("text",""),
                  "allowedtags"=>array("multipleselect",""),
                  "filtering"=>array("text",""),
                  "edition"=>array("select",""),
                  "editionparams"=>array("text",""),
                  "weight"=>array("select",""),
                  "comment"=>array("longtext",""),
                  "idgroup"=>array("select","+"));
             }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //

    function _uniqueFields() {  return array(array("name","class"),);  }
   // end{uniquefields} automatic generation  //


} // class 


/*-----------------------------------*/
/* loops                             */

function loop_allowedtags_documentation(&$context,$funcname)

{
  ##$groups=array_merge(array_keys($GLOBALS['xhtmlgroups']),array_keys($GLOBALS['multiplelevel']));
  require_once("balises.php");
  foreach($GLOBALS['xhtmlgroups'] as $groupname => $tags) {
    $localcontext=$context;
    $localcontext['count']=$count;
    $count++;
    $localcontext['groupname']=$groupname;
    $localcontext['allowedtags']="";
    foreach ($tags as $k=>$v) { if (!is_numeric($k)) unset($tags[$k]); }
    if (!$tags) continue;
    $localcontext['allowedtags']=join(", ",$tags);
    call_user_func("code_do_$funcname",$localcontext);
  }
}


?>
