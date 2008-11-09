<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Number.php 3 2003-07-17 15:19:15Z will $

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

include("uifc/FormField.php");
include("uifc/FormFieldBuilder.php");

class Number extends FormField {
  //
  // private variables
  //

  var $max;
  var $min;
  // the internally used decimal separator
  var $DECIMAL_SEPARATOR = ".";

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this form field lives in
  // param: id: the identifier of this object
  // param: value: the default value
  // param: invalidMessage: message to be shown upon invalid input. Optional
  // param: emptyMessage: message to be shown upon empty input
  //     if the field is not optional. This message is optional
  function Number(&$page, $id, $value, $invalidMessage, $emptyMessage = "") {
    // superclass constructor
    $this->FormField($page, $id, $value, $invalidMessage, $emptyMessage);

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

    // no need to do any i18n conversion for hidden access
    if($access != "")
        $value = $this->localizeNumber($value);

    $builder = new FormFieldBuilder();
    $formField .= $builder->makeTextField($id, $value, $access, $GLOBALS["FormField_width"], 0, $GLOBALS["FormField_change"]);
    $formField .= $builder->makeJavaScript($this, "top.code.Number_changeHandler", "top.code.Number_submitHandler");

    $form =& $page->getForm();
    $formId = $form->getId();
    $formField .= "
<SCRIPT LANGUAGE=\"javascript\">
var element = document.$formId.$id;
element.max = $this->max;
element.min = $this->min;
element.decimalSeparator = \"$decimal\";
element.internalDecimalSeparator = \"$this->DECIMAL_SEPARATOR\";
element.thousandsSeparator = \"$thousands\";
</SCRIPT>
";

    return $formField;
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

