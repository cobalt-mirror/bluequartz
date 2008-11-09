<?php
// Author: Patrick Bose
// Copyright 2001, Cobalt Networks.  All rights reserved.
// $Id: onepage.php 259 2004-01-03 06:28:40Z shibuya $
//
// This page duplicates some stuff from a qube setup, but
// the idea is that for raqs we want an express, one-page setup...
// or at least as close as we can get.

include("ServerScriptHelper.php");
include("ArrayPacker.php");

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

$fqdn =& $factory->getCompositeFormField(array($hostfield, $domainfield), '&nbsp.&nbsp');

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
*/
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
      return "base_wizardRaid";
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
