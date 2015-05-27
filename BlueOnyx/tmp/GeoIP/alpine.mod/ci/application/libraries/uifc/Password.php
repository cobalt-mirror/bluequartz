<?php
// Author: Kevin K.M. Chiu
// $Id: Password.php

global $isPasswordDefined;
if($isPasswordDefined)
  return;
$isPasswordDefined = true;

include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

class Password extends FormField {
  //
  // private variables
  //

  var $isConfirm;
  var $maxLength = 0;
  var $Label, $Description;

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
  function Password(&$page, $id, $value, $invalidMessage, $emptyMessage = "") {
    // superclass constructor
    $this->FormField($page, $id, $value, $invalidMessage, $emptyMessage);
    $this->page = $page;
    $this->isConfirm = true;
  }

  // Returns the current Page
  function getPage() {
    return $this->page;
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
    if (!isset($this->Description)) {
      $this->Description = "";
    }
    $this->Description = $description;
  }

  // Returns the current label-description:
  function getDescription() {
    return $this->Description;
  }

  // description: set the maximum length or characters the field can take
  // param: len: an integer
  function setMaxLength($len) {
    $this->maxLength = $len;
  }

  // description: Type of input validation. If none given, it defaults to "alphanum_plus":
  // param: size: an integer
  function setType($type) {
        $this->type = $type;
  }

  // Returns type of input validation:
  function getType() {
    if (!isset($this->type)) {
         $this->type = "password";
    }
    return $this->type;
  }

  // description: defines where the labels are placed on formfields:
  function setLabelType($LabelType) {
        $this->LabelType = $LabelType;
  }

  // Returns where the labels are placed on formfields:
  function getLabelType() {
    if (!isset($this->LabelType)) {
         $this->LabelType = "label_side top";
    }
    return $this->LabelType;
  }

  function &getDefaultStyle(&$stylist) {
    $style = "";
    return $style;
  }

  // description: see if the confirm field is shown
  // returns: if true, a confirm field is shown
  // see: setConfirm()
  function isConfirm() {
    if (!isset($this->isConfirm)) {
      $this->isConfirm = FALSE;
    }
    return $this->isConfirm;
  }

  // Enable or disable password checking_
  function setCheckPass($check) {
    $this->passCheck = $check;
  }

  // Return if we check for strong passwords or not.
  // Default: We do check.
  function getCheckPass() {
    if (!isset($this->passCheck)) {
      $this->passCheck = TRUE;
    }
    return $this->passCheck;
  }

  // description: show/hide the confirm field
  // param: isConfirm: if true, a confirm field is shown
  // see: isConfirm()
  function setConfirm($isConfirm) {
    $this->isConfirm = $isConfirm;
  }

  function toHtml($style = "") {
    $access = $this->getAccess();
    $id = $this->getId();
    $value = $this->getValue();

    $builder = new FormFieldBuilder();
    $builder->setLabelType($this->getLabelType());

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

    $formField = $builder->makePasswordField($id, $this->getValue(), $this->getAccess(), $this->getI18n(), $this->getType(), $this->isOptional(), $GLOBALS["_FormField_width"], $this->maxLength, $GLOBALS["_FormField_change"], $this->isConfirm(), $this->getPage(), $this->getCheckPass());

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