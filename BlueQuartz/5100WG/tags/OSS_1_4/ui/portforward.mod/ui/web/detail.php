<?php

// $id:$

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

$cce = $serverScriptHelper->getCceClient();

$factory = $serverScriptHelper->getHtmlComponentFactory("base-portforward", "detailHandler.php?OID=$OID");
$page = $factory->getPage();
$i18n = $factory->getI18n("base-portforward");

// Check to see if we are deleting this rule..
if ($OID && $todo == "remove") {
	$cce->destroy($OID);
	print $serverScriptHelper->toHandlerHtml("/base/portforward/list.php", $cce->errors());
	exit;
}

// Get the values to plug in:
if ($OID) {
	$values = $cce->get($OID);
} else {
	$values = array();
}

if ($DATA) {
	$values = unserialize($DATA);
}

$block = $factory->getPagedBlock($OID ? "title_modify" : "title_create");

$block->addFormField(
	$factory->getMultiChoice("protocol", array("TCP", "UDP"), array( $values["protocol"] )),
	$factory->getLabel("protocol")
);

include_once("uifc/MultiChoice.php");
include_once("uifc/Option.php");
include_once("uifc/Label.php");
$dev = new MultiChoice($page, "sourceIP");
$networkOids = $cce->findSorted("Network","device");
foreach ($networkOids as $networkOid) {
	$network = $cce->get($networkOid);
	if (!$network["enabled"]) continue;
	$device = $network["device"];
	// munge together a msgid name
	$tag = "[[base-portforward.SourceIP_" . $device . "]]";
	$isSelected = ($network["device"] == $values["sourceIP"]);
	$dev->addOption(
	  new Option(
	    new Label(	$page,
			$i18n->interpolate(
				$tag, array( "ipaddr" => $network["ipaddr"] )
			)
	    ),
	    $device, 
	    $isSelected
	  )
	);
}

// list device/ip in a dropdown
$block->addFormField(
	$dev,
	//$factory->getIpAddress("sourceIP", $values["sourceIP"]),
	$factory->getLabel("sourceIP")
);
$block->addFormField(
	$factory->getInteger("sourcePort", $values["sourcePort"], 1, 65535),
	$factory->getLabel("sourcePort")
);
$block->addFormField(
	$factory->getIpAddress("targetIP", $values["targetIP"]),
	$factory->getLabel("targetIP")
);
$block->addFormField(
	$factory->getInteger("targetPort", $values["targetPort"], 1, 65535),
	$factory->getLabel("targetPort")
);

$description = $factory->getTextBlock("description", $values["description"]);
$description->setOptional(true);
$block->addFormField(
	$description,
	$factory->getLabel("description")
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getCancelButton("/base/portforward/list.php"));

print $page->toHeaderHtml();
print $block->toHtml();
print $page->toFooterHtml();

/*
function getIpAddr ($deviceName) {
	$retval = $factory->getLabel("noIpAddr");
	$oids = $cce->find("Network");
	for ($i = 0; $i < count($oids); $i++) {
		$oid = $oids[$i];
		$obj = $cce->get($oid);
		if ($obj["device"] == $deviceName) {
			$retval = $obj["ipaddr"];
		}
	}
	return $retval
}
*/
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

