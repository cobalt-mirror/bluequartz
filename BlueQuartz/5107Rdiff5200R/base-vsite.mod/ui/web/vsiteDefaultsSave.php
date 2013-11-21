<?php
// $Id: vsiteDefaultsSave.php,v 1.9 2001/12/07 01:48:44 pbaltz Exp $
// Copyright Sun Microsystems, Inc. 2001
// vsiteDefaultsSave.php
// saves information entered on vsiteDefaults.php page into cce

include_once("ServerScriptHelper.php");
include_once("AutoFeatures.php");

$helper = new ServerScriptHelper($sessionId);

// Only adminUser should be here
if (!$helper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$cce = $helper->getCceClient();

$cce->setObject("System",
		array(
			"ipaddr" => $ipAddr,
			"domain" => $domain,
			"quota" => $quota,
			"maxusers" => $maxusers,
			"emailDisabled" => $emailDisabled,
                        "mailCatchAll" => $mailCatchAll,
			"dns_auto" => $dns_auto,
			"webAliasRedirects" => $webAliasRedirects,
			"site_preview" => $site_preview
		),
		"VsiteDefaults"
	);

$errors = $cce->errors();

// handle automatically detected features
$autoFeatures = new AutoFeatures($helper);
$cce_info = array();
list($cce_info["CCE_SERVICES_OID"]) = $cce->find("VsiteServices");
$af_errors = $autoFeatures->handle("defaults.Vsite", $cce_info);

$errors = array_merge($errors, $af_errors);

if (count($errors) != 0)
	print $helper->toHandlerHtml("/base/vsite/vsiteDefaults.php", $errors);
else
	print $helper->toHandlerHtml("/base/vsite/vsiteList.php");

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
