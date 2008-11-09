<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: TextBlock.php 237 2003-09-10 08:22:45Z shibuya $

global $isTextBlockDefined;
if($isTextBlockDefined)
  return;
$isTextBlockDefined = true;

include("uifc/FormField.php");
include("uifc/FormFieldBuilder.php");

class TextBlock extends FormField {
  //
  // private variables
  //

  var $height;
  var $width;
  var $wrap;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object that this object lives in
  // param: id: the identifier of the object
  // param: value: a text string. Optional
  // param: emptyMessage: message to be shown upon empty input
  //     if the field is not optional. This message is optional
  function TextBlock(&$page, $id, $value = "", $emptyMessage = "") {
    // superclass constructor
    $this->FormField($page, $id, $value, "", $emptyMessage);

    $this->height = $GLOBALS["_FormField_height"];
    $this->width = $GLOBALS["_FormField_width"];
    $this->wrap = false;
  }

  // description: get the height or number of rows
  // returns: an integer
  // see: setHeight()
  function getHeight() {
    return $this->height;
  }

  // description: set the height or number of rows
  // param: height: an integer
  // see: getHeight()
  function setHeight($height) {
    $this->height = $height;
  }

  // description: get the width of or number of columns
  // returns: an integer
  // see: setWidth()
  function getWidth() {
    return $this->width;
  }

  // description: set the width of or number of columns
  // param: width: an integer
  // see: getWidth()
  function setWidth($width) {
    $this->width = $width;
  }

  // description: set to/not to wrap text
  // param: val: true to wrap, false otherwise
  // see: isWrap()
  function setWrap($val = false) {
    $this->wrap = $val;
  }

  // description: see if text should be wrapped or not
  // returns: true to wrap, false otherwise
  // see: setWrap()
  function isWrap() {
    return $this->wrap;
  }

  function toHtml($style = "") {
    $builder = new FormFieldBuilder();
    $formField = $builder->makeTextAreaField($this->getId(), $this->getValue(), $this->getAccess(), $this->height, $this->width, "", $this->wrap ? "on" : "off");
    $formField .= $builder->makeJavaScript($this, "", $GLOBALS["_FormField_TextField_submit"]);

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
