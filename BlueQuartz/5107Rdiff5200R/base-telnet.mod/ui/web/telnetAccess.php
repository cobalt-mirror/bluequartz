<?php
// Author: Phil Ploquin, Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: telnetAccess.php 1016 2007-06-25 15:26:35Z shibuya $

include_once("ServerScriptHelper.php");
include_once("Product.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$product = new Product($cceClient);

$factory = $serverScriptHelper->getHtmlComponentFactory("base-telnet", "/base/telnet/telnetHandler.php");

// get object
$telnetNamespace = $cceClient->getObject("System", array(), "Telnet");
$currentAccess = $telnetNamespace["access"];

$page = $factory->getPage();


if(!$product->isRaq())
{
	$block = $factory->getPagedBlock("telnetSettings");

	// make options list
	$telnetAccess = $factory->getMultiChoice ("accessField",
       	                                   array("none", "root", "reg"), array($currentAccess));

	$block->addFormField($telnetAccess,
       	              $factory->getLabel("access"));
}
else
{
	$block = $factory->getPagedBlock("telnetSettings");

	// RaQ style boolean control
	$block->addFormField(
		$factory->getBoolean("enabled", $telnetNamespace["enabled"]),
		$factory->getLabel("enableServer")
	);

	$rate = $factory->getInteger("connectRateField", $telnetNamespace["connectRate"], 1, 1024);
	$rate->showBounds(1);
	$rate->setWidth(5);
	$block->addFormField(
  		$rate,
  		$factory->getLabel("connectRateField")
	);
}

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$serverScriptHelper->destructor();

print($page->toHeaderHtml());
print($block->toHtml());
print($page->toFooterHtml());
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
