<?php
// Author: Kevin K.M. Chiu
// $Id: Label.php

global $isLabelDefined;
if($isLabelDefined)
  return;
$isLabelDefined = true;

include_once("System.php");
include_once("uifc/HtmlComponent.php");

// also implements Collatable
class Label extends HtmlComponent {
  //
  // private variables
  //

  var $label = "";
  var $description = "";

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this object lives in
  // param: label: a label string
  // param: description: a description string
  function Label(&$page, $label, $description = "") {
    // superclass constructor
    $this->HtmlComponent($page);

    $this->setLabel($label);

    if (is_object($page)) {
//      echo "XXX: $label <br>";
      $page->Label = array("label" => $label, "description" => $description);
    }

  }

  function getCollatableValue() {
    return $this->label;
  }

  function getDefaultStyle($stylist) {
    //return $stylist->getStyle("Label");
    return "";
  }

  // description: get the description of the label
  // returns: a string
  // see: setDescription()
  function getDescription() {
    return $this->description;
  }

  // description: set the description of the label
  // param: description: a string
  // see: getDescription()
  function setDescription($description) {
    $this->description = $description;
  }

  // description: get the label string of the label
  // returns: a string
  // see: setLabel()
  function getLabel() {
    return $this->label;
  }

  // description: set the label string of the label
  // param: label: a string
  // see: getLabel()
  function setLabel($label) {
    $this->label = $label;
  }

  function toHtml($style = "") {
    $page =& $this->getPage(); 
    $id = $this->label;

    $i18n =& $page->getI18n();

    // The HTML_ENTITIES translation table is only valid for the 
    // ISO-8859-1 character set. Japanese is the only supported 
    // language which does not use the ISO-8859-1 charset, so we 
    // do a special case for that.
    //
    // Tue 20 Mar 2012 07:27:31 PM CET (mstauber) NOT TRUE! For UTF-8 we can use htmlspecialchars! \o/
    $encoding = $i18n->getProperty("encoding", "palette");
    $label = htmlspecialchars($this->getLabel());

    // using interpolateJs this way is not very clean, but this works for now
    //$description = $i18n->interpolateJs("[[VAR.string]]", array("string" => $this->getDescription()));
    $description = $i18n->getWrapped($this->getDescription());
    $desc_length = strlen($this->getDescription());

    if (isset($page->Label['label'])) {
      $label = $page->Label['label'];
    }
    else {
      // Stupid work-around for CompositeFormField.php's missing label output:
      if (isset($page->BXLabel[''])) {
        foreach ($page->BXLabel[''] as $key => $value) {
          $label = $key;
          $description = $value;
          $desc_length = strlen($description);          
        }
      }
    }
    if (isset($page->Label['description'])) {
      $description = $page->Label['description'];
      $desc_length = strlen($description);
    }

    if ($desc_length == "0") {
      // This is has no description, so we don't do a tooltip:
      return '<label for="' . $id . '" class=""><b>' . $label . '</b></label>';
    }
    else {
      //return '<fieldset class="label_side right no_lines"><label for="' . $id . '" title="' . $description . '" class="tooltip left">' . $label . '</label></fieldset>';
      return '<label for="' . $id . '" title="' . $description . '" class="tooltip left"><div style="font-size: 13px; font-weight: 700; padding: 15px 13px 10px; padding-top: 15px; padding-right-value: 20px; padding-bottom: 10px; padding-left-value: 20px; padding-left-ltr-source: physical; padding-left-rtl-source: physical; padding-right-ltr-source: physical; padding-right-rtl-source: physical; margin-right: 10px; margin-right-value: 10px; margin-right-ltr-source: physical; margin-right-rtl-source: physical; display: block; font-family: \'Open Sans\',sans-serif; color: #333;">' . $label . '</div></label>';
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