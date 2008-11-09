<?php
// Author: Mike Waychison <mwaychison@cobalt.com>
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: MultiFileUpload.php 237 2003-09-10 08:22:45Z shibuya $

global $isMultiFileUploadDefined;
if($isMultiFileUploadDefined)
  return;
$isMultiFileUploadDefined = true;

include("uifc/FormField.php");
include("uifc/FormFieldBuilder.php");
include("uifc/Button.php");
include("uifc/Label.php");

class MultiFileUpload extends FormField {
  //
  // private variables
  //

  var $maxFileSize;
  var $EMPTY_LABEL;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this form field lives in
  // param: id: the identifier of this object
  // param: value: the path
  // param: maxFileSize: the maximum file size allowed to upload in bytes.
  //     Optional
  // param: invalidMessage: message to be shown upon invalid input. Optional
  // param: emptyMessage: message to be shown upon empty input
  //     if the field is not optional. This message is optional
  function MultiFileUpload(&$page, $id, $value, $maxFileSize = false, $invalidMessage = "", $emptyMessage = "") {
    // superclass constructor
    $this->FormField($page, $id, $value, $invalidMessage, $emptyMessage);
    if (!$maxFileSize)
      $maxFileSize = 128*1024*1024;
    $this->setMaxFileSize($maxFileSize);
    $i18n =& $page->getI18n();
    $this->EMPTY_LABEL = $i18n->interpolate("[[palette.noFiles]]");
  }

  // description: get the maximum file size allowed to upload
  // returns: maxFileSize: bytes in integer
  // see: setMaxFileSize()
  function getMaxFileSize() {
    return $this->maxFileSize;
  }

  // description: set the maximum file size allowed to upload
  // param: maxFileSize: bytes in integer
  // see: getMaxFileSize()
  function setMaxFileSize($maxFileSize) {
    $this->maxFileSize = $maxFileSize;
  }

  function toHtml($style = "") {
    $oldId = $this->getId();
    $this->setId("_" . $this->getId() . "_SelectField");
    $id = $this->getId(); 
    $page =& $this->getPage();
    $form =& $page->getForm();
    $formId = $form->getId();

    $builder = new FormFieldBuilder();

    $i18n =& $page->getI18n(); 

    // set MAX_FILE_SIZE if needed
    // this needs to be before the file input field as required by PHP
    $maxFileSize = $this->getMaxFileSize();
    $formField .= $builder->makeHiddenField("MAX_FILE_SIZE", $maxFileSize);
    $formField .= "<table border=0 cellspacing=0 cellpadding=1><tr><td>";
    $formField .= $builder->makeSelectField($id, $this->getAccess(), $GLOBALS["_FormField_height"], $GLOBALS["_FormField_width"], true, $formId, "void (0)", array($this->EMPTY_LABEL));
    $formField .= "</td><td valign=bottom align=left>";
    // make the buttons
    
    $addButton = new Button( $this->page, "javascript: top.code.MultiFileUpload_QueryAttachment(document.$formId.$id,$maxFileSize)", new ImageLabel($this->page, "/libImage/addAttachment.gif", $i18n->get("[[palette.addAttachment]]"), $i18n->get("[[palette.addAttachment_help]]")));
    $formField .= $addButton->toHtml();
    $removeButton = new Button( $this->page, "javascript: top.code.MultiFileUpload_RemoveAttachment(document.$formId.$id)", new ImageLabel($this->page, "/libImage/removeAttachment.gif", $i18n->get("[[palette.removeAttachment]]"),$i18n->get("[[palette.removeAttachment_help]]")));
    $formField .= "<font size=1><br></font>".$removeButton->toHtml();
    $formField .= "</td></tr></table>";
    $formField .= $builder->makeJavaScript($this, "", "top.code.MultiFileUpload_SubmitHandler");
    $this->setId($oldId);
    $formField .= $builder->makeHiddenField($this->getId());
    $formField .= $builder->makeHiddenField($this->getId() . "_size");
    $formField .= $builder->makeHiddenField($this->getId() . "_name");
    $formField .= $builder->makeHiddenField($this->getId() . "_type");
    $formField .= "
<SCRIPT language=\"javascript\">
  var element = document.$formId.$id;
  element.emptyLabel = \"$this->EMPTY_LABEL\";
  element.parentDocument = document;

  document.$formId.$id._fieldName = \"" . $this->getId() ."\";
</SCRIPT>";
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
