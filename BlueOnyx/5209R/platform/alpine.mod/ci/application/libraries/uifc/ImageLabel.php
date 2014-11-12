<?php
// Author: Kevin K.M. Chiu
// $Id: ImageLabel.php

global $isImageLabelDefined;
if($isImageLabelDefined)
  return;
$isImageLabelDefined = true;

include_once("uifc/Label.php");

class ImageLabel extends Label {
  //
  // private variables
  //

  var $image;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this object lives in
  // param: image: an URL of an image
  // param: label: a label string
  // param: description: a description string
  function ImageLabel(&$page, $image, $label, $description = "") {
    // superclass constructor
    $this->Label($page->getStylist(), $label, $description);

    $this->setImage($image);

    if (is_object($page)) {
      $page->Label = array("label" => $label, "description" => $description);
    }

  }

  // description: get the image used as the label
  // returns: an URL of an image
  function getImage() {
    return $this->image;
  }

  // description: set the image used as the label
  // param: image: an URL of an image
  function setImage($image) {
    $this->image = $image;
  }

  function toHtml($style = "") {
    $image = $this->getImage();
    $label = $this->getLabel();
    $description = $this->getDescription();

    if($description == null || $description == "")
      return "<IMG ALT=\"$label\" BORDER=\"0\" SRC=\"$image\">";
    else
      return "<IMG ALT=\"$label\" BORDER=\"0\" SRC=\"$image\">";
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