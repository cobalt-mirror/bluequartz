<?php
// Copyright Sun Microsystems, Inc. 2001
// $Id: services.php,v 1.3 2001/04/03 23:35:48 will Exp $
// services.php
// The services enable/disable page with links to edit settings for various
// services.
//

include_once("ServerScriptHelper.php");

$helper = new ServerScriptHelper( $sessionId );
$factory = $helper->getHtmlComponentFactory("base-services", "/base/services/servicesHandler.php");
$cce = $helper->getCceClient();

$page = $factory->getPage();
$i18n = $helper->getI18n();

$settings = $factory->getScrollList(
	$i18n->get("serviceSettings"),
	array("enable", "service", "manage"),
	array(1) 
	);
$settings->setAlignments(array("center", "left", "center"));
$settings->setColumnWidths(array("1%", "", "1%"));


$services = array(
		array(0, "webServer", "/base/apache/apache.php"),
		array(1, "aspAdmin", "/base/casp/casp.php"),
		);
		// array(1, "dnsServer", "/base/dns/dns.php",
		//	"System", "DNS", "enabled")
		//array(1, "telnetServer", "",
		//	"System", "Telnet", "telnetaccess"),
		//array(1, "ftpServer", "",
		//	"System", "Ftp", "enabled"),
		//array(1, "snmpServer", "/base/snmp/snmp.php",
		//	"System", "Snmp", "enabled"),
		// array(1, "emailServer", "/base/email/email.php",
		//	"System", "Email", "enableSMTP"),

foreach ($services as $service)
{
	// Check if service is enabled...
	$enabled = 0;
	if($service[3]) 
	{
		$cce_service = $cce->getObject($service[3], array(), $service[4]);
		$enabled = $cce_service[$service[5]];

		// Special case telnet 
		if($enabled == 'none')
		{
			$enabled = 0;
		}
	}


	if ($service[0]) 
	{
		$checkbox = $factory->getBoolean($service, $enabled);
	}
	else
	{
		$checkbox = $factory->getTextField("", " ", "r");
	}

	if ($service[2]) 
	{
		$button = $factory->getModifyButton($service[2]);
	}
	else
	{
		$button = $factory->getTextField("", " ", "r");
	}

	$service_name = $factory->getTextField("", $i18n->get($service[1]), "r");

	$settings->addEntry(array($checkbox, $service_name, $button));
}


print $page->toHeaderHtml();
print $settings->toHtml();

$save = $factory->getSaveButton($factory->getSaveButton($page->getSubmitAction()));
print "<P>";
print $save->toHtml();

print $page->toFooterHtml();

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