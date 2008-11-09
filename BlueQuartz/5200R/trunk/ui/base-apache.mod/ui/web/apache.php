<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: apache.php 1007 2007-06-25 15:22:40Z shibuya $

include_once("ArrayPacker.php");
include_once("Product.php");
include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

// Only adminUser should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
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

if(!$product->isRaq())
{
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
	if(!$frontpage["enabled"]) 
	{
  
		$enable = $factory->getOption("frontpageEnabled", $frontpage["enabled"]);
  
		$enable->addFormField(
    
			$factory->getPassword("passwordWebmasterField"),
    
			$factory->getLabel("passwordWebmasterField")
  		);
  		$frontpageEnabled = $factory->getMultiChoice("frontpageField");
  		$frontpageEnabled->addOption($enable);
	}
	else
	{
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
else
{

	$block->addFormField(
		$factory->getBoolean("hostnameLookupsField", $web["hostnameLookups"]),
		$factory->getLabel("hostnameLookupsField")
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
		// $factory->getInteger("minSpareField", $web["minSpare"], 1, $web["minSpareAdvised"]),


	$max_spare = $factory->getInteger("maxSpareField", $web["maxSpare"], 1, $web["maxSpareAdvised"]);
	$max_spare->setWidth(5);
	$max_spare->showBounds(1);

	$block->addFormField(
		$max_spare,
		$factory->getLabel("maxSpareField")
	);

}

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

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
