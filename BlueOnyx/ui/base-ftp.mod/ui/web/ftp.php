<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: ftp.php 

include_once("ServerScriptHelper.php");
include_once("Product.php");

$serverScriptHelper = new ServerScriptHelper();

// Only 'serverFTP' should be here:
if (!$serverScriptHelper->getAllowed('serverFTP')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$product = new Product($cceClient);
$factory = $serverScriptHelper->getHtmlComponentFactory("base-ftp", "/base/ftp/ftpHandler.php");

// get object
$ftp = $cceClient->getObject("System", array(), "Ftp");

$page = $factory->getPage();

$block = $factory->getPagedBlock("ftpSettings");

$block->processErrors($serverScriptHelper->getErrors());

$block->addFormField(
  $factory->getBoolean("enableServerField", $ftp["enabled"]),
  $factory->getLabel("enableServerField")
);

$block->addFormField(
  $factory->getBoolean("useReverseDNSField", $ftp["useReverseDNS"]),
  $factory->getLabel("useReverseDNSField")
);

if(!$product->isRaq())
{
	$max = $factory->getInteger("maxUserField", $ftp["maxConnections"], 1, 1024);
	$max->showBounds(1);
	$max->setWidth(5);
	$block->addFormField(
	  $max,
	  $factory->getLabel("maxUserField")
	);
}

$rate = $factory->getInteger("connectRateField", $ftp["connectRate"], 1, 1024);
$rate->showBounds(1);
$rate->setWidth(5);
$block->addFormField(
  $rate,
  $factory->getLabel("connectRateField")
);

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