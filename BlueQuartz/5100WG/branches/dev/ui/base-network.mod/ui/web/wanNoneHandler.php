<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: wanNoneHandler.php 201 2003-07-18 19:11:07Z will $

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

// turn off IP forwarding
$cceClient->setObject("System", array("internetMode" => "none", "ipForwarding" => 0), "Network");
$errors = $cceClient->errors();

// should also blank out default gateway because otherwise what we are doing is slightly misleading
$cceClient->setObject("System", array("gateway" => ""));
$errors = array_merge($errors, $cceClient->errors());

// turn off DHCP and disable eth1 if we were dhcping, to avoid us using an ip that the dhcp server
// may give out to someone else after we don't renew our lease after timing out
//$eth1 = $cceClient->getObject("Network", array("device" => "eth1"));
//$pppoe = $cceClient->getObject("System", array(), "Pppoe");

//if ($eth1["bootproto"] == "dhcp" || $pppoe["connMode"] != "off") {
	$cceClient->setObject(
			"Network", 
			array(
				"enabled" => "0", 
				"bootproto" => "none"
			), 
			"", 
			array("device" => "eth1")
		);
	$errors = array_merge($errors, $cceClient->errors());
//}

// turn off PPPoE
$cceClient->setObject("System", array("connMode" => "off"), "Pppoe");
$errors = array_merge($errors, $cceClient->errors());

// turn off modem
$cceClient->set($oids[0], "Modem", array("connMode" => "off"));
$errors = array_merge($errors, $cceClient->errors());

print($serverScriptHelper->toHandlerHtml("/base/network/wan.php?isFixed=true", $errors));

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

