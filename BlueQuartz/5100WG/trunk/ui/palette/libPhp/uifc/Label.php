<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Label.php 237 2003-09-10 08:22:45Z shibuya $

global $isLabelDefined;
if($isLabelDefined)
  return;
$isLabelDefined = true;

include("System.php");
include("uifc/HtmlComponent.php");

// also implements Collatable
class Label extends HtmlComponent {
  //
  // private variables
  //

  var $label;
  var $description;

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
    $this->setDescription($description);
  }

  function getCollatableValue() {
    return $this->label;
  }

  function getDefaultStyle($stylist) {
    return $stylist->getStyle("Label");
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
    global $_LABEL_ID; 
    $_LABEL_ID++;
    $id = $_LABEL_ID;

    if($style == null || $style->getPropertyNumber() == 0)
      $style =& $this->getDefaultStyle($page->getStylist());

    // find out style properties
    $styleStr = $style->toBackgroundStyle().$style->toTextStyle();

    $i18n =& $page->getI18n();

    // The HTML_ENTITIES translation table is only valid for the 
    // ISO-8859-1 character set. Japanese is the only supported 
    // language which does not use the ISO-8859-1 charset, so we 
    // do a special case for that.
    $encoding = $i18n->getProperty("encoding", "palette");
    if ($encoding == "none" || !strpos($encoding, "8859-1") === false ) {
      $specialChars = array_merge(get_html_translation_table(HTML_SPECIALCHARS), get_html_translation_table(HTML_ENTITIES));
      $label = strtr(strtr($this->getLabel(), array_flip($specialChars)), $specialChars);
    } else {
      $label = htmlspecialchars($this->getLabel());
    }
    // using interpolateJs this way is not very clean, but this works for now
    $description = $i18n->interpolateJs("[[VAR.string]]", array("string" => $this->getDescription()));

    if($description == null || $description == "")
      return "<FONT STYLE=\"$styleStr\">$label</FONT>"; 
    else {
      // log activity if necessary
      $logMouseOver = "";
      $logMouseOut = "";
      $logClick = "";
      $system = new System();
      if($system->getConfig("logPath") != "") {
	$logMouseOver = "top.code.uiLog_log('mouseOver', 'Label', '$label');";
	$logMouseOut = "top.code.uiLog_log('mouseOut', 'Label', '$label');";
	$logClick = "onClick=\"top.code.uiLog_log('click', 'Label', '$label');\"";
      }

      $javascript = "document._label_".$id."_description = '$description';"; 
      return "\n<SCRIPT language=\"javascript\">\n\t$javascript \n</SCRIPT>\n<A STYLE=\"$styleStr\" HREF=\"javascript: void 0\" onMouseOver=\"$logMouseOver return top.code.info_mouseOver(document._label_".$id."_description)\" onMouseOut=\"$logMouseOut return top.code.info_mouseOut();\" $logClick><div STYLE=\"$styleStr\">$label</div></A>";
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
