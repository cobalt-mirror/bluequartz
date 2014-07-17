<?php
// Author: Kevin K.M. Chiu
// $Id$

// description:
// This class represents a widget that allows users to choose one to many
// options out of one to many. It can render itself as different forms (e.g.
// pulldowns, checkboxes) for different option types (e.g. single option, many
// options).
//
// applicability:
// Use it where options need to be selected.
//
// usage:
// Instantiate an object and add option(s) (i.e. Option class) to it. Each
// option can contain form field objects. For example, a multichoice for
// payment method can have cash and credit card options and credit card option
// can have a credit card number field associated with it. Although this class
// selects the best form to render automatically, users can use the
// setFullSize() to force this class to use a more readable but consume more
// space form. The setMultiple() methods can be uses to make multiple options
// selectable at once. When multiple is set, this submitted value of this form
// field is an array encoded in a string by array packer.
//
// If the checkbox is not ticked, all sub-elements will be hidden via the class
// "display_none".
//
// Note: 
// CHECKBOX works fine. 
//
// RADIO works, but has a few quirks. At this time it doesn't take access type
// into account. So it's always visible.
//

global $isMultiChoiceDefined;
if($isMultiChoiceDefined)
  return;
$isMultiChoiceDefined = true;

include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");
include_once("ArrayPacker.php");

class MultiChoice extends FormField {
  //
  // private variables
  //

  var $fullSize;
  var $multiple;
  var $scroll;
  var $row;
  var $options;
  var $Label, $Description;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this form field lives in
  // param: id: the identifier of this object
  function MultiChoice(&$page, $id, $i18n) {

    $this->i18n = $i18n;
    $this->BxPage = $page;

    // superclass constructor
    $this->FormField($page, $id, "", $this->i18n);

    $this->fullSize = false;
    $this->multiple = false;
    $this->scroll = false;
    $this->row = 1;
    $this->options = array();
  }

  // Set Optional
  function setOptional($setOptional) {
    $this->Optional = $setOptional;
  }

  // Get Optional
  function getOptional() {
    if (!isset($this->Optional)) {
      $this->Optional = FALSE;
    }
    return $this->Optional;
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

  function &getDefaultStyle(&$stylist) {
    $style = "";
    return $style;
  }

  // description: get all options added
  // returns: an array of Option objects
  // see: addOption()
  function getOptions() {
    return $this->options;
  }

  // description: defines where the labels are placed on formfields:
  function setLabelType($labeltype) {
        $this->LabelType = $labeltype;
  }

  // Returns where the labels are placed on formfields:
  function getLabelType() {
    if (!isset($this->LabelType)) {
         $this->LabelType = "label_side";
    }
    return $this->LabelType;
  }

  // this is used to provide a generic interface
  // to formfields inside the options
  // description: get all options added
  // returns: an array of Option objects
  // see: addOption()
  function getFormFields() {
    $fields = array();
    foreach ($this->options as $a_option) {
      $fields = array_merge($fields, $a_option->getFormFields());
    }
    return $fields;
  }

  // description: add an option
  //     options are not selected by default when they are added
  // param: option: an Option object
  // see: getOptions()
  function addOption(&$option, $selected = "__undef") {
    $index = count($this->options);
    $this->options[$index] =& $option;
    if ($selected === "__undef") {
        $this->setSelected($index, $option->isSelected());
    } 
    else {
	    $this->setSelected($index, $selected);
    }
  }

  // description: get the full size mode
  // returns: full size mode in boolean
  // see: setFullSize()
  function isFullSize() {
    return $this->fullSize;
  }

  // description: set the full size mode
  // param: fullSize: true to make the object rendered as more readable,
  //     but less compact. False otherwise
  // returns: nothing
  // see: isFullSize()
  function setFullSize($fullSize) {
    $this->fullSize = $fullSize;
  }

  // description: get the multiple mode
  // returns: multiple mode in boolean
  // see: setMultiple()
  function isMultiple() {
    return $this->multiple;
  }

  // description: set the multiple mode
  // param: multiple: true if multiple choices can be selected at the same
  //     time. False otherwise
  // see: isMultiple()
  function setMultiple($multiple) {
    $this->multiple = $multiple;
  }

  // description: get the scroll mode
  // returns: scroll mode in boolean
  // see: setScroll() 
  function isScroll() { 
    return $this->scroll;
  }

  // description: set the scroll mode
  // param: scroll: true if scroll choices can be selected at the same
  //     time. False otherwise
  // see: isScroll()
  function setScroll($scroll) {
    $this->scroll = $scroll;
  }

  // description: select a option
  // param: index: an integer index of the option
  // param: isSelected: true for selected, false otherwise.
  //     Optional and true by default
  // returns: nothing
  function setSelected($index, $isSelected = true) {
    $this->ms_SelectedIndex = $index;
    $options = $this->getOptions();

    if (!is_int($index)) {
      // attempt to discover the index
       for ($i = 0; $i < count($options); $i++) {
         if ($index === $options[$i]->getValue()) {
           $index = $i;
           break;
         }
       }
    }

    // index does not point to any option?
    if (isset($options[$index])) {
      if(!is_object($options[$index])) {
        return;
      }
    }

    // make the values array with all the select values
    $values = array();
    for($i = 0; $i < count($options); $i++) {
      $option =& $options[$i];

      if(($i != $index && $option->isSelected()) || ($i == $index && $isSelected)) {
       $values[] = $option->getValue();
      }
    }

    // set value
    $this->setValue(arrayToString($values));
  }

  function setValue($value) {
    $values = stringtoArray($value);
    
    if ($this->isMultiple()) {
      $this->value = arrayToString($values);
    }
    else {
      if (isset($values[0])) {
        $this->value = $values[0];
      }
      else {
        $this->value = "";
      }
    }

    // select options based on the value
    $options = $this->getOptions();
    for($i = 0; $i < count($options); $i++)
      if(in_array($options[$i]->getValue(), $values))
      	// needs to set the real object here, not the copy of it
      	$this->options[$i]->setSelected(true);
            else
      	$this->options[$i]->setSelected(false);
  }

  function toHtml($style = "") {
    if($style == null || $style->getPropertyNumber() == 0) {
      $page =& $this->getPage();
      if (!$page) { 
        print "<hr><b>MultiChoice.toHtml: no page object</b><hr>\n"; 
      }
      $style =& $this->getDefaultStyle($page->getStylist());
    }

    $options = $this->getOptions();

    // select style
    $noComposite = true;
    for($i = 0; $i < count($options); $i++) {
        if(count($options[$i]->getFormFields()) > 0) {
	        $noComposite = false;
	        break;
        }
    }

    if(!$this->isFullSize() && !$this->isMultiple() && !$this->isScroll() && $noComposite) {
      return $this->_toPullDown();
    }
    else if($this->isScroll()) {
      return $this->_toScroll();
    }
    else if($this->isMultiple() || count($options) == 1) {
      return $this->_toRows("checkbox", $style);
    }
    else {
      return $this->_toRows("radio", $style);
    }
  }

    //
    // private methods
    //

    function _toRows($selectionMode, &$style) {
        $choiceLabelStyle = "";
        $fieldGrouperStyleStr = "";
        $formFieldLabelStyle = "";

        // mstauber 
        $subscriptStyleStr = "";

        $builder = new FormFieldBuilder();

        $id = $this->getId();

        // Check Class BXPage to see if we have a label and description for this FormField:
        if (is_array($this->page->getLabel($id))) {
          foreach ($this->page->getLabel($id) as $label => $description) {
            // We do? Tell FormFieldBuilder about it:
            $builder->setCurrentLabel($label);
            $builder->setDescription($description);
          }
        }
        else {
          // We have no label for this FormField:
          $builder->setCurrentLabel("");
          $builder->setDescription("");
        }

        // check for user data
      	// user data is in HTTP_POST_VARS
      	global $HTTP_POST_VARS;
        $selected = array();
        if($this->isDataPreserved() && isset($HTTP_POST_VARS[$id]))
            $selected = stringToArray($HTTP_POST_VARS[$id]);

        // use an internal ID if it is checkbox mode and
        if ($selectionMode == "checkbox") {
            //$id = "_MultiChoice_checkbox_$id";
        }

        $options = "";
        $options = $this->getOptions();
        $num = count($options);
        // for the row form, always trust the assigned access
        // since there could be one option with child fields
        $access = $this->getAccess();

        $page =& $this->getPage();
        $form =& $page->getForm();
        $formId = $form->getId();
        $i18n =& $page->getI18n();
        $optionalStr = $i18n->get("optional", "palette");

        // make sure alignment is good
        //$result = "      <div class=\"columns clearfix\">\n";
        $result = "\n";

        if ($selectionMode == "checkbox") {
          //
          //--- Handle Checkboxes here:
          //

          // make all the options
          for($i = 0; $i < $num; $i++) {
              $option =& $options[$i];
              $childFields = $option->getFormFields();

              // get properties
              $label =& $option->getLabel();
              $labelHtml = $label->toHtml($choiceLabelStyle);
              $value = $option->getValue();
              
              $checked = false;
              if(in_array($value, $selected))
                  $checked = true;
              else if(count($selected) == 0)
                  $checked = $option->isSelected();

              // make field ID for Javascript
              $fieldId = ($i == 0) ? $id : $id."[$i]";

              // put child field on the same line
              // if there is only one child field and no label for that field
              $isSingleLine = (count($childFields) == 1 && !is_object($option->getFormFieldLabel($childFields[0])));

              // get the HTML for the child field
              $childFieldHtml = "";
              if ($isSingleLine) {
                $childFieldHtml = $childFields[0]->toHtml();
                $childFieldId = $childFields[0]->getId();
              }

              // String conversion:
              $ht = $id . "_help";
              $helptext = $this->i18n->getWrapped($ht);
              $my_name_opt1 = $this->i18n->getHtml("[[base-user.enableAutoResponderField]]");

              // boolean cce values are only 0 or 1
              if (isset($checked)) {
                if ($checked != "") {
                  $theField = "1";
                  $theField_checked = " checked";
                } 
                else {
                  $theField = "0";
                  $theField_checked = "";
                }
              }

              // We have to make a bloody assumption here:
              // That our checkbox is the first option. It should be.
              // If so, we pull it's "access" settings and use them.
              if (isset($this->options['0']->formFields['0']->access)) {
                $access = $this->options['0']->formFields['0']->access;
              }

              // Handle the various access types:
              if ($access == "r") {
                if ($theField == "1") {
                  $helptext_checkbox = $i18n->getWrapped("[[palette.enabled]]") . "<br>" . $i18n->getWrapped("[[palette.notprivileged]]");
                  $fields = '       <label for="' . $id . '" title="' . $helptext . '" class="tooltip right">' . $this->i18n->getHtml($id) . '</label>
                                                        <div>
                                                            <label for="' . $id . '" title="' . $helptext_checkbox . '" class="tooltip hover uniform">
                                                                <div class="ui-icon ui-icon-check"></div>
                                                            </label>
                                                        </div>';
                }
                else {
                  $helptext_checkbox = $i18n->getWrapped("[[palette.disabled]]") . "<br>" . $i18n->getWrapped("[[palette.notprivileged]]");
                  $fields = '       <label for="' . $id . '" title="' . $helptext . '" class="tooltip right">' . $this->i18n->getHtml($id) . '</label>
                                                        <div>
                                                            <label for="' . $id . '" title="' . $helptext_checkbox . '" class="tooltip hover uniform">
                                                                <div class="ui-icon ui-icon-cancel"></div>
                                                            </label>
                                                        </div>';
                }
              }
              else {
                $fields = '       <label for="' . $id . '" title="' . $helptext . '" class="tooltip right">' . $this->i18n->getHtml($id) . '</label>
                                                        <div>
                                                          <label for="' . $id . '" title="' . $helptext . '" class="tooltip right"><input type="checkbox" class="mcb-' . $id . '" name="' . $id . '" id="' . $id . '"' . $theField_checked . '/>'. $my_name_opt1 .'</label>
                                                        </div>';
              }

              // Note: The class "mcb-$id" is used for hiding/showing hidden elements on checkbox ticking/unticking.
              // We do that by adding/removing the class 'display_none':
              if ($theField == "1") {
                $hidemyass = " ";
              }
              else {
                $hidemyass = " display_none";
              }

              $result .= $builder->makeHiddenField("checkbox-" . $id, $theField);


              $result .= '
              <fieldset class="label_side top uniform">
                                                '. $fields . '
              </fieldset>
              <fieldset class="hide-' . $id . ' label_side top uniform' . $hidemyass . '">
                <label>
                  <button class="light small icon_only has_text" disabled="disabled">
                    <img src="/.adm/images/icons/small/grey/bended_arrow_up.png"></img>
                    <span>...</span>
                  </button>
                </label>
              <div class="' . $hidemyass . ' hide-' . $id . '">
                <h2 class="box_head">' . $this->i18n->getHtml($id) . '</h2>
                          ';

              // put child fields on different lines
              // if there are more than 1 of them or if the first one has label
              if (!$isSingleLine && count($childFields) > 0) {
  	            $result .= "\n";

  	            // make all the form fields of the options
  	            for($j = 0; $j < count($childFields); $j++) {
  	                $childField =& $childFields[$j];
                    $childLabel =& $option->getFormFieldLabel($childField);

                    // The Checkbox is not ticked. Now this is special!
                    // In that case we want to make all Children FormFields invisible.
                    if ($theField == "0") {
                      // We do so by setting the e class "display_none" and also make it toggleable 
                      // by setting the ID "hide-" plus the ID of the formfield. But we also set any
                      // classes that were there previously:
                      $this_fields_classes = $childField->getLabelType();
                      if (preg_match("/display_none hide-$id/", $this_fields_classes, $matches, PREG_OFFSET_CAPTURE, 3)) {
                        // This is one of the more stupid things: For one reason or another certain GUI pages loop
                        // through this more than once, adding multiple 'display_none hide-$id' to $this_fields_classes.
                        // That results in formfields not showing until you have toggled the 'enable' checkbox for as often
                        // as the script has looped. To compensate that we trim down $this_fields_classes if it contains
                        // multiple 'display_none hide-$id' entries:
                        $tfc = explode(' ', $this_fields_classes);
                        $tfc = array_unique($tfc);
                        $this_fields_classes = implode(' ', $tfc);
                      }
                      $childField->setLabelType($this_fields_classes);

                      // Now with that done we have one problem:
                      // FormFields of the class "display_none" are ignored. Entirely! So we generate
                      // "hidden" fields instead which contain the values of the FormFields that we 
                      // want to hide:
                      $childField_hidden = $builder->makeHiddenField($childField->id, $childField->value);

                      // Print out the new hidden fields:
                      $result .= "\n" . $childField_hidden;

                      // Now a bit of jQuery magic: We set an extra-header in BxPage which - on ticking
                      // the checkbox of this multichoice - makes the hidden elements visible. It hides
                      // them again when the checkbox is unticked:
                      $extraheader = "
                          <script type=\"text/javascript\">
                            $(document).ready(function () {
                                $('.mcb-$id').click(function(){
                                  if($(this).is(\":checked\")) {
                                    $('.hide-$id').removeClass(\"display_none\");
                                  } else {
                                    $('.hide-$id').addClass(\"display_none\");
                                  }
                                });
                            });
                          </script>
                        ";
                      $this->BxPage->ff_extra_headers[$id] = $extraheader;

                    }

                    // Print out the children FormFields:
  	                $childFieldHtml = $childField->toHtml();
  	                $childFieldId = $childField->getId();

  	                $result .= $childFieldHtml;
  	            }
              }
          }
        }
        else {
          //
          //--- Handle Radio's here:
          //

          // String conversion:
          $ht = $id . "_help";
          $helptext = $this->i18n->getWrapped($ht);

          $result .= '
          <fieldset class="label_side top">
            <label for="' . $id . '" title="' . $helptext . '" class="tooltip right">' . $this->i18n->getHtml($id) . '</label>
          <div class="">
            <TABLE>
                      ';

          // Make all the options
          for($i = 0; $i < $num; $i++) {
              $option =& $options[$i];
              $childFields = $option->getFormFields();

              // get properties
              $label =& $option->getLabel();
              $labelHtml = $label->toHtml($choiceLabelStyle);
              $value = $option->getValue();
              
              $checked = false;
              if(in_array($value, $selected))
                  $checked = true;
              else if(count($selected) == 0)
                  $checked = $option->isSelected();

              // make field ID for Javascript
              $fieldId = ($i == 0) ? $id : $id."[$i]";

              $field = $builder->makeRadioField($id, $value, $access, $this->i18n, $checked);

              // put child field on the same line
              // if there is only one child field and no label for that field
              $isSingleLine = (count($childFields) == 1 && !is_object($option->getFormFieldLabel($childFields[0])));

              // get the HTML for the child field
              $childFieldHtml = "";
              if ($isSingleLine) {
                $childFieldHtml = $childFields[0]->toHtml();
                $childFieldId = $childFields[0]->getId();
              }

              // boolean cce values are only 0 or 1
              if (isset($checked)) {
                if ($checked != "") {
                  $theField = "1";
                  $theField_checked = " checked";
                } 
                else {
                  $theField = "0";
                  $theField_checked = "";
                }
              }

              // We have to make a bloody assumption here:
              // That our checkbox is the first option. It should be.
              // If so, we pull it's "access" settings and use them.
              if (isset($this->options['0']->formFields['0']->access)) {
                $access = $this->options['0']->formFields['0']->access;
              }

              $result .= "\n";

              // Make all the form fields of the options
              for($j = 0; $j < count($childFields); $j++) {
                  $childField =& $childFields[$j];
                  $childLabel =& $option->getFormFieldLabel($childField);

                  // Print out the children FormFields:
//print_rp($childField);
                  $childFieldHtml = $childField->toHtml();
                  $childFieldId = $childField->getId();

                  // Make sure the first Radio is always checked.
                  // And no: We don't care about setSelected() here.
                  if (!isset($ss)) {
                    $OPselected = " CHECKED";
                    $ss = TRUE;
                  }
                  else {
                    $OPselected = "";
                  }

                  // Hate to do this in a table, but the alternative would be CSS hacking:
                  $result .= '<TR>' . "\n";
                  $result .= '  <TD VALIGN="TOP"><INPUT TYPE="RADIO" NAME="locationField" VALUE="' . $childFieldId . '"'. $OPselected . '>' . "</TD>\n";
                  $result .= '  <TD VALIGN="TOP">' . $childFieldHtml . "</TD>\n";
                  $result .= '</TR>' . "\n";
              }
          }
        }

        $result .= '</TABLE>' . "\n";

        // Almost done. One more DIV to close:
        $result .= '</div></fieldset>' . "\n" . '<!-- MultiChoice: End -->' . "\n";

        // make hidden field for array if it is checkbox mode
        if ($selectionMode == "checkbox") {
            $checkboxesJavascript = "";
            for($i = 0; $i < count($options); $i++) {
	            // link checkboxes to hidden value
	            $fieldId = (count($options) == 1 && $i == 0) ? $id : $id."[$i]";
	            $checkboxesJavascript .= "element.checkboxes[element.checkboxes.length] = document.$formId.$fieldId;\n";
            }
        }
        $realId = "";
        $realId = $this->getId();
        $top_result = "\n" . '<!-- MultiChoice: Start -->' . "\n";
        $result .= "\n";

        $result = $top_result . $result;
        return $result;
    }

    function _toPullDown() {
        $page =& $this->getPage();
        $form =& $page->getForm();
        $formId = $form->getId();
        $options = $this->getOptions();

        $num = count($options);
        // Make read only if there's only one option
        // Actually, we don't want to do that anymore:
        // $access = $num > 1 ? $this->getAccess() : 'r';
        $access = 'rw';

        // get all option labels, values and see which one is selected
        $labels = array();
        $values = array();
        $selectedIndexes = array();
        for($i = 0; $i < $num; $i++) {
            $option =& $options[$i];

            $labelObj =& $option->getLabel();
            $labels[] = $labelObj->getLabel();
            $values[] = $option->getValue();

            // check for pre-exisiting user selection
            $id = $this->getId();
        }

        $builder = new FormFieldBuilder();

        // Check Class BXPage to see if we have a label and description for this FormField:
        if (is_array($this->page->getLabel($id))) {
          foreach ($this->page->getLabel($id) as $label => $description) {
            // We do? Tell FormFieldBuilder about it:
            $builder->setCurrentLabel($label);
            $builder->setDescription($description);
          }
        }
        else {
          // We have no label for this FormField:
          $builder->setCurrentLabel("");
          $builder->setDescription("");
        }

        if (count($selectedIndexes) == 0) {
            $selectedIndexes = array(0 => $this->ms_SelectedIndex);
        }

        // Tell FormFieldBuilder where the lable is:
        $builder->setLabelType($this->getLabelType());
        
        return $builder->makeSelectField($this->getId(), $access, $this->i18n, 1, $GLOBALS["_FormField_width"], false, $formId, "", $labels, $values, $selectedIndexes, $this->getOptional());
    }

    function _toScroll()
    {   
        $page =& $this->getPage();
        $form =& $page->getForm();
        $formId = $form->getId();
        $options = $this->getOptions();

        $num = count($options);
        // Make read only if there's only one option
        $access = $num > 1 ? $this->getAccess() : 'r';

        // get all option labels, values and see which one is selected
        $labels = array();
        $values = array();
        $selectedIndexes = array();
        for($i = 0; $i < $num; $i++)
        {   
            $option =& $options[$i];

            $labelObj =& $option->getLabel();
            $labels[] = $labelObj->getLabel();
            $values[] = $option->getValue();

            // check for pre-exisiting user selection
            $id = $this->getId();
            global $HTTP_POST_VARS;
            if($this->isDataPreserved() && isset($HTTP_POST_VARS[$id]) && ($values[$i] == $HTTP_POST_VARS[$id]))
            {   
                $selectedIndexes[] = $i;
            }
            else if($option->isSelected())
            {   
                $selectedIndexes[] = $i;
            }
        }

        $builder = new FormFieldBuilder();

        // Check Class BXPage to see if we have a label and description for this FormField:
        if (is_array($this->page->getLabel($id))) {
          foreach ($this->page->getLabel($id) as $label => $description) {
            // We do? Tell FormFieldBuilder about it:
            $builder->setCurrentLabel($label);
            $builder->setDescription($description);
          }
        }
        else {
          // We have no label for this FormField:
          $builder->setCurrentLabel("");
          $builder->setDescription("");
        }

        if (count($selectedIndexes) == 0)
            $selectedIndexes[0] = 0;

        // Tell FormFieldBuilder where the lable is:
        $builder->setLabelType($this->getLabelType());

        $out = $builder->makeSelectField($this->getId().'[]', $access, $this->row, $GLOBALS["_FormField_width"], true, $formId, "", $labels, $values, $selectedIndexes, $this->getOptional());
        return $out;
    }
}

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