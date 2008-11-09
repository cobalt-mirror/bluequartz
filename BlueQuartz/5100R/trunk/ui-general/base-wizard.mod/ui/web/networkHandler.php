<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: networkHandler.php 1050 2008-01-23 11:45:43Z mstauber $

include_once("ServerScriptHelper.php");
include_once("Product.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$product = new Product($cceClient);

if ($product->isRaq())
	$eth0 = $cceClient->getObject("Network", array("device" => "eth0"));

$oid = $cceClient->find("System");

$cceClient->set($oid, "", array("hostname" => $hostNameField, "domainname" => $domainNameField, "dns" => $dnsAddressesField));
$errors = $cceClient->errors();

if (!$product->isRaq())
{
	// explicitly turn off everything to be consistent with the admin
	// site, just in case someone sets up their internet connection and 
	// changes their mind
	if ($internetField == "none") {
		$cceClient->set($oid, "Modem", array("connMode" => "off"));
		$cceClient->set($oid, "Pppoe", array("connMode" => "off"));
		$cceClient->set($oid, "", array("gateway" => ""));
		$cceClient->setObject("Network", array("enabled" => 0), "", 
					array("device" => "eth1"));
	}
}
else
{
	// product is a raq create a vsite
	$cceClient->create("Vsite", 
			array(
				"hostname" => $hostNameField, 
				"domain" => $domainNameField, 
				"fqdn" => ("$hostNameField.$domainNameField"), 
				"ipaddr" => $eth0["ipaddr"], 
				"quota" => 1000, 
				"maxusers" => 25));

	$errors = array_merge($errors, $cceClient->errors());
}
	
$cceClient->set($oid, "Network",
	array(
	"internetMode" => ($product->isRaq() ? "lan" : $internetField),
	# hack NAT here:
	"nat" => "2",
	"ipForwarding" => "2",
));
$errors = array_merge($errors, $cceClient->errors());

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
