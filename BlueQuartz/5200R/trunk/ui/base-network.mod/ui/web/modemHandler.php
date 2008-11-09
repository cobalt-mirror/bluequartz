<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: modemHandler.php 1003 2007-06-25 15:19:33Z shibuya $

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

$oids = $cceClient->find("System");

$ip = ($modemIpField == "") ? "0.0.0.0" : $modemIpField;

// create array of values to pass to set
// need to do this so password field can be optional
// "authMode" => $modemAuthModeField,
$settings = array(
	"connMode" => $modemConnModeField,
	"serverName" => $modemAuthHostField,
	"phone" => $modemPhoneField,
	"userName" => $modemUserNameField,
	"initStr" => $modemInitStrField,
	"localIp" => $ip,
	"speed" => $modemSpeedField,
	"pulse" => $modemPulseField,
	"dialhours" => $dialoutWindowing
);

// $memberSelector = $factory->getSetSelector("dialoutWindowing", $dialtimesString,
//   $nodialtimesString, "dialTimes", "noDialTimes");

error_log("phone $modemPhoneField");

if ($modemPasswordField)
	$settings["password"] = $modemPasswordField;

$cceClient->set($oids[0], "Modem", $settings);
$errors = $cceClient->errors();

// get rid of default gateway because, if the modem is our internet connection
// it needs to setup a default gateway through the ppp interface to get packets out
$cceClient->set($oids[0], "", array( "gateway" => "" ));
$errors = array_merge($errors, $cceClient->errors());

// IP forwarding and NAT
$cceClient->set($oids[0], "Network", array("internetMode" => "narrowband", "ipForwarding" => "true", "nat" => $natField));
$errors = array_merge($errors, $cceClient->errors());

// turn off DHCP
$cceClient->setObject("Network", array("bootproto" => "none"), "", array("device" => "eth1"));
$errors = array_merge($errors, $cceClient->errors());

// turn off PPPoE
$cceClient->set($oids[0], "Pppoe", array("connMode" => "off"));
$errors = array_merge($errors, $cceClient->errors());

if ((count($errors) == 0) && $test)
	print($serverScriptHelper->toHandlerHtml("/base/modem/modemTest.php"));
else
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
