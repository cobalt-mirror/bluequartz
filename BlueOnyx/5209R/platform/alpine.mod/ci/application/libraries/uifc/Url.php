<?php
// Author: Kevin K.M. Chiu
// $Id: Url.php

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
  function Url(&$page, $id, $value, $i18n, $label = "", $target = "", $invalidMessage = "", $emptyMessage = "") {

    // superclass constructor
    $this->FormField($page, $id, $value, $invalidMessage, $emptyMessage);

    // we would normally need to add data preservation for targets 
    // and labels. But those are only used in a read-only state
    // and there is no changed data to preserve in a read-only state,
    // so we don't need to do anything
    $this->setLabel($label);
    $this->setTarget($target);

    $this->i18n = $i18n;
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

  // description: Type of input validation. 
  // param: size: an integer
  function setType($type) {
        $this->type = $type;
  }

  // Returns type of input validation:
  // If none given, it defaults to "alphanum_plus":
  function getType() {
    if (!isset($this->type)) {
         $this->type = "domainname";
    }
    return $this->type;
  }

  function toHtml($style = "") {
    $access = $this->getAccess();
    $id = $this->getId();
    $i18n =& $this->getI18n();
    $value = $this->getValue();

    if($access == "r") {
      $label = $this->getLabel();
      $target = $this->getTarget();

      if($label == "") {
        $label = $value;
      }

      if($target != "") {
        $target = "TARGET=\"$target\"";
      }

      // HTML safe
      $label = htmlspecialchars($label);

      return "<A HREF=\"$value\" $target>$label</A>";
    }
    else {
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

      $formField = $builder->makeTextField($id, $value, $access, $i18n, $this->getType(), $this->isOptional(), $GLOBALS["_FormField_width_big"], 50, $GLOBALS["_FormField_change"]);

      return $formField;
    }
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