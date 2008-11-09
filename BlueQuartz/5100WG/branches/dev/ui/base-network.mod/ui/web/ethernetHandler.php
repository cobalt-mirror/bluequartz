<?php
// Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: ethernetHandler.php 201 2003-07-18 19:11:07Z will $

include_once('ServerScriptHelper.php');
include_once('Product.php');
include_once('base/network/network_common.php');

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$product = new Product( $cceClient );

$oids = $cceClient->find("System");

if ($product->isRaq()) {
	$cceClient->set($oids[0], "", array("hostname" => $hostNameField, "domainname" => $domainNameField, "dns" => $dnsAddressesField, "gateway" => $gatewayField));
} else {
	$cceClient->set($oids[0], "", array("hostname" => strtolower($hostNameField), "domainname" => strtolower($domainNameField), "dns" => $dnsAddressesField));
}

$errors = $cceClient->errors();

// figure out if this is the admin if changing
$orig_field_name = "ipAddressOrig$adminIf";
$new_field_name = "ipAddressField$adminIf";

if ($SERVER_ADDR == $$orig_field_name)
	$redirect = $$new_field_name;
else
	$redirect = $SERVER_ADDR;

$call_to_handler = true;
if($adminIf && ($$orig_field_name != $$new_field_name))
{
	//If IP is different and admin used this IP, redirect to new one
	$call_to_handler = false;
	
	$factory =& $serverScriptHelper->getHtmlComponentFactory('base-network');
	$i18n = $factory->getI18n();
	$page = $factory->getPage();

	$page->setOnLoad("top.location = 'http://$redirect/login/'");
	$reconnect =& $factory->getButton("javascript: top.location = 'http://$redirect/login/';", 'reconnect');
	$fallback =& $factory->getButton("javascript: top.location = 'http://$SERVER_ADDR/login/';", 'oldIPReconnect');
	
	print $page->toHeaderHtml();
	print $i18n->interpolateHtml('[[base-network.adminRedirect]]');
	print "<p></p>\n";
	print $reconnect->toHtml();
	print "<p></p>\n";
	print $fallback->toHtml();
	print $page->toFooterHtml();
	
	// make sure that part gets sent back before possible disconnect
	// due to network confusion
	// push ie to display junk
	for ($i = 0; $i < 600; $i++) print "<br>\n";
	flush();
	sleep(2);
}

// handle all devices
$devices = array('eth0', 'eth1');
if (isset($deviceList))
	$devices = $cceClient->scalar_to_array($deviceList);

// special array for admin if errors
$admin_if_errors = array();
for ($i = 0; $i < count($devices); $i ++)
{
	$var_name = "ipAddressField" . $devices[$i];
	$ip_field = $$var_name;
	$var_name = "ipAddressOrig" . $devices[$i];
	$ip_orig = $$var_name;
	$var_name = "netMaskField" . $devices[$i];
	$nm_field = $$var_name;
	$var_name = "netMaskOrig" . $devices[$i];
	$nm_orig = $$var_name;
	$var_name = "bootProtoField" . $devices[$i];
	$boot_field = $$var_name;
   
	// setup or set disabled
	if ($ip_field == '')
	{
		// first migrate any aliases to eth0 (possibly do this better)
		$aliases = $cceClient->findx('Network', array(), 
					array('device' => "^$devices[$i]:"));
		for ($k = 0; $k < count($aliases); $k++)
		{
			$new_device = find_free_device($cceClient, 'eth0');
			$ok = $cceClient->set($aliases[$k], '', 
					array('device' => $new_device));
			
			$errors = array_merge($errors, $cceClient->errors());
		}
		$cceClient->setObject(
			'Network', 
			array("enabled" => "0"), 
			"",
			array("device" => $devices[$i])
		);
		if ($devices[$i] == $adminIf)
			$admin_if_errors = $cceClient->errors();
		else
			$errors = array_merge($errors, $cceClient->errors());
	}
	else if ($ip_field && 
				(($ip_field != $ip_orig) || ($nm_field != $nm_orig)))
	{

		// since we only deal with real interfaces here, things are simpler
		// than they could be
		if (false && $ip_field != $ip_orig)
		{
			// check to see if there is an alias that is already using
			// the new ip address.  if there is, destroy the Network object
			// for this device, and assign the alias this device name.
			list($alias) = $cceClient->find('Network', 
								array(
									'real' => 0,
									'ipaddr' => $ip_field
									));
			if ($alias)
			{
				$ok = $cceClient->set($alias, '',
					array(
						'device' => $devices[$i],
						'real' => 1,
						'ipaddr' => $ip_field,
						'netmask' => $nm_field,
						'enabled' => 1,
						'bootproto' => 'none'
						));
				$errors = array_merge($errors, $cceClient->errors());
				if (!$ok)
					break;
				else
					continue;
			}
		}
		$cceClient->setObject('Network',
				array(
					'ipaddr' => $ip_field,
					'netmask' => $nm_field,
					'enabled' => 1,
					'bootproto' => 'none'
					),
			   '', array('device' => $devices[$i]));

		if ($devices[$i] == $adminIf)
			$admin_if_errors = $cceClient->errors();
		else
			$errors = array_merge($errors, $cceClient->errors());
	}
}

if ($call_to_handler)
{
	/*
	 * admin if errors get priority if any occurred and we
	 * get the chance to report them
	 */
	if (count($admin_if_errors) > 0) {
		$errors = array_merge($admin_if_errors, $errors);
	}

	// going to form, preserve data
	print($serverScriptHelper->toHandlerHtml("/base/network/ethernet.php", 
						$errors));
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

