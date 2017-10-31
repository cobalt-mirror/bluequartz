<?php
// Author: Kevin K.M. Chiu
// $Id: IpAddressList.php

global $isIpAddressListDefined;
if($isIpAddressListDefined)
  return;
$isIpAddressListDefined = true;

include_once("ArrayPacker.php");
include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

class IpAddressList extends FormField {
  //
  // public methods
  //

  // description: Type of input validation. 
  // param: size: an integer
  function setType($type) {
        $this->type = $type;
  }

  // Returns type of input validation:
  // If none given, it defaults to "alphanum_plus":
  function getType() {
    if (!isset($this->type)) {
         $this->type = "ipaddr_list";
    }
    return $this->type;
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

  function toHtml($style = "") {
    $id = $this->getId();
    $page =& $this->getPage();
    $form =& $page->getForm();
    $formId = $form->getId();
    $i18n =& $this->getI18n();

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

    $result = $builder->makeTextListField($this->getId(), stringToArray($this->getValue()), $this->getAccess(), $i18n, $this->getType(), $this->isOptional(), $formId, $GLOBALS["_FormField_height"], $GLOBALS["_FormField_width"]);

    return $result;
  }
}

/*
Copyright (c) 2014-2017 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014-2017 Team BlueOnyx, BLUEONYX.IT
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