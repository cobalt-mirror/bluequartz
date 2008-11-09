<?php
// Author: Patrick Bose
// Copyright 2001, Cobalt Networks.  All rights reserved.
// $Id: onepageHandler.php 201 2003-07-18 19:11:07Z will $
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

$ok = $cceClient->set($soid, "", array("hostname" => $hostNameField, "domainname" => $domainNameField, "dns" => $dnsAddressesField));
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
                array('enabled' => 1, 'access' => 'reg'));
$errors = array_merge($errors, $cceClient->errors());

//////////// handle admin settings

$ok = $cceClient->set($admin_oid, '', array("password" => $passwordField));
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

// check for previous vsite, in case they are going through the 
// setup wizard a second time
$prev_vsite = array();
$prev_vsite = $cceClient->find('Vsite', 
                array('fqdn' => ($hostNameField . '.' . $domainNameField)));

if ($product->isRaq() && (count($prev_vsite) == 0))
{
    // create the basic Vsite here if on a raq
    $eth0 = $cceClient->getObject('Network', array('device'  => 'eth0'));
    $vsiteDefaults = $cceClient->get($soid, 'VsiteDefaults');

    if ($eth0 && $vsiteDefaults)
    {
        $vsite_oid = $cceClient->create('Vsite',
            array(
                'hostname' => $hostNameField,
                'domain' => $domainNameField,
                'fqdn' => ($hostNameField . '.' . $domainNameField),
                'ipaddr' => $eth0['ipaddr'],
                'maxusers' => $vsiteDefaults['maxusers']
            )
        );
    }

    $errors = array_merge($errors, $cceClient->errors());

    // set disk quota
    if ($eth0 && $vsiteDefaults)
    {
        $cceClient->set($vsite_oid, 'Disk', 
                array('quota' => $vsiteDefaults['quota']));
        // don't collect errors from here since this, would confuse
        // the user since they never see this.  I think perhaps more
        // steps need to be added to the setup wizard or something
    }
}

// need to not use data presevation here since we redirect weird
print($serverScriptHelper->toHandlerHtml("", $errors, false));
print $output;

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

