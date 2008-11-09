<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Style.php 201 2003-07-18 19:11:07Z will $

global $isStyleDefined;
if($isStyleDefined)
  return;
$isStyleDefined = true;

class Style {
  //
  // private variables
  //

  var $id;
  var $properties;
  var $stylist;
  var $substyleCache;
  var $variant;

  //
  // public methods
  //

  // description: constructor
  // param: id: a string that identifies the style
  // param: variant: variant in string
  // param: stylist: the stylsit object that made this style
  function Style($id, $variant, $stylist) {
    $this->setId($id);
    $this->setVariant($variant);
    $this->setStylist($stylist);

    $this->properties = array();
    $this->substyleCache = array();
  }

  function getId() {
    return $this->id;
  }

  function setId($id) {
    $this->id = $id;
  }

  // description: get a property of the style
  // param: name: the name of the property
  // param: target: an optional string to specify where the property applies to
  // returns: the property in string
  function getProperty($name, $target = "") {
    return $this->properties["$name:$target"];
  }

  function setProperty($name, $target, $value) {
    $this->properties["$name:$target"] = $value;
  }

  // description: get identifiers (i.e. name and target) of all properties
  // returns: an array of arrays of 2 elements. First being property name and
  //     second being property target
  function getPropertyIds() {
    $keys = array_keys($this->properties);

    // make array to return
    $ids = array();
    for($i = 0; $i < count($keys); $i++) {
      $nameTarget = explode(":", $keys[$i]);
      $ids[] = array($nameTarget[0], $nameTarget[1]);
    }
    return $ids;
  }

  function getPropertyNumber() {
    return count(array_keys($this->properties));
  }

  // get the style for the target
  // param: target: a string that specifies the target
  // returns: a Style object
  function &getSubstyle($target) {
    // check cache
    $style =& $this->substyleCache[$target];
    if(is_object($style))
      return $style;

    $style = new Style($this->getId(), $this->getVariant(), $this->getStylist());

    $properties = $this->properties;
    $keys = array_keys($properties);
    for($i = 0; $i < count($keys); $i++) {
      $key = $keys[$i];

      // find name and target
      $nameTarget = explode(":", $key);
      $propertyName = $nameTarget[0];
      $propertyTarget = $nameTarget[1];

      // add property to style
      if($target == $propertyTarget)
        $style->setProperty($propertyName, "", $properties[$key]);
    }

    // save in cache
    $this->substyleCache[$target] =& $style;

    return $style;
  }

  function getStylist() {
    return $this->stylist;
  }

  function setStylist($stylist) {
    $this->stylist = $stylist;
  }

	// description: return an array of just the target names of all properties
  function getTargetNames() {
  	$prop = $this->getPropertyIds();
  	foreach($prop as $p) {
  		$t[] = $p[1];
  	}
  	return(array_unique($t));
  }

  function getVariant() {
    return $this->variant;
  }

  function setVariant($variant) {
    $this->variant = $variant;
  }

  // description: override properties of this style by those defined in another
  //     In other words, read all properties of another style and write them to
  //     this one
  // param: style: a Style object to override this style by
  function override($style) {
    $ids = $style->getPropertyIds();
    for($i = 0; $i < count($ids); $i++) {
      $id = $ids[$i];
      $this->setProperty($id[0], $id[1], $style->getProperty($id[0], $id[1]));
    }
  }

  // description: get the CSS style for background. This includes
  //     backgroundColor for CSS background-color
  //     backgroundImage for CSS background-image
  //     backgroundRepeat for CSS background-repeat
  //     backgroundAttachment for CSS background-attachment
  // param: target: an optional string for the target to apply to
  // returns: a CSS style string
  function toBackgroundStyle($target = "") {
    $backgroundColor = $this->getProperty("backgroundColor", $target);
    $backgroundImage = $this->getProperty("backgroundImage", $target);
    $backgroundRepeat = $this->getProperty("backgroundRepeat", $target);
    $backgroundAttachment = $this->getProperty("backgroundAttachment", $target);

    if($backgroundColor != "")
      $result .= "background-color:$backgroundColor;";
    if($backgroundImage != "")
    {
      // remove unwanted width and height attributes
      $backgroundImage = substr($backgroundImage, 0, strpos($backgroundImage, "\""));
      $result .= "background-image:url($backgroundImage);";
    }
    if($backgroundRepeat != "")
      $result .= "background-repeat:$backgroundRepeat;";
    if($backgroundAttachment != "")
      $result .= "background-attachment:$backgroundAttachment;";

    return $result;
  }

  // description: get the CSS style for text. This includes
  //     color for CSS color
  //     fontFamily for CSS font-family
  //     fontSize for CSS font-size
  //     fontWeight for CSS font-weight
  //     textDecoration for CSS text-decoration
  //     fontVariant for CSS font-varient
  // param: target: an optional string for the target to apply to
  // returns: a CSS style string
  function toTextStyle($target = "") {
    $color = $this->getProperty("color", $target);
    $fontFamily = $this->getProperty("fontFamily", $target);
    $fontSize = $this->getProperty("fontSize", $target);
    $fontStyle = $this->getProperty("fontStyle", $target);
    $fontWeight = $this->getProperty("fontWeight", $target);
    $textDecoration = $this->getProperty("textDecoration", $target);
    $fontVariant = $this->getProperty("fontVariant", $target);

    if($color != "")
      $result .= "color:$color;";
    if($fontFamily != "")
      $result .= "font-family:$fontFamily;";
    if($fontSize != "")
      $result .= "font-size:$fontSize;";
    if($fontStyle != "")
      $result .= "font-style:$fontStyle;";
    if($fontWeight != "")
      $result .= "font-weight:$fontWeight;";
    if($textDecoration != "")
      $result .= "text-decoration:$textDecoration;";
    if($fontVariant != "")
      $result .= "font-variant:$fontVariant;";

    return $result;
  }
}
/*
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

