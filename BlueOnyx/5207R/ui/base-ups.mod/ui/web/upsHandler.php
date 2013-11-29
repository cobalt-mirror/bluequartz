<?php
// Author: Joshua Uziel
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: upsHandler.php,v 1.3 2001/09/24 04:18:05 uzi Exp $

include("ServerScriptHelper.php");

$helper = new ServerScriptHelper($sessionId);
$cce = $helper->getCceClient();

// Null state if disabled... I think it makes things clear.
if ($stateField == "disabled")
	$mystate = "";
else
	$mystate = $stateField;

list($manufacturer, $model) = split(": ", $selectModel);

list($driverOid) = $cce->findx("upsDrivers", array( "manufacturer" => $manufacturer, "modelName" => $model), array(), "", "");

// Update even if info won't be active... "customer is always right"
$cce->setObject("System",
		array("state" => $mystate,
		      "macs" => $macsField,
		      "wakeslaves" => $wakeSlavesField,
		      "wakedelay" => $wakeDelayField,
		      "masterip" => $masteripField,
		      "driver" => $driverOid,
		      "device" => $selectDevice),
		"UPS");

$errors = $cce->errors();

print $helper->toHandlerHtml("/base/ups/ups.php", $errors);

$helper->destructor();
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
