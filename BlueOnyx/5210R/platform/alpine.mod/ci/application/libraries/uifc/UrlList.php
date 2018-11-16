<?php
// Author: Kevin K.M. Chiu
// $Id: UrlList.php

global $isUrlListDefined;
if($isUrlListDefined)
  return;
$isUrlListDefined = true;

include_once("ArrayPacker.php");
include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

class UrlList extends FormField {
  //
  // private variables
  //

  var $labels;
  var $targets;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this form field lives in
  // param: id: the identifier of this object
  // param: value: an URL encoded list of URLs
  // param: labels: an array of label strings. Optional
  // param: targets: an array of target attributes for the A tag in strings.
  //     Optional
  // param: invalidMessage: message to be shown upon invalid input. Optional
  // param: emptyMessage: message to be shown upon empty input
  //     if the field is not optional. This message is optional
  function UrlList(&$page, $id, $value, $i18n, $labels = array(), $targets = array(), $invalidMessage, $emptyMessage) {
    // superclass constructor
    $this->FormField($page, $id, $value, $invalidMessage, $emptyMessage);

    // we would normally need to add data preservation for targets 
    // and labels. But those are only used in a read-only state
    // and there is no changed data to preserve in a read-only state,
    // so we don't need to do anything
    $this->setLabels($labels);
    $this->setTargets($targets);

    $this->i18n = $i18n;
  }

  // description: get the labels
  // returns: an array of label strings
  // see: setLabels()
  function getLabels() {
    return $this->labels;
  }

  // description: set the labels
  // param: labels: an array of label strings
  // see: getLabels()
  function setLabels($labels) {
    $this->labels = $labels;
  }

  // description: get the targets attributes
  // returns: an array of targets in strings
  // see: setTargets()
  function getTargets() {
    return $this->targets;
  }

  // description: set the targets attributes
  // param: labels: an array of targets in strings
  // see: getTargets()
  function setTargets($targets) {
    $this->targets = $targets;
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

    $access = $this->getAccess();
    $values = stringToArray($this->getValue());

    if($access == "r") {
      // read only
      $labels = $this->getLabels();
      $targets = $this->getTargets();

      $result = "";
      for($i = 0; $i < count($values); $i++) {
        // add delimiter if necessary
        if($i > 0) {
          $result .= ", ";
        }

        $value = $values[$i];

        // label equals value if there is no label
        if(count($labels) <= $i || $labels[$i] == "") {
          $label = $value;
        }
        else {
          $label = $labels[$i];
        }

        $target = "";
        if(count($targets) > $i) {
          $target = "TARGET=\"$targets[$i]\"";
        }

        $result .= "<A HREF=\"$value\" $target>$label</A>";
      }
    }
    else {

      $id = $this->getId();
      $page =& $this->getPage();
      $form =& $page->getForm();
      $formId = $form->getId();
      $i18n =& $this->getI18n();

      $builder = new FormFieldBuilder();

      // Verification type as per Schema file:
      $type = "alphanum_plus_multiline"; // <- Should be "domainnames", which we haven't done yet. This will do in between.

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
    }

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