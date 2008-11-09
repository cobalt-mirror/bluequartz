<?php
// Author: Jesse Throwe
// Copyright 2001 Sun Microsystems, Inc. All rights reserved.
// $Id: scandetectionBlockClear.php,v 1.1.1.1.2.1 2002/02/20 01:30:36 pbaltz Exp $

// -------------------------------------
// Includes

include("ServerScriptHelper.php");

// -------------------------------------
// Variables

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

// -------------------------------------
// Find our network oids, and reload the firewall on each of them

$networks = $cceClient->find("Network");
foreach ($networks as $network_oid) {
	$thisnetwork = $cceClient->get($network_oid);
	$device = $thisnetwork['device'];
	$serverScriptHelper->shell("/usr/sbin/ldfirewall -r $device", $output, 'root');
	$serverScriptHelper->shell("/usr/sbin/ldfirewall $device /etc/scandetection/scandetection.fwall", $output, 'root');
}

// Go back to the original site, returning any errors on the way
print($serverScriptHelper->toHandlerHtml("/base/scandetection/scandetection.php", $errors, "base-scandetection"));

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
