<?php
// Author: Kevin K.M. Chiu
// $Id: DomainNameList.php

global $isDomainNameListDefined;
if($isDomainNameListDefined)
  return;
$isDomainNameListDefined = true;

include_once("ArrayPacker.php");
include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

class DomainNameList extends FormField {

  var $Label, $Description;

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

  function toHtml($style = "") {
    $page =& $this->getPage();
    $form =& $page->getForm();
    $formId = $form->getId();
    $i18n =& $this->getI18n();

    // Verification type as per Schema file:
    $type = "domainnames";

    $builder = new FormFieldBuilder();

    $id = $this->getId();

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

    $result = $builder->makeTextListField($this->getId(), stringToArray($this->getValue()), $this->getAccess(), $i18n, $type, $this->isOptional(), $formId, $GLOBALS["_FormField_height"], $GLOBALS["_FormField_width"]);

    return $result;
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