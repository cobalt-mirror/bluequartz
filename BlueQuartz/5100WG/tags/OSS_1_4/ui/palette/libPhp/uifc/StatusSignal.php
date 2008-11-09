<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: StatusSignal.php 3 2003-07-17 15:19:15Z will $

global $isStatusSignalDefined;
if($isStatusSignalDefined)
  return;
$isStatusSignalDefined = true;

include("uifc/HtmlComponent.php");

class StatusSignal extends HtmlComponent {
  //
  // private variables
  //

  var $status;
  var $url;
  var $described;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this object lives in
  // param: status: "none", "normal", "problem", "severeProblem", "new", 
  //	"disabled" "noMonitor" "replied", "old", 
  //    "success", "failure", "pending"
  // param: url: the url to which to link. Optional
  function StatusSignal($page, $status, $url = "") {
    // superclass constructor
    $this->HtmlComponent($page);

    $this->setStatus($status);

    $this->setUrl($url);

    $this->setDescribed(false);
  }

  function getCollatableValue() {
    $orderMap = array(
      "noMonitor" => 5, "disabled" => 4, "none" => 3, "normal" => 2, 
      "problem" => 1, "severeProblem" => 0,
      "new" => 10, "replied" => 11, "old" => 12,
      "success" => 20, "failure" => 21,
      "pending" => 30
    );

    return $orderMap[$this->status];
  }

  function getDefaultStyle($stylist) {
    return $stylist->getStyle("StatusSignal");
  }

  // description: get the status
  // returns: a string
  // see: setStatus()
  function getStatus() {
    return $this->status;
  }

  // description: set the status
  // param: status: a string. Possible values are "noMonitor", "disabled",
  //     "none", "normal", "problem", "severeProblem", "new", "replied", "old",
  //     "success", "failure", "pending"
  // see: getStatus()
  function setStatus($status) {
    $this->status = $status;
  }

  // description: set the URL to link to
  // param: url: the url to which to link
  function setUrl($url) {
    $this->url = $url;
  }

  // description: describe the signal to users if set to true
  // param: described: true if described, false otherwise
  // see: isDescribed()
  function setDescribed($described) {
    $this->described = $described;
  }

  // description: see if the signal is described to users
  // returns: true if described, false otherwise
  // see: setDescribed()
  function isDescribed() {
    return $this->described;
  }

  function toHtml($style = "") {
    $page = $this->getPage();
    $i18n = $page->getI18n();

    if($style == null || $style->getPropertyNumber() == 0)
      $style = $this->getDefaultStyle($page->getStylist());

    // find out image and description
    $image = "";
    $description = "";

    switch($this->getStatus()) {
      case "none":
	$imageProperty = "noneIcon";
	$descriptionId = "statusSignalNone";
	$mouseoverId = "statusSignalNone_help";
	break;

      case "disabled":
	$imageProperty = "disabledIcon";
	$descriptionId = "statusSignalDisabled";
        $mouseoverId = "statusSignalDisabled_help";
	break;

      case "noMonitor":
	$imageProperty = "noMonitorIcon";
	$descriptionId = "statusSignalNoMonitor";
        $mouseoverId = "statusSignalNoMonitor_help";
	break;

      case "normal":
	$imageProperty = "normalIcon";
	$descriptionId = "statusSignalNormal";
        $mouseoverId = "statusSignalNormal_help";
	break;

      case "problem":
	$imageProperty = "problemIcon";
	$descriptionId = "statusSignalProblem";
	$mouseoverId = "statusSignalProblem_help";
	break;

      case "severeProblem":
	$imageProperty = "severeProblemIcon";
	$descriptionId = "statusSignalSevereProblem";
	$mouseoverId = "statusSignalSevereProblem_help";
	break;

      case "new":
	$imageProperty = "newIcon";
	$descriptionId = "statusSignalNew";
        $mouseoverId = "statusSignalNew_help";
	break;

      case "replied":
	$imageProperty = "repliedIcon";
	$descriptionId = "statusSignalReplied";
        $mouseoverId = "statusSignalReplied_help";
	break;

      case "old":
	$imageProperty = "oldIcon";
	$descriptionId = "statusSignalOld";
        $mouseoverId = "statusSignalOld_help";
	break;

      case "success":
	$imageProperty = "successIcon";
	$descriptionId = "statusSignalSuccess";
	$mouseoverId = "statusSignalSuccess_help";
	break;

      case "failure":
	$imageProperty = "failureIcon";
	$descriptionId = "statusSignalFailure";
	$mouseoverId = "statusSignalFailure_help";
	break;

      case "pending":
	$imageProperty = "pendingIcon";
	$descriptionId = "statusSignalPending";
	$mouseoverId = "statusSignalPending_help";
	break;
    }

    $image = $style->getProperty($imageProperty);
    $description = $i18n->get($descriptionId, "palette");

    $hasDescription = $this->isDescribed();
    if ($mouseoverId && $hasDescription)
        $mouseTag = "onMouseOver=\"return top.code.info_mouseOver('" . $i18n->get($mouseoverId, "palette") . "')\" onMouseOut=\"return top.code.info_mouseOut();\"";


    $retstr = "";
    if (!($url = $this->url))
      $url = "javascript: void(0);";
   
    $retstr = "<A HREF=\"$url\" $mouseTag>";

    $retstr = $retstr 
	. "<IMG ALT=\"$description\" BORDER=\"0\" SRC=\"$image\">";

    $retstr = $retstr . "</A>";

    return $retstr;
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

