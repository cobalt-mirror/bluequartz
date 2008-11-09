<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: broadbandHandler.php 3 2003-07-17 15:19:15Z will $

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

$bootProtoMap = array("dhcp" => "dhcp", "pppoe" => "none", "static" => "none");

$eth1 = $cceClient->find("Network", array("device" => "eth1"));

// need to setup dhcp first because dhclient may rely on these settings
if ($networkField == "dhcp") {
	$cceClient->set(
			$eth1[0],
			"Dhclient", 
			array(
				"hostName" => $dhcpHostNameField, 
				"identifier" => $dhcpClientIdField
			)
		);
}
$errors = $cceClient->errors();

// setup static stuff before enabling the interface if in static mode
// probably not necessary, but just in case
if ($networkField == "static") {
	$cceClient->set(
		$eth1[0],
		"", 
		array(
			"ipaddr" => $ipAddressField,
			"netmask" => $netMaskField, 
			"bootproto" => $bootProtoMap[$networkField],
			"enabled" => 1
		)
	);
	$errors = array_merge($errors, $cceClient->errors());
	$cceClient->setObject("System", 
			      array("gateway" => $gatewayField));

} else if (count($errors) == 0) {
	// enable and set bootproto, need to do this before starting 
	// pppoe because it relies on the interface being up already
	// only blow away the gateway if the bootproto is changing, otherwise
	// saving as dhcp twice will leave the system with no gateway
	if ($networkField != $oldBootproto) $cceClient->setObject("System", array("gateway" => ""));
	$cceClient->set(
		$eth1[0],
		"",
		array(
			"bootproto" => $bootProtoMap[$networkField],
			"enabled" => 1
		)
	);
}
$errors = array_merge($errors, $cceClient->errors());

// setup pppoe last because it relies on the interface being enabled
if((count($errors) == 0) && ($networkField == "pppoe")) {
	$connModeMap = array(
				"dhcp" => "off", 
				"pppoe" => "on", 
				"static" => "off"
			);

	$cceClient->setObject(
			"System",
			array(
				"connMode" => $connModeMap[$networkField],
				"ethNumber" => 1, 
				"userName" => $pppUserNameField,
				"password" => $pppPasswordField
			), 
			"Pppoe"
		);
	$errors = array_merge($errors, $cceClient->errors());
}

print($serverScriptHelper->toHandlerHtml("", $errors));

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

