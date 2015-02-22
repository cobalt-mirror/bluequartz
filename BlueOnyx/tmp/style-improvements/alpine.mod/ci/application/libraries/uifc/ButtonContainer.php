<?php
// Author: Michael Stauber
// $Id: ButtonContainer.php

global $isButtonContainerDefined;
if($isButtonContainerDefined)
  return;
$isButtonContainerDefined = true;

include_once("uifc/ButtonContainer.php");

class ButtonContainer extends HtmlComponentFactory {
  //
  // private variables
  //

  //
  // public methods
  //

  // description: constructor
  function ButtonContainer($page, $id, $button, $i18n, $InvalidMessage, $EmptyMessage) {
    $this->BxPage = $page;
    $this->setId($id);
    $this->Button = $button;
    $this->i18n = $i18n;
  }

  // description: get the ID of the list
  // returns: an ID string
  // see: setId()
  function getId() {
    return $this->id;
  }

  // description: set the ID of the list
  // param: id: an ID string
  // see: getId()
  function setId($id) {
    $this->id = $id;
  }  

  function setAccess($access) {
    $this->access = $access;
  }

  // description: get the access property
  // returns: a string
  // see: setAccess()
  function getAccess() {
    return $this->access;
  }

  function getValue() {
        return($this->id);
  }

  function isOptional() {
        return(FALSE);
  }

  // description: get all the form fields of the block
  // returns: an array of FormField objects
  // see: addFormField()
  public function &getFormFields() {
    return $this->formFields;
  }

  // description: add a form field to this block
  // param: formField: a FormField object
  // param: label: a label object. Optional
  //     hidden form fields are not shown and therefore do not need labels
  // param: pageId: the ID of the page the form field is in
  //     Optional if there is only one page
  // see: getFormFields()
  public function addFormField(&$formField, $label = "", $pageId = "", $errormsg = false) {
    $this->formFields[] =& $formField;

    $this->formFieldLabels[$formField->getId()] = $label;

    // Pass the information from the LabelObject's labels to the class BxPage() so that it can store them for us
    // until we need to pull that information:
    if (isset($label->page->Label)) {
      $this->BxPage->setLabel($formField->getId(), $label->page->Label['label'], $label->page->Label['description']);
    }
    else {
      $this->BxPage->setLabel($formField->getId(), "", "");
    }
  }

  // Actually I had more planned for this class. But forms and datatables don't really work that well together.
  // Which is a pitty. See /uifc/deprecated/ButtonContainer.php for another version of this class with more stuff.

  function toHtml($style = "") {
    $id = $this->getId();
    $result = "<!-- ButtonContainer: Start -->\n";
    $result .= '<div class="button_bar clearfix">';
    if (!is_array($this->Button)) {
      $result .= $this->Button->toHtml();
    }
    else {
      foreach ($this->Button as $button) {
        $result .= $button->toHtml();
      }
    }
    $result .=  "\n</div>\n";

    $formFields = $this->getFormFields();

    if (($id != "") && ($id != " ")) {
      // Only show box header if we don't have an empty ID. That way we can use it if we want and can hide
      // it easily if we don't want it:
      $result .= '            <h2 class="box_head">' . $this->i18n->getHtml($id) . '</h2>' . "\n";
    }

    for($i = 0; $i < count($formFields); $i++) {
      $childField =& $formFields[$i];
      $this_fields_classes = $childField->getLabelType();
      $childId = $childField->id;

      $result .= $childField->toHtml();
    }

    $result .= "<br>\n<!-- ButtonContainer: End -->\n";

    return $result;
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