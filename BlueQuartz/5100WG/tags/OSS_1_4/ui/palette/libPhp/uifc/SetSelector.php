<?php
// Author: Jonathan Mayer, Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: SetSelector.php 3 2003-07-17 15:19:15Z will $

// description:
// This class represents a selector to pick items out of a set.
//
// applicability:
// In general, this class is suitable to pick multiple items from homogeneous
// and large sets. If the set in question is small, (e.g. 5), or only one item
// can be picked, use MultiChoice instead.
//
// usage:
// Instantiate with the right entry and value sets. Entry set contains the
// picked items while value set contains all the items including those from
// entry set. Use setEntriesLabel() and setValueLabel() to label the sets if
// necessary. Use toHtml() to get a HTML representation.

global $isSetSelectorDefined;
if($isSetSelectorDefined)
  return;
$isSetSelectorDefined = true;

include("ArrayPacker.php");
include("uifc/FormField.php");
include("uifc/FormFieldBuilder.php");

class SetSelector extends FormField {
  //
  // private variables
  //

  var $EMPTY_LABEL = "[[palette.noItems]]";

  var $entries;
  var $entriesVals;
  var $valueVals;
  var $entriesLabel;
  var $isValueOrder;
  var $rows; // number of rows
  var $valueLabel;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object that this object lives in
  // param: id: the identifier of the object
  // param: value: an ampersand "&" separated list for the value set
  // param: entries: an ampersand "&" separated list for the entry set
  // param: emptyMessage: message to be shown upon empty input
  function SetSelector(&$page, $id, $value, $entries, $emptyMessage, $valueVals="", $entriesVals="") {
    // superclass constructor
    $this->FormField($page, $id, $value, "", $emptyMessage);

    $this->setEntries($entries);

    $this->setValueVals($valueVals==""?$value:$valueVals);
    $this->setEntriesVals($entriesVals==""?$entries:$entriesVals);

    $this->setEntriesLabel();
    $this->setValueLabel();

    // default
    $this->rows = 6;

    // internationalize
    $i18n =& $page->getI18n();
    $this->EMPTY_LABEL = $i18n->interpolate($this->EMPTY_LABEL);
  }

  function &getDefaultStyle(&$stylist) {
    return $stylist->getStyle("SetSelector");
  }

  // description: get the label of the entry set
  // returns: a Label object
  // see: setEntriesLabel()
  function &getEntriesLabel() {
    return $this->entriesLabel;
  }

  // description: set the label of the entry set
  // param: entriesLabel: a Label object
  // see: getEntriesLabel()
  function setEntriesLabel($entriesLabel = "") {
    $this->entriesLabel = $entriesLabel;
  }

  // description: get the label of the value set
  // returns: a Label object
  // see: setValueLabel()
  function &getValueLabel() {
    return $this->valueLabel;
  }

  // description: set the label of the value set
  // param: valueLabel: a Label object
  // see: getValueLabel()
  function setValueLabel($valueLabel = "") {
    $this->valueLabel = $valueLabel;
  }

  // description: get the entry set to choose out of
  // returns: an ampersand "&" separated list for the entry set
  // see: setEntries()
  function getEntries() {
    return $this->entries;
  }

  // description: set the entry set to choose out of
  // param: entries: an ampersand "&" separated list for the entry set
  // see: getEntries()
  function setEntries($entries) {
    $this->entries = $entries;
  }

  // description: see if ordering support for values is enabled
  // returns: true if ordering is enabled, false otherwise
  // see: setValueOrder()
  function isValueOrder() {
    return $this->isValueOrder;
  }

  // description: enable/disable ordering support for values
  // param: isValueOrder: true to enable, false otherwise
  // see: isValueOrder()
  function setValueOrder($isValueOrder) {
    $this->isValueOrder = $isValueOrder;
  }

  function getValueVals(){
    return $this->valueVals;
  }

  function setValueVals($valueVals){
    $this->valueVals = $valueVals;
  }

  function getEntriesVals(){
    return $this->entriesVals;
  }

  function setEntriesVals($entriesVals){
    $this->entriesVals = $entriesVals;
  }

  function toHtml($style = "") {
    $page =& $this->getPage();

    if($style == null || $style->getPropertyNumber() == 0)
      $style =& $this->getDefaultStyle($page->getStylist());

    // find out style properties
    $addIcon = $style->getProperty("addIcon");
    $addIconDisabled = $style->getProperty("addIconDisabled");
    $removeIcon = $style->getProperty("removeIcon");
    $removeIconDisabled = $style->getProperty("removeIconDisabled");
    $upIcon = $style->getProperty("upIcon");
    $upIconDisabled = $style->getProperty("upIconDisabled");
    $downIcon = $style->getProperty("downIcon");
    $downIconDisabled = $style->getProperty("downIconDisabled");

    $access = $this->getAccess();
    $form =& $page->getForm();
    $formId = $form->getId();
    $id = $this->getId();
    $entriesId = "_".$id."_entries";
    $entriesStr = $this->getEntries();
    $entriesStrVal = $this->getEntriesVals();
    $value = $this->getValue();
    $valueVal = $this->getValueVals();
    $valueId = "_".$id."_value";
    $addId = "_".$id."_add";
    $removeId = "_".$id."_remove";
    $upId = "_".$id."_up";
    $downId = "_".$id."_down";

    $formFieldBuilder = new FormFieldBuilder();

    // make select field for value set
    $values = stringToArray($value);
    $valuesVals = stringToArray($valueVal);
    // make defined order (not necessary sorted in different locales)
    //sort($values);

    if(count($values) == 0){
      $values[] = $this->EMPTY_LABEL;
      $valuesVals[] = $this->EMPTY_LABEL;
    }
    $valueSelect = $formFieldBuilder->makeSelectField($valueId, $access, $this->rows, $GLOBALS["_FormField_width"], true, $formId, "top.code.SetSelector_setButtons(document.$formId.$id)", $values, $valuesVals);

    // make select field for entry set
    $entries = array();
    $entriesVals = array();
    $allEntries = stringToArray($entriesStr);
    $allEntriesVals = stringToArray($entriesStrVal);

    for ($i = 0; $i < count($allEntries); $i++){
      if(!in_array($allEntries[$i], $values)){
      	array_push($entries, $allEntries[$i]);
        array_push($entriesVals, $allEntriesVals[$i]);
      }
    }
    // make defined order (not necessary sorted in different locales)
    //sort($entries);

    if(count($entries) == 0){
      $entries[] = $this->EMPTY_LABEL;
      $entiesVals[] = $this->EMPTY_LABEL;
    }
    $entriesSelect = $formFieldBuilder->makeSelectField($entriesId, $access, $this->rows, $GLOBALS["_FormField_width"], true, $formId, "top.code.SetSelector_setButtons(document.$formId.$id)", $entries, $entriesVals);

    // make hidden field
    $hidden = $formFieldBuilder->makeHiddenField($id);

    // make labels row
    $entriesLabel =& $this->getEntriesLabel();
    $entriesLabelHtml = ($entriesLabel != null) ? $entriesLabel->toHtml() : "<IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">";
    $valueLabel =& $this->getValueLabel();
    $valueLabelHtml = ($valueLabel != null) ? $valueLabel->toHtml() : "<IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">";

    $labelRow = "";
    if($entriesLabel != null || $valueLabel != null)
      $labelRow = "<TR><TD ALIGN=\"CENTER\" VALIGN=\"BOTTOM\">$valueLabelHtml</TD><TD><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\"></TD><TD ALIGN=\"CENTER\" VALIGN=\"BOTTOM\">$entriesLabelHtml</TD></TR>";

    // make up and down buttons if necessary
    $upRow = "";
    $downRow = "";
    $orderJavascript = "";
    if($this->isValueOrder) {
      $upRow = "<TR><TD ALIGN=\"CENTER\"><A HREF=\"javascript:top.code.SetSelector_orderItem(document.$formId.$id.valueElement, 'up'); top.code.SetSelector_setButtons(document.$formId.$id)\" onMouseOver=\"return true;\"><IMG BORDER=\"0\" NAME=\"$upId\" SRC=\"$upIconDisabled\"></A></TD></TR>";
      $downRow = "<TR><TD ALIGN=\"CENTER\"><A HREF=\"javascript:top.code.SetSelector_orderItem(document.$formId.$id.valueElement, 'down'); top.code.SetSelector_setButtons(document.$formId.$id)\" onMouseOver=\"return true;\"><IMG BORDER=\"0\" NAME=\"$downId\" SRC=\"$downIconDisabled\"></A></TD></TR>";

      $orderJavascript = "
element.upButton = document.$upId;
element.upButton.url = \"$upIcon\";
element.upButton.disabledUrl = \"$upIconDisabled\";

element.downButton = document.$downId;
element.downButton.url = \"$downIcon\";
element.downButton.disabledUrl = \"$downIconDisabled\";
";
    }

    // Strip image dimensions
    $addIconS = substr($addIcon, 0, strpos($addIcon, "\""));
    $addIconDisabledS = substr($addIconDisabled, 0, 
        strpos($addIconDisabled, "\""));
    $removeIconS = substr($removeIcon, 0, strpos($removeIcon, "\""));
    $removeIconDisabledS = substr($removeIconDisabled, 0, 
        strpos($removeIconDisabled, "\""));

    $result = "
<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\">
$labelRow
$upRow
  <TR>
    <TD ALIGN=\"CENTER\">
      $valueSelect
    </TD>
    <TD ALIGN=\"CENTER\" VALIGN=\"MIDDLE\">
    <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>
      <A HREF=\"javascript:top.code.SetSelector_moveItem(document.$formId.$id, 'add'); top.code.SetSelector_setButtons(document.$formId.$id)\" onMouseOver=\"return true;\"><IMG BORDER=\"0\" NAME=\"$addId\" SRC=\"$addIconDisabled\"></A>
      </td></tr><tr><td>
      <A HREF=\"javascript:top.code.SetSelector_moveItem(document.$formId.$id, 'remove'); top.code.SetSelector_setButtons(document.$formId.$id)\" onMouseOver=\"return true;\"><IMG BORDER=\"0\" NAME=\"$removeId\" SRC=\"$removeIconDisabled\"></A>
      </td></tr></table>
    </TD>
    <TD ALIGN=\"CENTER\">
      $entriesSelect
    </TD>
  </TR>
$downRow
</TABLE>
$hidden
<SCRIPT LANGUAGE=\"javascript\">
var element = document.$formId.$id;
element.emptyLabel = \"$this->EMPTY_LABEL\";
element.focus = document.$formId.$valueId.focus;
element.parentDocument = document;

element.entriesElement = document.$formId.$entriesId;
element.valueElement = document.$formId.$valueId;

element.addButton = document.$addId;
element.addButton.url = \"$addIconS\";
element.addButton.disabledUrl = \"$addIconDisabledS\";

element.removeButton = document.$removeId;
element.removeButton.url = \"$removeIconS\";
element.removeButton.disabledUrl = \"$removeIconDisabledS\";

$orderJavascript
</SCRIPT>
";

    $result .= $formFieldBuilder->makeJavaScript($this, "", "top.code.SetSelector_submitHandler");

    return $result;
  }
} // end of class SetSelector

// eof
/*
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>

