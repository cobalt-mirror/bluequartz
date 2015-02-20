<?php
// Author: Kevin K.M. Chiu
// $Id: FileUpload.php

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
  function FileUpload(&$page, $id, $value, $i18n, $maxFileSize = "", $invalidMessage, $emptyMessage = "") {
    // superclass constructor
    $this->FormField($page, $id, $value, $invalidMessage, $emptyMessage);

    $this->i18n = $i18n;
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

  // Sets the current label
  function setCurrentLabel($label) {
    $this->Label = $label;
  }

  // Returns the current label
  function getCurrentLabel() {
    if (!isset($this->Label)) {
      $this->Label = "";
    }
    return $this->Label;
  }

  // Sets the current label-description:
  function setDescription($description) {
    $this->Description = $description;
  }

  // description: defines where the labels are placed on formfields:
  function setLabelType($labeltype) {
        $this->LabelType = $labeltype;
  }

  // Returns where the labels are placed on formfields:
  function getLabelType() {
    if (!isset($this->LabelType)) {
         $this->LabelType = "label_side top";
    }
    return $this->LabelType;
  }

  function toHtml($style = "") {
    $id = $this->getId();

    $builder = new FormFieldBuilder();

    // Check Class BXPage to see if we have a label and description for this FormField:
    if (is_array($this->page->getLabel($id))) {
      foreach ($this->page->getLabel($id) as $label => $description) {
        // We do? Tell FormFieldBuilder about it:
        $builder->setCurrentLabel($label);
        $builder->setDescription($description);
      }
    }
    else {
      // We have no label for this FormField:
      $builder->setCurrentLabel("");
      $builder->setDescription("");
    }

    // Tell FormFieldBuilder where the lable is:
    $builder->setLabelType($this->getLabelType());

    // set MAX_FILE_SIZE if needed
    // this needs to be before the file input field as required by PHP
    $maxFileSize = $this->getMaxFileSize();
    //if($maxFileSize != "")
    $formField = $builder->makeHiddenField("MAX_FILE_SIZE", $maxFileSize);

    $formField .= $builder->makeFileUploadField($id, $this->getAccess(), $this->i18n, $GLOBALS["_FormField_width"], null, "");

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