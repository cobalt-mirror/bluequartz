<?
// Author: Jeff Lovell, Tim Hockin
// Copyright 2000 Cobalt Networks.  All rights reserved

include("ServerScriptHelper.php");

// Base setup
$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-backup");
$page = $factory->getPage();
$i18n = $factory->i18n;

print($page->toHeaderHtml());

if ($didImmediate) {
	print(
		"<SCRIPT LANGUAGE=\"javascript\"> \n"
		. "alert(\"" 
			. $i18n->getJS("immediatePopup", "base-backup", 
				array("name" => $didImmediate)) . "\"); \n" 
		. "</SCRIPT> \n"
	);
}

$scrollList = $factory->getScrollList("scheduleListHeader", array(
	"backupName",
	"backupInterval", 
	"listAction"), 
	array(0, 1)
);
$scrollList->setAlignments(array("left", "left", "left"));
$scrollList->setColumnWidths(array("", "", "1%"));

// for each Backup object
$oids = $cceClient->find("Backup");
for ($i = 0; $i < count($oids); $i++) {
	$backup = $cceClient->get($oids[$i], "");
   
	// Backup Immediate is special, don't show
	//if ($backup["backupSchedule"] == "backupImmediate") {
	//	continue;
	//}

	$backupName = $backup["backupTag"];
	
	$knobs = $factory->getCompositeFormField(array(
	    $factory->getDetailButton(
	    	"/base/backup/scheduleDetail.php?oid=" . $backup["OID"]),
            $factory->getRemoveButton(
		"javascript: confirmRemove('$backupName','" 
		. $backup["OID"] . "')")
	));

	// add each item to the list
	$scrollList->addEntry(array(
		$factory->getTextField("", $backupName, "r"),
		$factory->getLabel($backup["backupSchedule"], "false"),
		$knobs
	));
}

$scrollList->addButton($factory->getAddButton("/base/backup/backupAdd.php"));
?>

<SCRIPT LANGUAGE="javascript">
function confirmRemove(backupName, oid) {
  var msg = "<?php print($i18n->get("removeScheduleConfirm"))?>";
  msg = top.code.string_substitute(msg, "[[VAR.backupName]]", backupName);

  if(confirm(msg))
    location = "/base/backup/scheduleRemoveHandler.php?oid="+oid;
}
// show flow buttons
top.code.flow_showNavigation(true);
</SCRIPT>

<? 
print($scrollList->toHtml());
print($page->toFooterHtml()); 

$serverScriptHelper->destructor();

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

