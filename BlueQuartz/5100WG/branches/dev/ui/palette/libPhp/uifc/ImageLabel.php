<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: ImageLabel.php 201 2003-07-18 19:11:07Z will $

global $isImageLabelDefined;
if($isImageLabelDefined)
  return;
$isImageLabelDefined = true;

include("uifc/Label.php");

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
      return "<A HREF=\"javascript: void 0\" onMouseOver=\"return top.code.info_mouseOver('$description')\" onMouseOut=\"return top.code.info_mouseOut();\"><IMG ALT=\"$label\" BORDER=\"0\" SRC=\"$image\"></A>";
  }
}/*
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>

