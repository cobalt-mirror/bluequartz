<?php
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: poweroptions.php

include_once("ServerScriptHelper.php");
include_once("uifc/Label.php");
include_once("uifc/Option.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

//Only users with 'serverPower' capability should be here
if (!$serverScriptHelper->getAllowed('serverPower')) {
  header("location: /error/forbidden.html");
  return;
}

if (!$action) {
  $action = "display";
}

// if $useExistingData is true, the page will display the data
// that exists in global variables, rather than looking up the data
// in CCE. That enables us to return errors without blowing away
// all user data.
$useExistingData = ($action == "commit") ? true : false;


// HANDLE PAGE
if ($action == "commit") {
  $errors = handlePage($powermode, $wakemode);

  genPage($useExistingData, $errors);

} else if ($action == "display") {

// DISPLAY PAGE
  genPage($useExistingData);
}

$serverScriptHelper->destructor();
exit;



function genPage($useExistingData, $errors = array()) {
  global $cceClient;
  global $serverScriptHelper;
  global $powermode;

  $factory = $serverScriptHelper->getHtmlComponentFactory("base-power", "/base/power/poweroptions.php?action=commit");
  $i18n = $factory->getI18n();

  if (!$useExistingData) {
    list($sysoid) = $cceClient->find("System");
    $ccePowerOpts = $cceClient->get($sysoid, "Power");
    $powermode = $ccePowerOpts["powermode"];
    $wakemode = $ccePowerOpts["wakemode"];
  }

  $page = $factory->getPage();

/*  
  $block = $factory->getPagedBlock("powerOptions_block");
  
  //using a multichoice dropdown
  
  $powerModeField = $factory->getMultiChoice("powermode");
  $powerModeField->setFullSize(true);

  $sameOption = $factory->getOption("same", $powermode == "same");
  $powerModeField->addOption($sameOption);
  
  $offOption = $factory->getOption("off", $powermode == "off");
  $powerModeField->addOption($offOption);
  
  $onOption = $factory->getOption("on", $powermode == "on");
  $powerModeField->addOption($onOption);
  
  $block->addFormField($powerModeField, $factory->getLabel("powermode"));
  
  $block->addButton($factory->getSaveButton($page->getSubmitAction()));

  //using a multichoice dropdown
   
  $wakeModeField = $factory->getMultiChoice("wakemode");
  $wakeModeField->setFullSize(true);
  
  $noneOption = $factory->getOption("none", $wakemode == "none");
  $wakeModeField->addOption($noneOption);
  
  $magicOption = $factory->getOption("magic", $wakemode == "magic");
  $wakeModeField->addOption($magicOption);

  $block->addFormField($wakeModeField, $factory->getLabel("wakemode"));
  
  //Add error handling to page
  $block->process_errors($errors, array("wakemode" => "wakemode", "powermode" => "powermode"));
*/

  //Reboot and Shutdown buttons
  //create the button
  $rebootbutton = $factory->getButton("javascript: confirmReboot()", "reboot");
  
  //confirmation string
  $confirm =  $i18n->get("askRebootConfirmation");
  
  //create the script
  $rebootScript =
"    <SCRIPT LANGUAGE=\"javascript\">
    function confirmReboot()
  {
    if (confirm(\"$confirm\"))
      {
	location = \"/base/power/rebootHandler.php\"
	  }
  }
  </SCRIPT>";

  //Shutdown button
  $shutdownbutton = $factory->getButton("javascript: confirmShutdown()", "shutdown_menu");

  //confirmation string
  $confirm =  $i18n->get("askShutdownConfirmation");

  //create the script
  $shutdownScript =
"    <SCRIPT LANGUAGE=\"javascript\">
    function confirmShutdown()
  {
    if (confirm(\"$confirm\"))
      {
       location = \"/base/power/shutdownHandler.php\"
         }
  }
  </SCRIPT>";
 
  $buttons = $factory->getCompositeFormField(array($rebootbutton, $shutdownbutton), '&nbsp;&nbsp;&nbsp;&nbsp;');

  print($page->toHeaderHtml());
  print($buttons->toHtml());
  print($rebootScript);
  print($shutdownScript);
  print("<BR>\n");
//  print($block->toHtml()); 
  print($page->toFooterHtml()); 
}


function handlePage($powermode, $wakemode) {
  global $cceClient;

  $cceClient->setObject("System", array("powermode" => $powermode, "wakemode" => $wakemode), "Power");

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
