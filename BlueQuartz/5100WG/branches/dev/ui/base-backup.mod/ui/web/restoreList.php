<?
// Author: Brenda Mula, Tim Hockin
// Copyright 2000 Cobalt Networks.  All rights reserved
// $Id: restoreList.php 201 2003-07-18 19:11:07Z will $

include("ServerScriptHelper.php");
include("ArrayPacker.php");
include("./parse_hist.php");

// main: Base setup
$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-backup");
$page = $factory->getPage();
$i18n = $factory->i18n;

$stylist = $serverScriptHelper->getStylist();
$style = $stylist->getStyle("BackupStatusIcons");
$restoreIcon = $style->getProperty("restoreIcon");
$restoreIconInactive = $style->getProperty("restoreIconInactive");

$restoreIcon = ($restoreIcon) ? $restoreIcon : "/libImage/restoreBackup.gif" ;
$restoreIconInactive = ($restoreIconInactive) ? $restoreIconInactive : "/libImage/restoreBackupGray.gif" ;


print($page->toHeaderHtml());

if ($restoreTo == "restoreOver") {
	print(
		"<SCRIPT LANGUAGE=\"javascript\"> \n"
		. "alert(\""
			. $i18n->getJS("restoreOverPopup", "base-backup",
				array("name" => $restoreName)) . "\"); \n"
		. "</SCRIPT> \n"
	);
} elseif ($restoreTo == "restoreTemp") {
	print(
		"<SCRIPT LANGUAGE=\"javascript\"> \n"
		. "alert(\""
			. $i18n->getJS("restoreTempPopup", "base-backup",
				array("name" => $restoreName)) . "\"); \n"
		. "</SCRIPT> \n"
	);
}

$scrollList = $factory->getScrollList("restoreListHeader", array(
	"backupName", 
	"backupDate", 
	"backupRetval",
	"listAction"), array(0, 1, 2));
$scrollList->setAlignments(array("left", "left", "left", "left"));
$scrollList->setColumnWidths(array("1", "200", "", "1%"));

// Read XML history files (admin only, for now)
$file = getHome("admin") . "/.cbackup/history";
$histlist = parse_hist($file);

$file = "/home/users/admin/.cbackup/pending";
$histlist = array_merge($histlist, parse_hist($file));

// build the ui element
for ($i = 0; $i < count($histlist); $i++) {
	$history = $histlist[$i];

	// Fixups go here
        if ($history["METHOD"] == "smb") {
                // samba needs "\" not "/"
                $history["LOCATION"] = eregi_replace("/", "\\", 
			$history["LOCATION"]);
        }

	// date backup started
	if (!$history["START_TIME"] || $history["START_TIME"] == "???") {
		$idate = $i18n->get("unknown");
	} else {
		$idate = $i18n->strftime("%c", $history["START_TIME"]);
	}
	
	// what to do for details/remove buttons
	$detailstr = "/base/backup/historyDetail.php?" . 
		"args=" . urlencode(hashToString($history));
	$rmstr = "javascript: confirmRemove('" . 
		$history["START_TIME"] . "','" .
		$history["NAME"] . "','$idate')";

	if ($history["RETURNCODE"] == 0) {
		$restorestr = "/base/backup/restore.php?" .
			"args=" . urlencode(hashToString($history));
		$iconstr = "success";
        	$iconmsg = "statusSuccess";
	} else if ($history["RETURNCODE"] == -1) {
		$iconstr = "pending";
        	$iconmsg = "statusPending";
	} else {
		$iconstr = "failure";
        	$iconmsg = "statusFailure";
	}
	$icon = $factory->getStatusSignal($iconstr);
	$iconfield = $factory->getCompositeFormField(array(
                $icon, $factory->getLabel($iconmsg)), "&nbsp;&nbsp;", "r");

	// array of knobs for the Actions field
	$knobsar = array(
		$factory->getDetailButton($detailstr)
	);
	if ($history["RETURNCODE"] == 0) {
		$knobsar[] = $factory->getImageButton($restorestr, $restoreIcon, "restoreBackup", "restoreBackup_help");
	} else {
		$knobsar[] = $factory->getImageButton($restorestr, $restoreIconInactive, "restoreBackup", "restoreBackup_help");
	}
	$knobsar[] = $factory->getRemoveButton($rmstr);

	// Backup name, date, status, Users, Actions
	$scrollList->addEntry(array(
		$factory->getTextBlock("", $history["NAME"], "r"),
		$factory->getTextBlock("", $idate, "r"),
		$iconfield,
		$factory->getCompositeFormField($knobsar)
	));
}	

$dirbutton = $factory->getButton("/base/backup/restoreFromDir.php",
	"restoreFromDir");

// Functions
function getHome($userid)
{
	$pwent = posix_getpwnam($userid);
	return $pwent[dir];
}

?>

<SCRIPT LANGUAGE="javascript">
function confirmRemove(backupTime, backupName, idate) {
  var message = "<?php print($i18n->get("removeHistoryConfirm"))?>";
  message = top.code.string_substitute(message, "[[VAR.time]]", idate);
  message = top.code.string_substitute(message, "[[VAR.name]]", backupName);

  if(confirm(message))
    location = "/base/backup/historyRemoveHandler.php"+
	"?backupTime="+backupTime+"&backupName="+backupName;
}
// show flow buttons
top.code.flow_showNavigation(true);
</SCRIPT>


<?
print($dirbutton->toHtml());
print("<br>");
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

