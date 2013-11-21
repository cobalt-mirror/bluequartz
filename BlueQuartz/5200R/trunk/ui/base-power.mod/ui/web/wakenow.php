<?php
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: wakenow.php 1419 2010-03-10 14:40:29Z shibuya $

include_once("ServerScriptHelper.php");
include_once("uifc/Label.php");
include_once("uifc/TextList.php");
include_once("uifc/Button.php");

$serverScriptHelper = new ServerScriptHelper();

//Only users with serverPower capability should be here
if (!$serverScriptHelper->getAllowed('serverPower')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();

if (!$action) {
  $action = "display";
}

// if $useExistingData is true, the page will display the data
// that exists in global variables, rather than looking up the data
// in CCE. That enables us to return errors without blowing away
// all user data.
$useExistingData = ($action == "commit") ? true : false;

//HANDLE PAGE
if ($action == "commit") {
  $errors = handlePage($mac_addresses);

  genPage($useExistingData, $errors);

} else if ($action == "display") {

//DISPLAY PAGE
  genPage($useExistingData);
}

$serverScriptHelper->destructor();
  
exit;




function genPage($useExistingData, $errors = array()) {
  global $cceClient;
  global $serverScriptHelper;
  global $mac_addresses;

  $factory = $serverScriptHelper->getHtmlComponentFactory("base-power", "/base/power/wakenow.php?action=commit");
  
  if (!$useExistingData) {
    list($sysoid) = $cceClient->find("System");
    $cceWakeOpts = $cceClient->get($sysoid, "Power");
    $mac_addresses = $cceWakeOpts["wake_macaddresses"];
  }
  
  $page = $factory->getPage();
  
  $wakeNowBlock = $factory->getPagedBlock("wakeNow_block");

  //using a multichoice dropdown
  
  $macList = $factory->getTextList("mac_addresses", $mac_addresses, "rw");
  $wakeNowBlock->addFormField($macList, $factory->getLabel("mac_addresses"));
  $wakeNowBlock->addButton($factory->getButton($page->getSubmitAction(), "wakenow_button"));
  
  //Add error handling to page
  $wakeNowBlock->process_errors($errors, array("wake_macaddresses" => "mac_addresses"));

  print($page->toHeaderHtml());
  print($wakeNowBlock->toHtml());
  print($page->toFooterHtml()); 
}



function handlePage($mac_addresses) {
  global $cceClient;

  $cceClient->setObject("System", array("wake_macaddresses" => $mac_addresses, "wake_now" => time()), "Power");
  return $cceClient->errors();
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
