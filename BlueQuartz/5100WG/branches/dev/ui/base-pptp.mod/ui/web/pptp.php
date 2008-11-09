<?
include_once("ServerScriptHelper.php");
include_once("ArrayPacker.php");

$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-pptp", "/base/pptp/pptpHandler.php");
$cce = $serverScriptHelper->getCceClient();

$page = $factory->getPage();
$block = $factory->getPagedBlock("pptp_settings_block");

$data = $cce->getObject("System", array(), "Pptp");

/*( create a list of all the users on the system */
$userObjs = $cce->getObjects("User");
$usernames = array();
foreach($userObjs as $userObj) {
  $usernames[] = $userObj["name"];
}

$pptpAccess = $factory->getMultiChoice("pptp_access_field");
$pptpAccess->addOption($factory->getOption("pptp_access_disabled", !$data["enabled"]));
$pptpAccess->addOption($factory->getOption("pptp_access_allusers", $data["allowType"] == "all" && $data["enabled"]));
$pptpAccessSome = $factory->getOption("pptp_access_someusers", $data["allowType"] == "some" && $data["enabled"]);

$pptpAccessSomeSelector = $factory->getSetSelector(
  	"pptp_access_someusers_selector", 
  	$data["allowData"], 
	arrayToString($usernames), 
	"pptp_access_someusers_selector_allowed",
	"pptp_access_someusers_selector_disallowed"
);
$pptpAccessSomeSelector->setOptional("silent");
$pptpAccessSome->addFormField(
  $pptpAccessSomeSelector,
  $factory->getLabel("pptp_access_someusers_selector_label")
);
$pptpAccess->addOption($pptpAccessSome);

$block->addFormField(
  $pptpAccess,
  $factory->getLabel("pptp_access")
);

$dnsList = $factory->getIpAddressList("pptp_dns_addresses", $data["dns"]);
$dnsList->setOptional(true);
$block->addFormField(
  $dnsList,
  $factory->getLabel("pptp_dns_addresses")
);

$winsList = $factory->getIpAddressList("pptp_wins_addresses", $data["wins"]);
$winsList->setOptional(true);
$block->addFormField(
  $winsList,
  $factory->getLabel("pptp_wins_addresses")
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$rangeButton = $factory->getButton("/base/pptp/pptpDynamicList.php", "dynamic_ranges_button");

print $page->toHeaderHtml();
print $rangeButton->toHtml();
print "<BR>";
print $block->toHtml();
print $page->toFooterHtml();

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

