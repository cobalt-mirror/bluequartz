<?php
/*
 * Copyright 2000-2002 Sun Microsystems, Inc.  All rights reserved.
 * $Id: apacheHandler.php 1538 2010-10-13 09:46:37Z oride $
 */

include_once("ArrayPacker.php");
include_once("ServerScriptHelper.php");
include_once("Product.php");

$serverScriptHelper = new ServerScriptHelper();

// Only serverHttpd should be here
if (!$serverScriptHelper->getAllowed('serverHttpd')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$product = new Product($cceClient);

$oids = $cceClient->find("System");

if(!$product->isRaq())
{
	// set System.Frontpage settings
	$frontpage = $cceClient->get($oids[0], "Frontpage");

	$attributes = array("enabled" => $frontpageField);
	// password needed only to enable frontpage
	if(!$frontpage["enabled"] && $frontpageField)
		$attributes["passwordWebmaster"] = $passwordWebmasterField;

	$cceClient->set($oids[0], "Frontpage", $attributes);
	$errors = $cceClient->errors();

	// set System.Web settings
	$cgiAccessMap = array("cgiAll" => "all", "cgiNone" => "subset", "cgiSubset" => "subset");
	$cgiUsers = ($cgiAccessField == "cgiNone") ? "" : $cgiUsersField;
	$cceClient->setObject("System", array("cgiAccess" => $cgiAccessMap[$cgiAccessField], "cgiUsers" => $cgiUsers), "Web");
	$errors = array_merge($errors, $cceClient->errors());
} else {
	// Apache server parameters only

	// min spares needs to be less than or equal to max spares
	if ($minSpareField > $maxSpareField) {
		$errors = array(new Error('[[base-apache.MinMaxError]]'));
	} else {
		$apache_config = array(
			"minSpare" => $minSpareField, 
			"maxSpare" => $maxSpareField, 
			"maxClients" => $maxClientsField, 
			"hostnameLookups" => $hostnameLookupsField);

		$ok = $cceClient->set($oids[0], "Web", $apache_config);
		$errors = array_merge((array)$errors, $cceClient->errors());
		if ($ok && ($maxClientsField < $maxSpareField)) {
			array_push($errors,
				   new Error('[[base-apache.ClientMaxError]]'));
		}
	
		$controlpanel_config = array(
			"urlAdminAccess" => $urlAdminAccess, 
			"urlSiteadminAccess" => $urlSiteadminAccess, 
			"urlPersonalAccess" => $urlPersonalAccess);
		$ok = $cceClient->set($oids[0], "ControlPanel", $controlpanel_config);
	}
}
print($serverScriptHelper->toHandlerHtml("/base/apache/apache.php", $errors));

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
