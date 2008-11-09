<?php
// Author: Kevin K.M. Chiu, Mike Waychison
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: EmailAddressList.php 259 2004-01-03 06:28:40Z shibuya $

global $isEmailAddressListDefined;
if($isEmailAddressListDefined)
  return;
$isEmailAddressListDefined = true;

include("ArrayPacker.php");
include("uifc/FormField.php");
include("uifc/Button.php");
include("uifc/ImageLabel.php");
include("uifc/FormFieldBuilder.php");

class EmailAddressList extends FormField {
  // 
  // private data
  //
  
  var $hasImport;
  var $importFunction;
  var $formatField;

  //
  // public methods
  //


  function EmailAddressList (&$page, $id, $value, $invalidMessage, $emptyMessage) {
    // superclass constructor
    $this->FormField ($page, $id, $value, $invalidMessage, $emptyMessage);
    
    // set defaults
    $this->setFormat("BLOCK");
    $this->hasImport = false;
    $this->importFunction = "";

  }

  // description: set the import feature of the list so that email addresses
  //     can be imported from the address book
  // param: on: true to enable import, false to disable
  // param: javascriptFunction: Javascript code that is being run during
  //     import
  function setImport( $on, $javascriptFunction = "") {
    $this->hasImport = $on;
    $this->importFunction = $javascriptFunction;
  }

  // description: set the format of the list
  // param: format: "BLOCK" or "SINGLELINE". In "BLOCK" mode, email addresses
  //     are one per line using a TextBlock field. In "SINGLELINE" mode,
  //     multiple email addresses can be entered comma seperated in a
  //     "First Lastname <abc@abc.net>" format. "SINGLELINE" mode will return
  //     unformatted email addresses in $id_full variable in additional to the
  //     $id variable.
  function setFormat( $format = "BLOCK")  {
    $format = strtoupper($format);
    if ($format == "BLOCK" || $format == "SINGLELINE") {
      $this->formatField = strtoupper($format);
      return true;
    } else {
      return false; 
    }
  }

  function toHtml($style = "") {
    $page =& $this->getPage();
    $form =& $page->getForm();
    $formId = $form->getId();
    $i18n =& $page->getI18n();

    $result = "<table border=0 cellspacing=0 cellpadding=0><tr><td>"; 
    // always wrap email list nowrap>";
	
    $builder = new FormFieldBuilder();
    if ($this->formatField == "BLOCK") {
      $result .= $builder->makeTextListField($this->getId(), stringToArray($this->getValue()), $this->getAccess(), $formId, $GLOBALS["_FormField_height"], $GLOBALS["_FormField_width"]);
      $result .= $builder->makeJavaScript($this, "top.code.EmailAddressList_changeHandler", $GLOBALS["_FormField_TextList_submit"]);
    } else {
      $oldId = $this->getId();
      $this->setId("_".$oldId."_TextField");
      if (!ereg("^\&(.*)\&$", $this->getValue(), $regs)) {
        $value = implode(", ", stringToArray($this->getValue()));
      } else {
        $value = $this->getValue();
      }
      $result .= $builder->makeTextField( $this->getId(), $value, $this->getAccess(), $GLOBALS["_FormField_width_big"], null, $GLOBALS["_FormField_change"]);
      $id = $this->getId();
      $result .= "
<SCRIPT LANGUAGE=\"javascript\">
document.$formId.$id.postField = \"$oldId\";
</SCRIPT>
";
      $result .= $builder->makeJavaScript($this, "top.code.EmailAddressListSingleLine_changeHandler", "top.code.EmailAddressListSingleLine_submitHandler");
      $this->setId($oldId);
      $result .= $builder->makeHiddenField( $this->getId(), "");
      $result .= $builder->makeHiddenField( $this->getId() . "_full", "");
    }

    if ($this->hasImport) {
        $result .= "</td><td nowrap>";
	$importButton = new Button ($page, "javascript: " . $this->importFunction . "('_" . $this->getId() . "_TextField')", new ImageLabel($page, "/libImage/importAddress.gif", $i18n->interpolate("[[palette.import]]"), $i18n->interpolate("[[palette.import_help]]")));
	$result .= $importButton->toHtml();
	//$result .= "<a href=\"javascript: " . $this->importFunction . "('" . $this->getId() . "')\">bleh</a>";
    }
    $result .= "</td></tr></table>";
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
