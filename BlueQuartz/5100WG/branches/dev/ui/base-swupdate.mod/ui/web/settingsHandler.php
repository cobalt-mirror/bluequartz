<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: settingsHandler.php 201 2003-07-18 19:11:07Z will $

include("ArrayPacker.php");
include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

$intervalMap = array("never" => "Never", "daily" => "Daily", "weekly" => "Weekly", "monthly" => "Monthly");

$notifyModeMap = array("all" => "AllNew", "updates" => "UpdatesOnly");

$checkSetMap = array("all" => "All", "updates" => "Updates");

$settings = array(
	"updateInterval" => $intervalMap[$scheduleField],
        "httpProxy" => $httpProxyField, 
	"ftpProxy" => $ftpProxyField,
        "requireSignature" => $requireSignatureField
	);

// Use ActiveMonitor's email contact list if possible
$am_obj = $cceClient->getObject('ActiveMonitor', array(), '');
if( ! $am_obj["alertEmailList"] )
	$settings["updateEmailNotification"] = $emailField;

$cceClient->setObject("System", $settings, "SWUpdate");
$errors = $cceClient->errors();

// remove all the existing servers first
$cceClient->destroyObjects("SWUpdateServer");

// add back all the specified ones
$servers = stringToArray($serverField);
for($i = 0; $i < count($servers); $i++) {
  $cceClient->create("SWUpdateServer", array("location" => $servers[$i],
    "notificationMode" => $notifyModeMap[$notificationLightField], "orderPreference" => $i+1));
  $errors = array_merge($errors, $cceClient->errors());
}

print($serverScriptHelper->toHandlerHtml("/base/swupdate/settings.php", $errors));

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

