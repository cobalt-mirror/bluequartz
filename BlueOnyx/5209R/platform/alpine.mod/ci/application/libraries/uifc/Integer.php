<?php
// Author: Kevin K.M. Chiu
// $Id: Integer.php

global $isIntegerDefined;
if($isIntegerDefined)
  return;
$isIntegerDefined = true;

include_once("uifc/Number.php");
include_once("uifc/FormFieldBuilder.php");

class Integer extends Number {
  //
  // private variables
  //

  var $width;
  var $Label, $Description, $LabelType;

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
  function Integer(&$page, $id, $value, $i18n, $invalidMessage, $emptyMessage = "") {

    // Set up $i18n:
    $this->i18n = $i18n;

    // superclass constructor
    $this->Number($page, $id, $value, $this->i18n, $invalidMessage, $emptyMessage);

    $this->max = 4000000000;
    $this->min = -4000000000;
    $this->width = $GLOBALS["_FormField_width"];
    $this->maxLength = 9;

    // Set up $type:
    $this->type = "range";
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

  function setWidth($w) {
  $this->width = $w;
  }

  function &getDefaultStyle($stylist="") {
    $stylist = "";
    return $stylist;
  }

  // description: display the maximum & minimum values
  // param: boolean, "memory" or "disk", depending on how we display the values
  function showBounds($bool) {
    $this->showBounds = $bool;
  }

  // description: Type of input validation. If none given, it defaults to "alphanum_plus":
  // param: size: an integer
  function setType($type) {
        $this->type = $type;
  }

  // description: set the maximum length or characters the field can take
  // param: len: an integer
  function setMaxLength($len) {
    $this->maxLength = $len;
  }

  // description: defines where the labels are placed on formfields:
  function setLabelType($type) {
        $this->LabelType = $type;
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

    if (isset($this->showBounds)) {
      if ($this->showBounds == "disk") {
        $formatted_min = simplify_number_diskspace($this->min, "KB", "2", "B");
        $formatted_max = simplify_number_diskspace($this->max, "KB", "2", "B");

        $i18n = $page->getI18n();
        $bound_text = $i18n->interpolate('[[palette.integerBounds]]', 
          array("minBound" => $formatted_min, "maxBound" => $formatted_max));
      }
      elseif ($this->showBounds == "dezi") {
        $formatted_min = simplify_number($this->min, "K", "2", "B");
        $formatted_max = simplify_number($this->max, "K", "2", "B");

        $i18n = $page->getI18n();
        $bound_text = $i18n->interpolate('[[palette.integerBounds]]', 
          array("minBound" => $formatted_min, "maxBound" => $formatted_max));

      }
      elseif ($this->showBounds == "diskquota") {
        $formatted_min = simplify_number($this->min, "KB", "2", "B");
        $formatted_max = simplify_number($this->max, "KB", "2", "B");

        $i18n = $page->getI18n();
        $bound_text = $i18n->interpolate('[[palette.integerBounds]]', 
          array("minBound" => $formatted_min, "maxBound" => $formatted_max));

      }
      elseif ($this->showBounds == "memory") {
        $formatted_min = simplify_number($this->min, "KB", "3");
        $formatted_max = simplify_number($this->max, "KB", "3");

        $i18n = $page->getI18n();
        $bound_text = $i18n->interpolate('[[palette.integerBounds]]', 
          array("minBound" => $formatted_min, "maxBound" => $formatted_max));

      }
      elseif (($this->showBounds == "1") || ($this->showBounds == TRUE)) {
        $formatted_min = $this->localizeNumber($this->min);
        $formatted_max = $this->localizeNumber($this->max);

        $i18n = $page->getI18n();
        $bound_text = $i18n->interpolate('[[palette.integerBounds]]', 
          array("minBound" => $formatted_min, "maxBound" => $formatted_max));
      }
      else {
        $bound_text = '';
      }
    }
    else {
      $bound_text = '';      
    }

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
      // We have no label for this FormField and build it based on the ID:
      $builder->setCurrentLabel($this->i18n->getHtml($id));
      $builder->setDescription($this->i18n->getWrapped($id . "_help"));
    }

    // Tell FormFieldBuilder where the lable is:
    $builder->setLabelType($this->getLabelType());
    $builder->setRangeMin($this->min);
    $builder->setRangeMax($this->max);

    $formField = $builder->makeTextField($id, $this->getValue(), $this->getAccess(), $i18n, $this->type, $this->isOptional(), $this->width, $this->maxLength, $GLOBALS["_FormField_change"], $bound_text);

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