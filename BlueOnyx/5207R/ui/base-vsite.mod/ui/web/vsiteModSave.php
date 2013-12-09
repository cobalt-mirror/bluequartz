<?php
/*
 * Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
 * $Id: vsiteModSave.php
 *
 * processes input from vsiteMod.php page
 */

include_once("ServerScriptHelper.php");
include_once("AutoFeatures.php");

$helper = new ServerScriptHelper($sessionId);

// Only 'manageSite' should be here
if (!$helper->getAllowed('manageSite')) {
  header("location: /error/forbidden.html");
  return;
}

$cce =& $helper->getCceClient();

$vsiteOID = $cce->find("Vsite", array("name" => $group));

// Suspended sites must NOT have site_preview enabled:
if ($suspend == "1") { 
    $site_preview = "0"; 
}

if ($prefix != "") {
	$userPrefixEnabled = "1";
}
else {
	$userPrefixEnabled = "0";
}

$cce->set($vsiteOID[0], "", 
	  array(
		"hostname" => $hostname,
		"domain" => $domain,
		"fqdn" => ($hostname . "." . $domain),
		"ipaddr" => $ipAddr,
		"maxusers" => $maxusers,
		"dns_auto" => $dns_auto,
		"site_preview" => $site_preview,
		"prefix" => $prefix,
        "userPrefixEnabled" => $userPrefixEnabled, 
        "userPrefixField" => $prefix, 		
		"suspend" => $suspend
	  )
	 );

$errors = $cce->errors();

$cce->set($vsiteOID[0], 'Disk', array('quota' => $quota));
$errors = array_merge($errors, $cce->errors());

// handle auto features here
$autoFeatures = new AutoFeatures($helper);
$cce_info = array("CCE_OID" => $vsiteOID[0]);
list($cce_info["CCE_SERVICES_OID"]) = $cce->find("VsiteServices");
$af_errors = $autoFeatures->handle("modify.Vsite", $cce_info);

$errors = array_merge($errors, $af_errors);

print $helper->toHandlerHtml("/base/vsite/vsiteMod.php?group=$group", $errors);

// nice people say aufwiedersehen
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
