<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: CompositeFormField.php 3 2003-07-17 15:19:15Z will $

// description:
// This class is a container of FormField objects.
//
// applicability:
// This class is useful for putting in multiple form fields into places that
// accept one form field object.
//
// usage:
// Simple use addFormField() to add form fields into this object, then use
// toHtml() to get a HTML representation of it.

global $isCompositeFormFieldDefined;
if($isCompositeFormFieldDefined)
  return;
$isCompositeFormFieldDefined = true;

include("uifc/FormField.php");

class CompositeFormField extends FormField {
  //
  // private variables
  //

  var $delimiter;
  var $formFields;
  var $alignment;

  //
  // public methods
  //

  // description: constructor
  function CompositeFormField() {
    $this->formFields = array();

    // set default
    $this->delimiter = " ";
  }

  function getCollatableValue() {
    return $this->formFields[0]->getCollatableValue();
  }

  // description: get the delimiter to separate form fields
  // returns: a delimiter in string
  function getDelimiter() {
    return $this->delimiter;
  }

  // description: set the delimiter to separate form fields
  // param: delimiter: a delimiter in string
  function setDelimiter($delimiter) {
    $this->delimiter = $delimiter;
  }

  // description: get form fields added to this object
  // returns: an array of FormField object
  // see: addFormField()
  function getFormFields() {
    return $this->formFields;
  }

  // description: set vertical alignment of the horizontal row of form fields
  // param: "top", "middle", "bottom"
  function setAlignment($alignment) {
    $this->alignment = $alignment;
  }

  // description: add a form field to this object
  // param: formField: a FormField object
  // see: getFormFields()
  function addFormField($formField) {
    $this->formFields[] = $formField;
  }

  function toHtml($style = "") {
    if ($this->alignment) {
      $align_text = "VALIGN=" . $this->alignment;
    }

    $result .= "<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR $align_text>";

    $delimiter .= $this->getDelimiter();
    $formFields = $this->getFormFields();
    for($i = 0; $i < count($formFields); $i++) {
      if($i > 0)
	$result .= "<TD>$delimiter</TD>";
      $result .= "<TD><div class=\"nested-string\">".$formFields[$i]->toHtml($style)."</div></TD>";
    }

    $result .= "</TR></TABLE>";

    return $result;
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

