<?php
// Author: Kevin K.M. Chiu
// $Id: Block.php

global $isBlockDefined;
if($isBlockDefined)
  return;
$isBlockDefined = true;

include_once("uifc/HtmlComponent.php");

// abstract
class Block extends HtmlComponent {
  //
  // private variables
  //

  var $buttons;
  var $label;
  var $width;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this block is in
  // param: label: a Label object for the block title. Optional
  function Block($page, $label = "") {
    // superclass constructor
    $this->HtmlComponent($page);

    $this->setLabel($label);

    $this->buttons = array();
    $this->width = -1;
  }

  // description: get all buttons added to the block
  // returns: an array of Button objects
  // see: addButton()
  function getButtons() {
    return $this->buttons;
  }

  // description: add a button to the block
  // param: button: a Button object
  // see: getButtons()
  function addButton($button) {
    $this->buttons[] = $button;
  }

  // description: set the width of the block
  // param: width: the width of the block in pixels 
  // see: getWidth()
  function setWidth($width) {
    $this->width = $width;
  }

  // description: get the width of the block in pixels
  // returns: the width of the block in pixels 
  // see: setWidth()
  function getWidth() {
    return $this->width;
  }

  // description: get the label of the block
  // returns: a Label object
  // see: setLabel()
  function getLabel() {
    return $this->label;
  }

  // description: set the label of the block
  // param: label: a Label object
  // see: getLabel()
  function setLabel($label) {
    $this->label = $label;
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