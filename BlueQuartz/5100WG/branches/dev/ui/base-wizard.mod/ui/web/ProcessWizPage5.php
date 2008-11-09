<?php
// process the data on wizard page 5

include_once("ServerScriptHelper.php");
include_once("base/wizard/WizardSupport.php");

global $WizError;

global $WizConnectionTypeField;
global $WizHostNameField;
global $WizDomainNameField;
global $WizDnsAddressesField;
global $WizNextPageOverride;

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$i18n = $serverScriptHelper->getI18n("base-wizard");

error_log("hostname => $WizHostNameField\n", 3, "/tmp/wiz.log" );
error_log("domainname => $WizDomainNameField\n", 3, "/tmp/wiz.log" );
error_log("dns => $WizDnsAddressesField\n", 3, "/tmp/wiz.log" );
error_log("connection type => $WizConnectionTypeField\n", 3, "/tmp/wiz.log" );

if ($WizHostNameField == "")
{
	$WizError = "You must enter a value for Host Name.";
}
if ($WizDomainNameField == "")
{
	$WizError .= "<BR>You must enter a value for Domain Name.";
}
if ($WizDnsAddressesField == "")
{
	$WizError .= "<BR>You must enter a DNS address.";
}

if ($WizError == "")
{
	switch ($WizConnectionTypeField)
	{
	case "broadband":
		$WizNextPageOverride = "5a";
		break;

	case "lan":
		$WizNextPageOverride = "5b";
		break;

	case "narrowband":
		$WizNextPageOverride = "5c";
		break;

	case "none":
	default:
		$WizNextPageOverride = "";
		break;
	}

	$oid = $cceClient->find("System");

	$cceClient->set($oid, "",
									array("hostname" => strtolower($WizHostNameField),
												"domainname" => strtolower($WizDomainNameField),
												"dns" => "&".preg_replace("/(?:%0D%0A)+|(?:\r\n)+/","&",$WizDnsAddressesField)."&")
									);
	$errors = $cceClient->errors();

	// explicitly turn off everything to be consistent with the admin
	// site, just in case someone sets up their internet connection and
	// changes their mind
	if ($WizConnectionTypeField == "none")
	{
		$cceClient->set($oid, "Modem", array("connMode" => "off"));
		$cceClient->set($oid, "Pppoe", array("connMode" => "off"));
		$cceClient->set($oid, "", array("gateway" => ""));
		$cceClient->setObject("Network", array("enabled" => 0), "",
																	array("device" => "eth1"));
		$errors = array_merge($errors, $cceClient->errors());
	}

	$cceClient->set($oid, "Network",
									array(
										"internetMode" => $WizConnectionTypeField,
										# hack NAT here:
										"nat" => "1",
										"ipForwarding" => "1",)
									);
	$errors = array_merge($errors, $cceClient->errors());

	$WizError = WizDecodeErrors($errors);
}

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

