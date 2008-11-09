<?php
// Author: Kevin K.M. Chiu
// Copyright 2001, Cobalt Networks.  All rights reserved.
// $Id: Block.php 259 2004-01-03 06:28:40Z shibuya $

global $isBlockDefined;
if($isBlockDefined)
  return;
$isBlockDefined = true;

include("uifc/HtmlComponent.php");

// abstract
class Block extends HtmlComponent {
  //
  // private variables
  //

  var $buttons;
  var $label;
  var $width;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this block is in
  // param: label: a Label object for the block title. Optional
  function Block($page, $label = "") {
    // superclass constructor
    $this->HtmlComponent($page);

    $this->setLabel($label);

    $this->buttons = array();
    $this->width = -1;
  }

  // description: get all buttons added to the block
  // returns: an array of Button objects
  // see: addButton()
  function getButtons() {
    return $this->buttons;
  }

  // description: add a button to the block
  // param: button: a Button object
  // see: getButtons()
  function addButton($button) {
    $this->buttons[] = $button;
  }

  // description: set the width of the block
  // param: width: the width of the block in pixels 
  // see: getWidth()
  function setWidth($width) {
    $this->width = $width;
  }

  // description: get the width of the block in pixels
  // returns: the width of the block in pixels 
  // see: setWidth()
  function getWidth() {
    return $this->width;
  }

  // description: get the label of the block
  // returns: a Label object
  // see: setLabel()
  function getLabel() {
    return $this->label;
  }

  // description: set the label of the block
  // param: label: a Label object
  // see: getLabel()
  function setLabel($label) {
    $this->label = $label;
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
