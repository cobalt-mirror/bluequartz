<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: FileUpload.php 995 2007-05-05 07:44:27Z shibuya $

global $isFileUploadDefined;
if($isFileUploadDefined)
  return;
$isFileUploadDefined = true;

include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

class FileUpload extends FormField {
  //
  // private variables
  //

  var $maxFileSize;

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
  function FileUpload(&$page, $id, $value, $maxFileSize = "", $invalidMessage, $emptyMessage = "") {
    // superclass constructor
    $this->FormField($page, $id, $value, $invalidMessage, $emptyMessage);

    $this->setMaxFileSize($maxFileSize);
  }

  function getMaxFileSize() {
    return $this->maxFileSize;
  }

  // description: set the maximum file size allowed to upload
  // param: maxFileSize: bytes in integer
  function setMaxFileSize($maxFileSize) {
    $this->maxFileSize = $maxFileSize;
  }

  function toHtml($style = "") {
    $id = $this->getId();

    $builder = new FormFieldBuilder();

    // set MAX_FILE_SIZE if needed
    // this needs to be before the file input field as required by PHP
    $maxFileSize = $this->getMaxFileSize();
    //if($maxFileSize != "")
    $formField .= $builder->makeHiddenField("MAX_FILE_SIZE", $maxFileSize);

    $formField .= $builder->makeFileUploadField($id, $this->getAccess(), $GLOBALS["_FormField_width"], null, "");
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
