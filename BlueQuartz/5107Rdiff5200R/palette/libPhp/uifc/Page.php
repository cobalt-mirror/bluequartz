<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Page.php 1184 2008-09-10 21:23:19Z mstauber $

// description:
// This class represents a page on the user interface. It also encapsulates all
// information about the page. For example, a Stylist object and an I18n object
// resides in each Page object.
//
// applicability:
// Every page on the user interface that uses UIFC.
//
// usage:
// All UIFC pages must have one and only one page object. All toHtml() calls
// of any HtmlComponent must reside within the toHeaderHtml() and
// toFooterHtml() calls of the page object. Otherwise, undefined result can
// happen.

global $isPageDefined;
if($isPageDefined)
  return;
$isPageDefined = true;

include_once("System.php");
include_once("uifc/Form.php");

class Page {
  //
  // private variables
  //

  var $form;
  var $i18n;
  var $stylist;
  var $onLoad;

  //
  // public methods
  //

  // description: constructor
  // param: stylist: a Stylist object that defines the style
  // param: i18n: an I18n object for internationalization
  // param: formAction: the action of the Form object this Page has. Optional
  function Page(&$stylist, &$i18n, $formAction) {
    $this->setStylist($stylist);
    $this->setI18n($i18n);

    $this->form = new Form($this, $formAction);
    $this->onLoad = false;
  }

  function &getDefaultStyle(&$stylist) {
    return $stylist->getStyle("Page");
  }

  // description: get the form embedded in the page
  // returns: a Form object
  function &getForm() {
    return $this->form;
  }

  // description: get the I18n object used to internationalize this page
  // returns: an I18n object
  // see: setI18n()
  function &getI18n() {
    return $this->i18n;
  }

  // description: set the I18n object used to internationalize this page
  // param: i18n: an I18n object
  // see: getI18n()
  function setI18n(&$i18n) {
    $this->i18n =& $i18n;
  }

  // description: set Javascript to be performed when the page loads
  // param: js: a string of Javascript code
  function setOnLoad($js) {
	$this->onLoad = $js;
  }

  // description: get the stylist that stylize the page
  // returns: a Stylist object
  // see: setStylist()
  function &getStylist() {
    return $this->stylist;
  }

  // description: set the stylist that stylize the page
  // param: stylist: a Stylist object
  // see: getStylist()
  function setStylist(&$stylist) {
    $this->stylist =& $stylist;
  }

  // description: get the submit action that submits the form in this page
  // returns: a string
  function getSubmitAction() {
    $form =& $this->getForm();
    return $form->getSubmitAction();
  }

  // description: get the target of the embedded form to submit to
  // returns: a string
  // see: setSubmitTarget()
  function getSubmitTarget() {
    $form =& $this->getForm();
    return $form->getTarget();
  }

  // description: set the target of the embedded form to submit to
  // returns: a string
  // see: getSubmitTarget()
  function setSubmitTarget($target) {
    $this->form->setTarget($target);
  }

  // description: translate the header of the page into HTML representation
  // param: style: a Style object that defines the style of the representation
  //     Optional. If not supplied, default style is used
  // returns: HTML in string
  function toHeaderHtml($style = "") {
    if($style == null || $style->getPropertyNumber() == 0)
      $style =& $this->getDefaultStyle($this->getStylist());

    // find out style properties
    $aLinkColor = $style->getProperty("aLinkColor");
    $backgroundStyleStr = $style->toBackgroundStyle();
    $center = ($style->getProperty("center") == "true") ? "<CENTER>" : "";
    $textStyleStr = $style->toTextStyle();

    $form =& $this->getForm();
    $formHeader = $form->toHeaderHtml();

    $onLoad = $this->onLoad;
    if (!$onLoad) { $onLoad = "return true"; };

    // super paranoid about turning off caching
    // See http://support.microsoft.com/support/kb/articles/Q234/0/67.asp
    header("cache-control: no-cache");

    //make really sure the browser knows what we are giving it.
    $lang=$this->i18n->getLocales();
    header("Content-language: $lang[0]");
    if(($encoding=$this->i18n->getProperty("encoding","palette"))!="none")
	header("Content-type: text/html; charset=$encoding");

    // log activity if necessary
    $logLoad = "";
    $logUnload = "";
    $system = new System();
    if($system->getConfig("logPath") != "") {
      $logLoad = "if (top.code.uiLog_log != null) { top.code.uiLog_log('load', 'Page', location.href); }";
      $logUnload = "onUnload=\"if (top.code.uiLog_log != null) { top.code.uiLog_log('unload', 'Page', location.href); } \"";
    }

    return "
<HTML>
<HEAD>
<META HTTP-EQUIV=\"expires\" CONTENT=\"-1\">
<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
</HEAD>

  <script language=\"Javascript\" type=\"text/javascript\" src=\"/libJs/ajax_lib.js\"></script>
  <script language=\"Javascript\">
    <!--
      checkpassOBJ = function() {
        this.onFailure = function() {
          alert(\"Unable to validate password\");
        }
        this.OnSuccess = function() {
          var response = this.GetResponseText();
          document.getElementById(\"results\").innerHTML = response;
        }
      }


      function validate_password ( word ) {
        checkpassOBJ.prototype = new ajax_lib();
        checkpass = new checkpassOBJ();
        var URL = \"/uifc/check_password.php\";
        var PARAM = \"password=\" + word;
        checkpass.post(URL, PARAM);
      }

    //-->
  </script>

<SCRIPT LANGUAGE=\"javascript\">
// top.code may not exist yet
if(top.code != null && top.code.css_captureEvents != null)
  top.code.css_captureEvents(window);
</SCRIPT>

<BODY ALINK=\"$aLinkColor\" onLoad=\"$logLoad $onLoad\" $logUnload STYLE=\"$backgroundStyleStr\">
<FONT STYLE=\"$textStyleStr\">
$center
$formHeader
<BR>
";
  }

  // description: translate the footer of the page into HTML representation
  // param: style: a Style object that defines the style of the representation
  //     Optional. If not supplied, default style is used
  // returns: HTML in string
  function toFooterHtml($style = "") {
    if($style == null || $style->getPropertyNumber() == 0)
      $style =& $this->getDefaultStyle($this->getStylist());

    // find out style properties
    $center = ($style->getProperty("center")) ? "</CENTER>" : "";

    $form =& $this->getForm();
    $formFooter = $form->toFooterHtml();

    // Pragma: no-cache again because IE is buggy.
    // See http://support.microsoft.com/support/kb/articles/Q222/0/64.ASP
    // No longer needed. Removing trailing head section - mstauber
    return "
$formFooter
$center
</FONT>
</BODY>
</HTML>
";
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
