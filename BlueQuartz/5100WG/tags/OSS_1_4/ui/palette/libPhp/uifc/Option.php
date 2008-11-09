<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Option.php 3 2003-07-17 15:19:15Z will $

// description:
// This class represents an option for the MultiChoice class.
//
// applicability:
// Use it where MultiChoice is used.

global $isOptionDefined;
if($isOptionDefined)
  return;
$isOptionDefined = true;

class Option {
  //
  // private variables
  //

  var $formFields;
  var $formFieldLabels;
  var $label;
  var $isSelected;
  var $value;

  //
  // public methods
  //

  // description: constructor
  // param: label: a Label object
  // param: value: the value of this option
  // param: isSelected: true if selected, false otherwise
  //     Optional and false by default
  function Option(&$label, $value, $isSelected = false) {
    $this->setLabel($label);
    $this->setValue($value);
    $this->setSelected($isSelected);

    $this->formFields = array();
    $this->formFieldLabels = array();
  }

  // description: get the label
  // returns: a Label object
  // see: setLabel()
  function &getLabel() {
    return $this->label;
  }

  // description: set the label
  // param: a Label object
  // see: getLabel()
  function setLabel(&$label) {
    $this->label =& $label;
  }

  // description: see if the option is selected
  // returns: true if selected, false otherwise
  // see: setSelected()
  function isSelected() {
    return $this->isSelected;
  }

  // description: select/unselect the option
  // param: isSelected: true to select, false to unselect
  // see: isSelected()
  function setSelected($isSelected) {
    $this->isSelected = $isSelected;
  }

  // description: get the value
  // returns: a string
  // see: setValue()
  function getValue() {
    return $this->value;
  }

  // description: set the value
  // param: value: a string
  // see: getValue()
  function setValue($value) {
    $this->value = $value;
  }

  // description: get all the form fields of the block
  // returns: an array of FormField objects
  function getFormFields() {
    return $this->formFields;
  }

  // description: get the label for a form field
  // param: formField: a FormField object
  // returns: a Label object
  function &getFormFieldLabel(&$formField) {
    return $this->formFieldLabels[$formField->getId()];
  }

  // description: add a form field to this option,
  //     so this option can associate with another form field
  // param: formField: a FormField object
  // param: label: a Label object. Optional
  function addFormField(&$formField, $label = "") {
    $this->formFields[] =& $formField;
    $this->formFieldLabels[$formField->getId()] = $label;
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

