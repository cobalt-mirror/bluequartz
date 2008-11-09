<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: status.php 259 2004-01-03 06:28:40Z shibuya $

// description:
// This is a generic page for showing status.
//
// usage:
// A normal lifecycle of this page involves initialization, refresh loop and
// garbage collection phrases.
// First call to this page is the initialization phrase. URL variables are read
// and being put into a status file. The list of variables are the same as
// those in status file (shown below). After initialization, the page goes into
// a refresh loop until the isNoRefresh flag is set and garbage collect the
// status file if isNoGarbageCollect is not set.
// In the refresh loop, this page takes one URL variable "statusId", then it
// uses this ID to open a status file <statusId> under statusDir defined in
//  ui.cfg. "statusId" can only contain alphanumeric characters.
// The status file basically contains key-value pairs in the format
// "<key>: <value>". Each pair sits in one line should not be longer than 1024
// bytes. The possible pairs are:
// title
//   Internationalizable string for the title
// message
//   Internationalizable string that tells what the status is.
// progress
//   Percentage complete of the status. It is an integer between 0 and 100.
// submessage
//   Internationalizable string that tells what the sub-status is. Optional if
//   sub-status is not necessary.
// subprogress
//   Percentage complete of the status. It is an integer between 0 and 100.
//   Optional if sub-status is not necessary.
// backUrl
//   If supplied, a back button with this URL as action is added to the page.
//   Optional.
// cancelUrl
//   If supplied, a cancel button with this URL as action is added to the page.
//   Optional.
// redirectUrl
//   Direct to this URL if supplied. Optional.
// isNoRefresh
//   "true" or "false". If "true", status page stops refresh. Optional and
//   "false" by default.
// isNoGabageCollect
//   "true" or "false". If not "true", the status file is cleaned up when the
//   page stops refresh.

include("ServerScriptHelper.php");
include("uifc/PagedBlock.php");

// good status ID must exist
// It can only contain alphanumeric characters for security reasons (e.g.
// no /../)
if(!preg_match("/^[a-zA-Z0-9]+$/", $statusId)) {
  print("<HTML><BODY>ERROR: Bad statusId \"$statusId\"</BODY></HTML>");
  return;
}

// get the full path of the status file
include("System.php");
$system = new System();
$statusDir = $system->getConfig("statusDir");
$statusPath = "$statusDir/$statusId";

// initialize status file if these variables exist
if($title != "" || $message != "" || $progress != "" || $submessage != "" || $subprogress != "" || $backUrl != "" || $cancelUrl != "" || $redirectUrl != "" || $isNoRefresh != "" || $isNoGarbageCollect != "") {
  // make sure directory exist
  if(!is_dir($statusDir))
    mkdir($statusDir, 0755);

  $handle = fopen($statusPath, "w");
  fwrite($handle, "title: $title\n");
  fwrite($handle, "message: $message\n");
  fwrite($handle, "progress: $progress\n");
  fwrite($handle, "submessage: $submessage\n");
  fwrite($handle, "subprogress: $subprogress\n");
  fwrite($handle, "backUrl: $backUrl\n");
  fwrite($handle, "cancelUrl: $cancelUrl\n");
  fwrite($handle, "redirectUrl: $redirectUrl\n");
  fwrite($handle, "isNoRefresh: $isNoRefresh\n");
  fwrite($handle, "isNoGarbageCollect: $isNoGarbageCollect\n");
  fclose($handle);
}
else {
  // initialize values because they may not be obtained from the status file
  $title = "";
  $message = "";
  $progress = "";
  $submessage = "";
  $subprogress = "";
  $backUrl = "";
  $cancelUrl = "";
  $redirectUrl = "";
  $isNoRefresh = "false";
  $isNoGarbageCollect = "false";

  if(is_file($statusPath)) {
    // read status file
    $handle = fopen($statusPath, "r");
    while(!feof($handle)) {
      $line = fgets($handle, 1024);

      // skip empty lines
      if(strlen($line) == 0) continue;

      // skip comments
      if(preg_match("/^\s*\#/", $line)) continue;

      // read all the values
      if(preg_match("/^title:\s(.+)$/", $line, $matches))
	$title = $matches[1];
      if(preg_match("/^message:\s(.+)$/", $line, $matches))
	$message = $matches[1];
      if(preg_match("/^progress:\s(.+)$/", $line, $matches))
	$progress = $matches[1];
      if(preg_match("/^submessage:\s(.+)$/", $line, $matches))
	$submessage = $matches[1];
      if(preg_match("/^subprogress:\s(.+)$/", $line, $matches))
	$subprogress = $matches[1];
      if(preg_match("/^backUrl:\s(.+)$/", $line, $matches))
	$backUrl = $matches[1];
      if(preg_match("/^cancelUrl:\s(.+)$/", $line, $matches))
	$cancelUrl = $matches[1];
      if(preg_match("/^redirectUrl:\s(.+)$/", $line, $matches))
	$redirectUrl = $matches[1];
      if(preg_match("/^isNoRefresh:\s(.+)$/", $line, $matches))
	$isNoRefresh = $matches[1];
      if(preg_match("/^isNoGarbageCollect:\s(.+)$/", $line, $matches))
	$isNoGarbageCollect = $matches[1];
    }
    fclose($handle);
  }
}

// garbage collect
if($isNoRefresh == "true" && $isNoGarbageCollect != "true")
  unlink($statusPath);

// redirect
if($redirectUrl != "") {
  header("location: $redirectUrl");
  return;
}

$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("palette");
$i18n = $serverScriptHelper->getI18n("palette");

$page = $factory->getPage();

$block = new PagedBlock($page, "status", $factory->getLabel($title, false));

$block->addFormField(
  $factory->getTextField("messageField", $i18n->interpolate($message), "r"),
  $factory->getLabel("messageField")
);

if($progress != "") {
  $block->addFormField(
    $factory->getBar("progressField", $progress),
    $factory->getLabel("progressField")
  );
}

// add sub-status if it is supplied
if($submessage != "") {
  $block->addFormField(
    $factory->getTextField("submessageField", $i18n->interpolate($submessage), "r"),
    $factory->getLabel("submessageField")
  );
}

if($subprogress != "") {
  $block->addFormField(
    $factory->getBar("subprogressField", $subprogress),
    $factory->getLabel("subprogressField")
  );
}

if($backUrl != "")
  $block->addButton($factory->getBackButton($backUrl));

if($cancelUrl != "")
  $block->addButton($factory->getCancelButton($cancelUrl));

$serverScriptHelper->destructor();

// print the page
print($page->toHeaderHtml());

print($block->toHtml());

// reload if not finished
if($isNoRefresh != "true")
  print("<SCRIPT LANGUAGE=\"javascript\">setTimeout(\"location = '".$HTTP_SERVER_VARS["SCRIPT_NAME"]."?statusId=$statusId'\", 3000);</SCRIPT>");

print($page->toFooterHtml());
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
