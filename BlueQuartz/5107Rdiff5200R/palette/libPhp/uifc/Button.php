<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Button.php 995 2007-05-05 07:44:27Z shibuya $

global $isButtonDefined;
if($isButtonDefined)
  return;
$isButtonDefined = true;

include_once("uifc/HtmlComponent.php");

class Button extends HtmlComponent {
  //
  // private variables
  //

  var $action;
  var $isDisabled;
  var $isHeader;
  var $label;
  var $labelDisabled;
  var $targetFrame;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this object lives in
  // param: action: the string used within HREF attribute of the A tag
  // param: label: a Label object for the normal state
  // param: labelDisabled: a Label object for the disabled state. Optional
  //     If not supplied, it is the same as the label parameter
  function Button(&$page, $action, &$label, $labelDisabled = "") {
    // superclass constructor
    $this->HtmlComponent($page);

    $this->setAction($action);
    $this->setLabel($label, $labelDisabled);
    $this->setTarget("");

    // set defaults
    $this->setDisabled(false);
  }

  // description: get the action to perform when the button is pressed
  // return: an URL
  // see: setAction()
  function getAction() {
    return $this->action;
  }

  // description: set the action to perform when the button is pressed
  // param: action: an URL
  // see: getAction()
  function setAction($action) {
    $this->action = $action;
  }

  // description: set the target frame of the action
  // param: target: a string
  // see: getTarget()
  function setTarget($target) {
    $this->targetFrame = $target;
  }

  // description: get the target frame of the action
  // returns: a string
  // see: setTarget()
  function getTarget() {
    return $this->targetFrame;
  }

  function setDescription($description)
  {
    $this->label->setDescription($description);
  }
  
  function setDisabledDescription($description)
  {
    $this->labelDisabled->setDescription($description);
  }

  function &getDefaultStyle($stylist) {
    return $stylist->getStyle("Button");
  }

  // description: see if the button is disabled
  // returns: true if the button is disabled, false otherwise
  // see: setDisabled()
  function isDisabled() {
    return $this->isDisabled;
  }

  // description: set the disabled flag
  // param: disabled: true if the button is disabled, false otherwise
  // see: isDisabled()
  function setDisabled($isDisabled) {
    $this->isDisabled = $isDisabled;
  }

  // description: see if the button uses the header style
  // returns: true if the button is a header button, false otherwise
  // see: setHeader()
  function isHeader() {
    return $this->isHeader;
  }

  // description: set the header style for a button
  // param: disabled: true if the button is a header button, false otherwise
  // see: isHeader()
  function setHeader($isHeader) {
    $this->isHeader = $isHeader;
  }

  // description: get the label for normal state of the button
  // returns: a Label object
  // see: setLabel()
  function &getLabel() {
    return $this->label;
  }

  // description: get the label for disabled state of the button
  // returns: a Label object
  // see: setLabel()
  function &getLabelDisabled() {
    return $this->labelDisabled;
  }

  // description: set the label for the button
  // param: label: a Label object for the normal state
  // param: labelDisabled: a Label object for the disabled state. Optional
  //     If not supplied, it is the same as the label parameter
  function setLabel(&$label, $labelDisabled = "") {
    $this->label =& $label;
    $this->labelDisabled = ($labelDisabled != "") ? $labelDisabled : $label;
  }

  function toHtml($style = "") {
    $page =& $this->getPage();

    if($style == null || $style->getPropertyNumber() == 0)
      $style = $this->getDefaultStyle($page->getStylist());

    $isDisabled = $this->isDisabled();
    $isHeader = $this->isHeader();

    // find the right style target
    $useHeaderButton = $style->getProperty("useHeaderButton");
    if ($isHeader && $useHeaderButton) {
        $target = $isDisabled ? "headerDisabled" : "header";
    } else {
    $target = $isDisabled ? "disabled" : "normal";
    }

    // find out style properties
    $styleStr = $style->toBackgroundStyle($target).$style->toTextStyle($target);
    $leftImage = $style->getProperty("leftImage", $target);
    $rightImage = $style->getProperty("rightImage", $target);
    $fillImage = $style->getProperty("fillImage", $target);
    $textDecoration = $style->getProperty("textDecoration", $target);
    // Remove unneeded width and height parameters
    //$fillImage = substr($fillImage, 0, strpos($fillImage, "\""));
    $labelStyle = $style->getSubstyle($target);

    if($isDisabled) {
      $label =& $this->getLabelDisabled();
      $labelHtml = $label->toHtml($labelStyle);

      // make cells with images
      $leftImageCell = ($leftImage != "") ? "<TD><IMG BORDER=\"0\" SRC=\"$leftImage\"></TD>" : "";
      $rightImageCell = ($rightImage != "") ? "<TD><IMG BORDER=\"0\" SRC=\"$rightImage\"></TD>" : "";
      $fillImageTag = ($fillImage != "") ? "background=\"$fillImage\"" : "";
	


      return "
<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR>
  $leftImageCell
  <TD NOWRAP STYLE=\"$styleStr\" $fillImageTag>$labelHtml</TD>
  $rightImageCell
</TR></TABLE>
";
    }
    else {
      $action = $this->getAction();
      $label =& $this->getLabel();
      $description = $label->getDescription(); 
      global $_BUTTON_ID; 
      $_BUTTON_ID++;
      $id = $_BUTTON_ID;

      // log activity if necessary
      $system = new System();
      if($system->getConfig("logPath") != "") {
	$labelText = $label->getLabel(); 
	$logClick = "top.code.uiLog_log('click', 'Button', '$labelText');";
	$logMouseOver = "top.code.uiLog_log('mouseOver', 'Button', '$labelText');";
	$logMouseOut = "top.code.uiLog_log('mouseOut', 'Button', '$labelText');";
      }

      $click = "$logClick return true;";
      $mouseOver = "$logMouseOver return true;";
      $mouseOut = "$logMouseOver return true;";
      if($description) {
	$click = "$logClick return top.code.info_click();";

	// Javascript definitions were bypassing toHtml..  
        $i18n =& $page->getI18n();
        $langs = $i18n->getLocales();
        // The HTML_ENTITIES translation table is only valid for the
        // ISO-8859-1 character set. Japanese is the only supported
        // language which does not use the ISO-8859-1 charset, so we
        // do a special case for that.
       $encoding = $i18n->getProperty("encoding", "palette");
       if ($encoding == "none" || !strpos($encoding, "8859-1") === false ) {
          $specialChars = array_merge(get_html_translation_table(HTML_SPECIALCHARS), get_html_translation_table(HTML_ENTITIES));
          $escaped_description = strtr(strtr($description, array_flip($specialChars)), $specialChars);
        } else {
          $description = htmlspecialchars($description);
        }
        // using interpolateJs this way is not very clean, but this works for now
        $escaped_description = $i18n->interpolateJs("[[VAR.string]]", array("string" => $description));

        $javascript .="document._button_description_$id = '$escaped_description'";
        $mouseOver = "$logMouseOver return top.code.info_mouseOver(document._button_description_$id)";
	$mouseOut = "$logMouseOut return top.code.info_mouseOut();";

	// clear up description temporarily because the rollover help of the
	// label prevents button click-through
	$label->setDescription("");
      }

      $labelHtml = $label->toHtml($labelStyle);

      // restore description if necessary
      if($description)
	$label->setDescription($description);

      $targetFrame = $this->targetFrame;
      if ($targetFrame)
        $targetString = " TARGET=\"$targetFrame\" ";

	  // deal with Netscape/Gecko based browsers special a tag needs
	  $cssLinkStyle = ($textDecoration != "") ? "style=\"text-decoration: none\"" : "";

      $linkHtml = "<A HREF=\"$action\" $cssLinkStyle onClick=\"$click\" $targetString onMouseOver=\"$mouseOver\" onMouseOut=\"$mouseOut\">$labelHtml</A>";
      if (get_class($label) == "imagelabel" || is_subclass_of($label, "imagelabel")) {
        // This is an image we are dealing with, skip the button border stuff
	return "
<SCRIPT language=\"javascript\">
$javascript
</SCRIPT>
$linkHtml
";
      } else {
        // this is not an image, construct the button borders
        // make cells with images
	$leftImageCell = ($leftImage != "") ? "<TD><A HREF=\"$action\" $targetString onClick=\"$click\" onMouseOver=\"$mouseOver\"  onMouseOut=\"$mouseOut\"><IMG BORDER=\"0\" SRC=\"$leftImage\"></A></TD>" :""; 
	$rightImageCell = ($rightImage != "") ? "<TD><A HREF=\"$action\" $targetString onClick=\"$click\" onMouseOver=\"$mouseOver;\"  onMouseOut=\"$mouseOut\"><IMG BORDER=\"0\" SRC=\"$rightImage\"></A></TD>" : "";
    $fillImageTag = ($fillImage != "") ? "background=\"$fillImage\"" : "";
  
        return " 
<SCRIPT language=\"javascript\">
$javascript
</SCRIPT>
<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR>
  $leftImageCell
  <TD NOWRAP STYLE=\"$styleStr\" $fillImageTag valign=\"middle\" >$linkHtml</TD>
  $rightImageCell
</TR></TABLE>
";
      }
    }
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
