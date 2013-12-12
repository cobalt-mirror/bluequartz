<?php
/*
 * $Id: apacheHandler.php
 */

include_once("ArrayPacker.php");
include_once("ServerScriptHelper.php");
include_once("Product.php");

$serverScriptHelper = new ServerScriptHelper();

// Only 'serverHttpd' should be here
if (!$serverScriptHelper->getAllowed('serverHttpd')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$product = new Product($cceClient);

$oids = $cceClient->find("System");

// get web
$web = $cceClient->getObject("System", array(), "Web");

$errors = array();

if(!$product->isRaq()) {
	// set System.Frontpage settings
	$frontpage = $cceClient->get($oids[0], "Frontpage");

	$attributes = array("enabled" => $frontpageField);
	// password needed only to enable frontpage
	if(!$frontpage["enabled"] && $frontpageField)
		$attributes["passwordWebmaster"] = $passwordWebmasterField;

	$cceClient->set($oids[0], "Frontpage", $attributes);
	$errors[] = $cceClient->errors();

	// set System.Web settings
	$cgiAccessMap = array("cgiAll" => "all", "cgiNone" => "subset", "cgiSubset" => "subset");
	$cgiUsers = ($cgiAccessField == "cgiNone") ? "" : $cgiUsersField;
	$cceClient->setObject("System", array("cgiAccess" => $cgiAccessMap[$cgiAccessField], "cgiUsers" => $cgiUsers), "Web");
	$errors[] = array_merge($errors, $cceClient->errors());
} 
else {
	// Apache server parameters only

	// min spares needs to be less than or equal to max spares
	if ($minSpareField > $maxSpareField) {
		array_push($errors, new Error('[[base-apache.MinMaxError]]'));
	} 
	else {
		$apache_config = array(
			"httpPort" => $httpPortField,
			"sslPort" => $sslPortField,

			"minSpare" => $minSpareField, 
			"maxSpare" => $maxSpareField, 
			"maxClients" => $maxClientsField, 
			"hostnameLookups" => $hostnameLookupsField,
			
			"Options_All" => $Options_All,
			"Options_FollowSymLinks" => $Options_FollowSymLinks,			
			"Options_Includes" => $Options_Includes,
			"Options_Indexes" => $Options_Indexes,			
			"Options_MultiViews" => $Options_MultiViews,
			"Options_SymLinksIfOwnerMatch" => $Options_SymLinksIfOwnerMatch,			

			"AllowOverride_All" => $AllowOverride_All,
			"AllowOverride_AuthConfig" => $AllowOverride_AuthConfig,			
			"AllowOverride_FileInfo" => $AllowOverride_FileInfo,
			"AllowOverride_Indexes" => $AllowOverride_Indexes,			
			"AllowOverride_Limit" => $AllowOverride_Limit,
			"AllowOverride_Options" => $AllowOverride_Options,			

			"Writeback_BlueOnyx_Conf" => time()

			);

		// Check if the HTTP/SSL ports are in use:
		$HTTPportInUse = `/bin/netstat -tupan|/bin/grep LISTEN|/bin/grep :$httpPortField|/bin/grep -v httpd|/usr/bin/wc -l`;
		$SSLportInUse = `/bin/netstat -tupan|/bin/grep LISTEN|/bin/grep :$sslPortField|/bin/grep -v httpd|/usr/bin/wc -l`;

		if (($HTTPportInUse != "0\n") && ($web['httpPort'] != $httpPortField)) {
			array_push($errors, new Error('[[base-apache.httpPortInUse]]'));
		}
		elseif (($SSLportInUse != "0\n") && ($web['sslPort'] != $sslPortField)) {
			array_push($errors, new Error('[[base-apache.SSLportInUse]]'));
		}
		elseif ($maxClientsField < $maxSpareField) {
		    array_push($errors, new Error('[[base-apache.ClientMaxError]]'));
		}
		else {
		    $ok = $cceClient->set($oids[0], "Web", $apache_config);
		    array_push($errors, $cceClient->errors());

		    // In case the HTTP-port or SSL-port are changed, we also need to update all 
		    // VHost containers with the new port information. Which is a bit messy. But
		    // We can simply do so by updating all 'VirtualHost.ipaddr' and let our
		    // existing handler base/apache/virtual_host.pl take care of it:
		    $VirtualHosts = $cceClient->find("VirtualHost");
		    foreach ($VirtualHosts as $VH) {
		    	$VHsettings = $cceClient->get($VH);
			$ok = $cceClient->set($VH, "", array('ipaddr' => $VHsettings['ipaddr']));
		    }
		}
	}

}
print($serverScriptHelper->toHandlerHtml("/base/apache/apache.php", $errors));

$serverScriptHelper->destructor();

/*
Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
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
