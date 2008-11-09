<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Bar.php 237 2003-09-10 08:22:45Z shibuya $

global $isBarDefined;
if($isBarDefined)
  return;
$isBarDefined = true;

include("uifc/FormField.php");

class Bar extends FormField {
  //
  // private variables
  //

  var $label;

  //
  // public methods
  //

  function &getDefaultStyle(&$stylist) {
    return $stylist->getStyle("Bar");
  }

  function getLabel() {
    return $this->label;
  }

  // description: set label to replace the percentage shown by default
  // param: label: a label in string
  function setLabel($label) {
    $this->label = $label;
  }

  // description: set bar to type vertical
  function setVertical()
  {
    $this->orientation = 'v';
  }

  function toHtml($style = "")
  {
    // only support "r" access

    if($style == null || $style->getPropertyNumber() == 0) {
      $page =& $this->getPage();
      $style =& $this->getDefaultStyle($page->getStylist());
    }

    // find out style properties
    $startImage = $style->getProperty($this->orientation . "startImage");
    $filledImage = $style->getProperty($this->orientation . "filledImage");
    $emptyImage = $style->getProperty($this->orientation . "emptyImage");
    $endImage = $style->getProperty($this->orientation . "endImage");
    $leftBumper = $style->getProperty($this->orientation . "leftBumper");
    $rightBumper = $style->getProperty($this->orientation . "rightBumper");
    $bumperWidth = $style->getProperty($this->orientation . "bumperWidth");
    // Remove unneeded width and height parameters
    $startImage = substr($startImage, 0, strpos($startImage, "\""));
    $filledImage = substr($filledImage, 0, strpos($filledImage, "\""));
    $emptyImage = substr($emptyImage, 0, strpos($emptyImage, "\""));
    $endImage = substr($endImage, 0, strpos($endImage, "\""));

    $labelStyleStr = $style->toTextStyle();

    $value = $this->getValue();
    if($value == "")
      $value = 0;

    // find out label
    $label = $this->getLabel();
    if($label == "")
      $label = $value."%";

    if ($this->orientation == 'v')
    {
      // specify dimension
      $fullWidth  =  20;
      $fullHeight = 200;

      $barHeight = $fullHeight * $value / 100;
			settype($barHeight, "integer");

      $remainingHeight = $fullHeight - $barHeight;
      $result = "<BR><IMG SRC=\"$endImage\" BORDER=\"0\" WIDTH=\"$fullWidth\">";

      if ($remainingHeight > 0)
        $result .= "<BR><IMG SRC=\"$emptyImage\" BORDER=\"0\" HEIGHT=\"$remainingHeight\" WIDTH=\"$fullWidth\">";

if ($leftBumper != "")
{

      if ($barHeight > 0)
        $result .= "<BR><IMG SRC=\"$rightBumper\" Border=0><br><IMG SRC=\"$filledImage\" BORDER=\"0\" HEIGHT=\"$barHeight\" WIDTH=\"$fullWidth\"><br><IMG SRC=\"$leftBumper\" Border=0>";
      else
        $result .= "<BR><IMG SRC=\"$emptyImage\" BORDER=\"0\" HEIGHT=\"$bumperWidth\" WIDTH=$fullWidth>";  

}
else
{ 
      if ($barHeight > 0)
        $result .= "<BR><IMG SRC=\"$filledImage\" BORDER=\"0\" HEIGHT=\"$barHeight\" WIDTH=\"$fullWidth\">";

}

      $result .= "<BR><IMG SRC=\"$startImage\" BORDER=\"0\" WIDTH=\"$fullWidth\">";

      $result .= "<BR><FONT STYLE=\"$labelStyleStr\">$label</FONT>";
    }
    else
    {
      // specify dimension
      $fullWidth = 110;
      $fullHeight = 20;

      // find out width
      $barWidth = $fullWidth*$value/100;
			settype($barWidth, "integer");

      $remainingWidth = $fullWidth-$barWidth;

      $result = "<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR><TD><IMG SRC=\"$startImage\" BORDER=\"0\" HEIGHT=\"$fullHeight\">";

if ($leftBumper != "")
{
      if($barWidth > 0)
        $result .= "<IMG SRC=\"$leftBumper\" Border=0><IMG SRC=\"$filledImage\" BORDER=\"0\" HEIGHT=\"$fullHeight\" WIDTH=\"$barWidth\"><IMG SRC=\"$rightBumper\" Border=0>";
      else
        $result .= "<IMG SRC=\"$emptyImage\" BORDER=\"0\" HEIGHT=\"$fullHeight\" WIDTH=$bumperWidth>";

}
else
{
      if($barWidth > 0)
        $result .= "<IMG SRC=\"$filledImage\" BORDER=\"0\" HEIGHT=\"$fullHeight\" WIDTH=\"$barWidth\">";
}

      if($remainingWidth > 0)
        $result .= "<IMG SRC=\"$emptyImage\" BORDER=\"0\" HEIGHT=\"$fullHeight\" WIDTH=\"$remainingWidth\">";

      $result .= "<IMG SRC=\"$endImage\" BORDER=\"0\" HEIGHT=\"$fullHeight\"></TD><TD><FONT STYLE=\"$labelStyleStr\">$label</FONT></TD></TR></TABLE>";
    }

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
