<?php
// Author: Michael Stauber
// $Id: BXDivider.php

global $isBxDividerDefined;
if($isBxDividerDefined)
  return;
$isBxDividerDefined = true;

include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

class BxDivider extends FormField {
  //
  // public methods
  //

  function BxDivider(&$page, $id, $value, $i18n, $type="", $invalidMessage = "", $emptyMessage = "", $range="") {

    // Set up $i18n:
    $this->i18n = $i18n;

    $this->page = $page;

    // Setup blank label and description:
    $this->Label = "";
    $this->Description = "";

    // Set up label:
    $this->label = $value;

    // Set up $type:
    $this->type = "";

    // Set up LabelType:
    $this->LabelType = "label_side top";

    // superclass constructor
    $this->FormField($page, $id, $this->label, $this->i18n, $this->type, $invalidMessage, $emptyMessage);
  }

  // Get Label Type:
  function getLabelType() {
    if (!isset($this->LabelType)) {
       $this->LabelType = "label_side top";
    }
    return $this->LabelType;
  }

  // description: defines where the labels are placed on formfields:
  function setLabelType($type) {
        $this->LabelType = $type;
  }

  // Sets the current label
  function setCurrentLabel($label) {
    $this->Label = $label;
  }

  // Sets the current label-description:
  function setDescription($description) {
    $this->Description = $description;
  }

  function toHtml($style = "") {
    $id = $this->getId();
    $label = $this->label;

    $builder = new FormFieldBuilder();

    // Check Class BXPage to see if we have a label and description for this FormField:
    $tmpLabel = $this->getCurrentLabel();

    $tmpDesc = $this->getDescription();
    if ((isset($tmpLabel)) && ($tmpLabel != "")) {
        $builder->setCurrentLabel($tmpLabel);
        $label = $tmpLabel;
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
    $formField = $builder->makeBxDivider($id, $label, $this->getI18n());
    return $formField;
  }
}

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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