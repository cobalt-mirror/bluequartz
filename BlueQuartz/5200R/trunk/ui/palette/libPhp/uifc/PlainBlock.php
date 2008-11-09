<?php
// Copyright 2001, Sun Microsystems.  All rights reserved.
// $Id: PlainBlock.php 995 2007-05-05 07:44:27Z shibuya $

// description:
// Plain Block represents a block with NO title and HtmlComponents in it.
//
// applicability:
// Use where you need a block of HTMLComponents without a title 
//
// usage:
// After instantiation, use addHtmlComponent() to add HTML components into the
// block. Use toHtml() to get HTML representation.

global $isPlainBlockDefined;
if($isPlainBlockDefined)
  return;
$isPlainBlockDefined = true;

include_once("uifc/Block.php");

class PlainBlock extends Block {
  //
  // private variables
  //

  var $components;
  var $dividers;
  var $dividerIndexes;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this block is in
  // param: label: a Label object for the block title. Optional
  function PlainBlock($page, $label = "") {
    // superclass constructor
    $this->Block($page, $label);

    $this->components = array();
    $this->dividers = array();
    $this->dividerIndexes = array();
  }

  function getDefaultStyle($stylist) {
    return $stylist->getStyle("PlainBlock");
  }

  // description: get all the HTML components of the block
  // returns: an array of HtmlComponent objects
  // see: addHtmlComponent()
  function getHtmlComponents() {
    return $this->components;
  }

  // description: add a HTML component to this block
  // param: htmlComponent: a HtmlComponent object
  // see: getHtmlComponents()
  function addHtmlComponent($htmlComponent) {
    $this->components[] = $htmlComponent;
  }

  // description: get all dividers added to the block
  // returns: an array of Label objects
  // see: addDivider()
  function getDividers() {
    return $this->dividers;
  }

  // description: add a divider
  // param: label: a label object. Optional
  // see: getDividers()
  function addDivider($label = "") {
    $this->dividers[] = $label;

    // find the number of HTML components before the divider on the page
    $components = $this->getHtmlComponents();

    $this->dividerIndexes[] = count($components);
  }

  function toHtml($style = "") {
    $page = $this->getPage();
    $i18n = $page->getI18n();

    if($style == null || $style->getPropertyNumber() == 0)
      $style = $this->getDefaultStyle($page->getStylist());

    // find out style properties
    $borderColor = $style->getProperty("borderColor");
    $borderThickness = $style->getProperty("borderThickness");
    $dividerAlign = $style->getProperty("dividerAlign");
    $dividerLabelStyle = $style->getSubstyle("dividerLabel");
    $dividerHeight = $style->getProperty("dividerHeight");
    $dividerStyleStr = $style->toBackgroundStyle("dividerCell");
    $htmlComponentStyleStr = $style->toBackgroundStyle("htmlComponentCell");

    $width = ($width == -1) ? $style->getProperty("width") : $this->width;

    $form = $page->getForm();
    $formId = $form->getId();


    // find all HTML components
    $components = $this->getHtmlComponents();

    // find all dividers
    $dividers = $this->getDividers();
    $dividerIndexes = $this->dividerIndexes;

    $result = "
<TABLE BGCOLOR=\"$borderColor\" BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\" WIDTH=\"$width\"><TR><TD>
<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"$borderThickness\" WIDTH=\"100%\">\n";

    for($i = 0; $i < count($components); $i++) {
      // add dividers
      for($j = 0; $j < count($dividers); $j++) {
	// divider at the right position?
	if($dividerIndexes[$j] == $i) {
	  $labelObj = $dividers[$j];
	  $label = is_object($labelObj) ? $labelObj->toHtml($dividerLabelStyle) : "";
	  $result .="<TR><TD ALIGN=\"$dividerAlign\" STYLE=\"$dividerStyleStr\" HEIGHT=\"$dividerHeight\"><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">$label</TD></TR>"; 
	}
      }

      // get HTML
      $componentHtml = $components[$i]->toHtml();

      $result .= "
	<TR>
	  <TD STYLE=\"$htmlComponentStyleStr\">$componentHtml</TD>
	</TR>
";
    }

    // add last dividers
    $componentCount = count($components);
    for($i = 0; $i < count($dividers); $i++) {
      // divider at the last position?
      if($dividerIndexes[$i] >= $componentCount) {
	$label = is_object($dividers[$i]) ? $dividers[$i]->toHtml($dividerLabelStyle) : "";
	$result .="<TR><TD STYLE=\"$dividerStyleStr\" HEIGHT=\"$dividerHeight\"><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">$label</TD></TR>"; 
      }
    }

    // make buttons
    $buttons = $this->getButtons();
    if(count($buttons) > 0) {
      $allButtons .= "<BR>";
      $allButtons .= "<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR>";

      for($i = 0; $i < count($buttons); $i++) {
	if($i > 0)
	  $allButtons .=  "<TD><IMG SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\" HEIGHT=\"5\"></TD>";
	$allButtons .= "<TD>".$buttons[$i]->toHtml()."</TD>";
      }

      $allButtons .= "</TR></TABLE>";
    }

    $result .= "</TABLE></TD></TR></TABLE>\n$allButtons\n";

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
