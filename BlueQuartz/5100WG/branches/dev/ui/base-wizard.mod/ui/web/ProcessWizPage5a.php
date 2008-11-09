<?php
// process the data on wizard page 1

include_once("ServerScriptHelper.php");
include_once("base/wizard/WizardSupport.php");

global $WizError;
global $WizNetworkField;
global $WizDhcp;
global $WizDhcpClientIdField;
global $WizDhcpHostNameField;
global $WizIpAddressField;
global $WizNetMaskField;
global $WizGatewayField;
global $WizPppPasswordField;
global $WizPppUserNameField;
global $WizOldBootProto;

WizDebug("hostname => $WizHostNameField\n");
WizDebug("networkField => $WizNetworkField\n");
WizDebug("dhcp => $WizDhcp\n");
WizDebug("dhcpClientIdField => $WizDhcpClientIdField\n");
WizDebug("dhcpHostNameField => $WizDhcpHostNameField\n");
WizDebug("ipAddressField => $WizIpAddressField\n");
WizDebug("netMaskField => $WizNetMaskField\n");
WizDebug("gatewayField => $WizGatewayField\n");
WizDebug("pppUserNameField => $WizPppUserNameField\n");
WizDebug("pppPasswordField => $WizPppPasswordField\n");
WizDebug("oldBootProto => $WizOldBootProto\n");


$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$i18n = $serverScriptHelper->getI18n("base-wizard");

$bootProtoMap = array("WizDhcp" => "dhcp", "WizPppoe" => "none", "WizStatic" => "none");

$eth1 = $cceClient->find("Network", array("device" => "eth1"));

if ($WizNetworkField == "WizPppoe" && strlen($WizPppUserNameField) == 0)
{
	$WizError .= "You must provide a user name to use PPPoE.<BR>"; // $i18n->getHtml("passwordField_empty");
}
if ($WizNetworkField == "WizPppoe" && strlen($WizPppPasswordField) == 0)
{
	$WizError .= "You must provide a password to use PPPoE.<BR>"; // $i18n->getHtml("passwordField_empty");
}
if ($WizNetworkField == "WizStatic" && strlen($WizIpAddressField) == 0)
{
	$WizError .= "You must provide an IP address.<BR>"; // $i18n->getHtml("passwordField_empty");
}
if ($WizNetworkField == "WizStatic" && strlen($WizNetMaskField) == 0)
{
	$WizError .= "You must provide a subnet mask<BR>"; // $i18n->getHtml("passwordField_empty");
}
if ($WizNetworkField == "WizStatic" && strlen($WizGatewayField) == 0)
{
	$WizError .= "You must provide a gateway address.<BR>"; // $i18n->getHtml("passwordField_empty");
}

// need to setup DHCP first because dhclient may rely on these settings
if ($WizNetworkField == "WizDhcp")
{
	$cceClient->set(
			$eth1[0],
			"Dhclient", 
			array(
				"hostName" => $WizDhcpHostNameField, 
				"identifier" => $WizDhcpClientIdField
			)
		);

	$errors = $cceClient->errors();
}

// setup static stuff before enabling the interface if in static mode
// probably not necessary, but just in case
if ($WizNetworkField == "WizStatic")
{
	$cceClient->set(
		$eth1[0],
		"", 
		array(
			"ipaddr" => $WizIpAddressField,
			"netmask" => $WizNetMaskField, 
			"bootproto" => $bootProtoMap[$WizNetworkField],
			"enabled" => 1
		)
	);
	$errors = array_merge($errors, $cceClient->errors());
	$cceClient->setObject("System", 
			      array("gateway" => $WizGatewayField));

}
else if (count($errors) == 0)
{
	// enable and set bootproto, need to do this before starting 
	// pppoe because it relies on the interface being up already
	// only blow away the gateway if the bootproto is changing, otherwise
	// saving as DHCP twice will leave the system with no gateway
	if ($WizNetworkField != $WizOldBootProto)
	{
		$cceClient->setObject("System", array("gateway" => ""));
	}

	$cceClient->set(
		$eth1[0],
		"",
		array(
			"bootproto" => $bootProtoMap[$WizNetworkField],
			"enabled" => 1
		)
	);
}
$errors = array_merge($errors, $cceClient->errors());

// setup pppoe last because it relies on the interface being enabled
if((count($errors) == 0) && ($WizNetworkField == "WizPppoe"))
{
	$connModeMap = array(
				"WizDhcp" => "off", 
				"WizPppoe" => "on", 
				"WizStatic" => "off"
			);

	$cceClient->setObject(
			"System",
			array(
				"connMode" => $connModeMap[$WizNetworkField],
				"ethNumber" => 1, 
				"userName" => $WizPppUserNameField,
				"password" => $WizPppPasswordField
			), 
			"Pppoe"
		);
	$errors = array_merge($errors, $cceClient->errors());
}

$WizError = WizDecodeErrors($errors);

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

