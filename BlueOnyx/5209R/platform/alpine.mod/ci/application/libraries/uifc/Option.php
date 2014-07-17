<?php
// Author: Kevin K.M. Chiu
// $Id: Option.php

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
  function Option(&$label, $value, $isSelected = false, $i18n) {
    $this->setLabel($label);
    $this->setValue($value);
    $this->access = "rw";
    $this->setSelected($isSelected);

    $this->i18n = $i18n;

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

  // description: sets the access of this formfield
  // param: access level ('r', 'rw', etc.)
  function setAccess($access) {
    $this->access = $access;
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
    $formField->access = $this->access;
    $this->formFields[] =& $formField;
    $this->formFieldLabels[$formField->getId()] = $label;
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