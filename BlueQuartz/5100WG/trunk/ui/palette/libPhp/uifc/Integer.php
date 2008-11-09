<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Integer.php 237 2003-09-10 08:22:45Z shibuya $

global $isIntegerDefined;
if($isIntegerDefined)
  return;
$isIntegerDefined = true;

include("uifc/Number.php");
include("uifc/FormFieldBuilder.php");

class Integer extends Number {
  //
  // private variables
  //

  var $width;

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
  function Integer(&$page, $id, $value, $invalidMessage, $emptyMessage = "") {
    // superclass constructor
    $this->Number($page, $id, $value, $invalidMessage, $emptyMessage);

    $this->max = 4000000000;
    $this->min = -4000000000;
    $this->width = $GLOBALS["_FormField_width"];
  }

  function setWidth($w) {
	$this->width = $w;
  }

  // description: display the maximum & minimum values
  // param: boolean
  function showBounds($bool) {
    $this->showBounds = $bool;
  }

  function toHtml($style = "") {
    $id = $this->getId();
    $page =& $this->getPage();
    $form =& $page->getForm();
    $formId = $form->getId();

    $builder = new FormFieldBuilder();
    $formField .= "<table border=0 cellspacing=0 cellpadding=0><tr><td><div class=\"nested-string\">";
    $formField .= $builder->makeTextField($id, $this->getValue(), $this->getAccess(), $this->width, strlen($this->max), $GLOBALS["_FormField_change"]);

    if($this->showBounds) {
      // We don't accept localized input, so we can't localize output
      // $formatted_min = $this->localizeNumber($this->min);
      $formatted_min = $this->min;
      // $formatted_max = $this->localizeNumber($this->max);
      $formatted_max = $this->max;

      $i18n = $page->getI18n();
      $bound_text = $i18n->interpolate('[[palette.integerBounds]]', 
        array("minBound" => $formatted_min, "maxBound" => $formatted_max));

      $formField .= "</div></td><td><div class=\"nested-string\">";

      $formField .= $builder->makeTextField('bounds'.$id, $bound_text, "r", 0, 0, 
        $this->GENERIC_CHANGE_HANDLER);
    }
    $formField .= "</div></td></tr></table>";

    $formField .= $builder->makeJavaScript($this, "top.code.Integer_changeHandler", $GLOBALS["_FormField_TextField_submit"]);

    $formField .= "
<SCRIPT LANGUAGE=\"javascript\">
var element = document.$formId.$id;
element.max = $this->max;
element.min = $this->min;
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
