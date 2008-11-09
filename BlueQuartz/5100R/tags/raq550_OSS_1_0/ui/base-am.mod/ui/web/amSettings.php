<?php
// Author: Phil Ploquin, Tim Hockin
// Copyright 2000, Cobalt Networks.  All rights reserved.
// This is the Active Monitor settings config page

include("ArrayPacker.php");
include("ServerScriptHelper.php");

$servhelp = new ServerScriptHelper();
$cce = $servhelp->getCceClient();
$factory = $servhelp->getHtmlComponentFactory("base-am", 
	"/base/am/amSettingsHandler.php");
$page = $factory->getPage();
$i18n = $factory->i18n;
$errors = $servhelp->getErrors();

print($page->toHeaderHtml());

$amobj = $cce->getObject("ActiveMonitor");
if ($amobj == null)
{
	$msg = $factory->getTextBlock("", 
		$i18n->interpolate("[[base-am.amObjNotFound]]"), "r");
	print($msg->toHtml());
} else {
	$pagedBlock = $factory->getPagedBlock("amSettings");

	$pagedBlock->processErrors($errors, array( "enabled" => "enableAMField"));
	
	// enabled checkbox
	$pagedBlock->addFormField(
		$factory->getBoolean("enableAMField", $amobj["enabled"]),
		$factory->getLabel("enableAMField"));
	
	// alert list
	$alerts = $factory->getEmailAddressList("alertEmailList", 
		$amobj["alertEmailList"]);
	$alerts->setOptional(true);

	$pagedBlock->addFormField(
		$alerts,
		$factory->getLabel("alertEmailList"));

	$selected = array();
	$selectedVals = array();
	$notSelected = array();
	$notSelectedVals = array();

	$names = $cce->names($amobj["OID"]);
	$namespaces = array();

	for ($i=0; $i < count($names); ++$i) {
		$nspace = $cce->get($amobj["OID"], $names[$i]);
		$name = $i18n->get($nspace["nameTag"]);
		$namespaces[$name] = $nspace;
	}

	// sort by i18n'ed strings
	ksort($namespaces);
	
	while (list($name, $nspace) = each($namespaces)) {
		if (!$nspace["hideUI"]) {
			if ($nspace["monitor"]) {
				$selected[] = $name;
				$selectedVals[] = $nspace["NAMESPACE"];
			} else {
				$notSelected[] = $name;
				$notSelectedVals[] = $nspace["NAMESPACE"];
			}
		}
	}
	// $selected, $unselected = UI strings, already interpolated
	// selectedvals, unselectedvals = namespace tags
	$picklist = $factory->getSetSelector("itemsToMonitor",
		arrayToString($selected), arrayToString($notSelected), 
		"selected", "notSelected","rw",arrayToString($selectedVals),
		arrayToString($notSelectedVals));
	$picklist->setOptional(true);

	$pagedBlock->addFormField($picklist, 
		$factory->getLabel("itemsToMonitor"));

	$pagedBlock->addButton($factory->getSaveButton(
		$page->getSubmitAction()));
	print($pagedBlock->toHtml());
}

print($page->toFooterHtml());

$servhelp->destructor();
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
