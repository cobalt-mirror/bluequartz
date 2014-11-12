<?php
// $Id: PlainBlock.php 1050 2008-01-23 11:45:43Z mstauber $

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