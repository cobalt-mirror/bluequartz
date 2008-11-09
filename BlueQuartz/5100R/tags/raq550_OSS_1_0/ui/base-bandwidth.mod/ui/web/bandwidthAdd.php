<?php
// Author: Joshua Uziel
// Copyright 2001, Sun Microsystems, Inc.  All rights reserved.
// $Id: bandwidthAdd.php,v 1.2 2001/12/20 02:23:15 uzi Exp $

include("ServerScriptHelper.php");
include("Product.php");

$helper = new ServerScriptHelper();
$cce = $helper->getCceClient();
$product = new Product($cce);
$factory = $helper->getHtmlComponentFactory("base-bandwidth", "/base/bandwidth/bandwidthHandler.php");
$i18n = $helper->getI18n("base-bandwidth");

$block = $factory->getPagedBlock("bandwidthSettings");

$page = $factory->getPage();

$netobj = $cce->findSorted("Network", "ipaddr");
$bwobj  = $cce->findSorted("Bandwidth", "ipaddr");

// Get the IP addresses for the network objects.
$netip = array();
for ($i=0; $i<sizeof($netobj); $i++) {
	$obj = $cce->get($netobj[$i]);
	if ($obj["ipaddr"] && $obj["enabled"]) {
		array_push($netip, $obj["ipaddr"]);
	}
}

// Get the IP addresses for already configured bandwidth objects.
$bwip = array();
for ($i=0; $i<sizeof($bwobj); $i++) {
	$obj = $cce->get($bwobj[$i]);
	if ($obj["ipaddr"]) {
		array_push($bwip, $obj["ipaddr"]);
	}
}

// Differenciate the network IPs from the bandwidth IPs.  We pass it
// through array_values() because array_diff preserves the keys.
$ipaddr = array_values(array_diff($netip, $bwip));

$ipselect = $factory->getMultiChoice("ipAddressField", $ipaddr);
$ipselect->setSelected(0, true);

$block->addFormField( $ipselect, $factory->getLabel("ipAddressField"));

$block->addFormField(
	$factory->getInteger("bwLimitField"),
	$factory->getLabel("bwLimitField")
);

$errors = $helper->getErrors();
$block->processErrors($errors);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getCancelButton("/base/bandwidth/bandwidthList.php"));

if (!sizeof($ipaddr)) {
	$errors = array(new Error('[[base-bandwidth.allLimited]]'));
	print $helper->toHandlerHtml("/base/bandwidth/bandwidthList.php", $errors);
} else {
	print($page->toHeaderHtml());
	print($block->toHtml());
	print($page->toFooterHtml());
}

$helper->destructor();
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
