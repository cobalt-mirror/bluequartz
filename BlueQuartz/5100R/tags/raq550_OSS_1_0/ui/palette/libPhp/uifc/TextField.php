<?php
// Author: Harris Vaegan-LLoyd
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: TextField.php 259 2004-01-03 06:28:40Z shibuya $

global $isTextFieldDefined;
if($isTextFieldDefined)
  return;
$isTextFieldDefined = true;

include("uifc/FormField.php");
include("uifc/FormFieldBuilder.php");

class TextField extends FormField {
  //
  // public methods
  //

  var $size, $maxLength;

  // description: constructor
  // param: page: the Page object this form field lives in
  // param: id: the identifier of this object
  // param: value: the default value
  // param: invalidMessage: message to be shown upon invalid input. Optional
  // param: emptyMessage: message to be shown upon empty input
  //     if the field is not optional. This message is optional
  function TextField(&$page, $id, $value, $invalidMessage = "", $emptyMessage = "") {
    // superclass constructor
    $this->FormField($page, $id, $value, $invalidMessage, $emptyMessage);

    $this->size = $GLOBALS["_FormField_width"];
    $this->maxLength = 0;
  }

  // Deprecated. Use setWidth
  function setSize($size) {
    $this->setWidth($size);
  }

  // description: set the size or number of columns
  // param: size: an integer
  function setWidth($size) {
        $this->size = $size;
  }

  // returns an integer representing the current width setting or number of columns
  function getWidth() {
        return($this->size);
  }

  // description: set the maximum length or characters the field can take
  // param: len: an integer
  function setMaxLength($len) {
    $this->maxLength = $len;
  }

  function toHtml($style = "") {
    $id = $this->getId();

    $builder = new FormFieldBuilder();
    $formField = $builder->makeTextField($id, $this->getValue(), $this->getAccess(), $this->size, $this->maxLength, $GLOBALS["_FormField_change"]);
    $formField .= $builder->makeJavaScript($this, "", $GLOBALS["_FormField_TextField_submit"]);

    return $formField;
  }
}/*
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
