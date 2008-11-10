<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: FormFieldBuilder.php 1184 2008-09-10 21:23:19Z mstauber $

// description:
// This class helps to build form field components.
//
// applicability:
// Any form field can use this class to build components.

global $isFormFieldBuilderDefined;
if($isFormFieldBuilderDefined)
  return;
$isFormFieldBuilderDefined = true;

include_once("System.php");
include_once("ArrayPacker.php");

class FormFieldBuilder {
  //
  // public methods
  //

  // description: make a checkbox field
  // param: id: the identifier of the field
  // param: value: the value of the HTML input field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: checked: if it has a value checked, false otherwise
  // param: onClick: the onClick attribute of the field
  // returns: HTML that represents the field
  function makeCheckboxField($id, $value, $access, $checked, $onClick = "") {
    switch($access) {
      case "":
	return $this->makeHiddenField($id, $value);

      case "r":

       // Need to get the style info for the read-only version      
       // it hurts  to get a new helper obj, but we have no choice
        include_once("ServerScriptHelper.php");
        $helper = new ServerScriptHelper();
        $stylist = $helper->getStylist("CheckBox");   
        $style = $stylist->getStyle();   

	if($checked) {
	  return ("<IMG SRC=\"" .   $style->getProperty("image", "checked") . "\">" 
	                . $this->makeHiddenField($id, $value));
	} else {
	  return ("<IMG SRC=\"" .   $style->getProperty("image", "unchecked") . "\">");
        }
      case "w":
	$checked = "";
	break;

      case "rw":
	$checked = $checked ? "CHECKED" : "";
	break;
    }

    // log activity if necessary
    $system = new System();
    $logClick = ($system->getConfig("logPath") != "") ? "top.code.uiLog_log('click', 'FormField', '$id', this.checked);" : "";

    // find onClick handler
    if($onClick != "" || $logClick != "")
      $onClick = "onClick=\"$logClick $onClick\"";

    // HTML safe
    $value = htmlspecialchars($value);

    return "<INPUT TYPE=\"CHECKBOX\" NAME=\"$id\" VALUE=\"$value\" $checked $onClick>\n";
  }

  // description: make a file upload field
  // param: id: the identifier of the field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: size: the length of the field
  // param: maxLength: maximum number of characters
  //     that can be entered into the field
  // param: onChange: the onChange attribute of the field
  // returns: HTML that represents the field
  function makeFileUploadField($id, $access, $size, $maxLength, $onChange) {
    if($access == "" || $access == "r")
      return $this->makeHiddenField($id, "");

    // find size
    $size = ($size > 0) ? "SIZE=\"$size\"" : "";

    // find max size
    $maxLength = ($maxLength > 0) ? "MAXLENGTH=\"$maxLength\"" : "";

    // log activity if necessary
    $system = new System();
    $logChange = ($system->getConfig("logPath") != "") ? "top.code.uiLog_log('change', 'FormField', '$id', this.value);" : "";

    // find onChange handler
    if($onChange != "" || $logChange != "")
      $onChange = "onChange=\"$logChange $onChange\"";

    return "<INPUT TYPE=\"FILE\" NAME=\"$id\" $size $maxLength $onChange>\n";
  }

  // description: make a hidden field
  // param: id: the identifier of the field
  // param: value: the value of the HTML input field
  // returns: HTML that represents the field
  function makeHiddenField($id, $value = "") {
    // HTML safe
    $value = htmlspecialchars($value);

    return "<INPUT TYPE=\"HIDDEN\" NAME=\"$id\" VALUE=\"$value\">\n";
  }

  // description: make javascript for form fields
  // param: formField: the form field to generate javascript for
  // param: changeHandler: the Javascript function
  //     that is called when the form field change
  // param: submitHandler: the Javascript function
  //     that is called when the form field submits
  // returns: HTML that represents the field
  function makeJavaScript($formField, $changeHandler, $submitHandler) {
    $access = $formField->getAccess();
    if($access != "w" && $access != "rw")
      return "";

    $emptyMessage = $formField->getEmptyMessage();
    $invalidMessage = $formField->getInvalidMessage();
    $id = $formField->getId();
    $page = $formField->getPage();
    $form = $page->getForm();
    $formId = $form->getId();
    $isOptional = $formField->isOptional() ? "true" : "false";

    $javascript .= "
<SCRIPT LANGUAGE=\"javascript\">
var element = document.$formId.$id;
";

if($changeHandler != "")
    $javascript .= "element.changeHandler = $changeHandler;\n";

if($submitHandler != "")
    $javascript .= "element.submitHandler = $submitHandler;\n";

if($invalidMessage != "")
    $javascript .= "element.invalidMessage = \"$invalidMessage\";\n";

if($emptyMessage != "")
    $javascript .= "element.emptyMessage = \"$emptyMessage\";\n";

    $javascript .= "
element.isOptional = $isOptional;
</SCRIPT>
";

    return $javascript;
  }

  // description: make a password field
  // param: id: the identifier of the field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: size: the length of the field
  // param: onChange: the onChange attribute of the field
  // returns: HTML that represents the field
  function makePasswordField($id, $value, $access, $size, $onChange) {
    if($access == "" || $access == "r")
      return $this->makeHiddenField($id, "");

    // find size
    $size = ($size > 0) ? "SIZE=\"$size\"" : "";

    // log activity if necessary
    $system = new System();
    $logChange = ($system->getConfig("logPath") != "") ? "top.code.uiLog_log('change', 'FormField', '$id');" : "";

    // find onChange handler
    if($onChange != "" || $logChange != "")
      $onChange = "onChange=\"$logChange $onChange\"";

    // HTML safe
    $value = htmlspecialchars($value);

    // Secure Password stuff - activated on onKeyUp:    
    $onKeyUp = 'onKeyUp="validate_password(this.value)"';

    return "<INPUT id=\"pass\" TYPE=\"PASSWORD\" NAME=\"$id\" VALUE=\"$value\" $size $onKeyUp $onChange>\n";
  }

  // description: make a radio field
  // param: id: the identifier of the field
  // param: value: the value of the HTML input field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: checked: true if it is checked, false otherwise
  // returns: HTML that represents the field
  function makeRadioField($id, $value, $access, $checked) {
    switch($access) {
      case "":
	return $this->makeHiddenField($id, $value);

      case "r":
	if($checked)
	  return "O ".$this->makeHiddenField($id, $value);
	else
	  return "X";

      case "w":
	$checked = "";
	break;

      case "rw":
	$checked = $checked ? "CHECKED" : "";
	break;
    }

    // HTML safe
    $value = htmlspecialchars($value);

    // log activity if necessary
    $system = new System();
    $logClick = ($system->getConfig("logPath") != "") ? "onClick=\"top.code.uiLog_log('click', 'FormField', '$id', this.value);\"" : "";

    return "<INPUT TYPE=\"RADIO\" NAME=\"$id\" VALUE=\"$value\" $checked $logClick>\n";
  }

  // description: make a select field
  // param: id: the identifier of the field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: size: the SIZE attribute of the HTML SELECT tag
  // param: width: the minimum width
  //     Select field width is static in Netscape, dynamic in IE 
  // param: isMultiple: true if multiple items can be selected, false otherwise
  // param: formId: the ID of the form this field lives in
  // param: onChange: the onChange attribute of the field. Optional.
  // param: labels: an array of labels in string. Optional.
  //     Must have same length with values
  // param: values: an array of values in string. Optional.
  //     Must have same length with labels
  // param: selectedIndexes: an array of indexes of labels for the selected
  // returns: HTML that represents the field
  function makeSelectField($id, $access, $size, $width, $isMultiple, $formId, $onChange = "", $labels = array(), $values = array(), $selectedIndexes = array()) {
    switch($access) {
      case "":
	if(!$isMultiple)
	  return $this->makeHiddenField($id, $values[$selectedIndexes[0]]);

	$result = "";
	for($i = 0; $i < count($selectedIndexes); $i++)
	  $result .= $this->makeHiddenField($id, $values[$selectedIndexes[$i]]);
	return $result;

      case "r":
	if(!$isMultiple)
	  // HTML safe
	  return htmlspecialchars($labels[$selectedIndexes[0]]).$this->makeHiddenField($id, $values[$selectedIndexes[0]]);

	$result = "";
	for($i = 0; $i < count($selectedIndexes); $i++)
	  // HTML safe
	  $result .= htmlspecialchars($labels[$selectedIndexes[$i]]).$this->makeHiddenField($id, $values[$selectedIndexes[$i]]);
	return $result;

      // impossible case
      case "w":

      case "rw":
	$multiple = ($isMultiple) ? "MULTIPLE" : "";

	// log activity if necessary
	$system = new System();
	// log value if only one option can be selected
	$value = !$isMultiple ? ", this.options[this.selectedIndex].value" : "";
	$logChange = ($system->getConfig("logPath") != "") ? "top.code.uiLog_log('change', 'FormField', '$id' $value);" : "";

	$onChange = ($onChange != "" || $logChange != "") ? "onChange=\"$logChange $onChange\"" : "";

	$result = "<SELECT $multiple NAME=\"$id\" ID=\"$id\" $onChange SIZE=\"$size\">\n";

	for($i = 0; $i < count($labels); $i++) {
	  $label = $labels[$i];
	  $value = $values[$i];

	  $selected = (in_array($i, $selectedIndexes)) ? "SELECTED" : "";

	  // HTML safe
	  $label = htmlspecialchars($label);
	  $value = htmlspecialchars($value);

	  $result .= "<OPTION VALUE=\"$value\" $selected>$label\n";
	}

	// add spacer
	$result .= "<OPTION>";
	for($i = 0; $i < $width; $i++)
	  $result .= "_";

	// do not put any new lines here because fields that use this code may
	// want no line breaks to be shown on screen
	$result .= "</SELECT>";

	// clean up
	$optionNum = count($labels);
	$result .= "
<SCRIPT LANGUAGE=\"javascript\">
document.$formId.$id.options.length = $optionNum;
</SCRIPT>";

	return $result;
    }
  }

  // description: make a text field
  // param: id: the identifier of the field
  // param: value: the value of the HTML input field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: size: the length of the field
  // param: maxLength: maximum number of characters
  //     that can be entered into the field
  // param: onChange: the onChange attribute of the field
  // returns: HTML that represents the field
  function makeTextField($id, $value, $access, $size, $maxLength, $onChange) {
    $shortval = $value;
    if (($maxLength > 0) && (strlen($value) > $maxLength)) {
	$shortval = substr($value, 0, $maxLength) . ' ...';
    }

    switch($access) {
      case "":
	return $this->makeHiddenField($id, $value);

      case "r":
	// HTML safe
	$shortval = htmlspecialchars($shortval);

	return $shortval . $this->makeHiddenField($id, $value);

      case "R":
	// assume $shortval is already html-safe
	return $shortval . $this->makeHiddenField($id, $value);

      case "w":
	$value = "";
	break;

      case "rw":
	// HTML safe
	$value = htmlspecialchars($value);

	$value = "VALUE=\"$value\"";
	break;
    }

    // log activity if necessary
    $system = new System();
    $logChange = ($system->getConfig("logPath") != "") ? "top.code.uiLog_log('change', 'FormField', '$id', this.value);" : "";

    // find size
    $size = ($size > 0) ? "SIZE=\"$size\"" : "";

    // find max size
    $maxLength = ($maxLength > 0) ? "MAXLENGTH=\"$maxLength\"" : "";

    // find onChange handler
    if($onChange != "" || $logChange != "")
      $onChange = "onChange=\"$logChange $onChange\"";

    return "<INPUT TYPE=\"TEXT\" NAME=\"$id\" $value $size $maxLength $onChange>\n";
  }

  // description: make a text area field
  // param: id: the identifier of the field
  // param: value: the value of the HTML input field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: rows: the number of rows
  // param: columns: the number of columns
  // param: onChange: the onChange attribute of the field
  // param: wrap: "on", "hard" or "off". "on" means word wrapping occurs on
  //     boundaries. "hard" means wrapping points are converted to CR-LF in
  //     the value submitted. "off" means no wrapping. Optional and "off" by
  //     default.
  // returns: HTML that represents the field
  function makeTextAreaField($id, $value, $access, $rows, $columns, $onChange, $wrap = "off") {
    // log activity if necessary
    $system = new System();
    $logChange = ($system->getConfig("logPath") != "") ? "top.code.uiLog_log('change', 'FormField', '$id', this.value);" : "";

    // find onChange handler
    if($onChange != "" || $logChange != "")
      $onChange = "onChange=\"$logChange $onChange\"";

    switch($access) {
      case "":
	return $this->makeHiddenField($id, $value);

      case "r":
	// HTML safe
	$value = htmlspecialchars($value);

	// preserve line breaks
	$value = preg_replace("/\r?\n/", "<BR>", $value);

	// if no wrap
	if($wrap == "off")
	  $value = "<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR><TD NOWRAP>$value</TD></TR></TABLE>";

	return $value;

      case "w":
	$value = "";
	break;

      case "rw":
	break;
    }

    return "<TEXTAREA NAME=\"$id\" ROWS=\"$rows\" COLS=\"$columns\" $onChange WRAP=\"$wrap\">$value</TEXTAREA>\n";
  }

  // description: make a text list field
  // param: id: the identifier of the field
  // param: values: an array of values in string
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: formId: the identifier of the form this field lives in
  // param: rows: the number of rows
  // param: columns: the number of columns
  // returns: HTML that represents the field
  function makeTextListField($id, $values, $access, $formId, $rows, $columns) {
    $valueString = arrayToString($values);

    switch($access) {
      case "":
	return $this->makeHiddenField($id, $valueString);

      case "r":
	for($i = 0; $i < count($values); $i++) {
	  if($i > 0)
	    $result .= "<BR>";
	  // HTML safe
	  $result .= htmlspecialchars($values[$i]);
	}
	$result .= $this->makeHiddenField($id, $valueString);
	return $result;

      case "w":
	// clear off values
	$values = array();
	break;

      case "rw":
	break;
    }

    $textId = "_".$id."_textArea";
    $valueText = implode("\n", $values);

    // make text area field
    $text = $this->makeTextAreaField($textId, $valueText, $access, $rows, $columns, "top.code.textArea_reformat(document.$formId.$id.textArea); if(document.$formId.$id.changeHandler != null) document.$formId.$id.changeHandler(document.$formId.$id)");

    // make hidden field
    $hidden = $this->makeHiddenField($id, "");

    return "
$text
$hidden
<SCRIPT LANGUAGE=\"javascript\">
document.$formId.$id.textArea = document.$formId.$textId;
</SCRIPT>
";
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
