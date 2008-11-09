<?php
// Author: Joshua Uziel
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: ups.php,v 1.4.2.1 2002/01/10 01:51:53 uzi Exp $

include("ServerScriptHelper.php");
include("ArrayPacker.php");

$helper = new ServerScriptHelper();
$factory = $helper->getHtmlComponentFactory("base-ups",
					"/base/ups/upsHandler.php");
$cce = $helper->getCceClient();

// Grab our settings.
$ups = $cce->getObject("System", array(), "UPS");

$page = $factory->getPage();

$block = $factory->getPagedBlock("upsSettings");
$block->setColumnWidths(array("20%", "80%"));

// We have three "states" for the machine:
// 	disabled: do nothing regarding a UPS
// 	master  : UPS is directly connected
// 	slave   : get UPS info from a master

// Disabled is only a null field, but a state nonetheless.
$disabled = $factory->getOption("disabled");

// Master gets a list of slaves (if any) and if it should wake the slaves.
$master = $factory->getOption("master");
$master->addFormField(
	$factory->getBoolean("wakeSlavesField", $ups["wakeslaves"]),
	$factory->getLabel("wakeSlavesField")
);
$delay = $factory->getInteger("wakeDelayField", $ups["wakedelay"]);
$delay->setOptional(true);
$delay->setMin(0);
$delay->setMax(300);
$master->addFormField(
	$delay,
	$factory->getLabel("wakeDelayField")
);
$macs = $factory->getMacAddressList("macsField", $ups["macs"]);
$macs->setOptional(true);
$master->addFormField(
	$macs,
	$factory->getLabel("macsField")
);

// Slave just needs the master's IP
$slave = $factory->getOption("slave");
$masterip = $factory->getIpAddress("masteripField", $ups["masterip"]);
$masterip->setOptional("silent");
$slave->addFormField(
	$masterip,
	$factory->getLabel("masteripField")
);

// Show all of our states
$state = $factory->getMultiChoice("stateField");
$state->addOption($disabled);
$state->addOption($master);
$state->addOption($slave);

// Set to our current active state
if ($ups["state"] == "master")
	$state->setSelected(1, true);
else if ($ups["state"] == "slave")
	$state->setSelected(2, true);
else
	$state->setSelected(0, true);

$block->addFormField(
	$state,
	$factory->getLabel("stateField")
);

// The save button
$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$errors = $helper->getErrors();
$block->processErrors($errors);     

print $page->toHeaderHtml();
print $block->toHtml();
print $page->toFooterHtml();

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
