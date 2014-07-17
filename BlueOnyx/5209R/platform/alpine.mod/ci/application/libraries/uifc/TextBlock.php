<?php

// Author: Kevin K.M. Chiu
// $Id: TextBlock.php

global $isTextBlockDefined;
if($isTextBlockDefined)
  return;
$isTextBlockDefined = true;

include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

class TextBlock extends FormField {
  //
  // private variables
  //

  var $height;
  var $width;
  var $wrap;
  var $Label, $Description;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object that this object lives in
  // param: id: the identifier of the object
  // param: value: a text string. Optional
  // param: emptyMessage: message to be shown upon empty input
  //     if the field is not optional. This message is optional

  function TextBlock(&$page, $id, $value = "", $i18n, $emptyMessage = "") {
    // superclass constructor
    $this->FormField($page, $id, $value, "", $emptyMessage);
    $this->height = "";
    $this->width = "";
    $this->wrap = false;
    $this->i18n = $i18n;
  }

  // description: get the height or number of rows
  // returns: an integer
  // see: setHeight()
  function getHeight() {
    return $this->height;
  }

  // description: set the height or number of rows
  // param: height: an integer
  // see: getHeight()
  function setHeight($height) {
    $this->height = $height;
  }

  // description: get the width of or number of columns
  // returns: an integer
  // see: setWidth()
  function getWidth() {
    return $this->width;
  }

  // description: set the width of or number of columns
  // param: width: an integer
  // see: getWidth()
  function setWidth($width) {
    $this->width = $width;
  }

  function setAccess($access) {
    $this->access = $access;
  }

  function getAccess() {
    if (!isset($this->access)) {
      $this->access = "rw";
    }
    return $this->access;
  }

  // description: set to/not to wrap text
  // param: val: true to wrap, false otherwise
  // see: isWrap()
  function setWrap($val = false) {
    $this->wrap = $val;
  }

  // description: see if text should be wrapped or not
  // returns: true to wrap, false otherwise
  // see: setWrap()
  function isWrap() {
    return $this->wrap;
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

  // Set Optional
  function setOptional($setOptional) {
    $this->Optional = $setOptional;
  }

  // Get Optional
  function getOptional() {
    if (!isset($this->Optional)) {
      $this->Optional = "TRUE";
    }
    return $this->Optional;
  }

  function toHtml($style = "") {
    $builder = new FormFieldBuilder();
    $formField = $builder->makeTextAreaField($this->getId(), $this->getValue(), $this->getAccess(), $this->i18n, $this->getType(), $this->getOptional(), "", "", "", $this->wrap ? "on" : "off");
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