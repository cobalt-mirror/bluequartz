<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: broadbandHandler.php 1003 2007-06-25 15:19:33Z shibuya $

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

$oids = $cceClient->find("System");
$eth1 = $cceClient->find("Network", array("device" => "eth1"));
 
// eth1
$bootProtoMap = array("dhcp" => "dhcp", "pppoe" => "none", "static" => "none");

// turn off modem now, otherwise we lose control if pppoe is started and pppd is running
$cceClient->set($oids[0], "Modem", array("connMode" => "off"));
$errors = $cceClient->errors();

// set DHCP info, do this first in case needed by dhclient
$cceClient->set(
		$eth1[0], 
		"Dhclient", 
		array(
			"hostName" => $dhcpHostNameField, 
			"identifier" => $dhcpClientIdField
		)
	);
$errors = array_merge($errors, $cceClient->errors());

// set ip and netmask if in static mode
// we also set the gateway here as it needs the interface to be up to work.
if ($networkField == "static") {
	$cceClient->set(
			$eth1[0],
			"", 
			array(
				"ipaddr" => $ipAddressField, 
				"netmask" => $netMaskField,
	                        "enabled" => "1",
        	                "bootproto" => $bootProtoMap[$networkField]
			)
	);
	$errors = array_merge($errors, $cceClient->errors());
	$cceClient->set($oids[0], "", array("gateway" => $gatewayField));
} else if (count($errors) == 0) {
	// else just enable the interface
	if ($networkField != $oldBootproto) $cceClient->set($oids[0], "", array("gateway" => ""));
	$cceClient->set(
        	        $eth1[0],
			"",
                	array(
                        	"enabled" => "1",
                        	"bootproto" => $bootProtoMap[$networkField]
                	)
        	);
}
$errors = array_merge($errors, $cceClient->errors());


// IP forward and NAT
$cceClient->set($oids[0], "Network", array("internetMode" => "broadband", "ipForwarding" => "true", "nat" => $natField));
$errors = array_merge($errors, $cceClient->errors());

// set PPPoE info
$connModeMap = array("dhcp" => "off", "pppoe" => "on", "static" => "off");
$attributes = array("connMode" => $connModeMap[$networkField], "ethNumber" => 1, "userName" => $pppUserNameField);
// password can be blank for PPPoE
if($pppPasswordField != "")
  $attributes["password"] = $pppPasswordField;

$cceClient->set($oids[0], "Pppoe", $attributes);
$errors = array_merge($errors, $cceClient->errors());

print($serverScriptHelper->toHandlerHtml("/base/network/wan.php", $errors));

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
