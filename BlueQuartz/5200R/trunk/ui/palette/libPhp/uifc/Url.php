<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Url.php 995 2007-05-05 07:44:27Z shibuya $

global $isUrlDefined;
if($isUrlDefined)
  return;
$isUrlDefined = true;

include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

class Url extends FormField {
  //
  // private variables
  //

  var $label;
  var $target;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this form field lives in
  // param: id: the identifier of this object
  // param: value: the URL
  // param: label: a label in string. Optional
  // param: target: the TARGET attribute of the A tag. Optional
  // param: invalidMessage: message to be shown upon invalid input. Optional
  // param: emptyMessage: message to be shown upon empty input
  //     if the field is not optional. This message is optional
  function Url(&$page, $id, $value, $label = "", $target = "", $invalidMessage = "", $emptyMessage = "") {
    // superclass constructor
    $this->FormField($page, $id, $value, $invalidMessage, $emptyMessage);

    // we would normally need to add data preservation for targets 
    // and labels. But those are only used in a read-only state
    // and there is no changed data to preserve in a read-only state,
    // so we don't need to do anything
    $this->setLabel($label);
    $this->setTarget($target);
  }

  function getCollatableValue() {
    $label = $this->getLabel();
    return ($label != "") ? $label : $this->getValue();
  }

  // description: get the label
  // returns: a label in string
  // see: setLabel()
  function getLabel() {
    return $this->label;
  }

  // description: set the label
  // param: label: a label in string
  // see: getLabel()
  function setLabel($label) {
    $this->label = $label;
  }

  // description: get the target
  // returns: the TARGET attribute of the A tag
  // see: setTarget()
  function getTarget() {
    return $this->target;
  }

  // description: set the target
  // param: target: the TARGET attribute of the A tag
  // see: getTarget()
  function setTarget($target) {
    $this->target = $target;
  }

  function toHtml($style = "") {
    $access = $this->getAccess();
    $id = $this->getId();
    $value = $this->getValue();

    if($access == "r") {
      $label = $this->getLabel();
      $target = $this->getTarget();

      if($label == "")
	$label = $value;

      if($target != "")
	$target = "TARGET=\"$target\"";

      // HTML safe
      $label = htmlspecialchars($label);

      return "<A HREF=\"$value\" $target>$label</A>";
    }
    else {
      $builder = new FormFieldBuilder();
      $formField .= $builder->makeTextField($id, $value, $access, $GLOBALS["_FormField_width_big"], "", $GLOBALS["_FormField_change"]);
      $formField .= $builder->makeJavaScript($this, "top.code.Url_changeHandler", $GLOBALS["_FormField_TextField_submit"]);

      return $formField;
    }
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
