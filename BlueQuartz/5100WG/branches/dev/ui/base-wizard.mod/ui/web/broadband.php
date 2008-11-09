<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: broadband.php 201 2003-07-18 19:11:07Z will $

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-wizard", "/base/wizard/broadbandHandler.php");
$cceClient = $serverScriptHelper->getCceClient();
$i18n = $serverScriptHelper->getI18n("base-wizard");

// get settings
$oids = $cceClient->find("System");
$system = $cceClient->get($oids[0]);
$dhclient = $cceClient->getObject("Network", array("device" => "eth1"), "Dhclient");
$pppoe = $cceClient->get($oids[0], "Pppoe");
$eth1 = $cceClient->getObject("Network", array("device" => "eth1"));

$page = $factory->getPage();

$block = $factory->getPagedBlock("broadbandSettings");

$dhcp = $factory->getOption("dhcp");
$hostName = $factory->getTextField("dhcpHostNameField", $dhclient["hostName"]);
$hostName->setOptional(true);
$dhcp->addFormField(
  $hostName,
  $factory->getLabel("dhcpHostNameField")
);
$clientId = $factory->getTextField("dhcpClientIdField", $dhclient["identifier"]);
$clientId->setOptional(true);
$dhcp->addFormField(
  $clientId,
  $factory->getLabel("dhcpClientIdField")
);

$ppp = $factory->getOption("pppoe");
$ppp->addFormField(
  $factory->getTextField("pppUserNameField", $pppoe["userName"]),
  $factory->getLabel("pppUserNameField")
);
$ppp->addFormField(
  $factory->getPassword("pppPasswordField"),
  $factory->getLabel("pppPasswordField")
);

$static = $factory->getOption("static");
$static->addFormField(
  $factory->getIpAddress("ipAddressField", ($eth1["enabled"] ? $eth1["ipaddr"] : "")),
  $factory->getLabel("ipAddressField")
);
$static->addFormField(
  $factory->getIpAddress("netMaskField", ($eth1["enabled"] ? $eth1["netmask"] : "")),
  $factory->getLabel("netMaskField")
);
$static->addFormField(
  $factory->getIpAddress("gatewayField", $system["gateway"]),
  $factory->getLabel("gatewayField")
);

$network = $factory->getMultiChoice("networkField");
$network->addOption($dhcp);
$network->addOption($ppp);
$network->addOption($static);
if($eth1["bootproto"] == "dhcp")
  $network->setSelected(0, true);
else if($pppoe["connMode"] != "off")
  $network->setSelected(1, true);
else
  $network->setSelected(2, true);

$block->addFormField(
  $network,
  $factory->getLabel("networkField")
);

// save the state, so the gateway doesn't get blown away when eth1 is dhcped and
// the user accidently saves twice
$oldbootproto = $factory->getTextField("oldBootproto", $eth1["bootproto"], "");
$block->addFormField(
	$oldbootproto
);
 
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($i18n->getHtml("broadbandMessage")); ?>
<BR><BR>

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

