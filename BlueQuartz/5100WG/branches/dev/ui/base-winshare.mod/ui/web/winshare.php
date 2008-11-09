<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: winshare.php 201 2003-07-18 19:11:07Z will $

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-winshare", "/base/winshare/winshareHandler.php");

// get object
$oids = $cceClient->find("System");
$winShare = $cceClient->get($oids[0], "WinShare");
$winNetwork = $cceClient->get($oids[0], "WinNetwork");

$page = $factory->getPage();

$block = $factory->getPagedBlock("winshareSettings", array("basic", "advanced"));

$block->addFormField(
  $factory->getBoolean("enableServerField", $winShare["enabled"]),
  $factory->getLabel("enableServerField"),
  "basic"
);

$int=$factory->getInteger("maxUserField", $winShare["maxConnections"], 1, 1024);
$int->showBounds(1);

$block->addFormField(
  $int,
  $factory->getLabel("maxUserField"),
  "basic"
);

$block->addFormField(
  $factory->getTextField("workgroupField", $winNetwork["workgroup"]),
  $factory->getLabel("workgroupField"),
  "basic"
);

$block->addFormField(
  $factory->getBoolean("networkLogonField", $winNetwork["domainLogon"]),
  $factory->getLabel("networkLogonField"),
  "advanced"
);

// make the others option with an IP address
$others = $factory->getOption("winsServerOthers");
$others->addFormField(
  $factory->getIpAddress("winsServerIpAddressField", $winNetwork["winsIpAddress"]),
  $factory->getLabel("winsServerIpAddressField")
);

// make WINS server field
$winsServer = $factory->getMultiChoice("winsServerField", array("winsServerNone", "winsServerSelf"));
$winsServer->addOption($others);

// set selected
$settingToIndex = array("none" => 0, "self" => 1, "others" => 2);
$winsServer->setSelected($settingToIndex[$winNetwork["winsSetting"]], true);

$block->addFormField(
  $winsServer,
  $factory->getLabel("winsServerField"),
  "advanced"
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php if ($winNetwork["domainLogon"]) {
$windowsButton = $factory->getButton("machineList.php", "windowsMachineButton");
print $windowsButton->toHtml() . "<br>";
}?>

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

