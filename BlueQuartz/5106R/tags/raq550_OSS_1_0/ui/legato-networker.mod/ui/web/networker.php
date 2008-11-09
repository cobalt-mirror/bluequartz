<?php
// Author: Eric Braswell, ebraswell@cobalt, Kevin Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: networker.php,v 1.16.2.1 2002/03/25 22:48:34 bservies Exp $

include("ServerScriptHelper.php");
include("User.php");
include("Meta.php");

$serverScriptHelper = new ServerScriptHelper();
$product = $serverScriptHelper->getProductCode();
$isMonterey = ereg("35[0-9][0-9]R", $product);

if ( $isMonterey && ! user_authok( $PHP_AUTH_USER, "admin")) {
	$serverScriptHelper->destructor();
	header("location: /.cobalt/error/forbidden.html");
	return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("legato-networker", "/legato/networker/networkerHandler.php");

// get settings
$systemObj = $cceClient->getObject("System",array(),"NetWorker");

$page = $factory->getPage();

$block = $factory->getPagedBlock("networkerSettings");  // ID of settings
$block->processErrors($serverScriptHelper->getErrors());

// make IP address
//$ipAddr =$factory->getNetAddressList("lgNetWorkerIPField",$systemObj["lgNetWorkerIP"]);

if (!$isMonterey) {
  // make host name
  $hostName = $factory->getNetAddressList("lgNetWorkerHostField",$systemObj["lgNetWorkerHost"]);	
  $hostName->setOptional("silent"); 
  // make option
  $option = $factory->getOption("enableServer", $systemObj["enabled"]);

  // get port range fields
  $servRange = $factory->getIntRange(
			"servPorts", 
			"$systemObj[servicePortLow]:$systemObj[servicePortHigh]"
			);

  $connRange = $factory->getIntRange(
			"connPorts",
			"$systemObj[connPortLow]:$systemObj[connPortHigh]"
			);

  // make multi choice
  $multiChoice = $factory->getMultiChoice("enableServer");
  
  // add host name to option
  $option->addFormField($hostName, $factory->getLabel("lgNetWorkerHostField"));
  
  // add option to multi choice
  $multiChoice->addOption($option);
  
  // add multi choice to block
  $block->addFormField($multiChoice,  $factory->getLabel("enableServer"));
  
  // add port configs to block
  $block->addFormField($servRange, $factory->getLabel("servPorts"));
  $block->addFormField($connRange, $factory->getLabel("connPorts"));
  
  $block->addButton($factory->getSaveButton($page->getSubmitAction()));

} else {
  // make host name
  $hostName = $factory->getNetAddressList("lgNetWorkerHostField",$systemObj["lgNetWorkerHost"]);	
  $hostName->setOptional("silent"); 
  
  // make enableButton
  $enableServer = $factory->getBoolean("enableServer", $systemObj["enabled"]);
  
  // get port range fields
  $servRange = $factory->getIntRange(
			"servPorts", 
			"$systemObj[servicePortLow]:$systemObj[servicePortHigh]"
			);

  $connRange = $factory->getIntRange(
			"connPorts",
			"$systemObj[connPortLow]:$systemObj[connPortHigh]"
			);

  // add enable to block
  $block->addFormField($enableServer,  $factory->getLabel("enableServer"));
  
  // add host name to block
  $block->addFormField($hostName, $factory->getLabel("lgNetWorkerHostField"));

  $block->addFormField($servRange, $factory->getLabel("servPorts"));
  $block->addFormField($connRange, $factory->getLabel("connPorts"));

  $block->addButton($factory->getSaveButton($page->getSubmitAction()));
}  

$block->process_errors($serverScriptHelper->getErrors());

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
