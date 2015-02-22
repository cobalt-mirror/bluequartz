<?php
// Author: Jonathan Mayer, Kevin K.M. Chiu, Michael Stauber
// $Id: SetSelector.php

// description:
// This class represents a selector to pick items out of a set.
//
// applicability:
// In general, this class is suitable to pick multiple items from homogeneous
// and large sets. If the set in question is small, (e.g. 5), or only one item
// can be picked, use MultiChoice instead.
//
// Usage:
//
// This is a bit more difficult to comprehend than it should be. For an easy
// to understand example we take this here. It's from /am/amSettings:
//
//    $select_caps =& $factory->getSetSelector('itemsToMonitor',
//        $cceClient->array_to_scalar($selected),               <- Ampersand (&) separated list of the selected (active) Labels (i18-nized Labels)
//        $cceClient->array_to_scalar($all_monitor_items),      <- Ampersand (&) separated list of ALL Labels (i18-nized Labels)
//        'selected', 'notSelected', 'rw',                      <- Table heading selected / unselected part and "rw" for the access
//        $cceClient->array_to_scalar($selectedVals),           <- Ampersand (&) separated list of just the selected (active) IDs
//        $cceClient->array_to_scalar($all_monitor_itemsVals)); <- Ampersand (&) separated list of ALL IDs
//
//    $select_caps->setOptional(true);                      <- Self explaining.
//
//    $block->addFormField($select_caps,                    <- Out with the getSetSelector()
//                $factory->getLabel('itemsToMonitor'),     <- Set a Label for it.
//                $defaultPage                              <- Specify which pagedBlock "tab" it goes onto.
//                );
//
//

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
  var $i18n;
  var $Label;
  var $Description;

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
  function SetSelector(&$page, $id, $values, $i18n, $entries, $emptyMessage, $valueVals="", $entriesVals="") {
    // superclass constructor
    $this->FormField($page, $id, $values, "", $emptyMessage);

    $this->setValues($values);
    $this->setEntries($entries);

    $this->i18n = $i18n;

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
    if ($id == "make_fifty") {
      $this->rows = "50";
    }
    elseif ($id == "make_fourty") {
      $this->rows = "40";
    }
    elseif ($id == "make_thirty") {
      $this->rows = "30";
    }
    elseif ($id == "make_twenty") {
      $this->rows = "20";
    }
    elseif ($id == "make_ten") {
      $this->rows = "10";
    }
    else {
      $this->rows = "6";
    }

    // internationalize
    $i18n =& $page->getI18n();
    $this->EMPTY_LABEL = $i18n->interpolate($this->EMPTY_LABEL);
  }

  // Sets the current label
  function setCurrentLabel($label) {
    $this->Label = $label;
  }

  // Returns the current label
  function getCurrentLabel() {
    if (!isset($this->Label)) {
      $this->Label = "";
    }
    return $this->Label;
  }

  // Sets the current label-description:
  function setDescription($description) {
    if (!isset($this->Description)) {
      $this->Description = "";
    }
    $this->Description = $description;
  }

  // Returns the current label-description:
  function getDescription() {
    return $this->Description;
  }

  // description: defines where the labels are placed on formfields:
  function setLabelType($labeltype) {
        $this->LabelType = $labeltype;
  }

  // Returns where the labels are placed on formfields:
  function getLabelType() {
    if (!isset($this->LabelType)) {
         $this->LabelType = "label_side top";
    }
    return $this->LabelType;
  }

  function &getDefaultStyle(&$stylist) {
    $style = "";
    return $style;
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
      $this->entries = new FormField($this->page, $this->getId() . "_entry_strings", $entries, $this->i18n);
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
      $this->values = new FormField($this->page, $this->getId() . "_value_strings", $values, $this->i18n);
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
      $this->valueVals = new FormField($this->page, "_" . $this->getId() . "_values", $valueVals, $this->i18n);
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
      $this->entriesVals = new FormField($this->page, "_" . $this->getId() . "_entries", $entriesVals, $this->i18n);
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
    $addIcon = "";
    $addIconDisabled = "";
    $removeIcon = "";
    $removeIconDisabled = "";
    $upIcon = "";
    $upIconDisabled = "";
    $downIcon = "";
    $downIconDisabled = "";

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

    // Check Class BXPage to see if we have a label and description for this FormField:
    if (is_array($this->page->getLabel($id))) {
      foreach ($this->page->getLabel($id) as $label => $description) {
        // We do? Tell FormFieldBuilder about it:
        $formFieldBuilder->setCurrentLabel($label);
        $formFieldBuilder->setDescription($description);
      }
    }
    else {
      // We have no label for this FormField:
      $formFieldBuilder->setCurrentLabel("");
      $formFieldBuilder->setDescription("");
    }

    // Tell FormFieldBuilder where the lable is:
    $formFieldBuilder->setLabelType($this->getLabelType());

    // make select field for value set
    $values = stringToArray($value);
    $valuesVals = stringToArray($valueVal);

    if(count($values) == 0){
      $values[] = $this->EMPTY_LABEL;
      $valuesVals[] = $this->EMPTY_LABEL;
    }

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

    if(count($entries) == 0){
      $entries[] = $this->EMPTY_LABEL;
      $entiesVals[] = $this->EMPTY_LABEL;
    }

    // New combined selector:
    $newcombinedselector = $formFieldBuilder->makeMultiSelectField($id, $access, $this->i18n, $this->rows, $GLOBALS["_FormField_width"], true, $formId, "", $allEntries, $allEntriesVals, scalar_to_array($this->getValueVals()));

    // make hidden field
    $hidden = $formFieldBuilder->makeHiddenField($id);
    $hidden_values = $formFieldBuilder->makeHiddenField($this->valueVals->getId());
    $hidden_entries = $formFieldBuilder->makeHiddenField($this->entriesVals->getId());
    $hidden_values_strings = $formFieldBuilder->makeHiddenField($this->values->getId());
    $hidden_entries_strings = $formFieldBuilder->makeHiddenField($this->entries->getId());

    // make labels row
    $valueLabel =& $this->getValueLabel();
    $valueLabelHtml = ($valueLabel != null) ? $valueLabel->toHtml() : "<IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">";

    // If a Label and Description are set, we use them. If not, then we
    // calculate these based on the ID of the FormObject:
    if ((isset($this->Label)) && (strlen($this->Label) > "0")) {
      $label = $this->Label;
    }
    else {
      $label = $this->i18n->getHtml($id);
    }
    if ((isset($this->Description)) && (strlen($this->Description) > "0")) {
      $helptext = $this->Description;
    }
    else {
        $h = $id . '_help';
        $helptext = $this->i18n->getWrapped("[[$h]]");
    }

    $result = '
              <div class="col_100">
                <fieldset class="bottom">
                  <label class="tooltip right uniform" title="' . $helptext . '">' . $label . '</label>
                  <div>
                      ' . $newcombinedselector . '
                  </div>
                </fieldset>
              </div>';

    return $result;
  }
} // end of class SetSelector

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. 
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/
?>