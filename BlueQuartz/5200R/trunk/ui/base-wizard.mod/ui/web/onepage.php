<?php
// Author: Patrick Bose
// Copyright 2001, Cobalt Networks.  All rights reserved.
// $Id: onepage.php 1444 2010-03-23 15:45:11Z shibuya $
//
// This page duplicates some stuff from a qube setup, but
// the idea is that for raqs we want an express, one-page setup...
// or at least as close as we can get.

include_once("ServerScriptHelper.php");
include_once("ArrayPacker.php");

$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-wizard", "/base/wizard/onepageHandler.php");
$cceClient = $serverScriptHelper->getCceClient();
$i18n = $serverScriptHelper->getI18n("base-wizard");

$page = $factory->getPage();
$page->setOnLoad('top.code.flow_setPageLoaded(true);');
$form = $page->getForm();
$formId = $form->getId();
$block = $factory->getPagedBlock("onepageSettings");

$raid = $cceClient->getObject("System", array(), "RAID");
if ( $raid["configurable"] )
	$hasRAID = true;

//////////////// Network settings

$systemObj = $cceClient->getObject("System");
$networkObj = $cceClient->getObject("System", array(), "Network");

$block->addDivider($factory->getLabel("networkSettings", false));
$block->processErrors($serverScriptHelper->getErrors());

//host and domain names
if (($systemObj['hostname'] == 'localhost') &&
    ($systemObj['domainname'] == '')) {
	// assume this is first boot if domainname is not set
	$defaultHostname = '';
} else {
	$defaultHostname = $systemObj['hostname'];
}

$hostfield = $factory->getVerticalCompositeFormField(array(
			   $factory->getDomainName("hostNameField", $defaultHostname),
			   $factory->getLabel("hostNameField")));
$domainfield = $factory->getVerticalCompositeFormField(array(
			     $factory->getDomainName("domainNameField", $systemObj["domainname"]),
			     $factory->getLabel("domainNameField")));

$fqdn =& $factory->getCompositeFormField(array($hostfield, $domainfield), '&nbsp;.&nbsp;');

$block->addFormField(
    $fqdn,
    $factory->getLabel("enterFqdn")
);

$dns = $factory->getIpAddressList("dnsAddressesField", $systemObj["dns"]);
$dns->setOptional(true);
$block->addFormField(
  $dns,
  $factory->getLabel("dnsAddressesField")
);

$gw = $factory->getIpAddress("gatewayField", $systemObj["gateway"]);
$gw->setOptional(true);
$block->addFormField($gw, $factory->getLabel("gatewayField"), $default_page);


// real interfaces
// ascii sorted, this may be a problem if there are more than 10 interfaces
$interfaces = $cceClient->findx('Network', array('real' => 1), array(),
				'ascii', 'device');
$devices = array();
$deviceList = array();
$devnames = array();
$i18n = $factory->getI18n();
$admin_if = '';
for ($i = 0; $i < 1; $i++)
{
	$is_admin_if = false;
	$iface = $cceClient->get($interfaces[$i]);
	$device = $iface['device'];
	
	// save the devices and strings for javascript fun
	$deviceList[] = $device;
	$devices[] = "'$device'";    
	$devnames[] = "'" . $i18n->getJs("[[base-network.interface$device]]") . "'";

	if ($iface["enabled"]) 
	{
		$ipaddr = $iface["ipaddr"];
		$netmask = $iface["netmask"];
	} 
	else 
	{
		$ipaddr = "";
		$netmask = "";
	}

	if ($SERVER_ADDR == $ipaddr)
	{
	    $admin_if = $device;
	    $is_admin_if = true;
	}

	$block->addDivider(
	        $factory->getLabel("[[base-network.interface$device]]", false), 
	        $default_page);

	// With IP Pooling enabled, display the IP field with a 
	// range of possible choices
	list($sysoid) = $cceClient->find("System");
	$net_opts = $cceClient->get($sysoid, "Network");
	$access = $net_opts['interfaceConfigure'] ? 'rw' : 'r';
	if ($net_opts["pooling"]) {
		$range_strings = array();
		$oids = $cceClient->findx('IPPoolingRange', array(), array(), 'old_numeric', 'creation_time');
		foreach ($oids as $oid) {
			$range = $cceClient->get($oid);
			$range_strings[] = $range['min'] . ' - ' . $range['max'];
		}
		$string = arrayToString($range_strings);
		$ip = $factory->getIpAddress("ipAddressField$device", $ipaddr, $access);
		$ip->setInvalidMessage($i18n->getJs('ipAddressField_invalid'));

		if ($device != 'eth0') {
			$ip->setEmptyMessage($i18n->getJs('ipAddressField_empty', 'base-network', array('interface' => "[[base-network.interface$device]]")));
		}

		// IP is optional, if it isn't the admin iface or eth0
		if (!$is_admin_if && ($device != 'eth0'))
			$ip->setOptional(true);

		$mylabel = $factory->getLabel("[[base-network.valid_ranges]]");
		$mylabel->setDescription($factory->i18n->get('[[base-network.valid_ranges_help]]'));
		$range_list = $factory->getCompositeFormField(
			  array($mylabel,
				$factory->getTextList("valid_ranges", $string, "r")
				),
			  "&nbsp;"
			  );
		$range_list->setAlignment("TOP");
		$ip_field =& $factory->getVerticalCompositeFormField(array($ip, $range_list));
		$ip_field->setId("ipAddr");
		$ip_field->setAlignment("LEFT");

	} else {
	  
		$ip_field =& $factory->getIpAddress("ipAddressField$device", $ipaddr, $access);
		$ip_field->setInvalidMessage($i18n->getJs('ipAddressField_invalid'));
		if ($device != 'eth0') {
			$ip_field->setEmptyMessage($i18n->getJs('ipAddressField_empty', 'base-network', array('interface' => "[[base-network.interface$device]]")));
		}

		// IP not optional for the admin interface and for eth0
		if (!$is_admin_if && ($device != 'eth0'))
			$ip_field->setOptional(true);
	}

	// use special help text for eth0 and eth1 to keep the qube happy
	$ip_label = 'ipAddressField';
	$nm_label = 'netMaskField';
	if ($device == 'eth0')
	{
	    $ip_label = '[[base-network.ipAddressField1]]';
	    $nm_label = '[[base-network.netMaskField1]]';
	}
	else if ($device == 'eth1')
	{
	    $ip_label = 'ipAddressField2';
	    $nm_label = 'netMaskField2';
	}
	
	$block->addFormField(
	    $ip_field,
	    $factory->getLabel($ip_label, true, 
	                array(), array('name' => "[[base-network.help$device]]")),
	    $default_page
	);

	$netmask_field =& $factory->getIpAddress("netMaskField$device", $netmask, $access);
	$netmask_field->setInvalidMessage($i18n->getJs('netMaskField_invalid'));
	if ($device != 'eth0') {
		$netmask_field->setEmptyMessage($i18n->getJs('netMaskField_empty', 'base-network', array('interface' => "[[base-network.interface$device]]")));
	}

	// Netmask is not optional for the admin iface and for eth0
	if (!$is_admin_if && ($device != 'eth0'))
	    $netmask_field->setOptional(true);

	$block->addFormField(
	    $netmask_field,
	    $factory->getLabel($nm_label, true,
	                array(), array('name' => "[[base-network.help$device]]")),
	    $default_page
	);

	$block->addFormField(
	    $factory->getMacAddress("macAddressField$device", $iface["mac"], "r"),
	    $factory->getLabel("macAddressField"),
	    $default_page
	);

	// check for aliases, so a warning can be issued when disabling an
	// interface with aliases on it
	$aliases = $cceClient->findx('Network', array('real' => 0),
					array('device' => "^$device:"));
	if (count($aliases) > 0)
	{
		$block->addFormField(
			$factory->getBoolean("hasAliases$device", 1, ''));
	}
	else
	{
		$block->addFormField(
			$factory->getBoolean("hasAliases$device", 0, ''));
	}

	// retain orginal information
	$block->addFormField(
	    $factory->getIpAddress("ipAddressOrig$device", $ipaddr, ""), 
	    '',
	    $default_page
	    );
	$block->addFormField(
	    $factory->getIpAddress("netMaskOrig$device", $netmask, ""), 
	    "",
	    $default_page
	    );
	$block->addFormField(
	    $factory->getTextField("bootProtoField$device", $iface["bootproto"], ""),
	    "",
	    $default_page
	    );
	$block->addFormField(
	    $factory->getBoolean("enabled$device", $iface["enabled"], ""),
	    "",
	    $default_page
	    );
}

//////////////// Admin settings

$admin = $cceClient->getObject("User", array("name" => "admin"));

$block->addDivider($factory->getLabel("adminSettings", false));

$block->addFormField(
  $factory->getUserName("adminNameField", $admin["name"], "r"),
  $factory->getLabel("adminNameField")
);

$block->addFormField(
  $factory->getPassword("passwordField"),
  $factory->getLabel("passwordField")
);

//////////////// Locale settings
/*  
	I think this should probably remain the first thing a user sees in the
	setup wizard, since that way they can at least view the wizard in their
	own language rather than forcing a default. --pbaltz 4/6/2001
*/

$block->addDivider($factory->getLabel("localeSettings"));

// Installed locales:
$possibleLocales = stringToArray($systemObj["locales"]);
// $possibleLocales = array_merge("browser", $possibleLocales);

$broser_locales = array();
$browser_locales = split(',', $serverScriptHelper->getLocalePreference($HTTP_ACCEPT_LANGUAGE));
for($i = 0; $i < count($browser_locales); $i++) {

  for($j = 0; $j < count($possibleLocales); $j++) {

    if($browser_locales[$i] == $possibleLocales[$j]) {
      $locale_match = $possibleLocales[$j];
      $i = $j = 999; // last both loops
    }

  }
} 

// $locale = $factory->getLocale("languageField", $localePreference);
$locale = $factory->getLocale("languageField", $locale_match);

$locale->setPossibleLocales($possibleLocales);
$block->addFormField(
  $locale,
  $factory->getLabel("localeField")
);

//////////////// Time settings

$time = $cceClient->getObject("System", array(), "Time");

$block->addDivider($factory->getLabel("timeSettings", false));

$t = time();
$block->addFormField($factory->getTimeStamp("oldTime", $t, "date", ""));
$block->addFormField(
  $factory->getTimeStamp("dateField", $t, "datetime"),
  $factory->getLabel("dateField")
);

$block->addFormField($factory->getTimeZone("oldTimeZone", $time["timeZone"], ""));
$block->addFormField(
  $factory->getTimeZone("timeZoneField", $time["timeZone"]),
  $factory->getLabel("timeZoneField")
);


//////////////// Output the page

print($page->toHeaderHtml());

if ( $hasRAID ) { ?>
<SCRIPT LANGUAGE="javascript">
function flow_getNextItemId() {
      return "base_wizardRegistration";
}
</SCRIPT>
<?php } else { // end if hasRAID ?>

<SCRIPT LANGUAGE="javascript">
function flow_getNextItemId() {
      return "base_wizardRegistration";
}
</SCRIPT>
<?php } // end ifs ?>

<?php print($i18n->getHtml("onepageMessage")); ?>
<BR><BR>

<?php print($block->toHtml()); ?>

<?php print($page->toFooterHtml());
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
