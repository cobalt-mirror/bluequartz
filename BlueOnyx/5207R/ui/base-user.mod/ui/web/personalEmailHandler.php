<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: personalEmailHandler.php 1163 2008-06-29 19:00:27Z mstauber $

include_once("ServerScriptHelper.php");
include_once("BXEncoding.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

$errors = array();

// boolean cce values are only 0 or 1
if($autoResponderField) {
  $autoResponderField = "1";
} else {
  $autoResponderField = "0";
}
if($forwardEnableField) {
  $forwardEnableField = "1";
} else {
  $forwardEnableField = "0";
}

if ($_autoRespondStartDate_amPm == "PM") { 
  $_autoRespondStartDate_hour = $_autoRespondStartDate_hour + 12; 
 } 

if ($_autoRespondStopDate_amPm == "PM") { 
  $_autoRespondStopDate_hour = $_autoRespondStopDate_hour + 12; 
 } 

$vacationMsgStart = mktime($_autoRespondStartDate_hour, $_autoRespondStartDate_minute, 
         $_autoRespondStartDate_second, $_autoRespondStartDate_month, 
         $_autoRespondStartDate_day, $_autoRespondStartDate_year); 
$vacationMsgStop = mktime($_autoRespondStopDate_hour, $_autoRespondStopDate_minute, 
        $_autoRespondStopDate_second, $_autoRespondStopDate_month, 
        $_autoRespondStopDate_day, $_autoRespondStopDate_year); 

if (($vacationMsgStop - $vacationMsgStart) < 0) { 
  $vacationMsgStop = $oldStop; 
  
  $error_msg = "[[base-user.invalidVacationDate]]"; 
  $errors[] = new Error($error_msg); 
 } 

$cceClient->setObject("User", array(
  "forwardEnable" => $forwardEnableField, 
  "forwardEmail" => $forwardEmailField, 
  "forwardSave" => $forwardSaveField,
  "vacationOn" => $autoResponderField, 
        "vacationMsg" => BXEncoding::toUTF8($autoResponderMessageField), 
  "vacationMsgStart" => $vacationMsgStart, 
  "vacationMsgStop" =>$vacationMsgStop),  
  "Email", 
  array("name" => $serverScriptHelper->getLoginName()));
$errors = array_merge($cceClient->errors(), $errors);

print($serverScriptHelper->toHandlerHtml("personalEmail.php", $errors));

$serverScriptHelper->destructor();
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