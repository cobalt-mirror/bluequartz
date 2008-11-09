<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: MultiChoice.php 201 2003-07-18 19:11:07Z will $

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
  var $options;
  var $submitHandler;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this form field lives in
  // param: id: the identifier of this object
  function MultiChoice(&$page, $id, $submitHandler="top.code.MultiChoice_submitHandlerOption") {
    // superclass constructor
    $this->FormField($page, $id);

    $this->fullSize = false;
    $this->multiple = false;
    $this->options = array();
    $this->submitHandler=$submitHandler;
  }

  function &getDefaultStyle(&$stylist) {
    return $stylist->getStyle("MultiChoice");
  }

  // description: get all options added
  // returns: an array of Option objects
  // see: addOption()
  function getOptions() {
    return $this->options;
  }

  // description: add an option
  //     options are not selected by default when they are added
  // param: option: an Option object
  // see: getOptions()
  function addOption(&$option, $selected = "__undef") 
  {
    $index = count($this->options);
    $this->options[$index] =& $option;
    if ($selected === "__undef") 
    {
        $this->setSelected($index, $option->isSelected());
    } 
    else 
    {
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

  // description: select a option
  // param: index: an integer index of the option
  // param: isSelected: true for selected, false otherwise.
  //     Optional and true by default
  // returns: nothing
  function setSelected($index, $isSelected = true) {
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
    if(!is_object($options[$index]))
      return;

    // make the values array with all the select values
    $values = array();
    for($i = 0; $i < count($options); $i++) {
      $option =& $options[$i];

      if(($i != $index && $option->isSelected()) || ($i == $index && $isSelected))
	$values[] = $option->getValue();
    }

    // set value
    $this->setValue(arrayToString($values));
  }

  function setValue($value) {
    $values = stringtoArray($value);
    
    if ($this->isMultiple()) {
      $this->value = arrayToString($values);
    } else {
      $this->value = $values[0];
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
    for($i = 0; $i < count($options); $i++)
    {
        if(count($options[$i]->getFormFields()) > 0) 
        {
	        $noComposite = false;
	        break;
        }
    }

    if(!$this->isFullSize() && !$this->isMultiple() && $noComposite)
        return $this->_toPullDown();
    else if($this->isMultiple() || count($options) == 1)
        return $this->_toRows("checkbox", $style);
    else
        return $this->_toRows("radio", $style);
  }

    //
    // private methods
    //

    function _toRows($selectionMode, &$style) 
    {
        $choiceLabelStyle = $style->getSubstyle("choiceLabel");
        $fieldGrouperStyleStr = $style->toBackgroundStyle("fieldGrouper");
        $formFieldLabelStyle = $style->getSubstyle("formFieldLabel");
        $subscriptStyleStr = $style->toTextStyle("subscript");
        $choiceLabelTextStyle = $style->toTextStyle("choiceLabel");

        $builder = new FormFieldBuilder();

        $id = $this->getId();
        // check for user data
        global $$id;
        $selected = array();
//        if($this->isDataPreserved() && isset($$id))
//            $selected = stringToArray($$id);

        // use an internal ID if it is checkbox mode and
        if($selectionMode == "checkbox")
            $id = "_MultiChoice_checkbox_$id";

        $access = $this->getAccess();
        $options = $this->getOptions();

        $page =& $this->getPage();
        $form =& $page->getForm();
        $formId = $form->getId();
        $i18n =& $page->getI18n();
        $optionalStr = $i18n->get("optional", "palette");

        // make sure alignment is good
        $result = "<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\">\n";

        // make all the options
        for($i = 0; $i < count($options); $i++) 
        {
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

            // use checkbox or radio button based on selection mode
            if($selectionMode == "checkbox")
	            $field = $builder->makeCheckboxField($id, $value, $access, $checked);
            else if($selectionMode == "radio")
	            $field = $builder->makeRadioField($id, $value, $access, $checked);

            // put child field on the same line
            // if there is only one child field and no label for that field
            $isSingleLine = (count($childFields) == 1 && !is_object($option->getFormFieldLabel($childFields[0])));

            // get the HTML and Javascript for the child field
            $childFieldHtml = "";
            $childFieldJavascript = "";
            if($isSingleLine) 
            {
	            $childFieldHtml = $childFields[0]->toHtml();
                $childFieldId = $childFields[0]->getId();
                $childFieldJavascript = "element.childFields[element.childFields.length] = document.$formId.$childFieldId;";
            }

            $result .= "
<TR>
  <TD VALIGN=\"TOP\">$field</TD>
  <TD>$labelHtml<IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">$childFieldHtml</TD>
</TR>
<SCRIPT LANGUAGE=\"javascript\">
var element = document.$formId.$fieldId;
element.childFields = new Array();
element.submitHandler = $this->submitHandler;
$childFieldJavascript
</SCRIPT>
";

            // put child fields on different lines
            // if there are more than 1 of them or if the first one has label
            if(!$isSingleLine && count($childFields) > 0) 
            {
	            $result .= "
<TR>
  <TD><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\"></TD>
  <TD>
    <TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\">";

	            // make all the form fields of the options
	            for($j = 0; $j < count($childFields); $j++) 
                {
	                $childField =& $childFields[$j];
	                $childFieldHtml = $childField->toHtml();
	                $childFieldId = $childField->getId();

                    if ($childField->isOptional() && 
                            ($childField->isOptional() !== 'silent'))
                    {
                        $optional = "<FONT STYLE=\"$subscriptStyleStr\">($optionalStr)</FONT>";
                    }
                    else
		                $optional = "";

	                $childLabel =& $option->getFormFieldLabel($childField);
	                if(is_object($childLabel))
                    {
	                    $childLabelHtml = "
	<TD>" . $childLabel->toHtml($formFieldLabelStyle) . " $optional</TD>
	<TD><IMG SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\" HEIGHT=\"5\"></TD>
";
                        // since there is a label, the optional string has
                        // been placed after it. blank out optional so
                        // it isn't used again below
                        $optional = "";
                    }
                    
	                $result .= "
      <TR>
	<TD STYLE=\"$fieldGrouperStyleStr\"><IMG SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"1\"></TD>
	<TD><IMG SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\" HEIGHT=\"5\"></TD>
$childLabelHtml
	<TD><div style=\"$choiceLabelTextStyle\">$childFieldHtml $optional</div></TD>
      </TR>
<SCRIPT LANGUAGE=\"javascript\">
var element = document.$formId.$fieldId;
element.childFields[element.childFields.length] = document.$formId.$childFieldId;
</SCRIPT>
";
	            }

	            $result .= "
    </TABLE>
  </TD>
</TR>
";
            }
        }

        $result .= "</TABLE>\n";

        // make hidden field for array if it is checkbox mode
        if($selectionMode == "checkbox") 
        {
            $checkboxesJavascript = "";
            for($i = 0; $i < count($options); $i++) 
            {
	            // link checkboxes to hidden value
	            $fieldId = (count($options) == 1 && $i == 0) ? $id : $id."[$i]";
	            $checkboxesJavascript .= "element.checkboxes[element.checkboxes.length] = document.$formId.$fieldId;\n";
            }

            $realId = $this->getId();
            $result .= $builder->makeHiddenField($realId);
            $result .= "
<SCRIPT LANGUAGE=\"javascript\">
var element = document.$formId.$realId;
element.checkboxes = new Array();
$checkboxesJavascript
element.submitHandler = top.code.MultiChoice_submitHandler;
</SCRIPT>
";
        }

        return $result;
    }

    function _toPullDown() 
    {
        $page =& $this->getPage();
        $form =& $page->getForm();
        $formId = $form->getId();
        $options = $this->getOptions();

        // get all option labels, values and see which one is selected
        $labels = array();
        $values = array();
        $selectedIndexes = array();
        for($i = 0; $i < count($options); $i++) 
        {
            $option =& $options[$i];

            $labelObj =& $option->getLabel();
            $labels[] = $labelObj->getLabel();
            $values[] = $option->getValue();

            // check for pre-exisiting user selection
            $id = $this->getId();
            global $$id;
            if($this->isDataPreserved() && isset($$id) && ($values[$i] == $$id))
            {
                $selectedIndexes[] = $i;
            }
            else if(!isset($$id) && $option->isSelected())
            {
                $selectedIndexes[] = $i;
            }
        }

        $builder = new FormFieldBuilder();
        return $builder->makeSelectField($this->getId(), $this->getAccess(), 1, $GLOBALS["_FormField_width"], false, $formId, "", $labels, $values, $selectedIndexes);
    }
}
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

