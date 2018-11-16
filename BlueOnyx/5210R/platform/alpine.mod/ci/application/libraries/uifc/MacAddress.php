<?php
// Author: Kevin K.M. Chiu
// $Id: MacAddress.php

global $isMacAddressDefined;
if($isMacAddressDefined)
  return;
$isMacAddressDefined = true;

include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

class MacAddress extends FormField {

  var $size, $maxLength, $Label, $Description;

  //
  // public methods
  //

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

  // description: returns the maximum length or characters the field can take
  function getMaxLength() {
    if (!isset($this->maxLength)) {
      $this->maxLength = FALSE;
    }
    return $this->maxLength;
  }
  // description: Type of input validation. If none given, it defaults to "alphanum_plus":
  // param: size: an integer
  function setType($type) {
        $this->type = $type;
  }

  // Returns type of input validation:
  function getType() {
    if (!isset($this->type)) {
         $this->type = "";
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

  function toHtml($style = "") {
    $id = $this->getId();
    $page =& $this->getPage();
    $form =& $page->getForm();
    $formId = $form->getId();
    $i18n =& $this->getI18n();

    $builder = new FormFieldBuilder();

    // Check Class BXPage to see if we have a label and description for this FormField:
    $tmpLabel = $this->getCurrentLabel();
    $tmpDesc = $this->getDescription();
    if (isset($tmpLabel)) {
        $builder->setCurrentLabel($tmpLabel);
        $builder->setDescription($tmpDesc);
    }
    elseif (is_array($this->page->getLabel($id))) {
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

    $formField = $builder->makeTextField($id, $this->getValue(), $this->getAccess(), $this->i18n, $this->getType(), $this->isOptional(), $this->size, $this->maxLength, $GLOBALS["_FormField_change"], "");

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