<?php
// Author: Patrick Bose
// Copyright 2001, Cobalt Networks.  All rights reserved.
// $Id: onepageHandler.php 1136 2008-06-05 01:48:04Z mstauber $
//
// the onepage handler... trying to maintain similarities to
// the individual handlers used on qube for ease of maintenance...

include_once("ServerScriptHelper.php");
include_once("Product.php");
include_once('Capabilities.php');

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

/////////// handle network settings

list($soid) = $cceClient->find("System");
list($admin_oid) = $cceClient->find('User', array('name' => 'admin'));

$adminIf = "eth1";

if (!file_exists("/proc/user_beancounters")) {
    // Regular Network Interfaces

    // handle all devices
    $devices = array('eth0', 'eth1');
    if (isset($deviceList))
	$devices = $cceClient->scalar_to_array($deviceList);

    // special array for admin if errors
    $admin_if_errors = array();
    for ($i = 0; $i < 1; $i ++) {
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
	if ($ip_field == '') {
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
}

// mstauber

if (!file_exists("/proc/user_beancounters")) {
    // Regular Network Interfaces
    $ok = $cceClient->set($soid, "", array("hostname" => $hostNameField, "domainname" => $domainNameField, "dns" => $dnsAddressesField, "gateway" => $gatewayField));
}
else {
    // OpenVZ Network Interfaces
    $ok = $cceClient->set($soid, "", array("hostname" => $hostNameField, "domainname" => $domainNameField, "dns" => $dnsAddressesField));
}

$errors = $cceClient->errors();

// Enable the DNS server if we self-reference by IP address
$enable_dns = 0;
$net_oids = $cceClient->find("Network");
foreach ($net_oids as $net)
{
	$network = $cceClient->get($net);
	if (ereg("&".$network["ipaddr"]."&", $dnsAddressesField))
		$enable_dns = 1;
}
if (ereg('&127.0.0.1&', $dnsAddressesField))
{
	$enable_dns = 1;
}
$cceClient->set($soid, "DNS", array("enabled" => $enable_dns));

$internetField = "lan";

$ok = $cceClient->set($soid, "Network",
    array(
    "internetMode" => $internetField,
    # hack NAT here:
    "nat" => "1",
    "ipForwarding" => "1",
));
$errors = array_merge($errors, $cceClient->errors());

// set system Telnet to a RaQ friendly state
$ok = $cceClient->set($soid, 'Telnet', 
                array('enabled' => 0, 'access' => 'reg'));
$errors = array_merge($errors, $cceClient->errors());

//////////// handle admin settings

// Open CrackLib Dictionary for usage:
$dictionary = crack_opendict('/usr/share/dict/pw_dict') or die('Unable to open CrackLib dictionary');

// Perform password check with cracklib:
$check = crack_check($dictionary, $passwordField);

// Retrieve messages from cracklib:
$diag = crack_getlastmessage();

// Check if user wants to keep default password (bad idea!)
if ($passwordField == "bluequartz") {
    $diag = "You must change the password! Do not keep the default 'bluequartz' password!";
}

if ($diag == 'strong password') {
    // Nothing to do. Cracklib thinks it's a good password, so set it to CCE:
    $ok = $cceClient->set($admin_oid, '', array("password" => $passwordField));
}
else {
    $attributes["password"] = "1";
    $errors[] = new Error("[[base-user.error-password-invalid]]" . $diag);
}

// Close cracklib dictionary:
crack_closedict($dictionary);

$errors = array_merge($errors, $cceClient->errors());

//////////// handle locale settings

$ok = $cceClient->set($soid, '', array('productLanguage' => $languageField));
$errors = array_merge($errors, $cceClient->errors());

///////////// handle time settings

if ($timeZoneField != $oldTimeZone) {
    $timeZone = $timeZoneField;
    putenv("TZ=$timeZone");
}

if (preg_match('/(\d+):(\d+):(\d+):(\d+):(\d+):(\d+)/', $dateField, $matches)) {
    $date = mktime($matches[4], $matches[5], $matches[6], $matches[2], 
               $matches[3], $matches[1]);
}
if ($date and ($date != $oldTime))
    $time = $date;

// $serverScriptHelper->shell("/usr/sausalito/sbin/setTime \"$time\" \"$timeZone\" \"$ntpAddress\" \"true\"", $output, "root");
$serverScriptHelper->shell("/usr/sausalito/sbin/setTime \"$time\" \"$timeZone\" \"$ntpAddress\" \"true\" \"defer\"", $output, "root");

$product = new Product($cceClient);


// need to not use data presevation here since we redirect weird
print($serverScriptHelper->toHandlerHtml("", $errors, false));
print $output;

///////////// handle default mailman settings

list($oid) = $cceClient->find('Mailman', array('name' => 'mailman'));
if (!$oid) {
    $ok = $cceClient->create('Mailman', array(
                                              'name' => 'mailman',
                                              'password' => $passwordField,
                                              'description' => 'default maillist',
                                              'site' => 'server',
                                              'fqdn' => "$hostNameField.$domainNameField",
                                              'owner' => "admin@$hostNameField.$domainNameField"
                                             ));
    $errors = array_merge($errors, $cceClient->errors());
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
