<?php
// Author: Mike Waychison <mwaychison@cobalt.com>
// $Id: MultiFileUpload.php

global $isMultiFileUploadDefined;
if($isMultiFileUploadDefined)
  return;
$isMultiFileUploadDefined = true;

include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");
include_once("uifc/Button.php");
include_once("uifc/Label.php");

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
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. 
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/
?>