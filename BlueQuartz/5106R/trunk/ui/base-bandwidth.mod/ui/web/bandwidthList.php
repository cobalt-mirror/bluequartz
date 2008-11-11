<?php
// Author: Joshua Uziel
// Copyright 2001, Sun Microsystems, Inc.  All rights reserved.
// $Id: bandwidthList.php,v 1.9.2.2 2002/04/16 23:15:04 uzi Exp $

include("ServerScriptHelper.php");
include("ArrayPacker.php");

$helper = new ServerScriptHelper() or die ("no SSH");
$cce = $helper->getCceClient() or die ("no CCE");
$factory = $helper->getHtmlComponentFactory(
	"base-bandwidth", "/base/bandwidth/bandwidthList.php");
$i18n = $helper->getI18n("base-bandwidth") or die ("no i18n");

$page = $factory->getPage();

// deal with remove actions
if ($_REMOVE) {
	$cce->destroy($_REMOVE);
}

// build scroll list of bandwidth limits
$scrollList = $factory->getScrollList("bwList", array("bwIpaddr", "bwLimit",
					"bwVsites", "bwAction"), array(0));
$scrollList->setAlignments(array("left", "center", "center", "center"));
$scrollList->setColumnWidths(array("25%", "20%", "35%", "10%"));
$scrollList->setEmptyMessage("[[base-bandwidth.nolimits]]");

// disable sorting
$scrollList->setSortEnabled(false);
$scrollList->setArrowVisible(true);
// find page length
$pageLength = $scrollList->getLength();

// find start point
$start = $scrollList->getPageIndex()*$pageLength;

$oids = $cce->findSorted("Bandwidth", "ipaddr");

// sort in the right direction
if($scrollList->getSortOrder() == "descending")
	$oids = array_reverse($oids);

$vsiteoids = $cce->findSorted("Vsite", "fqdn");

// count the number of items displayed
$itemcount=0;

for($i = $start; $i < sizeof($oids) && $i < $start+$pageLength; $i++) {
	$oid = $oids[$i];
	$temp = $cce->get($oid);

	$bwtext = ($temp["bwlimit"] >= 0) ? $temp["bwlimit"] : 
		$i18n->interpolate('[[base-bandwidth.none]]');
	$iptext = $temp["ipaddr"];

	if ($iptext == "") continue;

	// keep our item number, bump the counter
	$item = $itemcount++;

	$remButton = $factory->getRemoveButton("javascript: confirmRemove('$iptext', '$oid')");
	$modButton = $factory->getModifyButton("/base/bandwidth/bandwidthMod.php?_TARGET=$oid");

	$vstext = "";		// Set an initial value

	for ($j = 0; $j < sizeof($vsiteoids); $j++) {
		$vsoid = $vsiteoids[$j];
		$vstemp = $cce->get($vsoid);

		if (!strcmp($iptext, $vstemp["ipaddr"])) {
			$vstext .= $vstemp["fqdn"] . "   ";
		}
	}

 	// No Vsites affected
	if (!$vstext) $vstext = $i18n->interpolate('[[base-bandwidth.none]]');

		$scrollList->addEntry( array(
			$factory->getTextField("", $iptext, "r"),
			$factory->getTextField("", $bwtext, "r"),
			$factory->getTextField("", $vstext, "r"),
			$factory->getCompositeFormField(array($modButton, $remButton))
		), "", false, $item);
}

// set total number of entries in list
$scrollList->setEntryNum($itemcount);

$scrollList->addButton($factory->getAddButton("/base/bandwidth/bandwidthAdd.php"));

$errors = $helper->getErrors();
$scrollList->processErrors($errors);
print $page->toHeaderHtml();

$remString = $i18n->interpolateJs('[[base-bandwidth.removecheck]]');

?>

<SCRIPT LANGUAGE="javascript">
var remstr='<?php print $remString; ?>';
function confirmRemove(ipaddr, oid) {
	if(confirm(top.code.string_substitute(remstr, '[[VAR.ipaddr]]', ipaddr)))
		location = "/base/bandwidth/bandwidthList.php?_REMOVE=" + oid;
}
</SCRIPT>

<?php

print $scrollList->toHtml();
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
