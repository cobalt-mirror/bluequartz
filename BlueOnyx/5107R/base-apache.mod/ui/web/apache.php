<?php
// Authors: Kevin K.M. Chiu & Michael Stauber
// $Id: apache.php

include_once("ArrayPacker.php");
include_once("Product.php");
include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-apache");

// Only 'serverHttpd' should be here
if (!$serverScriptHelper->getAllowed('serverHttpd')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$product = new Product($cceClient);

$factory = $serverScriptHelper->getHtmlComponentFactory("base-apache", "/base/apache/apacheHandler.php");

// get web
$web = $cceClient->getObject("System", array(), "Web");

$page = $factory->getPage();

$block = $factory->getPagedBlock("apacheSettings");
$block->processErrors($serverScriptHelper->getErrors());

if(!$product->isRaq()) {

	// get frontpage
	$frontpage = $cceClient->getObject("System", array(), "Frontpage");

	// get all user names
	$users = $cceClient->getObjects("User");
	$userNames = array();
	for($i = 0; $i < count($users); $i++)
		$userNames[] = $users[$i]["name"];

	$userNamesString = arrayToString($userNames);

	// make frontpage field
	// password only needed to enable frontpage
	if(!$frontpage["enabled"]) {
  
		$enable = $factory->getOption("frontpageEnabled", $frontpage["enabled"]);
  
		$enable->addFormField(
    
			$factory->getPassword("passwordWebmasterField"),
    
			$factory->getLabel("passwordWebmasterField")
  		);
  		$frontpageEnabled = $factory->getMultiChoice("frontpageField");
  		$frontpageEnabled->addOption($enable);
	}
	else {
  		$frontpageEnabled = $factory->getBoolean("frontpageField", $frontpage["enabled"]);
	}

	$block->addFormField(
  	$frontpageEnabled,
  	$factory->getLabel("frontpageField")
	);

	// make cgi field
	$access = $web["cgiAccess"];
	$users = $web["cgiUsers"];
	$cgiSubset = $factory->getOption("cgiSubset", $access == "subset" && $users != "");
	$cgiSubset->addFormField(
  		$factory->getSetSelector("cgiUsersField", $users, $userNamesString, "cgiUsersAllowed", "cgiUsersDisallowed"),
  		$factory->getLabel("cgiUsersField")
	);
	$cgiAccess = $factory->getMultiChoice("cgiAccessField");
	$cgiAccess->addOption($factory->getOption("cgiNone", $access == "subset" && $users == ""));
	$cgiAccess->addOption($factory->getOption("cgiAll", $access == "all"));
	$cgiAccess->addOption($cgiSubset);
	$block->addFormField(
  
		$cgiAccess,
  
		$factory->getLabel("cgiAccessField")
	);
}
else {
	// Add divider:
	$block->addDivider($factory->getLabel("DIVIDER_TOP", false));

	$block->addFormField(
		$factory->getBoolean("hostnameLookupsField", $web["hostnameLookups"]),
		$factory->getLabel("hostnameLookupsField")
	);

	// HTTP Port:
	$httpPortField = $factory->getInteger("httpPortField", $web["httpPort"], "80", "65535");
	$httpPortField->setWidth(5);
	$httpPortField->showBounds(1);
	$block->addFormField(
		$httpPortField,
		$factory->getLabel("httpPortField")
	);

	// SSL Port:
	$sslPortField = $factory->getInteger("sslPortField", $web["sslPort"], "443", "65535");
	$sslPortField->setWidth(5);
	$sslPortField->showBounds(1);
	$block->addFormField(
		$sslPortField,
		$factory->getLabel("sslPortField")
	);

	$max_client = $factory->getInteger("maxClientsField", $web["maxClients"], 1, $web["maxClientsAdvised"]);
	$max_client->setWidth(5);
	$max_client->showBounds(1);

	$block->addFormField(
		$max_client,
		$factory->getLabel("maxClientsField")
	);


	$min = $factory->getInteger("minSpareField", $web["minSpare"], 1, $web["minSpareAdvised"]);
        $min->setWidth(5);
        $min->showBounds(1);

	$block->addFormField(
		$min,
		$factory->getLabel("minSpareField")
	);

	$max_spare = $factory->getInteger("maxSpareField", $web["maxSpare"], 1, $web["maxSpareAdvised"]);
	$max_spare->setWidth(5);
	$max_spare->showBounds(1);

	$block->addFormField(
		$max_spare,
		$factory->getLabel("maxSpareField")
	);

	// BlueOnyx.conf modification stuff:

	// Add divider:
	$block->addDivider($factory->getLabel("DIVIDER_EXPLANATION", false));

	$my_TEXT = $i18n->interpolate("[[base-apache.BlueOnyx_Info_Text]]");
	$block->addFormField(
	  $factory->getTextField("BlueOnyx_Info_Text", $my_TEXT, 'r'),
	  $factory->getLabel(" ")
	);

	// Add divider:
	$block->addDivider($factory->getLabel("DIVIDER_OPTIONS", false));

	$block->addFormField(
		$factory->getBoolean("Options_All", $web["Options_All"]),
		$factory->getLabel("Options_AllField")
	);
	$block->addFormField(
		$factory->getBoolean("Options_FollowSymLinks", $web["Options_FollowSymLinks"]),
		$factory->getLabel("Options_FollowSymLinksField")
	);
	$block->addFormField(
		$factory->getBoolean("Options_Includes", $web["Options_Includes"]),
		$factory->getLabel("Options_IncludesField")
	);
	$block->addFormField(
		$factory->getBoolean("Options_Indexes", $web["Options_Indexes"]),
		$factory->getLabel("Options_IndexesField")
	);
	$block->addFormField(
		$factory->getBoolean("Options_MultiViews", $web["Options_MultiViews"]),
		$factory->getLabel("Options_MultiViewsField")
	);
	$block->addFormField(
		$factory->getBoolean("Options_SymLinksIfOwnerMatch", $web["Options_SymLinksIfOwnerMatch"]),
		$factory->getLabel("Options_SymLinksIfOwnerMatchField")
	);

	// Add divider:
	$block->addDivider($factory->getLabel("DIVIDER_ALLOWOVERRIDE", false));

	$block->addFormField(
		$factory->getBoolean("AllowOverride_All", $web["AllowOverride_All"]),
		$factory->getLabel("AllowOverride_AllField")
	);
	$block->addFormField(
		$factory->getBoolean("AllowOverride_AuthConfig", $web["AllowOverride_AuthConfig"]),
		$factory->getLabel("AllowOverride_AuthConfigField")
	);
	$block->addFormField(
		$factory->getBoolean("AllowOverride_FileInfo", $web["AllowOverride_FileInfo"]),
		$factory->getLabel("AllowOverride_FileInfoField")
	);
	$block->addFormField(
		$factory->getBoolean("AllowOverride_Indexes", $web["AllowOverride_Indexes"]),
		$factory->getLabel("AllowOverride_IndexesField")
	);
	$block->addFormField(
		$factory->getBoolean("AllowOverride_Limit", $web["AllowOverride_Limit"]),
		$factory->getLabel("AllowOverride_LimitField")
	);
	$block->addFormField(
		$factory->getBoolean("AllowOverride_Options", $web["AllowOverride_Options"]),
		$factory->getLabel("AllowOverride_OptionsField")
	);
}

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($block->toHtml()); ?>

<?php print($page->toFooterHtml());
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