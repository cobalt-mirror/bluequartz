<?php
// Author: Jonathan Mayer, Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: SetSelector.php 995 2007-05-05 07:44:27Z shibuya $

// description:
// This class represents a selector to pick items out of a set.
//
// applicability:
// In general, this class is suitable to pick multiple items from homogeneous
// and large sets. If the set in question is small, (e.g. 5), or only one item
// can be picked, use MultiChoice instead.
//
// usage:
// The lefthand set is called the "values" set. The righthand set is called the
// "entries" set.
// Instantiate with the correct entry and value sets. Entry set contains the
// picked items while value set contains all the items including those from
// entry set. Use setEntriesLabel() and setValueLabel() to label the sets if
// necessary. Use toHtml() to get a HTML representation.

global $isSetSelectorDefined;
if($isSetSelectorDefined)
  return;
$isSetSelectorDefined = true;

include_once("ArrayPacker.php");
include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

class SetSelector extends FormField {
  //
  // private variables
  //

  var $EMPTY_LABEL = "[[palette.noItems]]";

  var $values; //strings for entries in left column
  var $entries; //strings for entries in right column
  var $entriesVals; //ids for entries in left column
  var $valueVals;//ids for entries in right column
  var $valueLabel;//title of left column
  var $entriesLabel;// title of right column
  var $isValueOrder;
  var $rows; // number of rows to display

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object that this object lives in
  // param: id: the identifier of the object
  // param: values: an ampersand "&" separated list for the value set
  // param: entries: an ampersand "&" separated list for the entry set
  // param: emptyMessage: message to be shown upon empty input
  // param: valueVals: ids for the items in the value set
  // param: entriesVals: ids for the items in the entry set
  function SetSelector(&$page, $id, $values, $entries, $emptyMessage, $valueVals="", $entriesVals="") {
    // superclass constructor
    $this->FormField($page, $id, $values, "", $emptyMessage);

    $this->setValues($values);
    $this->setEntries($entries);

    // if we're passed in ids and not just UI strings,
    // use the ids. Else, just use the UI strings
    if ($valueVals) {
      $this->setValueVals($valueVals);
    } else {
      $this->setValueVals($values);
    }

    // if we're passed in ids and not just UI strings,
    // use the ids. Else, just use the UI strings/
    if ($entriesVals) {
      $this->setEntriesVals($entriesVals);
    } else {
      $this->setEntriesVals($entries);
    }

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

    // set whether or not to preserve user data
    function setPreserveData($status = true)
    {
      // since the SetSelector FormField actually is a container for 
      // the various pieces, we need to push the preserve flag 
      // out to its pieces.
      if ($this->values) {
	$this->values->setPreserveData($status);
      }
      if ($this->entries) {
	$this->entries->setPreserveData($status);
      }
      if ($this->entriesVals) {
	$this->entriesVals->setPreserveData($status);
      }
      if ($this->valuesVals) {
	$this->valuesVals->setPreserveData($status);
      }
      $this->_prsrv = $status;
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
    return $this->entries->getValue();
  }

  // description: set the entry set to choose out of
  // param: entries: an ampersand "&" separated list for the entry set
  // see: getEntries()
  function setEntries($entries) {
    if (!$this->entries) {
      $this->entries = new FormField($this->page, $this->getId() . "_entry_strings", $entries);
    } else {
      $this->entries->setValue($entries);
    }
  }

  // description: get the value set to choose out of
  // returns: an ampersand "&" separated list for the value set
  // see: setValues()
  function getValues() {
    return $this->values->getValue();
  }

  // description: set the value set to choose out of
  // param: entries: an ampersand "&" separated list for the value set
  // see: getValues()
  function setValues($values) {
    if (!$this->values) {
      $this->values = new FormField($this->page, $this->getId() . "_value_strings", $values);
    } else {
      $this->values->setValue($values);
    }
  }

  // We used to just have the $this->value property hold our "values"
  // Now we have $this->values, a FormField that holds our values.
  // $this->value doesn't need to be used, but will be for backwards compatibility
  function setValue($value) {
    $this->setValues($value);
    $this->value = $value;

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

  // description: get the ids of value set
  // returns: an ampersand "&" separated list of ids for the value set
  // see: setValueVals()
  function getValueVals(){
    return $this->valueVals->getValue();
  }

  // description: set the ids of value set
  // param: an ampersand "&" separated list of ids for the value set
  // see: getValueVals()
  function setValueVals($valueVals){
    if (!$this->valueVals) {
      $this->valueVals = new FormField($this->page, "_" . $this->getId() . "_values", $valueVals);
    } else {
      $this->valueVals->setValue($valueVals);
    }
  }

  // description: get the ids of entry set
  // returns: an ampersand "&" separated list of ids for the entry set
  // see: setEntriesVals()
  function getEntriesVals(){
    return $this->entriesVals->getValue();
  }

  // description: set the ids of entry set
  // param: an ampersand "&" separated list of ids for the entry set
  // see: getEntriesVals()
  function setEntriesVals($entriesVals){
    if (!$this->entriesVals) {
      $this->entriesVals = new FormField($this->page, "_" . $this->getId() . "_entries", $entriesVals);
    } else {
      $this->entriesVals->setValue($entriesVals);
    }
  }

  // Since we're a container for form fields, we need to provide this interface
  function getFormFields() {
    return array($this->values, $this->entries, $this->valueVals, $this->entriesVals);
  }

  //render the html
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

    $value = $this->getValues();
    $valueVal = $this->getValueVals();
//      error_log("value is $valueVal");
    $valueId = $this->valueVals->getId() . "_select";

    $entriesStrVal = $this->getEntriesVals();
    $entriesId = $this->entriesVals->getId(). "_select";
    $entriesStr = $this->getEntries();

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
    $hidden_values = $formFieldBuilder->makeHiddenField($this->valueVals->getId());
    $hidden_entries = $formFieldBuilder->makeHiddenField($this->entriesVals->getId());
    $hidden_values_strings = $formFieldBuilder->makeHiddenField($this->values->getId());
    $hidden_entries_strings = $formFieldBuilder->makeHiddenField($this->entries->getId());

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

    $result = "
<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\">
$labelRow
$upRow
  <TR>
    <TD ALIGN=\"CENTER\">
      $valueSelect
    </TD>
    <TD ALIGN=\"CENTER\" VALIGN=\"MIDDLE\">
      <A HREF=\"javascript:top.code.SetSelector_moveItem(document.$formId.$id, 'add'); top.code.SetSelector_setButtons(document.$formId.$id)\" onMouseOver=\"return true;\"><IMG BORDER=\"0\" NAME=\"$addId\" SRC=\"$addIconDisabled\"></A>
      <BR>
      <A HREF=\"javascript:top.code.SetSelector_moveItem(document.$formId.$id, 'remove'); top.code.SetSelector_setButtons(document.$formId.$id)\" onMouseOver=\"return true;\"><IMG BORDER=\"0\" NAME=\"$removeId\" SRC=\"$removeIconDisabled\"></A>
    </TD>
    <TD ALIGN=\"CENTER\">
      $entriesSelect
    </TD>
  </TR>
$downRow
</TABLE>
$hidden
$hidden_values
$hidden_entries
$hidden_values_strings
$hidden_entries_strings
<SCRIPT LANGUAGE=\"javascript\">
var element = document.$formId.$id;
element.emptyLabel = \"$this->EMPTY_LABEL\";
element.focus = document.$formId.$valueId.focus;
element.parentDocument = document;

element.entriesElement = document.$formId.$entriesId;
element.valueElement = document.$formId.$valueId;

element.entries_field = document.$formId." . $this->entriesVals->getId() .";
element.values_field = document.$formId." . $this->valueVals->getId() .";

element.entries_strings = document.$formId." . $this->entries->getId() .";
element.values_strings = document.$formId." . $this->values->getId() .";

element.addButton = document.$addId;
element.addButton.url = \"$addIcon\";
element.addButton.disabledUrl = \"$addIconDisabled\";

element.removeButton = document.$removeId;
element.removeButton.url = \"$removeIcon\";
element.removeButton.disabledUrl = \"$removeIconDisabled\";

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
