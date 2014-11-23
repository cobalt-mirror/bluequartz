<?php
// Author: Kevin K.M. Chiu
// $Id: Number.php

// description:
// This class represents a number which can have decimal separator and
// thousands separators.
//
// applicability:
// If what you really need is integer, use the Integer class. If floating point
// number is what you are looking for, use this class.
//
// usage:
// Internal values of this class does not have thousands separators and only
// use "." as decimal separators. When this class is represented, it
// automatically represent the value with thousands separators and decimal
// separators for the current locale.

global $isNumberDefined;
if($isNumberDefined)
  return;
$isNumberDefined = true;

include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

class Number extends FormField {
  //
  // private variables
  //

  var $max;
  var $min;
  // the internally used decimal separator
  var $DECIMAL_SEPARATOR = ".";
  var $Label, $Description;

  //
  // public methods
  //

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

  // description: constructor
  // param: page: the Page object this form field lives in
  // param: id: the identifier of this object
  // param: value: the default value
  // param: invalidMessage: message to be shown upon invalid input. Optional
  // param: emptyMessage: message to be shown upon empty input
  //     if the field is not optional. This message is optional
  function Number(&$page, $id, $value, $i18n, $invalidMessage, $emptyMessage = "") {

    // superclass constructor
    $this->HtmlComponent($page);

    // Set up $i18n:
    $this->i18n = $i18n;

    // superclass constructor
    $this->FormField($page, $id, $value, $this->i18n, "number", $invalidMessage, $emptyMessage);

    $this->max = pow(2, 105);
    $this->min = -pow(2, 105);
  }

  // description: get the maximum valid value
  // returns: a number
  // see: setMax()
  function getMax() {
    return $this->max;
  }

  // description: set the maximum valid value
  // param: max: a number
  // see: getMax()
  function setMax($max) {
    $this->max = $max;
  }

  // description: get the minimum valid value
  // returns: a number
  // see: setMin()
  function getMin() {
    return $this->min;
  }

  // description: set the minimum valid value
  // param: min: a number
  // see: getMin()
  function setMin($min) {
    $this->min = $min;
  }

  function localizeNumber($value) {
    // get separators from i18n properties
    $page =& $this->getPage();
    $i18n =& $page->getI18n();
    $thousands = $i18n->getProperty("thousandsSeparator", "palette");
    $decimal = $i18n->getProperty("decimalSeparator", "palette");

    // i18n thousands separators and decimal separators
    // get the location of the decimal separator
    $index = strrpos($value, $this->DECIMAL_SEPARATOR);
    if($index === false)
      $index = strlen($value);
    else
      // replace decimal point if necessary
      if($decimal != $this->DECIMAL_SEPARATOR)
        $value = substr_replace($value, $decimal, $index, strlen($this->DECIMAL_SEPARATOR));

    // add thousands separator
    // get everything after the decimal separator
    $newValue = substr($value, $index);
    // add chucks between thousands separators
    for($i = $index-3; $i > 0; $i -= 3)
      $newValue = $thousands . substr($value, $i, 3) . $newValue;
    // add the left-most chuck
    $newValue = substr($value, 0, $i+3) . $newValue;
    return $newValue;
  }

  function toHtml($style = "") {
    $id = $this->getId();
    $value = $this->getValue();
    $access = $this->getAccess();
    $page = $this->getPage();
    $i18n =& $page->getI18n();
    $checktype= "number";    
    $thousands = $i18n->getProperty("thousandsSeparator", "palette");
    $decimal = $i18n->getProperty("decimalSeparator", "palette");

    // no need to do any i18n conversion for hidden access
    if($access != "")
        $value = $this->localizeNumber($value);

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
    $formField = $builder->makeTextField($id, $value, $access, $i18n, $checktype, "", 0, "");

    $form =& $page->getForm();
    $formId = $form->getId();

    return $formField;
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