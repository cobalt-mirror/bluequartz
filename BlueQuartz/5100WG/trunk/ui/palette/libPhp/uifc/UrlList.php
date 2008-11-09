<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: UrlList.php 237 2003-09-10 08:22:45Z shibuya $

global $isUrlListDefined;
if($isUrlListDefined)
  return;
$isUrlListDefined = true;

include("ArrayPacker.php");
include("uifc/FormField.php");
include("uifc/FormFieldBuilder.php");

class UrlList extends FormField {
  //
  // private variables
  //

  var $labels;
  var $targets;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this form field lives in
  // param: id: the identifier of this object
  // param: value: an URL encoded list of URLs
  // param: labels: an array of label strings. Optional
  // param: targets: an array of target attributes for the A tag in strings.
  //     Optional
  // param: invalidMessage: message to be shown upon invalid input. Optional
  // param: emptyMessage: message to be shown upon empty input
  //     if the field is not optional. This message is optional
  function UrlList(&$page, $id, $value, $labels = array(), $targets = array(), $invalidMessage, $emptyMessage) {
    // superclass constructor
    $this->FormField($page, $id, $value, $invalidMessage, $emptyMessage);

    $this->setLabels($labels);
    $this->setTargets($targets);
  }

  // description: get the labels
  // returns: an array of label strings
  // see: setLabels()
  function getLabels() {
    return $this->labels;
  }

  // description: set the labels
  // param: labels: an array of label strings
  // see: getLabels()
  function setLabels($labels) {
    $this->labels = $labels;
  }

  // description: get the targets attributes
  // returns: an array of targets in strings
  // see: setTargets()
  function getTargets() {
    return $this->targets;
  }

  // description: set the targets attributes
  // param: labels: an array of targets in strings
  // see: getTargets()
  function setTargets($targets) {
    $this->targets = $targets;
  }

  function toHtml($style = "") {
    $access = $this->getAccess();
    $values = stringToArray($this->getValue());

    if($access == "r") {
      // read only
      $labels = $this->getLabels();
      $targets = $this->getTargets();

      $result = "";
      for($i = 0; $i < count($values); $i++) {
	// add delimiter if necessary
	if($i > 0)
	  $result .= ", ";

	$value = $values[$i];

	// label equals value if there is no label
	if(count($labels) <= $i || $labels[$i] == "")
	  $label = $value;
	else
	  $label = $labels[$i];

	$target = "";
	if(count($targets) > $i)
	  $target = "TARGET=\"$targets[$i]\"";

	$result .= "<A HREF=\"$value\" $target>$label</A>";
      }
    }
    else {
      $page =& $this->getPage();
      $form =& $page->getForm();
      $formId = $form->getId();

      $builder = new FormFieldBuilder();
      $result = $builder->makeTextListField($this->getId(), $values, $access, $formId, $GLOBALS["_FormField_height"], $GLOBALS["_FormField_width_big"]);
      $result .= $builder->makeJavaScript($this, "top.code.Url_changeHandler", $GLOBALS["_FormField_TextList_submit"]);
    }

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
