<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: settings.php 259 2004-01-03 06:28:40Z shibuya $

include("ArrayPacker.php");
include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-swupdate", "/base/swupdate/settingsHandler.php");

// get settings
$swUpdate = $cceClient->getObject("System", array(), "SWUpdate");
// we use the first server object as the default for properties like proxies
// because they have the same value anyway. These properties should actually be
// in System.SWUpdate
$oids = $cceClient->findNSorted("SWUpdateServer", "orderPreference");
$servers = array();

for($i = 0; $i < count($oids); $i++)
  $servers[] = $cceClient->get($oids[$i]);

$page = $factory->getPage();
$block = $factory->getPagedBlock("softwareInstallSettings", array("basic", "advanced"));
$block->processErrors($serverScriptHelper->getErrors());

$scheduleMap = array("Never" => "never", "Daily" => "daily", "Weekly" => "weekly", "Monthly" => "monthly");
$block->addFormField(
  $factory->getMultiChoice("scheduleField", array("daily", "weekly", "monthly", "never"), array($scheduleMap[$swUpdate["updateInterval"]])),
  $factory->getLabel("scheduleField"),
  "basic"
);

$notifyMap = array("AllNew" => "all", "UpdatesOnly" => "updates");
$block->addFormField(
  $factory->getMultiChoice("notificationLightField", array("all", "updates"), array($notifyMap[$servers[0]["notificationMode"]])),
  $factory->getLabel("notificationLightField"),
  "basic"
);

// Use ActiveMonitor's email contact list if possible
$am_obj = $cceClient->getObject('ActiveMonitor', array(), '');
if( ! $am_obj["alertEmailList"] ) 
{
  $email = $factory->getEmailAddressList("emailField", $swUpdate["updateEmailNotification"]);
  $email->setOptional(true);
  $block->addFormField(
    $email,
    $factory->getLabel("emailField"),
    "basic"
  );
}

$locations = array();
for($i = 0; $i < count($servers); $i++)
  $locations[] = $servers[$i]["location"];
$updateServer = $factory->getUrlList("serverField", arrayToString($locations));
$block->addFormField(
  $updateServer,
  $factory->getLabel("serverField"),
  "advanced"
);

$httpProxy = $factory->getUrl("httpProxyField", $swUpdate["httpProxy"]);
$httpProxy->setOptional(true);
$block->addFormField(
  $httpProxy,
  $factory->getLabel("httpProxyField"),
  "advanced"
);

$ftpProxy = $factory->getUrl("ftpProxyField", $swUpdate["ftpProxy"]);
$ftpProxy->setOptional(true);
$block->addFormField(
  $ftpProxy,
  $factory->getLabel("ftpProxyField"),
  "advanced"
);

/*
$typeMap = array("All" => "all", "Updates" => "updates");
$block->addFormField(
  $factory->getMultiChoice("checkSetField", array("all", "updates"), array($typeMap[$swUpdate["updateType"]])),
  $factory->getLabel("checkSetField")
);
*/

/*
$block->addFormField(
  $factory->getBoolean("autoField", $servers[0]["autoUpdate"]),
  $factory->getLabel("autoField"),
  "advanced"
);
*/

$block->addFormField(
  $factory->getBoolean("requireSignatureField", $swUpdate["requireSignature"]),
  $factory->getLabel("requireSignatureField"),
  "advanced"
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($block->toHtml()); ?>

<?php print($page->toFooterHtml());
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
